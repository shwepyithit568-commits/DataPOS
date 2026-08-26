<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogPerPageTest extends TestCase
{
    use RefreshDatabase;

    private function makeStoreWithProducts(int $count): Store
    {
        $store = Store::create([
            'name' => 'PerPage Store',
            'slug' => 'perpage-store',
        ]);

        for ($i = 1; $i <= $count; $i++) {
            Product::create([
                'store_id' => $store->id,
                'sku' => 'PP-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'name' => 'PerPage Product '.$i,
                'slug' => 'perpage-product-'.$i,
                'retail_price' => 1000 + $i,
                'wholesale_price' => 900 + $i,
                'stock_status' => 'in_stock',
                'created_at' => now()->subMinutes($count - $i),
                'updated_at' => now()->subMinutes($count - $i),
            ]);
        }

        return $store;
    }

    /** Toolbar shows the per-page selector with 40/80/120/All options. */
    public function test_toolbar_renders_per_page_selector_with_all_options(): void
    {
        $this->makeStoreWithProducts(3);

        $response = $this->get('/products?store_slug=perpage-store');
        $response->assertStatus(200);
        $response->assertSee('name="per_page"', false);
        $response->assertSee('value="40"', false);
        $response->assertSee('value="80"', false);
        $response->assertSee('value="120"', false);
        $response->assertSee('value="all"', false);
    }

    /** Default page size is 40: product #41 appears on page 2, not page 1. */
    public function test_default_per_page_is_40(): void
    {
        $this->makeStoreWithProducts(45);

        $page1 = $this->get('/products?store_slug=perpage-store');
        $page1->assertStatus(200);
        $page1->assertSee('PerPage Product 1');
        $page1->assertSee('PerPage Product 40');
        $page1->assertDontSee('PerPage Product 41');
        $page1->assertSee('page=2', false);

        $page2 = $this->get('/products?store_slug=perpage-store&page=2');
        $page2->assertStatus(200);
        $page2->assertSee('PerPage Product 41');
        $page2->assertSee('PerPage Product 45');
        $page2->assertDontSee('PerPage Product 40');
    }

    /** per_page=80 shows all 45 on one page and hides pagination. */
    public function test_per_page_80_shows_all_on_single_page(): void
    {
        $this->makeStoreWithProducts(45);

        $response = $this->get('/products?store_slug=perpage-store&per_page=80');
        $response->assertStatus(200);
        $response->assertSee('PerPage Product 1');
        $response->assertSee('PerPage Product 45');
        $response->assertDontSee('page=2', false);
    }

    /** per_page=all shows every product on one page. */
    public function test_per_page_all_shows_everything(): void
    {
        $this->makeStoreWithProducts(45);

        $response = $this->get('/products?store_slug=perpage-store&per_page=all');
        $response->assertStatus(200);
        $response->assertSee('PerPage Product 1');
        $response->assertSee('PerPage Product 45');
        $response->assertDontSee('page=2', false);
    }

    /** Unknown values fall back to the 40 default. */
    public function test_invalid_per_page_falls_back_to_40(): void
    {
        $this->makeStoreWithProducts(45);

        $response = $this->get('/products?store_slug=perpage-store&per_page=999');
        $response->assertStatus(200);
        $response->assertSee('PerPage Product 40');
        $response->assertDontSee('PerPage Product 41');
        $response->assertSee('page=2', false);
    }

    /** Other filters survive a per_page change (hidden params preserved). */
    public function test_per_page_keeps_active_filters(): void
    {
        $store = $this->makeStoreWithProducts(45);

        $response = $this->get('/products?store_slug=perpage-store&per_page=80&search=PerPage&min_price=1000&max_price=2000');
        $response->assertStatus(200);
        // Filter chips (search/min/max) still rendered on the page.
        $response->assertSee('PerPage', false);
        $response->assertSee('Ks 1,000', false);
    }
}
