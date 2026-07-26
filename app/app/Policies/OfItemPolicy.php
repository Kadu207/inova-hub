<?php

namespace App\Policies;

use App\Models\OfItem;
use App\Models\User;

class OfItemPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, OfItem $item): bool
    {
        return $this->memberOf($user, $item->organization_id);
    }

    private function memberOf(User $user, string $organizationId): bool
    {
        return $user->memberships()
            ->where('organization_id', $organizationId)
            ->exists();
    }
}
