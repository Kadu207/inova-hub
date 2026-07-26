<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Política de Privacidade (rascunho) — Inova Hub</title>
    <style>
        :root { --bg:#0f1419; --text:#eef2f6; --muted:#9aa8b5; --accent:#2bb673; }
        body { margin:0; font-family:"Segoe UI",system-ui,sans-serif; background:var(--bg); color:var(--text); }
        main { width:min(100% - 2rem, 40rem); margin:0 auto; padding:2rem 0 3rem; line-height:1.55; }
        h1 { font-size:clamp(1.4rem,4vw,1.85rem); }
        h2 { font-size:1.1rem; margin-top:1.75rem; }
        p, li { color:var(--muted); }
        a { color:var(--accent); }
        .meta { font-size:0.9rem; color:var(--muted); }
    </style>
</head>
<body>
<main>
    <p class="meta"><a href="{{ url('/') }}">Inova Hub</a> · rascunho MVP</p>
    <h1>Política de Privacidade (rascunho)</h1>
    <p class="meta">Inova Hub (painel) + Finova (WhatsApp). Controladora: Inova TI Tech (razão social/CNPJ a confirmar no go-live).</p>

    <h2>1. Dados que tratamos</h2>
    <ul>
        <li>Conta: nome, e-mail, organização</li>
        <li>Finanças manuais: lançamentos e categorias</li>
        <li>WhatsApp: número vinculado, mensagens necessárias ao serviço</li>
        <li>Open Finance: saldos/extratos via Pluggy (consentimento <strong style="color:var(--text)">{{ $consentVersion }}</strong>)</li>
        <li>Pagamentos (futuro Asaas): status de assinatura</li>
        <li>IA: textos/áudios enviados para interpretação de lançamentos</li>
    </ul>

    <h2>2. Bases e finalidades</h2>
    <p>Execução de contrato / legítimo interesse para operar o produto; consentimento específico para Open Finance e para canais onde a lei exigir.</p>

    <h2>3. Open Finance</h2>
    <p>Detalhes, finalidades e revogação: <a href="{{ route('legal.open-finance') }}">Consentimento Open Finance</a>. Somente leitura (BR-005). Revogação apaga dados OF do item (BR-006).</p>

    <h2>4. Retenção</h2>
    <p>Dados de conta enquanto a organização existir. Dados OF até revogação ou exclusão da conta. Logs de consentimento mantidos para comprovação.</p>

    <h2>5. Contato do titular</h2>
    <p>E-mail DPO / titular: a definir antes do soft launch (ver checklist legal em docs).</p>

    <p class="meta" style="margin-top:2rem;">Rascunho — revisão jurídica obrigatória antes de marketing pago / produção bancária.</p>
</main>
</body>
</html>
