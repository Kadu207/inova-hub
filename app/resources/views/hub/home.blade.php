@extends('layouts.hub')

@section('title', 'Início — Inova Hub')

@section('content')
    <div class="topbar">
        <p class="brand" style="margin:0;">Inova Hub</p>
        <form method="post" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Sair</button>
        </form>
    </div>

    <div class="card">
        <p class="sub" style="margin-top:0;">Olá, {{ $user->name }}.</p>
        <p>Organização ativa: <strong>{{ $organizationId ?? '—' }}</strong></p>
    </div>

    <div class="card" style="margin-top:var(--space);">
        <p class="sub" style="margin-top:0;">Finanças</p>
        <p class="sub" style="margin-bottom:0;">Lançamentos manuais com filtros e totais do período.</p>
        <p style="margin-top:1rem;">
            <a class="btn" href="{{ route('hub.transactions.index') }}">Ver lançamentos</a>
        </p>
    </div>

    <div class="card" style="margin-top:var(--space);">
        <p class="sub" style="margin-top:0;">Finova (WhatsApp)</p>

        @if ($whatsappIdentity)
            <p>Status: <strong>conectado</strong></p>
            <p>Número: <strong>{{ $whatsappIdentity->phone_e164 }}</strong></p>
            <p style="margin-top:1rem;">
                <a class="btn" href="{{ route('hub.whatsapp') }}">Gerenciar WhatsApp</a>
            </p>
        @else
            <p>Status: <strong>desconectado</strong></p>
            <p class="sub" style="margin-bottom:0;">Vincule seu número para falar com a Finova e confirmar o OTP.</p>
            <p style="margin-top:1rem;">
                <a class="btn" href="{{ route('hub.whatsapp') }}">Vincular / reenviar OTP</a>
            </p>
        @endif
    </div>
@endsection
