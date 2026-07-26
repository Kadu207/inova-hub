# BR-005 — Open Finance somente leitura

## Regra

Integração Pluggy no MVP é **somente leitura**: saldos, extratos, cartões e metadados de conta. A plataforma **não** inicia Pix, TED nem pagamentos via Open Finance no MVP.

## Aceite

- Connect widget autoriza consentimento de leitura  
- Finova responde saldo/extrato/cartão  
- Nenhuma API de payment initiation chamada  

## Teste

`PluggyOpenFinanceAdapterTest::test_br005_read_only_surface_has_no_payment_methods` — interface sem métodos de pagamento.
