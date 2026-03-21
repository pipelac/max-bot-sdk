<?php

namespace App\Component\Max\Utils;

use App\Component\Max\Exception\MaxValidationException;

/**
 * Построитель inline-клавиатур для MAX Bot API.
 *
 * Пример:
 * <code>
 * $keyboard = KeyboardBuilder::build(array(
 *     array(
 *         array('type' => 'callback', 'text' => 'Кнопка 1', 'payload' => 'btn1'),
 *         array('type' => 'callback', 'text' => 'Кнопка 2', 'payload' => 'btn2'),
 *     ),
 * ));
 * </code>
 *
 * @since 1.0.0
 */
final class KeyboardBuilder
{
    /** @var int Макс. кнопок */
    const MAX_BUTTONS = 210;

    /** @var int Макс. рядов */
    const MAX_ROWS = 30;

    /** @var int Макс. кнопок в ряду */
    const MAX_PER_ROW = 7;

    /**
     * Сформировать вложение inline-клавиатуры.
     *
     * @param array $rows Массив рядов кнопок.
     * @return array Вложение для attachments.
     * @throws MaxValidationException
     */
    public static function build(array $rows)
    {
        if (count($rows) > self::MAX_ROWS) {
            throw new MaxValidationException(
                'Превышено макс. количество рядов кнопок: ' . self::MAX_ROWS
            );
        }

        $totalButtons = 0;
        foreach ($rows as $row) {
            if (!is_array($row)) {
                throw new MaxValidationException('Каждый ряд кнопок должен быть массивом.');
            }
            if (count($row) > self::MAX_PER_ROW) {
                throw new MaxValidationException(
                    'Превышено макс. кнопок в ряду: ' . self::MAX_PER_ROW
                );
            }
            $totalButtons += count($row);
        }

        if ($totalButtons > self::MAX_BUTTONS) {
            throw new MaxValidationException(
                'Превышено макс. количество кнопок: ' . self::MAX_BUTTONS
            );
        }

        return array(
            'type'    => 'inline_keyboard',
            'payload' => array('buttons' => $rows),
        );
    }
}
