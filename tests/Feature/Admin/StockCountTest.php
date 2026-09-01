<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Enums\InventoryMovementType;
use App\POS\Models\InventoryBalance;
use App\POS\Models\InventoryMovement;
use App\POS\Models\StockCount;
use App\POS\Models\StockCountLine;
use App\POS\Services\InventoryService;
use App\POS\Services\StockCountService;
use App\POS\Services\StoreLocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockCountTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $admin;
    protected Category $category;
    protected Product $product1;
    protected Product $product2;
    protected Product $product3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'DataPOS Flagship Store',
            'slug' => 'datapos-flagship',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Store Manager Ko Phyo',
            'role' => 'store_manager',
        ]);
        $this->admin->stores()->attach($this->store->id, ['role' => 'store_manager']);

        $this->category = Category::create([
            'store_id' => $this->store->id,
            'name' => 'Smartphones',
            'slug' => 'smartphones',
        ]);

        $otherCategory = Category::create([
            'store_id' => $this->store->id,
            'name' => 'Accessories',
            'slug' => 'accessories',
        ]);

        $this->product1 = Product::create([
            'store_id' => $this->store->id,
            'category_id' => $this->category->id,
            'name' => 'iPhone 15 Pro 128GB',
            'slug' => 'iphone-15-pro-128gb',
            'sku' => 'IP15P-128',
            'barcode' => '8801234567890',
            'retail_price' => 3200000,
            'wholesale_price' => 3000000,
            'cost_price' => 2800000,
        ]);

        $this->product2 = Product::create([
            'store_id' => $this->store->id,
            'category_id' => $this->category->id,
            'name' => 'Samsung Galaxy S24 Ultra',
            'slug' => 'samsung-s24-ultra',
            'sku' => 'S24U-512',
            'barcode' => '8809876543210',
            'retail_price' => 3800000,
            'wholesale_price' => 3600000,
            'cost_price' => 3400000,
        ]);

        $this->product3 = Product::create([
            'store_id' => $this->store->id,
            'category_id' => $otherCategory->id,
            'name' => 'Anker 20W Fast Charger',
            'slug' => 'anker-20w-charger',
            'sku' => 'ANK-20W',
            'barcode' => '8805555555555',
            'retail_price' => 45000,
            'wholesale_price' => 40000,
            'cost_price' => 30000,
        ]);

        // Post opening stock movements
        $inventoryService = app(InventoryService::class);
        $inventoryService->postMovement([
            'store' => $this->store,
            'product_id' => $this->product1->id,
            'movement_type' => InventoryMovementType::OpeningBalance->value,
            'quantity_delta' => 10,
            'unit_cost' => 2800000,
        ]);

        $inventoryService->postMovement([
            'store' => $this->store,
            'product_id' => $this->product2->id,
            'movement_type' => InventoryMovementType::OpeningBalance->value,
            'quantity_delta' => 5,
            'unit_cost' => 3400000,
        ]);
    }

    public function test_admin_can_access_stock_count_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('store.admin.stock_count.index', [
            'store_slug' => $this->store->slug,
        ]));

        $response->assertStatus(200);
        $response->assertSee(__('messages.stock_count_title'));
        $response->assertSee(__('messages.stock_count_new_session'));
    }

    public function test_admin_can_view_create_session_form(): void
    {
        $response = $this->actingAs($this->admin)->get(route('store.admin.stock_count.create', [
            'store_slug' => $this->store->slug,
        ]));

        $response->assertStatus(200);
        $response->assertSee(__('messages.stock_count_scope_all'));
        $response->assertSee(__('messages.stock_count_scope_category'));
        $response->assertSee('Smartphones');
    }

    public function test_admin_can_create_stock_count_session_for_all_products(): void
    {
        $response = $this->actingAs($this->admin)->post(route('store.admin.stock_count.store', [
            'store_slug' => $this->store->slug,
        ]), [
            'scope' => 'all',
            'notes' => 'August Full Inventory Audit',
        ]);

        $session = StockCount::where('store_id', $this->store->id)->first();
        $this->assertNotNull($session);
        $this->assertEquals('all', $session->scope);
        $this->assertEquals(3, $session->total_items);
        $this->assertEquals(StockCount::STATUS_IN_PROGRESS, $session->status);

        $response->assertRedirect(route('store.admin.stock_count.show', [
            'store_slug' => $this->store->slug,
            'stock_count' => $session->id,
        ]));
    }

    public function test_admin_can_create_stock_count_session_by_category(): void
    {
        $response = $this->actingAs($this->admin)->post(route('store.admin.stock_count.store', [
            'store_slug' => $this->store->slug,
        ]), [
            'scope' => 'category',
            'category_ids' => [$this->category->id],
            'notes' => 'Smartphones only count',
        ]);

        $session = StockCount::where('store_id', $this->store->id)->where('scope', 'category')->first();
        $this->assertNotNull($session);
        $this->assertEquals('category', $session->scope);
        $this->assertEquals(2, $session->total_items); // product 1 & 2 only
    }

    public function test_admin_can_update_line_count_via_ajax(): void
    {
        $service = app(StockCountService::class);
        $session = $service->createSession($this->store, ['scope' => 'all'], $this->admin);

        $line = $session->lines()->where('product_id', $this->product1->id)->first();
        $this->assertEquals(10, (float) $line->system_quantity);

        // Update counted qty to 12 (+2 surplus variance)
        $response = $this->actingAs($this->admin)->postJson(route('store.admin.stock_count.update_line', [
            'store_slug' => $this->store->slug,
            'stock_count' => $session->id,
            'line' => $line->id,
        ]), [
            'counted_quantity' => 12,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'line' => [
                'id' => $line->id,
                'counted_quantity' => 12,
                'variance_quantity' => 2,
                'is_counted' => true,
            ],
        ]);

        $line->refresh();
        $this->assertEquals(12, (float) $line->counted_quantity);
        $this->assertEquals(2, (float) $line->variance_quantity);
        $this->assertTrue($line->is_counted);

        $session->refresh();
        $this->assertEquals(1, $session->counted_items);
        $this->assertEquals(1, $session->variance_items);
        $this->assertEquals(2, (float) $session->total_variance_qty);
    }

    public function test_admin_can_bulk_update_counts(): void
    {
        $service = app(StockCountService::class);
        $session = $service->createSession($this->store, ['scope' => 'all'], $this->admin);

        $line1 = $session->lines()->where('product_id', $this->product1->id)->first(); // System: 10
        $line2 = $session->lines()->where('product_id', $this->product2->id)->first(); // System: 5

        $response = $this->actingAs($this->admin)->post(route('store.admin.stock_count.bulk_update', [
            'store_slug' => $this->store->slug,
            'stock_count' => $session->id,
        ]), [
            'lines' => [
                ['id' => $line1->id, 'counted_quantity' => 10, 'notes' => 'Accurate'],
                ['id' => $line2->id, 'counted_quantity' => 4, 'notes' => '1 missing damaged'],
            ],
        ]);

        $response->assertRedirect();

        $session->refresh();
        $this->assertEquals(2, $session->counted_items);
        $this->assertEquals(1, $session->variance_items); // 4 - 5 = -1 variance
    }

    public function test_admin_can_quick_scan_barcode_or_sku(): void
    {
        $service = app(StockCountService::class);
        $session = $service->createSession($this->store, ['scope' => 'all'], $this->admin);

        // Scan by SKU
        $response = $this->actingAs($this->admin)->getJson(route('store.admin.stock_count.quick_scan', [
            'store_slug' => $this->store->slug,
            'stock_count' => $session->id,
            'q' => 'IP15P-128',
        ]));

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertEquals('iPhone 15 Pro 128GB', $data[0]['product_name']);
        $this->assertEquals('IP15P-128', $data[0]['sku']);

        // Scan by partial name
        $responseSku = $this->actingAs($this->admin)->getJson(route('store.admin.stock_count.quick_scan', [
            'store_slug' => $this->store->slug,
            'stock_count' => $session->id,
            'q' => 'Galaxy',
        ]));

        $responseSku->assertStatus(200);
        $dataSku = $responseSku->json();
        $this->assertCount(1, $dataSku);
        $this->assertEquals('Samsung Galaxy S24 Ultra', $dataSku[0]['product_name']);
    }

    public function test_admin_can_approve_and_reconcile_stock_count_adjustments(): void
    {
        $service = app(StockCountService::class);
        $session = $service->createSession($this->store, ['scope' => 'all'], $this->admin);

        $line1 = $session->lines()->where('product_id', $this->product1->id)->first();
        $line2 = $session->lines()->where('product_id', $this->product2->id)->first();

        // Product 1 (System: 10) -> Counted: 13 (+3 surplus variance)
        $line1->setCount(13);
        // Product 2 (System: 5) -> Counted: 3 (-2 shortage variance)
        $line2->setCount(3);

        $session->recalculateStats();

        // Approve and reconcile
        $response = $this->actingAs($this->admin)->post(route('store.admin.stock_count.approve', [
            'store_slug' => $this->store->slug,
            'stock_count' => $session->id,
        ]));

        $response->assertRedirect(route('store.admin.stock_count.show', [
            'store_slug' => $this->store->slug,
            'stock_count' => $session->id,
        ]));

        $session->refresh();
        $this->assertEquals(StockCount::STATUS_APPROVED, $session->status);
        $this->assertEquals($this->admin->id, $session->approved_by);
        $this->assertNotNull($session->approved_at);

        // Verify inventory ledger movements were posted
        $adjustmentIn = InventoryMovement::where('store_id', $this->store->id)
            ->where('source_type', 'stock_count')
            ->where('source_id', $session->id)
            ->where('product_id', $this->product1->id)
            ->where('movement_type', InventoryMovementType::AdjustmentIn->value)
            ->first();

        $this->assertNotNull($adjustmentIn);
        $this->assertEquals(3.0, (float) $adjustmentIn->quantity_delta);

        $adjustmentOut = InventoryMovement::where('store_id', $this->store->id)
            ->where('source_type', 'stock_count')
            ->where('source_id', $session->id)
            ->where('product_id', $this->product2->id)
            ->where('movement_type', InventoryMovementType::AdjustmentOut->value)
            ->first();

        $this->assertNotNull($adjustmentOut);
        $this->assertEquals(-2.0, (float) $adjustmentOut->quantity_delta);

        // Verify new stock on hand reflects reconciled count
        $invService = app(InventoryService::class);
        $this->assertEquals('13.000', $invService->totalOnHand($this->store->id, $this->product1->id));
        $this->assertEquals('3.000', $invService->totalOnHand($this->store->id, $this->product2->id));
    }

    public function test_admin_can_cancel_in_progress_session(): void
    {
        $service = app(StockCountService::class);
        $session = $service->createSession($this->store, ['scope' => 'all'], $this->admin);

        $response = $this->actingAs($this->admin)->post(route('store.admin.stock_count.cancel', [
            'store_slug' => $this->store->slug,
            'stock_count' => $session->id,
        ]));

        $response->assertRedirect(route('store.admin.stock_count.index', [
            'store_slug' => $this->store->slug,
        ]));

        $session->refresh();
        $this->assertTrue($session->isCancelled());

        // Ensure no movements posted
        $movementsCount = InventoryMovement::where('source_type', 'stock_count')->where('source_id', $session->id)->count();
        $this->assertEquals(0, $movementsCount);
    }

    public function test_admin_can_view_printable_stock_count_sheet(): void
    {
        $service = app(StockCountService::class);
        $session = $service->createSession($this->store, ['scope' => 'all'], $this->admin);

        $response = $this->actingAs($this->admin)->get(route('store.admin.stock_count.print', [
            'store_slug' => $this->store->slug,
            'stock_count' => $session->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee($session->session_number);
        $response->assertSee('iPhone 15 Pro 128GB');
        $response->assertSee(__('messages.stock_count_print_title'));
    }

    public function test_unauthorized_user_cannot_access_stock_count(): void
    {
        $otherUser = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($otherUser)->get(route('store.admin.stock_count.index', [
            'store_slug' => $this->store->slug,
        ]));

        $response->assertStatus(403);
    }

    public function test_admin_can_export_stock_counts_excel_and_csv(): void
    {
        $service = app(StockCountService::class);
        $session = $service->createSession($this->store, ['scope' => 'all'], $this->admin);

        // CSV Export for index list
        $csvResponse = $this->actingAs($this->admin)->get(route('store.admin.stock_count.export', [
            'store_slug' => $this->store->slug,
            'format' => 'csv',
        ]));
        $csvResponse->assertStatus(200);
        $csvResponse->assertHeader('content-type', 'text/csv; charset=UTF-8');

        // Excel Export for index list
        $xlsxResponse = $this->actingAs($this->admin)->get(route('store.admin.stock_count.export', [
            'store_slug' => $this->store->slug,
            'format' => 'xlsx',
        ]));
        $xlsxResponse->assertStatus(200);
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', (string) $xlsxResponse->headers->get('content-type'));

        // CSV Export for single session lines detail
        $sessionCsvResponse = $this->actingAs($this->admin)->get(route('store.admin.stock_count.export_session', [
            'store_slug' => $this->store->slug,
            'stock_count' => $session->id,
            'format' => 'csv',
        ]));
        $sessionCsvResponse->assertStatus(200);
        $sessionCsvResponse->assertHeader('content-type', 'text/csv; charset=UTF-8');

        // Excel Export for single session lines detail
        $sessionXlsxResponse = $this->actingAs($this->admin)->get(route('store.admin.stock_count.export_session', [
            'store_slug' => $this->store->slug,
            'stock_count' => $session->id,
            'format' => 'xlsx',
        ]));
        $sessionXlsxResponse->assertStatus(200);
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', (string) $sessionXlsxResponse->headers->get('content-type'));
    }
}
