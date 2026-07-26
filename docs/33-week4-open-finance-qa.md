# 33 — Semana 4 QA / Open Finance (D28)

**Data:** 2026-07-26  
**Escopo:** Pluggy D22–D27 + consentimento versionado + IDOR OF.

## Dias

| Dia | Tema | Status |
|-----|------|--------|
| D22 | Adapter Pluggy + connectors | ✅ |
| D23 | Connect widget + `of_items` | ✅ |
| D24 | Webhook sync contas/TX | ✅ |
| D25 | Hub saldos/extrato + GET webhook | ✅ |
| D26 | Finova `bank.*` intents | ✅ |
| D27 | Categoria + revogar (BR-006) | ✅ |
| D28 | Termos OF + consent_logs + IDOR | ✅ |

## Testes Pest (OF)

| Suite | Foco |
|-------|------|
| `PluggyOpenFinanceAdapterTest` | BR-005 read-only + `deleteItem` |
| `HubConnectionsTest` | Connect token, persist item, consent obrigatório |
| `PluggyWebhookTest` | Idempotência webhook |
| `FinovaBankIntentTest` | Saldo/extrato/cartões |
| `OfCategorizeAndRevokeTest` | BR-006 + categoria |
| `OfTenantIsolationTest` | IDOR sync/revoke/account/category + scope + legal pages |

Rodar: `php artisan test --filter='Pluggy|HubConnections|FinovaBank|OfCategorize|OfTenant'`

## Critérios D28

- [x] Texto consentimento versionado (`of-1.0`)  
- [x] `consent_logs` + `of_items.consent_version`  
- [x] Páginas `/legal/open-finance` e `/legal/privacy`  
- [x] Checklist instituições no doc 14  
- [x] IDOR OF bloqueado nos testes  

## Instituições

Ver tabela em [14-prerequisites-checklist.md](14-prerequisites-checklist.md) §2.2.1 — preencher smoke real na VPS (sandbox Pluggy Bank).

## Próximo

**D29** — Google Cloud OAuth (Agenda).
