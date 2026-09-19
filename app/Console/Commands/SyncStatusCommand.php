<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Models\SyncOutboxRecord;
use App\Services\OfflineSyncService;
use App\Services\SyncClientService;
use App\Services\SyncOutboxWriter;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SyncStatusCommand extends Command
{
    protected $signature = 'sync:status
        {--store= : Store slug or ID; defaults to every store on this installation}
        {--offline : Skip the reachability probe (useful when the internet is known to be down)}';

    protected $description = 'Show how this installation replicates to the central one, and whether the queue is draining';

    public function handle(SyncOutboxWriter $writer, OfflineSyncService $sync, SyncClientService $client): int
    {
        $this->renderConfiguration($writer);

        $stores = $this->resolveStores();

        if ($stores->isEmpty()) {
            $this->error('No matching store found.');

            return self::FAILURE;
        }

        foreach ($stores as $store) {
            $this->newLine();
            $this->line("<info>{$store->name}</info> ({$store->slug})");

            $health = $sync->getSyncHealth($store);
            $oldest = SyncOutboxRecord::query()
                ->where('store_id', $store->id)
                ->whereIn('status', ['pending', 'failed'])
                ->orderBy('created_offline_at')
                ->first();

            $this->table(
                ['Queue', 'Records'],
                [
                    ['Waiting to be pushed', $health['pending_count']],
                    ['Failed (needs a look)', $health['failed_count']],
                    ['Already replicated', $health['synced_count']],
                    ['Oldest waiting record', $oldest
                        ? $oldest->created_offline_at->diffForHumans()
                        : '—'],
                    ['Last successful push', $health['last_synced_at'] ?? 'never'],
                ]
            );

            if (! $this->option('offline') && $writer->ready()) {
                $ping = $client->ping($store);

                $this->line(match ($ping['message']) {
                    'online'      => '  Central: <info>reachable</info>',
                    'offline'     => '  Central: <comment>unreachable (the shop keeps selling; the queue waits)</comment>',
                    'unauthorized' => '  Central: <error>sync key rejected</error>',
                    default       => '  Central: <error>' . $ping['message'] . '</error>',
                });
            }
        }

        return self::SUCCESS;
    }

    private function renderConfiguration(SyncOutboxWriter $writer): void
    {
        $problems = $writer->configProblems();
        $key = (string) config('sync.api_key');

        $this->table(
            ['Setting', 'Value'],
            [
                ['Replication', config('sync.enabled') ? 'on' : 'off (DATAPOS_SYNC_ENABLED)'],
                ['Role', (string) config('sync.role')],
                ['Central URL', (string) config('sync.central_url') ?: '—'],
                ['Store slug', (string) config('sync.store_slug') ?: '—'],
                ['Sync key', $key !== '' ? '••••' . substr($key, -4) : '— not set'],
                ['Device name', $writer->deviceId()],
            ]
        );

        if ($problems !== []) {
            $this->warn('Not ready to replicate — set: ' . implode(', ', $problems));
        }
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
