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

    // ─── Новые типы событий (v2.3.0) ────────────────────────────
    case BotAdminPermissionsChanged = 'bot_admin_permissions_changed';
    case CommentCreated = 'comment_created';
    case CommentEdited = 'comment_edited';
    case CommentRemoved = 'comment_removed';
    case BotStopped = 'bot_stopped';
    case DialogCleared = 'dialog_cleared';
    case DialogMuted = 'dialog_muted';
    case DialogUnmuted = 'dialog_unmuted';
    case DialogRemoved = 'dialog_removed';

    /**
     * Относится ли событие к комментариям.
     */
    public function isCommentEvent(): bool
    {
        return match ($this) {
            self::CommentCreated, self::CommentEdited, self::CommentRemoved => true,
            default => false,
        };
    }

    /**
     * Относится ли событие к жизненному циклу диалога пользователя с ботом.
     */
    public function isDialogEvent(): bool
    {
        return match ($this) {
            self::DialogCleared, self::DialogMuted, self::DialogUnmuted, self::DialogRemoved => true,
            default => false,
        };
    }

    /**
     * Относится ли событие к жизненному циклу бота (старт/остановка/добавление/удаление).
     */
    public function isBotLifecycleEvent(): bool
    {
        return match ($this) {
            self::BotStarted, self::BotStopped, self::BotAdded, self::BotRemoved => true,
            default => false,
        };
    }

    /**
     * Относится ли событие к сообщениям чата (создание, редактирование, удаление, callback).
     */
    public function isMessageEvent(): bool
    {
        return match ($this) {
            self::MessageCreated, self::MessageCallback, self::MessageEdited, self::MessageRemoved => true,
            default => false,
        };
    }

    /**
     * Относится ли событие к членству пользователей в чате.
     */
    public function isMembershipEvent(): bool
    {
        return match ($this) {
            self::UserAdded, self::UserRemoved, self::BotAdded, self::BotRemoved => true,
            default => false,
        };
    }
}
