<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Collection;

class ProductSkuUniquenessAudit
{
    public function analyze(?string $storeOption = null): array
    {
        $store = $this->resolveStore($storeOption);
        $products = Product::query()
            ->with('store:id,slug,name')
            ->when($store, fn($query) => $query->where('store_id', $store->id))
            ->orderBy('store_id')
            ->orderBy('sku')
            ->orderBy('id')
            ->get(['id', 'store_id', 'sku', 'name', 'retail_price', 'stock_status']);

        $rows = $products->map(fn(Product $product): array => [
            'id' => $product->id,
            'store_id' => $product->store_id,
            'store_slug' => $product->store?->slug,
            'sku' => (string) $product->sku,
            'name' => $product->name,
            'retail_price' => (string) $product->retail_price,
            'stock_status' => $product->stock_status,
            'case_key' => mb_strtolower((string) $product->sku),
            'whitespace_key' => $this->normalizeWhitespace((string) $product->sku),
        ]);

        $exactDuplicateGroups = $this->duplicateGroups($rows, fn(array $row): string => $row['store_id'] . '|' . $row['sku']);
        $caseOnlyDuplicateGroups = $this->duplicateGroups($rows, fn(array $row): string => $row['store_id'] . '|' . $row['case_key'])
            ->filter(fn(Collection $group): bool => $group->pluck('sku')->unique()->count() > 1);
        $whitespaceDuplicateGroups = $this->duplicateGroups($rows, fn(array $row): string => $row['store_id'] . '|' . $row['whitespace_key'])
            ->filter(fn(Collection $group): bool => $group->pluck('sku')->unique()->count() > 1);
        $blankRows = $rows->filter(fn(array $row): bool => trim($row['sku']) === '')->values();

        $affectedRows = collect()
            ->merge($exactDuplicateGroups->flatMap(fn(Collection $group) => $group))
            ->merge($caseOnlyDuplicateGroups->flatMap(fn(Collection $group) => $group))
            ->merge($whitespaceDuplicateGroups->flatMap(fn(Collection $group) => $group))
            ->merge($blankRows)
            ->unique('id')
            ->values();

        return [
            'store' => $store,
            'rows' => $rows->values(),
            'exact_duplicate_groups' => $exactDuplicateGroups,
            'case_only_duplicate_groups' => $caseOnlyDuplicateGroups,
            'whitespace_duplicate_groups' => $whitespaceDuplicateGroups,
            'blank_rows' => $blankRows,
            'affected_rows' => $affectedRows,
            'summary' => [
                'total_products_inspected' => $rows->count(),
                'duplicate_sku_groups' => $exactDuplicateGroups->count(),
                'blank_sku_rows' => $blankRows->count(),
                'case_only_duplicate_groups' => $caseOnlyDuplicateGroups->count(),
                'whitespace_normalized_duplicate_groups' => $whitespaceDuplicateGroups->count(),
                'affected_stores' => $affectedRows->pluck('store_slug')->filter()->unique()->values()->all(),
                'affected_product_ids' => $affectedRows->pluck('id')->values()->all(),
            ],
        ];
    }

    private function duplicateGroups(Collection $rows, callable $keyCallback): Collection
    {
        return $rows
            ->filter(fn(array $row): bool => trim($row['sku']) !== '')
            ->groupBy($keyCallback)
            ->filter(fn(Collection $group): bool => $group->count() > 1);
    }

    private function normalizeWhitespace(string $sku): string
    {
        return (string) preg_replace('/\s+/', '', trim($sku));
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
