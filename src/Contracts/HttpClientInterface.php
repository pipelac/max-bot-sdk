<?php

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
     * @param string $method  HTTP-метод (GET, POST, PUT, PATCH, DELETE).
     * @param string $url     URL или путь эндпоинта.
     * @param array  $options Опции запроса (headers, json, query, multipart).
     * @return array Ассоциативный массив с ключами 'status_code' и 'body'.
     */
    public function request($method, $url, array $options = []);

    /**
     * Возвращает код последнего HTTP-ответа.
     *
     * @return int
     */
    public function getLastStatusCode();

    /**
     * Возвращает базовый URL API.
     *
     * @return string
     */
    public function getBaseUrl();
}
