# Estado real do projeto (atualizar a cada mudança)

**Última atualização:** 2026-07-25 (D12 Finova oi/ajuda no código; D11 live na VPS)  
**Regra:** agentes devem ler isto e **corrigir** após qualquer deploy/DNS/tunnel — não alucinar.

## DNS / VPS

| Item | Status |
|------|--------|
| Hub + API HTTPS | **200** ✅ |
| `/opt/inovahub` Git | OK |
| D08–D11 em prod | OK (`webhook_events` + `inovahub-worker` up) |
| D12 na VPS | **Pendente deploy** (`git pull` + restart worker/app) |

## Código

| Item | Status |
|------|--------|
| D11 webhook verify/receive | Live na VPS |
| D12 intent router + sender | No working tree → push `main` |
| Copy Finova (oi / ajuda / fallback / OTP) | `FinovaCopy` |
| Job responde via Graph API | `SendsWhatsappText` + `ProcessWhatsAppMessage` |
| Testes | `FinovaReplyTest` + `WhatsappWebhookTest` **9 passed** |

## Operador

1. Preencher Meta tokens no `.env` (`WHATSAPP_TOKEN`, `WHATSAPP_PHONE_NUMBER_ID`, `META_APP_SECRET`) — sem isso o send falha em silêncio (job marca processado, Graph não chama).
2. Deploy D12 na VPS (pull + restart app/worker).
3. Enviar “oi” / “ajuda” no número de teste; esperar resposta < 8s.
4. Próximo: **D13** card status WhatsApp no Hub.
