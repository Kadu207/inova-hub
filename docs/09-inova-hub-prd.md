# 09 — PRD Inova Hub + Finova

**Versão:** 0.2  
**Data:** 21/07/2026  
**Status:** Draft — alinhado ao calendário D01–D56

## 1. Visão

Permitir que pessoas e empresas organizem **dinheiro, agenda e equipe** conversando com a **Finova** no WhatsApp e analisando tudo no **Inova Hub** — com Open Finance (Pluggy), billing self-service (Asaas) e compliance superiores ao Meu Assessor.

## 2. Objetivos de negócio

| Objetivo | Métrica (90 dias pós-MVP) |
|----------|---------------------------|
| Aquisição B2C | 500 trials ativos |
| Ativação | ≥60% enviam ≥3 msgs Finova na 1ª semana |
| Retenção | ≥40% M1 pagos |
| Conexão bancária | ≥40% dos ativos conectam ≥1 banco |
| NPS | ≥40 |
| Zero incidente LGPD crítico | 0 |

## 3. Personas

- **Carla (B2C):** profissional liberal, vive no Zap.  
- **Pedro (casal):** conta compartilhada.  
- **Renata (RH):** benefícios (fase B).  
- **Marcos (vertical):** packs (fase C).

## 4. Escopo por fase

### Fase A — MVP B2C (P0) — D01 a D56

**In:**
- Finova: texto + áudio → lançamentos, consultas, compromissos, tarefas  
- Finova + Hub: **Open Finance via Pluggy** (saldo, extrato, cartões)  
- Inova Hub: auth, dashboard, lançamentos, agenda Google, membros  
- **Asaas:** trial, assinatura, cancelar no painel  
- Export CSV + excluir conta (LGPD)  
- WhatsApp Cloud API  

**Out (MVP):**
- Stripe  
- Drive semântico completo  
- Meet + atas  
- B2B / verticais  
- App nativo  
- Pix/TED de saída (iniciador de pagamento)  

### Fase B — B2B Benefícios

Portal RH, CSV telefones, métricas agregadas  

### Fase C — Verticais

Packs clínica / imobiliária / profissional liberal  

## 5. Requisitos funcionais (MVP)

| ID | Requisito | Prioridade |
|----|-----------|------------|
| FR-01 | Vincular WhatsApp via OTP | P0 |
| FR-02 | Registrar despesa/receita por NL | P0 |
| FR-03 | Consultar totais por período/categoria | P0 |
| FR-04 | Criar compromisso + lembrete | P0 |
| FR-05 | Hub CRUD lançamentos | P0 |
| FR-06 | Sync Google Calendar (escopos mínimos) | P0 |
| FR-07 | Conta compartilhada com papéis | P0 |
| FR-08 | Cancelar assinatura no Hub (Asaas) | P0 |
| FR-09 | Exportar dados CSV | P0 |
| FR-10 | Excluir conta LGPD | P0 |
| FR-11 | Conectar bancos via Pluggy Connect | P0 |
| FR-12 | Sync contas/saldos/cartões/extratos | P0 |
| FR-13 | Finova: saldo, extrato, cartões | P0 |
| FR-14 | Revogar conexão Open Finance | P0 |
| FR-15 | MFA opcional no Hub | P1 |
| FR-16 | Stripe billing | P1 |

## 6. Requisitos não funcionais

- Disponibilidade 99,5% MVP  
- Latência Finova texto < 8s p95  
- Secrets só em `.env`/vault  
- **Multi-tenant:** `organization_id` + scopes + policies + RLS Postgres; testes IDOR obrigatórios  
- **UI 100% responsiva** (landing + Hub); DoD em docs/22-responsive-ui.md  
- **Segurança L1–L11** aplicáveis; docs/21-security-layers.md  
- Consentimento OF versionado  

## 7. Monetização

| Plano | Preço sugerido | Inclui |
|-------|----------------|--------|
| Trial | 7 dias | Core + OF |
| Pessoal | R$ 29,90/mês | Finova + Hub + Google + Pluggy |
| Família/Equipe | R$ 49,90/mês | Até 5 membros |
| Benefícios B2B | sob consulta | Fase B |

**Gateway MVP:** Asaas apenas.

## 8. Fora de escopo ético

Clean-room: não copiar código/assets/copy do Meu Assessor.

## 9. Referências

- [20-system-design.md](20-system-design.md) — System Design completo  
- [14-prerequisites-checklist.md](14-prerequisites-checklist.md)  
- [15-day-by-day-mvp.md](15-day-by-day-mvp.md)  
- [10-architecture.md](10-architecture.md)  
