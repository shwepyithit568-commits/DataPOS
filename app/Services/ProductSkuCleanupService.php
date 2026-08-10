<?php

namespace App\Services;

use App\Models\DataMaintenanceLog;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ProductSkuCleanupService
{
    public function __construct(private ProductSkuUniquenessAudit $audit)
    {
    }

    public function preview(string $mappingFile, ?string $storeOption = null): array
    {
        return $this->prepare($mappingFile, $storeOption);
    }

    public function apply(string $mappingFile, ?string $storeOption = null, ?string $executedBy = null): array
    {
        $prepared = $this->prepare($mappingFile, $storeOption);
        $executionId = (string) Str::uuid();
        $changedIds = [];

        DB::transaction(function () use ($prepared, $executionId, $executedBy, &$changedIds): void {
            foreach ($prepared['changes'] as $change) {
                $updated = DB::table('products')
                    ->where('id', $change['id'])
                    ->where('sku', $change['old_value'])
                    ->update([
                        'sku' => $change['new_value'],
                        'updated_at' => now(),
                    ]);

                if ($updated !== 1) {
                    throw new RuntimeException("Product {$change['id']} changed before cleanup could apply.");
                }

                DataMaintenanceLog::create([
                    'execution_id' => $executionId,
                    'operation' => 'product_sku_cleanup',
                    'store_id' => $change['store_id'],
                    'record_type' => Product::class,
                    'record_id' => $change['id'],
                    'field_name' => 'sku',
                    'old_value' => $change['old_value'],
                    'new_value' => $change['new_value'],
                    'metadata' => [
                        'store_slug' => $change['store_slug'],
                        'product_name' => $change['name'],
                    ],
                    'executed_by' => $executedBy,
                    'created_at' => now(),
                ]);

                $changedIds[] = $change['id'];
            }
        });

        return [
            'execution_id' => $executionId,
            'changed_count' => count($changedIds),
            'changed_ids' => $changedIds,
            'preview' => $prepared,
        ];
    }

    private function prepare(string $mappingFile, ?string $storeOption): array
    {
        $analysis = $this->audit->analyze($storeOption);
        $mapping = $this->readMapping($mappingFile);
        $store = $this->resolveStore($storeOption);

        $affectedIds = collect($analysis['summary']['affected_product_ids']);
        $missing = $affectedIds
            ->reject(fn(int $id): bool => array_key_exists((string) $id, $mapping))
            ->values()
            ->all();

        if ($missing !== []) {
            throw new RuntimeException('Unresolved SKU collision rows: ' . implode(', ', $missing));
        }

        $products = Product::query()
            ->with('store:id,slug')
            ->when($store, fn($query) => $query->where('store_id', $store->id))
            ->whereIn('id', array_map('intval', array_keys($mapping)))
            ->get();

        $changes = $products->map(function (Product $product) use ($mapping): array {
            $newSku = trim((string) $mapping[(string) $product->id]);
            if ($newSku === '') {
                throw new RuntimeException("Product {$product->id} has an empty target SKU.");
            }

            return [
                'id' => $product->id,
                'store_id' => $product->store_id,
                'store_slug' => $product->store?->slug,
                'name' => $product->name,
                'old_value' => (string) $product->sku,
                'new_value' => $newSku,
            ];
        })->filter(fn(array $change): bool => $change['old_value'] !== $change['new_value'])->values();

        $targetDuplicates = $changes
            ->groupBy(fn(array $change): string => $change['store_id'] . '|' . $change['new_value'])
            ->filter(fn($group) => $group->count() > 1);

        if ($targetDuplicates->isNotEmpty()) {
            throw new RuntimeException('Mapping file contains duplicate target SKUs.');
        }

        foreach ($changes as $change) {
            $exists = Product::where('store_id', $change['store_id'])
                ->where('sku', $change['new_value'])
                ->where('id', '!=', $change['id'])
                ->exists();

            if ($exists) {
                throw new RuntimeException("Target SKU already exists in store for product {$change['id']}.");
            }
        }

        return [
            'analysis' => $analysis,
            'changes' => $changes,
            'mapping_file' => $mappingFile,
        ];
    }

    private function readMapping(string $mappingFile): array
    {
        if (! is_file($mappingFile)) {
            throw new RuntimeException('Resolution mapping file not found.');
        }

        $decoded = json_decode((string) file_get_contents($mappingFile), true);
        if (! is_array($decoded)) {
            throw new RuntimeException('Resolution mapping file must be valid JSON.');
        }

        return $decoded;
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
}
