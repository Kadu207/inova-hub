<?php

namespace App\Jobs;

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
     * @param  array{eventId: string, event: string, itemId: string, clientUserId?: ?string, createdTransactionsLink?: ?string, accountId?: ?string}  $payload
     */
    public function __construct(
        public array $payload,
    ) {}

    public function handle(SyncsPluggyItem $sync): void
    {
        $eventId = $this->payload['eventId'];

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

        try {
            $sync->handle($this->payload);

            $event->update([
                'status' => WebhookEvent::STATUS_PROCESSED,
                'processed_at' => now(),
                'last_error' => null,
            ]);

            Log::info('pluggy.item_synced', [
                'event' => $this->payload['event'],
                'item_id' => $this->payload['itemId'],
            ]);
        } catch (Throwable $e) {
            $event->update([
                'status' => WebhookEvent::STATUS_FAILED,
                'last_error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
