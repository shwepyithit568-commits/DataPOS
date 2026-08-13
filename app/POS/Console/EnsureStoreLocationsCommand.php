<?php

namespace App\POS\Console;

use App\Models\Store;
use App\POS\Services\StoreLocationService;
use Illuminate\Console\Command;

class EnsureStoreLocationsCommand extends Command
{
    protected $signature = 'inventory:ensure-locations
                            {--store= : Store slug or ID — optional, defaults to ALL stores}';

    protected $description = 'Ensure every store has a default branch and default warehouse (idempotent backfill)';

    public function handle(StoreLocationService $locations): int
    {
        $stores = Store::query()
            ->when($this->option('store'), function ($query, $ref) {
                $query->where('slug', $ref)
                    ->when(is_numeric($ref), fn ($q) => $q->orWhere('id', (int) $ref));
            })
            ->orderBy('id')
            ->get();

        if ($stores->isEmpty()) {
            $this->error('No stores found' . ($this->option('store') ? " for '{$this->option('store')}'" : '') . '.');

            return self::FAILURE;
        }

        foreach ($stores as $store) {
            $result = $locations->ensureDefaults($store);
            $this->line("  #{$store->id} {$store->slug} → branch #{$result['branch']->id} '{$result['branch']->name}' + warehouse #{$result['warehouse']->id} '{$result['warehouse']->name}'");
        }

        $this->info('✅ ' . $stores->count() . ' store(s) now have default branch + warehouse.');

        return self::SUCCESS;
    }
}
