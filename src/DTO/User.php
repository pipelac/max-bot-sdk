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
    private readonly ?string $firstName;
    private readonly ?string $lastName;
    private readonly ?string $username;
    private readonly bool $isBot;
    private readonly ?int $lastActivityTime;
    private readonly ?string $description;
    private readonly ?string $avatarUrl;
    private readonly ?string $fullAvatarUrl;
    /** @var list<array<string, mixed>>|null */
    private readonly ?array $commands;

    /**
     * @param array<string, mixed> $data
     */
    private function __construct(array $data)
    {
        $this->userId = self::getInt($data, 'user_id');
        $this->firstName = self::getStringOrNull($data, 'first_name');
        $this->lastName = self::getStringOrNull($data, 'last_name');

        $rawName = self::getString($data, 'name');
        if ($rawName !== '') {
            $this->name = $rawName;
        } else {
            $this->name = trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
        }

        $this->username = self::getStringOrNull($data, 'username');
        $this->isBot = self::getBool($data, 'is_bot');
        $this->lastActivityTime = self::getIntOrNull($data, 'last_activity_time');
        $this->description = self::getStringOrNull($data, 'description');
        $this->avatarUrl = self::getStringOrNull($data, 'avatar_url');
        $this->fullAvatarUrl = self::getStringOrNull($data, 'full_avatar_url');

        $rawCommands = self::getArrayOrNull($data, 'commands');
        if ($rawCommands !== null) {
            $commands = [];
            foreach ($rawCommands as $cmd) {
                if (\is_array($cmd)) {
                    /** @var array<string, mixed> $cmd */
                    $commands[] = $cmd;
                }
            }
            $this->commands = $commands;
        } else {
            $this->commands = null;
        }
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

    /**
     * Имя пользователя или бота (User.first_name).
     *
     * @since 2.4.0
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * Фамилия пользователя (User.last_name).
     *
     * @since 2.4.0
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
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

    /**
     * URL полноразмерного аватара (UserWithPhoto.full_avatar_url).
     *
     * @since 2.4.0
     */
    public function getFullAvatarUrl(): ?string
    {
        return $this->fullAvatarUrl;
    }

    /**
     * Список команд бота (BotInfo.commands).
     *
     * @return list<array<string, mixed>>|null
     * @since 2.4.0
     */
    public function getCommands(): ?array
    {
        return $this->commands;
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
            'first_name'         => $this->firstName,
            'last_name'          => $this->lastName,
            'username'           => $this->username,
            'is_bot'             => $this->isBot,
            'last_activity_time' => $this->lastActivityTime,
            'description'        => $this->description,
            'avatar_url'         => $this->avatarUrl,
            'full_avatar_url'    => $this->fullAvatarUrl,
            'commands'           => $this->commands,
        ];
    }
}
