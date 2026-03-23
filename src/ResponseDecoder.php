<?php

declare(strict_types=1);

namespace MaxBotSdk;

use MaxBotSdk\Contracts\LoggerInterface;
use MaxBotSdk\Contracts\ResponseDecoderInterface;
use MaxBotSdk\Exception\MaxApiException;

/**
 * Декодер ответов MAX Bot API.
 *
 * @since 1.0.0
 */
final class ResponseDecoder implements ResponseDecoderInterface
{
    /** @var array<int, string> */
    private const ERROR_MESSAGES = [
        400 => 'Недействительный запрос',
        401 => 'Ошибка аутентификации (неверный токен)',
        404 => 'Ресурс не найден',
        405 => 'Метод не допускается',
        429 => 'Превышено количество запросов (rate limit)',
        503 => 'Сервис недоступен',
    ];

    public function __construct(
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     * @throws MaxApiException
     */
    public function decode(int $statusCode, string $body, string $method = '', string $endpoint = ''): array
    {
        if ($statusCode >= 400) {
            $this->handleError($statusCode, $body, $method, $endpoint);
        }

        if ($statusCode === 204 || \trim($body) === '') {
            return [];
        }

        $decoded = \json_decode($body, true);
        if ($decoded === null && \json_last_error() !== JSON_ERROR_NONE) {
            $this->logger?->error('MaxBot: Некорректный JSON в ответе', [
                'method'   => $method,
                'endpoint' => $endpoint,
                'status'   => $statusCode,
            ]);
            throw new MaxApiException(
                \sprintf('Некорректный JSON [%s %s]', $method, $endpoint),
                $statusCode,
            );
        }

        return \is_array($decoded) ? $decoded : [];
    }

    /**
     * @throws MaxApiException
     */
    private function handleError(int $statusCode, string $body, string $method, string $endpoint): never
    {
        $description = null;
        $errorCode = null;

        $decoded = \json_decode($body, true);
        if (\is_array($decoded)) {
            $description = isset($decoded['message']) ? (string) $decoded['message'] : null;
            $errorCode = isset($decoded['code']) ? (string) $decoded['code'] : null;
        }

        $errorMsg = self::ERROR_MESSAGES[$statusCode] ?? 'HTTP ошибка';

        if ($description !== null) {
            $errorMsg .= ': ' . $description;
        }

        $this->logger?->error('MaxBot: API вернул ошибку', [
            'method'      => $method,
            'endpoint'    => $endpoint,
            'status_code' => $statusCode,
            'description' => $description,
            'error_code'  => $errorCode,
        ]);

        throw new MaxApiException(
            \sprintf('%s [%s %s, код: %d]', $errorMsg, $method, $endpoint, $statusCode),
            $statusCode,
            $description,
            $errorCode,
        );
    }
}
