@extends('layouts.hub')

@section('title', 'Conexões bancárias — Inova Hub')

@section('content')
    <div class="topbar">
        <p class="brand" style="margin:0;">Bancos</p>
        <a class="btn btn-ghost" href="{{ route('hub.home') }}" style="width:auto;margin:0;padding:0 1rem;">Início</a>
    </div>

    <div class="card">
        <p class="sub" style="margin-top:0;">Open Finance (Pluggy) — somente leitura</p>
        <p>Conecte um banco sandbox para a Finova e o Hub consultarem saldo e extrato. Não pedimos senha bancária no Inova Hub; o consentimento fica no widget Pluggy.</p>

        @if (! $configured)
            <p class="errors" style="margin-top:1rem;">Pluggy não configurado. Defina <code>PLUGGY_CLIENT_ID</code> e <code>PLUGGY_CLIENT_SECRET</code>.</p>
        @else
            <button type="button" id="btn-connect-bank">Conectar banco</button>
            <p id="connect-status" class="sub" style="margin-top:0.75rem;" role="status" aria-live="polite"></p>
        @endif
    </div>

    <div class="card" style="margin-top:var(--space);">
        <p class="sub" style="margin-top:0;">Conexões desta organização</p>
        @forelse ($items as $item)
            <div class="tx-row" style="margin-top:0.75rem;">
                <div>
                    <p class="tx-desc">{{ $item->connector_name ?: 'Item Pluggy' }}</p>
                    <p class="sub" style="margin:0.25rem 0 0;">ID: {{ $item->pluggy_item_id }} · {{ $item->accounts_count }} conta(s)</p>
                </div>
                <p class="tx-amount">{{ $item->status }}</p>
            </div>
        @empty
            <p class="sub" style="margin:0;">Nenhuma conexão ainda. Use “Conectar banco” e escolha um conector de teste Pluggy.</p>
        @endforelse
    </div>
@endsection

@section('scripts')
@if ($configured)
<script src="https://cdn.pluggy.ai/pluggy-connect/latest/pluggy-connect.js"></script>
<script>
(() => {
  const btn = document.getElementById('btn-connect-bank');
  const statusEl = document.getElementById('connect-status');
  if (!btn) return;

  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const includeSandbox = @json($includeSandbox);

  function setStatus(msg) {
    if (statusEl) statusEl.textContent = msg;
  }

  btn.addEventListener('click', async () => {
    btn.disabled = true;
    setStatus('Gerando Connect Token…');

    try {
      const tokenRes = await fetch(@json(route('hub.connections.connect-token')), {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: '{}',
      });

      const tokenJson = await tokenRes.json();
      if (!tokenRes.ok || !tokenJson.accessToken) {
        throw new Error(tokenJson.message || 'Falha ao obter token');
      }

      setStatus('Abrindo widget Pluggy…');

      const pluggyConnect = new PluggyConnect({
        connectToken: tokenJson.accessToken,
        includeSandbox: includeSandbox,
        onSuccess: async (itemData) => {
          const itemId = itemData?.item?.id || itemData?.id;
          const connectorName = itemData?.item?.connector?.name
            || itemData?.connector?.name
            || null;
          const status = itemData?.item?.status || itemData?.status || 'CREATED';

          if (!itemId) {
            setStatus('Conexão ok, mas o itemId não veio no callback. Atualize a página.');
            btn.disabled = false;
            return;
          }

          setStatus('Salvando conexão…');

          const saveRes = await fetch(@json(route('hub.connections.items.store')), {
            method: 'POST',
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrf,
              'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
              item_id: itemId,
              status: status,
              connector_name: connectorName,
            }),
          });

          if (!saveRes.ok) {
            setStatus('Item criado na Pluggy, mas falhou ao salvar no Hub. Atualize e tente de novo.');
            btn.disabled = false;
            return;
          }

          setStatus('Banco conectado! Recarregando…');
          window.location.reload();
        },
        onError: (error) => {
          console.error(error);
          setStatus('Erro no widget Pluggy. Tente novamente.');
          btn.disabled = false;
        },
        onClose: () => {
          btn.disabled = false;
          if (statusEl && statusEl.textContent.includes('Abrindo')) {
            setStatus('Widget fechado.');
          }
        },
      });

      pluggyConnect.init();
    } catch (err) {
      console.error(err);
      setStatus(err.message || 'Falha ao conectar.');
      btn.disabled = false;
    }
  });
})();
</script>
@endif
@endsection
