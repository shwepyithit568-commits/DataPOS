<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\GlassFinderItem;
use App\Models\HomeBanner;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Store;
use App\Models\User;
use App\Models\WholesaleApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BusinessWorkflowAuditTest extends TestCase
{
    use RefreshDatabase;

    protected Store $storeA;
    protected Store $storeB;
    protected User $adminA;
    protected User $adminB;
    protected Category $categoryA;
    protected Brand $brandA;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->storeA = Store::create(['name' => 'DataPOS A', 'slug' => 'alinn-thit-a', 'domain' => 'storea.test']);
        $this->storeB = Store::create(['name' => 'DataPOS B', 'slug' => 'alinn-thit-b', 'domain' => 'storeb.test']);

        $this->adminA = User::factory()->create(['phone' => '09111111111']);
        $this->adminA->stores()->attach($this->storeA->id, ['role' => 'store_manager']);

        $this->adminB = User::factory()->create(['phone' => '09222222222']);
        $this->adminB->stores()->attach($this->storeB->id, ['role' => 'store_manager']);

        $this->categoryA = Category::create(['store_id' => $this->storeA->id, 'name' => 'Cases & Covers', 'slug' => 'cases-covers']);
        $this->brandA = Brand::create(['store_id' => $this->storeA->id, 'name' => 'Anker', 'slug' => 'anker']);
    }

    /**
     * Scenario 1 & 2: Product Creation, Editing, Frontend Display, Multi-Image Upload, Primary Selection & Deletion
     */
    public function test_scenario_1_and_2_product_and_image_gallery_workflow(): void
    {
        // 1. Create Product
        $createResponse = $this->actingAs($this->adminA)
            ->post("/store/{$this->storeA->slug}/admin/products", [
                'name' => 'Premium Glass Guard',
                'sku' => 'GG-100',
                'category_id' => $this->categoryA->id,
                'brand_id' => $this->brandA->id,
                'retail_price' => 15000,
                'wholesale_price' => 10000,
                'stock_status' => 'in_stock',
                'warranty' => '6 Months Replacement',
                'return_policy' => '7 Days Return',
                'description' => '9H Hardness Tempered Glass',
                'is_featured' => 1,
            ]);

        $createResponse->assertRedirect("/store/{$this->storeA->slug}/admin/products");
        $product = Product::where('sku', 'GG-100')->firstOrFail();

        // 2. Upload 4 Images
        $img1 = UploadedFile::fake()->create('img1.jpg', 100, 'image/jpeg');
        $img2 = UploadedFile::fake()->create('img2.jpg', 100, 'image/jpeg');
        $img3 = UploadedFile::fake()->create('img3.jpg', 100, 'image/jpeg');
        $img4 = UploadedFile::fake()->create('img4.jpg', 100, 'image/jpeg');

        $uploadResponse = $this->actingAs($this->adminA)
            ->post("/store/{$this->storeA->slug}/admin/products/{$product->id}/images", [
                'images' => [$img1, $img2, $img3, $img4],
            ]);

        $uploadResponse->assertRedirect();
        $this->assertCount(4, $product->fresh()->images);

        $galleryImages = $product->fresh()->images;
        $secondImage = $galleryImages[1];

        // 3. Set Primary Image
        $primaryResponse = $this->actingAs($this->adminA)
            ->post("/store/{$this->storeA->slug}/admin/products/{$product->id}/images/{$secondImage->id}/primary");

        $primaryResponse->assertRedirect();
        $this->assertTrue($secondImage->fresh()->is_primary);
        $this->assertEquals($secondImage->image_path, $product->fresh()->image_path);

        // 4. Delete Image
        $firstImage = $galleryImages[0];
        $deleteImgResponse = $this->actingAs($this->adminA)
            ->delete("/store/{$this->storeA->slug}/admin/products/{$product->id}/images/{$firstImage->id}");

        $deleteImgResponse->assertRedirect();
        $this->assertDatabaseMissing('product_images', ['id' => $firstImage->id]);
        $this->assertCount(3, $product->fresh()->images);

        // 5. Verify Storefront Product Page
        $storefrontResponse = $this->get("/store/{$this->storeA->slug}/product/{$product->slug}");
        $storefrontResponse->assertStatus(200);
        $storefrontResponse->assertSee('Premium Glass Guard');
        $storefrontResponse->assertSee('15,000');
    }

    /**
     * Scenario 3: Order Operation Workflow (Customer creates -> Admin verifies & updates status)
     */
    public function test_scenario_3_order_operation_lifecycle(): void
    {
        $product = Product::create([
            'store_id' => $this->storeA->id,
            'name' => 'Fast Charger 20W',
            'sku' => 'CHG-20W',
            'slug' => 'fast-charger-20w',
            'retail_price' => 25000,
            'wholesale_price' => 18000,
            'stock_status' => 'in_stock',
        ]);

        // The inventory ledger is the stock source of truth (SoT §5) — seed
        // opening stock so confirming the order can reserve it (otherwise the
        // adapter correctly blocks confirmation for unavailable stock).
        app(\App\POS\Services\InventoryService::class)->postMovement([
            'store_id' => $this->storeA->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => 10,
            'client_transaction_id' => 'audit-scenario3-open',
        ]);

        // Customer Creates Order via single product order request route
        $orderResponse = $this->post("/store/{$this->storeA->slug}/orders", [
            'product_id' => $product->id,
            'customer_name' => 'Ko Aung',
            'customer_phone' => '09987654321',
            'customer_address' => 'No. 45, Pyay Road, Yangon',
            'contact_channel' => 'viber',
            'quantity' => 2,
        ]);

        $orderResponse->assertRedirect();
        $order = Order::where('customer_phone', '09987654321')->firstOrFail();
        $this->assertEquals('pending_contact', $order->status);
        $this->assertEquals(50000, $order->total_amount);

        // Admin Sees Order in List & Detail
        $adminListResponse = $this->actingAs($this->adminA)->get("/store/{$this->storeA->slug}/admin/orders");
        $adminListResponse->assertStatus(200);
        $adminListResponse->assertSee('Ko Aung');

        $adminDetailResponse = $this->actingAs($this->adminA)->get("/store/{$this->storeA->slug}/admin/orders/{$order->id}");
        $adminDetailResponse->assertStatus(200);
        $adminDetailResponse->assertSee('Ko Aung');
        $adminDetailResponse->assertSee('Fast Charger 20W');

        // Test Status Updates: confirmed -> cancelled -> pending_contact
        $statuses = ['confirmed', 'cancelled', 'pending_contact'];
        foreach ($statuses as $status) {
            $updateStatusResponse = $this->actingAs($this->adminA)
                ->patch("/store/{$this->storeA->slug}/admin/orders/{$order->id}/status", [
                    'status' => $status,
                ]);

            $updateStatusResponse->assertRedirect();
            $this->assertEquals($status, $order->fresh()->status);
        }
    }

    /**
     * Scenario 4: Wholesale Workflow (Apply -> Approve -> Pricing Access)
     */
    public function test_scenario_4_wholesale_workflow(): void
    {
        $customer = User::factory()->create(['phone' => '09444444444']);

        // 1. Submit Wholesale Application
        $applyResponse = $this->actingAs($customer)
            ->post("/store/{$this->storeA->slug}/wholesale/apply", [
                'business_name' => 'Mandalay Mobile Shop',
                'contact_person' => 'U Ba',
                'phone' => '09444444444',
                'business_type' => 'Retailer',
                'city' => 'Mandalay',
                'address' => '78th Street, Mandalay',
            ]);

        $applyResponse->assertRedirect();
        $application = WholesaleApplication::where('phone', '09444444444')->firstOrFail();
        $this->assertEquals('pending', $application->status);

        // 2. Admin Approves Application
        $approveResponse = $this->actingAs($this->adminA)
            ->patch("/store/{$this->storeA->slug}/admin/wholesale/applications/{$application->id}", [
                'status' => 'approved',
            ]);

        $approveResponse->assertRedirect();
        $this->assertEquals('approved', $application->fresh()->status);

        // 3. Wholesale User Sees Wholesale Pricing
        $product = Product::create([
            'store_id' => $this->storeA->id,
            'name' => 'Wholesale Screen Guard',
            'sku' => 'WS-SG-01',
            'slug' => 'wholesale-screen-guard',
            'retail_price' => 10000,
            'wholesale_price' => 6000,
            'stock_status' => 'in_stock',
        ]);

        $catalogResponse = $this->actingAs($customer)->get("/store/{$this->storeA->slug}/product/{$product->slug}");
        $catalogResponse->assertStatus(200);
        $catalogResponse->assertSee('6,000');
    }

    /**
     * Scenario 5: Glass Finder Admin & Storefront Search
     */
    public function test_scenario_5_glass_finder_workflow(): void
    {
        // 1. Create Glass Finder Item
        $createResponse = $this->actingAs($this->adminA)
            ->post("/store/{$this->storeA->slug}/admin/glass-finder", [
                'brand' => 'Samsung',
                'phone_model' => 'Galaxy S23',
                'glass_code' => 'SAM-S23-TG',
                'stock_status' => 'in_stock',
            ]);

        $createResponse->assertRedirect();
        $glassItem = GlassFinderItem::where('phone_model', 'Galaxy S23')->firstOrFail();

        // 2. Edit Glass Finder Item
        $editResponse = $this->actingAs($this->adminA)
            ->put("/store/{$this->storeA->slug}/admin/glass-finder/{$glassItem->id}", [
                'brand' => 'Samsung',
                'phone_model' => 'Galaxy S23 Ultra',
                'glass_code' => 'SAM S23 ULTRA TG',
                'stock_status' => 'in_stock',
            ]);

        $editResponse->assertRedirect();
        $this->assertEquals('Galaxy S23 Ultra', $glassItem->fresh()->phone_model);
        $this->assertEquals('sams23ultratg', $glassItem->fresh()->normalized_glass_code);

        // 3. Storefront Glass Finder Search
        $searchResponse = $this->get("/glass-finder?search=S23+Ultra");
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('Galaxy S23 Ultra');

        // 4. Delete Glass Finder Item
        $deleteResponse = $this->actingAs($this->adminA)
            ->delete("/store/{$this->storeA->slug}/admin/glass-finder/{$glassItem->id}");

        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('glass_finder_items', ['id' => $glassItem->id]);
    }

    /**
     * Scenario 6: Multi-Store Isolation Safeguards
     */
    public function test_scenario_6_multi_store_isolation(): void
    {
        $productB = Product::create([
            'store_id' => $this->storeB->id,
            'name' => 'Store B Exclusive Cover',
            'sku' => 'STB-001',
            'slug' => 'store-b-exclusive-cover',
            'retail_price' => 8000,
            'wholesale_price' => 5000,
            'stock_status' => 'in_stock',
        ]);

        $bannerB = HomeBanner::create([
            'store_id' => $this->storeB->id,
            'title' => 'Store B Banner',
            'image_path' => 'banners/b.jpg',
        ]);

        $orderB = Order::create([
            'store_id' => $this->storeB->id,
            'order_number' => 'ORD-B-100',
            'customer_name' => 'User B',
            'customer_phone' => '09555555555',
            'customer_address' => 'Mandalay',
            'contact_channel' => 'viber',
            'total_amount' => 5000,
            'status' => 'pending_contact',
        ]);

        // Admin A cannot edit Store B Product
        $response1 = $this->actingAs($this->adminA)->get("/store/{$this->storeA->slug}/admin/products/{$productB->id}/edit");
        $response1->assertStatus(403);

        // Admin A cannot delete Store B Banner
        $response2 = $this->actingAs($this->adminA)->delete("/store/{$this->storeA->slug}/admin/banners/{$bannerB->id}");
        $response2->assertStatus(403);

        // Admin A cannot view Store B Order Detail
        $response3 = $this->actingAs($this->adminA)->get("/store/{$this->storeA->slug}/admin/orders/{$orderB->id}");
        $response3->assertStatus(403);
    }
}
