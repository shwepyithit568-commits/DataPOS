<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Models\VariantPreset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_brand_and_product_crud_authorization(): void
    {
        Storage::fake('public');

        $storeA = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $storeB = Store::create(['name' => 'Store B', 'slug' => 'store-b']);

        $staffA = User::create([
            'name' => 'Staff A',
            'phone' => '09111111111',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $staffA->stores()->attach($storeA->id, ['role' => 'staff', 'status' => 'active']);

        // Create Category in Store A
        $responseCat = $this->actingAs($staffA)->post('/store/store-a/admin/categories', [
            'name' => 'Screen Protectors',
        ]);
        $responseCat->assertRedirect();
        $this->assertDatabaseHas('categories', ['name' => 'Screen Protectors', 'store_id' => $storeA->id]);

        // Unauthorized Store B access by Staff A
        $responseUnauthorized = $this->actingAs($staffA)->post('/store/store-b/admin/categories', [
            'name' => 'Hacked Category',
        ]);
        $responseUnauthorized->assertStatus(403);
    }

    public function test_retail_and_wholesale_price_visibility_rules(): void
    {
        $store = Store::create(['name' => 'Store Main', 'slug' => 'store-main']);

        $product = Product::create([
            'store_id' => $store->id,
            'sku' => 'SKU-001',
            'name' => 'Premium Glass',
            'slug' => 'premium-glass',
            'retail_price' => 5000.00,
            'wholesale_price' => 3000.00,
            'stock_status' => 'in_stock',
        ]);

        // 1. Guest / Retail Customer -> Retail Price Only
        $responseGuest = $this->get('/products?store_slug=store-main');
        $responseGuest->assertStatus(200);
        $responseGuest->assertSee('5,000');
        $responseGuest->assertDontSee('3,000');

        // 2. Approved Wholesale Customer -> Wholesale price only
        $wholesaleUser = User::create([
            'name' => 'Wholesale Buyer',
            'phone' => '09888888888',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $wholesaleUser->stores()->attach($store->id, ['role' => 'wholesale_customer', 'status' => 'active']);

        $responseWholesale = $this->actingAs($wholesaleUser)->get('/products?store_slug=store-main');
        $responseWholesale->assertStatus(200);
        $responseWholesale->assertDontSee('5,000');
        $responseWholesale->assertSee(__('messages.wholesale') . ': Ks 3,000');

        // 3. Pending Wholesale / Retail Customer -> Retail Price Only
        $pendingUser = User::create([
            'name' => 'Pending Buyer',
            'phone' => '09777777777',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $pendingUser->stores()->attach($store->id, ['role' => 'retail_customer', 'status' => 'pending']);

        $responsePending = $this->actingAs($pendingUser)->get('/products?store_slug=store-main');
        $responsePending->assertStatus(200);
        $responsePending->assertSee('5,000');
        $responsePending->assertDontSee('3,000');
    }

    public function test_retail_sale_schedule_is_hidden_from_wholesale_customers(): void
    {
        $store = Store::create(['name' => 'Store Main', 'slug' => 'store-main']);

        Product::create([
            'store_id' => $store->id,
            'sku' => 'SALE-001',
            'name' => 'Flash Sale Case',
            'slug' => 'flash-sale-case',
            'retail_price' => 40000.00,
            'old_price' => 50000.00,
            'sale_starts_at' => now()->subHour(),
            'sale_ends_at' => now()->addHour(),
            'wholesale_price' => 25000.00,
            'stock_status' => 'in_stock',
        ]);

        $responseRetail = $this->get('/products?store_slug=store-main');
        $responseRetail->assertStatus(200);
        $responseRetail->assertSee('40,000');
        $responseRetail->assertSee('50,000');
        $responseRetail->assertSee('-20%');
        $responseRetail->assertDontSee(__('messages.wholesale') . ': Ks 25,000');

        $wholesaleUser = User::create([
            'name' => 'Wholesale Buyer',
            'phone' => '09888888888',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $wholesaleUser->stores()->attach($store->id, ['role' => 'wholesale_customer', 'status' => 'active']);

        $responseWholesale = $this->actingAs($wholesaleUser)->get('/products?store_slug=store-main');
        $responseWholesale->assertStatus(200);
        $responseWholesale->assertSee(__('messages.wholesale') . ': Ks 25,000');
        $responseWholesale->assertDontSee('40,000');
        $responseWholesale->assertDontSee('50,000');
        $responseWholesale->assertDontSee('-20%');
    }

    public function test_admin_product_search_is_scoped_to_own_store(): void
    {
        $storeA = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $storeB = Store::create(['name' => 'Store B', 'slug' => 'store-b']);

        // Create products with overlapping search terms in both stores
        Product::create([
            'store_id' => $storeA->id,
            'sku' => 'A-IPHONE',
            'name' => 'iPhone 14 Screen',
            'slug' => 'iphone-14-screen-a',
            'retail_price' => 45000.00,
            'wholesale_price' => 38000.00,
            'stock_status' => 'in_stock',
        ]);
        Product::create([
            'store_id' => $storeB->id,
            'sku' => 'B-IPHONE',
            'name' => 'iPhone 14 Screen',
            'slug' => 'iphone-14-screen-b',
            'retail_price' => 50000.00,
            'wholesale_price' => 42000.00,
            'stock_status' => 'in_stock',
        ]);
        // Store A has an out-of-stock product that should only appear in stock filter
        Product::create([
            'store_id' => $storeA->id,
            'sku' => 'A-OUT',
            'name' => 'Old Model Glass',
            'slug' => 'old-model-glass',
            'retail_price' => 10000.00,
            'wholesale_price' => 8000.00,
            'stock_status' => 'out_of_stock',
        ]);

        $staffA = User::create([
            'name' => 'Staff A',
            'phone' => '09111111111',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $staffA->stores()->attach($storeA->id, ['role' => 'staff', 'status' => 'active']);

        // 1. Search by product name — only Store A products returned
        $response = $this->actingAs($staffA)->get('/store/store-a/admin/products?search=iPhone');
        $response->assertStatus(200);
        $response->assertSee('iPhone 14 Screen');
        $response->assertSee('A-IPHONE');
        // Store B product must NOT appear
        $response->assertDontSee('B-IPHONE');

        // 2. Search by SKU — only Store A product returned
        $responseSku = $this->actingAs($staffA)->get('/store/store-a/admin/products?search=A-IPHONE');
        $responseSku->assertStatus(200);
        $responseSku->assertSee('A-IPHONE');
        $responseSku->assertDontSee('B-IPHONE');

        // 3. Filter by stock_status — only Store A products
        $responseStock = $this->actingAs($staffA)->get('/store/store-a/admin/products?stock_status=out_of_stock');
        $responseStock->assertStatus(200);
        $responseStock->assertSee('Old Model Glass');
        $responseStock->assertDontSee('B-IPHONE');
        $responseStock->assertDontSee('A-IPHONE');

        // 4. Combined search + stock filter
        $responseCombined = $this->actingAs($staffA)->get('/store/store-a/admin/products?search=iPhone&stock_status=in_stock');
        $responseCombined->assertStatus(200);
        $responseCombined->assertSee('A-IPHONE');
        $responseCombined->assertDontSee('B-IPHONE');
        $responseCombined->assertDontSee('A-OUT');
    }

    public function test_admin_can_update_category_with_store_isolation(): void
    {
        $storeA = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $storeB = Store::create(['name' => 'Store B', 'slug' => 'store-b']);

        $managerA = User::create([
            'name' => 'Manager A',
            'phone' => '09111111111',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $managerA->stores()->attach($storeA->id, ['role' => 'store_manager', 'status' => 'active']);

        $managerB = User::create([
            'name' => 'Manager B',
            'phone' => '09222222222',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $managerB->stores()->attach($storeB->id, ['role' => 'store_manager', 'status' => 'active']);

        // Create category in Store A
        $categoryA = \App\Models\Category::create([
            'store_id' => $storeA->id,
            'name' => 'Original Name',
            'slug' => 'original-name',
        ]);

        // Manager A can edit own category
        $editResponse = $this->actingAs($managerA)
            ->get("/store/store-a/admin/categories/{$categoryA->id}/edit");
        $editResponse->assertStatus(200);
        $editResponse->assertSee('Original Name');

        // Manager A can update own category
        $updateResponse = $this->actingAs($managerA)
            ->put("/store/store-a/admin/categories/{$categoryA->id}", [
                'name' => 'Updated Name',
                'description' => 'New description',
            ]);
        $updateResponse->assertRedirect('/store/store-a/admin/categories');
        $this->assertDatabaseHas('categories', [
            'id' => $categoryA->id,
            'name' => 'Updated Name',
            'slug' => 'updated-name',
        ]);

        // Manager B cannot edit Store A category
        $crossEditResponse = $this->actingAs($managerB)
            ->get("/store/store-b/admin/categories/{$categoryA->id}/edit");
        $crossEditResponse->assertStatus(403);

        // Manager B cannot update Store A category
        $crossUpdateResponse = $this->actingAs($managerB)
            ->put("/store/store-b/admin/categories/{$categoryA->id}", [
                'name' => 'Hacked Name',
            ]);
        $crossUpdateResponse->assertStatus(403);
    }

    public function test_admin_can_update_brand_with_store_isolation(): void
    {
        $storeA = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $storeB = Store::create(['name' => 'Store B', 'slug' => 'store-b']);

        $managerA = User::create([
            'name' => 'Manager A',
            'phone' => '09111111111',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $managerA->stores()->attach($storeA->id, ['role' => 'store_manager', 'status' => 'active']);

        $managerB = User::create([
            'name' => 'Manager B',
            'phone' => '09222222222',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $managerB->stores()->attach($storeB->id, ['role' => 'store_manager', 'status' => 'active']);

        // Create brand in Store A
        $brandA = \App\Models\Brand::create([
            'store_id' => $storeA->id,
            'name' => 'Original Brand',
            'slug' => 'original-brand',
        ]);

        // Manager A can edit own brand
        $editResponse = $this->actingAs($managerA)
            ->get("/store/store-a/admin/brands/{$brandA->id}/edit");
        $editResponse->assertStatus(200);
        $editResponse->assertSee('Original Brand');

        // Manager A can update own brand
        $updateResponse = $this->actingAs($managerA)
            ->put("/store/store-a/admin/brands/{$brandA->id}", [
                'name' => 'Updated Brand',
            ]);
        $updateResponse->assertRedirect('/store/store-a/admin/brands');
        $this->assertDatabaseHas('brands', [
            'id' => $brandA->id,
            'name' => 'Updated Brand',
            'slug' => 'updated-brand',
        ]);

        // Manager B cannot edit Store A brand
        $crossEditResponse = $this->actingAs($managerB)
            ->get("/store/store-b/admin/brands/{$brandA->id}/edit");
        $crossEditResponse->assertStatus(403);

        // Manager B cannot update Store A brand
        $crossUpdateResponse = $this->actingAs($managerB)
            ->put("/store/store-b/admin/brands/{$brandA->id}", [
                'name' => 'Hacked Brand',
            ]);
        $crossUpdateResponse->assertStatus(403);
    }

    public function test_featured_product_toggle_with_store_isolation(): void
    {
        $storeA = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $storeB = Store::create(['name' => 'Store B', 'slug' => 'store-b']);

        $managerA = User::create([
            'name' => 'Manager A',
            'phone' => '09111111111',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $managerA->stores()->attach($storeA->id, ['role' => 'store_manager', 'status' => 'active']);

        $managerB = User::create([
            'name' => 'Manager B',
            'phone' => '09222222222',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $managerB->stores()->attach($storeB->id, ['role' => 'store_manager', 'status' => 'active']);

        // Create product in Store A
        $productA = \App\Models\Product::create([
            'store_id' => $storeA->id,
            'sku' => 'FEA-001',
            'name' => 'Featured Test',
            'slug' => 'featured-test',
            'retail_price' => 10000.00,
            'wholesale_price' => 7000.00,
            'stock_status' => 'in_stock',
            'is_featured' => false,
        ]);

        // Create product in Store B
        $productB = \App\Models\Product::create([
            'store_id' => $storeB->id,
            'sku' => 'FEA-002',
            'name' => 'Not Featured',
            'slug' => 'not-featured',
            'retail_price' => 5000.00,
            'wholesale_price' => 3000.00,
            'stock_status' => 'in_stock',
            'is_featured' => false,
        ]);

        // Manager A toggles own product to featured
        $toggleResponse = $this->actingAs($managerA)
            ->post("/store/store-a/admin/products/{$productA->id}/toggle-featured");
        $toggleResponse->assertRedirect();
        $this->assertDatabaseHas('products', [
            'id' => $productA->id,
            'is_featured' => true,
        ]);

        // Toggle again to unfeature
        $toggleBackResponse = $this->actingAs($managerA)
            ->post("/store/store-a/admin/products/{$productA->id}/toggle-featured");
        $toggleBackResponse->assertRedirect();
        $this->assertDatabaseHas('products', [
            'id' => $productA->id,
            'is_featured' => false,
        ]);

        // Manager B cannot toggle Store A product
        $crossToggleResponse = $this->actingAs($managerB)
            ->post("/store/store-b/admin/products/{$productA->id}/toggle-featured");
        $crossToggleResponse->assertStatus(403);

        // Manager A cannot toggle Store B product
        $crossToggleResponse2 = $this->actingAs($managerA)
            ->post("/store/store-a/admin/products/{$productB->id}/toggle-featured");
        $crossToggleResponse2->assertStatus(403);
    }

    public function test_product_image_upload_and_validation(): void
    {
        Storage::fake('public');

        $store = Store::create(['name' => 'Store Main', 'slug' => 'store-main']);
        $manager = User::create([
            'name' => 'Manager',
            'phone' => '09555555555',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

        $productImage = UploadedFile::fake()->create('glass.jpg', 50, 'image/jpeg');

        $response = $this->actingAs($manager)->post('/store/store-main/admin/products', [
            'name' => '9D Glass',
            'sku' => 'GLASS-9D',
            'retail_price' => 2500.00,
            'wholesale_price' => 1500.00,
            'stock_status' => 'in_stock',
            'image' => $productImage,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('products', [
            'sku' => 'GLASS-9D',
            'store_id' => $store->id,
        ]);
    }

    public function test_admin_can_manage_variant_presets_and_product_form_lists_them(): void
    {
        $store = Store::create(['name' => 'Store Main', 'slug' => 'store-main']);
        $otherStore = Store::create(['name' => 'Other Store', 'slug' => 'other-store']);

        $manager = User::create([
            'name' => 'Manager',
            'phone' => '09666666666',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

        $response = $this->actingAs($manager)->post('/store/store-main/admin/variant-presets', [
            'name' => 'Mobile Storage',
            'category_family' => 'mobile',
            'sort_order' => 1,
            'options' => [
                [
                    'name' => '128GB',
                    'sku_suffix' => '128',
                    'retail_price_adjustment' => 0,
                    'wholesale_price_adjustment' => 0,
                    'stock_status' => 'in_stock',
                ],
                [
                    'name' => '256GB',
                    'sku_suffix' => '256',
                    'retail_price_adjustment' => 150000,
                    'wholesale_price_adjustment' => 120000,
                    'stock_status' => 'in_stock',
                ],
            ],
        ]);

        $response->assertRedirect('/store/store-main/admin/variant-presets');
        $this->assertDatabaseHas('variant_presets', [
            'store_id' => $store->id,
            'name' => 'Mobile Storage',
            'category_family' => 'mobile',
        ]);
        $preset = VariantPreset::where('store_id', $store->id)->where('name', 'Mobile Storage')->firstOrFail();

        VariantPreset::create([
            'store_id' => $otherStore->id,
            'name' => 'Hidden Other Store Preset',
            'options' => [['name' => 'Other', 'sku_suffix' => 'OTH', 'stock_status' => 'in_stock']],
        ]);

        $form = $this->actingAs($manager)->get('/store/store-main/admin/products/create');

        $form->assertStatus(200);
        $form->assertSee('Preset 1');
        $form->assertSee('Preset 2 (optional)');
        $form->assertSee('Mobile Storage');
        $form->assertSee('Apply Preset');
        $form->assertSee('Generate Combinations');
        $form->assertDontSee('Hidden Other Store Preset');

        $settings = $this->actingAs($manager)->get('/store/store-main/admin/variant-presets');
        $settings->assertStatus(200);
        $settings->assertSee('Duplicate');
        $settings->assertSee('Move Up');
        $settings->assertSee('Move Down');
        $settings->assertSee('View Rows');
        $settings->assertSee('Mobile');

        $duplicate = $this->actingAs($manager)
            ->post("/store/store-main/admin/variant-presets/{$preset->id}/duplicate");
        $duplicate->assertRedirect();
        $this->assertDatabaseHas('variant_presets', [
            'store_id' => $store->id,
            'name' => 'Mobile Storage Copy',
            'category_family' => 'mobile',
        ]);

        $copy = VariantPreset::where('store_id', $store->id)->where('name', 'Mobile Storage Copy')->firstOrFail();
        $oldPresetSort = $preset->refresh()->sort_order;
        $oldCopySort = $copy->sort_order;

        $move = $this->actingAs($manager)
            ->patch("/store/store-main/admin/variant-presets/{$copy->id}/move", [
                'direction' => 'up',
            ]);
        $move->assertRedirect('/store/store-main/admin/variant-presets');
        $this->assertSame($oldPresetSort, $copy->refresh()->sort_order);
        $this->assertSame($oldCopySort, $preset->refresh()->sort_order);
    }

    public function test_admin_can_save_more_than_ten_generated_variants(): void
    {
        $store = Store::create(['name' => 'Store Main', 'slug' => 'store-main']);
        $manager = User::create([
            'name' => 'Manager',
            'phone' => '09666666667',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

        $variants = collect(range(1, 16))->map(fn (int $number): array => [
            'name' => 'Variant ' . $number,
            'sku' => 'COMBO-' . $number,
            'retail_price' => 100000 + $number,
            'wholesale_price' => 90000 + $number,
            'stock_status' => 'in_stock',
            'is_default' => $number === 1 ? '1' : '0',
        ])->all();

        $response = $this->actingAs($manager)->post('/store/store-main/admin/products', [
            'name' => 'Combination Product',
            'sku' => 'COMBO-PRODUCT',
            'retail_price' => 100000,
            'wholesale_price' => 90000,
            'stock_status' => 'in_stock',
            'variants' => $variants,
        ]);

        $response->assertRedirect('/store/store-main/admin/products');

        $product = Product::where('sku', 'COMBO-PRODUCT')->firstOrFail();
        $this->assertCount(16, $product->variants);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'sku' => 'COMBO-16',
        ]);
    }

    public function test_variant_attributes_are_saved_and_storefront_groups_selector(): void
    {
        $store = Store::create(['name' => 'Store Main', 'slug' => 'store-main']);
        $manager = User::create([
            'name' => 'Manager',
            'phone' => '09666666668',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

        $variants = [
            [
                'name' => '256GB / Black',
                'attributes' => [
                    ['label' => 'Mobile Storage', 'value' => '256GB'],
                    ['label' => 'Phone Color', 'value' => 'Black'],
                ],
                'sku' => 'PHONE-256-BK',
                'retail_price' => 1900000,
                'wholesale_price' => 1800000,
                'stock_status' => 'in_stock',
                'is_default' => '1',
            ],
            [
                'name' => '512GB / Black',
                'attributes' => [
                    ['label' => 'Mobile Storage', 'value' => '512GB'],
                    ['label' => 'Phone Color', 'value' => 'Black'],
                ],
                'sku' => 'PHONE-512-BK',
                'retail_price' => 2100000,
                'wholesale_price' => 1980000,
                'stock_status' => 'out_of_stock',
                'is_default' => '0',
            ],
        ];

        $this->actingAs($manager)->post('/store/store-main/admin/products', [
            'name' => 'Structured Variant Phone',
            'sku' => 'PHONE-STRUCT',
            'retail_price' => 1900000,
            'wholesale_price' => 1800000,
            'stock_status' => 'in_stock',
            'variants' => $variants,
        ])->assertRedirect('/store/store-main/admin/products');

        $product = Product::where('sku', 'PHONE-STRUCT')->firstOrFail();
        $variant = $product->variants()->where('sku', 'PHONE-256-BK')->firstOrFail();
        $this->assertEquals([
            ['label' => 'Mobile Storage', 'value' => '256GB'],
            ['label' => 'Phone Color', 'value' => 'Black'],
        ], $variant->attributes);

        // Storefront page renders the attribute labels (grouped selector data)
        $page = $this->get('/store/store-main/product/' . $product->slug);
        $page->assertOk();
        $page->assertSee('Mobile Storage');
        $page->assertSee('Phone Color');
    }

    public function test_duplicate_variant_names_are_rejected(): void
    {
        $store = Store::create(['name' => 'Store Main', 'slug' => 'store-main']);
        $manager = User::create([
            'name' => 'Manager',
            'phone' => '09666666669',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

        $variants = [
            ['name' => 'Black', 'sku' => 'PH-DUP-1', 'retail_price' => 1000, 'wholesale_price' => 900, 'stock_status' => 'in_stock', 'is_default' => '1'],
            ['name' => 'Black', 'sku' => 'PH-DUP-2', 'retail_price' => 1100, 'wholesale_price' => 950, 'stock_status' => 'in_stock', 'is_default' => '0'],
        ];

        $this->actingAs($manager)->post('/store/store-main/admin/products', [
            'name' => 'Dup Name Phone',
            'sku' => 'PH-DUP',
            'retail_price' => 1000,
            'wholesale_price' => 900,
            'stock_status' => 'in_stock',
            'variants' => $variants,
        ])->assertSessionHasErrors('variants');

        $this->assertDatabaseMissing('products', ['sku' => 'PH-DUP']);
    }

    public function test_admin_can_upload_variant_image(): void
    {
        Storage::fake('public');
        $store = Store::create(['name' => 'Store Main', 'slug' => 'store-main']);
        $manager = User::create([
            'name' => 'Manager',
            'phone' => '09666666670',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

        $this->actingAs($manager)->post('/store/store-main/admin/products', [
            'name' => 'Image Variant Phone',
            'sku' => 'PH-IMG',
            'retail_price' => 1000,
            'wholesale_price' => 900,
            'stock_status' => 'in_stock',
            'variants' => [
                [
                    'name' => 'Black',
                    'sku' => 'PH-IMG-BK',
                    'retail_price' => 1000,
                    'wholesale_price' => 900,
                    'stock_status' => 'in_stock',
                    'is_default' => '1',
                    'image' => UploadedFile::fake()->image('variant-black.jpg'),
                ],
            ],
        ])->assertRedirect('/store/store-main/admin/products');

        $product = Product::where('sku', 'PH-IMG')->firstOrFail();
        $variant = $product->variants()->firstOrFail();
        $this->assertNotNull($variant->image_path);
        Storage::disk('public')->assertExists($variant->image_path);
    }

    public function test_out_of_stock_product_detail_shows_viber_ask_button(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $store->setting()->create([
            'store_name' => 'Store A',
            'viber_number' => '959892499955',
            'telegram_username' => 'store_support',
        ]);
        $product = Product::create([
            'store_id' => $store->id,
            'sku' => 'OUT-1',
            'name' => 'Rare Glass',
            'slug' => 'rare-glass',
            'retail_price' => 5000,
            'wholesale_price' => 3000,
            'stock_status' => 'out_of_stock',
        ]);

        $response = $this->get('/store/store-a/product/rare-glass?store_slug=store-a');

        $response->assertStatus(200);
        // Out-of-stock ask buttons render with the store Viber/Telegram.
        $response->assertSee('viber://chat?number=959892499955', false);
        $response->assertSee('https://t.me/store_support', false);
        // Updated label: "ask when back in stock" (replaces old "ask about stock")
        $response->assertSee(__('messages.ask_when_back_in_stock'));
        // "Get Viber" not-installed fallback next to the ask button.
        $response->assertSee(__('messages.viber_missing'));
        $response->assertSee('href="https://www.viber.com/download/"', false);
        // No add-to-order hook for out-of-stock items.
        $response->assertDontSee('orderBuilder.addItem', false);
    }

    public function test_in_stock_product_direct_order_box_shows_viber_get_fallback(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $store->setting()->create([
            'store_name' => 'Store A',
            'viber_number' => '959892499955',
            'telegram_username' => 'store_support',
        ]);
        $product = Product::create([
            'store_id' => $store->id,
            'sku' => 'DIRECT-1',
            'name' => 'Direct Glass',
            'slug' => 'direct-glass',
            'retail_price' => 8000,
            'wholesale_price' => 5000,
            'stock_status' => 'in_stock',
        ]);

        $response = $this->get('/store/store-a/product/direct-glass?store_slug=store-a');

        $response->assertStatus(200);
        // Direct-order box renders the Viber modal trigger button + Get Viber fallback.
        $response->assertSee(__('messages.direct_order'));
        // Viber button now opens the modal (no direct viber:// link in HTML)
        $response->assertSee('openViberModal()', false);
        $response->assertSee(__('messages.open_viber'));
        $response->assertSee(__('messages.viber_missing'));
        $response->assertSee('href="https://www.viber.com/download/"', false);
    }

    // ---- Viber flow audit: helper consolidation + modal + label tests ----

    public function test_product_detail_viber_modal_renders_with_correct_buttons(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $store->setting()->create([
            'store_name' => 'Store A',
            'viber_number' => '959892499955',
            'phone' => '09899101720',
        ]);
        $product = Product::create([
            'store_id' => $store->id,
            'sku' => 'MODAL-1',
            'name' => 'Modal Test Product',
            'slug' => 'modal-test',
            'retail_price' => 10000,
            'wholesale_price' => 7000,
            'stock_status' => 'in_stock',
        ]);

        $response = $this->get('/store/store-a/product/modal-test?store_slug=store-a');

        $response->assertStatus(200);
        // Modal buttons exist (not direct Viber link anymore)
        $response->assertSee(__('messages.open_viber'));
        $response->assertSee(__('messages.copy_message'));
        $response->assertSee(__('messages.call_phone'));
        $response->assertSee(__('messages.close'));
        // Modal preview exists
        $response->assertSee(__('messages.viber_order_preview'));
        // Direct Viber link replaced by button with @click="openViberModal()"
        $response->assertSee('openViberModal()', false);
        // The Direct Order box itself has no direct viber:// <a> link (button opens modal)
        $response->assertSee('openViberModal()');
        // Phone number for tel: link (renders inside modal too if phone is set)
        $response->assertSee('tel:09899101720', false);
    }

    public function test_product_detail_viber_modal_uses_helper_not_raw_concat(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $store->setting()->create([
            'store_name' => 'Store A',
            'viber_number' => '959892499955',
        ]);
        $product = Product::create([
            'store_id' => $store->id,
            'sku' => 'HDR-1',
            'name' => 'Header Test',
            'slug' => 'header-test',
            'retail_price' => 5000,
            'wholesale_price' => 3000,
            'stock_status' => 'in_stock',
        ]);

        $response = $this->get('/store/store-a/product/header-test?store_slug=store-a');

        $response->assertStatus(200);
        // Alpine should use window.alinnViber helper, not raw string concat
        $response->assertSee('window.alinnViber', false);
        // Server-rendered no-JS fallback must use the canonical helper
        $response->assertSee('viber://chat?number=959892499955', false);
    }

    public function test_header_viber_link_has_ios_href(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $store->setting()->create([
            'store_name' => 'Store A',
            'viber_number' => '959892499955',
            'phone' => '09123456789',
        ]);
        $product = Product::create([
            'store_id' => $store->id,
            'sku' => 'HDR-TEST',
            'name' => 'Header Test',
            'slug' => 'header-test',
            'retail_price' => 5000,
            'wholesale_price' => 3000,
            'stock_status' => 'in_stock',
        ]);

        $response = $this->get('/store/store-a/product/header-test?store_slug=store-a');

        $response->assertStatus(200);
        // Header Viber link should have data-ios-href for iOS devices
        $response->assertSee('data-ios-href="viber://contact?number=%2B959892499955"', false);
    }

    public function test_out_of_stock_shows_ask_when_back_in_stock_label(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $store->setting()->create([
            'store_name' => 'Store A',
            'viber_number' => '959892499955',
        ]);
        $product = Product::create([
            'store_id' => $store->id,
            'sku' => 'OOS-1',
            'name' => 'Out of Stock Item',
            'slug' => 'oos-item',
            'retail_price' => 5000,
            'wholesale_price' => 3000,
            'stock_status' => 'out_of_stock',
        ]);

        $response = $this->get('/store/store-a/product/oos-item?store_slug=store-a');

        $response->assertStatus(200);
        // Out-of-stock should show "ask when back in stock" (not the old "ask about stock")
        $response->assertSee(__('messages.ask_when_back_in_stock'));
        // Should still have Viber button for asking
        $response->assertSee('viber://chat?number=959892499955', false);
    }

    public function test_share_menu_has_viber_share_label(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $store->setting()->create([
            'store_name' => 'Store A',
            'viber_number' => '959892499955',
        ]);
        $product = Product::create([
            'store_id' => $store->id,
            'sku' => 'SHARE-1',
            'name' => 'Share Test',
            'slug' => 'share-test',
            'retail_price' => 5000,
            'wholesale_price' => 3000,
            'stock_status' => 'in_stock',
        ]);

        $response = $this->get('/store/store-a/product/share-test?store_slug=store-a');

        $response->assertStatus(200);
        // Share menu Viber item should use the localized label
        $response->assertSee(__('messages.share_via_viber'));
    }

    public function test_order_confirmation_uses_helper_and_has_ios_href(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $store->setting()->create([
            'store_name' => 'Store A',
            'viber_number' => '959892499955',
            'phone' => '09123456789',
        ]);
        $user = User::factory()->create();
        $this->actingAs($user);

        $p1 = Product::create([
            'store_id' => $store->id,
            'sku' => 'ORD-1',
            'name' => 'Order Item',
            'slug' => 'order-item',
            'retail_price' => 10000,
            'wholesale_price' => 5000,
            'stock_status' => 'in_stock',
        ]);

        $itemsJson = json_encode([
            ['product_id' => $p1->id, 'quantity' => 2, 'price' => 10000, 'name' => 'Order Item', 'sku' => 'ORD-1'],
        ]);

        $response = $this->post('/store/store-a/orders', [
            'items_json' => $itemsJson,
            'customer_name' => 'Test User',
            'customer_phone' => '09123456789',
            'customer_address' => 'Yangon',
            'contact_channel' => 'viber',
        ]);

        $response->assertRedirect();
        // Follow redirect to confirmation page
        $confirmationUrl = $response->headers->get('Location');
        $this->assertNotNull($confirmationUrl);

        $response2 = $this->get($confirmationUrl);
        $response2->assertStatus(200);
        // Confirmation page should have the normalized Viber URL
        $response2->assertSee('viber://chat?number=959892499955', false);
        // Should have iOS data-ios-href
        $response2->assertSee('data-ios-href="viber://contact?number=%2B959892499955"', false);
    }

    public function test_localization_keys_exist_in_all_three_locales(): void
    {
        $keys = [
            'share_via_viber', 'viber_order_modal_title', 'viber_order_preview',
            'copy_message', 'message_copied', 'message_copied_hint',
            'open_viber', 'call_phone', 'select_variant_first',
            'ask_when_back_in_stock', 'unit_price', 'total_price', 'close',
        ];

        foreach (['en', 'my', 'zh_CN'] as $locale) {
            foreach ($keys as $key) {
                $translation = __($key, [], $locale);
                $this->assertNotEmpty(
                    $translation,
                    "Missing translation for '{$key}' in locale '{$locale}'"
                );
            }
        }
    }

    public function test_live_search_suggestions_are_scoped_to_store(): void
    {
        $storeA = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $storeB = Store::create(['name' => 'Store B', 'slug' => 'store-b']);

        Product::create([
            'store_id' => $storeA->id,
            'sku' => 'A-GLASS',
            'name' => 'iPhone 14 Tempered Glass',
            'slug' => 'iphone-14-tg',
            'retail_price' => 5000.00,
            'old_price' => 6000.00,
            'wholesale_price' => 4000.00,
            'stock_status' => 'in_stock',
        ]);
        Product::create([
            'store_id' => $storeA->id,
            'sku' => 'A-OLD',
            'name' => 'iPhone Old Glass',
            'slug' => 'iphone-old-glass',
            'retail_price' => 1000.00,
            'wholesale_price' => 700.00,
            'stock_status' => 'out_of_stock',
        ]);
        Product::create([
            'store_id' => $storeB->id,
            'sku' => 'B-GLASS',
            'name' => 'iPhone 14 Tempered Glass',
            'slug' => 'iphone-14-tg-b',
            'retail_price' => 7000.00,
            'wholesale_price' => 6000.00,
            'stock_status' => 'in_stock',
        ]);

        // Matches by name/SKU within Store A only; in-stock results come first.
        $response = $this->getJson('/products/suggestions?store_slug=store-a&search=iphone');
        $response->assertOk();
        $response->assertJsonCount(2, 'products');
        $response->assertJsonPath('products.0.name', 'iPhone 14 Tempered Glass');
        $response->assertJsonPath('products.0.price', 'Ks 5,000');
        $response->assertJsonPath('products.0.old_price', 'Ks 6,000');
        $response->assertJsonPath('products.0.url', url('/store/store-a/product/iphone-14-tg'));
        // Store B's product URL must not leak into Store A's suggestions.
        $response->assertDontSee('iphone-14-tg-b');

        // Store B sees only its own product.
        $responseB = $this->getJson('/products/suggestions?store_slug=store-b&search=iphone');
        $responseB->assertOk();
        $responseB->assertJsonCount(1, 'products');

        // SKU search works too.
        $responseSku = $this->getJson('/products/suggestions?store_slug=store-a&search=A-OLD');
        $responseSku->assertOk();
        $responseSku->assertJsonCount(1, 'products');
        $responseSku->assertJsonPath('products.0.name', 'iPhone Old Glass');

        // Empty query → empty list (no error).
        $this->getJson('/products/suggestions?store_slug=store-a&search=')->assertOk()->assertJsonCount(0, 'products');

        // No matches → empty list.
        $this->getJson('/products/suggestions?store_slug=store-a&search=zzzzz')->assertOk()->assertJsonCount(0, 'products');

        // Unknown store → 404.
        $this->getJson('/products/suggestions?store_slug=nope&search=iphone')->assertNotFound();
    }

    public function test_live_search_suggestions_include_categories_and_brands(): void
    {
        $store = Store::create(['name' => 'Store', 'slug' => 'store-main']);

        $glassCat = Category::create(['store_id' => $store->id, 'name' => 'Glass Screen Protector', 'slug' => 'glass-screen-protector', 'icon' => '📱']);
        $caseCat = Category::create(['store_id' => $store->id, 'name' => 'Phone Case', 'slug' => 'phone-case']);
        $brand = Brand::create(['store_id' => $store->id, 'name' => 'Nillkin', 'slug' => 'nillkin']);

        // Categories/brands are only suggested when they actually carry products.
        Product::create([
            'store_id' => $store->id,
            'category_id' => $glassCat->id,
            'brand_id' => $brand->id,
            'name' => 'Glass A',
            'slug' => 'glass-a',
            'sku' => 'GLA-001',
            'retail_price' => 5000,
            'wholesale_price' => 4000,
            'stock_status' => 'in_stock',
        ]);
        Product::create([
            'store_id' => $store->id,
            'category_id' => $caseCat->id,
            'name' => 'Case B',
            'slug' => 'case-b',
            'sku' => 'CASE-001',
            'retail_price' => 3000,
            'wholesale_price' => 2000,
            'stock_status' => 'in_stock',
        ]);

        $response = $this->getJson('/products/suggestions?store_slug=store-main&search=glass');
        $response->assertOk();
        $response->assertJsonCount(1, 'categories');
        $response->assertJsonPath('categories.0.name', 'Glass Screen Protector');
        $response->assertJsonPath('categories.0.count', 1);
        $response->assertJsonPath('categories.0.url', url('/products?category_id=' . $glassCat->id . '&store_slug=store-main'));
        // The matching product is also suggested.
        $response->assertJsonPath('products.0.name', 'Glass A');
        // Phone Case carries products but its name does not match the query.
        $response->assertJsonCount(0, 'brands');

        $responseBrand = $this->getJson('/products/suggestions?store_slug=store-main&search=nill');
        $responseBrand->assertOk();
        $responseBrand->assertJsonCount(1, 'brands');
        $responseBrand->assertJsonPath('brands.0.name', 'Nillkin');
        $responseBrand->assertJsonPath('brands.0.count', 1);
        $responseBrand->assertJsonPath('brands.0.url', url('/products?brand_id=' . $brand->id . '&store_slug=store-main'));

        // No matches → all three sections empty.
        $empty = $this->getJson('/products/suggestions?store_slug=store-main&search=zzzz');
        $empty->assertOk()->assertJsonCount(0, 'categories');
        $empty->assertJsonCount(0, 'brands');
        $empty->assertJsonCount(0, 'products');
    }

    public function test_live_search_trending_chips_return_popular_categories_and_brands(): void
    {
        $store = Store::create(['name' => 'Store', 'slug' => 'store-main']);

        $topCat = Category::create(['store_id' => $store->id, 'name' => 'Top Category', 'slug' => 'top-category']);
        $smallCat = Category::create(['store_id' => $store->id, 'name' => 'Small Category', 'slug' => 'small-category']);
        $emptyCat = Category::create(['store_id' => $store->id, 'name' => 'Empty Category', 'slug' => 'empty-category']);
        $topBrand = Brand::create(['store_id' => $store->id, 'name' => 'Top Brand', 'slug' => 'top-brand']);
        $emptyBrand = Brand::create(['store_id' => $store->id, 'name' => 'Empty Brand', 'slug' => 'empty-brand']);

        // Top Category gets two products, Small Category one, Top Brand one.
        foreach ([$topCat, $topCat, $smallCat] as $i => $cat) {
            Product::create([
                'store_id' => $store->id,
                'category_id' => $cat->id,
                'name' => 'Item ' . $i,
                'slug' => 'item-' . $i,
                'sku' => 'SKU-' . $i,
                'retail_price' => 1000,
                'wholesale_price' => 800,
                'stock_status' => 'in_stock',
            ]);
        }
        Product::create([
            'store_id' => $store->id,
            'brand_id' => $topBrand->id,
            'name' => 'Brand Item',
            'slug' => 'brand-item',
            'sku' => 'SKU-B',
            'retail_price' => 2000,
            'wholesale_price' => 1500,
            'stock_status' => 'in_stock',
        ]);

        // Empty search → trending chips (categories first, then brands, by product count).
        $response = $this->getJson('/products/suggestions?store_slug=store-main&search=');
        $response->assertOk();
        $response->assertJsonCount(0, 'categories');
        $response->assertJsonCount(0, 'brands');
        $response->assertJsonCount(0, 'products');
        $response->assertJsonCount(3, 'trending');
        $response->assertJsonPath('trending.0.label', 'Top Category');
        $response->assertJsonPath('trending.0.type', 'category');
        $response->assertJsonPath('trending.1.label', 'Small Category');
        $response->assertJsonPath('trending.2.label', 'Top Brand');
        $response->assertJsonPath('trending.2.type', 'brand');
        // Categories/brands without products are never suggested as trending.
        $response->assertDontSee('Empty Category');
        $response->assertDontSee('Empty Brand');

        // A non-empty search does not return trending (only matching sections).
        $withSearch = $this->getJson('/products/suggestions?store_slug=store-main&search=item');
        $withSearch->assertOk()->assertJsonCount(0, 'trending');
        $withSearch->assertJsonCount(4, 'products');
    }

    public function test_parent_category_filter_returns_child_products(): void
    {
        $store = Store::create(['name' => 'Store', 'slug' => 'store-main']);

        $sparePart = Category::create(['store_id' => $store->id, 'name' => 'Spare Part', 'slug' => 'spare-part']);
        $touch = Category::create(['store_id' => $store->id, 'name' => 'TouchLCD', 'slug' => 'touch-lcd', 'parent_id' => $sparePart->id]);
        $other = Category::create(['store_id' => $store->id, 'name' => 'Cable', 'slug' => 'cable']);

        Product::create([
            'store_id' => $store->id,
            'category_id' => $touch->id,
            'name' => 'Child Item Screen',
            'slug' => 'child-item-screen',
            'sku' => 'CHILD-001',
            'retail_price' => 30000,
            'wholesale_price' => 25000,
            'stock_status' => 'in_stock',
        ]);
        Product::create([
            'store_id' => $store->id,
            'category_id' => $other->id,
            'name' => 'Unrelated Cable',
            'slug' => 'unrelated-cable',
            'sku' => 'OTHER-001',
            'retail_price' => 5000,
            'wholesale_price' => 4000,
            'stock_status' => 'in_stock',
        ]);

        // Filtering by the parent category must include the child's product
        // but not products from unrelated categories.
        $response = $this->get('/products?store_slug=store-main&category_id=' . $sparePart->id);
        $response->assertStatus(200);
        $response->assertSee('Child Item Screen');
        $response->assertDontSee('Unrelated Cable');

        // Filtering by the child category itself still works.
        $responseChild = $this->get('/products?store_slug=store-main&category_id=' . $touch->id);
        $responseChild->assertStatus(200);
        $responseChild->assertSee('Child Item Screen');
        $responseChild->assertDontSee('Unrelated Cable');
    }
}
