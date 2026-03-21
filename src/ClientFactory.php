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
 * Собирает полный граф зависимостей: Config → HttpClient → ResponseDecoder → RetryHandler → Client.
 * По умолчанию использует встроенный CurlHttpClient, но принимает любой HttpClientInterface.
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
 * // С кастомным HTTP-клиентом:
 * $client = ClientFactory::createFromConfig($config, $myGuzzleAdapter);
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
     * @param string                  $token      Токен бота MAX.
     * @param LoggerInterface|null    $logger     Логгер.
     * @param HttpClientInterface|null $httpClient Кастомный HTTP-клиент (null = CurlHttpClient).
     * @return Client
     */
    public static function create($token, LoggerInterface $logger = null, HttpClientInterface $httpClient = null)
    {
        $config = new Config($token);
        if ($logger !== null) {
            $config = ConfigBuilder::create($token)->withLogger($logger)->build();
        }
        return self::buildClient($config, $httpClient);
    }

    /**
     * Создать клиент из INI-файла.
     *
     * @param string|null             $path       Путь к файлу (null = cfg/config.ini).
     * @param HttpClientInterface|null $httpClient Кастомный HTTP-клиент (null = CurlHttpClient).
     * @return Client
     */
    public static function createFromIni($path = null, HttpClientInterface $httpClient = null)
    {
        $config = Config::fromIniFile($path);
        return self::buildClient($config, $httpClient);
    }

    /**
     * Создать клиент из переменных окружения (12-Factor App).
     *
     * @param HttpClientInterface|null $httpClient Кастомный HTTP-клиент (null = CurlHttpClient).
     * @return Client
     */
    public static function createFromEnvironment(HttpClientInterface $httpClient = null)
    {
        $config = Config::fromEnvironment();
        return self::buildClient($config, $httpClient);
    }

    /**
     * Создать клиент из ConfigBuilder.
     *
     * @param ConfigBuilder            $builder    Настроенный builder.
     * @param HttpClientInterface|null $httpClient Кастомный HTTP-клиент (null = CurlHttpClient).
     * @return Client
     */
    public static function createFromBuilder(ConfigBuilder $builder, HttpClientInterface $httpClient = null)
    {
        $config = $builder->build();
        return self::buildClient($config, $httpClient);
    }

    /**
     * Создать клиент из готового объекта конфигурации.
     *
     * @param ConfigInterface          $config     Конфигурация.
     * @param HttpClientInterface|null $httpClient Кастомный HTTP-клиент (null = CurlHttpClient).
     * @return Client
     */
    public static function createFromConfig(ConfigInterface $config, HttpClientInterface $httpClient = null)
    {
        return self::buildClient($config, $httpClient);
    }

    /**
     * Собирает весь граф зависимостей и создаёт Client.
     *
     * Если $httpClient не передан, создаёт встроенный CurlHttpClient.
     *
     * @param ConfigInterface          $config     Конфигурация.
     * @param HttpClientInterface|null $httpClient Кастомный HTTP-клиент.
     * @return Client
     */
    private static function buildClient(ConfigInterface $config, HttpClientInterface $httpClient = null)
    {
        $logger = $config->getLogger();

        if ($httpClient === null) {
            $httpClient = new CurlHttpClient($config, $logger);
        }

        $responseDecoder = new ResponseDecoder($logger);
        $retryHandler = new RetryHandler($config->getRetries());

        return new Client($config, $httpClient, $responseDecoder, $retryHandler);
    }
}
