# Estado real do projeto (atualizar a cada mudança)

**Última atualização:** 2026-07-25 (D14 retro Semana 2; D13 live na VPS)  
**Regra:** agentes devem ler isto e **corrigir** após qualquer deploy/DNS/tunnel — não alucinar.

## DNS / VPS

| Item | Status |
|------|--------|
| Hub + API HTTPS | **200** ✅ |
| `/opt/inovahub` Git | OK |
| D08–D13 em prod | OK (`3a370f6`) |
| Semana 2 | **Fechada no código**; E2E Finova bloqueada por tokens Meta (P0) |

## Código

| Item | Status |
|------|--------|
| Suite local | **23 passed** |
| Retro | `docs/28-week2-retro.md` |
| Checklist | `docs/14-prerequisites-checklist.md` atualizado |
| Seed login | `admin@inovahub.test` / `password` (só teste) |

## Operador

1. Login Hub: https://inovahub.inovatitech.com.br/login  
2. Se seed sumiu: `docker compose -f docker-compose.prod.yml --env-file .env.prod exec app php artisan db:seed --force`  
3. Meta tokens + webhook (P0) — `docs/27-meta-whatsapp-setup.md`  
4. Próximo: **D15** domínio transações/categorias.
