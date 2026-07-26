# 32 — Consentimento Open Finance (versão of-1.0)

**Status:** rascunho MVP (D28)  
**Páginas públicas:** `/legal/open-finance` · `/legal/privacy`  
**Config:** `config/open_finance.php` → `OF_CONSENT_VERSION` (default `of-1.0`)

## O que o produto registra

| Campo | Onde |
|-------|------|
| `consent_version` | `of_items` |
| `consent_at` / `consent_revoked_at` | `of_items` |
| Aceite auditável | `consent_logs` (`type=open_finance`, `version`, `user_id`, `meta`) |

## Fluxo Hub

1. Usuário lê termos (link) e marca checkbox da versão vigente  
2. Widget Pluggy (consentimento da instituição)  
3. `POST /hub/connections/items` exige `consent_accepted` + `consent_version` igual à config  
4. Grava `ConsentLog` + `of_items.consent_version`  
5. Revogar (D27) apaga dados OF e seta `consent_revoked_at`

## Regras

- BR-005 somente leitura  
- BR-006 revogação  
- Bump de versão: altere `OF_CONSENT_VERSION` / `config('open_finance.consent_version')` e o texto em `resources/views/legal/open-finance.blade.php`

## Revisão jurídica

Obrigatória antes de marketing pago e produção bancária (não-sandbox). CNPJ/DPO ainda no checklist do doc 14.
