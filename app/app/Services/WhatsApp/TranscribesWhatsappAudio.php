<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * STT via Whisper (OpenAI ou Groq). Áudio só em arquivo temporário — apagado no finally.
 */
class TranscribesWhatsappAudio
{
    public function handle(string $bytes, string $mimeType = 'audio/ogg'): string
    {
        $apiKey = (string) config('services.llm.api_key');
        $baseUrl = rtrim((string) config('services.llm.stt_base_url', config('services.llm.base_url')), '/');
        $model = (string) config('services.llm.stt_model', 'whisper-1');

        if ($apiKey === '' || $baseUrl === '') {
            throw new RuntimeException('STT requires OPENAI_API_KEY or GROQ_API_KEY.');
        }

        $extension = str_contains($mimeType, 'mpeg') || str_contains($mimeType, 'mp3') ? 'mp3' : 'ogg';
        $path = tempnam(sys_get_temp_dir(), 'finova_audio_');
        if ($path === false) {
            throw new RuntimeException('Cannot create temp audio file.');
        }

        $audioPath = $path.'.'.$extension;
        rename($path, $audioPath);

        try {
            file_put_contents($audioPath, $bytes);

            $response = Http::withToken($apiKey)
                ->attach('file', file_get_contents($audioPath) ?: '', 'audio.'.$extension)
                ->post($baseUrl.'/audio/transcriptions', [
                    'model' => $model,
                    'language' => 'pt',
                    'response_format' => 'json',
                ]);

            if (! $response->successful()) {
                Log::error('finova.stt_failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);
                throw new RuntimeException('Whisper transcription failed: '.$response->status());
            }

            $text = trim((string) ($response->json('text') ?? ''));

            if ($text === '') {
                throw new RuntimeException('Empty transcription.');
            }

            Log::info('finova.stt_ok', ['chars' => mb_strlen($text)]);

            return $text;
        } catch (Throwable $e) {
            throw $e;
        } finally {
            if (is_file($audioPath)) {
                @unlink($audioPath);
            }
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
}
