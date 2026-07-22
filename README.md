# Inova Hub + Finova

Plataforma de finanças, agenda e equipe no WhatsApp.

| Papel | Nome | Uso |
|-------|------|-----|
| Plataforma / painel / B2B | **Inova Hub** | Site, dashboard, portal RH, verticais |
| Assistente WhatsApp | **Finova** | “Fale com a Finova” |
| Tagline | Finanças, agenda e equipe no WhatsApp | Marketing |

## Concorrente analisado

**Meu Assessor** (`meuassessor.com`) — assistente por IA via WhatsApp + painel web.  
**Não confundir** com o app das lojas “Meu Assessor IA” (AppStars) — produto independente.

## Estrutura

```text
docs/           # Snirf, PRD, arquitetura, roadmap
.firecrawl/     # Dados brutos do scrape (não versionar conteúdo sensível)
.specify/       # Spec Kit (em evolução)
```

## Documentação principal

1. [Resumo executivo](docs/00-executive-summary.md)
2. [Mapa do ecossistema](docs/01-ecosystem-map.md)
3. [Features do concorrente](docs/02-product-features.md)
4. [Jornadas](docs/03-user-journeys.md)
5. [Integrações](docs/04-integrations.md)
6. [Superfície de API](docs/05-api-surface.md)
7. [Telas UI](docs/06-ui-screens.md)
8. [Riscos legais/privacidade](docs/07-legal-privacy-risks.md)
9. [Concorrentes](docs/08-competitors.md)
10. [PRD Inova Hub](docs/09-inova-hub-prd.md)
11. [Arquitetura](docs/10-architecture.md)
12. [**System Design completo**](docs/20-system-design.md) — C4, dados, EDD, API, segurança, deploy
13. [Roadmap MVP](docs/11-roadmap-mvp.md)
14. [Marca Finova](docs/12-brand-finova.md)
15. [Gap snirf autenticado](docs/13-auth-sniff-gap.md)
16. [Checklist pré-requisitos](docs/14-prerequisites-checklist.md) — domínio, Cloudflare, Hetzner, Meta, Pluggy, Asaas
17. [Plano dia a dia D01–D56](docs/15-day-by-day-mvp.md) — requisitos, decisões, tarefas, entregáveis
18. [Banco + governança](docs/17-database-and-governance.md) — **PostgreSQL**, SDD/TDD/EDD
19. [Squads de agentes](docs/18-agent-squads.md)
20. [DNS Cloudflare](docs/19-dns-cloudflare.md) — tunnel + `api-inovahub`
21. [Regras de negócio](docs/business-rules/README.md)
22. [Camadas de segurança L1–L11](docs/21-security-layers.md)
23. [UI 100% responsiva](docs/22-responsive-ui.md)
24. [Banco multi-tenant + RLS](docs/23-multitenant-database.md)
25. [**Deploy VPS via PuTTY**](docs/24-deploy-vps-putty.md) — Compose + Tunnel `:8088`
26. [**Estado do projeto (memória)**](docs/25-project-state.md) — atualizar para não alucinar

## Decisões MVP

- **Banco de dados:** PostgreSQL 16 **multi-tenant** (`organization_id` + RLS)  
- **UI:** 100% responsiva (mobile-first)  
- **Segurança:** camadas L1–L11 obrigatórias  
- **Bancos (Open Finance):** Pluggy (leitura)  
- **Pagamentos:** Asaas (Stripe só depois)  
- **Infra:** Cloudflare + Hetzner + Docker  
- **Engenharia:** SDD + TDD + EDD + Spec Kit + CodeRabbit + Squads A/B  

## App

Código Laravel em [`app/`](app/). Subir local (portas isoladas dos outros projetos Inova):

```powershell
docker compose up -d postgres redis
docker compose up -d --build app
```

| Container | Serviço | Porta no host (local) |
|-----------|---------|------------------------|
| `inovahub-app` | Laravel | `127.0.0.1:8092` |
| `inovahub-postgres` | PostgreSQL | `127.0.0.1:5442` |
| `inovahub-redis` | Redis | `127.0.0.1:6392` |

Projeto Docker Compose: **`inovahub`** (não mais o nome da pasta).

```powershell
docker exec inovahub-app curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1/
```
## Limites do snirf

Análise **passiva e pública** (+ painel autenticado apenas com conta própria do operador).  
Sem pentest, força bruta ou exploração de `admin`.
