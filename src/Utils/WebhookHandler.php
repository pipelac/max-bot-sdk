<?php

namespace App\Component\Max\Utils;

use App\Component\Max\DTO\Update;

/**
 * Обработчик webhook-запросов MAX Bot API.
 *
 * Отвечает за парсинг входящих обновлений и верификацию секрета.
 * Не использует суперглобалы — все данные передаются явно.
 *
 * Пример:
 * <code>
 * $handler = new WebhookHandler();
 * $update = $handler->parseUpdate(file_get_contents('php://input'));
 * if ($update !== null && $handler->verifySecret('my_secret', $_SERVER['HTTP_X_MAX_BOT_API_SECRET'])) {
 *     // Обработка обновления
 * }
 * </code>
 *
 * @since 1.0.0
 */
final class WebhookHandler
{
    /**
     * Распарсить входящий webhook Update.
     *
     * @param string $rawBody Тело HTTP-запроса.
     * @return Update|null Объект Update или null при невалидных данных.
     */
    public function parseUpdate($rawBody)
    {
        if (empty($rawBody)) {
            return null;
        }

        $decoded = json_decode($rawBody, true);
        if ($decoded === null || !is_array($decoded)) {
            return null;
        }

        return Update::fromArray($decoded);
    }

    /**
     * Проверить подлинность webhook-запроса по secret.
     *
     * Использует timing-safe сравнение (hash_equals).
     *
     * @param string $expectedSecret Ожидаемый секрет.
     * @param string $actualSecret   Значение секрета из заголовка X-Max-Bot-Api-Secret.
     * @return bool
     */
    public function verifySecret($expectedSecret, $actualSecret)
    {
        if (empty($expectedSecret) || empty($actualSecret)) {
            return false;
        }
        return hash_equals($expectedSecret, $actualSecret);
    }
}
