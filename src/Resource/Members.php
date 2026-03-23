<?php

declare(strict_types=1);

namespace MaxBotSdk\Resource;

use MaxBotSdk\DTO\ActionResult;
use MaxBotSdk\DTO\ChatMember;
use MaxBotSdk\DTO\PaginatedResult;

/**
 * Ресурс: управление участниками чатов MAX Bot API.
 *
 * @since 1.0.0
 */
final class Members extends ResourceAbstract
{
    /**
     * @return PaginatedResult<ChatMember>
     */
    public function getMembers(int $chatId, ?int $count = null, ?int $marker = null): PaginatedResult
    {
        $chatId = $this->validateId($chatId, 'Chat ID');
        $query = [];
        if ($count !== null) {
            $query['count'] = $count;
        }
        if ($marker !== null) {
            $query['marker'] = $marker;
        }

        $data = $this->get('/chats/' . $chatId . '/members', $query);
        return PaginatedResult::fromApiResponse($data, 'members', ChatMember::class);
    }

    /**
     * @param list<int> $userIds
     */
    public function addMembers(int $chatId, array $userIds): ActionResult
    {
        $chatId = $this->validateId($chatId, 'Chat ID');
        $data = $this->post('/chats/' . $chatId . '/members', ['user_ids' => $userIds]);
        return ActionResult::fromArray($data);
    }

    public function removeMember(int $chatId, int $userId, ?bool $block = null): ActionResult
    {
        $chatId = $this->validateId($chatId, 'Chat ID');
        $userId = $this->validateId($userId, 'User ID');
        $query = ['user_id' => $userId];
        if ($block !== null) {
            $query['block'] = $block ? 'true' : 'false';
        }
        $data = $this->delete('/chats/' . $chatId . '/members', $query);
        return ActionResult::fromArray($data);
    }

    public function getMyMembership(int $chatId): ChatMember
    {
        $chatId = $this->validateId($chatId, 'Chat ID');
        $data = $this->get('/chats/' . $chatId . '/members/me');
        return ChatMember::fromArray($data);
    }

    public function leaveChat(int $chatId): ActionResult
    {
        $chatId = $this->validateId($chatId, 'Chat ID');
        $data = $this->delete('/chats/' . $chatId . '/members/me');
        return ActionResult::fromArray($data);
    }

    /**
     * @return PaginatedResult<ChatMember>
     */
    public function getAdmins(int $chatId): PaginatedResult
    {
        $chatId = $this->validateId($chatId, 'Chat ID');
        $data = $this->get('/chats/' . $chatId . '/members/admins');
        return PaginatedResult::fromApiResponse($data, 'members', ChatMember::class);
    }

    public function addAdmin(int $chatId, int $userId): ActionResult
    {
        $chatId = $this->validateId($chatId, 'Chat ID');
        $userId = $this->validateId($userId, 'User ID');
        $data = $this->post('/chats/' . $chatId . '/members/admins', ['user_id' => $userId]);
        return ActionResult::fromArray($data);
    }

    public function removeAdmin(int $chatId, int $userId): ActionResult
    {
        $chatId = $this->validateId($chatId, 'Chat ID');
        $userId = $this->validateId($userId, 'User ID');
        $data = $this->delete('/chats/' . $chatId . '/members/admins/' . $userId);
        return ActionResult::fromArray($data);
    }
}
