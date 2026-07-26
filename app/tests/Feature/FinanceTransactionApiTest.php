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
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FinanceTransactionApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_register_seeds_default_br_categories(): void
    {
        $this->get('/register')->assertOk();

        $response = $this->post('/register', [
            '_token' => session()->token(),
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'organization_name' => 'Casa Ana',
        ]);

        $response->assertRedirect('/hub');

        $org = Organization::query()->where('slug', 'casa-ana')->firstOrFail();
        $this->assertSame(
            count(SeedsDefaultCategories::defaults()),
            Category::query()->withoutGlobalScopes()->where('organization_id', $org->id)->count()
        );
        $this->assertDatabaseHas('categories', [
            'organization_id' => $org->id,
            'slug' => 'moradia',
            'is_system' => true,
        ]);
    }

    public function test_owner_can_crud_transactions_via_api(): void
    {
        [$user, $org] = $this->makeOwner();
        app(SeedsDefaultCategories::class)->handle($org);

        Sanctum::actingAs($user);

        $list = $this->withHeader('X-Organization-Id', $org->id)
            ->getJson('/api/v1/categories');

        $list->assertOk();
        $this->assertGreaterThanOrEqual(8, count($list->json('data')));
        $this->assertTrue(collect($list->json('data'))->contains('slug', 'alimentacao'));

        $categoryId = collect($list->json('data'))->firstWhere('slug', 'alimentacao')['id'];

        $create = $this->withHeader('X-Organization-Id', $org->id)
            ->postJson('/api/v1/transactions', [
                'amount_cents' => 4590,
                'type' => 'expense',
                'category_id' => $categoryId,
                'occurred_at' => '2026-07-25T12:00:00Z',
                'description' => 'Almoço',
            ]);

        $create->assertCreated();
        $create->assertJsonPath('data.amount_cents', 4590);
        $txId = $create->json('data.id');

        $this->withHeader('X-Organization-Id', $org->id)
            ->getJson("/api/v1/transactions/{$txId}")
            ->assertOk()
            ->assertJsonPath('data.description', 'Almoço');

        $this->withHeader('X-Organization-Id', $org->id)
            ->putJson("/api/v1/transactions/{$txId}", [
                'amount_cents' => 5000,
                'description' => 'Almoço atualizado',
            ])
            ->assertOk()
            ->assertJsonPath('data.amount_cents', 5000);

        $this->withHeader('X-Organization-Id', $org->id)
            ->deleteJson("/api/v1/transactions/{$txId}")
            ->assertNoContent();

        $this->assertDatabaseMissing('transactions', ['id' => $txId]);
    }

    public function test_idor_hides_transaction_from_other_organization(): void
    {
        [$userA, $orgA] = $this->makeOwner();
        [$userB, $orgB] = $this->makeOwner();

        $categoryB = Category::factory()->create([
            'organization_id' => $orgB->id,
            'slug' => 'transporte',
            'kind' => Category::KIND_EXPENSE,
        ]);

        $txB = Transaction::factory()->create([
            'organization_id' => $orgB->id,
            'category_id' => $categoryB->id,
            'user_id' => $userB->id,
            'amount_cents' => 9999,
            'description' => 'Segredo B',
        ]);

        Sanctum::actingAs($userA);

        $this->withHeader('X-Organization-Id', $orgA->id)
            ->getJson("/api/v1/transactions/{$txB->id}")
            ->assertNotFound();

        $this->withHeader('X-Organization-Id', $orgA->id)
            ->putJson("/api/v1/transactions/{$txB->id}", ['amount_cents' => 1])
            ->assertNotFound();

        $this->withHeader('X-Organization-Id', $orgA->id)
            ->deleteJson("/api/v1/transactions/{$txB->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('transactions', [
            'id' => $txB->id,
            'amount_cents' => 9999,
        ]);
    }

    public function test_rejects_category_kind_mismatch(): void
    {
        [$user, $org] = $this->makeOwner();
        app(SeedsDefaultCategories::class)->handle($org);

        $incomeCategory = Category::query()->withoutGlobalScopes()
            ->where('organization_id', $org->id)
            ->where('slug', 'salario')
            ->firstOrFail();

        Sanctum::actingAs($user);

        $this->withHeader('X-Organization-Id', $org->id)
            ->postJson('/api/v1/transactions', [
                'amount_cents' => 1000,
                'type' => 'expense',
                'category_id' => $incomeCategory->id,
                'occurred_at' => now()->toIso8601String(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_system_category_cannot_be_deleted(): void
    {
        [$user, $org] = $this->makeOwner();
        app(SeedsDefaultCategories::class)->handle($org);

        $system = Category::query()->withoutGlobalScopes()
            ->where('organization_id', $org->id)
            ->where('slug', 'moradia')
            ->firstOrFail();

        Sanctum::actingAs($user);

        $this->withHeader('X-Organization-Id', $org->id)
            ->deleteJson("/api/v1/categories/{$system->id}")
            ->assertForbidden();
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
