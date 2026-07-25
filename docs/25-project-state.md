# Estado real do projeto (atualizar a cada mudança)

**Última atualização:** 2026-07-25 (D13 card Finova no Hub; D12 live na VPS)  
**Regra:** agentes devem ler isto e **corrigir** após qualquer deploy/DNS/tunnel — não alucinar.

## DNS / VPS

| Item | Status |
|------|--------|
| Hub + API HTTPS | **200** ✅ |
| `/opt/inovahub` Git | OK |
| D08–D12 em prod | OK (pull `d26d2a4` + app/worker rebuild) |
| D13 na VPS | **Pendente deploy** (`git pull` + restart app) |

## Código

| Item | Status |
|------|--------|
| D12 Finova oi/ajuda | Live na VPS |
| D13 card status Finova | No `main` após push |
| Home `/hub` | Conectado / desconectado + CTA OTP |
| Testes | `HubHomeWhatsappStatusTest` **3 passed** (+ AuthFlow) |

## Operador

1. Meta tokens no `.env` ainda necessários para send real (D12).
2. Deploy D13: `git pull` + `up -d --build app` (sem migrate).
3. Abrir https://inovahub.inovatitech.com.br/hub e conferir card Finova.
4. Próximo: **D14** retrospectiva semana 2 + suite testes.
