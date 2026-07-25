<?php

namespace Tests\Feature;

use App\Jobs\ProcessWhatsAppMessage;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Models\WhatsappOtp;
use App\Services\WhatsApp\ConsumesWhatsappOtp;
use App\Services\WhatsApp\FinovaCopy;
use App\Services\WhatsApp\ParsesWhatsappWebhook;
use App\Services\WhatsApp\ResolvesFinovaIntent;
use App\Services\WhatsApp\SendsWhatsappText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FinovaReplyTest extends TestCase
{
    use RefreshDatabase;

    public function test_intent_router_resolves_oi_and_ajuda(): void
    {
        $router = new ResolvesFinovaIntent;

        $this->assertSame(FinovaCopy::greeting(), $router->reply($router->handle('Oi Finova')));
        $this->assertSame(FinovaCopy::help(), $router->reply($router->handle('ajuda por favor')));
        $this->assertSame(FinovaCopy::fallback(), $router->reply($router->handle('blablabla')));
    }

    public function test_job_replies_greeting_for_oi(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.out']]], 200),
        ]);

        config([
            'services.whatsapp.token' => 'token-test',
            'services.whatsapp.phone_number_id' => '123456',
        ]);

        WebhookEvent::query()->create([
            'source' => WebhookEvent::SOURCE_WHATSAPP,
            'external_id' => 'wamid.OI1',
            'status' => WebhookEvent::STATUS_RECEIVED,
        ]);

        (new ProcessWhatsAppMessage([
            'wamid' => 'wamid.OI1',
            'from' => '5511999887766',
            'text' => 'oi',
            'type' => 'text',
        ]))->handle(
            app(ParsesWhatsappWebhook::class),
            app(ConsumesWhatsappOtp::class),
            app(ResolvesFinovaIntent::class),
            app(SendsWhatsappText::class),
        );

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/messages')
                && ($request['text']['body'] ?? '') === FinovaCopy::greeting();
        });

        $this->assertDatabaseHas('webhook_events', [
            'external_id' => 'wamid.OI1',
            'status' => WebhookEvent::STATUS_PROCESSED,
        ]);
    }

    public function test_job_replies_help_for_ajuda(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.out']]], 200),
        ]);

        config([
            'services.whatsapp.token' => 'token-test',
            'services.whatsapp.phone_number_id' => '123456',
        ]);

        WebhookEvent::query()->create([
            'source' => WebhookEvent::SOURCE_WHATSAPP,
            'external_id' => 'wamid.HELP1',
            'status' => WebhookEvent::STATUS_RECEIVED,
        ]);

        (new ProcessWhatsAppMessage([
            'wamid' => 'wamid.HELP1',
            'from' => '5511999887766',
            'text' => 'ajuda',
            'type' => 'text',
        ]))->handle(
            app(ParsesWhatsappWebhook::class),
            app(ConsumesWhatsappOtp::class),
            app(ResolvesFinovaIntent::class),
            app(SendsWhatsappText::class),
        );

        Http::assertSent(function ($request) {
            return ($request['text']['body'] ?? '') === FinovaCopy::help();
        });
    }

    public function test_job_links_otp_and_sends_confirmation(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.out']]], 200),
        ]);

        config([
            'services.whatsapp.token' => 'token-test',
            'services.whatsapp.phone_number_id' => '123456',
        ]);

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
            'external_id' => 'wamid.OTP2',
            'status' => WebhookEvent::STATUS_RECEIVED,
        ]);

        (new ProcessWhatsAppMessage([
            'wamid' => 'wamid.OTP2',
            'from' => '5511999887766',
            'text' => '654321',
            'type' => 'text',
        ]))->handle(
            app(ParsesWhatsappWebhook::class),
            app(ConsumesWhatsappOtp::class),
            app(ResolvesFinovaIntent::class),
            app(SendsWhatsappText::class),
        );

        $this->assertDatabaseHas('whatsapp_identities', [
            'user_id' => $user->id,
            'phone_e164' => '+5511999887766',
        ]);

        Http::assertSent(function ($request) {
            return ($request['text']['body'] ?? '') === FinovaCopy::otpLinked();
        });
    }
}
