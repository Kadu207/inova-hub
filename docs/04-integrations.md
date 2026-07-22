# 04 — Integrações

## Meu Assessor (confirmado em políticas públicas)

| Integração | Uso | Evidência |
|------------|-----|-----------|
| WhatsApp Business Cloud API (Meta) | Enviar/receber mensagens; automação | Política de privacidade §6 |
| Google Calendar OAuth 2.0 | Sync agenda | Política §§7, 11–12 |
| Google People API | Contatos para reuniões | Política §12b |
| Open Finance | Saldos, extratos, cartões (+110 IFs) | Site + FAQ |
| Hotmart | Assinatura / cancelamento | Termos §4 |
| Google Meet | Links + atas (claim) | Site FAQ |

### Observações críticas

1. TLS 1.3 ≠ criptografia ponta a ponta.  
2. Escopos Google inconsistentes na política do concorrente.  
3. Provedor OF do concorrente não é nomeado publicamente.

## Inova Hub / Finova — stack de integração (decisões)

| Integração | Provedor | Fase |
|------------|----------|------|
| WhatsApp | Meta Cloud API (WABA) | **P0** |
| STT | Whisper (OpenAI) | **P0** |
| LLM (NLU) | OpenAI ou Groq | **P0** |
| Calendário | Google Calendar (escopos mínimos) | **P0** |
| Open Finance (todos os bancos OF) | **Pluggy** (somente leitura) | **P0** |
| Pagamentos / assinatura | **Asaas** (PIX + cartão) | **P0** |
| Pagamentos internacionais | Stripe | **P1** (não no MVP) |
| E-mail | Resend | **P0** |
| Storage arquivos | Cloudflare R2 | P1 (drive) |
| Observabilidade | Logs + uptime (+ Sentry opcional) | P0/P1 |

### Open Finance (Pluggy)

- Widget Connect no Inova Hub  
- Webhooks na VPS  
- Contas, saldos, cartões, transações  
- Consultas na Finova  
- Revogação + apagamento no Hub  
- **Não** no MVP: iniciador de pagamento / Pix saída  

### Billing (Asaas)

- Interface `BillingGateway` → Asaas  
- Trial, planos Pessoal/Família, cancelamento self-service  
- **Stripe não** no MVP  

## Contratos / compliance

- Meta WhatsApp Business Policy  
- Google API Limited Use  
- Open Finance: consentimento versionado + revogação  
- LGPD + DPA com suboperadores  
- Termos alinhados aos escopos reais OAuth e Pluggy  

Ver checklist: [14-prerequisites-checklist.md](14-prerequisites-checklist.md) · calendário: [15-day-by-day-mvp.md](15-day-by-day-mvp.md)
