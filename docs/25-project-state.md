# Estado real do projeto (atualizar a cada mudança)

**Última atualização:** 2026-07-25 (D18 áudio→Whisper→NLU)  
**Regra:** agentes devem ler isto e **corrigir** após qualquer deploy/DNS/tunnel — não alucinar.

## DNS / VPS

| Item | Status |
|------|--------|
| Hub + API HTTPS | **200** ✅ |
| D17 em prod | OK |
| D18 na VPS | **Pendente** (`git pull` + rebuild app/worker) |

## Código

| Item | Status |
|------|--------|
| Download mídia Meta | `DownloadsWhatsappMedia` |
| STT Whisper | `TranscribesWhatsappAudio` (temp file apagado) |
| Pipeline | áudio → texto → NLU D17 |
| Chaves LLM/STT | `docs/29-llm-keys-setup.md` |

## Operador

1. Como obter keys: [29-llm-keys-setup.md](29-llm-keys-setup.md)  
2. Deploy D18 + `OPENAI_API_KEY` (ou Groq) para áudio real  
3. Próximo: **D19** consultas “quanto gastei essa semana?”
