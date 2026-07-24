<?php

namespace Database\Seeders;

use App\Models\Membership;
use App\Models\Organization;
use App\Models\TenantNote;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->updateOrCreate(
            ['email' => 'admin@inovahub.test'],
            [
                'name' => 'Admin Inova Hub',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        $org = Organization::query()->updateOrCreate(
            ['slug' => 'inova-demo'],
            ['name' => 'Inova Demo'],
        );

        Membership::query()->withoutGlobalScopes()->updateOrCreate(
            [
                'organization_id' => $org->id,
                'user_id' => $owner->id,
            ],
            ['role' => Membership::ROLE_OWNER],
        );

        TenantNote::query()->withoutGlobalScopes()->updateOrCreate(
            [
                'organization_id' => $org->id,
                'title' => 'Bem-vindo ao Inova Hub',
            ],
            [
                'user_id' => $owner->id,
                'body' => 'Nota seed para validar multi-tenant (D08).',
            ],
        );
    }
}
