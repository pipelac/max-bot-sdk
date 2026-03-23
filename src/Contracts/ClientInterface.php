<?php

declare(strict_types=1);

namespace MaxBotSdk\Contracts;

/**
 * Интерфейс клиента MAX Bot API.
 *
 * Определяет контракт для работы с REST API MAX.
 * Предоставляет низкоуровневые HTTP-методы и доступ к ресурсам.
 *
 * @since 1.0.0
 */
interface ClientInterface
{
    /**
     * Выполняет GET-запрос к MAX Bot API.
     *
     * @param array<string, mixed> $query Параметры query string.
     * @return array<string, mixed> Декодированный ответ API.
     */
    public function get(string $endpoint, array $query = []): array;

    /**
     * Выполняет POST-запрос к MAX Bot API.
     *
     * @param array<string, mixed>|null $json  Тело запроса (JSON).
     * @param array<string, mixed>      $query Параметры query string.
     * @return array<string, mixed> Декодированный ответ API.
     */
    public function post(string $endpoint, ?array $json = null, array $query = []): array;

    /**
     * Выполняет PUT-запрос к MAX Bot API.
     *
     * @param array<string, mixed>|null $json  Тело запроса (JSON).
     * @param array<string, mixed>      $query Параметры query string.
     * @return array<string, mixed> Декодированный ответ API.
     */
    public function put(string $endpoint, ?array $json = null, array $query = []): array;

    /**
     * Выполняет PATCH-запрос к MAX Bot API.
     *
     * @param array<string, mixed>|null $json  Тело запроса (JSON).
     * @param array<string, mixed>      $query Параметры query string.
     * @return array<string, mixed> Декодированный ответ API.
     */
    public function patch(string $endpoint, ?array $json = null, array $query = []): array;

    /**
     * Выполняет DELETE-запрос к MAX Bot API.
     *
     * @param array<string, mixed> $query Параметры query string.
     * @return array<string, mixed> Декодированный ответ API.
     */
    public function delete(string $endpoint, array $query = []): array;

    /**
     * Получает экземпляр HTTP-клиента.
     */
    public function getHttpClient(): HttpClientInterface;

    /**
     * Получает объект конфигурации.
     */
    public function getConfig(): ConfigInterface;
}
