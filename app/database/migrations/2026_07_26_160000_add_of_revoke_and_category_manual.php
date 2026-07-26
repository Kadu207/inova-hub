<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('of_items', function (Blueprint $table) {
            $table->timestamp('consent_revoked_at')->nullable()->after('consent_at');
        });

        Schema::table('of_transactions', function (Blueprint $table) {
            $table->boolean('category_manual')->default(false)->after('category_suggested');
        });
    }

    public function down(): void
    {
        Schema::table('of_transactions', function (Blueprint $table) {
            $table->dropColumn('category_manual');
        });

        Schema::table('of_items', function (Blueprint $table) {
            $table->dropColumn('consent_revoked_at');
        });
    }
};
