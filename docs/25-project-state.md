# Estado real do projeto (atualizar a cada mudança)

**Última atualização:** 2026-07-22  
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
| `inovahub.inovatitech.com.br` | Pode ter ficado NXDOMAIN se CNAME sumiu — **verificar** |
| `api-inovahub.inovatitech.com.br` | DNS CF ok; origem depende de Tunnel + `:8088` |
| Tunnel nome | `inovahub` |
| Service origem | `http://127.0.0.1:8088` |
| SSL edge | Full (origem HTTP via tunnel) |

## VPS (`128.140.77.31`)

| Item | Status |
|------|--------|
| Path | `/opt/inovahub` |
| `cloudflared.service` | Instalado com token do tunnel `inovahub` (active) |
| Compose prod | `docker-compose.prod.yml` + `.env.prod` |
| Containers | `inovahub-app`, `inovahub-postgres`, `inovahub-redis` |
| `composer install` | OK (vendor reinstalado no container) |
| `php artisan migrate --force` | OK (users/cache/jobs) |
| `curl http://127.0.0.1:8088/` | **500** — porta OK; APP_KEY sem prefixo `base64:` e/ou `.env.prod` sem `DB_PASSWORD` |

## Local (Docker Desktop)

| Item | Status |
|------|--------|
| Projeto Compose | `inovahub` |
| Containers nomeados | `inovahub-*` |
| Bind host 8092/5442/6392 | Frequentemente falha; rede interna OK |

## Próximo passo operacional

1. Corrigir `APP_KEY=base64:...` em `app/.env` e `.env.prod` (regenerar se vazou).
2. Garantir `DB_PASSWORD=` preenchido no `.env.prod` (compose exige).
3. Ver `storage/logs/laravel.log` e retestar `curl` → 200.
4. Confirmar DNS CNAME + Tunnel → HTTPS sem 502/530.
