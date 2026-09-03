<?php

namespace Tests\Feature;

use App\Models\HomeBanner;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Batch 3 — admin form field recovery (banner description, store tagline).
 *
 * @see resources/views/admin/banners/index.blade.php
 * @see resources/views/admin/banners/edit.blade.php
 * @see resources/views/admin/settings/edit.blade.php
 */
class AdminBannerAndSettingsFormTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'Form Store',
            'slug' => 'form-store',
            'is_active' => true,
        ]);

        $this->manager = User::factory()->create([
            'phone' => '09880001111',
            'role' => 'customer',
        ]);
        $this->manager->stores()->attach($this->store->id, [
            'role' => 'store_manager',
            'status' => 'active',
        ]);
    }

    /** Task 13F: create form renders the description textarea with a 500-char cap. */
    public function test_banner_create_form_renders_description_field_with_maxlength(): void
    {
        $response = $this->actingAs($this->manager)
            ->get('/store/form-store/admin/banners');

        $response->assertOk();
        $response->assertSee('name="description"', false);
        $response->assertSee('maxlength="500"', false);
        // 1920×900 guidance for the banner upload.
        $response->assertSee('1920');
        $response->assertSee('900');
    }

    /** Task 13F: edit form repopulates the existing description via old() fallback. */
    public function test_banner_edit_form_repopulates_description(): void
    {
        $banner = $this->store->homeBanners()->create([
            'page' => 'home',
            'title' => 'Existing Banner',
            'description' => 'Existing description text.',
            'image_path' => 'banners/existing.jpg',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->manager)
            ->get("/store/form-store/admin/banners/{$banner->id}/edit");

        $response->assertOk();
        $response->assertSee('name="description"', false);
        $response->assertSee('maxlength="500"', false);
        $response->assertSee('Existing description text.');
    }

    /** Task 13F: banner list displays the description on the banner card. */
    public function test_banner_list_displays_description_on_card(): void
    {
        $this->store->homeBanners()->create([
            'page' => 'home',
            'title' => 'Card Banner',
            'description' => 'Card description.',
            'image_path' => 'banners/card.jpg',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->manager)
            ->get('/store/form-store/admin/banners');

        $response->assertOk();
        $response->assertSee('Card Banner');
        $response->assertSee('Card description.');
    }

    /** Task 13F: settings form renders the tagline input with a 160-char cap. */
    public function test_settings_form_renders_tagline_field_with_maxlength(): void
    {
        $this->store->setting()->create([
            'store_name' => 'Form Store',
            'tagline' => 'Tagline On Form',
            'default_language' => 'my',
        ]);

        $response = $this->actingAs($this->manager)
            ->get('/store/form-store/admin/settings');

        $response->assertOk();
        $response->assertSee('name="tagline"', false);
        $response->assertSee('maxlength="160"', false);
        $response->assertSee('value="Tagline On Form"', false);
    }

    /** UI/UX Standard v4.1: Compact 2px padding, centered stat cards, card/table view toggles. */
    public function test_banner_page_renders_with_standard_v4_1_compact_layout(): void
    {
        $this->store->homeBanners()->create([
            'page' => 'home',
            'title' => 'Summer Promo Banner',
            'description' => 'Discounts on all phones.',
            'image_path' => 'banners/summer.webp',
            'link_url' => 'https://example.com/summer',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->manager)
            ->get('/store/form-store/admin/banners');

        $response->assertOk();
        // 2px ultra-dense main padding on mobile
        $response->assertSee('p-0.5 sm:p-1', false);
        // Centered row-based stat cards
        $response->assertSee('flex items-center justify-center gap-2.5 sm:gap-3', false);
        // Excel export button / toolbar present
        $response->assertSee('/store/form-store/admin/banners/export', false);
        // Both card view and table view are present in DOM
        $response->assertSee('id="banners-grid"', false);
        $response->assertSee('id="banners-table"', false);
    }

    /** UI/UX Standard v4.1: Banner export supports CSV and XLSX. */
    public function test_banner_export_csv_and_xlsx(): void
    {
        $this->store->homeBanners()->create([
            'page' => 'home',
            'title' => 'Export Promo Banner',
            'description' => 'Test export desc',
            'image_path' => 'banners/test.webp',
            'link_url' => '/products',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        // CSV export
        $csvResponse = $this->actingAs($this->manager)
            ->get('/store/form-store/admin/banners/export?format=csv');

        $csvResponse->assertOk();
        $this->assertStringContainsString('text/csv', $csvResponse->headers->get('content-type', ''));

        // XLSX export
        $xlsxResponse = $this->actingAs($this->manager)
            ->get('/store/form-store/admin/banners/export?format=xlsx');

        $xlsxResponse->assertOk();
        $this->assertStringContainsString('spreadsheetml.sheet', $xlsxResponse->headers->get('content-type', ''));
    }
}
