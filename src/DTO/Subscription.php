<?php

namespace App\Component\Max\DTO;

/**
 * Объект подписки (webhook).
 *
 * @since 1.0.0
 */
final class Subscription extends AbstractDto
{
    /** @var string */
    private $url;

    /** @var int|null */
    private $time;

    /** @var array */
    private $updateTypes;

    /** @var string|null */
    private $version;

    /**
     * @param array $data
     */
    private function __construct(array $data)
    {
        $this->url = self::getString($data, 'url');
        $this->time = self::getIntOrNull($data, 'time');
        $this->updateTypes = self::getArray($data, 'update_types');
        $this->version = self::getStringOrNull($data, 'version');
    }

    /**
     * {@inheritdoc}
     */
    public static function fromArray(array $data)
    {
        return new self($data);
    }

    /** @return string */
    public function getUrl()
    {
        return $this->url;
    }

    /** @return int|null */
    public function getTime()
    {
        return $this->time;
    }

    /** @return array */
    public function getUpdateTypes()
    {
        return $this->updateTypes;
    }

    /** @return string|null */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return array(
            'url'          => $this->url,
            'time'         => $this->time,
            'update_types' => $this->updateTypes,
            'version'      => $this->version,
        );
    }
}
