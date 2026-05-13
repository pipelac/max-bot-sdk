<?php

namespace MaxBotSdk\Utils;

/**
 * Утилита для верификации контактов (request_contact).
 *
 * @since 1.0.2
 */
final class ContactValidator
{
    /**
     * Проверяет валидность контакта через HMAC-SHA256.
     *
     * @param string $botToken Токен бота
     * @param string $vcfInfo Строка vCard, извлеченная из payload
     * @param string $hash Хеш, присланный API
     * @return bool Результат проверки (true - валидно, false - подделка)
     */
    public static function verifyContactHash($botToken, $vcfInfo, $hash)
    {
        // Из JSON может прийти строковое представление переноса строки (\r\n) - 4 символа.
        // Для правильного расчета хеша их нужно заменить на реальные CRLF (2 символа).
        $normalizedVcf = str_replace('\r\n', "\r\n", $vcfInfo);

        $calculatedHash = hash_hmac('sha256', $normalizedVcf, $botToken);

        return hash_equals($calculatedHash, $hash);
    }
}
