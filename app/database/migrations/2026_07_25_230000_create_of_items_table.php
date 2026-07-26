<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('of_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('pluggy_item_id', 64);
            $table->string('status', 32)->default('CREATED');
            $table->string('client_user_id', 191)->nullable();
            $table->string('connector_name')->nullable();
            $table->timestamp('consent_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'pluggy_item_id']);
            $table->unique('pluggy_item_id');
            $table->index(['organization_id', 'status']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE of_items ENABLE ROW LEVEL SECURITY');
            DB::statement(<<<'SQL'
CREATE POLICY of_items_tenant_isolation ON of_items
  USING (organization_id::text = nullif(current_setting('app.current_org', true), ''))
  WITH CHECK (organization_id::text = nullif(current_setting('app.current_org', true), ''))
SQL);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP POLICY IF EXISTS of_items_tenant_isolation ON of_items');
        }

        Schema::dropIfExists('of_items');
    }
};
