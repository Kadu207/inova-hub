<?php

namespace App\Jobs;

use App\Models\WebhookEvent;
use App\Services\WhatsApp\ConsumesWhatsappOtp;
use App\Services\WhatsApp\ParsesWhatsappWebhook;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

final class ProcessWhatsAppMessage implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array{wamid: string, from: string, text: string, type: string}  $message
     */
    public function __construct(
        public array $message,
    ) {}

    public function handle(ParsesWhatsappWebhook $parser, ConsumesWhatsappOtp $consumes): void
    {
        $wamid = $this->message['wamid'];
        $from = $this->message['from'];
        $text = $this->message['text'];

        $event = WebhookEvent::query()
            ->where('source', WebhookEvent::SOURCE_WHATSAPP)
            ->where('external_id', $wamid)
            ->first();

        if ($event === null) {
            return;
        }

        if ($event->status === WebhookEvent::STATUS_PROCESSED) {
            return;
        }

        try {
            $otp = $parser->extractOtpCode($text);

            if ($otp !== null) {
                $identity = $consumes->handle($from, $otp);
                Log::info('whatsapp.otp_linked', [
                    'wamid' => $wamid,
                    'user_id' => $identity->user_id,
                    'phone_masked' => $this->maskPhone($identity->phone_e164),
                ]);
            } else {
                Log::info('whatsapp.message_received', [
                    'wamid' => $wamid,
                    'type' => $this->message['type'],
                    'phone_masked' => $this->maskPhone($from),
                    'has_text' => $text !== '',
                ]);
            }

            $event->update([
                'status' => WebhookEvent::STATUS_PROCESSED,
                'processed_at' => now(),
                'last_error' => null,
            ]);
        } catch (ValidationException $e) {
            $event->update([
                'status' => WebhookEvent::STATUS_FAILED,
                'last_error' => collect($e->errors())->flatten()->first() ?? 'validation',
            ]);
            Log::notice('whatsapp.otp_failed', [
                'wamid' => $wamid,
                'phone_masked' => $this->maskPhone($from),
            ]);
        } catch (Throwable $e) {
            $event->update([
                'status' => WebhookEvent::STATUS_FAILED,
                'last_error' => $e->getMessage(),
            ]);

            throw $e;
        }
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
