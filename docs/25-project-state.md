# Estado real do projeto (atualizar a cada mudança)

**Última atualização:** 2026-07-25 (D22 Pluggy adapter)  
**Regra:** agentes devem ler isto e **corrigir** após qualquer deploy/DNS/tunnel — não alucinar.

## DNS / VPS

| Item | Status |
|------|--------|
| Hub + API HTTPS | **200** ✅ |
| Swap VPS | `/swapfile-inova` 4G ✅ |
| D21 na VPS | Deploy feito (pull `b544ff9`) |
| D22 na VPS | **Pendente** (`git pull` + rebuild app; colar `PLUGGY_*`) |

## Código

| Item | Status |
|------|--------|
| `OpenFinanceProvider` | Pluggy HTTP adapter |
| Auth + connectors | `POST /auth` · `GET /connectors` |
| Smoke | `php artisan pluggy:connectors` |
| Setup | `docs/31-pluggy-setup.md` |

## Operador

1. Dashboard Pluggy → Application → Client ID/Secret no `.env` / `.env.prod`  
2. Deploy D22 + `docker compose … exec app php artisan pluggy:connectors`  
3. Próximo: **D23** widget Connect no Hub
