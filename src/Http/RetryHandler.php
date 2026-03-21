<?php

namespace App\Component\Max\Http;

use App\Component\Max\Exception\MaxApiException;
use App\Component\Max\Exception\MaxConnectionException;

/**
 * Retry-обработчик с экспоненциальной задержкой и jitter.
 *
 * Повтор происходит для transient-ошибок:
 * - HTTP 429 (Too Many Requests)
 * - HTTP 5xx (Server Error)
 * - Ошибки соединения (MaxConnectionException)
 *
 * @since 1.0.0
 */
class RetryHandler
{
    /** @var int */
    private $maxRetries;

    /** @var int Базовая задержка в миллисекундах */
    private $baseDelayMs;

    /** @var int Максимальная задержка в миллисекундах */
    private $maxDelayMs;

    /**
     * @param int $maxRetries Максимум повторных попыток.
     * @param int $baseDelayMs Базовая задержка (мс). По умолчанию 1000.
     * @param int $maxDelayMs Максимальная задержка (мс). По умолчанию 30000.
     */
    public function __construct($maxRetries = 3, $baseDelayMs = 1000, $maxDelayMs = 30000)
    {
        $this->maxRetries = max(0, (int) $maxRetries);
        $this->baseDelayMs = max(100, (int) $baseDelayMs);
        $this->maxDelayMs = max($this->baseDelayMs, (int) $maxDelayMs);
    }

    /**
     * Выполнить операцию с retry-логикой.
     *
     * @param callable $operation Callable, возвращающий результат.
     * @return mixed Результат выполнения.
     * @throws MaxApiException При истечении попыток.
     * @throws MaxConnectionException При истечении попыток.
     */
    public function execute($operation)
    {
        $attempt = 0;

        while (true) {
            try {
                return call_user_func($operation);
            } catch (MaxApiException $e) {
                $attempt++;
                if ($attempt > $this->maxRetries || !$this->isRetryableApi($e)) {
                    throw $e;
                }
                $this->sleep($this->calculateDelay($attempt));
            } catch (MaxConnectionException $e) {
                $attempt++;
                if ($attempt > $this->maxRetries) {
                    throw $e;
                }
                $this->sleep($this->calculateDelay($attempt));
            }
        }
    }

    /**
     * Является ли API-ошибка transient (можно повторить).
     *
     * @param MaxApiException $e
     * @return bool
     */
    private function isRetryableApi(MaxApiException $e)
    {
        $statusCode = $e->getStatusCode();
        return $statusCode === 429 || $statusCode >= 500;
    }

    /**
     * Рассчитать задержку с exponential backoff + jitter.
     *
     * @param int $attempt Номер попытки (1-based).
     * @return int Задержка в миллисекундах.
     */
    private function calculateDelay($attempt)
    {
        // Exponential backoff: base * 2^(attempt-1)
        $delay = $this->baseDelayMs * (int) pow(2, $attempt - 1);

        // Cap at max delay
        $delay = min($delay, $this->maxDelayMs);

        // Add jitter: ±10%
        $jitter = (int) ($delay * 0.1);
        $delay = $delay + mt_rand(-$jitter, $jitter);

        return max(0, $delay);
    }

    /**
     * Пауза на заданное количество миллисекунд.
     *
     * @param int $milliseconds
     * @return void
     */
    protected function sleep($milliseconds)
    {
        usleep($milliseconds * 1000);
    }
}
