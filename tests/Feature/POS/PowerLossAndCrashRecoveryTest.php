<?php

namespace Tests\Feature\POS;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\PosPayment;
use App\POS\Models\PosSale;
use App\POS\Models\PosSaleItem;
use App\POS\Services\CashierShiftService;
use App\POS\Services\DocumentSequenceService;
use App\POS\Services\InventoryService;
use App\POS\Services\PosSaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PowerLossAndCrashRecoveryTest extends TestCase
{
    use RefreshDatabase;

    private PosSaleService $sales;
    private InventoryService $inventory;
    private CashierShiftService $shifts;
    private DocumentSequenceService $sequences;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sales = app(PosSaleService::class);
        $this->inventory = app(InventoryService::class);
        $this->shifts = app(CashierShiftService::class);
        $this->sequences = app(DocumentSequenceService::class);
    }

    private function makeStore(string $slug = 'crash-recovery-store'): Store
    {
        return Store::create([
            'name' => 'Crash Recovery Store',
            'slug' => $slug,
            'is_active' => true,
            'currency' => 'MMK',
        ]);
    }

    private function staff(Store $store): User
    {
        $user = User::create([
            'name' => 'Cashier ' . Str::random(4),
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $user->stores()->attach($store->id, ['role' => 'staff', 'status' => 'active']);

        return $user;
    }

    private function makeProduct(Store $store, array $overrides = []): Product
    {
        $name = $overrides['name'] ?? 'Product ' . Str::random(4);

        return Product::create(array_merge([
            'store_id' => $store->id,
            'sku' => strtoupper(Str::random(8)),
            'name' => $name,
            'slug' => Str::slug($name . '-' . Str::random(3)),
            'retail_price' => 15000,
            'wholesale_price' => 12000,
        ], $overrides));
    }

    private function seedStock(Store $store, Product $product, string $qty = '20', string $cost = '10000'): void
    {
        $this->inventory->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => $qty,
            'unit_cost' => $cost,
            'source_type' => 'opening_balance',
            'client_transaction_id' => 'seed:' . Str::uuid(),
            'occurred_at' => now(),
        ]);
    }

    /**
     * §14.3 Power Loss & Mid-Transaction Crash:
     * When power is cut or an unhandled exception strikes mid-posting, DB::transaction()
     * must roll back 100% — zero orphaned sales, zero partial inventory deductions, zero corrupt rows.
     */
    public function test_mid_sale_power_loss_triggers_atomic_rollback(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-POWER', 'opening_cash' => 50000], $cashier);

        $product = $this->makeProduct($store, ['retail_price' => 20000]);
        $this->seedStock($store, $product, '10');

        $initialOnHand = $this->inventory->totalOnHand($store->id, $product->id);
        $this->assertSame('10.000', $initialOnHand);

        // Simulate sudden failure during posting by forcing an invalid payment configuration
        $this->sales->addToCart($store, $product->id, null, '2');

        try {
            // Payment sum mismatch triggers exception simulating mid-sale failure
            $this->sales->post($store, $this->sales->cartLines($store), [
                ['method' => 'cash', 'amount' => '10000'], // Needs 40,000; short by 30,000
            ], $cashier, $shift);

            $this->fail('Posting should have failed due to payment shortage.');
        } catch (InventoryException $e) {
            $this->assertStringContainsString('Payments do not cover the sale total', $e->getMessage());
        }

        // Verify total rollback
        $this->assertSame(0, PosSale::where('store_id', $store->id)->count());
        $this->assertSame(0, PosSaleItem::count());
        $this->assertSame(0, PosPayment::count());

        // Inventory must remain intact at exactly 10.000
        $this->assertSame('10.000', $this->inventory->totalOnHand($store->id, $product->id));
    }

    /**
     * §14.3 Shift State Persistence across Restart:
     * An open cashier shift must survive server shutdown/restart without losing opening cash or closing prematurely.
     */
    public function test_cashier_shift_state_persists_across_restart(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);

        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-RESTART', 'opening_cash' => 75000], $cashier);
        $this->assertTrue($shift->isOpen());
        $this->assertSame('75000.00', (string) $shift->opening_cash);

        // Simulate restart by clearing runtime instance caches
        $this->app->forgetInstance(CashierShiftService::class);
        $freshShifts = app(CashierShiftService::class);

        $activeShift = $freshShifts->openShiftFor($store, $cashier);
        $this->assertNotNull($activeShift);
        $this->assertSame($shift->id, $activeShift->id);
        $this->assertTrue($activeShift->isOpen());
        $this->assertSame('75000.00', (string) $activeShift->opening_cash);
    }

    /**
     * §14.3 Held Cart Resumption across Restart:
     * Held cart stored on disk survives restart, can be resumed, and posted without duplicate records.
     */
    public function test_held_cart_resumes_after_restart_without_data_corruption(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-HOLD', 'opening_cash' => 50000], $cashier);

        $product = $this->makeProduct($store, ['retail_price' => 15000]);
        $this->seedStock($store, $product, '10');

        $this->sales->addToCart($store, $product->id, null, '1');
        $heldSale = $this->sales->holdCart($store, $cashier, $shift);
        $this->assertTrue($heldSale->isHeld());
        $this->assertSame('15000.00', (string) $heldSale->total);

        // Simulate restart
        $this->app->forgetInstance(PosSaleService::class);
        $freshSales = app(PosSaleService::class);

        // Resume held sale
        $freshSales->resumeHeld($store, $heldSale, $cashier);
        $this->assertSame('resumed', $heldSale->fresh()->status);

        // Post the resumed sale
        $posted = $freshSales->post($store, $freshSales->cartLines($store), [
            ['method' => 'cash', 'amount' => '15000'],
        ], $cashier, $shift, $heldSale);

        $this->assertSame($heldSale->id, $posted->id);
        $this->assertTrue($posted->isPosted());
        $this->assertSame(1, PosSale::where('store_id', $store->id)->count());
    }

    /**
     * §6.3 & §14.3 Document Sequence Collision Prevention across Restart:
     * Sequential numbering continues consecutively without gaps or collisions after restart.
     */
    public function test_sequential_document_numbers_survive_restart_without_collision(): void
    {
        $store = $this->makeStore();
        $periodKey = Carbon::today()->format('Ymd');

        // Sequence before restart
        $num1 = $this->sequences->nextNumber($store, 'pos_sale');
        $this->assertStringEndsWith('-0001', $num1);

        // Simulate restart
        $this->app->forgetInstance(DocumentSequenceService::class);
        $freshSequences = app(DocumentSequenceService::class);

        // Sequence after restart
        $num2 = $freshSequences->nextNumber($store, 'pos_sale');
        $this->assertStringEndsWith('-0002', $num2);

        $num3 = $freshSequences->nextNumber($store, 'pos_sale');
        $this->assertStringEndsWith('-0003', $num3);
    }
}
