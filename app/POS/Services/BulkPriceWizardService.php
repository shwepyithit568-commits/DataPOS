<?php

namespace App\POS\Services;

use App\Models\AuditLog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BulkPriceWizardService
{
    /**
     * Get KPI and status metrics for pricing in the store.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(Store $store): array
    {
        $totalProducts = Product::where('store_id', $store->id)->count();

        $withCostQuery = Product::where('store_id', $store->id)
            ->whereNotNull('purchase_cost')
            ->where('purchase_cost', '>', 0);

        $withCostCount = $withCostQuery->count();
        $zeroCostCount = $totalProducts - $withCostCount;

        // Calculate average margin for products that have both retail_price and purchase_cost
        $marginStats = Product::where('store_id', $store->id)
            ->whereNotNull('purchase_cost')
            ->where('purchase_cost', '>', 0)
            ->whereNotNull('retail_price')
            ->where('retail_price', '>', 0)
            ->selectRaw('
                COUNT(*) as count,
                AVG(((CAST(retail_price AS REAL) - CAST(purchase_cost AS REAL)) / CAST(retail_price AS REAL)) * 100.0) as avg_margin,
                SUM(purchase_cost) as total_cost_sum,
                SUM(retail_price) as total_retail_sum
            ')
            ->first();

        $avgMargin = $marginStats && $marginStats->count > 0 ? (float) $marginStats->avg_margin : 0.0;

        // Count products currently below cost (loss making)
        $belowCostCount = Product::where('store_id', $store->id)
            ->whereNotNull('purchase_cost')
            ->where('purchase_cost', '>', 0)
            ->whereNotNull('retail_price')
            ->whereRaw('retail_price < purchase_cost')
            ->count();

        return [
            'total_products' => $totalProducts,
            'with_cost_count' => $withCostCount,
            'zero_cost_count' => $zeroCostCount,
            'avg_margin' => round($avgMargin, 1),
            'below_cost_count' => $belowCostCount,
        ];
    }

    /**
     * Get filtering options (Categories, Brands, Suppliers).
     *
     * @return array<string, mixed>
     */
    public function getFilterOptions(Store $store): array
    {
        $categories = Category::where('store_id', $store->id)
            ->withCount('products')
            ->orderBy('name')
            ->get();

        $brands = Brand::where('store_id', $store->id)
            ->withCount('products')
            ->orderBy('name')
            ->get();

        $suppliers = Supplier::where('store_id', $store->id)
            ->orderBy('name')
            ->get();

        return [
            'categories' => $categories,
            'brands' => $brands,
            'suppliers' => $suppliers,
        ];
    }

    /**
     * Query products matching the filters.
     *
     * @param array<string, mixed> $filters
     * @return Collection<int, Product>
     */
    public function getProducts(Store $store, array $filters = []): Collection
    {
        $query = Product::where('store_id', $store->id)
            ->with(['category', 'brand', 'supplier', 'variants']);

        if (!empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (!empty($filters['brand_id'])) {
            $query->where('brand_id', (int) $filters['brand_id']);
        }

        if (!empty($filters['supplier_id'])) {
            $query->where('supplier_id', (int) $filters['supplier_id']);
        }

        if (!empty($filters['stock_status'])) {
            $query->where('stock_status', $filters['stock_status']);
        }

        if (!empty($filters['cost_filter'])) {
            if ($filters['cost_filter'] === 'with_cost') {
                $query->whereNotNull('purchase_cost')->where('purchase_cost', '>', 0);
            } elseif ($filters['cost_filter'] === 'zero_cost') {
                $query->where(function (Builder $q) {
                    $q->whereNull('purchase_cost')->orWhere('purchase_cost', '<=', 0);
                });
            } elseif ($filters['cost_filter'] === 'below_cost') {
                $query->whereNotNull('purchase_cost')
                    ->where('purchase_cost', '>', 0)
                    ->whereRaw('retail_price < purchase_cost');
            }
        }

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Calculate new price based on calculation strategy and rounding rules.
     */
    public function calculateNewPrice(
        float $cost,
        float $currentPrice,
        string $mode,
        float $value,
        string $rounding = 'none'
    ): float {
        $calculated = $currentPrice;

        switch ($mode) {
            case 'markup_on_cost':
                // Cost + (Cost * X%)
                $base = $cost > 0 ? $cost : $currentPrice;
                $calculated = $base * (1 + ($value / 100));
                break;

            case 'margin_on_cost':
                // Target Margin: Cost / (1 - Margin%)
                $base = $cost > 0 ? $cost : $currentPrice;
                $marginDecimal = $value / 100;
                if ($marginDecimal >= 1) {
                    $marginDecimal = 0.99; // prevent division by zero or negative
                }
                $calculated = $base / (1 - $marginDecimal);
                break;

            case 'percentage_on_current':
                // Current Price * (1 + X%)
                $calculated = $currentPrice * (1 + ($value / 100));
                break;

            case 'fixed_amount_on_current':
                // Current Price +/- Fixed Amount
                $calculated = $currentPrice + $value;
                break;

            case 'fixed_price':
                // Exact Price
                $calculated = $value;
                break;

            case 'wholesale_from_retail':
                // Retail Price * (1 - Discount%)
                $calculated = $currentPrice * (1 - ($value / 100));
                break;

            default:
                $calculated = $currentPrice;
                break;
        }

        if ($calculated < 0) {
            $calculated = 0;
        }

        return $this->applyRounding($calculated, $rounding);
    }

    /**
     * Apply rounding rules to price.
     */
    public function applyRounding(float $price, string $rounding): float
    {
        switch ($rounding) {
            case 'round_10':
                return round($price / 10) * 10;

            case 'round_50':
                return round($price / 50) * 50;

            case 'round_100':
                return round($price / 100) * 100;

            case 'round_500':
                return round($price / 500) * 500;

            case 'round_1000':
                return round($price / 1000) * 1000;

            case 'charm_900':
                // e.g. 15,200 -> 14,900 or 15,900
                $thousands = floor($price / 1000) * 1000;
                $result = $thousands + 900;
                return $result > 0 ? $result : $price;

            case 'charm_990':
                $thousands = floor($price / 1000) * 1000;
                $result = $thousands + 990;
                return $result > 0 ? $result : $price;

            case 'none':
            default:
                return round($price, 2);
        }
    }

    /**
     * Apply bulk price changes to database within a single transaction.
     *
     * @param array<int, array<string, mixed>> $items Array of ['product_id' => int, 'retail_price' => ?float, 'wholesale_price' => ?float, 'old_price' => ?float]
     * @param array<string, mixed> $options ['sync_variants' => bool, 'update_old_price' => bool]
     * @return array<string, mixed> Result summary
     */
    public function applyBulkUpdate(
        Store $store,
        array $items,
        array $options = [],
        ?User $actor = null,
        ?string $ipAddress = null
    ): array {
        if (empty($items)) {
            return [
                'success' => false,
                'updated_count' => 0,
                'message' => 'No products selected for update.',
            ];
        }

        $syncVariants = (bool) ($options['sync_variants'] ?? true);
        $setOldPrice = (bool) ($options['set_old_price'] ?? false);

        $updatedCount = 0;
        $warnings = [];
        $auditChanges = [];

        DB::transaction(function () use (
            $store,
            $items,
            $syncVariants,
            $setOldPrice,
            &$updatedCount,
            &$warnings,
            &$auditChanges
        ) {
            foreach ($items as $item) {
                $productId = (int) ($item['product_id'] ?? 0);
                if (!$productId) {
                    continue;
                }

                $product = Product::where('store_id', $store->id)->find($productId);
                if (!$product) {
                    continue;
                }

                $oldRetail = (float) $product->retail_price;
                $oldWholesale = (float) $product->wholesale_price;
                $oldCompareAt = (float) $product->old_price;
                $cost = (float) $product->purchase_cost;

                $newRetail = isset($item['retail_price']) && $item['retail_price'] !== ''
                    ? (float) $item['retail_price']
                    : null;

                $newWholesale = isset($item['wholesale_price']) && $item['wholesale_price'] !== ''
                    ? (float) $item['wholesale_price']
                    : null;

                $newOldPrice = isset($item['old_price']) && $item['old_price'] !== ''
                    ? (float) $item['old_price']
                    : null;

                $productUpdates = [];

                if ($newRetail !== null && $newRetail >= 0) {
                    $productUpdates['retail_price'] = $newRetail;

                    if ($setOldPrice && $newRetail < $oldRetail && $oldCompareAt <= 0) {
                        $productUpdates['old_price'] = $oldRetail;
                    }

                    if ($cost > 0 && $newRetail < $cost) {
                        $warnings[] = "Product '{$product->name}' (SKU: {$product->sku}) new retail price ({$newRetail}) is below cost ({$cost}).";
                    }
                }

                if ($newWholesale !== null && $newWholesale >= 0) {
                    $productUpdates['wholesale_price'] = $newWholesale;
                }

                if ($newOldPrice !== null) {
                    $productUpdates['old_price'] = $newOldPrice > 0 ? $newOldPrice : null;
                }

                if (!empty($productUpdates)) {
                    $product->update($productUpdates);

                    if ($syncVariants) {
                        $variantUpdates = [];
                        if (isset($productUpdates['retail_price'])) {
                            $variantUpdates['retail_price'] = $productUpdates['retail_price'];
                        }
                        if (isset($productUpdates['wholesale_price'])) {
                            $variantUpdates['wholesale_price'] = $productUpdates['wholesale_price'];
                        }

                        if (!empty($variantUpdates)) {
                            ProductVariant::where('product_id', $product->id)
                                ->where('is_default', true)
                                ->update($variantUpdates);
                        }
                    }

                    $updatedCount++;

                    $auditChanges[] = [
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'name' => $product->name,
                        'old_retail' => $oldRetail,
                        'new_retail' => $newRetail ?? $oldRetail,
                        'old_wholesale' => $oldWholesale,
                        'new_wholesale' => $newWholesale ?? $oldWholesale,
                    ];
                }
            }
        });

        // Write to Audit Log
        if ($updatedCount > 0) {
            AuditLog::write(
                $store->id,
                'bulk_price_update',
                'products',
                null,
                [
                    'updated_count' => $updatedCount,
                    'options' => $options,
                    'sample_changes' => array_slice($auditChanges, 0, 20),
                    'total_changes_logged' => count($auditChanges),
                ],
                $actor?->id,
                $ipAddress
            );
        }

        return [
            'success' => true,
            'updated_count' => $updatedCount,
            'warnings' => $warnings,
            'message' => "Successfully updated prices for {$updatedCount} products.",
        ];
    }

    /**
     * Generate CSV export of current product pricing.
     */
    public function exportCsv(Store $store, array $filters = []): StreamedResponse
    {
        $products = $this->getProducts($store, $filters);
        $filename = 'price-list-' . $store->slug . '-' . date('Ymd-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($products) {
            $file = fopen('php://output', 'w');
            // Add UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'Product ID',
                'SKU',
                'Product Name',
                'Category',
                'Brand',
                'Supplier',
                'Purchase Cost (MMK)',
                'Current Retail Price (MMK)',
                'Wholesale Price (MMK)',
                'Compare-at Price (MMK)',
                'Retail Margin %',
                'Stock Status',
            ]);

            foreach ($products as $p) {
                $cost = (float) ($p->purchase_cost ?? 0);
                $retail = (float) ($p->retail_price ?? 0);
                $wholesale = (float) ($p->wholesale_price ?? 0);
                $oldPrice = (float) ($p->old_price ?? 0);

                $margin = $retail > 0 && $cost > 0
                    ? round((($retail - $cost) / $retail) * 100, 1)
                    : 0;

                fputcsv($file, [
                    $p->id,
                    $p->sku,
                    $p->name,
                    $p->category?->name ?? '-',
                    $p->brand?->name ?? '-',
                    $p->supplier?->name ?? '-',
                    number_format($cost, 2, '.', ''),
                    number_format($retail, 2, '.', ''),
                    number_format($wholesale, 2, '.', ''),
                    $oldPrice > 0 ? number_format($oldPrice, 2, '.', '') : '',
                    $margin . '%',
                    $p->stock_status,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
