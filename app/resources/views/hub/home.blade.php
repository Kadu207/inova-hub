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
        <p class="sub">Próximo: vincular WhatsApp (Finova) — D10.</p>
    </div>
@endsection
