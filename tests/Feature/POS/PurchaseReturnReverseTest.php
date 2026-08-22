<?php

namespace Tests\Feature\POS;

use App\Models\Supplier;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\InventoryBalance;
use App\POS\Models\InventoryMovement;
use App\POS\Models\PurchaseOrder;
use App\POS\Models\PurchaseReturn;
use App\POS\Services\InventoryService;
use App\POS\Services\PurchaseOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PurchaseReturnReverseTest extends TestCase
{
    use RefreshDatabase;

    private PurchaseOrderService $service;
    private InventoryService $inventory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PurchaseOrderService::class);
        $this->inventory = app(InventoryService::class);
    }

    private function makeStore(string $slug = 'shop-a'): Store
    {
        return Store::create(['name' => ucfirst($slug), 'slug' => $slug, 'is_active' => true]);
    }

    private function staff(Store $store): User
    {
        $user = User::create([
            'name' => 'Staff ' . Str::random(4),
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $user->stores()->attach($store->id, ['role' => 'staff', 'status' => 'active']);

        return $user;
    }

    private function makeProduct(Store $store, array $overrides = []): Product
    {
        $name = $overrides['name'] ?? 'Widget ' . Str::random(3);

        return Product::create(array_merge([
            'store_id' => $store->id,
            'sku' => strtoupper(Str::random(8)),
            'name' => $name,
            'slug' => Str::slug($name . '-' . Str::random(3)),
            'retail_price' => 10000,
            'wholesale_price' => 9000,
        ], $overrides));
    }

    private function makeSupplier(Store $store): Supplier
    {
        return Supplier::create([
            'store_id' => $store->id,
            'name' => 'Acme Corp',
            'phone' => '09123456789',
        ]);
    }

    /**
     * Helper: create a received PO with items, then process a return.
     * Returns ['po', 'return', 'actor', 'product'].
     */
    private function createReturnFixture(Store $store, string $poQty = '20', string $returnQty = '5'): array
    {
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);
        $supplier = $this->makeSupplier($store);

        $po = $this->service->create($store, [
            ['product_id' => $product->id, 'quantity' => $poQty, 'unit_cost' => '9000'],
        ], $supplier->id, null, null, $actor);

        $this->service->markOrdered($po, $actor);
        $this->service->receive($po->fresh(), $actor);

        $result = $this->service->returnItems(
            $po->fresh(),
            [['product_id' => $product->id, 'quantity' => $returnQty]],
            'Defective',
            $actor,
        );

        return [
            'po' => $po->fresh(),
            'return' => $result['return'],
            'actor' => $actor,
            'product' => $product,
            'supplier' => $supplier,
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  reversePurchaseReturn — core behavior                              */
    /* ------------------------------------------------------------------ */

    public function test_reverse_restores_stock(): void
    {
        $store = $this->makeStore();
        $fixture = $this->createReturnFixture($store, '20', '5');

        // After return: stock = 20 - 5 = 15
        $this->assertSame('15.000', $this->inventory->totalOnHand($store->id, $fixture['product']->id));

        // Reverse the return — stock should go back to 20.
        $this->service->reversePurchaseReturn($fixture['return'], $fixture['actor']);

        $this->assertSame('20.000', $this->inventory->totalOnHand($store->id, $fixture['product']->id));
    }

    public function test_reverse_restores_po_totals(): void
    {
        $store = $this->makeStore();
        $fixture = $this->createReturnFixture($store, '20', '5');

        $po = $fixture['po'];
        // After return: total_qty = 15, total_cost = 15 * 9000 = 135000
        $this->assertSame('15.000', (string) $po->fresh()->total_quantity);
        $this->assertSame('135000.00', (string) $po->fresh()->total_cost);

        $this->service->reversePurchaseReturn($fixture['return'], $fixture['actor']);

        $poFresh = $po->fresh();
        $this->assertSame('20.000', (string) $poFresh->total_quantity);
        $this->assertSame('180000.00', (string) $poFresh->total_cost);
        $this->assertSame('received', $poFresh->status);
    }

    public function test_reverse_restores_supplier_credit(): void
    {
        $store = $this->makeStore();
        $fixture = $this->createReturnFixture($store, '20', '5');
        $supplier = $fixture['supplier'];

        // After receive: supplier total_credit += remaining balance (180000 since paid_amount=0)
        // After return: supplier total_credit -= 45000 (5 * 9000)
        // So after return: total_credit = 180000 - 45000 = 135000
        $creditAfterReturn = (float) $supplier->fresh()->total_credit;

        $this->service->reversePurchaseReturn($fixture['return'], $fixture['actor']);

        // After reverse: total_credit should go back to 180000
        $creditAfterReverse = (float) $supplier->fresh()->total_credit;
        $this->assertSame(180000.0, $creditAfterReverse);
    }

    public function test_reverse_creates_reversal_movements(): void
    {
        $store = $this->makeStore();
        $fixture = $this->createReturnFixture($store, '20', '5');

        // There should be 1 original purchase_returned movement.
        $originalMovements = InventoryMovement::where('store_id', $store->id)
            ->where('source_type', 'purchase_return')
            ->where('source_id', $fixture['return']->id)
            ->count();
        $this->assertSame(1, $originalMovements);

        $this->service->reversePurchaseReturn($fixture['return'], $fixture['actor']);

        // Now there should be a reversal movement linked to the original.
        $reversalMovements = InventoryMovement::where('store_id', $store->id)
            ->where('movement_type', 'reversal')
            ->where('source_type', 'purchase_return_reversal')
            ->count();
        $this->assertSame(1, $reversalMovements);

        // The reversal's quantity_delta should be positive (stock added back).
        $reversal = InventoryMovement::where('movement_type', 'reversal')
            ->where('source_type', 'purchase_return_reversal')
            ->first();
        $this->assertGreaterThan(0, (float) $reversal->quantity_delta);
    }

    public function test_reverse_marks_return_as_reversed(): void
    {
        $store = $this->makeStore();
        $fixture = $this->createReturnFixture($store);

        $this->assertNull($fixture['return']->reversed_at);
        $this->assertFalse($fixture['return']->isReversed());

        $this->service->reversePurchaseReturn($fixture['return'], $fixture['actor']);

        $reversed = $fixture['return']->fresh();
        $this->assertTrue($reversed->isReversed());
        $this->assertNotNull($reversed->reversed_at);
        $this->assertSame($fixture['actor']->id, $reversed->reversed_by);
    }

    public function test_reverse_writes_audit_log(): void
    {
        $store = $this->makeStore();
        $fixture = $this->createReturnFixture($store);

        $this->service->reversePurchaseReturn($fixture['return'], $fixture['actor']);

        $this->assertDatabaseHas('audit_logs', [
            'store_id' => $store->id,
            'action' => 'purchase_return_reversed',
            'entity_type' => 'purchase_return',
            'entity_id' => $fixture['return']->id,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Rejection cases                                                    */
    /* ------------------------------------------------------------------ */

    public function test_reverse_already_reversed_is_rejected(): void
    {
        $store = $this->makeStore();
        $fixture = $this->createReturnFixture($store);

        $this->service->reversePurchaseReturn($fixture['return'], $fixture['actor']);

        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('already been reversed');
        $this->service->reversePurchaseReturn($fixture['return']->fresh(), $fixture['actor']);
    }

    /* ------------------------------------------------------------------ */
    /*  Full round-trip: return → reverse → return again                    */
    /* ------------------------------------------------------------------ */

    public function test_can_return_again_after_reverse(): void
    {
        $store = $this->makeStore();
        $fixture = $this->createReturnFixture($store, '20', '5');

        // Reverse the return.
        $this->service->reversePurchaseReturn($fixture['return'], $fixture['actor']);

        // Stock is back to 20 — can return again.
        $this->assertSame('20.000', $this->inventory->totalOnHand($store->id, $fixture['product']->id));

        $result = $this->service->returnItems(
            $fixture['po'],
            [['product_id' => $fixture['product']->id, 'quantity' => '3']],
            'Second return',
            $fixture['actor'],
        );

        $this->assertSame('17.000', $this->inventory->totalOnHand($store->id, $fixture['product']->id));
        $this->assertStringStartsWith('PR-', $result['return']->return_number);
    }

    /* ------------------------------------------------------------------ */
    /*  Partial return + partial reverse scenario                          */
    /* ------------------------------------------------------------------ */

    public function test_partial_return_reverse_does_not_affect_other_returns(): void
    {
        $store = $this->makeStore();
        $actor = $this->staff($store);
        $product = $this->makeProduct($store);
        $supplier = $this->makeSupplier($store);

        $po = $this->service->create($store, [
            ['product_id' => $product->id, 'quantity' => '20', 'unit_cost' => '9000'],
        ], $supplier->id);

        $this->service->markOrdered($po, $actor);
        $this->service->receive($po->fresh(), $actor);

        // Return 5 items.
        $return1 = $this->service->returnItems(
            $po->fresh(),
            [['product_id' => $product->id, 'quantity' => '5']],
            'First return',
            $actor,
        );

        // Return 3 more items.
        $return2 = $this->service->returnItems(
            $po->fresh(),
            [['product_id' => $product->id, 'quantity' => '3']],
            'Second return',
            $actor,
        );

        // Stock = 20 - 5 - 3 = 12
        $this->assertSame('12.000', $this->inventory->totalOnHand($store->id, $product->id));

        // Reverse only the first return (5 items).
        $this->service->reversePurchaseReturn($return1['return'], $actor);

        // Stock = 12 + 5 = 17
        $this->assertSame('17.000', $this->inventory->totalOnHand($store->id, $product->id));

        // The second return should NOT be affected.
        $return2Return = $return2['return']->fresh();
        $this->assertNull($return2Return->reversed_at, 'Second return should not be reversed');
    }
}
