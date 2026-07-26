# 34 — Google OAuth / Agenda (D29)

**Status:** wiring Hub live; operador cria o client no Google Cloud  
**Consentimento:** `gcal-1.0` (`GOOGLE_CALENDAR_CONSENT_VERSION`)  
**Hub:** `/hub/google` — tela de consentimento + **Conectar Google**

## Escopos (mínimos)

- `openid` · `email` (identificar conta)
- `https://www.googleapis.com/auth/calendar.events`
- **Fora do MVP:** People API / contatos

## Operador — Google Cloud Console

1. Criar (ou usar) projeto GCP  
2. APIs & Services → Enable **Google Calendar API**  
3. OAuth consent screen (External / Testing): app Inova Hub; escopos acima  
4. Credentials → OAuth 2.0 Client ID → **Web application**  
5. Authorized redirect URIs:
   - Produção: `https://inovahub.inovatitech.com.br/hub/google/callback`
   - Local (se testar): `http://127.0.0.1:8092/hub/google/callback`  
6. Copiar Client ID / Secret para `.env.prod` / `.env` (nunca no git)

```text
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=https://inovahub.inovatitech.com.br/hub/google/callback
GOOGLE_CALENDAR_CONSENT_VERSION=gcal-1.0
```

7. VPS: atualizar `.env.prod`, rebuild/restart `app` (não precisa migrate se já rodou D29)

## Fluxo produto

1. Owner abre `/hub/google`  
2. Aceita checkbox `gcal-1.0`  
3. **Conectar Google** → redirect Google  
4. Callback troca `code` → tokens em `oauth_tokens` (**encrypted**) + `consent_logs`  
5. D30: sync eventos → UI agenda  

## Critério D29

Botão **Conectar Google** inicia OAuth (URL `accounts.google.com` com `client_id` e escopos).
