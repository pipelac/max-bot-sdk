<?php

declare(strict_types=1);

namespace MaxBotSdk\Tests\Unit;

use MaxBotSdk\Exception\MaxValidationException;
use MaxBotSdk\Utils\KeyboardBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class KeyboardBuilderTest extends TestCase
{
    #[Test]
    public function buildSimpleKeyboard(): void
    {
        $rows = [
            [
                ['type' => 'callback', 'text' => 'Кнопка 1', 'payload' => 'btn1'],
                ['type' => 'callback', 'text' => 'Кнопка 2', 'payload' => 'btn2'],
            ],
        ];

        $result = KeyboardBuilder::build($rows);
        self::assertSame('inline_keyboard', $result['type']);
        self::assertCount(1, $result['payload']['buttons']);
        self::assertCount(2, $result['payload']['buttons'][0]);
    }

    #[Test]
    public function buildMultiRowKeyboard(): void
    {
        $rows = [
            [['type' => 'callback', 'text' => 'R1', 'payload' => '1']],
            [['type' => 'callback', 'text' => 'R2', 'payload' => '2']],
            [['type' => 'callback', 'text' => 'R3', 'payload' => '3']],
        ];

        $result = KeyboardBuilder::build($rows);
        self::assertCount(3, $result['payload']['buttons']);
    }

    #[Test]
    public function buildTooManyRowsThrows(): void
    {
        $rows = [];
        for ($i = 0; $i < 31; $i++) {
            $rows[] = [['type' => 'callback', 'text' => 'Btn', 'payload' => (string) $i]];
        }

        $this->expectException(MaxValidationException::class);
        KeyboardBuilder::build($rows);
    }

    #[Test]
    public function buildTooManyButtonsPerRowThrows(): void
    {
        $row = [];
        for ($i = 0; $i < 8; $i++) {
            $row[] = ['type' => 'callback', 'text' => 'Btn', 'payload' => (string) $i];
        }

        $this->expectException(MaxValidationException::class);
        KeyboardBuilder::build([$row]);
    }

    #[Test]
    public function buildTotalButtonsExactLimit(): void
    {
        $rows = [];
        for ($i = 0; $i < 30; $i++) {
            $row = [];
            for ($j = 0; $j < 7; $j++) {
                $row[] = ['type' => 'callback', 'text' => 'B', 'payload' => "$i-$j"];
            }
            $rows[] = $row;
        }
        $result = KeyboardBuilder::build($rows);
        self::assertSame('inline_keyboard', $result['type']);
    }
}
