<?php

declare(strict_types=1);

namespace App\Domain;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Immutable, ordered collection of Price points for a single day.
 */
final class PriceCollection implements Countable, IteratorAggregate {
    /** @var list<Price> */
    private readonly array $prices;

    /**
     * @param list<Price> $prices
     */
    public function __construct(array $prices) {
        $this->prices = $prices;
    }

    public function count(): int {
        return count($this->prices);
    }

    /**
     * @return Traversable<int, Price>
     */
    public function getIterator(): Traversable {
        yield from $this->prices;
    }

    /**
     * @return list<Price>
     */
    public function all(): array {
        return $this->prices;
    }

    public function first(): ?Price {
        return $this->prices[0] ?? null;
    }

    /**
     * @return list<array{timestamp:int, price:float}>
     */
    public function toArray(): array {
        return array_map(
            static fn (Price $p): array => [
                'timestamp' => $p->timestampUtc,
                'price' => $p->priceEurPerMwh,
            ],
            $this->prices,
        );
    }

    /**
     * @param list<array{timestamp:int, price:float}> $data
     */
    public static function fromArray(array $data): self {
        return new self(array_map(
            static fn (array $p): Price => new Price((int) $p['timestamp'], (float) $p['price']),
            $data,
        ));
    }
}
