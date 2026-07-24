<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_identities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('phone_e164', 20);
            $table->timestamp('linked_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'phone_e164']);
            $table->index(['phone_e164', 'revoked_at']);
            $table->index('organization_id');
        });

        Schema::create('whatsapp_otps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('phone_e164', 20);
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamps();

            $table->index(['organization_id', 'user_id']);
            $table->index(['phone_e164', 'consumed_at']);
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            foreach (['whatsapp_identities', 'whatsapp_otps'] as $table) {
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
            DB::statement('DROP POLICY IF EXISTS tenant_isolation_whatsapp_otps ON whatsapp_otps');
            DB::statement('DROP POLICY IF EXISTS tenant_isolation_whatsapp_identities ON whatsapp_identities');
        }

        Schema::dropIfExists('whatsapp_otps');
        Schema::dropIfExists('whatsapp_identities');
    }
};
