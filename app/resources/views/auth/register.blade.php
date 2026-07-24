@extends('layouts.hub')

@section('title', 'Criar conta — Inova Hub')

@section('content')
    <p class="brand">Inova Hub</p>
    <p class="sub">Crie sua conta e organização.</p>
    <div class="card">
        @if ($errors->any())
            <ul class="errors">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
        <form method="post" action="{{ route('register.store') }}">
            @csrf
            <label for="name">Nome</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required autocomplete="name">

            <label for="email">E-mail</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username">

            <label for="organization_name">Organização (opcional)</label>
            <input id="organization_name" name="organization_name" type="text" value="{{ old('organization_name') }}">

            <label for="password">Senha</label>
            <input id="password" name="password" type="password" required autocomplete="new-password">

            <label for="password_confirmation">Confirmar senha</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">

            <button type="submit">Criar conta</button>
        </form>
        <p class="footer-link">Já tem conta? <a href="{{ route('login') }}">Entrar</a></p>
    </div>
@endsection
