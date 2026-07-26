# Estado real do projeto (atualizar a cada mudança)

**Última atualização:** 2026-07-26 (D28 Termos OF + QA semana 4)  
**Regra:** agentes devem ler isto e **corrigir** após qualquer deploy/DNS/tunnel — não alucinar.

## DNS / VPS

| Item | Status |
|------|--------|
| Hub + API HTTPS | **200** ✅ |
| D27 na VPS | Deploy + migrate feitos (`256ed76`) |
| D28 na VPS | **Pendente** (`git pull` + migrate + rebuild **app** + **worker**) |

## Código

| Item | Status |
|------|--------|
| Consent OF | `of-1.0` · checkbox Hub · `consent_logs` · `/legal/open-finance` |
| Privacidade | rascunho `/legal/privacy` |
| IDOR OF | `OfTenantIsolationTest` |
| QA semana 4 | `docs/33-week4-open-finance-qa.md` |

## Operador

1. Deploy D28 (pull + migrate + rebuild app/worker)  
2. Abrir `/legal/open-finance` e `/hub/connections` (checkbox versão)  
3. Preencher tabela instituições no doc 14 após smoke sandbox  
4. Próximo: **D29** Google Cloud OAuth (Agenda)
