<?php

namespace App\Providers;

use App\Models\Organization;
use App\Models\TenantNote;
use App\Policies\OrganizationPolicy;
use App\Policies\TenantNotePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(TenantNote::class, TenantNotePolicy::class);
    }
}
