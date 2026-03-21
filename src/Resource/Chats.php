<?php

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
     * Получить список чатов бота.
     *
     * @param int|null $count  Количество чатов.
     * @param int|null $marker Маркер для пагинации.
     * @return PaginatedResult Коллекция Chat DTO.
     * @throws \MaxBotSdk\Exception\MaxApiException
     */
    public function getChats($count = null, $marker = null)
    {
        $query = [];
        if ($count !== null) {
            $query['count'] = (int) $count;
        }
        if ($marker !== null) {
            $query['marker'] = (int) $marker;
        }

        $data = $this->get('/chats', $query);
        return PaginatedResult::fromApiResponse($data, 'chats', Chat::class);
    }

    /**
     * Получить информацию о чате.
     *
     * @param int $chatId ID чата.
     * @return Chat
     * @throws \MaxBotSdk\Exception\MaxApiException
     * @throws \MaxBotSdk\Exception\MaxValidationException
     */
    public function getChat($chatId)
    {
        $chatId = $this->validateId($chatId, 'Chat ID');
        $data = $this->get('/chats/' . $chatId);
        return Chat::fromArray($data);
    }

    /**
     * Редактировать чат.
     *
     * @param int   $chatId   ID чата.
     * @param array $chatData Данные для обновления (title, description, ...).
     * @return Chat
     * @throws \MaxBotSdk\Exception\MaxApiException
     * @throws \MaxBotSdk\Exception\MaxValidationException
     */
    public function editChat($chatId, array $chatData)
    {
        $chatId = $this->validateId($chatId, 'Chat ID');
        $data = $this->patch('/chats/' . $chatId, $chatData);
        return Chat::fromArray($data);
    }

    /**
     * Удалить чат.
     *
     * @param int $chatId ID чата.
     * @return ActionResult
     * @throws \MaxBotSdk\Exception\MaxApiException
     * @throws \MaxBotSdk\Exception\MaxValidationException
     */
    public function deleteChat($chatId)
    {
        $chatId = $this->validateId($chatId, 'Chat ID');
        $data = $this->delete('/chats/' . $chatId);
        return ActionResult::fromArray($data);
    }

    /**
     * Отправить действие в чат (typing, mark_seen, ...).
     *
     * @param int    $chatId ID чата.
     * @param string $action Тип действия.
     * @return ActionResult
     * @throws \MaxBotSdk\Exception\MaxApiException
     * @throws \MaxBotSdk\Exception\MaxValidationException
     */
    public function sendAction($chatId, $action)
    {
        $chatId = $this->validateId($chatId, 'Chat ID');
        InputValidator::validateNotEmpty($action, 'Action');
        $data = $this->post('/chats/' . $chatId . '/actions', ['action' => $action]);
        return ActionResult::fromArray($data);
    }

    /**
     * Закрепить сообщение в чате.
     *
     * @param int    $chatId    ID чата.
     * @param string $messageId ID сообщения.
     * @return ActionResult
     * @throws \MaxBotSdk\Exception\MaxApiException
     * @throws \MaxBotSdk\Exception\MaxValidationException
     */
    public function pinMessage($chatId, $messageId)
    {
        $chatId = $this->validateId($chatId, 'Chat ID');
        InputValidator::validateNotEmpty($messageId, 'Message ID');
        $data = $this->put('/chats/' . $chatId . '/pin', ['message_id' => $messageId]);
        return ActionResult::fromArray($data);
    }

    /**
     * Открепить сообщение в чате.
     *
     * @param int $chatId ID чата.
     * @return ActionResult
     * @throws \MaxBotSdk\Exception\MaxApiException
     * @throws \MaxBotSdk\Exception\MaxValidationException
     */
    public function unpinMessage($chatId)
    {
        $chatId = $this->validateId($chatId, 'Chat ID');
        $data = $this->delete('/chats/' . $chatId . '/pin');
        return ActionResult::fromArray($data);
    }

    /**
     * Получить закреплённое сообщение чата.
     *
     * @param int $chatId ID чата.
     * @return Message|null
     * @throws \MaxBotSdk\Exception\MaxApiException
     * @throws \MaxBotSdk\Exception\MaxValidationException
     */
    public function getPinnedMessage($chatId)
    {
        $chatId = $this->validateId($chatId, 'Chat ID');
        $data = $this->get('/chats/' . $chatId . '/pin');
        if (empty($data) || !isset($data['message'])) {
            return null;
        }
        return Message::fromArray($data['message']);
    }
}
