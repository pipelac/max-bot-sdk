<?php

namespace MaxBotSdk\Tests\Unit;

use MaxBotSdk\Utils\ContactValidator;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для ContactValidator.
 */
class ContactValidatorTest extends TestCase
{
    /**
     * @dataProvider validHashProvider
     */
    public function testVerifyContactHashValid($botToken, $vcfInfo, $expectedHash)
    {
        $this->assertTrue(ContactValidator::verifyContactHash($botToken, $vcfInfo, $expectedHash));
    }

    public function validHashProvider()
    {
        return array(
            'Обычная строка' => array(
                'token123',
                'BEGIN:VCARD\r\nVERSION:3.0\r\nEND:VCARD',
                hash_hmac('sha256', "BEGIN:VCARD\r\nVERSION:3.0\r\nEND:VCARD", 'token123')
            ),
            'Пустая строка' => array(
                'token123',
                '',
                hash_hmac('sha256', '', 'token123')
            )
        );
    }

    public function testVerifyContactHashInvalid()
    {
        $token = 'token123';
        $vcf = 'BEGIN:VCARD\r\nEND:VCARD';
        $invalidHash = 'deadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeef';

        $this->assertFalse(ContactValidator::verifyContactHash($token, $vcf, $invalidHash));
    }

    public function testVerifyContactHashReplacesNewlines()
    {
        $token = 'secret_token';
        // Входные данные с экранированными символами (4 символа: \, r, \, n)
        $vcfInput = 'Line1\r\nLine2';
        
        // Ожидаемый результат: реальный CRLF (2 символа)
        $expectedVcf = "Line1\r\nLine2";
        
        $expectedHash = hash_hmac('sha256', $expectedVcf, $token);

        $this->assertTrue(ContactValidator::verifyContactHash($token, $vcfInput, $expectedHash));
    }
}
