<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WhatsappIdentity;
use App\Services\Finance\Nlu\HandlesWhatsappTransactionText;
use App\Services\Finance\Query\ResolvesTransactionQuery;
use App\Services\Finance\Query\SummarizesTransactionsForPeriod;
use App\Services\Finance\Query\TransactionPeriod;
use App\Services\Finance\SeedsDefaultCategories;
use App\Support\Tenancy\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinovaTransactionQueryTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_resolver_detects_today_week_month_queries(): void
    {
        $resolver = new ResolvesTransactionQuery;

        $this->assertSame(TransactionPeriod::Week, $resolver->handle('quanto gastei essa semana?'));
        $this->assertSame(TransactionPeriod::Today, $resolver->handle('quanto gastei hoje'));
        $this->assertSame(TransactionPeriod::Month, $resolver->handle('resumo do mês'));
        $this->assertNull($resolver->handle('gastei 45 no almoço'));
        $this->assertNull($resolver->handle('oi'));
    }

    public function test_whatsapp_week_query_matches_hub_summary(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-22 15:00:00', 'UTC'));

        [$user, $org] = $this->makeOwner();
        app(SeedsDefaultCategories::class)->handle($org);

        WhatsappIdentity::query()->withoutGlobalScopes()->create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'phone_e164' => '+5511977001122',
            'linked_at' => now(),
        ]);

        $alimentacao = Category::query()->withoutGlobalScopes()
            ->where('organization_id', $org->id)
            ->where('slug', 'alimentacao')
            ->firstOrFail();
        $transporte = Category::query()->withoutGlobalScopes()
            ->where('organization_id', $org->id)
            ->where('slug', 'transporte')
            ->firstOrFail();
        $salario = Category::query()->withoutGlobalScopes()
            ->where('organization_id', $org->id)
            ->where('slug', 'salario')
            ->firstOrFail();

        TenantContext::set($org->id);

        Transaction::factory()->create([
            'organization_id' => $org->id,
            'category_id' => $alimentacao->id,
            'user_id' => $user->id,
            'type' => 'expense',
            'amount_cents' => 4500,
            'occurred_at' => '2026-07-21 12:00:00',
            'description' => 'Almoço',
        ]);
        Transaction::factory()->create([
            'organization_id' => $org->id,
            'category_id' => $transporte->id,
            'user_id' => $user->id,
            'type' => 'expense',
            'amount_cents' => 3000,
            'occurred_at' => '2026-07-22 09:00:00',
            'description' => 'Uber',
        ]);
        Transaction::factory()->create([
            'organization_id' => $org->id,
            'category_id' => $salario->id,
            'user_id' => $user->id,
            'type' => 'income',
            'amount_cents' => 100000,
            'occurred_at' => '2026-07-20 08:00:00',
            'description' => 'Salário',
        ]);
        // fora da semana
        Transaction::factory()->create([
            'organization_id' => $org->id,
            'category_id' => $alimentacao->id,
            'user_id' => $user->id,
            'type' => 'expense',
            'amount_cents' => 9999,
            'occurred_at' => '2026-07-10 12:00:00',
            'description' => 'Antigo',
        ]);

        $summary = app(SummarizesTransactionsForPeriod::class)->handle(TransactionPeriod::Week);
        $this->assertSame(7500, $summary['expense_cents']);
        $this->assertSame(100000, $summary['income_cents']);
        $this->assertSame(92500, $summary['net_cents']);

        $reply = app(HandlesWhatsappTransactionText::class)
            ->handle('5511977001122', 'quanto gastei essa semana?');

        $this->assertTrue($reply['handled']);
        $this->assertStringContainsString('75,00', (string) $reply['reply']);
        $this->assertStringContainsString('1.000,00', (string) $reply['reply']);

        $this->actingAs($user);
        $this->withSession(['current_organization_id' => $org->id]);

        $this->get('/hub')
            ->assertOk()
            ->assertSee('desta semana')
            ->assertSee('75,00')
            ->assertSee('1.000,00');

        Carbon::setTestNow();
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
