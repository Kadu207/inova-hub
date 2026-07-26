# Estado real do projeto (atualizar a cada mudança)

**Última atualização:** 2026-07-25 (D21 hardening NLU + QA semana 3)  
**Regra:** agentes devem ler isto e **corrigir** após qualquer deploy/DNS/tunnel — não alucinar.

## DNS / VPS

| Item | Status |
|------|--------|
| Hub + API HTTPS | **200** ✅ |
| Swap VPS | `/swapfile-inova` 4G ✅ |
| D20 na VPS | Deploy feito (pull `a0b81a7`) |
| D21 na VPS | **Pendente** (`git pull` + rebuild **app** + **worker**) |

## Código

| Item | Status |
|------|--------|
| NLU eval | 50 casos · ≥85% · FPs rejeitados |
| Áudio vazio | reply `audioFailed` · sem crash |
| Marca / QA | `docs/12-brand-finova.md` · `docs/30-week3-nlu-qa.md` |

## Operador

1. Deploy D21 (app + worker)  
2. Smoke: texto despesa + “quanto gastei essa semana” + áudio (se STT key)  
3. Próximo: **D22** Pluggy Open Finance (credenciais sandbox)
