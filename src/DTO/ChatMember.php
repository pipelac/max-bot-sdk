<?php

namespace App\Component\Max\DTO;

/**
 * Объект участника чата.
 *
 * @since 1.0.0
 */
final class ChatMember extends AbstractDto
{
    /** @var int */
    private $userId;

    /** @var string */
    private $name;

    /** @var string|null */
    private $username;

    /** @var string|null */
    private $avatarUrl;

    /** @var bool */
    private $isOwner;

    /** @var bool */
    private $isAdmin;

    /** @var int|null */
    private $joinTime;

    /** @var array|null */
    private $permissions;

    /**
     * @param array $data
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

    /**
     * {@inheritdoc}
     */
    public static function fromArray(array $data)
    {
        return new self($data);
    }

    /** @return int */
    public function getUserId()
    {
        return $this->userId;
    }

    /** @return string */
    public function getName()
    {
        return $this->name;
    }

    /** @return string|null */
    public function getUsername()
    {
        return $this->username;
    }

    /** @return string|null */
    public function getAvatarUrl()
    {
        return $this->avatarUrl;
    }

    /** @return bool */
    public function isOwner()
    {
        return $this->isOwner;
    }

    /** @return bool */
    public function isAdmin()
    {
        return $this->isAdmin;
    }

    /** @return int|null */
    public function getJoinTime()
    {
        return $this->joinTime;
    }

    /** @return array|null */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return array(
            'user_id'     => $this->userId,
            'name'        => $this->name,
            'username'    => $this->username,
            'avatar_url'  => $this->avatarUrl,
            'is_owner'    => $this->isOwner,
            'is_admin'    => $this->isAdmin,
            'join_time'   => $this->joinTime,
            'permissions' => $this->permissions,
        );
    }
}
