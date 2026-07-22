# 07 — Riscos legais e privacidade

## Matriz (concorrente → lição para Inova Hub)

| Severidade | Achado | Impacto | Resposta Inova Hub / Finova |
|------------|--------|---------|------------------------------|
| Alta | App “Meu Assessor IA” AppStars ≠ site | Confusão, phishing, reclamações | Marca distinta; aviso “não temos app nas lojas” se aplicável |
| Alta | Razão social inconsistente (Tittanium / JMM / Meu Assessor) | Chargeback, LGPD, contrato | Uma entidade + DPO + controlador explícito |
| Alta | Escopos Google contraditórios na política | Consentimento inválido | Escopos mínimos; tela pré-OAuth; log versionado |
| Alta | “E2E” usado como sinônimo de TLS | Publicidade enganosa | Texto: criptografia em trânsito + em repouso + quem processa |
| Alta | Dados financeiros + IA + WhatsApp | Alto impacto em breach | Minimização, retenção, DPA, segregação |
| Média/Alta | Admin público na internet | Força bruta | Admin só VPN/IP allowlist + MFA |
| Média | WordPress benefícios abandonado | Superfície extra | Não deixar ambientes mortos |
| Média | Agendamentos indexados | Vazamento comercial | noindex + links opacos + expiração |
| Média | Cancelamento só Hotmart | Reclamações | Cancelamento + export + delete no Hub |

## Dados tratados (política concorrente)

- Nome, WhatsApp, ID conta  
- Transações e compromissos enviados  
- Uso do painel  
- Dados Google autorizados  

## O que NÃO fazer no snirf / produto

- Pentest sem autorização  
- Login em admin de terceiros  
- Engenharia reversa do APK AppStars como se fosse oficial  
- Copiar copy/claims enganosos  

## Checklist LGPD Inova Hub (pré-launch)

- [ ] Política alinhada aos escopos reais  
- [ ] Registro de atividades de tratamento  
- [ ] Base legal por finalidade  
- [ ] Canal do titular (export/delete) no produto  
- [ ] DPA com Meta, LLM, Open Finance, storage  
- [ ] Retenção e anonimização documentadas  
- [ ] MFA para staff; auditoria de acessos administrativos  
