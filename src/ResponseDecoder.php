<?php

namespace MaxBotSdk;

use MaxBotSdk\Contracts\LoggerInterface;
use MaxBotSdk\Contracts\ResponseDecoderInterface;
use MaxBotSdk\Exception\MaxApiException;

/**
 * Декодер ответов MAX Bot API.
 *
 * Обрабатывает JSON-ответы, определяет ошибки API
 * и преобразует их в исключения.
 *
 * @since 1.0.0
 */
final class ResponseDecoder implements ResponseDecoderInterface
{
    /**
     * @var array Маппинг HTTP-кодов на описания ошибок.
     */
    private static $errorMessages = [
        400 => 'Недействительный запрос',
        401 => 'Ошибка аутентификации (неверный токен)',
        404 => 'Ресурс не найден',
        405 => 'Метод не допускается',
        429 => 'Превышено количество запросов (rate limit)',
        503 => 'Сервис недоступен',
    ];

    /** @var LoggerInterface|null */
    private $logger;

    /**
     * @param LoggerInterface|null $logger Логгер.
     */
    public function __construct($logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Декодирует ответ API.
     *
     * @param int    $statusCode HTTP-код ответа.
     * @param string $body       Тело ответа.
     * @param string $method     HTTP-метод запроса (для ошибок).
     * @param string $endpoint   Эндпоинт запроса (для ошибок).
     * @return array Декодированный ответ.
     * @throws MaxApiException
     */
    public function decode($statusCode, $body, $method = '', $endpoint = '')
    {
        if ($statusCode >= 400) {
            $this->handleError($statusCode, $body, $method, $endpoint);
        }

        // HTTP 204 No Content — пустой успешный ответ
        if ($statusCode === 204 || trim($body) === '') {
            return [];
        }

        $decoded = json_decode($body, true);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            if ($this->logger !== null) {
                $this->logger->error('MaxBot: Некорректный JSON в ответе', [
                    'method'   => $method,
                    'endpoint' => $endpoint,
                    'status'   => $statusCode,
                ]);
            }
            throw new MaxApiException(
                sprintf('Некорректный JSON [%s %s]', $method, $endpoint),
                $statusCode
            );
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Обрабатывает HTTP-ошибку.
     *
     * @param int    $statusCode
     * @param string $body
     * @param string $method
     * @param string $endpoint
     * @throws MaxApiException
     */
    private function handleError($statusCode, $body, $method, $endpoint)
    {
        $description = null;
        $errorCode = null;

        $decoded = json_decode($body, true);
        if (is_array($decoded)) {
            $description = isset($decoded['message']) ? $decoded['message'] : null;
            $errorCode = isset($decoded['code']) ? $decoded['code'] : null;
        }

        $errorMsg = isset(self::$errorMessages[$statusCode])
            ? self::$errorMessages[$statusCode]
            : 'HTTP ошибка';

        if ($description !== null) {
            $errorMsg .= ': ' . $description;
        }

        if ($this->logger !== null) {
            $this->logger->error('MaxBot: API вернул ошибку', [
                'method'      => $method,
                'endpoint'    => $endpoint,
                'status_code' => $statusCode,
                'description' => $description,
                'error_code'  => $errorCode,
            ]);
        }

        throw new MaxApiException(
            sprintf('%s [%s %s, код: %d]', $errorMsg, $method, $endpoint, $statusCode),
            $statusCode,
            $description,
            $errorCode
        );
    }
}
