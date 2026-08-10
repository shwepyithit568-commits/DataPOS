<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\StorefrontSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontHowToOrderTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

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
            'opening_hours' => 'Mon - Sat: 9:00 AM - 6:00 PM',
            'viber_number' => '09123456789',
            'telegram_username' => 'storeA',
            'delivery_info' => "Yangon delivery 2,000 Ks.\nNationwide via Express.",
            'payment_info' => 'KBZ Pay / Wave Pay accepted.',
            'default_language' => 'en',
        ]);
    }

    public function test_how_to_order_page_renders_with_steps_and_contact_info(): void
    {
        $response = $this->get('/how-to-order?store_slug=store-a');

        $response->assertStatus(200);
        $response->assertSee('How to Order');
        // New UI renders Burmese default steps (1–5) when the store has no
        // custom how_to_steps — step 1 and step 5 titles.
        $response->assertSee('ပစ္စည်းရှာပါ');
        $response->assertSee('ငွေပေးချေပြီး ပစ္စည်းလက်ခံပါ');
        // Contact info pulled from the store's StorefrontSetting
        $response->assertSee('Yuzana Plaza, 4th Floor, Yangon');
        $response->assertSee('tel:09123456789');
        $response->assertSee('Mon - Sat: 9:00 AM - 6:00 PM');
        $response->assertSee('KBZ Pay / Wave Pay accepted.');
        $response->assertSee('Yangon delivery 2,000 Ks.');
        // Viber / Telegram chat buttons built from settings
        $response->assertSee('viber://chat?number=959123456789');
        $response->assertSee('https://t.me/storeA');
    }

    public function test_how_to_order_page_preserves_store_context_in_links(): void
    {
        $response = $this->get('/how-to-order?store_slug=store-a');

        $response->assertStatus(200);
        $response->assertSee('/order-builder?store_slug=store-a');
        $response->assertSee('/how-to-order?store_slug=store-a');
    }

    public function test_how_to_order_page_requires_a_resolved_store(): void
    {
        // With two active stores and no store context, no fallback store can
        // be resolved — the page 404s instead of showing a wrong store.
        Store::create([
            'name' => 'Store B',
            'slug' => 'store-b',
            'is_active' => true,
        ]);

        $response = $this->get('/how-to-order');

        $response->assertStatus(404);
    }
}
