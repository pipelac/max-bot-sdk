<?php

namespace MaxBotSdk\Tests\Unit;

use MaxBotSdk\Exception\MaxApiException;
use MaxBotSdk\Exception\MaxConfigException;
use MaxBotSdk\Exception\MaxConnectionException;
use MaxBotSdk\Exception\MaxException;
use MaxBotSdk\Exception\MaxFileException;
use MaxBotSdk\Exception\MaxValidationException;
use PHPUnit\Framework\TestCase;

/**
 * Тесты иерархии исключений.
 */
class ExceptionTest extends TestCase
{
    public function testMaxExceptionExtendsRuntimeException()
    {
        $e = new MaxException('test');
        $this->assertInstanceOf(\RuntimeException::class, $e);
    }

    public function testApiExceptionExtendsMaxException()
    {
        $e = new MaxApiException('test', 400);
        $this->assertInstanceOf(MaxException::class, $e);
        $this->assertEquals(400, $e->getStatusCode());
    }

    public function testApiExceptionDetails()
    {
        $e = new MaxApiException('msg', 401, 'Bad token', 'ERR_01');
        $this->assertEquals('Bad token', $e->getDescription());
        $this->assertEquals('ERR_01', $e->getErrorCode());
        $this->assertEquals(401, $e->getStatusCode());
    }

    public function testApiExceptionPrevious()
    {
        $prev = new \RuntimeException('original');
        $e = new MaxApiException('msg', 500, null, null, $prev);
        $this->assertSame($prev, $e->getPrevious());
    }

    public function testConfigExceptionExtendsMaxException()
    {
        $e = new MaxConfigException('bad config');
        $this->assertInstanceOf(MaxException::class, $e);
    }

    public function testFileExceptionExtendsMaxException()
    {
        $e = new MaxFileException('file not found');
        $this->assertInstanceOf(MaxException::class, $e);
    }

    public function testValidationExceptionExtendsMaxException()
    {
        $e = new MaxValidationException('bad input');
        $this->assertInstanceOf(MaxException::class, $e);
    }

    public function testConnectionExceptionExtendsMaxException()
    {
        $e = new MaxConnectionException('timeout');
        $this->assertInstanceOf(MaxException::class, $e);
    }

    public function testAllExceptionsCatchableByBase()
    {
        $exceptions = [
            new MaxApiException('api', 400),
            new MaxConfigException('config'),
            new MaxFileException('file'),
            new MaxValidationException('validation'),
            new MaxConnectionException('connection'),
        ];

        foreach ($exceptions as $e) {
            $this->assertInstanceOf(MaxException::class, $e);
        }
    }
}
