<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\WhatsappIdentity;
use App\Services\Finance\Query\BuildsFinanceDashboardAggregates;
use App\Services\Finance\Query\SummarizesTransactionsForPeriod;
use App\Services\Finance\Query\TransactionPeriod;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class HubHomeController extends Controller
{
    public function __invoke(
        Request $request,
        SummarizesTransactionsForPeriod $summarizer,
        BuildsFinanceDashboardAggregates $aggregates,
    ): View {
        $user = $request->user();
        $orgId = TenantContext::id() ?? $request->session()->get('current_organization_id');

        $whatsappIdentity = WhatsappIdentity::query()
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->first();

        $weekSummary = $summarizer->handle(TransactionPeriod::Week);
        $dashboard = $aggregates->handle(days: 30);
        $dailyExpenses = array_column($dashboard['daily'], 'expense_cents');
        $maxDailyExpense = $dailyExpenses === [] ? 1 : max(1, ...$dailyExpenses);

        return view('hub.home', [
            'user' => $user,
            'organizationId' => $orgId,
            'whatsappIdentity' => $whatsappIdentity,
            'weekSummary' => $weekSummary,
            'dashboard' => $dashboard,
            'maxDailyExpense' => $maxDailyExpense,
        ]);
    }
}
