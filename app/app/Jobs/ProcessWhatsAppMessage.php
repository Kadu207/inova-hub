<?php

namespace App\Jobs;

use App\Models\WebhookEvent;
use App\Services\WhatsApp\ConsumesWhatsappOtp;
use App\Services\WhatsApp\FinovaCopy;
use App\Services\WhatsApp\ParsesWhatsappWebhook;
use App\Services\WhatsApp\ResolvesFinovaIntent;
use App\Services\WhatsApp\SendsWhatsappText;
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

    public function handle(
        ParsesWhatsappWebhook $parser,
        ConsumesWhatsappOtp $consumes,
        ResolvesFinovaIntent $intents,
        SendsWhatsappText $sender,
    ): void {
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
            $looksLikePureOtp = preg_match('/^\s*\d{6}\s*$/', $text) === 1;

            if ($otp !== null && ($looksLikePureOtp || str_contains(mb_strtolower($text), 'codigo') || str_contains(mb_strtolower($text), 'código'))) {
                try {
                    $identity = $consumes->handle($from, $otp);
                    Log::info('whatsapp.otp_linked', [
                        'wamid' => $wamid,
                        'user_id' => $identity->user_id,
                        'phone_masked' => $this->maskPhone($identity->phone_e164),
                    ]);
                    $sender->handle($from, FinovaCopy::otpLinked());
                } catch (ValidationException $e) {
                    if ($looksLikePureOtp) {
                        $sender->handle($from, FinovaCopy::otpFailed());
                        $event->update([
                            'status' => WebhookEvent::STATUS_FAILED,
                            'last_error' => collect($e->errors())->flatten()->first() ?? 'validation',
                        ]);
                        Log::notice('whatsapp.otp_failed', [
                            'wamid' => $wamid,
                            'phone_masked' => $this->maskPhone($from),
                        ]);

                        return;
                    }

                    $this->replyWithIntent($intents, $sender, $from, $text, $wamid);
                }
            } else {
                $this->replyWithIntent($intents, $sender, $from, $text, $wamid);
            }

            $event->update([
                'status' => WebhookEvent::STATUS_PROCESSED,
                'processed_at' => now(),
                'last_error' => null,
            ]);
        } catch (Throwable $e) {
            $event->update([
                'status' => WebhookEvent::STATUS_FAILED,
                'last_error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function replyWithIntent(
        ResolvesFinovaIntent $intents,
        SendsWhatsappText $sender,
        string $from,
        string $text,
        string $wamid,
    ): void {
        $intent = $intents->handle($text);
        $reply = $intents->reply($intent);
        $sender->handle($from, $reply);

        Log::info('whatsapp.intent_replied', [
            'wamid' => $wamid,
            'intent' => $intent->value,
            'phone_masked' => $this->maskPhone($from),
        ]);
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
