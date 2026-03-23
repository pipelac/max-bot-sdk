<?php

declare(strict_types=1);

namespace MaxBotSdk;

use MaxBotSdk\Contracts\ConfigInterface;
use MaxBotSdk\Contracts\LoggerInterface;
use MaxBotSdk\Exception\MaxConfigException;

/**
 * Иммутабельная конфигурация MAX Bot API SDK.
 *
 * Все параметры задаются при создании через конструктор, фабричные методы
 * или ConfigBuilder. После создания изменение невозможно.
 *
 * @since 1.0.0
 */
final class Config implements ConfigInterface
{
    public const DEFAULT_TIMEOUT = 30;
    public const MIN_TIMEOUT = 5;
    public const MAX_TIMEOUT = 300;
    public const DEFAULT_RETRIES = 3;
    public const MAX_RETRIES = 10;
    public const DEFAULT_RATE_LIMIT = 30;
    public const MAX_RATE_LIMIT = 100;

    private readonly string $token;
    private readonly int $timeout;
    private readonly int $retries;
    private readonly int $rateLimit;
    private readonly bool $verifySsl;
    private readonly bool $logRequests;
    private readonly string $appName;
    private readonly ?LoggerInterface $logger;

    /**
     * @throws MaxConfigException Если параметры невалидны.
     */
    public function __construct(
        string $token,
        int $timeout = self::DEFAULT_TIMEOUT,
        int $retries = self::DEFAULT_RETRIES,
        int $rateLimit = self::DEFAULT_RATE_LIMIT,
        bool $verifySsl = true,
        bool $logRequests = true,
        string $appName = 'MaxBot',
        ?LoggerInterface $logger = null,
    ) {
        $token = trim($token);
        if ($token === '') {
            throw new MaxConfigException('Токен MAX бота не указан.');
        }
        $this->token = $token;

        if ($timeout < self::MIN_TIMEOUT || $timeout > self::MAX_TIMEOUT) {
            throw new MaxConfigException(\sprintf(
                'Таймаут должен быть от %d до %d секунд.',
                self::MIN_TIMEOUT,
                self::MAX_TIMEOUT,
            ));
        }
        $this->timeout = $timeout;

        if ($retries < 0 || $retries > self::MAX_RETRIES) {
            throw new MaxConfigException(\sprintf(
                'Количество повторов должно быть от 0 до %d.',
                self::MAX_RETRIES,
            ));
        }
        $this->retries = $retries;

        if ($rateLimit < 1 || $rateLimit > self::MAX_RATE_LIMIT) {
            throw new MaxConfigException(\sprintf(
                'Лимит запросов должен быть от 1 до %d.',
                self::MAX_RATE_LIMIT,
            ));
        }
        $this->rateLimit = $rateLimit;

        $this->verifySsl = $verifySsl;
        $this->logRequests = $logRequests;
        $this->appName = trim($appName) !== '' ? trim($appName) : 'MaxBot';
        $this->logger = $logger;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getRetries(): int
    {
        return $this->retries;
    }

    public function getRateLimit(): int
    {
        return $this->rateLimit;
    }

    public function getVerifySsl(): bool
    {
        return $this->verifySsl;
    }

    public function getLogRequests(): bool
    {
        return $this->logRequests;
    }

    public function getAppName(): string
    {
        return $this->appName;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Создаёт конфигурацию из переменных окружения (12-Factor App).
     *
     * @throws MaxConfigException Если MAX_BOT_TOKEN не задан.
     */
    public static function fromEnvironment(): self
    {
        $token = getenv('MAX_BOT_TOKEN');
        if ($token === false || trim($token) === '') {
            throw new MaxConfigException('Переменная окружения MAX_BOT_TOKEN не задана.');
        }

        $envTimeout = getenv('MAX_BOT_TIMEOUT');
        $timeout = ($envTimeout !== false && $envTimeout !== '') ? (int) $envTimeout : self::DEFAULT_TIMEOUT;

        $envRetries = getenv('MAX_BOT_RETRIES');
        $retries = ($envRetries !== false && $envRetries !== '') ? (int) $envRetries : self::DEFAULT_RETRIES;

        $envRateLimit = getenv('MAX_BOT_RATE_LIMIT');
        $rateLimit = ($envRateLimit !== false && $envRateLimit !== '') ? (int) $envRateLimit : self::DEFAULT_RATE_LIMIT;

        $envVerifySsl = getenv('MAX_BOT_VERIFY_SSL');
        $verifySsl = ($envVerifySsl !== false && $envVerifySsl !== '') ? self::toBool($envVerifySsl) : true;

        $envLogRequests = getenv('MAX_BOT_LOG_REQUESTS');
        $logRequests = ($envLogRequests !== false && $envLogRequests !== '') ? self::toBool($envLogRequests) : true;

        $envAppName = getenv('MAX_BOT_APP_NAME');
        $appName = ($envAppName !== false && $envAppName !== '') ? $envAppName : 'MaxBot';

        return new self($token, $timeout, $retries, $rateLimit, $verifySsl, $logRequests, $appName);
    }

    /**
     * Создаёт конфигурацию из INI-файла.
     *
     * @throws MaxConfigException
     */
    public static function fromIniFile(?string $path = null): self
    {
        if ($path === null || $path === '') {
            $sdkDir = \dirname(__DIR__);
            $path = $sdkDir . \DIRECTORY_SEPARATOR . 'cfg' . \DIRECTORY_SEPARATOR . 'config.ini';
        }

        if (!is_file($path) || !is_readable($path)) {
            throw new MaxConfigException('INI файл конфигурации не найден: ' . $path);
        }

        $data = parse_ini_file($path, true);
        if ($data === false) {
            throw new MaxConfigException('Не удалось разобрать INI файл: ' . $path);
        }

        /** @var array<string, string> $section */
        $section = isset($data['max']) && \is_array($data['max']) ? $data['max'] : [];

        $token = self::iniVal($section, 'token');
        if ($token === null || $token === '') {
            throw new MaxConfigException('В INI-файле (секция [max]) не задан token.');
        }

        $timeout = self::iniVal($section, 'timeout');
        $retries = self::iniVal($section, 'retries');
        $rateLimit = self::iniVal($section, 'rate_limit');
        $verifySsl = self::iniVal($section, 'verify_ssl');
        $logRequests = self::iniVal($section, 'log_requests');
        $appName = self::iniVal($section, 'app_name');

        return new self(
            $token,
            $timeout !== null ? (int) $timeout : self::DEFAULT_TIMEOUT,
            $retries !== null ? (int) $retries : self::DEFAULT_RETRIES,
            $rateLimit !== null ? (int) $rateLimit : self::DEFAULT_RATE_LIMIT,
            $verifySsl !== null ? self::toBool($verifySsl) : true,
            $logRequests !== null ? self::toBool($logRequests) : true,
            $appName ?? 'MaxBot',
        );
    }

    /**
     * @param array<string, string> $arr
     */
    private static function iniVal(array $arr, string $key): ?string
    {
        return isset($arr[$key]) ? (string) $arr[$key] : null;
    }

    private static function toBool(mixed $value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }
        $v = \is_scalar($value) ? strtolower(trim((string) $value)) : '';

        return match ($v) {
            '1', 'true', 'yes', 'on' => true,
            default => false,
        };
    }
}
