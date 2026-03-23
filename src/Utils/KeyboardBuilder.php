<?php

declare(strict_types=1);

namespace MaxBotSdk\Utils;

use MaxBotSdk\Exception\MaxValidationException;

/**
 * Построитель inline-клавиатур для MAX Bot API.
 *
 * @since 1.0.0
 */
final class KeyboardBuilder
{
    public const MAX_BUTTONS = 210;
    public const MAX_ROWS = 30;
    public const MAX_PER_ROW = 7;

    /**
     * @param list<list<array<string, mixed>>> $rows
     * @return array{type: string, payload: array{buttons: list<list<array<string, mixed>>>}}
     * @throws MaxValidationException
     */
    public static function build(array $rows): array
    {
        if (\count($rows) > self::MAX_ROWS) {
            throw new MaxValidationException(
                'Превышено макс. количество рядов кнопок: ' . self::MAX_ROWS,
            );
        }

        $totalButtons = 0;
        foreach ($rows as $row) {
            if (!\is_array($row)) {
                throw new MaxValidationException('Каждый ряд кнопок должен быть массивом.');
            }
            if (\count($row) > self::MAX_PER_ROW) {
                throw new MaxValidationException(
                    'Превышено макс. кнопок в ряду: ' . self::MAX_PER_ROW,
                );
            }
            $totalButtons += \count($row);
        }

        if ($totalButtons > self::MAX_BUTTONS) {
            throw new MaxValidationException(
                'Превышено макс. количество кнопок: ' . self::MAX_BUTTONS,
            );
        }

        return [
            'type'    => 'inline_keyboard',
            'payload' => ['buttons' => $rows],
        ];
    }
}
