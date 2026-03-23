<?php

declare(strict_types=1);

namespace MaxBotSdk\DTO;

/**
 * Объект подписки (webhook).
 *
 * @since 1.0.0
 */
final class Subscription extends AbstractDto
{
    private readonly string $url;
    private readonly ?int $time;
    /** @var list<string> */
    private readonly array $updateTypes;
    private readonly ?string $version;

    /**
     * @param array<string, mixed> $data
     */
    private function __construct(array $data)
    {
        $this->url = self::getString($data, 'url');
        $this->time = self::getIntOrNull($data, 'time');
        $this->updateTypes = array_values(array_map(
            static fn(mixed $v): string => \is_scalar($v) ? (string) $v : '',
            self::getArray($data, 'update_types'),
        ));
        $this->version = self::getStringOrNull($data, 'version');
    }

    public static function fromArray(array $data): static
    {
        return new self($data);
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getTime(): ?int
    {
        return $this->time;
    }

    /**
     * @return list<string>
     */
    public function getUpdateTypes(): array
    {
        return $this->updateTypes;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function toArray(): array
    {
        return [
            'url'          => $this->url,
            'time'         => $this->time,
            'update_types' => $this->updateTypes,
            'version'      => $this->version,
        ];
    }
}
