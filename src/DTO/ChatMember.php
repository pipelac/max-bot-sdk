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
    private readonly ?string $firstName;
    private readonly ?string $lastName;
    private readonly ?string $username;
    private readonly ?string $avatarUrl;
    private readonly ?string $fullAvatarUrl;
    private readonly bool $isOwner;
    private readonly bool $isAdmin;
    private readonly ?int $joinTime;
    private readonly ?int $lastAccessTime;
    /** @var array<string, mixed>|null */
    private readonly ?array $permissions;
    private readonly ?string $alias;

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
        $this->avatarUrl = self::getStringOrNull($data, 'avatar_url');
        $this->fullAvatarUrl = self::getStringOrNull($data, 'full_avatar_url');
        $this->isOwner = self::getBool($data, 'is_owner');
        $this->isAdmin = self::getBool($data, 'is_admin');
        $this->joinTime = self::getIntOrNull($data, 'join_time');
        $this->lastAccessTime = self::getIntOrNull($data, 'last_access_time');
        $this->permissions = self::getArrayOrNull($data, 'permissions');
        $this->alias = self::getStringOrNull($data, 'alias');
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
     * @since 2.4.0
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
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

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    /**
     * @since 2.4.0
     */
    public function getFullAvatarUrl(): ?string
    {
        return $this->fullAvatarUrl;
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
     * Время последнего доступа участника (ChatMember.last_access_time).
     *
     * @since 2.4.0
     */
    public function getLastAccessTime(): ?int
    {
        return $this->lastAccessTime;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPermissions(): ?array
    {
        return $this->permissions;
    }

    /**
     * Отображаемый титул администратора (ChatMember.alias).
     *
     * @since 2.4.0
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function __toString(): string
    {
        return \sprintf('ChatMember#%d (%s)', $this->userId, $this->name);
    }

    public function toArray(): array
    {
        return [
            'user_id'          => $this->userId,
            'name'             => $this->name,
            'first_name'       => $this->firstName,
            'last_name'        => $this->lastName,
            'username'         => $this->username,
            'avatar_url'       => $this->avatarUrl,
            'full_avatar_url'  => $this->fullAvatarUrl,
            'is_owner'         => $this->isOwner,
            'is_admin'         => $this->isAdmin,
            'join_time'        => $this->joinTime,
            'last_access_time' => $this->lastAccessTime,
            'permissions'      => $this->permissions,
            'alias'            => $this->alias,
        ];
    }
}
