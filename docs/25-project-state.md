# Estado real do projeto (atualizar a cada mudança)

**Última atualização:** 2026-07-24 (D08 multi-tenant no código; Hub DNS ainda NXDOMAIN)  
**Regra:** agentes devem ler isto e **corrigir** após qualquer deploy/DNS/tunnel — não alucinar.

## Repositórios

| Item | Valor |
|------|-------|
| GitHub | https://github.com/Kadu207/inova-hub |
| GitLab | https://gitlab.com/Kadu207/inova-hub |
| Branch | `main` |

## Cloudflare / DNS

| Host | Status conhecido |
|------|------------------|
| `inovahub.inovatitech.com.br` | **NXDOMAIN** — falta CNAME/Public Hostname `inovahub` (guia: `docs/26-dns-hub-step-by-step.md`) |
| `api-inovahub.inovatitech.com.br` | **HTTPS 200** (Tunnel + Laravel) ✅ |
| Tunnel nome | `inovahub` |
| Service origem | `http://127.0.0.1:8088` |

## VPS (`128.140.77.31`)

| Item | Status |
|------|--------|
| Path | `/opt/inovahub` |
| Tunnel / `:8088` | OK · migrate base OK |
| D08 migrations | **pendente na VPS** (`git pull` + `migrate`) |

## D08 (código)

| Item | Status |
|------|--------|
| `organizations` / `memberships` / `tenant_notes` | Migrations + models |
| `TenantContext` + Global Scope + Policies | OK |
| Middleware alias `tenant` | OK |
| Seed `admin@inovahub.test` | OK |
| `TenantIsolationTest` | **4 passed** |

## Próximo passo operacional

1. Operador: fechar DNS Hub (`docs/26-dns-hub-step-by-step.md`) → `curl -I` 200.
2. VPS: `git pull` + `docker compose … exec app php artisan migrate --force` + seed.
3. Agente: D09 Auth Hub (Sanctum register/login).
