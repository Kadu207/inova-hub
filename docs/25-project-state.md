# Estado real do projeto (atualizar a cada mudança)

**Última atualização:** 2026-07-25 (D11 webhook no código; Meta tokens ainda pendentes no `.env`)  
**Regra:** agentes devem ler isto e **corrigir** após qualquer deploy/DNS/tunnel — não alucinar.

## DNS / VPS

| Item | Status |
|------|--------|
| Hub + API HTTPS | **200** ✅ |
| `/opt/inovahub` Git | OK |
| D08–D10 em prod | OK (OTP migrate feito) |
| D11 na VPS | **Pendente deploy** (`git pull` + migrate + worker) |

## Código

| Item | Status |
|------|--------|
| D11 webhook verify/receive | No `main` |
| Assinatura `X-Hub-Signature-256` | OK |
| Idempotência `wamid` | `webhook_events` unique |
| Job `ProcessWhatsAppMessage` | Consome OTP → vínculo |
| Testes | `WhatsappWebhookTest` **5 passed** |
| Compose `inovahub-worker` | Adicionado |

## Operador

1. Preencher Meta tokens no `.env` (`docs/27-meta-whatsapp-setup.md`).
2. Deploy D11 + worker na VPS.
3. Verificar webhook no Meta console.
4. Próximo: **D12** Finova responde oi/ajuda.
