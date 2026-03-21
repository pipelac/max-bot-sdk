<?php

namespace MaxBotSdk\Resource;

use MaxBotSdk\DTO\ActionResult;
use MaxBotSdk\Utils\InputValidator;

/**
 * Ресурс: обработка callback-ов (нажатия inline-кнопок).
 *
 * @since 1.0.0
 */
final class Callbacks extends ResourceAbstract
{
    /**
     * Ответить на callback.
     *
     * @param string      $callbackId   ID callback.
     * @param array|null  $message      Обновлённое сообщение или null.
     * @param string|null $notification Одноразовое уведомление.
     * @return ActionResult
     * @throws \MaxBotSdk\Exception\MaxApiException
     * @throws \MaxBotSdk\Exception\MaxValidationException
     */
    public function answerCallback($callbackId, array $message = null, $notification = null)
    {
        InputValidator::validateCallbackId($callbackId);

        $payload = [];
        if ($message !== null) {
            $payload['message'] = $message;
        }
        if ($notification !== null) {
            $payload['notification'] = $notification;
        }

        $data = $this->post('/answers', $payload, ['callback_id' => $callbackId]);
        return ActionResult::fromArray($data);
    }
}
