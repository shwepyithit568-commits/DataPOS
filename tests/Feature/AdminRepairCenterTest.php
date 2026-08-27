<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\ServiceJob;
use App\POS\Models\ServiceJobPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRepairCenterTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;
    protected User $staff;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create(['name' => 'Store One', 'slug' => 'store-one']);
        $this->store->setting()->create(['store_name' => 'Store One', 'default_language' => 'en']);

        $this->manager = User::factory()->create(['phone' => '09111111111']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);

        $this->staff = User::factory()->create(['phone' => '09222222222']);
        $this->staff->stores()->attach($this->store->id, ['role' => 'staff', 'status' => 'active']);

        $this->customer = User::factory()->create(['name' => 'Aung Customer', 'phone' => '09333333333', 'role' => 'customer']);
        $this->customer->stores()->attach($this->store->id, ['role' => 'retail_customer', 'status' => 'active']);
    }

    private function makeJob(array $overrides = []): ServiceJob
    {
        // created_at is not mass-assignable (not in $fillable) — force it via
        // the query builder so date-range tests can place jobs on fixed dates.
        $createdAt = $overrides['created_at'] ?? null;
        unset($overrides['created_at']);

        $job = ServiceJob::create(array_merge([
            'store_id' => $this->store->id,
            'job_number' => ServiceJob::generateNumber($this->store->id),
            'contact_name' => 'Walk In',
            'contact_phone' => '09999999999',
            'device_type' => 'Phone',
            'model' => 'iPhone 13',
            'imei_serial' => '353912345678901',
            'reported_problem' => 'Screen cracked',
            'status' => 'received',
            'estimated_charge' => '50000.00',
            'created_by' => $this->manager->id,
        ], $overrides));

        if ($createdAt !== null) {
            \Illuminate\Support\Facades\DB::table('service_jobs')
                ->where('id', $job->id)
                ->update(['created_at' => $createdAt]);
        }

        // Mirror the controller's intake flow: every job starts with a
        // "received" history row so transition counts stay meaningful.
        \App\POS\Models\ServiceJobStatus::create([
            'service_job_id' => $job->id,
            'status' => $job->status,
            'note' => 'Device received',
            'changed_by' => $this->manager->id,
        ]);

        return $job;
    }

    public function test_manager_can_view_repair_index(): void
    {
        $this->makeJob();

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/repairs");

        $response->assertStatus(200);
        $response->assertSeeText('Repair Center');
        $response->assertSeeText('iPhone 13');
    }

    public function test_staff_can_view_repair_index(): void
    {
        $response = $this->actingAs($this->staff)
            ->get("/store/{$this->store->slug}/admin/repairs");

        $response->assertStatus(200);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get("/store/{$this->store->slug}/admin/repairs");

        $response->assertRedirect(route('login'));
    }

    public function test_customer_without_admin_role_is_blocked(): void
    {
        $response = $this->actingAs($this->customer)
            ->get("/store/{$this->store->slug}/admin/repairs");

        $response->assertStatus(403);
    }

    public function test_manager_can_create_repair_job(): void
    {
        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/repairs", [
                'contact_name' => 'U Ba Win',
                'contact_phone' => '09777777777',
                'device_type' => 'Phone',
                'model' => 'Samsung A52',
                'imei_serial' => '359876543210987',
                'reported_problem' => 'Battery drains fast',
                'estimated_charge' => '35000',
            ]);

        /** @var ServiceJob $job */
        $job = ServiceJob::where('store_id', $this->store->id)->first();

        $this->assertNotNull($job);
        $this->assertSame('received', $job->status);
        $this->assertStringStartsWith('SVC-', $job->job_number);
        // Intake must append the initial status-history row.
        $this->assertSame(1, $job->statusHistory()->count());

        $response->assertRedirect(route('store.admin.repairs.show', [
            ...[$this->store->slug],
            'repair' => $job->id,
        ]));
    }

    public function test_create_requires_customer_or_contact(): void
    {
        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/repairs", [
                'device_type' => 'Phone',
                'reported_problem' => 'Broken screen',
            ]);

        $response->assertSessionHasErrors('contact_phone');
        $this->assertSame(0, ServiceJob::count());
    }

    public function test_create_with_linked_customer(): void
    {
        $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/repairs", [
                'customer_id' => $this->customer->id,
                'device_type' => 'Laptop',
                'reported_problem' => 'No power',
            ]);

        $job = ServiceJob::first();
        $this->assertNotNull($job);
        $this->assertSame($this->customer->id, $job->customer_id);
    }

    public function test_show_displays_job_details(): void
    {
        $job = $this->makeJob();

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/repairs/{$job->id}");

        $response->assertStatus(200);
        $response->assertSeeText($job->job_number);
        $response->assertSeeText('Screen cracked');
    }

    public function test_show_returns_404_for_other_store_job(): void
    {
        $otherStore = Store::create(['name' => 'Store Two', 'slug' => 'store-two']);
        $otherStore->setting()->create(['store_name' => 'Store Two', 'default_language' => 'en']);

        $job = ServiceJob::create([
            'store_id' => $otherStore->id,
            'job_number' => 'RPR-20260822-9999',
            'contact_name' => 'Other',
            'device_type' => 'Phone',
            'reported_problem' => 'Test',
            'status' => 'received',
            'created_by' => $this->manager->id,
        ]);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/repairs/{$job->id}");

        $response->assertStatus(404);
    }

    public function test_status_transition_appends_history(): void
    {
        $job = $this->makeJob();

        $response = $this->actingAs($this->staff)
            ->post("/store/{$this->store->slug}/admin/repairs/{$job->id}/status", [
                'status' => 'diagnosing',
                'note' => 'Checking display flex',
            ]);

        $job->refresh();
        $this->assertSame('diagnosing', $job->status);
        $this->assertSame(2, $job->statusHistory()->count());
        $this->assertSame('diagnosing', $job->statusHistory()->first()->status);

        $response->assertSessionHas('success');
    }

    public function test_invalid_status_is_rejected(): void
    {
        $job = $this->makeJob();

        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/repairs/{$job->id}/status", [
                'status' => 'not-a-real-status',
            ]);

        $response->assertSessionHasErrors('status');
        $this->assertSame('received', $job->fresh()->status);
    }

    public function test_same_status_transition_is_rejected(): void
    {
        $job = $this->makeJob();

        $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/repairs/{$job->id}/status", [
                'status' => 'received',
            ]);

        $this->assertSame(1, $job->statusHistory()->count());
    }

    public function test_terminal_job_blocks_status_change(): void
    {
        $job = $this->makeJob(['status' => 'delivered']);

        $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/repairs/{$job->id}/status", [
                'status' => 'in_repair',
            ]);

        $this->assertSame('delivered', $job->fresh()->status);
    }

    public function test_payment_records_receipt(): void
    {
        $job = $this->makeJob(['final_charge' => '50000.00']);

        $response = $this->actingAs($this->staff)
            ->post("/store/{$this->store->slug}/admin/repairs/{$job->id}/payments", [
                'method' => 'kpay',
                'amount' => '20000',
                'reference' => 'KPZ-123',
            ]);

        $response->assertSessionHas('success');
        $this->assertSame(1, $job->payments()->count());
        $this->assertSame(30000.0, $job->fresh()->outstanding());
    }

    public function test_overpayment_is_rejected(): void
    {
        $job = $this->makeJob(['final_charge' => '50000.00']);

        $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/repairs/{$job->id}/payments", [
                'method' => 'cash',
                'amount' => '60000',
            ]);

        $this->assertSame(0, $job->payments()->count());
    }

    public function test_index_search_filters_by_imei(): void
    {
        $this->makeJob(['imei_serial' => 'AAA111', 'model' => 'FindMe']);
        $this->makeJob(['imei_serial' => 'BBB222', 'model' => 'Other']);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/repairs?search=AAA111");

        $response->assertStatus(200);
        $response->assertSeeText('FindMe');
        $response->assertDontSeeText('Other');
    }

    public function test_index_status_filter(): void
    {
        $this->makeJob(['status' => 'ready', 'model' => 'ReadyPhone']);
        $this->makeJob(['status' => 'in_repair', 'model' => 'WorkingPhone']);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/repairs?status=ready");

        $response->assertStatus(200);
        $response->assertSeeText('ReadyPhone');
        $response->assertDontSeeText('WorkingPhone');
    }

    public function test_index_does_not_list_other_store_jobs(): void
    {
        $otherStore = Store::create(['name' => 'Store Two', 'slug' => 'store-two']);
        $otherStore->setting()->create(['store_name' => 'Store Two', 'default_language' => 'en']);

        ServiceJob::create([
            'store_id' => $otherStore->id,
            'job_number' => 'RPR-20260822-8888',
            'contact_name' => 'Other Store Job',
            'device_type' => 'Phone',
            'reported_problem' => 'Test',
            'status' => 'received',
            'created_by' => $this->manager->id,
        ]);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/repairs");

        $response->assertStatus(200);
        $response->assertDontSeeText('Other Store Job');
    }

    public function test_index_renders_in_all_supported_locales_without_key_leaks(): void
    {
        foreach (['en', 'my', 'zh_CN'] as $code) {
            $store = Store::create(['name' => "Store {$code}", 'slug' => "store-{$code}"]);
            $store->setting()->create(['store_name' => "Store {$code}", 'default_language' => $code]);
            $this->manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

            $response = $this->actingAs($this->manager)
                ->get("/store/store-{$code}/admin/repairs");

            $response->assertStatus(200);
            $response->assertDontSee('messages.', false);
        }
    }

    public function test_coming_soon_registry_no_longer_contains_repairs(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/coming-soon/repairs");

        // Module shipped → registry entry removed → slug 404s.
        $response->assertStatus(404);
    }

    /* ------------------------------------------------------------------ */
    /*  Repairs Center parity — tabs, filters, export, items, stock        */
    /* ------------------------------------------------------------------ */

    public function test_index_tab_processing_buckets_in_progress_jobs(): void
    {
        $this->makeJob(['status' => 'received', 'model' => 'ProcessingPhone']);
        $this->makeJob(['status' => 'in_repair', 'model' => 'BenchPhone']);
        $this->makeJob(['status' => 'delivered', 'model' => 'DonePhone']);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/repairs?tab=processing");

        $response->assertStatus(200);
        $response->assertSeeText('ProcessingPhone');
        $response->assertSeeText('BenchPhone');
        $response->assertDontSeeText('DonePhone');
    }

    public function test_index_tab_ready_buckets_ready_jobs(): void
    {
        $this->makeJob(['status' => 'ready', 'model' => 'ReadyPhone']);
        $this->makeJob(['status' => 'received', 'model' => 'NotReadyYet']);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/repairs?tab=ready");

        $response->assertStatus(200);
        $response->assertSeeText('ReadyPhone');
        $response->assertDontSeeText('NotReadyYet');
    }

    public function test_index_tab_history_buckets_terminal_jobs(): void
    {
        $this->makeJob(['status' => 'delivered', 'model' => 'DeliveredPhone']);
        $this->makeJob(['status' => 'cancelled', 'model' => 'CancelledPhone']);
        $this->makeJob(['status' => 'received', 'model' => 'StillOpen']);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/repairs?tab=history");

        $response->assertStatus(200);
        $response->assertSeeText('DeliveredPhone');
        $response->assertSeeText('CancelledPhone');
        $response->assertDontSeeText('StillOpen');
    }

    public function test_index_date_range_filter(): void
    {
        $this->makeJob(['model' => 'OldJob', 'created_at' => '2026-08-01 09:00:00']);
        $this->makeJob(['model' => 'NewJob', 'created_at' => '2026-08-10 09:00:00']);

        // Short alias (from/to)
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/repairs?from=2026-08-05&to=2026-08-15");

        $response->assertStatus(200);
        $response->assertSeeText('NewJob');
        $response->assertDontSeeText('OldJob');

        // Toolbar param names (date_from/date_to) must filter identically.
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/repairs?date_from=2026-08-05&date_to=2026-08-15");

        $response->assertStatus(200);
        $response->assertSeeText('NewJob');
        $response->assertDontSeeText('OldJob');
    }

    public function test_create_rejects_foreign_store_customer(): void
    {
        $otherStore = Store::create(['name' => 'Other', 'slug' => 'other-store']);
        $foreignCustomer = User::factory()->create(['phone' => '09555555555']);
        $foreignCustomer->stores()->attach($otherStore->id, ['role' => 'retail_customer', 'status' => 'active']);

        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/repairs", [
                'customer_id' => $foreignCustomer->id,
                'device_type' => 'Phone',
                'reported_problem' => 'Test',
            ]);

        $response->assertSessionHasErrors('customer_id');
        $this->assertSame(0, ServiceJob::count());
    }

    public function test_create_rejects_foreign_store_technician(): void
    {
        $otherStore = Store::create(['name' => 'Other', 'slug' => 'other-store']);
        $foreignStaff = User::factory()->create(['phone' => '09666666666']);
        $foreignStaff->stores()->attach($otherStore->id, ['role' => 'staff', 'status' => 'active']);

        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/repairs", [
                'contact_name' => 'U Test',
                'device_type' => 'Phone',
                'reported_problem' => 'Test',
                'technician_id' => $foreignStaff->id,
            ]);

        $response->assertSessionHasErrors('technician_id');
        $this->assertSame(0, ServiceJob::count());
    }

    public function test_index_sort_by_customer_name(): void
    {
        $this->makeJob(['contact_name' => 'Zaw Win', 'model' => 'PhoneA']);
        $this->makeJob(['contact_name' => 'Aung Aung', 'model' => 'PhoneB']);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/repairs?sort=customer");

        $response->assertStatus(200);
        // Aung Aung (alphabetically first) must appear before Zaw Win in the DOM.
        $this->assertLessThan(
            strpos($response->getContent(), 'Zaw Win'),
            strpos($response->getContent(), 'Aung Aung')
        );
    }

    public function test_export_downloads_csv_of_filtered_jobs(): void
    {
        $job = $this->makeJob(['model' => 'ExportPhone', 'contact_name' => 'Export Customer']);
        $job->items()->create([
            'item_type' => 'service',
            'name' => 'Screen replacement',
            'quantity' => 1,
            'unit_price' => '15000.00',
            'subtotal' => '15000.00',
        ]);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/repairs/export");

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type') ?? '');
        $csv = $response->streamedContent();
        $this->assertStringContainsString($job->job_number, $csv);
        $this->assertStringContainsString('Export Customer', $csv);
        $this->assertStringContainsString('Screen replacement', $csv);
    }

    public function test_create_job_persists_line_items_with_subtotals(): void
    {
        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/repairs", [
                'contact_name' => 'U Test',
                'contact_phone' => '09876543210',
                'device_type' => 'Phone',
                'reported_problem' => 'Broken screen',
                'estimated_charge' => '25000',
                'estimated_completion' => '2026-08-25',
                'items' => [
                    ['item_type' => 'service', 'name' => 'Screen replacement', 'quantity' => '1', 'unit_price' => '15000', 'product_id' => ''],
                    ['item_type' => 'part', 'name' => 'LCD', 'quantity' => '2', 'unit_price' => '5000', 'product_id' => ''],
                ],
            ]);

        /** @var ServiceJob $job */
        $job = ServiceJob::first();

        $this->assertNotNull($job);
        $this->assertSame('2026-08-25', $job->estimated_completion?->format('Y-m-d'));
        $this->assertSame(2, $job->items()->count());
        $this->assertSame('15000.00', $job->items()->first()->subtotal);
        $this->assertSame('10000.00', $job->items()->skip(1)->first()->subtotal);
        $this->assertSame(25000.0, $job->itemsTotal());
    }

    public function test_create_skips_cross_store_item_products(): void
    {
        $otherStore = Store::create(['name' => 'Other', 'slug' => 'other-store']);
        $otherProduct = Product::create([
            'store_id' => $otherStore->id,
            'sku' => 'OTHER-1',
            'name' => 'Other Store Part',
            'slug' => 'other-store-part',
            'retail_price' => 9999,
            'wholesale_price' => 8999,
        ]);

        $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/repairs", [
                'contact_name' => 'U Test',
                'device_type' => 'Phone',
                'reported_problem' => 'Test',
                'items' => [
                    ['item_type' => 'part', 'name' => 'Foreign part', 'quantity' => '1', 'unit_price' => '1000', 'product_id' => (string) $otherProduct->id],
                    ['item_type' => 'service', 'name' => 'Diagnostics', 'quantity' => '1', 'unit_price' => '2000', 'product_id' => ''],
                ],
            ]);

        /** @var ServiceJob $job */
        $job = ServiceJob::first();
        $this->assertNotNull($job);
        // Foreign product row is dropped, the local service row is kept.
        $this->assertSame(1, $job->items()->count());
        $this->assertSame('Diagnostics', $job->items()->first()->name);
    }

    public function test_deduct_item_posts_service_consumption_movement(): void
    {
        $product = $this->makeProduct();
        $this->seedStock($product, '10');

        $job = $this->makeJob();
        $item = $job->items()->create([
            'item_type' => 'part',
            'name' => 'LCD',
            'product_id' => $product->id,
            'sku' => $product->sku,
            'quantity' => 2,
            'unit_price' => '20000.00',
            'subtotal' => '40000.00',
        ]);

        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/repairs/{$job->id}/items/{$item->id}/deduct");

        $response->assertSessionHas('success');
        $this->assertTrue($item->fresh()->is_deducted);

        $movement = \App\POS\Models\InventoryMovement::query()
            ->where('store_id', $this->store->id)
            ->where('product_id', $product->id)
            ->where('movement_type', 'service_consumption')
            ->first();

        $this->assertNotNull($movement);
        $this->assertSame('-2.000', (string) $movement->quantity_delta);
        $this->assertSame($job->id, (int) $movement->source_id);
        $this->assertSame('8.000', $this->inventory()->totalOnHand($this->store->id, $product->id));
    }

    public function test_deduct_item_is_idempotent(): void
    {
        $product = $this->makeProduct();
        $this->seedStock($product, '5');

        $job = $this->makeJob();
        $item = $job->items()->create([
            'item_type' => 'part',
            'name' => 'Battery',
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => '15000.00',
            'subtotal' => '15000.00',
        ]);

        $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/repairs/{$job->id}/items/{$item->id}/deduct");
        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/repairs/{$job->id}/items/{$item->id}/deduct");

        $response->assertSessionHasErrors('items');
        // Only one consumption movement was ever posted.
        $count = \App\POS\Models\InventoryMovement::query()
            ->where('movement_type', 'service_consumption')
            ->count();
        $this->assertSame(1, $count);
    }

    public function test_deduct_item_blocks_insufficient_stock(): void
    {
        $product = $this->makeProduct();
        $this->seedStock($product, '1');

        $job = $this->makeJob();
        $item = $job->items()->create([
            'item_type' => 'part',
            'name' => 'Battery',
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_price' => '15000.00',
            'subtotal' => '75000.00',
        ]);

        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/repairs/{$job->id}/items/{$item->id}/deduct");

        $response->assertSessionHasErrors('items');
        $this->assertFalse($item->fresh()->is_deducted);
        $this->assertSame('1.000', $this->inventory()->totalOnHand($this->store->id, $product->id));
    }

    public function test_update_keeps_deducted_parts_and_replaces_editable_items(): void
    {
        $product = $this->makeProduct();
        $this->seedStock($product, '5');

        $job = $this->makeJob();
        $part = $job->items()->create([
            'item_type' => 'part',
            'name' => 'LCD',
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => '20000.00',
            'subtotal' => '20000.00',
        ]);
        $job->items()->create([
            'item_type' => 'service',
            'name' => 'Diagnostics',
            'quantity' => 1,
            'unit_price' => '5000.00',
            'subtotal' => '5000.00',
        ]);

        // Consume the part first.
        $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/repairs/{$job->id}/items/{$part->id}/deduct");

        // Update the job with a fresh set of editable lines.
        $this->actingAs($this->manager)
            ->put("/store/{$this->store->slug}/admin/repairs/{$job->id}", [
                'contact_name' => 'Walk In',
                'contact_phone' => '09999999999',
                'device_type' => 'Phone',
                'model' => 'iPhone 13',
                'reported_problem' => 'Screen cracked',
                'items' => [
                    ['item_type' => 'service', 'name' => 'Full service', 'quantity' => '1', 'unit_price' => '10000', 'product_id' => ''],
                ],
            ]);

        $job->refresh();
        // The consumed part is preserved; the editable lines were replaced.
        $this->assertSame(2, $job->items()->count());
        $this->assertTrue($job->items()->where('is_deducted', true)->first()->name === 'LCD');
        $this->assertSame('Full service', $job->items()->where('is_deducted', false)->first()->name);
    }

    public function test_print_ticket_renders(): void
    {
        $job = $this->makeJob(['model' => 'PrintMe']);
        $job->items()->create([
            'item_type' => 'service',
            'name' => 'Battery replacement',
            'quantity' => 1,
            'unit_price' => '12000.00',
            'subtotal' => '12000.00',
        ]);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/repairs/{$job->id}/print");

        $response->assertStatus(200);
        $response->assertSeeText($job->job_number);
        $response->assertSeeText('Battery replacement');
    }

    public function test_quick_add_technician(): void
    {
        $response = $this->actingAs($this->manager)
            ->postJson("/store/{$this->store->slug}/admin/repairs/quick-add-technician", [
                'name' => 'Ko Kyaw Technician',
                'phone' => '09988776655',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'technician' => [
                'name' => 'Ko Kyaw Technician',
                'phone' => '09988776655',
            ],
        ]);

        $user = User::where('phone', '09988776655')->first();
        $this->assertNotNull($user);
        $this->assertTrue($this->store->users()->where('users.id', $user->id)->exists());
    }

    public function test_service_report_view_renders_200_with_metrics_and_jobs(): void
    {
        $this->makeJob(['contact_name' => 'Ko Kyaw', 'status' => 'delivered', 'final_charge' => '65000.00']);

        $response = $this->actingAs($this->manager)->get(route('pos.reports.services', [
            'store_slug' => $this->store->slug,
            'preset' => 'this_month',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Ko Kyaw');
        $response->assertSee('65,000');
    }

    public function test_service_report_export_csv(): void
    {
        $this->makeJob(['contact_name' => 'Ma Hla', 'status' => 'delivered', 'final_charge' => '30000.00']);

        $response = $this->actingAs($this->manager)->get(route('pos.reports.services.export', [
            'store_slug' => $this->store->slug,
            'preset' => 'this_month',
            'format' => 'csv',
        ]));

        $response->assertStatus(200);
        $this->assertTrue($response->headers->contains('content-type', 'text/csv; charset=UTF-8'));
    }

    public function test_service_report_export_xlsx(): void
    {
        $this->makeJob(['contact_name' => 'U Ba', 'status' => 'ready', 'final_charge' => '40000.00']);

        $response = $this->actingAs($this->manager)->get(route('pos.reports.services.export', [
            'store_slug' => $this->store->slug,
            'preset' => 'this_month',
            'format' => 'xlsx',
        ]));

        $response->assertStatus(200);
        $this->assertTrue($response->headers->contains('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'));
    }

    private function makeProduct(array $overrides = []): Product
    {
        $name = $overrides['name'] ?? 'Part ' . \Illuminate\Support\Str::random(3);

        return Product::create(array_merge([
            'store_id' => $this->store->id,
            'sku' => strtoupper(\Illuminate\Support\Str::random(8)),
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name . '-' . \Illuminate\Support\Str::random(3)),
            'retail_price' => 10000,
            'wholesale_price' => 9000,
        ], $overrides));
    }

    private function seedStock(Product $product, string $qty): void
    {
        $this->inventory()->postMovement([
            'store_id' => $this->store->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => $qty,
            'unit_cost' => '8000',
            'source_type' => 'opening_balance',
            'client_transaction_id' => 'seed:' . \Illuminate\Support\Str::uuid(),
            'occurred_at' => now(),
        ]);
    }

    private function inventory(): \App\POS\Services\InventoryService
    {
        return app(\App\POS\Services\InventoryService::class);
    }
}

