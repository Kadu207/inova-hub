@extends('layouts.hub')

@section('title', ($transaction ? 'Editar' : 'Novo').' lançamento — Inova Hub')

@section('content')
    <div class="topbar">
        <p class="brand" style="margin:0;">{{ $transaction ? 'Editar' : 'Novo' }} lançamento</p>
        <a class="btn btn-ghost" href="{{ route('hub.transactions.index') }}" style="width:auto;margin:0;">Voltar</a>
    </div>

    <div class="card">
        @if ($errors->any())
            <ul class="errors">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="post" action="{{ $transaction ? route('hub.transactions.update', $transaction) : route('hub.transactions.store') }}">
            @csrf
            @if ($transaction)
                @method('PUT')
            @endif

            <label for="type">Tipo</label>
            <select id="type" name="type" required>
                <option value="expense" @selected(old('type', $transaction->type ?? $defaultType) === 'expense')>Despesa</option>
                <option value="income" @selected(old('type', $transaction->type ?? $defaultType) === 'income')>Receita</option>
            </select>

            <label for="category_id">Categoria</label>
            <select id="category_id" name="category_id" required>
                @foreach ($categories as $category)
                    <option
                        value="{{ $category->id }}"
                        data-kind="{{ $category->kind }}"
                        @selected(old('category_id', $transaction->category_id ?? '') === $category->id)
                    >
                        {{ $category->name }} ({{ $category->kind === 'income' ? 'receita' : 'despesa' }})
                    </option>
                @endforeach
            </select>

            <label for="amount">Valor (R$)</label>
            <input
                id="amount"
                name="amount"
                type="text"
                inputmode="decimal"
                placeholder="45,90"
                value="{{ old('amount', $transaction ? number_format($transaction->amount_cents / 100, 2, ',', '.') : '') }}"
                required
            >

            <label for="occurred_at">Data</label>
            <input
                id="occurred_at"
                name="occurred_at"
                type="date"
                value="{{ old('occurred_at', ($transaction?->occurred_at ?? now())->timezone(config('app.timezone'))->format('Y-m-d')) }}"
                required
            >

            <label for="description">Descrição</label>
            <input
                id="description"
                name="description"
                type="text"
                maxlength="255"
                value="{{ old('description', $transaction->description ?? '') }}"
                placeholder="Ex.: Almoço"
            >

            <button type="submit">{{ $transaction ? 'Salvar' : 'Criar lançamento' }}</button>
        </form>
    </div>
@endsection
