<?php

namespace MaxBotSdk\DTO;

/**
 * Результат Long Polling (обновления + маркер).
 *
 * @since 1.0.0
 */
final class UpdatesResult extends AbstractDto implements \Countable, \IteratorAggregate
{
    /** @var Update[] */
    private $updates;

    /** @var int|null */
    private $marker;

    /**
     * @param Update[] $updates
     * @param int|null $marker
     */
    private function __construct(array $updates, $marker = null)
    {
        $this->updates = $updates;
        $this->marker = $marker !== null ? (int) $marker : null;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromArray(array $data)
    {
        $updates = [];
        $rawUpdates = isset($data['updates']) && is_array($data['updates'])
            ? $data['updates']
            : [];
        foreach ($rawUpdates as $raw) {
            if (is_array($raw)) {
                $updates[] = Update::fromArray($raw);
            }
        }

        $marker = isset($data['marker']) ? $data['marker'] : null;

        return new self($updates, $marker);
    }

    /**
     * @return Update[]
     */
    public function getUpdates()
    {
        return $this->updates;
    }

    /**
     * @return int|null
     */
    public function getMarker()
    {
        return $this->marker;
    }

    /**
     * @return bool Есть ли ещё обновления.
     */
    public function hasMore()
    {
        return $this->marker !== null;
    }

    /**
     * @return int Количество обновлений.
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->updates);
    }

    /**
     * Итератор для foreach.
     *
     * @return \ArrayIterator
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new \ArrayIterator($this->updates);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $updates = [];
        foreach ($this->updates as $update) {
            $updates[] = $update->toArray();
        }

        return [
            'updates' => $updates,
            'marker'  => $this->marker,
        ];
    }
}
