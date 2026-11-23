<?php

declare(strict_types=1);

namespace MaxBotSdk\Resource;

use MaxBotSdk\DTO\ActionResult;
use MaxBotSdk\DTO\Message;
use MaxBotSdk\DTO\PaginatedResult;
use MaxBotSdk\Utils\InputValidator;
use MaxBotSdk\Utils\KeyboardBuilder;

/**
 * Ресурс: работа с сообщениями MAX Bot API.
 *
 * @since 1.0.0
 */
final class Messages extends ResourceAbstract
{
    /**
     * Отправить сообщение в чат или пользователю (POST /messages).
     *
     * @param array<string, mixed>           $body               Тело сообщения (NewMessageBody).
     * @param bool|array<string, mixed>|null $notify             Флаг уведомления участников (bool по OpenAPI; array оставлен для обратной совместимости).
     * @param int|null                       $chatId             ID чата или канала.
     * @param int|null                       $userId             ID пользователя для отправки в личный диалог.
     * @param bool|null                      $disableLinkPreview Отключить генерацию превью ссылок.
     */
    public function sendMessage(
        array $body,
        bool|array|null $notify = null,
        ?int $chatId = null,
        ?int $userId = null,
        ?bool $disableLinkPreview = null,
    ): Message {
        $query = [];
        if ($chatId !== null) {
            $query['chat_id'] = $this->validateId($chatId, 'Chat ID');
        }
        if ($userId !== null) {
            $query['user_id'] = $this->validateId($userId, 'User ID');
        }
        if ($disableLinkPreview !== null) {
            $query['disable_link_preview'] = $disableLinkPreview ? 'true' : 'false';
        }

        $payload = $body;
        if ($notify !== null) {
            $payload['notify'] = $notify;
        }

        $data = $this->post('/messages', $payload, $query);
        return Message::fromArray($data);
    }

    public function sendText(string $text, int $chatId, string $format = 'markdown'): Message
    {
        $this->validateText($text);
        $chatId = $this->validateId($chatId, 'Chat ID');
        return $this->sendMessage(['text' => $text, 'format' => $format], null, $chatId);
    }

    /**
     * @param list<list<array<string, mixed>>> $rows Ряды кнопок.
     */
    public function sendTextWithKeyboard(string $text, int $chatId, array $rows): Message
    {
        $this->validateText($text);
        $chatId = $this->validateId($chatId, 'Chat ID');
        $keyboard = KeyboardBuilder::build($rows);

        return $this->sendMessage(
            [
                'text'        => $text,
                'attachments' => [$keyboard],
            ],
            null,
            $chatId,
        );
    }

    /**
     * Получить список сообщений (GET /messages).
     *
     * @param int|null          $chatId     ID чата (необязателен при указании $messageIds).
     * @param int|null          $count      Количество сообщений (1..100, по умолчанию 50).
     * @param int|null          $from       Время начала диапазона (Unix timestamp).
     * @param int|null          $to         Время окончания диапазона (Unix timestamp).
     * @param list<string>|null $messageIds Список ID сообщений для точечной выборки.
     * @param int|null          $before     Получить сообщения до указанного Unix timestamp (мс).
     * @param int|null          $after      Получить сообщения после указанного Unix timestamp (мс).
     * @return PaginatedResult<Message>
     */
    public function getMessages(
        ?int $chatId = null,
        ?int $count = null,
        ?int $from = null,
        ?int $to = null,
        ?array $messageIds = null,
        ?int $before = null,
        ?int $after = null,
    ): PaginatedResult {
        $query = [];
        if ($chatId !== null) {
            $query['chat_id'] = $this->validateId($chatId, 'Chat ID');
        }
        if ($count !== null) {
            $query['count'] = $count;
        }
        if ($from !== null) {
            $query['from'] = $from;
        }
        if ($to !== null) {
            $query['to'] = $to;
        }
        if ($messageIds !== null && $messageIds !== []) {
            $query['message_ids'] = implode(',', $messageIds);
        }
        if ($before !== null) {
            $query['before'] = $before;
        }
        if ($after !== null) {
            $query['after'] = $after;
        }

        $data = $this->get('/messages', $query);
        return PaginatedResult::fromApiResponse($data, 'messages', Message::class);
    }

    public function getMessage(string $messageId, ?int $chatId = null): Message
    {
        InputValidator::validateNotEmpty($messageId, 'Message ID');
        $query = [];
        if ($chatId !== null) {
            $query['chat_id'] = $this->validateId($chatId, 'Chat ID');
        }
        $data = $this->get('/messages/' . $messageId, $query);
        return Message::fromArray($data);
    }

    /**
     * @param array<string, mixed> $body
     */
    public function editMessage(string $messageId, array $body): Message
    {
        InputValidator::validateNotEmpty($messageId, 'Message ID');
        $data = $this->put('/messages', $body, ['message_id' => $messageId]);
        return Message::fromArray($data);
    }

    public function deleteMessage(string $messageId): ActionResult
    {
        InputValidator::validateNotEmpty($messageId, 'Message ID');
        $data = $this->delete('/messages', ['message_id' => $messageId]);
        return ActionResult::fromArray($data);
    }
}
