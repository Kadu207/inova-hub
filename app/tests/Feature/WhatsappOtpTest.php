<?php

namespace Tests\Feature;

use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use App\Models\WhatsappIdentity;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsappOtpTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_user_can_issue_otp_and_confirm_dev_link(): void
    {
        [$user, $org] = $this->makeOwner();

        $this->actingAs($user);
        $this->withSession(['current_organization_id' => $org->id]);
        TenantContext::set($org->id);

        $this->get('/hub/whatsapp')->assertOk();

        $issue = $this->post('/hub/whatsapp/otp', [
            '_token' => session()->token(),
            'phone' => '11988887777',
        ]);

        $issue->assertRedirect('/hub/whatsapp');
        $issue->assertSessionHas('whatsapp_otp_plain');

        $plain = session('whatsapp_otp_plain');
        $phone = session('whatsapp_otp_phone');

        $this->assertNotEmpty($plain);
        $this->assertSame('+5511988887777', $phone);

        $confirm = $this->post('/hub/whatsapp/confirm-dev', [
            '_token' => session()->token(),
            'phone' => $phone,
            'code' => $plain,
        ]);

        $confirm->assertRedirect('/hub/whatsapp');
        $this->assertDatabaseHas('whatsapp_identities', [
            'user_id' => $user->id,
            'phone_e164' => '+5511988887777',
            'organization_id' => $org->id,
        ]);
        $this->assertTrue(
            WhatsappIdentity::query()->withoutGlobalScopes()->where('user_id', $user->id)->whereNull('revoked_at')->exists()
        );
    }

    public function test_phone_cannot_belong_to_two_active_users(): void
    {
        [$userA, $orgA] = $this->makeOwner();
        [$userB, $orgB] = $this->makeOwner();

        WhatsappIdentity::query()->withoutGlobalScopes()->create([
            'organization_id' => $orgA->id,
            'user_id' => $userA->id,
            'phone_e164' => '+5511911112222',
            'linked_at' => now(),
        ]);

        $this->actingAs($userB);
        $this->withSession(['current_organization_id' => $orgB->id]);
        TenantContext::set($orgB->id);

        $this->get('/hub/whatsapp')->assertOk();

        $this->post('/hub/whatsapp/otp', [
            '_token' => session()->token(),
            'phone' => '+5511911112222',
        ])->assertSessionHasErrors('phone');
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
