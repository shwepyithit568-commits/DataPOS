<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\POS\Services\InventoryService;
use Illuminate\Console\Command;

class SyncStockStatusCommand extends Command
{
    protected $signature = 'inventory:sync-stock-status {--store= : Optional store slug or ID}';

    protected $description = 'Synchronize products and variants stock_status cache with actual ledger inventory balances';

    public function handle(InventoryService $inventoryService): int
    {
        $storeIdentifier = $this->option('store');
        $storeId = null;

        if ($storeIdentifier) {
            $store = is_numeric($storeIdentifier)
                ? Store::find($storeIdentifier)
                : Store::where('slug', $storeIdentifier)->first();

            if (! $store) {
                $this->error("Store [{$storeIdentifier}] not found.");
                return self::FAILURE;
            }

            $storeId = (int) $store->id;
            $this->info("Synchronizing stock statuses for store: {$store->name} (ID: {$storeId})...");
        } else {
            $this->info("Synchronizing stock statuses across all stores...");
        }

        $count = $inventoryService->syncAllStockStatuses($storeId);

        $this->info("Successfully synchronized {$count} products with ledger balances.");

        return self::SUCCESS;
    }
}
