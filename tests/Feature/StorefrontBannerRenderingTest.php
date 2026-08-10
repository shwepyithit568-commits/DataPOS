<?php

namespace Tests\Feature;

use App\Models\HomeBanner;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Batch 3 — storefront banner rendering (home hero + glass finder hero).
 *
 * @see resources/views/welcome.blade.php
 * @see resources/views/storefront/glass_finder/index.blade.php
 */
class StorefrontBannerRenderingTest extends TestCase
{
    use RefreshDatabase;

    private function makeStore(string $slug = 'acdc-banner-store'): Store
    {
        return Store::create([
            'name' => 'ACDC Banner Store',
            'slug' => $slug,
            'is_active' => true,
        ]);
    }

    private function createBanner(Store $store, array $attributes = []): HomeBanner
    {
        return $store->homeBanners()->create(array_merge([
            'page' => 'home',
            'title' => 'Hero Banner Title',
            'description' => 'Hero banner description.',
            'image_path' => 'banners/hero.jpg',
            'link_url' => null,
            'sort_order' => 0,
            'is_active' => true,
        ], $attributes));
    }

    /** Task 13A: home hero renders active banner image, alt, and description. */
    public function test_home_hero_renders_active_banner_with_image_and_description(): void
    {
        $store = $this->makeStore();
        $this->createBanner($store, [
            'title' => 'Spring Sale 2026',
            'description' => 'Up to 30% off accessories.',
        ]);

        $response = $this->get('/?store_slug=' . $store->slug);

        $response->assertOk();
        $response->assertSee('Spring Sale 2026');
        $response->assertSee('Up to 30% off accessories.');
        $response->assertSee('src="' . asset('storage/banners/hero.jpg') . '"', false);
        $response->assertSee('alt="Spring Sale 2026"', false);
    }

    /** Task 13A: banner without a description falls back to the store display name caption. */
    public function test_home_hero_banner_without_description_uses_store_name_fallback(): void
    {
        $store = $this->makeStore();
        $store->setting()->create(['store_name' => 'ACDC Banner Store']);

        $this->createBanner($store, ['description' => null]);

        $response = $this->get('/?store_slug=' . $store->slug);

        $response->assertOk();
        $response->assertSee('ACDC Banner Store ၏ အထူးသီးသန့်');
    }

    /** Task 13A: inactive banner is not rendered on the home hero. */
    public function test_home_hero_hides_inactive_banner(): void
    {
        $store = $this->makeStore();
        $this->createBanner($store, ['title' => 'Hidden Banner', 'is_active' => false]);

        $response = $this->get('/?store_slug=' . $store->slug);

        $response->assertOk();
        $response->assertDontSee('Hidden Banner');
    }

    /** Task 13B: glass finder hero renders its own active banner. */
    public function test_glass_finder_hero_renders_active_banner(): void
    {
        $store = $this->makeStore();
        $this->createBanner($store, [
            'page' => 'glass_finder',
            'title' => 'Glass Finder Promo',
            'description' => 'Find the right tempered glass.',
            'image_path' => 'banners/gf.jpg',
        ]);

        $response = $this->get('/glass-finder?store_slug=' . $store->slug);

        $response->assertOk();
        $response->assertSee('Glass Finder Promo');
        // The glass-finder hero shows title and image only (no description caption).
        $response->assertSee('src="' . asset('storage/banners/gf.jpg') . '"', false);
    }

    /** Task 13B: banners are scoped per page — home banner never leaks onto glass finder and vice versa. */
    public function test_banners_are_scoped_to_their_page(): void
    {
        $store = $this->makeStore();
        $this->createBanner($store, ['page' => 'home', 'title' => 'Home Only Banner']);
        $this->createBanner($store, ['page' => 'glass_finder', 'title' => 'Glass Finder Only Banner']);

        $homeResponse = $this->get('/?store_slug=' . $store->slug);
        $homeResponse->assertOk();
        $homeResponse->assertSee('Home Only Banner');
        $homeResponse->assertDontSee('Glass Finder Only Banner');

        $gfResponse = $this->get('/glass-finder?store_slug=' . $store->slug);
        $gfResponse->assertOk();
        $gfResponse->assertSee('Glass Finder Only Banner');
        $gfResponse->assertDontSee('Home Only Banner');
    }
}
