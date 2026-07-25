<?php

namespace App\Services\WhatsApp;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class VerifiesMetaSignature
{
    public function assertValid(Request $request): void
    {
        $secret = (string) config('services.whatsapp.app_secret');

        if ($secret === '') {
            throw new AccessDeniedHttpException('META_APP_SECRET not configured.');
        }

        $header = (string) $request->header('X-Hub-Signature-256', '');

        if (! str_starts_with($header, 'sha256=')) {
            throw new AccessDeniedHttpException('Missing WhatsApp signature.');
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        if (! hash_equals($expected, $header)) {
            throw new AccessDeniedHttpException('Invalid WhatsApp signature.');
        }
    }
}
