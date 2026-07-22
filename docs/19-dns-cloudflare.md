# DNS — inovahub.inovatitech.com.br

## Valores confirmados (D01)

| Campo | Valor |
|-------|-------|
| Host app | `inovahub.inovatitech.com.br` |
| Host API (recomendado) | `api-inovahub.inovatitech.com.br` |
| Host API (legado / evitar) | `api.inovahub.inovatitech.com.br` — **sem Universal SSL** |
| IP VPS Hetzner | `128.140.77.31` |
| Zona Cloudflare | `inovatitech.com.br` |
| Origem preferida | **Opção C — Cloudflare Tunnel** (80/443 da VPS já ocupadas) |

## Status DNS / HTTPS (atualizado 22/07/2026 ~00:45)

| Host | DNS | HTTPS |
|------|-----|-------|
| `inovahub.inovatitech.com.br` | **NXDOMAIN** — registro apagado/ausente | `curl: (6) Could not resolve host` |
| `api-inovahub.inovatitech.com.br` | OK (anycast CF) | **502 Bad Gateway** — edge OK; origem/tunnel sem backend |
| `api.inovahub.inovatitech.com.br` | NXDOMAIN (removido — ok) | — |

**502 no Cloudflare** = DNS/proxy ok, mas o Tunnel não está Healthy **ou** nada escuta em `http://127.0.0.1:8088` na VPS.

### Por que a API quebra o SSL

Universal SSL (plano Free) cobre só o apex + **1 nível**:
- ✅ `inovahub.inovatitech.com.br`
- ❌ `api.inovahub.inovatitech.com.br` (dois labels antes do apex)

**Correção grátis (recomendado):** usar `api-inovahub.inovatitech.com.br`.  
**Alternativa paga:** Advanced Certificate Manager / Total TLS mantendo `api.inovahub.…`.

### B vs C (sua escolha)

| | B — porta 8443 + Origin Rule | C — Cloudflare Tunnel |
|--|------------------------------|------------------------|
| Segurança | Abre porta pública extra | **Melhor** — origem não precisa de porta pública |
| Firewall | Liberar 8443 | Só SSH (e o que já existe) |
| Complexidade | Média | Média (conta Zero Trust + `cloudflared`) |
| SSL edge | Continua preciso do hostname de 1 nível | Idem |

**Decisão:** **C** (mais seguro com 80/443 ocupadas). B só se Tunnel não for viável agora.

### Como validar no Windows (um comando por linha)

```bat
nslookup inovahub.inovatitech.com.br 1.1.1.1
nslookup api.inovahub.inovatitech.com.br 1.1.1.1
curl -I https://inovahub.inovatitech.com.br
curl -I https://api.inovahub.inovatitech.com.br
```

Não cole dois comandos na mesma linha sem Enter — o Windows junta os nomes e falha (`...com.brnslookup...`, `...com.brcurl`).

Se `nslookup` sem `1.1.1.1` der timeout, o DNS local/IPv6 está instável; use `1.1.1.1` ou `8.8.8.8`.

---

## Registros Cloudflare (referência)

| Tipo | Nome | Conteúdo | Proxy | TTL | Nota |
|------|------|----------|-------|-----|------|
| A ou CNAME | `inovahub` | IP VPS **ou** tunnel | Proxied | Auto | App |
| A ou CNAME | `api-inovahub` | IP VPS **ou** tunnel | Proxied | Auto | API (1 nível — SSL free OK) |
| A | `api.inovahub` | — | — | — | **Remover ou DNS only**; SSL free quebra |

Com Tunnel (C), o dashboard Zero Trust costuma criar os CNAMEs automaticamente.

SSL/TLS Overview: **Full** (HTTP na origem via tunnel) está ok; **Full (strict)** se a origem falar HTTPS com cert válido.

---

## VPS: 80 e 443 já em uso

### Opção C — Cloudflare Tunnel (escolhida / mais segura)

Sem abrir 8443. O `cloudflared` sai da VPS para a Cloudflare (outbound).

#### C1 — Cloudflare Zero Trust
1. [dash.cloudflare.com](https://dash.cloudflare.com) → **Zero Trust** (pode pedir plano Free Zero Trust).
2. **Networks → Tunnels → Create a tunnel**.
3. Tipo: **Cloudflared**.
4. Nome: `inovahub`.
5. Copie o comando de instalação (token) — use na VPS no passo C2.

#### C2 — Instalar na VPS (PuTTY ou SSH Linux)

**PuTTY (este PC Windows):**
1. Abra PuTTY → Host Name: `128.140.77.31` (ou o hostname SSH) → Open.
2. Login com seu usuário (ex.: `root` ou o user com sudo).
3. Cole os comandos abaixo **um bloco por vez** (botão direito cola no PuTTY).

**PC Linux:** `ssh usuario@128.140.77.31` — mesmos comandos.

```bash
# 1) Instalar cloudflared (Debian/Ubuntu amd64)
curl -L --output cloudflared.deb https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb
sudo dpkg -i cloudflared.deb
cloudflared --version

# 2) NÃO cole texto placeholder. No dashboard:
#    Zero Trust → Networks → Tunnels → inovahub → Configure → Install connector
#    Copie o comando completo (já vem com o token eyJ...).
# Se JÁ existir cloudflared.service nesta VPS (outro tunnel), NÃO rode service install de novo —
#    ou adicione os hostnames inovahub/api-inovahub NO tunnel que já está Healthy,
#    ou: sudo cloudflared service uninstall && sudo cloudflared service install <token-real>
```

No dashboard o tunnel deve ficar **Healthy** (verde).

Diagnóstico se 502 continuar:
```bash
sudo journalctl -u cloudflared -n 50 --no-pager
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8088/
ss -tlnp | grep 8088 || true
```
- Se `curl` local ≠ 200: o Hub ainda não está rodando em `:8088`.
- Se tunnel Healthy e local 200, mas site 502: confira o **Public Hostname** (service URL).

#### C3 — App escutando só em localhost
No Compose de produção na VPS, publique o app só em loopback, ex.:
`127.0.0.1:8088:80`  
(não precisa ser 80/443 públicos).

#### C4 — Public hostnames do tunnel
Em **Tunnels → inovahub → Public Hostname → Add**:

| Subdomain | Domain | Service |
|-----------|--------|---------|
| `inovahub` | `inovatitech.com.br` | `http://127.0.0.1:8088` |
| `api-inovahub` | `inovatitech.com.br` | `http://127.0.0.1:8088` |

Mesmo backend; o Laravel separa rotas web vs `/api` e `/webhooks/*`.

Marque **DNS** para o Cloudflare criar/atualizar o CNAME.

#### C5 — Limpar DNS antigo da API
1. **DNS → Records**: apague ou desative o A `api.inovahub` (o que causa TLS ilegal).
2. Confirme existência de `api-inovahub` (CNAME tunnel ou A).

#### C6 — SSL e HTTPS
1. **SSL/TLS → Overview**: **Full** (origem HTTP via tunnel).
2. **Edge Certificates → Always Use HTTPS**: On.
3. Teste:
```bat
curl -I https://inovahub.inovatitech.com.br
curl -I https://api-inovahub.inovatitech.com.br
```
Esperado após app no ar: `200` / `302` / `404` Laravel — **não** erro 35 de TLS.

#### C7 — Webhooks (Meta / Pluggy / Asaas)
Use sempre:
`https://api-inovahub.inovatitech.com.br/webhooks/...`

---

### Opção B — fallback (8443 + Origin Rule)

Só se Tunnel atrasar. Ainda use hostname `api-inovahub` (não `api.inovahub`).

1. Proxy na VPS em `0.0.0.0:8443` → app.
2. UFW/Hetzner: liberar **8443/tcp**.
3. **Rules → Origin Rules**: hostname `inovahub` **OR** `api-inovahub` → destination port **8443**.
4. Cert na 8443 + SSL **Full (strict)**.
5. Mais superfície de ataque que o Tunnel.

### Opção A — vhost no proxy que já tem 80/443

Se descobrir que Caddy/Nginx/Traefik já é dono de 80/443, dá para só adicionar hosts — também seguro, sem porta nova. Mesmos hostnames `inovahub` + `api-inovahub`.

---

## URLs alvo (canônicas)

- App / Hub: `https://inovahub.inovatitech.com.br`
- API / webhooks: `https://api-inovahub.inovatitech.com.br`
  - Meta: `/webhooks/whatsapp`
  - Pluggy: `/webhooks/pluggy`
  - Asaas: `/webhooks/asaas`

---

## Portas locais (Docker Desktop)

| Serviço | Compose (host) | Status típico |
|---------|----------------|---------------|
| App | `127.0.0.1:8092` | bind no host costuma falhar nesta máquina |
| Postgres | `127.0.0.1:5442` | idem |
| Redis | `127.0.0.1:6392` | idem |

Containers sobem e respondem na rede interna. Teste:

```bat
docker exec inovahub-app curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1/
```

Esperado: `200`.

---

## Checklist Operador

- [x] DNS `inovahub` Proxied + HTTPS edge (`301`)
- [x] Diagnosticar falha TLS em `api.inovahub` (Universal SSL 2 níveis)
- [ ] Criar `api-inovahub` (1 nível) e remover/desativar `api.inovahub`
- [ ] Deploy origem: **C Tunnel** (preferido) ou B 8443
- [ ] SSL Full + Always HTTPS
- [ ] `curl -I` hub + api-inovahub sem erro 35
- [ ] Atualizar webhooks SaaS para `api-inovahub`
- [ ] (Depois) TXT Resend SPF/DKIM — DNS only
