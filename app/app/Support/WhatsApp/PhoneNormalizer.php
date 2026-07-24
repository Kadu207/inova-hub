<?php

namespace App\Support\WhatsApp;

final class PhoneNormalizer
{
    /**
     * Aceita +5511999999999, 5511999999999, (11) 99999-9999 → E.164 BR default +55.
     */
    public static function toE164(string $raw, string $defaultCountry = '55'): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if ($digits === '') {
            throw new \InvalidArgumentException('Telefone inválido.');
        }

        if (str_starts_with($raw, '+')) {
            return '+'.$digits;
        }

        if (str_starts_with($digits, $defaultCountry)) {
            return '+'.$digits;
        }

        return '+'.$defaultCountry.$digits;
    }
}
