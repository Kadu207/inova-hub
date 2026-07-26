<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class DownloadsWhatsappMedia
{
    /**
     * Baixa bytes da mídia Cloud API. Não persiste em disco permanente.
     *
     * @return array{bytes: string, mime: string}
     */
    public function handle(string $mediaId): array
    {
        $token = (string) config('services.whatsapp.token');

        if ($token === '' || $mediaId === '') {
            throw new RuntimeException('WhatsApp media download requires WHATSAPP_TOKEN and media id.');
        }

        $meta = Http::withToken($token)
            ->acceptJson()
            ->get("https://graph.facebook.com/v21.0/{$mediaId}");

        if (! $meta->successful()) {
            Log::error('whatsapp.media_meta_failed', ['status' => $meta->status()]);
            throw new RuntimeException('Failed to resolve WhatsApp media URL.');
        }

        $url = (string) $meta->json('url');
        $mime = (string) ($meta->json('mime_type') ?? 'audio/ogg');

        if ($url === '') {
            throw new RuntimeException('WhatsApp media URL missing.');
        }

        $binary = Http::withToken($token)
            ->withHeaders(['Accept' => '*/*'])
            ->get($url);

        if (! $binary->successful()) {
            Log::error('whatsapp.media_download_failed', ['status' => $binary->status()]);
            throw new RuntimeException('Failed to download WhatsApp media bytes.');
        }

        return [
            'bytes' => $binary->body(),
            'mime' => $mime,
        ];
    }
}
