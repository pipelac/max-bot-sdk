<?php

namespace MaxBotSdk\Contracts;

/**
 * Интерфейс конфигурации MAX Bot API SDK.
 *
 * Определяет контракт для доступа к параметрам конфигурации.
 * Конфигурация иммутабельна — все значения задаются при создании.
 *
 * @since 1.0.0
 */
interface ConfigInterface
{
    /**
     * Получает токен бота.
     *
     * @return string
     */
    public function getToken();

    /**
     * Получает таймаут HTTP-запросов в секундах.
     *
     * @return int
     */
    public function getTimeout();

    /**
     * Получает количество повторных попыток.
     *
     * @return int
     */
    public function getRetries();

    /**
     * Получает лимит запросов в секунду.
     *
     * @return int
     */
    public function getRateLimit();

    /**
     * Получает флаг проверки SSL.
     *
     * @return bool
     */
    public function getVerifySsl();

    /**
     * Получает флаг логирования запросов.
     *
     * @return bool
     */
    public function getLogRequests();

    /**
     * Получает имя приложения для логов.
     *
     * @return string
     */
    public function getAppName();

    /**
     * Получает логгер или null.
     *
     * @return LoggerInterface|null
     */
    public function getLogger();
}
