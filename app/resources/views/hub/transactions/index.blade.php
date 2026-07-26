@extends('layouts.hub')

@section('title', 'Lançamentos — Inova Hub')

@section('content')
    <div class="topbar">
        <p class="brand" style="margin:0;">Lançamentos</p>
        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
            <a class="btn btn-ghost" href="{{ route('hub.home') }}" style="width:auto;margin:0;">Início</a>
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
        <div class="totals">
            <div>
                <p class="sub" style="margin:0;">Receitas</p>
                <p class="total-value income">R$ {{ number_format($totals['income_cents'] / 100, 2, ',', '.') }}</p>
            </div>
            <div>
                <p class="sub" style="margin:0;">Despesas</p>
                <p class="total-value expense">R$ {{ number_format($totals['expense_cents'] / 100, 2, ',', '.') }}</p>
            </div>
            <div>
                <p class="sub" style="margin:0;">Saldo</p>
                <p class="total-value {{ $totals['net_cents'] >= 0 ? 'income' : 'expense' }}">
                    R$ {{ number_format($totals['net_cents'] / 100, 2, ',', '.') }}
                </p>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:var(--space);">
        <form method="get" action="{{ route('hub.transactions.index') }}" class="filters">
            <div class="filter-grid">
                <div>
                    <label for="from">De</label>
                    <input id="from" name="from" type="date" value="{{ $filters['from'] }}">
                </div>
                <div>
                    <label for="to">Até</label>
                    <input id="to" name="to" type="date" value="{{ $filters['to'] }}">
                </div>
                <div>
                    <label for="category_id">Categoria</label>
                    <select id="category_id" name="category_id">
                        <option value="">Todas</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected($filters['category_id'] === $category->id)>
                                {{ $category->name }} ({{ $category->kind === 'income' ? 'receita' : 'despesa' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="type">Tipo</label>
                    <select id="type" name="type">
                        <option value="">Todos</option>
                        <option value="expense" @selected($filters['type'] === 'expense')>Despesa</option>
                        <option value="income" @selected($filters['type'] === 'income')>Receita</option>
                    </select>
                </div>
            </div>
            <button type="submit">Filtrar</button>
        </form>
        <p style="margin:0.75rem 0 0;">
            <a class="btn" href="{{ route('hub.transactions.create') }}">Novo lançamento</a>
        </p>
    </div>

    <div class="tx-list" style="margin-top:var(--space);">
        @forelse ($transactions as $tx)
            <article class="card tx-card">
                <div class="tx-row">
                    <div>
                        <p class="tx-desc">{{ $tx->description ?: ($tx->category->name ?? 'Lançamento') }}</p>
                        <p class="sub" style="margin:0.25rem 0 0;">
                            {{ $tx->category->name ?? '—' }} · {{ $tx->occurred_at->timezone(config('app.timezone'))->format('d/m/Y') }}
                        </p>
                    </div>
                    <p class="tx-amount {{ $tx->type === 'income' ? 'income' : 'expense' }}">
                        {{ $tx->type === 'income' ? '+' : '−' }}
                        R$ {{ number_format($tx->amount_cents / 100, 2, ',', '.') }}
                    </p>
                </div>
                <div class="tx-actions">
                    <a class="btn btn-ghost" href="{{ route('hub.transactions.edit', $tx) }}">Editar</a>
                    <form method="post" action="{{ route('hub.transactions.destroy', $tx) }}" onsubmit="return confirm('Excluir este lançamento?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-danger">Excluir</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="card">
                <p class="sub" style="margin:0;">Nenhum lançamento neste filtro. Crie o primeiro gasto ou receita.</p>
            </div>
        @endforelse
    </div>

    @if ($transactions->hasMorePages() || ! $transactions->onFirstPage())
        <div class="card" style="margin-top:var(--space);display:flex;gap:0.75rem;justify-content:space-between;">
            @if ($transactions->onFirstPage())
                <span class="sub" style="margin:0;">Anterior</span>
            @else
                <a href="{{ $transactions->previousPageUrl() }}">Anterior</a>
            @endif
            @if ($transactions->hasMorePages())
                <a href="{{ $transactions->nextPageUrl() }}">Próxima</a>
            @else
                <span class="sub" style="margin:0;">Próxima</span>
            @endif
        </div>
    @endif
@endsection
