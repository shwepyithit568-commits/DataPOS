<?php

namespace App\Services;

use App\Models\GlassFinderItem;
use App\Models\DataMaintenanceLog;
use App\Models\Store;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GlassFinderNormalizationAudit
{
    public function analyze(?string $storeOption = null): array
    {
        $store = $this->resolveStore($storeOption);
        $items = $this->itemsQuery($store?->id)->get();

        $rows = $items->map(function (GlassFinderItem $item): array {
            $expected = GlassCodeNormalizer::normalize($item->glass_code);

            return [
                'id' => $item->id,
                'store_id' => $item->store_id,
                'store_slug' => $item->store?->slug,
                'phone_model' => $item->phone_model,
                'brand' => $item->brand,
                'stock_status' => $item->stock_status,
                'glass_code' => $item->glass_code,
                'current_normalized_glass_code' => (string) $item->normalized_glass_code,
                'expected_normalized_glass_code' => $expected,
                'requires_update' => (string) $item->normalized_glass_code !== $expected,
            ];
        });

        $businessKeyGroups = $rows
            ->filter(fn(array $row): bool => $row['expected_normalized_glass_code'] !== '')
            ->groupBy(fn(array $row): string => $this->businessKey($row));

        $duplicateGroups = $businessKeyGroups
            ->filter(fn(Collection $group): bool => $group->count() > 1);

        $exactDuplicateGroups = collect();
        $conflictingDuplicateGroups = collect();

        foreach ($duplicateGroups as $key => $group) {
            $brandStatusCount = $group
                ->map(fn(array $row): string => $row['brand'] . '|' . $row['stock_status'])
                ->unique()
                ->count();

            if ($brandStatusCount === 1) {
                $exactDuplicateGroups->put($key, $group);
            } else {
                $conflictingDuplicateGroups->put($key, $group);
            }
        }

        $validCompatibilityGroups = $rows
            ->filter(fn(array $row): bool => $row['expected_normalized_glass_code'] !== '')
            ->groupBy(fn(array $row): string => $row['store_id'] . '|' . $row['expected_normalized_glass_code'])
            ->filter(function (Collection $group): bool {
                return $group->pluck('phone_model')->unique()->count() > 1;
            });

        $blockedIds = $duplicateGroups
            ->flatMap(fn(Collection $group): Collection => $group->pluck('id'))
            ->merge($rows->filter(fn(array $row): bool => $row['expected_normalized_glass_code'] === '')->pluck('id'))
            ->unique()
            ->values();

        $safeUpdateRows = $rows
            ->filter(fn(array $row): bool => $row['requires_update'])
            ->reject(fn(array $row): bool => $blockedIds->contains($row['id']))
            ->values();

        $blockedRows = $rows
            ->filter(fn(array $row): bool => $row['requires_update'] && $blockedIds->contains($row['id']))
            ->values();

        return [
            'store' => $store,
            'rows' => $rows->values(),
            'safe_update_rows' => $safeUpdateRows,
            'blocked_rows' => $blockedRows,
            'exact_duplicate_groups' => $exactDuplicateGroups,
            'conflicting_duplicate_groups' => $conflictingDuplicateGroups,
            'valid_compatibility_groups' => $validCompatibilityGroups,
            'summary' => [
                'rows_inspected' => $rows->count(),
                'already_normalized_rows' => $rows->where('requires_update', false)->count(),
                'rows_requiring_updates' => $rows->where('requires_update', true)->count(),
                'safe_update_rows' => $safeUpdateRows->count(),
                'rows_blocked_from_automatic_update' => $blockedRows->count(),
                'exact_duplicate_groups' => $exactDuplicateGroups->count(),
                'conflicting_duplicate_groups' => $conflictingDuplicateGroups->count(),
                'valid_compatibility_groups' => $validCompatibilityGroups->count(),
                'affected_stores' => $rows->where('requires_update', true)->pluck('store_slug')->filter()->unique()->values()->all(),
                'affected_phone_models' => $rows->where('requires_update', true)->pluck('phone_model')->unique()->values()->all(),
            ],
        ];
    }

    public function apply(?string $storeOption = null): array
    {
        $analysis = $this->analyze($storeOption);
        $executionId = (string) Str::uuid();
        $changedIds = [];

        DB::transaction(function () use ($analysis, $executionId, $storeOption, &$changedIds): void {
            foreach ($analysis['safe_update_rows'] as $row) {
                DB::table('glass_finder_items')
                    ->where('id', $row['id'])
                    ->update([
                        'normalized_glass_code' => $row['expected_normalized_glass_code'],
                        'updated_at' => now(),
                    ]);

                DataMaintenanceLog::create([
                    'execution_id' => $executionId,
                    'operation' => 'glass_finder_normalize',
                    'store_id' => $row['store_id'],
                    'record_type' => GlassFinderItem::class,
                    'record_id' => $row['id'],
                    'field_name' => 'normalized_glass_code',
                    'old_value' => $row['current_normalized_glass_code'],
                    'new_value' => $row['expected_normalized_glass_code'],
                    'metadata' => [
                        'store_slug' => $row['store_slug'],
                        'phone_model' => $row['phone_model'],
                        'glass_code' => $row['glass_code'],
                    ],
                    'executed_by' => $storeOption ? 'artisan --store=' . $storeOption : 'artisan',
                    'created_at' => now(),
                ]);

                $changedIds[] = $row['id'];
            }
        });

        if ($changedIds !== []) {
            Log::info('Glass Finder normalized_glass_code updated.', [
                'changed_ids' => $changedIds,
                'store' => $storeOption,
            ]);
        }

        return [
            'execution_id' => $executionId,
            'analysis' => $analysis,
            'changed_ids' => $changedIds,
            'changed_count' => count($changedIds),
        ];
    }

    private function resolveStore(?string $storeOption): ?Store
    {
        if ($storeOption === null || $storeOption === '') {
            return null;
        }

        return Store::query()
            ->where('slug', $storeOption)
            ->orWhere('id', $storeOption)
            ->firstOrFail();
    }

    private function itemsQuery(?int $storeId)
    {
        return GlassFinderItem::query()
            ->with('store:id,slug,name')
            ->when($storeId, fn($query) => $query->where('store_id', $storeId))
            ->orderBy('store_id')
            ->orderBy('phone_model')
            ->orderBy('normalized_glass_code')
            ->orderBy('id');
    }

    private function businessKey(array $row): string
    {
        return $row['store_id'] . '|' . $row['phone_model'] . '|' . $row['expected_normalized_glass_code'];
    }
}
