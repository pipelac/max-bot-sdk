<?php

namespace MaxBotSdk\Tests\Unit;

use MaxBotSdk\Exception\MaxValidationException;
use MaxBotSdk\Utils\InputValidator;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для InputValidator.
 */
class InputValidatorTest extends TestCase
{
    public function testValidateTextValid()
    {
        $result = InputValidator::validateText('Привет');
        $this->assertEquals('Привет', $result);
    }

    public function testValidateTextEmptyThrows()
    {
        $this->expectException(MaxValidationException::class);
        InputValidator::validateText('');
    }

    public function testValidateTextWhitespaceThrows()
    {
        $this->expectException(MaxValidationException::class);
        InputValidator::validateText('   ');
    }

    public function testValidateTextTooLongThrows()
    {
        $this->expectException(MaxValidationException::class);
        InputValidator::validateText(str_repeat('а', 4001));
    }

    public function testValidateTextExactLimit()
    {
        $text = str_repeat('a', 4000);
        $result = InputValidator::validateText($text);
        $this->assertEquals(4000, mb_strlen($result, 'UTF-8'));
    }

    public function testValidateIdValid()
    {
        $this->assertEquals(123, InputValidator::validateId(123, 'chat_id'));
        $this->assertEquals(456, InputValidator::validateId('456', 'chat_id'));
    }

    public function testValidateIdNullThrows()
    {
        $this->expectException(MaxValidationException::class);
        InputValidator::validateId(null, 'chat_id');
    }

    public function testValidateIdEmptyThrows()
    {
        $this->expectException(MaxValidationException::class);
        InputValidator::validateId('', 'chat_id');
    }

    public function testValidateIdStringThrows()
    {
        $this->expectException(MaxValidationException::class);
        InputValidator::validateId('abc', 'chat_id');
    }

    public function testValidateWebhookUrlValid()
    {
        $result = InputValidator::validateWebhookUrl('https://example.com/webhook');
        $this->assertEquals('https://example.com/webhook', $result);
    }

    public function testValidateWebhookUrlHttpThrows()
    {
        $this->expectException(MaxValidationException::class);
        InputValidator::validateWebhookUrl('http://example.com/webhook');
    }

    public function testValidateUploadTypeValid()
    {
        $this->assertEquals('image', InputValidator::validateUploadType('image'));
        $this->assertEquals('video', InputValidator::validateUploadType('video'));
        $this->assertEquals('audio', InputValidator::validateUploadType('audio'));
        $this->assertEquals('file', InputValidator::validateUploadType('file'));
    }

    public function testValidateUploadTypeInvalidThrows()
    {
        $this->expectException(MaxValidationException::class);
        InputValidator::validateUploadType('document');
    }

    public function testValidateCallbackIdValid()
    {
        $result = InputValidator::validateCallbackId('cb_123');
        $this->assertEquals('cb_123', $result);
    }

    public function testValidateCallbackIdEmptyThrows()
    {
        $this->expectException(MaxValidationException::class);
        InputValidator::validateCallbackId('');
    }

    public function testValidateNotEmptyValid()
    {
        $result = InputValidator::validateNotEmpty('hello', 'field');
        $this->assertEquals('hello', $result);
    }

    public function testValidateNotEmptyNullThrows()
    {
        $this->expectException(MaxValidationException::class);
        InputValidator::validateNotEmpty(null, 'field');
    }

    public function testValidateChatLinkValid()
    {
        $this->assertEquals('my_channel', InputValidator::validateChatLink('my_channel'));
        $this->assertEquals('@my_channel', InputValidator::validateChatLink('@my_channel'));
        $this->assertEquals('@channel-123', InputValidator::validateChatLink('@channel-123'));
        $this->assertEquals('@a', InputValidator::validateChatLink('@a'));
    }

    public function testValidateChatLinkEmptyThrows()
    {
        $this->expectException(MaxValidationException::class);
        InputValidator::validateChatLink('   ');
    }

    public function testValidateChatLinkTooLongThrows()
    {
        $this->expectException(MaxValidationException::class);
        InputValidator::validateChatLink('@' . str_repeat('a', 255));
    }

    public function testValidateChatLinkInvalidFormatThrows()
    {
        $this->expectException(MaxValidationException::class);
        InputValidator::validateChatLink('123channel');
    }

    public function testValidateChatLinkInvalidCharsThrows()
    {
        $this->expectException(MaxValidationException::class);
        InputValidator::validateChatLink('@my channel');
    }

    public function testValidateChatLinkUrlThrows()
    {
        $this->expectException(MaxValidationException::class);
        InputValidator::validateChatLink('https://max.ru/channel');
    }
}
