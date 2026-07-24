<?php

namespace App\Support\Tenancy;

use Illuminate\Support\Facades\DB;

final class TenantContext
{
    private static ?string $organizationId = null;

    public static function set(?string $organizationId): void
    {
        self::$organizationId = $organizationId;

        try {
            if (DB::getDriverName() !== 'pgsql') {
                return;
            }

            DB::select('select set_config(?, ?, true)', [
                'app.current_org',
                $organizationId ?? '',
            ]);
        } catch (\Throwable) {
            // Ignore when the DB transaction is already aborted (tests).
        }
    }

    public static function id(): ?string
    {
        return self::$organizationId;
    }

    public static function check(): string
    {
        if (self::$organizationId === null || self::$organizationId === '') {
            throw new \RuntimeException('Tenant context is not set.');
        }

        return self::$organizationId;
    }

    public static function clear(): void
    {
        self::$organizationId = null;

        try {
            if (DB::getDriverName() === 'pgsql') {
                DB::select("select set_config('app.current_org', '', true)");
            }
        } catch (\Throwable) {
            // Ignore when the DB transaction is already aborted (tests).
        }
    }
}
