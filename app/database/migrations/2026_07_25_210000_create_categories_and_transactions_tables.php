<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('kind', 16); // expense | income
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->unique(['organization_id', 'slug']);
            $table->index(['organization_id', 'kind']);
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('amount_cents');
            $table->string('type', 16); // expense | income
            $table->string('currency', 3)->default('BRL');
            $table->string('source', 16)->default('manual'); // manual | finova | of
            $table->string('description')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['organization_id', 'occurred_at']);
            $table->index(['organization_id', 'category_id']);
            $table->index(['organization_id', 'type']);
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            foreach (['categories', 'transactions'] as $table) {
                DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
                DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");
                DB::statement(<<<SQL
CREATE POLICY tenant_isolation_{$table} ON {$table}
  FOR ALL
  USING (organization_id::text = nullif(current_setting('app.current_org', true), ''))
  WITH CHECK (organization_id::text = nullif(current_setting('app.current_org', true), ''))
SQL);
            }
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('DROP POLICY IF EXISTS tenant_isolation_transactions ON transactions');
            DB::statement('DROP POLICY IF EXISTS tenant_isolation_categories ON categories');
        }

        Schema::dropIfExists('transactions');
        Schema::dropIfExists('categories');
    }
};
