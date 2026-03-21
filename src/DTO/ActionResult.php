<?php

namespace App\Component\Max\DTO;

/**
 * Результат void-операций API (delete, action, pin/unpin и т.д.).
 *
 * @since 1.0.0
 */
final class ActionResult extends AbstractDto
{
    /** @var bool */
    private $success;

    /** @var string|null */
    private $message;

    /**
     * @param array $data
     */
    private function __construct(array $data)
    {
        $this->success = self::getBool($data, 'success', true);
        $this->message = self::getStringOrNull($data, 'message');
    }

    /**
     * {@inheritdoc}
     */
    public static function fromArray(array $data)
    {
        return new self($data);
    }

    /**
     * Создать успешный результат.
     *
     * @return self
     */
    public static function success()
    {
        return new self(array('success' => true));
    }

    /** @return bool */
    public function isSuccess()
    {
        return $this->success;
    }

    /** @return string|null */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return array(
            'success' => $this->success,
            'message' => $this->message,
        );
    }
}
