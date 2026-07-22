# Constitution — Inova Hub / Finova

## Princípios

1. **Marca:** Inova Hub = plataforma; Finova = WhatsApp.
2. **Banco:** PostgreSQL 16 multi-tenant (`organization_id` + RLS).
3. **UI:** 100% responsiva (mobile-first) em landing e Hub.
4. **Segurança:** camadas L1–L11 (docs/21-security-layers.md).
5. **SDD:** Spec Kit / day-by-day antes de feature ampla.
6. **TDD:** Regra de negócio nasce em Pest (Red → Green); IDOR obrigatório.
7. **EDD:** Webhooks → Jobs idempotentes (Meta, Pluggy, Asaas).
8. **Squads:** A = build/QA; B = pós-lançamento (ops/suporte).
9. **CodeRabbit:** blockers de segurança/tenant/UI devem fechar antes do merge.
10. **Clean-room:** sem copiar código/assets do Meu Assessor.
11. **Compliance:** LGPD, consentimento OF, Asaas self-service cancel.
12. **DNS:** `inovahub.inovatitech.com.br` → VPS `128.140.77.31`.

## Stack default

Laravel + PostgreSQL + Redis + Horizon + Pluggy + Asaas + Meta WhatsApp Cloud API.
