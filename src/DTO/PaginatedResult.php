<?php

declare(strict_types=1);

namespace MaxBotSdk\DTO;

use ArrayIterator;
use Countable;
use IteratorAggregate;

/**
 * Пагинированный результат API.
 *
 * @template T of AbstractDto
 * @implements IteratorAggregate<int, T>
 *
 * @since 1.0.0
 */
final class PaginatedResult extends AbstractDto implements Countable, IteratorAggregate
{
    /**
     * @param list<T>  $items
     * @param int|null $marker
     */
    private function __construct(
        private readonly array $items,
        private readonly ?int $marker,
    ) {}

    public static function fromArray(array $data): static
    {
        $marker = isset($data['marker']) && \is_scalar($data['marker']) ? (int) $data['marker'] : null;
        return new self([], $marker);
    }

    /**
     * Создаёт PaginatedResult из ответа API с маппингом элементов.
     *
     * @template U of AbstractDto
     * @param array<string, mixed> $data     Ответ API.
     * @param string               $itemsKey Ключ массива с элементами.
     * @param class-string<U>      $itemClass Класс DTO для маппинга.
     * @return self<U>
     */
    public static function fromApiResponse(array $data, string $itemsKey, string $itemClass): self
    {
        $rawItems = isset($data[$itemsKey]) && \is_array($data[$itemsKey])
            ? $data[$itemsKey]
            : [];

        $items = [];
        foreach ($rawItems as $raw) {
            if (\is_array($raw)) {
                $items[] = $itemClass::fromArray($raw);
            }
        }

        $marker = isset($data['marker']) && \is_scalar($data['marker']) ? (int) $data['marker'] : null;

        return new self($items, $marker);
    }

    /**
     * @return list<T>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getMarker(): ?int
    {
        return $this->marker;
    }

    public function hasMore(): bool
    {
        return $this->marker !== null;
    }

    public function count(): int
    {
        return \count($this->items);
    }

    /**
     * @return ArrayIterator<int, T>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    public function toArray(): array
    {
        return [
            'items'  => array_map(
                static fn(AbstractDto $item): array => $item->toArray(),
                $this->items,
            ),
            'marker' => $this->marker,
        ];
    }
}
