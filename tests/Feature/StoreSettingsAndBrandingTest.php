<?php

namespace Tests\Feature;

use App\Models\HomeBanner;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreSettingsAndBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_settings_page_renders_section_sidebar(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $manager = User::create([
            'name' => 'Manager A',
            'phone' => '09111111111',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

        $response = $this->actingAs($manager)->get('/store/store-a/admin/settings');

        $response->assertStatus(200);
        $response->assertSee(__('messages.settings_storefront_settings'));
        // Section sidebar replaces the old tab bar (clean Products-style header
        // uses the shared admin-page-title/admin-page-sub pattern).
        $response->assertSee('admin-page-title', false);
        // Sidebar labels are localized (default locale is my) — assert the translated values.
        $response->assertSee(__('messages.settings_general'));
        $response->assertSee(__('messages.settings_contact'));
        $response->assertSee(__('messages.settings_delivery'));
        $response->assertSee(__('messages.settings_how_to_order'));
        $response->assertSee(__('messages.settings_view_storefront'));
        $response->assertDontSee('role="tablist"', false);
        $response->assertDontSee('x-collapse', false);
    }

    public function test_store_manager_can_update_own_storefront_settings(): void
    {
        Storage::fake('public');

        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $manager = User::create([
            'name' => 'Manager A',
            'phone' => '09111111111',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

        $logo = UploadedFile::fake()->create('logo.png', 10, 'image/png');

        // General section: identity fields.
        $this->actingAs($manager)->post('/store/store-a/admin/settings', [
            'section' => 'general',
            'store_name' => 'Updated Store A',
            'opening_hours' => '8:00 AM - 8:00 PM',
            'default_language' => 'my',
            'logo' => $logo,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('storefront_settings', [
            'store_id' => $store->id,
            'store_name' => 'Updated Store A',
            'opening_hours' => '8:00 AM - 8:00 PM',
        ]);

        // Contact section: phone.
        $this->actingAs($manager)->post('/store/store-a/admin/settings', [
            'section' => 'contact',
            'phone' => '09999888777',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('storefront_settings', [
            'store_id' => $store->id,
            'phone' => '09999888777',
        ]);
    }

    public function test_store_a_manager_cannot_update_store_b_settings(): void
    {
        $storeA = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $storeB = Store::create(['name' => 'Store B', 'slug' => 'store-b']);

        $managerA = User::create([
            'name' => 'Manager A',
            'phone' => '09111111111',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $managerA->stores()->attach($storeA->id, ['role' => 'store_manager', 'status' => 'active']);

        $response = $this->actingAs($managerA)->post('/store/store-b/admin/settings', [
            'store_name' => 'Hacked Store B',
            'default_language' => 'my',
        ]);

        $response->assertStatus(403);
    }

    public function test_banner_upload_validation_and_crud(): void
    {
        Storage::fake('public');

        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $staff = User::create([
            'name' => 'Staff A',
            'phone' => '09222222222',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $staff->stores()->attach($store->id, ['role' => 'staff', 'status' => 'active']);

        $bannerImage = UploadedFile::fake()->create('banner.jpg', 20, 'image/jpeg');

        $response = $this->actingAs($staff)->post('/store/store-a/admin/banners', [
            'title' => 'Special Promo',
            'page' => 'home',
            'image' => $bannerImage,
            'sort_order' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('home_banners', [
            'store_id' => $store->id,
            'title' => 'Special Promo',
        ]);
    }

    public function test_storefront_home_displays_dynamic_store_settings_and_banners(): void
    {
        $store = Store::create(['name' => 'Store Dynamic', 'slug' => 'store-dynamic']);
        $store->setting()->create([
            'store_name' => 'Dynamic Mobile Store',
            'phone' => '09777666555',
            'opening_hours' => '9:00 AM - 5:00 PM',
            'viber_number' => '09892499955',
            'telegram_username' => '@dynamic_store',
            'delivery_info' => 'Yangon same-day delivery',
            'payment_info' => 'KPay, CBPay, Bank Transfer',
        ]);

        HomeBanner::create([
            'store_id' => $store->id,
            'title' => 'Dynamic Mega Sale',
            'image_path' => 'banners/sale.jpg',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->get('/?store_slug=store-dynamic');

        $response->assertStatus(200);
        $response->assertSee('Dynamic Mobile Store');
        $response->assertSee('09777666555');
        $response->assertSee('viber://chat?number=959892499955', false);
        $response->assertSee('https://t.me/dynamic_store', false);
        // Dynamic settings and banners rendered on homepage.
        $response->assertSee('Dynamic Mobile Store');
        $response->assertSee('09777666555');
        $response->assertSee('Dynamic Mega Sale');
    }

    public function test_store_manager_can_set_footer_ad_text(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $manager = User::create([
            'name' => 'Manager A',
            'phone' => '09111111112',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

        $response = $this->actingAs($manager)->post('/store/store-a/admin/settings', [
            'section' => 'delivery',
            'footer_ad_text' => 'Our software: contact 09xxxxxxxxx',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('storefront_settings', [
            'store_id' => $store->id,
            'footer_ad_text' => 'Our software: contact 09xxxxxxxxx',
        ]);
    }

    public function test_settings_page_renders_footer_ad_text_field(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $manager = User::create([
            'name' => 'Manager A',
            'phone' => '09111111113',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

        $response = $this->actingAs($manager)->get('/store/store-a/admin/settings/delivery');

        $response->assertOk();
        $response->assertSee('name="footer_ad_text"', false);
        $response->assertSee('Footer Ad Text');
    }

    public function test_footer_settings_page_renders_combined_live_preview(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $store->setting()->create([
            'store_name' => 'Store A',
            'tagline' => 'Live footer preview tagline',
            'footer_ad_text' => 'Live preview ad text',
        ]);
        $manager = User::create([
            'name' => 'Manager A',
            'phone' => '09111111114',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

        $response = $this->actingAs($manager)->get('/store/store-a/admin/settings/footer');

        $response->assertOk();
        // Sidebar nav shows the new Footer section.
        $response->assertSee(__('messages.settings_footer'));
        // Combined live preview renders the REAL storefront footer component.
        $response->assertSee('Live footer preview tagline');
        $response->assertSee('Live preview ad text');
        $response->assertSee(__('messages.customer_service'));
        // Edit-source quick links.
        $response->assertSee(__('messages.settings_footer_sources'));
        $response->assertSee('/store/store-a/admin/settings/contact', false);
        $response->assertSee('/store/store-a/admin/settings/delivery', false);
    }
}
