<?php

namespace App\POS\Services;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\PurchaseOrder;
use App\Services\StorePermissionService;
use Illuminate\Validation\ValidationException;

class PurchasePriceAdjustmentService
{
    public function __construct(
        protected StorePermissionService $permissionService,
    ) {}

    /**
     * Resolve default markups with precedence:
     * 1. Store Settings (StorefrontSetting pos_settings['default_retail_markup'] / ['default_wholesale_markup'])
     * 2. Fallback values: Retail 20.00%, Wholesale 10.00%
     *
     * @return array{retail_markup: string, wholesale_markup: string, source: string}
     */
    public function getStoreMarkups(Store $store): array
    {
        $posSettings = $store->setting?->pos_settings ?? [];

        $retail = isset($posSettings['default_retail_markup']) && is_numeric($posSettings['default_retail_markup'])
            ? bcadd((string) $posSettings['default_retail_markup'], '0', 2)
            : '20.00';

        $wholesale = isset($posSettings['default_wholesale_markup']) && is_numeric($posSettings['default_wholesale_markup'])
            ? bcadd((string) $posSettings['default_wholesale_markup'], '0', 2)
            : '10.00';

        $source = (isset($posSettings['default_retail_markup']) || isset($posSettings['default_wholesale_markup']))
            ? 'store_settings'
            : 'default_fallback';

        return [
            'retail_markup' => $retail,
            'wholesale_markup' => $wholesale,
            'source' => $source,
        ];
    }

    /**
     * Calculate cost baselines from server database.
     *
     * For Create PO: current product/variant DB purchase_cost.
     * For Edit PO: database original PO line unit_cost for existing lines.
     *
     * @param  Store  $store
     * @param  array  $poItems  Normalized PO lines: [['product_id' => int, 'product_variant_id' => ?int, 'unit_cost' => string, ...]]
     * @param  PurchaseOrder|null  $existingPo
     * @return array<string, string> Map of "$productId:$variantId" => baselineCost
     */
    public function calculateCostBaselines(Store $store, array $poItems, ?PurchaseOrder $existingPo = null): array
    {
        $baselines = [];

        // For existing PO (Edit mode), index original lines from DB
        $existingLinesMap = [];
        if ($existingPo) {
            foreach ($existingPo->items()->get() as $item) {
                $key = $item->product_id . ':' . ($item->product_variant_id ?: '0');
                $existingLinesMap[$key] = (string) $item->unit_cost;
            }
        }

        // Collect all product IDs
        $productIds = array_unique(array_filter(array_column($poItems, 'product_id')));
        $products = Product::where('store_id', $store->id)
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        foreach ($poItems as $line) {
            $pId = (int) $line['product_id'];
            $vId = ! empty($line['product_variant_id']) ? (int) $line['product_variant_id'] : 0;
            $key = $pId . ':' . $vId;

            if ($existingPo && isset($existingLinesMap[$key])) {
                // Edit PO: baseline is original PO line cost from DB
                $baselines[$key] = bcadd($existingLinesMap[$key], '0', 2);
            } else {
                // Create PO (or newly-added line in Edit PO): baseline is current product purchase_cost from DB
                $product = $products->get($pId);
                $productCost = $product ? (string) ($product->purchase_cost ?? '0') : '0';
                $baselines[$key] = bcadd($productCost, '0', 2);
            }
        }

        return $baselines;
    }

    /**
     * Validate and normalize price updates payload.
     *
     * Rules enforced:
     * - Store isolation (product & variant must belong to current store)
     * - PO line presence (each updated item must be in submitted PO lines)
     * - Variant-product hierarchy check (variant must belong to specified product)
     * - Duplicate rejection (duplicate product_id + product_variant_id is rejected)
     * - Cost-change requirement (price update is only allowed if server baseline cost changed)
     * - Permission check (products.update required if any update_prices=true)
     * - Decimal normalization (Bcmath 2 decimal places)
     * - Minimum price / negative price rejection
     * - Wholesale <= Retail rule
     * - Selling Price >= New Cost rule (no price below new purchase cost)
     *
     * @param  Store  $store
     * @param  array  $priceUpdatesRaw
     * @param  array  $poLines  Normalized PO lines
     * @param  User  $actor
     * @param  array  $baselines  Map of "$pId:$vId" => baselineCost
     * @return array  Validated and normalized price updates
     *
     * @throws ValidationException
     */
    public function validateAndNormalizePriceUpdates(
        Store $store,
        array $priceUpdatesRaw,
        array $poLines,
        User $actor,
        array $baselines,
    ): array {
        if (empty($priceUpdatesRaw)) {
            return [];
        }

        $poLinesMap = [];
        foreach ($poLines as $line) {
            $k = ((int) $line['product_id']) . ':' . (! empty($line['product_variant_id']) ? (int) $line['product_variant_id'] : 0);
            $poLinesMap[$k] = bcadd((string) $line['unit_cost'], '0', 2);
        }

        $seenKeys = [];
        $validatedUpdates = [];
        $hasAnyPriceUpdate = false;

        foreach ($priceUpdatesRaw as $index => $raw) {
            $pId = isset($raw['product_id']) ? (int) $raw['product_id'] : null;
            $vId = ! empty($raw['product_variant_id']) ? (int) $raw['product_variant_id'] : null;
            $shouldUpdate = ! empty($raw['update_prices']) && filter_var($raw['update_prices'], FILTER_VALIDATE_BOOLEAN);

            if (! $pId) {
                throw ValidationException::withMessages([
                    "price_updates.{$index}.product_id" => ['Product ID is required.'],
                ]);
            }

            $key = $pId . ':' . ($vId ?: 0);

            // 1. Duplicate check (reject with validation error, no silent deduplication)
            if (isset($seenKeys[$key])) {
                throw ValidationException::withMessages([
                    "price_updates.{$index}" => ["Duplicate price update entry for product ID {$pId}" . ($vId ? " variant ID {$vId}" : '') . '.'],
                ]);
            }
            $seenKeys[$key] = true;

            // 2. PO line presence
            if (! isset($poLinesMap[$key])) {
                throw ValidationException::withMessages([
                    "price_updates.{$index}" => ["Item (Product: {$pId}" . ($vId ? ", Variant: {$vId}" : '') . ') is not present in purchase order lines.'],
                ]);
            }

            // 3. Store isolation & hierarchy check
            $product = Product::where('id', $pId)->where('store_id', $store->id)->first();
            if (! $product) {
                throw ValidationException::withMessages([
                    "price_updates.{$index}.product_id" => ["Product {$pId} does not belong to this store."],
                ]);
            }

            $variant = null;
            if ($vId) {
                $variant = ProductVariant::where('id', $vId)->where('product_id', $pId)->first();
                if (! $variant) {
                    throw ValidationException::withMessages([
                        "price_updates.{$index}.product_variant_id" => ["Product variant {$vId} does not belong to product {$pId}."],
                    ]);
                }
            }

            if (! $shouldUpdate) {
                // If update_prices is false, skip price adjustment logic for this item
                continue;
            }

            $hasAnyPriceUpdate = true;

            // 4. Cost baseline check: only accept price updates if server baseline cost actually changed
            $baselineCost = $baselines[$key] ?? '0.00';
            $newUnitCost = $poLinesMap[$key];
            if (bccomp($newUnitCost, $baselineCost, 2) === 0) {
                throw ValidationException::withMessages([
                    "price_updates.{$index}" => ["Price update rejected: Unit cost for product {$product->name} has not changed from baseline (" . $baselineCost . ').'],
                ]);
            }

            // 5. When update_prices=true, all expected and new retail/wholesale prices are required
            if (! isset($raw['expected_retail_price']) || ! is_numeric($raw['expected_retail_price'])) {
                throw ValidationException::withMessages([
                    "price_updates.{$index}.expected_retail_price" => ['Expected retail price is required for verification.'],
                ]);
            }
            if (! isset($raw['expected_wholesale_price']) || ! is_numeric($raw['expected_wholesale_price'])) {
                throw ValidationException::withMessages([
                    "price_updates.{$index}.expected_wholesale_price" => ['Expected wholesale price is required for verification.'],
                ]);
            }
            if (! isset($raw['retail_price']) || ! is_numeric($raw['retail_price'])) {
                throw ValidationException::withMessages([
                    "price_updates.{$index}.retail_price" => ['New retail price is required and must be numeric.'],
                ]);
            }
            if (! isset($raw['wholesale_price']) || ! is_numeric($raw['wholesale_price'])) {
                throw ValidationException::withMessages([
                    "price_updates.{$index}.wholesale_price" => ['New wholesale price is required and must be numeric.'],
                ]);
            }

            $expectedRetail = bcadd((string) $raw['expected_retail_price'], '0', 2);
            $expectedWholesale = bcadd((string) $raw['expected_wholesale_price'], '0', 2);
            $newRetail = bcadd((string) $raw['retail_price'], '0', 2);
            $newWholesale = bcadd((string) $raw['wholesale_price'], '0', 2);

            // 6. Minimum & Non-negative price rejection
            if (bccomp($newRetail, '0', 2) < 0) {
                throw ValidationException::withMessages([
                    "price_updates.{$index}.retail_price" => ['Retail price cannot be negative.'],
                ]);
            }
            if (bccomp($newWholesale, '0', 2) < 0) {
                throw ValidationException::withMessages([
                    "price_updates.{$index}.wholesale_price" => ['Wholesale price cannot be negative.'],
                ]);
            }

            // 7. Wholesale <= Retail rule
            if (bccomp($newWholesale, $newRetail, 2) > 0) {
                throw ValidationException::withMessages([
                    "price_updates.{$index}.wholesale_price" => ['Wholesale price cannot exceed retail price.'],
                ]);
            }

            // 8. Selling Price >= New Cost rule (strict block: selling below new cost is prohibited)
            if (bccomp($newRetail, $newUnitCost, 2) < 0) {
                throw ValidationException::withMessages([
                    "price_updates.{$index}.retail_price" => ["Retail price ({$newRetail}) cannot be lower than new unit cost ({$newUnitCost})."],
                ]);
            }
            if (bccomp($newWholesale, $newUnitCost, 2) < 0) {
                throw ValidationException::withMessages([
                    "price_updates.{$index}.wholesale_price" => ["Wholesale price ({$newWholesale}) cannot be lower than new unit cost ({$newUnitCost})."],
                ]);
            }

            $validatedUpdates[$key] = [
                'product_id' => $pId,
                'product_variant_id' => $vId,
                'expected_retail_price' => $expectedRetail,
                'expected_wholesale_price' => $expectedWholesale,
                'retail_price' => $newRetail,
                'wholesale_price' => $newWholesale,
                'new_unit_cost' => $newUnitCost,
                'old_unit_cost' => $baselineCost,
            ];
        }

        // 9. Permission check: if any selling prices are to be updated, actor MUST have products.update
        if ($hasAnyPriceUpdate && ! $this->permissionService->can($actor, $store, 'products.update')) {
            throw ValidationException::withMessages([
                'price_updates' => ['You do not have permission to update product selling prices. Save with "Keep Current Prices" instead.'],
            ]);
        }

        return $validatedUpdates;
    }

    /**
     * Apply price updates within the single DB transaction owned by PurchaseOrderService.
     *
     * Concurrency conflict rule:
     * Uses lockForUpdate() on target product/variant, checks expected prices against current DB.
     * If DB prices changed concurrently, throws InventoryException with code 409 causing full transaction rollback.
     *
     * Audit rule:
     * Structured audit log is written ONLY if retail or wholesale price actually changed.
     *
     * @param  Store  $store
     * @param  PurchaseOrder  $po
     * @param  array  $validatedUpdates
     * @param  User  $actor
     *
     * @throws InventoryException
     */
    public function applyPriceUpdatesWithinTransaction(
        Store $store,
        PurchaseOrder $po,
        array $validatedUpdates,
        User $actor,
    ): void {
        if (empty($validatedUpdates)) {
            return;
        }

        foreach ($validatedUpdates as $update) {
            $pId = $update['product_id'];
            $vId = $update['product_variant_id'];

            if ($vId) {
                // Variant update
                $variant = ProductVariant::where('id', $vId)
                    ->where('product_id', $pId)
                    ->lockForUpdate()
                    ->first();

                if (! $variant) {
                    throw new InventoryException("Product variant #{$vId} not found during price update.");
                }

                $currentRetail = bcadd((string) ($variant->retail_price ?? '0'), '0', 2);
                $currentWholesale = bcadd((string) ($variant->wholesale_price ?? '0'), '0', 2);

                // Concurrent conflict detection
                if (
                    bccomp($currentRetail, $update['expected_retail_price'], 2) !== 0 ||
                    bccomp($currentWholesale, $update['expected_wholesale_price'], 2) !== 0
                ) {
                    throw new InventoryException(
                        "Price conflict for variant #{$variant->name}: prices were modified by another user (Current Retail: {$currentRetail}, Wholesale: {$currentWholesale}). Please review again.",
                        409
                    );
                }

                $retailChanged = bccomp($currentRetail, $update['retail_price'], 2) !== 0;
                $wholesaleChanged = bccomp($currentWholesale, $update['wholesale_price'], 2) !== 0;

                if ($retailChanged || $wholesaleChanged) {
                    $variant->update([
                        'retail_price' => $update['retail_price'],
                        'wholesale_price' => $update['wholesale_price'],
                    ]);

                    AuditLog::write(
                        storeId: $store->id,
                        action: 'product_selling_prices_updated_from_purchase',
                        entityType: 'product_variant',
                        entityId: $variant->id,
                        metadata: [
                            'po_id' => $po->id,
                            'po_number' => $po->po_number,
                            'product_id' => $pId,
                            'product_variant_id' => $variant->id,
                            'old_retail_price' => $currentRetail,
                            'new_retail_price' => $update['retail_price'],
                            'old_wholesale_price' => $currentWholesale,
                            'new_wholesale_price' => $update['wholesale_price'],
                            'old_unit_cost' => $update['old_unit_cost'],
                            'new_unit_cost' => $update['new_unit_cost'],
                            'actor_id' => $actor->id,
                            'store_id' => $store->id,
                        ],
                        actorId: $actor->id,
                    );
                }
            } else {
                // Product update
                $product = Product::where('id', $pId)
                    ->where('store_id', $store->id)
                    ->lockForUpdate()
                    ->first();

                if (! $product) {
                    throw new InventoryException("Product #{$pId} not found during price update.");
                }

                $currentRetail = bcadd((string) ($product->retail_price ?? '0'), '0', 2);
                $currentWholesale = bcadd((string) ($product->wholesale_price ?? '0'), '0', 2);

                // Concurrent conflict detection
                if (
                    bccomp($currentRetail, $update['expected_retail_price'], 2) !== 0 ||
                    bccomp($currentWholesale, $update['expected_wholesale_price'], 2) !== 0
                ) {
                    throw new InventoryException(
                        "Price conflict for product #{$product->name}: prices were modified by another user (Current Retail: {$currentRetail}, Wholesale: {$currentWholesale}). Please review again.",
                        409
                    );
                }

                $retailChanged = bccomp($currentRetail, $update['retail_price'], 2) !== 0;
                $wholesaleChanged = bccomp($currentWholesale, $update['wholesale_price'], 2) !== 0;

                if ($retailChanged || $wholesaleChanged) {
                    $product->update([
                        'retail_price' => $update['retail_price'],
                        'wholesale_price' => $update['wholesale_price'],
                    ]);

                    AuditLog::write(
                        storeId: $store->id,
                        action: 'product_selling_prices_updated_from_purchase',
                        entityType: 'product',
                        entityId: $product->id,
                        metadata: [
                            'po_id' => $po->id,
                            'po_number' => $po->po_number,
                            'product_id' => $product->id,
                            'product_variant_id' => null,
                            'old_retail_price' => $currentRetail,
                            'new_retail_price' => $update['retail_price'],
                            'old_wholesale_price' => $currentWholesale,
                            'new_wholesale_price' => $update['wholesale_price'],
                            'old_unit_cost' => $update['old_unit_cost'],
                            'new_unit_cost' => $update['new_unit_cost'],
                            'actor_id' => $actor->id,
                            'store_id' => $store->id,
                        ],
                        actorId: $actor->id,
                    );
                }
            }
        }
    }
}
