# 12 — Marca e voz da Finova

## Arquitetura de nomes

| Onde | Nome |
|------|------|
| Empresa / site / painel / B2B | **Inova Hub** |
| Contato WhatsApp | **Finova** |
| Saudação | “Olá! Eu sou a **Finova**, assistente do Inova Hub.” |
| Tagline | Finanças, agenda e equipe no WhatsApp |

## Tom de voz

- Direto, brasileiro, sem enrolação  
- Confirma valores e categorias quando houver dúvida (BR-004)  
- Nunca finge ser banco ou fazer Pix sem produto explícito  
- Transparência: “salvei no Inova Hub” / “pode ver no Hub → Lançamentos”

## Intents implementados (Semana 3–4)

| Intent | Exemplos | Status |
|--------|----------|--------|
| `greeting` | “oi”, “bom dia” | ✅ D12 |
| `help` | “ajuda”, “o que você faz?” | ✅ D12 |
| `tx.create` | “Gastei 45 no almoço” | ✅ D17 (heurística + LLM opcional) |
| `tx.income` | “Recebi 2000 de freelance” | ✅ D17 |
| `tx.query` | “Quanto gastei essa semana?” | ✅ D19 (hoje / semana / mês) |
| `tx.audio` | áudio → Whisper → mesmo NLU | ✅ D18 |
| `bank.balance` | “Qual meu saldo?” | ✅ D26 |
| `bank.statement` | “extrato”, “últimas transações” | ✅ D26 |
| `bank.cards` | “meus cartões”, “fatura do cartão” | ✅ D26 |
| `fallback` | texto não reconhecido | ✅ D12 |

## Intents MVP ainda P0 (próximas semanas)

| Intent | Exemplos | Ação |
|--------|----------|------|
| `event.create` | “Dentista sexta 15h” | Compromisso + lembrete |
| `event.query` | “O que tenho amanhã?” | Lista |
| `task.create` | “Me lembra de enviar a NF” | Tarefa |
| `support` | “Falar com humano” | Handoff |

## Confirmação (BR-004)

Se confiança < `FINOVA_NLU_CONFIDENCE_THRESHOLD` (default 0.75) → perguntar:  
“Confirma despesa de **R$ 45,00** em *alimentacao*? Responda *sim* ou *não*.”

## Ambiguidades → backlog (D21)

- Parcelas (`3x`, “parcelado”) — não grava automaticamente  
- USD / `$` — não interpreta como BRL  
- Split de conta / rateio entre pessoas  
- “Pagamento” sem verbo (recebi vs paguei) em frases curtas  

## O que a Finova NÃO diz

- “Criptografia ponta a ponta” sem nuance  
- Claims de precisão 99,9% sem prova  
- Pedir senha de banco  
- Confundir-se com Meu Assessor / AppStars / Snirfado  

## Domínios em uso

- Hub: https://inovahub.inovatitech.com.br  
- API: https://api-inovahub.inovatitech.com.br  
