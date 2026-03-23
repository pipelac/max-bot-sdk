<?php

declare(strict_types=1);

namespace MaxBotSdk\Tests\Unit;

use MaxBotSdk\Exception\MaxApiException;
use MaxBotSdk\Exception\MaxConfigException;
use MaxBotSdk\Exception\MaxConnectionException;
use MaxBotSdk\Exception\MaxException;
use MaxBotSdk\Exception\MaxFileException;
use MaxBotSdk\Exception\MaxValidationException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ExceptionTest extends TestCase
{
    #[Test]
    public function maxExceptionExtendsRuntimeException(): void
    {
        $e = new MaxException('test');
        self::assertInstanceOf(\RuntimeException::class, $e);
    }

    #[Test]
    public function apiExceptionExtendsMaxException(): void
    {
        $e = new MaxApiException('test', 400);
        self::assertInstanceOf(MaxException::class, $e);
        self::assertSame(400, $e->getStatusCode());
    }

    #[Test]
    public function apiExceptionDetails(): void
    {
        $e = new MaxApiException('msg', 401, 'Bad token', 'ERR_01');
        self::assertSame('Bad token', $e->getDescription());
        self::assertSame('ERR_01', $e->getErrorCode());
        self::assertSame(401, $e->getStatusCode());
    }

    #[Test]
    public function apiExceptionPrevious(): void
    {
        $prev = new \RuntimeException('original');
        $e = new MaxApiException('msg', 500, null, null, $prev);
        self::assertSame($prev, $e->getPrevious());
    }

    #[Test]
    public function configExceptionExtendsMaxException(): void
    {
        $e = new MaxConfigException('bad config');
        self::assertInstanceOf(MaxException::class, $e);
    }

    #[Test]
    public function fileExceptionExtendsMaxException(): void
    {
        $e = new MaxFileException('file not found');
        self::assertInstanceOf(MaxException::class, $e);
    }

    #[Test]
    public function validationExceptionExtendsMaxException(): void
    {
        $e = new MaxValidationException('bad input');
        self::assertInstanceOf(MaxException::class, $e);
    }

    #[Test]
    public function connectionExceptionExtendsMaxException(): void
    {
        $e = new MaxConnectionException('timeout');
        self::assertInstanceOf(MaxException::class, $e);
    }

    #[Test]
    public function allExceptionsCatchableByBase(): void
    {
        $exceptions = [
            new MaxApiException('api', 400),
            new MaxConfigException('config'),
            new MaxFileException('file'),
            new MaxValidationException('validation'),
            new MaxConnectionException('connection'),
        ];

        foreach ($exceptions as $e) {
            self::assertInstanceOf(MaxException::class, $e);
        }
    }

    #[Test]
    public function leafExceptionsAreFinal(): void
    {
        $leafClasses = [
            MaxApiException::class,
            MaxConfigException::class,
            MaxConnectionException::class,
            MaxFileException::class,
            MaxValidationException::class,
        ];

        foreach ($leafClasses as $class) {
            $ref = new \ReflectionClass($class);
            self::assertTrue($ref->isFinal(), "$class должен быть final");
        }
    }

    #[Test]
    public function apiExceptionDefaultNullValues(): void
    {
        $e = new MaxApiException('test', 400);
        self::assertNull($e->getDescription());
        self::assertNull($e->getErrorCode());
        self::assertNull($e->getPrevious());
    }

    #[Test]
    public function exceptionMessagePreserved(): void
    {
        $e = new MaxConfigException('Missing token');
        self::assertSame('Missing token', $e->getMessage());
    }

    #[Test]
    public function fileExceptionWithPrevious(): void
    {
        $prev = new \RuntimeException('disk error');
        $e = new MaxFileException('Upload failed', 0, $prev);
        self::assertSame($prev, $e->getPrevious());
        self::assertSame('Upload failed', $e->getMessage());
    }

    #[Test]
    public function connectionExceptionWithPrevious(): void
    {
        $prev = new \RuntimeException('curl error');
        $e = new MaxConnectionException('Connection failed', 0, $prev);
        self::assertSame($prev, $e->getPrevious());
    }
}
