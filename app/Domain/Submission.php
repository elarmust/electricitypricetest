<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Submission payload from the "Send result" form.
 */
final class Submission {
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $phone,
    ) {
    }
}
