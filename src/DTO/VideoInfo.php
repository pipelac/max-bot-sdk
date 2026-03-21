<?php

namespace MaxBotSdk\DTO;

/**
 * Объект информации о видео.
 *
 * @since 1.0.0
 */
final class VideoInfo extends AbstractDto
{
    /** @var string */
    private $token;

    /** @var string */
    private $url;

    /** @var int|null */
    private $width;

    /** @var int|null */
    private $height;

    /** @var int|null */
    private $duration;

    /** @var array|null */
    private $thumbnail;

    /**
     * @param array $data
     */
    private function __construct(array $data)
    {
        $this->token = self::getString($data, 'token');
        $this->url = self::getString($data, 'url');
        $this->width = self::getIntOrNull($data, 'width');
        $this->height = self::getIntOrNull($data, 'height');
        $this->duration = self::getIntOrNull($data, 'duration');
        $this->thumbnail = self::getArrayOrNull($data, 'thumbnail');
    }

    /**
     * {@inheritdoc}
     */
    public static function fromArray(array $data)
    {
        return new self($data);
    }

    /** @return string */
    public function getToken()
    {
        return $this->token;
    }

    /** @return string */
    public function getUrl()
    {
        return $this->url;
    }

    /** @return int|null */
    public function getWidth()
    {
        return $this->width;
    }

    /** @return int|null */
    public function getHeight()
    {
        return $this->height;
    }

    /** @return int|null */
    public function getDuration()
    {
        return $this->duration;
    }

    /** @return array|null */
    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return [
            'token'     => $this->token,
            'url'       => $this->url,
            'width'     => $this->width,
            'height'    => $this->height,
            'duration'  => $this->duration,
            'thumbnail' => $this->thumbnail,
        ];
    }
}
