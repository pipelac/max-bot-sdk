<?php

declare(strict_types=1);

namespace MaxBotSdk\DTO;

/**
 * Результат void-операций API (delete, action, pin/unpin и т.д.).
 *
 * @since 1.0.0
 */
final class ActionResult extends AbstractDto
{
    private readonly bool $success;
    private readonly ?string $message;

    /**
     * @param array<string, mixed> $data
     */
    private function __construct(array $data)
    {
        $this->success = self::getBool($data, 'success', true);
        $this->message = self::getStringOrNull($data, 'message');
    }

    public static function fromArray(array $data): static
    {
        return new self($data);
    }

    public static function success(): self
    {
        return new self(['success' => true]);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
        ];
    }
}
