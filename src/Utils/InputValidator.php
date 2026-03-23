<?php

declare(strict_types=1);

namespace MaxBotSdk\Utils;

use MaxBotSdk\Enum\UploadType;
use MaxBotSdk\Exception\MaxValidationException;

/**
 * Утилита для валидации входных параметров MAX Bot API.
 *
 * @since 1.0.0
 */
final class InputValidator
{
    public const MAX_TEXT_LENGTH = 4000;

    public static function validateText(string $text): string
    {
        if (trim($text) === '') {
            throw new MaxValidationException('Текст сообщения не может быть пустым.');
        }
        $length = mb_strlen($text, 'UTF-8');
        if ($length > self::MAX_TEXT_LENGTH) {
            throw new MaxValidationException(\sprintf(
                'Текст превышает максимум %d символов (текущий: %d).',
                self::MAX_TEXT_LENGTH,
                $length,
            ));
        }
        return $text;
    }

    public static function validateId(mixed $id, string $name = 'ID'): int
    {
        if ($id === null || $id === '' || (!\is_int($id) && !is_numeric($id))) {
            throw new MaxValidationException($name . ' должен быть числом.');
        }
        return (int) $id;
    }

    public static function validateWebhookUrl(string $url): string
    {
        if (!str_starts_with($url, 'https://')) {
            throw new MaxValidationException('URL webhook должен начинаться с https://');
        }
        return $url;
    }

    public static function validateUploadType(UploadType $type): UploadType
    {
        return $type;
    }

    public static function validateCallbackId(string $callbackId): string
    {
        if ($callbackId === '') {
            throw new MaxValidationException('callback_id не может быть пустым.');
        }
        return $callbackId;
    }

    public static function validateNotEmpty(?string $value, string $fieldName = 'Поле'): string
    {
        if ($value === null || trim($value) === '') {
            throw new MaxValidationException($fieldName . ' не может быть пустым.');
        }
        return $value;
    }

    public static function maskToken(string $token): string
    {
        $length = \strlen($token);
        if ($length <= 6) {
            return str_repeat('*', $length);
        }
        return str_repeat('*', $length - 6) . substr($token, -6);
    }
}
