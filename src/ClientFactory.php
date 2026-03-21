<?php

namespace App\Component\Max;

use App\Component\Max\Contracts\ClientInterface;
use App\Component\Max\Contracts\ConfigInterface;
use App\Component\Max\Contracts\HttpClientInterface;
use App\Component\Max\Contracts\LoggerInterface;
use App\Component\Max\Http\CurlHttpClient;
use App\Component\Max\Http\RetryHandler;

/**
 * Фабрика для создания экземпляров Client.
 *
 * Собирает полный граф зависимостей: Config → CurlHttpClient → ResponseDecoder → RetryHandler → Client.
 *
 * Пример:
 * <code>
 * // Из токена:
 * $client = ClientFactory::create('TOKEN');
 *
 * // Из INI-файла:
 * $client = ClientFactory::createFromIni('/path/to/config.ini');
 *
 * // Из ENV:
 * $client = ClientFactory::createFromEnvironment();
 *
 * // Из ConfigBuilder:
 * $client = ClientFactory::createFromBuilder(
 *     ConfigBuilder::create('TOKEN')->withTimeout(60)->withRetries(5)
 * );
 * </code>
 *
 * @since 1.0.0
 */
final class ClientFactory
{
    /**
     * Создать клиент по токену.
     *
     * @param string               $token  Токен бота MAX.
     * @param LoggerInterface|null $logger Логгер.
     * @return Client
     */
    public static function create($token, LoggerInterface $logger = null)
    {
        $config = new Config($token);
        if ($logger !== null) {
            $config = ConfigBuilder::create($token)->withLogger($logger)->build();
        }
        return self::buildClient($config);
    }

    /**
     * Создать клиент из INI-файла.
     *
     * @param string|null $path Путь к файлу (null = cfg/config.ini).
     * @return Client
     */
    public static function createFromIni($path = null)
    {
        $config = Config::fromIniFile($path);
        return self::buildClient($config);
    }

    /**
     * Создать клиент из переменных окружения (12-Factor App).
     *
     * @return Client
     */
    public static function createFromEnvironment()
    {
        $config = Config::fromEnvironment();
        return self::buildClient($config);
    }

    /**
     * Создать клиент из ConfigBuilder.
     *
     * @param ConfigBuilder $builder Настроенный builder.
     * @return Client
     */
    public static function createFromBuilder(ConfigBuilder $builder)
    {
        $config = $builder->build();
        return self::buildClient($config);
    }

    /**
     * Создать клиент из готового объекта конфигурации.
     *
     * @param ConfigInterface $config Конфигурация.
     * @return Client
     */
    public static function createFromConfig(ConfigInterface $config)
    {
        return self::buildClient($config);
    }

    /**
     * Собирает весь граф зависимостей и создаёт Client.
     *
     * @param ConfigInterface $config Конфигурация.
     * @return Client
     */
    private static function buildClient(ConfigInterface $config)
    {
        $logger = $config->getLogger();
        $httpClient = new CurlHttpClient($config, $logger);
        $responseDecoder = new ResponseDecoder($logger);
        $retryHandler = new RetryHandler($config->getRetries());

        return new Client($config, $httpClient, $responseDecoder, $retryHandler);
    }
}
