@extends('layouts.hub')

@section('title', 'Conexões bancárias — Inova Hub')

@section('content')
    <div class="topbar">
        <p class="brand" style="margin:0;">Bancos</p>
        <a class="btn btn-ghost" href="{{ route('hub.home') }}" style="width:auto;margin:0;padding:0 1rem;">Início</a>
    </div>

    @if (session('status'))
        <p class="sub" role="status" style="margin:0 0 var(--space);">{{ session('status') }}</p>
    @endif

    <div class="card">
        <p class="sub" style="margin-top:0;">Open Finance (Pluggy) — somente leitura</p>
        <p>Conecte um banco sandbox para ver saldos e extratos. Leitura apenas — sem Pix/TED (BR-005).</p>
        <p class="sub" style="margin:0.75rem 0 0;">
            Consentimento versão <strong>{{ $consentVersion }}</strong> —
            <a href="{{ route('legal.open-finance') }}" target="_blank" rel="noopener">ler termos OF</a>
            ·
            <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener">privacidade</a>
        </p>

        <div class="totals" style="margin-top:1rem;">
            <div>
                <p class="sub" style="margin:0;">Saldo total OF</p>
                <p class="total-value {{ $totalBalanceCents >= 0 ? 'income' : 'expense' }}">
                    R$ {{ number_format($totalBalanceCents / 100, 2, ',', '.') }}
                </p>
            </div>
            <div>
                <p class="sub" style="margin:0;">Conexões</p>
                <p class="total-value">{{ $items->count() }}</p>
            </div>
            <div>
                <p class="sub" style="margin:0;">Contas</p>
                <p class="total-value">{{ $items->sum('accounts_count') }}</p>
            </div>
        </div>

        @if (! $configured)
            <p class="errors" style="margin-top:1rem;">Pluggy não configurado. Defina <code>PLUGGY_CLIENT_ID</code> e <code>PLUGGY_CLIENT_SECRET</code>.</p>
        @else
            <label style="display:flex; gap:0.65rem; align-items:flex-start; margin-top:1rem; cursor:pointer;">
                <input type="checkbox" id="of-consent" style="width:auto; min-height:auto; margin-top:0.2rem;">
                <span class="sub" style="margin:0;">Li e aceito o consentimento Open Finance (versão {{ $consentVersion }}). Posso revogar a qualquer momento.</span>
            </label>
            <button type="button" id="btn-connect-bank" disabled>Conectar banco</button>
            <p id="connect-status" class="sub" style="margin-top:0.75rem;" role="status" aria-live="polite"></p>
        @endif
    </div>

    @forelse ($items as $item)
        <div class="card" style="margin-top:var(--space);">
            <div class="tx-row">
                <div>
                    <p class="tx-desc">{{ $item->connector_name ?: 'Item Pluggy' }}</p>
                    <p class="sub" style="margin:0.25rem 0 0;">{{ $item->status }} · {{ $item->accounts_count }} conta(s)</p>
                </div>
                <div style="display:flex; gap:0.5rem; flex-wrap:wrap; justify-content:flex-end;">
                    <form method="post" action="{{ route('hub.connections.sync', $item) }}">
                        @csrf
                        <button type="submit" class="btn-ghost" style="width:auto;margin:0;padding:0 1rem;">Sincronizar</button>
                    </form>
                    <form method="post" action="{{ route('hub.connections.revoke', $item) }}"
                          onsubmit="return confirm('Revogar esta conexão? Saldos e extratos desta conexão serão apagados.');">
                        @csrf
                        <button type="submit" class="btn-ghost" style="width:auto;margin:0;padding:0 1rem;color:#b42318;">Revogar</button>
                    </form>
                </div>
            </div>

            @forelse ($item->accounts as $account)
                <a href="{{ route('hub.connections.accounts.show', $account) }}" class="tx-row" style="margin-top:0.85rem; text-decoration:none; color:inherit; display:flex;">
                    <div>
                        <p class="tx-desc">{{ $account->name ?: ($account->type ?: 'Conta') }}</p>
                        <p class="sub" style="margin:0.25rem 0 0;">
                            {{ $account->subtype ?: $account->type ?: '—' }}
                            @if ($account->number)
                                · {{ $account->number }}
                            @endif
                        </p>
                    </div>
                    <p class="tx-amount {{ $account->balance_cents >= 0 ? 'income' : 'expense' }}">
                        R$ {{ number_format($account->balance_cents / 100, 2, ',', '.') }}
                    </p>
                </a>
            @empty
                <p class="sub" style="margin:0.85rem 0 0;">Ainda sem contas. Clique em Sincronizar ou aguarde o webhook Pluggy.</p>
            @endforelse
        </div>
    @empty
        <div class="card" style="margin-top:var(--space);">
            <p class="sub" style="margin:0;">Nenhuma conexão ainda. Use “Conectar banco” e escolha um conector de teste Pluggy.</p>
        </div>
    @endforelse
@endsection

@section('scripts')
@if ($configured)
<script src="https://cdn.pluggy.ai/pluggy-connect/latest/pluggy-connect.js"></script>
<script>
(() => {
  const btn = document.getElementById('btn-connect-bank');
  const consent = document.getElementById('of-consent');
  const statusEl = document.getElementById('connect-status');
  if (!btn || !consent) return;

  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const includeSandbox = @json($includeSandbox);
  const consentVersion = @json($consentVersion);

  function setStatus(msg) {
    if (statusEl) statusEl.textContent = msg;
  }

  function syncConsentGate() {
    btn.disabled = !consent.checked;
  }
  consent.addEventListener('change', syncConsentGate);
  syncConsentGate();

  btn.addEventListener('click', async () => {
    if (!consent.checked) {
      setStatus('Aceite o consentimento Open Finance para continuar.');
      return;
    }

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
            syncConsentGate();
            return;
          }

          setStatus('Salvando e sincronizando…');

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
              consent_accepted: true,
              consent_version: consentVersion,
            }),
          });

          if (!saveRes.ok) {
            setStatus('Item criado na Pluggy, mas falhou ao salvar no Hub. Atualize e tente de novo.');
            syncConsentGate();
            return;
          }

          setStatus('Banco conectado! Recarregando…');
          window.location.reload();
        },
        onError: (error) => {
          console.error(error);
          setStatus('Erro no widget Pluggy. Tente novamente.');
          syncConsentGate();
        },
        onClose: () => {
          syncConsentGate();
          if (statusEl && statusEl.textContent.includes('Abrindo')) {
            setStatus('Widget fechado.');
          }
        },
      });

      pluggyConnect.init();
    } catch (err) {
      console.error(err);
      setStatus(err.message || 'Falha ao conectar.');
      syncConsentGate();
    }
  });
})();
</script>
@endif
@endsection
