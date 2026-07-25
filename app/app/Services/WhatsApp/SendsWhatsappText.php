<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class SendsWhatsappText
{
    public function handle(string $toPhoneDigits, string $body): bool
    {
        $token = (string) config('services.whatsapp.token');
        $phoneNumberId = (string) config('services.whatsapp.phone_number_id');

        $to = preg_replace('/\D+/', '', $toPhoneDigits) ?? '';

        if ($to === '') {
            throw new RuntimeException('Destination phone is empty.');
        }

        if ($token === '' || $phoneNumberId === '') {
            Log::warning('whatsapp.send_skipped_missing_config', [
                'to_masked' => $this->mask($to),
            ]);

            return false;
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->post("https://graph.facebook.com/v21.0/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $body,
                ],
            ]);

        if (! $response->successful()) {
            Log::error('whatsapp.send_failed', [
                'status' => $response->status(),
                'to_masked' => $this->mask($to),
                'body' => $response->json(),
            ]);

            throw new RuntimeException('WhatsApp Cloud API send failed: '.$response->status());
        }

        Log::info('whatsapp.send_ok', [
            'to_masked' => $this->mask($to),
            'message_id' => $response->json('messages.0.id'),
        ]);

        return true;
    }

    private function mask(string $digits): string
    {
        if (strlen($digits) < 4) {
            return '***';
        }

        return str_repeat('*', max(strlen($digits) - 4, 0)).substr($digits, -4);
    }
}
