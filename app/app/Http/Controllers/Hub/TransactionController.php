<?php

namespace App\Http\Controllers\Hub;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Transaction::class);

        $filters = $this->filters($request);
        $query = $this->filteredQuery($filters);

        $totals = [
            'expense_cents' => (int) (clone $query)->where('type', Transaction::TYPE_EXPENSE)->sum('amount_cents'),
            'income_cents' => (int) (clone $query)->where('type', Transaction::TYPE_INCOME)->sum('amount_cents'),
        ];
        $totals['net_cents'] = $totals['income_cents'] - $totals['expense_cents'];

        $transactions = (clone $query)
            ->with('category:id,name,slug,kind')
            ->orderByDesc('occurred_at')
            ->simplePaginate(15)
            ->withQueryString();

        $categories = Category::query()->orderBy('kind')->orderBy('name')->get();

        return view('hub.transactions.index', [
            'transactions' => $transactions,
            'categories' => $categories,
            'filters' => $filters,
            'totals' => $totals,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Transaction::class);

        return view('hub.transactions.form', [
            'transaction' => null,
            'categories' => Category::query()->orderBy('kind')->orderBy('name')->get(),
            'defaultType' => $request->query('type', Transaction::TYPE_EXPENSE),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Transaction::class);

        $data = $this->validated($request);
        $category = $this->resolveCategory($data['category_id'], $data['type']);

        Transaction::query()->create([
            'category_id' => $category->id,
            'user_id' => $request->user()->id,
            'amount_cents' => $data['amount_cents'],
            'type' => $data['type'],
            'currency' => 'BRL',
            'source' => Transaction::SOURCE_MANUAL,
            'description' => $data['description'] ?? null,
            'occurred_at' => $data['occurred_at'],
        ]);

        return redirect()
            ->route('hub.transactions.index')
            ->with('status', 'Lançamento criado.');
    }

    public function edit(Transaction $transaction): View
    {
        $this->authorize('update', $transaction);

        return view('hub.transactions.form', [
            'transaction' => $transaction,
            'categories' => Category::query()->orderBy('kind')->orderBy('name')->get(),
            'defaultType' => $transaction->type,
        ]);
    }

    public function update(Request $request, Transaction $transaction): RedirectResponse
    {
        $this->authorize('update', $transaction);

        $data = $this->validated($request);
        $category = $this->resolveCategory($data['category_id'], $data['type']);

        $transaction->update([
            'category_id' => $category->id,
            'amount_cents' => $data['amount_cents'],
            'type' => $data['type'],
            'description' => $data['description'] ?? null,
            'occurred_at' => $data['occurred_at'],
        ]);

        return redirect()
            ->route('hub.transactions.index')
            ->with('status', 'Lançamento atualizado.');
    }

    public function destroy(Transaction $transaction): RedirectResponse
    {
        $this->authorize('delete', $transaction);

        $transaction->delete();

        return redirect()
            ->route('hub.transactions.index')
            ->with('status', 'Lançamento excluído.');
    }

    /**
     * @return array{from: ?string, to: ?string, category_id: ?string, type: ?string}
     */
    private function filters(Request $request): array
    {
        return [
            'from' => $request->query('from'),
            'to' => $request->query('to'),
            'category_id' => $request->query('category_id'),
            'type' => $request->query('type'),
        ];
    }

    /**
     * @param  array{from: ?string, to: ?string, category_id: ?string, type: ?string}  $filters
     */
    private function filteredQuery(array $filters)
    {
        return Transaction::query()
            ->when($filters['from'], function ($q, $from) {
                $q->where('occurred_at', '>=', Carbon::parse($from)->startOfDay());
            })
            ->when($filters['to'], function ($q, $to) {
                $q->where('occurred_at', '<=', Carbon::parse($to)->endOfDay());
            })
            ->when($filters['category_id'], fn ($q, $id) => $q->where('category_id', $id))
            ->when($filters['type'], fn ($q, $type) => $q->where('type', $type));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'amount' => ['required', 'string'],
            'type' => ['required', Rule::in([Transaction::TYPE_EXPENSE, Transaction::TYPE_INCOME])],
            'category_id' => ['required', 'uuid'],
            'occurred_at' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $cents = $this->parseAmountToCents($data['amount']);
        if ($cents < 1) {
            throw ValidationException::withMessages([
                'amount' => ['Informe um valor maior que zero.'],
            ]);
        }

        $data['amount_cents'] = $cents;
        unset($data['amount']);

        return $data;
    }

    private function parseAmountToCents(string $raw): int
    {
        $normalized = trim(str_replace(['R$', ' '], '', $raw));

        if (str_contains($normalized, ',')) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        }

        if (! is_numeric($normalized)) {
            throw ValidationException::withMessages([
                'amount' => ['Valor inválido. Use formato 45,90.'],
            ]);
        }

        return (int) round(((float) $normalized) * 100);
    }

    private function resolveCategory(string $categoryId, string $type): Category
    {
        $category = Category::query()->find($categoryId);

        if ($category === null) {
            throw ValidationException::withMessages([
                'category_id' => ['Categoria não encontrada.'],
            ]);
        }

        if ($category->kind !== $type) {
            throw ValidationException::withMessages([
                'category_id' => ['A categoria não combina com o tipo do lançamento.'],
            ]);
        }

        return $category;
    }
}
