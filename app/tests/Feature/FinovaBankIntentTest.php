<?php

namespace Tests\Feature;

use App\Jobs\ProcessWhatsAppMessage;
use App\Models\Membership;
use App\Models\OfAccount;
use App\Models\OfItem;
use App\Models\OfTransaction;
use App\Models\Organization;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Models\WhatsappIdentity;
use App\Services\Finance\Nlu\HandlesWhatsappTransactionText;
use App\Services\OpenFinance\BankIntent;
use App\Services\OpenFinance\BankIntentEvalSet;
use App\Services\OpenFinance\ResolvesBankQuery;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FinovaBankIntentTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_eval_set_resolves_bank_intents(): void
    {
        $resolver = new ResolvesBankQuery;
        $cases = BankIntentEvalSet::cases();
        $this->assertGreaterThanOrEqual(20, count($cases));

        $hits = 0;
        $misses = [];
        foreach ($cases as $case) {
            $intent = $resolver->handle($case['text']);
            if ($intent !== null && $intent->value === $case['intent']) {
                $hits++;
            } else {
                $misses[] = $case['text'];
            }
        }

        $this->assertSame(
            count($cases),
            $hits,
            'Misses: '.implode(' | ', $misses)
        );

        foreach (BankIntentEvalSet::negatives() as $text) {
            $this->assertNull($resolver->handle($text), "False positive: {$text}");
        }
    }

    public function test_qual_meu_saldo_replies_with_of_balances(): void
    {
        [$user, $org] = $this->makeOwnerLinked('+5511999001122');

        TenantContext::set($org->id);
        $item = OfItem::query()->create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'pluggy_item_id' => 'item-bank-1',
            'status' => OfItem::STATUS_UPDATED,
            'connector_name' => 'Pluggy Bank',
            'consent_at' => now(),
        ]);
        OfAccount::query()->create([
            'organization_id' => $org->id,
            'of_item_id' => $item->id,
            'pluggy_account_id' => 'acc-bank-1',
            'name' => 'Conta Corrente',
            'type' => 'BANK',
            'subtype' => 'CHECKING_ACCOUNT',
            'currency' => 'BRL',
            'balance_cents' => 123456,
            'synced_at' => now(),
        ]);
        TenantContext::clear();

        $result = app(HandlesWhatsappTransactionText::class)
            ->handle('5511999001122', 'Qual meu saldo?');

        $this->assertTrue($result['handled']);
        $this->assertStringContainsString('1.234,56', (string) $result['reply']);
        $this->assertStringContainsString('Conta Corrente', (string) $result['reply']);
        $this->assertStringContainsString('Open Finance', (string) $result['reply']);
    }

    public function test_extrato_and_cards_intents(): void
    {
        [$user, $org] = $this->makeOwnerLinked('+5511999002233');

        TenantContext::set($org->id);
        $item = OfItem::query()->create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'pluggy_item_id' => 'item-bank-2',
            'status' => OfItem::STATUS_UPDATED,
            'connector_name' => 'Pluggy Bank',
            'consent_at' => now(),
        ]);
        $checking = OfAccount::query()->create([
            'organization_id' => $org->id,
            'of_item_id' => $item->id,
            'pluggy_account_id' => 'acc-check',
            'name' => 'Corrente',
            'type' => 'BANK',
            'balance_cents' => 10000,
            'synced_at' => now(),
        ]);
        OfAccount::query()->create([
            'organization_id' => $org->id,
            'of_item_id' => $item->id,
            'pluggy_account_id' => 'acc-card',
            'name' => 'Visa Platinum',
            'type' => 'CREDIT',
            'subtype' => 'CREDIT_CARD',
            'balance_cents' => -50000,
            'synced_at' => now(),
        ]);
        OfTransaction::query()->create([
            'organization_id' => $org->id,
            'of_account_id' => $checking->id,
            'pluggy_transaction_id' => 'tx-of-1',
            'amount_cents' => 2590,
            'currency' => 'BRL',
            'type' => OfTransaction::TYPE_EXPENSE,
            'description' => 'Mercado OF',
            'occurred_at' => now()->subHour(),
        ]);
        TenantContext::clear();

        $handler = app(HandlesWhatsappTransactionText::class);

        $statement = $handler->handle('5511999002233', 'ver extrato');
        $this->assertTrue($statement['handled']);
        $this->assertStringContainsString('Mercado OF', (string) $statement['reply']);
        $this->assertStringContainsString('25,90', (string) $statement['reply']);

        $cards = $handler->handle('5511999002233', 'meus cartões');
        $this->assertTrue($cards['handled']);
        $this->assertStringContainsString('Visa Platinum', (string) $cards['reply']);
        $this->assertStringContainsString('500,00', (string) $cards['reply']);
    }

    public function test_saldo_without_of_connection_guides_to_hub(): void
    {
        $this->makeOwnerLinked('+5511999003344');

        $result = app(HandlesWhatsappTransactionText::class)
            ->handle('5511999003344', 'qual meu saldo');

        $this->assertTrue($result['handled']);
        $this->assertStringContainsString('Conectar banco', (string) $result['reply']);
    }

    public function test_whatsapp_job_replies_balance_for_qual_meu_saldo(): void
    {
        [$user, $org] = $this->makeOwnerLinked('+5511999004455');

        TenantContext::set($org->id);
        $item = OfItem::query()->create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'pluggy_item_id' => 'item-job-1',
            'status' => OfItem::STATUS_UPDATED,
            'connector_name' => 'Pluggy Bank',
            'consent_at' => now(),
        ]);
        OfAccount::query()->create([
            'organization_id' => $org->id,
            'of_item_id' => $item->id,
            'pluggy_account_id' => 'acc-job-1',
            'name' => 'Poupança',
            'type' => 'BANK',
            'balance_cents' => 9900,
            'synced_at' => now(),
        ]);
        TenantContext::clear();

        config([
            'services.whatsapp.token' => 'token-test',
            'services.whatsapp.phone_number_id' => 'phone-1',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.out']]], 200),
        ]);

        WebhookEvent::query()->create([
            'source' => WebhookEvent::SOURCE_WHATSAPP,
            'external_id' => 'wamid.BALANCE1',
            'status' => WebhookEvent::STATUS_RECEIVED,
        ]);

        $job = new ProcessWhatsAppMessage([
            'wamid' => 'wamid.BALANCE1',
            'from' => '5511999004455',
            'text' => 'Qual meu saldo?',
            'type' => 'text',
        ]);
        app()->call([$job, 'handle']);

        Http::assertSent(function ($request) {
            $body = (string) ($request['text']['body'] ?? '');

            return str_contains($request->url(), '/messages')
                && str_contains($body, '99,00')
                && str_contains($body, 'Poupança');
        });
    }

    public function test_week_spend_query_still_not_bank_intent(): void
    {
        $this->assertNull((new ResolvesBankQuery)->handle('quanto gastei essa semana?'));
        $this->assertSame(BankIntent::Balance, (new ResolvesBankQuery)->handle('Qual meu saldo?'));
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function makeOwnerLinked(string $phoneE164): array
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        Membership::query()->withoutGlobalScopes()->create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'role' => Membership::ROLE_OWNER,
        ]);
        WhatsappIdentity::query()->withoutGlobalScopes()->create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'phone_e164' => $phoneE164,
            'linked_at' => now(),
        ]);

        return [$user, $org];
    }
}
