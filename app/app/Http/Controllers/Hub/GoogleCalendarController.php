<?php

namespace App\Http\Controllers\Hub;

use App\Models\OauthToken;
use App\Services\Google\ConnectsGoogleCalendar;
use App\Services\Google\GoogleOAuthClient;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

final class GoogleCalendarController
{
    public function show(Request $request, GoogleOAuthClient $oauth): View
    {
        $orgId = TenantContext::id() ?? $request->session()->get('current_organization_id');

        $connection = null;
        if ($orgId !== null) {
            $connection = OauthToken::query()
                ->where('provider', OauthToken::PROVIDER_GOOGLE)
                ->whereNull('revoked_at')
                ->first();
        }

        return view('hub.google-calendar', [
            'user' => $request->user(),
            'configured' => $oauth->isConfigured(),
            'consentVersion' => $oauth->consentVersion(),
            'scopes' => $oauth->scopes(),
            'connection' => $connection,
        ]);
    }

    public function redirect(Request $request, GoogleOAuthClient $oauth): RedirectResponse
    {
        $request->validate([
            'consent_accepted' => ['accepted'],
        ]);

        if (! $oauth->isConfigured()) {
            return redirect()
                ->route('hub.google.show')
                ->withErrors(['google' => 'Google OAuth não configurado. Defina GOOGLE_CLIENT_ID e GOOGLE_CLIENT_SECRET.']);
        }

        $state = $oauth->makeState();
        $request->session()->put('google_oauth_state', $state);
        $request->session()->put('google_oauth_consent', true);
        $request->session()->put('google_oauth_consent_version', $oauth->consentVersion());

        $auth = $oauth->authorizationUrl($state);

        return redirect()->away($auth['url']);
    }

    public function callback(
        Request $request,
        GoogleOAuthClient $oauth,
        ConnectsGoogleCalendar $connects,
    ): RedirectResponse {
        $expectedState = $request->session()->pull('google_oauth_state');
        $consentAccepted = (bool) $request->session()->pull('google_oauth_consent');
        $request->session()->forget('google_oauth_consent_version');

        if ($request->query('error')) {
            return redirect()
                ->route('hub.google.show')
                ->withErrors(['google' => 'Autorização Google cancelada ou negada.']);
        }

        $state = (string) $request->query('state', '');
        $code = (string) $request->query('code', '');

        if (! is_string($expectedState) || $expectedState === '' || ! hash_equals($expectedState, $state) || $code === '') {
            return redirect()
                ->route('hub.google.show')
                ->withErrors(['google' => 'Estado OAuth inválido. Tente conectar novamente.']);
        }

        $user = $request->user();
        $orgId = TenantContext::id() ?? $request->session()->get('current_organization_id');

        if ($user === null || $orgId === null) {
            return redirect()->route('login');
        }

        try {
            $tokens = $oauth->exchangeCode($code);
            $connects->handle((string) $orgId, $user, $tokens, $consentAccepted);
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('hub.google.show')
                ->withErrors(['google' => 'Falha ao concluir OAuth Google. Tente novamente.']);
        }

        return redirect()
            ->route('hub.google.show')
            ->with('status', 'Google Agenda conectada. A sincronização de eventos chega no D30.');
    }

    public function disconnect(Request $request): RedirectResponse
    {
        $token = OauthToken::query()
            ->where('provider', OauthToken::PROVIDER_GOOGLE)
            ->whereNull('revoked_at')
            ->first();

        if ($token !== null) {
            $token->update([
                'revoked_at' => now(),
            ]);
        }

        return redirect()
            ->route('hub.google.show')
            ->with('status', 'Conexão Google revogada neste Hub.');
    }
}
