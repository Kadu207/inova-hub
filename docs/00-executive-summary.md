# 00 — Resumo executivo

**Data:** 21 de julho de 2026  
**Produto a construir:** Inova Hub (plataforma) + Finova (assistente WhatsApp)  
**Alvo do snirf:** Meu Assessor (`meuassessor.com`)  
**Escopo comercial:** A (B2C) + B (B2B benefícios) + C (verticais)

## Veredito

O Meu Assessor é um SaaS maduro de **assistente pessoal por IA no WhatsApp** com painel web complementar — **sem app nativo oficial**. Posiciona-se em finanças + agenda + tarefas + drive + Open Finance, cobrado via Hotmart (~R$ 29,90/mês no plano anual promocional).

Há **colisão de marca** com o app “Meu Assessor IA” nas lojas (AppStars) — produto distinto. Inova Hub / Finova devem comunicar essa distinção e proteger a marca própria.

## Oportunidade

| Lacuna do concorrente | Como Inova Hub / Finova ganha |
|----------------------|-------------------------------|
| Identidade jurídica confusa (Tittanium / JMM / Meu Assessor) | CNPJ e marca claros |
| Cancelamento só via Hotmart (reclamações públicas) | Cancelar/exportar/excluir no painel |
| Alegação “E2E” imprecisa (TLS ≠ E2E) | Comunicação técnica honesta |
| Escopos Google inconsistentes na política | OAuth mínimo + consentimento versionado |
| B2B benefícios pouco desenvolvido | Portal RH nativo (fase B) |
| Sem verticais claras | Packs por nicho (fase C) |
| App falso nas lojas | Aviso oficial + marca Finova distinta |

## Arquitetura de marca (aprovada)

- **Inova Hub** — plataforma, painel, B2B, verticais  
- **Finova** — “Fale com a Finova” no WhatsApp  
- **Tagline:** Finanças, agenda e equipe no WhatsApp  

## Status do snirf

| Camada | Status |
|--------|--------|
| Site público + políticas | Concluído (Firecrawl + pesquisa) |
| Subdomínios (app, admin, beneficios) | Mapeados |
| App lojas AppStars | Documentado como NÃO oficial |
| Painel autenticado (APIs internas) | **Gap** — requer conta teste do operador |
| Admin | Só superfície pública (sem login) |

## Decisões de build (MVP)

| Tema | Decisão |
|------|---------|
| Bancos | **Pluggy** Open Finance (P0, somente leitura) |
| Pagamentos | **Asaas** (não Stripe no MVP) |
| Infra | Cloudflare DNS + Hetzner VPS + Docker |
| Calendário | Google OAuth escopos mínimos |

## Próximo passo comercial

1. Preencher [checklist](14-prerequisites-checklist.md) (domínio, IP, contas Meta/Pluggy/Asaas)  
2. Executar [plano dia a dia D01–D56](15-day-by-day-mvp.md)  
3. (Opcional) Conta teste Meu Assessor para snirf autenticado  

Ver: [PRD](09-inova-hub-prd.md) · [Roadmap](11-roadmap-mvp.md) · [Arquitetura](10-architecture.md)
