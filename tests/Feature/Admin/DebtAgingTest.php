<?php

namespace Tests\Feature\Admin;

use App\Models\Store;
use App\Models\User;
use App\POS\Models\CustomerLedgerEntry;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebtAgingTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;
    protected Store $otherStore;
    protected User $otherManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create(['name' => 'Aging Store 1', 'slug' => 'aging-store-1']);
        $this->store->setting()->create(['store_name' => 'Aging Store 1', 'default_language' => 'en']);

        $this->manager = User::factory()->create(['name' => 'Manager Mg Mg', 'phone' => '09111222333']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);

        $this->otherStore = Store::create(['name' => 'Aging Store 2', 'slug' => 'aging-store-2']);
        $this->otherStore->setting()->create(['store_name' => 'Aging Store 2', 'default_language' => 'en']);

        $this->otherManager = User::factory()->create(['name' => 'Other Manager', 'phone' => '09888777666']);
        $this->otherManager->stores()->attach($this->otherStore->id, ['role' => 'store_manager', 'status' => 'active']);
    }

    public function test_manager_can_access_debt_aging_dashboard(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/reports/debt-aging");

        $response->assertOk();
        $response->assertSee(__('messages.debt_aging_title'));
    }

    public function test_debt_aging_computes_fifo_aging_buckets(): void
    {
        $customer1 = User::factory()->create(['name' => 'Daw Myaing', 'phone' => '09222333444']);
        $customer2 = User::factory()->create(['name' => 'U Ba', 'phone' => '09555666777']);

        // Customer 1: Old debt (100 days ago) = 500,000, and Recent debt (10 days ago) = 200,000. Total = 700,000.
        CustomerLedgerEntry::create([
            'store_id'     => $this->store->id,
            'customer_id'  => $customer1->id,
            'type'         => CustomerLedgerEntry::TYPE_SALE_DEBT,
            'amount'       => 500000.00,
            'occurred_at'  => Carbon::now()->subDays(100),
        ]);
        CustomerLedgerEntry::create([
            'store_id'     => $this->store->id,
            'customer_id'  => $customer1->id,
            'type'         => CustomerLedgerEntry::TYPE_SALE_DEBT,
            'amount'       => 200000.00,
            'occurred_at'  => Carbon::now()->subDays(10),
        ]);

        // Customer 2: Debt (45 days ago) = 150,000
        CustomerLedgerEntry::create([
            'store_id'     => $this->store->id,
            'customer_id'  => $customer2->id,
            'type'         => CustomerLedgerEntry::TYPE_SALE_DEBT,
            'amount'       => 150000.00,
            'occurred_at'  => Carbon::now()->subDays(45),
        ]);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/reports/debt-aging");

        $response->assertOk();
        // Total Outstanding = 850,000
        $response->assertSee('850,000');
        $response->assertSee('Daw Myaing');
        $response->assertSee('U Ba');
        $response->assertSee('500,000'); // Customer 1 90+ days bucket
        $response->assertSee('200,000'); // Customer 1 0-30 days bucket
        $response->assertSee('150,000'); // Customer 2 31-60 days bucket
    }

    public function test_debt_aging_search_and_bucket_filters(): void
    {
        $customerA = User::factory()->create(['name' => 'Ko Aung Gyi', 'phone' => '09777111222']);
        $customerB = User::factory()->create(['name' => 'Ma Thandar', 'phone' => '09777333444']);

        CustomerLedgerEntry::create([
            'store_id'     => $this->store->id,
            'customer_id'  => $customerA->id,
            'type'         => CustomerLedgerEntry::TYPE_SALE_DEBT,
            'amount'       => 100000.00,
            'occurred_at'  => Carbon::now()->subDays(15),
        ]);

        CustomerLedgerEntry::create([
            'store_id'     => $this->store->id,
            'customer_id'  => $customerB->id,
            'type'         => CustomerLedgerEntry::TYPE_SALE_DEBT,
            'amount'       => 300000.00,
            'occurred_at'  => Carbon::now()->subDays(95),
        ]);

        // Search by phone
        $searchRes = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/reports/debt-aging?search=09777111222");

        $searchRes->assertOk();
        $searchRes->assertSee('Ko Aung Gyi');
        $searchRes->assertDontSee('Ma Thandar');

        // Filter 90+ days
        $filterRes = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/reports/debt-aging?bucket=90_plus");

        $filterRes->assertOk();
        $filterRes->assertSee('Ma Thandar');
        $filterRes->assertDontSee('Ko Aung Gyi');
    }

    public function test_debt_aging_print_and_export(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/reports/debt-aging/print");

        $response->assertOk();
        $response->assertSee('Print Statement');

        $csvRes = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/reports/debt-aging/export");

        $csvRes->assertOk();
        $csvRes->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_debt_aging_store_isolation(): void
    {
        $secretCustomer = User::factory()->create(['name' => 'Secret Store 2 Debtor', 'phone' => '09999888777']);
        CustomerLedgerEntry::create([
            'store_id'     => $this->otherStore->id,
            'customer_id'  => $secretCustomer->id,
            'type'         => CustomerLedgerEntry::TYPE_SALE_DEBT,
            'amount'       => 999999.00,
            'occurred_at'  => Carbon::now()->subDays(5),
        ]);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/reports/debt-aging");

        $response->assertOk();
        $response->assertDontSee('Secret Store 2 Debtor');
        $response->assertDontSee('999,999');
    }
}
