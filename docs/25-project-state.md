# Estado real do projeto (atualizar a cada mudança)

**Última atualização:** 2026-07-25 (D25 Hub OF saldos/extratos + webhook GET ping)  
**Regra:** agentes devem ler isto e **corrigir** após qualquer deploy/DNS/tunnel — não alucinar.

## DNS / VPS

| Item | Status |
|------|--------|
| Hub + API HTTPS | **200** ✅ |
| D24 migrate | `of_items` + `of_accounts`/`of_transactions` DONE |
| Webhook GET | `GET /webhooks/pluggy` → texto “Pluggy webhook OK” (navegador) |
| Webhook POST | Pluggy envia eventos (não abre como página) |
| D25 na VPS | **Pendente** (pull + rebuild **app** + **worker**) |

## Código

| Item | Status |
|------|--------|
| Hub bancos | saldo total + contas + botão Sincronizar |
| Extrato | `/hub/connections/accounts/{id}` |
| Schedule | `pluggy:sync-items` hourly (precisa `schedule:work` ou cron) |

## Operador

1. Deploy D25  
2. Abrir no browser: https://api-inovahub.inovatitech.com.br/webhooks/pluggy → deve ver texto OK  
3. Dashboard Pluggy → webhook POST na mesma URL (event `all`)  
4. Hub → Conectar banco → Sincronizar → ver saldo e extrato  
5. Próximo: **D26** intents Finova bancários
