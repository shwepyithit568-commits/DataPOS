<?php

namespace App\POS\Services;

use App\Models\Store;
use App\POS\Models\Branch;
use App\POS\Models\Warehouse;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Default branch/warehouse provisioning (target-design §2.11).
 *
 * Every store gets one default branch + one default warehouse automatically.
 * ensureDefaults() is idempotent (safe to call on every store creation and
 * lazily from the inventory ledger when a movement omits a warehouse).
 */
class StoreLocationService
{
    /**
     * Ensure the store has a default branch and default warehouse.
     *
     * @return array{branch: Branch, warehouse: Warehouse}
     */
    public function ensureDefaults(Store $store): array
    {
        return DB::transaction(function () use ($store) {
            $branch = Branch::query()
                ->where('store_id', $store->id)
                ->where('is_default', true)
                ->first();

            if (! $branch) {
                $branch = $this->createDefaultBranch($store);
            }

            $warehouse = Warehouse::query()
                ->where('store_id', $store->id)
                ->where('is_default', true)
                ->first();

            if (! $warehouse) {
                $warehouse = $this->createDefaultWarehouse($store, $branch);
            }

            return ['branch' => $branch, 'warehouse' => $warehouse];
        });
    }

    /** Resolve the store's default warehouse, creating it if missing. */
    public function defaultWarehouse(Store $store): Warehouse
    {
        return $this->ensureDefaults($store)['warehouse'];
    }

    /** Resolve the store's default branch, creating it if missing. */
    public function defaultBranch(Store $store): Branch
    {
        return $this->ensureDefaults($store)['branch'];
    }

    protected function createDefaultBranch(Store $store): Branch
    {
        try {
            return Branch::create([
                'store_id' => $store->id,
                'name' => 'Main Branch',
                'code' => 'MAIN',
                'is_default' => true,
            ]);
        } catch (QueryException $e) {
            $existing = Branch::query()
                ->where('store_id', $store->id)
                ->where('is_default', true)
                ->first();

            if ($existing) {
                return $existing; // concurrent creation race
            }

            throw $e;
        }
    }

    protected function createDefaultWarehouse(Store $store, Branch $branch): Warehouse
    {
        try {
            return Warehouse::create([
                'store_id' => $store->id,
                'branch_id' => $branch->id,
                'name' => 'Main Warehouse',
                'code' => 'MAIN',
                'is_default' => true,
            ]);
        } catch (QueryException $e) {
            $existing = Warehouse::query()
                ->where('store_id', $store->id)
                ->where('is_default', true)
                ->first();

            if ($existing) {
                return $existing; // concurrent creation race
            }

            throw $e;
        }
    }
}
