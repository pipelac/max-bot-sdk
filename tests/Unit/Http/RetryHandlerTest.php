<?php

namespace MaxBotSdk\Tests\Unit\Http;

use MaxBotSdk\Exception\MaxApiException;
use MaxBotSdk\Exception\MaxConnectionException;
use MaxBotSdk\Http\RetryHandler;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для RetryHandler.
 */
class RetryHandlerTest extends TestCase
{
    public function testSuccessOnFirstAttempt()
    {
        $handler = new TestRetryHandler(3);
        $result = $handler->execute(function () {
            return 'success';
        });
        $this->assertEquals('success', $result);
    }

    public function testRetryOnServerError()
    {
        $handler = new TestRetryHandler(3);
        $attempts = 0;

        $result = $handler->execute(function () use (&$attempts) {
            $attempts++;
            if ($attempts < 3) {
                throw new MaxApiException('Server error', 500);
            }
            return 'recovered';
        });

        $this->assertEquals('recovered', $result);
        $this->assertEquals(3, $attempts);
    }

    public function testRetryOn429()
    {
        $handler = new TestRetryHandler(2);
        $attempts = 0;

        $result = $handler->execute(function () use (&$attempts) {
            $attempts++;
            if ($attempts < 2) {
                throw new MaxApiException('Rate limited', 429);
            }
            return 'ok';
        });

        $this->assertEquals('ok', $result);
        $this->assertEquals(2, $attempts);
    }

    public function testRetryOnConnectionException()
    {
        $handler = new TestRetryHandler(3);
        $attempts = 0;

        $result = $handler->execute(function () use (&$attempts) {
            $attempts++;
            if ($attempts < 3) {
                throw new MaxConnectionException('Connection timeout');
            }
            return 'reconnected';
        });

        $this->assertEquals('reconnected', $result);
        $this->assertEquals(3, $attempts);
    }

    public function testConnectionExceptionExhaustedRetries()
    {
        $handler = new TestRetryHandler(1);
        $attempts = 0;

        try {
            $handler->execute(function () use (&$attempts) {
                $attempts++;
                throw new MaxConnectionException('Connection refused');
            });
            $this->fail('Expected MaxConnectionException');
        } catch (MaxConnectionException $e) {
            $this->assertEquals(2, $attempts); // 1 initial + 1 retry
        }
    }

    public function testNoRetryOn400()
    {
        $handler = new TestRetryHandler(3);
        $attempts = 0;

        try {
            $handler->execute(function () use (&$attempts) {
                $attempts++;
                throw new MaxApiException('Bad request', 400);
            });
            $this->fail('Expected MaxApiException');
        } catch (MaxApiException $e) {
            $this->assertEquals(400, $e->getStatusCode());
            $this->assertEquals(1, $attempts); // Не повторяет для 4xx (кроме 429)
        }
    }

    public function testNoRetryOn401()
    {
        $handler = new TestRetryHandler(3);
        $attempts = 0;

        try {
            $handler->execute(function () use (&$attempts) {
                $attempts++;
                throw new MaxApiException('Unauthorized', 401);
            });
            $this->fail('Expected MaxApiException');
        } catch (MaxApiException $e) {
            $this->assertEquals(1, $attempts);
        }
    }

    public function testExhaustedRetries()
    {
        $handler = new TestRetryHandler(2);
        $attempts = 0;

        try {
            $handler->execute(function () use (&$attempts) {
                $attempts++;
                throw new MaxApiException('Server error', 500);
            });
            $this->fail('Expected MaxApiException');
        } catch (MaxApiException $e) {
            $this->assertEquals(500, $e->getStatusCode());
            $this->assertEquals(3, $attempts); // 1 initial + 2 retries
        }
    }

    public function testZeroRetries()
    {
        $handler = new TestRetryHandler(0);
        $attempts = 0;

        try {
            $handler->execute(function () use (&$attempts) {
                $attempts++;
                throw new MaxApiException('Server error', 500);
            });
            $this->fail('Expected MaxApiException');
        } catch (MaxApiException $e) {
            $this->assertEquals(1, $attempts); // Без повторов
        }
    }

    // --- isRetryableApi: Reflection тест приватного метода ---

    public function testIsRetryableApiFor502()
    {
        $handler = new RetryHandler(3);
        $method = new \ReflectionMethod($handler, 'isRetryableApi');
        $method->setAccessible(true);

        $e502 = new MaxApiException('Bad gateway', 502);
        $this->assertTrue($method->invoke($handler, $e502));
    }

    public function testIsRetryableApiFor404()
    {
        $handler = new RetryHandler(3);
        $method = new \ReflectionMethod($handler, 'isRetryableApi');
        $method->setAccessible(true);

        $e404 = new MaxApiException('Not found', 404);
        $this->assertFalse($method->invoke($handler, $e404));
    }

    public function testIsRetryableApiFor429()
    {
        $handler = new RetryHandler(3);
        $method = new \ReflectionMethod($handler, 'isRetryableApi');
        $method->setAccessible(true);

        $e429 = new MaxApiException('Rate limit', 429);
        $this->assertTrue($method->invoke($handler, $e429));
    }

    // --- calculateDelay: Reflection тест ---

    public function testCalculateDelayExponentialBackoff()
    {
        $handler = new RetryHandler(3, 1000, 30000);
        $method = new \ReflectionMethod($handler, 'calculateDelay');
        $method->setAccessible(true);

        $delay1 = $method->invoke($handler, 1); // base * 2^0 = 1000 ± jitter
        $delay2 = $method->invoke($handler, 2); // base * 2^1 = 2000 ± jitter
        $delay3 = $method->invoke($handler, 3); // base * 2^2 = 4000 ± jitter

        $this->assertGreaterThanOrEqual(900, $delay1);
        $this->assertLessThanOrEqual(1100, $delay1);
        $this->assertGreaterThanOrEqual(1800, $delay2);
        $this->assertLessThanOrEqual(2200, $delay2);
        $this->assertGreaterThanOrEqual(3600, $delay3);
        $this->assertLessThanOrEqual(4400, $delay3);
    }

    public function testCalculateDelayMaxCap()
    {
        $handler = new RetryHandler(3, 10000, 15000);
        $method = new \ReflectionMethod($handler, 'calculateDelay');
        $method->setAccessible(true);

        // attempt 3: 10000 * 2^2 = 40000 → capped at 15000 ± jitter
        $delay = $method->invoke($handler, 3);
        $this->assertLessThanOrEqual(16500, $delay);
    }

    // --- Sleep recording ---

    public function testSleepIsCalledDuringRetry()
    {
        $handler = new TestRetryHandler(2);
        $attempts = 0;

        $handler->execute(function () use (&$attempts) {
            $attempts++;
            if ($attempts < 2) {
                throw new MaxApiException('Server error', 500);
            }
            return 'ok';
        });

        $this->assertNotEmpty($handler->sleepCalls);
        $this->assertCount(1, $handler->sleepCalls);
        $this->assertGreaterThan(0, $handler->sleepCalls[0]);
    }

    // --- Constructor validation ---

    public function testConstructorMinValues()
    {
        $handler = new RetryHandler(-5, 50, 10);
        $method = new \ReflectionProperty($handler, 'maxRetries');
        $method->setAccessible(true);
        $this->assertEquals(0, $method->getValue($handler));

        $baseDelay = new \ReflectionProperty($handler, 'baseDelayMs');
        $baseDelay->setAccessible(true);
        $this->assertEquals(100, $baseDelay->getValue($handler)); // min 100
    }
}

/**
 * Тестовый RetryHandler, который не делает реальный sleep.
 */
class TestRetryHandler extends RetryHandler
{
    /** @var array */
    public $sleepCalls = [];

    protected function sleep($milliseconds)
    {
        $this->sleepCalls[] = $milliseconds;
        // Не спим в тестах
    }
}
