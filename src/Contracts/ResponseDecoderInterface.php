<?php

namespace App\Component\Max\Contracts;

use App\Component\Max\Exception\MaxApiException;

/**
 * Интерфейс декодера ответов MAX Bot API.
 *
 * @since 1.0.0
 */
interface ResponseDecoderInterface
{
    /**
     * Декодирует ответ API.
     *
     * @param int    $statusCode HTTP-код ответа.
     * @param string $body       Тело ответа.
     * @param string $method     HTTP-метод запроса.
     * @param string $endpoint   Эндпоинт запроса.
     * @return array Декодированный ответ.
     * @throws MaxApiException
     */
    public function decode($statusCode, $body, $method = '', $endpoint = '');
}
