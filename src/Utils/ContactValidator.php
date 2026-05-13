<?php

declare(strict_types=1);

namespace MaxBotSdk\Utils;

/**
 * Утилита для верификации контактов (request_contact).
 *
 * @since 2.1.0
 */
final class ContactValidator
{
    /**
     * Проверяет подлинность контакта, отправленного пользователем в ответ на кнопку request_contact.
     *
     * @param string $botToken Токен бота (access_token)
     * @param string $vcfInfo Строка с контактной информацией (поле vcf_info из payload)
     * @param string $hash Хеш для проверки (поле hash из payload)
     * @return bool
     */
    public static function verifyContactHash(string $botToken, string $vcfInfo, string $hash): bool
    {
        // Преобразуем экранированные символы \r\n (из JSON) в реальные переносы строк
        $normalizedVcf = str_replace('\r\n', "\r\n", $vcfInfo);

        $expectedHash = hash_hmac('sha256', $normalizedVcf, $botToken);

        return hash_equals($expectedHash, $hash);
    }
}
