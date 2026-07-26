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
}
