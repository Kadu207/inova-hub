<?php

namespace App\Services\Auth;

use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use App\Services\Finance\SeedsDefaultCategories;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class RegistersUser
{
    public function __construct(
        private readonly SeedsDefaultCategories $seedsDefaultCategories,
    ) {}

    /**
     * @param  array{name: string, email: string, password: string, organization_name?: string}  $data
     * @return array{user: User, organization: Organization}
     */
    public function handle(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $orgName = $data['organization_name'] ?? ($data['name']."'s Hub");
            $baseSlug = Str::slug($orgName) ?: 'org';
            $slug = $baseSlug;
            $i = 1;
            while (Organization::query()->where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$i;
                $i++;
            }

            $organization = Organization::query()->create([
                'name' => $orgName,
                'slug' => $slug,
            ]);

            Membership::query()->withoutGlobalScopes()->create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'role' => Membership::ROLE_OWNER,
            ]);

            $this->seedsDefaultCategories->handle($organization);

            return compact('user', 'organization');
        });
    }
}
