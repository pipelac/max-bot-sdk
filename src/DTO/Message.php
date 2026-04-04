<?php

namespace MaxBotSdk\DTO;

/**
 * Объект сообщения MAX.
 *
 * @since 1.0.0
 */
final class Message extends AbstractDto
{
    /** @var string|null */
    private $messageId;

    /** @var string|null */
    private $text;

    /** @var string|null */
    private $format;

    /** @var User|null */
    private $sender;

    /** @var array */
    private $recipient;

    /** @var Attachment[] */
    private $attachments;

    /** @var int|null */
    private $timestamp;

    /** @var array|null */
    private $link;

    /** @var array|null */
    private $stat;

    /**
     * @param array $data
     */
    private function __construct(array $data)
    {
        // ID сообщения может быть в разных местах
        if (isset($data['body']['mid'])) {
            $this->messageId = (string) $data['body']['mid'];
        } elseif (isset($data['mid'])) {
            $this->messageId = (string) $data['mid'];
        } elseif (isset($data['message_id'])) {
            $this->messageId = (string) $data['message_id'];
        } else {
            $this->messageId = null;
        }

        // Тело: может быть вложенным в body или на верхнем уровне
        $body = self::getArray($data, 'body', $data);
        $this->text = self::getStringOrNull($body, 'text');
        $this->format = self::getStringOrNull($body, 'format');
        $this->link = self::getArrayOrNull($body, 'link');

        // Вложения
        $this->attachments = [];
        $rawAttachments = self::getArray($body, 'attachments');
        foreach ($rawAttachments as $att) {
            if (is_array($att)) {
                $this->attachments[] = Attachment::fromArray($att);
            }
        }

        // Отправитель
        $senderData = self::getArrayOrNull($data, 'sender');
        $this->sender = $senderData !== null ? User::fromArray($senderData) : null;

        // Получатель
        $this->recipient = self::getArray($data, 'recipient');

        // Timestamp
        $this->timestamp = self::getIntOrNull($data, 'timestamp');

        // Статистика
        $this->stat = self::getArrayOrNull($data, 'stat');
    }

    /**
     * {@inheritdoc}
     */
    public static function fromArray(array $data)
    {
        $messageData = isset($data['message']) && is_array($data['message']) ? $data['message'] : $data;

        // Достаем текст, вложения и mid из 'body', если они там есть
        if (isset($messageData['body']) && is_array($messageData['body'])) {
            $innerBody = $messageData['body'];
            $fieldsToExtract = ['mid', 'text', 'format', 'attachments'];
            foreach ($fieldsToExtract as $field) {
                if (isset($innerBody[$field]) && !isset($messageData[$field])) {
                    $messageData[$field] = $innerBody[$field];
                }
            }
        }

        // Переносим поля из верхнего уровня, если $data имел 'message'
        if (isset($data['message']) && is_array($data['message'])) {
            $outerFields = ['message_id', 'timestamp', 'sender', 'recipient', 'link', 'stat'];
            foreach ($outerFields as $field) {
                if (!isset($messageData[$field]) && isset($data[$field])) {
                    $messageData[$field] = $data[$field];
                }
            }
        }

        return new self($messageData);
    }

    /** @return string|null */
    public function getMessageId()
    {
        return $this->messageId;
    }

    /** @return string|null */
    public function getText()
    {
        return $this->text;
    }

    /** @return string|null */
    public function getFormat()
    {
        return $this->format;
    }

    /** @return User|null */
    public function getSender()
    {
        return $this->sender;
    }

    /** @return array */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * @return int|null ID чата получателя.
     */
    public function getChatId()
    {
        return isset($this->recipient['chat_id']) ? (int) $this->recipient['chat_id'] : null;
    }

    /**
     * @return Attachment[]
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /** @return int|null */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /** @return array|null */
    public function getLink()
    {
        return $this->link;
    }

    /** @return array|null */
    public function getStat()
    {
        return $this->stat;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('Message#%s', $this->messageId !== null ? $this->messageId : 'unknown');
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $attachments = [];
        foreach ($this->attachments as $att) {
            $attachments[] = $att->toArray();
        }

        return [
            'message_id'  => $this->messageId,
            'text'        => $this->text,
            'format'      => $this->format,
            'sender'      => $this->sender !== null ? $this->sender->toArray() : null,
            'recipient'   => $this->recipient,
            'attachments' => $attachments,
            'timestamp'   => $this->timestamp,
            'link'        => $this->link,
            'stat'        => $this->stat,
        ];
    }
}
