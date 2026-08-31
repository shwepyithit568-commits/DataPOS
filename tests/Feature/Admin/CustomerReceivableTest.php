<?php

namespace Tests\Feature\Admin;

use App\Models\Store;
use App\Models\User;
use App\POS\Models\CustomerLedgerEntry;
use App\POS\Services\CustomerDebtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerReceivableTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $admin;
    protected User $customer;
    protected CustomerDebtService $debtService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'DataPOS Mobile Hub',
            'slug' => 'datapos-mobile',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'store_manager',
        ]);
        $this->admin->stores()->attach($this->store->id, ['role' => 'store_manager']);

        $this->customer = User::factory()->create([
            'name' => 'U Ba Customer',
            'phone' => '09971234567',
            'role' => 'customer',
        ]);
        $this->customer->stores()->attach($this->store->id, ['role' => 'customer']);

        $this->debtService = app(CustomerDebtService::class);
    }

    public function test_admin_can_view_receivables_index_dashboard(): void
    {
        // Record opening balance of 150,000 Ks
        $this->debtService->recordOpeningBalance(
            store: $this->store,
            customerId: $this->customer->id,
            amount: '150000.00',
            actor: $this->admin,
            notes: 'Test opening debt',
        );

        $response = $this->actingAs($this->admin)->get(route('store.admin.receivables.index', [
            'store_slug' => $this->store->slug,
        ]));

        $response->assertStatus(200);
        $response->assertSee('U Ba Customer');
        $response->assertSee('150,000');
    }

    public function test_admin_can_filter_and_search_receivables(): void
    {
        $this->debtService->recordOpeningBalance(
            store: $this->store,
            customerId: $this->customer->id,
            amount: '150000.00',
            actor: $this->admin,
        );

        $secondCustomer = User::factory()->create([
            'name' => 'Daw Mya Customer',
            'phone' => '09987654321',
            'role' => 'customer',
        ]);
        $secondCustomer->stores()->attach($this->store->id, ['role' => 'customer']);

        $this->debtService->recordOpeningBalance(
            store: $this->store,
            customerId: $secondCustomer->id,
            amount: '50000.00',
            actor: $this->admin,
        );

        // Search for U Ba
        $response = $this->actingAs($this->admin)->get(route('store.admin.receivables.index', [
            'store_slug' => $this->store->slug,
            'search' => '09971234567',
        ]));

        $response->assertStatus(200);
        $response->assertSee('U Ba Customer');
        $response->assertDontSee('Daw Mya Customer');

        // Filter high debt (>= 100,000 Ks)
        $filterResponse = $this->actingAs($this->admin)->get(route('store.admin.receivables.index', [
            'store_slug' => $this->store->slug,
            'filter' => 'high_debt',
        ]));

        $filterResponse->assertStatus(200);
        $filterResponse->assertSee('U Ba Customer');
        $filterResponse->assertDontSee('Daw Mya Customer');
    }

    public function test_admin_can_view_customer_receivable_show_timeline(): void
    {
        $this->debtService->recordOpeningBalance(
            store: $this->store,
            customerId: $this->customer->id,
            amount: '80000.00',
            actor: $this->admin,
            notes: 'Initial credit account balance',
        );

        $response = $this->actingAs($this->admin)->get(route('store.admin.receivables.show', [
            'store_slug' => $this->store->slug,
            'customer' => $this->customer->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('U Ba Customer');
        $response->assertSee('80,000');
        $response->assertSee('Initial credit account balance');
    }

    public function test_admin_can_collect_customer_debt_payment(): void
    {
        $this->debtService->recordOpeningBalance(
            store: $this->store,
            customerId: $this->customer->id,
            amount: '100000.00',
            actor: $this->admin,
        );

        $response = $this->actingAs($this->admin)->post(route('store.admin.receivables.collect', [
            'store_slug' => $this->store->slug,
            'customer' => $this->customer->id,
        ]), [
            'amount' => '40000',
            'payment_method' => 'kpay',
            'reference_no' => 'KP12345678',
            'notes' => 'Partial payment via KPay',
        ]);

        $response->assertRedirect(route('store.admin.receivables.show', [
            'store_slug' => $this->store->slug,
            'customer' => $this->customer->id,
        ]));
        $response->assertSessionHas('success');

        // Check that balance is now 60,000 Ks
        $newBalance = $this->debtService->balanceFor($this->store->id, $this->customer->id);
        $this->assertEquals('60000.00', $newBalance);

        // Assert CustomerLedgerEntry exists
        $this->assertDatabaseHas('customer_ledger_entries', [
            'store_id' => $this->store->id,
            'customer_id' => $this->customer->id,
            'type' => CustomerLedgerEntry::TYPE_COLLECTION,
            'amount' => -40000.00,
        ]);
    }

    public function test_admin_cannot_collect_more_than_outstanding_balance(): void
    {
        $this->debtService->recordOpeningBalance(
            store: $this->store,
            customerId: $this->customer->id,
            amount: '50000.00',
            actor: $this->admin,
        );

        $response = $this->actingAs($this->admin)->post(route('store.admin.receivables.collect', [
            'store_slug' => $this->store->slug,
            'customer' => $this->customer->id,
        ]), [
            'amount' => '80000', // exceeds 50,000 Ks
            'payment_method' => 'cash',
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals('50000.00', $this->debtService->balanceFor($this->store->id, $this->customer->id));
    }

    public function test_admin_can_view_printable_statement_in_a4_and_thermal_format(): void
    {
        $this->debtService->recordOpeningBalance(
            store: $this->store,
            customerId: $this->customer->id,
            amount: '65000.00',
            actor: $this->admin,
        );

        // Test A4 format
        $responseA4 = $this->actingAs($this->admin)->get(route('store.admin.receivables.statement', [
            'store_slug' => $this->store->slug,
            'customer' => $this->customer->id,
            'format' => 'a4',
        ]));
        $responseA4->assertStatus(200);
        $responseA4->assertSee('format-a4');
        $responseA4->assertSee('65,000');

        // Test Thermal format
        $responseThermal = $this->actingAs($this->admin)->get(route('store.admin.receivables.statement', [
            'store_slug' => $this->store->slug,
            'customer' => $this->customer->id,
            'format' => 'thermal',
        ]));
        $responseThermal->assertStatus(200);
        $responseThermal->assertSee('format-thermal');
    }

    public function test_admin_can_export_receivables_csv(): void
    {
        $this->debtService->recordOpeningBalance(
            store: $this->store,
            customerId: $this->customer->id,
            amount: '65000.00',
            actor: $this->admin,
        );

        $response = $this->actingAs($this->admin)->get(route('store.admin.receivables.export', [
            'store_slug' => $this->store->slug,
        ]));

        $response->assertStatus(200);
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response->baseResponse);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_receivables_index_renders_without_translation_key_leaks(): void
    {
        $this->debtService->recordOpeningBalance(
            store: $this->store,
            customerId: $this->customer->id,
            amount: '65000.00',
            actor: $this->admin,
        );

        foreach (['en', 'my', 'zh'] as $locale) {
            app()->setLocale($locale);
            $response = $this->actingAs($this->admin)->get(route('store.admin.receivables.index', [
                'store_slug' => $this->store->slug,
                'lang' => $locale,
            ]));

            $response->assertStatus(200);
            $content = $response->getContent();
            $this->assertFalse(
                (bool) preg_match('/messages\.[a-zA-Z0-9_-]+/', $content),
                "Found leaked translation key in locale [{$locale}] on admin/receivables"
            );
        }
    }

    /**
     * Regression: omitting reference_no caused "Undefined array key 'reference_no'"
     * because the nullable-validated field was not included in $data by Laravel
     * when the key was absent from the request body.
     *
     * @see CustomerReceivableController@collect — fixed with ($data['reference_no'] ?? null)
     */
    public function test_collect_debt_without_reference_no_does_not_500(): void
    {
        $this->debtService->recordOpeningBalance(
            store: $this->store,
            customerId: $this->customer->id,
            amount: '100000.00',
            actor: $this->admin,
        );

        $response = $this->actingAs($this->admin)->post(route('store.admin.receivables.collect', [
            'store_slug' => $this->store->slug,
            'customer' => $this->customer->id,
        ]), [
            'amount' => '40000',
            'payment_method' => 'cash',
            // intentionally omit reference_no and notes
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals('60000.00', $this->debtService->balanceFor($this->store->id, $this->customer->id));
    }
}
