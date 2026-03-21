<?php

namespace App\Component\Max\Exception;

/**
 * Исключение для ошибок сетевого соединения.
 *
 * Выбрасывается при невозможности установить соединение с MAX API
 * (timeout, DNS-ошибки, сетевые проблемы).
 *
 * @since 1.0.0
 */
final class MaxConnectionException extends MaxException
{
}
