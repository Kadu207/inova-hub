<?php

namespace App\Services\Finance\Query;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class BuildsFinanceDashboardAggregates
{
    /**
     * @return array{
     *   from: string,
     *   to: string,
     *   expense_cents: int,
     *   income_cents: int,
     *   by_category: list<array{name: string, amount_cents: int, pct: float}>,
     *   daily: list<array{date: string, expense_cents: int, income_cents: int}>
     * }
     */
    public function handle(?Carbon $now = null, int $days = 30): array
    {
        $tz = config('app.timezone', 'UTC');
        $local = ($now ?? now())->copy()->timezone($tz);
        $to = $local->copy()->endOfDay();
        $from = $local->copy()->subDays($days - 1)->startOfDay();

        $transactions = Transaction::query()
            ->where('occurred_at', '>=', $from)
            ->where('occurred_at', '<=', $to)
            ->with('category:id,name')
            ->get();

        $expenseCents = (int) $transactions->where('type', Transaction::TYPE_EXPENSE)->sum('amount_cents');
        $incomeCents = (int) $transactions->where('type', Transaction::TYPE_INCOME)->sum('amount_cents');

        $byCategory = $transactions
            ->where('type', Transaction::TYPE_EXPENSE)
            ->groupBy(fn (Transaction $tx) => $tx->category?->name ?? 'Outros')
            ->map(fn (Collection $group) => (int) $group->sum('amount_cents'))
            ->sortDesc()
            ->take(8);

        $categoryRows = [];
        foreach ($byCategory as $name => $amount) {
            $categoryRows[] = [
                'name' => (string) $name,
                'amount_cents' => $amount,
                'pct' => $expenseCents > 0 ? round(($amount / $expenseCents) * 100, 1) : 0.0,
            ];
        }

        $byDayExpense = $transactions
            ->where('type', Transaction::TYPE_EXPENSE)
            ->groupBy(fn (Transaction $tx) => $tx->occurred_at->timezone($tz)->toDateString())
            ->map(fn (Collection $group) => (int) $group->sum('amount_cents'));

        $byDayIncome = $transactions
            ->where('type', Transaction::TYPE_INCOME)
            ->groupBy(fn (Transaction $tx) => $tx->occurred_at->timezone($tz)->toDateString())
            ->map(fn (Collection $group) => (int) $group->sum('amount_cents'));

        $daily = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $from->copy()->addDays($i)->toDateString();
            $daily[] = [
                'date' => $date,
                'expense_cents' => (int) ($byDayExpense[$date] ?? 0),
                'income_cents' => (int) ($byDayIncome[$date] ?? 0),
            ];
        }

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'expense_cents' => $expenseCents,
            'income_cents' => $incomeCents,
            'by_category' => $categoryRows,
            'daily' => $daily,
        ];
    }
}
