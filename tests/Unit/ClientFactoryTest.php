<?php

declare(strict_types=1);

namespace MaxBotSdk\Tests\Unit;

use MaxBotSdk\Client;
use MaxBotSdk\ClientFactory;
use MaxBotSdk\Config;
use MaxBotSdk\ConfigBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ClientFactoryTest extends TestCase
{
    #[Test]
    public function createReturnsClient(): void
    {
        $client = ClientFactory::create('test_token');
        self::assertInstanceOf(Client::class, $client);
    }

    #[Test]
    public function createSetsToken(): void
    {
        $client = ClientFactory::create('my_token');
        self::assertSame('my_token', $client->getConfig()->getToken());
    }

    #[Test]
    public function fromConfigReturnsClient(): void
    {
        $config = new Config('token_123', 60);
        $client = ClientFactory::fromConfig($config);
        self::assertInstanceOf(Client::class, $client);
        self::assertSame(60, $client->getConfig()->getTimeout());
    }

    #[Test]
    public function fromBuilderReturnsClient(): void
    {
        $builder = ConfigBuilder::create('builder_token')
            ->withTimeout(90)
            ->withRetries(5);
        $client = ClientFactory::fromBuilder($builder);
        self::assertInstanceOf(Client::class, $client);
        self::assertSame('builder_token', $client->getConfig()->getToken());
        self::assertSame(90, $client->getConfig()->getTimeout());
        self::assertSame(5, $client->getConfig()->getRetries());
    }

    #[Test]
    public function fromEnvironment(): void
    {
        putenv('MAX_BOT_TOKEN=env_factory_token');
        try {
            $client = ClientFactory::fromEnvironment();
            self::assertInstanceOf(Client::class, $client);
            self::assertSame('env_factory_token', $client->getConfig()->getToken());
        } finally {
            putenv('MAX_BOT_TOKEN');
        }
    }

    #[Test]
    public function fromIni(): void
    {
        $iniContent = "[max]\ntoken = ini_factory_token\ntimeout = 45\n";
        $tmpFile = tempnam(sys_get_temp_dir(), 'max_cfg_');
        file_put_contents($tmpFile, $iniContent);

        try {
            $client = ClientFactory::fromIni($tmpFile);
            self::assertInstanceOf(Client::class, $client);
            self::assertSame('ini_factory_token', $client->getConfig()->getToken());
            self::assertSame(45, $client->getConfig()->getTimeout());
        } finally {
            unlink($tmpFile);
        }
    }
}
