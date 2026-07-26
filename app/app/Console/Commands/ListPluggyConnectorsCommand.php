<?php

namespace App\Console\Commands;

use App\Contracts\OpenFinance\OpenFinanceProvider;
use Illuminate\Console\Command;
use Throwable;

final class ListPluggyConnectorsCommand extends Command
{
    protected $signature = 'pluggy:connectors {--limit=15 : Max connectors to print}';

    protected $description = 'Authenticate with Pluggy sandbox and list BR connectors (D22 smoke)';

    public function handle(OpenFinanceProvider $provider): int
    {
        try {
            $connectors = $provider->listConnectors();
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $limit = max(1, (int) $this->option('limit'));
        $this->info(sprintf('Pluggy OK — %d connectors (showing %d)', count($connectors), min($limit, count($connectors))));

        $rows = array_slice($connectors, 0, $limit);
        $this->table(
            ['id', 'name', 'type', 'country'],
            array_map(fn (array $c) => [$c['id'], $c['name'], $c['type'], $c['country']], $rows)
        );

        return self::SUCCESS;
    }
}
