<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use App\POS\Models\CustomerLedgerEntry;
use App\POS\Models\PosSale;
use App\POS\Models\PosSaleItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCustomerDirectoryTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;
    protected User $staff;
    protected User $retail;
    protected User $wholesale;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create(['name' => 'Store One', 'slug' => 'store-one']);
        $this->store->setting()->create(['store_name' => 'Store One', 'default_language' => 'en']);

        $this->manager = User::factory()->create(['phone' => '09111111111']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);

        $this->staff = User::factory()->create(['phone' => '09222222222']);
        $this->staff->stores()->attach($this->store->id, ['role' => 'staff', 'status' => 'active']);

        $this->retail = User::factory()->create(['name' => 'Aung Retail', 'phone' => '09333333333', 'role' => 'customer']);
        $this->retail->stores()->attach($this->store->id, ['role' => 'retail_customer', 'status' => 'active']);

        $this->wholesale = User::factory()->create(['name' => 'Moe Wholesale', 'phone' => '09444444444', 'role' => 'customer']);
        $this->wholesale->stores()->attach($this->store->id, ['role' => 'wholesale_customer', 'status' => 'active']);
    }

    public function test_manager_can_view_customer_directory_index(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/customers");

        $response->assertStatus(200);
        $response->assertSeeText('Customer Directory');
        $response->assertSeeText('Aung Retail');
        $response->assertSeeText('Moe Wholesale');
    }

    public function test_staff_can_view_customer_directory_index(): void
    {
        $response = $this->actingAs($this->staff)
            ->get("/store/{$this->store->slug}/admin/customers");

        $response->assertStatus(200);
        $response->assertSeeText('Aung Retail');
    }

    public function test_index_shows_outstanding_debt_from_ledger(): void
    {
        CustomerLedgerEntry::create([
            'store_id' => $this->store->id,
            'customer_id' => $this->retail->id,
            'type' => CustomerLedgerEntry::TYPE_OPENING_BALANCE,
            'amount' => '15000.00',
            'source_type' => 'manual',
            'client_transaction_id' => 'cd-test-open-1',
        ]);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/customers");

        $response->assertStatus(200);
        $response->assertSeeText(format_currency(15000, $this->store));
    }

    public function test_index_search_filters_by_name(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/customers?search=Aung");

        $response->assertStatus(200);
        $response->assertSeeText('Aung Retail');
        $response->assertDontSeeText('Moe Wholesale');
    }

    public function test_index_role_filter_only_shows_matching_type(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/customers?role=wholesale_customer");

        $response->assertStatus(200);
        $response->assertSeeText('Moe Wholesale');
        $response->assertDontSeeText('Aung Retail');
    }

    public function test_show_displays_customer_profile_and_recent_orders(): void
    {
        $sale = PosSale::create([
            'store_id' => $this->store->id,
            'customer_id' => $this->retail->id,
            'receipt_number' => 'R-1001',
            'status' => 'posted',
            'subtotal' => '50000.00',
            'total' => '50000.00',
            'posted_at' => now(),
        ]);
        PosSaleItem::create([
            'pos_sale_id' => $sale->id,
            'product_name' => 'USB Cable',
            'unit_price' => '5000.00',
            'quantity' => '10.000',
            'line_total' => '50000.00',
        ]);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/customers/{$this->retail->id}");

        $response->assertStatus(200);
        $response->assertSeeText('Aung Retail');
        $response->assertSeeText('R-1001');
        $response->assertSeeText(format_currency(50000, $this->store));
    }

    public function test_show_returns_404_for_customer_not_in_store(): void
    {
        $otherStore = Store::create(['name' => 'Store Two', 'slug' => 'store-two']);
        $otherStore->setting()->create(['store_name' => 'Store Two', 'default_language' => 'en']);

        $stranger = User::factory()->create(['phone' => '09555555555', 'role' => 'customer']);
        $stranger->stores()->attach($otherStore->id, ['role' => 'retail_customer', 'status' => 'active']);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/customers/{$stranger->id}");

        $response->assertStatus(404);
    }

    public function test_index_does_not_list_customers_from_other_stores(): void
    {
        $otherStore = Store::create(['name' => 'Store Two', 'slug' => 'store-two']);
        $otherStore->setting()->create(['store_name' => 'Store Two', 'default_language' => 'en']);

        $stranger = User::factory()->create(['name' => 'Kyaw Other Store', 'phone' => '09666666666', 'role' => 'customer']);
        $stranger->stores()->attach($otherStore->id, ['role' => 'retail_customer', 'status' => 'active']);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/customers");

        $response->assertStatus(200);
        $response->assertDontSeeText('Kyaw Other Store');
    }

    public function test_customer_without_admin_role_is_blocked(): void
    {
        $response = $this->actingAs($this->retail)
            ->get("/store/{$this->store->slug}/admin/customers");

        $response->assertStatus(403);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get("/store/{$this->store->slug}/admin/customers");

        $response->assertRedirect(route('login'));
    }

    public function test_cross_store_manager_access_is_blocked(): void
    {
        $otherStore = Store::create(['name' => 'Store Two', 'slug' => 'store-two']);
        $otherStore->setting()->create(['store_name' => 'Store Two', 'default_language' => 'en']);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$otherStore->slug}/admin/customers");

        $response->assertStatus(403);
    }

    public function test_index_renders_in_all_supported_locales_without_key_leaks(): void
    {
        foreach (['en', 'my', 'zh_CN'] as $code) {
            $store = Store::create(['name' => "Store {$code}", 'slug' => "store-{$code}"]);
            $store->setting()->create(['store_name' => "Store {$code}", 'default_language' => $code]);
            $this->manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

            $response = $this->actingAs($this->manager)
                ->get("/store/store-{$code}/admin/customers");

            $response->assertStatus(200);
            $response->assertDontSee('messages.', false);
        }
    }

    public function test_customer_directory_export_csv(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/customers/export?format=csv");

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $csv = $response->streamedContent();

        // Verify UTF-8 BOM
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('Customer Directory', $csv);
        $this->assertStringContainsString('Aung Retail', $csv);
        $this->assertStringContainsString('Moe Wholesale', $csv);
    }

    public function test_customer_directory_export_xlsx(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/customers/export?format=xlsx");

        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml.sheet', $response->headers->get('content-type', ''));
    }

    public function test_customer_directory_ui_ux_standard_v4_1_compact_layout(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/customers");

        $response->assertOk();
        // 2px ultra-dense main padding on mobile
        $response->assertSee('p-0.5 sm:p-1', false);
        // Centered row-based stat cards
        $response->assertSee('flex items-center justify-center gap-2.5 sm:gap-3', false);
        // Both card grid and spreadsheet table view containers are present
        $response->assertSee('id="customers-grid"', false);
        $response->assertSee('id="customers-table"', false);
        // Export button link is present
        $response->assertSee('/store/' . $this->store->slug . '/admin/customers/export', false);
    }
}
