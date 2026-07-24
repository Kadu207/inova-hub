# Estado real do projeto (atualizar a cada mudança)

**Última atualização:** 2026-07-24 (VPS com Git + D08/D09 migrados; Hub login HTTPS 200)  
**Regra:** agentes devem ler isto e **corrigir** após qualquer deploy/DNS/tunnel — não alucinar.

## Cloudflare / DNS

| Host | Status |
|------|--------|
| `inovahub.inovatitech.com.br` | **HTTPS 200** ✅ |
| `api-inovahub.inovatitech.com.br` | **HTTPS 200** ✅ |
| Tunnel | `inovahub` → `http://127.0.0.1:8088` |

## VPS (`/opt/inovahub`)

| Item | Status |
|------|--------|
| Git | OK (`origin` GitHub) |
| `composer install` | OK (Sanctum incluso) |
| Migrate D08/D09 | OK (orgs/memberships/notes + personal_access_tokens) |
| Seed | OK |
| `curl :8088` | **302** (redirect auth) ✅ |
| `https://…/login` | **HTTP 200** ✅ |

## Código

| Item | Status |
|------|--------|
| D08 multi-tenant | Produção |
| D09 Auth Hub | Produção (register/login/logout) |

## Próximo passo

1. Operador: testar no browser `/register` e `/login`.
2. Agente/Operador: **D10** OTP WhatsApp (precisa Meta WABA / tokens).
