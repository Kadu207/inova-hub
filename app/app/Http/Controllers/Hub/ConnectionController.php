<?php

namespace App\Http\Controllers\Hub;

use App\Contracts\OpenFinance\OpenFinanceProvider;
use App\Models\OfItem;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
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
            ->withCount('accounts')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('hub.connections', [
            'user' => $request->user(),
            'configured' => $configured,
            'items' => $items,
            'includeSandbox' => (bool) config('services.pluggy.include_sandbox', true),
        ]);
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

        $item = OfItem::query()->updateOrCreate(
            [
                'organization_id' => $orgId,
                'pluggy_item_id' => $data['item_id'],
            ],
            [
                'user_id' => $user->id,
                'status' => $data['status'] ?? OfItem::STATUS_CREATED,
                'client_user_id' => sprintf('org:%s:user:%s', $orgId, $user->id),
                'connector_name' => $data['connector_name'] ?? null,
                'consent_at' => now(),
            ]
        );

        return response()->json([
            'id' => $item->id,
            'pluggy_item_id' => $item->pluggy_item_id,
            'status' => $item->status,
        ], 201);
    }
}
