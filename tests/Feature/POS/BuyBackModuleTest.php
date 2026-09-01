<?php

namespace Tests\Feature\POS;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\BuyBack;
use App\POS\Models\Warehouse;
use App\POS\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BuyBackModuleTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $inventory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventory = app(InventoryService::class);
    }

    private function makeStore(string $slug = 'buyback-shop'): Store
    {
        return Store::create(['name' => 'BuyBack Shop', 'slug' => $slug, 'is_active' => true]);
    }

    private function staff(Store $store, string $role = 'staff'): User
    {
        $user = User::create([
            'name' => 'Cashier ' . Str::random(4),
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $user->stores()->attach($store->id, ['role' => $role, 'status' => 'active']);

        return $user;
    }

    private function makeProduct(Store $store, int $price = 50000): Product
    {
        $name = 'Used Phone ' . Str::random(3);

        return Product::create([
            'store_id' => $store->id,
            'sku' => strtoupper(Str::random(8)),
            'name' => $name,
            'slug' => Str::slug($name . '-' . Str::random(3)),
            'retail_price' => $price,
            'wholesale_price' => $price - 5000,
        ]);
    }

    public function test_manager_and_staff_can_view_buybacks_index(): void
    {
        $store = $this->makeStore();
        $manager = $this->staff($store, 'store_manager');
        $staff = $this->staff($store, 'staff');

        $resManager = $this->actingAs($manager)->get("/store/{$store->slug}/pos/buy-back");
        $resManager->assertOk();
        $resManager->assertSee(__('messages.sidebar_buy_back'));

        $resStaff = $this->actingAs($staff)->get("/store/{$store->slug}/pos/buy-back");
        $resStaff->assertOk();
    }

    public function test_buyback_creation_and_show(): void
    {
        $store = $this->makeStore();
        $manager = $this->staff($store, 'store_manager');
        $product = $this->makeProduct($store, 60000);

        $payload = [
            'reason' => 'Trade in for iPhone',
            'notes' => 'Minor scratch on back',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 45000,
                ],
            ],
        ];

        $postRes = $this->actingAs($manager)->post("/store/{$store->slug}/pos/buy-back", $payload);
        $buyback = BuyBack::where('store_id', $store->id)->first();
        $this->assertNotNull($buyback);
        $this->assertEquals(45000.0, (float) $buyback->total_value);
        $this->assertEquals('pending', $buyback->status);

        $postRes->assertRedirect("/store/{$store->slug}/pos/buy-back/{$buyback->id}");

        $showRes = $this->actingAs($manager)->get("/store/{$store->slug}/pos/buy-back/{$buyback->id}");
        $showRes->assertOk();
        $showRes->assertSee($buyback->buyback_number);
        $showRes->assertSee('45,000');
    }

    public function test_complete_buyback_restores_inventory(): void
    {
        $store = $this->makeStore();
        $manager = $this->staff($store, 'store_manager');
        $product = $this->makeProduct($store, 60000);
        $warehouse = Warehouse::create(['store_id' => $store->id, 'name' => 'Main', 'is_default' => true]);

        $buyback = BuyBack::create([
            'store_id' => $store->id,
            'buyback_number' => BuyBack::generateNumber($store->id),
            'total_value' => 40000,
            'refund_amount' => 40000,
            'status' => 'pending',
            'created_by' => $manager->id,
        ]);
        $buyback->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 20000,
        ]);

        $completeRes = $this->actingAs($manager)->post("/store/{$store->slug}/pos/buy-back/{$buyback->id}/complete");
        $completeRes->assertSessionHas('success');

        $buyback->refresh();
        $movement = \App\POS\Models\InventoryMovement::where('store_id', $store->id)
            ->where('product_id', $product->id)
            ->first();
        $this->assertNotNull($movement);
        $this->assertEquals(2.0, (float) $movement->quantity_delta);
    }

    public function test_cancel_buyback(): void
    {
        $store = $this->makeStore();
        $manager = $this->staff($store, 'store_manager');

        $buyback = BuyBack::create([
            'store_id' => $store->id,
            'buyback_number' => BuyBack::generateNumber($store->id),
            'total_value' => 30000,
            'refund_amount' => 30000,
            'status' => 'pending',
            'created_by' => $manager->id,
        ]);

        $cancelRes = $this->actingAs($manager)->post("/store/{$store->slug}/pos/buy-back/{$buyback->id}/cancel");
        $cancelRes->assertSessionHas('success');

        $buyback->refresh();
        $this->assertEquals('cancelled', $buyback->status);
    }

    public function test_buyback_export_csv_and_xlsx(): void
    {
        $store = $this->makeStore();
        $manager = $this->staff($store, 'store_manager');
        $product = $this->makeProduct($store, 50000);

        $buyback = BuyBack::create([
            'store_id' => $store->id,
            'buyback_number' => BuyBack::generateNumber($store->id),
            'total_value' => 35000,
            'refund_amount' => 35000,
            'status' => 'completed',
            'created_by' => $manager->id,
        ]);
        $buyback->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 35000,
        ]);

        // CSV export
        $csvRes = $this->actingAs($manager)->get("/store/{$store->slug}/pos/buy-back/export?format=csv");
        $csvRes->assertOk();
        $this->assertStringContainsString('.csv', (string) $csvRes->headers->get('content-disposition'));

        // XLSX export
        $xlsxRes = $this->actingAs($manager)->get("/store/{$store->slug}/pos/buy-back/export?format=xlsx");
        $xlsxRes->assertOk();
        $this->assertStringContainsString('.xlsx', (string) $xlsxRes->headers->get('content-disposition'));
    }

    public function test_cross_store_buyback_access_is_blocked(): void
    {
        $storeA = $this->makeStore('store-a-buyback');
        $storeB = $this->makeStore('store-b-buyback');
        $managerA = $this->staff($storeA, 'store_manager');
        $managerB = $this->staff($storeB, 'store_manager');

        $buybackA = BuyBack::create([
            'store_id' => $storeA->id,
            'buyback_number' => BuyBack::generateNumber($storeA->id),
            'total_value' => 50000,
            'refund_amount' => 50000,
            'status' => 'pending',
            'created_by' => $managerA->id,
        ]);

        $response = $this->actingAs($managerB)->get("/store/{$storeB->slug}/pos/buy-back/{$buybackA->id}");
        $response->assertNotFound();
    }
}
