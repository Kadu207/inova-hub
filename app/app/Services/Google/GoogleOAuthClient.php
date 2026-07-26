<?php

namespace App\Services\Google;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

final class GoogleOAuthClient
{
    public function isConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect_uri'));
    }

    /**
     * @return list<string>
     */
    public function scopes(): array
    {
        /** @var list<string> $scopes */
        $scopes = config('google_calendar.scopes', []);

        return $scopes;
    }

    public function consentVersion(): string
    {
        return (string) config('google_calendar.consent_version', 'gcal-1.0');
    }

    /**
     * @return array{url: string, state: string}
     */
    public function authorizationUrl(string $state): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Google OAuth is not configured.');
        }

        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.redirect_uri'),
            'response_type' => 'code',
            'scope' => implode(' ', $this->scopes()),
            'access_type' => 'offline',
            'include_granted_scopes' => 'true',
            'prompt' => 'consent',
            'state' => $state,
        ]);

        return [
            'url' => rtrim((string) config('services.google.auth_url'), '?').'?'.$query,
            'state' => $state,
        ];
    }

    public function makeState(): string
    {
        return Str::random(40);
    }

    /**
     * @return array{access_token: string, refresh_token: ?string, expires_in: int, scope: ?string, token_type: ?string}
     */
    public function exchangeCode(string $code): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Google OAuth is not configured.');
        }

        $response = Http::asForm()
            ->timeout(20)
            ->post((string) config('services.google.token_url'), [
                'code' => $code,
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => config('services.google.redirect_uri'),
                'grant_type' => 'authorization_code',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Google token exchange failed: '.$response->body());
        }

        $data = $response->json();
        if (! is_array($data) || empty($data['access_token'])) {
            throw new RuntimeException('Google token response missing access_token.');
        }

        return [
            'access_token' => (string) $data['access_token'],
            'refresh_token' => isset($data['refresh_token']) ? (string) $data['refresh_token'] : null,
            'expires_in' => (int) ($data['expires_in'] ?? 3600),
            'scope' => isset($data['scope']) ? (string) $data['scope'] : null,
            'token_type' => isset($data['token_type']) ? (string) $data['token_type'] : null,
        ];
    }

    public function fetchEmail(string $accessToken): ?string
    {
        $response = Http::withToken($accessToken)
            ->timeout(15)
            ->acceptJson()
            ->get((string) config('services.google.userinfo_url'));

        if (! $response->successful()) {
            return null;
        }

        $email = $response->json('email');

        return is_string($email) && $email !== '' ? $email : null;
    }
}
