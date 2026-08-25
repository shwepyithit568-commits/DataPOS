<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Enums\InventoryMovementType;
use App\POS\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoStockStatusFromInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'Auto Stock Store',
            'slug' => 'auto-stock-store',
        ]);
        $this->store->setting()->create(['store_name' => 'Auto Stock Store', 'default_language' => 'en']);

        $this->manager = User::factory()->create(['phone' => '09123456789']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);
    }

    public function test_product_created_with_initial_stock_is_automatically_in_stock(): void
    {
        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/products", [
                'name' => 'In Stock Test Item',
                'sku' => 'SKU-INSTOCK-01',
                'retail_price' => 10000,
                'wholesale_price' => 8000,
                'initial_stock' => 15,
                'purchase_cost' => 6000,
            ]);

        $response->assertRedirect();

        $product = Product::where('store_id', $this->store->id)->where('sku', 'SKU-INSTOCK-01')->first();
        $this->assertNotNull($product);
        $this->assertEquals('in_stock', $product->stock_status);
        $this->assertEquals(15.0, $product->stock_on_hand);
    }

    public function test_product_created_without_initial_stock_defaults_to_out_of_stock(): void
    {
        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/products", [
                'name' => 'Zero Stock Test Item',
                'sku' => 'SKU-NOSTOCK-01',
                'retail_price' => 10000,
                'wholesale_price' => 8000,
                'initial_stock' => 0,
            ]);

        $response->assertRedirect();

        $product = Product::where('store_id', $this->store->id)->where('sku', 'SKU-NOSTOCK-01')->first();
        $this->assertNotNull($product);
        $this->assertEquals('out_of_stock', $product->stock_status);
        $this->assertEquals(0.0, $product->stock_on_hand);
    }

    public function test_updating_product_automatically_derives_stock_status_from_inventory_balance(): void
    {
        $product = Product::create([
            'store_id' => $this->store->id,
            'name' => 'Existing Stock Product',
            'slug' => 'existing-stock-product',
            'sku' => 'SKU-EXIST-01',
            'retail_price' => 5000,
            'wholesale_price' => 4000,
            'stock_status' => 'out_of_stock',
        ]);

        // Post 10 items into inventory
        app(InventoryService::class)->postMovement([
            'store_id' => $this->store->id,
            'product_id' => $product->id,
            'movement_type' => InventoryMovementType::OpeningBalance->value,
            'quantity_delta' => '10',
            'unit_cost' => '3000',
            'source_type' => 'test',
            'source_id' => 1,
            'occurred_at' => now(),
        ]);

        $product->refresh();
        $this->assertEquals('in_stock', $product->stock_status);
        $this->assertEquals(10.0, $product->stock_on_hand);

        // Edit the product through the controller without passing stock_status
        $response = $this->actingAs($this->manager)
            ->put("/store/{$this->store->slug}/admin/products/{$product->id}", [
                'name' => 'Existing Stock Product Renamed',
                'sku' => 'SKU-EXIST-01',
                'retail_price' => 6000,
                'wholesale_price' => 4500,
            ]);

        $response->assertRedirect();

        $product->refresh();
        $this->assertEquals('in_stock', $product->stock_status);
        $this->assertEquals('Existing Stock Product Renamed', $product->name);
    }

    public function test_inventory_sale_movement_flips_stock_status_to_out_of_stock_when_exhausted(): void
    {
        $product = Product::create([
            'store_id' => $this->store->id,
            'name' => 'Stock Depletion Item',
            'slug' => 'stock-depletion-item',
            'sku' => 'SKU-DEPLETE-01',
            'retail_price' => 2000,
            'wholesale_price' => 1500,
            'stock_status' => 'out_of_stock',
        ]);

        // Inbound: 2 units
        app(InventoryService::class)->postMovement([
            'store_id' => $this->store->id,
            'product_id' => $product->id,
            'movement_type' => InventoryMovementType::PurchaseReceived->value,
            'quantity_delta' => '2',
            'unit_cost' => '1000',
            'source_type' => 'test',
            'source_id' => 2,
            'occurred_at' => now(),
        ]);

        $product->refresh();
        $this->assertEquals('in_stock', $product->stock_status);

        // Outbound (Sale): -2 units
        app(InventoryService::class)->postMovement([
            'store_id' => $this->store->id,
            'product_id' => $product->id,
            'movement_type' => InventoryMovementType::PosSale->value,
            'quantity_delta' => '-2',
            'source_type' => 'test',
            'source_id' => 3,
            'occurred_at' => now(),
        ]);

        $product->refresh();
        $this->assertEquals('out_of_stock', $product->stock_status);
        $this->assertEquals(0.0, $product->stock_on_hand);
    }
}
