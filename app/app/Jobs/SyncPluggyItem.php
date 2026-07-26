<?php

namespace App\Jobs;

use App\Jobs\CategorizeOfTransactions;
use App\Models\OfItem;
use App\Models\WebhookEvent;
use App\Services\OpenFinance\SyncsPluggyItem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SyncPluggyItem implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array{eventId?: ?string, event: string, itemId: string, clientUserId?: ?string, createdTransactionsLink?: ?string, accountId?: ?string}  $payload
     */
    public function __construct(
        public array $payload,
    ) {}

    public function handle(SyncsPluggyItem $sync): void
    {
        $eventId = $this->payload['eventId'] ?? null;
        $event = null;

        if (is_string($eventId) && $eventId !== '') {
            $event = WebhookEvent::query()
                ->where('source', WebhookEvent::SOURCE_PLUGGY)
                ->where('external_id', $eventId)
                ->first();

            if ($event === null) {
                return;
            }

            if ($event->status === WebhookEvent::STATUS_PROCESSED) {
                return;
            }
        }

        try {
            $item = $sync->handle($this->payload);

            $event?->update([
                'status' => WebhookEvent::STATUS_PROCESSED,
                'processed_at' => now(),
                'last_error' => null,
            ]);

            if ($item instanceof OfItem
                && $item->status !== OfItem::STATUS_DELETED
                && filled($item->organization_id)) {
                CategorizeOfTransactions::dispatch((string) $item->organization_id);
            }

            Log::info('pluggy.item_synced', [
                'event' => $this->payload['event'],
                'item_id' => $this->payload['itemId'],
            ]);
        } catch (Throwable $e) {
            $event?->update([
                'status' => WebhookEvent::STATUS_FAILED,
                'last_error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
