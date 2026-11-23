<?php

declare(strict_types=1);

namespace MaxBotSdk\Enum;

/**
 * Права администратора чата или канала (ChatAdminPermission MAX Bot API).
 *
 * @since 2.4.0
 */
enum ChatAdminPermission: string
{
    case ReadAllMessages = 'read_all_messages';
    case AddRemoveMembers = 'add_remove_members';
    case AddAdmins = 'add_admins';
    case ChangeChatInfo = 'change_chat_info';
    case PinMessage = 'pin_message';
    case EditLink = 'edit_link';
    case Write = 'write';
    case Edit = 'edit';
    case Delete = 'delete';
    case CanCall = 'can_call';
    case ViewStats = 'view_stats';
}
