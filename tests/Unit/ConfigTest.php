<?php

declare(strict_types=1);

namespace MaxBotSdk\Tests\Unit;

use MaxBotSdk\Config;
use MaxBotSdk\Contracts\ConfigInterface;
use MaxBotSdk\Contracts\LoggerInterface;
use MaxBotSdk\Exception\MaxConfigException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    #[Test]
    public function constructWithValidToken(): void
    {
        $config = new Config('valid_token');
        self::assertSame('valid_token', $config->getToken());
    }

    #[Test]
    public function constructWithEmptyTokenThrows(): void
    {
        $this->expectException(MaxConfigException::class);
        new Config('');
    }

    #[Test]
    public function constructWithWhitespaceTokenThrows(): void
    {
        $this->expectException(MaxConfigException::class);
        new Config('   ');
    }

    #[Test]
    public function defaultValues(): void
    {
        $config = new Config('token');
        self::assertSame(30, $config->getTimeout());
        self::assertSame(3, $config->getRetries());
        self::assertSame(30, $config->getRateLimit());
        self::assertTrue($config->getVerifySsl());
        self::assertTrue($config->getLogRequests());
        self::assertSame('MaxBot', $config->getAppName());
        self::assertNull($config->getLogger());
    }

    #[Test]
    public function implementsConfigInterface(): void
    {
        $config = new Config('token');
        self::assertInstanceOf(ConfigInterface::class, $config);
    }

    #[Test]
    public function constructWithCustomTimeout(): void
    {
        $config = new Config('token', 60);
        self::assertSame(60, $config->getTimeout());
    }

    #[Test]
    public function constructWithTimeoutTooLow(): void
    {
        $this->expectException(MaxConfigException::class);
        new Config('token', 1);
    }

    #[Test]
    public function constructWithTimeoutTooHigh(): void
    {
        $this->expectException(MaxConfigException::class);
        new Config('token', 999);
    }

    #[Test]
    public function constructWithCustomRetries(): void
    {
        $config = new Config('token', 30, 5);
        self::assertSame(5, $config->getRetries());
    }

    #[Test]
    public function constructWithRetriesTooHigh(): void
    {
        $this->expectException(MaxConfigException::class);
        new Config('token', 30, 20);
    }

    #[Test]
    public function constructWithCustomRateLimit(): void
    {
        $config = new Config('token', 30, 3, 50);
        self::assertSame(50, $config->getRateLimit());
    }

    #[Test]
    public function constructWithRateLimitTooHigh(): void
    {
        $this->expectException(MaxConfigException::class);
        new Config('token', 30, 3, 200);
    }

    #[Test]
    public function constructWithVerifySslFalse(): void
    {
        $config = new Config('token', 30, 3, 30, false);
        self::assertFalse($config->getVerifySsl());
    }

    #[Test]
    public function constructWithLogRequestsFalse(): void
    {
        $config = new Config('token', 30, 3, 30, true, false);
        self::assertFalse($config->getLogRequests());
    }

    #[Test]
    public function constructWithCustomAppName(): void
    {
        $config = new Config('token', 30, 3, 30, true, true, 'MyApp');
        self::assertSame('MyApp', $config->getAppName());
    }

    #[Test]
    public function constructWithLogger(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $config = new Config('token', 30, 3, 30, true, true, 'MaxBot', $logger);
        self::assertSame($logger, $config->getLogger());
    }

    #[Test]
    public function fromEnvironmentValid(): void
    {
        \putenv('MAX_BOT_TOKEN=env_test_token');
        \putenv('MAX_BOT_TIMEOUT=60');
        \putenv('MAX_BOT_RETRIES=5');
        \putenv('MAX_BOT_RATE_LIMIT=50');
        \putenv('MAX_BOT_VERIFY_SSL=false');
        \putenv('MAX_BOT_LOG_REQUESTS=0');
        \putenv('MAX_BOT_APP_NAME=EnvApp');

        try {
            $config = Config::fromEnvironment();
            self::assertSame('env_test_token', $config->getToken());
            self::assertSame(60, $config->getTimeout());
            self::assertSame(5, $config->getRetries());
            self::assertSame(50, $config->getRateLimit());
            self::assertFalse($config->getVerifySsl());
            self::assertFalse($config->getLogRequests());
            self::assertSame('EnvApp', $config->getAppName());
        } finally {
            \putenv('MAX_BOT_TOKEN');
            \putenv('MAX_BOT_TIMEOUT');
            \putenv('MAX_BOT_RETRIES');
            \putenv('MAX_BOT_RATE_LIMIT');
            \putenv('MAX_BOT_VERIFY_SSL');
            \putenv('MAX_BOT_LOG_REQUESTS');
            \putenv('MAX_BOT_APP_NAME');
        }
    }

    #[Test]
    public function fromEnvironmentTokenOnly(): void
    {
        \putenv('MAX_BOT_TOKEN=minimal_token');
        try {
            $config = Config::fromEnvironment();
            self::assertSame('minimal_token', $config->getToken());
            self::assertSame(30, $config->getTimeout());
            self::assertSame(3, $config->getRetries());
        } finally {
            \putenv('MAX_BOT_TOKEN');
        }
    }

    #[Test]
    public function fromEnvironmentMissingTokenThrows(): void
    {
        \putenv('MAX_BOT_TOKEN');
        $this->expectException(MaxConfigException::class);
        Config::fromEnvironment();
    }

    #[Test]
    public function fromEnvironmentBoolConversions(): void
    {
        \putenv('MAX_BOT_TOKEN=bool_test_token');
        \putenv('MAX_BOT_VERIFY_SSL=true');
        \putenv('MAX_BOT_LOG_REQUESTS=yes');

        try {
            $config = Config::fromEnvironment();
            self::assertTrue($config->getVerifySsl());
            self::assertTrue($config->getLogRequests());
        } finally {
            \putenv('MAX_BOT_TOKEN');
            \putenv('MAX_BOT_VERIFY_SSL');
            \putenv('MAX_BOT_LOG_REQUESTS');
        }
    }

    #[Test]
    public function fromIniFileValid(): void
    {
        $iniContent = "[max]\ntoken = ini_test_token\ntimeout = 45\nretries = 2\nrate_limit = 20\nverify_ssl = false\nlog_requests = true\napp_name = IniApp\n";
        $tmpFile = \tempnam(\sys_get_temp_dir(), 'max_cfg_');
        \file_put_contents($tmpFile, $iniContent);

        try {
            $config = Config::fromIniFile($tmpFile);
            self::assertSame('ini_test_token', $config->getToken());
            self::assertSame(45, $config->getTimeout());
            self::assertSame(2, $config->getRetries());
            self::assertSame(20, $config->getRateLimit());
            self::assertFalse($config->getVerifySsl());
            self::assertTrue($config->getLogRequests());
            self::assertSame('IniApp', $config->getAppName());
        } finally {
            \unlink($tmpFile);
        }
    }

    #[Test]
    public function fromIniFileTokenOnly(): void
    {
        $iniContent = "[max]\ntoken = simple_token\n";
        $tmpFile = \tempnam(\sys_get_temp_dir(), 'max_cfg_');
        \file_put_contents($tmpFile, $iniContent);

        try {
            $config = Config::fromIniFile($tmpFile);
            self::assertSame('simple_token', $config->getToken());
            self::assertSame(30, $config->getTimeout());
        } finally {
            \unlink($tmpFile);
        }
    }

    #[Test]
    public function fromIniFileNotFoundThrows(): void
    {
        $this->expectException(MaxConfigException::class);
        Config::fromIniFile('/nonexistent/path/config.ini');
    }

    #[Test]
    public function fromIniFileMissingTokenThrows(): void
    {
        $iniContent = "[max]\ntimeout = 30\n";
        $tmpFile = \tempnam(\sys_get_temp_dir(), 'max_cfg_');
        \file_put_contents($tmpFile, $iniContent);

        try {
            $this->expectException(MaxConfigException::class);
            Config::fromIniFile($tmpFile);
        } finally {
            \unlink($tmpFile);
        }
    }

    #[Test]
    public function fromIniFileMissingSectionThrowsForToken(): void
    {
        $iniContent = "[other]\nfoo = bar\n";
        $tmpFile = \tempnam(\sys_get_temp_dir(), 'max_cfg_');
        \file_put_contents($tmpFile, $iniContent);

        try {
            $this->expectException(MaxConfigException::class);
            Config::fromIniFile($tmpFile);
        } finally {
            \unlink($tmpFile);
        }
    }
}
