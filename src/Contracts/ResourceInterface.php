<?php

declare(strict_types=1);

namespace MaxBotSdk\Contracts;

/**
 * Интерфейс ресурса API.
 *
 * @since 1.0.0
 */
interface ResourceInterface
{
    public function getClient(): ClientInterface;
}
