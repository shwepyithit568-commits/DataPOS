<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\DeviceWarranty;
use App\POS\Models\ServiceJob;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarrantyTrackerTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $admin;
    protected Product $product;
    protected User $customer;

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

        $this->product = Product::create([
            'store_id' => $this->store->id,
            'name' => 'iPhone 15 Pro Max 256GB Natural Titanium',
            'slug' => 'iphone-15-pro-max-256gb',
            'sku' => 'IP15PM-256',
            'retail_price' => 4500000,
            'wholesale_price' => 4100000,
        ]);

        $this->customer = User::factory()->create([
            'name' => 'Ko Aung Kyaw',
            'phone' => '09790123456',
            'role' => 'customer',
        ]);
        $this->customer->stores()->attach($this->store->id, ['role' => 'customer']);
    }

    public function test_admin_can_access_warranty_tracker_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get(route('store.admin.warranty.index', [
            'store_slug' => $this->store->slug,
        ]));

        $response->assertStatus(200);
        $response->assertSee('DataPOS Mobile Hub');
    }

    public function test_admin_can_register_new_device_warranty(): void
    {
        $postData = [
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'customer_phone' => $this->customer->phone,
            'serial_number' => 'F2LZ90K8MD6M',
            'imei_primary' => '356789123456789',
            'imei_secondary' => '356789123456780',
            'invoice_number' => 'INV-2026-0891',
            'purchase_date' => '2026-08-01',
            'warranty_duration_months' => 12,
            'warranty_type' => 'official_brand',
            'status' => 'active',
            'terms_conditions' => 'Official Apple 1-Year Limited Warranty',
            'notes' => 'Customer purchased with AppleCare promotion',
        ];

        $response = $this->actingAs($this->admin)->post(route('store.admin.warranty.store', [
            'store_slug' => $this->store->slug,
        ]), $postData);

        $response->assertRedirect();
        $this->assertDatabaseHas('device_warranties', [
            'store_id' => $this->store->id,
            'serial_number' => 'F2LZ90K8MD6M',
            'imei_primary' => '356789123456789',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_quick_scan_lookup_by_serial_or_imei(): void
    {
        DeviceWarranty::create([
            'store_id' => $this->store->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'customer_name' => 'Ko Aung Kyaw',
            'customer_phone' => '09790123456',
            'serial_number' => 'TEST-SN-9999',
            'imei_primary' => '861234567890123',
            'purchase_date' => '2026-01-01',
            'warranty_duration_months' => 12,
            'warranty_expiry_date' => '2027-01-01',
            'warranty_type' => 'shop',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->get(route('store.admin.warranty.quick_scan', [
            'store_slug' => $this->store->slug,
            'q' => 'TEST-SN-9999',
        ]));

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'serial_number' => 'TEST-SN-9999',
            'imei_primary' => '861234567890123',
        ]);
    }

    public function test_admin_can_view_warranty_detail_and_service_history(): void
    {
        $warranty = DeviceWarranty::create([
            'store_id' => $this->store->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'customer_name' => 'Ko Aung Kyaw',
            'customer_phone' => '09790123456',
            'serial_number' => 'SN-SERVICE-1234',
            'imei_primary' => '351111222233334',
            'purchase_date' => '2026-01-01',
            'warranty_duration_months' => 12,
            'warranty_expiry_date' => '2027-01-01',
            'warranty_type' => 'shop',
            'status' => 'active',
        ]);

        // Create linked repair ticket with same serial
        ServiceJob::create([
            'store_id' => $this->store->id,
            'job_number' => 'JOB-9001',
            'status' => 'completed',
            'device_type' => 'smartphone',
            'imei_serial' => 'SN-SERVICE-1234',
            'model' => 'iPhone 15 Pro Max',
            'reported_problem' => 'Screen glass crack',
            'estimated_charge' => 150000,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('store.admin.warranty.show', [
            'store_slug' => $this->store->slug,
            'warranty' => $warranty->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('SN-SERVICE-1234');
        $response->assertSee('JOB-9001');
    }

    public function test_admin_can_record_warranty_claim(): void
    {
        $warranty = DeviceWarranty::create([
            'store_id' => $this->store->id,
            'product_name' => 'Samsung S24 Ultra',
            'serial_number' => 'S24-CLAIM-TEST',
            'purchase_date' => '2026-01-01',
            'warranty_duration_months' => 12,
            'warranty_expiry_date' => '2027-01-01',
            'warranty_type' => 'shop',
            'status' => 'active',
            'claim_count' => 0,
        ]);

        $response = $this->actingAs($this->admin)->post(route('store.admin.warranty.claim', [
            'store_slug' => $this->store->slug,
            'warranty' => $warranty->id,
        ]), [
            'claim_reason' => 'Battery draining fast',
            'resolution' => 'Replaced battery under warranty',
            'status' => 'claimed',
        ]);

        $response->assertRedirect();
        $warranty->refresh();
        $this->assertEquals(1, $warranty->claim_count);
        $this->assertEquals('claimed', $warranty->status);
        $this->assertStringContainsString('Battery draining fast', $warranty->notes);
    }

    public function test_admin_can_print_warranty_certificate(): void
    {
        $warranty = DeviceWarranty::create([
            'store_id' => $this->store->id,
            'product_name' => 'Dell Latitude Laptop',
            'serial_number' => 'DELL-CERT-7788',
            'customer_name' => 'Daw Mya Mya',
            'customer_phone' => '09450000000',
            'purchase_date' => '2026-05-15',
            'warranty_duration_months' => 24,
            'warranty_expiry_date' => '2028-05-15',
            'warranty_type' => 'official_brand',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->get(route('store.admin.warranty.certificate', [
            'store_slug' => $this->store->slug,
            'warranty' => $warranty->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('DELL-CERT-7788');
        $response->assertSee('Daw Mya Mya');
        $response->assertSee('cert-card');
    }

    public function test_admin_can_export_warranties_excel_and_csv(): void
    {
        DeviceWarranty::create([
            'store_id' => $this->store->id,
            'product_name' => 'iPad Air M2 128GB',
            'serial_number' => 'IPAD-M2-9999',
            'imei_primary' => '359999888877776',
            'customer_name' => 'U Thant Zin',
            'customer_phone' => '09971234567',
            'purchase_date' => '2026-03-01',
            'warranty_duration_months' => 12,
            'warranty_expiry_date' => '2027-03-01',
            'warranty_type' => 'official_brand',
            'status' => 'active',
        ]);

        // 1. Test Excel (.xlsx) export
        $excelResponse = $this->actingAs($this->admin)->get(route('store.admin.warranty.export', [
            'store_slug' => $this->store->slug,
            'format' => 'xlsx',
        ]));

        $excelResponse->assertStatus(200);
        $this->assertStringContainsString('spreadsheet', $excelResponse->headers->get('content-type'));
        $this->assertStringContainsString('.xlsx', $excelResponse->headers->get('content-disposition'));

        // 2. Test CSV export
        $csvResponse = $this->actingAs($this->admin)->get(route('store.admin.warranty.export', [
            'store_slug' => $this->store->slug,
            'format' => 'csv',
        ]));

        $csvResponse->assertStatus(200);
        $this->assertStringContainsString('text/csv', $csvResponse->headers->get('content-type'));
        $content = $csvResponse->streamedContent();
        $this->assertStringContainsString('iPad Air M2 128GB', $content);
        $this->assertStringContainsString('IPAD-M2-9999', $content);
        $this->assertStringContainsString('U Thant Zin', $content);
    }
}

