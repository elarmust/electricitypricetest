<?php

declare(strict_types=1);

namespace App\Domain;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Immutable, ordered collection of Price points for a single day.
 *
 * @implements IteratorAggregate<int, Price>
 */
final class PriceCollection implements Countable, IteratorAggregate {
    /** @var list<Price> */
    private readonly array $prices;

    /**
     * @param  list<Price>  $prices
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

    /**
     * Return a new collection whose prices carry every value the UI/email needs:
     * the raw spot (real base) plus the billing base (spot + fee + margin) and
     * that base with VAT applied. The billing base is the canonical price shown
     * everywhere; VAT is already folded into adjustedWithVatEurPerMwh.
     */
    public function enrichBilling(float $networkFeeEurMwh, float $marginEurMwh, float $vatMultiplier): self {
        return new self(array_map(
            static fn (Price $p): Price => new Price(
                $p->timestampUtc,
                $p->realBaseEurPerMwh,
                $p->realBaseEurPerMwh + $networkFeeEurMwh + $marginEurMwh,
                ($p->realBaseEurPerMwh + $networkFeeEurMwh + $marginEurMwh) * $vatMultiplier,
            ),
            $this->prices,
        ));
    }

    public function first(): ?Price {
        return $this->prices[0] ?? null;
    }

    /**
     * @return list<array{timestamp:int, realBase:float, adjustedBase:float, adjustedWithVat:float}>
     */
    public function toArray(): array {
        return array_map(
            static fn (Price $p): array => [
                'timestamp' => $p->timestampUtc,
                'realBase' => $p->realBaseEurPerMwh,
                'adjustedBase' => $p->adjustedBaseEurPerMwh,
                'adjustedWithVat' => $p->adjustedWithVatEurPerMwh,
            ],
            $this->prices,
        );
    }

    /**
     * @param  list<array{timestamp:int, realBase:float}>  $data
     */
    public static function fromArray(array $data): self {
        return new self(array_map(
            static fn (array $p): Price => Price::fromSpot((int) $p['timestamp'], (float) $p['realBase']),
            $data,
        ));
    }
}
