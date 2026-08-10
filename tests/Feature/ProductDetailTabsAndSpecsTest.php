<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use App\Support\ProductSpecifications;
use App\Support\SafeHtml;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDetailTabsAndSpecsTest extends TestCase
{
    use RefreshDatabase;

    private function makeStore(string $slug = 'spec-store'): Store
    {
        return Store::create(['name' => 'Spec Store', 'slug' => $slug, 'is_active' => true]);
    }

    private function makeProduct(Store $store, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'store_id' => $store->id,
            'sku' => 'SPEC-001',
            'name' => 'Spec Product',
            'slug' => 'spec-product',
            'retail_price' => 10000,
            'wholesale_price' => 7000,
            'stock_status' => 'in_stock',
            'description' => 'A plain description.',
        ], $overrides));
    }

    private function managerFor(Store $store): User
    {
        $manager = User::create([
            'name' => 'Manager',
            'phone' => '09111111111',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

        return $manager;
    }

    /** Direct-order Viber/Telegram buttons rebuild client-side with the selected variant. */
    public function test_direct_order_links_rebuild_with_selected_variant(): void
    {
        $store = $this->makeStore();
        $store->setting()->create([
            'store_name' => 'Spec Store',
            'viber_number' => '09790444128',
            'telegram_username' => '@dataposmobile',
            'default_language' => 'my',
        ]);
        $product = $this->makeProduct($store, [
            'sku' => 'OP-A3S-BC-BLK',
            'name' => 'OPPO A3S Back Cover',
            'slug' => 'oppo-a3s-back-cover',
            'description' => 'A plain description.',
        ]);
        $product->variants()->create([
            'name' => 'Blue',
            'sku' => 'OP-A3S-BC-BLU',
            'retail_price' => 15000,
            'wholesale_price' => 14000,
            'stock_status' => 'in_stock',
        ]);

        $response = $this->get('/store/spec-store/product/' . $product->slug . '?store_slug=spec-store');

        $response->assertOk();
        // Alpine reactive bindings on the Direct Order buttons.
        $response->assertSee(':href="viberHref"', false);
        $response->assertSee(':data-ios-href="viberIosHref"', false);
        $response->assertSee(':href="telegramHref"', false);
        // Channel targets embedded in the component so the draft can be rebuilt.
        $response->assertSee('viberNumber:', false);
        $response->assertSee('959790444128', false);
        $response->assertSee('telegramUser:', false);
        $response->assertSee('dataposmobile', false);
        // No-JS fallback: the server-rendered draft still carries the base product.
        // Blade escapes the ampersand inside the href, matching the live markup.
        $response->assertSee('viber://chat?number=959790444128&amp;draft=', false);
        $response->assertSee('t.me/dataposmobile?text=', false);
        $response->assertSee('OPPO A3S Back Cover', false);
        $response->assertSee('OP-A3S-BC-BLK', false);
        // The reactive draft builder references the selected variant name + price.
        $response->assertSee('get orderName()', false);
        $response->assertSee('get orderDraft()', false);
        $response->assertSee('get viberHref()', false);
        $response->assertSee('get telegramHref()', false);
    }

    /** Case 1 — description + full specs: tabs render with every row. */
    public function test_product_page_renders_description_and_specifications_tabs_with_full_specs(): void
    {
        $store = $this->makeStore();
        $parent = Category::create(['store_id' => $store->id, 'name' => 'Spare Part', 'slug' => 'spare-part']);
        $child = Category::create(['store_id' => $store->id, 'name' => 'Body Frame', 'slug' => 'body-frame', 'parent_id' => $parent->id]);
        $brand = Brand::create(['store_id' => $store->id, 'name' => 'Redmi', 'slug' => 'redmi']);
        $product = $this->makeProduct($store, [
            'sku' => 'RM-N10PRO(5G)-BD-BLK',
            'brand_id' => $brand->id,
            'category_id' => $child->id,
            'warranty' => '1 Month Warranty',
            'description' => 'ပစ္စည်းအသေးစိတ် ဖော်ပြချက်',
        ]);

        $response = $this->get('/store/spec-store/product/' . $product->slug . '?store_slug=spec-store');

        $response->assertOk();
        // ARIA tab pattern.
        $response->assertSee('role="tablist"', false);
        $response->assertSee('role="tab"', false);
        $response->assertSee('id="tab-description"', false);
        $response->assertSee('id="tab-specifications"', false);
        $response->assertSee('id="panel-description"', false);
        $response->assertSee('id="panel-specifications"', false);
        // Both tab labels + the description content.
        $response->assertSee(__('messages.tab_description'));
        $response->assertSee(__('messages.tab_specifications'));
        $response->assertSee('ပစ္စည်းအသေးစိတ် ဖော်ပြချက်');
        // Full specification rows (presenter output).
        $rows = ProductSpecifications::rowsFor($product->fresh(['brand', 'category.parent', 'variants']));
        $labels = array_column($rows, 'label');
        $this->assertContains(__('messages.spec_brand'), $labels);
        $this->assertContains(__('messages.spec_product_type'), $labels);
        $this->assertContains(__('messages.spec_main_category'), $labels);
        $this->assertContains(__('messages.spec_sku'), $labels);
        $this->assertContains(__('messages.spec_warranty'), $labels);
        $this->assertContains(__('messages.spec_stock_status'), $labels);
        foreach ($rows as $row) {
            $response->assertSee($row['value']);
        }
        // Stock status uses the customer-readable wording (Burmese by default).
        $this->assertSame(__('messages.spec_stock_in'), ProductSpecifications::stockLabel('in_stock'));
        $this->assertSame(__('messages.spec_stock_out'), ProductSpecifications::stockLabel('out_of_stock'));
    }

    /** Case 2 — empty description: clean Burmese fallback, tabs still shown when specs exist. */
    public function test_empty_description_shows_fallback_message(): void
    {
        $store = $this->makeStore();
        $brand = Brand::create(['store_id' => $store->id, 'name' => 'Redmi', 'slug' => 'redmi']);
        $product = $this->makeProduct($store, ['brand_id' => $brand->id, 'description' => '']);

        $response = $this->get('/store/spec-store/product/' . $product->slug . '?store_slug=spec-store');

        $response->assertOk();
        $response->assertSee(__('messages.spec_description_empty'));
        $response->assertSee(__('messages.tab_description'));
        $response->assertSee(__('messages.tab_specifications'));
    }

    /** Cases 3–4 — empty brand / no warranty rows are omitted, never "N/A". */
    public function test_specs_omit_empty_brand_and_warranty(): void
    {
        $store = $this->makeStore();
        // No brand, no warranty.
        $product = $this->makeProduct($store, ['brand_id' => null, 'warranty' => null]);

        $rows = ProductSpecifications::rowsFor($product->fresh(['brand', 'category.parent', 'variants']));
        $labels = array_column($rows, 'label');

        $this->assertNotContains(__('messages.spec_brand'), $labels);
        $this->assertNotContains(__('messages.spec_warranty'), $labels);
        $this->assertNotContains('N/A', array_column($rows, 'value'));
        $this->assertNotEmpty($rows); // SKU + stock still present.
    }

    /** Case 5 — variants = []: no variant rows, product-level stock row stays. */
    public function test_product_without_variants_has_no_variant_rows(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store);

        $rows = ProductSpecifications::rowsFor($product->fresh(['brand', 'category.parent', 'variants']));
        $labels = array_column($rows, 'label');

        $this->assertNotContains(__('messages.spec_variant_name'), $labels);
        $this->assertNotContains(__('messages.spec_variant_sku'), $labels);
        $this->assertContains(__('messages.spec_sku'), $labels);
        $this->assertContains(__('messages.spec_stock_status'), $labels);
    }

    /** Case 6 — multiple variants: Variant Name/SKU rows + structured Color/Storage/Size attributes. */
    public function test_multiple_variants_show_variant_rows_and_attribute_groups(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store);
        ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Red 128GB',
            'sku' => 'SPEC-001-RED-128',
            'attributes' => [['label' => 'Color', 'value' => 'Red'], ['label' => 'Storage', 'value' => '128GB']],
            'retail_price' => 10000,
            'stock_status' => 'in_stock',
            'is_default' => true,
        ]);
        ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Blue 128GB',
            'sku' => 'SPEC-001-BLU-128',
            'attributes' => [['label' => 'Color', 'value' => 'Blue'], ['label' => 'Storage', 'value' => '128GB']],
            'retail_price' => 10000,
            'stock_status' => 'in_stock',
        ]);

        $rows = ProductSpecifications::rowsFor($product->fresh(['brand', 'category.parent', 'variants']));
        $byLabel = collect($rows)->keyBy('label');

        $this->assertSame('Red 128GB, Blue 128GB', $byLabel->get(__('messages.spec_variant_name'))['value']);
        $this->assertSame('SPEC-001-RED-128, SPEC-001-BLU-128', $byLabel->get(__('messages.spec_variant_sku'))['value']);
        // Structured attribute groups: Color values joined; Storage de-duplicated.
        $this->assertSame('Red, Blue', $byLabel->get('Color')['value']);
        $this->assertSame('128GB', $byLabel->get('Storage')['value']);
        // No prices in the table.
        $this->assertNotContains('Ks', array_column($rows, 'value'));
    }

    /** Case 7 — very long SKU renders without breaking the page. */
    public function test_very_long_sku_renders(): void
    {
        $store = $this->makeStore();
        $longSku = 'SKU-' . str_repeat('X', 80);
        $product = $this->makeProduct($store, ['sku' => $longSku]);

        $response = $this->get('/store/spec-store/product/' . $product->slug . '?store_slug=spec-store');

        $response->assertOk();
        $response->assertSee($longSku);
    }

    /** Cases 8–10 — Burmese + rich-text + unsafe HTML: sanitized correctly. */
    public function test_rich_text_and_unsafe_html_description_is_sanitized(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store, [
            'description' => '<h2>မြန်မာခေါင်းစဉ်</h2><p>Safe <strong>bold</strong> text</p>'
                . '<script>alert(1)</script>'
                . '<a href="javascript:alert(2)" onclick="x()">bad link</a>'
                . '<a href="https://example.com" rel="noopener">good link</a>'
                . '<img src="x" onerror="alert(3)">',
        ]);

        $response = $this->get('/store/spec-store/product/' . $product->slug . '?store_slug=spec-store');

        $response->assertOk();
        // The page legitimately contains its own <script nonce> blocks and the
        // auto-generated meta description keeps stripped script *text*, so scope
        // the XSS checks to the payload tags/attributes, not bare text.
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertDontSee('javascript:alert', false);
        $response->assertDontSee('onclick=', false);
        $response->assertDontSee('onerror=', false);
        // Formatting preserved (multibyte text is entity-encoded by DOMDocument,
        // so assert tag structure + the raw Burmese text from the meta description).
        $response->assertSee('<h2>', false);
        $response->assertSee('မြန်မာခေါင်းစဉ်');
        $response->assertSee('<strong>bold</strong>', false);
        $response->assertSee('good link', false);
        $response->assertSee('href="https://example.com"', false);
        $response->assertSee('bad link', false); // text kept, attribute stripped

        // Direct sanitizer checks (project-approved method).
        $this->assertSame('<p>ok</p>', SafeHtml::sanitize('<p>ok</p>'));
        $this->assertSame('', SafeHtml::sanitize('<script>alert(1)</script>')); // dropped entirely
        $this->assertSame('plain &amp; safe', SafeHtml::sanitize('plain & safe'));
        $this->assertSame('', SafeHtml::sanitize(null));
    }

    /** Deep-link hash navigation + no-JS fallback structure. */
    public function test_tabs_support_hash_deep_links_and_panels_are_server_rendered(): void
    {
        $store = $this->makeStore();
        $brand = Brand::create(['store_id' => $store->id, 'name' => 'Redmi', 'slug' => 'redmi']);
        $product = $this->makeProduct($store, ['brand_id' => $brand->id]);

        $response = $this->get('/store/spec-store/product/' . $product->slug . '?store_slug=spec-store');

        $response->assertOk();
        // productTabs Alpine component wired (drives hash + keyboard + switching).
        $response->assertSee('x-data="productTabs"', false);
        // Both panels server-rendered in the DOM (content survives without JS).
        $response->assertSee('id="panel-description"', false);
        $response->assertSee('id="panel-specifications"', false);
        // aria-controls / aria-labelledby wiring.
        $response->assertSee('aria-controls="panel-description"', false);
        $response->assertSee('aria-labelledby="tab-specifications"', false);
    }

    /** Minimal product (no brand/category/warranty/description) still shows tabs with just the stock row. */
    public function test_minimal_product_shows_tabs_with_stock_row_only(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store, [
            'description' => '',
            'sku' => '',
            'brand_id' => null,
            'category_id' => null,
            'warranty' => null,
        ]);

        $rows = ProductSpecifications::rowsFor($product->fresh(['brand', 'category.parent', 'variants']));

        // Only the stock-status row survives for a bare product.
        $this->assertCount(1, $rows);
        $this->assertSame(__('messages.spec_stock_status'), $rows[0]['label']);

        $response = $this->get('/store/spec-store/product/' . $product->slug . '?store_slug=spec-store');

        $response->assertOk();
        $response->assertSee('x-data="productTabs"', false);
        $response->assertSee('id="tab-description"', false);
        $response->assertSee('id="tab-specifications"', false);
    }

    /** Admin Part 2 — edit page: preview section + initial description/specs preview. */
    public function test_admin_product_edit_page_renders_description_and_specs_previews(): void
    {
        $store = $this->makeStore();
        $manager = $this->managerFor($store);
        $brand = Brand::create(['store_id' => $store->id, 'name' => 'Redmi', 'slug' => 'redmi']);
        $product = $this->makeProduct($store, [
            'brand_id' => $brand->id,
            'warranty' => '1 Month Warranty',
            'description' => '<p>Editor <strong>content</strong></p>',
        ]);

        $response = $this->actingAs($manager)->get('/store/spec-store/admin/products/' . $product->id . '/edit');

        $response->assertOk();
        // Preview section + both preview labels.
        $response->assertSee(__('messages.product_form_preview_section'));
        $response->assertSee(__('messages.product_form_description_preview'));
        $response->assertSee(__('messages.product_form_specs_preview'));
        // Initial description preview is server-rendered (sanitized) in the Alpine state.
        $response->assertSee('<p>Editor <strong>content</strong></p>', false);
        $response->assertSee('previewSpecs', false);
        $response->assertSee(__('messages.spec_brand'));
        $response->assertSee(__('messages.spec_stock_in'));
    }

    /** Admin Part 2 — create page: preview section present with empty-specs message. */
    public function test_admin_product_create_page_renders_previews(): void
    {
        $store = $this->makeStore();
        $manager = $this->managerFor($store);

        $response = $this->actingAs($manager)->get('/store/spec-store/admin/products/create');

        $response->assertOk();
        $response->assertSee(__('messages.product_form_preview_section'));
        $response->assertSee(__('messages.product_form_description_preview'));
        $response->assertSee(__('messages.product_form_specs_preview'));
        $response->assertSee('previewSpecs', false);
        $response->assertSee(__('messages.spec_description_empty'));
    }

    /** Admin Part 3a — product list index wires the View modal buttons + single merged export. */
    public function test_admin_product_index_renders_view_buttons_and_merged_export(): void
    {
        $store = $this->makeStore();
        $manager = $this->managerFor($store);
        $product = $this->makeProduct($store);

        $response = $this->actingAs($manager)->get('/store/spec-store/admin/products');

        $response->assertOk();
        $response->assertSee('/admin/products/' . $product->id . '/details', false);
        $response->assertSee('openDetails', false);
        $response->assertSee('/admin/products/export', false);
        // The old separate Specs CSV export is gone — one Export button only.
        $response->assertDontSee('/admin/products/export-specs', false);
        $response->assertDontSee('Specs CSV');
        $response->assertSee('data-spec-tab', false);
    }

    /** Admin Part 3 — product list "View" detail modal partial (Description | Specifications). */
    public function test_admin_product_details_partial_renders_sanitized_tabs(): void
    {
        $store = $this->makeStore();
        $manager = $this->managerFor($store);
        $parent = Category::create(['store_id' => $store->id, 'name' => 'Spare Part', 'slug' => 'spare-part']);
        $child = Category::create(['store_id' => $store->id, 'name' => 'Body Frame', 'slug' => 'body-frame', 'parent_id' => $parent->id]);
        $brand = Brand::create(['store_id' => $store->id, 'name' => 'Redmi', 'slug' => 'redmi']);
        $product = $this->makeProduct($store, [
            'category_id' => $child->id,
            'brand_id' => $brand->id,
            'warranty' => '1 Month Warranty',
            'description' => '<h2>Editor</h2><p>Safe <strong>content</strong></p><script>alert(1)</script>',
        ]);

        $response = $this->actingAs($manager)->get('/store/spec-store/admin/products/' . $product->id . '/details');

        $response->assertOk();
        $response->assertSee(__('messages.tab_description'));
        $response->assertSee(__('messages.tab_specifications'));
        // Sanitized rich text: injected script payload dropped entirely, safe tags kept.
        $response->assertSee('<h2>Editor</h2>', false);
        $response->assertSee('<p>Safe <strong>content</strong></p>', false);
        $response->assertDontSee('alert(1)');
        // Shared presenter rows.
        $response->assertSee(__('messages.spec_brand'));
        $response->assertSee('Redmi');
        $response->assertSee(__('messages.spec_sku'));
        $response->assertSee('SPEC-001');
        $response->assertSee(__('messages.spec_stock_in'));
    }

    /** Admin Part 4 — details partial with an empty description shows the fallback message. */
    public function test_admin_product_details_partial_shows_empty_description_fallback(): void
    {
        $store = $this->makeStore();
        $manager = $this->managerFor($store);
        $product = $this->makeProduct($store, ['description' => '']);

        $response = $this->actingAs($manager)->get('/store/spec-store/admin/products/' . $product->id . '/details');

        $response->assertOk();
        $response->assertSee(__('messages.spec_description_empty'));
    }

    /** Admin Part 5 — details partial rejects a product from another store. */
    public function test_admin_product_details_partial_rejects_other_store_product(): void
    {
        $storeA = $this->makeStore('store-a');
        $storeB = $this->makeStore('store-b');
        $managerB = $this->managerFor($storeB);
        $productA = $this->makeProduct($storeA, ['sku' => 'OTHER-001', 'slug' => 'other-product']);

        $response = $this->actingAs($managerB)->get('/store/store-b/admin/products/' . $productA->id . '/details');

        $response->assertStatus(403);
    }

    /** Admin Part 6 — merged product export CSV: full round-trip columns + specs columns, BOM, sanitized description. */
    public function test_admin_export_csv_merges_roundtrip_and_specs_columns(): void
    {
        $store = $this->makeStore();
        $manager = $this->managerFor($store);
        $brand = Brand::create(['store_id' => $store->id, 'name' => 'Redmi', 'slug' => 'redmi']);
        $this->makeProduct($store, [
            'brand_id' => $brand->id,
            'warranty' => '1 Month Warranty',
            'description' => "<p>Safe <strong>text</strong></p><script>alert(1)</script>",
        ]);

        $response = $this->actingAs($manager)->get('/store/spec-store/admin/products/export');

        $response->assertOk();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $response->streamedContent());
        $content = substr($response->streamedContent(), 3);
        $rows = array_map('str_getcsv', explode("\n", trim($content)));
        // Round-trip import columns (unchanged names) + the specs columns merged in.
        $this->assertSame([
            'SKU', 'Name', 'Category', 'Parent Category', 'Brand',
            'Retail Price (Ks)', 'Wholesale Price (Ks)', 'Discount Price (Ks)',
            'Sale Starts At', 'Sale Ends At', 'Stock Status', 'Stock Status (Burmese)',
            'Warranty', 'Return Policy', 'Meta Description', 'Featured',
            'Description', 'Sanitized Description', 'Images', 'Variants',
            'Variant Name(s)', 'Variant SKU(s)',
        ], $rows[0]);
        $this->assertSame('SPEC-001', $rows[1][0]);
        $this->assertSame('Redmi', $rows[1][4]);
        $this->assertSame('in_stock', $rows[1][10]);
        $this->assertSame('ပစ္စည်းရှိ', $rows[1][11]);
        $this->assertSame('1 Month Warranty', $rows[1][12]);
        // Raw description kept for round-trip (script included), sanitized
        // copy for staff review (script stripped, safe HTML kept).
        $this->assertStringContainsString('<p>Safe <strong>text</strong></p><script>alert(1)</script>', $rows[1][16]);
        $this->assertStringContainsString('<p>Safe <strong>text</strong></p>', $rows[1][17]);
        $this->assertStringNotContainsString('alert(1)', $rows[1][17]);
    }

    /** Admin Part 7 — merged export honors per_page (only exported rows). */
    public function test_admin_export_respects_per_page(): void
    {
        $store = $this->makeStore();
        $manager = $this->managerFor($store);
        for ($i = 1; $i <= 51; $i++) {
            $this->makeProduct($store, [
                'sku' => 'BULK-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'slug' => 'bulk-product-' . $i,
            ]);
        }

        $limited = $this->actingAs($manager)->get('/store/spec-store/admin/products/export?per_page=50');
        $limitedRows = array_map('str_getcsv', explode("\n", trim(substr($limited->streamedContent(), 3))));
        $this->assertSame(50, count($limitedRows) - 1);
        $this->assertStringNotContainsString('BULK-051', $limited->streamedContent());

        $all = $this->actingAs($manager)->get('/store/spec-store/admin/products/export');
        $allRows = array_map('str_getcsv', explode("\n", trim(substr($all->streamedContent(), 3))));
        $this->assertSame(51, count($allRows) - 1);
    }
}
