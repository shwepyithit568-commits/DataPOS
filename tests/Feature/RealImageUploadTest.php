<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\HomeBanner;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RealImageUploadTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->store = Store::create([
            'name' => 'Upload Test Store',
            'slug' => 'upload-test-store',
            'is_active' => true,
        ]);

        $this->manager = User::factory()->create([
            'phone' => '0999999' . rand(1000, 9999),
            'role' => 'customer',
        ]);

        $this->manager->stores()->attach($this->store->id, [
            'role' => 'store_manager',
            'status' => 'active',
        ]);
    }

    public function test_product_jpeg_upload(): void
    {
        $file = UploadedFile::fake()->image('phone.jpg')->size(4096);

        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/products", [
                'name' => 'JPEG Test Phone',
                'sku' => 'SKU-JPEG-001',
                'retail_price' => 50000,
                'wholesale_price' => 40000,
                'stock_status' => 'in_stock',
                'image' => $file,
            ]);

        $response->assertRedirect("/store/{$this->store->slug}/admin/products");
        $response->assertSessionHasNoErrors();

        $product = Product::where('sku', 'SKU-JPEG-001')->firstOrFail();
        $this->assertNotNull($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
    }

    public function test_product_webp_upload(): void
    {
        $file = UploadedFile::fake()->create('phone.webp', 150, 'image/webp');

        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/products", [
                'name' => 'WebP Test Phone',
                'sku' => 'SKU-WEBP-001',
                'retail_price' => 60000,
                'wholesale_price' => 50000,
                'stock_status' => 'in_stock',
                'image' => $file,
            ]);

        $response->assertRedirect("/store/{$this->store->slug}/admin/products");
        $response->assertSessionHasNoErrors();

        $product = Product::where('sku', 'SKU-WEBP-001')->firstOrFail();
        $this->assertNotNull($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
    }

    public function test_category_image_upload(): void
    {
        $file = UploadedFile::fake()->image('category.png')->size(4096);

        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/categories", [
                'name' => 'Test Category',
                'image' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $category = Category::where('store_id', $this->store->id)->where('name', 'Test Category')->firstOrFail();
        $this->assertNotNull($category->image_path);
        Storage::disk('public')->assertExists($category->image_path);
    }

    public function test_brand_logo_upload(): void
    {
        $file = UploadedFile::fake()->image('logo.png')->size(4096);

        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/brands", [
                'name' => 'Test Brand Logo',
                'logo' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $brand = Brand::where('store_id', $this->store->id)->where('name', 'Test Brand Logo')->firstOrFail();
        $this->assertNotNull($brand->logo_path);
        Storage::disk('public')->assertExists($brand->logo_path);
    }

    public function test_product_gallery_accepts_four_images_and_rejects_more(): void
    {
        $product = Product::create([
            'store_id' => $this->store->id,
            'name' => 'Four Image Product',
            'sku' => 'SKU-GALLERY-004',
            'slug' => 'four-image-product',
            'retail_price' => 70000,
            'wholesale_price' => 60000,
            'stock_status' => 'in_stock',
        ]);

        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/products/{$product->id}/images", [
                'images' => [
                    UploadedFile::fake()->image('gallery-1.jpg')->size(4096),
                    UploadedFile::fake()->image('gallery-2.jpg')->size(4096),
                    UploadedFile::fake()->image('gallery-3.jpg')->size(4096),
                    UploadedFile::fake()->image('gallery-4.jpg')->size(4096),
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertCount(4, $product->fresh()->images);

        $response = $this->actingAs($this->manager)
            ->from("/store/{$this->store->slug}/admin/products/{$product->id}/edit")
            ->post("/store/{$this->store->slug}/admin/products/{$product->id}/images", [
                'images' => [UploadedFile::fake()->image('gallery-5.jpg')->size(100)],
            ]);

        $response->assertRedirect("/store/{$this->store->slug}/admin/products/{$product->id}/edit");
        $response->assertSessionHasErrors('images');
        $this->assertCount(4, $product->fresh()->images);
    }

    public function test_product_create_can_attach_gallery_images_for_detail_page(): void
    {
        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/products", [
                'name' => 'Create Gallery Product',
                'sku' => 'SKU-CREATE-GALLERY-001',
                'retail_price' => 80000,
                'wholesale_price' => 70000,
                'stock_status' => 'in_stock',
                'gallery_images' => [
                    UploadedFile::fake()->image('create-gallery-1.jpg')->size(1024),
                    UploadedFile::fake()->image('create-gallery-2.jpg')->size(1024),
                    UploadedFile::fake()->image('create-gallery-3.jpg')->size(1024),
                    UploadedFile::fake()->image('create-gallery-4.jpg')->size(1024),
                ],
            ]);

        $response->assertRedirect("/store/{$this->store->slug}/admin/products");
        $response->assertSessionHasNoErrors();

        $product = Product::where('sku', 'SKU-CREATE-GALLERY-001')->firstOrFail();

        $this->assertCount(4, $product->images);
        $this->assertNotNull($product->image_path);
        $this->assertContains($product->image_path, $product->all_image_paths);

        $detailResponse = $this->get("/store/{$this->store->slug}/product/{$product->slug}");

        $detailResponse->assertStatus(200);
        foreach ($product->fresh()->images as $image) {
            $detailResponse->assertSee($image->image_path);
        }
    }

    public function test_brand_logo_can_be_removed_without_deleting_brand(): void
    {
        Storage::disk('public')->put('brands/remove-me.jpg', 'brand-logo');

        $brand = Brand::create([
            'store_id' => $this->store->id,
            'name' => 'Removable Brand Logo',
            'slug' => 'removable-brand-logo',
            'logo_path' => 'brands/remove-me.jpg',
        ]);

        $response = $this->actingAs($this->manager)
            ->put("/store/{$this->store->slug}/admin/brands/{$brand->id}", [
                'name' => $brand->name,
                'remove_logo' => '1',
            ]);

        $response->assertRedirect("/store/{$this->store->slug}/admin/brands");
        $response->assertSessionHasNoErrors();

        $this->assertNull($brand->fresh()->logo_path);
        Storage::disk('public')->assertMissing('brands/remove-me.jpg');
    }

    public function test_category_image_can_be_removed_without_deleting_category(): void
    {
        Storage::disk('public')->put('categories/remove-me.jpg', 'category-image');

        $category = Category::create([
            'store_id' => $this->store->id,
            'name' => 'Removable Category Image',
            'slug' => 'removable-category-image',
            'image_path' => 'categories/remove-me.jpg',
        ]);

        $response = $this->actingAs($this->manager)
            ->put("/store/{$this->store->slug}/admin/categories/{$category->id}", [
                'name' => $category->name,
                'remove_image' => '1',
            ]);

        $response->assertRedirect("/store/{$this->store->slug}/admin/categories");
        $response->assertSessionHasNoErrors();

        $this->assertNull($category->fresh()->image_path);
        Storage::disk('public')->assertMissing('categories/remove-me.jpg');
    }

    public function test_home_banner_upload(): void
    {
        $file = UploadedFile::fake()->create('banner.jpg', 300, 'image/jpeg');

        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/banners", [
                'title' => 'Test Hero Banner',
                'page' => 'home',
                'image' => $file,
                'sort_order' => 1,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $banner = HomeBanner::where('store_id', $this->store->id)->where('title', 'Test Hero Banner')->firstOrFail();
        $this->assertNotNull($banner->image_path);
        Storage::disk('public')->assertExists($banner->image_path);
    }
}
