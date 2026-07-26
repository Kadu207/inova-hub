<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\Finance\Query\BuildsFinanceDashboardAggregates;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AggregateController extends Controller
{
    public function __construct(
        private readonly BuildsFinanceDashboardAggregates $aggregates,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Transaction::class);

        $days = min(90, max(7, (int) $request->query('days', 30)));

        return response()->json([
            'data' => $this->aggregates->handle(days: $days),
        ]);
    }

    public function byCategory(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Transaction::class);

        $days = min(90, max(7, (int) $request->query('days', 30)));
        $data = $this->aggregates->handle(days: $days);

        return response()->json([
            'data' => [
                'from' => $data['from'],
                'to' => $data['to'],
                'expense_cents' => $data['expense_cents'],
                'by_category' => $data['by_category'],
            ],
        ]);
    }

    public function daily(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Transaction::class);

        $days = min(90, max(7, (int) $request->query('days', 30)));
        $data = $this->aggregates->handle(days: $days);

        return response()->json([
            'data' => [
                'from' => $data['from'],
                'to' => $data['to'],
                'daily' => $data['daily'],
            ],
        ]);
    }
}
