<?php

namespace Tests\Feature;

use App\Models\Membership;
use App\Models\Organization;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WhatsappIdentity;
use App\Services\Finance\Nlu\HandlesWhatsappTransactionText;
use App\Services\Finance\Nlu\HeuristicTransactionExtractor;
use App\Services\Finance\Nlu\TransactionNluEvalSet;
use App\Services\Finance\SeedsDefaultCategories;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FinovaTransactionNluTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_heuristic_eval_set_reaches_eighty_five_percent(): void
    {
        $extractor = new HeuristicTransactionExtractor;
        $cases = TransactionNluEvalSet::cases();
        $this->assertCount(50, $cases);

        $hits = 0;
        $misses = [];

        foreach ($cases as $case) {
            $extracted = $extractor->extract($case['text']);
            if (
                $extracted !== null
                && $extracted->type === $case['type']
                && $extracted->amountCents === $case['amount_cents']
                && $extracted->categorySlug === $case['category_slug']
            ) {
                $hits++;
            } else {
                $misses[] = $case['text'];
            }
        }

        $accuracy = $hits / count($cases);
        $this->assertGreaterThanOrEqual(
            0.85,
            $accuracy,
            sprintf(
                'NLU accuracy %.0f%% (%d/%d) below 85%%. Misses: %s',
                $accuracy * 100,
                $hits,
                count($cases),
                implode(' | ', $misses)
            )
        );
    }

    public function test_rejects_common_false_positives(): void
    {
        $extractor = new HeuristicTransactionExtractor;

        $this->assertNull($extractor->extract('oi'));
        $this->assertNull($extractor->extract('ajuda'));
        $this->assertNull($extractor->extract('quanto gastei essa semana?'));
        $this->assertNull($extractor->extract('resumo do mês'));
        $this->assertNull($extractor->extract('gastei 50 USD no hotel'));
        $this->assertNull($extractor->extract('comprei em 3x de 100'));
        $this->assertNull($extractor->extract('654321'));
        $this->assertNull($extractor->extract(''));
    }

    public function test_linked_user_persists_high_confidence_expense(): void
    {
        [$user, $org] = $this->makeOwner();
        app(SeedsDefaultCategories::class)->handle($org);

        WhatsappIdentity::query()->withoutGlobalScopes()->create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'phone_e164' => '+5511988776655',
            'linked_at' => now(),
        ]);

        Http::fake();

        $result = app(HandlesWhatsappTransactionText::class)
            ->handle('5511988776655', 'gastei 45 no almoço');

        $this->assertTrue($result['handled']);
        $this->assertStringContainsString('45,00', (string) $result['reply']);
        $this->assertDatabaseHas('transactions', [
            'organization_id' => $org->id,
            'amount_cents' => 4500,
            'type' => Transaction::TYPE_EXPENSE,
            'source' => Transaction::SOURCE_FINOVA,
        ]);
    }

    public function test_low_confidence_asks_confirmation_before_saving(): void
    {
        config(['services.finova.nlu_confidence_threshold' => 0.99]);

        [$user, $org] = $this->makeOwner();
        app(SeedsDefaultCategories::class)->handle($org);

        WhatsappIdentity::query()->withoutGlobalScopes()->create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'phone_e164' => '+5511911223344',
            'linked_at' => now(),
        ]);

        $handler = app(HandlesWhatsappTransactionText::class);

        $ask = $handler->handle('5511911223344', 'gastei 20 no almoço');
        $this->assertTrue($ask['handled']);
        $this->assertStringContainsString('Confirma', (string) $ask['reply']);
        $this->assertDatabaseCount('transactions', 0);

        $confirm = $handler->handle('5511911223344', 'sim');
        $this->assertTrue($confirm['handled']);
        $this->assertDatabaseHas('transactions', [
            'organization_id' => $org->id,
            'amount_cents' => 2000,
        ]);
    }

    public function test_unlinked_phone_is_asked_to_link(): void
    {
        $result = app(HandlesWhatsappTransactionText::class)
            ->handle('5511999990000', 'gastei 10 no uber');

        $this->assertTrue($result['handled']);
        $this->assertStringContainsString('vincule', mb_strtolower((string) $result['reply']));
        $this->assertDatabaseCount('transactions', 0);
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
