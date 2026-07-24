<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('memberships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 32); // owner | member | viewer (BR-009)
            $table->timestamps();

            $table->unique(['organization_id', 'user_id']);
            $table->index(['organization_id', 'role']);
        });

        // Tabela de negócio mínima para provar isolamento (BR-001) até Week 3 finanças
        Schema::create('tenant_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('body')->nullable();
            $table->timestamps();

            $table->index('organization_id');
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE memberships ENABLE ROW LEVEL SECURITY');
            DB::statement('ALTER TABLE memberships FORCE ROW LEVEL SECURITY');
            DB::statement('ALTER TABLE tenant_notes ENABLE ROW LEVEL SECURITY');
            DB::statement('ALTER TABLE tenant_notes FORCE ROW LEVEL SECURITY');

            DB::statement(<<<'SQL'
CREATE POLICY tenant_isolation_memberships ON memberships
  FOR ALL
  USING (organization_id::text = nullif(current_setting('app.current_org', true), ''))
  WITH CHECK (organization_id::text = nullif(current_setting('app.current_org', true), ''))
SQL);

            DB::statement(<<<'SQL'
CREATE POLICY tenant_isolation_tenant_notes ON tenant_notes
  FOR ALL
  USING (organization_id::text = nullif(current_setting('app.current_org', true), ''))
  WITH CHECK (organization_id::text = nullif(current_setting('app.current_org', true), ''))
SQL);
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('DROP POLICY IF EXISTS tenant_isolation_tenant_notes ON tenant_notes');
            DB::statement('DROP POLICY IF EXISTS tenant_isolation_memberships ON memberships');
        }

        Schema::dropIfExists('tenant_notes');
        Schema::dropIfExists('memberships');
        Schema::dropIfExists('organizations');
    }
};
