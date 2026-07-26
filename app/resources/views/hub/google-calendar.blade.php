@extends('layouts.hub')

@section('title', 'Google Agenda — Inova Hub')

@section('content')
    <div class="topbar">
        <p class="brand" style="margin:0;">Agenda</p>
        <a class="btn btn-ghost" href="{{ route('hub.home') }}" style="width:auto;margin:0;padding:0 1rem;">Início</a>
    </div>

    @if (session('status'))
        <p class="sub" role="status" style="margin:0 0 var(--space);">{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <ul class="errors">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <div class="card">
        <p class="sub" style="margin-top:0;">Google Agenda (OAuth)</p>
        <p>Conecte sua conta Google para a Finova e o Hub usarem a agenda. Escopos mínimos — sem People API / contatos.</p>
        <p class="sub" style="margin:0.75rem 0 0;">
            Consentimento versão <strong>{{ $consentVersion }}</strong>
        </p>

        <p class="sub" style="margin:1rem 0 0.35rem;">Escopos solicitados</p>
        <ul class="sub" style="margin:0; padding-left:1.1rem;">
            @foreach ($scopes as $scope)
                <li><code style="color:inherit;">{{ $scope }}</code></li>
            @endforeach
        </ul>

        @if ($connection)
            <p style="margin-top:1rem;">Status: <strong>conectado</strong></p>
            @if ($connection->provider_account_email)
                <p class="sub" style="margin:0.35rem 0 0;">Conta: {{ $connection->provider_account_email }}</p>
            @endif
            <p class="sub" style="margin:0.35rem 0 0;">
                Desde {{ $connection->connected_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}
            </p>
            <form method="post" action="{{ route('hub.google.disconnect') }}" style="margin-top:1rem;"
                  onsubmit="return confirm('Desconectar Google Agenda deste Hub?');">
                @csrf
                <button type="submit" class="btn-ghost" style="width:auto;margin:0;">Desconectar</button>
            </form>
            <p class="sub" style="margin-top:1rem;">Sincronização de eventos (lista no Hub) chega no D30.</p>
        @elseif (! $configured)
            <p class="errors" style="margin-top:1rem;">
                Google OAuth não configurado no servidor. Em <code>/opt/inovahub/.env.prod</code> defina
                <code>GOOGLE_CLIENT_ID</code>, <code>GOOGLE_CLIENT_SECRET</code> e
                <code>GOOGLE_REDIRECT_URI</code>, depois
                <code>docker compose -f docker-compose.prod.yml --env-file .env.prod up -d app worker</code>.
                Guia: docs/34-google-oauth-setup.md
            </p>
        @else
            <form method="post" action="{{ route('hub.google.redirect') }}" style="margin-top:1rem;">
                @csrf
                <label style="display:flex; gap:0.65rem; align-items:flex-start; cursor:pointer;">
                    <input type="checkbox" name="consent_accepted" value="1" required
                           style="width:auto; min-height:auto; margin-top:0.2rem;">
                    <span class="sub" style="margin:0;">
                        Li e aceito o uso da Google Agenda (versão {{ $consentVersion }}) para criar/consultar
                        compromissos no Inova Hub e na Finova. Posso desconectar a qualquer momento.
                    </span>
                </label>
                <button type="submit" id="btn-connect-google">Conectar Google</button>
            </form>
        @endif
    </div>
@endsection
