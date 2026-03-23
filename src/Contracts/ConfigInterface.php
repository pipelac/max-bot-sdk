<?php

declare(strict_types=1);

namespace MaxBotSdk\Contracts;

/**
 * Интерфейс конфигурации MAX Bot API SDK.
 *
 * Конфигурация иммутабельна — все значения задаются при создании.
 *
 * @since 1.0.0
 */
interface ConfigInterface
{
    public function getToken(): string;

    public function getTimeout(): int;

    public function getRetries(): int;

    public function getRateLimit(): int;

    public function getVerifySsl(): bool;

    public function getLogRequests(): bool;

    public function getAppName(): string;

    public function getLogger(): ?LoggerInterface;
}
