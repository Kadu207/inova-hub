# Estado real do projeto (atualizar a cada mudança)

**Última atualização:** 2026-07-24 (D10 OTP no código; Meta tokens pendentes)  
**Regra:** agentes devem ler isto e **corrigir** após qualquer deploy/DNS/tunnel — não alucinar.

## DNS / VPS

| Item | Status |
|------|--------|
| Hub + API HTTPS | **200** ✅ |
| `/opt/inovahub` Git | OK |
| D08/D09 em prod | OK |
| D10 migrate na VPS | **Pendente** (`git pull` + migrate) |

## Código

| Item | Status |
|------|--------|
| D10 OTP + identities | No `main` · `WhatsappOtpTest` 2 passed |
| Confirmar (dev) | Ativo se `WHATSAPP_TOKEN` vazio |
| Webhook Meta (D11) | Ainda não |

## Operador

1. Seguir `docs/27-meta-whatsapp-setup.md` (WABA + tokens no `.env`).
2. `git pull` + migrate na VPS.
3. Testar `/hub/whatsapp` (OTP + confirmar dev).
4. Depois: D11 webhook.
