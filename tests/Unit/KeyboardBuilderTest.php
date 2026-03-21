<?php

namespace App\Component\Max\Tests\Unit;

use App\Component\Max\Utils\KeyboardBuilder;
use App\Component\Max\Exception\MaxValidationException;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для KeyboardBuilder.
 */
class KeyboardBuilderTest extends TestCase
{
    public function testBuildSimpleKeyboard()
    {
        $rows = array(
            array(
                array('type' => 'callback', 'text' => 'Кнопка 1', 'payload' => 'btn1'),
                array('type' => 'callback', 'text' => 'Кнопка 2', 'payload' => 'btn2'),
            ),
        );

        $result = KeyboardBuilder::build($rows);

        $this->assertEquals('inline_keyboard', $result['type']);
        $this->assertCount(1, $result['payload']['buttons']);
        $this->assertCount(2, $result['payload']['buttons'][0]);
    }

    public function testBuildMultiRowKeyboard()
    {
        $rows = array(
            array(array('type' => 'callback', 'text' => 'R1', 'payload' => '1')),
            array(array('type' => 'callback', 'text' => 'R2', 'payload' => '2')),
            array(array('type' => 'callback', 'text' => 'R3', 'payload' => '3')),
        );

        $result = KeyboardBuilder::build($rows);
        $this->assertCount(3, $result['payload']['buttons']);
    }

    public function testBuildTooManyRowsThrows()
    {
        $rows = array();
        for ($i = 0; $i < 31; $i++) {
            $rows[] = array(array('type' => 'callback', 'text' => 'Btn', 'payload' => $i));
        }

        $this->expectException(MaxValidationException::class);
        KeyboardBuilder::build($rows);
    }

    public function testBuildTooManyButtonsPerRowThrows()
    {
        $row = array();
        for ($i = 0; $i < 8; $i++) {
            $row[] = array('type' => 'callback', 'text' => 'Btn', 'payload' => $i);
        }

        $this->expectException(MaxValidationException::class);
        KeyboardBuilder::build(array($row));
    }

    public function testBuildNonArrayRowThrows()
    {
        $this->expectException(MaxValidationException::class);
        KeyboardBuilder::build(array('not_an_array'));
    }

    public function testBuildTotalButtonsExactLimit()
    {
        $rows = array();
        // 7 кнопок * 30 рядов = 210 — ровно лимит, должно пройти
        for ($i = 0; $i < 30; $i++) {
            $row = array();
            for ($j = 0; $j < 7; $j++) {
                $row[] = array('type' => 'callback', 'text' => 'B', 'payload' => "$i-$j");
            }
            $rows[] = $row;
        }
        $result = KeyboardBuilder::build($rows);
        $this->assertEquals('inline_keyboard', $result['type']);
    }
}
