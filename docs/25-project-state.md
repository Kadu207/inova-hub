# Estado real do projeto (atualizar a cada mudança)

**Última atualização:** 2026-07-25 (D23 Pluggy Connect widget)  
**Regra:** agentes devem ler isto e **corrigir** após qualquer deploy/DNS/tunnel — não alucinar.

## DNS / VPS

| Item | Status |
|------|--------|
| Hub + API HTTPS | **200** ✅ |
| Swap VPS | `/swapfile-inova` 4G ✅ |
| D22 na VPS | Smoke OK (`pluggy:connectors` → 235 connectors) |
| D23 na VPS | **Pendente** (`git pull` + migrate + rebuild **app**) |

## Código

| Item | Status |
|------|--------|
| Connect Token | `POST /hub/connections/connect-token` · `POST /api/v1/open-finance/connect-token` |
| Hub UI | `/hub/connections` (+ redirect `/app/connections`) |
| Persistência | tabela `of_items` (itemId no onSuccess) |
| Widget | CDN Pluggy + `includeSandbox` |

## Operador

1. Deploy D23 + `php artisan migrate --force`  
2. Abrir Hub → **Conectar banco** → conector sandbox Pluggy Bank  
3. Próximo: **D24** webhook Pluggy + sync accounts/txs
