<?php

declare(strict_types=1);

namespace MaxBotSdk\Tests\Unit;

use MaxBotSdk\Utils\ContactValidator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ContactValidatorTest extends TestCase
{
    #[Test]
    public function verifyContactHashReturnsTrueForValidHash(): void
    {
        $botToken = 'test_token';
        // Имитируем реальные переносы строк, если они уже были заменены парсером
        $vcfInfo = "BEGIN:VCARD\r\nVERSION:3.0\r\nFN:Ivan\r\nEND:VCARD\r\n";
        
        $expectedHash = hash_hmac('sha256', $vcfInfo, $botToken);

        self::assertTrue(
            ContactValidator::verifyContactHash($botToken, $vcfInfo, $expectedHash)
        );
    }

    #[Test]
    public function verifyContactHashReturnsFalseForInvalidHash(): void
    {
        $botToken = 'test_token';
        $vcfInfo = "BEGIN:VCARD\r\nVERSION:3.0\r\nFN:Ivan\r\nEND:VCARD\r\n";
        
        $expectedHash = hash_hmac('sha256', $vcfInfo, $botToken);
        $invalidHash = $expectedHash . 'invalid';

        self::assertFalse(
            ContactValidator::verifyContactHash($botToken, $vcfInfo, $invalidHash)
        );
    }

    #[Test]
    public function verifyContactHashReplacesEscapedNewlines(): void
    {
        $botToken = 'test_token';
        // Строка с экранированными \r\n (как они могут прийти из JSON)
        $vcfInfoEscaped = 'BEGIN:VCARD\r\nVERSION:3.0\r\nFN:Ivan\r\nEND:VCARD\r\n';
        
        // Ожидаемый результат после замены
        $vcfInfoReal = "BEGIN:VCARD\r\nVERSION:3.0\r\nFN:Ivan\r\nEND:VCARD\r\n";
        
        $expectedHash = hash_hmac('sha256', $vcfInfoReal, $botToken);

        self::assertTrue(
            ContactValidator::verifyContactHash($botToken, $vcfInfoEscaped, $expectedHash)
        );
    }
}
