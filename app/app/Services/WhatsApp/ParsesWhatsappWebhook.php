<?php

namespace App\Services\WhatsApp;

/**
 * Extrai mensagens de texto do payload Cloud API (sanitizado / sem PII extra).
 */
final class ParsesWhatsappWebhook
{
    /**
     * @return list<array{wamid: string, from: string, text: string, type: string}>
     */
    public function extractInboundMessages(array $payload): array
    {
        if (($payload['object'] ?? null) !== 'whatsapp_business_account') {
            return [];
        }

        $messages = [];

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];
                foreach ($value['messages'] ?? [] as $message) {
                    $wamid = (string) ($message['id'] ?? '');
                    $from = (string) ($message['from'] ?? '');
                    $type = (string) ($message['type'] ?? '');

                    if ($wamid === '' || $from === '') {
                        continue;
                    }

                    $text = '';
                    if ($type === 'text') {
                        $text = (string) ($message['text']['body'] ?? '');
                    }

                    $messages[] = [
                        'wamid' => $wamid,
                        'from' => $from,
                        'text' => $text,
                        'type' => $type,
                    ];
                }
            }
        }

        return $messages;
    }

    public function extractOtpCode(string $text): ?string
    {
        if (preg_match('/\b(\d{6})\b/', $text, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }
}
