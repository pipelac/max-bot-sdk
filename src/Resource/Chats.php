<?php

declare(strict_types=1);

namespace MaxBotSdk\Resource;

use MaxBotSdk\DTO\ActionResult;
use MaxBotSdk\DTO\Chat;
use MaxBotSdk\DTO\Message;
use MaxBotSdk\DTO\PaginatedResult;
use MaxBotSdk\Utils\InputValidator;

/**
 * Ресурс: управление чатами MAX Bot API.
 *
 * @since 1.0.0
 */
final class Chats extends ResourceAbstract
{
    /**
     * @return PaginatedResult<Chat>
     */
    public function getChats(?int $count = null, ?int $marker = null): PaginatedResult
    {
        $query = [];
        if ($count !== null) {
            $query['count'] = $count;
        }
        if ($marker !== null) {
            $query['marker'] = $marker;
        }

        $data = $this->get('/chats', $query);
        return PaginatedResult::fromApiResponse($data, 'chats', Chat::class);
    }

    public function getChat(int $chatId): Chat
    {
        $chatId = $this->validateId($chatId, 'Chat ID');
        $data = $this->get('/chats/' . $chatId);
        return Chat::fromArray($data);
    }

    /**
     * @param array<string, mixed> $chatData
     */
    public function editChat(int $chatId, array $chatData): Chat
    {
        $chatId = $this->validateId($chatId, 'Chat ID');
        $data = $this->patch('/chats/' . $chatId, $chatData);
        return Chat::fromArray($data);
    }

    public function deleteChat(int $chatId): ActionResult
    {
        $chatId = $this->validateId($chatId, 'Chat ID');
        $data = $this->delete('/chats/' . $chatId);
        return ActionResult::fromArray($data);
    }

    public function sendAction(int $chatId, string $action): ActionResult
    {
        $chatId = $this->validateId($chatId, 'Chat ID');
        InputValidator::validateNotEmpty($action, 'Action');
        $data = $this->post('/chats/' . $chatId . '/actions', ['action' => $action]);
        return ActionResult::fromArray($data);
    }

    public function pinMessage(int $chatId, string $messageId): ActionResult
    {
        $chatId = $this->validateId($chatId, 'Chat ID');
        InputValidator::validateNotEmpty($messageId, 'Message ID');
        $data = $this->put('/chats/' . $chatId . '/pin', ['message_id' => $messageId]);
        return ActionResult::fromArray($data);
    }

    public function unpinMessage(int $chatId): ActionResult
    {
        $chatId = $this->validateId($chatId, 'Chat ID');
        $data = $this->delete('/chats/' . $chatId . '/pin');
        return ActionResult::fromArray($data);
    }

    public function getPinnedMessage(int $chatId): ?Message
    {
        $chatId = $this->validateId($chatId, 'Chat ID');
        $data = $this->get('/chats/' . $chatId . '/pin');
        if ($data === [] || !isset($data['message']) || !\is_array($data['message'])) {
            return null;
        }
        return Message::fromArray($data['message']);
    }
}
