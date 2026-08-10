<?php

namespace Tests\Feature;

use Database\Seeders\UatSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontNavigationContextTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.allow_uat_seeding' => true]);
        $this->seed(UatSeeder::class);
    }

    public function test_store_context_preserved_across_navigation_links(): void
    {
        // 1. Visit Store A Homepage with store_slug parameter
        $response = $this->get('/?store_slug=datapos-mobile');
        $response->assertStatus(200);
        $response->assertSee('/products?store_slug=datapos-mobile');
        $response->assertSee('/glass-finder?store_slug=datapos-mobile');
        $response->assertSee('/how-to-order?store_slug=datapos-mobile');

        // 2. Visit Store A Products list with store_slug parameter
        $response = $this->get('/products?store_slug=datapos-mobile');
        $response->assertStatus(200);
        $response->assertSee('/store/datapos-mobile/product/');
        $response->assertSee('name="store_slug" value="datapos-mobile"', false);

        // 3. Visit Store A Product detail
        $response = $this->get('/store/datapos-mobile/product/samsung-s24-ultra-tg');
        $response->assertStatus(200);
        $response->assertSee('/products?store_slug=datapos-mobile');
        $response->assertSee('/glass-finder?store_slug=datapos-mobile');
        $response->assertSee('/how-to-order?store_slug=datapos-mobile');

        // 4. Visit Store A Glass Finder
        $response = $this->get('/glass-finder?store_slug=datapos-mobile');
        $response->assertStatus(200);
        $response->assertSee('?store_slug=datapos-mobile');
        $response->assertSee('name="store_slug" value="datapos-mobile"', false);

        // 5. Test Store B isolation preservation
        $response = $this->get('/products?store_slug=uat-store-b');
        $response->assertStatus(200);
        $response->assertSee('/products?store_slug=uat-store-b');
        $response->assertSee('/store/uat-store-b/product/store-b-test-product-in-stock');
        $response->assertDontSee('/store/datapos-mobile/product');
        $response->assertSee('sticky top-0', false); // header bar is sticky (always present, logo optional)
        $response->assertSee('bg-white/95 dark:bg-slate-900/95 backdrop-blur', false); // solid header styling wrapper (Option B)
        $response->assertSee('h-16 sm:h-[4.5rem]', false);
        $response->assertSee('Storefront primary navigation', false);
        $response->assertSee('h-9 w-9 sm:h-10 sm:w-10', false);

        // Header cart icon is hidden below md (mobile already has it in the bottom nav)
        $response->assertSee('hidden md:flex h-11 w-11', false);
    }

    public function test_mobile_hamburger_menu_renders_with_search_and_nav_links(): void
    {
        $response = $this->get('/?store_slug=datapos-mobile');
        $response->assertStatus(200);

        // Alpine state used by the toggle + panel
        $response->assertSee('mobileMenuOpen: false', false);

        // Hamburger toggle button (mobile only, below lg)
        $response->assertSee('data-mobile-menu-button', false);
        $response->assertSee(":aria-expanded=\"mobileMenuOpen ? 'true' : 'false'\"", false);
        $response->assertSee('lg:hidden', false);

        // Slide-down panel with search + nav links (close on click)
        $response->assertSee('data-mobile-nav', false);
        $response->assertSee('name="search"', false);
        $response->assertSee('@click="mobileMenuOpen = false"', false);

        // Panel links keep the store context
        $response->assertSee('/products?store_slug=datapos-mobile', false);
        $response->assertSee('/glass-finder?store_slug=datapos-mobile', false);
        $response->assertSee('/how-to-order?store_slug=datapos-mobile', false);
    }
}
