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
6. Copiar Client ID / Secret (nunca no git / chat)

## VPS — onde colocar os secrets

O `docker compose --env-file .env.prod` **só** injeta variáveis listadas em `docker-compose.prod.yml`.  
Coloque o Google em **`/opt/inovahub/.env.prod`** (recomendado):

```text
GOOGLE_CLIENT_ID=....apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=....
GOOGLE_REDIRECT_URI=https://inovahub.inovatitech.com.br/hub/google/callback
GOOGLE_CALENDAR_CONSENT_VERSION=gcal-1.0
```

Opcional (espelho): as mesmas linhas em `/opt/inovahub/app/.env` (como Pluggy/WhatsApp).

Depois:

```bash
cd /opt/inovahub
git pull origin main
docker compose -f docker-compose.prod.yml --env-file .env.prod up -d app worker
```

Conferir **sem** exibir o secret:

```bash
docker compose -f docker-compose.prod.yml --env-file .env.prod exec app php artisan tinker --execute="echo filled(config('services.google.client_id')) && filled(config('services.google.client_secret')) ? 'GOOGLE_OK' : 'GOOGLE_MISSING';"
```

Esperado: `GOOGLE_OK`. Se `GOOGLE_MISSING`, as vars não entraram no container.

## Erros comuns

| Mensagem | Causa | Ação |
|----------|--------|------|
| Google OAuth não configurado | `GOOGLE_CLIENT_ID/SECRET` vazios no container | Preencher `.env.prod` + `up -d app worker` |
| Estado OAuth inválido | Callback sem sessão/`state` (URL aberta direto, cookie perdido, tentativa antes de configurar) | Ignorar; conectar de novo pelo Hub **depois** de `GOOGLE_OK` |
| redirect_uri_mismatch | URI no GCP ≠ `GOOGLE_REDIRECT_URI` | Igualar exatamente o callback do Hub |

## Fluxo produto

1. Owner abre `/hub/google` (deve mostrar checkbox, **não** “não configurado”)  
2. Aceita checkbox `gcal-1.0`  
3. **Conectar Google** → `accounts.google.com`  
4. Callback troca `code` → `oauth_tokens` (encrypted) + `consent_logs`  
5. D30: sync eventos → UI agenda  

## Critério D29

Botão **Conectar Google** inicia OAuth (URL `accounts.google.com` com `client_id` e escopos).
