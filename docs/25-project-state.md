# Estado real do projeto (atualizar a cada mudança)

**Última atualização:** 2026-07-25 (D24 Pluggy webhook + OF sync)  
**Regra:** agentes devem ler isto e **corrigir** após qualquer deploy/DNS/tunnel — não alucinar.

## DNS / VPS

| Item | Status |
|------|--------|
| Hub + API HTTPS | **200** ✅ |
| D23 na VPS | Pull `1d12741` ok; migrate `of_items` **falhou** (user_id uuid×bigint) — corrigido neste commit |
| D24 na VPS | **Pendente** (pull + migrate + rebuild **app** + **worker**) |

## Código

| Item | Status |
|------|--------|
| Webhook | `POST /webhooks/pluggy` |
| Sync | Job `SyncPluggyItem` → `of_accounts` / `of_transactions` |
| Fix | `of_items.user_id` = `foreignId` (bigint) |

## Operador

1. Deploy D24 + `migrate --force`  
2. Dashboard Pluggy → Webhook URL `https://api-inovahub.inovatitech.com.br/webhooks/pluggy` (event `all`)  
3. Opcional: `PLUGGY_WEBHOOK_SECRET` + header `X-Webhook-Secret` na Pluggy  
4. Conectar banco sandbox e conferir contas no Hub  
5. Próximo: **D25** UI saldos/extratos no Hub
