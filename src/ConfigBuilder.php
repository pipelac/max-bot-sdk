<?php

declare(strict_types=1);

namespace MaxBotSdk;

use MaxBotSdk\Contracts\LoggerInterface;

/**
 * Fluent builder для создания иммутабельной конфигурации MAX Bot API SDK.
 *
 * @since 1.0.0
 */
final class ConfigBuilder
{
    private int $timeout = Config::DEFAULT_TIMEOUT;
    private int $retries = Config::DEFAULT_RETRIES;
    private int $rateLimit = Config::DEFAULT_RATE_LIMIT;
    private bool $verifySsl = true;
    private bool $logRequests = true;
    private string $appName = 'MaxBot';
    private ?LoggerInterface $logger = null;

    private function __construct(
        private readonly string $token,
    ) {
    }

    public static function create(string $token): self
    {
        return new self($token);
    }

    public function withTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    public function withRetries(int $retries): self
    {
        $this->retries = $retries;
        return $this;
    }

    public function withRateLimit(int $rateLimit): self
    {
        $this->rateLimit = $rateLimit;
        return $this;
    }

    public function withVerifySsl(bool $verifySsl): self
    {
        $this->verifySsl = $verifySsl;
        return $this;
    }

    public function withLogRequests(bool $logRequests): self
    {
        $this->logRequests = $logRequests;
        return $this;
    }

    public function withAppName(string $appName): self
    {
        $this->appName = $appName;
        return $this;
    }

    public function withLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    public function build(): Config
    {
        return new Config(
            $this->token,
            $this->timeout,
            $this->retries,
            $this->rateLimit,
            $this->verifySsl,
            $this->logRequests,
            $this->appName,
            $this->logger,
        );
    }
}
