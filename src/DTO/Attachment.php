<?php

declare(strict_types=1);

namespace MaxBotSdk\DTO;

/**
 * Объект вложения (attachment) сообщения.
 *
 * @since 1.0.0
 */
final class Attachment extends AbstractDto
{
    private readonly string $type;
    /** @var array<string, mixed> */
    private readonly array $payload;
    /** @var array<string, mixed> */
    private readonly array $rawData;
    private readonly ?string $filename;
    private readonly ?int $size;
    private readonly ?int $width;
    private readonly ?int $height;
    private readonly ?int $duration;
    private readonly ?string $videoType;
    /** @var array<string, mixed>|null */
    private readonly ?array $thumbnail;
    private readonly ?float $latitude;
    private readonly ?float $longitude;
    private readonly ?string $transcription;
    private readonly ?string $title;
    private readonly ?string $description;
    private readonly ?string $imageUrl;

    /**
     * @param array<string, mixed> $data
     */
    private function __construct(array $data)
    {
        $this->rawData = $data;
        $this->type = self::getString($data, 'type');
        $this->payload = self::getArray($data, 'payload');
        $this->filename = self::getStringOrNull($data, 'filename');
        $this->size = self::getIntOrNull($data, 'size');
        $this->width = self::getIntOrNull($data, 'width');
        $this->height = self::getIntOrNull($data, 'height');
        $this->duration = self::getIntOrNull($data, 'duration');
        $this->videoType = self::getStringOrNull($data, 'video_type');
        $this->thumbnail = self::getArrayOrNull($data, 'thumbnail');
        $this->latitude = self::getFloatOrNull($data, 'latitude');
        $this->longitude = self::getFloatOrNull($data, 'longitude');
        $this->transcription = self::getStringOrNull($data, 'transcription');
        $this->title = self::getStringOrNull($data, 'title');
        $this->description = self::getStringOrNull($data, 'description');
        $this->imageUrl = self::getStringOrNull($data, 'image_url');
    }

    public static function fromArray(array $data): static
    {
        return new self($data);
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getPayloadValue(string $key, mixed $default = null): mixed
    {
        return $this->payload[$key] ?? $default;
    }

    /**
     * Получить произвольное поле верхнего уровня вложения.
     *
     * @since 2.4.0
     */
    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->rawData[$key] ?? $default;
    }

    /**
     * @since 2.4.0
     */
    public function getFilename(): ?string
    {
        return $this->filename;
    }

    /**
     * @since 2.4.0
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * @since 2.4.0
     */
    public function getWidth(): ?int
    {
        return $this->width;
    }

    /**
     * @since 2.4.0
     */
    public function getHeight(): ?int
    {
        return $this->height;
    }

    /**
     * Длительность медиа-вложения в секундах (VideoAttachment.duration).
     *
     * @since 2.4.0
     */
    public function getDuration(): ?int
    {
        return $this->duration;
    }

    /**
     * Тип видео-вложения ('video' | 'video_message').
     *
     * @since 2.4.0
     */
    public function getVideoType(): ?string
    {
        return $this->videoType;
    }

    /**
     * @return array<string, mixed>|null
     * @since 2.4.0
     */
    public function getThumbnail(): ?array
    {
        return $this->thumbnail;
    }

    /**
     * @since 2.4.0
     */
    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    /**
     * @since 2.4.0
     */
    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    /**
     * Расшифровка аудиосообщения (AudioAttachment.transcription).
     *
     * @since 2.4.0
     */
    public function getTranscription(): ?string
    {
        return $this->transcription;
    }

    /**
     * Заголовок превью ссылки (ShareAttachment.title).
     *
     * @since 2.4.0
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Описание превью ссылки (ShareAttachment.description).
     *
     * @since 2.4.0
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * URL изображения превью (ShareAttachment.image_url).
     *
     * @since 2.4.0
     */
    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function toArray(): array
    {
        return array_merge($this->rawData, [
            'type'    => $this->type,
            'payload' => $this->payload,
        ]);
    }
}
