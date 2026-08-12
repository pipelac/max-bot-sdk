<?php

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

    /**
     * Изменить или обновить команды бота (PATCH /me/commands).
     *
     * @param array $commands Список команд
     * @return ActionResult
     * @throws \MaxBotSdk\Exception\MaxApiException
     * @since 1.2.0
     */
    public function patchCommands(array $commands)
    {
        $data = $this->patch('/me/commands', array('commands' => $commands));
        return ActionResult::fromArray($data);
    }
}
