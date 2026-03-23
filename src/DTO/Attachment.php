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

    /**
     * @param array<string, mixed> $data
     */
    private function __construct(array $data)
    {
        $this->type = self::getString($data, 'type');
        $this->payload = self::getArray($data, 'payload');
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

    public function toArray(): array
    {
        return [
            'type'    => $this->type,
            'payload' => $this->payload,
        ];
    }
}
