<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the "works for a non-technical shop counter" behaviour of the product
 * form: nothing a Myanmar retail shop does not use may block a save, and the
 * Alpine state on the page must actually survive HTML parsing.
 */
class ProductFormStaffUsabilityTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'Provincial Shop',
            'slug' => 'provincial-shop',
            'business_type' => 'mobile_tech',
        ]);
        $this->store->setting()->create(['store_name' => 'Provincial Shop', 'default_language' => 'my']);

        $this->manager = User::factory()->create(['phone' => '09771112222']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);
    }

    private function createUrl(): string
    {
        return "/store/{$this->store->slug}/admin/products/create";
    }

    private function collectionUrl(): string
    {
        return "/store/{$this->store->slug}/admin/products";
    }

    /**
     * A literal double quote inside the double-quoted x-data attribute ends the
     * HTML attribute early: Alpine still boots, but with none of the declared
     * properties — every dropdown renders empty and every x-show panel is stuck
     * open. Assert the LAST declared members render, which only happens when the
     * whole attribute survived.
     */
    public function test_alpine_state_is_not_truncated_on_create_and_edit(): void
    {
        $product = Product::create([
            'store_id' => $this->store->id,
            'name' => 'Existing Product',
            'slug' => 'existing-product',
            'sku' => 'EXIST-1',
            'retail_price' => 1000,
            'wholesale_price' => 900,
        ]);

        $tokens = [
            'autoSku:',
            'smartCategoryPick',
            'smartCategoryOptions',
            'onSmartCategoryPick',
            'scanBarcodeFromCamera',
            'jumpToError',
            'marginPercent',
            'handleKeydown',
        ];

        foreach ([$this->createUrl(), "/store/{$this->store->slug}/admin/products/{$product->id}/edit"] as $url) {
            $response = $this->actingAs($this->manager)->get($url);
            $response->assertOk();
            foreach ($tokens as $token) {
                $response->assertSee($token, false);
            }
        }
    }

    public function test_sku_is_not_client_required_so_staff_can_leave_it_blank(): void
    {
        $response = $this->actingAs($this->manager)->get($this->createUrl());

        $response->assertOk();
        $response->assertDontSee(':required="!autoSku"', false);
    }

    public function test_blank_sku_gets_an_auto_generated_unique_code(): void
    {
        $response = $this->actingAs($this->manager)->post($this->collectionUrl(), [
            'name' => 'No SKU USB Cable',
            'retail_price' => 5000,
            'wholesale_price' => 4500,
        ]);

        $response->assertSessionHasNoErrors();
        $product = Product::where('store_id', $this->store->id)->where('name', 'No SKU USB Cable')->firstOrFail();
        $this->assertStringStartsWith('SKU-', $product->sku);
    }

    public function test_wholesale_price_is_optional_and_falls_back_to_retail(): void
    {
        $response = $this->actingAs($this->manager)->post($this->collectionUrl(), [
            'name' => 'Retail Only Phone Case',
            'retail_price' => 12000,
            // wholesale_price deliberately omitted — retail-only shop
        ]);

        $response->assertSessionHasNoErrors();
        $product = Product::where('store_id', $this->store->id)->where('name', 'Retail Only Phone Case')->firstOrFail();
        $this->assertSame('12000.00', (string) $product->wholesale_price);
    }

    public function test_wholesale_price_update_falls_back_to_retail_when_cleared(): void
    {
        $product = Product::create([
            'store_id' => $this->store->id,
            'name' => 'Clearable Wholesale',
            'slug' => 'clearable-wholesale',
            'sku' => 'CLEAR-1',
            'retail_price' => 20000,
            'wholesale_price' => 15000,
        ]);

        $response = $this->actingAs($this->manager)
            ->put("/store/{$this->store->slug}/admin/products/{$product->id}", [
                'name' => 'Clearable Wholesale',
                'sku' => 'CLEAR-1',
                'retail_price' => 20000,
                'wholesale_price' => '',
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('20000.00', (string) $product->fresh()->wholesale_price);
    }

    public function test_barcode_field_offers_camera_scan_on_phones(): void
    {
        $response = $this->actingAs($this->manager)->get($this->createUrl());

        $response->assertOk();
        // Rear camera is requested so a phone opens the camera directly.
        $response->assertSee('capture="environment"', false);
        $response->assertSee('admin-barcode-scan-region', false);
        $response->assertSee('scanBarcodeFromCamera(event)', false);
    }

    public function test_server_errors_render_as_jump_links_to_the_field(): void
    {
        $this->actingAs($this->manager)->post($this->collectionUrl(), [
            'name' => 'Missing Prices',
        ])->assertSessionHasErrors(['retail_price']);

        $page = $this->actingAs($this->manager)->get($this->createUrl());

        $page->assertOk();
        $page->assertSee("jumpToError('retail_price')", false);
    }

    public function test_service_type_never_posts_opening_stock_even_if_a_value_is_submitted(): void
    {
        // Staff start a standard product, type an initial stock, then switch the
        // type to Service. The field is only hidden client-side, so the server
        // must refuse to open a stock balance for a stockless product.
        $response = $this->actingAs($this->manager)->post($this->collectionUrl(), [
            'name' => 'Screen Replacement Service',
            'retail_price' => 25000,
            'wholesale_price' => 25000,
            'product_type' => 'service',
            'initial_stock' => 7,
        ]);

        $response->assertSessionHasNoErrors();
        $product = Product::where('store_id', $this->store->id)->where('name', 'Screen Replacement Service')->firstOrFail();

        $this->assertSame(0, \App\POS\Models\InventoryMovement::where('product_id', $product->id)->count());
    }

    public function test_product_type_selector_is_out_of_the_counter_path_but_complete(): void
    {
        $response = $this->actingAs($this->manager)->get($this->createUrl());

        $response->assertOk();
        // The compact strip is what the cashier sees, plus a way to change type.
        $response->assertSee('data-test-label="Change Product Type"', false);
        $response->assertSee('productTypeLabel', false);
        // All six types still exist, inside the More Details accordion.
        $response->assertSee('id="product-type-selector"', false);
        foreach (['standard', 'serialized', 'variant', 'service', 'digital', 'weight_based'] as $type) {
            $response->assertSee('data-test-label="Product Type ' . $type . '"', false);
        }
        $response->assertSee('setProductType(', false);

        // The accordion is what reveals it, so the strip button must drive it.
        $response->assertSee('@expand-advanced.window', false);
    }

    public function test_variants_editor_is_collapsed_for_a_plain_product_and_open_for_a_variant_product(): void
    {
        $plain = $this->actingAs($this->manager)->get($this->createUrl());
        $plain->assertOk();
        $plain->assertSee('data-test-label="Toggle Variants"', false);
        $plain->assertSee('variantsOpen: false', false);

        $variantProduct = Product::create([
            'store_id' => $this->store->id,
            'name' => 'Tee Shirt',
            'slug' => 'tee-shirt',
            'sku' => 'TEE-1',
            'retail_price' => 9000,
            'wholesale_price' => 7000,
            'product_type' => 'variant',
        ]);
        $variantProduct->variants()->create([
            'store_id' => $this->store->id,
            'name' => 'Tee Shirt / M',
            'sku' => 'TEE-1-M',
            'retail_price' => 9000,
            'stock_status' => 'in_stock',
        ]);

        $edit = $this->actingAs($this->manager)->get("/store/{$this->store->slug}/admin/products/{$variantProduct->id}/edit");
        $edit->assertOk();
        // A product that already has variants, or is a variant product, opens the editor.
        $edit->assertSee('variantsOpen: true', false);
    }

    public function test_live_code_check_reports_a_taken_sku_and_a_free_one(): void
    {
        Product::create([
            'store_id' => $this->store->id,
            'name' => 'Existing Charger',
            'slug' => 'existing-charger',
            'sku' => 'CHG-20W',
            'barcode' => '8850001112223',
            'retail_price' => 15000,
            'wholesale_price' => 12000,
        ]);

        $taken = $this->actingAs($this->manager)
            ->getJson("/store/{$this->store->slug}/admin/products/check-code?field=sku&value=chg-20w");
        $taken->assertOk()->assertJson(['exists' => true, 'name' => 'Existing Charger']);

        $free = $this->actingAs($this->manager)
            ->getJson("/store/{$this->store->slug}/admin/products/check-code?field=sku&value=CHG-9999");
        $free->assertOk()->assertJson(['exists' => false]);

        $barcodeTaken = $this->actingAs($this->manager)
            ->getJson("/store/{$this->store->slug}/admin/products/check-code?field=barcode&value=8850001112223");
        $barcodeTaken->assertOk()->assertJson(['exists' => true]);

        $excluded = $this->actingAs($this->manager)
            ->getJson("/store/{$this->store->slug}/admin/products/check-code?field=sku&value=CHG-20W&exclude=" . Product::where('sku', 'CHG-20W')->value('id'));
        $excluded->assertOk()->assertJson(['exists' => false]);

        $blank = $this->actingAs($this->manager)
            ->getJson("/store/{$this->store->slug}/admin/products/check-code?field=sku&value=");
        $blank->assertOk()->assertJson(['exists' => false]);
    }

    public function test_code_check_is_store_scoped(): void
    {
        $otherStore = Store::create(['name' => 'Other', 'slug' => 'other-store', 'business_type' => 'mobile_tech']);
        Product::create([
            'store_id' => $otherStore->id,
            'name' => 'Other Store Item',
            'slug' => 'other-store-item',
            'sku' => 'ONLY-OTHER',
            'retail_price' => 1000,
            'wholesale_price' => 1000,
        ]);

        $response = $this->actingAs($this->manager)
            ->getJson("/store/{$this->store->slug}/admin/products/check-code?field=sku&value=ONLY-OTHER");

        $response->assertOk()->assertJson(['exists' => false]);
    }

    private function staffUser(): User
    {
        $staff = User::factory()->create(['phone' => '09773334444']);
        $staff->stores()->attach($this->store->id, ['role' => 'staff', 'status' => 'active']);

        return $staff;
    }

    public function test_cost_fields_are_hidden_from_staff_but_shown_to_manager_and_owner(): void
    {
        $staff = $this->staffUser();

        $staffPage = $this->actingAs($staff)->get($this->createUrl());
        $staffPage->assertOk();
        $staffPage->assertDontSee('name="purchase_cost"', false);
        $staffPage->assertDontSee('name="wholesale_price"', false);
        $staffPage->assertDontSee('name="reorder_level"', false);

        $managerPage = $this->actingAs($this->manager)->get($this->createUrl());
        $managerPage->assertOk();
        $managerPage->assertSee('name="purchase_cost"', false);
        $managerPage->assertSee('name="wholesale_price"', false);
        $managerPage->assertSee('name="reorder_level"', false);
    }

    public function test_staff_cannot_write_cost_values_even_by_crafting_the_request(): void
    {
        $staff = $this->staffUser();

        $response = $this->actingAs($staff)->post($this->collectionUrl(), [
            'name' => 'Staff Created Item',
            'retail_price' => 9000,
            // Staff never sees these fields, so they must be ignored if injected.
            'wholesale_price' => 1,
            'purchase_cost' => 2,
            'reorder_level' => 3,
        ]);

        $response->assertSessionHasNoErrors();
        $product = Product::where('store_id', $this->store->id)->where('name', 'Staff Created Item')->firstOrFail();

        // wholesale falls back to retail, and the cost fields stay empty
        $this->assertSame('9000.00', (string) $product->wholesale_price);
        $this->assertNull($product->purchase_cost);
        $this->assertNull($product->reorder_level);
    }

    public function test_staff_edit_does_not_wipe_the_managers_cost_values(): void
    {
        $staff = $this->staffUser();

        $product = Product::create([
            'store_id' => $this->store->id,
            'name' => 'Manager Priced Item',
            'slug' => 'manager-priced-item',
            'sku' => 'MPI-1',
            'retail_price' => 30000,
            'wholesale_price' => 25000,
            'purchase_cost' => 18000,
            'reorder_level' => 5,
        ]);

        // A staff member edits only the retail price.
        $response = $this->actingAs($staff)
            ->put("/store/{$this->store->slug}/admin/products/{$product->id}", [
                'name' => 'Manager Priced Item',
                'sku' => 'MPI-1',
                'retail_price' => 32000,
            ]);

        $response->assertSessionHasNoErrors();
        $fresh = $product->fresh();
        $this->assertSame('32000.00', (string) $fresh->retail_price);
        $this->assertSame('25000.00', (string) $fresh->wholesale_price, 'wholesale must survive a staff edit');
        $this->assertSame('18000.0000', (string) $fresh->purchase_cost, 'purchase cost must survive a staff edit');
        $this->assertSame('5.000', (string) $fresh->reorder_level, 'reorder level must survive a staff edit');
    }

    public function test_products_list_hides_the_wholesale_column_from_staff(): void
    {
        $staff = $this->staffUser();

        $staffList = $this->actingAs($staff)->get("/store/{$this->store->slug}/admin/products");
        $staffList->assertOk();
        $staffList->assertDontSee(__('messages.wholesale_price') ?? 'Wholesale', false);
    }

    public function test_pos_quantity_input_allows_decimals_for_weight_products(): void
    {
        $response = $this->actingAs($this->manager)->get("/store/{$this->store->slug}/pos");

        $response->assertOk();
        // Whole-unit products stay whole; weight-based lines get 3-decimal entry.
        // (The step logic itself lives in the POS bundle: isLooseWeight/minQty.)
        $response->assertSee('minQty(line)', false);
    }
}
