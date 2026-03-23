<?php

declare(strict_types=1);

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
     * @throws \MaxBotSdk\Exception\MaxApiException
     */
    public function getMe(): User
    {
        $data = $this->get('/me');
        return User::fromArray($data);
    }
}
