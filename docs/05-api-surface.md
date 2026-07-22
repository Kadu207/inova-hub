# 05 — Superfície de API

## Status

| Fonte | Status |
|-------|--------|
| APIs públicas documentadas (OpenAPI) | Não encontradas publicamente |
| Endpoints do painel autenticado | **Gap** — requer conta teste + captura Network |
| Webhooks WhatsApp / Hotmart / Open Finance | Inferidos por arquitetura típica; não inventariados |

## Inventário inferido (não confirmado em código)

> Hipóteses de domínio — **não** usar como contrato formal. Substituir após snirf autenticado.

### Domínios de API esperados no concorrente

| Domínio | Exemplos de recursos |
|---------|----------------------|
| Auth | login, refresh, reset password |
| Account | profile, preferences, shared members |
| Transactions | CRUD, categories, filters, export |
| Calendar | events, sync Google, reminders |
| Tasks/Projects | projects, tasks, priorities |
| Files | upload metadata, search |
| Open Finance | connect, accounts, cards, sync status |
| Billing | Hotmart status / entitlement |
| WhatsApp | webhook inbound, send message (interno) |

## Arquivos de inventário

```text
docs/api-inventory.json   # preenchido na fase autenticada
docs/ui-inventory.json    # idem
```

Estado atual: placeholders com `status: "awaiting_authenticated_sniff"`.

## Inova Hub — API alvo (contrato inicial)

Prefixo sugerido: `https://api.inovahub.com.br/v1`

| Método | Path | Descrição |
|--------|------|-----------|
| POST | `/auth/register` | Cadastro |
| POST | `/auth/login` | Login |
| POST | `/auth/whatsapp/otp` | Vincular Finova |
| GET/POST | `/transactions` | Lançamentos |
| GET/POST | `/events` | Agenda |
| GET/POST | `/tasks` | Tarefas |
| POST | `/webhooks/whatsapp` | Meta webhook |
| POST | `/webhooks/open-finance` | Provider |
| GET | `/exports/transactions` | CSV/Excel |
| DELETE | `/account` | Exclusão LGPD |
| POST | `/billing/cancel` | Cancelamento self-service |

Detalhamento OpenAPI: gerar na fase de implementação (Spec Kit).
