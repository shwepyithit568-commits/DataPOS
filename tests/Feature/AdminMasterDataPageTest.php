<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Store;
use App\Models\User;
use App\Models\VariantPreset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMasterDataPageTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;
    protected User $staff;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create(['name' => 'Store One', 'slug' => 'store-one']);
        $this->store->setting()->create(['store_name' => 'Store One', 'default_language' => 'en']);

        $this->manager = User::factory()->create(['phone' => '09111111111']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);

        $this->staff = User::factory()->create(['phone' => '09222222222']);
        $this->staff->stores()->attach($this->store->id, ['role' => 'staff', 'status' => 'active']);

        $this->customer = User::factory()->create(['phone' => '09333333333', 'role' => 'customer']);
    }

    public function test_master_data_page_renders_tab_bar_and_default_categories_tab(): void
    {
        Category::create(['store_id' => $this->store->id, 'name' => 'Mobile Phones', 'slug' => 'mobile-phones']);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/products/master-data");

        $response->assertStatus(200);
        $response->assertSeeText('Master Data');
        $response->assertSeeText('Categories');
        $response->assertSeeText('Brands');
        $response->assertSeeText('Variant Settings');
        // Default tab content — the category tree renders.
        $response->assertSeeText('Mobile Phones');
    }

    public function test_brands_tab_renders_brand_list(): void
    {
        Brand::create(['store_id' => $this->store->id, 'name' => 'Apple', 'slug' => 'apple']);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/products/master-data?tab=brands");

        $response->assertStatus(200);
        $response->assertSeeText('Apple');
    }

    public function test_variant_presets_tab_renders_presets(): void
    {
        VariantPreset::create([
            'store_id' => $this->store->id,
            'name' => 'iPhone Color',
            'options' => [['name' => 'Black', 'sku_suffix' => '-BK']],
        ]);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/products/master-data?tab=variant-presets");

        $response->assertStatus(200);
        $response->assertSeeText('iPhone Color');
    }

    public function test_unknown_tab_falls_back_to_categories(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/products/master-data?tab=does-not-exist");

        $response->assertStatus(200);
        $response->assertSeeText('Categories');
    }

    public function test_staff_can_view_master_data_page(): void
    {
        $response = $this->actingAs($this->staff)
            ->get("/store/{$this->store->slug}/admin/products/master-data?tab=brands");

        $response->assertStatus(200);
    }

    public function test_customer_without_store_role_is_blocked(): void
    {
        $response = $this->actingAs($this->customer)
            ->get("/store/{$this->store->slug}/admin/products/master-data");

        $response->assertStatus(403);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get("/store/{$this->store->slug}/admin/products/master-data");

        $response->assertRedirect(route('login'));
    }

    public function test_cross_store_access_is_blocked(): void
    {
        $otherStore = Store::create(['name' => 'Store Two', 'slug' => 'store-two']);
        $otherStore->setting()->create(['store_name' => 'Store Two', 'default_language' => 'en']);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$otherStore->slug}/admin/products/master-data");

        $response->assertStatus(403);
    }

    public function test_sidebar_links_to_master_data_page(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/dashboard");

        $response->assertStatus(200);
        $response->assertSee('data-route-name="store.admin.products.master-data"', false);
        $response->assertSeeText('Master Data');
    }

    public function test_category_create_from_master_data_returns_to_master_data_page(): void
    {
        $masterDataUrl = "/store/{$this->store->slug}/admin/products/master-data?tab=categories";

        // back() honours the Referer header, which is the master-data URL when
        // the inline create form lives on that page.
        $response = $this->actingAs($this->manager)
            ->withHeaders(['referer' => $masterDataUrl])
            ->post("/store/{$this->store->slug}/admin/categories", [
                'name' => 'New Cat',
            ]);

        $response->assertRedirect($masterDataUrl);
        $this->assertDatabaseHas('categories', ['store_id' => $this->store->id, 'name' => 'New Cat']);
    }

    public function test_variant_preset_create_from_master_data_returns_to_master_data_page(): void
    {
        $masterDataUrl = "/store/{$this->store->slug}/admin/products/master-data?tab=variant-presets";

        // Visiting the tab captures the return URL (AdminListReturn) …
        $this->actingAs($this->manager)->get($masterDataUrl);

        // … so a preset created from that tab lands back on it.
        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/variant-presets", [
                'name' => 'Test Preset',
                'options' => [[
                    'name' => 'Black',
                    'sku_suffix' => '-BK',
                    'retail_price_adjustment' => 0,
                    'wholesale_price_adjustment' => 0,
                    'stock_status' => 'in_stock',
                ]],
            ]);

        $response->assertRedirect($masterDataUrl);
        $this->assertDatabaseHas('variant_presets', ['store_id' => $this->store->id, 'name' => 'Test Preset']);
    }
}
