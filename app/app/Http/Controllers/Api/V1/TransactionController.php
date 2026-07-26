<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Transaction::class);

        $transactions = Transaction::query()
            ->with('category:id,name,slug,kind')
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->query('type')))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->query('category_id')))
            ->orderByDesc('occurred_at')
            ->paginate(min((int) $request->query('per_page', 25), 100));

        return response()->json($transactions);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Transaction::class);

        $data = $this->validated($request);
        $category = $this->resolveCategory($data['category_id'], $data['type']);

        $transaction = Transaction::query()->create([
            'category_id' => $category->id,
            'user_id' => $request->user()->id,
            'amount_cents' => $data['amount_cents'],
            'type' => $data['type'],
            'currency' => $data['currency'] ?? 'BRL',
            'source' => $data['source'] ?? Transaction::SOURCE_MANUAL,
            'description' => $data['description'] ?? null,
            'occurred_at' => $data['occurred_at'],
        ]);

        $transaction->load('category:id,name,slug,kind');

        return response()->json(['data' => $transaction], 201);
    }

    public function show(Transaction $transaction): JsonResponse
    {
        $this->authorize('view', $transaction);

        $transaction->load('category:id,name,slug,kind');

        return response()->json(['data' => $transaction]);
    }

    public function update(Request $request, Transaction $transaction): JsonResponse
    {
        $this->authorize('update', $transaction);

        $data = $this->validated($request, updating: true);
        $type = $data['type'] ?? $transaction->type;
        $categoryId = $data['category_id'] ?? $transaction->category_id;
        $this->resolveCategory($categoryId, $type);

        $transaction->fill([
            'category_id' => $categoryId,
            'amount_cents' => $data['amount_cents'] ?? $transaction->amount_cents,
            'type' => $type,
            'currency' => $data['currency'] ?? $transaction->currency,
            'source' => $data['source'] ?? $transaction->source,
            'description' => array_key_exists('description', $data) ? $data['description'] : $transaction->description,
            'occurred_at' => $data['occurred_at'] ?? $transaction->occurred_at,
        ]);
        $transaction->save();
        $transaction->load('category:id,name,slug,kind');

        return response()->json(['data' => $transaction]);
    }

    public function destroy(Transaction $transaction): JsonResponse
    {
        $this->authorize('delete', $transaction);

        $transaction->delete();

        return response()->json(null, 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $updating = false): array
    {
        $required = $updating ? 'sometimes' : 'required';

        return $request->validate([
            'amount_cents' => [$required, 'integer', 'min:1'],
            'type' => [$required, Rule::in([Transaction::TYPE_EXPENSE, Transaction::TYPE_INCOME])],
            'category_id' => [$required, 'uuid'],
            'occurred_at' => [$required, 'date'],
            'description' => ['nullable', 'string', 'max:255'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'source' => ['sometimes', Rule::in([
                Transaction::SOURCE_MANUAL,
                Transaction::SOURCE_FINOVA,
                Transaction::SOURCE_OF,
            ])],
        ]);
    }

    private function resolveCategory(string $categoryId, string $type): Category
    {
        $category = Category::query()->find($categoryId);

        if ($category === null) {
            throw ValidationException::withMessages([
                'category_id' => ['Categoria não encontrada nesta organização.'],
            ]);
        }

        if ($category->kind !== $type) {
            throw ValidationException::withMessages([
                'category_id' => ['A categoria não é compatível com o tipo do lançamento.'],
            ]);
        }

        return $category;
    }
}
