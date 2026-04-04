<?php

declare(strict_types=1);

namespace MaxBotSdk\DTO;

/**
 * Объект обновления (webhook / long-polling).
 *
 * @since 1.0.0
 */
final class Update extends AbstractDto
{
    private readonly string $updateType;
    private readonly int $timestamp;
    /** @var array<string, mixed> */
    private readonly array $body;
    private readonly ?string $messageId;
    private readonly ?int $chatId;
    private readonly ?int $userId;

    /**
     * @param array<string, mixed> $data
     */
    private function __construct(array $data)
    {
        $this->updateType = self::getString($data, 'update_type');
        $this->timestamp = self::getInt($data, 'timestamp');
        $this->messageId = self::getStringOrNull($data, 'message_id');
        $this->chatId = self::getIntOrNull($data, 'chat_id');
        $this->userId = self::getIntOrNull($data, 'user_id');

        // Всё остальное сохраняем как body
        $this->body = $data;
    }

    public static function fromArray(array $data): static
    {
        return new self($data);
    }

    public function getUpdateType(): string
    {
        return $this->updateType;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * @return array<string, mixed>
     */
    public function getBody(): array
    {
        return $this->body;
    }

    public function getMessageId(): ?string
    {
        return $this->messageId;
    }

    public function getChatId(): ?int
    {
        if ($this->chatId !== null) {
            return $this->chatId;
        }

        $msg = $this->getMessage();
        if ($msg !== null) {
            return $msg->getChatId();
        }

        return null;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * Получить объект сообщения из body (если тип содержит message).
     */
    public function getMessage(): ?Message
    {
        $messageData = self::getArrayOrNull($this->body, 'message');
        return $messageData !== null ? Message::fromArray($messageData) : null;
    }

    /**
     * Получить callback данные из body.
     *
     * @return array<string, mixed>|null
     */
    public function getCallback(): ?array
    {
        return self::getArrayOrNull($this->body, 'callback');
    }

    /**
     * Получить данные пользователя из body.
     */
    public function getUser(): ?User
    {
        $userData = self::getArrayOrNull($this->body, 'user');
        return $userData !== null ? User::fromArray($userData) : null;
    }

    public function toArray(): array
    {
        return $this->body;
    }
}
