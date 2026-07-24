# Deploy VPS (PuTTY) — Inova Hub + Tunnel `:8088`

**VPS:** `128.140.77.31` · User tipico: `gestaoti`  
**Tunnel:** `inovahub` → `http://127.0.0.1:8088`  
**URLs:** `https://inovahub.inovatitech.com.br` · `https://api-inovahub.inovatitech.com.br`

Pré-requisito: `cloudflared.service` **active** no tunnel `inovahub` (já feito).

---

## 0) Cloudflare DNS (antes ou em paralelo)

Em **DNS → Records**, garanta (Proxied):

| Type | Name | Target |
|------|------|--------|
| CNAME | `inovahub` | `<tunnel-id>.cfargotunnel.com` (ou o que o dashboard criou) |
| CNAME | `api-inovahub` | mesmo tunnel |

Se `nslookup inovahub.inovatitech.com.br 1.1.1.1` der NXDOMAIN, o CNAME do Hub está faltando — reative no Public Hostname do tunnel (checkbox DNS).

---

## 1) PuTTY — entrar na VPS

1. PuTTY → Host: `128.140.77.31` → Open  
2. Login: `gestaoti` (+ senha ou key)

Verificar Docker:

```bash
docker --version
docker compose version
```

Se faltar Docker: instalar [docs oficiais](https://docs.docker.com/engine/install/) e adicionar o user ao grupo `docker` (`sudo usermod -aG docker $USER` → relogar).

---

## 2) Colocar o código na VPS

O projeto local ainda pode **não** ter remote GitHub. Escolha **A** ou **B**.

### A — Git clone (recomendado)

```bash
sudo mkdir -p /opt/inovahub
sudo chown "$USER:$USER" /opt/inovahub
cd /opt/inovahub
git clone https://github.com/Kadu207/inova-hub.git .
# espelho GitLab: https://gitlab.com/Kadu207/inova-hub.git
```

### B — Copiar deste Windows com `pscp` (sem Git remoto)

No **PowerShell do PC** (pasta do projeto):

```powershell
cd "C:\Users\Carlos\OneDrive\Área de Trabalho\Projetos DEV\Inova Assessor - Snirfado"

# cria pasta na VPS (PuTTY, uma vez):
#   sudo mkdir -p /opt/inovahub && sudo chown gestaoti:gestaoti /opt/inovahub

pscp -r docker-compose.prod.yml .env.prod.example infra app docs README.md AGENTS.md gestaoti@128.140.77.31:/opt/inovahub/
```

Ou com OpenSSH (se tiver):

```powershell
scp -r docker-compose.prod.yml .env.prod.example infra app gestaoti@128.140.77.31:/opt/inovahub/
```

**Não copie `app/vendor` pelo pscp** — na VPS rode `composer install` no container (o entrypoint também tenta reparar vendor incompleto).

Sugestão mínima a enviar: `docker-compose.prod.yml`, `infra/`, `app/` **sem** `vendor/` e **sem** `.env`.

---

## 3) `.env.prod` na VPS (PuTTY)

```bash
cd /opt/inovahub
cp .env.prod.example .env.prod
nano .env.prod
```

Preencha:

```env
COMPOSE_PROJECT_NAME=inovahub
APP_URL=https://inovahub.inovatitech.com.br
APP_KEY=
DB_DATABASE=inova_hub
DB_USERNAME=inova
DB_PASSWORD=GERE_UMA_SENHA_FORTE
```

Gerar `APP_KEY` (no PC ou na VPS com PHP/Docker):

```bash
# Na VPS, após o build, ou use uma key base64:32 gerada localmente:
# docker run --rm php:8.4-cli php -r "echo 'base64:'.base64_encode(random_bytes(32)), PHP_EOL;"
```

Cole o resultado em `APP_KEY=base64:...`.

Também prepare o Laravel `app/.env` (pode espelhar DB/Redis do compose):

```bash
cd /opt/inovahub/app
cp .env.example .env
nano .env
```

Ajuste no mínimo:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://inovahub.inovatitech.com.br
APP_KEY=base64:...mesma...
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=inova_hub
DB_USERNAME=inova
DB_PASSWORD=mesma_do_.env.prod
REDIS_HOST=redis
REDIS_PORT=6379
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
```

---

## 4) Subir o stack (porta do Tunnel)

```bash
cd /opt/inovahub
docker compose -f docker-compose.prod.yml --env-file .env.prod up -d --build
docker compose -f docker-compose.prod.yml ps
```

Conferir origem local:

```bash
ss -tlnp | grep 8088
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8088/
```

Esperado: `200` (ou `302`).

Migrations:

```bash
docker exec inovahub-app php artisan migrate --force
docker exec inovahub-app php artisan config:cache
docker exec inovahub-app php artisan route:cache
```

---

## 5) Validar pela internet

No Windows:

```bat
nslookup inovahub.inovatitech.com.br 1.1.1.1
nslookup api-inovahub.inovatitech.com.br 1.1.1.1
curl -I https://inovahub.inovatitech.com.br
curl -I https://api-inovahub.inovatitech.com.br
```

Esperado: **não** 502/530; `200`/`301`/`302`.

Se ainda 502:

```bash
sudo journalctl -u cloudflared -n 30 --no-pager
curl -I http://127.0.0.1:8088/
docker logs inovahub-app --tail 50
```

---

## Sync código na VPS (quando `/opt/inovahub` não tem `.git`)

Deploy inicial foi via `pscp`. Para passar a usar Git **sem apagar** `.env`:

```bash
cd /opt

# 1) Backup (se ainda não fez)
sudo mv inovahub inovahub.bak   # ignore se já existir inovahub.bak

# 2) Clone como root (gestaoti não consegue criar pasta em /opt)
sudo git clone https://github.com/Kadu207/inova-hub.git inovahub
sudo chown -R gestaoti:gestaoti /opt/inovahub

# 3) Preservar secrets do backup
sudo cp /opt/inovahub.bak/.env.prod /opt/inovahub/.env.prod
sudo cp /opt/inovahub.bak/app/.env /opt/inovahub/app/.env
sudo chown gestaoti:gestaoti /opt/inovahub/.env.prod /opt/inovahub/app/.env

# 4) Subir stack do path novo
cd /opt/inovahub
docker compose -f docker-compose.prod.yml --env-file .env.prod up -d --build
docker compose -f docker-compose.prod.yml --env-file .env.prod exec app composer install --no-interaction --prefer-dist --optimize-autoloader
docker compose -f docker-compose.prod.yml --env-file .env.prod exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml --env-file .env.prod exec app php artisan db:seed --force
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8088/
```

Se o clone parcial falhou antes, confira:

```bash
ls -la /opt | grep inovahub
# deve existir inovahub.bak; inovahub só após o clone com sudo
```

Atualizações seguintes:

```bash
cd /opt/inovahub
git pull origin main
docker compose -f docker-compose.prod.yml --env-file .env.prod exec app composer install --no-interaction
docker compose -f docker-compose.prod.yml --env-file .env.prod exec app php artisan migrate --force
```

---

## Notas

- **80/443 da VPS** não são usados pelo Hub; só `127.0.0.1:8088`.
- Postgres/Redis **sem** portas no host em prod (só rede interna do Compose).
- Ao trocar o `cloudflared` para o tunnel `inovahub`, outros sites que usavam o tunnel antigo precisam ter Public Hostnames recriados neste tunnel ou o connector antigo restaurado.
- SSL Cloudflare: **Full** com origem HTTP no tunnel.
