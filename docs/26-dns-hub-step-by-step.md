# D06 — DNS Hub: passo a passo completo

**Objetivo:** `https://inovahub.inovatitech.com.br` → **HTTP 200** (igual `api-inovahub`).

**Status verificado 24/07/2026:**
- `api-inovahub.inovatitech.com.br` → DNS OK + HTTPS 200 ✅  
- `inovahub.inovatitech.com.br` → **NXDOMAIN** ❌ (ainda falta o registro)

Você mencionou “rota criada: api-inovahub” — isso está **correto para a API**. O Hub precisa de um **segundo** Public Hostname / CNAME chamado exatamente `inovahub` (sem `api-`).

---

## Opção A — Zero Trust Tunnel (recomendado)

1. Abra https://dash.cloudflare.com  
2. Entre em **Zero Trust** (mesmo login da conta).  
3. Menu **Networks → Tunnels**.  
4. Clique no tunnel **`inovahub`** (status deve estar Healthy).  
5. Aba **Public Hostname** → liste o que existe:
   - Deve ter: `api-inovahub` · `inovatitech.com.br` · `http://127.0.0.1:8088`
6. Clique **Add a public hostname** e preencha:

| Campo | Valor exato |
|--------|-------------|
| Subdomain | `inovahub` |
| Domain | `inovatitech.com.br` |
| Path | *(vazio)* |
| Type | HTTP |
| URL | `127.0.0.1:8088` |

7. Salve. O Cloudflare deve criar automaticamente o DNS CNAME.  
8. Confira em **Websites → inovatitech.com.br → DNS → Records**:

| Type | Name | Content (exemplo) | Proxy |
|------|------|-------------------|-------|
| CNAME | `inovahub` | `<uuid>.cfargotunnel.com` | Proxied (laranja) |
| CNAME | `api-inovahub` | `<uuid>.cfargotunnel.com` | Proxied |

Os dois targets devem ser o **mesmo** tunnel UUID.

---

## Opção B — Só DNS (se o Public Hostname do Hub já existir)

1. **DNS → Records → Add record**  
2. Type: **CNAME**  
3. Name: **`inovahub`**  
4. Target: copie o target do registro `api-inovahub` (termina em `.cfargotunnel.com`)  
5. Proxy: **Proxied**  
6. Save  

---

## Validação (Windows CMD / PowerShell)

```bat
nslookup inovahub.inovatitech.com.br 1.1.1.1
curl -I https://inovahub.inovatitech.com.br
curl -I https://api-inovahub.inovatitech.com.br
```

**Sucesso Hub:**
- `nslookup` mostra IPs Cloudflare (104.x / 172.x), **não** NXDOMAIN  
- `curl -I` → `HTTP/2 200` (ou 301/302), headers `server: cloudflare` e idealmente `x-powered-by: PHP/8.4.x`

**Ainda falhou?**
- NXDOMAIN → registro DNS não criado ou nome errado (`inovahub` ≠ `api-inovahub`)  
- 502/530 → tunnel down ou nada em `:8088` na VPS  
- Timeout → proxy/firewall; teste da VPS: `curl -I https://inovahub.inovatitech.com.br`

---

## Checklist rápido

- [ ] Public Hostname `inovahub` → `http://127.0.0.1:8088`  
- [ ] Public Hostname `api-inovahub` → `http://127.0.0.1:8088`  
- [ ] CNAME DNS Proxied para os dois  
- [ ] `nslookup inovahub… 1.1.1.1` OK  
- [ ] `curl -I https://inovahub…` → 200  
