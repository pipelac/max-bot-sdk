<?php

declare(strict_types=1);

namespace MaxBotSdk\DTO;

/**
 * Объект участника чата.
 *
 * @since 1.0.0
 */
final class ChatMember extends AbstractDto
{
    private readonly int $userId;
    private readonly string $name;
    private readonly ?string $username;
    private readonly ?string $avatarUrl;
    private readonly bool $isOwner;
    private readonly bool $isAdmin;
    private readonly ?int $joinTime;
    /** @var array<string, mixed>|null */
    private readonly ?array $permissions;

    /**
     * @param array<string, mixed> $data
     */
    private function __construct(array $data)
    {
        $this->userId = self::getInt($data, 'user_id');
        $this->name = self::getString($data, 'name');
        $this->username = self::getStringOrNull($data, 'username');
        $this->avatarUrl = self::getStringOrNull($data, 'avatar_url');
        $this->isOwner = self::getBool($data, 'is_owner');
        $this->isAdmin = self::getBool($data, 'is_admin');
        $this->joinTime = self::getIntOrNull($data, 'join_time');
        $this->permissions = self::getArrayOrNull($data, 'permissions');
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

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function isOwner(): bool
    {
        return $this->isOwner;
    }

    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function getJoinTime(): ?int
    {
        return $this->joinTime;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPermissions(): ?array
    {
        return $this->permissions;
    }

    public function __toString(): string
    {
        return \sprintf('ChatMember#%d (%s)', $this->userId, $this->name);
    }

    public function toArray(): array
    {
        return [
            'user_id'     => $this->userId,
            'name'        => $this->name,
            'username'    => $this->username,
            'avatar_url'  => $this->avatarUrl,
            'is_owner'    => $this->isOwner,
            'is_admin'    => $this->isAdmin,
            'join_time'   => $this->joinTime,
            'permissions' => $this->permissions,
        ];
    }
}
