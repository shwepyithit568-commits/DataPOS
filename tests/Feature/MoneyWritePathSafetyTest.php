<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\PosSale;
use App\POS\Services\CurrencyExchangeService;
use App\POS\Services\PosSaleService;
use App\POS\Services\PromotionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Third-pass regressions: the remaining paths that wrote money to the database
 * as floats, and the ones that could run twice under concurrency.
 */
class MoneyWritePathSafetyTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;
    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'Write Path Store',
            'slug' => 'write-path-store',
            'is_active' => true,
        ]);

        $this->cashier = User::factory()->create(['role' => 'store_manager']);
        $this->cashier->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);
    }

    private function product(string $retail = '100.00', string $wholesale = '90.00'): Product
    {
        $name = 'Item ' . Str::random(3);

        return Product::create([
            'store_id'        => $this->store->id,
            'sku'             => strtoupper(Str::random(8)),
            'name'            => $name,
            'slug'            => Str::slug($name . '-' . Str::random(3)),
            'retail_price'    => $retail,
            'wholesale_price' => $wholesale,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Promotion discounts                                               */
    /* ------------------------------------------------------------------ */

    public function test_percent_discount_is_exact(): void
    {
        $promotion = Promotion::create([
            'store_id'   => $this->store->id,
            'name'       => 'Ten percent',
            'code'       => 'TEN' . Str::random(4),
            'type'       => 'percent_off',
            'value'      => '10.00',
            'is_active'  => true,
            'is_public'  => true,
            'starts_at'  => now()->subDay(),
            'expires_at' => now()->addDay(),
        ]);

        $discount = app(PromotionService::class)->calculateDiscountDecimal($promotion, '0.30');

        // 0.30 x 10% = 0.03; float maths yields 0.029999999999999995 here.
        $this->assertSame('0.03', $discount);
    }

    public function test_flat_discount_never_exceeds_the_order_total(): void
    {
        $promotion = Promotion::create([
            'store_id'   => $this->store->id,
            'name'       => 'Flat 50',
            'code'       => 'FLAT' . Str::random(4),
            'type'       => 'flat_off',
            'value'      => '50.00',
            'is_active'  => true,
            'is_public'  => true,
            'starts_at'  => now()->subDay(),
            'expires_at' => now()->addDay(),
        ]);

        $service = app(PromotionService::class);

        $this->assertSame('50.00', $service->calculateDiscountDecimal($promotion, '100.00'));
        $this->assertSame('20.00', $service->calculateDiscountDecimal($promotion, '20.00'));
    }

    public function test_promotion_money_is_cast_to_decimal_not_float(): void
    {
        $promotion = Promotion::create([
            'store_id'         => $this->store->id,
            'name'             => 'Cast check',
            'code'             => 'CAST' . Str::random(4),
            'type'             => 'flat_off',
            'value'            => '1500.25',
            'min_order_amount' => '2000.75',
            'is_active'        => true,
            'is_public'        => true,
            'starts_at'        => now()->subDay(),
            'expires_at'       => now()->addDay(),
        ]);

        $this->assertSame('1500.25', (string) $promotion->fresh()->value);
        $this->assertSame('2000.75', (string) $promotion->fresh()->min_order_amount);
    }

    /* ------------------------------------------------------------------ */
    /*  Currency conversion                                               */
    /* ------------------------------------------------------------------ */

    public function test_currency_conversion_is_exact(): void
    {
        $service = app(CurrencyExchangeService::class);
        $service->ensureDefaultCurrencies($this->store);

        // Base MMK = 1; THB is seeded by ensureDefaultCurrencies, so set its
        // rate rather than inserting a second row.
        Currency::where('store_id', $this->store->id)->where('code', 'THB')
            ->update(['exchange_rate' => '0.500000']);

        $this->assertSame('6.66', $service->convertDecimal($this->store, '3.33', 'MMK', 'THB'));
    }

    public function test_converting_to_the_same_currency_returns_the_amount(): void
    {
        $service = app(CurrencyExchangeService::class);

        $this->assertSame('12.34', $service->convertDecimal($this->store, '12.34', 'MMK', 'MMK'));
    }

    /* ------------------------------------------------------------------ */
    /*  Held sale posting                                                 */
    /* ------------------------------------------------------------------ */

    public function test_a_held_sale_cannot_be_posted_twice(): void
    {
        [$service, $held, $shift] = $this->heldSale();

        $payments = [['method' => 'cash', 'amount' => '100.00']];
        $lines = $this->heldLines($held);

        // First post consumes the held sale.
        $service->post($this->store, $lines, $payments, $this->cashier, $shift, $held);

        // Second post of the same held sale must be refused, not post again.
        $this->expectException(InventoryException::class);

        $service->post($this->store, $lines, $payments, $this->cashier, $shift, $held->fresh());
    }

    public function test_posting_a_held_sale_writes_one_set_of_payments(): void
    {
        [$service, $held, $shift] = $this->heldSale();

        $sale = $service->post(
            $this->store,
            $this->heldLines($held),
            [['method' => 'cash', 'amount' => '100.00']],
            $this->cashier,
            $shift,
            $held
        );

        $this->assertSame(1, $sale->payments()->count());
        $this->assertSame('posted', $sale->fresh()->status);
    }

    /**
     * @return array{0: PosSaleService, 1: PosSale, 2: \App\POS\Models\CashierShift}
     */
    private function heldSale(): array
    {
        $service = app(PosSaleService::class);
        $shift = app(\App\POS\Services\CashierShiftService::class)
            ->openShift($this->store, ['register_name' => 'REG-1', 'opening_cash' => '50000'], $this->cashier);

        $product = $this->product();

        app(\App\POS\Services\InventoryService::class)->postMovement([
            'store_id'              => $this->store->id,
            'product_id'            => $product->id,
            'movement_type'         => 'opening_balance',
            'quantity_delta'        => '10',
            'unit_cost'             => '80.00',
            'source_type'           => 'opening_balance',
            'client_transaction_id' => 'seed:' . Str::uuid(),
            'occurred_at'           => now(),
        ]);

        $service->addToCart($this->store, $product->id, null, '1');

        $held = $service->holdCart($this->store, $this->cashier, $shift);

        return [$service, $held, $shift];
    }

    /**
     * @return array<int, array{product_id:int, product_variant_id:?int, quantity:string}>
     */
    private function heldLines(PosSale $held): array
    {
        return $held->items->map(fn ($i) => [
            'product_id'         => $i->product_id,
            'product_variant_id' => $i->product_variant_id,
            'quantity'           => (string) $i->quantity,
        ])->all();
    }

    /* ------------------------------------------------------------------ */
    /*  Order inventory adapter                                           */
    /* ------------------------------------------------------------------ */

    public function test_duplicate_order_lines_merge_into_an_exact_quantity(): void
    {
        $product = $this->product();
        $variant = $product->variants()->create([
            'name' => 'Default',
            'sku' => 'SKU-' . Str::random(6),
            'retail_price' => '100.00',
            'wholesale_price' => '90.00',
            'is_default' => true,
        ]);

        $order = \App\Models\Order::create([
            'store_id'       => $this->store->id,
            'order_number'   => 'ORD-' . Str::random(8),
            'customer_name'  => 'Ko Aung',
            'customer_phone' => '09123456789',
            'status'         => 'pending_contact',
            'total_amount'   => '30.00',
            'contact_channel' => 'viber',
        ]);

        foreach (['0.10', '0.20'] as $qty) {
            $order->items()->create([
                'product_id'         => $product->id,
                'product_variant_id' => $variant->id,
                'product_name'       => $product->name,
                'unit_price'         => '100.00',
                'quantity'           => $qty,
                'subtotal'           => bcmul('100.00', $qty, 2),
            ]);
        }

        $adapter = app(\App\POS\Integrations\OrderInventoryAdapter::class);
        $lines = (new \ReflectionClass($adapter))
            ->getMethod('inventoryLines')
            ->invoke($adapter, $order->fresh()->load('items.product'));

        $this->assertCount(1, $lines, 'Two lines for one product/variant should merge.');
        $this->assertSame('0.300', (string) $lines[0]['quantity']);
    }

    /* ------------------------------------------------------------------ */
    /*  Bulk price write                                                  */
    /* ------------------------------------------------------------------ */

    public function test_bulk_price_update_stores_the_exact_price(): void
    {
        $product = $this->product('100.00');

        app(\App\POS\Services\BulkPriceWizardService::class)->applyBulkUpdate(
            $this->store,
            [['product_id' => $product->id, 'retail_price' => '12999.99']],
            ['sync_variants' => false]
        );

        $this->assertSame('12999.99', (string) $product->fresh()->retail_price);
    }

    public function test_bulk_price_update_sets_the_previous_price_as_compare_at(): void
    {
        $product = $this->product('15000.00');

        app(\App\POS\Services\BulkPriceWizardService::class)->applyBulkUpdate(
            $this->store,
            [['product_id' => $product->id, 'retail_price' => '12000.00']],
            ['sync_variants' => false, 'set_old_price' => true]
        );

        $fresh = $product->fresh();
        $this->assertSame('12000.00', (string) $fresh->retail_price);
        $this->assertSame('15000.00', (string) $fresh->old_price);
    }
}
