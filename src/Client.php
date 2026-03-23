<?php

declare(strict_types=1);

namespace MaxBotSdk;

use MaxBotSdk\Contracts\ClientInterface;
use MaxBotSdk\Contracts\ConfigInterface;
use MaxBotSdk\Contracts\HttpClientInterface;
use MaxBotSdk\Contracts\LoggerInterface;
use MaxBotSdk\Contracts\ResponseDecoderInterface;
use MaxBotSdk\Enum\LogLevel;
use MaxBotSdk\Http\RateLimiter;
use MaxBotSdk\Http\RetryHandler;
use MaxBotSdk\Resource\Bot;
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
 * @since 1.0.0
 */
final class Client implements ClientInterface
{
    private readonly ?LoggerInterface $logger;
    private readonly ?RateLimiter $rateLimiter;

    /** @var array<class-string, object> */
    private array $resourceInstances = [];

    public function __construct(
        private readonly ConfigInterface $config,
        private readonly HttpClientInterface $httpClient,
        private readonly ResponseDecoderInterface $responseDecoder,
        private readonly RetryHandler $retryHandler,
    ) {
        $this->logger = $config->getLogger();
        $rateLimit = $config->getRateLimit();
        $this->rateLimiter = $rateLimit > 0 ? new RateLimiter($rateLimit) : null;
    }

    // ─── HTTP методы ─────────────────────────────────────────────

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function get(string $endpoint, array $query = []): array
    {
        return $this->request('GET', $endpoint, ['query' => $query]);
    }

    /**
     * @param array<string, mixed>|null $json
     * @param array<string, mixed>      $query
     * @return array<string, mixed>
     */
    public function post(string $endpoint, ?array $json = null, array $query = []): array
    {
        $options = ['query' => $query];
        if ($json !== null) {
            $options['json'] = $json;
        }
        return $this->request('POST', $endpoint, $options);
    }

    /**
     * @param array<string, mixed>|null $json
     * @param array<string, mixed>      $query
     * @return array<string, mixed>
     */
    public function put(string $endpoint, ?array $json = null, array $query = []): array
    {
        $options = ['query' => $query];
        if ($json !== null) {
            $options['json'] = $json;
        }
        return $this->request('PUT', $endpoint, $options);
    }

    /**
     * @param array<string, mixed>|null $json
     * @param array<string, mixed>      $query
     * @return array<string, mixed>
     */
    public function patch(string $endpoint, ?array $json = null, array $query = []): array
    {
        $options = ['query' => $query];
        if ($json !== null) {
            $options['json'] = $json;
        }
        return $this->request('PATCH', $endpoint, $options);
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function delete(string $endpoint, array $query = []): array
    {
        return $this->request('DELETE', $endpoint, ['query' => $query]);
    }

    public function getHttpClient(): HttpClientInterface
    {
        return $this->httpClient;
    }

    public function getConfig(): ConfigInterface
    {
        return $this->config;
    }

    // ─── Ресурсы (lazy) ──────────────────────────────────────────

    public function bot(): Bot
    {
        return $this->getResource(Bot::class);
    }

    public function messages(): Messages
    {
        return $this->getResource(Messages::class);
    }

    public function chats(): Chats
    {
        return $this->getResource(Chats::class);
    }

    public function members(): Members
    {
        return $this->getResource(Members::class);
    }

    public function subscriptions(): Subscriptions
    {
        return $this->getResource(Subscriptions::class);
    }

    public function uploads(): Uploads
    {
        return $this->getResource(Uploads::class);
    }

    public function callbacks(): Callbacks
    {
        return $this->getResource(Callbacks::class);
    }

    // ─── Приватные методы ────────────────────────────────────────

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function request(string $method, string $endpoint, array $options = []): array
    {
        $this->rateLimiter?->throttle();

        $this->log(LogLevel::Debug, 'Запрос к API', [
            'method'   => $method,
            'endpoint' => $endpoint,
        ]);

        /** @var array<string, mixed> $result */
        $result = $this->retryHandler->execute(function () use ($method, $endpoint, $options): array {
            $response = $this->httpClient->request($method, $endpoint, $options);
            return $this->responseDecoder->decode(
                $response['status_code'],
                $response['body'],
                $method,
                $endpoint,
            );
        });

        return $result;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function log(LogLevel $level, string $message, array $context = []): void
    {
        if ($this->logger === null) {
            return;
        }

        $maskedToken = InputValidator::maskToken($this->config->getToken());
        $context['token'] = $maskedToken;
        $fullMessage = $this->config->getAppName() . ': ' . $message;

        match ($level) {
            LogLevel::Debug   => $this->logger->debug($fullMessage, $context),
            LogLevel::Info    => $this->logger->info($fullMessage, $context),
            LogLevel::Warning => $this->logger->warning($fullMessage, $context),
            LogLevel::Error   => $this->logger->error($fullMessage, $context),
        };
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    private function getResource(string $class): object
    {
        if (!isset($this->resourceInstances[$class])) {
            $this->resourceInstances[$class] = new $class($this);
        }

        /** @var T */
        return $this->resourceInstances[$class];
    }
}
