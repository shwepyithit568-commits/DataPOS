<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Services\SyncClientService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SyncPushCommand extends Command
{
    protected $signature = 'sync:push
        {--store= : Store slug or ID; defaults to every store on this installation}
        {--limit= : Maximum number of queued records to push in this run}';

    protected $description = 'Push shop records captured locally (sales, debt collections) to the central installation';

    public function handle(SyncClientService $client): int
    {
        if (! config('sync.enabled')) {
            $this->warn('Replication is switched off (DATAPOS_SYNC_ENABLED=false) — nothing to push.');

            return self::SUCCESS;
        }

        $stores = $this->resolveStores();

        if ($stores->isEmpty()) {
            $this->error('No matching store found.');

            return self::FAILURE;
        }

        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $needsAttention = false;

        foreach ($stores as $store) {
            $result = $client->push($store, $limit);

            if (! $result['ok']) {
                $this->reportFailure($store, $result);

                // An unreachable central is a normal shop situation (the internet
                // is simply down) — the queue keeps everything. A bad credential
                // or missing configuration is not, and should page somebody.
                if (in_array($result['message'], ['offline', 'nothing_to_push'], true)) {
                    continue;
                }

                $needsAttention = true;
                continue;
            }

            $this->line(sprintf(
                '<info>%s</info>: pushed %d — %d synced, %d failed.',
                $store->name,
                $result['pushed'],
                $result['synced'],
                $result['failed']
            ));

            if ($result['failed'] > 0) {
                $needsAttention = true;
            }
        }

        return $needsAttention ? self::FAILURE : self::SUCCESS;
    }

    private function reportFailure(Store $store, array $result): void
    {
        $message = match ($result['message']) {
            'nothing_to_push' => 'nothing queued.',
            'offline'         => 'central unreachable — the queue is safe and will be retried.',
            'unauthorized'    => 'the central rejected the sync key (regenerate it on the central and copy the new value into .env).',
            'not_configured'  => 'incomplete configuration: ' . implode(', ', $result['problems'] ?? []),
            default           => $result['message'],
        };

        $this->line(sprintf('<comment>%s</comment>: %s', $store->name, $message));
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
