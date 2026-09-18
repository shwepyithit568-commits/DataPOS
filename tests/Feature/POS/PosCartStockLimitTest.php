<?php

namespace Tests\Feature\POS;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Services\CashierShiftService;
use App\POS\Services\InventoryService;
use App\POS\Services\PosSaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The cart cannot hold more than the shelf has.
 *
 * post() already refused an over-sold bill, but only at the very end — the
 * cashier could build a cart of 11 against a shelf of 2, tell the customer the
 * total, and then hit the wall at checkout. The stepper, the typed quantity and
 * the service now stop at the same number, and the number comes from the ledger
 * (variant-aware), not from a typed figure.
 */
class PosCartStockLimitTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private User $cashier;

    private Product $product;

    private PosSaleService $sales;

    private InventoryService $inventory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sales = app(PosSaleService::class);
        $this->inventory = app(InventoryService::class);

        $this->store = Store::create(['name' => 'Limit Shop', 'slug' => 'limit-shop', 'is_active' => true]);

        $this->cashier = User::create([
            'name' => 'Cashier',
            'phone' => '09720000101',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $this->cashier->stores()->attach($this->store->id, ['role' => 'staff', 'status' => 'active']);

        $this->product = Product::create([
            'store_id' => $this->store->id,
            'name' => 'Limited Phone Case',
            'slug' => 'limited-case-' . Str::random(4),
            'sku' => 'LIM-' . Str::random(4),
            'retail_price' => 5000,
            'wholesale_price' => 4000,
        ]);

        $this->inventory->postMovement([
            'store_id' => $this->store->id,
            'product_id' => $this->product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => '2',
            'source_type' => 'opening_balance',
            'client_transaction_id' => 'seed:' . Str::uuid(),
            'occurred_at' => now(),
        ]);
    }

    public function test_adding_a_quantity_above_stock_is_refused(): void
    {
        $this->expectException(InventoryException::class);

        $this->sales->addToCart($this->store, $this->product->id, null, '3');
    }

    public function test_a_second_add_cannot_push_the_line_past_stock(): void
    {
        $this->sales->addToCart($this->store, $this->product->id, null, '2');

        // 2 + 1 would be 3 against a shelf of 2.
        $this->expectException(InventoryException::class);
        $this->sales->addToCart($this->store, $this->product->id, null, '1');
    }

    public function test_exactly_the_available_quantity_is_allowed(): void
    {
        $this->sales->addToCart($this->store, $this->product->id, null, '1');
        $this->sales->addToCart($this->store, $this->product->id, null, '1');

        $this->assertCount(1, $this->sales->cartLines($this->store));
        $this->assertSame('2.000', (string) $this->sales->cartLines($this->store)[0]['quantity']);
    }

    public function test_the_stepper_cannot_push_a_line_past_stock(): void
    {
        $this->sales->addToCart($this->store, $this->product->id, null, '2');

        $this->expectException(InventoryException::class);
        $this->sales->updateCartLine($this->store, 0, '3');
    }

    public function test_lowering_the_quantity_is_still_allowed(): void
    {
        $this->sales->addToCart($this->store, $this->product->id, null, '2');

        $this->sales->updateCartLine($this->store, 0, '1');

        // updateCartLine stores the cashier's own figure as typed.
        $this->assertSame(1.0, (float) $this->sales->cartLines($this->store)[0]['quantity']);
    }

    public function test_an_out_of_stock_product_cannot_be_added_at_all(): void
    {
        $empty = Product::create([
            'store_id' => $this->store->id,
            'name' => 'Nothing Left',
            'slug' => 'nothing-' . Str::random(4),
            'sku' => 'NON-' . Str::random(4),
            'retail_price' => 1000,
            'wholesale_price' => 900,
        ]);

        $this->expectException(InventoryException::class);
        $this->sales->addToCart($this->store, $empty->id, null, '1');
    }

    public function test_the_cart_payload_carries_the_ceiling_for_the_ui(): void
    {
        $this->sales->addToCart($this->store, $this->product->id, null, '1');
        app(CashierShiftService::class)->openShift($this->store, ['register_name' => 'REG', 'opening_cash' => '0'], $this->cashier);

        $state = $this->sales->cartState($this->store, $this->cashier);

        // The +/− cap and the typed-quantity clamp read this number.
        $this->assertSame(2.0, (float) $state['lines'][0]['balance']);
    }

    public function test_a_variant_product_is_capped_by_its_own_variant_stock(): void
    {
        $variantProduct = Product::create([
            'store_id' => $this->store->id,
            'name' => 'Two Colours',
            'slug' => 'two-colours-' . Str::random(4),
            'sku' => 'TC-' . Str::random(4),
            'retail_price' => 20000,
            'wholesale_price' => 18000,
            'product_type' => 'variant',
        ]);
        $black = $variantProduct->variants()->create(['name' => 'Black', 'sku' => 'TC-BLK', 'retail_price' => 20000, 'sort_order' => 0, 'is_default' => true]);
        $white = $variantProduct->variants()->create(['name' => 'White', 'sku' => 'TC-WHT', 'retail_price' => 20000, 'sort_order' => 1]);

        $this->inventory->postMovement([
            'store_id' => $this->store->id,
            'product_id' => $variantProduct->id,
            'product_variant_id' => $black->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => '1',
            'source_type' => 'opening_balance',
            'client_transaction_id' => 'seed:' . Str::uuid(),
            'occurred_at' => now(),
        ]);

        // White has none at all.
        try {
            $this->sales->addToCart($this->store, $variantProduct->id, $white->id, '1');
            $this->fail('a variant with no stock must not be addable');
        } catch (InventoryException $e) {
            $this->assertStringContainsString('0', $e->getMessage());
        }

        // Black takes exactly one, then stops.
        $this->sales->addToCart($this->store, $variantProduct->id, $black->id, '1');

        $this->expectException(InventoryException::class);
        $this->sales->updateCartLine($this->store, 0, '2');
    }

    public function test_a_web_order_may_hold_more_than_the_shelf_shows(): void
    {
        // A confirmed order reserves its own units, so on-hand is 0 while the
        // order still has to be handed over at the counter. The import skips the
        // cap (posting releases the reservation first), manual entry does not.
        $this->inventory->postMovement([
            'store_id' => $this->store->id,
            'product_id' => $this->product->id,
            'movement_type' => 'online_reserve',
            'quantity_delta' => '-2',
            'source_type' => 'order_reserve',
            'source_id' => 1,
            'client_transaction_id' => 'reserve-test',
            'occurred_at' => now(),
        ]);
        $this->assertSame('0.000', $this->inventory->totalOnHand($this->store->id, $this->product->id));

        // Manual entry is capped…
        try {
            $this->sales->addToCart($this->store, $this->product->id, null, '1');
            $this->fail('manual entry must respect the shelf');
        } catch (InventoryException $e) {
            $this->assertNotNull($e->getMessage());
        }

        // …while the import path is not (measured through the same guard).
        $this->sales->addToCart($this->store, $this->product->id, null, '1', enforceStock: false);
        $this->assertSame(1.0, (float) $this->sales->cartLines($this->store)[0]['quantity']);
    }
}
