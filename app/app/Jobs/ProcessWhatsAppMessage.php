<?php

namespace App\Jobs;

use App\Models\WebhookEvent;
use App\Services\Finance\Nlu\HandlesWhatsappTransactionText;
use App\Services\WhatsApp\ConsumesWhatsappOtp;
use App\Services\WhatsApp\DownloadsWhatsappMedia;
use App\Services\WhatsApp\FinovaCopy;
use App\Services\WhatsApp\ParsesWhatsappWebhook;
use App\Services\WhatsApp\ResolvesFinovaIntent;
use App\Services\WhatsApp\SendsWhatsappText;
use App\Services\WhatsApp\TranscribesWhatsappAudio;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

final class ProcessWhatsAppMessage implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array{wamid: string, from: string, text: string, type: string, media_id?: ?string}  $message
     */
    public function __construct(
        public array $message,
    ) {}

    public function handle(
        ParsesWhatsappWebhook $parser,
        ConsumesWhatsappOtp $consumes,
        ResolvesFinovaIntent $intents,
        SendsWhatsappText $sender,
        HandlesWhatsappTransactionText $transactions,
        DownloadsWhatsappMedia $downloads,
        TranscribesWhatsappAudio $transcribes,
    ): void {
        $wamid = $this->message['wamid'];
        $from = $this->message['from'];
        $text = $this->message['text'];
        $type = $this->message['type'] ?? 'text';
        $mediaId = $this->message['media_id'] ?? null;

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
            if ($type === 'audio') {
                $text = $this->transcribeAudio($downloads, $transcribes, $sender, $from, (string) $mediaId);
                if ($text === null) {
                    $event->update([
                        'status' => WebhookEvent::STATUS_FAILED,
                        'last_error' => 'stt_unavailable_or_failed',
                    ]);

                    return;
                }
            }

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

                    $this->replyConversation($transactions, $intents, $sender, $from, $text, $wamid);
                }
            } else {
                $this->replyConversation($transactions, $intents, $sender, $from, $text, $wamid);
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

    private function transcribeAudio(
        DownloadsWhatsappMedia $downloads,
        TranscribesWhatsappAudio $transcribes,
        SendsWhatsappText $sender,
        string $from,
        string $mediaId,
    ): ?string {
        if ($mediaId === '') {
            $sender->handle($from, FinovaCopy::audioFailed());

            return null;
        }

        try {
            $media = $downloads->handle($mediaId);
            $text = $transcribes->handle($media['bytes'], $media['mime']);

            Log::info('whatsapp.audio_transcribed', [
                'phone_masked' => $this->maskPhone($from),
                'chars' => mb_strlen($text),
            ]);

            return $text;
        } catch (RuntimeException $e) {
            Log::notice('whatsapp.audio_stt_skipped', ['reason' => $e->getMessage()]);

            $reply = str_contains($e->getMessage(), 'STT requires')
                ? FinovaCopy::audioNeedsStt()
                : FinovaCopy::audioFailed();
            $sender->handle($from, $reply);

            return null;
        }
    }

    private function replyConversation(
        HandlesWhatsappTransactionText $transactions,
        ResolvesFinovaIntent $intents,
        SendsWhatsappText $sender,
        string $from,
        string $text,
        string $wamid,
    ): void {
        $txResult = $transactions->handle($from, $text);

        if ($txResult['handled'] === true) {
            $sender->handle($from, (string) $txResult['reply']);
            Log::info('whatsapp.transaction_intent', [
                'wamid' => $wamid,
                'phone_masked' => $this->maskPhone($from),
            ]);

            return;
        }

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
