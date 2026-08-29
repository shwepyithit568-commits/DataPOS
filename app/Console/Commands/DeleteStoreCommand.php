<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Services\DemoBusinessScenarioService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DeleteStoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'datapos:delete-store
                            {store : The store slug or ID to delete}
                            {--force : Force delete without interactive confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently delete a store and all associated catalog, transaction, order, and POS records';

    /**
     * Execute the console command.
     */
    public function handle(DemoBusinessScenarioService $scenarioService): int
    {
        $storeRef = $this->argument('store');

        $store = Store::query()
            ->where('slug', $storeRef)
            ->when(is_numeric($storeRef), fn ($query) => $query->orWhere('id', (int) $storeRef))
            ->first();

        if (! $store) {
            $this->error("❌ Store '{$storeRef}' was not found.");
            return self::FAILURE;
        }

        $activeStores = Store::where('is_active', true)->count();
        if ($store->is_active && $activeStores <= 1 && ! $this->option('force')) {
            $this->error("⚠️ Store '{$store->name}' ({$store->slug}) is the ONLY active store in the system. Deleting it will leave no active stores.");
            if (! $this->confirm('Are you absolutely sure you want to proceed?', false)) {
                $this->info('Operation cancelled.');
                return self::SUCCESS;
            }
        }

        if (! $this->option('force')) {
            if (! $this->confirm("Are you sure you want to permanently delete store '{$store->name}' ({$store->slug}) and all related products, orders, and sales data?", false)) {
                $this->info('Operation cancelled.');
                return self::SUCCESS;
            }
        }

        $this->info("🗑️ Deleting all catalog, order, and transaction data for '{$store->name}' ({$store->slug})...");

        DB::transaction(function () use ($store, $scenarioService) {
            // 1. Clean transactions, catalog, inventory, orders, pos sales
            $scenarioService->cleanStoreData($store);

            // 2. Delete store settings, payment methods, delivery methods, branches, warehouses
            if (Schema::hasTable('storefront_settings')) {
                DB::table('storefront_settings')->where('store_id', $store->id)->delete();
            }
            if (Schema::hasTable('store_payment_methods')) {
                DB::table('store_payment_methods')->where('store_id', $store->id)->delete();
            }
            if (Schema::hasTable('store_delivery_methods')) {
                DB::table('store_delivery_methods')->where('store_id', $store->id)->delete();
            }
            if (Schema::hasTable('warehouses')) {
                DB::table('warehouses')->where('store_id', $store->id)->delete();
            }
            if (Schema::hasTable('branches')) {
                DB::table('branches')->where('store_id', $store->id)->delete();
            }
            if (Schema::hasTable('store_user')) {
                DB::table('store_user')->where('store_id', $store->id)->delete();
            }

            // 3. If primary, promote next active store
            if ($store->is_primary) {
                $next = Store::where('is_active', true)
                    ->where('id', '!=', $store->id)
                    ->orderBy('id')
                    ->first();
                if ($next) {
                    $next->update(['is_primary' => true]);
                }
            }

            // 4. Delete the store itself
            $store->delete();
        });

        $this->info("✅ Store '{$store->name}' ({$store->slug}) has been permanently deleted.");

        return self::SUCCESS;
    }
}
