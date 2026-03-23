<?php

declare(strict_types=1);

namespace MaxBotSdk\Http;

/**
 * Rate Limiter — скользящее окно для ограничения запросов.
 *
 * @since 1.0.0
 */
final class RateLimiter
{
    /** @var list<float> Timestamps отправленных запросов. */
    private array $timestamps = [];

    public function __construct(
        private readonly int $maxRequestsPerSecond,
    ) {}

    public function throttle(): void
    {
        $now = microtime(true);
        $this->cleanup($now);

        if (\count($this->timestamps) >= $this->maxRequestsPerSecond) {
            $oldest = reset($this->timestamps);
            if ($oldest !== false) {
                $waitUntil = $oldest + 1.0;
                $delay = $waitUntil - $now;
                if ($delay > 0) {
                    $this->sleep($delay);
                }
            }
            $this->cleanup(microtime(true));
        }

        $this->timestamps[] = microtime(true);
    }

    public function getMaxRequestsPerSecond(): int
    {
        return $this->maxRequestsPerSecond;
    }

    private function cleanup(float $now): void
    {
        $cutoff = $now - 1.0;
        $this->timestamps = array_values(
            array_filter(
                $this->timestamps,
                static fn(float $ts): bool => $ts > $cutoff,
            ),
        );
    }

    protected function sleep(float $seconds): void
    {
        usleep((int) ($seconds * 1_000_000));
    }
}
