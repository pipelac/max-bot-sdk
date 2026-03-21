<?php

namespace MaxBotSdk;

use MaxBotSdk\Contracts\LoggerInterface;
use MaxBotSdk\Exception\MaxConfigException;

/**
 * Fluent builder для создания иммутабельной конфигурации MAX Bot API SDK.
 *
 * Единственный способ создать Config с нестандартными параметрами.
 *
 * Пример:
 * <code>
 * $config = ConfigBuilder::create('TOKEN')
 *     ->withTimeout(60)
 *     ->withRetries(5)
 *     ->withLogger($logger)
 *     ->build();
 * </code>
 *
 * @since 1.0.0
 */
final class ConfigBuilder
{
    /** @var string Токен бота. */
    private $token;

    /** @var int */
    private $timeout = Config::DEFAULT_TIMEOUT;

    /** @var int */
    private $retries = Config::DEFAULT_RETRIES;

    /** @var int */
    private $rateLimit = Config::DEFAULT_RATE_LIMIT;

    /** @var bool */
    private $verifySsl = true;

    /** @var bool */
    private $logRequests = true;

    /** @var string */
    private $appName = 'MaxBot';

    /** @var LoggerInterface|null */
    private $logger;

    /**
     * @param string $token Токен бота.
     */
    private function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Создать builder с указанным токеном.
     *
     * @param string $token Токен бота MAX.
     * @return self
     */
    public static function create($token)
    {
        return new self($token);
    }

    /**
     * @param int $timeout Секунды.
     * @return self
     */
    public function withTimeout($timeout)
    {
        $this->timeout = (int) $timeout;
        return $this;
    }

    /**
     * @param int $retries
     * @return self
     */
    public function withRetries($retries)
    {
        $this->retries = (int) $retries;
        return $this;
    }

    /**
     * @param int $rateLimit
     * @return self
     */
    public function withRateLimit($rateLimit)
    {
        $this->rateLimit = (int) $rateLimit;
        return $this;
    }

    /**
     * @param bool $verify
     * @return self
     */
    public function withVerifySsl($verify)
    {
        $this->verifySsl = (bool) $verify;
        return $this;
    }

    /**
     * @param bool $log
     * @return self
     */
    public function withLogRequests($log)
    {
        $this->logRequests = (bool) $log;
        return $this;
    }

    /**
     * @param string $appName
     * @return self
     */
    public function withAppName($appName)
    {
        $this->appName = $appName;
        return $this;
    }

    /**
     * @param LoggerInterface|null $logger
     * @return self
     */
    public function withLogger($logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Собирает и возвращает иммутабельный объект Config.
     *
     * @return Config
     * @throws MaxConfigException
     */
    public function build()
    {
        return new Config(
            $this->token,
            $this->timeout,
            $this->retries,
            $this->rateLimit,
            $this->verifySsl,
            $this->logRequests,
            $this->appName,
            $this->logger
        );
    }
}
