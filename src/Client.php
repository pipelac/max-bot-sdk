<?php

namespace MaxBotSdk;

use MaxBotSdk\Contracts\ClientInterface;
use MaxBotSdk\Contracts\ConfigInterface;
use MaxBotSdk\Contracts\HttpClientInterface;
use MaxBotSdk\Contracts\LoggerInterface;
use MaxBotSdk\Contracts\ResponseDecoderInterface;
use MaxBotSdk\Http\RateLimiter;
use MaxBotSdk\Http\RetryHandler;
use MaxBotSdk\Resource\Bot;

// Resources
use MaxBotSdk\Resource\Callbacks;
use MaxBotSdk\Resource\Chats;
use MaxBotSdk\Resource\Members;
use MaxBotSdk\Resource\Messages;
use MaxBotSdk\Resource\Subscriptions;
use MaxBotSdk\Resource\Uploads;
use MaxBotSdk\Utils\InputValidator;

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

    /** @var RateLimiter|null Rate limiter. */
    private $rateLimiter;

    /** @var array Кэш инстансов ресурсов (ленивая инициализация). */
    private $resourceInstances = [];

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

        // Rate Limiter: инициализируем если настроен лимит
        $rateLimit = $config->getRateLimit();
        $this->rateLimiter = ($rateLimit > 0) ? new RateLimiter($rateLimit) : null;

        $this->log('debug', 'Клиент инициализирован', [
            'token_masked' => InputValidator::maskToken($config->getToken()),
        ]);
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
    public function get($endpoint, array $query = [])
    {
        return $this->performRequest('GET', $endpoint, null, $query);
    }

    /**
     * {@inheritdoc}
     */
    public function post($endpoint, array $json = null, array $query = [])
    {
        return $this->performRequest('POST', $endpoint, $json, $query);
    }

    /**
     * {@inheritdoc}
     */
    public function put($endpoint, array $json = null, array $query = [])
    {
        return $this->performRequest('PUT', $endpoint, $json, $query);
    }

    /**
     * {@inheritdoc}
     */
    public function patch($endpoint, array $json = null, array $query = [])
    {
        return $this->performRequest('PATCH', $endpoint, $json, $query);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($endpoint, array $query = [])
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
    private function performRequest($method, $endpoint, $json = null, array $query = [])
    {
        $httpClient = $this->httpClient;
        $decoder = $this->responseDecoder;

        $startTime = microtime(true);

        // Rate limiting
        if ($this->rateLimiter !== null) {
            $this->rateLimiter->throttle();
        }

        $result = $this->retryHandler->execute(function () use ($method, $endpoint, $json, $query, $httpClient, $decoder) {
            $options = [];

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

        $this->log('debug', 'Ответ API', [
            'method'   => $method,
            'endpoint' => $endpoint,
            'duration' => $duration,
        ]);

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
    private function log($level, $message, array $context = [])
    {
        if ($this->logger === null) {
            return;
        }

        $allowed = ['debug', 'info', 'warning', 'error'];
        if (!in_array($level, $allowed, true)) {
            $level = 'debug';
        }

        $prefix = $this->config->getAppName() . ': ';
        $this->logger->$level($prefix . $message, $context);
    }
}
