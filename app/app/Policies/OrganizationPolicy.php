<?php

namespace App\Policies;

use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;

final class OrganizationPolicy
{
    public function view(User $user, Organization $organization): bool
    {
        return $this->member($user, $organization);
    }

    public function update(User $user, Organization $organization): bool
    {
        return $this->hasRole($user, $organization, [Membership::ROLE_OWNER]);
    }

    public function manageMembers(User $user, Organization $organization): bool
    {
        return $this->hasRole($user, $organization, [Membership::ROLE_OWNER]);
    }

    private function member(User $user, Organization $organization): bool
    {
        return Membership::query()
            ->withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('organization_id', $organization->id)
            ->exists();
    }

    /**
     * @param  list<string>  $roles
     */
    private function hasRole(User $user, Organization $organization, array $roles): bool
    {
        return Membership::query()
            ->withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('organization_id', $organization->id)
            ->whereIn('role', $roles)
            ->exists();
    }
}
