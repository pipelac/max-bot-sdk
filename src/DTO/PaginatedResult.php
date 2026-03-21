<?php

namespace MaxBotSdk\DTO;

/**
 * Результат с пагинацией (для чатов, участников, сообщений и т.д.).
 *
 * Реализует Countable и IteratorAggregate для интеграции с PHP stdlib.
 *
 * @since 1.0.0
 */
final class PaginatedResult extends AbstractDto implements \Countable, \IteratorAggregate
{
    /** @var array Элементы текущей страницы */
    private $items;

    /** @var int|null Маркер для следующей страницы */
    private $marker;

    /**
     * @param array    $items
     * @param int|null $marker
     */
    private function __construct(array $items, $marker = null)
    {
        $this->items = $items;
        $this->marker = $marker !== null ? (int) $marker : null;
    }

    /**
     * {@inheritdoc}
     *
     * Создаёт PaginatedResult из массива с ключом 'items'.
     *
     * @param array $data Полный ответ API.
     * @return self
     */
    public static function fromArray(array $data)
    {
        return self::fromApiResponse($data, 'items');
    }

    /**
     * Создать PaginatedResult из ответа API с произвольным ключом и DTO-маппингом.
     *
     * @param array       $data      Полный ответ API.
     * @param string      $itemsKey  Ключ массива элементов в ответе.
     * @param string|null $itemClass FQCN для маппинга элементов.
     * @return self
     */
    public static function fromApiResponse(array $data, $itemsKey = 'items', $itemClass = null)
    {
        $rawItems = isset($data[$itemsKey]) && is_array($data[$itemsKey]) ? $data[$itemsKey] : [];
        $marker = isset($data['marker']) ? $data['marker'] : null;

        $items = [];
        if ($itemClass !== null && method_exists($itemClass, 'fromArray')) {
            foreach ($rawItems as $raw) {
                if (is_array($raw)) {
                    $items[] = call_user_func([$itemClass, 'fromArray'], $raw);
                }
            }
        } else {
            $items = $rawItems;
        }

        return new self($items, $marker);
    }

    /**
     * @return array Элементы текущей страницы.
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @return int|null Маркер для следующей страницы.
     */
    public function getMarker()
    {
        return $this->marker;
    }

    /**
     * @return bool Есть ли ещё страницы.
     */
    public function hasMore()
    {
        return $this->marker !== null;
    }

    /**
     * Количество элементов на текущей странице.
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->items);
    }

    /**
     * Итератор для foreach.
     *
     * @return \ArrayIterator
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $result = [];
        foreach ($this->items as $item) {
            if (is_object($item) && method_exists($item, 'toArray')) {
                $result[] = $item->toArray();
            } else {
                $result[] = $item;
            }
        }

        return [
            'items'  => $result,
            'marker' => $this->marker,
        ];
    }
}
