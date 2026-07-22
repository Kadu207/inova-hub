# 22 — UI 100% responsiva (obrigatório)

**Status:** mandatório — landing + Inova Hub (painel)  
**Breakpoints canônicos (mobile-first):**

| Nome | Largura |
|------|---------|
| `xs` | &lt; 380px (phones estreitos) |
| `sm` | ≥ 380px |
| `md` | ≥ 768px |
| `lg` | ≥ 1024px |
| `xl` | ≥ 1280px |

---

## Princípios

1. **Mobile-first:** CSS/base para mobile; `min-width` para crescer.  
2. **Uma composição por viewport:** evitar “dashboard engessado” no mobile.  
3. **Touch-friendly:** alvos ≥ 44×44px; espaçamento adequado.  
4. **Sem scroll horizontal** de layout (exceto tabelas com scroll interno explícito).  
5. **Conteúdo crítico acima da dobra no mobile:** saldo/resumo + CTA Finova.  
6. **Imagens fluidas:** `max-width: 100%`; lazy-load.  
7. **Tipografia fluida:** `clamp()` para títulos; line-length legível (~45–75ch).  
8. **Formulários em coluna única** no `sm`↓.  
9. **Navegação:** bottom nav ou drawer no mobile; sidebar a partir de `lg`.  
10. **Modais:** full-screen ou bottom sheet no mobile; dialog centrado no desktop.

---

## Escopo de páginas

| Superfície | Responsivo? |
|------------|-------------|
| Landing Inova Hub | Sim — 100% |
| Login / register / onboarding | Sim |
| Dashboard | Sim |
| Lançamentos / filtros | Sim (tabela → cards no mobile) |
| Conexões OF / Google | Sim |
| Billing / cancel / export | Sim |
| Membros | Sim |
| Erros 4xx/5xx | Sim |

WhatsApp (Finova) não é web UI; mensagens devem ser curtas e legíveis no app Meta.

---

## Padrões de layout

### Mobile (`&lt; md`)

- Header compacto + menu hamburger ou bottom tabs  
- Cards empilhados  
- Gráficos altura fixa responsiva (não cortar labels)  
- Filtros em accordion / bottom sheet  

### Tablet (`md`–`lg`)

- 2 colunas onde fizer sentido  
- Sidebar colapsável  

### Desktop (`lg`+)

- Sidebar fixa + content  
- Tabelas densas com sticky header  
- Atalhos teclado opcionais (não únicos)  

---

## Componentes obrigatórios responsivos

| Componente | Comportamento |
|------------|---------------|
| Data table | Desktop: table · Mobile: lista de cards |
| Charts | `width: 100%`; legendas empilhadas no mobile |
| Forms | 1 coluna mobile; grid 2 cols desktop |
| CTA principal | Full width no mobile |
| Toasts | Não cobrir botões de ação |

---

## Acessibilidade (mínimo)

- Contraste WCAG AA  
- Foco visível  
- Labels em inputs  
- `lang="pt-BR"`  
- Não depender só de hover  
- Prefer `prefers-reduced-motion`  

---

## Testes de responsividade (DoD UI)

Antes de merge de tela:

- [ ] Chrome DevTools: 360×800, 390×844, 768×1024, 1280×800, 1440×900  
- [ ] Sem overflow-x no `body`  
- [ ] Orientação landscape phone OK (login/dashboard)  
- [ ] Teclado virtual não esconde CTA crítico (billing/OTP)  
- [ ] Lighthouse mobile Performance/A11y sem regressão grave  

Ferramenta futura: Playwright viewport matrix no CI (P1).

---

## Implementação (stack)

- CSS utility (Tailwind) **ou** CSS modules com tokens — mobile-first  
- Evitar larguras fixas em px para containers principais  
- `dvh` para altura de viewport em mobile  
- Imagens: WebP/AVIF quando possível  

## Anti-padrões (proibidos)

- Tabelas largas sem alternativa mobile  
- Modais que saem da tela no iOS  
- Texto cortado com `overflow: hidden` em títulos  
- Hover-only para ações essenciais  
- Viewport meta ausente (`width=device-width, initial-scale=1`)  
