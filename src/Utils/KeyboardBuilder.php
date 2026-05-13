<?php

namespace MaxBotSdk\Utils;

use MaxBotSdk\Exception\MaxValidationException;

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

    /** @var int Строгий макс. кнопок в ряду для специфичных типов */
    const MAX_PER_ROW_STRICT = 3;

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

            $limit = self::MAX_PER_ROW;
            foreach ($row as $button) {
                if (is_array($button) && isset($button['type'])) {
                    if (in_array($button['type'], array('link', 'open_app', 'request_geo_location', 'request_contact'), true)) {
                        $limit = self::MAX_PER_ROW_STRICT;
                        break;
                    }
                }
            }

            if (count($row) > $limit) {
                if ($limit === self::MAX_PER_ROW_STRICT) {
                    throw new MaxValidationException('Превышено макс. кнопок в ряду (лимит 3 из-за специфичных типов)');
                } else {
                    throw new MaxValidationException('Превышено макс. кнопок в ряду: ' . self::MAX_PER_ROW);
                }
            }
            $totalButtons += count($row);
        }

        if ($totalButtons > self::MAX_BUTTONS) {
            throw new MaxValidationException(
                'Превышено макс. количество кнопок: ' . self::MAX_BUTTONS
            );
        }

        return [
            'type'    => 'inline_keyboard',
            'payload' => ['buttons' => $rows],
        ];
    }
}
