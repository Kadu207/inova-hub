# BR-004 — Confirmação se confiança baixa

Se o score de confiança do NLU for abaixo do limiar (`FINOVA_NLU_CONFIDENCE_THRESHOLD`, default **0.75**), Finova pergunta confirmação antes de gravar (valor + categoria).

Respostas aceitas: *sim* / *não* (e equivalentes). Pendência fica em cache ~10 min por telefone.

## Teste

`tests/Feature/FinovaTransactionNluTest.php` → `test_low_confidence_asks_confirmation_before_saving`
