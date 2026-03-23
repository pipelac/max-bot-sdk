<?php

declare(strict_types=1);

namespace MaxBotSdk\Tests\Unit;

use MaxBotSdk\Config;
use MaxBotSdk\ConfigBuilder;
use MaxBotSdk\Contracts\LoggerInterface;
use MaxBotSdk\Exception\MaxConfigException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConfigBuilderTest extends TestCase
{
    #[Test]
    public function createReturnsBuilder(): void
    {
        $builder = ConfigBuilder::create('token');
        self::assertInstanceOf(ConfigBuilder::class, $builder);
    }

    #[Test]
    public function buildReturnsConfig(): void
    {
        $config = ConfigBuilder::create('my_token')->build();
        self::assertInstanceOf(Config::class, $config);
        self::assertSame('my_token', $config->getToken());
    }

    #[Test]
    public function withTimeout(): void
    {
        $config = ConfigBuilder::create('token')
            ->withTimeout(60)
            ->build();
        self::assertSame(60, $config->getTimeout());
    }

    #[Test]
    public function withRetries(): void
    {
        $config = ConfigBuilder::create('token')
            ->withRetries(7)
            ->build();
        self::assertSame(7, $config->getRetries());
    }

    #[Test]
    public function withRateLimit(): void
    {
        $config = ConfigBuilder::create('token')
            ->withRateLimit(50)
            ->build();
        self::assertSame(50, $config->getRateLimit());
    }

    #[Test]
    public function withVerifySsl(): void
    {
        $config = ConfigBuilder::create('token')
            ->withVerifySsl(false)
            ->build();
        self::assertFalse($config->getVerifySsl());
    }

    #[Test]
    public function withLogRequests(): void
    {
        $config = ConfigBuilder::create('token')
            ->withLogRequests(false)
            ->build();
        self::assertFalse($config->getLogRequests());
    }

    #[Test]
    public function withAppName(): void
    {
        $config = ConfigBuilder::create('token')
            ->withAppName('TestApp')
            ->build();
        self::assertSame('TestApp', $config->getAppName());
    }

    #[Test]
    public function fullFluentChain(): void
    {
        $config = ConfigBuilder::create('test_token')
            ->withTimeout(120)
            ->withRetries(5)
            ->withRateLimit(20)
            ->withVerifySsl(false)
            ->withLogRequests(true)
            ->withAppName('SuperBot')
            ->build();

        self::assertSame('test_token', $config->getToken());
        self::assertSame(120, $config->getTimeout());
        self::assertSame(5, $config->getRetries());
        self::assertSame(20, $config->getRateLimit());
        self::assertFalse($config->getVerifySsl());
        self::assertTrue($config->getLogRequests());
        self::assertSame('SuperBot', $config->getAppName());
    }

    #[Test]
    public function buildWithEmptyTokenThrows(): void
    {
        $this->expectException(MaxConfigException::class);
        ConfigBuilder::create('')->build();
    }

    #[Test]
    public function withLoggerPassesLoggerToConfig(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $config = ConfigBuilder::create('token')
            ->withLogger($logger)
            ->build();
        self::assertSame($logger, $config->getLogger());
    }

    #[Test]
    public function withLoggerNullIsDefault(): void
    {
        $config = ConfigBuilder::create('token')->build();
        self::assertNull($config->getLogger());
    }

    #[Test]
    public function defaultValuesAreApplied(): void
    {
        $config = ConfigBuilder::create('token')->build();
        self::assertSame(Config::DEFAULT_TIMEOUT, $config->getTimeout());
        self::assertSame(Config::DEFAULT_RETRIES, $config->getRetries());
        self::assertSame(Config::DEFAULT_RATE_LIMIT, $config->getRateLimit());
        self::assertTrue($config->getVerifySsl());
        self::assertTrue($config->getLogRequests());
        self::assertSame('MaxBot', $config->getAppName());
    }
}
