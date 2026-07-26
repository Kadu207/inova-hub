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
        <p class="sub">Resumo desta semana (mesmo cálculo da Finova)</p>
        <div class="totals">
            <div>
                <p class="sub" style="margin:0;">Receitas</p>
                <p class="total-value income">R$ {{ number_format($weekSummary['income_cents'] / 100, 2, ',', '.') }}</p>
            </div>
            <div>
                <p class="sub" style="margin:0;">Despesas</p>
                <p class="total-value expense">R$ {{ number_format($weekSummary['expense_cents'] / 100, 2, ',', '.') }}</p>
            </div>
            <div>
                <p class="sub" style="margin:0;">Saldo</p>
                <p class="total-value {{ $weekSummary['net_cents'] >= 0 ? 'income' : 'expense' }}">
                    R$ {{ number_format($weekSummary['net_cents'] / 100, 2, ',', '.') }}
                </p>
            </div>
        </div>
        @if ($weekSummary['top_categories'] !== [])
            <p class="sub" style="margin:1rem 0 0.35rem;">Top categorias</p>
            <ul style="margin:0;padding-left:1.1rem;color:var(--muted);">
                @foreach ($weekSummary['top_categories'] as $row)
                    <li>{{ $row['name'] }} — R$ {{ number_format($row['amount_cents'] / 100, 2, ',', '.') }}</li>
                @endforeach
            </ul>
        @endif
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
