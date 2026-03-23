<?php

declare(strict_types=1);

namespace MaxBotSdk\Exception;

/**
 * Исключение для ошибок MAX Bot API (HTTP 4xx/5xx).
 *
 * @since 1.0.0
 */
final class MaxApiException extends MaxException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 0,
        private readonly ?string $description = null,
        private readonly ?string $errorCode = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }
}
