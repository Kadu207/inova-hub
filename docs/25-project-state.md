# Estado real do projeto (atualizar a cada mudança)

**Última atualização:** 2026-07-25 (D17 NLU gastos por texto)  
**Regra:** agentes devem ler isto e **corrigir** após qualquer deploy/DNS/tunnel — não alucinar.

## DNS / VPS

| Item | Status |
|------|--------|
| Hub + API HTTPS | **200** ✅ |
| D16 em prod | OK |
| D17 na VPS | **Pendente** (`git pull` + rebuild app/worker; sem migrate) |

## Código

| Item | Status |
|------|--------|
| NLU heurístico PT-BR | OK (≥85% eval 20 frases) |
| LLM opcional | `OPENAI_API_KEY` ou `GROQ_API_KEY` |
| Confirmação BR-004 | Cache pending + sim/não |
| Suite | verdes incl. `FinovaTransactionNluTest` |

## Operador

1. Deploy D17 + restart **worker** (job WhatsApp).  
2. Opcional: colar `OPENAI_API_KEY`/`GROQ_API_KEY` no `.env`.  
3. WhatsApp vinculado + Meta tokens para E2E.  
4. Próximo: **D18** STT áudio → mesmo pipeline.
