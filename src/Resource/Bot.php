<?php

namespace MaxBotSdk\Resource;

use MaxBotSdk\DTO\User;

/**
 * Ресурс: информация о боте.
 *
 * @since 1.0.0
 */
final class Bot extends ResourceAbstract
{
    /**
     * Получить информацию о текущем боте.
     *
     * @return User
     * @throws \MaxBotSdk\Exception\MaxApiException
     */
    public function getMe()
    {
        $data = $this->get('/me');
        return User::fromArray($data);
    }
}
