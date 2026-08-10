<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Support\SeoMeta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Return Policy storefront display + Meta Description SEO implementation.
 *
 * Covers: policy shown only when non-empty and always escaped; meta
 * description fallback priority (meta → plain description → generic summary →
 * store default); Unicode-safe truncation; no duplicate tags; canonical URL;
 * OG image present/absent; and unchanged saves preserving brand / return
 * policy / meta description.
 */
class ReturnPolicyAndSeoMetaTest extends TestCase
{
    use RefreshDatabase;

    private function makeStore(string $slug = 'seo-store'): Store
    {
        return Store::create(['name' => 'SEO Store', 'slug' => $slug, 'is_active' => true]);
    }

    private function makeProduct(Store $store, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'store_id' => $store->id,
            'sku' => 'SEO-001',
            'name' => 'SEO Product',
            'slug' => 'seo-product',
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

    private function productUrl(Product $product): string
    {
        return url('/store/seo-store/product/' . $product->slug);
    }

    /** 1. Product with a return policy — the policy section renders. */
    public function test_return_policy_is_shown_on_storefront(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store, ['return_policy' => 'Return within 7 days with receipt.']);

        $response = $this->get($this->productUrl($product));

        $response->assertOk();
        // Locale-independent markers: the disclosure panel id + the policy text.
        $response->assertSee('return-policy-panel');
        $response->assertSee('aria-controls="return-policy-panel"', false);
        $response->assertSee('Return within 7 days with receipt.');
    }

    /** 2. Empty / whitespace-only return policy — no section at all. */
    public function test_empty_return_policy_hides_section(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store, ['return_policy' => '   ']);

        $response = $this->get($this->productUrl($product));

        $response->assertOk();
        $this->assertStringNotContainsString('return-policy-panel', $response->getContent());
    }

    /** 3. Unsafe HTML in return policy — escaped, never executed. */
    public function test_return_policy_unsafe_html_is_escaped(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store, ['return_policy' => "<script>alert(1)</script>Return within 7 days."]);

        $response = $this->get($this->productUrl($product));

        $response->assertOk();
        $response->assertSee('&lt;script&gt;', false);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $response->getContent());
    }

    /** 4. Edit form preserves the existing return policy value. */
    public function test_admin_edit_form_keeps_return_policy_and_unchanged_save_preserves_it(): void
    {
        $store = $this->makeStore();
        $manager = $this->managerFor($store);
        $product = $this->makeProduct($store, ['return_policy' => '7-day exchange', 'meta_description' => 'Existing meta.']);

        $edit = $this->actingAs($manager)->get('/store/seo-store/admin/products/' . $product->id . '/edit');
        $edit->assertOk();
        $edit->assertSee('7-day exchange');

        // Unchanged save (return_policy omitted entirely) preserves it.
        $this->actingAs($manager)->put('/store/seo-store/admin/products/' . $product->id, [
            'name' => $product->name,
            'sku' => $product->sku,
            'retail_price' => $product->retail_price,
            'wholesale_price' => $product->wholesale_price,
            'stock_status' => $product->stock_status,
            'description' => $product->description,
        ]);
        $this->assertSame('7-day exchange', $product->fresh()->return_policy);
        $this->assertSame('Existing meta.', $product->fresh()->meta_description);
    }

    /** 5. meta_description present — all three SEO tags use it. */
    public function test_meta_description_populates_all_seo_tags(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store, ['meta_description' => 'Unique SEO summary for tests.']);

        $response = $this->get($this->productUrl($product));
        $html = $response->getContent();

        $escaped = e('Unique SEO summary for tests.');
        $this->assertStringContainsString('<meta name="description" content="' . $escaped . '">', $html);
        $this->assertStringContainsString('<meta property="og:description" content="' . $escaped . '" />', $html);
        $this->assertStringContainsString('<meta name="twitter:description" content="' . $escaped . '" />', $html);
        // Exactly one description meta tag (no duplicates).
        $this->assertSame(1, substr_count($html, '<meta name="description"'));
    }

    /** 6. Empty meta + rich description — plain-text description fallback, no tags. */
    public function test_empty_meta_uses_plain_text_description_fallback(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store, [
            'meta_description' => '',
            'description' => "<p>First <strong>line</strong></p>\n\n<p>Second line here.</p>",
        ]);

        $response = $this->get($this->productUrl($product));
        $html = $response->getContent();

        $expected = e('First line Second line here.');
        $this->assertStringContainsString('<meta name="description" content="' . $expected . '">', $html);
        $this->assertStringNotContainsString('<meta name="description" content="<p>', $html);
    }

    /** 7. Both empty — generic product summary fallback (name + store). */
    public function test_both_empty_uses_generic_summary_fallback(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store, [
            'meta_description' => '',
            'description' => '',
        ]);

        $response = $this->get($this->productUrl($product));
        $html = $response->getContent();

        $this->assertStringContainsString('SEO Product', $html);
        $this->assertStringContainsString('SEO Store', $html);
        // The meta tag is present (generic summary, not the store welcome default).
        $this->assertSame(1, substr_count($html, '<meta name="description"'));
    }

    /** 8. Rich-text description — no HTML tags leak into the metadata. */
    public function test_rich_text_description_never_leaks_tags_into_metadata(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store, [
            'meta_description' => '',
            'description' => '<h2>Heading</h2><p>Body <em>text</em></p><script>bad()</script>',
        ]);

        $response = $this->get($this->productUrl($product));
        $html = $response->getContent();

        // Extract the description meta content and assert it is clean plain text.
        preg_match('/<meta name="description" content="([^"]*)"/', $html, $m);
        $this->assertNotEmpty($m);
        $this->assertStringNotContainsString('<', $m[1]);
        $this->assertStringNotContainsString('bad()', $m[1]);
        $this->assertStringContainsString('Heading', $m[1]);
        $this->assertStringContainsString('Body', $m[1]);
    }

    /** 9. Unicode-safe truncation — Burmese text is never split mid-character. */
    public function test_burmese_truncation_is_unicode_safe(): void
    {
        $long = str_repeat('မြန်မာစာစမ်းသပ်မှု', 40); // ~320 chars

        $truncated = SeoMeta::truncateForMeta($long, 160);

        $this->assertLessThanOrEqual(161, mb_strlen($truncated));
        $this->assertStringEndsWith('…', $truncated);
        $this->assertStringNotContainsString("\u{FFFD}", $truncated);
        $this->assertSame(mb_substr($long, 0, 160) . '…', $truncated);
    }

    /** 10. Quotes and special characters — attribute-safe escaped metadata. */
    public function test_quotes_and_special_chars_are_attribute_safe(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store, ['meta_description' => 'He said "hello" & bought \'it\' <now>']);

        $response = $this->get($this->productUrl($product));
        $html = $response->getContent();

        preg_match('/<meta name="description" content="([^"]*)"/', $html, $m);
        $this->assertNotEmpty($m);
        // Attribute is intact — the value is entity-encoded, no raw quote breaks it.
        // (The '<now>' tag was stripped as a tag, so no '<' remains — as required.)
        $this->assertStringContainsString('&quot;', $m[1]);
        $this->assertStringContainsString('&amp;', $m[1]);
        $this->assertStringContainsString('&#039;', $m[1]);
        $this->assertStringNotContainsString('"', $m[1]);
        $this->assertStringNotContainsString('<', $m[1]);
    }

    /** 11/12. Canonical + og:url point at the clean product URL, robots index,follow. */
    public function test_canonical_og_url_and_robots(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store);

        $response = $this->get($this->productUrl($product));
        $html = $response->getContent();

        $this->assertStringContainsString('<link rel="canonical" href="' . $this->productUrl($product) . '" />', $html);
        $this->assertStringContainsString('<meta property="og:url" content="' . $this->productUrl($product) . '" />', $html);
        $this->assertStringContainsString('<meta name="robots" content="index,follow" />', $html);
        $this->assertStringContainsString('<meta property="og:type" content="product" />', $html);
    }

    /** 13a. Product with an image — og:image uses the primary product image. */
    public function test_og_image_uses_product_image_when_present(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store, ['image_path' => 'products/test.jpg']);

        $response = $this->get($this->productUrl($product));

        $response->assertSee('<meta property="og:image" content="' . asset('storage/products/test.jpg') . '" />', false);
    }

    /** 13b. Product without image and no store logo — no og:image tag (no broken URL). */
    public function test_og_image_absent_when_no_product_image_or_store_logo(): void
    {
        $store = $this->makeStore();
        $product = $this->makeProduct($store, ['image_path' => null]);

        $response = $this->get($this->productUrl($product));

        $this->assertStringNotContainsString('<meta property="og:image"', $response->getContent());
    }

    /** 13c. Product without image but store logo present — og:image falls back to the store logo. */
    public function test_og_image_falls_back_to_store_logo_when_product_has_no_image(): void
    {
        $store = $this->makeStore();
        $store->setting()->create(['store_name' => 'SEO Store', 'storefront_logo_path' => 'store-logos/share.webp']);
        $product = $this->makeProduct($store, ['image_path' => null]);

        $response = $this->get($this->productUrl($product));

        $response->assertSee('<meta property="og:image" content="' . asset('storage/store-logos/share.webp') . '" />', false);
    }

    /** 14. Unchanged save preserves brand, return policy and meta description together. */
    public function test_unchanged_save_preserves_brand_return_policy_and_meta(): void
    {
        $store = $this->makeStore();
        $manager = $this->managerFor($store);
        $brand = Brand::create(['store_id' => $store->id, 'name' => 'Xiaomi', 'slug' => 'xiaomi']);
        $category = Category::create(['store_id' => $store->id, 'name' => 'Body Frame', 'slug' => 'body-frame']);
        $product = $this->makeProduct($store, [
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'return_policy' => '7-day exchange',
            'meta_description' => 'Existing meta.',
            'warranty' => '1 Month Warranty',
        ]);

        $this->actingAs($manager)->put('/store/seo-store/admin/products/' . $product->id, [
            'name' => $product->name,
            'sku' => $product->sku,
            'retail_price' => $product->retail_price,
            'wholesale_price' => $product->wholesale_price,
            'stock_status' => $product->stock_status,
            'description' => $product->description,
        ]);

        $fresh = $product->fresh();
        $this->assertSame($brand->id, $fresh->brand_id);
        $this->assertSame($category->id, $fresh->category_id);
        $this->assertSame('1 Month Warranty', $fresh->warranty);
        $this->assertSame('7-day exchange', $fresh->return_policy);
        $this->assertSame('Existing meta.', $fresh->meta_description);
    }

    /** 15. Admin form renders the meta helper text + counter plumbing. */
    public function test_admin_meta_description_ux_renders_helper_and_counter(): void
    {
        $store = $this->makeStore();
        $manager = $this->managerFor($store);
        $product = $this->makeProduct($store, ['meta_description' => 'Short meta.']);

        $response = $this->actingAs($manager)->get('/store/seo-store/admin/products/' . $product->id . '/edit');

        $response->assertOk();
        $response->assertSee(__('messages.product_form_meta_helper'));
        $response->assertSee(__('messages.product_form_meta_empty_fallback'));
        $response->assertSee('metaDescLen', false);
        $response->assertSee('/160');
        $response->assertSee('refreshReturnPolicyPreview', false);
    }
}
