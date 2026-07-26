<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('of_items', function (Blueprint $table) {
            $table->string('consent_version', 32)->nullable()->after('consent_at');
        });

        Schema::create('consent_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 64);
            $table->string('version', 32);
            $table->timestamp('accepted_at');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'type', 'accepted_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE consent_logs ENABLE ROW LEVEL SECURITY');
            DB::statement(<<<'SQL'
CREATE POLICY consent_logs_tenant_isolation ON consent_logs
  USING (organization_id::text = nullif(current_setting('app.current_org', true), ''))
  WITH CHECK (organization_id::text = nullif(current_setting('app.current_org', true), ''))
SQL);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP POLICY IF EXISTS consent_logs_tenant_isolation ON consent_logs');
        }

        Schema::dropIfExists('consent_logs');

        Schema::table('of_items', function (Blueprint $table) {
            $table->dropColumn('consent_version');
        });
    }
};
