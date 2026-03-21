<?php

namespace App\Component\Max\Tests\Unit;

use App\Component\Max\Config;
use App\Component\Max\ConfigBuilder;
use App\Component\Max\Exception\MaxConfigException;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для класса ConfigBuilder.
 */
class ConfigBuilderTest extends TestCase
{
    public function testCreateReturnsBuilder()
    {
        $builder = ConfigBuilder::create('token');
        $this->assertInstanceOf(ConfigBuilder::class, $builder);
    }

    public function testBuildReturnsConfig()
    {
        $config = ConfigBuilder::create('my_token')->build();
        $this->assertInstanceOf(Config::class, $config);
        $this->assertEquals('my_token', $config->getToken());
    }

    public function testWithTimeout()
    {
        $config = ConfigBuilder::create('token')
            ->withTimeout(60)
            ->build();
        $this->assertEquals(60, $config->getTimeout());
    }

    public function testWithRetries()
    {
        $config = ConfigBuilder::create('token')
            ->withRetries(7)
            ->build();
        $this->assertEquals(7, $config->getRetries());
    }

    public function testWithRateLimit()
    {
        $config = ConfigBuilder::create('token')
            ->withRateLimit(50)
            ->build();
        $this->assertEquals(50, $config->getRateLimit());
    }

    public function testWithVerifySsl()
    {
        $config = ConfigBuilder::create('token')
            ->withVerifySsl(false)
            ->build();
        $this->assertFalse($config->getVerifySsl());
    }

    public function testWithLogRequests()
    {
        $config = ConfigBuilder::create('token')
            ->withLogRequests(false)
            ->build();
        $this->assertFalse($config->getLogRequests());
    }

    public function testWithAppName()
    {
        $config = ConfigBuilder::create('token')
            ->withAppName('TestApp')
            ->build();
        $this->assertEquals('TestApp', $config->getAppName());
    }

    public function testFullFluentChain()
    {
        $config = ConfigBuilder::create('test_token')
            ->withTimeout(120)
            ->withRetries(5)
            ->withRateLimit(20)
            ->withVerifySsl(false)
            ->withLogRequests(true)
            ->withAppName('SuperBot')
            ->build();

        $this->assertEquals('test_token', $config->getToken());
        $this->assertEquals(120, $config->getTimeout());
        $this->assertEquals(5, $config->getRetries());
        $this->assertEquals(20, $config->getRateLimit());
        $this->assertFalse($config->getVerifySsl());
        $this->assertTrue($config->getLogRequests());
        $this->assertEquals('SuperBot', $config->getAppName());
    }

    public function testBuildWithEmptyTokenThrows()
    {
        $this->expectException(MaxConfigException::class);
        ConfigBuilder::create('')->build();
    }

    public function testWithLoggerPassesLoggerToConfig()
    {
        $logger = $this->createMock(\App\Component\Max\Contracts\LoggerInterface::class);
        $config = ConfigBuilder::create('token')
            ->withLogger($logger)
            ->build();
        $this->assertSame($logger, $config->getLogger());
    }

    public function testWithLoggerNullIsDefault()
    {
        $config = ConfigBuilder::create('token')->build();
        $this->assertNull($config->getLogger());
    }

    public function testDefaultValuesAreApplied()
    {
        $config = ConfigBuilder::create('token')->build();
        $this->assertEquals(Config::DEFAULT_TIMEOUT, $config->getTimeout());
        $this->assertEquals(Config::DEFAULT_RETRIES, $config->getRetries());
        $this->assertEquals(Config::DEFAULT_RATE_LIMIT, $config->getRateLimit());
        $this->assertTrue($config->getVerifySsl());
        $this->assertTrue($config->getLogRequests());
        $this->assertEquals('MaxBot', $config->getAppName());
    }
}
