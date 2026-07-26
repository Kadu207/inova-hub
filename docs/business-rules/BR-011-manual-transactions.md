# BR-011 — Lançamentos manuais e categorias (CRUD)

## Regra

Cada organização possui categorias financeiras e lançamentos (`transactions`) isolados por `organization_id` (BR-001). Moeda padrão **BRL**. Valores em **centavos** (`amount_cents` > 0); o sinal econômico vem de `type` (`expense` | `income`).

## Categorias

- No cadastro da org, seed padrão BR: moradia, alimentação, transporte, saúde, lazer, educação, salário, outros.
- `is_system = true` → não podem ser excluídas via API.
- `kind`: `expense` | `income`.
- Unique por org: `(organization_id, slug)`.

## Lançamentos

- Campos mínimos: `amount_cents`, `type`, `category_id` (mesma org), `occurred_at`, `source` (`manual` | `finova` | `of`), `description` opcional, `currency` default `BRL`.
- `category.kind` deve ser compatível com `transaction.type`.
- Viewer: só leitura. Owner/member: criar/editar/excluir.

## Isolamento

- Global Scope + Policy + RLS (Postgres).
- IDOR: usuário da org A não lê/altera/apaga lançamento da org B.

## Teste

`tests/Feature/FinanceTransactionApiTest.php`
