@extends('layouts.hub')

@section('title', 'WhatsApp — Inova Hub')

@section('content')
    <div class="topbar">
        <p class="brand" style="margin:0;">Finova</p>
        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
            <a class="btn" href="{{ route('hub.home') }}" style="width:auto;margin:0;padding:0 1rem;background:transparent;color:var(--muted);border:1px solid color-mix(in srgb, var(--text) 20%, transparent);">Início</a>
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Sair</button>
            </form>
        </div>
    </div>

    @if (session('status'))
        <p class="sub" style="color:var(--accent);">{{ session('status') }}</p>
    @endif

    <div class="card">
        <p class="sub" style="margin-top:0;">Vincular WhatsApp à sua conta (BR-002).</p>

        @if ($identity)
            <p>Status: <strong>conectado</strong></p>
            <p>Número: <strong>{{ $identity->phone_e164 }}</strong></p>
        @else
            <p>Status: <strong>não conectado</strong></p>

            @if ($errors->any())
                <ul class="errors">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            <form method="post" action="{{ route('hub.whatsapp.otp') }}">
                @csrf
                <label for="phone">Seu WhatsApp (com DDD)</label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone', $pendingPhone) }}" placeholder="+5511999999999" required>
                <button type="submit">Gerar código OTP</button>
            </form>

            @if ($plainCode)
                <div style="margin-top:1.25rem;padding:1rem;border-radius:10px;background:#0c1116;border:1px dashed color-mix(in srgb, var(--accent) 40%, transparent);">
                    <p style="margin:0 0 0.5rem;">Envie este código para a <strong>Finova</strong> no WhatsApp:</p>
                    <p style="font-size:1.75rem;letter-spacing:0.2em;margin:0;font-weight:700;">{{ $plainCode }}</p>
                    <p class="sub" style="margin:0.75rem 0 0;">Número: {{ $pendingPhone }} · válido por 10 minutos</p>
                </div>
            @endif

            @if ($devConfirmEnabled)
                <form method="post" action="{{ route('hub.whatsapp.confirm-dev') }}" style="margin-top:1.25rem;">
                    @csrf
                    <p class="sub">Confirmar (dev) — use enquanto o webhook Meta (D11) não estiver ativo.</p>
                    <label for="phone_confirm">Telefone</label>
                    <input id="phone_confirm" name="phone" type="tel" value="{{ old('phone', $pendingPhone) }}" required>
                    <label for="code">Código de 6 dígitos</label>
                    <input id="code" name="code" type="text" inputmode="numeric" maxlength="6" value="{{ old('code', $plainCode) }}" required>
                    <button type="submit">Confirmar vínculo (dev)</button>
                </form>
            @endif
        @endif
    </div>
@endsection
