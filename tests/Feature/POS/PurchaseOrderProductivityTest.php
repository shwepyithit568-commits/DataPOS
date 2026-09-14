<?php

namespace Tests\Feature\POS;

use App\Models\Product;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use App\POS\Models\PurchaseOrder;
use App\POS\Models\PurchaseReturn;
use App\POS\Models\PurchaseReturnItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PurchaseOrderProductivityTest extends TestCase
{
    use RefreshDatabase;

    private function makeStore(string $slug = 'test-store'): Store
    {
        return Store::create([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'is_active' => true,
        ]);
    }

    private function makeUser(Store $store, string $role = 'store_owner'): User
    {
        $user = User::create([
            'name' => 'User ' . Str::random(4),
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => $role,
        ]);
        $user->stores()->attach($store->id, ['role' => $role, 'status' => 'active']);

        return $user;
    }

    public function test_po_create_page_renders_with_scanner_and_voucher_check_elements(): void
    {
        $store = $this->makeStore();
        $user = $this->makeUser($store);

        $response = $this->actingAs($user)->get("/store/{$store->slug}/pos/purchases/create");

        $response->assertOk();
        $response->assertSee('id="po-voucher-check"', false);
        $response->assertSee('openCameraScanner()', false);
        $response->assertSee('id="po-camera-scanner-viewport"', false);
        $response->assertSee('F2 / Ctrl+S', false);
        $response->assertSee('voucherMatchStatus', false);
    }

    public function test_po_edit_page_renders_with_scanner_and_voucher_check_elements(): void
    {
        $store = $this->makeStore();
        $user = $this->makeUser($store);

        $supplier = Supplier::create([
            'store_id' => $store->id,
            'name' => 'Test Supplier',
        ]);

        $po = PurchaseOrder::create([
            'store_id' => $store->id,
            'supplier_id' => $supplier->id,
            'po_number' => 'PO-' . strtoupper(Str::random(6)),
            'status' => 'pending',
            'subtotal' => 50000,
            'net_total' => 50000,
            'paid_amount' => 0,
            'payment_status' => 'unpaid',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get("/store/{$store->slug}/pos/purchases/{$po->id}/edit");

        $response->assertOk();
        $response->assertSee('id="po-voucher-check"', false);
        $response->assertSee('openCameraScanner()', false);
        $response->assertSee('id="po-camera-scanner-viewport"', false);
        $response->assertSee('F2 / Ctrl+S', false);
        $response->assertSee('voucherMatchStatus', false);
    }

    public function test_product_search_by_barcode_returns_matched_product_for_scanner(): void
    {
        $store = $this->makeStore();
        $user = $this->makeUser($store);

        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Scanner Test Mouse',
            'slug' => 'scanner-test-mouse-' . Str::random(4),
            'sku' => 'MOUSE-BARCODE-99',
            'barcode' => '8801234567890',
            'cost_price' => 15000,
            'retail_price' => 22000,
            'wholesale_price' => 18000,
            'status' => 'active',
        ]);

        // Search by exact barcode (simulating camera barcode scanner result)
        $response = $this->actingAs($user)
            ->getJson("/store/{$store->slug}/pos/purchases/products?q=" . urlencode('8801234567890'));

        $response->assertOk();
        $results = $response->json('results');
        $this->assertNotEmpty($results);
        $this->assertEquals($product->id, $results[0]['product_id']);
        $this->assertEquals('Scanner Test Mouse', $results[0]['name']);
    }

    public function test_po_creation_succeeds_with_line_items_and_discount(): void
    {
        $store = $this->makeStore();
        $user = $this->makeUser($store);

        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Test Keyboard',
            'slug' => 'test-keyboard-' . Str::random(4),
            'sku' => 'KB-TEST-01',
            'cost_price' => 20000,
            'retail_price' => 30000,
            'wholesale_price' => 25000,
            'status' => 'active',
        ]);

        $payload = [
            'reference' => 'REF-VOUCHER-TEST-1',
            'notes' => 'Productivity test purchase',
            'discount_amount' => 2000,
            'delivery_fee' => 1000,
            'payment_status' => 'unpaid',
            'paid_amount' => 0,
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_variant_id' => null,
                    'quantity' => 5,
                    'unit_cost' => 20000,
                ],
            ],
        ];

        $response = $this->actingAs($user)
            ->post("/store/{$store->slug}/pos/purchases", $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('purchase_orders', [
            'store_id' => $store->id,
            'reference' => 'REF-VOUCHER-TEST-1',
            'subtotal' => 100000, // 5 * 20000
            'discount_amount' => 2000,
            'delivery_fee' => 1000,
            'total_cost' => 99000, // 100000 - 2000 + 1000
        ]);
    }

    public function test_purchase_return_detail_endpoint_returns_json_with_items(): void
    {
        $store = $this->makeStore();
        $user = $this->makeUser($store);

        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Returned Monitor',
            'slug' => 'returned-monitor-' . Str::random(4),
            'sku' => 'MON-RET-01',
            'cost_price' => 50000,
            'retail_price' => 70000,
            'wholesale_price' => 60000,
            'status' => 'active',
        ]);

        $supplier = Supplier::create([
            'store_id' => $store->id,
            'name' => 'Samsung Supplier',
        ]);

        $po = PurchaseOrder::create([
            'store_id' => $store->id,
            'supplier_id' => $supplier->id,
            'po_number' => 'PO-RET-001',
            'status' => 'received',
            'subtotal' => 100000,
            'total_cost' => 100000,
            'created_by' => $user->id,
        ]);

        $return = PurchaseReturn::create([
            'store_id' => $store->id,
            'purchase_order_id' => $po->id,
            'supplier_id' => $supplier->id,
            'return_number' => 'PR-202609-001',
            'total_quantity' => 2,
            'total_cost' => 100000,
            'reason' => 'Dead pixels on screen',
            'created_by' => $user->id,
            'returned_at' => now(),
        ]);

        PurchaseReturnItem::create([
            'purchase_return_id' => $return->id,
            'store_id' => $store->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_cost' => 50000,
            'line_total' => 100000,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/store/{$store->slug}/pos/purchases/returns/{$return->id}");

        $response->assertOk();
        $json = $response->json();
        $this->assertEquals('PR-202609-001', $json['return_number']);
        $this->assertEquals('Dead pixels on screen', $json['reason']);
        $this->assertEquals(2, $json['total_quantity']);
        $this->assertEquals(100000, $json['total_cost']);
        $this->assertNotEmpty($json['items']);
        $this->assertEquals('Returned Monitor', $json['items'][0]['name']);
        $this->assertStringContainsString('/print', $json['print_url']);
    }

    public function test_purchase_return_print_view_renders_correctly(): void
    {
        $store = $this->makeStore();
        $user = $this->makeUser($store);

        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Printer Cable',
            'slug' => 'printer-cable-' . Str::random(4),
            'sku' => 'CABLE-PRN-01',
            'cost_price' => 5000,
            'retail_price' => 8000,
            'wholesale_price' => 6000,
            'status' => 'active',
        ]);

        $po = PurchaseOrder::create([
            'store_id' => $store->id,
            'po_number' => 'PO-PRINT-99',
            'status' => 'received',
            'subtotal' => 25000,
            'total_cost' => 25000,
            'created_by' => $user->id,
        ]);

        $return = PurchaseReturn::create([
            'store_id' => $store->id,
            'purchase_order_id' => $po->id,
            'return_number' => 'PR-PRINT-TEST',
            'total_quantity' => 5,
            'total_cost' => 25000,
            'reason' => 'Wrong cable length',
            'created_by' => $user->id,
            'returned_at' => now(),
        ]);

        PurchaseReturnItem::create([
            'purchase_return_id' => $return->id,
            'store_id' => $store->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_cost' => 5000,
            'line_total' => 25000,
        ]);

        $response = $this->actingAs($user)
            ->get("/store/{$store->slug}/pos/purchases/returns/{$return->id}/print");

        $response->assertOk();
        $response->assertSee('PR-PRINT-TEST');
        $response->assertSee('PO-PRINT-99');
        $response->assertSee('Printer Cable');
        $response->assertSee('Wrong cable length');
        $response->assertSee('btn-80mm', false);
        $response->assertSee('btn-58mm', false);
        $response->assertSee('btn-a4', false);
    }

    public function test_cross_store_isolation_for_purchase_return(): void
    {
        $storeA = $this->makeStore('store-alpha');
        $storeB = $this->makeStore('store-beta');
        $userA = $this->makeUser($storeA);

        $poB = PurchaseOrder::create([
            'store_id' => $storeB->id,
            'po_number' => 'PO-BETA-01',
            'status' => 'received',
            'subtotal' => 10000,
            'total_cost' => 10000,
            'created_by' => $userA->id,
        ]);

        $returnB = PurchaseReturn::create([
            'store_id' => $storeB->id,
            'purchase_order_id' => $poB->id,
            'return_number' => 'PR-BETA-01',
            'total_quantity' => 1,
            'total_cost' => 10000,
            'created_by' => $userA->id,
        ]);

        // Attempting to access Store B's return while in Store A context
        $response = $this->actingAs($userA)
            ->getJson("/store/{$storeA->slug}/pos/purchases/returns/{$returnB->id}");

        $response->assertNotFound();
    }
}
