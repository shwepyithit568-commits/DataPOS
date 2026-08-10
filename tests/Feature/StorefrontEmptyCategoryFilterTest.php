<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontEmptyCategoryFilterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Categories/brands with no products are hidden from the storefront
     * catalog filter — empty ones only clutter the customer's view.
     */
    public function test_empty_categories_and_brands_are_hidden_from_catalog_filter(): void
    {
        $store = Store::create(['name' => 'DataPOS', 'slug' => 'datapos-test']);

        $emptyCategory = Category::create(['store_id' => $store->id, 'name' => 'BATTERY', 'slug' => 'battery']);
        $usedCategory = Category::create(['store_id' => $store->id, 'name' => 'CHARGER', 'slug' => 'charger']);

        $emptyBrand = Brand::create(['store_id' => $store->id, 'name' => 'ONDA', 'slug' => 'onda']);
        $usedBrand = Brand::create(['store_id' => $store->id, 'name' => 'U-WiNN', 'slug' => 'u-winn']);

        Product::create([
            'store_id' => $store->id,
            'category_id' => $usedCategory->id,
            'brand_id' => $usedBrand->id,
            'sku' => 'SKU-1',
            'name' => 'A88 Micro',
            'slug' => 'a88-micro',
            'retail_price' => 30000,
            'wholesale_price' => 15000,
            'stock_status' => 'in_stock',
        ]);

        $response = $this->get('/products?store_slug=' . $store->slug);

        $response->assertOk();

        // Used category/brand appear in the filter dropdown...
        $response->assertSee('CHARGER', false);
        $response->assertSee('U-WiNN', false);

        // ...while empty category/brand are hidden.
        $response->assertDontSee('BATTERY', false);
        $response->assertDontSee('ONDA', false);
    }

    /** Empty categories are also hidden from the home page category chips. */
    public function test_empty_categories_hidden_from_home_page_chips(): void
    {
        $store = Store::create(['name' => 'DataPOS', 'slug' => 'datapos-test']);

        $emptyCategory = Category::create(['store_id' => $store->id, 'name' => 'POWER BANK', 'slug' => 'power-bank']);
        $usedCategory = Category::create(['store_id' => $store->id, 'name' => 'CABLE', 'slug' => 'cable']);

        Product::create([
            'store_id' => $store->id,
            'category_id' => $usedCategory->id,
            'brand_id' => null,
            'sku' => 'SKU-2',
            'name' => 'Type-C Cable',
            'slug' => 'type-c-cable',
            'retail_price' => 5000,
            'wholesale_price' => 3000,
            'stock_status' => 'in_stock',
        ]);

        $response = $this->get('/?store_slug=' . $store->slug);

        $response->assertOk();
        $response->assertSee('CABLE', false);
        $response->assertDontSee('POWER BANK', false);
    }
}
