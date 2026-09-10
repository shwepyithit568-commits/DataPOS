<?php

namespace Tests\Feature\Storefront;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use App\POS\Enums\InventoryMovementType;
use App\POS\Services\InventoryService;
use App\ViewModels\Storefront\ProductCardViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontStockStatusSyncTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'Tech Store',
            'slug' => 'tech-store',
        ]);
        $this->store->setting()->create([
            'store_name' => 'Tech Store',
            'default_language' => 'my',
        ]);

        $this->manager = User::factory()->create(['phone' => '09987654321']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);
    }

    public function test_product_with_zero_stock_on_hand_reports_out_of_stock(): void
    {
        $product = Product::create([
            'store_id' => $this->store->id,
            'name' => 'Zero Stock Phone',
            'slug' => 'zero-stock-phone',
            'sku' => 'SKU-ZERO-01',
            'retail_price' => 50000,
            'wholesale_price' => 45000,
            'stock_status' => 'in_stock',
            'is_ecommerce' => true,
        ]);

        // Inbound: 2 units
        app(InventoryService::class)->postMovement([
            'store_id' => $this->store->id,
            'product_id' => $product->id,
            'movement_type' => InventoryMovementType::OpeningBalance->value,
            'quantity_delta' => '2',
            'occurred_at' => now(),
        ]);

        $this->assertTrue($product->refresh()->isInStock());

        // Outbound: -2 units (exhausted)
        app(InventoryService::class)->postMovement([
            'store_id' => $this->store->id,
            'product_id' => $product->id,
            'movement_type' => InventoryMovementType::PosSale->value,
            'quantity_delta' => '-2',
            'occurred_at' => now(),
        ]);

        $product->refresh();

        // Model evaluation
        $this->assertFalse($product->isInStock());

        // ViewModel evaluation
        $vm = new ProductCardViewModel($product, $this->store);
        $this->assertTrue($vm->isOutOfStock());
    }

    public function test_storefront_catalog_renders_out_of_stock_badge_for_zero_inventory_product(): void
    {
        $product = Product::create([
            'store_id' => $this->store->id,
            'name' => 'Out of Stock Case',
            'slug' => 'out-of-stock-case',
            'sku' => 'SKU-CASE-01',
            'retail_price' => 15000,
            'wholesale_price' => 12000,
            'stock_status' => 'out_of_stock',
            'is_ecommerce' => true,
        ]);

        $response = $this->get("/products?store_slug={$this->store->slug}");
        $response->assertOk();
        $response->assertSee(__('messages.out_of_stock'));
        // Quick add to cart button must NOT be rendered for out of stock product
        $response->assertDontSee("\$store.orderBuilder.addItem({ id: {$product->id}");
    }

    public function test_duplicated_product_starts_as_out_of_stock(): void
    {
        $original = Product::create([
            'store_id' => $this->store->id,
            'name' => 'In Stock Original',
            'slug' => 'in-stock-original',
            'sku' => 'ORIG-001',
            'retail_price' => 20000,
            'wholesale_price' => 18000,
            'stock_status' => 'in_stock',
            'product_type' => 'standard',
        ]);

        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/products/{$original->id}/duplicate");

        $response->assertRedirect();

        $copy = Product::where('store_id', $this->store->id)
            ->where('sku', 'ORIG-001-copy')
            ->firstOrFail();

        $this->assertEquals('out_of_stock', $copy->stock_status);
        $this->assertFalse($copy->isInStock());
    }

    public function test_sync_command_synchronizes_exhausted_product(): void
    {
        $product = Product::create([
            'store_id' => $this->store->id,
            'name' => 'Unsynced Zero Stock Item',
            'slug' => 'unsynced-zero-stock-item',
            'sku' => 'UNSYNC-001',
            'retail_price' => 25000,
            'wholesale_price' => 20000,
            'stock_status' => 'in_stock', // stale
            'product_type' => 'standard',
        ]);

        $this->artisan('inventory:sync-stock-status', ['--store' => $this->store->id])
            ->assertSuccessful();

        $product->refresh();
        $this->assertEquals('out_of_stock', $product->stock_status);
        $this->assertFalse($product->isInStock());
    }
}
