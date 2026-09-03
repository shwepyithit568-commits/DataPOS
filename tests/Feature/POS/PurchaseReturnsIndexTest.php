<?php

namespace Tests\Feature\POS;

use App\Models\Product;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use App\POS\Models\PurchaseOrder;
use App\POS\Models\PurchaseReturn;
use App\POS\Services\PurchaseOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PurchaseReturnsIndexTest extends TestCase
{
    use RefreshDatabase;

    private PurchaseOrderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PurchaseOrderService::class);
    }

    private function makeStore(string $slug = 'shop-returns'): Store
    {
        return Store::create(['name' => ucfirst($slug), 'slug' => $slug, 'is_active' => true]);
    }

    private function staff(Store $store): User
    {
        $user = User::create([
            'name' => 'Staff ' . Str::random(4),
            'email' => 'staff-' . Str::random(6) . '@example.com',
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('secret'),
            'role' => 'cashier',
            'is_active' => true,
        ]);
        $user->stores()->attach($store->id, ['role' => 'staff', 'status' => 'active']);
        return $user;
    }

    public function test_returns_index_renders_successfully(): void
    {
        $store = $this->makeStore();
        $staff = $this->staff($store);
        $supplier = Supplier::create(['store_id' => $store->id, 'name' => 'Apex Distributor', 'phone' => '0912345678']);
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'USB Cable',
            'slug' => 'usb-cable-' . Str::random(3),
            'sku' => 'USB-01',
            'retail_price' => 5000,
            'wholesale_price' => 4500,
            'purchase_price' => 3000,
        ]);

        $po = $this->service->create(
            $store,
            [['product_id' => $product->id, 'quantity' => 10, 'unit_cost' => 3000]],
            $supplier->id,
            'INV-001',
            'Initial Order',
            $staff
        );
        $this->service->markOrdered($po, $staff);
        $this->service->receive($po, $staff);

        $this->service->returnItems($po, [
            ['product_id' => $product->id, 'quantity' => 2, 'unit_cost' => 3000],
        ], 'Damaged box', $staff);

        $response = $this->actingAs($staff)->get(route('pos.purchases.returns', ['store_slug' => $store->slug]));
        $response->assertOk();
        $response->assertSee('Apex Distributor');
        $response->assertSee('Damaged box');
        $response->assertSee('PO:');
        $response->assertSee(route('pos.purchases.returns.export', ['store_slug' => $store->slug]));
    }

    public function test_returns_search_and_sort(): void
    {
        $store = $this->makeStore();
        $staff = $this->staff($store);
        $supplierA = Supplier::create(['store_id' => $store->id, 'name' => 'Alpha Supply', 'phone' => '0911111111']);
        $supplierB = Supplier::create(['store_id' => $store->id, 'name' => 'Beta Electronics', 'phone' => '0922222222']);
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Adapter',
            'slug' => 'adapter-' . Str::random(3),
            'sku' => 'ADP-01',
            'retail_price' => 10000,
            'wholesale_price' => 9000,
            'purchase_price' => 6000,
        ]);

        $poA = $this->service->create(
            $store,
            [['product_id' => $product->id, 'quantity' => 5, 'unit_cost' => 6000]],
            $supplierA->id,
            'INV-A',
            'Order A',
            $staff
        );
        $this->service->markOrdered($poA, $staff);
        $this->service->receive($poA, $staff);
        $this->service->returnItems($poA, [
            ['product_id' => $product->id, 'quantity' => 1, 'unit_cost' => 6000],
        ], 'Alpha return reason', $staff);

        $poB = $this->service->create(
            $store,
            [['product_id' => $product->id, 'quantity' => 5, 'unit_cost' => 6000]],
            $supplierB->id,
            'INV-B',
            'Order B',
            $staff
        );
        $this->service->markOrdered($poB, $staff);
        $this->service->receive($poB, $staff);
        $this->service->returnItems($poB, [
            ['product_id' => $product->id, 'quantity' => 1, 'unit_cost' => 6000],
        ], 'Beta return reason', $staff);

        $resSearch = $this->actingAs($staff)->get(route('pos.purchases.returns', [
            'store_slug' => $store->slug,
            'search' => 'Alpha',
        ]));
        $resSearch->assertOk();
        $resSearch->assertSee('Alpha Supply');
        $resSearch->assertDontSee('Beta Electronics');
    }

    public function test_returns_export_excel(): void
    {
        $store = $this->makeStore();
        $staff = $this->staff($store);
        $supplier = Supplier::create(['store_id' => $store->id, 'name' => 'Delta Imports', 'phone' => '0933333333']);
        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Power Bank',
            'slug' => 'power-bank-' . Str::random(3),
            'sku' => 'PB-99',
            'retail_price' => 25000,
            'wholesale_price' => 22000,
            'purchase_price' => 18000,
        ]);

        $po = $this->service->create(
            $store,
            [['product_id' => $product->id, 'quantity' => 5, 'unit_cost' => 18000]],
            $supplier->id,
            'INV-DELTA',
            'Delta PO',
            $staff
        );
        $this->service->markOrdered($po, $staff);
        $this->service->receive($po, $staff);
        $this->service->returnItems($po, [
            ['product_id' => $product->id, 'quantity' => 1, 'unit_cost' => 18000],
        ], 'Faulty battery', $staff);

        $response = $this->actingAs($staff)->get(route('pos.purchases.returns.export', ['store_slug' => $store->slug]));
        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
