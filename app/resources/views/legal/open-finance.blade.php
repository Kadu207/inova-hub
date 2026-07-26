<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $consentTitle }} — Inova Hub</title>
    <style>
        :root { --bg:#0f1419; --text:#eef2f6; --muted:#9aa8b5; --accent:#2bb673; }
        body { margin:0; font-family:"Segoe UI",system-ui,sans-serif; background:var(--bg); color:var(--text); }
        main { width:min(100% - 2rem, 40rem); margin:0 auto; padding:2rem 0 3rem; line-height:1.55; }
        h1 { font-size:clamp(1.4rem,4vw,1.85rem); letter-spacing:-0.02em; }
        h2 { font-size:1.1rem; margin-top:1.75rem; }
        p, li { color:var(--muted); }
        a { color:var(--accent); }
        .meta { font-size:0.9rem; color:var(--muted); }
    </style>
</head>
<body>
<main>
    <p class="meta"><a href="{{ url('/') }}">Inova Hub</a> · rascunho jurídico MVP</p>
    <h1>{{ $consentTitle }}</h1>
    <p class="meta">Versão <strong style="color:var(--text)">{{ $consentVersion }}</strong> · vigente para conexões bancárias via Pluggy</p>

    <h2>1. O que você autoriza</h2>
    <p>Ao conectar uma instituição financeira no Inova Hub, você autoriza a Inova TI Tech (controladora) a, por meio da Pluggy (operadora de Open Finance), <strong style="color:var(--text)">consultar em modo somente leitura</strong> saldos, extratos, cartões e metadados de contas vinculadas ao seu consentimento.</p>

    <h2>2. O que NÃO autorizamos</h2>
    <ul>
        <li>Iniciar Pix, TED, DOC ou qualquer pagamento</li>
        <li>Transferir valores ou alterar limites</li>
        <li>Armazenar senha bancária (credenciais ficam no fluxo Pluggy/instituição)</li>
    </ul>

    <h2>3. Finalidades</h2>
    <ul>
        <li>Exibir saldos e extratos no painel Inova Hub</li>
        <li>Responder consultas bancárias no WhatsApp (Finova), quando o número estiver vinculado</li>
        <li>Sugerir categorias de gastos (editáveis por você)</li>
    </ul>

    <h2>4. Dados armazenados</h2>
    <p>Guardamos identificadores Pluggy do item/contas/transações, saldos, descrições, datas, categorias sugeridas e o registro de consentimento (<code>consent_version</code>, <code>consent_at</code>). Você pode <strong style="color:var(--text)">revogar</strong> a qualquer momento em Bancos → Revogar; apagamos os dados OF da conexão e registramos <code>consent_revoked_at</code>.</p>

    <h2>5. Compartilhamento</h2>
    <p>Pluggy e a instituição financeira processam o consentimento Open Finance. Provedores de infraestrutura (hospedagem, fila, e-mail) podem processar dados sob contrato. Não vendemos dados bancários.</p>

    <h2>6. Titular</h2>
    <p>Para exercer direitos LGPD (acesso, correção, exclusão, portabilidade), use o canal do titular indicado na Política de Privacidade (rascunho). Exportação/exclusão self-service completa chega nas semanas seguintes do MVP.</p>

    <p class="meta" style="margin-top:2rem;">Documento rascunho — não substitui revisão jurídica final. Ver também <a href="{{ route('legal.privacy') }}">Privacidade</a>.</p>
</main>
</body>
</html>
