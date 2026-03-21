<?php

namespace MaxBotSdk\Contracts;

/**
 * Интерфейс ресурса API.
 *
 * @since 1.0.0
 */
interface ResourceInterface
{
    /**
     * Получает экземпляр клиента.
     *
     * @return ClientInterface
     */
    public function getClient();
}
