# 17 — Banco de dados e governança de engenharia

## Banco de dados (decisão fechada)

| Item | Valor |
|------|-------|
| SGBD | **PostgreSQL 16** |
| Motivo | Relacional, JSONB, UUID, RLS/tenant, maduro com Laravel |
| Local/dev | Docker Compose serviço `postgres` |
| Produção | Mesmo Postgres na VPS Hetzner (container) ou managed depois |
| ORM | Eloquent (Laravel) |
| Testes | Banco `inova_hub_test` + Pest; RefreshDatabase |

**Não usaremos** MySQL/MariaDB/SQLite em produção. SQLite só se algum teste unitário isolado exigir (preferir Postgres também nos testes).

### Extensões / convenções

- PKs: `uuid` (`uuid_generate_v4` / ordered UUID Laravel)  
- Toda tabela de negócio com `organization_id` (tenant)  
- `created_at` / `updated_at`; soft deletes só onde fizer sentido  
- Índices em FKs e campos de consulta (telefone WhatsApp, pluggy item_id)

---

## Práticas obrigatórias

| Sigla | Significado neste projeto | Como aplicar |
|-------|---------------------------|--------------|
| **SDD** | Spec-Driven Development | Spec Kit: constitution → spec → plan → tasks **antes** de feature |
| **TDD** | Test-Driven Development | Pest: Red → Green → Refactor; regra de negócio nasce com teste |
| **EDD** | Event-Driven Design | WhatsApp, Pluggy, Asaas = eventos/webhooks → jobs na fila Redis |
| **Spec Kit** | Fluxo `.specify/` | Ver constitution e templates |
| **CodeRabbit** | Review automático no GitHub | `.coderabbit.yaml` + app no repo |

---

## Domínio e DNS (registrados)

| Campo | Valor |
|-------|-------|
| Host app | `inovahub.inovatitech.com.br` |
| IP VPS | `128.140.77.31` |

Registros Cloudflare sugeridos (zona `inovatitech.com.br`):

| Tipo | Nome | Conteúdo | Proxy |
|------|------|----------|-------|
| A | `inovahub` | `128.140.77.31` | Proxied |
| A | `api.inovahub` | `128.140.77.31` | Proxied |

URL app: `https://inovahub.inovatitech.com.br`  
URL API/webhooks: `https://api.inovahub.inovatitech.com.br`

Ver também [14-prerequisites-checklist.md](14-prerequisites-checklist.md) e [18-agent-squads.md](18-agent-squads.md).
