<?php

declare(strict_types=1);

namespace MaxBotSdk\Contracts;

/**
 * Интерфейс логгера (PSR-3 совместимый).
 *
 * @since 1.0.0
 */
interface LoggerInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function debug(string $message, array $context = []): void;

    /**
     * @param array<string, mixed> $context
     */
    public function info(string $message, array $context = []): void;

    /**
     * @param array<string, mixed> $context
     */
    public function warning(string $message, array $context = []): void;

    /**
     * @param array<string, mixed> $context
     */
    public function error(string $message, array $context = []): void;
}
