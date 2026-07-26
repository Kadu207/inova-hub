# Estado real do projeto (atualizar a cada mudança)

**Última atualização:** 2026-07-26 (D27 categorização OF + revogação)  
**Regra:** agentes devem ler isto e **corrigir** após qualquer deploy/DNS/tunnel — não alucinar.

## DNS / VPS

| Item | Status |
|------|--------|
| Hub + API HTTPS | **200** ✅ |
| D26 na VPS | Deploy feito (`11a3bf4`) |
| D27 na VPS | **Pendente** (`git pull` + migrate + rebuild **app** + **worker**) |

## Código

| Item | Status |
|------|--------|
| Categoria OF | Job + heurística; editável no extrato Hub (`category_manual`) |
| Revogar | Hub **Revogar** → Pluggy `DELETE /items` + wipe local + `consent_revoked_at` (BR-006) |
| Testes | `OfCategorizeAndRevokeTest` |

## Operador

1. Deploy D27 (pull + migrate + rebuild app/worker)  
2. Smoke: editar categoria no extrato; **Revogar** conexão sandbox  
3. Confirmar que saldo/extrato da conexão sumiram e Finova não responde banco dessa conexão  
4. Próximo: **D28** Termos OF + QA semana 4
