<?php

namespace App\POS\Services;

use App\Models\AuditLog;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\Branch;
use App\POS\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class BranchManagementService
{
    public function __construct(
        protected StoreLocationService $locationService
    ) {
    }

    /**
     * Get all branches for store with counts.
     *
     * @return Collection<int, Branch>
     */
    public function getBranches(Store $store): Collection
    {
        $this->locationService->ensureDefaults($store);

        return Branch::where('store_id', $store->id)
            ->withCount('warehouses')
            ->with('warehouses')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get summary KPI stats.
     *
     * @return array<string, mixed>
     */
    public function getSummaryStats(Store $store): array
    {
        $this->locationService->ensureDefaults($store);

        $branches = Branch::where('store_id', $store->id)->get();
        $defaultBranch = $branches->firstWhere('is_default', true);
        $totalWarehouses = Warehouse::where('store_id', $store->id)->count();

        return [
            'total_branches' => $branches->count(),
            'active_branches' => $branches->where('is_active', true)->count(),
            'default_branch_name' => $defaultBranch?->name ?? 'Main Branch',
            'default_branch_code' => $defaultBranch?->code ?? 'MAIN',
            'total_warehouses' => $totalWarehouses,
        ];
    }

    /**
     * Save (create or update) a branch.
     */
    public function saveBranch(
        Store $store,
        array $data,
        ?Branch $branch = null,
        bool $createWarehouse = false,
        ?User $user = null
    ): Branch {
        return DB::transaction(function () use ($store, $data, $branch, $createWarehouse, $user) {
            $isDefault = !empty($data['is_default']);

            // If store has 0 other branches, enforce default
            $otherCount = Branch::where('store_id', $store->id)
                ->when($branch, fn($q) => $q->where('id', '!=', $branch->id))
                ->count();

            if ($otherCount === 0) {
                $isDefault = true;
            }

            if ($isDefault) {
                Branch::where('store_id', $store->id)->update(['is_default' => false]);
            }

            $attributes = [
                'name' => $data['name'],
                'code' => !empty($data['code']) ? strtoupper(trim($data['code'])) : null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'manager_name' => $data['manager_name'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_default' => $isDefault,
                'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
            ];

            if ($branch) {
                $branch->update($attributes);
                $action = 'branch_updated';
            } else {
                $branch = Branch::create(array_merge($attributes, ['store_id' => $store->id]));
                $action = 'branch_created';

                // Automatically create a dedicated warehouse if requested
                if ($createWarehouse) {
                    Warehouse::create([
                        'store_id' => $store->id,
                        'branch_id' => $branch->id,
                        'name' => $branch->name . ' Warehouse',
                        'code' => ($branch->code ? $branch->code . '-WH' : null),
                        'is_default' => false,
                        'is_active' => true,
                    ]);
                }
            }

            AuditLog::write(
                $store->id,
                $action,
                'branches',
                $branch->id,
                [
                    'name' => $branch->name,
                    'code' => $branch->code,
                    'is_default' => $branch->is_default,
                ],
                $user?->id
            );

            return $branch;
        });
    }

    /**
     * Set a branch as default.
     */
    public function setDefault(Store $store, Branch $branch, ?User $user = null): void
    {
        DB::transaction(function () use ($store, $branch, $user) {
            Branch::where('store_id', $store->id)->update(['is_default' => false]);
            $branch->update(['is_default' => true, 'is_active' => true]);

            AuditLog::write(
                $store->id,
                'branch_set_default',
                'branches',
                $branch->id,
                ['name' => $branch->name, 'code' => $branch->code],
                $user?->id
            );
        });
    }

    /**
     * Delete a branch (protected if default).
     */
    public function deleteBranch(Store $store, Branch $branch, ?User $user = null): bool
    {
        if ($branch->is_default) {
            throw new \RuntimeException('Cannot delete the default branch.');
        }

        return DB::transaction(function () use ($store, $branch, $user) {
            // Unlink warehouses before deletion
            Warehouse::where('branch_id', $branch->id)->update(['branch_id' => null]);

            $branch->delete();

            AuditLog::write(
                $store->id,
                'branch_deleted',
                'branches',
                $branch->id,
                ['name' => $branch->name],
                $user?->id
            );

            return true;
        });
    }
}
