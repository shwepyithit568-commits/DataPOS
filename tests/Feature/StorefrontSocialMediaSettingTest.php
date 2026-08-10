<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin-editable footer "Follow Us" social media links (Facebook, YouTube, TikTok).
 *
 * @see resources/views/layouts/storefront/app.blade.php (footer contact card)
 * @see resources/views/admin/settings/edit.blade.php (Contact tab)
 */
class StorefrontSocialMediaSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_form_renders_social_media_fields(): void
    {
        $store = $this->storeWithManager('store-a', '09881110001');

        $response = $this->actingAs($store['manager'])->get('/store/store-a/admin/settings/contact');

        $response->assertOk();
        $response->assertSee('name="facebook_url"', false);
        $response->assertSee('name="youtube_url"', false);
        $response->assertSee('name="tiktok_url"', false);
    }

    public function test_admin_can_save_social_media_urls(): void
    {
        $store = $this->storeWithManager('store-a', '09881110002');

        $response = $this->actingAs($store['manager'])->post('/store/store-a/admin/settings', [
            'section' => 'contact',
            'facebook_url' => 'https://facebook.com/acdc.mobile',
            'youtube_url' => 'https://youtube.com/@acdc',
            'tiktok_url' => 'https://tiktok.com/@acdc',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('storefront_settings', [
            'store_id' => $store['store']->id,
            'facebook_url' => 'https://facebook.com/acdc.mobile',
            'youtube_url' => 'https://youtube.com/@acdc',
            'tiktok_url' => 'https://tiktok.com/@acdc',
        ]);
    }

    public function test_social_media_fields_are_nullable_and_clearable(): void
    {
        $store = $this->storeWithManager('store-a', '09881110003');

        $this->actingAs($store['manager'])->post('/store/store-a/admin/settings', [
            'section' => 'contact',
            'facebook_url' => 'https://facebook.com/acdc.mobile',
            'youtube_url' => 'https://youtube.com/@acdc',
            'tiktok_url' => 'https://tiktok.com/@acdc',
        ])->assertSessionHasNoErrors();

        $this->assertSame('https://facebook.com/acdc.mobile', $store['store']->fresh()->setting->facebook_url);

        // Clearing the fields removes them.
        $this->actingAs($store['manager'])->post('/store/store-a/admin/settings', [
            'section' => 'contact',
            'facebook_url' => '',
            'youtube_url' => '',
            'tiktok_url' => '',
        ])->assertSessionHasNoErrors();

        $setting = $store['store']->fresh()->setting;
        $this->assertNull($setting->facebook_url);
        $this->assertNull($setting->youtube_url);
        $this->assertNull($setting->tiktok_url);
    }

    public function test_social_media_url_rejects_over_255_characters(): void
    {
        $store = $this->storeWithManager('store-a', '09881110004');

        $response = $this->actingAs($store['manager'])->post('/store/store-a/admin/settings', [
            'section' => 'contact',
            'facebook_url' => 'https://facebook.com/' . str_repeat('x', 240),
        ]);

        $response->assertSessionHasErrors(['facebook_url']);
    }

    public function test_storefront_ignores_unsafe_social_url(): void
    {
        $store = $this->makeStore();
        $store->setting()->create([
            'store_name' => 'Social Store',
            'facebook_url' => 'javascript:alert(1)',
            'youtube_url' => 'https://youtube.com/@acdc',
        ]);

        $response = $this->get('/?store_slug=' . $store->slug);

        $response->assertOk();
        // Unsafe link is dropped; safe one still renders.
        $response->assertDontSee('javascript:', false);
        $response->assertDontSee('href="javascript:alert(1)"', false);
        $response->assertSee('href="https://youtube.com/@acdc"', false);
        // "Follow Us" heading still shows because at least one safe link is set.
        $response->assertSee(__('messages.follow_us'));
    }

    public function test_cannot_update_another_store_social_media_setting(): void
    {
        $storeA = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $storeB = Store::create(['name' => 'Store B', 'slug' => 'store-b']);

        $managerA = User::factory()->create(['phone' => '09881110005', 'role' => 'customer']);
        $managerA->stores()->attach($storeA->id, ['role' => 'store_manager', 'status' => 'active']);

        $response = $this->actingAs($managerA)->post('/store/store-b/admin/settings', [
            'store_name' => 'Hacked',
            'default_language' => 'my',
            'facebook_url' => 'https://facebook.com/evil',
        ]);

        $response->assertStatus(403);
        $this->assertNull($storeB->fresh()->setting);
    }

    private function makeStore(): Store
    {
        return Store::create([
            'name' => 'Social Store',
            'slug' => 'social-store',
            'is_active' => true,
        ]);
    }

    /**
     * Helper: create a store with a single attached manager.
     *
     * @return array{store: Store, manager: User}
     */
    private function storeWithManager(string $slug, string $phone): array
    {
        $store = Store::create(['name' => 'Store A', 'slug' => $slug]);
        $manager = User::factory()->create(['phone' => $phone, 'role' => 'customer']);
        $manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

        return ['store' => $store, 'manager' => $manager];
    }
}
