<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Finance\SeedsDefaultCategories;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HubTransactionsUiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_user_can_create_edit_and_filter_transactions_in_hub(): void
    {
        [$user, $org] = $this->makeOwner();
        app(SeedsDefaultCategories::class)->handle($org);

        $category = Category::query()->withoutGlobalScopes()
            ->where('organization_id', $org->id)
            ->where('slug', 'alimentacao')
            ->firstOrFail();

        $this->actingAs($user);
        $this->withSession(['current_organization_id' => $org->id]);
        TenantContext::set($org->id);

        $this->get('/hub/transactions')->assertOk()->assertSee('Lançamentos');
        $this->get('/app/transactions')->assertRedirect('/hub/transactions');

        $create = $this->post('/hub/transactions', [
            '_token' => session()->token(),
            'type' => 'expense',
            'category_id' => $category->id,
            'amount' => '45,90',
            'occurred_at' => '2026-07-20',
            'description' => 'Almoço Hub',
        ]);

        $create->assertRedirect('/hub/transactions');
        $this->assertDatabaseHas('transactions', [
            'organization_id' => $org->id,
            'description' => 'Almoço Hub',
            'amount_cents' => 4590,
            'type' => 'expense',
        ]);

        $tx = Transaction::query()->withoutGlobalScopes()
            ->where('organization_id', $org->id)
            ->where('description', 'Almoço Hub')
            ->firstOrFail();

        $this->get('/hub/transactions?from=2026-07-01&to=2026-07-31')
            ->assertOk()
            ->assertSee('Almoço Hub')
            ->assertSee('45,90');

        $this->get('/hub/transactions?from=2026-08-01&to=2026-08-31')
            ->assertOk()
            ->assertDontSee('Almoço Hub');

        $update = $this->put('/hub/transactions/'.$tx->id, [
            '_token' => session()->token(),
            'type' => 'expense',
            'category_id' => $category->id,
            'amount' => '50,00',
            'occurred_at' => '2026-07-20',
            'description' => 'Almoço atualizado',
        ]);

        $update->assertRedirect('/hub/transactions');
        $this->assertDatabaseHas('transactions', [
            'id' => $tx->id,
            'amount_cents' => 5000,
            'description' => 'Almoço atualizado',
        ]);

        $this->get('/hub/transactions/'.$tx->id.'/edit')
            ->assertOk()
            ->assertSee('Editar');
    }

    public function test_hub_shows_period_totals(): void
    {
        [$user, $org] = $this->makeOwner();
        app(SeedsDefaultCategories::class)->handle($org);

        $expenseCat = Category::query()->withoutGlobalScopes()
            ->where('organization_id', $org->id)
            ->where('slug', 'transporte')
            ->firstOrFail();
        $incomeCat = Category::query()->withoutGlobalScopes()
            ->where('organization_id', $org->id)
            ->where('slug', 'salario')
            ->firstOrFail();

        Transaction::factory()->create([
            'organization_id' => $org->id,
            'category_id' => $expenseCat->id,
            'user_id' => $user->id,
            'type' => 'expense',
            'amount_cents' => 3000,
            'occurred_at' => '2026-07-10 10:00:00',
            'description' => 'Uber',
        ]);
        Transaction::factory()->create([
            'organization_id' => $org->id,
            'category_id' => $incomeCat->id,
            'user_id' => $user->id,
            'type' => 'income',
            'amount_cents' => 10000,
            'occurred_at' => '2026-07-05 10:00:00',
            'description' => 'Salário',
        ]);

        $this->actingAs($user);
        $this->withSession(['current_organization_id' => $org->id]);
        TenantContext::set($org->id);

        $this->get('/hub/transactions?from=2026-07-01&to=2026-07-31')
            ->assertOk()
            ->assertSee('100,00')
            ->assertSee('30,00')
            ->assertSee('70,00');
    }

    public function test_idor_blocks_editing_other_org_transaction(): void
    {
        [$userA, $orgA] = $this->makeOwner();
        [, $orgB] = $this->makeOwner();

        $categoryB = Category::factory()->create([
            'organization_id' => $orgB->id,
            'kind' => Category::KIND_EXPENSE,
        ]);
        $txB = Transaction::factory()->create([
            'organization_id' => $orgB->id,
            'category_id' => $categoryB->id,
            'amount_cents' => 1234,
            'description' => 'Segredo',
        ]);

        $this->actingAs($userA);
        $this->withSession(['current_organization_id' => $orgA->id]);
        TenantContext::set($orgA->id);

        $this->get('/hub/transactions/'.$txB->id.'/edit')->assertNotFound();
        $this->delete('/hub/transactions/'.$txB->id, [
            '_token' => session()->token(),
        ])->assertNotFound();
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function makeOwner(): array
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        Membership::query()->withoutGlobalScopes()->create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'role' => Membership::ROLE_OWNER,
        ]);

        return [$user, $org];
    }
}
