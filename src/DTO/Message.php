<?php

declare(strict_types=1);

namespace MaxBotSdk\DTO;

/**
 * Объект сообщения MAX.
 *
 * @since 1.0.0
 */
final class Message extends AbstractDto
{
    private readonly string $messageId;
    private readonly ?int $seq;
    private readonly ?string $text;
    private readonly ?string $format;
    private readonly ?User $sender;
    /** @var array<string, mixed>|null */
    private readonly ?array $recipient;
    /** @var list<Attachment> */
    private readonly array $attachments;
    /** @var list<array<string, mixed>>|null */
    private readonly ?array $markup;
    private readonly ?int $timestamp;
    private readonly ?string $link;
    /** @var array<string, mixed>|null */
    private readonly ?array $linkData;
    private readonly ?string $url;
    /** @var array<string, mixed>|null */
    private readonly ?array $stat;

    /**
     * @param array<string, mixed> $data
     */
    private function __construct(array $data)
    {
        // messageId может прийти как 'message_id' или 'mid'
        $this->messageId = self::getString($data, 'message_id') ?: self::getString($data, 'mid');
        $this->seq = self::getIntOrNull($data, 'seq');
        $this->text = self::getStringOrNull($data, 'text');
        $this->format = self::getStringOrNull($data, 'format');

        // sender может быть объектом или вложенным массивом
        $senderData = self::getArrayOrNull($data, 'sender');
        $this->sender = $senderData !== null ? User::fromArray($senderData) : null;

        $this->recipient = self::getArrayOrNull($data, 'recipient');
        $this->timestamp = self::getIntOrNull($data, 'timestamp');
        $this->link = self::getStringOrNull($data, 'link');
        $this->linkData = self::getArrayOrNull($data, 'link');
        $this->url = self::getStringOrNull($data, 'url');
        $this->stat = self::getArrayOrNull($data, 'stat');

        // Парсим вложения
        $rawAttachments = self::getArray($data, 'attachments');
        $attachments = [];
        foreach ($rawAttachments as $raw) {
            if (\is_array($raw)) {
                $attachments[] = Attachment::fromArray($raw);
            }
        }
        $this->attachments = $attachments;

        $rawMarkup = self::getArrayOrNull($data, 'markup');
        if ($rawMarkup !== null) {
            $markup = [];
            foreach ($rawMarkup as $item) {
                if (\is_array($item)) {
                    /** @var array<string, mixed> $item */
                    $markup[] = $item;
                }
            }
            $this->markup = $markup;
        } else {
            $this->markup = null;
        }
    }

    public static function fromArray(array $data): static
    {
        $messageData = isset($data['message']) && \is_array($data['message']) ? $data['message'] : $data;

        // Достаем текст, вложения, mid, seq и markup из 'body', если они там есть
        if (isset($messageData['body']) && \is_array($messageData['body'])) {
            $innerBody = $messageData['body'];
            $fieldsToExtract = ['mid', 'seq', 'text', 'format', 'attachments', 'markup'];
            foreach ($fieldsToExtract as $field) {
                if (isset($innerBody[$field]) && !isset($messageData[$field])) {
                    $messageData[$field] = $innerBody[$field];
                }
            }
        }

        // Переносим поля из верхнего уровня, если $data имел 'message'
        if (isset($data['message']) && \is_array($data['message'])) {
            $outerFields = ['message_id', 'timestamp', 'sender', 'recipient', 'link', 'url', 'stat'];
            foreach ($outerFields as $field) {
                if (!isset($messageData[$field]) && isset($data[$field])) {
                    $messageData[$field] = $data[$field];
                }
            }
        }

        return new self($messageData);
    }

    public function getMessageId(): string
    {
        return $this->messageId;
    }

    /**
     * Порядковый номер сообщения в чате (MessageBody.seq).
     *
     * @since 2.4.0
     */
    public function getSeq(): ?int
    {
        return $this->seq;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRecipient(): ?array
    {
        return $this->recipient;
    }

    /**
     * @return list<Attachment>
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }

    /**
     * Элементы разметки текста (MessageBody.markup).
     *
     * @return list<array<string, mixed>>|null
     * @since 2.4.0
     */
    public function getMarkup(): ?array
    {
        return $this->markup;
    }

    public function getTimestamp(): ?int
    {
        return $this->timestamp;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    /**
     * Структурированный объект LinkedMessage (reply / forward), если `link` пришёл как объект.
     *
     * @return array<string, mixed>|null
     * @since 2.4.0
     */
    public function getLinkData(): ?array
    {
        return $this->linkData;
    }

    /**
     * Публичный URL сообщения в канале/чате (Message.url).
     *
     * @since 2.4.0
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getStat(): ?array
    {
        return $this->stat;
    }

    public function toArray(): array
    {
        return [
            'message_id'  => $this->messageId,
            'seq'         => $this->seq,
            'text'        => $this->text,
            'format'      => $this->format,
            'sender'      => $this->sender?->toArray(),
            'recipient'   => $this->recipient,
            'attachments' => array_map(
                static fn(Attachment $a): array => $a->toArray(),
                $this->attachments,
            ),
            'markup'      => $this->markup,
            'timestamp'   => $this->timestamp,
            'link'        => $this->linkData ?? $this->link,
            'url'         => $this->url,
            'stat'        => $this->stat,
        ];
    }
}
