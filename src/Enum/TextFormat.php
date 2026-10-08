<?php

declare(strict_types=1);

namespace MaxBotSdk\Enum;

/**
 * Поддерживаемые форматы разметки текста в MAX Bot API.
 *
 * @since 2.3.0
 */
enum TextFormat: string
{
    case Markdown = 'markdown';
    case Html = 'html';
}
