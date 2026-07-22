# Estado real do projeto (atualizar a cada mudança)

**Última atualização:** 2026-07-22 (origem local VPS HTTP 200)  
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
| `curl http://127.0.0.1:8088/` | **200** — APP_KEY corrigida (`base64:…`) |

## Local (Docker Desktop)

| Item | Status |
|------|--------|
| Projeto Compose | `inovahub` |
| Containers nomeados | `inovahub-*` |
| Bind host 8092/5442/6392 | Frequentemente falha; rede interna OK |

## Próximo passo operacional

1. Validar público: `curl -I https://inovahub.inovatitech.com.br` e `api-inovahub` (não 502/530).
2. Se NXDOMAIN no Hub: recriar CNAME no tunnel `inovahub`.
3. Seguir MVP (auth/tenancy) em `docs/15-day-by-day-mvp.md`.
