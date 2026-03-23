<?php

declare(strict_types=1);

namespace MaxBotSdk\Enum;

/**
 * Типы обновлений (webhook/long-polling) MAX Bot API.
 *
 * @since 2.1.0
 */
enum UpdateType: string
{
    case MessageCreated = 'message_created';
    case MessageCallback = 'message_callback';
    case MessageEdited = 'message_edited';
    case MessageRemoved = 'message_removed';
    case BotStarted = 'bot_started';
    case BotAdded = 'bot_added';
    case BotRemoved = 'bot_removed';
    case UserAdded = 'user_added';
    case UserRemoved = 'user_removed';
    case ChatTitleChanged = 'chat_title_changed';
}
