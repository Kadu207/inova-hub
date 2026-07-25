<?php

namespace App\Services\WhatsApp;

enum FinovaIntent: string
{
    case Greeting = 'greeting';
    case Help = 'help';
    case Fallback = 'fallback';
}

final class ResolvesFinovaIntent
{
    public function handle(string $text): FinovaIntent
    {
        $normalized = mb_strtolower(trim($text));
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        if ($normalized === '') {
            return FinovaIntent::Fallback;
        }

        if (preg_match('/\b(oi|ol[aá]|hey|hola|bom dia|boa tarde|boa noite)\b/u', $normalized) === 1) {
            return FinovaIntent::Greeting;
        }

        if (preg_match('/\b(ajuda|help|menu|o que (voc[eê]|vc) (faz|pode)|comandos)\b/u', $normalized) === 1) {
            return FinovaIntent::Help;
        }

        return FinovaIntent::Fallback;
    }

    public function reply(FinovaIntent $intent): string
    {
        return match ($intent) {
            FinovaIntent::Greeting => FinovaCopy::greeting(),
            FinovaIntent::Help => FinovaCopy::help(),
            FinovaIntent::Fallback => FinovaCopy::fallback(),
        };
    }
}
