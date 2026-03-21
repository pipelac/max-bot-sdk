<?php

namespace MaxBotSdk\Tests\Unit;

use MaxBotSdk\Client;
use MaxBotSdk\ClientFactory;
use MaxBotSdk\Config;
use MaxBotSdk\ConfigBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для ClientFactory.
 */
class ClientFactoryTest extends TestCase
{
    public function testCreateReturnsClient()
    {
        $client = ClientFactory::create('test_token');
        $this->assertInstanceOf(Client::class, $client);
    }

    public function testCreateSetsToken()
    {
        $client = ClientFactory::create('my_token');
        $this->assertEquals('my_token', $client->getConfig()->getToken());
    }

    public function testCreateFromConfigReturnsClient()
    {
        $config = new Config('token_123', 60);
        $client = ClientFactory::createFromConfig($config);
        $this->assertInstanceOf(Client::class, $client);
        $this->assertEquals(60, $client->getConfig()->getTimeout());
    }

    public function testCreateFromBuilderReturnsClient()
    {
        $builder = ConfigBuilder::create('builder_token')
            ->withTimeout(90)
            ->withRetries(5);
        $client = ClientFactory::createFromBuilder($builder);
        $this->assertInstanceOf(Client::class, $client);
        $this->assertEquals('builder_token', $client->getConfig()->getToken());
        $this->assertEquals(90, $client->getConfig()->getTimeout());
        $this->assertEquals(5, $client->getConfig()->getRetries());
    }

    public function testCreateFromEnvironment()
    {
        putenv('MAX_BOT_TOKEN=env_factory_token');
        try {
            $client = ClientFactory::createFromEnvironment();
            $this->assertInstanceOf(Client::class, $client);
            $this->assertEquals('env_factory_token', $client->getConfig()->getToken());
        } finally {
            putenv('MAX_BOT_TOKEN');
        }
    }

    public function testCreateFromIni()
    {
        $iniContent = "[max]\ntoken = ini_factory_token\ntimeout = 45\n";
        $tmpFile = tempnam(sys_get_temp_dir(), 'max_cfg_');
        file_put_contents($tmpFile, $iniContent);

        try {
            $client = ClientFactory::createFromIni($tmpFile);
            $this->assertInstanceOf(Client::class, $client);
            $this->assertEquals('ini_factory_token', $client->getConfig()->getToken());
            $this->assertEquals(45, $client->getConfig()->getTimeout());
        } finally {
            unlink($tmpFile);
        }
    }
}
