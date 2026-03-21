<?php

namespace App\Component\Max\Resource;

use App\Component\Max\Contracts\ClientInterface;
use App\Component\Max\Contracts\ResourceInterface;
use App\Component\Max\Utils\InputValidator;

/**
 * Абстрактный базовый класс для ресурсов MAX Bot API.
 *
 * Предоставляет общую точку доступа к Client (через интерфейс) и унифицированные
 * методы для HTTP-запросов с валидацией.
 *
 * @since 1.0.0
 */
abstract class ResourceAbstract implements ResourceInterface
{
    /**
     * @var ClientInterface Экземпляр клиента.
     */
    protected $client;

    /**
     * @param ClientInterface $client Клиент MAX Bot API.
     */
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * GET-запрос через Client.
     *
     * @param string $endpoint Эндпоинт.
     * @param array  $query    Query-параметры.
     * @return array
     */
    protected function get($endpoint, array $query = array())
    {
        return $this->client->get($endpoint, $query);
    }

    /**
     * POST-запрос через Client.
     *
     * @param string     $endpoint Эндпоинт.
     * @param array|null $json     JSON-тело.
     * @param array      $query    Query-параметры.
     * @return array
     */
    protected function post($endpoint, array $json = null, array $query = array())
    {
        return $this->client->post($endpoint, $json, $query);
    }

    /**
     * PUT-запрос через Client.
     *
     * @param string     $endpoint Эндпоинт.
     * @param array|null $json     JSON-тело.
     * @param array      $query    Query-параметры.
     * @return array
     */
    protected function put($endpoint, array $json = null, array $query = array())
    {
        return $this->client->put($endpoint, $json, $query);
    }

    /**
     * PATCH-запрос через Client.
     *
     * @param string     $endpoint Эндпоинт.
     * @param array|null $json     JSON-тело.
     * @param array      $query    Query-параметры.
     * @return array
     */
    protected function patch($endpoint, array $json = null, array $query = array())
    {
        return $this->client->patch($endpoint, $json, $query);
    }

    /**
     * DELETE-запрос через Client.
     *
     * @param string $endpoint Эндпоинт.
     * @param array  $query    Query-параметры.
     * @return array
     */
    protected function delete($endpoint, array $query = array())
    {
        return $this->client->delete($endpoint, $query);
    }

    /**
     * Валидирует числовой ID.
     *
     * @param mixed  $id   Значение.
     * @param string $name Имя параметра.
     * @return int
     */
    protected function validateId($id, $name = 'ID')
    {
        return InputValidator::validateId($id, $name);
    }

    /**
     * Валидирует текст сообщения.
     *
     * @param string $text Текст.
     * @return string
     */
    protected function validateText($text)
    {
        return InputValidator::validateText($text);
    }
}
