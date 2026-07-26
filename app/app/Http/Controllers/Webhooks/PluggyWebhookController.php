<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\SyncPluggyItem;
use App\Models\WebhookEvent;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

final class PluggyWebhookController extends Controller
{
    private const SYNC_EVENTS = [
        'item/created',
        'item/updated',
        'item/deleted',
        'item/login_succeeded',
        'transactions/created',
        'transactions/updated',
    ];

    public function receive(Request $request): Response
    {
        $expected = (string) config('services.pluggy.webhook_secret');
        if ($expected !== '') {
            $provided = (string) $request->header('X-Webhook-Secret', '');
            if ($provided === '' || ! hash_equals($expected, $provided)) {
                return response('Unauthorized', 401);
            }
        }

        $event = (string) $request->input('event', '');
        $eventId = (string) $request->input('eventId', '');
        $itemId = (string) $request->input('itemId', '');

        if ($eventId === '') {
            return response('Bad Request', 400);
        }

        if ($event === 'connector/status_updated' || $itemId === '') {
            return response('EVENT_RECEIVED', 200);
        }

        if (! in_array($event, self::SYNC_EVENTS, true)) {
            return response('EVENT_RECEIVED', 200);
        }

        try {
            WebhookEvent::query()->create([
                'source' => WebhookEvent::SOURCE_PLUGGY,
                'external_id' => $eventId,
                'status' => WebhookEvent::STATUS_RECEIVED,
                'payload_meta' => [
                    'event' => $event,
                    'item_id' => $itemId,
                    'has_client_user' => filled($request->input('clientUserId')),
                ],
            ]);
        } catch (UniqueConstraintViolationException) {
            Log::info('pluggy.duplicate_event', ['event_id' => $eventId]);

            return response('EVENT_RECEIVED', 200);
        }

        SyncPluggyItem::dispatch([
            'eventId' => $eventId,
            'event' => $event,
            'itemId' => $itemId,
            'clientUserId' => $request->input('clientUserId'),
            'createdTransactionsLink' => $request->input('createdTransactionsLink'),
            'accountId' => $request->input('accountId'),
        ]);

        return response('EVENT_RECEIVED', 200);
    }
}
