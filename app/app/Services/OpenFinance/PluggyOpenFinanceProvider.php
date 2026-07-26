<?php

namespace App\Services\OpenFinance;

use App\Contracts\OpenFinance\OpenFinanceProvider;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class PluggyOpenFinanceProvider implements OpenFinanceProvider
{
    public function authenticate(): string
    {
        $clientId = (string) config('services.pluggy.client_id');
        $clientSecret = (string) config('services.pluggy.client_secret');
        $baseUrl = rtrim((string) config('services.pluggy.base_url'), '/');

        if ($clientId === '' || $clientSecret === '') {
            throw new RuntimeException('Pluggy requires PLUGGY_CLIENT_ID and PLUGGY_CLIENT_SECRET.');
        }

        try {
            $response = Http::baseUrl($baseUrl)
                ->acceptJson()
                ->timeout(20)
                ->post('/auth', [
                    'clientId' => $clientId,
                    'clientSecret' => $clientSecret,
                ])
                ->throw();
        } catch (RequestException $e) {
            throw new RuntimeException('Pluggy auth failed: '.$e->getMessage(), previous: $e);
        }

        $apiKey = (string) ($response->json('apiKey') ?? '');
        if ($apiKey === '') {
            throw new RuntimeException('Pluggy auth response missing apiKey.');
        }

        return $apiKey;
    }

    public function listConnectors(?string $apiKey = null): array
    {
        $apiKey ??= $this->authenticate();
        $baseUrl = rtrim((string) config('services.pluggy.base_url'), '/');

        try {
            $response = Http::baseUrl($baseUrl)
                ->withHeaders(['X-API-KEY' => $apiKey])
                ->acceptJson()
                ->timeout(30)
                ->get('/connectors', [
                    'countries' => 'BR',
                ])
                ->throw();
        } catch (RequestException $e) {
            throw new RuntimeException('Pluggy connectors failed: '.$e->getMessage(), previous: $e);
        }

        $results = $response->json('results');
        if (! is_array($results)) {
            throw new RuntimeException('Pluggy connectors response missing results.');
        }

        $connectors = [];
        foreach ($results as $row) {
            if (! is_array($row) || ! isset($row['id'], $row['name'])) {
                continue;
            }

            $connectors[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'type' => (string) ($row['type'] ?? 'PERSONAL_BANK'),
                'country' => (string) ($row['country'] ?? 'BR'),
                'primary_color' => isset($row['primaryColor']) ? (string) $row['primaryColor'] : null,
            ];
        }

        return $connectors;
    }

    public function createConnectToken(array $options = []): string
    {
        $apiKey = $this->authenticate();
        $baseUrl = rtrim((string) config('services.pluggy.base_url'), '/');

        $payload = [];
        if ($options !== []) {
            $payload['options'] = $options;
        }

        try {
            $response = Http::baseUrl($baseUrl)
                ->withHeaders(['X-API-KEY' => $apiKey])
                ->acceptJson()
                ->timeout(20)
                ->post('/connect_token', $payload)
                ->throw();
        } catch (RequestException $e) {
            throw new RuntimeException('Pluggy connect_token failed: '.$e->getMessage(), previous: $e);
        }

        $token = (string) ($response->json('accessToken') ?? '');
        if ($token === '') {
            throw new RuntimeException('Pluggy connect_token response missing accessToken.');
        }

        return $token;
    }

    public function getItem(string $itemId): array
    {
        $apiKey = $this->authenticate();
        $baseUrl = rtrim((string) config('services.pluggy.base_url'), '/');

        try {
            $response = Http::baseUrl($baseUrl)
                ->withHeaders(['X-API-KEY' => $apiKey])
                ->acceptJson()
                ->timeout(20)
                ->get('/items/'.$itemId)
                ->throw();
        } catch (RequestException $e) {
            throw new RuntimeException('Pluggy get item failed: '.$e->getMessage(), previous: $e);
        }

        $data = $response->json();
        if (! is_array($data) || empty($data['id'])) {
            throw new RuntimeException('Pluggy item response invalid.');
        }

        return [
            'id' => (string) $data['id'],
            'status' => (string) ($data['status'] ?? 'UPDATED'),
            'connector_name' => isset($data['connector']['name']) ? (string) $data['connector']['name'] : null,
            'client_user_id' => isset($data['clientUserId']) ? (string) $data['clientUserId'] : null,
        ];
    }

    public function listAccounts(string $itemId): array
    {
        $apiKey = $this->authenticate();
        $baseUrl = rtrim((string) config('services.pluggy.base_url'), '/');

        try {
            $response = Http::baseUrl($baseUrl)
                ->withHeaders(['X-API-KEY' => $apiKey])
                ->acceptJson()
                ->timeout(30)
                ->get('/accounts', ['itemId' => $itemId])
                ->throw();
        } catch (RequestException $e) {
            throw new RuntimeException('Pluggy accounts failed: '.$e->getMessage(), previous: $e);
        }

        $results = $response->json('results');
        if (! is_array($results)) {
            throw new RuntimeException('Pluggy accounts response missing results.');
        }

        $accounts = [];
        foreach ($results as $row) {
            if (! is_array($row) || empty($row['id'])) {
                continue;
            }

            $accounts[] = [
                'id' => (string) $row['id'],
                'name' => isset($row['name']) ? (string) $row['name'] : null,
                'type' => isset($row['type']) ? (string) $row['type'] : null,
                'subtype' => isset($row['subtype']) ? (string) $row['subtype'] : null,
                'number' => isset($row['number']) ? (string) $row['number'] : null,
                'currency' => (string) ($row['currencyCode'] ?? 'BRL'),
                'balance_cents' => $this->toCents($row['balance'] ?? 0),
            ];
        }

        return $accounts;
    }

    public function listTransactions(string $accountId, ?string $createdAtFrom = null): array
    {
        $query = ['accountId' => $accountId, 'pageSize' => 100];
        if ($createdAtFrom !== null && $createdAtFrom !== '') {
            $query['createdAtFrom'] = $createdAtFrom;
        }

        $apiKey = $this->authenticate();
        $baseUrl = rtrim((string) config('services.pluggy.base_url'), '/');

        try {
            $response = Http::baseUrl($baseUrl)
                ->withHeaders(['X-API-KEY' => $apiKey])
                ->acceptJson()
                ->timeout(45)
                ->get('/transactions', $query)
                ->throw();
        } catch (RequestException $e) {
            throw new RuntimeException('Pluggy transactions failed: '.$e->getMessage(), previous: $e);
        }

        return $this->mapTransactions($response->json('results'));
    }

    public function listTransactionsFromLink(string $url): array
    {
        $apiKey = $this->authenticate();

        try {
            $response = Http::withHeaders(['X-API-KEY' => $apiKey])
                ->acceptJson()
                ->timeout(45)
                ->get($url)
                ->throw();
        } catch (RequestException $e) {
            throw new RuntimeException('Pluggy transactions link failed: '.$e->getMessage(), previous: $e);
        }

        return $this->mapTransactions($response->json('results'));
    }

    /**
     * @param  mixed  $results
     * @return list<array{id: string, account_id: string, amount_cents: int, currency: string, type: string, description: ?string, category_suggested: ?string, occurred_at: string}>
     */
    private function mapTransactions(mixed $results): array
    {
        if (! is_array($results)) {
            return [];
        }

        $out = [];
        foreach ($results as $row) {
            if (! is_array($row) || empty($row['id']) || empty($row['accountId'])) {
                continue;
            }

            $amount = (float) ($row['amount'] ?? 0);
            $type = $amount < 0 ? 'expense' : 'income';

            $out[] = [
                'id' => (string) $row['id'],
                'account_id' => (string) $row['accountId'],
                'amount_cents' => abs($this->toCents($amount)),
                'currency' => (string) ($row['currencyCode'] ?? 'BRL'),
                'type' => $type,
                'description' => isset($row['description']) ? (string) $row['description'] : null,
                'category_suggested' => isset($row['category']) ? (string) $row['category'] : null,
                'occurred_at' => (string) ($row['date'] ?? now()->toIso8601String()),
            ];
        }

        return $out;
    }

    private function toCents(mixed $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
