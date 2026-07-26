# BR-006 — Revogar Open Finance

## Regra

Ao revogar conexão Pluggy no Hub (ou via webhook `item/deleted`), a plataforma:

1. Chama `DELETE /items/{id}` na Pluggy (quando a revogação parte do Hub)
2. Apaga `of_accounts` e `of_transactions` do item no tenant
3. Mantém o registro `of_items` com `status=DELETED` e `consent_revoked_at`
4. Finova deixa de usar a conexão (`status != DELETED`)

## Aceite

- Botão **Revogar** em `/hub/connections`
- Dados OF da conexão somem do Hub
- IDOR: outro tenant não revoga item alheio
- Categoria sugerida em extrato OF é editável (`category_manual` preserva edição no resync)

## Teste

`OfCategorizeAndRevokeTest::test_br006_revoke_wipes_of_data_and_idor_blocks_other_org`
