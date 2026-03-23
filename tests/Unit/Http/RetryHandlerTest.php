<?php

declare(strict_types=1);

namespace MaxBotSdk\Tests\Unit\Http;

use MaxBotSdk\Exception\MaxApiException;
use MaxBotSdk\Exception\MaxConnectionException;
use MaxBotSdk\Http\RetryHandler;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RetryHandlerTest extends TestCase
{
    #[Test]
    public function successOnFirstAttempt(): void
    {
        $handler = new TestRetryHandler(3);
        $result = $handler->execute(static fn(): string => 'success');
        self::assertSame('success', $result);
    }

    #[Test]
    public function retryOnServerError(): void
    {
        $handler = new TestRetryHandler(3);
        $attempts = 0;

        $result = $handler->execute(static function () use (&$attempts): string {
            $attempts++;
            if ($attempts < 3) {
                throw new MaxApiException('Server error', 500);
            }
            return 'recovered';
        });

        self::assertSame('recovered', $result);
        self::assertSame(3, $attempts);
    }

    #[Test]
    public function retryOn429(): void
    {
        $handler = new TestRetryHandler(2);
        $attempts = 0;

        $result = $handler->execute(static function () use (&$attempts): string {
            $attempts++;
            if ($attempts < 2) {
                throw new MaxApiException('Rate limited', 429);
            }
            return 'ok';
        });

        self::assertSame('ok', $result);
        self::assertSame(2, $attempts);
    }

    #[Test]
    public function retryOnConnectionException(): void
    {
        $handler = new TestRetryHandler(3);
        $attempts = 0;

        $result = $handler->execute(static function () use (&$attempts): string {
            $attempts++;
            if ($attempts < 3) {
                throw new MaxConnectionException('Connection timeout');
            }
            return 'reconnected';
        });

        self::assertSame('reconnected', $result);
        self::assertSame(3, $attempts);
    }

    #[Test]
    public function connectionExceptionExhaustedRetries(): void
    {
        $handler = new TestRetryHandler(1);
        $attempts = 0;

        try {
            $handler->execute(static function () use (&$attempts): never {
                $attempts++;
                throw new MaxConnectionException('Connection refused');
            });
            self::fail('Expected MaxConnectionException');
        } catch (MaxConnectionException) {
            self::assertSame(2, $attempts); // 1 initial + 1 retry
        }
    }

    #[Test]
    public function noRetryOn400(): void
    {
        $handler = new TestRetryHandler(3);
        $attempts = 0;

        try {
            $handler->execute(static function () use (&$attempts): never {
                $attempts++;
                throw new MaxApiException('Bad request', 400);
            });
            self::fail('Expected MaxApiException');
        } catch (MaxApiException $e) {
            self::assertSame(400, $e->getStatusCode());
            self::assertSame(1, $attempts);
        }
    }

    #[Test]
    public function noRetryOn401(): void
    {
        $handler = new TestRetryHandler(3);
        $attempts = 0;

        try {
            $handler->execute(static function () use (&$attempts): never {
                $attempts++;
                throw new MaxApiException('Unauthorized', 401);
            });
            self::fail('Expected MaxApiException');
        } catch (MaxApiException) {
            self::assertSame(1, $attempts);
        }
    }

    #[Test]
    public function exhaustedRetries(): void
    {
        $handler = new TestRetryHandler(2);
        $attempts = 0;

        try {
            $handler->execute(static function () use (&$attempts): never {
                $attempts++;
                throw new MaxApiException('Server error', 500);
            });
            self::fail('Expected MaxApiException');
        } catch (MaxApiException $e) {
            self::assertSame(500, $e->getStatusCode());
            self::assertSame(3, $attempts); // 1 initial + 2 retries
        }
    }

    #[Test]
    public function zeroRetries(): void
    {
        $handler = new TestRetryHandler(0);
        $attempts = 0;

        try {
            $handler->execute(static function () use (&$attempts): never {
                $attempts++;
                throw new MaxApiException('Server error', 500);
            });
            self::fail('Expected MaxApiException');
        } catch (MaxApiException) {
            self::assertSame(1, $attempts);
        }
    }
}

/**
 * Тестовый RetryHandler, который не делает реальный sleep.
 */
class TestRetryHandler extends RetryHandler
{
    /** @var list<int> */
    public array $sleepCalls = [];

    protected function sleep(int $milliseconds): void
    {
        $this->sleepCalls[] = $milliseconds;
    }
}
