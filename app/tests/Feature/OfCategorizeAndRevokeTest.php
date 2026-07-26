<?php

namespace Tests\Feature;

use App\Jobs\CategorizeOfTransactions;
use App\Models\Membership;
use App\Models\OfAccount;
use App\Models\OfItem;
use App\Models\OfTransaction;
use App\Models\Organization;
use App\Models\User;
use App\Services\OpenFinance\CategorizesOfTransactions;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OfCategorizeAndRevokeTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_categorizer_suggests_keywords_and_skips_manual(): void
    {
        [$user, $org] = $this->makeOwner();
        TenantContext::set($org->id);

        $item = OfItem::query()->create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'pluggy_item_id' => 'item-cat-1',
            'status' => OfItem::STATUS_UPDATED,
            'connector_name' => 'Pluggy Bank',
            'consent_at' => now(),
        ]);
        $account = OfAccount::query()->create([
            'organization_id' => $org->id,
            'of_item_id' => $item->id,
            'pluggy_account_id' => 'acc-cat-1',
            'name' => 'CC',
            'type' => 'BANK',
            'currency' => 'BRL',
            'balance_cents' => 1000,
            'synced_at' => now(),
        ]);

        $uber = OfTransaction::query()->create([
            'organization_id' => $org->id,
            'of_account_id' => $account->id,
            'pluggy_transaction_id' => 'tx-uber',
            'amount_cents' => 2500,
            'currency' => 'BRL',
            'type' => OfTransaction::TYPE_EXPENSE,
            'description' => 'UBER TRIP SP',
            'category_suggested' => null,
            'category_manual' => false,
            'occurred_at' => now(),
        ]);
        $manual = OfTransaction::query()->create([
            'organization_id' => $org->id,
            'of_account_id' => $account->id,
            'pluggy_transaction_id' => 'tx-manual',
            'amount_cents' => 1000,
            'currency' => 'BRL',
            'type' => OfTransaction::TYPE_EXPENSE,
            'description' => 'UBER OUTRO',
            'category_suggested' => 'lazer',
            'category_manual' => true,
            'occurred_at' => now(),
        ]);

        $updated = app(CategorizesOfTransactions::class)->handle($org->id);

        $this->assertSame(1, $updated);
        $this->assertSame('transporte', $uber->fresh()->category_suggested);
        $this->assertSame('lazer', $manual->fresh()->category_suggested);
    }

    public function test_hub_owner_can_edit_of_category(): void
    {
        [$user, $org] = $this->makeOwner();
        TenantContext::set($org->id);

        $item = OfItem::query()->create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'pluggy_item_id' => 'item-edit-1',
            'status' => OfItem::STATUS_UPDATED,
            'connector_name' => 'Pluggy Bank',
            'consent_at' => now(),
        ]);
        $account = OfAccount::query()->create([
            'organization_id' => $org->id,
            'of_item_id' => $item->id,
            'pluggy_account_id' => 'acc-edit-1',
            'name' => 'CC',
            'type' => 'BANK',
            'currency' => 'BRL',
            'balance_cents' => 1000,
            'synced_at' => now(),
        ]);
        $tx = OfTransaction::query()->create([
            'organization_id' => $org->id,
            'of_account_id' => $account->id,
            'pluggy_transaction_id' => 'tx-edit-1',
            'amount_cents' => 1990,
            'currency' => 'BRL',
            'type' => OfTransaction::TYPE_EXPENSE,
            'description' => 'Café',
            'category_suggested' => 'outros',
            'category_manual' => false,
            'occurred_at' => now(),
        ]);

        $this->actingAs($user);
        $this->withSession(['current_organization_id' => $org->id]);

        $this->get('/hub/connections/accounts/'.$account->id)
            ->assertOk()
            ->assertSee('Categoria')
            ->assertSee('Café');

        $this->from('/hub/connections/accounts/'.$account->id)
            ->patch('/hub/connections/transactions/'.$tx->id.'/category', [
                '_token' => session()->token(),
                'category_suggested' => 'alimentacao',
            ])
            ->assertRedirect(route('hub.connections.accounts.show', $account));

        $tx->refresh();
        $this->assertSame('alimentacao', $tx->category_suggested);
        $this->assertTrue($tx->category_manual);
    }

    public function test_br006_revoke_wipes_of_data_and_idor_blocks_other_org(): void
    {
        config([
            'services.pluggy.client_id' => '11111111-1111-1111-1111-111111111111',
            'services.pluggy.client_secret' => 'secret',
            'services.pluggy.base_url' => 'https://api.pluggy.ai',
        ]);

        Http::fake([
            'api.pluggy.ai/auth' => Http::response(['apiKey' => 'api-key'], 200),
            'api.pluggy.ai/items/*' => Http::response('', 200),
        ]);

        [$userA, $orgA] = $this->makeOwner();
        [$userB, $orgB] = $this->makeOwner('Org B');

        TenantContext::set($orgA->id);
        $item = OfItem::query()->create([
            'organization_id' => $orgA->id,
            'user_id' => $userA->id,
            'pluggy_item_id' => 'item-revoke-1',
            'status' => OfItem::STATUS_UPDATED,
            'connector_name' => 'Pluggy Bank',
            'consent_at' => now(),
        ]);
        $account = OfAccount::query()->create([
            'organization_id' => $orgA->id,
            'of_item_id' => $item->id,
            'pluggy_account_id' => 'acc-revoke-1',
            'name' => 'CC',
            'type' => 'BANK',
            'currency' => 'BRL',
            'balance_cents' => 5000,
            'synced_at' => now(),
        ]);
        OfTransaction::query()->create([
            'organization_id' => $orgA->id,
            'of_account_id' => $account->id,
            'pluggy_transaction_id' => 'tx-revoke-1',
            'amount_cents' => 100,
            'currency' => 'BRL',
            'type' => OfTransaction::TYPE_EXPENSE,
            'description' => 'Pix',
            'occurred_at' => now(),
        ]);

        $this->actingAs($userB);
        $this->withSession(['current_organization_id' => $orgB->id]);
        TenantContext::set($orgB->id);
        $this->get('/hub/connections')->assertOk();

        $this->post('/hub/connections/'.$item->id.'/revoke', [
            '_token' => session()->token(),
        ])->assertNotFound();

        $this->actingAs($userA);
        $this->withSession(['current_organization_id' => $orgA->id]);
        TenantContext::set($orgA->id);
        $this->get('/hub/connections')->assertOk();

        $this->from('/hub/connections')
            ->post('/hub/connections/'.$item->id.'/revoke', [
                '_token' => session()->token(),
            ])
            ->assertRedirect(route('hub.connections.index'));

        $item->refresh();
        $this->assertSame(OfItem::STATUS_DELETED, $item->status);
        $this->assertNotNull($item->consent_revoked_at);
        $this->assertSame(0, OfAccount::query()->where('of_item_id', $item->id)->count());
        $this->assertSame(0, OfTransaction::query()->count());

        $this->get('/hub/connections')
            ->assertOk()
            ->assertDontSee('Pluggy Bank');

        Http::assertSent(function ($request) {
            return $request->method() === 'DELETE'
                && str_contains($request->url(), '/items/item-revoke-1');
        });
    }

    public function test_categorize_job_runs_for_organization(): void
    {
        [$user, $org] = $this->makeOwner();
        TenantContext::set($org->id);

        $item = OfItem::query()->create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'pluggy_item_id' => 'item-job-1',
            'status' => OfItem::STATUS_UPDATED,
            'consent_at' => now(),
        ]);
        $account = OfAccount::query()->create([
            'organization_id' => $org->id,
            'of_item_id' => $item->id,
            'pluggy_account_id' => 'acc-job-1',
            'name' => 'CC',
            'type' => 'BANK',
            'currency' => 'BRL',
            'balance_cents' => 1,
        ]);
        OfTransaction::query()->create([
            'organization_id' => $org->id,
            'of_account_id' => $account->id,
            'pluggy_transaction_id' => 'tx-job-1',
            'amount_cents' => 50,
            'currency' => 'BRL',
            'type' => OfTransaction::TYPE_EXPENSE,
            'description' => 'IFOOD PEDIDO',
            'occurred_at' => now(),
        ]);

        (new CategorizeOfTransactions($org->id))->handle(app(CategorizesOfTransactions::class));

        $this->assertSame(
            'alimentacao',
            OfTransaction::query()->where('pluggy_transaction_id', 'tx-job-1')->value('category_suggested')
        );
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
