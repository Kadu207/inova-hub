<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWhatsAppMessage;
use App\Models\WebhookEvent;
use App\Services\WhatsApp\ParsesWhatsappWebhook;
use App\Services\WhatsApp\VerifiesMetaSignature;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

final class WhatsappWebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        // PHP converts hub.mode → hub_mode in query strings
        $mode = (string) ($request->query('hub_mode') ?? $request->query('hub.mode') ?? '');
        $token = (string) ($request->query('hub_verify_token') ?? $request->query('hub.verify_token') ?? '');
        $challenge = (string) ($request->query('hub_challenge') ?? $request->query('hub.challenge') ?? '');
        $expected = (string) config('services.whatsapp.verify_token');

        if ($mode === 'subscribe' && $expected !== '' && hash_equals($expected, $token)) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    public function receive(
        Request $request,
        VerifiesMetaSignature $verifier,
        ParsesWhatsappWebhook $parser,
    ): Response {
        $verifier->assertValid($request);

        $messages = $parser->extractInboundMessages($request->all());

        foreach ($messages as $message) {
            try {
                WebhookEvent::query()->create([
                    'source' => WebhookEvent::SOURCE_WHATSAPP,
                    'external_id' => $message['wamid'],
                    'status' => WebhookEvent::STATUS_RECEIVED,
                    'payload_meta' => [
                        'type' => $message['type'],
                        'from_masked' => $this->maskPhone($message['from']),
                        'text_len' => strlen($message['text']),
                    ],
                ]);
            } catch (UniqueConstraintViolationException) {
                Log::info('whatsapp.duplicate_wamid', ['wamid' => $message['wamid']]);

                continue;
            }

            ProcessWhatsAppMessage::dispatch($message);
        }

        return response('EVENT_RECEIVED', 200);
    }

    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) < 4) {
            return '***';
        }

        return str_repeat('*', max(strlen($digits) - 4, 0)).substr($digits, -4);
    }
}
