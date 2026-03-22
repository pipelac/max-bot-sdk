<?php

namespace MaxBotSdk\Utils;

use MaxBotSdk\Exception\MaxValidationException;

/**
 * Утилита для валидации входных параметров MAX Bot API.
 *
 * @since 1.0.0
 */
final class InputValidator
{
    /** @var int Максимальная длина текста сообщения. */
    const MAX_TEXT_LENGTH = 4000;

    /**
     * Валидирует текст сообщения.
     *
     * @param string $text Текст для проверки.
     * @return string Валидный текст.
     * @throws MaxValidationException
     */
    public static function validateText($text)
    {
        if (trim($text) === '') {
            throw new MaxValidationException('Текст сообщения не может быть пустым.');
        }
        $length = mb_strlen($text, 'UTF-8');
        if ($length > self::MAX_TEXT_LENGTH) {
            throw new MaxValidationException(sprintf(
                'Текст превышает максимум %d символов (текущий: %d).',
                self::MAX_TEXT_LENGTH,
                $length
            ));
        }
        return $text;
    }

    /**
     * Валидирует числовой идентификатор.
     *
     * @param mixed  $id   Значение для проверки.
     * @param string $name Имя параметра для сообщения об ошибке.
     * @return int Валидный ID.
     * @throws MaxValidationException
     */
    public static function validateId($id, $name = 'ID')
    {
        if ($id === null || $id === '' || (!is_int($id) && !is_numeric($id))) {
            throw new MaxValidationException($name . ' должен быть числом.');
        }
        return (int) $id;
    }

    /**
     * Валидирует URL webhook.
     *
     * @param string $url URL для проверки.
     * @return string Валидный URL.
     * @throws MaxValidationException
     */
    public static function validateWebhookUrl($url)
    {
        if (strpos($url, 'https://') !== 0) {
            throw new MaxValidationException('URL webhook должен начинаться с https://');
        }
        return $url;
    }

    /**
     * Валидирует тип загружаемого файла.
     *
     * @param string $type Тип файла.
     * @return string Валидный тип.
     * @throws MaxValidationException
     */
    public static function validateUploadType($type)
    {
        $allowed = ['image', 'video', 'audio', 'file'];
        if (!in_array($type, $allowed, true)) {
            throw new MaxValidationException(
                'Некорректный тип файла. Допустимы: ' . implode(', ', $allowed)
            );
        }
        return $type;
    }

    /**
     * Валидирует callback_id.
     *
     * @param string $callbackId ID коллбэка.
     * @return string
     * @throws MaxValidationException
     */
    public static function validateCallbackId($callbackId)
    {
        if (empty($callbackId)) {
            throw new MaxValidationException('callback_id не может быть пустым.');
        }
        return $callbackId;
    }

    /**
     * Валидирует непустую строку.
     *
     * @param string|null $value     Строка для проверки.
     * @param string $fieldName Название поля.
     * @return string
     * @throws MaxValidationException
     */
    public static function validateNotEmpty($value, $fieldName = 'Поле')
    {
        if ($value === null || trim((string) $value) === '') {
            throw new MaxValidationException($fieldName . ' не может быть пустым.');
        }
        return (string) $value;
    }

    /**
     * Маскирует токен для безопасного логирования.
     *
     * @param string $token Токен.
     * @return string Маскированный токен.
     */
    public static function maskToken($token)
    {
        $length = strlen($token);
        if ($length <= 6) {
            return str_repeat('*', $length);
        }
        return str_repeat('*', $length - 6) . substr($token, -6);
    }
}
