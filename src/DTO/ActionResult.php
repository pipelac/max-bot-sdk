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
    private readonly ?string $code;

    /**
     * @param array<string, mixed> $data
     */
    private function __construct(array $data)
    {
        $this->success = self::getBool($data, 'success', true);
        $this->message = self::getStringOrNull($data, 'message');
        $this->code = self::getStringOrNull($data, 'code');
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

    /**
     * Вернул ли запрос ошибку (success === false).
     *
     * @since 2.4.0
     */
    public function hasError(): bool
    {
        return !$this->success;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Машиночитаемый код ошибки API (SimpleQueryResult.code, добавлен в ноябре 2026 / OpenAPI v0.0.34).
     *
     * @since 2.4.0
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'code'    => $this->code,
        ];
    }
}
