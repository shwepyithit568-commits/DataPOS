<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Services\SyncClientService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SyncPullCommand extends Command
{
    protected $signature = 'sync:pull
        {--store= : Store slug or ID; defaults to every store on this installation}';

    protected $description = 'Pull the central installation\'s catalog delta (products, prices, categories, customers) — read-only';

    public function handle(SyncClientService $client): int
    {
        if (! config('sync.enabled')) {
            $this->warn('Replication is switched off (DATAPOS_SYNC_ENABLED=false) — nothing to pull.');

            return self::SUCCESS;
        }

        $stores = $this->resolveStores();

        if ($stores->isEmpty()) {
            $this->error('No matching store found.');

            return self::FAILURE;
        }

        $needsAttention = false;

        foreach ($stores as $store) {
            $result = $client->pull($store);

            if (! $result['ok']) {
                $this->line(sprintf('<comment>%s</comment>: %s', $store->name, $result['message']));

                if (! in_array($result['message'], ['offline'], true)) {
                    $needsAttention = true;
                }

                continue;
            }

            $delta = $result['delta'];

            $this->line(sprintf(
                '<info>%s</info>: %d products, %d variants, %d categories, %d customers (%s).',
                $store->name,
                count($delta['products'] ?? []),
                count($delta['variants'] ?? []),
                count($delta['categories'] ?? []),
                count($delta['customers'] ?? []),
                $result['message']
            ));
        }

        return $needsAttention ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return Collection<int, Store>
     */
    private function resolveStores(): Collection
    {
        $identifier = $this->option('store');

        if ($identifier === null) {
            return Store::query()->orderBy('id')->get();
        }

        $store = is_numeric($identifier)
            ? Store::find($identifier)
            : Store::where('slug', $identifier)->first();

        return $store ? collect([$store]) : collect();
    }
}
