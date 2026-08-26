<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FlashSaleHomeSectionTest extends TestCase
{
    use RefreshDatabase;

    private function makeStore(string $slug = 'flash-sale-store'): Store
    {
        $store = Store::create([
            'name' => 'Flash Sale Store',
            'slug' => $slug,
            'is_active' => true,
        ]);
        // Pin the locale to English — the test slices the DOM on the literal
        // "Most Popular Category" header, so a Burmese rendering (session or
        // default-language fallback) would break the extraction. The store
        // name must not contain "Flash Sale" — test 3 asserts the section
        // text is absent from the whole page.
        $store->setting()->create([
            'store_name' => 'Deal Store',
            'default_language' => 'en',
        ]);

        return $store;
    }

    private function makeProduct(Store $store, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'store_id' => $store->id,
            'category_id' => Category::create(['store_id' => $store->id, 'name' => 'Deals ' . Str::random(4), 'slug' => 'deals-' . Str::random(4)])->id,
            'brand_id' => Brand::create(['store_id' => $store->id, 'name' => 'Deal Brand ' . Str::random(4), 'slug' => 'deal-brand-' . Str::random(4)])->id,
            'sku' => 'SALE-' . strtoupper(Str::random(6)),
            'name' => 'Deal Product',
            'slug' => 'deal-' . Str::random(8),
            'retail_price' => 700,
            'wholesale_price' => 500,
            'stock_status' => 'in_stock',
        ], $overrides));
    }

    /** Active window + scheduled sale both render with the countdown; expired and non-sale products do not. */
    public function test_home_shows_active_and_upcoming_flash_sales_with_countdown(): void
    {
        $store = $this->makeStore();

        $active = $this->makeProduct($store, [
            'name' => 'Active Deal Phone',
            'old_price' => 1000,
            'sale_starts_at' => now()->subHour(),
            'sale_ends_at' => now()->addHours(5),
        ]);
        $upcoming = $this->makeProduct($store, [
            'name' => 'Upcoming Deal Charger',
            'old_price' => 2000,
            'sale_starts_at' => now()->addDay(),
            'sale_ends_at' => now()->addDays(2),
        ]);
        $expired = $this->makeProduct($store, [
            'name' => 'Expired Deal Cable',
            'old_price' => 3000,
            'sale_starts_at' => now()->subDays(2),
            'sale_ends_at' => now()->subDay(),
        ]);
        $noSale = $this->makeProduct($store, ['name' => 'Regular Product']);

        $response = $this->get('/?store_slug=' . $store->slug);

        $response->assertOk();
        // Section + countdown component render.
        $response->assertSee(__('messages.flash_sale'));
        $response->assertSee('flashTimer(', false);
        $response->assertSee(__('messages.sale_ends_in'));
        $response->assertSee('-30%', false);

        // Scope the deal assertions to the flash-sale section only — expired
        // and non-sale products can still appear elsewhere (e.g. new arrivals).
        $html = $response->getContent();
        $start = strpos($html, 'id="flash-sale-section"');
        $this->assertNotFalse($start);
        $end = strpos($html, '<!-- end flash-sale-section -->', $start);
        $section = $end !== false ? substr($html, $start, $end - $start) : substr($html, $start, 5000);
        $this->assertStringContainsString('Active Deal Phone', $section);
        $this->assertStringContainsString('Upcoming Deal Charger', $section);
        $this->assertStringContainsString(__('messages.starting_soon_short'), $section);
        $this->assertStringNotContainsString('Expired Deal Cable', $section);
        $this->assertStringNotContainsString('Regular Product', $section);
    }

    /** When only scheduled deals exist the countdown label switches to "starting soon". */
    public function test_home_shows_starting_soon_label_for_upcoming_only(): void
    {
        $store = $this->makeStore('flash-upcoming-store');
        $this->makeProduct($store, [
            'name' => 'Soon Deal Case',
            'old_price' => 900,
            'sale_starts_at' => now()->addHours(3),
            'sale_ends_at' => now()->addDays(1),
        ]);

        $response = $this->get('/?store_slug=' . $store->slug);

        $response->assertOk();
        $response->assertSee(__('messages.flash_sale'));
        $response->assertSee('Soon Deal Case');
        $response->assertSee(__('messages.starting_soon'), false);
        $response->assertDontSee(__('messages.sale_ends_in'));
        $response->assertSee('flashTimer(', false);
    }

    /** Without any sale windows the flash-sale section is not rendered. */
    public function test_home_hides_flash_sale_section_without_deals(): void
    {
        $store = $this->makeStore('flash-empty-store');
        $this->makeProduct($store, ['name' => 'Normal Item']);

        $response = $this->get('/?store_slug=' . $store->slug);

        $response->assertOk();
        $response->assertDontSee(__('messages.flash_sale'));
        $response->assertDontSee('flashTimer(', false);
    }
}
