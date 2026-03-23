<?php

declare(strict_types=1);

namespace MaxBotSdk\DTO;

use ArrayIterator;
use Countable;
use IteratorAggregate;

/**
 * Результат Long Polling (обновления + маркер).
 *
 * @implements IteratorAggregate<int, Update>
 *
 * @since 1.0.0
 */
final class UpdatesResult extends AbstractDto implements Countable, IteratorAggregate
{
    /**
     * @param list<Update> $updates
     */
    private function __construct(
        private readonly array $updates,
        private readonly ?int $marker,
    ) {
    }

    public static function fromArray(array $data): static
    {
        $updates = [];
        $rawUpdates = isset($data['updates']) && \is_array($data['updates'])
            ? $data['updates']
            : [];

        foreach ($rawUpdates as $raw) {
            if (\is_array($raw)) {
                $updates[] = Update::fromArray($raw);
            }
        }

        $marker = isset($data['marker']) && \is_scalar($data['marker']) ? (int) $data['marker'] : null;

        return new self($updates, $marker);
    }

    /**
     * @return list<Update>
     */
    public function getUpdates(): array
    {
        return $this->updates;
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
        return \count($this->updates);
    }

    /**
     * @return ArrayIterator<int, Update>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->updates);
    }

    public function toArray(): array
    {
        return [
            'updates' => \array_map(
                static fn(Update $u): array => $u->toArray(),
                $this->updates,
            ),
            'marker'  => $this->marker,
        ];
    }
}
