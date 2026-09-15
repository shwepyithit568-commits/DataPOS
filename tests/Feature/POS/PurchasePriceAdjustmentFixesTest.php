<?php

namespace Tests\Feature\POS;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use App\POS\Services\PurchaseOrderService;
use App\POS\Services\PurchasePriceAdjustmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regression suite for the five defects confirmed in the 2026-09-15 audit of the
 * PO "Review Selling Price Adjustments" feature.
 *
 *  1. Edit PO sent the parent product's wholesale price for variants whose own
 *     wholesale_price is NULL → permanent false 409 "prices were modified by another user".
 *  2. The "Store Settings" markup tier was unreachable dead code (nothing persisted the key).
 *  3. Hardcoded English labels inside the price modal / camera modal / voucher strip.
 *  4. No HTTP-level coverage for the 409 & validation error paths; the cart was lost on bounce.
 *  5. Duplicate PO lines validated against a different cost than the one actually stored.
 *
 * @see \App\POS\Services\PurchasePriceAdjustmentService
 * @see resources/views/pos/purchases/edit.blade.php
 */
class PurchasePriceAdjustmentFixesTest extends TestCase
{
    use RefreshDatabase;

    private PurchasePriceAdjustmentService $adjustments;
    private PurchaseOrderService $poService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adjustments = app(PurchasePriceAdjustmentService::class);
        $this->poService = app(PurchaseOrderService::class);
    }

    private function makeStore(string $slug = 'fix-store'): Store
    {
        return Store::create([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'is_active' => true,
        ]);
    }

    private function makeUser(Store $store, string $role = 'store_owner'): User
    {
        $user = User::create([
            'name' => 'User ' . Str::random(4),
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => $role,
        ]);
        $user->stores()->attach($store->id, ['role' => $role, 'status' => 'active']);

        return $user;
    }

    private function makeProduct(Store $store, array $overrides = []): Product
    {
        $name = $overrides['name'] ?? 'Product ' . Str::random(4);

        return Product::create(array_merge([
            'store_id' => $store->id,
            'sku' => strtoupper(Str::random(8)),
            'name' => $name,
            'slug' => Str::slug($name . '-' . Str::random(4)),
            'purchase_cost' => '5000.00',
            'retail_price' => '8000.00',
            'wholesale_price' => '7000.00',
            'product_type' => 'standard',
        ], $overrides));
    }

    private function makeVariant(Product $product, array $overrides = []): ProductVariant
    {
        return ProductVariant::create(array_merge([
            'product_id' => $product->id,
            'name' => 'Variant ' . Str::random(3),
            'sku' => $product->sku . '-V1',
            'retail_price' => '8500.00',
            'wholesale_price' => '7500.00',
        ], $overrides));
    }

    /* ------------------------------------------------------------------ */
    /*  Defect 1 — variant rows must never inherit the product's prices     */
    /* ------------------------------------------------------------------ */

    public function test_edit_rows_use_variant_own_prices_never_the_parent_product(): void
    {
        $store = $this->makeStore('fix-variant-rows');
        $user = $this->makeUser($store);
        $product = $this->makeProduct($store, ['wholesale_price' => '7000.00']);
        // Exactly what Admin\ProductController::syncVariants stores for a blank wholesale field.
        $variant = $this->makeVariant($product, ['retail_price' => '8500.00', 'wholesale_price' => null]);

        $po = $this->poService->create($store, [
            ['product_id' => $product->id, 'product_variant_id' => $variant->id, 'quantity' => '5', 'unit_cost' => '5000.00'],
        ], null, null, null, $user);

        $rows = $this->adjustments->buildRowsForPo($po->fresh());

        $this->assertCount(1, $rows);
        $this->assertSame('0', $rows[0]['wholesale_price'], 'variant row must use its own NULL wholesale (0), not the product 7000');
        $this->assertSame('8500', $rows[0]['retail_price']);
        $this->assertSame('5000', $rows[0]['baseline_cost']);
        $this->assertSame($variant->id, $rows[0]['product_variant_id']);
    }

    public function test_edit_rows_use_product_prices_for_non_variant_lines(): void
    {
        $store = $this->makeStore('fix-product-rows');
        $user = $this->makeUser($store);
        $product = $this->makeProduct($store, ['retail_price' => '8000.00', 'wholesale_price' => '7000.00']);

        $po = $this->poService->create($store, [
            ['product_id' => $product->id, 'quantity' => '2', 'unit_cost' => '5500.00'],
        ], null, null, null, $user);

        $rows = $this->adjustments->buildRowsForPo($po->fresh());

        $this->assertCount(1, $rows);
        $this->assertNull($rows[0]['product_variant_id']);
        $this->assertSame('8000', $rows[0]['retail_price']);
        $this->assertSame('7000', $rows[0]['wholesale_price']);
        $this->assertSame('5500', $rows[0]['baseline_cost']);
    }

    /**
     * The exact payload the edit page now produces must save — before the fix this
     * returned 409 "prices were modified by another user" on the very first attempt.
     */
    public function test_edit_po_price_update_succeeds_when_variant_has_no_wholesale_price(): void
    {
        $store = $this->makeStore('fix-variant-save');
        $user = $this->makeUser($store);
        $product = $this->makeProduct($store, ['wholesale_price' => '7000.00']);
        $variant = $this->makeVariant($product, ['retail_price' => '8500.00', 'wholesale_price' => null]);

        $po = $this->poService->create($store, [
            ['product_id' => $product->id, 'product_variant_id' => $variant->id, 'quantity' => '5', 'unit_cost' => '5000.00'],
        ], null, null, null, $user);

        $row = $this->adjustments->buildRowsForPo($po->fresh())[0];

        $response = $this->actingAs($user)->put("/store/{$store->slug}/pos/purchases/{$po->id}", [
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => '5',
                    'unit_cost' => '6000.00',
                ],
            ],
            'price_updates' => [
                [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'expected_retail_price' => $row['retail_price'],
                    'expected_wholesale_price' => $row['wholesale_price'],
                    'retail_price' => '10000.00',
                    'wholesale_price' => '9000.00',
                    'update_prices' => '1',
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertSame('10000.00', (string) $variant->fresh()->retail_price);
        $this->assertSame('9000.00', (string) $variant->fresh()->wholesale_price);
        // The parent product keeps its own prices.
        $this->assertSame('8000.00', (string) $product->fresh()->retail_price);
        $this->assertSame('7000.00', (string) $product->fresh()->wholesale_price);
    }

    public function test_edit_page_renders_the_variant_row_payload(): void
    {
        $store = $this->makeStore('fix-edit-render');
        $user = $this->makeUser($store);
        $product = $this->makeProduct($store);
        $variant = $this->makeVariant($product, ['name' => 'Zerostock', 'wholesale_price' => null]);

        $po = $this->poService->create($store, [
            ['product_id' => $product->id, 'product_variant_id' => $variant->id, 'quantity' => '3', 'unit_cost' => '5200.00'],
        ], null, null, null, $user);

        $response = $this->actingAs($user)->get("/store/{$store->slug}/pos/purchases/{$po->id}/edit");

        $response->assertOk();
        $response->assertSee('Zerostock');
        $response->assertSee('initialRows', false);
    }

    /* ------------------------------------------------------------------ */
    /*  Defect 2 — the store markup setting must be real                    */
    /* ------------------------------------------------------------------ */

    public function test_store_markup_defaults_to_fallback_when_not_configured(): void
    {
        $store = $this->makeStore('fix-markup-default');

        $markups = $this->adjustments->getStoreMarkups($store);

        $this->assertSame('20.00', $markups['retail_markup']);
        $this->assertSame('10.00', $markups['wholesale_markup']);
        $this->assertFalse($markups['configured']);
        $this->assertSame('default_fallback', $markups['source']);
    }

    public function test_store_markup_setting_is_persisted_and_used_by_pos_pages(): void
    {
        $store = $this->makeStore('fix-markup-saved');
        $user = $this->makeUser($store);

        $this->actingAs($user)->post("/store/{$store->slug}/admin/settings", [
            'section' => 'pos',
            'pos_settings' => [
                'default_retail_markup' => '35',
                'default_wholesale_markup' => '18.5',
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $saved = $store->fresh()->setting->pos_settings;
        $this->assertEquals(35, $saved['default_retail_markup']);
        $this->assertEquals(18.5, $saved['default_wholesale_markup']);

        $markups = $this->adjustments->getStoreMarkups($store->fresh());
        $this->assertSame('35.00', $markups['retail_markup']);
        $this->assertSame('18.50', $markups['wholesale_markup']);
        $this->assertTrue($markups['configured']);
        $this->assertSame('store_settings', $markups['source']);

        // Both PO pages must carry the "the owner configured this" flag so the front-end
        // precedence stops deferring to the legacy localStorage value.
        $this->actingAs($user)
            ->get("/store/{$store->slug}/pos/purchases/create")
            ->assertOk()
            ->assertSee('storeHasMarkups', false);
    }

    public function test_blank_markup_fields_fall_back_to_defaults(): void
    {
        $store = $this->makeStore('fix-markup-blank');
        $user = $this->makeUser($store);

        $this->actingAs($user)->post("/store/{$store->slug}/admin/settings", [
            'section' => 'pos',
            'pos_settings' => [
                'default_retail_markup' => '',
                'default_wholesale_markup' => '',
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $markups = $this->adjustments->getStoreMarkups($store->fresh());
        $this->assertFalse($markups['configured']);
        $this->assertSame('20.00', $markups['retail_markup']);
    }

    /* ------------------------------------------------------------------ */
    /*  Defect 3 — no hardcoded English on the PO counter pages             */
    /* ------------------------------------------------------------------ */

    public function test_po_pages_have_no_hardcoded_english_labels(): void
    {
        $store = $this->makeStore('fix-i18n');
        $user = $this->makeUser($store);
        $product = $this->makeProduct($store);
        $po = $this->poService->create($store, [
            ['product_id' => $product->id, 'quantity' => '1', 'unit_cost' => '5000.00'],
        ], null, null, null, $user);

        $hardcoded = [
            'Current:',
            'Sugg:',
            'Use Suggested Price',
            'Selected Vouchers / Invoices:',
            'Starting camera...',
            'Remove item',
        ];

        $urls = [
            "/store/{$store->slug}/pos/purchases/create",
            "/store/{$store->slug}/pos/purchases/{$po->id}/edit",
        ];

        // Labels handed to Alpine go through @js(), which \u-escapes non-ASCII text in the
        // rendered HTML — compare against the same json_encode form, not the raw string.
        $jsLiteral = fn (string $value) => trim(json_encode($value), '"');

        foreach ($urls as $url) {
            $response = $this->actingAs($user)->get($url);
            $response->assertOk();
            foreach ($hardcoded as $needle) {
                $response->assertDontSee($needle);
            }
            // The translated labels are rendered instead.
            $response->assertSee(__('messages.po_price_current_label'));
            $response->assertSee(__('messages.po_price_use_suggested'));
            $response->assertSee(__('messages.po_voucher_label'));
            $response->assertSee(__('messages.po_camera_starting'));
            // The badge tooltip labels are wired into the JS (these keys used to be dead).
            $response->assertSee('priceAdjustmentTitle', false);
            $response->assertSee($jsLiteral(__('messages.po_price_adjustment_badge_up')), false);
            $response->assertSee($jsLiteral(__('messages.po_price_adjustment_badge_down')), false);
        }

        // The labels follow the locale — Burmese render is not the English fallback.
        $myLabel = __('messages.po_price_use_suggested', [], 'my');
        $this->assertNotSame($myLabel, __('messages.po_price_use_suggested', [], 'en'));

        $myResponse = $this->actingAs($user)->withSession(['locale' => 'my'])
            ->get("/store/{$store->slug}/pos/purchases/create");
        $myResponse->assertOk();
        $myResponse->assertSee($myLabel);
        $myResponse->assertDontSee('Current:');
        $myResponse->assertDontSee('Sugg:');
    }

    /* ------------------------------------------------------------------ */
    /*  Defect 4 — HTTP error paths + cart recovery                         */
    /* ------------------------------------------------------------------ */

    public function test_conflict_returns_http_409_with_translated_message(): void
    {
        $store = $this->makeStore('fix-409');
        $user = $this->makeUser($store);
        $product = $this->makeProduct($store, ['retail_price' => '8000.00', 'wholesale_price' => '7000.00']);

        // Another user changed the price after the modal was opened.
        $product->update(['retail_price' => '8200.00']);

        $response = $this->actingAs($user)->post("/store/{$store->slug}/pos/purchases", [
            'items' => [
                ['product_id' => $product->id, 'quantity' => '10', 'unit_cost' => '6000.00'],
            ],
            'price_updates' => [
                [
                    'product_id' => $product->id,
                    'expected_retail_price' => '8000.00',
                    'expected_wholesale_price' => '7000.00',
                    'retail_price' => '9500.00',
                    'wholesale_price' => '8500.00',
                    'update_prices' => '1',
                ],
            ],
        ]);

        $response->assertStatus(409);
        $response->assertSessionHas('error', __('messages.po_price_conflict_alert') . ' (' . $product->name . ')');

        // Nothing was written.
        $this->assertSame('8200.00', (string) $product->fresh()->retail_price);
        $this->assertSame(0, \App\POS\Models\PurchaseOrder::where('store_id', $store->id)->count());
    }

    public function test_price_update_is_rejected_when_cost_did_not_change_via_http(): void
    {
        $store = $this->makeStore('fix-unchanged');
        $user = $this->makeUser($store);
        $product = $this->makeProduct($store, ['purchase_cost' => '5000.00']);

        $response = $this->actingAs($user)->post("/store/{$store->slug}/pos/purchases", [
            'items' => [
                // Same cost as the product's current purchase_cost → no change to justify a price edit.
                ['product_id' => $product->id, 'quantity' => '10', 'unit_cost' => '5000.00'],
            ],
            'price_updates' => [
                [
                    'product_id' => $product->id,
                    'expected_retail_price' => '8000.00',
                    'expected_wholesale_price' => '7000.00',
                    'retail_price' => '9500.00',
                    'wholesale_price' => '8500.00',
                    'update_prices' => '1',
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors();
        $this->assertSame('8000.00', (string) $product->fresh()->retail_price);
        $this->assertSame(0, \App\POS\Models\PurchaseOrder::where('store_id', $store->id)->count());
    }

    public function test_create_form_restores_the_cart_after_a_bounced_submission(): void
    {
        $store = $this->makeStore('fix-cart-restore');
        $user = $this->makeUser($store);
        $product = $this->makeProduct($store, ['name' => 'Restored Widget']);
        $supplier = Supplier::create(['store_id' => $store->id, 'name' => 'Acme Supply']);

        $createUrl = "/store/{$store->slug}/pos/purchases/create";
        // Prime the "previous URL" so back() points at the create form.
        $this->actingAs($user)->get($createUrl)->assertOk();

        // Retail below the new unit cost → server-side rejection, cashier is bounced back.
        $response = $this->actingAs($user)->followingRedirects()->post("/store/{$store->slug}/pos/purchases", [
            'items' => [
                ['product_id' => $product->id, 'quantity' => '7', 'unit_cost' => '6000.00'],
            ],
            'supplier_id' => $supplier->id,
            'reference' => 'PO-REF-1',
            'notes' => 'Deliver before Friday',
            'discount_amount' => '250',
            'price_updates' => [
                [
                    'product_id' => $product->id,
                    'expected_retail_price' => '8000.00',
                    'expected_wholesale_price' => '7000.00',
                    'retail_price' => '5500.00', // below the 6000.00 unit cost → rejected
                    'wholesale_price' => '5000.00',
                    'update_prices' => '1',
                ],
            ],
        ]);

        $response->assertOk();
        // The line item the cashier typed is back on the form instead of an empty cart.
        $response->assertSee('Restored Widget');
        // …along with the header fields.
        $response->assertSee('value="PO-REF-1"', false);
        $response->assertSee('value="Deliver before Friday"', false);
        $response->assertSee('discountAmount: 250', false);
        $response->assertSee('Acme Supply');
    }

    public function test_draft_rows_use_the_current_product_cost_as_baseline(): void
    {
        $store = $this->makeStore('fix-draft-rows');
        $product = $this->makeProduct($store, ['purchase_cost' => '5000.00', 'wholesale_price' => '7000.00']);
        $variant = $this->makeVariant($product, ['retail_price' => '8500.00', 'wholesale_price' => null]);

        $rows = $this->adjustments->buildRowsForDraft($store, [
            ['product_id' => $product->id, 'quantity' => '3', 'unit_cost' => '6500.00'],
            ['product_id' => $product->id, 'product_variant_id' => $variant->id, 'quantity' => '1', 'unit_cost' => '6600.00'],
            ['product_id' => 999999, 'quantity' => '1', 'unit_cost' => '10.00'], // deleted product → skipped
        ]);

        $this->assertCount(2, $rows);
        $this->assertSame('5000.00', $rows[0]['baseline_cost']);
        $this->assertSame('6500.00', $rows[0]['unit_cost']);
        $this->assertSame('3', $rows[0]['quantity']);
        $this->assertSame('0', $rows[1]['wholesale_price'], 'variant row keeps its own wholesale value');
        $this->assertSame('8500', $rows[1]['retail_price']);

        // Another store's product ids are ignored.
        $otherStore = $this->makeStore('fix-draft-other');
        $this->assertSame([], $this->adjustments->buildRowsForDraft($otherStore, [
            ['product_id' => $product->id, 'quantity' => '1', 'unit_cost' => '1.00'],
        ]));
    }

    /* ------------------------------------------------------------------ */
    /*  Defect 5 — duplicate lines must validate the cost that gets stored  */
    /* ------------------------------------------------------------------ */

    public function test_duplicate_po_lines_validate_against_the_cost_that_gets_stored(): void
    {
        $store = $this->makeStore('fix-duplicate-lines');
        $user = $this->makeUser($store);
        $product = $this->makeProduct($store, ['purchase_cost' => '5000.00']);

        // PurchaseOrderService merges duplicates: quantities are summed and the FIRST
        // unit_cost (6000) is the one stored on the PO line.
        $poLines = [
            ['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => '10', 'unit_cost' => '6000.00'],
            ['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => '5', 'unit_cost' => '5500.00'],
        ];
        $baselines = [$product->id . ':0' => '5000.00'];

        // A retail price that clears the second line's cost but not the stored one must be rejected.
        $rejected = false;
        try {
            $this->adjustments->validateAndNormalizePriceUpdates($store, [
                [
                    'product_id' => $product->id,
                    'expected_retail_price' => '8000.00',
                    'expected_wholesale_price' => '7000.00',
                    'retail_price' => '5700.00',
                    'wholesale_price' => '5600.00',
                    'update_prices' => '1',
                ],
            ], $poLines, $user, $baselines);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $rejected = true;
            $this->assertStringContainsString('6000', json_encode($e->errors()));
        }
        $this->assertTrue($rejected, 'price below the STORED line cost must be rejected');

        // The audit payload reports the stored cost, not the dropped duplicate.
        $validated = $this->adjustments->validateAndNormalizePriceUpdates($store, [
            [
                'product_id' => $product->id,
                'expected_retail_price' => '8000.00',
                'expected_wholesale_price' => '7000.00',
                'retail_price' => '9000.00',
                'wholesale_price' => '8000.00',
                'update_prices' => '1',
            ],
        ], $poLines, $user, $baselines);

        $this->assertSame('6000.00', $validated[$product->id . ':0']['new_unit_cost']);
        $this->assertSame('5000.00', $validated[$product->id . ':0']['old_unit_cost']);
    }
}
