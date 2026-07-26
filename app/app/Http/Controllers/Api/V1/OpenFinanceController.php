<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\OpenFinance\OpenFinanceProvider;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class OpenFinanceController
{
    public function connectToken(Request $request, OpenFinanceProvider $provider): JsonResponse
    {
        $user = $request->user();
        $orgId = TenantContext::id();

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
                'message' => 'Unable to create Pluggy connect token.',
            ], 502);
        }

        return response()->json([
            'accessToken' => $accessToken,
            'clientUserId' => $clientUserId,
        ]);
    }
}
