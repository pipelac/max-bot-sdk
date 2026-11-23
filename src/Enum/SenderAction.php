<?php

declare(strict_types=1);

namespace MaxBotSdk\Enum;

/**
 * Действия бота в чате (SenderAction MAX Bot API).
 *
 * @since 2.4.0
 */
enum SenderAction: string
{
    case TypingOn = 'typing_on';
    case SendingPhoto = 'sending_photo';
    case SendingVideo = 'sending_video';
    case SendingAudio = 'sending_audio';
    case SendingFile = 'sending_file';
    case MarkSeen = 'mark_seen';
}
