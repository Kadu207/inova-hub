<?php

namespace Tests\Feature;

use App\Jobs\ProcessWhatsAppMessage;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Models\WhatsappIdentity;
use App\Models\WhatsappOtp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsappWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.verify_token' => 'test-verify-token',
            'services.whatsapp.app_secret' => 'test-app-secret',
        ]);
    }

    public function test_verify_challenge_succeeds_with_valid_token(): void
    {
        $this->get('/webhooks/whatsapp?'.http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'test-verify-token',
            'hub_challenge' => '12345challenge',
        ]))
            ->assertOk()
            ->assertSee('12345challenge');
    }

    public function test_verify_challenge_rejects_invalid_token(): void
    {
        $this->get('/webhooks/whatsapp?'.http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'wrong',
            'hub_challenge' => '12345challenge',
        ]))->assertForbidden();
    }

    public function test_receive_rejects_invalid_signature(): void
    {
        $this->postJson('/webhooks/whatsapp', ['object' => 'whatsapp_business_account'])
            ->assertForbidden();
    }

    public function test_receive_dispatches_job_and_is_idempotent_by_wamid(): void
    {
        Queue::fake();

        $payload = $this->samplePayload('wamid.ABC', '5511999887766', '654321');
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = 'sha256='.hash_hmac('sha256', $body, 'test-app-secret');

        $this->call(
            'POST',
            '/webhooks/whatsapp',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X-Hub-Signature-256' => $signature,
            ],
            $body,
        )->assertOk()->assertSee('EVENT_RECEIVED');

        Queue::assertPushed(ProcessWhatsAppMessage::class, 1);
        $this->assertDatabaseHas('webhook_events', [
            'source' => 'whatsapp',
            'external_id' => 'wamid.ABC',
            'status' => WebhookEvent::STATUS_RECEIVED,
        ]);

        $this->call(
            'POST',
            '/webhooks/whatsapp',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X-Hub-Signature-256' => $signature,
            ],
            $body,
        )->assertOk();

        Queue::assertPushed(ProcessWhatsAppMessage::class, 1);
    }

    public function test_job_links_whatsapp_when_message_contains_otp(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        Membership::query()->withoutGlobalScopes()->create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'role' => Membership::ROLE_OWNER,
        ]);

        WhatsappOtp::query()->withoutGlobalScopes()->create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'phone_e164' => '+5511999887766',
            'code_hash' => Hash::make('654321'),
            'expires_at' => now()->addMinutes(10),
        ]);

        WebhookEvent::query()->create([
            'source' => WebhookEvent::SOURCE_WHATSAPP,
            'external_id' => 'wamid.OTP1',
            'status' => WebhookEvent::STATUS_RECEIVED,
        ]);

        (new ProcessWhatsAppMessage([
            'wamid' => 'wamid.OTP1',
            'from' => '5511999887766',
            'text' => 'Meu codigo e 654321 obrigado',
            'type' => 'text',
        ]))->handle(
            app(\App\Services\WhatsApp\ParsesWhatsappWebhook::class),
            app(\App\Services\WhatsApp\ConsumesWhatsappOtp::class),
            app(\App\Services\WhatsApp\ResolvesFinovaIntent::class),
            app(\App\Services\WhatsApp\SendsWhatsappText::class),
        );

        $this->assertDatabaseHas('whatsapp_identities', [
            'user_id' => $user->id,
            'phone_e164' => '+5511999887766',
        ]);
        $this->assertDatabaseHas('webhook_events', [
            'external_id' => 'wamid.OTP1',
            'status' => WebhookEvent::STATUS_PROCESSED,
        ]);
        $this->assertTrue(
            WhatsappIdentity::query()->withoutGlobalScopes()->whereNull('revoked_at')->exists()
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function samplePayload(string $wamid, string $from, string $text): array
    {
        return [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messages' => [[
                            'from' => $from,
                            'id' => $wamid,
                            'timestamp' => '1710000000',
                            'type' => 'text',
                            'text' => ['body' => $text],
                        ]],
                    ],
                ]],
            ]],
        ];
    }
}
