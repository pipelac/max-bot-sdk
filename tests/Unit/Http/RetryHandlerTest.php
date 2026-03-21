<?php

namespace App\Component\Max\Tests\Unit\Http;

use App\Component\Max\Http\RetryHandler;
use App\Component\Max\Exception\MaxApiException;
use App\Component\Max\Exception\MaxConnectionException;
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
}

/**
 * Тестовый RetryHandler, который не делает реальный sleep.
 */
class TestRetryHandler extends RetryHandler
{
    /** @var array */
    public $sleepCalls = array();

    protected function sleep($milliseconds)
    {
        $this->sleepCalls[] = $milliseconds;
        // Не спим в тестах
    }
}
