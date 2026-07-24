<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsappIdentity;
use App\Models\WhatsappOtp;
use App\Support\Tenancy\TenantContext;
use App\Support\WhatsApp\PhoneNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class ConsumesWhatsappOtp
{
    /**
     * Vincula identidade a partir do código OTP + telefone remetente (webhook ou confirmação).
     */
    public function handle(string $phoneRaw, string $code, ?string $organizationId = null): WhatsappIdentity
    {
        $phone = PhoneNormalizer::toE164($phoneRaw);
        $code = preg_replace('/\D+/', '', $code) ?? '';

        if (strlen($code) !== 6) {
            throw ValidationException::withMessages([
                'code' => 'Código OTP deve ter 6 dígitos.',
            ]);
        }

        $query = WhatsappOtp::query()
            ->withoutGlobalScopes()
            ->where('phone_e164', $phone)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('created_at');

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        $otp = $query->first();

        if ($otp === null) {
            throw ValidationException::withMessages([
                'code' => 'Nenhum OTP válido para este número. Gere um novo no Hub.',
            ]);
        }

        if ($otp->attempts >= 5) {
            throw ValidationException::withMessages([
                'code' => 'OTP bloqueado por tentativas. Gere um novo.',
            ]);
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');

            throw ValidationException::withMessages([
                'code' => 'Código incorreto.',
            ]);
        }

        return DB::transaction(function () use ($otp, $phone) {
            $otp->update(['consumed_at' => now()]);

            TenantContext::set($otp->organization_id);

            WhatsappIdentity::query()
                ->withoutGlobalScopes()
                ->where('user_id', $otp->user_id)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            return WhatsappIdentity::query()->create([
                'organization_id' => $otp->organization_id,
                'user_id' => $otp->user_id,
                'phone_e164' => $phone,
                'linked_at' => now(),
            ]);
        });
    }
}
