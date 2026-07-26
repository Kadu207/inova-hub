<?php

namespace App\Jobs;

use App\Services\OpenFinance\CategorizesOfTransactions;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

final class CategorizeOfTransactions implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ?string $organizationId = null,
    ) {}

    public function handle(CategorizesOfTransactions $categorizer): void
    {
        if ($this->organizationId !== null) {
            TenantContext::set($this->organizationId);
        }

        try {
            $count = $categorizer->handle($this->organizationId);
            Log::info('of.transactions_categorized', [
                'organization_id' => $this->organizationId,
                'updated' => $count,
            ]);
        } finally {
            if ($this->organizationId !== null) {
                TenantContext::clear();
            }
        }
    }
}
