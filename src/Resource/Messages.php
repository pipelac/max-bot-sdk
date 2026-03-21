<?php

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
     * @param array    $body   Тело сообщения (text, attachments, ...).
     * @param array|null $notify Настройки уведомления.
     * @param int|null $chatId ID чата.
     * @return Message
     * @throws \MaxBotSdk\Exception\MaxApiException
     */
    public function sendMessage(array $body, array $notify = null, $chatId = null)
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

    /**
     * Отправить текстовое сообщение (shortcut).
     *
     * @param string   $text   Текст.
     * @param int      $chatId ID чата.
     * @param string   $format Формат (markdown, html). По умолчанию markdown.
     * @return Message
     * @throws \MaxBotSdk\Exception\MaxApiException
     * @throws \MaxBotSdk\Exception\MaxValidationException
     */
    public function sendText($text, $chatId, $format = 'markdown')
    {
        $this->validateText($text);
        $chatId = $this->validateId($chatId, 'Chat ID');
        return $this->sendMessage(['text' => $text, 'format' => $format], null, $chatId);
    }

    /**
     * Отправить текст с inline-клавиатурой (shortcut).
     *
     * @param string $text   Текст.
     * @param int    $chatId ID чата.
     * @param array  $rows   Ряды кнопок для KeyboardBuilder.
     * @return Message
     * @throws \MaxBotSdk\Exception\MaxApiException
     * @throws \MaxBotSdk\Exception\MaxValidationException
     */
    public function sendTextWithKeyboard($text, $chatId, array $rows)
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
            $chatId
        );
    }

    /**
     * Получить сообщения из чата.
     *
     * @param int      $chatId ID чата.
     * @param int|null $count  Количество.
     * @param int|null $from   Начальная метка.
     * @param int|null $to     Конечная метка.
     * @return PaginatedResult Коллекция Message DTO.
     * @throws \MaxBotSdk\Exception\MaxApiException
     * @throws \MaxBotSdk\Exception\MaxValidationException
     */
    public function getMessages($chatId, $count = null, $from = null, $to = null)
    {
        $chatId = $this->validateId($chatId, 'Chat ID');
        $query = ['chat_id' => $chatId];
        if ($count !== null) {
            $query['count'] = (int) $count;
        }
        if ($from !== null) {
            $query['from'] = (int) $from;
        }
        if ($to !== null) {
            $query['to'] = (int) $to;
        }

        $data = $this->get('/messages', $query);
        return PaginatedResult::fromApiResponse($data, 'messages', Message::class);
    }

    /**
     * Получить сообщение по ID.
     *
     * @param string $messageId ID сообщения.
     * @return Message
     * @throws \MaxBotSdk\Exception\MaxApiException
     */
    public function getMessage($messageId)
    {
        InputValidator::validateNotEmpty($messageId, 'Message ID');
        $data = $this->get('/messages/' . $messageId);
        return Message::fromArray($data);
    }

    /**
     * Редактировать сообщение.
     *
     * @param string $messageId ID сообщения.
     * @param array  $body      Новое тело сообщения.
     * @return Message
     * @throws \MaxBotSdk\Exception\MaxApiException
     */
    public function editMessage($messageId, array $body)
    {
        InputValidator::validateNotEmpty($messageId, 'Message ID');
        $data = $this->put('/messages', $body, ['message_id' => $messageId]);
        return Message::fromArray($data);
    }

    /**
     * Удалить сообщение.
     *
     * @param string $messageId ID сообщения.
     * @return ActionResult
     * @throws \MaxBotSdk\Exception\MaxApiException
     */
    public function deleteMessage($messageId)
    {
        InputValidator::validateNotEmpty($messageId, 'Message ID');
        $data = $this->delete('/messages', ['message_id' => $messageId]);
        return ActionResult::fromArray($data);
    }
}
