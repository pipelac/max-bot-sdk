<?php

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
     * Получить список участников чата.
     *
     * @param int      $chatId ID чата.
     * @param int|null $count  Количество.
     * @param int|null $marker Маркер для пагинации.
     * @return PaginatedResult Коллекция ChatMember DTO.
     * @throws \MaxBotSdk\Exception\MaxApiException
     * @throws \MaxBotSdk\Exception\MaxValidationException
     */
    public function getMembers($chatId, $count = null, $marker = null)
    {
        $chatId = $this->validateId($chatId, 'Chat ID');
        $query = [];
        if ($count !== null) {
            $query['count'] = (int) $count;
        }
        if ($marker !== null) {
            $query['marker'] = (int) $marker;
        }

        $data = $this->get('/chats/' . $chatId . '/members', $query);
        return PaginatedResult::fromApiResponse($data, 'members', ChatMember::class);
    }

    /**
     * Добавить участников в чат.
     *
     * @param int   $chatId  ID чата.
     * @param int[] $userIds Массив ID пользователей.
     * @return ActionResult
     * @throws \MaxBotSdk\Exception\MaxApiException
     * @throws \MaxBotSdk\Exception\MaxValidationException
     */
    public function addMembers($chatId, array $userIds)
    {
        $chatId = $this->validateId($chatId, 'Chat ID');
        $data = $this->post('/chats/' . $chatId . '/members', ['user_ids' => $userIds]);
        return ActionResult::fromArray($data);
    }

    /**
     * Удалить участника из чата.
     *
     * @param int       $chatId ID чата.
     * @param int       $userId ID пользователя.
     * @param bool|null $block  Заблокировать ли пользователя.
     * @return ActionResult
     * @throws \MaxBotSdk\Exception\MaxApiException
     * @throws \MaxBotSdk\Exception\MaxValidationException
     */
    public function removeMember($chatId, $userId, $block = null)
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

    /**
     * Получить информацию об участии бота в чате.
     *
     * @param int $chatId ID чата.
     * @return ChatMember
     * @throws \MaxBotSdk\Exception\MaxApiException
     * @throws \MaxBotSdk\Exception\MaxValidationException
     */
    public function getMyMembership($chatId)
    {
        $chatId = $this->validateId($chatId, 'Chat ID');
        $data = $this->get('/chats/' . $chatId . '/members/me');
        return ChatMember::fromArray($data);
    }

    /**
     * Покинуть чат.
     *
     * @param int $chatId ID чата.
     * @return ActionResult
     * @throws \MaxBotSdk\Exception\MaxApiException
     * @throws \MaxBotSdk\Exception\MaxValidationException
     */
    public function leaveChat($chatId)
    {
        $chatId = $this->validateId($chatId, 'Chat ID');
        $data = $this->delete('/chats/' . $chatId . '/members/me');
        return ActionResult::fromArray($data);
    }

    /**
     * Получить список админов чата.
     *
     * @param int $chatId ID чата.
     * @return PaginatedResult Коллекция ChatMember DTO.
     * @throws \MaxBotSdk\Exception\MaxApiException
     * @throws \MaxBotSdk\Exception\MaxValidationException
     */
    public function getAdmins($chatId)
    {
        $chatId = $this->validateId($chatId, 'Chat ID');
        $data = $this->get('/chats/' . $chatId . '/members/admins');
        return PaginatedResult::fromApiResponse($data, 'members', ChatMember::class);
    }

    /**
     * Назначить пользователя администратором.
     *
     * @param int $chatId ID чата.
     * @param int $userId ID пользователя.
     * @return ActionResult
     * @throws \MaxBotSdk\Exception\MaxApiException
     * @throws \MaxBotSdk\Exception\MaxValidationException
     */
    public function addAdmin($chatId, $userId)
    {
        $chatId = $this->validateId($chatId, 'Chat ID');
        $userId = $this->validateId($userId, 'User ID');
        $data = $this->post('/chats/' . $chatId . '/members/admins', ['user_id' => $userId]);
        return ActionResult::fromArray($data);
    }

    /**
     * Снять с пользователя права администратора.
     *
     * @param int $chatId ID чата.
     * @param int $userId ID пользователя.
     * @return ActionResult
     * @throws \MaxBotSdk\Exception\MaxApiException
     * @throws \MaxBotSdk\Exception\MaxValidationException
     */
    public function removeAdmin($chatId, $userId)
    {
        $chatId = $this->validateId($chatId, 'Chat ID');
        $userId = $this->validateId($userId, 'User ID');
        $data = $this->delete('/chats/' . $chatId . '/members/admins/' . $userId);
        return ActionResult::fromArray($data);
    }
}
