# BR-001 — Isolamento multi-tenant

## Regra

Todo dado de negócio pertence a uma `organization`. Um usuário autenticado só lê/escreve recursos da organização da qual é membro. Tentativas cross-tenant retornam 404/403 (nunca dados de outro tenant).

Isolamento em **quatro camadas**: membership → Eloquent Global Scope → Policy → **PostgreSQL RLS** (`SET LOCAL app.current_org`).

## Aceite

- Given org A e org B com transações distintas  
- When user de A solicita ID de transação de B  
- Then resposta sem vazamento (403/404) e log de auditoria opcional  
- And query direta no DB sem `app.current_org` retorna 0 rows (FORCE RLS)  

## Implementação

- Coluna `organization_id` em todas as tabelas de negócio  
- Trait + Global Scope + middleware `SetTenantContext`  
- Jobs/webhooks resolvem tenant e setam contexto  
- RLS policies — ver [23-multitenant-database.md](../23-multitenant-database.md)  

## Teste

Pest: `TenantIsolationTest` + cenário RLS.  
Detalhes de segurança: [21-security-layers.md](../21-security-layers.md) L6/L7.
