# 15 — Plano dia a dia do MVP (D01–D56)

**Produto:** Inova Hub + Finova  
**Duração:** 8 semanas (56 dias corridos de trabalho planejado)  
**Início sugerido:** preencher data → D01 = ___/___/______  
**Donos:** Operador (você) · Agente (Cursor) · Ambos  
**Pagamentos:** Asaas · **Bancos:** Pluggy · **Infra:** Cloudflare + Hetzner  

## Formato de cada dia

- Requisitos · Decisões · Tarefas · Entregáveis · Donos · Dependências · Critério de pronto

## Calendário macro

| Semana | Dias | Foco |
|--------|------|------|
| 1 | D01–D07 | Contas + DNS + VPS + skeleton |
| 2 | D08–D14 | Auth Hub + WhatsApp webhook |
| 3 | D15–D21 | Finanças manuais + NLU/áudio |
| 4 | D22–D28 | Pluggy Open Finance |
| 5 | D29–D35 | Google Agenda + tarefas |
| 6 | D36–D42 | Asaas + LGPD self-service |
| 7 | D43–D49 | Conta compartilhada + segurança |
| 8 | D50–D56 | Beta + polish + soft launch |

Checklist de contas: [14-prerequisites-checklist.md](14-prerequisites-checklist.md)

---

# Semana 1 — Fundação (D01–D07)

### D01 — Kickoff e abertura de contas críticas

- **Requisitos:** Domínio conhecido; acesso Cloudflare e Hetzner; CNPJ/CPF para KYC.
- **Decisões:** Asaas (não Stripe); Pluggy; Meta WABA em paralelo.
- **Tarefas:**
  - [ ] Preencher domínio/IP no doc 14
  - [ ] Criar/abrir Meta Business + iniciar WABA
  - [ ] Criar conta Pluggy (sandbox)
  - [ ] Criar conta Asaas
  - [ ] Criar conta Resend + OpenAI/Groq
  - [ ] Criar repo GitHub privado `inova-hub`
- **Entregáveis:** Doc 14 parcialmente preenchido; 4 contas em andamento; repo vazio.
- **Donos:** Operador (contas) · Agente (atualizar docs se necessário)
- **Dependências:** Nenhuma
- **Critério de pronto:** Meta, Pluggy e Asaas com cadastro iniciado; repo criado.

### D02 — DNS Cloudflare

- **Requisitos:** Zona Cloudflare ativa; IP VPS.
- **Decisões:** Subdomínios `@`, `app`, `api`; proxy laranja; SSL Full (strict) na sequência.
- **Tarefas:**
  - [ ] Criar registros A para `@`, `app`, `api`
  - [ ] Anotar propagação
  - [ ] Preparar hostname webhook futuros
- **Entregáveis:** DNS configurado; print/checklist no doc 14.
- **Donos:** Operador
- **Dependências:** D01 (IP)
- **Critério de pronto:** `dig`/`nslookup` resolve para o IP da VPS.

### D03 — VPS Hetzner harden + Docker

- **Requisitos:** SSH na VPS.
- **Decisões:** Docker Compose (não K8s); firewall só 22/80/443.
- **Tarefas:**
  - [ ] Atualizar SO
  - [ ] Instalar Docker Engine + Compose plugin
  - [ ] UFW/firewall
  - [ ] User deploy + chave SSH
  - [ ] Snapshot inicial
- **Entregáveis:** VPS com Docker; acesso deploy documentado.
- **Donos:** Operador (ou Agente com SSH se autorizado)
- **Dependências:** D01
- **Critério de pronto:** `docker run hello-world` ok; portas restritas.

### D04 — Skeleton Laravel no repo

- **Requisitos:** Repo GitHub; PHP/Composer local ou via Docker.
- **Decisões:** Laravel 11+; Sanctum; estrutura `app` monolito API+web inicial.
- **Tarefas:**
  - [ ] `laravel new` / Composer create-project
  - [ ] `.env.example` com vars do doc 14
  - [ ] README de setup local
  - [ ] Commit inicial
- **Entregáveis:** Código base no GitHub.
- **Donos:** Agente (+ Operador review)
- **Dependências:** D01 repo
- **Critério de pronto:** App sobe localmente (ou em container) com página welcome.

### D05 — Compose: Postgres + Redis + app

- **Requisitos:** Docker na VPS e/ou local.
- **Decisões:** Postgres 16; Redis 7; serviços `app`, `worker`, `horizon`, `scheduler`.
- **Tarefas:**
  - [ ] Escrever `docker-compose.yml`
  - [ ] Volumes persistentes DB
  - [ ] Healthcheck DB/Redis
  - [ ] Migrar schema vazio
- **Entregáveis:** Compose funcional; DB acessível.
- **Donos:** Agente
- **Dependências:** D03, D04
- **Critério de pronto:** `migrate` ok; Redis PING ok.

### D06 — Reverse proxy + HTTPS

- **Requisitos:** DNS propagado; Docker up.
- **Decisões:** Traefik ou Caddy **ou** Cloudflare Flexible→Full com origem; preferir **Caddy** na VPS + Cloudflare Full strict.
- **Tarefas:**
  - [ ] Expor `app` e `api` (pode ser mesmo host com paths)
  - [ ] Certificados / Origin CA
  - [ ] Rota `/up` health
- **Entregáveis:** `https://app.<dominio>` e `https://api.<dominio>` respondendo.
- **Donos:** Ambos
- **Dependências:** D02, D05
- **Critério de pronto:** HTTPS 200 no healthcheck via domínio público.

### D07 — Fechamento semana 1 + CI mínimo

- **Requisitos:** Repo + VPS.
- **Decisões:** GitHub Actions: lint/test smoke; deploy manual na semana 1.
- **Tarefas:**
  - [ ] Workflow CI básico
  - [ ] Revisar doc 14 status
  - [ ] Confirmar KYC Meta/Pluggy/Asaas (follow-up)
- **Entregáveis:** CI verde; relatório status contas.
- **Donos:** Ambos
- **Dependências:** D01–D06
- **Critério de pronto:** Pipeline passa; HTTPS estável 24h.

---

# Semana 2 — Auth + WhatsApp (D08–D14)

### D08 — Modelo de usuários e tenancy

- **Requisitos:** Skeleton Laravel.
- **Decisões:** `organizations` + `memberships` desde o dia 1 (multi-tenant leve).
- **Tarefas:**
  - [ ] Migrations users/orgs/members
  - [ ] Policies base
  - [ ] Seed admin de teste
- **Entregáveis:** Schema tenancy; factories.
- **Donos:** Agente
- **Dependências:** D05
- **Critério de pronto:** Criar org + user em tinker/tests.

### D09 — Auth Hub (register/login)

- **Requisitos:** Tenancy.
- **Decisões:** E-mail/senha + Sanctum session/spa; reset via Resend.
- **Tarefas:**
  - [ ] Telas ou API register/login
  - [ ] Integração Resend reset password
  - [ ] Rate limit login
- **Entregáveis:** Login funcional no Hub.
- **Donos:** Agente
- **Dependências:** D08; Resend (D01)
- **Critério de pronto:** Ciclo register→login→logout ok.

### D10 — Onboarding: vincular WhatsApp (OTP)

- **Requisitos:** Auth; Meta token (sandbox/dev se produção atrasar).
- **Decisões:** OTP de 6 dígitos via Finova; vínculo `whatsapp_identities`.
- **Tarefas:**
  - [ ] Gerar OTP no Hub
  - [ ] Tabela vínculo telefone↔user
  - [ ] Fluxo “envie o código à Finova”
- **Entregáveis:** Fluxo OTP documentado + código.
- **Donos:** Ambos (Meta token = Operador)
- **Dependências:** D09; Meta parcialmente
- **Critério de pronto:** Número de teste vinculado a um user.

### D11 — Webhook Meta WhatsApp

- **Requisitos:** URL pública HTTPS; App Secret.
- **Decisões:** Validar assinatura; idempotência por `wamid`.
- **Tarefas:**
  - [ ] `POST /webhooks/whatsapp` verify + receive
  - [ ] Fila job `ProcessWhatsAppMessage`
  - [ ] Log sanitizado
- **Entregáveis:** Webhook verificado na Meta.
- **Donos:** Ambos
- **Dependências:** D06, D10
- **Critério de pronto:** Meta console mostra webhook ativo; mensagem teste enfileirada.

### D12 — Finova responde (echo + help)

- **Requisitos:** Webhook + worker.
- **Decisões:** Intent `help` e fallback educado; tom marca Finova.
- **Tarefas:**
  - [x] Sender WhatsApp API
  - [x] Intent router mínimo
  - [x] Copy “Eu sou a Finova…”
- **Entregáveis:** Bot responde no Zap.
- **Donos:** Agente
- **Dependências:** D11
- **Critério de pronto:** “oi” / “ajuda” respondidos < 8s. (código pronto; validar E2E após tokens Meta + deploy VPS)

### D13 — Painel home + sessão WhatsApp status

- **Requisitos:** Auth + vínculo.
- **Decisões:** Mostrar status conectado/desconectado WhatsApp.
- **Tarefas:**
  - [x] Dashboard v0
  - [x] Card status Finova
  - [x] Link reenviar OTP
- **Entregáveis:** UI home.
- **Donos:** Agente
- **Dependências:** D10, D12
- **Critério de pronto:** User vê status do Zap no Hub.

### D14 — Retrospectiva semana 2 + testes

- **Requisitos:** Fluxos D08–D13.
- **Decisões:** Lista de bugs P0 vs P1.
- **Tarefas:**
  - [x] Testes feature auth + webhook signature
  - [x] Checklist Meta produção (se KYC ok)
  - [x] Atualizar doc 14
- **Entregáveis:** Testes verdes; notas de risco Meta.
- **Donos:** Ambos
- **Dependências:** Semana 2
- **Critério de pronto:** Suite auth/webhook passa; Finova estável em número de teste. (suite **23 passed**; E2E Zap pendente tokens Meta — [28](28-week2-retro.md))

---

# Semana 3 — Finanças manuais + IA (D15–D21)

### D15 — Domínio transações e categorias

- **Requisitos:** Tenancy.
- **Decisões:** Categorias default BR (moradia, comida, transporte…); multi-currency depois.
- **Tarefas:**
  - [x] Migrations transactions/categories
  - [x] Seed categorias
  - [x] CRUD API
- **Entregáveis:** Modelo financeiro.
- **Donos:** Agente
- **Dependências:** D08
- **Critério de pronto:** CRUD via API/tests. (`/api/v1/transactions` + `/api/v1/categories`; BR-011)

### D16 — Hub: lista e filtros de lançamentos

- **Requisitos:** CRUD API.
- **Decisões:** Filtro data/categoria; paginação.
- **Tarefas:**
  - [x] Tela `/app/transactions`
  - [x] Editar/excluir
  - [x] Totais do período
- **Entregáveis:** UI lançamentos.
- **Donos:** Agente
- **Dependências:** D15
- **Critério de pronto:** User cria/edita gasto no Hub. (`/hub/transactions`; alias `/app/transactions`)

### D17 — NLU `tx.create` / `tx.income` (texto)

- **Requisitos:** LLM key; Finova router.
- **Decisões:** Confirmar se confiança < limiar; valores em BRL.
- **Tarefas:**
  - [x] Prompt/extractor JSON estruturado
  - [x] Intent handlers
  - [x] Eval set 20 frases
- **Entregáveis:** Gastos por texto no Zap.
- **Donos:** Agente
- **Dependências:** D12, D15; LLM
- **Critério de pronto:** ≥85% no eval set texto. (heurística ≥85%; LLM opcional via `OPENAI_API_KEY`/`GROQ_API_KEY`)

### D18 — STT áudio → mesmo pipeline

- **Requisitos:** Whisper/OpenAI; download mídia Meta.
- **Decisões:** Áudio não retido após STT (retenção mínima).
- **Tarefas:**
  - [x] Download media WhatsApp
  - [x] Transcrição
  - [x] Reusar NLU
- **Entregáveis:** Gasto por áudio.
- **Donos:** Agente
- **Dependências:** D17
- **Critério de pronto:** 5 áudios de teste corretos. (`FinovaAudioSttTest`)

### D19 — Consultas `tx.query`

- **Requisitos:** Transações.
- **Decisões:** Períodos: hoje, semana, mês; top categorias.
- **Tarefas:**
  - [x] Intent consulta
  - [x] Resposta formatada Finova
  - [x] Espelhar cards no Hub dashboard
- **Entregáveis:** “Quanto gastei essa semana?” funcional.
- **Donos:** Agente
- **Dependências:** D17
- **Critério de pronto:** Consulta bate com soma no Hub.

### D20 — Dashboard gráficos v1

- **Requisitos:** Transações.
- **Decisões:** Gráfico por categoria + evolução 30 dias (simples).
- **Tarefas:**
  - [x] Endpoints aggregates
  - [x] Charts no Hub
- **Entregáveis:** Dashboard útil.
- **Donos:** Agente
- **Dependências:** D16
- **Critério de pronto:** Totais consistentes com lista. (`/api/v1/aggregates*`; barras CSS no `/hub`)

### D21 — Hardening NLU + semana 3 QA

- **Requisitos:** Intents financeiros.
- **Decisões:** Lista de ambiguidades (parcelas, USD, etc.) → backlog.
- **Tarefas:**
  - [x] Expandir eval set para 50
  - [x] Corrige falsos positivos
  - [x] Doc intents em `12-brand-finova.md` sync
- **Entregáveis:** Relatório acurácia; intents atualizados. (`docs/30-week3-nlu-qa.md`)
- **Donos:** Ambos
- **Dependências:** D17–D20
- **Critério de pronto:** ≥85% no eval 50; sem crash em áudio vazio.

---

# Semana 4 — Open Finance Pluggy (D22–D28)

### D22 — Conta Pluggy + adapter

- **Requisitos:** Credenciais sandbox Pluggy.
- **Decisões:** Interface `OpenFinanceProvider` → implementação Pluggy (HTTP Laravel; sem SDK PHP oficial).
- **Tarefas:**
  - [x] Adapter `PluggyOpenFinanceProvider` (auth + list connectors)
  - [x] Config `.env` (`PLUGGY_*`)
  - [x] Teste listar connectors (Http::fake + `pluggy:connectors`)
- **Entregáveis:** Adapter conecta API sandbox. (`docs/31-pluggy-setup.md`)
- **Donos:** Ambos
- **Dependências:** Conta Pluggy (D01+)
- **Critério de pronto:** API sandbox responde com client credentials.

### D23 — Widget Connect no Hub

- **Requisitos:** Adapter; auth Hub.
- **Decisões:** Connect token por usuário; UX “Conectar banco”; `/app/connections` → `/hub/connections`.
- **Tarefas:**
  - [x] Endpoint create connect token (`POST /hub/connections/connect-token` + API)
  - [x] Embutir widget Pluggy (`includeSandbox`)
  - [x] Tela `/hub/connections` (+ redirect `/app/connections`)
- **Entregáveis:** User inicia OAuth bancário sandbox; `of_items` guarda `pluggy_item_id` no sucesso.
- **Donos:** Agente
- **Dependências:** D22, D09
- **Critério de pronto:** Fluxo Connect completa item de teste Pluggy.

### D24 — Webhook Pluggy + persistência

- **Requisitos:** URL pública.
- **Decisões:** Persistir `items`, `accounts`, `transactions` OF separados de lançamentos manuais (com link/merge depois).
- **Tarefas:**
  - [x] `POST /webhooks/pluggy` (secret header opcional)
  - [x] Job `SyncPluggyItem`
  - [x] Migrations OF (`of_items` fix user_id + `of_accounts` / `of_transactions`)
- **Entregáveis:** Dados sandbox no Postgres.
- **Donos:** Agente
- **Dependências:** D23, D06
- **Critério de pronto:** Webhook processa evento `item/created` ou similar.

### D25 — Sync saldos, extratos, cartões

- **Requisitos:** Item conectado.
- **Decisões:** Sync periódico (`pluggy:sync-items` hourly) + on-demand no Hub.
- **Tarefas:**
  - [x] Sync accounts/credit cards (via `SyncsPluggyItem`)
  - [x] Sync transactions
  - [x] UI listar contas + extrato no Hub
- **Entregáveis:** Contas visíveis no Hub (`/hub/connections` + extrato por conta).
- **Donos:** Agente
- **Dependências:** D24
- **Critério de pronto:** Saldo e ≥1 transação sandbox visíveis.

### D26 — Finova intents bancários

- **Requisitos:** Dados OF.
- **Decisões:** Intents `bank.balance`, `bank.statement`, `bank.cards`.
- **Tarefas:**
  - [x] Handlers + copy Finova
  - [x] Eval set bancário (`BankIntentEvalSet`)
- **Entregáveis:** Consultas no Zap.
- **Donos:** Agente
- **Dependências:** D25, D12
- **Critério de pronto:** “Qual meu saldo?” responde coerente.

### D27 — Categorização IA de transações OF + revogação

- **Requisitos:** TX OF.
- **Decisões:** Categoria sugerida editável; revogar conexão no Hub.
- **Tarefas:**
  - [x] Job categorize (`CategorizeOfTransactions` + heurística PT-BR)
  - [x] Botão desconectar / revogar consentimento
  - [x] Apagar dados OF no revoke (`consent_revoked_at`, BR-006)
- **Entregáveis:** Fluxo LGPD de desconexão + edição de categoria no extrato.
- **Donos:** Agente
- **Dependências:** D25
- **Critério de pronto:** Revogar remove item e dados associados.

### D28 — Termos OF + QA semana 4

- **Requisitos:** Fluxos OF.
- **Decisões:** Texto consentimento versionado (`consent_version`).
- **Tarefas:**
  - [x] Atualizar Termos/Privacidade (rascunho) — `/legal/*` + docs 32/33
  - [x] Checklist instituições testadas no doc 14
  - [x] Testes isolamento tenant em dados OF (`OfTenantIsolationTest`)
- **Entregáveis:** Docs legais OF; testes IDOR OF; `consent_logs`.
- **Donos:** Ambos
- **Dependências:** D22–D27
- **Critério de pronto:** Consentimento registrado; IDOR OF falha (bloqueado).

---

# Semana 5 — Agenda + tarefas (D29–D35)

### D29 — Google Cloud OAuth app

- **Requisitos:** Projeto GCP; domínio verificado se necessário.
- **Decisões:** Escopos mínimos Calendar; People API **fora** do MVP.
- **Tarefas:**
  - [x] Wiring OAuth Hub (`/hub/google`) + redirect/callback
  - [x] Redirect URI documentada (`/hub/google/callback`)
  - [x] Tela consentimento interna (`gcal-1.0`)
  - [ ] Criar OAuth client no GCP (operador) + secrets no `.env.prod`
- **Entregáveis:** Client ID/Secret no `.env` (operador); app inicia OAuth.
- **Donos:** Operador (+ Agente wiring)
- **Dependências:** D09
- **Critério de pronto:** Botão “Conectar Google” inicia OAuth.

### D30 — Sync Calendar → Hub

- **Requisitos:** Tokens OAuth encrypted at rest.
- **Decisões:** Sync incremental; criar/editar só com ação do usuário.
- **Tarefas:**
  - [ ] Guardar tokens encrypted
  - [ ] Sync eventos
  - [ ] UI `/app/calendar`
- **Entregáveis:** Agenda no Hub.
- **Donos:** Agente
- **Dependências:** D29
- **Critério de pronto:** Eventos Google aparecem no Hub.

### D31 — Finova `event.create` / `event.query`

- **Requisitos:** Calendar sync.
- **Decisões:** Criar no Google + Hub; lembrete WhatsApp.
- **Tarefas:**
  - [ ] Intents agenda
  - [ ] Job lembrete (Horizon schedule)
- **Entregáveis:** Compromissos por Zap.
- **Donos:** Agente
- **Dependências:** D30, D12
- **Critério de pronto:** “Dentista sexta 15h” cria evento; “o que tenho amanhã?” lista.

### D32 — Tarefas e projetos v1

- **Requisitos:** Tenancy.
- **Decisões:** Task simples + projeto opcional (sem kanban completo).
- **Tarefas:**
  - [ ] Migrations tasks/projects
  - [ ] UI lista
  - [ ] Intent `task.create`
- **Entregáveis:** Tarefas Hub + Finova.
- **Donos:** Agente
- **Dependências:** D08
- **Critério de pronto:** Criar tarefa nos dois canais.

### D33 — Lembretes WhatsApp confiáveis

- **Requisitos:** Scheduler.
- **Decisões:** Timezone do user; anti-duplicata.
- **Tarefas:**
  - [ ] Schedule reminders
  - [ ] Testes fuso
- **Entregáveis:** Lembrete disparado.
- **Donos:** Agente
- **Dependências:** D31
- **Critério de pronto:** Lembrete chega no horário ±2 min em teste.

### D34 — UX onboarding unificado

- **Requisitos:** Zap + Google + Banco.
- **Decisões:** Wizard 3 passos: WhatsApp → Banco (opcional) → Google (opcional).
- **Tarefas:**
  - [ ] Tela onboarding
  - [ ] Checklist progresso
- **Entregáveis:** Onboarding < 5 min.
- **Donos:** Agente
- **Dependências:** D10, D23, D29
- **Critério de pronto:** Novo user completa fluxo guiado.

### D35 — QA semana 5

- **Requisitos:** Agenda/tarefas.
- **Decisões:** Backlog Meet/atas = pós-MVP.
- **Tarefas:**
  - [ ] Testes OAuth revoke
  - [ ] Eval intents agenda/tarefa
- **Entregáveis:** Relatório QA.
- **Donos:** Ambos
- **Dependências:** D29–D34
- **Critério de pronto:** Revogar Google para tokens; intents ≥85%.

---

# Semana 6 — Asaas + LGPD (D36–D42)

### D36 — BillingGateway + Asaas sandbox

- **Requisitos:** API Key Asaas.
- **Decisões:** Interface `BillingGateway`; **somente Asaas**; Stripe não implementar.
- **Tarefas:**
  - [ ] Client Asaas
  - [ ] Clientes/assinaturas sandbox
  - [ ] Mapear planos Pessoal/Família
- **Entregáveis:** Cobrança teste criada.
- **Donos:** Ambos
- **Dependências:** Conta Asaas
- **Critério de pronto:** Webhook sandbox recebido (preparar D37).

### D37 — Webhooks Asaas + entitlement

- **Requisitos:** URL webhook.
- **Decisões:** Status `trialing`, `active`, `past_due`, `canceled`.
- **Tarefas:**
  - [ ] `POST /webhooks/asaas`
  - [ ] Atualizar `subscriptions`
  - [ ] Gate features por status
- **Entregáveis:** Acesso liberado/bloqueado por pagamento.
- **Donos:** Agente
- **Dependências:** D36
- **Critério de pronto:** Pagamento aprovado → active; cancelamento → canceled.

### D38 — Checkout no Hub

- **Requisitos:** Entitlement.
- **Decisões:** Trial 7 dias sem cartão se Asaas permitir; senão cartão no trial end.
- **Tarefas:**
  - [ ] Tela `/app/billing`
  - [ ] Escolher plano + PIX/cartão
  - [ ] Mostrar próxima fatura
- **Entregáveis:** UI billing.
- **Donos:** Agente
- **Dependências:** D37
- **Critério de pronto:** User sandbox completa pagamento teste.

### D39 — Cancelar assinatura no Hub (diferencial)

- **Requisitos:** Asaas cancel API.
- **Decisões:** Cancelamento self-service imediato; sem Hotmart.
- **Tarefas:**
  - [ ] Botão cancelar + confirmação
  - [ ] E-mail Resend confirmação
  - [ ] Protocolo/ID cancelamento
- **Entregáveis:** Cancel self-service.
- **Donos:** Agente
- **Dependências:** D38
- **Critério de pronto:** User cancela sem suporte humano.

### D40 — Exportar dados (CSV)

- **Requisitos:** Transações + eventos + tarefas.
- **Decisões:** CSV zip; job assíncrono se volume alto.
- **Tarefas:**
  - [ ] Export transactions/events/tasks
  - [ ] Download seguro autenticado
- **Entregáveis:** Export LGPD.
- **Donos:** Agente
- **Dependências:** D15, D30, D32
- **Critério de pronto:** Arquivo abre no Excel/Sheets.

### D41 — Excluir conta (LGPD)

- **Requisitos:** Cascade tenancy + OF revoke.
- **Decisões:** Soft-delete 7 dias opcional **ou** hard delete imediato — **hard delete** com confirmação dupla no MVP.
- **Tarefas:**
  - [ ] Fluxo delete account
  - [ ] Revogar Pluggy + Google
  - [ ] Cancelar Asaas
  - [ ] Apagar PII
- **Entregáveis:** Delete completo.
- **Donos:** Agente
- **Dependências:** D27, D35, D39
- **Critério de pronto:** Conta some; webhooks externos revogados.

### D42 — Textos legais + QA billing

- **Requisitos:** Fluxos D36–D41.
- **Decisões:** Publicar Termos/Privacidade alinhados a Asaas+Pluggy+Meta+Google.
- **Tarefas:**
  - [ ] Páginas legais no site
  - [ ] Testes billing edge (falha PIX, chargeback simulado se houver)
- **Entregáveis:** Legais no ar; QA billing.
- **Donos:** Ambos
- **Dependências:** Semana 6
- **Critério de pronto:** Jurídico mínimo online; cancel/export/delete ok.

---

# Semana 7 — Equipe + segurança (D43–D49)

### D43 — Convites conta compartilhada

- **Requisitos:** Memberships.
- **Decisões:** Papéis `owner`, `member`, `viewer`.
- **Tarefas:**
  - [ ] Convite por e-mail/WhatsApp
  - [ ] Aceite vínculo
  - [ ] UI `/app/members`
- **Entregáveis:** 2 users na mesma org.
- **Donos:** Agente
- **Dependências:** D08
- **Critério de pronto:** Membro lança gasto visível ao owner.

### D44 — Permissões por papel

- **Requisitos:** Convites.
- **Decisões:** Viewer só lê; member escreve; owner billing/OF/Google.
- **Tarefas:**
  - [ ] Policies Laravel
  - [ ] Testes autorização
- **Entregáveis:** Matriz de permissões.
- **Donos:** Agente
- **Dependências:** D43
- **Critério de pronto:** Viewer bloqueado em delete billing.

### D45 — Suite IDOR / isolamento

- **Requisitos:** Multi-tenant em todos recursos.
- **Decisões:** Testes automatizados obrigatórios pré-beta.
- **Tarefas:**
  - [ ] Testes cross-org em transactions, OF, events, files
  - [ ] Corrigir vazamentos
- **Entregáveis:** Relatório IDOR verde.
- **Donos:** Agente
- **Dependências:** D43–D44, D25
- **Critério de pronto:** 0 IDOR conhecido nos testes.

### D46 — Rate limit + abuse WhatsApp

- **Requisitos:** Produção próxima.
- **Decisões:** Limite msgs/user/hora; quarantine.
- **Tarefas:**
  - [ ] Rate limit webhook
  - [ ] Alertas log
- **Entregáveis:** Proteção abuse.
- **Donos:** Agente
- **Dependências:** D11
- **Critério de pronto:** Flood teste é cortado.

### D47 — Backups e restore drill

- **Requisitos:** Postgres volume.
- **Decisões:** Snapshot Hetzner + dump diário.
- **Tarefas:**
  - [ ] Script dump
  - [ ] Testar restore em volume novo
- **Entregáveis:** Runbook backup.
- **Donos:** Operador (+ Agente script)
- **Dependências:** D05
- **Critério de pronto:** Restore bem-sucedido documentado.

### D48 — Observabilidade mínima

- **Requisitos:** Produção.
- **Decisões:** Logs estruturados; Sentry opcional se tempo.
- **Tarefas:**
  - [ ] Logging request_id
  - [ ] Monitor health uptime (UptimeRobot/Cloudflare)
- **Entregáveis:** Alertas básicos.
- **Donos:** Ambos
- **Dependências:** D06
- **Critério de pronto:** Alerta se `/up` cair.

### D49 — Security review checklist

- **Requisitos:** Semanas 1–7.
- **Decisões:** Admin interno **não** público (diferente do concorrente).
- **Tarefas:**
  - [ ] Checklist OWASP ASVS light
  - [ ] Secrets audit
  - [ ] Revisar headers segurança
- **Entregáveis:** Doc security notes.
- **Donos:** Ambos
- **Dependências:** Semana 7
- **Critério de pronto:** Sem secret no git; headers OK.

---

# Semana 8 — Beta e soft launch (D50–D56)

### D50 — Pluggy/Asaas/Meta produção

- **Requisitos:** KYCs aprovados.
- **Decisões:** Cutover sandbox→prod com feature flags.
- **Tarefas:**
  - [ ] Trocar keys produção
  - [ ] Retestar Connect 1 banco real (conta do operador)
  - [ ] Retestar cobrança Asaas R$ 5
  - [ ] WhatsApp número produção
- **Entregáveis:** Stack produção validada.
- **Donos:** Operador + Agente
- **Dependências:** KYCs
- **Critério de pronto:** Fluxos reais ok na conta do operador.

### D51 — Landing Inova Hub v1

- **Requisitos:** Domínio.
- **Decisões:** Marca Hub + Finova; sem claims falsos E2E; aviso se sem app lojas.
- **Tarefas:**
  - [ ] Landing com CTA trial
  - [ ] Links legais
  - [ ] Preços
- **Entregáveis:** Site público.
- **Donos:** Agente
- **Dependências:** D06, D42
- **Critério de pronto:** Visitante chega no register.

### D52 — Beta fechado (10 usuários)

- **Requisitos:** Produção estável.
- **Decisões:** Convite only; canal feedback WhatsApp/Notion.
- **Tarefas:**
  - [ ] Convidar 10 pessoas
  - [ ] Script onboarding
  - [ ] Board de bugs
- **Entregáveis:** Lista beta + feedback.
- **Donos:** Operador
- **Dependências:** D50–D51
- **Critério de pronto:** ≥5 usuários ativos no dia.

### D53 — Correções P0 do beta

- **Requisitos:** Feedback D52.
- **Decisões:** Só P0/P1 altos.
- **Tarefas:**
  - [ ] Patch bugs bloqueantes
  - [ ] Redeploy
- **Entregáveis:** Release patch.
- **Donos:** Agente
- **Dependências:** D52
- **Critério de pronto:** Nenhum P0 aberto.

### D54 — Eval final Finova (100 utterances)

- **Requisitos:** Intents P0.
- **Decisões:** Gate ≥85% overall.
- **Tarefas:**
  - [ ] Rodar eval set
  - [ ] Ajustar prompts
- **Entregáveis:** Relatório acurácia.
- **Donos:** Agente
- **Dependências:** Semanas 3–5
- **Critério de pronto:** ≥85% no set 100.

### D55 — Runbooks operação

- **Requisitos:** Produção.
- **Decisões:** Runbooks: incidente WA, Pluggy down, Asaas down, restore DB.
- **Tarefas:**
  - [ ] Escrever `docs/16-runbooks.md`
  - [ ] Contatos emergência
- **Entregáveis:** Runbooks.
- **Donos:** Ambos
- **Dependências:** D47–D48
- **Critério de pronto:** Operador consegue seguir restore sem Agente.

### D56 — Soft launch + retrospectiva MVP

- **Requisitos:** D50–D55.
- **Decisões:** Abrir trial público limitado ou manter waitlist.
- **Tarefas:**
  - [ ] Toggle trial público
  - [ ] Atualizar executive summary / roadmap
  - [ ] Lista pós-MVP: Drive, Meet, Stripe, B2B, verticais
- **Entregáveis:** MVP declarado pronto para soft launch; backlog P1.
- **Donos:** Ambos
- **Dependências:** Critérios de saída
- **Critério de pronto:** Checklist abaixo 100%.

---

## Critérios de saída do MVP (obrigatórios no D56)

1. [ ] Onboarding < 5 min  
2. [ ] Finova: gasto texto/áudio, consulta, agenda, tarefa, saldo/extrato OF  
3. [ ] Hub: lançamentos, conexões Pluggy, Google, billing Asaas  
4. [ ] Cancelar / exportar / excluir no Hub  
5. [ ] Conta compartilhada com papéis  
6. [ ] Testes IDOR verdes  
7. [ ] Termos/Privacidade alinhados  
8. [ ] Eval Finova ≥ 85%  

## Pós-MVP (não está em D01–D56)

- Stripe (internacional)  
- Drive semântico / R2 completo  
- Google Meet + atas  
- B2B benefícios  
- Verticais  
- Iniciador de pagamento / Pix saída  
