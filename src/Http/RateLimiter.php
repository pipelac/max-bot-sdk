<?php

namespace MaxBotSdk\Http;

/**
 * Rate Limiter — ограничение скорости запросов к MAX Bot API.
 *
 * Реализует алгоритм скользящего окна (sliding window) для ограничения
 * количества HTTP-запросов в секунду. Вызывается перед каждым запросом.
 *
 * @since 1.0.0
 */
class RateLimiter
{
    /** @var int Максимальное количество запросов в секунду. */
    private $maxRequestsPerSecond;

    /** @var float[] Timestamps отправленных запросов. */
    private $timestamps = [];

    /**
     * @param int $maxRequestsPerSecond Максимум запросов в секунду.
     */
    public function __construct($maxRequestsPerSecond)
    {
        $this->maxRequestsPerSecond = max(1, (int) $maxRequestsPerSecond);
    }

    /**
     * Выполнить throttling перед запросом.
     *
     * Если лимит запросов исчерпан, метод блокирует выполнение (usleep)
     * до момента, когда окно освободится.
     *
     * @return void
     */
    public function throttle()
    {
        $now = microtime(true);

        // Удаляем timestamps старше 1 секунды
        $this->cleanup($now);

        // Если лимит исчерпан — ждём
        if (count($this->timestamps) >= $this->maxRequestsPerSecond) {
            $oldest = reset($this->timestamps);
            $waitUntil = $oldest + 1.0;
            $delay = $waitUntil - $now;
            if ($delay > 0) {
                $this->sleep($delay);
            }
            // Очищаем после ожидания
            $this->cleanup(microtime(true));
        }

        // Регистрируем текущий запрос
        $this->timestamps[] = microtime(true);
    }

    /**
     * Получить текущий лимит.
     *
     * @return int
     */
    public function getMaxRequestsPerSecond()
    {
        return $this->maxRequestsPerSecond;
    }

    /**
     * Удаляет устаревшие timestamps (старше 1 секунды).
     *
     * @param float $now Текущее время.
     * @return void
     */
    private function cleanup($now)
    {
        $cutoff = $now - 1.0;
        $this->timestamps = array_values(
            array_filter($this->timestamps, function ($ts) use ($cutoff) {
                return $ts > $cutoff;
            })
        );
    }

    /**
     * Блокирующий sleep. Вынесен в отдельный метод для тестируемости.
     *
     * @param float $seconds Время ожидания в секундах.
     * @return void
     */
    protected function sleep($seconds)
    {
        usleep((int) ($seconds * 1000000));
    }
}
