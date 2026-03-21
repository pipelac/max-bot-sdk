<?php

namespace MaxBotSdk\DTO;

/**
 * Объект вложения (attachment) сообщения.
 *
 * @since 1.0.0
 */
final class Attachment extends AbstractDto
{
    /** @var string */
    private $type;

    /** @var array */
    private $payload;

    /**
     * @param array $data
     */
    private function __construct(array $data)
    {
        $this->type = self::getString($data, 'type');
        $this->payload = self::getArray($data, 'payload');
    }

    /**
     * {@inheritdoc}
     */
    public static function fromArray(array $data)
    {
        return new self($data);
    }

    /** @return string */
    public function getType()
    {
        return $this->type;
    }

    /** @return array */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * Получить значение из payload по ключу.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function getPayloadValue($key, $default = null)
    {
        return isset($this->payload[$key]) ? $this->payload[$key] : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return [
            'type'    => $this->type,
            'payload' => $this->payload,
        ];
    }
}
