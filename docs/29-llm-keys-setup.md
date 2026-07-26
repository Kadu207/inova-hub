# 29 — Chaves LLM / STT (OpenAI ou Groq)

**Onde colar:** só em `/opt/inovahub/app/.env` na VPS (nunca no Git/chat).  
**Reiniciar depois:** `docker compose -f docker-compose.prod.yml --env-file .env.prod up -d app worker`

## FINOVA_NLU_CONFIDENCE_THRESHOLD

Não é uma chave secreta — é um número (default `0.75`).

- Acima do limiar → Finova grava o lançamento direto.
- Abaixo → pergunta *sim/não* (BR-004).

Exemplo:

```bash
FINOVA_NLU_CONFIDENCE_THRESHOLD=0.75
```

Sem essa linha, o default `0.75` já vale. **D17 funciona sem LLM** (heurística PT-BR).

---

## Opção A — OpenAI (NLU + Whisper D18)

1. Crie conta em https://platform.openai.com  
2. **Billing** → adicione método de pagamento (conta free tem cota baixa).  
3. **API keys** → https://platform.openai.com/api-keys → **Create new secret key**.  
4. Copie a key (`sk-...`) **uma vez**.  
5. No `.env` da VPS:

```bash
OPENAI_API_KEY=sk-...
LLM_BASE_URL=https://api.openai.com/v1
LLM_MODEL=gpt-4o-mini
```

Whisper (D18) usa a mesma `OPENAI_API_KEY`.

---

## Opção B — Groq (barato / rápido)

1. Conta em https://console.groq.com  
2. **API Keys** → Create API Key.  
3. No `.env`:

```bash
GROQ_API_KEY=gsk_...
LLM_BASE_URL=https://api.groq.com/openai/v1
LLM_MODEL=llama-3.3-70b-versatile
```

Groq também tem endpoint de áudio compatível (Whisper) — D18 tenta Groq se não houver OpenAI.

---

## O que é obrigatório?

| Recurso | Sem key | Com key |
|---------|---------|---------|
| D17 texto (*gastei 45 no almoço*) | Heurística OK | LLM opcional (melhor em frases ambíguas) |
| D18 áudio | Resposta pedindo key / texto | Whisper → mesmo NLU |

Meta WhatsApp (`WHATSAPP_TOKEN` etc.) continua necessário para receber/enviar no Zap — ver [27-meta-whatsapp-setup.md](27-meta-whatsapp-setup.md).
