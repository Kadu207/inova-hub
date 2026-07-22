# 14 — Checklist de pré-requisitos (Inova Hub / Finova)

**Atualizado:** 21/07/2026  
**Donos:** Operador = você · Agente = Cursor  
**Regra:** não colar secrets neste arquivo — só status e nomes de variáveis.

## Decisões travadas

| Tema | Decisão |
|------|---------|
| Pagamentos MVP | **Asaas** (não Stripe; não ambos) |
| Bancos | **Pluggy** Open Finance (somente leitura) |
| Hosting | Hetzner VPS + Docker Compose |
| DNS/SSL/R2 | Cloudflare |
| E-mail | Resend |
| IA | LLM + Whisper |
| Agenda | Google Calendar OAuth (escopos mínimos) |
| Stack | Laravel + PostgreSQL + Redis + Horizon |

---

## 1. Infra que você já tem

| Item | Status | Valor / nota |
|------|--------|--------------|
| Domínio | [x] Registrado | `inovahub.inovatitech.com.br` |
| Cloudflare DNS | [x] A Proxied OK | Resolve via CF anycast — ver [19-dns-cloudflare.md](19-dns-cloudflare.md) |
| VPS Hetzner | [x] IP conhecido | IP: `128.140.77.31` · **80/443 já em uso** → vhost / 8443+Origin Rule / Tunnel |
| SSH | [ ] Chave | User: _______________ |

### DNS Cloudflare

| Tipo | Nome | Destino | Proxy | Status |
|------|------|---------|-------|--------|
| A/CNAME | `inovahub` | CF / Tunnel | Proxied | [x] DNS+TLS edge |
| A/CNAME | `api-inovahub` | CF / Tunnel | Proxied | [ ] criar (substituir `api.inovahub`) |
| A | `api.inovahub` | — | — | [x] DNS ok, **TLS quebrado** — remover |
| TXT | SPF / DKIM Resend | conforme Resend | DNS only | [ ] |

SSL Cloudflare: **Full** agora → **Full (strict)** após cert na origem — [ ]  
Origem (80/443 ocupadas): escolher A/B/C em [19-dns-cloudflare.md](19-dns-cloudflare.md) — [ ]

---

## 2. Contas P0 (abrir em paralelo)

### 2.1 Meta WhatsApp (Finova)

| Item | Status | Ref |
|------|--------|-----|
| Meta Business Manager | [ ] | |
| App em developers.facebook.com | [ ] | App ID: ___ |
| WhatsApp product + WABA | [ ] | |
| Número dedicado (não pessoal) | [ ] | +55 ___ |
| Display name Finova | [ ] | |
| Webhook `https://api.<dominio>/webhooks/whatsapp` | [ ] | |
| Verify token + App Secret no `.env` | [ ] | `META_APP_SECRET`, `WHATSAPP_TOKEN`, `WHATSAPP_PHONE_ID` |

### 2.2 Pluggy (bancos Open Finance)

| Item | Status | Ref |
|------|--------|-----|
| Conta Pluggy | [ ] | |
| Application sandbox | [ ] | `PLUGGY_CLIENT_ID`, `PLUGGY_CLIENT_SECRET` |
| Application production | [ ] | após KYC |
| Webhook `https://api.<dominio>/webhooks/pluggy` | [ ] | |
| Widget Connect no Hub | [ ] | |

### 2.3 Asaas (billing)

| Item | Status | Ref |
|------|--------|-----|
| Conta Asaas (CNPJ/CPF) | [ ] | |
| API Key sandbox | [ ] | `ASAAS_API_KEY` |
| API Key produção | [ ] | |
| Webhook cobranças | [ ] | `https://api.<dominio>/webhooks/asaas` |
| Planos Pessoal / Família criados | [ ] | |

**Stripe:** fora do MVP — [ ] (adiado P1 internacional)

### 2.4 Demais SaaS

| Conta | Status | Env vars |
|-------|--------|----------|
| GitHub repo privado | [ ] | |
| OpenAI ou Groq | [ ] | `OPENAI_API_KEY` / `GROQ_API_KEY` |
| Whisper (OpenAI) | [ ] | mesma key se OpenAI |
| Resend | [ ] | `RESEND_API_KEY` |
| Google Cloud OAuth | [ ] | `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` |
| Cloudflare R2 (pode ser P1) | [ ] | `R2_*` |

### 2.5 Legal

| Item | Status |
|------|--------|
| CNPJ / razão social definida | [ ] |
| Termos de Uso no ar | [ ] |
| Política de Privacidade (OF + WA + IA + Asaas) | [ ] |
| Consentimento Open Finance versionado | [ ] |
| DPO / e-mail titular | [ ] |

---

## 3. VPS Hetzner

| Tarefa | Status |
|--------|--------|
| Docker Engine + Compose | [ ] |
| Firewall: 22, 80, 443 only | [ ] |
| User deploy sem root diário | [ ] |
| Swap se RAM ≤ 2 GB | [ ] |
| Snapshot semanal | [ ] |
| Compose: `app`, `worker`, `postgres`, `redis`, `horizon` | [ ] |
| Healthcheck `/up` | [ ] |

**RAM mínima recomendada:** 4 GB.

---

## 4. Variáveis `.env` (nomes apenas)

```text
APP_URL=
APP_KEY=
DB_*
REDIS_*
META_APP_SECRET=
WHATSAPP_TOKEN=
WHATSAPP_PHONE_NUMBER_ID=
WHATSAPP_VERIFY_TOKEN=
PLUGGY_CLIENT_ID=
PLUGGY_CLIENT_SECRET=
ASAAS_API_KEY=
ASAAS_WEBHOOK_TOKEN=
OPENAI_API_KEY=
RESEND_API_KEY=
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
```

---

## 5. Caminho crítico (começar no D01)

1. [ ] Meta WhatsApp KYC  
2. [ ] Pluggy KYC  
3. [ ] Asaas cadastro  
4. [ ] DNS + VPS Docker  

Ver calendário completo: [15-day-by-day-mvp.md](15-day-by-day-mvp.md)
