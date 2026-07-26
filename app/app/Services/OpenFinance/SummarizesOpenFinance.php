<?php

namespace App\Services\OpenFinance;

use App\Models\OfAccount;
use App\Models\OfItem;
use App\Models\OfTransaction;

final class SummarizesOpenFinance
{
    /**
     * @return array{
     *   has_connection: bool,
     *   total_balance_cents: int,
     *   accounts: list<array{name: string, type: ?string, subtype: ?string, balance_cents: int, is_card: bool}>,
     *   cards: list<array{name: string, type: ?string, subtype: ?string, balance_cents: int, is_card: bool}>,
     *   recent_transactions: list<array{description: string, amount_cents: int, type: string, occurred_at: string, account_name: string}>
     * }
     */
    public function handle(int $limitTransactions = 5): array
    {
        $hasConnection = OfItem::query()
            ->where('status', '!=', OfItem::STATUS_DELETED)
            ->exists();

        $accounts = OfAccount::query()
            ->orderByDesc('balance_cents')
            ->get()
            ->map(fn (OfAccount $a) => [
                'name' => $a->name ?: ($a->type ?: 'Conta'),
                'type' => $a->type,
                'subtype' => $a->subtype,
                'balance_cents' => (int) $a->balance_cents,
                'is_card' => $this->isCard($a->type, $a->subtype),
            ])
            ->all();

        $cards = array_values(array_filter($accounts, fn (array $a) => $a['is_card']));

        $recent = OfTransaction::query()
            ->with('account')
            ->orderByDesc('occurred_at')
            ->limit($limitTransactions)
            ->get()
            ->map(fn (OfTransaction $tx) => [
                'description' => $tx->description ?: 'Sem descrição',
                'amount_cents' => (int) $tx->amount_cents,
                'type' => $tx->type,
                'occurred_at' => $tx->occurred_at->timezone(config('app.timezone'))->format('d/m'),
                'account_name' => $tx->account?->name ?: 'Conta',
            ])
            ->all();

        return [
            'has_connection' => $hasConnection,
            'total_balance_cents' => (int) collect($accounts)->sum('balance_cents'),
            'accounts' => $accounts,
            'cards' => $cards,
            'recent_transactions' => $recent,
        ];
    }

    private function isCard(?string $type, ?string $subtype): bool
    {
        $blob = mb_strtoupper(trim(($type ?? '').' '.($subtype ?? '')));

        return str_contains($blob, 'CREDIT')
            || str_contains($blob, 'CARD')
            || str_contains($blob, 'CARTAO')
            || str_contains($blob, 'CARTÃO');
    }
}
