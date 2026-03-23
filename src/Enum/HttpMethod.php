<?php

declare(strict_types=1);

namespace MaxBotSdk\Enum;

/**
 * HTTP-методы для запросов к MAX Bot API.
 *
 * @since 2.1.0
 */
enum HttpMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
}
