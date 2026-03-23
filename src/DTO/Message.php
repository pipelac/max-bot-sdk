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
    private readonly ?string $text;
    private readonly ?string $format;
    private readonly ?User $sender;
    /** @var array<string, mixed>|null */
    private readonly ?array $recipient;
    /** @var list<Attachment> */
    private readonly array $attachments;
    private readonly ?int $timestamp;
    private readonly ?string $link;
    /** @var array<string, mixed>|null */
    private readonly ?array $stat;

    /**
     * @param array<string, mixed> $data
     */
    private function __construct(array $data)
    {
        // messageId может прийти как 'message_id' или 'mid'
        $this->messageId = self::getString($data, 'message_id') ?: self::getString($data, 'mid');
        $this->text = self::getStringOrNull($data, 'text');
        $this->format = self::getStringOrNull($data, 'format');

        // sender может быть объектом или вложенным массивом
        $senderData = self::getArrayOrNull($data, 'sender');
        $this->sender = $senderData !== null ? User::fromArray($senderData) : null;

        $this->recipient = self::getArrayOrNull($data, 'recipient');
        $this->timestamp = self::getIntOrNull($data, 'timestamp');
        $this->link = self::getStringOrNull($data, 'link');
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
    }

    public static function fromArray(array $data): static
    {
        // Обрабатываем вариант, когда данные обёрнуты в 'message'
        if (isset($data['message']) && \is_array($data['message'])) {
            $body = $data['message'];

            // Переносим поля из верхнего уровня, не перезаписывая вложенные
            $outerFields = ['message_id', 'timestamp', 'sender', 'recipient', 'link', 'stat'];
            foreach ($outerFields as $field) {
                if (!isset($body[$field]) && isset($data[$field])) {
                    $body[$field] = $data[$field];
                }
            }

            return new self($body);
        }

        return new self($data);
    }

    public function getMessageId(): string
    {
        return $this->messageId;
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

    public function getTimestamp(): ?int
    {
        return $this->timestamp;
    }

    public function getLink(): ?string
    {
        return $this->link;
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
            'text'        => $this->text,
            'format'      => $this->format,
            'sender'      => $this->sender?->toArray(),
            'recipient'   => $this->recipient,
            'attachments' => array_map(
                static fn(Attachment $a): array => $a->toArray(),
                $this->attachments,
            ),
            'timestamp'   => $this->timestamp,
            'link'        => $this->link,
            'stat'        => $this->stat,
        ];
    }
}
