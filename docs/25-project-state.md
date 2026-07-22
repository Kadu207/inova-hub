# Estado real do projeto (atualizar a cada mudança)

**Última atualização:** 2026-07-22 (API pública 200; Hub NXDOMAIN)  
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
| `inovahub.inovatitech.com.br` | **NXDOMAIN** — falta CNAME / Public Hostname DNS |
| `api-inovahub.inovatitech.com.br` | **HTTPS 200** (Tunnel + Laravel + PHP 8.4) |
| Tunnel nome | `inovahub` |
| Service origem | `http://127.0.0.1:8088` |
| SSL edge | Full (origem HTTP via tunnel) |

## VPS (`128.140.77.31`)

| Item | Status |
|------|--------|
| Path | `/opt/inovahub` |
| `cloudflared.service` | Active (tunnel `inovahub`) |
| Compose prod | OK |
| Containers | `inovahub-app`, `inovahub-postgres`, `inovahub-redis` |
| `composer install` | OK |
| `php artisan migrate --force` | OK |
| `curl http://127.0.0.1:8088/` | **200** |

## Local (Docker Desktop)

| Item | Status |
|------|--------|
| Projeto Compose | `inovahub` |
| Containers nomeados | `inovahub-*` |
| Bind host 8092/5442/6392 | Frequentemente falha; rede interna OK |

## Próximo passo operacional

1. Recriar DNS `inovahub` (CNAME Proxied → mesmo tunnel de `api-inovahub`).
2. `curl -I https://inovahub.inovatitech.com.br` → 200.
3. Continuar MVP (auth/tenancy).
