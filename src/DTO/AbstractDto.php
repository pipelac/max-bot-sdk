<?php

declare(strict_types=1);

namespace MaxBotSdk\DTO;

/**
 * Абстрактный базовый класс для всех DTO MAX Bot API.
 *
 * @since 1.0.0
 */
abstract class AbstractDto
{
    /**
     * Создаёт DTO из массива данных API.
     *
     * @param array<string, mixed> $data
     */
    abstract public static function fromArray(array $data): static;

    /**
     * Преобразует DTO обратно в массив.
     *
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;

    /**
     * @param array<string, mixed> $data
     */
    protected static function getString(array $data, string $key, string $default = ''): string
    {
        return isset($data[$key]) && \is_scalar($data[$key]) ? (string) $data[$key] : $default;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected static function getStringOrNull(array $data, string $key): ?string
    {
        return isset($data[$key]) && \is_scalar($data[$key]) ? (string) $data[$key] : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected static function getInt(array $data, string $key, int $default = 0): int
    {
        return isset($data[$key]) && \is_scalar($data[$key]) ? (int) $data[$key] : $default;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected static function getIntOrNull(array $data, string $key): ?int
    {
        return isset($data[$key]) && \is_scalar($data[$key]) ? (int) $data[$key] : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected static function getBool(array $data, string $key, bool $default = false): bool
    {
        return isset($data[$key]) ? (bool) $data[$key] : $default;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected static function getArray(array $data, string $key): array
    {
        return isset($data[$key]) && \is_array($data[$key]) ? $data[$key] : [];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>|null
     */
    protected static function getArrayOrNull(array $data, string $key): ?array
    {
        return isset($data[$key]) && \is_array($data[$key]) ? $data[$key] : null;
    }
}
