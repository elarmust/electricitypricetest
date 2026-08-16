<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * A consecutive window of price periods with its average price.
 */
final class Window {
    public function __construct(
        public readonly int $startIndex,
        public readonly int $length,
        public readonly float $averagePriceEurPerMwh,
    ) {
    }

    public function getEndIndex(): int {
        return $this->startIndex + $this->length - 1;
    }
}
