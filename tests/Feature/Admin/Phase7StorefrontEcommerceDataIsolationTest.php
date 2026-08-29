<?php

namespace Tests\Feature\Admin;

use App\Models\HomeBanner;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase7StorefrontEcommerceDataIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Store $storeA;
    protected Store $storeB;
    protected User $staffA;
    protected User $staffB;
    protected Product $productA;
    protected Product $productB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storeA = Store::create(['name' => 'Store Alpha', 'slug' => 'store-alpha', 'is_active' => true]);
        $this->storeB = Store::create(['name' => 'Store Beta', 'slug' => 'store-beta', 'is_active' => true]);

        $this->staffA = User::create(['name' => 'Staff Alpha', 'phone' => '09111111111', 'password' => bcrypt('password')]);
        $this->staffB = User::create(['name' => 'Staff Beta', 'phone' => '09222222222', 'password' => bcrypt('password')]);

        $this->storeA->users()->attach($this->staffA->id, ['role' => 'store_manager', 'status' => 'active']);
        $this->storeB->users()->attach($this->staffB->id, ['role' => 'store_manager', 'status' => 'active']);

        $this->productA = Product::create([
            'store_id' => $this->storeA->id,
            'name' => 'Alpha Phone 15',
            'slug' => 'alpha-phone-15',
            'sku' => 'ALPHA-15',
            'cost_price' => 500000,
            'retail_price' => 600000,
            'wholesale_price' => 550000,
            'is_ecommerce' => true,
            'stock_status' => 'in_stock',
        ]);

        $this->productB = Product::create([
            'store_id' => $this->storeB->id,
            'name' => 'Beta Tablet 10',
            'slug' => 'beta-tablet-10',
            'sku' => 'BETA-10',
            'cost_price' => 300000,
            'retail_price' => 400000,
            'wholesale_price' => 350000,
            'is_ecommerce' => true,
            'stock_status' => 'in_stock',
        ]);
    }

    public function test_storefront_catalog_and_product_show_isolation(): void
    {
        // 1. Storefront of Store A lists Product A but NOT Product B
        $response = $this->get("/store/{$this->storeA->slug}");
        $response->assertOk();
        $response->assertSee('Alpha Phone 15');
        $response->assertDontSee('Beta Tablet 10');

        // 2. Requesting Store B's product under Store A's store slug returns 404
        $this->get("/store/{$this->storeA->slug}/product/{$this->productB->slug}")
            ->assertNotFound();

        // 3. Search suggestions on Store A only returns Store A's product
        $searchRes = $this->get("/products/suggestions?store_slug={$this->storeA->slug}&search=Tablet");
        $searchRes->assertOk();
        $searchRes->assertJsonMissing(['name' => 'Beta Tablet 10']);
    }

    public function test_online_order_creation_cross_store_isolation(): void
    {
        // 1. Placing order on Store A with Store B's product is rejected
        $this->post("/store/{$this->storeA->slug}/orders", [
            'product_id' => $this->productB->id, // foreign product
            'customer_name' => 'Online Shopper',
            'customer_phone' => '09123456789',
            'customer_address' => 'Yangon',
            'contact_channel' => 'viber',
            'quantity' => 1,
        ])->assertSessionHasErrors('product_id');

        // 2. Valid order on Store A is created under storeA->id
        $this->post("/store/{$this->storeA->slug}/orders", [
            'product_id' => $this->productA->id,
            'customer_name' => 'Alpha Shopper',
            'customer_phone' => '09987654321',
            'customer_address' => 'Mandalay',
            'contact_channel' => 'viber',
            'quantity' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'store_id' => $this->storeA->id,
            'customer_name' => 'Alpha Shopper',
        ]);
    }

    public function test_admin_orders_cross_store_isolation(): void
    {
        $orderB = Order::create([
            'store_id' => $this->storeB->id,
            'order_number' => 'ORD-B-100',
            'customer_name' => 'Beta Customer Order',
            'customer_phone' => '09888888888',
            'customer_address' => 'Taunggyi',
            'contact_channel' => 'phone',
            'pricing_type' => 'retail',
            'total_amount' => 400000,
            'status' => 'pending_contact',
        ]);

        // 1. Store A admin orders list does not show Order B
        $response = $this->actingAs($this->staffA)
            ->get("/store/{$this->storeA->slug}/admin/orders");

        $response->assertOk();
        $response->assertDontSee('ORD-B-100');
        $response->assertDontSee('Beta Customer Order');

        // 2. Staff A cannot update status, finances, note, or delete Order B
        $this->actingAs($this->staffA)
            ->patch("/store/{$this->storeA->slug}/admin/orders/{$orderB->id}/status", [
                'status' => 'confirmed',
            ])
            ->assertForbidden();

        $this->actingAs($this->staffA)
            ->patch("/store/{$this->storeA->slug}/admin/orders/{$orderB->id}/finances", [
                'payment_status' => 'paid',
            ])
            ->assertForbidden();

        $this->actingAs($this->staffA)
            ->patch("/store/{$this->storeA->slug}/admin/orders/{$orderB->id}/note", [
                'admin_note' => 'Hacked note',
            ])
            ->assertForbidden();

        $this->actingAs($this->staffA)
            ->delete("/store/{$this->storeA->slug}/admin/orders/{$orderB->id}")
            ->assertForbidden();
    }

    public function test_storefront_home_banners_cross_store_isolation(): void
    {
        $bannerB = HomeBanner::create([
            'store_id' => $this->storeB->id,
            'title' => 'Beta Big Promo 50% Off',
            'description' => 'Huge discounts on Beta items',
            'page' => 'home',
            'image_path' => 'banners/beta_banner.jpg',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        // 1. Store A storefront does not display Banner B
        $response = $this->get("/store/{$this->storeA->slug}");
        $response->assertOk();
        $response->assertDontSee('Beta Big Promo 50% Off');

        // 2. Staff A cannot edit, update, or delete Banner B
        $this->actingAs($this->staffA)
            ->get("/store/{$this->storeA->slug}/admin/banners/{$bannerB->id}/edit")
            ->assertForbidden();

        $this->actingAs($this->staffA)
            ->put("/store/{$this->storeA->slug}/admin/banners/{$bannerB->id}", [
                'title' => 'Hacked Banner',
            ])
            ->assertForbidden();

        $this->actingAs($this->staffA)
            ->delete("/store/{$this->storeA->slug}/admin/banners/{$bannerB->id}")
            ->assertForbidden();
    }
}
