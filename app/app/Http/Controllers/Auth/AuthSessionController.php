<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\RegistersUser;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class AuthSessionController extends Controller
{
    public function createRegister(): View
    {
        return view('auth.register');
    }

    public function storeRegister(Request $request, RegistersUser $registersUser): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'organization_name' => ['nullable', 'string', 'max:120'],
        ]);

        $result = $registersUser->handle($data);

        Auth::login($result['user']);
        $request->session()->regenerate();
        $request->session()->put('current_organization_id', $result['organization']->id);
        TenantContext::set($result['organization']->id);

        return redirect()->route('hub.home');
    }

    public function createLogin(): View
    {
        return view('auth.login');
    }

    public function storeLogin(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = 'login:'.strtolower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'email' => "Muitas tentativas. Tente de novo em {$seconds}s.",
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => 'Credenciais inválidas.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        $orgId = $request->user()
            ?->memberships()
            ->withoutGlobalScopes()
            ->orderBy('created_at')
            ->value('organization_id');

        if ($orgId) {
            $request->session()->put('current_organization_id', $orgId);
            TenantContext::set((string) $orgId);
        }

        return redirect()->intended(route('hub.home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        TenantContext::clear();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
