# Estado real do projeto (atualizar a cada mudança)

**Última atualização:** 2026-07-26 (D29 Google OAuth wiring)  
**Regra:** agentes devem ler isto e **corrigir** após qualquer deploy/DNS/tunnel — não alucinar.

## DNS / VPS

| Item | Status |
|------|--------|
| Hub + API HTTPS | **200** ✅ |
| Origem | Cloudflare Tunnel `inovahub` → `127.0.0.1:8088` |
| D28 na VPS | Deploy + migrate feitos (`c9e148e`) |
| D29 na VPS | Deploy + migrate feitos (`465cec2`); falta `GOOGLE_*` no `.env.prod` se ainda vazio |

## Arquitetura

| Item | Status |
|------|--------|
| System Design | [20-system-design.md](20-system-design.md) **v1.2** |
| Google OAuth setup | [34-google-oauth-setup.md](34-google-oauth-setup.md) |

## Código

| Item | Status |
|------|--------|
| `/hub/google` | Consent `gcal-1.0` + **Conectar Google** → OAuth |
| `oauth_tokens` | Tokens Google encrypted at rest |
| Sync eventos | D30 |

## Operador

1. Criar OAuth client GCP (guia doc 34)  
2. Preencher `GOOGLE_*` no `.env.prod`  
3. Deploy D29 + migrate  
4. Smoke: Início → Conectar Google → redirect `accounts.google.com`  
5. Próximo: **D30** sync Calendar → Hub
