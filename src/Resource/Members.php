<?php

declare(strict_types=1);

namespace MaxBotSdk\Resource;

use MaxBotSdk\DTO\ActionResult;
use MaxBotSdk\DTO\ChatMember;
use MaxBotSdk\DTO\PaginatedResult;
use MaxBotSdk\Enum\ChatAdminPermission;

/**
 * Ресурс: управление участниками чатов MAX Bot API.
 *
 * @since 1.0.0
 */
final class Members extends ResourceAbstract
{
    /**
     * Получить список участников чата (GET /chats/{chatId}/members).
     *
     * @param list<int>|null $userIds Список ID пользователей для фильтрации.
     * @return PaginatedResult<ChatMember>
     */
    public function getMembers(
        int $chatId,
        ?int $count = null,
        ?int $marker = null,
        ?array $userIds = null,
    ): PaginatedResult {
        $chatId = $this->validateId($chatId, 'Chat ID');
        $query = [];
        if ($count !== null) {
            $query['count'] = $count;
        }
        if ($marker !== null) {
            $query['marker'] = $marker;
        }
        if ($userIds !== null && $userIds !== []) {
            $query['user_ids'] = implode(',', array_map(
                fn(int $id): string => (string) $this->validateId($id, 'User ID'),
                $userIds,
            ));
        }

        $data = $this->get('/chats/' . $chatId . '/members', $query);
        return PaginatedResult::fromApiResponse($data, 'members', ChatMember::class);
    }

    /**
     * @deprecated Метод POST /chats/{chatId}/members ограничен с 9 сентября 2026 и полностью удалён из MAX Bot API с 30 сентября 2026.
     * Добавление участников ботом больше не поддерживается; используйте ссылки-приглашения (invite links).
     *
     * @param list<int> $userIds
     */
    public function addMembers(int $chatId, array $userIds): ActionResult
    {
        static $deprecatedWarned = false;
        if (!$deprecatedWarned) {
            trigger_error(
                'Method MaxBotSdk\Resource\Members::addMembers() is deprecated since September 2026 and removed in MAX Bot API. Use chat invite links instead.',
                \E_USER_DEPRECATED,
            );
            $deprecatedWarned = true;
        }

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

    /**
     * Назначить администраторов чата или канала (POST /chats/{chatId}/members/admins, ChatAdminsList).
     *
     * @param list<array{user_id: int, permissions: list<ChatAdminPermission|string>, alias?: string|null}> $admins
     * @since 2.4.0
     */
    public function setAdmins(int $chatId, array $admins): ActionResult
    {
        $chatId = $this->validateId($chatId, 'Chat ID');
        $normalized = [];

        foreach ($admins as $admin) {
            $entry = [
                'user_id'     => $this->validateId($admin['user_id'], 'User ID'),
                'permissions' => array_map(
                    static fn(ChatAdminPermission|string $perm): string => $perm instanceof ChatAdminPermission ? $perm->value : $perm,
                    $admin['permissions'],
                ),
            ];
            if (isset($admin['alias']) && $admin['alias'] !== '') {
                $entry['alias'] = $admin['alias'];
            }
            $normalized[] = $entry;
        }

        $data = $this->post('/chats/' . $chatId . '/members/admins', ['admins' => $normalized]);
        return ActionResult::fromArray($data);
    }

    /**
     * Назначить администратора чата или канала (обёртка над ChatAdminsList).
     *
     * @param list<ChatAdminPermission|string> $permissions Права администратора.
     * @param string|null                      $alias       Отображаемый титул администратора.
     */
    public function addAdmin(
        int $chatId,
        int $userId,
        array $permissions = [],
        ?string $alias = null,
    ): ActionResult {
        $admin = [
            'user_id'     => $userId,
            'permissions' => $permissions,
        ];
        if ($alias !== null) {
            $admin['alias'] = $alias;
        }

        return $this->setAdmins($chatId, [$admin]);
    }

    public function removeAdmin(int $chatId, int $userId): ActionResult
    {
        $chatId = $this->validateId($chatId, 'Chat ID');
        $userId = $this->validateId($userId, 'User ID');
        $data = $this->delete('/chats/' . $chatId . '/members/admins/' . $userId);
        return ActionResult::fromArray($data);
    }
}
