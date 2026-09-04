<?php

namespace Tests\Feature;

use App\Capabilities\Capability;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\Warehouse;
use App\POS\Models\CashierShift;
use App\POS\Models\PosSale;
use App\POS\Services\CashierShiftService;
use App\POS\Services\InventoryService;
use App\POS\Services\PosSaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase4PosDecouplingTest extends TestCase
{
    use RefreshDatabase;

    private function createStore(bool $cashierShiftsEnabled = true): Store
    {
        $store = Store::create([
            'name' => 'Decoupled Shop ' . Str::random(4),
            'slug' => 'decoupled-shop-' . Str::lower(Str::random(6)),
            'is_active' => true,
            'business_profile' => 'retail_store',
            'operation_mode' => 'omnichannel',
            'capabilities_override' => [
                Capability::OPERATIONS_CASHIER_SHIFTS => $cashierShiftsEnabled,
            ],
        ]);

        Warehouse::create([
            'store_id' => $store->id,
            'name' => 'Main Warehouse',
            'code' => 'MAIN',
            'is_default' => true,
            'is_active' => true,
        ]);

        return $store;
    }

    private function createStaff(Store $store): User
    {
        $user = User::create([
            'name' => 'Cashier ' . Str::random(4),
            'phone' => '09' . random_int(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $user->stores()->attach($store->id, [
            'role' => 'staff',
            'status' => 'active',
        ]);

        return $user;
    }

    private function createProductWithStock(Store $store, string $qty = '50.000', string $price = '15000.00'): Product
    {
        $name = 'Test Item ' . Str::random(4);
        $product = Product::create([
            'store_id' => $store->id,
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::lower(Str::random(4)),
            'sku' => 'SKU-' . Str::upper(Str::random(6)),
            'retail_price' => $price,
            'wholesale_price' => $price,
            'is_active' => true,
        ]);

        $warehouse = Warehouse::where('store_id', $store->id)->where('is_default', true)->first();
        app(InventoryService::class)->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'warehouse_id' => $warehouse->id,
            'movement_type' => 'purchase_received',
            'quantity_delta' => $qty,
            'unit_cost' => '10000.00',
            'source_type' => 'manual',
            'source_id' => 1,
            'client_transaction_id' => 'init_stock_' . $product->id,
            'occurred_at' => now(),
            'posted_by' => 1,
        ]);

        return $product;
    }

    public function test_pos_landing_accessible_and_shift_ui_isolated_when_shifts_disabled(): void
    {
        $store = $this->createStore(cashierShiftsEnabled: false);
        $staff = $this->createStaff($store);

        $response = $this->actingAs($staff)->get(route('pos.index', ['store_slug' => $store->slug]));

        $response->assertStatus(200);
        // "open register" modal must be suppressed when shifts are disabled
        $response->assertDontSee('messages.pos_open_register');
        // Register reporting tab header must be suppressed
        $response->assertDontSee('messages.pos_tab_registers');
    }

    public function test_cart_state_reports_shifts_enabled_false_and_shift_open_true_when_shifts_disabled(): void
    {
        $store = $this->createStore(cashierShiftsEnabled: false);
        $staff = $this->createStaff($store);

        $response = $this->actingAs($staff)->get(route('pos.cart-state', ['store_slug' => $store->slug]), [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'cart' => [
                'shifts_enabled' => false,
                'shift_open' => true,
            ],
        ]);
    }

    public function test_shift_and_closing_endpoints_return_403_when_shifts_capability_disabled(): void
    {
        $store = $this->createStore(cashierShiftsEnabled: false);
        $staff = $this->createStaff($store);

        // Attempt direct shift open
        $openResponse = $this->actingAs($staff)->post(route('pos.shifts.open', ['store_slug' => $store->slug]), [
            'register_name' => 'Reg 1',
            'opening_cash' => '10000.00',
        ]);
        $openResponse->assertStatus(403);

        // Attempt direct daily closing index
        $closingResponse = $this->actingAs($staff)->get(route('pos.closing.index', ['store_slug' => $store->slug]));
        $closingResponse->assertStatus(403);
    }

    public function test_sale_can_be_posted_without_open_shift_when_shifts_disabled(): void
    {
        $store = $this->createStore(cashierShiftsEnabled: false);
        $staff = $this->createStaff($store);
        $product = $this->createProductWithStock($store, '20.000', '15000.00');

        // 1. Add item to cart
        $addResponse = $this->actingAs($staff)->post(route('pos.cart.add', ['store_slug' => $store->slug]), [
            'product_id' => $product->id,
            'quantity' => '2',
        ], ['Accept' => 'application/json']);
        $addResponse->assertStatus(200);

        // 2. Post sale with cash payment (no shift exists)
        $postResponse = $this->actingAs($staff)->post(route('pos.post', ['store_slug' => $store->slug]), [
            'payments' => [
                ['method' => 'cash', 'amount' => '30000.00'],
            ],
        ]);

        $postResponse->assertSessionHas('success');
        $saleId = session('posted_sale_id');
        $this->assertNotNull($saleId);

        $sale = PosSale::find($saleId);
        $this->assertNotNull($sale);
        $this->assertSame('posted', $sale->status);
        $this->assertSame('30000.00', (string) $sale->total);
        $this->assertNull($sale->cashier_shift_id);
        $this->assertSame($staff->id, $sale->cashier_id);
    }

    public function test_held_sale_can_be_held_resumed_and_posted_without_shift_when_disabled(): void
    {
        $store = $this->createStore(cashierShiftsEnabled: false);
        $staff = $this->createStaff($store);
        $product = $this->createProductWithStock($store, '20.000', '15000.00');

        // 1. Add item to cart
        $this->actingAs($staff)->post(route('pos.cart.add', ['store_slug' => $store->slug]), [
            'product_id' => $product->id,
            'quantity' => '3',
        ], ['Accept' => 'application/json']);

        // 2. Hold cart
        $holdResponse = $this->actingAs($staff)->post(route('pos.hold', ['store_slug' => $store->slug]), [], ['Accept' => 'application/json']);
        $holdResponse->assertStatus(200);

        $heldSale = PosSale::where('store_id', $store->id)->where('status', 'held')->first();
        $this->assertNotNull($heldSale);
        $this->assertNull($heldSale->cashier_shift_id);

        // 3. Resume held sale
        $resumeResponse = $this->actingAs($staff)->post(route('pos.resume', ['store_slug' => $store->slug, 'sale' => $heldSale->id]), [], ['Accept' => 'application/json']);
        $resumeResponse->assertStatus(200);

        // 4. Post resumed sale
        $postResponse = $this->actingAs($staff)->post(route('pos.post', ['store_slug' => $store->slug]), [
            'payments' => [
                ['method' => 'cash', 'amount' => '45000.00'],
            ],
        ]);
        $postResponse->assertSessionHas('success');

        $postedSale = PosSale::find($heldSale->id);
        $this->assertSame('posted', $postedSale->status);
        $this->assertNull($postedSale->cashier_shift_id);
    }

    public function test_sale_return_can_be_posted_with_cash_without_shift_when_shifts_disabled(): void
    {
        $store = $this->createStore(cashierShiftsEnabled: false);
        $staff = $this->createStaff($store);
        $product = $this->createProductWithStock($store, '20.000', '15000.00');

        // Add to cart & post sale
        $this->actingAs($staff)->post(route('pos.cart.add', ['store_slug' => $store->slug]), [
            'product_id' => $product->id,
            'quantity' => '2',
        ], ['Accept' => 'application/json']);

        $this->actingAs($staff)->post(route('pos.post', ['store_slug' => $store->slug]), [
            'payments' => [
                ['method' => 'cash', 'amount' => '30000.00'],
            ],
        ]);

        $sale = PosSale::where('store_id', $store->id)->latest('id')->first();
        $saleItem = $sale->items->first();

        // Return 1 item with cash refund without any open shift
        $returnResponse = $this->actingAs($staff)->post(route('pos.refund.store', ['store_slug' => $store->slug, 'sale' => $sale->id]), [
            'items' => [
                ['pos_sale_item_id' => $saleItem->id, 'quantity' => '1.000'],
            ],
            'refunds' => [
                ['method' => 'cash', 'amount' => '15000.00'],
            ],
        ]);

        $returnResponse->assertSessionHas('success');
    }

    public function test_posting_sale_requires_open_shift_when_shifts_capability_enabled(): void
    {
        $store = $this->createStore(cashierShiftsEnabled: true);
        $staff = $this->createStaff($store);
        $product = $this->createProductWithStock($store, '20.000', '15000.00');

        // Add to cart
        $this->actingAs($staff)->post(route('pos.cart.add', ['store_slug' => $store->slug]), [
            'product_id' => $product->id,
            'quantity' => '1',
        ], ['Accept' => 'application/json']);

        // Post without open shift -> must be rejected with error
        $postResponse = $this->actingAs($staff)->post(route('pos.post', ['store_slug' => $store->slug]), [
            'payments' => [
                ['method' => 'cash', 'amount' => '15000.00'],
            ],
        ]);

        $postResponse->assertSessionHas('error');
        $this->assertStringContainsString('An open cashier shift is required', session('error'));
    }
}
