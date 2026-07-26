# 14 — Checklist de pré-requisitos (Inova Hub / Finova)

**Atualizado:** 26/07/2026 (fim Semana 4 OF — ver [33-week4-open-finance-qa.md](33-week4-open-finance-qa.md))  
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
| Cloudflare DNS | [x] Tunnel `inovahub` | Hub + API HTTPS **200** |
| VPS Hetzner | [x] IP conhecido | `128.140.77.31` · **80/443 outros projetos** → Tunnel → `127.0.0.1:8088` |
| SSH | [x] PuTTY | User: `gestaoti` · path `/opt/inovahub` |

### DNS Cloudflare

| Tipo | Nome | Destino | Proxy | Status |
|------|------|---------|-------|--------|
| CNAME | `inovahub` | CF Tunnel | Proxied | [x] HTTPS 200 |
| CNAME | `api-inovahub` | CF Tunnel | Proxied | [x] HTTPS 200 |
| A/CNAME | `api.inovahub` | — | — | [x] **não usar** (Universal SSL Free não cobre 2 níveis) |
| TXT | SPF / DKIM Resend | conforme Resend | DNS only | [ ] |

SSL Cloudflare: edge via Tunnel — [x]  
Origem: Tunnel only (não disputa 80/443) — [x]

---

## 2. Contas P0 (abrir em paralelo)

### 2.1 Meta WhatsApp (Finova)

| Item | Status | Ref |
|------|--------|-----|
| Meta Business Manager | [ ] | Operador |
| App em developers.facebook.com | [ ] | App ID: ___ |
| WhatsApp product + WABA | [ ] | |
| Número dedicado (não pessoal) | [ ] | sandbox OK para D10–D14 |
| Display name Finova | [ ] | |
| Webhook | [ ] código ready | `https://api-inovahub.inovatitech.com.br/webhooks/whatsapp` |
| Verify token + secrets no `.env` | [ ] | `META_APP_SECRET`, `WHATSAPP_TOKEN`, `WHATSAPP_PHONE_NUMBER_ID`, `WHATSAPP_VERIFY_TOKEN` — guia [27](27-meta-whatsapp-setup.md) |

### 2.2 Pluggy (bancos Open Finance)

| Item | Status | Ref |
|------|--------|-----|
| Conta Pluggy | [x] | sandbox em uso na VPS |
| Application sandbox | [x] | `PLUGGY_CLIENT_ID`, `PLUGGY_CLIENT_SECRET` |
| Application production | [ ] | após KYC |
| Webhook `https://api-inovahub.inovatitech.com.br/webhooks/pluggy` | [x] | GET ping + POST events |
| Widget Connect no Hub | [x] | `/hub/connections` |
| Consentimento versionado (`of-1.0`) | [x] | `/legal/open-finance` · `consent_logs` |

#### 2.2.1 Instituições sandbox testadas (Semana 4)

Preencher após smoke na VPS. Não inventar resultado — marcar só o que foi exercitado.

| Instituição / conector | Ambiente | Conectar | Sync Hub | Finova saldo | Categoria | Revogar | Data | Nota |
|------------------------|----------|----------|----------|--------------|-----------|---------|------|------|
| Pluggy Bank (sandbox) | sandbox | [ ] | [ ] | [ ] | [ ] | [ ] | | |
| Outro conector BR | sandbox | [ ] | [ ] | [ ] | [ ] | [ ] | | |

Guia: [31-pluggy-setup.md](31-pluggy-setup.md) · QA: [33-week4-open-finance-qa.md](33-week4-open-finance-qa.md) · Termos: [32-open-finance-terms.md](32-open-finance-terms.md)

### 2.3 Asaas (billing)

| Item | Status | Ref |
|------|--------|-----|
| Conta Asaas (CNPJ/CPF) | [ ] | |
| API Key sandbox | [ ] | `ASAAS_API_KEY` |
| API Key produção | [ ] | |
| Webhook cobranças | [ ] | `https://api-inovahub.inovatitech.com.br/webhooks/asaas` |
| Planos Pessoal / Família criados | [ ] | |

**Stripe:** fora do MVP — [ ] (adiado P1 internacional)

### 2.4 Demais SaaS

| Conta | Status | Env vars |
|-------|--------|----------|
| GitHub / GitLab | [x] | `Kadu207/inova-hub` |
| OpenAI ou Groq | [ ] | `OPENAI_API_KEY` / `GROQ_API_KEY` |
| Whisper (OpenAI) | [ ] | mesma key se OpenAI |
| Resend | [ ] | `RESEND_API_KEY` |
| Google Cloud OAuth | [ ] | `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` |
| Cloudflare R2 (pode ser P1) | [ ] | `R2_*` |

### 2.5 Legal

| Item | Status |
|------|--------|
| CNPJ / razão social definida | [ ] |
| Termos de Uso no ar | [ ] parcial — OF em `/legal/open-finance` |
| Política de Privacidade (OF + WA + IA + Asaas) | [x] rascunho `/legal/privacy` |
| Consentimento Open Finance versionado | [x] `of-1.0` + `consent_logs` |
| DPO / e-mail titular | [ ] |

---

## 3. VPS Hetzner

| Tarefa | Status |
|--------|--------|
| Docker Engine + Compose | [x] |
| Firewall: 22 + Tunnel (80/443 outros) | [x] parcial |
| User deploy sem root diário | [x] `gestaoti` |
| Swap se RAM ≤ 2 GB | [ ] |
| Snapshot semanal | [ ] |
| Compose: `app`, `worker`, `postgres`, `redis` | [x] (Horizon depois) |
| Healthcheck `/up` | [x] via Hub/API 200 |

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

## 5. Caminho crítico (pós Semana 2)

1. [ ] Meta WhatsApp tokens + webhook verify (P0) — [27](27-meta-whatsapp-setup.md)  
2. [ ] Pluggy KYC  
3. [ ] Asaas cadastro  
4. [x] DNS + VPS Docker + Tunnel  

Ver calendário: [15-day-by-day-mvp.md](15-day-by-day-mvp.md) · Retro: [28-week2-retro.md](28-week2-retro.md)
