<?php

declare(strict_types=1);

namespace MaxBotSdk\Contracts;

use MaxBotSdk\Exception\MaxApiException;

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
     * @return array<string, mixed> Декодированный ответ.
     * @throws MaxApiException
     */
    public function decode(int $statusCode, string $body, string $method = '', string $endpoint = ''): array;
}
