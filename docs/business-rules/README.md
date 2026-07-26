# Catálogo de regras de negócio — Inova Hub / Finova

Toda regra tem ID `BR-xxx`. Mudança = atualizar este índice + arquivo + teste Pest.

| ID | Título | Domínio | Teste |
|----|--------|---------|-------|
| BR-001 | Tenant isolation | Segurança | `TenantIsolationTest` + `OfTenantIsolationTest` |
| BR-002 | Vincular WhatsApp por OTP | Onboarding | a criar |
| BR-003 | Lançamento financeiro por NL | Finanças | `FinovaTransactionNluTest` |
| BR-004 | Confirmação se confiança baixa | Finanças/IA | `FinovaTransactionNluTest` |
| BR-005 | Open Finance somente leitura | Pluggy | `PluggyOpenFinanceAdapterTest` |
| BR-006 | Revogar OF apaga dados do item | Pluggy/LGPD | `OfCategorizeAndRevokeTest` |
| BR-007 | Cancelamento self-service Asaas | Billing | a criar |
| BR-008 | Export e exclusão de conta | LGPD | a criar |
| BR-009 | Papéis owner/member/viewer | Equipe | a criar |
| BR-011 | Lançamentos manuais e categorias | Finanças | `tests/Feature/FinanceTransactionApiTest.php` |

Detalhes em arquivos `BR-00x-*.md` nesta pasta.
