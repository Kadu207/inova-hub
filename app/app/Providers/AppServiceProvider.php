<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Organization;
use App\Models\TenantNote;
use App\Models\Transaction;
use App\Policies\CategoryPolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\TenantNotePolicy;
use App\Policies\TransactionPolicy;
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
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Transaction::class, TransactionPolicy::class);
    }
}
