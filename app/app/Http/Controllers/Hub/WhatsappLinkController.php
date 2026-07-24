<?php

namespace App\Http\Controllers\Hub;

use App\Http\Controllers\Controller;
use App\Models\WhatsappIdentity;
use App\Services\WhatsApp\ConsumesWhatsappOtp;
use App\Services\WhatsApp\IssuesWhatsappOtp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class WhatsappLinkController extends Controller
{
    public function show(Request $request): View
    {
        $identity = WhatsappIdentity::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('revoked_at')
            ->first();

        return view('hub.whatsapp', [
            'user' => $request->user(),
            'identity' => $identity,
            'plainCode' => $request->session()->get('whatsapp_otp_plain'),
            'pendingPhone' => $request->session()->get('whatsapp_otp_phone'),
            'devConfirmEnabled' => app()->environment(['local', 'testing'])
                || blank(config('services.whatsapp.token')),
        ]);
    }

    public function issue(Request $request, IssuesWhatsappOtp $issues): RedirectResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
        ]);

        $result = $issues->handle($request->user(), $data['phone']);

        $request->session()->flash('whatsapp_otp_plain', $result['plain_code']);
        $request->session()->flash('whatsapp_otp_phone', $result['phone_e164']);
        $request->session()->flash('status', 'OTP gerado. Envie o código para a Finova no WhatsApp.');

        return redirect()->route('hub.whatsapp');
    }

    public function confirmDev(Request $request, ConsumesWhatsappOtp $consumes): RedirectResponse
    {
        $enabled = app()->environment(['local', 'testing'])
            || blank(config('services.whatsapp.token'));

        abort_unless($enabled, 403);

        $data = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
            'code' => ['required', 'string', 'max:12'],
        ]);

        $consumes->handle(
            $data['phone'],
            $data['code'],
            $request->session()->get('current_organization_id'),
        );

        $request->session()->forget(['whatsapp_otp_plain', 'whatsapp_otp_phone']);
        $request->session()->flash('status', 'WhatsApp vinculado com sucesso.');

        return redirect()->route('hub.whatsapp');
    }
}
