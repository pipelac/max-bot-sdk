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
    private const QUALITY_PRIORITY = [
        'mp4_1080',
        'mp4_720',
        'mp4_480',
        'mp4_360',
        'mp4_240',
        'mp4_144',
        'hls',
    ];

    private readonly string $token;
    private readonly string $url;
    /** @var array<string, string>|null */
    private readonly ?array $urls;
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

        $rawUrls = self::getArrayOrNull($data, 'urls');
        if ($rawUrls !== null) {
            $parsedUrls = [];
            foreach ($rawUrls as $quality => $link) {
                if (\is_string($link) && $link !== '') {
                    $parsedUrls[(string) $quality] = $link;
                }
            }
            $this->urls = $parsedUrls !== [] ? $parsedUrls : null;
        } else {
            $this->urls = null;
        }

        $directUrl = self::getString($data, 'url');
        $this->url = $directUrl !== '' ? $directUrl : $this->resolveBestUrl($this->urls);
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

    /**
     * Карта доступных URL видео по качеству (VideoUrls: mp4_1080..mp4_144, hls).
     *
     * @return array<string, string>|null
     * @since 2.4.0
     */
    public function getUrls(): ?array
    {
        return $this->urls;
    }

    /**
     * Получить URL видео для конкретного качества (например, 'mp4_1080', 'mp4_720', 'hls').
     *
     * @since 2.4.0
     */
    public function getUrlByQuality(string $quality): ?string
    {
        return $this->urls[$quality] ?? null;
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
            'urls'      => $this->urls,
            'width'     => $this->width,
            'height'    => $this->height,
            'duration'  => $this->duration,
            'thumbnail' => $this->thumbnail,
        ];
    }

    /**
     * @param array<string, string>|null $urls
     */
    private function resolveBestUrl(?array $urls): string
    {
        if ($urls === null || $urls === []) {
            return '';
        }

        foreach (self::QUALITY_PRIORITY as $quality) {
            if (isset($urls[$quality])) {
                return $urls[$quality];
            }
        }

        $first = reset($urls);
        return \is_string($first) ? $first : '';
    }
}
