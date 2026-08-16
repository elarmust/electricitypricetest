<?php

declare(strict_types=1);

namespace App\Domain;

final class Price {
    public function __construct(
        public readonly int $timestampUtc,
        public readonly float $priceEurPerMwh,
    ) {
    }

    public function getPriceEurPerKwh(): float {
        return $this->priceEurPerMwh / 1000.0;
    }
}
