<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\DemoStoresSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoStoresSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_all_six_demo_stores_with_rich_data(): void
    {
        $this->artisan('datapos:build-demo-stores')
            ->assertSuccessful();

        // 1. All 6 stores created
        $expectedSlugs = [
            'diamond-stone-agri',
            'datapos-mobile',
            'cctv-network-computer',
            'mobile-sale-service',
            'pharmacy',
            'si-taw-gyi-food-bar',
        ];

        foreach ($expectedSlugs as $slug) {
            $store = Store::where('slug', $slug)->first();
            $this->assertNotNull($store, "Store {$slug} should exist.");
            $this->assertTrue($store->is_active);
            $this->assertNotNull($store->setting);
            $this->assertGreaterThan(0, Product::where('store_id', $store->id)->count());
            $this->assertGreaterThan(0, Order::where('store_id', $store->id)->count());
        }

        // 2. Users created with correct credentials and roles
        $superAdmin = User::where('phone', '09777000111')->first();
        $this->assertNotNull($superAdmin);
        $this->assertSame('platform_owner', $superAdmin->role);

        $manager = User::where('phone', '09111222333')->first();
        $this->assertNotNull($manager);

        $cashier = User::where('phone', '09222333444')->first();
        $this->assertNotNull($cashier);

        $wholesaleCustomer = User::where('phone', '09988776655')->first();
        $this->assertNotNull($wholesaleCustomer);
    }
}
