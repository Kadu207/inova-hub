<?php

namespace Tests\Feature;

use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use App\Models\WhatsappIdentity;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HubHomeWhatsappStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_home_shows_disconnected_finova_status(): void
    {
        [$user, $org] = $this->makeOwner();

        $this->actingAs($user);
        $this->withSession(['current_organization_id' => $org->id]);
        TenantContext::set($org->id);

        $this->get('/hub')
            ->assertOk()
            ->assertSee('Finova')
            ->assertSee('desconectado')
            ->assertSee('Vincular / reenviar OTP')
            ->assertSee(route('hub.whatsapp', absolute: false), false);
    }

    public function test_home_shows_connected_finova_status_with_phone(): void
    {
        [$user, $org] = $this->makeOwner();

        WhatsappIdentity::query()->withoutGlobalScopes()->create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'phone_e164' => '+5511988887777',
            'linked_at' => now(),
        ]);

        $this->actingAs($user);
        $this->withSession(['current_organization_id' => $org->id]);
        TenantContext::set($org->id);

        $this->get('/hub')
            ->assertOk()
            ->assertSee('Finova')
            ->assertSee('conectado')
            ->assertSee('+5511988887777')
            ->assertSee('Gerenciar WhatsApp')
            ->assertDontSee('desconectado');
    }

    public function test_home_ignores_revoked_whatsapp_identity(): void
    {
        [$user, $org] = $this->makeOwner();

        WhatsappIdentity::query()->withoutGlobalScopes()->create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'phone_e164' => '+5511911112222',
            'linked_at' => now()->subDay(),
            'revoked_at' => now(),
        ]);

        $this->actingAs($user);
        $this->withSession(['current_organization_id' => $org->id]);
        TenantContext::set($org->id);

        $this->get('/hub')
            ->assertOk()
            ->assertSee('desconectado')
            ->assertDontSee('+5511911112222');
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
