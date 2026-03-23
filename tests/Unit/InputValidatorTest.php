<?php

declare(strict_types=1);

namespace MaxBotSdk\Tests\Unit;

use MaxBotSdk\Enum\UploadType;
use MaxBotSdk\Exception\MaxValidationException;
use MaxBotSdk\Utils\InputValidator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InputValidatorTest extends TestCase
{
    #[Test]
    public function validateTextValid(): void
    {
        $result = InputValidator::validateText('Привет');
        self::assertSame('Привет', $result);
    }

    #[Test]
    public function validateTextEmptyThrows(): void
    {
        $this->expectException(MaxValidationException::class);
        InputValidator::validateText('');
    }

    #[Test]
    public function validateTextWhitespaceThrows(): void
    {
        $this->expectException(MaxValidationException::class);
        InputValidator::validateText('   ');
    }

    #[Test]
    public function validateTextTooLongThrows(): void
    {
        $this->expectException(MaxValidationException::class);
        InputValidator::validateText(str_repeat('а', 4001));
    }

    #[Test]
    public function validateTextExactLimit(): void
    {
        $text = str_repeat('a', 4000);
        $result = InputValidator::validateText($text);
        self::assertSame(4000, mb_strlen($result, 'UTF-8'));
    }

    #[Test]
    public function validateIdValid(): void
    {
        self::assertSame(123, InputValidator::validateId(123, 'chat_id'));
        self::assertSame(456, InputValidator::validateId('456', 'chat_id'));
    }

    #[Test]
    public function validateIdNullThrows(): void
    {
        $this->expectException(MaxValidationException::class);
        InputValidator::validateId(null, 'chat_id');
    }

    #[Test]
    public function validateIdEmptyThrows(): void
    {
        $this->expectException(MaxValidationException::class);
        InputValidator::validateId('', 'chat_id');
    }

    #[Test]
    public function validateIdStringThrows(): void
    {
        $this->expectException(MaxValidationException::class);
        InputValidator::validateId('abc', 'chat_id');
    }

    #[Test]
    public function validateWebhookUrlValid(): void
    {
        $result = InputValidator::validateWebhookUrl('https://example.com/webhook');
        self::assertSame('https://example.com/webhook', $result);
    }

    #[Test]
    public function validateWebhookUrlHttpThrows(): void
    {
        $this->expectException(MaxValidationException::class);
        InputValidator::validateWebhookUrl('http://example.com/webhook');
    }

    #[Test]
    public function validateUploadTypeValid(): void
    {
        self::assertSame(UploadType::Image, InputValidator::validateUploadType(UploadType::Image));
        self::assertSame(UploadType::Video, InputValidator::validateUploadType(UploadType::Video));
        self::assertSame(UploadType::Audio, InputValidator::validateUploadType(UploadType::Audio));
        self::assertSame(UploadType::File, InputValidator::validateUploadType(UploadType::File));
    }

    #[Test]
    public function validateCallbackIdValid(): void
    {
        $result = InputValidator::validateCallbackId('cb_123');
        self::assertSame('cb_123', $result);
    }

    #[Test]
    public function validateCallbackIdEmptyThrows(): void
    {
        $this->expectException(MaxValidationException::class);
        InputValidator::validateCallbackId('');
    }

    #[Test]
    public function validateNotEmptyValid(): void
    {
        $result = InputValidator::validateNotEmpty('hello', 'field');
        self::assertSame('hello', $result);
    }

    #[Test]
    public function validateNotEmptyNullThrows(): void
    {
        $this->expectException(MaxValidationException::class);
        InputValidator::validateNotEmpty(null, 'field');
    }
}
