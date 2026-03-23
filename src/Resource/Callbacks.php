<?php

declare(strict_types=1);

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
     * @param array<string, mixed>|null $message Обновлённое сообщение или null.
     */
    public function answerCallback(string $callbackId, ?array $message = null, ?string $notification = null): ActionResult
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
