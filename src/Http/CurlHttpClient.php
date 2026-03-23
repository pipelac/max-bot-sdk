<?php

declare(strict_types=1);

namespace MaxBotSdk\Http;

use CURLFile;
use CurlHandle;
use MaxBotSdk\Contracts\ConfigInterface;
use MaxBotSdk\Contracts\HttpClientInterface;
use MaxBotSdk\Contracts\LoggerInterface;
use MaxBotSdk\Exception\MaxConnectionException;
use MaxBotSdk\Utils\InputValidator;

/**
 * HTTP-транспорт для MAX Bot API на базе ext-curl.
 *
 * @since 1.0.0
 */
final class CurlHttpClient implements HttpClientInterface
{
    public const SDK_VERSION = '2.0.0';
    public const BASE_URL = 'https://platform-api.max.ru';

    private int $lastStatusCode = 0;
    /** @var list<string> */
    private array $tempFiles = [];
    private readonly string $baseUrl;

    public function __construct(
        private readonly ConfigInterface $config,
        private readonly ?LoggerInterface $logger = null,
        ?string $baseUrl = null,
    ) {
        $this->baseUrl = $baseUrl !== null ? rtrim($baseUrl, '/') : self::BASE_URL;
    }

    /**
     * @param array<string, mixed> $options
     * @return array{status_code: int, body: string}
     * @throws MaxConnectionException
     */
    public function request(string $method, string $url, array $options = []): array
    {
        $this->tempFiles = [];
        $fullUrl = $this->buildUrl($url, $options);

        $ch = curl_init();
        if (!$ch instanceof CurlHandle) {
            throw new MaxConnectionException('Не удалось инициализировать cURL.');
        }

        try {
            $this->configureCurl($ch, $method, $fullUrl, $options);

            $responseBody = curl_exec($ch);

            if ($responseBody === false) {
                $error = curl_error($ch);
                $errno = curl_errno($ch);
                throw new MaxConnectionException(
                    \sprintf('Ошибка cURL [%s %s]: %s (код %d)', $method, $url, $error, $errno),
                    $errno,
                );
            }

            $this->lastStatusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $this->logRequest($method, $url, $this->lastStatusCode);

            return [
                'status_code' => $this->lastStatusCode,
                'body'        => (string) $responseBody,
            ];
        } finally {
            curl_close($ch);
            $this->cleanupTempFiles();
        }
    }

    public function getLastStatusCode(): int
    {
        return $this->lastStatusCode;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function buildUrl(string $url, array $options): string
    {
        $fullUrl = str_starts_with($url, 'http://') || str_starts_with($url, 'https://')
            ? $url
            : $this->baseUrl . '/' . ltrim($url, '/');

        if (isset($options['query']) && \is_array($options['query']) && $options['query'] !== []) {
            $separator = str_contains($fullUrl, '?') ? '&' : '?';
            $fullUrl .= $separator . http_build_query($options['query']);
        }

        return $fullUrl;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function configureCurl(CurlHandle $ch, string $method, string $fullUrl, array $options): void
    {
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->config->getTimeout());
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min($this->config->getTimeout(), 10));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->config->getVerifySsl());
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->config->getVerifySsl() ? 2 : 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

        $this->setMethod($ch, $method);

        $headers = $this->buildHeaders($method, $options);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $this->setBody($ch, $method, $options);
    }

    private function setMethod(CurlHandle $ch, string $method): void
    {
        $upper = strtoupper($method);
        match ($upper) {
            'GET'  => curl_setopt($ch, CURLOPT_HTTPGET, true),
            'POST' => curl_setopt($ch, CURLOPT_POST, true),
            default => curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $upper),
        };
    }

    /**
     * @param array<string, mixed> $options
     * @return list<string>
     */
    private function buildHeaders(string $method, array $options): array
    {
        /** @var array{version: string} $curlVersion */
        $curlVersion = curl_version();
        $headers = [
            'Authorization: ' . $this->config->getToken(),
            'User-Agent: MaxBotSDK/' . self::SDK_VERSION . ' PHP/' . PHP_VERSION . ' cURL/' . $curlVersion['version'],
        ];

        if (isset($options['headers']) && \is_array($options['headers'])) {
            foreach ($options['headers'] as $name => $value) {
                if (strtolower((string) $name) !== 'authorization') {
                    $headers[] = $name . ': ' . $value;
                }
            }
        }

        $hasMultipart = isset($options['multipart']);
        if (!$hasMultipart) {
            if ($method !== 'GET') {
                $headers[] = 'Content-Type: application/json';
            }
        }
        $headers[] = 'Accept: application/json';

        return $headers;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function setBody(CurlHandle $ch, string $method, array $options): void
    {
        if (isset($options['multipart']) && \is_array($options['multipart'])) {
            $postFields = $this->buildMultipart($options['multipart']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        } elseif (isset($options['json'])) {
            $json = json_encode($options['json'], JSON_UNESCAPED_UNICODE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        } elseif ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, '');
        }
    }

    /**
     * @param list<array<string, mixed>> $parts
     * @return array<string, CURLFile|string>
     */
    private function buildMultipart(array $parts): array
    {
        $postFields = [];

        foreach ($parts as $part) {
            $name = isset($part['name']) && \is_string($part['name']) ? $part['name'] : 'file';
            $filename = isset($part['filename']) && \is_string($part['filename']) ? $part['filename'] : null;

            if (isset($part['filepath']) && \is_string($part['filepath']) && is_file($part['filepath'])) {
                $mimeType = isset($part['mime_type']) && \is_string($part['mime_type']) ? $part['mime_type'] : 'application/octet-stream';
                $postFields[$name] = new CURLFile($part['filepath'], $mimeType, $filename ?? '');
            } elseif (isset($part['contents'])) {
                if ($filename !== null) {
                    $tmpFile = tempnam(sys_get_temp_dir(), 'max_upload_');
                    if ($tmpFile !== false) {
                        file_put_contents($tmpFile, $part['contents']);
                        $this->tempFiles[] = $tmpFile;
                        $mimeType = isset($part['mime_type']) && \is_string($part['mime_type']) ? $part['mime_type'] : 'application/octet-stream';
                        $postFields[$name] = new CURLFile($tmpFile, $mimeType, $filename);
                    }
                } else {
                    $postFields[$name] = \is_scalar($part['contents']) ? (string) $part['contents'] : '';
                }
            }
        }

        return $postFields;
    }

    private function cleanupTempFiles(): void
    {
        foreach ($this->tempFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        $this->tempFiles = [];
    }

    private function logRequest(string $method, string $url, int $statusCode): void
    {
        if ($this->logger === null || !$this->config->getLogRequests()) {
            return;
        }

        $maskedToken = InputValidator::maskToken($this->config->getToken());
        $this->logger->debug($this->config->getAppName() . ': HTTP запрос', [
            'method'      => $method,
            'url'         => $url,
            'status_code' => $statusCode,
            'token'       => $maskedToken,
        ]);
    }
}
