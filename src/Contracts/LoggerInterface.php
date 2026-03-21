<?php

namespace App\Component\Max\Contracts;

/**
 * Интерфейс логгера (PSR-3 совместимый для PHP 5.6).
 *
 * Позволяет инжектировать любой логгер, включая App\Component\Logger.
 *
 * @since 1.0.0
 */
interface LoggerInterface
{
    /**
     * @param string $message
     * @param array  $context
     * @return void
     */
    public function debug($message, array $context = array());

    /**
     * @param string $message
     * @param array  $context
     * @return void
     */
    public function info($message, array $context = array());

    /**
     * @param string $message
     * @param array  $context
     * @return void
     */
    public function warning($message, array $context = array());

    /**
     * @param string $message
     * @param array  $context
     * @return void
     */
    public function error($message, array $context = array());
}
