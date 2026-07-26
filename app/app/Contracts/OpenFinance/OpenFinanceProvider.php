<?php

namespace App\Contracts\OpenFinance;

/**
 * Read-only Open Finance surface (BR-005). Payment initiation is intentionally absent.
 */
interface OpenFinanceProvider
{
    /**
     * Exchange client credentials for a short-lived API key.
     */
    public function authenticate(): string;

    /**
     * List available bank connectors from the provider sandbox/production catalog.
     *
     * @return list<array{id: int, name: string, type: string, country: string, primary_color: ?string}>
     */
    public function listConnectors(?string $apiKey = null): array;

    /**
     * Create a short-lived Connect Token for the Pluggy Connect widget.
     *
     * @param  array{clientUserId?: string, webhookUrl?: string, oauthRedirectUrl?: string, avoidDuplicates?: bool}  $options
     */
    public function createConnectToken(array $options = []): string;

    /**
     * @return array{id: string, status: string, connector_name: ?string, client_user_id: ?string}
     */
    public function getItem(string $itemId): array;

    /**
     * @return list<array{id: string, name: ?string, type: ?string, subtype: ?string, number: ?string, currency: string, balance_cents: int}>
     */
    public function listAccounts(string $itemId): array;

    /**
     * @return list<array{id: string, account_id: string, amount_cents: int, currency: string, type: string, description: ?string, category_suggested: ?string, occurred_at: string}>
     */
    public function listTransactions(string $accountId, ?string $createdAtFrom = null): array;

    /**
     * Fetch transactions from a Pluggy pagination/createdTransactionsLink URL.
     *
     * @return list<array{id: string, account_id: string, amount_cents: int, currency: string, type: string, description: ?string, category_suggested: ?string, occurred_at: string}>
     */
    public function listTransactionsFromLink(string $url): array;

    /**
     * Delete a Pluggy item (revokes consent at the provider). Local wipe is caller's responsibility (BR-006).
     */
    public function deleteItem(string $itemId): void;
}
