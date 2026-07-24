<?php

namespace Tests\Feature;

use App\Models\Membership;
use App\Models\Organization;
use App\Models\TenantNote;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_user_cannot_view_note_from_another_organization_via_policy(): void
    {
        [$userA, $orgA] = $this->makeOwnerOrg('Org A');
        [, $orgB] = $this->makeOwnerOrg('Org B');

        $noteB = TenantNote::factory()->create([
            'organization_id' => $orgB->id,
            'title' => 'Segredo B',
        ]);

        $this->assertFalse(Gate::forUser($userA)->allows('view', $noteB));
        $this->assertTrue($userA->belongsToOrganization($orgA->id));
        $this->assertFalse($userA->belongsToOrganization($orgB->id));
    }

    public function test_global_scope_hides_other_tenant_notes(): void
    {
        [, $orgA] = $this->makeOwnerOrg('Org A');
        [, $orgB] = $this->makeOwnerOrg('Org B');

        TenantNote::factory()->create([
            'organization_id' => $orgA->id,
            'title' => 'Nota A',
        ]);
        TenantNote::factory()->create([
            'organization_id' => $orgB->id,
            'title' => 'Nota B',
        ]);

        TenantContext::set($orgA->id);

        $titles = TenantNote::query()->pluck('title')->all();

        $this->assertSame(['Nota A'], $titles);
        $this->assertNull(TenantNote::query()->where('title', 'Nota B')->first());
    }

    public function test_creating_note_fills_organization_id_from_context(): void
    {
        [$user, $org] = $this->makeOwnerOrg('Org Context');

        TenantContext::set($org->id);

        $note = TenantNote::query()->create([
            'user_id' => $user->id,
            'title' => 'Auto org',
            'body' => 'ok',
        ]);

        $this->assertSame($org->id, $note->organization_id);
    }

    public function test_membership_unique_per_organization_and_user(): void
    {
        [$user, $org] = $this->makeOwnerOrg('Org Unique');

        $this->expectException(UniqueConstraintViolationException::class);

        Membership::query()->withoutGlobalScopes()->create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'role' => Membership::ROLE_MEMBER,
        ]);
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function makeOwnerOrg(string $name): array
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create(['name' => $name]);

        Membership::query()->withoutGlobalScopes()->create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'role' => Membership::ROLE_OWNER,
        ]);

        return [$user, $org];
    }
}
