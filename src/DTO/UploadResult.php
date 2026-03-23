<?php

declare(strict_types=1);

namespace MaxBotSdk\DTO;

/**
 * Результат загрузки файла.
 *
 * @since 1.0.0
 */
final class UploadResult extends AbstractDto
{
    private readonly string $url;
    private readonly string $token;

    /**
     * @param array<string, mixed> $data
     */
    private function __construct(array $data)
    {
        $this->url = self::getString($data, 'url');
        $this->token = self::getString($data, 'token');
    }

    public static function fromArray(array $data): static
    {
        return new self($data);
    }

    public static function fromUrl(string $url): self
    {
        return new self(['url' => $url]);
    }

    public static function fromToken(string $token): self
    {
        return new self(['token' => $token]);
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function toArray(): array
    {
        return [
            'url'   => $this->url,
            'token' => $this->token,
        ];
    }
}
