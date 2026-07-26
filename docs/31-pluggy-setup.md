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

## Próximos dias

- **D23** Connect Token + widget Hub  
- **D24** webhook `/webhooks/pluggy` + persistência  
- **D25+** sync contas/extratos  
