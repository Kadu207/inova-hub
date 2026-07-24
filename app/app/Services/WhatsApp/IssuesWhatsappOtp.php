<?php

namespace App\Services\WhatsApp;

use App\Models\User;
use App\Models\WhatsappIdentity;
use App\Models\WhatsappOtp;
use App\Support\Tenancy\TenantContext;
use App\Support\WhatsApp\PhoneNormalizer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class IssuesWhatsappOtp
{
    /**
     * @return array{otp: WhatsappOtp, plain_code: string, phone_e164: string}
     */
    public function handle(User $user, string $phoneRaw): array
    {
        $organizationId = TenantContext::check();
        $phone = PhoneNormalizer::toE164($phoneRaw);

        if (strlen(preg_replace('/\D+/', '', $phone) ?? '') < 12) {
            throw ValidationException::withMessages([
                'phone' => 'Informe um celular com DDD (ex.: +5511999999999).',
            ]);
        }

        $taken = WhatsappIdentity::query()
            ->withoutGlobalScopes()
            ->where('phone_e164', $phone)
            ->whereNull('revoked_at')
            ->where('user_id', '!=', $user->id)
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages([
                'phone' => 'Este WhatsApp já está vinculado a outra conta.',
            ]);
        }

        WhatsappOtp::query()
            ->where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $plain = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $otp = WhatsappOtp::query()->create([
            'organization_id' => $organizationId,
            'user_id' => $user->id,
            'phone_e164' => $phone,
            'code_hash' => Hash::make($plain),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
        ]);

        return [
            'otp' => $otp,
            'plain_code' => $plain,
            'phone_e164' => $phone,
        ];
    }
}
