<?php

namespace App\Services\OpenFinance;

use App\Models\ConsentLog;
use App\Models\OfItem;
use App\Models\User;

final class RecordsOpenFinanceConsent
{
    public function currentVersion(): string
    {
        return (string) config('open_finance.consent_version', 'of-1.0');
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function record(string $organizationId, ?User $user, OfItem $item, array $meta = []): ConsentLog
    {
        $version = $this->currentVersion();

        $item->update([
            'consent_at' => $item->consent_at ?? now(),
            'consent_version' => $version,
        ]);

        return ConsentLog::query()->create([
            'organization_id' => $organizationId,
            'user_id' => $user?->id,
            'type' => ConsentLog::TYPE_OPEN_FINANCE,
            'version' => $version,
            'accepted_at' => now(),
            'meta' => array_merge([
                'of_item_id' => $item->id,
                'pluggy_item_id' => $item->pluggy_item_id,
                'connector_name' => $item->connector_name,
            ], $meta),
        ]);
    }
}
