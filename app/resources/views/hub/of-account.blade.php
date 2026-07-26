@extends('layouts.hub')

@section('title', ($account->name ?: 'Conta').' — Inova Hub')

@section('content')
    <div class="topbar">
        <p class="brand" style="margin:0;">Extrato</p>
        <a class="btn btn-ghost" href="{{ route('hub.connections.index') }}" style="width:auto;margin:0;padding:0 1rem;">Bancos</a>
    </div>

    <div class="card">
        <p class="sub" style="margin-top:0;">{{ $account->item?->connector_name ?: 'Open Finance' }}</p>
        <p class="tx-desc">{{ $account->name ?: ($account->type ?: 'Conta') }}</p>
        <p class="sub" style="margin:0.35rem 0 0;">
            {{ $account->subtype ?: $account->type ?: '—' }}
            @if ($account->number)
                · {{ $account->number }}
            @endif
        </p>
        <p class="total-value {{ $account->balance_cents >= 0 ? 'income' : 'expense' }}" style="margin-top:1rem;">
            R$ {{ number_format($account->balance_cents / 100, 2, ',', '.') }}
        </p>
        <p class="sub" style="margin:0.35rem 0 0;">
            Atualizado:
            {{ $account->synced_at ? $account->synced_at->timezone(config('app.timezone'))->format('d/m/Y H:i') : '—' }}
        </p>
    </div>

    <div class="card" style="margin-top:var(--space);">
        <p class="sub" style="margin-top:0;">Movimentações (OF)</p>
        <div class="tx-list">
            @forelse ($transactions as $tx)
                <div class="tx-row">
                    <div>
                        <p class="tx-desc">{{ $tx->description ?: 'Sem descrição' }}</p>
                        <p class="sub" style="margin:0.25rem 0 0;">
                            {{ $tx->occurred_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                            @if ($tx->category_suggested)
                                · {{ $tx->category_suggested }}
                            @endif
                        </p>
                    </div>
                    <p class="tx-amount {{ $tx->type === 'income' ? 'income' : 'expense' }}">
                        {{ $tx->type === 'income' ? '+' : '-' }}
                        R$ {{ number_format($tx->amount_cents / 100, 2, ',', '.') }}
                    </p>
                </div>
            @empty
                <p class="sub" style="margin:0;">Nenhuma transação sincronizada ainda. Volte em Bancos e clique em Sincronizar.</p>
            @endforelse
        </div>
    </div>
@endsection
