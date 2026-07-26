<?php

namespace App\Http\Middleware;

use App\Models\Membership;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            TenantContext::clear();

            return $next($request);
        }

        $organizationId = $request->header('X-Organization-Id')
            ?? $request->session()->get('current_organization_id');

        if ($organizationId === null) {
            $organizationId = Membership::query()
                ->withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->orderBy('created_at')
                ->value('organization_id');
        }

        if ($organizationId === null) {
            TenantContext::clear();

            return $next($request);
        }

        $isMember = Membership::query()
            ->withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('organization_id', $organizationId)
            ->exists();

        if (! $isMember) {
            abort(403, 'Not a member of this organization.');
        }

        TenantContext::set((string) $organizationId);

        if ($request->hasSession()) {
            $request->session()->put('current_organization_id', (string) $organizationId);
        }

        return $next($request);
    }
}
