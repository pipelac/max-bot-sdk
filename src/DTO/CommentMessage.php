<?php

declare(strict_types=1);

namespace MaxBotSdk\DTO;

use DateTimeImmutable;
use MaxBotSdk\Enum\TextFormat;

/**
 * Объект комментария к сообщению канала (CommentMessage).
 *
 * В отличие от обычного сообщения (Message) не содержит вложений
 * (attachments) и не поддерживает пересылку сообщений (link.type = forward).
 *
 * @since 2.3.0
 */
final class CommentMessage extends AbstractDto
{
    private readonly string $commentId;
    private readonly ?string $messageId;
    private readonly ?User $sender;
    /** @var array<string, mixed>|null */
    private readonly ?array $recipient;
    private readonly int $timestamp;
    /** @var array<string, mixed>|null */
    private readonly ?array $link;
    private readonly string $text;
    private readonly ?TextFormat $format;
    private readonly ?int $seq;
    /** @var array<string, mixed> */
    private readonly array $body;
    /** @var array<string, mixed> */
    private readonly array $rawData;

    /**
     * @param array<string, mixed> $data
     */
    private function __construct(array $data)
    {
        $this->rawData = $data;

        // Поддержка извлечения внутреннего объекта body (CommentMessageBody)
        $innerBody = self::getArray($data, 'body');
        $this->body = $innerBody !== [] ? $innerBody : $data;

        // Идентификатор комментария: body.mid -> comment_id -> mid -> message_id
        $this->commentId = self::getString($this->body, 'mid')
            ?: self::getString($data, 'comment_id')
            ?: self::getString($data, 'mid')
            ?: self::getString($data, 'message_id');

        $this->messageId = self::getStringOrNull($data, 'parent_message_id')
            ?: self::getStringOrNull($data, 'message_id');

        // Текст и разметка комментария
        $this->text = self::getString($this->body, 'text') ?: self::getString($data, 'text');

        $formatStr = self::getStringOrNull($this->body, 'format') ?: self::getStringOrNull($data, 'format');
        $this->format = $formatStr !== null ? TextFormat::tryFrom($formatStr) : null;

        $this->seq = self::getIntOrNull($this->body, 'seq') ?: self::getIntOrNull($data, 'seq');

        // Автор комментария (может быть null, если опубликован от имени канала)
        $senderData = self::getArrayOrNull($data, 'sender');
        $this->sender = $senderData !== null ? User::fromArray($senderData) : null;

        $this->recipient = self::getArrayOrNull($data, 'recipient');
        $this->timestamp = self::getInt($data, 'timestamp');
        $this->link = self::getArrayOrNull($data, 'link');
    }

    /**
     * Фабричный метод создания DTO из массива API.
     *
     * Поддерживает как корневой массив, так и ответ, обёрнутый в ключ 'message'.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        $payload = isset($data['message']) && \is_array($data['message']) ? $data['message'] : $data;
        return new self($payload);
    }

    public function getCommentId(): string
    {
        return $this->commentId;
    }

    /**
     * Алиас getCommentId() для совместимости с интерфейсом Message.
     */
    public function getMid(): string
    {
        return $this->commentId;
    }

    public function getMessageId(): ?string
    {
        return $this->messageId;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getFormat(): ?TextFormat
    {
        return $this->format;
    }

    public function getFormatString(): ?string
    {
        return $this->format?->value;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    /**
     * Опубликован ли комментарий от имени канала (автор не указан).
     */
    public function isChannelPost(): bool
    {
        return $this->sender === null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRecipient(): ?array
    {
        return $this->recipient;
    }

    /**
     * Получить ID канала-получателя, если доступен в recipient или на верхнем уровне объекта.
     */
    public function getRecipientChatId(): ?int
    {
        return ($this->recipient !== null ? self::getIntOrNull($this->recipient, 'chat_id') : null)
            ?? self::getIntOrNull($this->rawData, 'chat_id');
    }

    /**
     * Алиас getRecipientChatId() для удобства.
     */
    public function getChatId(): ?int
    {
        return $this->getRecipientChatId();
    }

    /**
     * Время создания комментария (Unix timestamp в миллисекундах).
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * Получить время создания как объект DateTimeImmutable.
     */
    public function getDateTime(): DateTimeImmutable
    {
        $seconds = (int) ($this->timestamp / 1000);
        $dt = (new DateTimeImmutable())->setTimestamp($seconds);
        return $dt;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getLink(): ?array
    {
        return $this->link;
    }

    /**
     * Является ли комментарий ответом на другой комментарий.
     */
    public function hasReply(): bool
    {
        return $this->link !== null;
    }

    public function getSeq(): ?int
    {
        return $this->seq;
    }

    /**
     * @return array<string, mixed>
     */
    public function getBody(): array
    {
        return $this->body;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->rawData;
    }
}
