<?php

namespace App\POS\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\POS\Models\InventoryBalance;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryValuationService
{
    /**
     * Calculate aggregate valuation metrics for the store.
     */
    public function getValuationMetrics(Store $store): array
    {
        $products = Product::where('store_id', $store->id)
            ->with(['inventoryBalances' => fn ($q) => $q->where('store_id', $store->id)])
            ->get();

        $totalItemsCount = $products->count();
        $totalUnits = 0.0;
        $totalCostValue = 0.0;
        $totalRetailValue = 0.0;
        $totalWholesaleValue = 0.0;

        $inStockCount = 0;
        $lowStockCount = 0;
        $outOfStockCount = 0;
        $zeroCostCount = 0;

        foreach ($products as $product) {
            $balances = $product->inventoryBalances;
            $qty = $balances->isNotEmpty()
                ? (float) $balances->sum('quantity_on_hand')
                : ($product->stock_status === 'in_stock' ? 1.0 : 0.0);

            $unitCost = $balances->isNotEmpty() && (float) $balances->first()->unit_cost_avg > 0
                ? (float) $balances->first()->unit_cost_avg
                : (float) ($product->purchase_cost ?? 0);

            $retailPrice = (float) ($product->retail_price ?? 0);
            $wholesalePrice = (float) ($product->wholesale_price ?? $retailPrice);

            if ($unitCost <= 0) {
                $zeroCostCount++;
            }

            if ($qty > 0) {
                $reorderLevel = (float) ($product->reorder_level ?? 5);
                if ($qty <= $reorderLevel) {
                    $lowStockCount++;
                } else {
                    $inStockCount++;
                }
            } else {
                $outOfStockCount++;
            }

            $costVal = $qty > 0 ? ($qty * $unitCost) : 0.0;
            $retailVal = $qty > 0 ? ($qty * $retailPrice) : 0.0;
            $wholesaleVal = $qty > 0 ? ($qty * $wholesalePrice) : 0.0;

            $totalUnits += $qty;
            $totalCostValue += $costVal;
            $totalRetailValue += $retailVal;
            $totalWholesaleValue += $wholesaleVal;
        }

        $potentialProfit = max(0.0, $totalRetailValue - $totalCostValue);
        $potentialMargin = $totalRetailValue > 0 ? round(($potentialProfit / $totalRetailValue) * 100, 2) : 0.0;

        return [
            'total_items_count'      => $totalItemsCount,
            'total_units'            => $totalUnits,
            'total_cost_value'       => $totalCostValue,
            'total_retail_value'     => $totalRetailValue,
            'total_wholesale_value'  => $totalWholesaleValue,
            'potential_profit'       => $potentialProfit,
            'potential_margin'       => $potentialMargin,
            'in_stock_count'         => $inStockCount,
            'low_stock_count'        => $lowStockCount,
            'out_of_stock_count'     => $outOfStockCount,
            'zero_cost_count'        => $zeroCostCount,
        ];
    }

    /**
     * Get valuation grouped by categories.
     */
    public function getCategoryValuation(Store $store): array
    {
        $products = Product::where('store_id', $store->id)
            ->with(['category', 'inventoryBalances' => fn ($q) => $q->where('store_id', $store->id)])
            ->get();

        $categories = [];
        $grandTotalCost = 0.0;

        foreach ($products as $product) {
            $catName = $product->category?->name ?? 'Uncategorized';
            $balances = $product->inventoryBalances;

            $qty = $balances->isNotEmpty()
                ? (float) $balances->sum('quantity_on_hand')
                : ($product->stock_status === 'in_stock' ? 1.0 : 0.0);

            $unitCost = $balances->isNotEmpty() && (float) $balances->first()->unit_cost_avg > 0
                ? (float) $balances->first()->unit_cost_avg
                : (float) ($product->purchase_cost ?? 0);

            $retailPrice = (float) ($product->retail_price ?? 0);

            if (!isset($categories[$catName])) {
                $categories[$catName] = [
                    'name'         => $catName,
                    'items_count'  => 0,
                    'total_qty'    => 0.0,
                    'cost_value'   => 0.0,
                    'retail_value' => 0.0,
                ];
            }

            $costVal = $qty > 0 ? ($qty * $unitCost) : 0.0;
            $retailVal = $qty > 0 ? ($qty * $retailPrice) : 0.0;

            $categories[$catName]['items_count']++;
            $categories[$catName]['total_qty'] += $qty;
            $categories[$catName]['cost_value'] += $costVal;
            $categories[$catName]['retail_value'] += $retailVal;
            $grandTotalCost += $costVal;
        }

        foreach ($categories as &$c) {
            $c['percent'] = $grandTotalCost > 0 ? round(($c['cost_value'] / $grandTotalCost) * 100, 1) : 0.0;
            $c['profit'] = max(0.0, $c['retail_value'] - $c['cost_value']);
        }
        unset($c);

        uasort($categories, fn ($a, $b) => $b['cost_value'] <=> $a['cost_value']);

        return array_values($categories);
    }

    /**
     * Get valuation grouped by brands.
     */
    public function getBrandValuation(Store $store): array
    {
        $products = Product::where('store_id', $store->id)
            ->with(['brand', 'inventoryBalances' => fn ($q) => $q->where('store_id', $store->id)])
            ->get();

        $brands = [];
        $grandTotalCost = 0.0;

        foreach ($products as $product) {
            $brandName = $product->brand?->name ?? 'No Brand';
            $balances = $product->inventoryBalances;

            $qty = $balances->isNotEmpty()
                ? (float) $balances->sum('quantity_on_hand')
                : ($product->stock_status === 'in_stock' ? 1.0 : 0.0);

            $unitCost = $balances->isNotEmpty() && (float) $balances->first()->unit_cost_avg > 0
                ? (float) $balances->first()->unit_cost_avg
                : (float) ($product->purchase_cost ?? 0);

            $retailPrice = (float) ($product->retail_price ?? 0);

            if (!isset($brands[$brandName])) {
                $brands[$brandName] = [
                    'name'         => $brandName,
                    'items_count'  => 0,
                    'total_qty'    => 0.0,
                    'cost_value'   => 0.0,
                    'retail_value' => 0.0,
                ];
            }

            $costVal = $qty > 0 ? ($qty * $unitCost) : 0.0;
            $retailVal = $qty > 0 ? ($qty * $retailPrice) : 0.0;

            $brands[$brandName]['items_count']++;
            $brands[$brandName]['total_qty'] += $qty;
            $brands[$brandName]['cost_value'] += $costVal;
            $brands[$brandName]['retail_value'] += $retailVal;
            $grandTotalCost += $costVal;
        }

        foreach ($brands as &$b) {
            $b['percent'] = $grandTotalCost > 0 ? round(($b['cost_value'] / $grandTotalCost) * 100, 1) : 0.0;
            $b['profit'] = max(0.0, $b['retail_value'] - $b['cost_value']);
        }
        unset($b);

        uasort($brands, fn ($a, $b) => $b['cost_value'] <=> $a['cost_value']);

        return array_values($brands);
    }

    /**
     * Get paginated/filtered list of product valuation items.
     */
    public function getValuationProducts(Store $store, array $filters = [], int|string $perPage = 25): LengthAwarePaginator|Collection
    {
        $query = Product::where('store_id', $store->id)
            ->with([
                'category',
                'brand',
                'inventoryBalances' => fn ($q) => $q->where('store_id', $store->id),
            ]);

        // Search filter
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Category filter
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Brand filter
        if (!empty($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }

        // Stock status filter
        if (!empty($filters['stock_status'])) {
            $status = $filters['stock_status'];
            if ($status === 'in_stock') {
                $query->where(function ($q) {
                    $q->whereHas('inventoryBalances', fn ($b) => $b->where('quantity_on_hand', '>', 0))
                      ->orWhere('stock_status', 'in_stock');
                });
            } elseif ($status === 'out_of_stock') {
                $query->where(function ($q) {
                    $q->whereDoesntHave('inventoryBalances')
                      ->orWhereHas('inventoryBalances', fn ($b) => $b->where('quantity_on_hand', '<=', 0));
                })->where('stock_status', '!=', 'in_stock');
            } elseif ($status === 'zero_cost') {
                $query->where(function ($q) {
                    $q->whereNull('purchase_cost')->orWhere('purchase_cost', '<=', 0);
                });
            }
        }

        $allMatching = $query->get()->map(function ($product) {
            $balances = $product->inventoryBalances;
            $qty = $balances->isNotEmpty()
                ? (float) $balances->sum('quantity_on_hand')
                : ($product->stock_status === 'in_stock' ? 1.0 : 0.0);

            $unitCost = $balances->isNotEmpty() && (float) $balances->first()->unit_cost_avg > 0
                ? (float) $balances->first()->unit_cost_avg
                : (float) ($product->purchase_cost ?? 0);

            $retailPrice = (float) ($product->retail_price ?? 0);
            $wholesalePrice = (float) ($product->wholesale_price ?? $retailPrice);

            $costValue = $qty > 0 ? round($qty * $unitCost, 2) : 0.0;
            $retailValue = $qty > 0 ? round($qty * $retailPrice, 2) : 0.0;
            $wholesaleValue = $qty > 0 ? round($qty * $wholesalePrice, 2) : 0.0;
            $profit = max(0.0, $retailValue - $costValue);
            $margin = $retailValue > 0 ? round(($profit / $retailValue) * 100, 1) : 0.0;

            $product->computed_qty = $qty;
            $product->computed_cost = $unitCost;
            $product->computed_cost_value = $costValue;
            $product->computed_retail_value = $retailValue;
            $product->computed_wholesale_value = $wholesaleValue;
            $product->computed_profit = $profit;
            $product->computed_margin = $margin;

            return $product;
        });

        // Sorting
        $sort = $filters['sort'] ?? 'cost_value_desc';
        $sorted = match ($sort) {
            'cost_value_asc'  => $allMatching->sortBy('computed_cost_value'),
            'retail_value_desc' => $allMatching->sortByDesc('computed_retail_value'),
            'retail_value_asc'  => $allMatching->sortBy('computed_retail_value'),
            'qty_desc'        => $allMatching->sortByDesc('computed_qty'),
            'qty_asc'         => $allMatching->sortBy('computed_qty'),
            'margin_desc'     => $allMatching->sortByDesc('computed_margin'),
            'name_asc'        => $allMatching->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE),
            default           => $allMatching->sortByDesc('computed_cost_value'),
        };

        if ($perPage === 'all' || (int) $perPage === 0) {
            return $sorted->values();
        }

        $page = (int) ($filters['page'] ?? 1);
        $perPageInt = (int) $perPage;
        $items = $sorted->forPage($page, $perPageInt)->values();

        return new LengthAwarePaginator(
            $items,
            $sorted->count(),
            $perPageInt,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }
}
