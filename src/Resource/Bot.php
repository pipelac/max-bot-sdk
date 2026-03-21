<?php

namespace App\Component\Max\Resource;

use App\Component\Max\DTO\User;

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
     * @throws \App\Component\Max\Exception\MaxApiException
     */
    public function getMe()
    {
        $data = $this->get('/me');
        return User::fromArray($data);
    }
}
