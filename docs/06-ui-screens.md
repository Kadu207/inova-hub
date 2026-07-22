# 06 — Telas UI (concorrente + alvo)

## Meu Assessor — telas públicas / inferidas

| Tela | URL / local | Observação |
|------|-------------|------------|
| Landing | meuassessor.com | Seções: Finanças, Agenda, Open Finance, Projetos, Drive, Planos, FAQ |
| Termos | /pages/termos-de-uso | Hotmart cancel, foro BC/SC |
| Privacidade | /pages/politica-de-privacidade | WhatsApp + Google OAuth |
| Compra concluída | /pages/compra-concluida | CTA WhatsApp ativação |
| Login painel | app.meuassessor.com/login | E-mail/senha |
| Cadastro | app.meuassessor.com/cadastro | Pós-compra + OTP WhatsApp |
| Admin login | admin.meuassessor.com | Público — não explorar |
| Benefícios | beneficios.meuassessor.com | WP / hello-world |
| Agenda compartilhada | app.../agenda/compartilhada/{id} | Indexada publicamente |

### Painel autenticado (claims de produto — sem acesso)

Esperado após login: dashboard financeiro, categorias, metas, agenda, projetos/tarefas, drive, conexões bancárias, Google, membros compartilhados, exportação, perfil.

**Status:** aguardando snirf autenticado → `ui-inventory.json`.

## Inova Hub — mapa de telas MVP (P0)

| Rota | Tela | Papel |
|------|------|-------|
| `/` | Marketing Inova Hub | Público |
| `/login` | Login | Usuário |
| `/register` | Cadastro + trial | Usuário |
| `/onboarding` | Vincular Finova + 3 primeiros comandos | Usuário |
| `/app` | Home dashboard | Titular |
| `/app/transactions` | Lançamentos | Titular/membro |
| `/app/calendar` | Agenda | Titular/membro |
| `/app/tasks` | Tarefas | Titular/membro |
| `/app/connections` | Open Finance + Google | Titular |
| `/app/members` | Conta compartilhada | Titular |
| `/app/settings` | Preferências, segurança, MFA | Titular |
| `/app/billing` | Plano, cancelar, exportar, excluir | Titular |
| `/b2b` | Portal RH (fase B) | RH |

## Finova — “telas” conversacionais

Não há UI nativa: fluxos por mensagem (texto/áudio/arquivo).

Ver [12-brand-finova.md](12-brand-finova.md) para intents e tom de voz.
