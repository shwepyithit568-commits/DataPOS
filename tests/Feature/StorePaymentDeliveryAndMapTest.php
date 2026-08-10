<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\StoreDeliveryMethod;
use App\Models\StorePaymentMethod;
use App\Models\StorefrontSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorePaymentDeliveryAndMapTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;
    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'Store A',
            'slug' => 'store-a',
            'is_active' => true,
        ]);

        StorefrontSetting::create([
            'store_id' => $this->store->id,
            'store_name' => 'Store A',
            'address' => 'Yuzana Plaza, 4th Floor, Yangon',
            'phone' => '09123456789',
            'viber_number' => '09123456789',
            'telegram_username' => 'storeA',
            'delivery_info' => 'Yangon delivery 2,000 Ks. Nationwide via Express.',
            'payment_info' => 'KBZ Pay / Wave Pay accepted.',
            'default_language' => 'en',
        ]);

        $this->manager = User::create([
            'name' => 'Manager A',
            'phone' => '09111111111',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);
    }

    // ---------------------------------------------------------------------
    // Payment methods — store-scoped CRUD
    // ---------------------------------------------------------------------

    public function test_admin_delivery_page_renders_method_cards_and_notes_form(): void
    {
        StorePaymentMethod::create(['store_id' => $this->store->id, 'name' => 'KPay', 'icon_type' => 'builtin', 'icon_value' => 'kpay', 'sort_order' => 1]);
        StoreDeliveryMethod::create(['store_id' => $this->store->id, 'name' => 'Pickup', 'icon' => '🏬', 'sort_order' => 1]);

        $response = $this->actingAs($this->manager)->get('/store/store-a/admin/settings/delivery');

        $response->assertOk();
        $response->assertSee('Payment Methods');
        $response->assertSee('Delivery Methods');
        $response->assertSee('KPay');
        $response->assertSee('Pickup');
        // The legacy notes live in their own standalone form (not nested inside
        // the section wrapper) — this section must not produce nested <form>s.
        $response->assertSee('name="payment_info"', false);
        $response->assertSee('name="delivery_info"', false);
        $response->assertSee('name="footer_ad_text"', false);
    }

    public function test_manager_can_create_and_activate_payment_methods(): void
    {
        $this->actingAs($this->manager)->post('/store/store-a/admin/settings/payment-methods', [
            'name' => 'KBZ Pay',
            'code' => 'kbz',
            'icon_type' => 'builtin',
            'icon_value' => 'kbz',
            'account_name' => 'U Aung',
            'account_number' => '1234567890',
            'is_active' => '1',
            'sort_order' => 1,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('store_payment_methods', [
            'store_id' => $this->store->id,
            'name' => 'KBZ Pay',
            'is_active' => true,
        ]);
    }

    public function test_payment_method_requires_a_name(): void
    {
        $this->actingAs($this->manager)->post('/store/store-a/admin/settings/payment-methods', [
            'name' => '',
            'icon_type' => 'initials',
        ])->assertSessionHasErrors('name');

        $this->assertDatabaseCount('store_payment_methods', 0);
    }

    public function test_manager_can_update_and_delete_payment_method(): void
    {
        $pm = StorePaymentMethod::create([
            'store_id' => $this->store->id,
            'name' => 'Wave Pay',
            'icon_type' => 'builtin',
            'icon_value' => 'wavepay',
            'sort_order' => 1,
        ]);

        $this->actingAs($this->manager)->put('/store/store-a/admin/settings/payment-methods/' . $pm->id, [
            'name' => 'WavePay (Updated)',
            'icon_type' => 'initials',
            'is_active' => '1',
            'sort_order' => 2,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('store_payment_methods', [
            'id' => $pm->id,
            'name' => 'WavePay (Updated)',
            'sort_order' => 2,
        ]);

        $this->actingAs($this->manager)->delete('/store/store-a/admin/settings/payment-methods/' . $pm->id)
            ->assertRedirect();

        $this->assertDatabaseMissing('store_payment_methods', ['id' => $pm->id]);
    }

    public function test_cross_store_payment_method_access_is_rejected(): void
    {
        $storeB = Store::create(['name' => 'Store B', 'slug' => 'store-b']);
        $pm = StorePaymentMethod::create([
            'store_id' => $storeB->id,
            'name' => 'Store B Pay',
            'icon_type' => 'initials',
        ]);

        // Manager of store-a cannot edit/delete store-b's method.
        $this->actingAs($this->manager)->put('/store/store-a/admin/settings/payment-methods/' . $pm->id, [
            'name' => 'Hacked',
            'icon_type' => 'initials',
        ])->assertStatus(404);

        $this->actingAs($this->manager)->delete('/store/store-a/admin/settings/payment-methods/' . $pm->id)
            ->assertStatus(404);

        $this->assertDatabaseHas('store_payment_methods', ['id' => $pm->id, 'name' => 'Store B Pay']);
    }

    public function test_unused_payment_icon_is_cleaned_up_on_delete(): void
    {
        Storage::fake('public');

        $pm = StorePaymentMethod::create([
            'store_id' => $this->store->id,
            'name' => 'Custom Pay',
            'icon_type' => 'custom',
            'icon_path' => 'payment-icons/test-icon.png',
            'sort_order' => 1,
        ]);
        Storage::disk('public')->put('payment-icons/test-icon.png', 'fake');

        $this->actingAs($this->manager)->delete('/store/store-a/admin/settings/payment-methods/' . $pm->id)
            ->assertRedirect();

        Storage::disk('public')->assertMissing('payment-icons/test-icon.png');
    }

    public function test_custom_payment_icon_upload_works(): void
    {
        Storage::fake('public');

        $icon = UploadedFile::fake()->image('kpay-icon.png', 100, 100);

        $this->actingAs($this->manager)->post('/store/store-a/admin/settings/payment-methods', [
            'name' => 'KPay',
            'icon_type' => 'custom',
            'icon_image' => $icon,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $pm = StorePaymentMethod::where('store_id', $this->store->id)->first();
        $this->assertNotNull($pm);
        $this->assertNotNull($pm->icon_path);
        $this->assertStringStartsWith('payment-icons/', $pm->icon_path);
        Storage::disk('public')->assertExists($pm->icon_path);
    }

    // ---------------------------------------------------------------------
    // Payment methods — storefront rendering
    // ---------------------------------------------------------------------

    public function test_active_payment_methods_render_in_order_on_how_to_order(): void
    {
        StorePaymentMethod::create(['store_id' => $this->store->id, 'name' => 'Second Pay', 'icon_type' => 'initials', 'sort_order' => 2]);
        StorePaymentMethod::create(['store_id' => $this->store->id, 'name' => 'First Pay', 'icon_type' => 'initials', 'sort_order' => 1]);
        StorePaymentMethod::create(['store_id' => $this->store->id, 'name' => 'Hidden Pay', 'icon_type' => 'initials', 'is_active' => false, 'sort_order' => 3]);

        $response = $this->get('/how-to-order?store_slug=store-a');

        $response->assertStatus(200);
        $response->assertSee('First Pay');
        $response->assertSee('Second Pay');
        $response->assertDontSee('Hidden Pay');

        // Order: first pay before second pay in the rendered HTML.
        $this->assertTrue(
            strpos($response->getContent(), 'First Pay') < strpos($response->getContent(), 'Second Pay')
        );
    }

    public function test_public_account_details_hidden_by_default(): void
    {
        StorePaymentMethod::create([
            'store_id' => $this->store->id,
            'name' => 'KBZ Pay',
            'icon_type' => 'builtin',
            'icon_value' => 'kbz',
            'account_name' => 'U Aung',
            'account_number' => '09999999999',
            'show_account_details' => false,
        ]);

        $response = $this->get('/how-to-order?store_slug=store-a');
        $response->assertStatus(200);
        $response->assertSee('KBZ Pay');
        // Account details must NOT appear while show_account_details is off.
        $response->assertDontSee('U Aung');
        $response->assertDontSee('09999999999');
    }

    public function test_account_details_render_when_explicitly_enabled(): void
    {
        StorePaymentMethod::create([
            'store_id' => $this->store->id,
            'name' => 'KBZ Pay',
            'icon_type' => 'builtin',
            'icon_value' => 'kbz',
            'account_name' => 'U Aung',
            'account_number' => '09999999999',
            'show_account_details' => true,
        ]);

        $response = $this->get('/how-to-order?store_slug=store-a');
        $response->assertStatus(200);
        $response->assertSee('U Aung');
        $response->assertSee('09999999999');
    }

    public function test_legacy_payment_info_fallback_when_no_structured_methods(): void
    {
        $response = $this->get('/how-to-order?store_slug=store-a');

        $response->assertStatus(200);
        // No structured methods exist yet → legacy textarea is the fallback.
        $response->assertSee('KBZ Pay / Wave Pay accepted.');
    }

    // ---------------------------------------------------------------------
    // Delivery methods
    // ---------------------------------------------------------------------

    public function test_delivery_method_crud_and_order(): void
    {
        $this->actingAs($this->manager)->post('/store/store-a/admin/settings/delivery-methods', [
            'name' => 'Express Courier',
            'icon' => '🚀',
            'service_area' => 'Yangon',
            'estimated_time' => 'Same day',
            'fee_note' => '2,000 Ks',
            'sort_order' => 1,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('store_delivery_methods', [
            'store_id' => $this->store->id,
            'name' => 'Express Courier',
            'estimated_time' => 'Same day',
        ]);

        $dm = StoreDeliveryMethod::where('store_id', $this->store->id)->first();
        $this->actingAs($this->manager)->put('/store/store-a/admin/settings/delivery-methods/' . $dm->id, [
            'name' => 'Express (Updated)',
            'icon' => '🚚',
            'sort_order' => 2,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('store_delivery_methods', ['id' => $dm->id, 'name' => 'Express (Updated)']);

        $this->actingAs($this->manager)->delete('/store/store-a/admin/settings/delivery-methods/' . $dm->id)
            ->assertRedirect();
        $this->assertDatabaseMissing('store_delivery_methods', ['id' => $dm->id]);
    }

    public function test_inactive_delivery_method_not_rendered(): void
    {
        StoreDeliveryMethod::create(['store_id' => $this->store->id, 'name' => 'Pickup', 'icon' => '🏬', 'is_active' => true, 'sort_order' => 1]);
        StoreDeliveryMethod::create(['store_id' => $this->store->id, 'name' => 'Old Courier', 'icon' => '🚚', 'is_active' => false, 'sort_order' => 2]);

        $response = $this->get('/how-to-order?store_slug=store-a');

        $response->assertStatus(200);
        $response->assertSee('Pickup');
        $response->assertDontSee('Old Courier');
    }

    public function test_legacy_delivery_info_fallback_when_no_structured_methods(): void
    {
        $response = $this->get('/how-to-order?store_slug=store-a');
        $response->assertStatus(200);
        $response->assertSee('Yangon delivery 2,000 Ks.');
    }

    // ---------------------------------------------------------------------
    // Google Maps / location
    // ---------------------------------------------------------------------

    public function test_exact_map_url_can_be_saved(): void
    {
        $this->actingAs($this->manager)->post('/store/store-a/admin/settings', [
            'section' => 'contact',
            'google_maps_url' => 'https://maps.app.goo.gl/ugrW3JVwLzCmjQP89',
            'map_enabled' => '1',
            'map_latitude' => '17.3515',
            'map_longitude' => '95.0125',
            'map_title' => 'DataPOS',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('storefront_settings', [
            'store_id' => $this->store->id,
            'google_maps_url' => 'https://maps.app.goo.gl/ugrW3JVwLzCmjQP89',
            'map_enabled' => true,
        ]);
    }

    public function test_unsafe_or_non_google_map_url_is_rejected(): void
    {
        $this->actingAs($this->manager)->post('/store/store-a/admin/settings', [
            'section' => 'contact',
            'google_maps_url' => 'javascript:alert(1)',
        ])->assertSessionHasErrors('google_maps_url');

        $this->actingAs($this->manager)->post('/store/store-a/admin/settings', [
            'section' => 'contact',
            'google_maps_url' => 'https://evil.example.com/map',
        ])->assertSessionHasErrors('google_maps_url');

        $this->assertDatabaseHas('storefront_settings', [
            'store_id' => $this->store->id,
            'google_maps_url' => null,
        ]);
    }

    public function test_invalid_latitude_is_rejected(): void
    {
        $this->actingAs($this->manager)->post('/store/store-a/admin/settings', [
            'section' => 'contact',
            'map_latitude' => '99.99', // outside -90..90
            'map_longitude' => '95.0125',
        ])->assertSessionHasErrors('map_latitude');
    }

    public function test_enabled_map_renders_location_card_with_exact_link(): void
    {
        $this->store->setting->update([
            'map_enabled' => true,
            'google_maps_url' => 'https://maps.app.goo.gl/ugrW3JVwLzCmjQP89',
            'map_embed_enabled' => false,
        ]);

        $response = $this->get('/how-to-order?store_slug=store-a');

        $response->assertStatus(200);
        $response->assertSee('https://maps.app.goo.gl/ugrW3JVwLzCmjQP89', false);
        // No iframe because embed is disabled / no exact coords.
        $response->assertDontSee('<iframe', false);
    }

    public function test_embed_renders_when_coordinates_exist(): void
    {
        $this->store->setting->update([
            'map_enabled' => true,
            'google_maps_url' => 'https://maps.app.goo.gl/ugrW3JVwLzCmjQP89',
            'map_latitude' => 17.0462098,
            'map_longitude' => 95.6441479,
            'map_title' => 'DataPOS',
            'map_embed_enabled' => true,
        ]);

        $response = $this->get('/how-to-order?store_slug=store-a');

        $response->assertStatus(200);
        // Privacy-friendly lazy embed pointing at the exact pin coordinates.
        $response->assertSee('google.com/maps?q=17.0462098%2C95.6441479&amp;z=17&amp;output=embed', false);
        $response->assertSee('loading="lazy"', false);
        $response->assertSee('title="DataPOS"', false);
        $response->assertSee('allowfullscreen', false);
    }

    public function test_disabled_map_does_not_render_iframe(): void
    {
        $this->store->setting->update([
            'map_enabled' => false,
            'google_maps_url' => 'https://maps.app.goo.gl/ugrW3JVwLzCmjQP89',
            'map_embed_enabled' => true,
            'map_latitude' => 17.3515,
            'map_longitude' => 95.0125,
        ]);

        $response = $this->get('/how-to-order?store_slug=store-a');

        $response->assertStatus(200);
        $response->assertDontSee('<iframe', false);
    }

    public function test_missing_exact_location_falls_back_to_address_search(): void
    {
        // No exact URL/coords — mapUrl() builds the legacy address-search link.
        $this->store->setting->update(['map_enabled' => true, 'google_maps_url' => null]);

        $response = $this->get('/how-to-order?store_slug=store-a');

        $response->assertStatus(200);
        // `&` is HTML-escaped to `&amp;` inside the rendered href attribute.
        $response->assertSee('google.com/maps/search/?api=1&amp;query=Yuzana%20Plaza', false);
    }

    // ---------------------------------------------------------------------
    // How to Order consistency
    // ---------------------------------------------------------------------

    public function test_step_count_matches_rendered_steps(): void
    {
        $this->store->setting->update([
            'how_to_steps' => [
                ['icon' => '1️⃣', 'title' => 'Step One', 'desc' => 'First'],
                ['icon' => '2️⃣', 'title' => 'Step Two', 'desc' => 'Second'],
                ['icon' => '3️⃣', 'title' => 'Step Three', 'desc' => 'Third'],
            ],
        ]);

        $response = $this->get('/how-to-order?store_slug=store-a');

        $response->assertStatus(200);
        $response->assertSee('Step One');
        $response->assertSee('Step Two');
        $response->assertSee('Step Three');
        $response->assertDontSee('Step Four');
        // The heading uses the dynamic count — hero title built with count 3.
        $response->assertSee('3');
    }

    public function test_intro_embedded_numbered_steps_are_stripped_from_hero(): void
    {
        // Stored intro lists 6 numbered steps while only 5 steps are active —
        // the rendered hero must never contradict the rendered step cards.
        $this->store->setting->update([
            'how_to_steps' => [
                ['icon' => '1️⃣', 'title' => 'A', 'desc' => 'x'],
                ['icon' => '2️⃣', 'title' => 'B', 'desc' => 'x'],
                ['icon' => '3️⃣', 'title' => 'C', 'desc' => 'x'],
                ['icon' => '4️⃣', 'title' => 'D', 'desc' => 'x'],
                ['icon' => '5️⃣', 'title' => 'E', 'desc' => 'x'],
            ],
            'how_to_intro' => "Lead paragraph.\n\n1️⃣ Step one\n2️⃣ Step two\n3️⃣ Step three\n4️⃣ Step four\n5️⃣ Step five\n6️⃣ Step six\n\n❓ Closing note.",
        ]);

        $response = $this->get('/how-to-order?store_slug=store-a');

        $response->assertStatus(200);
        $response->assertSee('Lead paragraph.');
        $response->assertSee('Closing note.');
        // The embedded numbered list is stripped so it can't contradict the cards.
        $response->assertDontSee('Step six');
        $response->assertDontSee('Step five');
        // Heading reflects the actual active step count (5, not 6).
        $response->assertSee('Order in 5 Simple Steps');
    }

    public function test_blank_steps_are_dropped_on_save(): void
    {
        $this->actingAs($this->manager)->post('/store/store-a/admin/settings', [
            'section' => 'how-to-order',
            'how_to_intro' => 'Simple intro.',
            'how_to_steps' => [
                ['icon' => '1️⃣', 'title' => 'Real Step', 'desc' => 'Desc'],
                ['icon' => '', 'title' => '', 'desc' => ''],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $steps = $this->store->setting->fresh()->how_to_steps;
        $this->assertCount(1, $steps);
        $this->assertEquals('Real Step', $steps[0]['title']);
    }

    public function test_structured_payment_and_delivery_used_on_order_builder(): void
    {
        StorePaymentMethod::create(['store_id' => $this->store->id, 'name' => 'KPay (Builder)', 'icon_type' => 'builtin', 'icon_value' => 'kpay', 'sort_order' => 1]);
        StoreDeliveryMethod::create(['store_id' => $this->store->id, 'name' => 'Bus Gate (Builder)', 'icon' => '🚌', 'sort_order' => 1]);

        $response = $this->get('/order-builder?store_slug=store-a');

        $response->assertStatus(200);
        $response->assertSee('KPay (Builder)');
        $response->assertSee('Bus Gate (Builder)');
    }
}
