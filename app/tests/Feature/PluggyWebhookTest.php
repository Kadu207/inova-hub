<?php

namespace Tests\Feature;

use App\Jobs\SyncPluggyItem;
use App\Models\Membership;
use App\Models\OfAccount;
use App\Models\OfItem;
use App\Models\OfTransaction;
use App\Models\Organization;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PluggyWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_rejects_invalid_webhook_secret_when_configured(): void
    {
        config(['services.pluggy.webhook_secret' => 'expected-secret']);

        $this->postJson('/webhooks/pluggy', [
            'event' => 'item/created',
            'eventId' => 'evt-1',
            'itemId' => 'item-1',
        ], [
            'X-Webhook-Secret' => 'wrong',
        ])->assertUnauthorized();
    }

    public function test_get_webhook_ping_is_reachable(): void
    {
        $this->get('/webhooks/pluggy')
            ->assertOk()
            ->assertSee('Pluggy webhook OK', false);
    }

    public function test_item_created_is_idempotent_and_dispatches_sync_job(): void
    {
        Queue::fake();
        config(['services.pluggy.webhook_secret' => 'secret']);

        $payload = [
            'event' => 'item/created',
            'eventId' => 'evt-created-1',
            'itemId' => 'item-pluggy-abc',
            'clientUserId' => 'org:org-1:user:1',
            'triggeredBy' => 'USER',
        ];

        $this->postJson('/webhooks/pluggy', $payload, [
            'X-Webhook-Secret' => 'secret',
        ])->assertOk()->assertSee('EVENT_RECEIVED');

        $this->assertDatabaseHas('webhook_events', [
            'source' => WebhookEvent::SOURCE_PLUGGY,
            'external_id' => 'evt-created-1',
            'status' => WebhookEvent::STATUS_RECEIVED,
        ]);

        Queue::assertPushed(SyncPluggyItem::class, function (SyncPluggyItem $job) {
            return $job->payload['itemId'] === 'item-pluggy-abc'
                && $job->payload['event'] === 'item/created';
        });

        $this->postJson('/webhooks/pluggy', $payload, [
            'X-Webhook-Secret' => 'secret',
        ])->assertOk();

        $this->assertSame(1, WebhookEvent::query()->where('external_id', 'evt-created-1')->count());
        Queue::assertPushed(SyncPluggyItem::class, 1);
    }

    public function test_sync_job_persists_accounts_and_transactions(): void
    {
        [$user, $org] = $this->makeOwner();
        $clientUserId = sprintf('org:%s:user:%s', $org->id, $user->id);

        config([
            'services.pluggy.client_id' => '11111111-1111-1111-1111-111111111111',
            'services.pluggy.client_secret' => 'secret',
            'services.pluggy.base_url' => 'https://api.pluggy.ai',
        ]);

        Http::fake([
            'api.pluggy.ai/auth' => Http::response(['apiKey' => 'api-key'], 200),
            'api.pluggy.ai/items/item-sync-1' => Http::response([
                'id' => 'item-sync-1',
                'status' => 'UPDATED',
                'clientUserId' => $clientUserId,
                'connector' => ['name' => 'Pluggy Bank'],
            ], 200),
            'api.pluggy.ai/accounts*' => Http::response([
                'results' => [[
                    'id' => 'acc-1',
                    'name' => 'Conta Corrente',
                    'type' => 'BANK',
                    'subtype' => 'CHECKING_ACCOUNT',
                    'number' => '123',
                    'currencyCode' => 'BRL',
                    'balance' => 150.55,
                ]],
            ], 200),
            'api.pluggy.ai/transactions*' => Http::response([
                'results' => [[
                    'id' => 'tx-1',
                    'accountId' => 'acc-1',
                    'amount' => -45.90,
                    'currencyCode' => 'BRL',
                    'description' => 'Padaria',
                    'category' => 'Food',
                    'date' => '2026-07-20T12:00:00.000Z',
                ]],
            ], 200),
        ]);

        WebhookEvent::query()->create([
            'source' => WebhookEvent::SOURCE_PLUGGY,
            'external_id' => 'evt-sync-1',
            'status' => WebhookEvent::STATUS_RECEIVED,
        ]);

        $job = new SyncPluggyItem([
            'eventId' => 'evt-sync-1',
            'event' => 'item/created',
            'itemId' => 'item-sync-1',
            'clientUserId' => $clientUserId,
        ]);
        app()->call([$job, 'handle']);

        $this->assertDatabaseHas('of_items', [
            'organization_id' => $org->id,
            'pluggy_item_id' => 'item-sync-1',
            'connector_name' => 'Pluggy Bank',
        ]);
        $this->assertDatabaseHas('of_accounts', [
            'organization_id' => $org->id,
            'pluggy_account_id' => 'acc-1',
            'balance_cents' => 15055,
        ]);
        $this->assertDatabaseHas('of_transactions', [
            'organization_id' => $org->id,
            'pluggy_transaction_id' => 'tx-1',
            'amount_cents' => 4590,
            'type' => OfTransaction::TYPE_EXPENSE,
            'description' => 'Padaria',
        ]);
        $this->assertDatabaseHas('webhook_events', [
            'external_id' => 'evt-sync-1',
            'status' => WebhookEvent::STATUS_PROCESSED,
        ]);

        TenantContext::set($org->id);
        $this->assertSame(1, OfItem::query()->count());
        $this->assertSame(1, OfAccount::query()->count());
        $this->assertSame(1, OfTransaction::query()->count());
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
