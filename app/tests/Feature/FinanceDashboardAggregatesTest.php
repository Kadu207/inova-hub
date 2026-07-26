<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Finance\Query\BuildsFinanceDashboardAggregates;
use App\Services\Finance\SeedsDefaultCategories;
use App\Support\Tenancy\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FinanceDashboardAggregatesTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::clear();
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_aggregates_match_transaction_sums_and_hub_charts(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-25 12:00:00', 'UTC'));

        [$user, $org] = $this->makeOwner();
        app(SeedsDefaultCategories::class)->handle($org);

        $alimentacao = Category::query()->withoutGlobalScopes()
            ->where('organization_id', $org->id)
            ->where('slug', 'alimentacao')
            ->firstOrFail();
        $transporte = Category::query()->withoutGlobalScopes()
            ->where('organization_id', $org->id)
            ->where('slug', 'transporte')
            ->firstOrFail();

        TenantContext::set($org->id);

        Transaction::factory()->create([
            'organization_id' => $org->id,
            'category_id' => $alimentacao->id,
            'user_id' => $user->id,
            'type' => 'expense',
            'amount_cents' => 4000,
            'occurred_at' => '2026-07-20 10:00:00',
        ]);
        Transaction::factory()->create([
            'organization_id' => $org->id,
            'category_id' => $transporte->id,
            'user_id' => $user->id,
            'type' => 'expense',
            'amount_cents' => 6000,
            'occurred_at' => '2026-07-22 10:00:00',
        ]);
        Transaction::factory()->create([
            'organization_id' => $org->id,
            'category_id' => $alimentacao->id,
            'user_id' => $user->id,
            'type' => 'expense',
            'amount_cents' => 2000,
            'occurred_at' => '2026-07-22 18:00:00',
        ]);

        $data = app(BuildsFinanceDashboardAggregates::class)->handle(days: 30);

        $this->assertSame(12000, $data['expense_cents']);
        $byName = collect($data['by_category'])->keyBy('name');
        $this->assertSame(6000, $byName['Transporte']['amount_cents']);
        $this->assertSame(6000, $byName['Alimentação']['amount_cents']);
        $this->assertSame(50.0, $byName['Transporte']['pct']);
        $this->assertCount(30, $data['daily']);

        $day = collect($data['daily'])->firstWhere('date', '2026-07-22');
        $this->assertSame(8000, $day['expense_cents']);

        Sanctum::actingAs($user);
        $api = $this->withHeader('X-Organization-Id', $org->id)
            ->getJson('/api/v1/aggregates?days=30')
            ->assertOk()
            ->assertJsonPath('data.expense_cents', 12000);

        $apiCategories = collect($api->json('data.by_category'))->keyBy('name');
        $this->assertSame(6000, $apiCategories['Transporte']['amount_cents']);

        $this->withHeader('X-Organization-Id', $org->id)
            ->getJson('/api/v1/aggregates/by-category')
            ->assertOk()
            ->assertJsonPath('data.expense_cents', 12000);

        $this->withHeader('X-Organization-Id', $org->id)
            ->getJson('/api/v1/aggregates/daily')
            ->assertOk()
            ->assertJsonCount(30, 'data.daily');

        $this->actingAs($user);
        $this->withSession(['current_organization_id' => $org->id]);

        $this->get('/hub')
            ->assertOk()
            ->assertSee('Despesas por categoria')
            ->assertSee('Evolução de despesas')
            ->assertSee('Transporte')
            ->assertSee('120,00');
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
