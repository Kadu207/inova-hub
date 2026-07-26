# 31 — Setup Pluggy Open Finance (D22+)

**Segredos:** só em `/opt/inovahub/app/.env` e/ou `.env.prod` na VPS — nunca no Git/chat.

## Conta sandbox

1. Crie conta em https://dashboard.pluggy.ai  
2. Crie uma **Application** (sandbox)  
3. Copie **Client ID** e **Client Secret**  
4. No `.env` da app (e espelhe no `.env.prod` se o Compose injeta daí):

```bash
PLUGGY_CLIENT_ID=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
PLUGGY_CLIENT_SECRET=...
PLUGGY_BASE_URL=https://api.pluggy.ai
```

Sandbox e produção usam o mesmo host; o ambiente é o da aplicação no dashboard.

## Smoke D22

```bash
cd /opt/inovahub
docker compose -f docker-compose.prod.yml --env-file .env.prod exec app php artisan pluggy:connectors
```

Esperado: `Pluggy OK — N connectors` e tabela com bancos BR (inclui conectores de teste Pluggy).

## Arquitetura

| Peça | Path |
|------|------|
| Contrato | `App\Contracts\OpenFinance\OpenFinanceProvider` |
| Adapter | `App\Services\OpenFinance\PluggyOpenFinanceProvider` |
| Config | `config/services.php` → `pluggy.*` |
| BR-005 | somente leitura — sem payment initiation |

## Widget (D23)

1. Login no Hub → https://inovahub.inovatitech.com.br/hub/connections  
2. **Conectar banco** → widget Pluggy (`includeSandbox=true`)  
3. Escolher conector de teste (ex. Pluggy Bank)  
4. Sucesso grava `of_items.pluggy_item_id`

## Webhook (D24)

URL pública:

```text
https://api-inovahub.inovatitech.com.br/webhooks/pluggy
```

- **GET no navegador** → texto `Pluggy webhook OK — use POST for events` (só healthcheck)  
- **POST da Pluggy** → eventos reais (`item/created`, etc.)

1. Dashboard Pluggy → Application → Webhooks → event **`all`** → URL acima  
2. (Recomendado) `PLUGGY_WEBHOOK_SECRET` + header `X-Webhook-Secret`  

```bash
PLUGGY_WEBHOOK_SECRET=...
PLUGGY_WEBHOOK_URL=https://api-inovahub.inovatitech.com.br/webhooks/pluggy
```

3. Connect Token envia `webhookUrl` quando a URL é HTTPS  
4. Após conectar no Hub, o job de sync também é disparado localmente (não depende só do webhook)

## Hub saldos/extratos (D25)

1. https://inovahub.inovatitech.com.br/hub/connections  
2. Ver **Saldo total OF** + contas com saldo  
3. Toque na conta → extrato OF  
4. **Sincronizar** = sync on-demand (`pluggy:sync-items` também roda hourly se houver scheduler)

## Próximos dias

- **D27** ✅ categorização OF + revogação LGPD  
- **D28** ✅ Termos OF + QA semana 4  
- **D29** Google Calendar OAuth  

