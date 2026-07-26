<?php

namespace App\Http\Controllers\Hub;

use App\Contracts\OpenFinance\OpenFinanceProvider;
use App\Jobs\SyncPluggyItem;
use App\Models\OfAccount;
use App\Models\OfItem;
use App\Models\OfTransaction;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

final class ConnectionController
{
    public function index(Request $request): View
    {
        $configured = filled(config('services.pluggy.client_id'))
            && filled(config('services.pluggy.client_secret'));

        $items = OfItem::query()
            ->with(['accounts' => fn ($q) => $q->orderByDesc('balance_cents')])
            ->withCount('accounts')
            ->where('status', '!=', OfItem::STATUS_DELETED)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $totalBalanceCents = $items->flatMap->accounts->sum('balance_cents');

        return view('hub.connections', [
            'user' => $request->user(),
            'configured' => $configured,
            'items' => $items,
            'totalBalanceCents' => (int) $totalBalanceCents,
            'includeSandbox' => (bool) config('services.pluggy.include_sandbox', true),
        ]);
    }

    public function showAccount(Request $request, OfAccount $account): View
    {
        $transactions = OfTransaction::query()
            ->where('of_account_id', $account->id)
            ->orderByDesc('occurred_at')
            ->limit(100)
            ->get();

        return view('hub.of-account', [
            'user' => $request->user(),
            'account' => $account->load('item'),
            'transactions' => $transactions,
        ]);
    }

    public function sync(Request $request, OfItem $item): RedirectResponse
    {
        SyncPluggyItem::dispatch([
            'event' => 'item/updated',
            'itemId' => $item->pluggy_item_id,
            'clientUserId' => $item->client_user_id,
        ]);

        return redirect()
            ->route('hub.connections.index')
            ->with('status', 'Sincronização enfileirada. Atualize em alguns segundos.');
    }

    public function connectToken(Request $request, OpenFinanceProvider $provider): JsonResponse
    {
        $user = $request->user();
        $orgId = TenantContext::id() ?? $request->session()->get('current_organization_id');

        if ($user === null || $orgId === null) {
            return response()->json(['message' => 'Tenant context required.'], 403);
        }

        $clientUserId = sprintf('org:%s:user:%s', $orgId, $user->id);

        $options = [
            'clientUserId' => $clientUserId,
            'avoidDuplicates' => true,
        ];

        $webhookUrl = (string) config('services.pluggy.webhook_url');
        if ($webhookUrl !== '' && str_starts_with($webhookUrl, 'https://')) {
            $options['webhookUrl'] = $webhookUrl;
        }

        try {
            $accessToken = $provider->createConnectToken($options);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Não foi possível criar o Connect Token Pluggy.',
            ], 502);
        }

        return response()->json([
            'accessToken' => $accessToken,
            'clientUserId' => $clientUserId,
        ]);
    }

    public function storeItem(Request $request): JsonResponse
    {
        $user = $request->user();
        $orgId = TenantContext::id() ?? $request->session()->get('current_organization_id');

        if ($user === null || $orgId === null) {
            return response()->json(['message' => 'Tenant context required.'], 403);
        }

        $data = $request->validate([
            'item_id' => ['required', 'string', 'max:64'],
            'status' => ['nullable', 'string', 'max:32'],
            'connector_name' => ['nullable', 'string', 'max:191'],
        ]);

        $clientUserId = sprintf('org:%s:user:%s', $orgId, $user->id);

        $item = OfItem::query()->updateOrCreate(
            [
                'organization_id' => $orgId,
                'pluggy_item_id' => $data['item_id'],
            ],
            [
                'user_id' => $user->id,
                'status' => $data['status'] ?? OfItem::STATUS_CREATED,
                'client_user_id' => $clientUserId,
                'connector_name' => $data['connector_name'] ?? null,
                'consent_at' => now(),
            ]
        );

        SyncPluggyItem::dispatch([
            'event' => 'item/created',
            'itemId' => $item->pluggy_item_id,
            'clientUserId' => $clientUserId,
        ]);

        return response()->json([
            'id' => $item->id,
            'pluggy_item_id' => $item->pluggy_item_id,
            'status' => $item->status,
        ], 201);
    }
}
