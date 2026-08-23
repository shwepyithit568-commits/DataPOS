<?php

namespace Tests\Feature;

use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Batch 3 — storefront header branding (tagline) and footer Viber rendering.
 *
 * @see resources/views/layouts/storefront/app.blade.php
 */
class StorefrontBrandingRenderingTest extends TestCase
{
    use RefreshDatabase;

    private function makeStore(string $slug = 'acdc-branding-store'): Store
    {
        return Store::create([
            'name' => 'ACDC Branding Store',
            'slug' => $slug,
            'is_active' => true,
        ]);
    }

    /** Task 13C: configured tagline is rendered under the store name. */
    public function test_header_renders_configured_tagline(): void
    {
        $store = $this->makeStore();
        $store->setting()->create([
            'store_name' => 'ACDC Branding Store',
            'tagline' => 'Genuine Mobile Accessories',
        ]);

        $response = $this->get('/?store_slug=' . $store->slug);

        $response->assertOk();
        $response->assertSee('Genuine Mobile Accessories');
    }

    /** Task 13C: without a tagline the header falls back to the default Myanmar tagline. */
    public function test_header_falls_back_to_default_tagline_when_unset(): void
    {
        $store = $this->makeStore();
        $store->setting()->create(['store_name' => 'ACDC Branding Store']);

        $response = $this->get('/?store_slug=' . $store->slug);

        $response->assertOk();
        $response->assertSee(__('messages.default_tagline'));
        $response->assertDontSee('Genuine Mobile Accessories');
    }

    /** Task 13D: footer Viber link exposes the iOS contact URL and the UA-switch handler. */
    public function test_footer_viber_link_renders_ios_href_and_switch_handler(): void
    {
        $store = $this->makeStore();
        $store->setting()->create([
            'store_name' => 'ACDC Branding Store',
            'viber_number' => '959892499955',
        ]);

        $response = $this->get('/?store_slug=' . $store->slug);

        $response->assertOk();
        // Chat route (Android/desktop) strips the "+"; the iOS route keeps the encoded "+".
        $response->assertSee('href="viber://chat?number=959892499955"', false);
        $response->assertSee('data-ios-href="viber://contact?number=%2B959892499955"', false);
        // The iOS deep-link swap moved to the delegated csp-helpers listener
        // (data-ios-href), so the anchor must no longer carry an inline handler.
        $response->assertDontSee('onclick="if (/iPad|iPhone|iPod/', false);
        // Not-installed fallback: a "Get Viber" link to the official download page
        // (auto-detects Play Store / App Store) renders next to the Viber button.
        $response->assertSee('https://www.viber.com/download/', false);
        $response->assertSee(__('messages.viber_missing'));
    }

    /** Task 13D: without a Viber number no Viber link (and no data-ios-href) is rendered. */
    public function test_footer_omits_viber_link_when_number_unset(): void
    {
        $store = $this->makeStore();
        $store->setting()->create([
            'store_name' => 'ACDC Branding Store',
            'telegram_username' => 'acdc_support',
        ]);

        $response = $this->get('/?store_slug=' . $store->slug);

        $response->assertOk();
        $response->assertDontSee('viber://chat?number=', false);
        $response->assertDontSee('data-ios-href', false);
        // Without a Viber number the "Get Viber" install fallback must not render.
        $response->assertDontSee('https://www.viber.com/download/', false);
        // Telegram link still renders.
        $response->assertSee('https://t.me/acdc_support', false);
    }

    /** Footer ad text renders when configured (replaces the old deployment watermark). */
    public function test_footer_renders_configured_ad_text(): void
    {
        $store = $this->makeStore();
        $store->setting()->create([
            'store_name' => 'ACDC Branding Store',
            'footer_ad_text' => 'Software orders: 09xxxxxxxxx',
        ]);

        $response = $this->get('/?store_slug=' . $store->slug);

        $response->assertOk();
        $response->assertSee('Software orders: 09xxxxxxxxx');
        $response->assertDontSee('DATA POS Commerce');
        $response->assertDontSee('Deployment - Contract');
    }

    /** Footer falls back to the ACDC Mobile copyright when no ad text is set. */
    public function test_footer_falls_back_to_acdc_mobile_copyright(): void
    {
        $store = $this->makeStore();
        $store->setting()->create(['store_name' => 'ACDC Branding Store']);

        $response = $this->get('/?store_slug=' . $store->slug);

        $response->assertOk();
        $response->assertSee('© ' . date('Y') . ' DataPOS');
        $response->assertDontSee('DATA POS Commerce');
    }

    /** Mobile floating contact button renders when Viber is configured (fallback channel). */
    public function test_mobile_floating_contact_button_renders_when_viber_configured(): void
    {
        $store = $this->makeStore();
        $store->setting()->create([
            'store_name' => 'ACDC Branding Store',
            'viber_number' => '959892499955',
        ]);

        $response = $this->get('/?store_slug=' . $store->slug);

        $response->assertOk();
        $response->assertSee('href="viber://chat?number=959892499955"', false);
        $response->assertSee(__('messages.chat_with_us'));
        $response->assertSee('fixed bottom-[calc(env(safe-area-inset-bottom,0px)+5.5rem)] right-4', false);
    }

    /** Mobile floating contact button prefers Telegram (iPhone-friendly) with the Telegram icon. */
    public function test_mobile_floating_contact_button_prefers_telegram_when_configured(): void
    {
        $store = $this->makeStore();
        $store->setting()->create([
            'store_name' => 'ACDC Branding Store',
            'viber_number' => '959892499955',
            'telegram_username' => 'osgunlocker',
        ]);

        $response = $this->get('/?store_slug=' . $store->slug);

        $response->assertOk();
        // The floating button prefers Telegram (iPhone-friendly) with the Telegram icon.
        // (The footer keeps its separate Viber + Telegram links, so Viber still appears there.)
        $response->assertSee('href="https://t.me/osgunlocker"', false);
        // Official Telegram brand icon (Simple Icons SVG) on the floating button.
        $response->assertSee('M11.944 0A12 12 0 0 0 0 12', false);
        $response->assertSee(__('messages.chat_with_us'));
    }

    /** Mobile floating contact button is absent when no contact channel is set. */
    public function test_mobile_floating_contact_button_absent_without_contact(): void
    {
        $store = $this->makeStore();
        $store->setting()->create(['store_name' => 'ACDC Branding Store']);

        $response = $this->get('/?store_slug=' . $store->slug);

        $response->assertOk();
        $response->assertDontSee('fixed bottom-[calc(env(safe-area-inset-bottom,0px)+5.5rem)] right-4', false);
    }

    /** Footer renders the "Follow Us" social media row when links are configured. */
    public function test_footer_renders_social_media_links_when_set(): void
    {
        $store = $this->makeStore();
        $store->setting()->create([
            'store_name' => 'ACDC Branding Store',
            'facebook_url' => 'https://facebook.com/acdc.mobile',
            'youtube_url' => 'https://youtube.com/@acdc',
            'tiktok_url' => 'https://tiktok.com/@acdc',
        ]);

        $response = $this->get('/?store_slug=' . $store->slug);

        $response->assertOk();
        $response->assertSee(__('messages.follow_us'));
        $response->assertSee('href="https://facebook.com/acdc.mobile"', false);
        $response->assertSee('href="https://youtube.com/@acdc"', false);
        $response->assertSee('href="https://tiktok.com/@acdc"', false);
    }

    /** Footer omits the "Follow Us" row entirely when no social links are set. */
    public function test_footer_omits_social_links_when_unset(): void
    {
        $store = $this->makeStore();
        $store->setting()->create(['store_name' => 'ACDC Branding Store']);

        $response = $this->get('/?store_slug=' . $store->slug);

        $response->assertOk();
        $response->assertDontSee(__('messages.follow_us'));
        // No Follow Us hrefs. (Scoped to the Follow Us link shape — the Share
        // fallback menu legitimately links to facebook.com/sharer even when no
        // social links are configured.)
        $response->assertDontSee('href="https://facebook.com/', false);
        $response->assertDontSee('href="https://youtube.com/', false);
        $response->assertDontSee('href="https://tiktok.com/', false);
    }

    /** Footer share button: native Web Share API + per-app fallback menu links. */
    public function test_footer_renders_share_button_with_app_links(): void
    {
        $store = $this->makeStore();
        $store->setting()->create([
            'store_name' => 'ACDC Branding Store',
            'viber_number' => '959892499955',
            'telegram_username' => 'acdc_support',
            'facebook_url' => 'https://facebook.com/acdc.mobile',
        ]);

        $response = $this->get('/?store_slug=' . $store->slug);

        $response->assertOk();
        // Share button wired to the Alpine shareAction component.
        $response->assertSee('x-data="shareAction"', false);
        $response->assertSee(__('messages.share'));
        // Fallback menu targets: Viber forward, Telegram share, Facebook share.
        $response->assertSee('viber://forward?text=', false);
        $response->assertSee('https://t.me/share/url?url=', false);
        $response->assertSee('https://www.facebook.com/sharer/sharer.php?u=', false);
        $response->assertSee(__('messages.copy_link'));
    }

    /** Share fallback menu omits app rows the store has not configured. */
    public function test_footer_share_menu_omits_unconfigured_channels(): void
    {
        $store = $this->makeStore();
        $store->setting()->create(['store_name' => 'ACDC Branding Store']);

        $response = $this->get('/?store_slug=' . $store->slug);

        $response->assertOk();
        // No Viber/Telegram/Facebook configured → no deep links in the menu.
        $response->assertDontSee('viber://forward?text=', false);
        $response->assertDontSee('https://t.me/share/url?url=', false);
        $response->assertDontSee('https://www.facebook.com/sharer/sharer.php?u=', false);
        // Copy link always available.
        $response->assertSee(__('messages.copy_link'));
    }
}
