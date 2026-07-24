<?php

namespace App\Policies;

use App\Models\Membership;
use App\Models\TenantNote;
use App\Models\User;

final class TenantNotePolicy
{
    public function view(User $user, TenantNote $note): bool
    {
        return $this->memberOfNoteOrg($user, $note);
    }

    public function update(User $user, TenantNote $note): bool
    {
        return Membership::query()
            ->withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('organization_id', $note->organization_id)
            ->whereIn('role', [Membership::ROLE_OWNER, Membership::ROLE_MEMBER])
            ->exists();
    }

    public function delete(User $user, TenantNote $note): bool
    {
        return $this->update($user, $note);
    }

    private function memberOfNoteOrg(User $user, TenantNote $note): bool
    {
        return Membership::query()
            ->withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('organization_id', $note->organization_id)
            ->exists();
    }
}
