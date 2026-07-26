<?php

namespace Tests\Feature;

use App\Models\Membership;
use App\Models\OfItem;
use App\Models\Organization;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
            ])->assertCreated()
            ->assertJsonPath('pluggy_item_id', 'item-pluggy-001');

        $this->assertDatabaseHas('of_items', [
            'organization_id' => $orgA->id,
            'pluggy_item_id' => 'item-pluggy-001',
            'connector_name' => 'Pluggy Bank',
        ]);

        $this->get('/hub/connections')
            ->assertOk()
            ->assertSee('Pluggy Bank')
            ->assertSee('item-pluggy-001');

        TenantContext::set($orgB->id);
        $this->actingAs($userB);
        $this->withSession(['current_organization_id' => $orgB->id]);

        $this->get('/hub/connections')
            ->assertOk()
            ->assertDontSee('item-pluggy-001');

        $this->assertSame(
            0,
            OfItem::query()->where('pluggy_item_id', 'item-pluggy-001')->count()
        );
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
