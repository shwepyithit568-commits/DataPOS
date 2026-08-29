<?php

namespace Tests\Feature\Storefront;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\StorefrontSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase T5 — Layout Bundle Componentization
 *
 * The active theme's approved composition actually changes the rendered
 * storefront: product-card variant, nav style and header accent. Same data
 * contract, different presentation — verified via distinctive markup markers.
 */
class ThemeComponentRenderTest extends TestCase
{
    use RefreshDatabase;

    private function makeStore(string $themePreset): Store
    {
        $store = Store::create([
            'name'      => 'Theme ' . $themePreset,
            'slug'      => 'theme-' . $themePreset . '-' . uniqid(),
            'is_active' => true,
        ]);

        $store->setting()->create([
            'store_name'       => $store->name,
            'tagline'          => 'Quality Products',
            'phone'            => '09123456789',
            'theme_preset'     => $themePreset,
            'theme_primary_color' => '#0ea5e9',
            'theme_accent_color'  => '#7c3aed',
            'theme_header_bg'     => '#ffffff',
            'theme_body_bg'       => '#f8fafc',
            'theme_glow_style'    => 'vivid',
            'theme_dark_mode'     => 'auto',
            'font_preset'         => 'outfit',
            'grid_density'        => 'compact',
        ]);

        // One featured, in-stock product so the home renders product cards.
        $category = Category::create(['store_id' => $store->id, 'name' => 'Gadgets', 'slug' => 'gadgets']);
        $brand = Brand::create(['store_id' => $store->id, 'name' => 'BrandX', 'slug' => 'brand-x']);

        Product::create([
            'store_id'        => $store->id,
            'category_id'     => $category->id,
            'brand_id'        => $brand->id,
            'name'            => 'Featured Widget',
            'slug'            => 'featured-widget',
            'sku'             => 'FW-001',
            'retail_price'    => 2500,
            'wholesale_price' => 0,
            'stock_status'    => 'in_stock',
            'is_ecommerce'    => true,
            'is_featured'     => true,
        ]);

        return $store;
    }

    public function test_midnight_tech_renders_showcase_cards_underline_nav_and_premium_accent(): void
    {
        $store = $this->makeStore('midnight_tech');
        $content = (string) $this->get('/store/' . $store->slug)->getContent();

        // Showcase card shell (padded, not the compact glued grid)
        $this->assertStringContainsString('flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-3', $content);
        // Underline nav (no pill container, uses gap-x-7 link row)
        $this->assertStringContainsString('gap-x-7', $content);
        $this->assertStringNotContainsString('rounded-2xl border border-slate-200/80 bg-white p-1 text-sm font-extrabold', $content);
        // Premium header accent chip (distinctive class string, not the bare
        // "PRO" text — that substring appears in PWA_INSTALL_PROMPT JS)
        $this->assertStringContainsString('uppercase tracking-wider text-white shadow-sm shadow-sky-500/30', $content);
    }

    public function test_marketplace_pro_renders_compact_cards_pill_nav_and_no_premium_accent(): void
    {
        $store = $this->makeStore('marketplace_pro');
        $content = (string) $this->get('/store/' . $store->slug)->getContent();

        // Compact card marker
        $this->assertStringContainsString('data-card-title-row', $content);
        $this->assertStringNotContainsString('flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-3', $content);
        // Pill nav container
        $this->assertStringContainsString('rounded-2xl border border-slate-200/80 bg-white p-1 text-sm font-extrabold', $content);
        // No premium accent
        $this->assertStringNotContainsString('uppercase tracking-wider text-white shadow-sm shadow-sky-500/30', $content);
    }

    public function test_legacy_preset_id_uses_its_canonical_composition(): void
    {
        // 'midnight' is a legacy alias for 'midnight_tech' → showcase card
        $store = $this->makeStore('midnight');
        $content = (string) $this->get('/store/' . $store->slug)->getContent();

        $this->assertStringContainsString('flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-3', $content);
        $this->assertStringContainsString('uppercase tracking-wider text-white shadow-sm shadow-sky-500/30', $content);
    }

    public function test_unknown_preset_id_falls_back_to_default_compact_composition(): void
    {
        $store = $this->makeStore('not_a_real_theme');
        $content = (string) $this->get('/store/' . $store->slug)->getContent();

        $this->assertStringContainsString('data-card-title-row', $content);
        $this->assertStringNotContainsString('uppercase tracking-wider text-white shadow-sm shadow-sky-500/30', $content);
    }
}
