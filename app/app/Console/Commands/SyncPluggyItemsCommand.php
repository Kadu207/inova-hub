<?php

namespace App\Console\Commands;

use App\Models\OfItem;
use App\Services\OpenFinance\SyncsPluggyItem;
use Illuminate\Console\Command;
use Throwable;

final class SyncPluggyItemsCommand extends Command
{
    protected $signature = 'pluggy:sync-items {--limit=50 : Max items to sync}';

    protected $description = 'On-demand/scheduled sync of Pluggy OF items (D25)';

    public function handle(SyncsPluggyItem $sync): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $items = OfItem::query()
            ->withoutGlobalScopes()
            ->where('status', '!=', OfItem::STATUS_DELETED)
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();

        $ok = 0;
        $fail = 0;

        foreach ($items as $item) {
            try {
                $sync->handle([
                    'itemId' => $item->pluggy_item_id,
                    'event' => 'item/updated',
                    'clientUserId' => $item->client_user_id,
                ]);
                $ok++;
                $this->line('OK '.$item->pluggy_item_id);
            } catch (Throwable $e) {
                $fail++;
                $this->error('FAIL '.$item->pluggy_item_id.': '.$e->getMessage());
            }
        }

        $this->info(sprintf('Synced %d ok / %d fail (of %d)', $ok, $fail, $items->count()));

        return $fail > 0 ? self::FAILURE : self::SUCCESS;
    }
}
