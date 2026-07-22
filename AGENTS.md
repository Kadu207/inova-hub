# AGENTS.md — Inova Hub / Finova

## Banco de dados

**PostgreSQL 16 multi-tenant** (shared schema + `organization_id` + RLS).  
Detalhes: `docs/23-multitenant-database.md`.

## UI

100% responsiva — `docs/22-responsive-ui.md`.

## Segurança

Camadas L1–L11 — `docs/21-security-layers.md`.

## Fluxos

- **SDD** → Spec Kit / `docs/15-day-by-day-mvp.md`
- **TDD** → Pest Red-Green
- **EDD** → webhooks → Redis queue jobs

## Squads

Ver `docs/18-agent-squads.md`.

### Squad A (construção / correção / testes)

`/pesquisador` `/roteirista` `/estrutural` `/orquestrador` `/implementador` `/testador` `/validador` `/finalizador`

### Squad B (pós-finalização / usuários)

`/suporte` `/incidente` `/sre` `/produto` `/lgpd` `/qa-pos`

## Regras de negócio

`docs/business-rules/` — IDs BR-xxx + teste obrigatório.

## Domínio

- App: https://inovahub.inovatitech.com.br
- API: https://api-inovahub.inovatitech.com.br
- VPS: 128.140.77.31

## Memória / estado (obrigatório)

- Fatos travados: `.cursor/rules/inova-hub-memory.mdc`
- Estado vivo (atualizar sempre): [`docs/25-project-state.md`](docs/25-project-state.md)
- Deploy PuTTY: [`docs/24-deploy-vps-putty.md`](docs/24-deploy-vps-putty.md)
