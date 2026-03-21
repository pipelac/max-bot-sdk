<?php

namespace App\Component\Max\Exception;

/**
 * Исключение для ошибок MAX Bot API (HTTP 4xx/5xx).
 *
 * Содержит HTTP-код, описание ошибки и код ошибки из ответа API.
 *
 * @since 1.0.0
 */
final class MaxApiException extends MaxException
{
    /** @var int HTTP статус код ответа */
    private $statusCode;

    /** @var string|null Описание ошибки из ответа API */
    private $description;

    /** @var string|null Код ошибки из ответа API */
    private $errorCode;

    /**
     * @param string          $message     Сообщение об ошибке.
     * @param int             $statusCode  HTTP статус код.
     * @param string|null     $description Описание ошибки от API.
     * @param string|null     $errorCode   Код ошибки от API.
     * @param \Exception|null $previous    Предыдущее исключение.
     */
    public function __construct($message, $statusCode = 0, $description = null, $errorCode = null, $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
        $this->statusCode = $statusCode;
        $this->description = $description;
        $this->errorCode = $errorCode;
    }

    /** @return int */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /** @return string|null */
    public function getDescription()
    {
        return $this->description;
    }

    /** @return string|null */
    public function getErrorCode()
    {
        return $this->errorCode;
    }
}
