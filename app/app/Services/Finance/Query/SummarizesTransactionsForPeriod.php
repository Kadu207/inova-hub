<?php

namespace App\Services\Finance\Query;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class SummarizesTransactionsForPeriod
{
    /**
     * @return array{
     *   period: TransactionPeriod,
     *   from: Carbon,
     *   to: Carbon,
     *   expense_cents: int,
     *   income_cents: int,
     *   net_cents: int,
     *   top_categories: list<array{name: string, amount_cents: int}>
     * }
     */
    public function handle(TransactionPeriod $period, ?Carbon $now = null): array
    {
        [$from, $to] = $this->bounds($period, $now ?? now());

        $base = Transaction::query()
            ->where('occurred_at', '>=', $from)
            ->where('occurred_at', '<=', $to);

        $expenseCents = (int) (clone $base)->where('type', Transaction::TYPE_EXPENSE)->sum('amount_cents');
        $incomeCents = (int) (clone $base)->where('type', Transaction::TYPE_INCOME)->sum('amount_cents');

        $top = Transaction::query()
            ->where('type', Transaction::TYPE_EXPENSE)
            ->where('occurred_at', '>=', $from)
            ->where('occurred_at', '<=', $to)
            ->with('category:id,name')
            ->get()
            ->groupBy(fn (Transaction $tx) => $tx->category?->name ?? 'Outros')
            ->map(fn (Collection $group) => (int) $group->sum('amount_cents'))
            ->sortDesc()
            ->take(3);

        $topCategories = [];
        foreach ($top as $name => $amount) {
            $topCategories[] = [
                'name' => (string) $name,
                'amount_cents' => $amount,
            ];
        }

        return [
            'period' => $period,
            'from' => $from,
            'to' => $to,
            'expense_cents' => $expenseCents,
            'income_cents' => $incomeCents,
            'net_cents' => $incomeCents - $expenseCents,
            'top_categories' => $topCategories,
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function bounds(TransactionPeriod $period, Carbon $now): array
    {
        $tz = config('app.timezone', 'UTC');
        $local = $now->copy()->timezone($tz);

        return match ($period) {
            TransactionPeriod::Today => [
                $local->copy()->startOfDay(),
                $local->copy()->endOfDay(),
            ],
            TransactionPeriod::Week => [
                $local->copy()->startOfWeek(Carbon::MONDAY),
                $local->copy()->endOfWeek(Carbon::SUNDAY),
            ],
            TransactionPeriod::Month => [
                $local->copy()->startOfMonth(),
                $local->copy()->endOfMonth(),
            ],
        };
    }
}
