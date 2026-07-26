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
use App\Contracts\OpenFinance\OpenFinanceProvider;
use App\Services\Finance\Nlu\HeuristicTransactionExtractor;
use App\Services\Finance\Nlu\LlmTransactionExtractor;
use App\Services\Finance\Nlu\TransactionExtractor;
use App\Services\OpenFinance\PluggyOpenFinanceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HeuristicTransactionExtractor::class);
        $this->app->singleton(LlmTransactionExtractor::class);
        $this->app->bind(TransactionExtractor::class, function ($app) {
            $key = (string) config('services.llm.api_key');

            return $key !== ''
                ? $app->make(LlmTransactionExtractor::class)
                : $app->make(HeuristicTransactionExtractor::class);
        });

        $this->app->singleton(OpenFinanceProvider::class, PluggyOpenFinanceProvider::class);
    }

    public function boot(): void
    {
        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(TenantNote::class, TenantNotePolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Transaction::class, TransactionPolicy::class);
    }
}
