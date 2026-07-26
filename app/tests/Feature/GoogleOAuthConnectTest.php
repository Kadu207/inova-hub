<?php

namespace Tests\Feature;

use App\Models\ConsentLog;
use App\Models\Membership;
use App\Models\OauthToken;
use App\Models\Organization;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleOAuthConnectTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_guest_is_redirected_from_google_page(): void
    {
        $this->get('/hub/google')->assertRedirect('/login');
    }

    public function test_owner_sees_consent_screen_and_connect_button_starts_oauth(): void
    {
        [$user, $org] = $this->makeOwner();

        config([
            'services.google.client_id' => 'google-client-id',
            'services.google.client_secret' => 'google-secret',
            'services.google.redirect_uri' => 'https://inovahub.inovatitech.com.br/hub/google/callback',
            'google_calendar.consent_version' => 'gcal-1.0',
        ]);

        $this->actingAs($user);
        $this->withSession(['current_organization_id' => $org->id]);
        TenantContext::set($org->id);

        $this->get('/hub/google')
            ->assertOk()
            ->assertSee('Conectar Google')
            ->assertSee('gcal-1.0')
            ->assertSee('calendar.events')
            ->assertSee('sem People API');

        $this->get('/hub')->assertOk()->assertSee('Conectar Google');

        $this->get('/hub/google')->assertOk();
        $response = $this->post('/hub/google/redirect', [
            '_token' => session()->token(),
            'consent_accepted' => '1',
        ]);

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertNotNull($location);
        $this->assertStringContainsString('accounts.google.com', $location);
        $this->assertStringContainsString('client_id=google-client-id', $location);
        $this->assertStringContainsString('calendar.events', urldecode((string) $location));
        $this->assertTrue(session()->has('google_oauth_state'));
    }

    public function test_redirect_requires_consent_checkbox(): void
    {
        [$user, $org] = $this->makeOwner();

        config([
            'services.google.client_id' => 'google-client-id',
            'services.google.client_secret' => 'google-secret',
            'services.google.redirect_uri' => 'https://example.test/hub/google/callback',
        ]);

        $this->actingAs($user);
        $this->withSession(['current_organization_id' => $org->id]);
        TenantContext::set($org->id);
        $this->get('/hub/google')->assertOk();

        try {
            $this->withoutExceptionHandling()
                ->post('/hub/google/redirect', [
                    '_token' => session()->token(),
                ]);
            $this->fail('Expected ValidationException');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('consent_accepted', $e->errors());
        }

        $this->withExceptionHandling();
    }

    public function test_callback_persists_encrypted_token_and_consent_log(): void
    {
        [$user, $org] = $this->makeOwner();

        config([
            'services.google.client_id' => 'google-client-id',
            'services.google.client_secret' => 'google-secret',
            'services.google.redirect_uri' => 'https://example.test/hub/google/callback',
            'google_calendar.consent_version' => 'gcal-1.0',
        ]);

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'access-xyz',
                'refresh_token' => 'refresh-xyz',
                'expires_in' => 3600,
                'scope' => 'openid email https://www.googleapis.com/auth/calendar.events',
                'token_type' => 'Bearer',
            ], 200),
            'openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'email' => 'user@gmail.com',
            ], 200),
        ]);

        $this->actingAs($user);
        $this->withSession([
            'current_organization_id' => $org->id,
            'google_oauth_state' => 'state-abc',
            'google_oauth_consent' => true,
        ]);
        TenantContext::set($org->id);

        $this->get('/hub/google/callback?code=auth-code&state=state-abc')
            ->assertRedirect(route('hub.google.show'));

        $token = OauthToken::query()->where('provider', OauthToken::PROVIDER_GOOGLE)->first();
        $this->assertNotNull($token);
        $this->assertSame('user@gmail.com', $token->provider_account_email);
        $this->assertSame('gcal-1.0', $token->consent_version);
        $this->assertSame('access-xyz', $token->access_token);
        $this->assertSame('refresh-xyz', $token->refresh_token);
        $this->assertNull($token->revoked_at);

        $this->assertDatabaseHas('consent_logs', [
            'organization_id' => $org->id,
            'type' => ConsentLog::TYPE_GOOGLE_CALENDAR,
            'version' => 'gcal-1.0',
        ]);

        $raw = \Illuminate\Support\Facades\DB::table('oauth_tokens')->where('id', $token->id)->first();
        $this->assertNotSame('access-xyz', $raw->access_token);
    }

    public function test_idor_hides_google_token_from_other_org(): void
    {
        [$userA, $orgA] = $this->makeOwner();
        [$userB, $orgB] = $this->makeOwner('Org B');

        config([
            'services.google.client_id' => 'google-client-id',
            'services.google.client_secret' => 'google-secret',
            'services.google.redirect_uri' => 'https://example.test/hub/google/callback',
        ]);

        TenantContext::set($orgA->id);
        OauthToken::query()->create([
            'organization_id' => $orgA->id,
            'user_id' => $userA->id,
            'provider' => OauthToken::PROVIDER_GOOGLE,
            'provider_account_email' => 'a@gmail.com',
            'access_token' => 'tok-a',
            'refresh_token' => 'ref-a',
            'expires_at' => now()->addHour(),
            'scopes' => ['calendar.events'],
            'consent_version' => 'gcal-1.0',
            'connected_at' => now(),
        ]);

        $this->actingAs($userB);
        $this->withSession(['current_organization_id' => $orgB->id]);
        TenantContext::set($orgB->id);

        $this->get('/hub/google')
            ->assertOk()
            ->assertDontSee('a@gmail.com')
            ->assertSee('Conectar Google');

        $this->assertSame(0, OauthToken::query()->count());
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function makeOwner(string $orgName = 'Org A'): array
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create(['name' => $orgName]);
        Membership::query()->withoutGlobalScopes()->create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'role' => Membership::ROLE_OWNER,
        ]);

        return [$user, $org];
    }
}
