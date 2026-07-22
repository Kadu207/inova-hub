# BR-007 — Cancelamento self-service (Asaas)

## Regra

O titular (`owner`) cancela a assinatura **no Inova Hub**, sem depender de Hotmart ou suporte humano. O sistema chama Asaas, atualiza entitlement e envia e-mail de confirmação com protocolo.

## Aceite

- Botão Cancelar em `/app/billing`  
- Status → `canceled`  
- E-mail Resend enviado  
- Features gated após cancelamento (exceto export/delete)  

## Teste

Fake BillingGateway: cancel → subscription canceled + mail queued.
