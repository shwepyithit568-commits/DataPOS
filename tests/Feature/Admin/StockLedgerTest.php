<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Enums\InventoryMovementType;
use App\POS\Models\InventoryMovement;
use App\POS\Services\InventoryService;
use App\POS\Services\StockLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockLedgerTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $admin;
    protected Product $product1;
    protected Product $product2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'DataPOS Mobile Center',
            'slug' => 'datapos-mobile',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Ko Min Thu',
            'role' => 'store_manager',
        ]);
        $this->admin->stores()->attach($this->store->id, ['role' => 'store_manager']);

        $category = Category::create([
            'store_id' => $this->store->id,
            'name' => 'Tablets',
            'slug' => 'tablets',
        ]);

        $this->product1 = Product::create([
            'store_id' => $this->store->id,
            'category_id' => $category->id,
            'name' => 'iPad Pro 11-inch M4',
            'slug' => 'ipad-pro-11-m4',
            'sku' => 'IPAD-M4-11',
            'retail_price' => 3100000,
            'wholesale_price' => 2900000,
            'cost_price' => 2700000,
        ]);

        $this->product2 = Product::create([
            'store_id' => $this->store->id,
            'category_id' => $category->id,
            'name' => 'Apple Pencil Pro',
            'slug' => 'apple-pencil-pro',
            'sku' => 'PENCIL-PRO',
            'retail_price' => 450000,
            'wholesale_price' => 400000,
            'cost_price' => 350000,
        ]);

        $inventoryService = app(InventoryService::class);

        // 1. Opening Balance for product 1 (+10)
        $inventoryService->postMovement([
            'store' => $this->store,
            'product_id' => $this->product1->id,
            'movement_type' => InventoryMovementType::OpeningBalance->value,
            'quantity_delta' => 10,
            'unit_cost' => 2700000,
            'occurred_at' => now()->subDays(5),
            'posted_by' => $this->admin->id,
        ]);

        // 2. POS Sale for product 1 (-2)
        $inventoryService->postMovement([
            'store' => $this->store,
            'product_id' => $this->product1->id,
            'movement_type' => InventoryMovementType::PosSale->value,
            'quantity_delta' => -2,
            'unit_cost' => 2700000,
            'source_type' => 'pos_sale',
            'source_id' => 101,
            'occurred_at' => now()->subDays(3),
            'posted_by' => $this->admin->id,
        ]);

        // 3. Purchase Received for product 1 (+5)
        $inventoryService->postMovement([
            'store' => $this->store,
            'product_id' => $this->product1->id,
            'movement_type' => InventoryMovementType::PurchaseReceived->value,
            'quantity_delta' => 5,
            'unit_cost' => 2700000,
            'source_type' => 'purchase_order',
            'source_id' => 55,
            'occurred_at' => now()->subDays(1),
            'posted_by' => $this->admin->id,
        ]);

        // 4. Opening Balance for product 2 (+20)
        $inventoryService->postMovement([
            'store' => $this->store,
            'product_id' => $this->product2->id,
            'movement_type' => InventoryMovementType::OpeningBalance->value,
            'quantity_delta' => 20,
            'unit_cost' => 350000,
            'occurred_at' => now()->subDays(4),
            'posted_by' => $this->admin->id,
        ]);
    }

    public function test_admin_can_access_stock_ledger_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('store.admin.stock_ledger.index', [
            'store_slug' => $this->store->slug,
        ]));

        $response->assertStatus(200);
        $response->assertSee(__('messages.stock_ledger_title'));
        $response->assertSee('iPad Pro 11-inch M4');
        $response->assertSee('Apple Pencil Pro');
    }

    public function test_admin_can_filter_movements_by_flow_and_type(): void
    {
        // Filter Inflow
        $responseInflow = $this->actingAs($this->admin)->get(route('store.admin.stock_ledger.index', [
            'store_slug' => $this->store->slug,
            'flow' => 'inflow',
        ]));
        $responseInflow->assertStatus(200);
        $responseInflow->assertSee('+10.000');
        $responseInflow->assertSee('+5.000');
        $responseInflow->assertDontSee('-2.000');

        // Filter Outflow
        $responseOutflow = $this->actingAs($this->admin)->get(route('store.admin.stock_ledger.index', [
            'store_slug' => $this->store->slug,
            'flow' => 'outflow',
        ]));
        $responseOutflow->assertStatus(200);
        $responseOutflow->assertSee('-2.000');
        $responseOutflow->assertDontSee('+10.000');
    }

    public function test_admin_can_search_movements_by_product_or_sku(): void
    {
        $response = $this->actingAs($this->admin)->get(route('store.admin.stock_ledger.index', [
            'store_slug' => $this->store->slug,
            'search' => 'PENCIL-PRO',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Apple Pencil Pro');
        $response->assertDontSee('iPad Pro 11-inch M4');
    }

    public function test_admin_can_view_individual_product_bin_card(): void
    {
        $response = $this->actingAs($this->admin)->get(route('store.admin.stock_ledger.bin_card', [
            'store_slug' => $this->store->slug,
            'product' => $this->product1->id,
            'preset' => 'all',
        ]));

        $response->assertStatus(200);
        $response->assertSee('iPad Pro 11-inch M4');
        $response->assertSee('IPAD-M4-11');
        $response->assertSee('13.000'); // 10 - 2 + 5 = 13 running on-hand

        // Check bin card computation via service directly
        $service = app(StockLedgerService::class);
        $data = $service->getProductBinCard($this->store, $this->product1);

        $this->assertEquals(13.0, $data['closing_balance']);
        $this->assertEquals(15.0, $data['total_in']); // 10 + 5
        $this->assertEquals(2.0, $data['total_out']); // 2
        $this->assertEquals(13.0, $data['current_on_hand']);

        // Check running balances on chronological timeline
        $chronological = $data['timeline_chronological'];
        $this->assertCount(3, $chronological);
        $this->assertEquals(10.0, $chronological[0]['running_balance']); // After opening +10
        $this->assertEquals(8.0, $chronological[1]['running_balance']);  // After sale -2
        $this->assertEquals(13.0, $chronological[2]['running_balance']); // After purchase +5
    }

    public function test_admin_can_export_movements_csv(): void
    {
        $response = $this->actingAs($this->admin)->get(route('store.admin.stock_ledger.export', [
            'store_slug' => $this->store->slug,
        ]));

        $response->assertStatus(200);
        $this->assertEquals('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));
    }

    public function test_admin_can_view_printable_bin_card(): void
    {
        $response = $this->actingAs($this->admin)->get(route('store.admin.stock_ledger.print_bin_card', [
            'store_slug' => $this->store->slug,
            'product' => $this->product1->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('iPad Pro 11-inch M4');
        $response->assertSee(__('messages.stock_ledger_bin_card_title'));
    }

    public function test_unauthorized_user_cannot_access_stock_ledger(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer)->get(route('store.admin.stock_ledger.index', [
            'store_slug' => $this->store->slug,
        ]));

        $response->assertStatus(403);
    }
}
