<?php

namespace App\Services\OpenFinance;

final class ResolvesBankQuery
{
    public function handle(string $text): ?BankIntent
    {
        $normalized = mb_strtolower(trim($text));
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        if ($normalized === '') {
            return null;
        }

        // Criação de lançamento manual não é consulta bancária
        if (preg_match('/\b(gastei|paguei|comprei|recebi|ganhei)\s+\d/u', $normalized) === 1) {
            return null;
        }

        if ($this->isCards($normalized)) {
            return BankIntent::Cards;
        }

        if ($this->isStatement($normalized)) {
            return BankIntent::Statement;
        }

        if ($this->isBalance($normalized)) {
            return BankIntent::Balance;
        }

        return null;
    }

    private function isBalance(string $normalized): bool
    {
        if (preg_match('/\b(qual\s+(é\s+)?(o\s+)?meu\s+saldo|meu\s+saldo|saldo\s+(do\s+)?banco|saldo\s+(da\s+)?conta|quanto\s+tenho|tenho\s+na\s+conta)\b/u', $normalized) === 1) {
            return true;
        }

        // "saldo?" isolado / curto
        if (preg_match('/^(qual\s+)?(é\s+)?(o\s+)?saldo\??$/u', $normalized) === 1) {
            return true;
        }

        return preg_match('/\bsaldo\b/u', $normalized) === 1
            && preg_match('/\b(banco|conta|open\s*finance|pluggy|conectado)\b/u', $normalized) === 1;
    }

    private function isStatement(string $normalized): bool
    {
        return preg_match('/\b(extrato|movimenta[cç][oõ]es|ultimas?\s+transa[cç][oõ]es|últimas?\s+transa[cç][oõ]es)\b/u', $normalized) === 1;
    }

    private function isCards(string $normalized): bool
    {
        return preg_match('/\b(cart(?:ao|ão|oes|ões)|fatura\s+(do\s+)?cart(?:ao|ão)|limite\s+(do\s+)?cart(?:ao|ão)|credit\s*card)\b/u', $normalized) === 1;
    }
}
