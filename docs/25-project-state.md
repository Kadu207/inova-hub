# Estado real do projeto (atualizar a cada mudança)

**Última atualização:** 2026-07-25 (D19 consultas tx.query + card semana no Hub)  
**Regra:** agentes devem ler isto e **corrigir** após qualquer deploy/DNS/tunnel — não alucinar.

## DNS / VPS

| Item | Status |
|------|--------|
| Hub + API HTTPS | **200** ✅ |
| D18 em prod | OK (keys no `.env` + restart) |
| D19 na VPS | **Pendente** (`git pull` + rebuild app/worker) |

## Código

| Item | Status |
|------|--------|
| Consultas hoje/semana/mês | Finova WhatsApp |
| Card resumo semana | `/hub` |
| Teste | `FinovaTransactionQueryTest` (Zap = Hub) |

## Operador

1. Deploy D19  
2. Testar: *quanto gastei essa semana?* no Zap e conferir card no Hub  
3. Próximo: **D20** dashboard gráficos v1
