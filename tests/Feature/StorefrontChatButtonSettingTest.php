<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin-editable floating "Chat with us" button (icon + label) and the chat
 * channel popup it opens.
 *
 * @see resources/views/layouts/storefront/app.blade.php (floating contact button)
 * @see resources/views/admin/settings/sections/contact.blade.php (Contact section)
 */
class StorefrontChatButtonSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_form_renders_chat_button_fields(): void
    {
        $store = $this->storeWithManager('store-a', '09881110001');

        $response = $this->actingAs($store['manager'])->get('/store/store-a/admin/settings/contact');

        $response->assertOk();
        $response->assertSee('name="chat_button_label"', false);
        $response->assertSee('name="chat_button_icon"', false);
        $response->assertSee('name="chat_button_icon_image"', false);
    }

    public function test_admin_can_save_chat_button_label_and_icon(): void
    {
        $store = $this->storeWithManager('store-a', '09881110002');

        $response = $this->actingAs($store['manager'])->post('/store/store-a/admin/settings', [
            'section' => 'contact',
            'chat_button_label' => 'မက်ဆေ့ပို့မယ်',
            'chat_button_icon' => '📞',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('storefront_settings', [
            'store_id' => $store['store']->id,
            'chat_button_label' => 'မက်ဆေ့ပို့မယ်',
            'chat_button_icon' => '📞',
        ]);
    }

    public function test_chat_button_fields_are_nullable_and_clearable(): void
    {
        $store = $this->storeWithManager('store-a', '09881110003');

        $this->actingAs($store['manager'])->post('/store/store-a/admin/settings', [
            'section' => 'contact',
            'chat_button_label' => 'Message us',
            'chat_button_icon' => '📱',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Message us', $store['store']->fresh()->setting->chat_button_label);

        // Clearing the fields restores the default icon fallback.
        $this->actingAs($store['manager'])->post('/store/store-a/admin/settings', [
            'section' => 'contact',
            'chat_button_label' => '',
            'chat_button_icon' => '',
        ])->assertSessionHasNoErrors();

        $setting = $store['store']->fresh()->setting;
        $this->assertNull($setting->chat_button_label);
        $this->assertNull($setting->chat_button_icon);
    }

    public function test_chat_button_label_rejects_over_50_characters(): void
    {
        $store = $this->storeWithManager('store-a', '09881110004');

        $response = $this->actingAs($store['manager'])->post('/store/store-a/admin/settings', [
            'section' => 'contact',
            'chat_button_label' => str_repeat('y', 51),
        ]);

        $response->assertSessionHasErrors(['chat_button_label']);
    }

    public function test_chat_button_icon_rejects_unknown_value(): void
    {
        $store = $this->storeWithManager('store-a', '09881110005');

        $response = $this->actingAs($store['manager'])->post('/store/store-a/admin/settings', [
            'section' => 'contact',
            'chat_button_icon' => '🚗',
        ]);

        $response->assertSessionHasErrors(['chat_button_icon']);
    }

    public function test_storefront_floating_button_shows_auto_chat_channels(): void
    {
        $store = $this->makeStore();
        $store->setting()->create([
            'store_name' => 'Chat Button Store',
            'viber_number' => '959892499955',
            'telegram_username' => 'osgunlocker',
            'facebook_url' => 'https://facebook.com/acdc.mobile',
        ]);

        $response = $this->get('/?store_slug=' . $store->slug);

        $response->assertOk();
        // Icon-only floating button + popup listing the auto channels.
        $response->assertSee('fixed bottom-[calc(env(safe-area-inset-bottom,0px)+5.5rem)] right-4', false);
        $response->assertSee(__('messages.chat_with_us'));
        $response->assertSee('href="viber://chat?number=959892499955"', false);
        $response->assertSee('href="https://t.me/osgunlocker"', false);
        $response->assertSee('href="https://facebook.com/acdc.mobile"', false);
        // "Get Viber" not-installed fallback inside the Viber row (matches the footer).
        $response->assertSee(__('messages.viber_missing'));
        $response->assertSee('href="https://www.viber.com/download/"', false);
    }

    public function test_storefront_floating_button_falls_back_to_telegram_icon(): void
    {
        $store = $this->makeStore();
        $store->setting()->create([
            'store_name' => 'Chat Button Store',
            'telegram_username' => 'osgunlocker',
        ]);

        $response = $this->get('/?store_slug=' . $store->slug);

        $response->assertOk();
        $response->assertSee('href="https://t.me/osgunlocker"', false);
        $response->assertSee(__('messages.chat_with_us'));
        $response->assertSee('fixed bottom-[calc(env(safe-area-inset-bottom,0px)+5.5rem)] right-4', false);
        // Official Telegram brand icon (Simple Icons SVG) on the floating button.
        $response->assertSee('M11.944 0A12 12 0 0 0 0 12', false);
        $response->assertDontSee('M11.4 0C9.473.028', false); // Viber icon absent — no Viber configured.
        // No Viber configured → no "Get Viber" fallback in the popup.
        $response->assertDontSee('href="https://www.viber.com/download/"', false);
    }

    public function test_storefront_ignores_unsafe_channel_url(): void
    {
        $store = $this->makeStore();
        $store->setting()->create([
            'store_name' => 'Chat Button Store',
            'telegram_username' => 'osgunlocker',
            'chat_channels' => [
                ['label' => 'Evil', 'icon' => '💬', 'href' => 'javascript:alert(1)'],
                ['label' => 'Safe', 'icon' => '✈️', 'href' => 'https://t.me/safe'],
            ],
        ]);

        $response = $this->get('/?store_slug=' . $store->slug);

        $response->assertOk();
        $response->assertDontSee('javascript:', false);
        // Unsafe channel is dropped; the safe one still renders in the popup.
        $response->assertSee('href="https://t.me/safe"', false);
    }

    public function test_cannot_update_another_store_chat_button_setting(): void
    {
        $storeA = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $storeB = Store::create(['name' => 'Store B', 'slug' => 'store-b']);

        $managerA = User::factory()->create(['phone' => '09881110006', 'role' => 'customer']);
        $managerA->stores()->attach($storeA->id, ['role' => 'store_manager', 'status' => 'active']);

        $response = $this->actingAs($managerA)->post('/store/store-b/admin/settings', [
            'section' => 'contact',
            'chat_button_label' => 'Hacked',
        ]);

        $response->assertStatus(403);
        $this->assertNull($storeB->fresh()->setting);
    }

    private function makeStore(): Store
    {
        return Store::create([
            'name' => 'Chat Button Store',
            'slug' => 'chat-button-store',
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
