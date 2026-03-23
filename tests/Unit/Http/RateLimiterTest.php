<?php

declare(strict_types=1);

namespace MaxBotSdk\Tests\Unit\Http;

use MaxBotSdk\Http\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Тесты RateLimiter (final class — тестируем через публичный API).
 */
final class RateLimiterTest extends TestCase
{
    #[Test]
    public function getMaxRequestsPerSecond(): void
    {
        $limiter = new RateLimiter(10);
        self::assertSame(10, $limiter->getMaxRequestsPerSecond());
    }

    #[Test]
    public function constructorAcceptsDifferentValues(): void
    {
        $limiter1 = new RateLimiter(1);
        self::assertSame(1, $limiter1->getMaxRequestsPerSecond());

        $limiter30 = new RateLimiter(30);
        self::assertSame(30, $limiter30->getMaxRequestsPerSecond());

        $limiter100 = new RateLimiter(100);
        self::assertSame(100, $limiter100->getMaxRequestsPerSecond());
    }

    #[Test]
    public function throttleBelowLimitCompletesQuickly(): void
    {
        $limiter = new RateLimiter(100); // Высокий лимит

        $start = microtime(true);
        for ($i = 0; $i < 10; $i++) {
            $limiter->throttle();
        }
        $elapsed = microtime(true) - $start;

        // 10 запросов при лимите 100/сек — не должно быть задержки > 100ms
        self::assertLessThan(0.1, $elapsed, 'Throttle ниже лимита не должен вызывать задержку');
    }

    #[Test]
    public function throttleRecordsTimestamps(): void
    {
        $limiter = new RateLimiter(50);

        $limiter->throttle();
        $limiter->throttle();
        $limiter->throttle();

        // Проверяем через Reflection что timestamps записываются
        $ref = new \ReflectionProperty($limiter, 'timestamps');
        $timestamps = $ref->getValue($limiter);

        self::assertCount(3, $timestamps);
        self::assertContainsOnly('float', $timestamps);
    }

    #[Test]
    public function throttleTimestampsIncrease(): void
    {
        $limiter = new RateLimiter(50);

        $limiter->throttle();
        usleep(1000); // 1ms
        $limiter->throttle();

        $ref = new \ReflectionProperty($limiter, 'timestamps');
        $timestamps = $ref->getValue($limiter);

        self::assertCount(2, $timestamps);
        self::assertGreaterThan($timestamps[0], $timestamps[1]);
    }

    #[Test]
    public function instanceIsIndependent(): void
    {
        $limiter1 = new RateLimiter(5);
        $limiter2 = new RateLimiter(10);

        $limiter1->throttle();

        $ref = new \ReflectionProperty(RateLimiter::class, 'timestamps');

        $ts1 = $ref->getValue($limiter1);
        $ts2 = $ref->getValue($limiter2);

        self::assertCount(1, $ts1);
        self::assertCount(0, $ts2, 'Вторая инстанция не должна содержать timestamps первой');
    }
}
