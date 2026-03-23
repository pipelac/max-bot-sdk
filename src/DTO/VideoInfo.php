<?php

declare(strict_types=1);

namespace MaxBotSdk\DTO;

/**
 * Объект информации о видео.
 *
 * @since 1.0.0
 */
final class VideoInfo extends AbstractDto
{
    private readonly string $token;
    private readonly string $url;
    private readonly ?int $width;
    private readonly ?int $height;
    private readonly ?int $duration;
    /** @var array<string, mixed>|null */
    private readonly ?array $thumbnail;

    /**
     * @param array<string, mixed> $data
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

    public static function fromArray(array $data): static
    {
        return new self($data);
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getThumbnail(): ?array
    {
        return $this->thumbnail;
    }

    public function toArray(): array
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
