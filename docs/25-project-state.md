# Estado real do projeto (atualizar a cada mudança)

**Última atualização:** 2026-07-24 (Hub HTTPS 200; D09 Auth no código; VPS sem `.git`)  
**Regra:** agentes devem ler isto e **corrigir** após qualquer deploy/DNS/tunnel — não alucinar.

## Cloudflare / DNS

| Host | Status |
|------|--------|
| `inovahub.inovatitech.com.br` | **HTTPS 200** ✅ D06 fechado |
| `api-inovahub.inovatitech.com.br` | **HTTPS 200** ✅ |

## VPS

| Item | Status |
|------|--------|
| `/opt/inovahub` | **Git OK** · migrations D08/D09 no disco |
| D08/D09 na VPS | **Pendente** — `composer install` incompleto (`vendor` ausente → curl `000`) |

## Código (`main`)

| Item | Status |
|------|--------|
| D08 multi-tenant | OK · testes OK |
| D09 Auth Hub | Sanctum + register/login/logout + rate limit · `AuthFlowTest` 3 passed |

## Próximo passo operacional

1. Operador: sync git na VPS (comandos abaixo / `docs/24-deploy-vps-putty.md`).
2. Validar https://inovahub.inovatitech.com.br/register e /login.
3. D10 OTP WhatsApp (precisa Meta).
