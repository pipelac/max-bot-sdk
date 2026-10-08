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
     * @param array<string, mixed>|null $message            Обновлённое сообщение или null.
     * @param string|null               $notification       Текст всплывающего уведомления пользователю.
     * @param bool|null                 $disableLinkPreview Отключить генерацию превью для ссылок.
     */
    public function answerCallback(
        string $callbackId,
        ?array $message = null,
        ?string $notification = null,
        ?bool $disableLinkPreview = null,
    ): ActionResult {
        InputValidator::validateCallbackId($callbackId);

        $payload = [];
        if ($message !== null) {
            $payload['message'] = $message;
        }
        if ($notification !== null) {
            $payload['notification'] = $notification;
        }

        $query = ['callback_id' => $callbackId];
        if ($disableLinkPreview !== null) {
            $query['disable_link_preview'] = $disableLinkPreview ? 'true' : 'false';
        }

        $data = $this->post('/answers', $payload, $query);
        return ActionResult::fromArray($data);
    }
}
