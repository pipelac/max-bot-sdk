<?php

declare(strict_types=1);

namespace MaxBotSdk\DTO;

use MaxBotSdk\Enum\UpdateType;

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

    /**
     * Получить типизированный тип события (SSOT).
     */
    public function getType(): ?UpdateType
    {
        return UpdateType::tryFrom($this->updateType);
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
            $recipient = $msg->getRecipient();
            if ($recipient !== null) {
                return self::getIntOrNull($recipient, 'chat_id');
            }
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

    /**
     * Получить объект комментария из body (для событий comment_created, comment_edited, comment_removed).
     */
    public function getComment(): ?CommentMessage
    {
        $commentData = self::getArrayOrNull($this->body, 'comment')
            ?: self::getArrayOrNull($this->body, 'message');

        if ($commentData !== null) {
            return CommentMessage::fromArray($commentData);
        }

        return null;
    }

    /**
     * Получить данные прав администратора (для события bot_admin_permissions_changed).
     *
     * @return array<string, mixed>|null
     */
    public function getAdminPermissions(): ?array
    {
        return self::getArrayOrNull($this->body, 'admin_permissions')
            ?: self::getArrayOrNull($this->body, 'permissions');
    }

    /**
     * Является ли обновление событием комментария.
     */
    public function isComment(): bool
    {
        return $this->getType()?->isCommentEvent()
            ?? str_starts_with($this->updateType, 'comment_');
    }

    /**
     * Является ли обновление событием диалога пользователя.
     */
    public function isDialog(): bool
    {
        return $this->getType()?->isDialogEvent()
            ?? str_starts_with($this->updateType, 'dialog_');
    }

    /**
     * Является ли обновление событием жизненного цикла бота.
     */
    public function isBotLifecycle(): bool
    {
        return $this->getType()?->isBotLifecycleEvent()
            ?? str_starts_with($this->updateType, 'bot_');
    }

    public function toArray(): array
    {
        return $this->body;
    }
}
