<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

final class LegalController
{
    public function openFinance(): View
    {
        return view('legal.open-finance', [
            'consentVersion' => (string) config('open_finance.consent_version', 'of-1.0'),
            'consentTitle' => (string) config('open_finance.consent_title'),
        ]);
    }

    public function privacy(): View
    {
        return view('legal.privacy', [
            'consentVersion' => (string) config('open_finance.consent_version', 'of-1.0'),
        ]);
    }
}
