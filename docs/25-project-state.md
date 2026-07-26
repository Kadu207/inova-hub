# Estado real do projeto (atualizar a cada mudança)

**Última atualização:** 2026-07-25 (D15 categorias/transactions API no `main`)  
**Regra:** agentes devem ler isto e **corrigir** após qualquer deploy/DNS/tunnel — não alucinar.

## DNS / VPS

| Item | Status |
|------|--------|
| Hub + API HTTPS | **200** ✅ |
| `/opt/inovahub` Git | OK |
| D08–D13 em prod | OK |
| D14 | Docs/retro OK |
| D15 na VPS | **Pendente** (`git pull` + migrate + `db:seed` categorias) |

## Código

| Item | Status |
|------|--------|
| `categories` + `transactions` | Migration + RLS |
| Seed BR default | `SeedsDefaultCategories` no register/seed |
| API | `/api/v1/categories`, `/api/v1/transactions` (Sanctum + tenant) |
| BR-011 | Catálogo + Pest IDOR |
| Suite | Finance + regressão verdes |

## Operador

1. Login seed: `admin@inovahub.test` / `password`
2. Deploy D15: pull + migrate + seed (categorias da org demo)
3. Meta tokens ainda P0 para Finova send
4. Próximo: **D16** UI lista/filtros de lançamentos no Hub
