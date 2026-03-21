<?php

namespace App\Component\Max\Exception;

use RuntimeException;

/**
 * Базовое исключение для всех ошибок MAX Bot API SDK.
 *
 * Все специализированные исключения наследуются от этого класса,
 * что позволяет перехватывать любые ошибки SDK одним блоком catch.
 *
 * @since 1.0.0
 */
class MaxException extends RuntimeException
{
}
