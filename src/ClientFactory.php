<?php

declare(strict_types=1);

namespace MaxBotSdk;

use MaxBotSdk\Contracts\ConfigInterface;
use MaxBotSdk\Contracts\HttpClientInterface;
use MaxBotSdk\Http\CurlHttpClient;
use MaxBotSdk\Http\RetryHandler;

/**
 * Фабрика для создания экземпляров Client.
 *
 * @since 1.0.0
 */
final class ClientFactory
{
    /**
     * Создать клиент по токену.
     */
    public static function create(string $token, ?HttpClientInterface $httpClient = null): Client
    {
        $config = new Config($token);
        return self::fromConfig($config, $httpClient);
    }

    /**
     * Создать клиент из INI-файла.
     */
    public static function fromIni(?string $path = null, ?HttpClientInterface $httpClient = null): Client
    {
        $config = Config::fromIniFile($path);
        return self::fromConfig($config, $httpClient);
    }

    /**
     * Создать клиент из переменных окружения.
     */
    public static function fromEnvironment(?HttpClientInterface $httpClient = null): Client
    {
        $config = Config::fromEnvironment();
        return self::fromConfig($config, $httpClient);
    }

    /**
     * Создать клиент из ConfigBuilder.
     */
    public static function fromBuilder(ConfigBuilder $builder, ?HttpClientInterface $httpClient = null): Client
    {
        return self::fromConfig($builder->build(), $httpClient);
    }

    /**
     * Создать клиент из объекта конфигурации.
     */
    public static function fromConfig(ConfigInterface $config, ?HttpClientInterface $httpClient = null): Client
    {
        $logger = $config->getLogger();
        $http = $httpClient ?? new CurlHttpClient($config, $logger);
        $decoder = new ResponseDecoder($logger);
        $retryHandler = new RetryHandler($config->getRetries());

        return new Client($config, $http, $decoder, $retryHandler);
    }
}
