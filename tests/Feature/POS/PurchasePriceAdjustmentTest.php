<?php

namespace Tests\Feature\POS;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\PurchaseOrder;
use App\POS\Services\PurchaseOrderService;
use App\POS\Services\PurchasePriceAdjustmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PurchasePriceAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    private PurchaseOrderService $poService;
    private PurchasePriceAdjustmentService $adjustmentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->poService = app(PurchaseOrderService::class);
        $this->adjustmentService = app(PurchasePriceAdjustmentService::class);
    }

    private function makeStore(string $slug = 'test-store'): Store
    {
        return Store::create([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'is_active' => true,
        ]);
    }

    private function makeUser(Store $store, string $role = 'store_owner'): User
    {
        $user = User::create([
            'name' => 'User ' . Str::random(4),
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => $role,
        ]);
        $user->stores()->attach($store->id, ['role' => $role, 'status' => 'active']);

        return $user;
    }

    private function makeUserWithoutPricePermission(Store $store): User
    {
        $user = User::create([
            'name' => 'Staff ' . Str::random(4),
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'staff',
        ]);
        $user->stores()->attach($store->id, [
            'role' => 'staff',
            'status' => 'active',
            'custom_permissions' => json_encode([
                'purchases.view',
                'purchases.create',
                'purchases.update',
                '!products.update',
                '!products.edit',
            ]),
        ]);

        return $user;
    }

    private function makeProduct(Store $store, array $overrides = []): Product
    {
        $name = $overrides['name'] ?? 'Product ' . Str::random(4);

        return Product::create(array_merge([
            'store_id' => $store->id,
            'sku' => strtoupper(Str::random(8)),
            'name' => $name,
            'slug' => Str::slug($name . '-' . Str::random(4)),
            'purchase_cost' => '5000.00',
            'retail_price' => '8000.00',
            'wholesale_price' => '7000.00',
            'product_type' => 'standard',
        ], $overrides));
    }

    private function makeVariant(Product $product, array $overrides = []): ProductVariant
    {
        return ProductVariant::create(array_merge([
            'product_id' => $product->id,
            'name' => 'Variant ' . Str::random(3),
            'sku' => $product->sku . '-V1',
            'retail_price' => '8500.00',
            'wholesale_price' => '7500.00',
        ], $overrides));
    }

    /** 1. Create PO with selected product price update */
    public function test_create_po_with_selected_product_price_update(): void
    {
        $store = $this->makeStore('po-store-1');
        $user = $this->makeUser($store, 'store_owner');
        $product = $this->makeProduct($store, [
            'purchase_cost' => '5000.00',
            'retail_price' => '8000.00',
            'wholesale_price' => '7000.00',
        ]);

        $response = $this->actingAs($user)->post("/store/{$store->slug}/pos/purchases", [
            'items' => [
                ['product_id' => $product->id, 'quantity' => '10', 'unit_cost' => '6000.00'],
            ],
            'price_updates' => [
                [
                    'product_id' => $product->id,
                    'expected_retail_price' => '8000.00',
                    'expected_wholesale_price' => '7000.00',
                    'retail_price' => '9500.00',
                    'wholesale_price' => '8500.00',
                    'update_prices' => '1',
                ],
            ],
        ]);

        $response->assertRedirect();

        $product->refresh();
        $this->assertEquals('9500.00', (string) $product->retail_price);
        $this->assertEquals('8500.00', (string) $product->wholesale_price);
    }

    /** 2. Keep existing prices does not modify products */
    public function test_keep_existing_prices_does_not_modify_products(): void
    {
        $store = $this->makeStore('po-store-2');
        $user = $this->makeUser($store, 'store_owner');
        $product = $this->makeProduct($store, [
            'purchase_cost' => '5000.00',
            'retail_price' => '8000.00',
            'wholesale_price' => '7000.00',
        ]);

        $response = $this->actingAs($user)->post("/store/{$store->slug}/pos/purchases", [
            'items' => [
                ['product_id' => $product->id, 'quantity' => '10', 'unit_cost' => '6000.00'],
            ],
            'price_updates' => [
                [
                    'product_id' => $product->id,
                    'expected_retail_price' => '8000.00',
                    'expected_wholesale_price' => '7000.00',
                    'retail_price' => '9500.00',
                    'wholesale_price' => '8500.00',
                    'update_prices' => '0',
                ],
            ],
        ]);

        $response->assertRedirect();

        $product->refresh();
        $this->assertEquals('8000.00', (string) $product->retail_price);
        $this->assertEquals('7000.00', (string) $product->wholesale_price);
    }

    /** 3. Edit PO price update */
    public function test_edit_po_price_update(): void
    {
        $store = $this->makeStore('po-store-3');
        $user = $this->makeUser($store, 'store_owner');
        $product = $this->makeProduct($store, [
            'purchase_cost' => '5000.00',
            'retail_price' => '8000.00',
            'wholesale_price' => '7000.00',
        ]);

        $po = $this->poService->create($store, [
            ['product_id' => $product->id, 'quantity' => '10', 'unit_cost' => '5000.00'],
        ], null, null, null, $user);

        $response = $this->actingAs($user)->put("/store/{$store->slug}/pos/purchases/{$po->id}", [
            'items' => [
                ['product_id' => $product->id, 'quantity' => '10', 'unit_cost' => '6500.00'],
            ],
            'price_updates' => [
                [
                    'product_id' => $product->id,
                    'expected_retail_price' => '8000.00',
                    'expected_wholesale_price' => '7000.00',
                    'retail_price' => '10000.00',
                    'wholesale_price' => '9000.00',
                    'update_prices' => '1',
                ],
            ],
        ]);

        $response->assertRedirect();

        $product->refresh();
        $this->assertEquals('10000.00', (string) $product->retail_price);
        $this->assertEquals('9000.00', (string) $product->wholesale_price);
    }

    /** 4. Structured AuditLog recorded for price changes */
    public function test_structured_audit_log_recorded_for_price_changes(): void
    {
        $store = $this->makeStore('po-store-4');
        $user = $this->makeUser($store, 'store_owner');
        $product = $this->makeProduct($store, [
            'purchase_cost' => '5000.00',
            'retail_price' => '8000.00',
            'wholesale_price' => '7000.00',
        ]);

        $this->actingAs($user)->post("/store/{$store->slug}/pos/purchases", [
            'items' => [
                ['product_id' => $product->id, 'quantity' => '10', 'unit_cost' => '6000.00'],
            ],
            'price_updates' => [
                [
                    'product_id' => $product->id,
                    'expected_retail_price' => '8000.00',
                    'expected_wholesale_price' => '7000.00',
                    'retail_price' => '9500.00',
                    'wholesale_price' => '8500.00',
                    'update_prices' => '1',
                ],
            ],
        ]);

        $audit = AuditLog::where('action', 'product_selling_prices_updated_from_purchase')
            ->where('entity_id', $product->id)
            ->first();

        $this->assertNotNull($audit);
        $this->assertEquals($store->id, $audit->store_id);
        $this->assertEquals($user->id, $audit->actor_id);
        $this->assertEquals('8000.00', $audit->metadata['old_retail_price']);
        $this->assertEquals('9500.00', $audit->metadata['new_retail_price']);
        $this->assertEquals('7000.00', $audit->metadata['old_wholesale_price']);
        $this->assertEquals('8500.00', $audit->metadata['new_wholesale_price']);
        $this->assertEquals('5000.00', $audit->metadata['old_unit_cost']);
        $this->assertEquals('6000.00', $audit->metadata['new_unit_cost']);
    }

    /** 5. No price audit when prices are unchanged */
    public function test_no_price_audit_when_prices_are_unchanged(): void
    {
        $store = $this->makeStore('po-store-5');
        $user = $this->makeUser($store, 'store_owner');
        $product = $this->makeProduct($store, [
            'purchase_cost' => '5000.00',
            'retail_price' => '8000.00',
            'wholesale_price' => '7000.00',
        ]);

        $this->actingAs($user)->post("/store/{$store->slug}/pos/purchases", [
            'items' => [
                ['product_id' => $product->id, 'quantity' => '10', 'unit_cost' => '6000.00'],
            ],
            'price_updates' => [
                [
                    'product_id' => $product->id,
                    'expected_retail_price' => '8000.00',
                    'expected_wholesale_price' => '7000.00',
                    'retail_price' => '8000.00',
                    'wholesale_price' => '7000.00',
                    'update_prices' => '1',
                ],
            ],
        ]);

        $audit = AuditLog::where('action', 'product_selling_prices_updated_from_purchase')
            ->where('entity_id', $product->id)
            ->first();

        $this->assertNull($audit);
    }

    /** 6. Cross-store product rejection */
    public function test_cross_store_product_rejection(): void
    {
        $storeA = $this->makeStore('store-a');
        $storeB = $this->makeStore('store-b');
        $userA = $this->makeUser($storeA, 'store_owner');
        $productB = $this->makeProduct($storeB);

        $this->expectException(ValidationException::class);
        $baselines = $this->adjustmentService->calculateCostBaselines($storeA, [
            ['product_id' => $productB->id, 'unit_cost' => '7000.00'],
        ]);
        $this->adjustmentService->validateAndNormalizePriceUpdates($storeA, [
            [
                'product_id' => $productB->id,
                'expected_retail_price' => '8000.00',
                'expected_wholesale_price' => '7000.00',
                'retail_price' => '9000.00',
                'wholesale_price' => '8000.00',
                'update_prices' => '1',
            ],
        ], [
            ['product_id' => $productB->id, 'unit_cost' => '7000.00'],
        ], $userA, $baselines);
    }

    /** 7. Product not present in PO lines rejection */
    public function test_product_not_present_in_po_lines_rejection(): void
    {
        $store = $this->makeStore('po-store-7');
        $user = $this->makeUser($store, 'store_owner');
        $product1 = $this->makeProduct($store);
        $product2 = $this->makeProduct($store);

        $this->expectException(ValidationException::class);
        $this->adjustmentService->validateAndNormalizePriceUpdates($store, [
            [
                'product_id' => $product2->id,
                'expected_retail_price' => '8000.00',
                'expected_wholesale_price' => '7000.00',
                'retail_price' => '9000.00',
                'wholesale_price' => '8000.00',
                'update_prices' => '1',
            ],
        ], [
            ['product_id' => $product1->id, 'unit_cost' => '7000.00'],
        ], $user, []);
    }

    /** 8. ProductVariant price update */
    public function test_product_variant_price_update(): void
    {
        $store = $this->makeStore('po-store-8');
        $user = $this->makeUser($store, 'store_owner');
        $product = $this->makeProduct($store, ['purchase_cost' => '5000.00']);
        $variant = $this->makeVariant($product, [
            'retail_price' => '8500.00',
            'wholesale_price' => '7500.00',
        ]);

        $response = $this->actingAs($user)->post("/store/{$store->slug}/pos/purchases", [
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => '5',
                    'unit_cost' => '6000.00',
                ],
            ],
            'price_updates' => [
                [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'expected_retail_price' => '8500.00',
                    'expected_wholesale_price' => '7500.00',
                    'retail_price' => '11000.00',
                    'wholesale_price' => '9500.00',
                    'update_prices' => '1',
                ],
            ],
        ]);

        $response->assertRedirect();

        $variant->refresh();
        $this->assertEquals('11000.00', (string) $variant->retail_price);
        $this->assertEquals('9500.00', (string) $variant->wholesale_price);

        // Parent product price should remain unaffected
        $product->refresh();
        $this->assertEquals('8000.00', (string) $product->retail_price);
    }

    /** 9. Variant/product relationship validation */
    public function test_variant_product_relationship_validation(): void
    {
        $store = $this->makeStore('po-store-9');
        $user = $this->makeUser($store, 'store_owner');
        $product1 = $this->makeProduct($store);
        $product2 = $this->makeProduct($store);
        $variant2 = $this->makeVariant($product2);

        $this->expectException(ValidationException::class);
        $this->adjustmentService->validateAndNormalizePriceUpdates($store, [
            [
                'product_id' => $product1->id,
                'product_variant_id' => $variant2->id,
                'expected_retail_price' => '8000.00',
                'expected_wholesale_price' => '7000.00',
                'retail_price' => '9000.00',
                'wholesale_price' => '8000.00',
                'update_prices' => '1',
            ],
        ], [
            ['product_id' => $product1->id, 'product_variant_id' => $variant2->id, 'unit_cost' => '7000.00'],
        ], $user, []);
    }

    /** 10. Unauthorized user cannot update selling prices */
    public function test_unauthorized_user_cannot_update_selling_prices(): void
    {
        $store = $this->makeStore('po-store-10');
        // Staff user without products.update permission
        $user = $this->makeUserWithoutPricePermission($store);
        $product = $this->makeProduct($store, ['purchase_cost' => '5000.00']);

        $baselines = [$product->id . ':0' => '5000.00'];

        $this->expectException(ValidationException::class);
        $this->adjustmentService->validateAndNormalizePriceUpdates($store, [
            [
                'product_id' => $product->id,
                'expected_retail_price' => '8000.00',
                'expected_wholesale_price' => '7000.00',
                'retail_price' => '9000.00',
                'wholesale_price' => '8000.00',
                'update_prices' => '1',
            ],
        ], [
            ['product_id' => $product->id, 'unit_cost' => '6000.00'],
        ], $user, $baselines);
    }

    /** 11. Unauthorized user can still save PO with keep current prices */
    public function test_unauthorized_user_can_still_save_po_with_keep_current_prices(): void
    {
        $store = $this->makeStore('po-store-11');
        $user = $this->makeUserWithoutPricePermission($store);
        $product = $this->makeProduct($store, ['purchase_cost' => '5000.00']);

        $baselines = [$product->id . ':0' => '5000.00'];

        // If update_prices is false, unauthorized staff can still save PO
        $validated = $this->adjustmentService->validateAndNormalizePriceUpdates($store, [
            [
                'product_id' => $product->id,
                'expected_retail_price' => '8000.00',
                'expected_wholesale_price' => '7000.00',
                'retail_price' => '9000.00',
                'wholesale_price' => '8000.00',
                'update_prices' => '0',
            ],
        ], [
            ['product_id' => $product->id, 'unit_cost' => '6000.00'],
        ], $user, $baselines);

        $this->assertEmpty($validated);
    }

    /** 12. Invalid or negative price rejection */
    public function test_invalid_or_negative_price_rejection(): void
    {
        $store = $this->makeStore('po-store-12');
        $user = $this->makeUser($store, 'store_owner');
        $product = $this->makeProduct($store, ['purchase_cost' => '5000.00']);

        $baselines = [$product->id . ':0' => '5000.00'];

        $this->expectException(ValidationException::class);
        $this->adjustmentService->validateAndNormalizePriceUpdates($store, [
            [
                'product_id' => $product->id,
                'expected_retail_price' => '8000.00',
                'expected_wholesale_price' => '7000.00',
                'retail_price' => '-100.00',
                'wholesale_price' => '7000.00',
                'update_prices' => '1',
            ],
        ], [
            ['product_id' => $product->id, 'unit_cost' => '6000.00'],
        ], $user, $baselines);
    }

    /** 13. Wholesale greater than retail rejection */
    public function test_wholesale_greater_than_retail_rejection(): void
    {
        $store = $this->makeStore('po-store-13');
        $user = $this->makeUser($store, 'store_owner');
        $product = $this->makeProduct($store, ['purchase_cost' => '5000.00']);

        $baselines = [$product->id . ':0' => '5000.00'];

        $this->expectException(ValidationException::class);
        $this->adjustmentService->validateAndNormalizePriceUpdates($store, [
            [
                'product_id' => $product->id,
                'expected_retail_price' => '8000.00',
                'expected_wholesale_price' => '7000.00',
                'retail_price' => '8000.00',
                'wholesale_price' => '9000.00', // Wholesale > Retail!
                'update_prices' => '1',
            ],
        ], [
            ['product_id' => $product->id, 'unit_cost' => '6000.00'],
        ], $user, $baselines);
    }

    /** 14. Selling price below new cost rejection */
    public function test_selling_price_below_new_cost_rejection(): void
    {
        $store = $this->makeStore('po-store-14');
        $user = $this->makeUser($store, 'store_owner');
        $product = $this->makeProduct($store, ['purchase_cost' => '5000.00']);

        $baselines = [$product->id . ':0' => '5000.00'];

        $this->expectException(ValidationException::class);
        $this->adjustmentService->validateAndNormalizePriceUpdates($store, [
            [
                'product_id' => $product->id,
                'expected_retail_price' => '8000.00',
                'expected_wholesale_price' => '7000.00',
                'retail_price' => '5500.00', // Lower than new unit_cost (6000.00)!
                'wholesale_price' => '5000.00',
                'update_prices' => '1',
            ],
        ], [
            ['product_id' => $product->id, 'unit_cost' => '6000.00'],
        ], $user, $baselines);
    }

    /** 15. Duplicate product update entries rejected */
    public function test_duplicate_product_update_entries_rejected(): void
    {
        $store = $this->makeStore('po-store-15');
        $user = $this->makeUser($store, 'store_owner');
        $product = $this->makeProduct($store, ['purchase_cost' => '5000.00']);

        $this->expectException(ValidationException::class);
        $this->adjustmentService->validateAndNormalizePriceUpdates($store, [
            [
                'product_id' => $product->id,
                'expected_retail_price' => '8000.00',
                'expected_wholesale_price' => '7000.00',
                'retail_price' => '9000.00',
                'wholesale_price' => '8000.00',
                'update_prices' => '1',
            ],
            [
                'product_id' => $product->id,
                'expected_retail_price' => '8000.00',
                'expected_wholesale_price' => '7000.00',
                'retail_price' => '9500.00',
                'wholesale_price' => '8500.00',
                'update_prices' => '1',
            ],
        ], [
            ['product_id' => $product->id, 'unit_cost' => '6000.00'],
        ], $user, []);
    }

    /** 16. Concurrent price conflict rejection */
    public function test_concurrent_price_conflict_rejection(): void
    {
        $store = $this->makeStore('po-store-16');
        $user = $this->makeUser($store, 'store_owner');
        $product = $this->makeProduct($store, [
            'purchase_cost' => '5000.00',
            'retail_price' => '8000.00',
            'wholesale_price' => '7000.00',
        ]);

        // Another user already modified retail_price in DB to 8200.00
        $product->update(['retail_price' => '8200.00']);

        $this->expectException(InventoryException::class);
        $this->expectExceptionCode(409);

        $po = PurchaseOrder::create([
            'store_id' => $store->id,
            'po_number' => 'PO-TEST',
            'status' => 'pending',
            'subtotal' => '60000.00',
            'total_cost' => '60000.00',
            'remaining_balance' => '60000.00',
        ]);

        $this->adjustmentService->applyPriceUpdatesWithinTransaction($store, $po, [
            $product->id . ':0' => [
                'product_id' => $product->id,
                'product_variant_id' => null,
                'expected_retail_price' => '8000.00', // Stale expected price!
                'expected_wholesale_price' => '7000.00',
                'retail_price' => '9500.00',
                'wholesale_price' => '8500.00',
                'new_unit_cost' => '6000.00',
                'old_unit_cost' => '5000.00',
            ],
        ], $user);
    }

    /** 17. Transaction rollback on price update failure */
    public function test_transaction_rollback_on_price_update_failure(): void
    {
        $store = $this->makeStore('po-store-17');
        $user = $this->makeUser($store, 'store_owner');
        $product = $this->makeProduct($store, [
            'purchase_cost' => '5000.00',
            'retail_price' => '8000.00',
            'wholesale_price' => '7000.00',
        ]);

        // Pre-modify DB price to cause conflict inside transaction
        $product->update(['retail_price' => '8500.00']);

        $poCountBefore = PurchaseOrder::where('store_id', $store->id)->count();

        try {
            $this->poService->create(
                $store,
                [['product_id' => $product->id, 'quantity' => '10', 'unit_cost' => '6000.00']],
                null,
                null,
                null,
                $user,
                [],
                [],
                [
                    $product->id . ':0' => [
                        'product_id' => $product->id,
                        'product_variant_id' => null,
                        'expected_retail_price' => '8000.00', // Stale!
                        'expected_wholesale_price' => '7000.00',
                        'retail_price' => '9500.00',
                        'wholesale_price' => '8500.00',
                        'new_unit_cost' => '6000.00',
                        'old_unit_cost' => '5000.00',
                    ],
                ]
            );
            $this->fail('Expected InventoryException not thrown.');
        } catch (InventoryException $e) {
            $this->assertEquals(409, $e->getCode());
        }

        // Assert PO was completely rolled back
        $this->assertEquals($poCountBefore, PurchaseOrder::where('store_id', $store->id)->count());
    }

    /** 18. Edit PO uses DB original cost as baseline for existing, new, and removed lines */
    public function test_edit_po_uses_db_original_cost_as_baseline_for_new_and_removed_lines(): void
    {
        $store = $this->makeStore('po-store-18');
        $user = $this->makeUser($store, 'store_owner');

        $product1 = $this->makeProduct($store, ['purchase_cost' => '5000.00']);
        $product2 = $this->makeProduct($store, ['purchase_cost' => '7000.00']);

        // Create PO with product1 at unit_cost 5500.00
        $po = $this->poService->create($store, [
            ['product_id' => $product1->id, 'quantity' => '10', 'unit_cost' => '5500.00'],
        ], null, null, null, $user);

        // When editing PO:
        // Product 1 baseline should be 5500.00 (from existing PO line in DB)
        // Newly-added Product 2 baseline should be 7000.00 (from product2 purchase_cost in DB)
        $baselines = $this->adjustmentService->calculateCostBaselines($store, [
            ['product_id' => $product1->id, 'unit_cost' => '5500.00'],
            ['product_id' => $product2->id, 'unit_cost' => '7500.00'],
        ], $po);

        $this->assertEquals('5500.00', $baselines[$product1->id . ':0']);
        $this->assertEquals('7000.00', $baselines[$product2->id . ':0']);

        // Price update on product1 when unit_cost did not change from baseline (5500.00) should be rejected
        $this->expectException(ValidationException::class);
        $this->adjustmentService->validateAndNormalizePriceUpdates($store, [
            [
                'product_id' => $product1->id,
                'expected_retail_price' => '8000.00',
                'expected_wholesale_price' => '7000.00',
                'retail_price' => '9000.00',
                'wholesale_price' => '8000.00',
                'update_prices' => '1',
            ],
        ], [
            ['product_id' => $product1->id, 'unit_cost' => '5500.00'],
        ], $user, $baselines);
    }
}
