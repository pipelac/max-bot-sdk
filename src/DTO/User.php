<?php

namespace App\Component\Max\DTO;

/**
 * Объект пользователя/бота MAX.
 *
 * @since 1.0.0
 */
final class User extends AbstractDto
{
    /** @var int */
    private $userId;

    /** @var string */
    private $name;

    /** @var string|null */
    private $username;

    /** @var bool */
    private $isBot;

    /** @var int|null */
    private $lastActivityTime;

    /** @var string|null */
    private $description;

    /** @var string|null */
    private $avatarUrl;

    /**
     * @param array $data Данные из API.
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

    /** @return bool */
    public function isBot()
    {
        return $this->isBot;
    }

    /** @return int|null */
    public function getLastActivityTime()
    {
        return $this->lastActivityTime;
    }

    /** @return string|null */
    public function getDescription()
    {
        return $this->description;
    }

    /** @return string|null */
    public function getAvatarUrl()
    {
        return $this->avatarUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return array(
            'user_id'            => $this->userId,
            'name'               => $this->name,
            'username'           => $this->username,
            'is_bot'             => $this->isBot,
            'last_activity_time' => $this->lastActivityTime,
            'description'        => $this->description,
            'avatar_url'         => $this->avatarUrl,
        );
    }
}
