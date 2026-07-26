<?php

namespace App\Services\OpenFinance;

use App\Contracts\OpenFinance\OpenFinanceProvider;
use App\Models\OfAccount;
use App\Models\OfItem;
use App\Models\OfTransaction;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Log;
use Throwable;

final class RevokesPluggyItem
{
    public function __construct(
        private OpenFinanceProvider $provider,
    ) {}

    public function handle(OfItem $item): OfItem
    {
        $orgId = (string) $item->organization_id;
        TenantContext::set($orgId);

        try {
            try {
                $this->provider->deleteItem($item->pluggy_item_id);
            } catch (Throwable $e) {
                // Ainda removemos localmente (LGPD) se a Pluggy já não tiver o item.
                Log::notice('pluggy.delete_item_skipped', [
                    'item_id' => $item->pluggy_item_id,
                    'reason' => $e->getMessage(),
                ]);
            }

            $accountIds = OfAccount::query()
                ->where('of_item_id', $item->id)
                ->pluck('id');

            if ($accountIds->isNotEmpty()) {
                OfTransaction::query()
                    ->whereIn('of_account_id', $accountIds)
                    ->delete();
                OfAccount::query()
                    ->where('of_item_id', $item->id)
                    ->delete();
            }

            $item->update([
                'status' => OfItem::STATUS_DELETED,
                'consent_revoked_at' => now(),
            ]);

            Log::info('pluggy.item_revoked', [
                'organization_id' => $orgId,
                'pluggy_item_id' => $item->pluggy_item_id,
            ]);

            return $item->fresh();
        } finally {
            TenantContext::clear();
        }
    }
}
