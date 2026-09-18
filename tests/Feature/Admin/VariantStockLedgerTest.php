<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Models\VariantPreset;
use App\POS\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Variant stock lives in the ledger.
 *
 * The product form used to save the quantity typed on a variant row into
 * `product_variants.quantity_on_hand` and nothing else, so a shop could create
 * "Black × 3", read 3 in the form, and still be unable to sell it: the POS and
 * every stock report read `inventory_balances`, which stayed empty. Sales then
 * never decremented the row either, so the two numbers drifted apart.
 */
class VariantStockLedgerTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private User $manager;

    private InventoryService $inventory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create(['name' => 'Variant Shop', 'slug' => 'variant-shop', 'is_active' => true]);
        $this->inventory = app(InventoryService::class);

        $this->manager = User::create([
            'name' => 'Manager',
            'phone' => '09710000101',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);
    }

    private function productPayload(array $variants, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Colour Phone ' . Str::random(4),
            'sku' => 'VP-' . Str::upper(Str::random(5)),
            'product_type' => 'variant',
            'retail_price' => '100000',
            'wholesale_price' => '90000',
            'purchase_cost' => '70000',
            'stock_status' => 'in_stock',
            'is_ecommerce' => '1',
            'is_taxable' => '1',
            'variants' => $variants,
        ], $overrides);
    }

    private function variantRows(array $quantities): array
    {
        $rows = [];
        foreach ($quantities as $i => [$name, $qty]) {
            $rows[] = [
                'name' => $name,
                'sku' => 'VP-' . strtoupper(substr($name, 0, 3)),
                'retail_price' => '100000',
                'wholesale_price' => '90000',
                'stock_status' => 'in_stock',
                'quantity_on_hand' => (string) $qty,
                'is_default' => $i === 0 ? '1' : '0',
                'attributes' => [['label' => 'Color', 'value' => $name]],
            ];
        }

        return $rows;
    }

    private function createProduct(array $payload): Product
    {
        $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/products", $payload)
            ->assertRedirect();

        return Product::where('store_id', $this->store->id)->latest('id')->firstOrFail();
    }

    /* ------------------------------------------------------------------ */

    public function test_variant_quantities_typed_on_the_form_become_real_stock(): void
    {
        $product = $this->createProduct($this->productPayload(
            $this->variantRows([['Black', 3], ['Blue', 2]])
        ));

        $black = $product->variants()->where('name', 'Black')->sole();
        $blue = $product->variants()->where('name', 'Blue')->sole();

        // The ledger — what the POS sells from and the reports count — has it.
        $this->assertSame('3.000', $this->inventory->totalOnHand($this->store->id, $product->id, $black->id));
        $this->assertSame('2.000', $this->inventory->totalOnHand($this->store->id, $product->id, $blue->id));

        $this->assertDatabaseHas('inventory_movements', [
            'store_id' => $this->store->id,
            'product_id' => $product->id,
            'product_variant_id' => $black->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => '3.000',
            'source_type' => 'product_create',
        ]);

        // Valued at the product's purchase cost, so COGS is right from day one.
        $this->assertSame(
            '70000.0000',
            (string) \App\POS\Models\InventoryBalance::where('store_id', $this->store->id)
                ->where('product_variant_id', $black->id)->value('unit_cost_avg')
        );
    }

    public function test_a_variant_product_does_not_also_open_a_product_level_balance(): void
    {
        $product = $this->createProduct($this->productPayload(
            $this->variantRows([['Black', 2]]),
            ['initial_stock' => '5'],
        ));

        // Otherwise the same goods would exist twice: at variant level (sellable)
        // and at product level (where nothing can sell them).
        $this->assertFalse(
            \App\POS\Models\InventoryBalance::where('store_id', $this->store->id)
                ->where('product_id', $product->id)->where('product_variant_id', 0)->exists(),
            'no product-level balance row (nothing could sell from it)'
        );
        $this->assertSame('2.000', $this->inventory->totalOnHand($this->store->id, $product->id));
        $this->assertSame('2.000', $this->inventory->totalOnHand($this->store->id, $product->id, $product->variants()->sole()->id));
    }

    public function test_editing_a_variant_quantity_does_not_move_stock(): void
    {
        $product = $this->createProduct($this->productPayload(
            $this->variantRows([['Black', 3]])
        ));
        $black = $product->variants()->sole();
        $movementsBefore = \App\POS\Models\InventoryMovement::where('product_id', $product->id)->count();

        $this->actingAs($this->manager)->put("/store/{$this->store->slug}/admin/products/{$product->id}", $this->productPayload([
            [
                'id' => $black->id,
                'name' => 'Black',
                'sku' => $black->sku,
                'retail_price' => '100000',
                'wholesale_price' => '90000',
                'stock_status' => 'in_stock',
                'quantity_on_hand' => '99',
                'is_default' => '1',
                'attributes' => [['label' => 'Color', 'value' => 'Black']],
            ],
        ]))->assertRedirect();

        // Stock is ledger-owned: retyping a figure must not rewrite what the
        // shop owns.
        $this->assertSame('3.000', $this->inventory->totalOnHand($this->store->id, $product->id, $black->id));
        $this->assertSame($movementsBefore, \App\POS\Models\InventoryMovement::where('product_id', $product->id)->count());
    }

    public function test_the_edit_page_shows_the_ledger_quantity(): void
    {
        $product = $this->createProduct($this->productPayload(
            $this->variantRows([['Black', 3]])
        ));
        $black = $product->variants()->sole();

        // Sell one → ledger 2 (the mirror column still says 3).
        $this->inventory->postMovement([
            'store_id' => $this->store->id,
            'product_id' => $product->id,
            'product_variant_id' => $black->id,
            'movement_type' => 'pos_sale',
            'quantity_delta' => '-1',
            'source_type' => 'pos_sale',
            'client_transaction_id' => 'test-sale:' . $product->id,
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($this->manager)->get("/store/{$this->store->slug}/admin/products/{$product->id}/edit");

        $response->assertOk();
        $response->assertSee('readonly', false);           // not editable any more
        $this->assertSame('2.000', $this->inventory->totalOnHand($this->store->id, $product->id, $black->id));
        // The rendered Alpine rows carry the ledger figure, not the stale 3.
        // (Blade escapes json_encode's quotes in the attribute.)
        $response->assertSee('quantity_on_hand&quot;:2', false);
    }

    public function test_preset_options_still_drive_the_variant_rows(): void
    {
        VariantPreset::create([
            'store_id' => $this->store->id,
            'name' => 'Color',
            'category_family' => 'accessories',
            'options' => [
                ['name' => 'Black', 'sku_suffix' => 'BLK', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                ['name' => 'Blue', 'sku_suffix' => 'BLU', 'retail_price_adjustment' => 5000, 'wholesale_price_adjustment' => 4000, 'stock_status' => 'in_stock'],
            ],
            'sort_order' => 1,
        ]);

        $product = $this->createProduct($this->productPayload($this->variantRows([['Black', 1], ['Blue', 1]])));

        $this->assertSame(2, $product->variants()->count());
        $this->assertSame('1.000', $this->inventory->totalOnHand($this->store->id, $product->id, $product->variants()->where('name', 'Blue')->sole()->id));
    }
}
