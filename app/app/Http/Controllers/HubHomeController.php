<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class HubHomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $orgId = TenantContext::id() ?? $request->session()->get('current_organization_id');

        return view('hub.home', [
            'user' => $user,
            'organizationId' => $orgId,
        ]);
    }
}
