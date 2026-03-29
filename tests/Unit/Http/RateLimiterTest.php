<?php

namespace MaxBotSdk\Tests\Unit\Http;

use MaxBotSdk\Http\RateLimiter;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для RateLimiter.
 */
class RateLimiterTest extends TestCase
{
    public function testConstructorSetsMaxRequests()
    {
        $limiter = new TestRateLimiter(10);
        $this->assertEquals(10, $limiter->getMaxRequestsPerSecond());
    }

    public function testConstructorMinimumIsOne()
    {
        $limiter = new TestRateLimiter(0);
        $this->assertEquals(1, $limiter->getMaxRequestsPerSecond());

        $limiter2 = new TestRateLimiter(-5);
        $this->assertEquals(1, $limiter2->getMaxRequestsPerSecond());
    }

    public function testThrottleDoesNotSleepBelowLimit()
    {
        $limiter = new TestRateLimiter(5);

        // 3 запроса при лимите 5 — не должно быть задержки
        $limiter->throttle();
        $limiter->throttle();
        $limiter->throttle();

        $this->assertEmpty($limiter->sleepCalls);
    }

    public function testThrottleSleepsWhenLimitReached()
    {
        // Лимит 2 запроса в секунду
        $limiter = new TestRateLimiter(2);

        // 2 запроса — норма
        $limiter->throttle();
        $limiter->throttle();

        // 3-й запрос — должен вызвать sleep
        $limiter->throttle();

        $this->assertNotEmpty($limiter->sleepCalls, 'Должен быть вызван sleep при превышении лимита');
    }

    public function testGetMaxRequestsPerSecond()
    {
        $limiter = new TestRateLimiter(42);
        $this->assertEquals(42, $limiter->getMaxRequestsPerSecond());
    }

    public function testThrottleRegistersTimestamps()
    {
        $limiter = new TestRateLimiter(100);
        $limiter->throttle();
        $limiter->throttle();

        // Через индексацию можно проверить что timestamps растут
        // Мы просто проверяем что 2 запроса без задержки проходят правильно
        $this->assertEmpty($limiter->sleepCalls);
    }

    // --- cleanup: Reflection тест приватного метода ---

    public function testCleanupRemovesOldTimestamps()
    {
        $limiter = new TestRateLimiter(2);
        $refClass = new \ReflectionClass(RateLimiter::class);
        $prop = $refClass->getProperty('timestamps');
        $prop->setAccessible(true);

        // Вставляем timestamps старше 1 секунды
        $now = microtime(true);
        $prop->setValue($limiter, [
            $now - 2.0, // old — должен быть удалён
            $now - 1.5, // old — должен быть удалён
            $now - 0.5, // new — должен остаться
        ]);

        $cleanup = $refClass->getMethod('cleanup');
        $cleanup->setAccessible(true);
        $cleanup->invoke($limiter, $now);

        $remaining = $prop->getValue($limiter);
        $this->assertCount(1, $remaining);
    }

    // --- Constructor edge case ---

    public function testConstructorLargeValues()
    {
        $limiter = new TestRateLimiter(1000);
        $this->assertEquals(1000, $limiter->getMaxRequestsPerSecond());
    }
}

/**
 * Тестовый RateLimiter без реального usleep.
 */
class TestRateLimiter extends RateLimiter
{
    /** @var float[] */
    public $sleepCalls = [];

    protected function sleep($seconds)
    {
        $this->sleepCalls[] = $seconds;
        // Не вызываем usleep — тест быстрый
    }
}
