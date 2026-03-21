<?php

namespace App\Component\Max;

use App\Component\Max\Contracts\ClientInterface;
use App\Component\Max\Contracts\ConfigInterface;
use App\Component\Max\Contracts\HttpClientInterface;
use App\Component\Max\Contracts\LoggerInterface;
use App\Component\Max\Contracts\ResponseDecoderInterface;
use App\Component\Max\Http\RetryHandler;
use App\Component\Max\Utils\InputValidator;

// Resources
use App\Component\Max\Resource\Bot;
use App\Component\Max\Resource\Callbacks;
use App\Component\Max\Resource\Chats;
use App\Component\Max\Resource\Members;
use App\Component\Max\Resource\Messages;
use App\Component\Max\Resource\Subscriptions;
use App\Component\Max\Resource\Uploads;

/**
 * Основной клиент MAX Bot API SDK.
 *
 * Фасад для всех операций с MAX Bot API. Все зависимости инжектируются
 * через конструктор (чистый DI), управляет ресурсами через ленивую инициализацию.
 *
 * Пример:
 * <code>
 * $client = ClientFactory::create('TOKEN');
 * $me = $client->bot()->getMe();              // Возвращает User DTO
 * $client->messages()->sendText('Привет!', $chatId);
 * </code>
 *
 * @since 1.0.0
 */
final class Client implements ClientInterface
{
    /** @var ConfigInterface Конфигурация SDK. */
    private $config;

    /** @var HttpClientInterface HTTP-клиент. */
    private $httpClient;

    /** @var ResponseDecoderInterface Декодер ответов. */
    private $responseDecoder;

    /** @var RetryHandler Обработчик повторных попыток. */
    private $retryHandler;

    /** @var LoggerInterface|null Логгер. */
    private $logger;

    /** @var array Кэш инстансов ресурсов (ленивая инициализация). */
    private $resourceInstances = array();

    /**
     * Конструктор с чистым Dependency Injection.
     *
     * @param ConfigInterface          $config          Конфигурация SDK.
     * @param HttpClientInterface      $httpClient      HTTP-клиент.
     * @param ResponseDecoderInterface $responseDecoder Декодер ответов.
     * @param RetryHandler             $retryHandler    Обработчик повторов.
     */
    public function __construct(
        ConfigInterface $config,
        HttpClientInterface $httpClient,
        ResponseDecoderInterface $responseDecoder,
        RetryHandler $retryHandler
    ) {
        $this->config = $config;
        $this->logger = $config->getLogger();
        $this->httpClient = $httpClient;
        $this->responseDecoder = $responseDecoder;
        $this->retryHandler = $retryHandler;

        $this->log('debug', 'Клиент инициализирован', array(
            'token_masked' => InputValidator::maskToken($config->getToken()),
        ));
    }

    // --- Явные методы доступа к ресурсам ---

    /**
     * Ресурс: информация о боте.
     *
     * @return Bot
     */
    public function bot()
    {
        return $this->getResource(Bot::class);
    }

    /**
     * Ресурс: управление чатами.
     *
     * @return Chats
     */
    public function chats()
    {
        return $this->getResource(Chats::class);
    }

    /**
     * Ресурс: управление участниками.
     *
     * @return Members
     */
    public function members()
    {
        return $this->getResource(Members::class);
    }

    /**
     * Ресурс: работа с сообщениями.
     *
     * @return Messages
     */
    public function messages()
    {
        return $this->getResource(Messages::class);
    }

    /**
     * Ресурс: подписки (webhook/polling).
     *
     * @return Subscriptions
     */
    public function subscriptions()
    {
        return $this->getResource(Subscriptions::class);
    }

    /**
     * Ресурс: загрузка файлов.
     *
     * @return Uploads
     */
    public function uploads()
    {
        return $this->getResource(Uploads::class);
    }

    /**
     * Ресурс: обработка callback-ов.
     *
     * @return Callbacks
     */
    public function callbacks()
    {
        return $this->getResource(Callbacks::class);
    }

    // --- HTTP-методы (делегирование) ---

    /**
     * {@inheritdoc}
     */
    public function get($endpoint, array $query = array())
    {
        return $this->performRequest('GET', $endpoint, null, $query);
    }

    /**
     * {@inheritdoc}
     */
    public function post($endpoint, array $json = null, array $query = array())
    {
        return $this->performRequest('POST', $endpoint, $json, $query);
    }

    /**
     * {@inheritdoc}
     */
    public function put($endpoint, array $json = null, array $query = array())
    {
        return $this->performRequest('PUT', $endpoint, $json, $query);
    }

    /**
     * {@inheritdoc}
     */
    public function patch($endpoint, array $json = null, array $query = array())
    {
        return $this->performRequest('PATCH', $endpoint, $json, $query);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($endpoint, array $query = array())
    {
        return $this->performRequest('DELETE', $endpoint, null, $query);
    }

    /**
     * {@inheritdoc}
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return $this->config;
    }

    // --- Приватные методы ---

    /**
     * Выполняет HTTP-запрос к API с retry-логикой.
     *
     * @param string     $method   HTTP-метод.
     * @param string     $endpoint Эндпоинт.
     * @param array|null $json     JSON-тело.
     * @param array      $query    Query-параметры.
     * @return array Декодированный ответ.
     */
    private function performRequest($method, $endpoint, $json = null, array $query = array())
    {
        $httpClient = $this->httpClient;
        $decoder = $this->responseDecoder;

        $startTime = microtime(true);

        $result = $this->retryHandler->execute(function () use ($method, $endpoint, $json, $query, $httpClient, $decoder) {
            $options = array();

            if ($json !== null) {
                $options['json'] = $json;
            }
            if (!empty($query)) {
                $options['query'] = $query;
            }

            $response = $httpClient->request($method, $endpoint, $options);

            return $decoder->decode(
                $response['status_code'],
                $response['body'],
                $method,
                $endpoint
            );
        });

        $duration = round(microtime(true) - $startTime, 3);

        $this->log('debug', 'Ответ API', array(
            'method'   => $method,
            'endpoint' => $endpoint,
            'duration' => $duration,
        ));

        return $result;
    }

    /**
     * Получает экземпляр ресурса с ленивой инициализацией.
     *
     * @param string $resourceClass Полное имя класса.
     * @return mixed
     */
    private function getResource($resourceClass)
    {
        if (!isset($this->resourceInstances[$resourceClass])) {
            $this->resourceInstances[$resourceClass] = new $resourceClass($this);
        }
        return $this->resourceInstances[$resourceClass];
    }

    /**
     * Логирование с префиксом имени приложения.
     *
     * @param string $level   Уровень (debug, info, warning, error).
     * @param string $message Сообщение.
     * @param array  $context Контекст.
     */
    private function log($level, $message, array $context = array())
    {
        if ($this->logger === null) {
            return;
        }

        $allowed = array('debug', 'info', 'warning', 'error');
        if (!in_array($level, $allowed, true)) {
            $level = 'debug';
        }

        $prefix = $this->config->getAppName() . ': ';
        $this->logger->$level($prefix . $message, $context);
    }
}
