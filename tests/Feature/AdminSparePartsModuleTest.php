<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ComingSoonController;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\InventoryMovement;
use App\POS\Models\ServiceJob;
use App\POS\Models\ServiceJobItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSparePartsModuleTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;
    protected User $staff;
    protected User $customer;
    protected Product $product;
    protected ServiceJob $job;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create(['name' => 'Main Tech Store', 'slug' => 'main-tech']);
        $this->store->setting()->create(['store_name' => 'Main Tech Store', 'default_language' => 'en']);

        $this->manager = User::factory()->create(['name' => 'Store Manager', 'phone' => '09111111111']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);

        $this->staff = User::factory()->create(['name' => 'Staff Tech', 'phone' => '09222222222']);
        $this->staff->stores()->attach($this->store->id, ['role' => 'staff', 'status' => 'active']);

        $this->customer = User::factory()->create(['name' => 'Customer User', 'phone' => '09333333333', 'role' => 'customer']);

        $category = Category::create([
            'store_id' => $this->store->id,
            'name' => 'Phone Spare Parts',
            'slug' => 'phone-spare-parts',
        ]);

        $this->product = Product::create([
            'store_id' => $this->store->id,
            'category_id' => $category->id,
            'name' => 'iPhone 13 Pro Screen Original',
            'slug' => 'iphone-13-pro-screen-original',
            'sku' => 'IP13P-SCR',
            'retail_price' => 120000,
            'wholesale_price' => 100000,
        ]);

        $this->job = ServiceJob::create([
            'store_id' => $this->store->id,
            'job_number' => 'JOB-202608-001',
            'contact_name' => 'Ko Aung',
            'contact_phone' => '09444444444',
            'device_type' => 'Smartphone',
            'brand' => 'Apple',
            'model' => 'iPhone 13 Pro',
            'reported_problem' => 'Broken Screen',
            'status' => 'in_repair',
            'created_by' => $this->manager->id,
        ]);

        ServiceJobItem::create([
            'service_job_id' => $this->job->id,
            'item_type' => 'part',
            'name' => 'iPhone 13 Pro Screen Original',
            'sku' => 'IP13P-SCR',
            'product_id' => $this->product->id,
            'quantity' => 1,
            'unit_price' => 120000,
            'subtotal' => 120000,
            'is_deducted' => false,
        ]);
    }

    public function test_manager_can_view_spare_parts_index(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/spare-parts");

        $response->assertStatus(200);
        $response->assertSee('iPhone 13 Pro Screen Original');
        $response->assertSee('JOB-202608-001');
        $response->assertSee('120,000');
    }

    public function test_staff_can_view_spare_parts_index(): void
    {
        $response = $this->actingAs($this->staff)
            ->get("/store/{$this->store->slug}/admin/spare-parts");

        $response->assertStatus(200);
        $response->assertSee('iPhone 13 Pro Screen Original');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get("/store/{$this->store->slug}/admin/spare-parts");

        $response->assertRedirect(route('login'));
    }

    public function test_customer_without_role_is_blocked(): void
    {
        $response = $this->actingAs($this->customer)
            ->get("/store/{$this->store->slug}/admin/spare-parts");

        $response->assertStatus(403);
    }

    public function test_index_search_filter(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/spare-parts?q=IP13P-SCR");

        $response->assertStatus(200);
        $response->assertSee('iPhone 13 Pro Screen Original');

        $responseEmpty = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/spare-parts?q=NONEXISTENT");

        $responseEmpty->assertStatus(200);
        $responseEmpty->assertDontSee('iPhone 13 Pro Screen Original');
    }

    public function test_index_deduction_filter(): void
    {
        $responsePending = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/spare-parts?deducted=pending");

        $responsePending->assertStatus(200);
        $responsePending->assertSee('iPhone 13 Pro Screen Original');

        $responseDeducted = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/spare-parts?deducted=deducted");

        $responseDeducted->assertStatus(200);
        $responseDeducted->assertDontSee('iPhone 13 Pro Screen Original');
    }

    public function test_export_downloads_csv(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/spare-parts/export");

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
    }

    public function test_manager_can_deduct_part_stock(): void
    {
        /** @var ServiceJobItem $item */
        $item = $this->job->items()->first();

        // Seed opening balance via InventoryService
        app(\App\POS\Services\InventoryService::class)->postMovement([
            'store_id' => $this->store->id,
            'product_id' => $this->product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => 10,
            'unit_cost' => '80000',
            'source_type' => 'opening_balance',
            'client_transaction_id' => 'ob-1',
            'occurred_at' => now(),
            'posted_by' => $this->manager->id,
        ]);

        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/spare-parts/{$item->id}/deduct");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $item->refresh();
        $this->assertTrue($item->is_deducted);
    }

    public function test_coming_soon_registry_no_longer_contains_spare_parts(): void
    {
        $this->assertArrayNotHasKey('spare-parts', ComingSoonController::modules());
    }

    public function test_index_renders_in_all_supported_locales(): void
    {
        foreach (['en', 'my', 'zh_CN'] as $locale) {
            $store = Store::create(['name' => "Store {$locale}", 'slug' => "store-sp-{$locale}"]);
            $store->setting()->create(['store_name' => "Store {$locale}", 'default_language' => $locale]);
            $this->manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

            $response = $this->actingAs($this->manager)
                ->get("/store/{$store->slug}/admin/spare-parts");

            $response->assertStatus(200);
            $response->assertDontSee('messages.', false);
        }
    }
}
