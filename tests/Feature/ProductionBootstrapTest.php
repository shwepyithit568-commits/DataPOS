<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductionBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_canonical_role_values_match_application_authorization(): void
    {
        $store = Store::create(['name' => 'ACDC Mobile', 'slug' => 'acdc-mobile']);

        $owner = User::factory()->create(['role' => 'platform_owner', 'phone' => '09900000000']);
        $manager = User::factory()->create(['role' => 'customer', 'phone' => '09911111111']);
        $staff = User::factory()->create(['role' => 'customer', 'phone' => '09922222222']);
        $wholesale = User::factory()->create(['role' => 'customer', 'phone' => '09933333333']);
        $retail = User::factory()->create(['role' => 'customer', 'phone' => '09944444444']);

        $manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);
        $staff->stores()->attach($store->id, ['role' => 'staff', 'status' => 'active']);
        $wholesale->stores()->attach($store->id, ['role' => 'wholesale_customer', 'status' => 'active']);
        $retail->stores()->attach($store->id, ['role' => 'retail_customer', 'status' => 'active']);

        $this->assertSame('store_manager', $owner->getStoreRole($store->id));
        $this->assertSame('store_manager', $manager->getStoreRole($store->id));
        $this->assertSame('staff', $staff->getStoreRole($store->id));
        $this->assertSame('wholesale_customer', $wholesale->getStoreRole($store->id));
        $this->assertSame('retail_customer', $retail->getStoreRole($store->id));
    }

    public function test_production_create_store_creates_acdc_mobile_without_demo_data(): void
    {
        $this->artisan('production:create-store', [
            '--name' => 'ACDC Mobile',
            '--slug' => 'acdc-mobile',
            '--phone' => '+959111111111',
            '--viber' => '+959111111111',
            '--telegram' => 'acdc_mobile',
            '--address' => 'Production address to confirm before launch',
            '--opening-hours' => 'Daily: confirm before launch',
            '--delivery-info' => 'Delivery coverage to confirm before launch',
            '--payment-info' => 'Payment methods to confirm before launch',
            '--default-language' => 'my',
        ])
            ->expectsOutput('Production store created.')
            ->expectsOutputToContain('Storefront URL: /store/acdc-mobile')
            ->expectsOutputToContain('Admin URL: /store/acdc-mobile/admin/')
            ->assertExitCode(0);

        $this->assertDatabaseHas('stores', [
            'name' => 'ACDC Mobile',
            'slug' => 'acdc-mobile',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('storefront_settings', [
            'store_name' => 'ACDC Mobile',
            'default_language' => 'my',
            'telegram_username' => 'acdc_mobile',
        ]);
        $this->assertDatabaseMissing('stores', ['slug' => 'datapos-mobile']);
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('glass_finder_items', 0);
    }

    public function test_production_create_store_rejects_duplicate_slug(): void
    {
        Store::create(['name' => 'ACDC Mobile', 'slug' => 'acdc-mobile']);

        $this->artisan('production:create-store', [
            '--name' => 'ACDC Mobile',
            '--slug' => 'acdc-mobile',
        ])
            ->expectsOutputToContain('slug')
            ->assertExitCode(1);

        $this->assertDatabaseCount('stores', 1);
    }

    public function test_production_bootstrap_creates_owner_store_and_scoped_manager(): void
    {
        $this->seed(ProductionSeeder::class);

        $this->artisan('production:create-admin', [
            '--role' => 'platform_owner',
            '--name' => 'Production Owner',
            '--phone' => '09955555555',
            '--password' => 'OwnerPass#12345',
            '--password-confirmation' => 'OwnerPass#12345',
        ])->assertExitCode(0);

        $this->artisan('production:create-store', [
            '--name' => 'ACDC Mobile',
            '--slug' => 'acdc-mobile',
        ])->assertExitCode(0);

        $this->artisan('production:create-admin', [
            '--role' => 'store_manager',
            '--store' => 'acdc-mobile',
            '--name' => 'ACDC Manager',
            '--phone' => '09966666666',
            '--password' => 'ManagerPass#12345',
            '--password-confirmation' => 'ManagerPass#12345',
        ])->assertExitCode(0);

        $store = Store::where('slug', 'acdc-mobile')->firstOrFail();
        $owner = User::where('phone', '09955555555')->firstOrFail();
        $manager = User::where('phone', '09966666666')->firstOrFail();

        $this->assertSame('platform_owner', $owner->role);
        $this->assertSame('customer', $manager->role);
        $this->assertDatabaseHas('store_user', [
            'user_id' => $manager->id,
            'store_id' => $store->id,
            'role' => 'store_manager',
            'status' => 'active',
        ]);

        $this->post('/login', [
            'phone' => '09966666666',
            'password' => 'ManagerPass#12345',
        ])->assertRedirect('/store/acdc-mobile/admin/dashboard');

        $this->actingAs($manager)
            ->get('/store/acdc-mobile/admin/')
            ->assertRedirect('/store/acdc-mobile/admin/dashboard');

        $this->actingAs($manager)
            ->get('/store/acdc-mobile/admin/dashboard')
            ->assertOk();
    }

    public function test_registered_customer_cannot_escalate_to_platform_owner_or_store_manager(): void
    {
        Store::create(['name' => 'ACDC Mobile', 'slug' => 'acdc-mobile']);

        $this->post('/register', [
            'name' => 'Customer',
            'phone' => '09977777777',
            'password' => 'StrongPass#12345',
            'password_confirmation' => 'StrongPass#12345',
            'role' => 'platform_owner',
            'store_role' => 'store_manager',
            'store_id' => 1,
        ])->assertRedirect('/');

        $user = User::where('phone', '09977777777')->firstOrFail();

        $this->assertSame('customer', $user->role);
        // Role tampering is still blocked — the shopper is enrolled only as a
        // retail_customer (shared ecommerce + POS list), never store_manager.
        $this->assertSame('retail_customer', $user->getStoreRole(1));
    }
}
