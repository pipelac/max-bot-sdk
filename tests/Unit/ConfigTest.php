<?php

namespace App\Component\Max\Tests\Unit;

use App\Component\Max\Config;
use App\Component\Max\Contracts\ConfigInterface;
use App\Component\Max\Contracts\LoggerInterface;
use App\Component\Max\Exception\MaxConfigException;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для Config (иммутабельная конфигурация).
 */
class ConfigTest extends TestCase
{
    // --- Конструктор ---

    public function testConstructWithValidToken()
    {
        $config = new Config('valid_token');
        $this->assertEquals('valid_token', $config->getToken());
    }

    public function testConstructWithEmptyTokenThrows()
    {
        $this->expectException(MaxConfigException::class);
        new Config('');
    }

    public function testConstructWithWhitespaceTokenThrows()
    {
        $this->expectException(MaxConfigException::class);
        new Config('   ');
    }

    // --- Defaults ---

    public function testDefaultValues()
    {
        $config = new Config('token');
        $this->assertEquals(30, $config->getTimeout());
        $this->assertEquals(3, $config->getRetries());
        $this->assertEquals(30, $config->getRateLimit());
        $this->assertTrue($config->getVerifySsl());
        $this->assertTrue($config->getLogRequests());
        $this->assertEquals('MaxBot', $config->getAppName());
        $this->assertNull($config->getLogger());
    }

    // --- Implements ConfigInterface ---

    public function testImplementsConfigInterface()
    {
        $config = new Config('token');
        $this->assertInstanceOf(ConfigInterface::class, $config);
    }

    // --- Constructor validation (immutable — all set via constructor) ---

    public function testConstructWithCustomTimeout()
    {
        $config = new Config('token', 60);
        $this->assertEquals(60, $config->getTimeout());
    }

    public function testConstructWithTimeoutTooLow()
    {
        $this->expectException(MaxConfigException::class);
        new Config('token', 1);
    }

    public function testConstructWithTimeoutTooHigh()
    {
        $this->expectException(MaxConfigException::class);
        new Config('token', 999);
    }

    public function testConstructWithCustomRetries()
    {
        $config = new Config('token', 30, 5);
        $this->assertEquals(5, $config->getRetries());
    }

    public function testConstructWithRetriesTooHigh()
    {
        $this->expectException(MaxConfigException::class);
        new Config('token', 30, 20);
    }

    public function testConstructWithCustomRateLimit()
    {
        $config = new Config('token', 30, 3, 50);
        $this->assertEquals(50, $config->getRateLimit());
    }

    public function testConstructWithRateLimitTooHigh()
    {
        $this->expectException(MaxConfigException::class);
        new Config('token', 30, 3, 200);
    }

    public function testConstructWithVerifySslFalse()
    {
        $config = new Config('token', 30, 3, 30, false);
        $this->assertFalse($config->getVerifySsl());
    }

    public function testConstructWithLogRequestsFalse()
    {
        $config = new Config('token', 30, 3, 30, true, false);
        $this->assertFalse($config->getLogRequests());
    }

    public function testConstructWithCustomAppName()
    {
        $config = new Config('token', 30, 3, 30, true, true, 'MyApp');
        $this->assertEquals('MyApp', $config->getAppName());
    }

    public function testConstructWithLogger()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $config = new Config('token', 30, 3, 30, true, true, 'MaxBot', $logger);
        $this->assertSame($logger, $config->getLogger());
    }

    // --- fromEnvironment ---

    public function testFromEnvironmentValid()
    {
        putenv('MAX_BOT_TOKEN=env_test_token');
        putenv('MAX_BOT_TIMEOUT=60');
        putenv('MAX_BOT_RETRIES=5');
        putenv('MAX_BOT_RATE_LIMIT=50');
        putenv('MAX_BOT_VERIFY_SSL=false');
        putenv('MAX_BOT_LOG_REQUESTS=0');
        putenv('MAX_BOT_APP_NAME=EnvApp');

        try {
            $config = Config::fromEnvironment();

            $this->assertEquals('env_test_token', $config->getToken());
            $this->assertEquals(60, $config->getTimeout());
            $this->assertEquals(5, $config->getRetries());
            $this->assertEquals(50, $config->getRateLimit());
            $this->assertFalse($config->getVerifySsl());
            $this->assertFalse($config->getLogRequests());
            $this->assertEquals('EnvApp', $config->getAppName());
        } finally {
            putenv('MAX_BOT_TOKEN');
            putenv('MAX_BOT_TIMEOUT');
            putenv('MAX_BOT_RETRIES');
            putenv('MAX_BOT_RATE_LIMIT');
            putenv('MAX_BOT_VERIFY_SSL');
            putenv('MAX_BOT_LOG_REQUESTS');
            putenv('MAX_BOT_APP_NAME');
        }
    }

    public function testFromEnvironmentTokenOnly()
    {
        putenv('MAX_BOT_TOKEN=minimal_token');

        try {
            $config = Config::fromEnvironment();

            $this->assertEquals('minimal_token', $config->getToken());
            $this->assertEquals(30, $config->getTimeout());
            $this->assertEquals(3, $config->getRetries());
        } finally {
            putenv('MAX_BOT_TOKEN');
        }
    }

    public function testFromEnvironmentMissingTokenThrows()
    {
        putenv('MAX_BOT_TOKEN'); // unset
        $this->expectException(MaxConfigException::class);
        Config::fromEnvironment();
    }

    public function testFromEnvironmentBoolConversions()
    {
        putenv('MAX_BOT_TOKEN=bool_test_token');
        putenv('MAX_BOT_VERIFY_SSL=true');
        putenv('MAX_BOT_LOG_REQUESTS=yes');

        try {
            $config = Config::fromEnvironment();

            $this->assertTrue($config->getVerifySsl());
            $this->assertTrue($config->getLogRequests());
        } finally {
            putenv('MAX_BOT_TOKEN');
            putenv('MAX_BOT_VERIFY_SSL');
            putenv('MAX_BOT_LOG_REQUESTS');
        }
    }

    // --- fromIniFile ---

    public function testFromIniFileValid()
    {
        $iniContent = "[max]\ntoken = ini_test_token\ntimeout = 45\nretries = 2\nrate_limit = 20\nverify_ssl = false\nlog_requests = true\napp_name = IniApp\n";
        $tmpFile = tempnam(sys_get_temp_dir(), 'max_cfg_');
        file_put_contents($tmpFile, $iniContent);

        try {
            $config = Config::fromIniFile($tmpFile);

            $this->assertEquals('ini_test_token', $config->getToken());
            $this->assertEquals(45, $config->getTimeout());
            $this->assertEquals(2, $config->getRetries());
            $this->assertEquals(20, $config->getRateLimit());
            $this->assertFalse($config->getVerifySsl());
            $this->assertTrue($config->getLogRequests());
            $this->assertEquals('IniApp', $config->getAppName());
        } finally {
            unlink($tmpFile);
        }
    }

    public function testFromIniFileTokenOnly()
    {
        $iniContent = "[max]\ntoken = simple_token\n";
        $tmpFile = tempnam(sys_get_temp_dir(), 'max_cfg_');
        file_put_contents($tmpFile, $iniContent);

        try {
            $config = Config::fromIniFile($tmpFile);

            $this->assertEquals('simple_token', $config->getToken());
            $this->assertEquals(30, $config->getTimeout()); // default
        } finally {
            unlink($tmpFile);
        }
    }

    public function testFromIniFileNotFoundThrows()
    {
        $this->expectException(MaxConfigException::class);
        Config::fromIniFile('/nonexistent/path/config.ini');
    }

    public function testFromIniFileMissingTokenThrows()
    {
        $iniContent = "[max]\ntimeout = 30\n";
        $tmpFile = tempnam(sys_get_temp_dir(), 'max_cfg_');
        file_put_contents($tmpFile, $iniContent);

        try {
            $this->expectException(MaxConfigException::class);
            Config::fromIniFile($tmpFile);
        } finally {
            unlink($tmpFile);
        }
    }

    public function testFromIniFileMissingSectionThrowsForToken()
    {
        $iniContent = "[other]\nfoo = bar\n";
        $tmpFile = tempnam(sys_get_temp_dir(), 'max_cfg_');
        file_put_contents($tmpFile, $iniContent);

        try {
            $this->expectException(MaxConfigException::class);
            Config::fromIniFile($tmpFile);
        } finally {
            unlink($tmpFile);
        }
    }
}
