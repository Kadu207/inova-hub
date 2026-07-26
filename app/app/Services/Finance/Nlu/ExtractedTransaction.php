<?php

namespace App\Services\Finance\Nlu;

final readonly class ExtractedTransaction
{
    public function __construct(
        public string $type,
        public int $amountCents,
        public string $categorySlug,
        public float $confidence,
        public ?string $description = null,
        public ?string $occurredOn = null,
    ) {}

    public function needsConfirmation(float $threshold): bool
    {
        return $this->confidence < $threshold;
    }
}
