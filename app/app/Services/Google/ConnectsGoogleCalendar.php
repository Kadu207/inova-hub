<?php

namespace App\Services\Google;

use App\Models\ConsentLog;
use App\Models\OauthToken;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Carbon;

final class ConnectsGoogleCalendar
{
    public function __construct(
        private GoogleOAuthClient $oauth,
    ) {}

    /**
     * @param  array{access_token: string, refresh_token: ?string, expires_in: int, scope: ?string}  $tokens
     */
    public function handle(string $organizationId, User $user, array $tokens, bool $consentAccepted): OauthToken
    {
        if (! $consentAccepted) {
            throw new \InvalidArgumentException('Google Calendar consent is required.');
        }

        TenantContext::set($organizationId);

        $email = $this->oauth->fetchEmail($tokens['access_token']);
        $version = $this->oauth->consentVersion();
        $scopes = $tokens['scope'] !== null
            ? preg_split('/\s+/', trim($tokens['scope'])) ?: []
            : $this->oauth->scopes();

        $existing = OauthToken::query()
            ->where('provider', OauthToken::PROVIDER_GOOGLE)
            ->first();

        $attrs = [
            'user_id' => $user->id,
            'provider_account_email' => $email,
            'access_token' => $tokens['access_token'],
            'expires_at' => Carbon::now()->addSeconds(max(60, $tokens['expires_in'])),
            'scopes' => array_values(array_filter($scopes)),
            'consent_version' => $version,
            'connected_at' => now(),
            'revoked_at' => null,
        ];

        if ($tokens['refresh_token'] !== null) {
            $attrs['refresh_token'] = $tokens['refresh_token'];
        } elseif ($existing?->refresh_token) {
            $attrs['refresh_token'] = $existing->refresh_token;
        }

        $token = OauthToken::query()->updateOrCreate(
            [
                'organization_id' => $organizationId,
                'provider' => OauthToken::PROVIDER_GOOGLE,
            ],
            $attrs
        );

        ConsentLog::query()->create([
            'organization_id' => $organizationId,
            'user_id' => $user->id,
            'type' => ConsentLog::TYPE_GOOGLE_CALENDAR,
            'version' => $version,
            'accepted_at' => now(),
            'meta' => [
                'provider' => OauthToken::PROVIDER_GOOGLE,
                'email' => $email,
                'scopes' => $token->scopes,
            ],
        ]);

        return $token;
    }
}
