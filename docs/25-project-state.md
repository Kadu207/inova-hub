# Estado real do projeto (atualizar a cada mudança)

**Última atualização:** 2026-07-26 (D26 Finova bank intents)  
**Regra:** agentes devem ler isto e **corrigir** após qualquer deploy/DNS/tunnel — não alucinar.

## DNS / VPS

| Item | Status |
|------|--------|
| Hub + API HTTPS | **200** ✅ |
| D25 na VPS | Deploy feito (`05998a7`) |
| D26 na VPS | **Pendente** (`git pull` + rebuild **app** + **worker**) |

## Código

| Item | Status |
|------|--------|
| `bank.balance` | “Qual meu saldo?” → saldos `of_accounts` |
| `bank.statement` | “extrato” → últimas `of_transactions` |
| `bank.cards` | “meus cartões” → contas CREDIT/CARD |
| Eval | `BankIntentEvalSet` + `FinovaBankIntentTest` |

## Operador

1. Deploy D26 (app + worker)  
2. WhatsApp vinculado + banco conectado/sincronizado  
3. Smoke: *Qual meu saldo?* / *extrato* / *meus cartões*  
4. Próximo: **D27** categorização OF + revogação
