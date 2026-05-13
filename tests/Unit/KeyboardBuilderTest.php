<?php

namespace MaxBotSdk\Tests\Unit;

use MaxBotSdk\Exception\MaxValidationException;
use MaxBotSdk\Utils\KeyboardBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для KeyboardBuilder.
 */
class KeyboardBuilderTest extends TestCase
{
    public function testBuildSimpleKeyboard()
    {
        $rows = [
            [
                ['type' => 'callback', 'text' => 'Кнопка 1', 'payload' => 'btn1'],
                ['type' => 'callback', 'text' => 'Кнопка 2', 'payload' => 'btn2'],
            ],
        ];

        $result = KeyboardBuilder::build($rows);

        $this->assertEquals('inline_keyboard', $result['type']);
        $this->assertCount(1, $result['payload']['buttons']);
        $this->assertCount(2, $result['payload']['buttons'][0]);
    }

    public function testBuildMultiRowKeyboard()
    {
        $rows = [
            [['type' => 'callback', 'text' => 'R1', 'payload' => '1']],
            [['type' => 'callback', 'text' => 'R2', 'payload' => '2']],
            [['type' => 'callback', 'text' => 'R3', 'payload' => '3']],
        ];

        $result = KeyboardBuilder::build($rows);
        $this->assertCount(3, $result['payload']['buttons']);
    }

    public function testBuildTooManyRowsThrows()
    {
        $rows = [];
        for ($i = 0; $i < 31; $i++) {
            $rows[] = [['type' => 'callback', 'text' => 'Btn', 'payload' => $i]];
        }

        $this->expectException(MaxValidationException::class);
        KeyboardBuilder::build($rows);
    }

    public function testBuildTooManyButtonsPerRowThrows()
    {
        $row = [];
        for ($i = 0; $i < 8; $i++) {
            $row[] = ['type' => 'callback', 'text' => 'Btn', 'payload' => $i];
        }

        $this->expectException(MaxValidationException::class);
        KeyboardBuilder::build([$row]);
    }

    public function testBuildNonArrayRowThrows()
    {
        $this->expectException(MaxValidationException::class);
        KeyboardBuilder::build(['not_an_array']);
    }

    public function testBuildTotalButtonsExactLimit()
    {
        $rows = [];
        // 7 кнопок * 30 рядов = 210 — ровно лимит, должно пройти
        for ($i = 0; $i < 30; $i++) {
            $row = [];
            for ($j = 0; $j < 7; $j++) {
                $row[] = ['type' => 'callback', 'text' => 'B', 'payload' => "$i-$j"];
            }
            $rows[] = $row;
        }
        $result = KeyboardBuilder::build($rows);
        $this->assertEquals('inline_keyboard', $result['type']);
    }

    public function testBuildStrictTypeLimitPositive()
    {
        $rows = array(
            array(
                array('type' => 'link', 'text' => 'Link 1', 'url' => 'https://a.com'),
                array('type' => 'link', 'text' => 'Link 2', 'url' => 'https://b.com'),
                array('type' => 'link', 'text' => 'Link 3', 'url' => 'https://c.com'),
            ),
        );

        $result = KeyboardBuilder::build($rows);
        $this->assertEquals('inline_keyboard', $result['type']);
        $this->assertCount(1, $result['payload']['buttons']);
        $this->assertCount(3, $result['payload']['buttons'][0]);
    }

    public function testBuildStrictTypeLimitNegativeThrows()
    {
        $rows = array(
            array(
                array('type' => 'link', 'text' => 'Link 1', 'url' => 'https://a.com'),
                array('type' => 'link', 'text' => 'Link 2', 'url' => 'https://b.com'),
                array('type' => 'link', 'text' => 'Link 3', 'url' => 'https://c.com'),
                array('type' => 'link', 'text' => 'Link 4', 'url' => 'https://d.com'),
            ),
        );

        $this->expectException(MaxValidationException::class);
        $this->expectExceptionMessage('Превышено макс. кнопок в ряду (лимит 3 из-за специфичных типов)');
        KeyboardBuilder::build($rows);
    }
}
