<?php

namespace App\Policies;

use App\Models\Membership;
use App\Models\Transaction;
use App\Models\User;

final class TransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Transaction $transaction): bool
    {
        return $this->memberOf($user, $transaction->organization_id);
    }

    public function create(User $user): bool
    {
        return $this->canWrite($user);
    }

    public function update(User $user, Transaction $transaction): bool
    {
        return $this->canWrite($user, $transaction->organization_id);
    }

    public function delete(User $user, Transaction $transaction): bool
    {
        return $this->canWrite($user, $transaction->organization_id);
    }

    private function memberOf(User $user, string $organizationId): bool
    {
        return Membership::query()
            ->withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('organization_id', $organizationId)
            ->exists();
    }

    private function canWrite(User $user, ?string $organizationId = null): bool
    {
        $query = Membership::query()
            ->withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->whereIn('role', [Membership::ROLE_OWNER, Membership::ROLE_MEMBER]);

        if ($organizationId !== null) {
            $query->where('organization_id', $organizationId);
        }

        return $query->exists();
    }
}
