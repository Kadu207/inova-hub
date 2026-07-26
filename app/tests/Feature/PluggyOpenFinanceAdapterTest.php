<?php

namespace Tests\Feature;

use App\Contracts\OpenFinance\OpenFinanceProvider;
use App\Services\OpenFinance\PluggyOpenFinanceProvider;
use Illuminate\Support\Facades\Http;
use ReflectionClass;
use RuntimeException;
use Tests\TestCase;

class PluggyOpenFinanceAdapterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.pluggy.client_id' => '11111111-1111-1111-1111-111111111111',
            'services.pluggy.client_secret' => 'test-secret',
            'services.pluggy.base_url' => 'https://api.pluggy.ai',
        ]);
    }

    public function test_provider_is_bound_to_pluggy(): void
    {
        $this->assertInstanceOf(PluggyOpenFinanceProvider::class, app(OpenFinanceProvider::class));
    }

    public function test_authenticate_exchanges_client_credentials_for_api_key(): void
    {
        Http::fake([
            'api.pluggy.ai/auth' => Http::response(['apiKey' => 'pluggy-api-key-xyz'], 200),
        ]);

        $key = app(OpenFinanceProvider::class)->authenticate();

        $this->assertSame('pluggy-api-key-xyz', $key);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.pluggy.ai/auth'
                && $request['clientId'] === '11111111-1111-1111-1111-111111111111'
                && $request['clientSecret'] === 'test-secret';
        });
    }

    public function test_list_connectors_uses_api_key_header(): void
    {
        Http::fake([
            'api.pluggy.ai/auth' => Http::response(['apiKey' => 'pluggy-api-key-xyz'], 200),
            'api.pluggy.ai/connectors*' => Http::response([
                'results' => [
                    [
                        'id' => 201,
                        'name' => 'Itaú',
                        'type' => 'PERSONAL_BANK',
                        'country' => 'BR',
                        'primaryColor' => '#EC7000',
                    ],
                    [
                        'id' => 212,
                        'name' => 'Pluggy Bank',
                        'type' => 'PERSONAL_BANK',
                        'country' => 'BR',
                    ],
                ],
            ], 200),
        ]);

        $connectors = app(OpenFinanceProvider::class)->listConnectors();

        $this->assertCount(2, $connectors);
        $this->assertSame(201, $connectors[0]['id']);
        $this->assertSame('Itaú', $connectors[0]['name']);
        $this->assertSame('BR', $connectors[0]['country']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/connectors')
                && $request->hasHeader('X-API-KEY', 'pluggy-api-key-xyz');
        });
    }

    public function test_missing_credentials_fail_fast(): void
    {
        config([
            'services.pluggy.client_id' => '',
            'services.pluggy.client_secret' => '',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PLUGGY_CLIENT_ID');

        app(OpenFinanceProvider::class)->authenticate();
    }

    public function test_br005_read_only_surface_has_no_payment_methods(): void
    {
        $reflection = new ReflectionClass(OpenFinanceProvider::class);
        $methods = array_map(fn ($m) => $m->getName(), $reflection->getMethods());

        foreach (['initiatePayment', 'createPayment', 'pay', 'transfer', 'pix'] as $forbidden) {
            $this->assertNotContains($forbidden, $methods);
        }

        $this->assertContains('authenticate', $methods);
        $this->assertContains('listConnectors', $methods);
        $this->assertContains('createConnectToken', $methods);
        $this->assertContains('getItem', $methods);
        $this->assertContains('listAccounts', $methods);
        $this->assertContains('listTransactions', $methods);
    }

    public function test_artisan_pluggy_connectors_lists_sandbox_catalog(): void
    {
        Http::fake([
            'api.pluggy.ai/auth' => Http::response(['apiKey' => 'k'], 200),
            'api.pluggy.ai/connectors*' => Http::response([
                'results' => [
                    ['id' => 1, 'name' => 'Banco Demo', 'type' => 'PERSONAL_BANK', 'country' => 'BR'],
                ],
            ], 200),
        ]);

        $this->artisan('pluggy:connectors', ['--limit' => 5])
            ->expectsOutputToContain('Pluggy OK')
            ->expectsOutputToContain('Banco Demo')
            ->assertSuccessful();
    }

    public function test_create_connect_token_returns_access_token(): void
    {
        Http::fake([
            'api.pluggy.ai/auth' => Http::response(['apiKey' => 'api-key'], 200),
            'api.pluggy.ai/connect_token' => Http::response(['accessToken' => 'ctok'], 200),
        ]);

        $token = app(OpenFinanceProvider::class)->createConnectToken([
            'clientUserId' => 'org:1:user:2',
        ]);

        $this->assertSame('ctok', $token);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/connect_token')
                && ($request['options']['clientUserId'] ?? null) === 'org:1:user:2';
        });
    }
}
