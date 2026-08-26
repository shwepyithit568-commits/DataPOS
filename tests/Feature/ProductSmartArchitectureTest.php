<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSmartArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'Alinn Thit Store',
            'slug' => 'alinn-thit',
            'business_type' => 'mobile_tech',
        ]);
        $this->store->setting()->create(['store_name' => 'Alinn Thit', 'default_language' => 'my']);

        $this->manager = User::factory()->create(['phone' => '09777777777']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);
    }

    public function test_can_quick_create_category_with_code(): void
    {
        $response = $this->actingAs($this->manager)
            ->postJson("/store/{$this->store->slug}/admin/categories/quick-store", [
                'name' => 'Cable',
                'code' => 'cb',
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'name' => 'Cable',
            'code' => 'CB',
        ]);

        $this->assertDatabaseHas('categories', [
            'store_id' => $this->store->id,
            'name' => 'Cable',
            'code' => 'CB',
        ]);
    }

    public function test_can_quick_create_brand_with_code(): void
    {
        $response = $this->actingAs($this->manager)
            ->postJson("/store/{$this->store->slug}/admin/brands/quick-store", [
                'name' => '168 Quality',
                'code' => '168',
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'name' => '168 Quality',
            'code' => '168',
        ]);

        $this->assertDatabaseHas('brands', [
            'store_id' => $this->store->id,
            'name' => '168 Quality',
            'code' => '168',
        ]);
    }

    public function test_can_create_product_with_smart_fields(): void
    {
        $warehouse = Warehouse::create([
            'store_id' => $this->store->id,
            'name' => 'Main Warehouse',
            'code' => 'WH-01',
            'is_active' => true,
        ]);

        $category = Category::create([
            'store_id' => $this->store->id,
            'name' => 'Charger',
            'code' => 'CH',
            'slug' => 'charger',
        ]);

        $brand = Brand::create([
            'store_id' => $this->store->id,
            'name' => 'Bavinto',
            'code' => 'BVT',
            'slug' => 'bavinto',
        ]);

        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/products", [
                'name' => 'L009 Cable Black',
                'sku' => '168-L009-CB-MC-BLK',
                'product_type' => 'serialized',
                'barcode' => '8859123456789',
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'warehouse_id' => $warehouse->id,
                'shelf_location' => 'Rack-A1',
                'compatible_models' => 'iPhone 11-15, Type-C Devices',
                'retail_price' => 15000,
                'wholesale_price' => 10000,
            ]);

        $response->assertRedirect();

        $product = Product::where('sku', '168-L009-CB-MC-BLK')->firstOrFail();
        $this->assertEquals('serialized', $product->product_type);
        $this->assertEquals('8859123456789', $product->barcode);
        $this->assertEquals('Rack-A1', $product->shelf_location);
        $this->assertEquals($warehouse->id, $product->warehouse_id);
        $this->assertEquals('iPhone 11-15, Type-C Devices', $product->compatible_models);
    }

    public function test_can_update_product_smart_fields(): void
    {
        $product = Product::create([
            'store_id' => $this->store->id,
            'name' => 'Original Item',
            'sku' => 'ORIG-001',
            'slug' => 'orig-001',
            'product_type' => 'standard',
            'retail_price' => 5000,
            'wholesale_price' => 3500,
            'stock_status' => 'in_stock',
        ]);

        $response = $this->actingAs($this->manager)
            ->put("/store/{$this->store->slug}/admin/products/{$product->id}", [
                'name' => 'Updated Service Item',
                'sku' => 'SERV-001',
                'product_type' => 'service',
                'barcode' => '999888777',
                'shelf_location' => 'Service-Desk',
                'compatible_models' => 'All Androids',
                'retail_price' => 8000,
                'wholesale_price' => 5000,
            ]);

        $response->assertRedirect();

        $product->refresh();
        $this->assertEquals('Updated Service Item', $product->name);
        $this->assertEquals('SERV-001', $product->sku);
        $this->assertEquals('service', $product->product_type);
        $this->assertEquals('999888777', $product->barcode);
        $this->assertEquals('Service-Desk', $product->shelf_location);
        $this->assertEquals('All Androids', $product->compatible_models);
    }

    public function test_can_create_and_update_digital_product(): void
    {
        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/products", [
                'name' => 'Windows 11 Pro Retail Key',
                'sku' => 'WIN11-PRO-KEY',
                'product_type' => 'digital',
                'retail_price' => 25000,
                'wholesale_price' => 18000,
            ]);

        $response->assertRedirect();

        $product = Product::where('sku', 'WIN11-PRO-KEY')->firstOrFail();
        $this->assertEquals('digital', $product->product_type);

        $updateResponse = $this->actingAs($this->manager)
            ->put("/store/{$this->store->slug}/admin/products/{$product->id}", [
                'name' => 'Windows 11 Pro OEM Key',
                'sku' => 'WIN11-PRO-OEM',
                'product_type' => 'digital',
                'retail_price' => 22000,
                'wholesale_price' => 15000,
            ]);

        $updateResponse->assertRedirect();
        $product->refresh();
        $this->assertEquals('digital', $product->product_type);
        $this->assertEquals('WIN11-PRO-OEM', $product->sku);
    }

    public function test_can_manage_product_master_presets_in_master_data(): void
    {
        // 1. Create Preset via Controller
        $createResponse = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/product-master-presets", [
                'type' => 'connector_spec',
                'code' => 'tc',
                'name' => 'Type-C Cable Connector',
                'content' => 'High speed reversible Type-C',
            ]);

        $createResponse->assertRedirect();
        $this->assertDatabaseHas('product_master_presets', [
            'store_id' => $this->store->id,
            'type' => 'connector_spec',
            'code' => 'TC',
            'name' => 'Type-C Cable Connector',
        ]);

        $preset = \App\Models\ProductMasterPreset::where('store_id', $this->store->id)->where('code', 'TC')->first();

        // 2. View Master Data Hub with tab=connectors
        $hubResponse = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/products/master-data?tab=connectors");

        $hubResponse->assertOk();
        $hubResponse->assertSee('TC');
        $hubResponse->assertSee('Type-C Cable Connector');

        // 3. Update Preset
        $updateResponse = $this->actingAs($this->manager)
            ->put("/store/{$this->store->slug}/admin/product-master-presets/{$preset->id}", [
                'type' => 'connector_spec',
                'code' => 'TC-PRO',
                'name' => 'Type-C Pro 100W',
                'content' => 'Updated 100W Spec',
            ]);

        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('product_master_presets', [
            'id' => $preset->id,
            'code' => 'TC-PRO',
            'name' => 'Type-C Pro 100W',
        ]);

        // 4. Create Shelf Location and Warranty Preset
        $shelfResponse = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/product-master-presets", [
                'type' => 'shelf_location',
                'code' => 'A-01',
                'name' => 'Shelf A1 Front',
                'content' => 'Front showcase',
            ]);
        $shelfResponse->assertRedirect();

        $warrantyResponse = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/product-master-presets", [
                'type' => 'warranty',
                'name' => '6 Months Warranty',
                'content' => 'Covers manufacturing defects',
            ]);
        $warrantyResponse->assertRedirect();

        // 5. Delete Preset
        $deleteResponse = $this->actingAs($this->manager)
            ->delete("/store/{$this->store->slug}/admin/product-master-presets/{$preset->id}");

        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('product_master_presets', [
            'id' => $preset->id,
        ]);
    }
}
