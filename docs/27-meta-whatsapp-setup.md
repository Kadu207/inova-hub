# Meta WhatsApp (Finova) — passo a passo WABA + tokens

**Objetivo:** obter tokens para a Finova enviar/receber WhatsApp e configurar webhook em `https://api-inovahub.inovatitech.com.br/webhooks/whatsapp`.

**Quem faz:** Operador (você)  
**Onde colar secrets:** só em `/opt/inovahub/app/.env` na VPS (nunca no Git / chat)

---

## 0) O que você vai ter no final

| Variável | Onde nasce |
|----------|------------|
| `META_APP_SECRET` | App → Settings → Basic |
| `WHATSAPP_TOKEN` | WhatsApp → API Setup (token temporário) ou System User (permanente) |
| `WHATSAPP_PHONE_NUMBER_ID` | WhatsApp → API Setup |
| `WHATSAPP_VERIFY_TOKEN` | Você inventa (string longa); mesma no Meta e no `.env` |

Número de teste Meta (sandbox) serve para D10–D12 sem KYC de produção.

---

## 1) Meta Business + App

1. Acesse https://business.facebook.com e crie/entre no **Business Manager**.
2. Acesse https://developers.facebook.com → **My Apps** → **Create App**.
3. Tipo: **Business** → nome ex.: `Inova Hub Finova`.
4. Associe ao Business Manager.

## 2) Produto WhatsApp

1. No app → **Add Product** → **WhatsApp** → Set up.
2. Escolha/crie uma **WhatsApp Business Account (WABA)**.
3. Em **API Setup** anote:
   - **Phone number ID** → `WHATSAPP_PHONE_NUMBER_ID`
   - **Temporary access token** → `WHATSAPP_TOKEN` (expira; depois troque por permanente)
4. Adicione seu celular como **número de teste** (lista “To”).

## 3) App Secret

1. App → **Settings → Basic**.
2. **App Secret** → Show → copie → `META_APP_SECRET`.

## 4) Verify token (você cria)

Gere uma string forte, ex.:

```bash
openssl rand -hex 32
```

Guarde como `WHATSAPP_VERIFY_TOKEN` (mesmo valor no Meta no passo 5).

## 5) Webhook

1. WhatsApp → **Configuration** → Webhook → **Edit**.
2. Callback URL:
   `https://api-inovahub.inovatitech.com.br/webhooks/whatsapp`
3. Verify token: o mesmo `WHATSAPP_VERIFY_TOKEN`.
4. Inscreva o campo **messages**.
5. Clique **Verify and save** (o endpoint D11 já responde ao challenge GET).

Teste manual do challenge:

```bash
curl -s "https://api-inovahub.inovatitech.com.br/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token=SEU_VERIFY&hub_challenge=ping"
# esperado: ping
```

Na VPS, rode o **worker** de fila (jobs WhatsApp):

```bash
cd /opt/inovahub
git pull origin main
docker compose -f docker-compose.prod.yml --env-file .env.prod up -d --build worker
docker compose -f docker-compose.prod.yml --env-file .env.prod exec app php artisan migrate --force
```

## 6) Colar na VPS

```bash
cd /opt/inovahub
nano app/.env
```

```env
META_APP_SECRET=...
WHATSAPP_TOKEN=...
WHATSAPP_PHONE_NUMBER_ID=...
WHATSAPP_VERIFY_TOKEN=...
```

```bash
docker compose -f docker-compose.prod.yml --env-file .env.prod exec app php artisan config:clear
```

## 7) Token permanente (recomendado antes do beta)

1. Business Settings → **System users** → Create admin system user.
2. Generate token com permissões WhatsApp (`whatsapp_business_messaging`, `whatsapp_business_management`).
3. Substitua `WHATSAPP_TOKEN` pelo token do system user.

## 8) Display name Finova

Em WABA → Profile: nome de exibição **Finova** (aprovação Meta pode levar dias).

## Checklist

- [ ] App + WhatsApp product
- [ ] Phone number ID + token no `.env`
- [ ] App Secret no `.env`
- [ ] Verify token alinhado Meta ↔ `.env`
- [ ] Número pessoal na lista de teste
- [ ] (D11) Webhook verified + messages

## Fluxo produto (D10)

1. No Hub, usuário logado informa o celular e gera OTP de 6 dígitos.  
2. Envia esse código em mensagem para a Finova no WhatsApp.  
3. Sistema vincula o número à conta (`whatsapp_identities`).  
Enquanto o webhook (D11) não estiver ativo, use o botão **Confirmar (dev)** no Hub só em ambiente de teste.
