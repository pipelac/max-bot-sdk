<?php

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
     * @param string $endpoint Эндпоинт API (например, '/me').
     * @param array  $query    Параметры query string.
     * @return array Декодированный ответ API.
     */
    public function get($endpoint, array $query = []);

    /**
     * Выполняет POST-запрос к MAX Bot API.
     *
     * @param string     $endpoint Эндпоинт API.
     * @param array|null $json     Тело запроса (JSON).
     * @param array      $query    Параметры query string.
     * @return array Декодированный ответ API.
     */
    public function post($endpoint, array $json = null, array $query = []);

    /**
     * Выполняет PUT-запрос к MAX Bot API.
     *
     * @param string     $endpoint Эндпоинт API.
     * @param array|null $json     Тело запроса (JSON).
     * @param array      $query    Параметры query string.
     * @return array Декодированный ответ API.
     */
    public function put($endpoint, array $json = null, array $query = []);

    /**
     * Выполняет PATCH-запрос к MAX Bot API.
     *
     * @param string     $endpoint Эндпоинт API.
     * @param array|null $json     Тело запроса (JSON).
     * @param array      $query    Параметры query string.
     * @return array Декодированный ответ API.
     */
    public function patch($endpoint, array $json = null, array $query = []);

    /**
     * Выполняет DELETE-запрос к MAX Bot API.
     *
     * @param string $endpoint Эндпоинт API.
     * @param array  $query    Параметры query string.
     * @return array Декодированный ответ API.
     */
    public function delete($endpoint, array $query = []);

    /**
     * Получает экземпляр HTTP-клиента.
     *
     * @return HttpClientInterface
     */
    public function getHttpClient();

    /**
     * Получает объект конфигурации.
     *
     * @return ConfigInterface
     */
    public function getConfig();
}
