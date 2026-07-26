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
        <p style="margin-top:1rem;">
            <a class="btn" href="{{ route('hub.transactions.index') }}">Ver lançamentos</a>
        </p>
    </div>

    <div class="card" style="margin-top:var(--space);">
        <p class="sub" style="margin-top:0;">Despesas por categoria (30 dias)</p>
        <p class="sub">Total: R$ {{ number_format($dashboard['expense_cents'] / 100, 2, ',', '.') }}</p>

        @forelse ($dashboard['by_category'] as $row)
            <div class="chart-row">
                <div class="chart-label">
                    <span>{{ $row['name'] }}</span>
                    <span>R$ {{ number_format($row['amount_cents'] / 100, 2, ',', '.') }} ({{ $row['pct'] }}%)</span>
                </div>
                <div class="chart-track" aria-hidden="true">
                    <div class="chart-fill" style="width: {{ min(100, $row['pct']) }}%;"></div>
                </div>
            </div>
        @empty
            <p class="sub" style="margin:0;">Sem despesas nos últimos 30 dias.</p>
        @endforelse
    </div>

    <div class="card" style="margin-top:var(--space);">
        <p class="sub" style="margin-top:0;">Evolução de despesas (30 dias)</p>
        <div class="spark" role="img" aria-label="Gráfico de despesas diárias dos últimos 30 dias">
            @foreach ($dashboard['daily'] as $day)
                @php
                    $h = (int) round(($day['expense_cents'] / $maxDailyExpense) * 100);
                @endphp
                <div class="spark-bar" title="{{ $day['date'] }}: R$ {{ number_format($day['expense_cents'] / 100, 2, ',', '.') }}" style="height: {{ max($day['expense_cents'] > 0 ? 8 : 2, $h) }}%;"></div>
            @endforeach
        </div>
        <p class="sub" style="margin:0.75rem 0 0;">
            {{ \Illuminate\Support\Carbon::parse($dashboard['from'])->format('d/m') }}
            —
            {{ \Illuminate\Support\Carbon::parse($dashboard['to'])->format('d/m') }}
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

    <div class="card" style="margin-top:var(--space);">
        <p class="sub" style="margin-top:0;">Bancos (Open Finance)</p>
        <p class="sub">Conecte contas via Pluggy (somente leitura).</p>
        <p style="margin-top:1rem;">
            <a class="btn" href="{{ route('hub.connections.index') }}">Conectar banco</a>
        </p>
    </div>

    <div class="card" style="margin-top:var(--space);">
        <p class="sub" style="margin-top:0;">Google Agenda</p>
        <p class="sub">OAuth com escopos mínimos de calendário (sem People API).</p>
        <p style="margin-top:1rem;">
            <a class="btn" href="{{ route('hub.google.show') }}">Conectar Google</a>
        </p>
    </div>
@endsection
