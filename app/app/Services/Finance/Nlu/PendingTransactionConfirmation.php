<?php

namespace App\Services\Finance\Nlu;

use Illuminate\Support\Facades\Cache;

final class PendingTransactionConfirmation
{
    private const TTL_SECONDS = 600;

    public function put(string $phoneDigits, ExtractedTransaction $extracted): void
    {
        Cache::put($this->key($phoneDigits), [
            'type' => $extracted->type,
            'amount_cents' => $extracted->amountCents,
            'category_slug' => $extracted->categorySlug,
            'confidence' => $extracted->confidence,
            'description' => $extracted->description,
            'occurred_on' => $extracted->occurredOn,
        ], self::TTL_SECONDS);
    }

    public function pull(string $phoneDigits): ?ExtractedTransaction
    {
        $payload = Cache::pull($this->key($phoneDigits));

        if (! is_array($payload)) {
            return null;
        }

        return new ExtractedTransaction(
            type: (string) $payload['type'],
            amountCents: (int) $payload['amount_cents'],
            categorySlug: (string) $payload['category_slug'],
            confidence: (float) $payload['confidence'],
            description: $payload['description'] ?? null,
            occurredOn: $payload['occurred_on'] ?? null,
        );
    }

    public function forget(string $phoneDigits): void
    {
        Cache::forget($this->key($phoneDigits));
    }

    private function key(string $phoneDigits): string
    {
        $digits = preg_replace('/\D+/', '', $phoneDigits) ?? '';

        return 'finova:pending_tx:'.$digits;
    }
}
