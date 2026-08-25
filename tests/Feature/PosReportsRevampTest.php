<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use App\POS\Models\PosPayment;
use App\POS\Models\PosSale;
use App\POS\Models\PosSaleItem;
use App\POS\Models\ServiceJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosReportsRevampTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create(['name' => 'POS Test Store', 'slug' => 'pos-test-store']);
        $this->store->setting()->create(['store_name' => 'POS Test Store', 'default_language' => 'en']);

        $this->manager = User::factory()->create(['name' => 'Manager Mg Mg', 'phone' => '09111222333']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);

        $this->staff = User::factory()->create(['name' => 'Staff Ko Ko', 'phone' => '09222333444']);
        $this->staff->stores()->attach($this->store->id, ['role' => 'staff', 'status' => 'active']);
    }

    public function test_pos_sales_report_page_renders_with_kpi_and_records(): void
    {
        $sale = PosSale::create([
            'store_id'       => $this->store->id,
            'cashier_id'     => $this->staff->id,
            'receipt_number' => 'REC-POS-001',
            'status'         => 'posted',
            'subtotal'       => 150000.00,
            'discount'       => 10000.00,
            'tax'            => 0.00,
            'total'          => 140000.00,
            'posted_at'      => now(),
        ]);

        PosPayment::create([
            'pos_sale_id' => $sale->id,
            'method'      => 'kpay',
            'amount'      => 140000.00,
        ]);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/pos/reports/sales?preset=today");

        $response->assertOk();
        $response->assertSee('140,000');
        $response->assertSee('REC-POS-001');
        $response->assertSee('Staff Ko Ko');
    }

    public function test_pos_sales_report_csv_export(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/pos/reports/sales/export?preset=today");

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_pos_service_jobs_report_renders_with_kpis(): void
    {
        $job = ServiceJob::create([
            'store_id'         => $this->store->id,
            'job_number'       => 'SVC-20260825-0001',
            'voucher_no'       => 'V-1001',
            'contact_name'     => 'Daw Hla',
            'contact_phone'    => '09123456789',
            'device_type'      => 'Smartphone',
            'brand'            => 'Apple',
            'model'            => 'iPhone 13',
            'reported_problem' => 'Screen Broken',
            'technician_id'    => $this->staff->id,
            'status'           => 'ready',
            'estimated_charge' => 85000.00,
            'final_charge'     => 85000.00,
            'created_by'       => $this->manager->id,
        ]);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/pos/reports/services?preset=today");

        $response->assertOk();
        $response->assertSee('SVC-20260825-0001');
        $response->assertSee('85,000');
        $response->assertSee('Daw Hla');
        $response->assertSee('iPhone 13');
        $response->assertSee('Staff Ko Ko');
    }

    public function test_pos_service_jobs_report_csv_export(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/pos/reports/services/export?preset=today");

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
