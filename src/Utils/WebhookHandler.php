<?php

declare(strict_types=1);

namespace MaxBotSdk\Utils;

use MaxBotSdk\DTO\Update;

/**
 * Обработчик webhook-запросов MAX Bot API.
 *
 * @since 1.0.0
 */
final class WebhookHandler
{
    public function parseUpdate(string $rawBody): ?Update
    {
        if ($rawBody === '') {
            return null;
        }

        $decoded = json_decode($rawBody, true);
        if (!\is_array($decoded)) {
            return null;
        }

        return Update::fromArray($decoded);
    }

    /**
     * Проверить подлинность webhook-запроса (timing-safe).
     */
    public function verifySecret(string $expectedSecret, string $actualSecret): bool
    {
        if ($expectedSecret === '' || $actualSecret === '') {
            return false;
        }
        return hash_equals($expectedSecret, $actualSecret);
    }
}
