<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\GlassFinderItem;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_multi_attribute_filtering_with_store_isolation(): void
    {
        $storeA = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $storeB = Store::create(['name' => 'Store B', 'slug' => 'store-b']);

        $category = Category::create(['store_id' => $storeA->id, 'name' => 'Tempered Glass', 'slug' => 'tempered-glass']);
        $brand = Brand::create(['store_id' => $storeA->id, 'name' => 'Apple', 'slug' => 'apple']);

        $p1 = Product::create([
            'store_id' => $storeA->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'sku' => 'SKU-A1',
            'name' => 'iPhone 15 Glass',
            'slug' => 'iphone-15-glass',
            'retail_price' => 10000,
            'wholesale_price' => 8000,
            'stock_status' => 'in_stock',
        ]);

        $p2 = Product::create([
            'store_id' => $storeA->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'sku' => 'SKU-A2',
            'name' => 'iPhone 15 Case',
            'slug' => 'iphone-15-case',
            'retail_price' => 5000,
            'wholesale_price' => 4000,
            'stock_status' => 'out_of_stock',
        ]);

        // Product in Store B (isolated)
        $pB = Product::create([
            'store_id' => $storeB->id,
            'sku' => 'SKU-B1',
            'name' => 'Store B Item',
            'slug' => 'store-b-item',
            'retail_price' => 20000,
            'wholesale_price' => 15000,
            'stock_status' => 'in_stock',
        ]);

        // Filter by Stock Status (in_stock)
        $responseInStock = $this->get('/products?store_slug=store-a&stock_status=in_stock');
        $responseInStock->assertStatus(200);
        $responseInStock->assertSee('iPhone 15 Glass');
        $responseInStock->assertDontSee('iPhone 15 Case');
        $responseInStock->assertDontSee('Store B Item');

        // Filter by Category
        $responseCategory = $this->get('/products?store_slug=store-a&category_id=' . $category->id);
        $responseCategory->assertStatus(200);
        $responseCategory->assertSee('iPhone 15 Glass');
        $responseCategory->assertSee('iPhone 15 Case');
        $responseCategory->assertDontSee('Store B Item');
    }

    public function test_product_card_renders_share_links_and_native_share_guard(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $category = Category::create(['store_id' => $store->id, 'name' => 'Tools', 'slug' => 'tools']);
        $brand = Brand::create(['store_id' => $store->id, 'name' => 'U-WiNN', 'slug' => 'uwinn']);
        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'sku' => 'SKU-SHARE-1',
            'name' => 'A88 Micro Share Test',
            'slug' => 'a88-micro-share-test',
            'retail_price' => 30000,
            'wholesale_price' => 15000,
            'stock_status' => 'in_stock',
        ]);

        $productUrl = url('/store/store-a/product/a88-micro-share-test');

        $response = $this->get('/products?store_slug=store-a');

        $response->assertStatus(200);
        // Share button + dropdown render.
        $response->assertSee(__('messages.share'));
        $response->assertSee('data-card-share', false);
        // The three platform share URLs are wired up.
        $response->assertSee('https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($productUrl), false);
        $response->assertSee('https://t.me/share/url?url=' . rawurlencode($productUrl), false);
        $response->assertSee('viber://forward?text=', false);
        // Native Web Share API guard is present in the markup (no server-side crash).
        $response->assertSee('navigator.share', false);
        $response->assertSee(__('messages.share_via_app'));
    }

    public function test_product_card_brand_badge_and_name_share_one_row(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $category = Category::create(['store_id' => $store->id, 'name' => 'Tools', 'slug' => 'tools']);
        $brand = Brand::create(['store_id' => $store->id, 'name' => 'U-WiNN', 'slug' => 'uwinn']);
        Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'sku' => 'SKU-ROW-1',
            'name' => 'A88 Micro',
            'slug' => 'a88-micro',
            'retail_price' => 30000,
            'wholesale_price' => 15000,
            'stock_status' => 'in_stock',
        ]);

        $response = $this->get('/products?store_slug=store-a');

        $response->assertStatus(200);
        // The brand badge was removed from the card markup (owner request); only the
        // product name (plus warranty/sku on desktop) remains in the title row. The
        // brand still appears elsewhere on the page (filter dropdown, favorites JS),
        // so we assert against the badge-specific data attribute instead.
        $response->assertSee('data-card-title-row', false);
        $response->assertDontSee('data-card-brand', false);
        $response->assertSee('A88 Micro');
    }

    public function test_product_card_details_button_shows_eye_icon_with_aria_label(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $category = Category::create(['store_id' => $store->id, 'name' => 'Tools', 'slug' => 'tools']);
        $brand = Brand::create(['store_id' => $store->id, 'name' => 'U-WiNN', 'slug' => 'uwinn']);
        Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'sku' => 'SKU-EYE-1',
            'name' => 'A88 Micro',
            'slug' => 'a88-micro-eye',
            'retail_price' => 30000,
            'wholesale_price' => 15000,
            'stock_status' => 'in_stock',
        ]);

        $response = $this->get('/products?store_slug=store-a');

        $response->assertStatus(200);
        // The details button is an SVG eye icon (mint green, no text label);
        // the label is preserved for screen readers via aria-label.
        $response->assertSee('aria-label="' . __('messages.details') . '"', false);
        $response->assertSee('bg-emerald-50', false);
        $response->assertSee('M2.458 12C3.732 7.943', false);
    }

    public function test_product_detail_image_gallery_support(): void
    {
        $store = Store::create(['name' => 'Store Main', 'slug' => 'store-main']);
        $product = Product::create([
            'store_id' => $store->id,
            'sku' => 'SKU-GAL-1',
            'name' => 'Camera Lens Protector',
            'slug' => 'camera-lens-protector',
            'retail_price' => 12000,
            'wholesale_price' => 9000,
            'stock_status' => 'in_stock',
            'image_path' => 'products/main.jpg',
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'products/gallery1.jpg',
            'is_primary' => true,
            'sort_order' => 1,
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'products/gallery2.jpg',
            'is_primary' => false,
            'sort_order' => 2,
        ]);

        $this->assertCount(2, $product->images);
        $this->assertContains('products/main.jpg', $product->all_image_paths);
        $this->assertContains('products/gallery1.jpg', $product->all_image_paths);

        $response = $this->get('/store/store-main/product/camera-lens-protector');
        $response->assertStatus(200);
        $response->assertSee('Camera Lens Protector');
        $response->assertSee('products/main.jpg');
        $response->assertSee('products/gallery1.jpg');
        $response->assertSee('products/gallery2.jpg');
    }

    public function test_product_detail_page_renders_open_graph_meta_tags(): void
    {
        $store = Store::create(['name' => 'Store Main', 'slug' => 'store-main']);
        $product = Product::create([
            'store_id' => $store->id,
            'sku' => 'SKU-OG-1',
            'name' => 'A88 Micro OG Test',
            'slug' => 'a88-micro-og-test',
            'retail_price' => 30000,
            'wholesale_price' => 15000,
            'stock_status' => 'in_stock',
            'image_path' => 'products/main.jpg',
            'description' => 'Premium tempered glass for the A88 Micro.',
        ]);

        $response = $this->get('/store/store-main/product/a88-micro-og-test');

        $response->assertStatus(200);
        // Open Graph tags so Facebook / Telegram / Viber render an image-rich share preview.
        $response->assertSee('property="og:title" content="A88 Micro OG Test"', false);
        $response->assertSee('property="og:type" content="product"', false);
        $response->assertSee('property="og:image" content="' . asset('storage/products/main.jpg') . '"', false);
        $response->assertSee('property="og:url" content="' . url('/store/store-main/product/a88-micro-og-test') . '"', false);
        // Description falls back to the product description (not the generic store welcome).
        $response->assertSee('Premium tempered glass for the A88 Micro.');
        // Twitter card mirrors the image for large-image previews.
        $response->assertSee('name="twitter:card" content="summary_large_image"', false);
        $response->assertSee('name="twitter:image"', false);
    }

    public function test_glass_finder_smart_search_and_grouped_compatibility(): void
    {
        $store = Store::create(['name' => 'Store Main', 'slug' => 'store-main']);

        GlassFinderItem::create([
            'store_id' => $store->id,
            'brand' => 'Samsung',
            'phone_model' => 'Galaxy S24 Ultra',
            'glass_code' => 'G-S24U',
            'stock_status' => 'in_stock',
        ]);

        GlassFinderItem::create([
            'store_id' => $store->id,
            'brand' => 'Samsung',
            'phone_model' => 'Galaxy S24 Plus',
            'glass_code' => 'G-S24U',
            'stock_status' => 'in_stock',
        ]);

        // Smart search by keyword "S24"
        $response = $this->get('/glass-finder?store_slug=store-main&search=S24');
        $response->assertStatus(200);
        $response->assertSee('Galaxy S24 Ultra');
        $response->assertSee('Galaxy S24 Plus');
        $response->assertSee('G-S24U');
    }
}
