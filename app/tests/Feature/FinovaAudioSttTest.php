<?php

namespace Tests\Feature;

use App\Jobs\ProcessWhatsAppMessage;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Models\WhatsappIdentity;
use App\Services\Finance\SeedsDefaultCategories;
use App\Services\WhatsApp\FinovaCopy;
use App\Services\WhatsApp\ParsesWhatsappWebhook;
use App\Services\WhatsApp\TranscribesWhatsappAudio;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FinovaAudioSttTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_parser_extracts_audio_media_id(): void
    {
        $messages = app(ParsesWhatsappWebhook::class)->extractInboundMessages([
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messages' => [[
                            'id' => 'wamid.AUDIO1',
                            'from' => '5511999887766',
                            'type' => 'audio',
                            'audio' => ['id' => 'media-123', 'mime_type' => 'audio/ogg'],
                        ]],
                    ],
                ]],
            ]],
        ]);

        $this->assertCount(1, $messages);
        $this->assertSame('audio', $messages[0]['type']);
        $this->assertSame('media-123', $messages[0]['media_id']);
        $this->assertSame('', $messages[0]['text']);
    }

    public function test_five_audio_transcriptions_create_correct_transactions(): void
    {
        [$user, $org] = $this->makeOwner();
        app(SeedsDefaultCategories::class)->handle($org);

        WhatsappIdentity::query()->withoutGlobalScopes()->create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'phone_e164' => '+5511988001122',
            'linked_at' => now(),
        ]);

        $this->app->instance(
            \App\Services\Finance\Nlu\TransactionExtractor::class,
            new \App\Services\Finance\Nlu\HeuristicTransactionExtractor
        );

        $cases = [
            'm1' => ['transcript' => 'gastei 45 no almoço', 'cents' => 4500],
            'm2' => ['transcript' => 'paguei 120 de uber', 'cents' => 12000],
            'm3' => ['transcript' => 'recebi 3500 de salário', 'cents' => 350000],
            'm4' => ['transcript' => 'farmácia 67 reais', 'cents' => 6700],
            'm5' => ['transcript' => 'gastei 80 no cinema', 'cents' => 8000],
        ];

        config([
            'services.whatsapp.token' => 'wa-token',
            'services.whatsapp.phone_number_id' => 'phone-1',
            'services.finova.nlu_confidence_threshold' => 0.5,
        ]);

        Http::fake([
            'graph.facebook.com/*/messages' => Http::response(['messages' => [['id' => 'wamid.out']]], 200),
        ]);

        $downloads = \Mockery::mock(\App\Services\WhatsApp\DownloadsWhatsappMedia::class);
        $downloads->shouldReceive('handle')->andReturn(['bytes' => 'ogg', 'mime' => 'audio/ogg']);
        $this->app->instance(\App\Services\WhatsApp\DownloadsWhatsappMedia::class, $downloads);

        $transcriptByMedia = [];
        foreach ($cases as $mediaId => $case) {
            $transcriptByMedia[$mediaId] = $case['transcript'];
        }

        $activeMedia = null;
        $transcribes = \Mockery::mock(\App\Services\WhatsApp\TranscribesWhatsappAudio::class);
        $transcribes->shouldReceive('handle')->andReturnUsing(function () use (&$activeMedia, $transcriptByMedia) {
            return $transcriptByMedia[$activeMedia];
        });
        $this->app->instance(\App\Services\WhatsApp\TranscribesWhatsappAudio::class, $transcribes);

        foreach ($cases as $mediaId => $case) {
            $activeMedia = $mediaId;
            $wamid = 'wamid.AUDIO.'.$mediaId;
            WebhookEvent::query()->create([
                'source' => WebhookEvent::SOURCE_WHATSAPP,
                'external_id' => $wamid,
                'status' => WebhookEvent::STATUS_RECEIVED,
            ]);

            $job = new ProcessWhatsAppMessage([
                'wamid' => $wamid,
                'from' => '5511988001122',
                'text' => '',
                'type' => 'audio',
                'media_id' => $mediaId,
            ]);
            app()->call([$job, 'handle']);

            $this->assertDatabaseHas('transactions', [
                'organization_id' => $org->id,
                'amount_cents' => $case['cents'],
                'source' => Transaction::SOURCE_FINOVA,
            ]);
        }

        $this->assertSame(5, Transaction::query()->withoutGlobalScopes()->where('organization_id', $org->id)->count());
    }

    public function test_audio_without_stt_key_replies_helpfully(): void
    {
        config([
            'services.whatsapp.token' => 'wa-token',
            'services.whatsapp.phone_number_id' => 'phone-1',
            'services.llm.api_key' => '',
            'services.llm.stt_base_url' => '',
        ]);

        Http::fake([
            'graph.facebook.com/v21.0/media-x' => Http::response([
                'url' => 'https://lookaside.fbsbx.com/media-x',
                'mime_type' => 'audio/ogg',
            ], 200),
            'lookaside.fbsbx.com/*' => Http::response('FAKE', 200),
            'graph.facebook.com/v21.0/phone-1/messages' => Http::response([
                'messages' => [['id' => 'wamid.out']],
            ], 200),
        ]);

        WebhookEvent::query()->create([
            'source' => WebhookEvent::SOURCE_WHATSAPP,
            'external_id' => 'wamid.AUDIO.NOSTT',
            'status' => WebhookEvent::STATUS_RECEIVED,
        ]);

        $job = new ProcessWhatsAppMessage([
            'wamid' => 'wamid.AUDIO.NOSTT',
            'from' => '5511988001122',
            'text' => '',
            'type' => 'audio',
            'media_id' => 'media-x',
        ]);
        app()->call([$job, 'handle']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/messages')
                && ($request['text']['body'] ?? '') === FinovaCopy::audioNeedsStt();
        });

        $this->assertDatabaseHas('webhook_events', [
            'external_id' => 'wamid.AUDIO.NOSTT',
            'status' => WebhookEvent::STATUS_FAILED,
        ]);
    }

    public function test_temp_audio_file_is_removed_after_stt(): void
    {
        config([
            'services.llm.api_key' => 'sk-test',
            'services.llm.stt_base_url' => 'https://api.openai.com/v1',
            'services.llm.stt_model' => 'whisper-1',
        ]);

        Http::fake([
            'api.openai.com/v1/audio/transcriptions' => Http::response(['text' => 'gastei 10 no uber'], 200),
        ]);

        $before = glob(sys_get_temp_dir().'/finova_audio_*') ?: [];

        $text = app(TranscribesWhatsappAudio::class)->handle('ogg-bytes', 'audio/ogg');
        $this->assertSame('gastei 10 no uber', $text);

        $after = glob(sys_get_temp_dir().'/finova_audio_*') ?: [];
        $this->assertSame($before, $after);
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
