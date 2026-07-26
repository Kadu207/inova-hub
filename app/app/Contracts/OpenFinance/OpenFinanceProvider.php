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
}
