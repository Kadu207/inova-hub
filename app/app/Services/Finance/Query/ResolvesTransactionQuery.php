<?php

namespace App\Services\Finance\Query;

final class ResolvesTransactionQuery
{
    public function handle(string $text): ?TransactionPeriod
    {
        $normalized = mb_strtolower(trim($text));
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        if ($normalized === '') {
            return null;
        }

        $isQuery = preg_match('/\b(quanto|quantos|resumo|totais?|saldo|gastos?|despesas?|receitas?)\b/u', $normalized) === 1
            || preg_match('/\b(gastei|paguei|recebi)\b.+\b(hoje|semana|m[eê]s)\b/u', $normalized) === 1
            || preg_match('/\b(hoje|semana|m[eê]s)\b.+\b(gastei|gastos?|despesas?|receitas?)\b/u', $normalized) === 1;

        if (! $isQuery) {
            return null;
        }

        // Evita confundir "gastei 45 no almoço" (criação) com consulta
        if (preg_match('/\b(gastei|paguei|comprei|recebi|ganhei)\s+\d/u', $normalized) === 1) {
            return null;
        }

        // Consultas OF (saldo/extrato/cartão) são tratadas por ResolvesBankQuery
        if (preg_match('/\b(extrato|cart(?:ao|ão|oes|ões)|fatura\s+(do\s+)?cart(?:ao|ão)|limite\s+(do\s+)?cart(?:ao|ão))\b/u', $normalized) === 1) {
            return null;
        }
        if (preg_match('/\b(qual\s+(é\s+)?(o\s+)?meu\s+saldo|meu\s+saldo|saldo\s+(do\s+)?banco|saldo\s+(da\s+)?conta|quanto\s+tenho|tenho\s+na\s+conta)\b/u', $normalized) === 1) {
            return null;
        }
        if (preg_match('/^(qual\s+)?(é\s+)?(o\s+)?saldo\??$/u', $normalized) === 1) {
            return null;
        }

        if (preg_match('/\b(hoje|agora)\b/u', $normalized) === 1) {
            return TransactionPeriod::Today;
        }

        if (preg_match('/\b(semana|semanal|7\s*dias)\b/u', $normalized) === 1) {
            return TransactionPeriod::Week;
        }

        if (preg_match('/\b(m[eê]s|mensal|30\s*dias)\b/u', $normalized) === 1) {
            return TransactionPeriod::Month;
        }

        // "quanto gastei?" sem período → semana (default do produto)
        if (preg_match('/\b(quanto|resumo|totais?)\b/u', $normalized) === 1) {
            return TransactionPeriod::Week;
        }

        return null;
    }
}
