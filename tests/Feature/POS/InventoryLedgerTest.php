<?php

namespace Tests\Feature\POS;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Enums\InventoryMovementType;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\InventoryBalance;
use App\POS\Models\InventoryMovement;
use App\POS\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryLedgerTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(InventoryService::class);
    }

    private function makeStore(string $slug = 'store-a'): Store
    {
        return Store::create(['name' => ucfirst($slug), 'slug' => $slug, 'is_active' => true]);
    }

    private function makeProduct(Store $store, string $slug = 'product-a'): Product
    {
        return Product::create([
            'store_id' => $store->id,
            'sku' => 'SKU-' . strtoupper($slug),
            'name' => ucfirst(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'retail_price' => 10000,
            'wholesale_price' => 8000,
            'stock_status' => 'out_of_stock',
            'is_featured' => false,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Posting                                                            */
    /* ------------------------------------------------------------------ */

    public function test_opening_balance_posts_movement_and_updates_balance(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store);

        $movement = $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => InventoryMovementType::OpeningBalance->value,
            'quantity_delta' => 50,
            'unit_cost' => 7500,
            'source_type' => 'migration_batch',
            'source_id' => 1,
            'client_transaction_id' => 'opening-1',
        ]);

        $this->assertSame('opening_balance', $movement->movement_type);
        $this->assertSame('50.000', (string) $movement->quantity_delta);

        $this->assertSame('50.000', $this->service->totalOnHand($store->id, $product->id));

        // The balance row lives under the store's default warehouse (auto-created).
        $this->assertNotNull($store->defaultWarehouse);
        $this->assertSame('50.000', (string) $this->service->balanceFor($store->id, $product->id, null, $store->defaultWarehouse->id)->quantity_on_hand);

        // Derived stock_status cache flipped to in_stock.
        $this->assertSame('in_stock', $product->fresh()->stock_status);
    }

    public function test_pos_sale_reduces_balance(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store);

        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => 10,
            'client_transaction_id' => 'open-1',
        ]);
        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'pos_sale',
            'quantity_delta' => -3,
            'source_type' => 'sale',
            'source_id' => 77,
            'client_transaction_id' => 'sale-77',
        ]);

        $this->assertSame('7.000', $this->service->totalOnHand($store->id, $product->id));
    }

    public function test_sign_mismatch_is_rejected(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store);

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('sign mismatch');

        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'pos_sale', // must be negative
            'quantity_delta' => 5,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Negative stock                                                     */
    /* ------------------------------------------------------------------ */

    public function test_negative_stock_is_blocked_by_default(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store);

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('Insufficient stock');

        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'pos_sale',
            'quantity_delta' => -1,
            'client_transaction_id' => 'sale-neg',
        ]);
    }

    public function test_negative_stock_allowed_when_config_enabled(): void
    {
        config(['inventory.allow_negative_stock' => true]);

        $store = $this->makeStore();
        $product = $this->makeProduct($store);

        $movement = $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'pos_sale',
            'quantity_delta' => -2,
            'client_transaction_id' => 'sale-neg-allowed',
        ]);

        $this->assertSame('-2.000', (string) $movement->quantity_delta);
        $this->assertSame('-2.000', $this->service->totalOnHand($store->id, $product->id));
    }

    /* ------------------------------------------------------------------ */
    /*  Idempotency & duplicates                                           */
    /* ------------------------------------------------------------------ */

    public function test_duplicate_client_transaction_id_is_idempotent(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store);

        $data = [
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => 20,
            'client_transaction_id' => 'ctid-1',
        ];

        $first = $this->service->postMovement($data);
        $second = $this->service->postMovement($data); // offline retry

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, InventoryMovement::query()->count());
        $this->assertSame('20.000', $this->service->totalOnHand($store->id, $product->id));
    }

    public function test_same_source_line_cannot_be_posted_twice(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store);

        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => 5,
            'source_type' => 'receipt',
            'source_id' => 10,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'purchase_received',
            'quantity_delta' => 3,
            'source_type' => 'receipt',
            'source_id' => 10, // same source line
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Reversal                                                           */
    /* ------------------------------------------------------------------ */

    public function test_reversal_restores_balance(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store);

        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => 10,
            'client_transaction_id' => 'open-1',
        ]);

        $sale = $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'pos_sale',
            'quantity_delta' => -4,
            'source_type' => 'sale',
            'source_id' => 5,
            'client_transaction_id' => 'sale-5',
        ]);

        $reversal = $this->service->reverseMovement($sale, ['reason' => 'wrong quantity']);

        $this->assertSame('reversal', $reversal->movement_type);
        $this->assertSame('4.000', (string) $reversal->quantity_delta);
        $this->assertSame($sale->id, $reversal->reversal_of_id);

        $this->assertSame('10.000', $this->service->totalOnHand($store->id, $product->id));
        $this->assertSame('in_stock', $product->fresh()->stock_status);
    }

    public function test_reversal_of_reversal_is_blocked(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store);

        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => 10,
        ]);

        $sale = $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'pos_sale',
            'quantity_delta' => -2,
            'source_type' => 'sale',
            'source_id' => 1,
        ]);

        $this->service->reverseMovement($sale, ['client_transaction_id' => 'rev-1']);

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('has already been reversed');

        $this->service->reverseMovement($sale);
    }

    /* ------------------------------------------------------------------ */
    /*  Immutability                                                       */
    /* ------------------------------------------------------------------ */

    public function test_movements_cannot_be_updated_or_deleted(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store);

        $movement = $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => 5,
        ]);

        $this->expectException(InventoryException::class);
        $movement->update(['quantity_delta' => 99]);
    }

    public function test_movements_cannot_be_deleted(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store);

        $movement = $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => 5,
        ]);

        $this->expectException(InventoryException::class);
        $movement->delete();
    }

    /* ------------------------------------------------------------------ */
    /*  Online order lifecycle (reserve → confirm → cancel)                */
    /* ------------------------------------------------------------------ */

    public function test_online_reserve_confirm_cancel_flow(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store);

        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => 10,
            'client_transaction_id' => 'open-1',
        ]);

        // Reserve holds stock from available (-).
        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'online_reserve',
            'quantity_delta' => -2,
            'source_type' => 'order_reserve',
            'source_id' => 42,
            'client_transaction_id' => 'reserve-42',
        ]);
        $this->assertSame('8.000', $this->service->totalOnHand($store->id, $product->id));

        // Confirm is record-only (0) — no double deduction.
        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'online_confirm',
            'quantity_delta' => 0,
            'source_type' => 'order_confirm',
            'source_id' => 42,
            'client_transaction_id' => 'confirm-42',
        ]);
        $this->assertSame('8.000', $this->service->totalOnHand($store->id, $product->id));

        // Cancel releases the hold back (+).
        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'online_cancel',
            'quantity_delta' => 2,
            'source_type' => 'order_cancel',
            'source_id' => 42,
            'client_transaction_id' => 'cancel-42',
        ]);
        $this->assertSame('10.000', $this->service->totalOnHand($store->id, $product->id));
    }

    /* ------------------------------------------------------------------ */
    /*  Variant & warehouse keys                                           */
    /* ------------------------------------------------------------------ */

    public function test_variant_must_belong_to_product(): void
    {
        $store = $this->makeStore();
        $productA = $this->makeProduct($store, 'product-a');
        $productB = $this->makeProduct($store, 'product-b');

        $variantOfB = \App\Models\ProductVariant::create([
            'product_id' => $productB->id,
            'name' => 'Black',
            'sku' => 'SKU-B-BLACK',
            'retail_price' => 12000,
            'wholesale_price' => 9000,
        ]);

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('does not belong to product');

        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $productA->id,
            'product_variant_id' => $variantOfB->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => 3,
        ]);
    }

    public function test_balance_keys_are_unique_per_store_product_variant_warehouse(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store);

        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => 5,
            'client_transaction_id' => 'a1',
        ]);
        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => 7,
            'client_transaction_id' => 'a2',
        ]);

        $this->assertSame(1, InventoryBalance::query()->count());
        $this->assertSame('12.000', $this->service->totalOnHand($store->id, $product->id));
    }

    /* ------------------------------------------------------------------ */
    /*  Cross-store & reconcile                                            */
    /* ------------------------------------------------------------------ */

    public function test_cross_store_product_posting_is_blocked(): void
    {
        $storeA = $this->makeStore('store-a');
        $storeB = $this->makeStore('store-b');
        $productA = $this->makeProduct($storeA);

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('cross-store posting');

        $this->service->postMovement([
            'store_id' => $storeB->id,
            'product_id' => $productA->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => 3,
        ]);
    }

    public function test_reconcile_rebuilds_balances_from_movements(): void
    {
        $store = $this->makeStore();
        $productA = $this->makeProduct($store, 'product-a');
        $productB = $this->makeProduct($store, 'product-b');

        $this->service->postMovement(['store_id' => $store->id, 'product_id' => $productA->id, 'movement_type' => 'opening_balance', 'quantity_delta' => 10]);
        $this->service->postMovement(['store_id' => $store->id, 'product_id' => $productA->id, 'movement_type' => 'pos_sale', 'quantity_delta' => -4]);
        $this->service->postMovement(['store_id' => $store->id, 'product_id' => $productB->id, 'movement_type' => 'opening_balance', 'quantity_delta' => 3]);

        // Tamper the cache, then rebuild.
        InventoryBalance::query()->update(['quantity_on_hand' => 999]);

        $written = $this->service->rebuildBalances();

        $this->assertSame(2, $written);
        $this->assertSame('6.000', $this->service->totalOnHand($store->id, $productA->id));
        $this->assertSame('3.000', $this->service->totalOnHand($store->id, $productB->id));
    }

    public function test_reconcile_verify_detects_mismatch(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store);

        $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => 10,
        ]);

        // Corrupt the cache directly (simulating a stray write or drift).
        InventoryBalance::query()->update(['quantity_on_hand' => 7]);

        $result = $this->service->verifyBalances();

        $this->assertCount(1, $result['mismatches']);
        $this->assertSame('7', $result['mismatches'][0]['stored']);
        $this->assertSame('10', $result['mismatches'][0]['expected']);

        // Rebuild makes verify pass again.
        $this->service->rebuildBalances();
        $result = $this->service->verifyBalances();
        $this->assertSame([], $result['mismatches']);
    }

    public function test_online_confirm_is_record_only(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store);

        $movement = $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'online_confirm',
            'quantity_delta' => 0,
            'source_type' => 'order_confirm',
            'source_id' => 1,
            'client_transaction_id' => 'c-1',
        ]);

        $this->assertSame('0.000', (string) $movement->quantity_delta);
        $this->assertNull($this->service->balanceFor($store->id, $product->id));
    }

    public function test_posted_by_is_recorded(): void
    {
        $user = User::create([
            'name' => 'Staff',
            'phone' => '09999999999',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $store = $this->makeStore();
        $product = $this->makeProduct($store);

        $movement = $this->service->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => 5,
            'posted_by' => $user->id,
        ]);

        $this->assertSame($user->id, $movement->posted_by);
    }
}
