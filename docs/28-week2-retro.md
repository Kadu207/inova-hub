# 28 — Retrospectiva Semana 2 (D08–D14)

**Data:** 2026-07-25  
**Escopo:** Multi-tenant → Auth → OTP → Webhook → Finova oi/ajuda → Card Hub  
**Suite local:** `php artisan test` → **23 passed** (89 assertions)

## Entregue

| Dia | Entrega | Status VPS |
|-----|---------|------------|
| D08 | Org + membership + RLS + scopes | Live |
| D09 | Register/login/logout + rate limit | Live |
| D10 | OTP Hub + `whatsapp_identities` | Live |
| D11 | Webhook Meta + assinatura + `wamid` + worker | Live |
| D12 | Intent oi/ajuda + Graph send | Live (código); send real depende de tokens) |
| D13 | Card status Finova em `/hub` | Live (`3a370f6`) |
| D14 | Suite + checklist Meta + este doc | Docs/`main` |

## Bugs / riscos

### P0 (bloqueia E2E Finova no Zap)

| ID | Item | Nota |
|----|------|------|
| P0-1 | Tokens Meta no `.env` VPS | Sem `WHATSAPP_TOKEN` / `PHONE_NUMBER_ID` / `META_APP_SECRET` o job não envia (`send_skipped_missing_config`) |
| P0-2 | Webhook Meta verify | Callback deve ser `https://api-inovahub.inovatitech.com.br/webhooks/whatsapp` + `WHATSAPP_VERIFY_TOKEN` igual ao Meta |
| P0-3 | Worker sempre up | `inovahub-worker` precisa estar running para D12 |

### P1 (não bloqueia Hub UI)

| ID | Item | Nota |
|----|------|------|
| P1-1 | Token temporário Meta | Expira — trocar por System User permanente |
| P1-2 | Seed só teste | `admin@inovahub.test` / `password` — não usar em produção real |
| P1-3 | Confirm (dev) OTP | Remover/desligar quando Graph + webhook estiverem estáveis |
| P1-4 | Docker Desktop local | Portas host às vezes não publicam — validar com `docker exec` |
| P1-5 | KYC / número produção | Sandbox basta até D14; produção WhatsApp ainda pendente |

## Checklist Meta (produção / sandbox)

Ver também [27-meta-whatsapp-setup.md](27-meta-whatsapp-setup.md).

- [ ] App + WABA criados
- [ ] `META_APP_SECRET`, `WHATSAPP_TOKEN`, `WHATSAPP_PHONE_NUMBER_ID`, `WHATSAPP_VERIFY_TOKEN` no `.env` da VPS
- [ ] Webhook Verify and save OK (GET challenge → corpo = challenge)
- [ ] Campo `messages` inscrito
- [ ] Número de teste na allowlist
- [ ] Worker up; enviar “oi” → resposta < 8s
- [ ] OTP Hub → 6 dígitos no Zap → “vinculado” + card **conectado** no Hub

## Decisão de saída semana 2

Semana 2 **fechada no código e nos testes**. Bloqueio restante é **operacional Meta** (P0-1/P0-2), não feature de produto.

**Próximo:** Semana 3 — **D15** domínio transações/categorias.
