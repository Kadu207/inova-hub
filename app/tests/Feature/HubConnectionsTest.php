<?php

namespace Tests\Feature;

use App\Jobs\SyncPluggyItem;
use App\Models\Membership;
use App\Models\OfAccount;
use App\Models\OfItem;
use App\Models\OfTransaction;
use App\Models\Organization;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HubConnectionsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_guest_is_redirected_from_connections(): void
    {
        $this->get('/hub/connections')->assertRedirect('/login');
        $this->get('/app/connections')->assertRedirect('/hub/connections');
    }

    public function test_owner_sees_connections_page_and_can_create_connect_token(): void
    {
        [$user, $org] = $this->makeOwner();

        config([
            'services.pluggy.client_id' => '11111111-1111-1111-1111-111111111111',
            'services.pluggy.client_secret' => 'secret',
            'services.pluggy.base_url' => 'https://api.pluggy.ai',
            'services.pluggy.include_sandbox' => true,
        ]);

        Http::fake([
            'api.pluggy.ai/auth' => Http::response(['apiKey' => 'api-key'], 200),
            'api.pluggy.ai/connect_token' => Http::response(['accessToken' => 'connect-token-xyz'], 200),
        ]);

        $this->actingAs($user);
        $this->withSession(['current_organization_id' => $org->id]);
        TenantContext::set($org->id);

        $this->get('/hub/connections')
            ->assertOk()
            ->assertSee('Conectar banco')
            ->assertSee('Open Finance')
            ->assertSee('cdn.pluggy.ai', false);

        $csrf = session('_token');
        $token = $this->withHeader('X-CSRF-TOKEN', (string) $csrf)
            ->postJson('/hub/connections/connect-token');
        $token->assertOk()
            ->assertJsonPath('accessToken', 'connect-token-xyz')
            ->assertJsonPath('clientUserId', sprintf('org:%s:user:%s', $org->id, $user->id));

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/connect_token')
                && $request->hasHeader('X-API-KEY', 'api-key')
                && ($request['options']['clientUserId'] ?? null) !== null;
        });
    }

    public function test_widget_success_persists_of_item_and_idor_hides_other_org(): void
    {
        Queue::fake();

        [$userA, $orgA] = $this->makeOwner();
        [$userB, $orgB] = $this->makeOwner('Org B');

        $this->actingAs($userA);
        $this->withSession(['current_organization_id' => $orgA->id]);
        TenantContext::set($orgA->id);

        $this->get('/hub/connections')->assertOk();
        $csrf = session('_token');

        $this->withHeader('X-CSRF-TOKEN', (string) $csrf)
            ->postJson('/hub/connections/items', [
                'item_id' => 'item-pluggy-001',
                'status' => 'UPDATED',
                'connector_name' => 'Pluggy Bank',
                'consent_accepted' => true,
                'consent_version' => config('open_finance.consent_version'),
            ])->assertCreated()
            ->assertJsonPath('pluggy_item_id', 'item-pluggy-001')
            ->assertJsonPath('consent_version', config('open_finance.consent_version'));

        Queue::assertPushed(SyncPluggyItem::class);

        $this->assertDatabaseHas('of_items', [
            'organization_id' => $orgA->id,
            'pluggy_item_id' => 'item-pluggy-001',
            'connector_name' => 'Pluggy Bank',
            'consent_version' => config('open_finance.consent_version'),
        ]);

        $this->assertDatabaseHas('consent_logs', [
            'organization_id' => $orgA->id,
            'type' => 'open_finance',
            'version' => config('open_finance.consent_version'),
        ]);

        $this->get('/hub/connections')
            ->assertOk()
            ->assertSee('Pluggy Bank')
            ->assertSee('Saldo total OF')
            ->assertSee('Consentimento versão');

        try {
            $this->withoutExceptionHandling()
                ->postJson('/hub/connections/items', [
                    'item_id' => 'item-no-consent',
                    'consent_version' => config('open_finance.consent_version'),
                ]);
            $this->fail('Expected ValidationException when consent is missing.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('consent_accepted', $e->errors());
        }

        $this->withExceptionHandling();

        TenantContext::set($orgB->id);
        $this->actingAs($userB);
        $this->withSession(['current_organization_id' => $orgB->id]);

        $this->get('/hub/connections')
            ->assertOk()
            ->assertDontSee('Pluggy Bank');

        $this->assertSame(
            0,
            OfItem::query()->where('pluggy_item_id', 'item-pluggy-001')->count()
        );
    }

    public function test_hub_shows_account_balance_and_statement(): void
    {
        [$user, $org] = $this->makeOwner();
        [$otherUser, $otherOrg] = $this->makeOwner('Other');

        TenantContext::set($org->id);
        $item = OfItem::query()->create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'pluggy_item_id' => 'item-ui-1',
            'status' => OfItem::STATUS_UPDATED,
            'connector_name' => 'Pluggy Bank',
            'consent_at' => now(),
        ]);
        $account = OfAccount::query()->create([
            'organization_id' => $org->id,
            'of_item_id' => $item->id,
            'pluggy_account_id' => 'acc-ui-1',
            'name' => 'Conta Corrente',
            'type' => 'BANK',
            'subtype' => 'CHECKING_ACCOUNT',
            'currency' => 'BRL',
            'balance_cents' => 25050,
            'synced_at' => now(),
        ]);
        OfTransaction::query()->create([
            'organization_id' => $org->id,
            'of_account_id' => $account->id,
            'pluggy_transaction_id' => 'tx-ui-1',
            'amount_cents' => 1990,
            'currency' => 'BRL',
            'type' => OfTransaction::TYPE_EXPENSE,
            'description' => 'Café sandbox',
            'occurred_at' => now()->subDay(),
        ]);

        $this->actingAs($user);
        $this->withSession(['current_organization_id' => $org->id]);

        $this->get('/hub/connections')
            ->assertOk()
            ->assertSee('Conta Corrente')
            ->assertSee('250,50')
            ->assertSee('Sincronizar');

        $this->get('/hub/connections/accounts/'.$account->id)
            ->assertOk()
            ->assertSee('Extrato')
            ->assertSee('Café sandbox')
            ->assertSee('19,90');

        $this->actingAs($otherUser);
        $this->withSession(['current_organization_id' => $otherOrg->id]);
        TenantContext::set($otherOrg->id);

        $this->get('/hub/connections/accounts/'.$account->id)->assertNotFound();
    }

    public function test_api_connect_token_requires_sanctum_and_tenant(): void
    {
        [$user, $org] = $this->makeOwner();

        config([
            'services.pluggy.client_id' => '11111111-1111-1111-1111-111111111111',
            'services.pluggy.client_secret' => 'secret',
            'services.pluggy.base_url' => 'https://api.pluggy.ai',
        ]);

        Http::fake([
            'api.pluggy.ai/auth' => Http::response(['apiKey' => 'api-key'], 200),
            'api.pluggy.ai/connect_token' => Http::response(['accessToken' => 'tok'], 200),
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/open-finance/connect-token', [], [
            'X-Organization-Id' => $org->id,
        ])->assertOk()
            ->assertJsonPath('accessToken', 'tok');
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
