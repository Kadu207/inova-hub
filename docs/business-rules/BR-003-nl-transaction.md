# BR-003 — Lançamento financeiro por linguagem natural

Finova interpreta texto (e depois áudio), extrai valor, tipo (despesa/receita), categoria e data; persiste na org do remetente vinculado. Moeda padrão **BRL**.

## Detalhes D17

- Extrator heurístico PT-BR sempre disponível.
- Se `OPENAI_API_KEY` ou `GROQ_API_KEY` estiver setado, usa LLM JSON com fallback heurístico.
- Remetente precisa de `whatsapp_identities` ativo.
- `source = finova` nos lançamentos.
- Eval set: `TransactionNluEvalSet` (≥85%).

## Teste

`tests/Feature/FinovaTransactionNluTest.php`
