# 30 — Semana 3 QA / NLU (D21)

**Data:** 2026-07-25  
**Escopo:** Hardening heurística Finova + eval 50 + áudio vazio + sync marca.

## Acurácia (heurística)

| Métrica | Valor |
|---------|-------|
| Eval set | `TransactionNluEvalSet` — **50** frases |
| Limite aceite | ≥ **85%** |
| Resultado CI local | **PASS** (`FinovaTransactionNluTest`) |
| Falsos positivos cobertos | oi, ajuda, query semana/mês, USD, parcelas, OTP puro, string vazia |

Rodar: `php artisan test --filter=FinovaTransactionNluTest`

## Hardening aplicado

- Rejeita consultas (`quanto gastei…`, `resumo…`) antes de tentar extrair lançamento  
- Rejeita USD / `$` e parcelas (`Nx`, parcelado) → backlog  
- Remove keyword solta `99` (transport)  
- Categorias com word-boundary; “pagamento” só como receita com recebi/ganhei  
- Guard contra ano como valor (ex. 2024)

## Áudio

| Caso | Comportamento |
|------|----------------|
| STT sem chave | `FinovaCopy::audioNeedsStt()` · webhook `failed` |
| Bytes vazios / STT falha | `FinovaCopy::audioFailed()` · **sem throw** · webhook `failed` |
| Temp file | removido após Whisper |

## Docs sync

- `docs/12-brand-finova.md` — intents live D12–D19 + backlog  
- Ambiguidades (parcelas, USD, split) documentadas

## Semana 3 checklist (D15–D21)

| Dia | Tema | Status |
|-----|------|--------|
| D15 | categories/transactions + API | ✅ |
| D16 | Hub lançamentos | ✅ |
| D17 | NLU texto | ✅ |
| D18 | Áudio STT | ✅ |
| D19 | tx.query + card semana | ✅ |
| D20 | aggregates + charts | ✅ |
| D21 | hardening + QA | ✅ |

## Próximo

**D22** — adapter Pluggy (credenciais sandbox necessárias).
