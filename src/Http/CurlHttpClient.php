<?php

namespace App\Component\Max\Http;

use App\Component\Max\Contracts\ConfigInterface;
use App\Component\Max\Contracts\HttpClientInterface;
use App\Component\Max\Contracts\LoggerInterface;
use App\Component\Max\Exception\MaxConnectionException;
use App\Component\Max\Utils\InputValidator;

/**
 * Standalone HTTP-транспорт для MAX Bot API на базе ext-curl.
 *
 * Полностью заменяет зависимость от BaseUtils (App\Component\Http).
 * Поддерживает: GET/POST/PUT/PATCH/DELETE, JSON body, multipart upload,
 * произвольные headers, timeout, SSL verify.
 *
 * @since 1.0.0
 */
final class CurlHttpClient implements HttpClientInterface
{
    /** @var string Базовый URL MAX Bot API. */
    const BASE_URL = 'https://platform-api.max.ru';

    /** @var ConfigInterface Конфигурация SDK. */
    private $config;

    /** @var LoggerInterface|null Логгер. */
    private $logger;

    /** @var int Код последнего HTTP-ответа. */
    private $lastStatusCode = 0;

    /** @var string[] Пути временных файлов для очистки после запроса. */
    private $tempFiles = array();

    /** @var string Базовый URL. */
    private $baseUrl;

    /**
     * @param ConfigInterface      $config Конфигурация.
     * @param LoggerInterface|null $logger Логгер.
     * @param string|null          $baseUrl Базовый URL (для тестов).
     */
    public function __construct(ConfigInterface $config, LoggerInterface $logger = null, $baseUrl = null)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->baseUrl = $baseUrl !== null ? rtrim($baseUrl, '/') : self::BASE_URL;
    }

    /**
     * {@inheritdoc}
     *
     * @throws MaxConnectionException
     */
    public function request($method, $url, array $options = array())
    {
        // Очищаем список temp-файлов от предыдущего запроса
        $this->tempFiles = array();

        // Формируем полный URL
        $fullUrl = $this->buildUrl($url, $options);

        $ch = curl_init();
        if ($ch === false) {
            throw new MaxConnectionException('Не удалось инициализировать cURL.');
        }

        try {
            $this->configureCurl($ch, $method, $fullUrl, $options);

            $responseBody = curl_exec($ch);

            if ($responseBody === false) {
                $error = curl_error($ch);
                $errno = curl_errno($ch);
                throw new MaxConnectionException(
                    sprintf('Ошибка cURL [%s %s]: %s (код %d)', $method, $url, $error, $errno),
                    $errno
                );
            }

            $this->lastStatusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

            $this->logRequest($method, $url, $this->lastStatusCode);

            return array(
                'status_code' => $this->lastStatusCode,
                'body'        => (string) $responseBody,
            );
        } finally {
            curl_close($ch);
            $this->cleanupTempFiles();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getLastStatusCode()
    {
        return $this->lastStatusCode;
    }

    /**
     * {@inheritdoc}
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Формирует полный URL с query-параметрами.
     *
     * @param string $url     Путь или полный URL.
     * @param array  $options Опции запроса.
     * @return string
     */
    private function buildUrl($url, array $options)
    {
        // Если URL уже абсолютный — не добавляем base
        if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
            $fullUrl = $url;
        } else {
            $fullUrl = $this->baseUrl . '/' . ltrim($url, '/');
        }

        // Добавляем query-параметры
        if (isset($options['query']) && is_array($options['query']) && !empty($options['query'])) {
            $separator = (strpos($fullUrl, '?') !== false) ? '&' : '?';
            $fullUrl .= $separator . http_build_query($options['query']);
        }

        return $fullUrl;
    }

    /**
     * Настраивает cURL handle.
     *
     * @param resource $ch      cURL handle.
     * @param string   $method  HTTP-метод.
     * @param string   $fullUrl Полный URL.
     * @param array    $options Опции запроса.
     * @return void
     */
    private function configureCurl($ch, $method, $fullUrl, array $options)
    {
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->config->getTimeout());
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min($this->config->getTimeout(), 10));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->config->getVerifySsl());
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->config->getVerifySsl() ? 2 : 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

        // HTTP-метод
        $this->setMethod($ch, $method);

        // Headers
        $headers = $this->buildHeaders($method, $options);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Body
        $this->setBody($ch, $method, $options);
    }

    /**
     * Устанавливает HTTP-метод.
     *
     * @param resource $ch     cURL handle.
     * @param string   $method HTTP-метод.
     * @return void
     */
    private function setMethod($ch, $method)
    {
        $method = strtoupper($method);
        switch ($method) {
            case 'GET':
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                break;
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                break;
            case 'PATCH':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            default:
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                break;
        }
    }

    /**
     * Формирует заголовки запроса.
     *
     * @param string $method  HTTP-метод.
     * @param array  $options Опции запроса.
     * @return array Массив заголовков в формате 'Key: Value'.
     */
    private function buildHeaders($method, array $options)
    {
        $headers = array(
            'Authorization: ' . $this->config->getToken(),
        );

        // Пользовательские заголовки
        if (isset($options['headers']) && is_array($options['headers'])) {
            foreach ($options['headers'] as $name => $value) {
                if (strtolower($name) !== 'authorization') {
                    $headers[] = $name . ': ' . $value;
                }
            }
        }

        // Content-Type для JSON body
        if (isset($options['json']) && !isset($options['multipart'])) {
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Accept: application/json';
        } elseif (!isset($options['multipart']) && $method !== 'GET') {
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Accept: application/json';
        } else {
            $headers[] = 'Accept: application/json';
        }

        return $headers;
    }

    /**
     * Устанавливает тело запроса.
     *
     * @param resource $ch      cURL handle.
     * @param string   $method  HTTP-метод.
     * @param array    $options Опции запроса.
     * @return void
     */
    private function setBody($ch, $method, array $options)
    {
        if (isset($options['multipart']) && is_array($options['multipart'])) {
            $postFields = $this->buildMultipart($options['multipart']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        } elseif (isset($options['json'])) {
            $json = json_encode($options['json'], JSON_UNESCAPED_UNICODE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        } elseif ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
            // Пустое тело для POST/PUT/PATCH без данных
            curl_setopt($ch, CURLOPT_POSTFIELDS, '');
        }
    }

    /**
     * Собирает multipart данные для cURL.
     *
     * @param array $parts Части multipart запроса.
     * @return array Массив для CURLOPT_POSTFIELDS.
     */
    private function buildMultipart(array $parts)
    {
        $postFields = array();

        foreach ($parts as $part) {
            $name = isset($part['name']) ? $part['name'] : 'file';
            $filename = isset($part['filename']) ? $part['filename'] : null;

            if (isset($part['filepath']) && is_file($part['filepath'])) {
                // Загрузка файла через CURLFile (стриминг, без загрузки всего в память)
                if (class_exists('CURLFile')) {
                    $mimeType = isset($part['mime_type']) ? $part['mime_type'] : 'application/octet-stream';
                    $postFields[$name] = new \CURLFile($part['filepath'], $mimeType, $filename);
                } else {
                    // Fallback для PHP < 5.5 (не наш случай, но на всякий)
                    $postFields[$name] = '@' . $part['filepath'];
                }
            } elseif (isset($part['contents'])) {
                // Используем содержимое напрямую
                if ($filename !== null && class_exists('CURLFile')) {
                    // Создаём временный файл для передачи через CURLFile
                    $tmpFile = tempnam(sys_get_temp_dir(), 'max_upload_');
                    file_put_contents($tmpFile, $part['contents']);
                    $this->tempFiles[] = $tmpFile;
                    $mimeType = isset($part['mime_type']) ? $part['mime_type'] : 'application/octet-stream';
                    $postFields[$name] = new \CURLFile($tmpFile, $mimeType, $filename);
                } else {
                    $postFields[$name] = $part['contents'];
                }
            }
        }

        return $postFields;
    }

    /**
     * Удаляет временные файлы, созданные для multipart-загрузки.
     *
     * @return void
     */
    private function cleanupTempFiles()
    {
        foreach ($this->tempFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        $this->tempFiles = array();
    }

    /**
     * Логирует HTTP-запрос.
     *
     * @param string $method     HTTP-метод.
     * @param string $url        URL.
     * @param int    $statusCode Код ответа.
     * @return void
     */
    private function logRequest($method, $url, $statusCode)
    {
        if ($this->logger === null || !$this->config->getLogRequests()) {
            return;
        }

        $maskedToken = InputValidator::maskToken($this->config->getToken());
        $this->logger->debug($this->config->getAppName() . ': HTTP запрос', array(
            'method'      => $method,
            'url'         => $url,
            'status_code' => $statusCode,
            'token'       => $maskedToken,
        ));
    }
}
