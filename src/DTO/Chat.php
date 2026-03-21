<?php

namespace App\Component\Max\DTO;

/**
 * Объект чата MAX.
 *
 * @since 1.0.0
 */
final class Chat extends AbstractDto
{
    /** @var int */
    private $chatId;

    /** @var string */
    private $type;

    /** @var string */
    private $status;

    /** @var string */
    private $title;

    /** @var string|null */
    private $description;

    /** @var int */
    private $participantsCount;

    /** @var int|null */
    private $ownerId;

    /** @var bool */
    private $isPublic;

    /** @var string|null */
    private $link;

    /** @var array|null */
    private $icon;

    /** @var int|null */
    private $lastEventTime;

    /**
     * @param array $data
     */
    private function __construct(array $data)
    {
        $this->chatId = self::getInt($data, 'chat_id');
        $this->type = self::getString($data, 'type');
        $this->status = self::getString($data, 'status');
        $this->title = self::getString($data, 'title');
        $this->description = self::getStringOrNull($data, 'description');
        $this->participantsCount = self::getInt($data, 'participants_count');
        $this->ownerId = self::getIntOrNull($data, 'owner_id');
        $this->isPublic = self::getBool($data, 'is_public');
        $this->link = self::getStringOrNull($data, 'link');
        $this->icon = self::getArrayOrNull($data, 'icon');
        $this->lastEventTime = self::getIntOrNull($data, 'last_event_time');
    }

    /**
     * {@inheritdoc}
     */
    public static function fromArray(array $data)
    {
        return new self($data);
    }

    /** @return int */
    public function getChatId()
    {
        return $this->chatId;
    }

    /** @return string */
    public function getType()
    {
        return $this->type;
    }

    /** @return string */
    public function getStatus()
    {
        return $this->status;
    }

    /** @return string */
    public function getTitle()
    {
        return $this->title;
    }

    /** @return string|null */
    public function getDescription()
    {
        return $this->description;
    }

    /** @return int */
    public function getParticipantsCount()
    {
        return $this->participantsCount;
    }

    /** @return int|null */
    public function getOwnerId()
    {
        return $this->ownerId;
    }

    /** @return bool */
    public function isPublic()
    {
        return $this->isPublic;
    }

    /** @return string|null */
    public function getLink()
    {
        return $this->link;
    }

    /** @return array|null */
    public function getIcon()
    {
        return $this->icon;
    }

    /** @return int|null */
    public function getLastEventTime()
    {
        return $this->lastEventTime;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return array(
            'chat_id'            => $this->chatId,
            'type'               => $this->type,
            'status'             => $this->status,
            'title'              => $this->title,
            'description'        => $this->description,
            'participants_count' => $this->participantsCount,
            'owner_id'           => $this->ownerId,
            'is_public'          => $this->isPublic,
            'link'               => $this->link,
            'icon'               => $this->icon,
            'last_event_time'    => $this->lastEventTime,
        );
    }
}
