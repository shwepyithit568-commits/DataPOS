<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\InventoryBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryValuationTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;
    protected Store $otherStore;
    protected User $otherManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create(['name' => 'Valuation Store 1', 'slug' => 'val-store-1']);
        $this->store->setting()->create(['store_name' => 'Valuation Store 1', 'default_language' => 'en']);

        $this->manager = User::factory()->create(['name' => 'Manager Mg Mg', 'phone' => '09111222333']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);

        $this->otherStore = Store::create(['name' => 'Valuation Store 2', 'slug' => 'val-store-2']);
        $this->otherStore->setting()->create(['store_name' => 'Valuation Store 2', 'default_language' => 'en']);

        $this->otherManager = User::factory()->create(['name' => 'Other Manager', 'phone' => '09888777666']);
        $this->otherManager->stores()->attach($this->otherStore->id, ['role' => 'store_manager', 'status' => 'active']);
    }

    public function test_manager_can_access_inventory_valuation_dashboard(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/reports/inventory-valuation");

        $response->assertOk();
        $response->assertSee(__('messages.inv_val_title'));
    }

    public function test_inventory_valuation_calculates_cost_and_retail_values(): void
    {
        $category = Category::create(['store_id' => $this->store->id, 'name' => 'Displays', 'slug' => 'displays']);
        $brand = Brand::create(['store_id' => $this->store->id, 'name' => 'Dell', 'slug' => 'dell']);

        $product1 = Product::create([
            'store_id'        => $this->store->id,
            'category_id'     => $category->id,
            'brand_id'        => $brand->id,
            'name'            => 'Dell 24-inch Monitor',
            'slug'            => 'dell-24-monitor',
            'sku'             => 'MON-DELL-24',
            'retail_price'    => 350000.00,
            'wholesale_price' => 320000.00,
            'purchase_cost'   => 280000.00,
            'stock_status'    => 'in_stock',
        ]);

        InventoryBalance::create([
            'store_id'           => $this->store->id,
            'warehouse_id'       => 0,
            'product_id'         => $product1->id,
            'product_variant_id' => 0,
            'quantity_on_hand'   => 10.000,
            'unit_cost_avg'      => 280000.0000,
        ]);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/reports/inventory-valuation");

        $response->assertOk();
        // Total Cost Value = 10 * 280,000 = 2,800,000
        $response->assertSee('2,800,000');
        // Total Retail Value = 10 * 350,000 = 3,500,000
        $response->assertSee('3,500,000');
        // Potential Profit = 3,500,000 - 2,800,000 = 700,000
        $response->assertSee('700,000');
        $response->assertSee('Dell 24-inch Monitor');
        $response->assertSee('Displays');
    }

    public function test_inventory_valuation_category_and_search_filtering(): void
    {
        $catA = Category::create(['store_id' => $this->store->id, 'name' => 'Audio', 'slug' => 'audio']);
        $catB = Category::create(['store_id' => $this->store->id, 'name' => 'Cables', 'slug' => 'cables']);

        $p1 = Product::create([
            'store_id'        => $this->store->id,
            'category_id'     => $catA->id,
            'name'            => 'Wireless Earbuds',
            'slug'            => 'wireless-earbuds',
            'sku'             => 'EAR-001',
            'retail_price'    => 45000.00,
            'wholesale_price' => 40000.00,
            'purchase_cost'   => 30000.00,
            'stock_status'    => 'in_stock',
        ]);
        InventoryBalance::create([
            'store_id'           => $this->store->id,
            'warehouse_id'       => 0,
            'product_id'         => $p1->id,
            'product_variant_id' => 0,
            'quantity_on_hand'   => 5.000,
            'unit_cost_avg'      => 30000.0000,
        ]);

        $p2 = Product::create([
            'store_id'        => $this->store->id,
            'category_id'     => $catB->id,
            'name'            => 'HDMI 4K Cable',
            'slug'            => 'hdmi-cable',
            'sku'             => 'CBL-HDMI',
            'retail_price'    => 15000.00,
            'wholesale_price' => 12000.00,
            'purchase_cost'   => 8000.00,
            'stock_status'    => 'in_stock',
        ]);
        InventoryBalance::create([
            'store_id'           => $this->store->id,
            'warehouse_id'       => 0,
            'product_id'         => $p2->id,
            'product_variant_id' => 0,
            'quantity_on_hand'   => 20.000,
            'unit_cost_avg'      => 8000.0000,
        ]);

        // Filter Category A (Audio)
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/reports/inventory-valuation?category_id={$catA->id}");

        $response->assertOk();
        $response->assertSee('Wireless Earbuds');
        $response->assertDontSee('HDMI 4K Cable');

        // Search SKU
        $searchResponse = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/reports/inventory-valuation?search=CBL-HDMI");

        $searchResponse->assertOk();
        $searchResponse->assertSee('HDMI 4K Cable');
        $searchResponse->assertDontSee('Wireless Earbuds');
    }

    public function test_inventory_valuation_print_statement(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/reports/inventory-valuation/print");

        $response->assertOk();
        $response->assertSee('Print Statement');
    }

    public function test_inventory_valuation_csv_export(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/reports/inventory-valuation/export");

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_inventory_valuation_store_isolation(): void
    {
        $otherProduct = Product::create([
            'store_id'        => $this->otherStore->id,
            'name'            => 'Secret Store 2 Gadget',
            'slug'            => 'secret-gadget',
            'sku'             => 'SEC-999',
            'retail_price'    => 999999.00,
            'wholesale_price' => 900000.00,
            'purchase_cost'   => 800000.00,
            'stock_status'    => 'in_stock',
        ]);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/reports/inventory-valuation");

        $response->assertOk();
        $response->assertDontSee('Secret Store 2 Gadget');
        $response->assertDontSee('SEC-999');
    }
}
