@extends('layouts.hub')

@section('title', 'Entrar — Inova Hub')

@section('content')
    <p class="brand">Inova Hub</p>
    <p class="sub">Acesse o painel.</p>
    <div class="card">
        @if ($errors->any())
            <ul class="errors">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
        <form method="post" action="{{ route('login.store') }}">
            @csrf
            <label for="email">E-mail</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username">

            <label for="password">Senha</label>
            <input id="password" name="password" type="password" required autocomplete="current-password">

            <label style="display:flex;gap:0.5rem;align-items:center;min-height:44px;">
                <input type="checkbox" name="remember" value="1" style="width:auto;min-height:auto;">
                Lembrar-me
            </label>

            <button type="submit">Entrar</button>
        </form>
        <p class="footer-link">Novo por aqui? <a href="{{ route('register') }}">Criar conta</a></p>
    </div>
@endsection
