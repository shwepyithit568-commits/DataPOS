<?php

namespace Tests\Feature;

use App\Models\GlassFinderItem;
use App\Models\HomeBanner;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store1;
    protected Store $store2;
    protected User $manager1;
    protected User $manager2;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->store1 = Store::create(['name' => 'Store One', 'slug' => 'store-one', 'domain' => 'one.test']);
        $this->store2 = Store::create(['name' => 'Store Two', 'slug' => 'store-two', 'domain' => 'two.test']);

        $this->manager1 = User::factory()->create(['phone' => '09111111111']);
        $this->manager1->stores()->attach($this->store1->id, ['role' => 'store_manager']);

        $this->manager2 = User::factory()->create(['phone' => '09222222222']);
        $this->manager2->stores()->attach($this->store2->id, ['role' => 'store_manager']);
    }

    public function test_admin_can_view_edit_product_page(): void
    {
        $product = Product::create([
            'store_id' => $this->store1->id,
            'name' => 'Test Phone Case',
            'sku' => 'CASE-01',
            'slug' => 'test-phone-case',
            'retail_price' => 5000,
            'wholesale_price' => 3500,
            'stock_status' => 'in_stock',
        ]);

        $response = $this->actingAs($this->manager1)
            ->get("/store/{$this->store1->slug}/admin/products/{$product->id}/edit");

        $response->assertStatus(200);
        $response->assertSee('Test Phone Case');
    }

    public function test_admin_can_update_product(): void
    {
        $product = Product::create([
            'store_id' => $this->store1->id,
            'name' => 'Original Name',
            'sku' => 'SKU-ORIGINAL',
            'slug' => 'original-name',
            'retail_price' => 5000,
            'wholesale_price' => 3500,
            'stock_status' => 'in_stock',
        ]);

        $response = $this->actingAs($this->manager1)
            ->put("/store/{$this->store1->slug}/admin/products/{$product->id}", [
                'name' => 'Updated Product Name',
                'sku' => 'SKU-UPDATED',
                'retail_price' => 6000,
                'wholesale_price' => 4000,
                'stock_status' => 'out_of_stock',
                'warranty' => '6 Months',
                'return_policy' => '7 Days',
            ]);

        $response->assertRedirect("/store/{$this->store1->slug}/admin/products");
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product Name',
            'sku' => 'SKU-UPDATED',
            'retail_price' => 6000,
            'warranty' => '6 Months',
        ]);
    }

    public function test_admin_cannot_edit_product_from_another_store(): void
    {
        $productStore2 = Product::create([
            'store_id' => $this->store2->id,
            'name' => 'Store 2 Product',
            'sku' => 'ST2-PROD',
            'slug' => 'store-2-product',
            'retail_price' => 10000,
            'wholesale_price' => 8000,
            'stock_status' => 'in_stock',
        ]);

        $response = $this->actingAs($this->manager1)
            ->get("/store/{$this->store1->slug}/admin/products/{$productStore2->id}/edit");

        $response->assertStatus(403);
    }

    public function test_multiple_product_images_management(): void
    {
        $product = Product::create([
            'store_id' => $this->store1->id,
            'name' => 'Gallery Product',
            'sku' => 'GAL-01',
            'slug' => 'gallery-product',
            'retail_price' => 12000,
            'wholesale_price' => 9000,
            'stock_status' => 'in_stock',
        ]);

        $file1 = UploadedFile::fake()->create('img1.jpg', 100, 'image/jpeg');
        $file2 = UploadedFile::fake()->create('img2.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($this->manager1)
            ->post("/store/{$this->store1->slug}/admin/products/{$product->id}/images", [
                'images' => [$file1, $file2],
            ]);

        $response->assertRedirect();
        $this->assertCount(2, $product->fresh()->images);

        $img1 = $product->images()->first();
        $img2 = $product->images()->skip(1)->first();

        // Test set primary image
        $this->actingAs($this->manager1)
            ->post("/store/{$this->store1->slug}/admin/products/{$product->id}/images/{$img2->id}/primary");

        $this->assertTrue($img2->fresh()->is_primary);
        $this->assertEquals($img2->image_path, $product->fresh()->image_path);

        // Test delete gallery image
        $this->actingAs($this->manager1)
            ->delete("/store/{$this->store1->slug}/admin/products/{$product->id}/images/{$img1->id}");

        $this->assertDatabaseMissing('product_images', ['id' => $img1->id]);
    }

    public function test_glass_finder_admin_edit_and_update(): void
    {
        $glassItem = GlassFinderItem::create([
            'store_id' => $this->store1->id,
            'brand' => 'iPhone',
            'phone_model' => 'iPhone 11',
            'glass_code' => 'IP-11-TG',
            'normalized_glass_code' => 'ip11tg',
            'stock_status' => 'in_stock',
        ]);

        $response = $this->actingAs($this->manager1)
            ->get("/store/{$this->store1->slug}/admin/glass-finder/{$glassItem->id}/edit");

        $response->assertStatus(200);
        $response->assertSee('iPhone 11');

        $updateResponse = $this->actingAs($this->manager1)
            ->put("/store/{$this->store1->slug}/admin/glass-finder/{$glassItem->id}", [
                'brand' => 'iPhone',
                'phone_model' => 'iPhone 11 Pro',
                'glass_code' => 'IP 11 PRO TG',
                'stock_status' => 'out_of_stock',
            ]);

        $updateResponse->assertRedirect("/store/{$this->store1->slug}/admin/glass-finder");
        $this->assertDatabaseHas('glass_finder_items', [
            'id' => $glassItem->id,
            'phone_model' => 'iPhone 11 Pro',
            'normalized_glass_code' => 'ip11protg',
            'stock_status' => 'out_of_stock',
        ]);
    }

    public function test_cross_store_banner_deletion_is_prevented(): void
    {
        $bannerStore2 = HomeBanner::create([
            'store_id' => $this->store2->id,
            'title' => 'Store 2 Banner',
            'image_path' => 'banners/banner2.jpg',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->manager1)
            ->delete("/store/{$this->store1->slug}/admin/banners/{$bannerStore2->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('home_banners', ['id' => $bannerStore2->id]);
    }
}
