<?php

declare(strict_types=1);

namespace MaxBotSdk\DTO;

/**
 * Объект пользователя/бота MAX.
 *
 * @since 1.0.0
 */
final class User extends AbstractDto
{
    private readonly int $userId;
    private readonly string $name;
    private readonly ?string $username;
    private readonly bool $isBot;
    private readonly ?int $lastActivityTime;
    private readonly ?string $description;
    private readonly ?string $avatarUrl;

    /**
     * @param array<string, mixed> $data
     */
    private function __construct(array $data)
    {
        $this->userId = self::getInt($data, 'user_id');
        $this->name = self::getString($data, 'name');
        $this->username = self::getStringOrNull($data, 'username');
        $this->isBot = self::getBool($data, 'is_bot');
        $this->lastActivityTime = self::getIntOrNull($data, 'last_activity_time');
        $this->description = self::getStringOrNull($data, 'description');
        $this->avatarUrl = self::getStringOrNull($data, 'avatar_url');
    }

    public static function fromArray(array $data): static
    {
        return new self($data);
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function isBot(): bool
    {
        return $this->isBot;
    }

    public function getLastActivityTime(): ?int
    {
        return $this->lastActivityTime;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function __toString(): string
    {
        return \sprintf('User#%d (%s)', $this->userId, $this->name);
    }

    public function toArray(): array
    {
        return [
            'user_id'            => $this->userId,
            'name'               => $this->name,
            'username'           => $this->username,
            'is_bot'             => $this->isBot,
            'last_activity_time' => $this->lastActivityTime,
            'description'        => $this->description,
            'avatar_url'         => $this->avatarUrl,
        ];
    }
}
