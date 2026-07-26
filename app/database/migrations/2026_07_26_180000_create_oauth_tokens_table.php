<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oauth_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('provider', 32);
            $table->string('provider_account_email')->nullable();
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('scopes')->nullable();
            $table->string('consent_version', 32)->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'provider']);
            $table->index(['organization_id', 'provider', 'revoked_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE oauth_tokens ENABLE ROW LEVEL SECURITY');
            DB::statement(<<<'SQL'
CREATE POLICY oauth_tokens_tenant_isolation ON oauth_tokens
  USING (organization_id::text = nullif(current_setting('app.current_org', true), ''))
  WITH CHECK (organization_id::text = nullif(current_setting('app.current_org', true), ''))
SQL);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP POLICY IF EXISTS oauth_tokens_tenant_isolation ON oauth_tokens');
        }

        Schema::dropIfExists('oauth_tokens');
    }
};
