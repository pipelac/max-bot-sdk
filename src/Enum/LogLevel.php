<?php

declare(strict_types=1);

namespace MaxBotSdk\Enum;

/**
 * Уровни логирования SDK.
 *
 * @since 2.1.0
 */
enum LogLevel: string
{
    case Debug = 'debug';
    case Info = 'info';
    case Warning = 'warning';
    case Error = 'error';
}
