<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontBrowseTest extends TestCase
{
    use RefreshDatabase;

    public function test_browse_page_lists_categories_subs_and_brands(): void
    {
        $store = Store::create(['name' => 'Store Main', 'slug' => 'store-main']);

        $sparePart = Category::create([
            'store_id' => $store->id,
            'name' => 'Spare Part',
            'slug' => 'spare-part',
            'icon' => '🛠️',
        ]);
        $touchLcd = Category::create([
            'store_id' => $store->id,
            'name' => 'TouchLCD',
            'slug' => 'touch-lcd',
            'parent_id' => $sparePart->id,
        ]);
        $brand = Brand::create(['store_id' => $store->id, 'name' => 'Samsung', 'slug' => 'samsung']);

        Product::create([
            'store_id' => $store->id,
            'category_id' => $touchLcd->id,
            'brand_id' => $brand->id,
            'name' => 'Samsung S24 Glass',
            'slug' => 'samsung-s24-glass',
            'sku' => 'S24-G',
            'retail_price' => 30000,
            'wholesale_price' => 25000,
            'stock_status' => 'in_stock',
        ]);

        $response = $this->get('/browse?store_slug=store-main');

        $response->assertStatus(200);
        // Left rail main category + right panel sub-category + brand strip.
        $response->assertSee('Spare Part');
        $response->assertSee('TouchLCD');
        $response->assertSee('Samsung');
        // Sub/brand tiles deep-link to the catalog without forcing a view,
        // so the page opens in the default grid view.
        $response->assertDontSee('view=list', false);
        $response->assertSee('/products?category_id=', false);
        $response->assertSee('/products?brand_id=', false);
    }

    public function test_browse_page_hides_empty_categories(): void
    {
        $store = Store::create(['name' => 'Store Main', 'slug' => 'store-main']);

        $empty = Category::create([
            'store_id' => $store->id,
            'name' => 'Empty Category',
            'slug' => 'empty-category',
        ]);

        $response = $this->get('/browse?store_slug=store-main');

        $response->assertStatus(200);
        $response->assertDontSee('Empty Category');
        // Rail should show the "no categories" hint instead.
        $response->assertSee(__('messages.no_categories'));
    }

    public function test_catalog_list_view_renders_products(): void
    {
        $store = Store::create(['name' => 'Store Main', 'slug' => 'store-main']);

        Product::create([
            'store_id' => $store->id,
            'name' => 'Premium Glass',
            'slug' => 'premium-glass',
            'sku' => 'PG-001',
            'retail_price' => 5000,
            'wholesale_price' => 3000,
            'stock_status' => 'in_stock',
        ]);

        $response = $this->get('/products?store_slug=store-main&view=list');

        $response->assertStatus(200);
        $response->assertSee('Premium Glass');
        // List view shows the toggle state so users can switch back to the grid.
        $response->assertSee(__('messages.view_list'));
    }

    public function test_catalog_grid_is_hairline_divided_and_has_view_toggle(): void
    {
        $store = Store::create(['name' => 'Store Main', 'slug' => 'store-main']);

        Product::create([
            'store_id' => $store->id,
            'name' => 'Grid Product',
            'slug' => 'grid-product',
            'sku' => 'GP-001',
            'retail_price' => 2000,
            'wholesale_price' => 1500,
            'stock_status' => 'in_stock',
        ]);

        $response = $this->get('/products?store_slug=store-main');

        $response->assertStatus(200);
        $response->assertSee('Grid Product');
        // Dense hairline grid container + grid/list toggle.
        $response->assertSee('gap-px', false);
        $response->assertSee(__('messages.view_grid'));
        $response->assertSee(__('messages.view_list'));
    }
}
