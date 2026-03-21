<?php

namespace App\Component\Max;

use App\Component\Max\Contracts\ConfigInterface;
use App\Component\Max\Contracts\LoggerInterface;
use App\Component\Max\Exception\MaxConfigException;

/**
 * Иммутабельная конфигурация MAX Bot API SDK.
 *
 * Все параметры задаются при создании через конструктор, фабричные методы
 * или ConfigBuilder. После создания изменение невозможно.
 *
 * Пример:
 * <code>
 * // Через конструктор (с defaults):
 * $config = new Config('TOKEN');
 *
 * // Через ConfigBuilder:
 * $config = ConfigBuilder::create('TOKEN')
 *     ->withTimeout(60)
 *     ->withRetries(5)
 *     ->build();
 *
 * // Из ENV (12-Factor App):
 * $config = Config::fromEnvironment();
 *
 * // Из INI файла:
 * $config = Config::fromIniFile('/path/to/config.ini');
 * </code>
 *
 * @since 1.0.0
 */
final class Config implements ConfigInterface
{
    /** @var int Таймаут по умолчанию (секунды). */
    const DEFAULT_TIMEOUT = 30;

    /** @var int Минимальный таймаут (секунды). */
    const MIN_TIMEOUT = 5;

    /** @var int Максимальный таймаут (секунды). */
    const MAX_TIMEOUT = 300;

    /** @var int Повторные попытки по умолчанию. */
    const DEFAULT_RETRIES = 3;

    /** @var int Максимум повторных попыток. */
    const MAX_RETRIES = 10;

    /** @var int Лимит запросов/сек по умолчанию. */
    const DEFAULT_RATE_LIMIT = 30;

    /** @var int Максимальный лимит запросов/сек. */
    const MAX_RATE_LIMIT = 100;

    /** @var string Токен бота. */
    private $token;

    /** @var int Таймаут HTTP-запросов (секунды). */
    private $timeout;

    /** @var int Количество повторных попыток. */
    private $retries;

    /** @var int Лимит запросов в секунду. */
    private $rateLimit;

    /** @var bool Проверка SSL-сертификатов. */
    private $verifySsl;

    /** @var bool Логировать успешные запросы. */
    private $logRequests;

    /** @var string Имя приложения для логов. */
    private $appName;

    /** @var LoggerInterface|null Внешний логгер. */
    private $logger;

    /**
     * Конструктор. Все параметры иммутабельны после создания.
     *
     * @param string               $token       Токен бота MAX.
     * @param int                  $timeout     Таймаут (5–300 сек).
     * @param int                  $retries     Повторные попытки (0–10).
     * @param int                  $rateLimit   Лимит запросов/сек (1–100).
     * @param bool                 $verifySsl   Проверка SSL.
     * @param bool                 $logRequests Логировать запросы.
     * @param string               $appName     Имя приложения для логов.
     * @param LoggerInterface|null $logger      Логгер.
     * @throws MaxConfigException  Если параметры невалидны.
     */
    public function __construct(
        $token,
        $timeout = self::DEFAULT_TIMEOUT,
        $retries = self::DEFAULT_RETRIES,
        $rateLimit = self::DEFAULT_RATE_LIMIT,
        $verifySsl = true,
        $logRequests = true,
        $appName = 'MaxBot',
        LoggerInterface $logger = null
    ) {
        $token = trim((string) $token);
        if ($token === '') {
            throw new MaxConfigException('Токен MAX бота не указан.');
        }
        $this->token = $token;

        $timeout = (int) $timeout;
        if ($timeout < self::MIN_TIMEOUT || $timeout > self::MAX_TIMEOUT) {
            throw new MaxConfigException(sprintf(
                'Таймаут должен быть от %d до %d секунд.',
                self::MIN_TIMEOUT, self::MAX_TIMEOUT
            ));
        }
        $this->timeout = $timeout;

        $retries = (int) $retries;
        if ($retries < 0 || $retries > self::MAX_RETRIES) {
            throw new MaxConfigException(sprintf(
                'Количество повторов должно быть от 0 до %d.',
                self::MAX_RETRIES
            ));
        }
        $this->retries = $retries;

        $rateLimit = (int) $rateLimit;
        if ($rateLimit < 1 || $rateLimit > self::MAX_RATE_LIMIT) {
            throw new MaxConfigException(sprintf(
                'Лимит запросов должен быть от 1 до %d.',
                self::MAX_RATE_LIMIT
            ));
        }
        $this->rateLimit = $rateLimit;

        $this->verifySsl = (bool) $verifySsl;
        $this->logRequests = (bool) $logRequests;
        $this->appName = trim((string) $appName) !== '' ? trim((string) $appName) : 'MaxBot';
        $this->logger = $logger;
    }

    // --- Геттеры (только чтение) ---

    /** @return string */
    public function getToken()
    {
        return $this->token;
    }

    /** @return int */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /** @return int */
    public function getRetries()
    {
        return $this->retries;
    }

    /** @return int */
    public function getRateLimit()
    {
        return $this->rateLimit;
    }

    /** @return bool */
    public function getVerifySsl()
    {
        return $this->verifySsl;
    }

    /** @return bool */
    public function getLogRequests()
    {
        return $this->logRequests;
    }

    /** @return string */
    public function getAppName()
    {
        return $this->appName;
    }

    /** @return LoggerInterface|null */
    public function getLogger()
    {
        return $this->logger;
    }

    // --- Фабричные методы ---

    /**
     * Создаёт конфигурацию из переменных окружения (12-Factor App).
     *
     * Переменные:
     *   MAX_BOT_TOKEN (обязательно), MAX_BOT_TIMEOUT, MAX_BOT_RETRIES,
     *   MAX_BOT_RATE_LIMIT, MAX_BOT_VERIFY_SSL, MAX_BOT_LOG_REQUESTS,
     *   MAX_BOT_APP_NAME
     *
     * @return self
     * @throws MaxConfigException Если MAX_BOT_TOKEN не задан.
     */
    public static function fromEnvironment()
    {
        $token = getenv('MAX_BOT_TOKEN');
        if ($token === false || trim($token) === '') {
            throw new MaxConfigException('Переменная окружения MAX_BOT_TOKEN не задана.');
        }

        $timeout = self::DEFAULT_TIMEOUT;
        $envTimeout = getenv('MAX_BOT_TIMEOUT');
        if ($envTimeout !== false && $envTimeout !== '') {
            $timeout = (int) $envTimeout;
        }

        $retries = self::DEFAULT_RETRIES;
        $envRetries = getenv('MAX_BOT_RETRIES');
        if ($envRetries !== false && $envRetries !== '') {
            $retries = (int) $envRetries;
        }

        $rateLimit = self::DEFAULT_RATE_LIMIT;
        $envRateLimit = getenv('MAX_BOT_RATE_LIMIT');
        if ($envRateLimit !== false && $envRateLimit !== '') {
            $rateLimit = (int) $envRateLimit;
        }

        $verifySsl = true;
        $envVerifySsl = getenv('MAX_BOT_VERIFY_SSL');
        if ($envVerifySsl !== false && $envVerifySsl !== '') {
            $verifySsl = self::toBool($envVerifySsl);
        }

        $logRequests = true;
        $envLogRequests = getenv('MAX_BOT_LOG_REQUESTS');
        if ($envLogRequests !== false && $envLogRequests !== '') {
            $logRequests = self::toBool($envLogRequests);
        }

        $appName = 'MaxBot';
        $envAppName = getenv('MAX_BOT_APP_NAME');
        if ($envAppName !== false && $envAppName !== '') {
            $appName = $envAppName;
        }

        return new self($token, $timeout, $retries, $rateLimit, $verifySsl, $logRequests, $appName);
    }

    /**
     * Создаёт конфигурацию из INI-файла.
     *
     * @param string|null $path Путь к INI-файлу. Если null — ищет cfg/config.ini.
     * @return self
     * @throws MaxConfigException
     */
    public static function fromIniFile($path = null)
    {
        if ($path === null || $path === '') {
            $sdkDir = dirname(__DIR__);
            $path = $sdkDir . DIRECTORY_SEPARATOR . 'cfg' . DIRECTORY_SEPARATOR . 'config.ini';
        }

        if (!is_file($path) || !is_readable($path)) {
            throw new MaxConfigException('INI файл конфигурации не найден: ' . $path);
        }

        $data = parse_ini_file($path, true);
        if ($data === false || !is_array($data)) {
            throw new MaxConfigException('Не удалось разобрать INI файл: ' . $path);
        }

        $section = isset($data['max']) && is_array($data['max']) ? $data['max'] : array();

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
            $appName !== null ? $appName : 'MaxBot'
        );
    }

    // --- Вспомогательные методы ---

    /**
     * @param array  $arr
     * @param string $key
     * @return string|null
     */
    private static function iniVal(array $arr, $key)
    {
        return isset($arr[$key]) ? (string) $arr[$key] : null;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    private static function toBool($value)
    {
        if (is_bool($value)) {
            return $value;
        }
        $v = strtolower(trim((string) $value));
        return ($v === '1' || $v === 'true' || $v === 'yes' || $v === 'on');
    }
}
