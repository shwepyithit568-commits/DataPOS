<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Store;
use App\Models\StorefrontSetting;
use App\Models\User;
use App\Services\HardwareMatrixService;
use App\Services\StoreOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreOnboardingAndEditionTest extends TestCase
{
    use RefreshDatabase;

    protected User $platformOwner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->platformOwner = User::create([
            'name'     => 'Platform Super Owner',
            'phone'    => '09900000000',
            'password' => bcrypt('password'),
            'role'     => 'platform_owner',
        ]);
    }

    public function test_platform_owner_can_provision_mobile_electronics_store(): void
    {
        $response = $this->actingAs($this->platformOwner)
            ->post(route('admin.stores.store'), [
                'name'             => 'City Mobile & Service',
                'slug'             => 'city-mobile',
                'edition'          => 'mobile_electronics',
                'owner_name'       => 'U Win Naing',
                'owner_phone'      => '09155551111',
                'owner_password'   => 'secret123',
                'owner_pos_pin'    => '9999',
                'default_language' => 'my',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.stores.index'));

        $store = Store::where('slug', 'city-mobile')->first();
        $this->assertNotNull($store);
        $this->assertSame('mobile_electronics', $store->business_profile);

        // Check Storefront Settings
        $setting = $store->setting;
        $this->assertNotNull($setting);
        $this->assertSame('marketplace_pro', $setting->theme_preset);
        $this->assertSame('outfit', $setting->font_preset);

        // Check Categories & Brands
        $this->assertTrue(Category::where('store_id', $store->id)->where('slug', 'mobile-phones')->exists());
        $this->assertTrue(Category::where('store_id', $store->id)->where('slug', 'phone-accessories')->exists());
        $this->assertTrue(Brand::where('store_id', $store->id)->where('name', 'Apple')->exists());

        // Check Store Owner Account
        $owner = User::where('phone', '09155551111')->first();
        $this->assertNotNull($owner);
        $this->assertSame('U Win Naing', $owner->name);

        $storeUser = $store->users()->where('user_id', $owner->id)->first();
        $this->assertNotNull($storeUser);
        $this->assertSame('store_owner', $storeUser->pivot->role);
    }

    public function test_platform_owner_can_provision_general_retail_store(): void
    {
        $response = $this->actingAs($this->platformOwner)
            ->post(route('admin.stores.store'), [
                'name'             => 'Ever Green Supermart',
                'slug'             => 'evergreen-mart',
                'edition'          => 'general_retail',
                'owner_name'       => 'Daw Khin Khin',
                'owner_phone'      => '09166662222',
                'owner_password'   => 'retail123',
                'owner_pos_pin'    => '5555',
                'default_language' => 'my',
            ]);

        $response->assertSessionHasNoErrors();

        $store = Store::where('slug', 'evergreen-mart')->first();
        $this->assertNotNull($store);
        $this->assertSame('general_retail', $store->business_profile);

        $setting = $store->setting;
        $this->assertSame('retail_trust', $setting->theme_preset);
        $this->assertSame('inter', $setting->font_preset);
        $this->assertSame('comfortable', $setting->grid_density);

        $this->assertTrue(Category::where('store_id', $store->id)->where('slug', 'beverages-drinks')->exists());
        $this->assertTrue(Category::where('store_id', $store->id)->where('slug', 'snacks-food')->exists());
        $this->assertTrue(Brand::where('store_id', $store->id)->where('name', 'Nestle')->exists());
    }

    public function test_platform_owner_can_provision_pharmacy_store(): void
    {
        $response = $this->actingAs($this->platformOwner)
            ->post(route('admin.stores.store'), [
                'name'             => 'Royal Care Pharmacy',
                'slug'             => 'royal-care',
                'edition'          => 'pharmacy_healthcare',
                'owner_name'       => 'Dr. Kyaw Min',
                'owner_phone'      => '09177773333',
                'default_language' => 'my',
            ]);

        $response->assertSessionHasNoErrors();

        $store = Store::where('slug', 'royal-care')->first();
        $this->assertNotNull($store);
        $this->assertSame('pharmacy', $store->business_profile);

        $setting = $store->setting;
        $this->assertSame('emerald_fresh', $setting->theme_preset);

        $this->assertTrue(Category::where('store_id', $store->id)->where('slug', 'prescription-medicine')->exists());
        $this->assertTrue(Brand::where('store_id', $store->id)->where('name', 'Mega We Care')->exists());
    }

    public function test_store_owner_can_login_and_access_store_dashboard_and_pos(): void
    {
        $onboarding = app(StoreOnboardingService::class);
        $store = $onboarding->provisionStore([
            'name'             => 'Omega Tech',
            'slug'             => 'omega-tech',
            'edition'          => 'mobile_electronics',
            'owner_name'       => 'Omega Boss',
            'owner_phone'      => '09188884444',
            'owner_password'   => 'omega1234',
            'owner_pos_pin'    => '7777',
            'default_language' => 'my',
        ]);

        $owner = User::where('phone', '09188884444')->first();
        $this->assertNotNull($owner);

        // Access store admin dashboard
        $dashResponse = $this->actingAs($owner)->get('/store/' . $store->slug . '/admin');
        $dashResponse->assertRedirect(route('store.admin.dashboard', ['store_slug' => $store->slug]));

        $dashFollow = $this->actingAs($owner)->get(route('store.admin.dashboard', ['store_slug' => $store->slug]));
        $dashFollow->assertOk();

        // Access POS
        $posResponse = $this->actingAs($owner)->get('/store/' . $store->slug . '/pos');
        $posResponse->assertOk();
    }

    public function test_hardware_matrix_service_generates_valid_escpos_commands(): void
    {
        $receipt58 = HardwareMatrixService::generateEscPosTestReceipt('58mm', 'Test Shop 58');
        $this->assertStringContainsString("\x1B@", $receipt58); // Init printer
        $this->assertStringContainsString("Test Shop 58", $receipt58);
        $this->assertStringContainsString("58MM ESC/POS", $receipt58);
        $this->assertStringContainsString("\x1DV\x41\x00", $receipt58); // Cut paper

        $receipt80 = HardwareMatrixService::generateEscPosTestReceipt('80mm', 'Test Shop 80');
        $this->assertStringContainsString("80MM ESC/POS", $receipt80);
        $this->assertStringContainsString("48 Characters/Line", $receipt80);
    }
}
