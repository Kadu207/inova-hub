# Estado real do projeto (atualizar a cada mudança)

**Última atualização:** 2026-07-25 (D16 UI lançamentos no Hub)  
**Regra:** agentes devem ler isto e **corrigir** após qualquer deploy/DNS/tunnel — não alucinar.

## DNS / VPS

| Item | Status |
|------|--------|
| Hub + API HTTPS | **200** ✅ |
| D15 em prod | OK (migrate + seed) |
| D16 na VPS | **Pendente** (`git pull` + rebuild app; sem migrate) |

## Código

| Item | Status |
|------|--------|
| `/hub/transactions` | Lista, filtros data/categoria/tipo, totais, CRUD |
| Alias | `/app/transactions` → `/hub/transactions` |
| Testes | `HubTransactionsUiTest` (+ suite) |

## Operador

1. Deploy D16 + abrir https://inovahub.inovatitech.com.br/hub/transactions  
2. Login: `admin@inovahub.test` / `password`  
3. Próximo: **D17** NLU gastos por texto (precisa LLM key)
