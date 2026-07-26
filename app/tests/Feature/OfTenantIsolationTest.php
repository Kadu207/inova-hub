<?php

namespace Tests\Feature;

use App\Models\ConsentLog;
use App\Models\Membership;
use App\Models\OfAccount;
use App\Models\OfItem;
use App\Models\OfTransaction;
use App\Models\Organization;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_global_scope_hides_of_resources_from_other_tenant(): void
    {
        [$userA, $orgA] = $this->makeOwner('Org A');
        [, $orgB] = $this->makeOwner('Org B');

        TenantContext::set($orgA->id);
        $itemA = $this->seedOfBundle($orgA->id, $userA->id, 'A');

        TenantContext::set($orgB->id);
        $this->assertSame(0, OfItem::query()->whereKey($itemA->id)->count());
        $this->assertSame(0, OfAccount::query()->count());
        $this->assertSame(0, OfTransaction::query()->count());

        TenantContext::set($orgA->id);
        $this->assertSame(1, OfItem::query()->count());
        $this->assertSame(1, OfAccount::query()->count());
        $this->assertSame(1, OfTransaction::query()->count());
    }

    public function test_idor_blocks_sync_revoke_account_and_category_of_other_org(): void
    {
        [$userA, $orgA] = $this->makeOwner('Org A');
        [$userB, $orgB] = $this->makeOwner('Org B');

        TenantContext::set($orgA->id);
        $item = $this->seedOfBundle($orgA->id, $userA->id, 'idor');
        $account = OfAccount::query()->firstOrFail();
        $tx = OfTransaction::query()->firstOrFail();

        $this->actingAs($userB);
        $this->withSession(['current_organization_id' => $orgB->id]);
        TenantContext::set($orgB->id);
        $this->get('/hub/connections')->assertOk();

        $token = session()->token();

        $this->post('/hub/connections/'.$item->id.'/sync', ['_token' => $token])->assertNotFound();
        $this->post('/hub/connections/'.$item->id.'/revoke', ['_token' => $token])->assertNotFound();
        $this->get('/hub/connections/accounts/'.$account->id)->assertNotFound();
        $this->patch('/hub/connections/transactions/'.$tx->id.'/category', [
            '_token' => $token,
            'category_suggested' => 'transporte',
        ])->assertNotFound();

        TenantContext::set($orgA->id);
        $this->assertSame(OfItem::STATUS_UPDATED, $item->fresh()->status);
        $this->assertSame('outros', $tx->fresh()->category_suggested);
    }

    public function test_consent_logs_are_tenant_scoped(): void
    {
        [$userA, $orgA] = $this->makeOwner('Org A');
        [, $orgB] = $this->makeOwner('Org B');

        TenantContext::set($orgA->id);
        ConsentLog::query()->create([
            'organization_id' => $orgA->id,
            'user_id' => $userA->id,
            'type' => ConsentLog::TYPE_OPEN_FINANCE,
            'version' => 'of-1.0',
            'accepted_at' => now(),
            'meta' => ['pluggy_item_id' => 'x'],
        ]);

        TenantContext::set($orgB->id);
        $this->assertSame(0, ConsentLog::query()->count());

        TenantContext::set($orgA->id);
        $this->assertSame(1, ConsentLog::query()->count());
    }

    public function test_legal_pages_are_public(): void
    {
        $this->get('/legal/open-finance')
            ->assertOk()
            ->assertSee('somente leitura')
            ->assertSee(config('open_finance.consent_version'));

        $this->get('/legal/privacy')
            ->assertOk()
            ->assertSee('Open Finance')
            ->assertSee(config('open_finance.consent_version'));
    }

    private function seedOfBundle(string $orgId, int $userId, string $suffix): OfItem
    {
        $item = OfItem::query()->create([
            'organization_id' => $orgId,
            'user_id' => $userId,
            'pluggy_item_id' => 'item-'.$suffix,
            'status' => OfItem::STATUS_UPDATED,
            'connector_name' => 'Pluggy Bank '.$suffix,
            'consent_at' => now(),
            'consent_version' => 'of-1.0',
        ]);
        $account = OfAccount::query()->create([
            'organization_id' => $orgId,
            'of_item_id' => $item->id,
            'pluggy_account_id' => 'acc-'.$suffix,
            'name' => 'CC '.$suffix,
            'type' => 'BANK',
            'currency' => 'BRL',
            'balance_cents' => 1000,
            'synced_at' => now(),
        ]);
        OfTransaction::query()->create([
            'organization_id' => $orgId,
            'of_account_id' => $account->id,
            'pluggy_transaction_id' => 'tx-'.$suffix,
            'amount_cents' => 100,
            'currency' => 'BRL',
            'type' => OfTransaction::TYPE_EXPENSE,
            'description' => 'Test '.$suffix,
            'category_suggested' => 'outros',
            'occurred_at' => now(),
        ]);

        return $item;
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function makeOwner(string $orgName): array
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
