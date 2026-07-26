# Estado real do projeto (atualizar a cada mudança)

**Última atualização:** 2026-07-25 (D20 dashboard gráficos v1)  
**Regra:** agentes devem ler isto e **corrigir** após qualquer deploy/DNS/tunnel — não alucinar.

## DNS / VPS

| Item | Status |
|------|--------|
| Hub + API HTTPS | **200** ✅ |
| D19 em prod | OK |
| D20 na VPS | **Pendente** (`git pull` + rebuild app) |

## Código

| Item | Status |
|------|--------|
| API aggregates | `/api/v1/aggregates`, `/by-category`, `/daily` |
| Hub charts | categoria + sparkline 30 dias |
| Teste | `FinanceDashboardAggregatesTest` |

## Operador

1. Deploy D20  
2. Abrir `/hub` e conferir gráficos  
3. Próximo: **D21** hardening NLU + QA semana 3
