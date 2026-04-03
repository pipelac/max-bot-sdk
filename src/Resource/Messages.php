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
     * Отправить сообщение в чат.
     *
     * @param array<string, mixed>      $body   Тело сообщения.
     * @param array<string, mixed>|null $notify Настройки уведомления.
     */
    public function sendMessage(array $body, ?array $notify = null, ?int $chatId = null): Message
    {
        $query = [];
        if ($chatId !== null) {
            $query['chat_id'] = $this->validateId($chatId, 'Chat ID');
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
     * @return PaginatedResult<Message>
     */
    public function getMessages(int $chatId, ?int $count = null, ?int $from = null, ?int $to = null): PaginatedResult
    {
        $chatId = $this->validateId($chatId, 'Chat ID');
        $query = ['chat_id' => $chatId];

        if ($count !== null) {
            $query['count'] = $count;
        }
        if ($from !== null) {
            $query['from'] = $from;
        }
        if ($to !== null) {
            $query['to'] = $to;
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
