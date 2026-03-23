<?php

declare(strict_types=1);

namespace MaxBotSdk\Http;

use MaxBotSdk\Exception\MaxApiException;
use MaxBotSdk\Exception\MaxConnectionException;

/**
 * Retry-обработчик с экспоненциальной задержкой и jitter.
 *
 * @since 1.0.0
 */
class RetryHandler
{
    private readonly int $maxRetries;
    private readonly int $baseDelayMs;
    private readonly int $maxDelayMs;

    public function __construct(
        int $maxRetries = 3,
        int $baseDelayMs = 1000,
        int $maxDelayMs = 30000,
    ) {
        $this->maxRetries = max(0, $maxRetries);
        $this->baseDelayMs = max(100, $baseDelayMs);
        $this->maxDelayMs = max($this->baseDelayMs, $maxDelayMs);
    }

    /**
     * Выполнить операцию с retry-логикой.
     *
     * @template T
     * @param callable(): T $operation
     * @return T
     * @throws MaxApiException
     * @throws MaxConnectionException
     */
    public function execute(callable $operation): mixed
    {
        $attempt = 0;

        while (true) {
            try {
                return $operation();
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

    private function isRetryableApi(MaxApiException $e): bool
    {
        $statusCode = $e->getStatusCode();
        return $statusCode === 429 || $statusCode >= 500;
    }

    private function calculateDelay(int $attempt): int
    {
        $delay = $this->baseDelayMs * (int) pow(2, $attempt - 1);
        $delay = min($delay, $this->maxDelayMs);

        // Jitter: ±10%
        $jitter = (int) ($delay * 0.1);
        $delay += random_int(-$jitter, $jitter);

        return max(0, $delay);
    }

    protected function sleep(int $milliseconds): void
    {
        usleep($milliseconds * 1000);
    }
}
