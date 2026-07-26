<?php

namespace App\Services\OpenFinance;

use App\Contracts\OpenFinance\OpenFinanceProvider;
use App\Models\OfAccount;
use App\Models\OfItem;
use App\Models\OfTransaction;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Carbon;

final class SyncsPluggyItem
{
    public function __construct(
        private OpenFinanceProvider $provider,
    ) {}

    /**
     * @param  array{itemId: string, event?: string, clientUserId?: ?string, createdTransactionsLink?: ?string, accountId?: ?string}  $payload
     */
    public function handle(array $payload): OfItem
    {
        $itemId = (string) $payload['itemId'];
        $clientUserId = $payload['clientUserId'] ?? null;
        $event = (string) ($payload['event'] ?? 'item/updated');

        $remote = $this->provider->getItem($itemId);
        $clientUserId = $clientUserId ?: $remote['client_user_id'];

        $orgId = $this->resolveOrganizationId($itemId, $clientUserId);
        if ($orgId === null) {
            throw new \RuntimeException('Unable to resolve organization for Pluggy item '.$itemId);
        }

        TenantContext::set($orgId);

        if ($event === 'item/deleted') {
            $item = OfItem::query()->withoutGlobalScopes()
                ->where('organization_id', $orgId)
                ->where('pluggy_item_id', $itemId)
                ->first();

            if ($item === null) {
                throw new \RuntimeException('Pluggy item not found for delete: '.$itemId);
            }

            $this->wipeLocalOfData($item);
            $item->update([
                'status' => OfItem::STATUS_DELETED,
                'consent_revoked_at' => $item->consent_revoked_at ?? now(),
            ]);

            return $item->fresh();
        }

        $userId = $this->resolveUserId($clientUserId);

        $item = OfItem::query()->updateOrCreate(
            [
                'organization_id' => $orgId,
                'pluggy_item_id' => $itemId,
            ],
            [
                'user_id' => $userId,
                'status' => $remote['status'] ?: OfItem::STATUS_UPDATED,
                'client_user_id' => $clientUserId,
                'connector_name' => $remote['connector_name'],
                'consent_at' => now(),
            ]
        );

        if (in_array($event, ['item/created', 'item/updated', 'item/login_succeeded', 'transactions/created', 'transactions/updated'], true)) {
            $this->syncAccountsAndTransactions($item, $payload);
        }

        return $item;
    }

    /**
     * @param  array{createdTransactionsLink?: ?string, accountId?: ?string}  $payload
     */
    private function syncAccountsAndTransactions(OfItem $item, array $payload): void
    {
        $accounts = $this->provider->listAccounts($item->pluggy_item_id);
        $accountMap = [];

        foreach ($accounts as $remoteAccount) {
            $account = OfAccount::query()->updateOrCreate(
                [
                    'organization_id' => $item->organization_id,
                    'pluggy_account_id' => $remoteAccount['id'],
                ],
                [
                    'of_item_id' => $item->id,
                    'name' => $remoteAccount['name'],
                    'type' => $remoteAccount['type'],
                    'subtype' => $remoteAccount['subtype'],
                    'number' => $remoteAccount['number'],
                    'currency' => $remoteAccount['currency'],
                    'balance_cents' => $remoteAccount['balance_cents'],
                    'synced_at' => now(),
                ]
            );
            $accountMap[$remoteAccount['id']] = $account;
        }

        $link = $payload['createdTransactionsLink'] ?? null;
        if (is_string($link) && $link !== '') {
            $this->persistTransactions($this->provider->listTransactionsFromLink($link), $accountMap, $item->organization_id);

            return;
        }

        $accountId = $payload['accountId'] ?? null;
        if (is_string($accountId) && isset($accountMap[$accountId])) {
            $this->persistTransactions(
                $this->provider->listTransactions($accountId),
                $accountMap,
                $item->organization_id
            );

            return;
        }

        foreach ($accountMap as $pluggyAccountId => $account) {
            $this->persistTransactions(
                $this->provider->listTransactions($pluggyAccountId),
                [$pluggyAccountId => $account],
                $item->organization_id
            );
        }
    }

    /**
     * @param  list<array{id: string, account_id: string, amount_cents: int, currency: string, type: string, description: ?string, category_suggested: ?string, occurred_at: string}>  $rows
     * @param  array<string, OfAccount>  $accountMap
     */
    private function persistTransactions(array $rows, array $accountMap, string $organizationId): void
    {
        foreach ($rows as $row) {
            $account = $accountMap[$row['account_id']] ?? null;
            if ($account === null) {
                continue;
            }

            $existing = OfTransaction::query()
                ->where('organization_id', $organizationId)
                ->where('pluggy_transaction_id', $row['id'])
                ->first();

            $attrs = [
                'of_account_id' => $account->id,
                'amount_cents' => $row['amount_cents'],
                'currency' => $row['currency'],
                'type' => $row['type'],
                'description' => $row['description'],
                'occurred_at' => Carbon::parse($row['occurred_at']),
            ];

            if ($existing === null) {
                $attrs['category_suggested'] = $row['category_suggested'];
            } elseif (! $existing->category_manual
                && ($existing->category_suggested === null || $existing->category_suggested === '')
                && filled($row['category_suggested'])) {
                $attrs['category_suggested'] = $row['category_suggested'];
            }

            OfTransaction::query()->updateOrCreate(
                [
                    'organization_id' => $organizationId,
                    'pluggy_transaction_id' => $row['id'],
                ],
                $attrs
            );
        }
    }

    private function wipeLocalOfData(OfItem $item): void
    {
        $accountIds = OfAccount::query()
            ->where('of_item_id', $item->id)
            ->pluck('id');

        if ($accountIds->isEmpty()) {
            return;
        }

        OfTransaction::query()->whereIn('of_account_id', $accountIds)->delete();
        OfAccount::query()->where('of_item_id', $item->id)->delete();
    }

    private function resolveOrganizationId(string $itemId, ?string $clientUserId): ?string
    {
        $existing = OfItem::query()->withoutGlobalScopes()
            ->where('pluggy_item_id', $itemId)
            ->value('organization_id');

        if (is_string($existing) && $existing !== '') {
            return $existing;
        }

        if ($clientUserId !== null && preg_match('/^org:([^:]+):user:/', $clientUserId, $m) === 1) {
            return $m[1];
        }

        return null;
    }

    private function resolveUserId(?string $clientUserId): ?int
    {
        if ($clientUserId === null || preg_match('/^org:[^:]+:user:(\d+)$/', $clientUserId, $m) !== 1) {
            return null;
        }

        return (int) $m[1];
    }
}
