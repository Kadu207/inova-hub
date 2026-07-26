@extends('layouts.hub')

@section('title', ($account->name ?: 'Conta').' — Inova Hub')

@section('content')
    <div class="topbar">
        <p class="brand" style="margin:0;">Extrato</p>
        <a class="btn btn-ghost" href="{{ route('hub.connections.index') }}" style="width:auto;margin:0;padding:0 1rem;">Bancos</a>
    </div>

    @if (session('status'))
        <p class="sub" role="status" style="margin:0 0 var(--space);">{{ session('status') }}</p>
    @endif

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
                <div class="tx-row" style="align-items:flex-start; flex-wrap:wrap; gap:0.75rem;">
                    <div style="flex:1 1 12rem; min-width:0;">
                        <p class="tx-desc">{{ $tx->description ?: 'Sem descrição' }}</p>
                        <p class="sub" style="margin:0.25rem 0 0;">
                            {{ $tx->occurred_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                            @if ($tx->category_manual)
                                · editada
                            @endif
                        </p>
                        <form method="post" action="{{ route('hub.connections.transactions.category', $tx) }}"
                              style="margin-top:0.5rem; display:flex; flex-wrap:wrap; gap:0.5rem; align-items:center;">
                            @csrf
                            @method('PATCH')
                            <label class="sub" for="cat-{{ $tx->id }}" style="margin:0;">Categoria</label>
                            <select id="cat-{{ $tx->id }}" name="category_suggested"
                                    style="max-width:100%; min-width:9rem;" required>
                                @unless ($tx->category_suggested && isset($categoryOptions[$tx->category_suggested]))
                                    <option value="" disabled @selected(! $tx->category_suggested)>Escolher…</option>
                                @endunless
                                @foreach ($categoryOptions as $slug => $label)
                                    <option value="{{ $slug }}" @selected($tx->category_suggested === $slug)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn-ghost" style="width:auto;margin:0;padding:0 0.85rem;">Salvar</button>
                        </form>
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
