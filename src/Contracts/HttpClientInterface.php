<?php

declare(strict_types=1);

namespace MaxBotSdk\Contracts;

/**
 * Интерфейс HTTP-клиента для MAX Bot API.
 *
 * Абстрагирует HTTP-транспорт, позволяя подменять реализацию
 * (например, для модульного тестирования).
 *
 * @since 1.0.0
 */
interface HttpClientInterface
{
    /**
     * Выполняет HTTP-запрос к MAX Bot API.
     *
     * @param array<string, mixed> $options Опции запроса (headers, json, query, multipart).
     * @return array{status_code: int, body: string} Ответ c кодом и телом.
     */
    public function request(string $method, string $url, array $options = []): array;

    /**
     * Возвращает код последнего HTTP-ответа.
     */
    public function getLastStatusCode(): int;

    /**
     * Возвращает базовый URL API.
     */
    public function getBaseUrl(): string;
}
