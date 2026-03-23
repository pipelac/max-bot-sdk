<?php

declare(strict_types=1);

namespace MaxBotSdk\Enum;

/**
 * Тип загружаемого файла MAX Bot API.
 *
 * @since 2.1.0
 */
enum UploadType: string
{
    case Image = 'image';
    case Video = 'video';
    case Audio = 'audio';
    case File = 'file';
}
