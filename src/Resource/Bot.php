<?php

declare(strict_types=1);

namespace MaxBotSdk\Resource;

use MaxBotSdk\DTO\ActionResult;
use MaxBotSdk\DTO\User;

/**
 * Ресурс: информация о боте и управление настройками бота.
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

    /**
     * Изменить или обновить команды бота (PATCH /me/commands).
     *
     * @param array<int, array{name: string, description?: string}> $commands
     * @return ActionResult
     * @throws \MaxBotSdk\Exception\MaxApiException
     * @since 2.2.0
     */
    public function patchCommands(array $commands): ActionResult
    {
        $data = $this->patch('/me/commands', ['commands' => $commands]);
        return ActionResult::fromArray($data);
    }
}
