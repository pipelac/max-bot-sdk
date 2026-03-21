<?php

namespace App\Component\Max\Resource;

use App\Component\Max\DTO\ActionResult;
use App\Component\Max\Utils\InputValidator;

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
     * @throws \App\Component\Max\Exception\MaxApiException
     * @throws \App\Component\Max\Exception\MaxValidationException
     */
    public function answerCallback($callbackId, array $message = null, $notification = null)
    {
        InputValidator::validateCallbackId($callbackId);

        $payload = array();
        if ($message !== null) {
            $payload['message'] = $message;
        }
        if ($notification !== null) {
            $payload['notification'] = $notification;
        }

        $data = $this->post('/answers', $payload, array('callback_id' => $callbackId));
        return ActionResult::fromArray($data);
    }
}
