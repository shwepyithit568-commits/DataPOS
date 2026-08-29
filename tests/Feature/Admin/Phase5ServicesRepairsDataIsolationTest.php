<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\DeviceWarranty;
use App\POS\Models\ServiceJob;
use App\POS\Models\ServiceJobItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase5ServicesRepairsDataIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Store $storeA;
    protected Store $storeB;
    protected User $staffA;
    protected User $staffB;
    protected Product $productA;
    protected Product $productB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storeA = Store::create(['name' => 'Store Alpha', 'slug' => 'store-alpha', 'is_active' => true]);
        $this->storeB = Store::create(['name' => 'Store Beta', 'slug' => 'store-beta', 'is_active' => true]);

        $this->staffA = User::create(['name' => 'Staff Alpha', 'phone' => '09111111111', 'password' => bcrypt('password')]);
        $this->staffB = User::create(['name' => 'Staff Beta', 'phone' => '09222222222', 'password' => bcrypt('password')]);

        $this->storeA->users()->attach($this->staffA->id, ['role' => 'store_manager', 'status' => 'active']);
        $this->storeB->users()->attach($this->staffB->id, ['role' => 'store_manager', 'status' => 'active']);

        $this->productA = Product::create([
            'store_id' => $this->storeA->id,
            'name' => 'Screen Replacement Alpha',
            'slug' => 'screen-alpha',
            'sku' => 'PART-A',
            'cost_price' => 10000,
            'retail_price' => 15000,
            'wholesale_price' => 12000,
        ]);

        $this->productB = Product::create([
            'store_id' => $this->storeB->id,
            'name' => 'Battery Replacement Beta',
            'slug' => 'battery-beta',
            'sku' => 'PART-B',
            'cost_price' => 20000,
            'retail_price' => 25000,
            'wholesale_price' => 22000,
        ]);
    }

    public function test_service_jobs_cross_store_isolation(): void
    {
        // 1. Staff A cannot create a service job using Store B's product as spare part
        $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/admin/service-jobs", [
                'contact_name' => 'John Doe',
                'contact_phone' => '09123456789',
                'device_type' => 'Smartphone',
                'brand' => 'Apple',
                'model' => 'iPhone 13',
                'reported_problem' => 'Broken screen',
                'items' => [
                    [
                        'item_type' => 'part',
                        'name' => 'Battery',
                        'product_id' => $this->productB->id, // foreign product
                        'quantity' => 1,
                        'unit_price' => 25000,
                    ],
                ],
            ])
            ->assertSessionHasErrors('items.0.product_id');

        // 2. Service Job in Store B cannot be accessed or updated by Staff A
        $jobB = ServiceJob::create([
            'store_id' => $this->storeB->id,
            'job_number' => 'SVC-B-001',
            'device_type' => 'Smartphone',
            'brand' => 'Samsung',
            'model' => 'Galaxy S23',
            'contact_name' => 'Beta Customer',
            'contact_phone' => '09999999999',
            'reported_problem' => 'Dead battery',
            'status' => 'received',
            'estimated_charge' => 30000,
            'created_by' => $this->staffB->id,
        ]);

        $this->actingAs($this->staffA)
            ->get("/store/{$this->storeA->slug}/admin/service-jobs/{$jobB->id}")
            ->assertNotFound();

        $this->actingAs($this->staffA)
            ->get("/store/{$this->storeA->slug}/admin/service-jobs/{$jobB->id}/print")
            ->assertNotFound();

        $this->actingAs($this->staffA)
            ->get("/store/{$this->storeA->slug}/admin/service-jobs/{$jobB->id}/edit")
            ->assertNotFound();

        $this->actingAs($this->staffA)
            ->put("/store/{$this->storeA->slug}/admin/service-jobs/{$jobB->id}", [
                'contact_name' => 'Hacked Name',
                'contact_phone' => '09999999999',
            ])
            ->assertNotFound();

        $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/admin/service-jobs/{$jobB->id}/status", [
                'status' => 'diagnosing',
            ])
            ->assertNotFound();

        $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/admin/service-jobs/{$jobB->id}/payments", [
                'method' => 'cash',
                'amount' => 5000,
            ])
            ->assertNotFound();
    }

    public function test_spare_parts_cross_store_isolation(): void
    {
        $jobB = ServiceJob::create([
            'store_id' => $this->storeB->id,
            'job_number' => 'SVC-B-002',
            'device_type' => 'Laptop',
            'brand' => 'Dell',
            'model' => 'XPS 15',
            'contact_name' => 'Beta Laptop Customer',
            'contact_phone' => '09888888888',
            'reported_problem' => 'Dead screen',
            'status' => 'in_repair',
            'created_by' => $this->staffB->id,
        ]);

        $partB = ServiceJobItem::create([
            'service_job_id' => $jobB->id,
            'item_type' => 'part',
            'name' => 'Battery Replacement Beta',
            'product_id' => $this->productB->id,
            'quantity' => 1,
            'unit_price' => 25000,
            'subtotal' => '25000.00',
            'is_deducted' => false,
        ]);

        // 1. Store A spare parts list does not show Part B
        $response = $this->actingAs($this->staffA)
            ->get("/store/{$this->storeA->slug}/admin/spare-parts");

        $response->assertOk();
        $response->assertDontSee('SVC-B-002');
        $response->assertDontSee('Beta Laptop Customer');

        // 2. Staff A cannot deduct Part B
        $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/admin/spare-parts/{$partB->id}/deduct")
            ->assertNotFound();
    }

    public function test_warranty_tracker_cross_store_isolation(): void
    {
        // 1. Staff A cannot register warranty using Store B's product
        $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/admin/warranty", [
                'product_id' => $this->productB->id, // foreign product
                'product_name' => 'Phone Beta',
                'serial_number' => 'SN-12345',
                'purchase_date' => now()->format('Y-m-d'),
                'warranty_duration_months' => 12,
                'warranty_type' => 'shop',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('product_id');

        // 2. Warranty created in Store B cannot be viewed, edited, claimed, or printed by Staff A
        $warrantyB = DeviceWarranty::create([
            'store_id' => $this->storeB->id,
            'product_id' => $this->productB->id,
            'product_name' => 'Phone Beta',
            'serial_number' => 'SN-B-001',
            'purchase_date' => now()->format('Y-m-d'),
            'warranty_expiry_date' => now()->addYear()->format('Y-m-d'),
            'warranty_duration_months' => 12,
            'warranty_type' => 'shop',
            'status' => 'active',
            'created_by' => $this->staffB->id,
        ]);

        $this->actingAs($this->staffA)
            ->get("/store/{$this->storeA->slug}/admin/warranty/{$warrantyB->id}")
            ->assertNotFound();

        $this->actingAs($this->staffA)
            ->get("/store/{$this->storeA->slug}/admin/warranty/{$warrantyB->id}/edit")
            ->assertNotFound();

        $this->actingAs($this->staffA)
            ->put("/store/{$this->storeA->slug}/admin/warranty/{$warrantyB->id}", [
                'product_name' => 'Hacked Warranty',
                'serial_number' => 'SN-HACKED',
                'purchase_date' => now()->format('Y-m-d'),
                'warranty_duration_months' => 12,
                'warranty_type' => 'shop',
                'status' => 'active',
            ])
            ->assertNotFound();

        $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/admin/warranty/{$warrantyB->id}/claim", [
                'claim_reason' => 'Defective part',
            ])
            ->assertNotFound();

        $this->actingAs($this->staffA)
            ->get("/store/{$this->storeA->slug}/admin/warranty/{$warrantyB->id}/certificate")
            ->assertNotFound();
    }

    public function test_public_service_tracking_token_isolation(): void
    {
        $jobB = ServiceJob::create([
            'store_id' => $this->storeB->id,
            'job_number' => 'SVC-B-TRACK',
            'device_type' => 'Smartphone',
            'brand' => 'Apple',
            'model' => 'iPhone 15',
            'contact_name' => 'Beta Tracking User',
            'contact_phone' => '09777777777',
            'reported_problem' => 'Battery issue',
            'status' => 'diagnosing',
            'created_by' => $this->staffB->id,
        ]);

        // Customer accessing Store A's tracking route with Store B's token is rejected with 404
        $this->get("/store/{$this->storeA->slug}/track/service/{$jobB->tracking_token}")
            ->assertNotFound();

        // Customer searching Store A for Store B's job number gets no results
        $response = $this->get("/store/{$this->storeA->slug}/track/service?q=SVC-B-TRACK");
        $response->assertOk();
        $response->assertDontSee('Beta Tracking User');
    }
}
