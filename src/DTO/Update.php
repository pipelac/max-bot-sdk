<?php

namespace MaxBotSdk\DTO;

/**
 * Объект обновления (webhook/long-polling) MAX.
 *
 * @since 1.0.0
 */
final class Update extends AbstractDto
{
    /** @var string */
    private $updateType;

    /** @var int */
    private $timestamp;

    /** @var Message|null */
    private $message;

    /** @var array|null Callback-данные (callback_id, payload, user) */
    private $callback;

    /** @var User|null */
    private $user;

    /** @var int|null */
    private $chatId;

    /** @var string|null */
    private $messageId;

    /**
     * @param array $data
     */
    private function __construct(array $data)
    {
        $this->updateType = self::getString($data, 'update_type');
        $this->timestamp = self::getInt($data, 'timestamp');

        // message_created, message_edited
        $messageData = self::getArrayOrNull($data, 'message');
        $this->message = $messageData !== null ? Message::fromArray($messageData) : null;

        // message_callback
        $this->callback = self::getArrayOrNull($data, 'callback');

        // bot_started, user_added, user_removed
        $userData = self::getArrayOrNull($data, 'user');
        $this->user = $userData !== null ? User::fromArray($userData) : null;

        // chat_id для chat-related events
        $this->chatId = self::getIntOrNull($data, 'chat_id');

        // message_removed
        $this->messageId = self::getStringOrNull($data, 'message_id');
    }

    /**
     * {@inheritdoc}
     */
    public static function fromArray(array $data)
    {
        return new self($data);
    }

    /** @return string */
    public function getUpdateType()
    {
        return $this->updateType;
    }

    /** @return int */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /** @return Message|null */
    public function getMessage()
    {
        return $this->message;
    }

    /** @return array|null */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * @return string|null ID callback-а (shortcut).
     */
    public function getCallbackId()
    {
        return isset($this->callback['callback_id']) ? (string) $this->callback['callback_id'] : null;
    }

    /**
     * @return string|null Payload callback-а.
     */
    public function getCallbackPayload()
    {
        return isset($this->callback['payload']) ? (string) $this->callback['payload'] : null;
    }

    /** @return User|null */
    public function getUser()
    {
        return $this->user;
    }

    /** @return int|null */
    public function getChatId()
    {
        return $this->chatId;
    }

    /** @return string|null */
    public function getMessageId()
    {
        return $this->messageId;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('Update[%s]@%d', $this->updateType, $this->timestamp);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $result = [
            'update_type' => $this->updateType,
            'timestamp'   => $this->timestamp,
        ];

        if ($this->message !== null) {
            $result['message'] = $this->message->toArray();
        }
        if ($this->callback !== null) {
            $result['callback'] = $this->callback;
        }
        if ($this->user !== null) {
            $result['user'] = $this->user->toArray();
        }
        if ($this->chatId !== null) {
            $result['chat_id'] = $this->chatId;
        }
        if ($this->messageId !== null) {
            $result['message_id'] = $this->messageId;
        }

        return $result;
    }
}
