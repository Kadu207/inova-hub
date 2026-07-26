<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('of_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('of_item_id')->constrained('of_items')->cascadeOnDelete();
            $table->string('pluggy_account_id', 64);
            $table->string('name')->nullable();
            $table->string('type', 64)->nullable();
            $table->string('subtype', 64)->nullable();
            $table->string('number')->nullable();
            $table->string('currency', 3)->default('BRL');
            $table->bigInteger('balance_cents')->default(0);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'pluggy_account_id']);
            $table->unique('pluggy_account_id');
            $table->index(['organization_id', 'of_item_id']);
        });

        Schema::create('of_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('of_account_id')->constrained('of_accounts')->cascadeOnDelete();
            $table->string('pluggy_transaction_id', 64);
            $table->bigInteger('amount_cents');
            $table->string('currency', 3)->default('BRL');
            $table->string('type', 16); // expense | income
            $table->string('description')->nullable();
            $table->string('category_suggested')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->unique(['organization_id', 'pluggy_transaction_id']);
            $table->unique('pluggy_transaction_id');
            $table->index(['organization_id', 'of_account_id', 'occurred_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            foreach (['of_accounts', 'of_transactions'] as $table) {
                DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
                DB::statement(<<<SQL
CREATE POLICY {$table}_tenant_isolation ON {$table}
  USING (organization_id::text = nullif(current_setting('app.current_org', true), ''))
  WITH CHECK (organization_id::text = nullif(current_setting('app.current_org', true), ''))
SQL);
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            foreach (['of_transactions', 'of_accounts'] as $table) {
                DB::statement("DROP POLICY IF EXISTS {$table}_tenant_isolation ON {$table}");
            }
        }

        Schema::dropIfExists('of_transactions');
        Schema::dropIfExists('of_accounts');
    }
};
