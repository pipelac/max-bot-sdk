<?php

namespace MaxBotSdk\Tests\Helper;

use MaxBotSdk\Contracts\HttpClientInterface;

/**
 * Мок HTTP-клиента для тестирования MAX SDK.
 *
 * Позволяет предопределять ответы на запросы и записывать историю
 * вызовов для последующей проверки в тестах.
 */
class MockHttpClient implements HttpClientInterface
{
    /** @var array Очередь ответов. */
    private $responses = [];

    /** @var array История запросов. */
    private $requests = [];

    /** @var int Индекс текущего ответа. */
    private $currentResponseIndex = 0;

    /** @var int Последний HTTP-код. */
    private $lastStatusCode = 200;

    /**
     * Добавляет предопределённый ответ в очередь.
     *
     * @param int    $statusCode HTTP-код ответа.
     * @param string $body       JSON-тело ответа.
     * @return self
     */
    public function setResponse($statusCode, $body = '{}')
    {
        $this->responses[] = [
            'status_code' => $statusCode,
            'body'        => $body,
        ];
        return $this;
    }

    /**
     * Возвращает все выполненные запросы.
     *
     * @return array
     */
    public function getRequests()
    {
        return $this->requests;
    }

    /**
     * Возвращает последний запрос.
     *
     * @return array|null
     */
    public function getLastRequest()
    {
        $count = count($this->requests);
        return $count > 0 ? $this->requests[$count - 1] : null;
    }

    /**
     * Очищает историю запросов и очередь ответов.
     */
    public function reset()
    {
        $this->requests = [];
        $this->responses = [];
        $this->currentResponseIndex = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function request($method, $url, array $options = [])
    {
        $this->requests[] = [
            'method'  => $method,
            'url'     => $url,
            'options' => $options,
        ];

        if (isset($this->responses[$this->currentResponseIndex])) {
            $response = $this->responses[$this->currentResponseIndex];
            $this->currentResponseIndex++;
        } else {
            $response = [
                'status_code' => 200,
                'body'        => '{}',
            ];
        }

        $this->lastStatusCode = $response['status_code'];

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastStatusCode()
    {
        return $this->lastStatusCode;
    }

    /**
     * {@inheritdoc}
     */
    public function getBaseUrl()
    {
        return 'https://platform-api.max.ru';
    }
}
