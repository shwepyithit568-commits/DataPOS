<?php

namespace Tests\Feature\POS;

use App\Models\AuditLog;
use App\POS\Models\Branch;
use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Exceptions\PeriodLockedException;
use App\POS\Models\CashierShift;
use App\POS\Models\DailyClosing;
use App\POS\Models\DocumentSequence;
use App\POS\Services\BusinessReconciliationService;
use App\POS\Services\CashierShiftService;
use App\POS\Services\DailyClosingService;
use App\POS\Services\DocumentSequenceService;
use App\POS\Services\PeriodLockService;
use App\POS\Services\PosSaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class P0IntegrityControlsTest extends TestCase
{
    use RefreshDatabase;

    private PeriodLockService $periodLock;
    private DocumentSequenceService $sequenceService;
    private CashierShiftService $shiftService;
    private DailyClosingService $closingService;
    private BusinessReconciliationService $reconciliationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->periodLock = app(PeriodLockService::class);
        $this->sequenceService = app(DocumentSequenceService::class);
        $this->shiftService = app(CashierShiftService::class);
        $this->closingService = app(DailyClosingService::class);
        $this->reconciliationService = app(BusinessReconciliationService::class);
    }

    private function makeStore(string $slug = 'p0-store'): Store
    {
        $store = Store::create([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'is_active' => true,
        ]);

        Branch::create([
            'store_id' => $store->id,
            'name' => 'Main Branch',
            'code' => 'MAIN',
            'is_default' => true,
            'is_active' => true,
        ]);

        return $store;
    }

    private function makeUser(Store $store, string $role = 'staff'): User
    {
        $user = User::create([
            'name' => ucfirst($role) . ' ' . Str::random(4),
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => $role === 'owner' ? 'admin' : 'customer',
        ]);
        $user->stores()->attach($store->id, ['role' => $role, 'status' => 'active']);

        return $user;
    }

    /* ------------------------------------------------------------------ */
    /*  1. Period Lock & Backdate Prevention (§5.3)                        */
    /* ------------------------------------------------------------------ */

    public function test_approved_daily_closing_locks_period_and_prevents_backdating(): void
    {
        $store = $this->makeStore();
        $manager = $this->makeUser($store, 'store_manager');
        $cashier = $this->makeUser($store, 'staff');

        $closedDate = Carbon::yesterday();

        // 1. Create and approve a daily closing for yesterday
        $closing = $this->closingService->create(
            store: $store,
            date: $closedDate,
            counted: ['cash' => '0'],
            explanation: null,
            actor: $cashier
        );
        $this->closingService->approve($store, $closing, $manager);

        // Verify period is locked
        $this->assertTrue($this->periodLock->isDateLocked($store, $closedDate));

        // 2. Assert that asserting date lock throws PeriodLockedException
        $this->expectException(PeriodLockedException::class);
        $this->periodLock->assertDateNotLocked($store, $closedDate, 'pos_sale');
    }

    public function test_owner_can_reopen_locked_period_with_audit_trail(): void
    {
        $store = $this->makeStore();
        $owner = $this->makeUser($store, 'owner');
        $cashier = $this->makeUser($store, 'staff');
        $closedDate = Carbon::yesterday();

        $closing = $this->closingService->create(
            store: $store,
            date: $closedDate,
            counted: ['cash' => '0'],
            explanation: null,
            actor: $cashier
        );
        $this->closingService->approve($store, $closing, $owner);
        $this->assertTrue($this->periodLock->isDateLocked($store, $closedDate));

        // Reopen period
        $reopened = $this->periodLock->reopenPeriod(
            store: $store,
            date: $closedDate,
            reason: 'Audit correction required for missing late night receipt',
            user: $owner
        );

        $this->assertSame('pending', $reopened->approval_status);
        $this->assertNotNull($reopened->reopened_at);
        $this->assertSame($owner->id, $reopened->reopened_by);
        $this->assertSame('Audit correction required for missing late night receipt', $reopened->reopen_reason);

        // Date is no longer locked
        $this->assertFalse($this->periodLock->isDateLocked($store, $closedDate));

        // AuditLog entry recorded
        $log = AuditLog::query()
            ->where('store_id', $store->id)
            ->where('action', 'daily_closing.reopened')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('Audit correction required for missing late night receipt', $log->metadata['reason'] ?? null);
    }

    /* ------------------------------------------------------------------ */
    /*  2. Store-Scoped Sequential Document Numbering (§6.3)              */
    /* ------------------------------------------------------------------ */

    public function test_document_sequence_generates_consecutive_collision_free_numbers(): void
    {
        $storeA = $this->makeStore('store-seq-a');
        $storeB = $this->makeStore('store-seq-b');

        $date = Carbon::parse('2026-09-09');

        // Store A sequences
        $seqA1 = $this->sequenceService->nextNumber($storeA, 'pos_sale', $date);
        $seqA2 = $this->sequenceService->nextNumber($storeA, 'pos_sale', $date);
        $seqA3 = $this->sequenceService->nextNumber($storeA, 'pos_sale', $date);

        $this->assertSame('RCP-20260909-0001', $seqA1);
        $this->assertSame('RCP-20260909-0002', $seqA2);
        $this->assertSame('RCP-20260909-0003', $seqA3);

        // Store B has independent isolated sequence starting from 0001
        $seqB1 = $this->sequenceService->nextNumber($storeB, 'pos_sale', $date);
        $this->assertSame('RCP-20260909-0001', $seqB1);

        // Return sequence format
        $retSeq = $this->sequenceService->nextNumber($storeA, 'pos_return', $date);
        $this->assertSame('RET-20260909-0001', $retSeq);

        // Purchase Order sequence format
        $poSeq = $this->sequenceService->nextNumber($storeA, 'purchase_order', $date);
        $this->assertSame('PO-20260909-0001', $poSeq);

        // Goods Receipt sequence format
        $grnSeq = $this->sequenceService->nextNumber($storeA, 'goods_receipt', $date);
        $this->assertSame('GRN-20260909-0001', $grnSeq);

        // Inventory Adjustment sequence format
        $adjSeq = $this->sequenceService->nextNumber($storeA, 'stock_adjustment', $date);
        $this->assertSame('ADJ-20260909-0001', $adjSeq);
    }

    /* ------------------------------------------------------------------ */
    /*  3. Cash Drawer Variance Sign-Off & Approvals (§5.2 & §5.4)         */
    /* ------------------------------------------------------------------ */

    public function test_shift_close_requires_variance_reason_when_variance_exceeds_threshold(): void
    {
        $store = $this->makeStore();
        $cashier = $this->makeUser($store, 'staff');

        $shift = $this->shiftService->openShift($store, [
            'register_name' => 'POS-01',
            'opening_cash' => 50000,
        ], $cashier);

        // Cashier enters actual closing cash with variance > 5,000 MMK without reason
        $this->expectException(InventoryException::class);
        $this->expectExceptionMessage('variance reason is required');

        $this->shiftService->closeShift($shift, [
            'actual_closing_amount' => '40000.00', // expected 50000, diff -10000
        ], $cashier);
    }

    public function test_shift_close_with_variance_reason_and_signoff_succeeds_and_logs_audit(): void
    {
        $store = $this->makeStore();
        $manager = $this->makeUser($store, 'store_manager');
        $cashier = $this->makeUser($store, 'staff');

        $shift = $this->shiftService->openShift($store, [
            'register_name' => 'POS-02',
            'opening_cash' => 50000,
        ], $cashier);

        $closedShift = $this->shiftService->closeShift($shift, [
            'actual_closing_amount' => '42000.00', // expected 50000, diff -8000
            'variance_reason' => 'Cash drawer shortage due to cashier change error verified with supervisor',
            'manager_signoff_id' => $manager->id,
        ], $cashier);

        $this->assertSame('closed', $closedShift->status);
        $this->assertSame('-8000.00', (string) $closedShift->difference);
        $this->assertSame('Cash drawer shortage due to cashier change error verified with supervisor', $closedShift->variance_reason);
        $this->assertSame($manager->id, $closedShift->manager_signoff_id);
        $this->assertNotNull($closedShift->signed_off_at);

        // AuditLog verified
        $log = AuditLog::query()
            ->where('store_id', $store->id)
            ->where('action', 'cashier_shift_variance_closed')
            ->where('entity_id', $shift->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('-8000.00', $log->metadata['difference'] ?? null);
        $this->assertSame('Cash drawer shortage due to cashier change error verified with supervisor', $log->metadata['variance_reason'] ?? null);
    }

    /* ------------------------------------------------------------------ */
    /*  4. Business Reconciliation Equations (§5.2)                        */
    /* ------------------------------------------------------------------ */

    public function test_stock_reconciliation_equation_calculates_clean_balance(): void
    {
        $store = $this->makeStore();

        $recon = $this->reconciliationService->stockReconciliation($store, now()->startOfDay(), now()->endOfDay());

        $this->assertArrayHasKey('opening_stock', $recon);
        $this->assertArrayHasKey('purchases_received', $recon);
        $this->assertArrayHasKey('sales_returns', $recon);
        $this->assertArrayHasKey('positive_adjustments', $recon);
        $this->assertArrayHasKey('transfers_in', $recon);
        $this->assertArrayHasKey('pos_sales', $recon);
        $this->assertArrayHasKey('online_sales', $recon);
        $this->assertArrayHasKey('purchase_returns', $recon);
        $this->assertArrayHasKey('negative_adjustments', $recon);
        $this->assertArrayHasKey('transfers_out', $recon);
        $this->assertArrayHasKey('calculated_closing', $recon);
        $this->assertArrayHasKey('actual_ledger_balance', $recon);
        $this->assertArrayHasKey('discrepancy', $recon);
        $this->assertArrayHasKey('is_clean', $recon);

        $this->assertTrue($recon['is_clean']);
        $this->assertSame('0.000', (string) $recon['discrepancy']);
    }

    public function test_cash_reconciliation_equation_calculates_drawer_math(): void
    {
        $store = $this->makeStore();
        $cashier = $this->makeUser($store, 'staff');

        $shift = $this->shiftService->openShift($store, [
            'register_name' => 'POS-R1',
            'opening_cash' => 100000,
        ], $cashier);
        $this->shiftService->addCashEvent($shift, ['type' => 'cash_in', 'amount' => 20000], $cashier);
        $this->shiftService->addCashEvent($shift, ['type' => 'cash_out', 'amount' => 10000], $cashier);

        // Close with balanced cash: 100000 + 20000 - 10000 = 110000
        $this->shiftService->closeShift($shift, [
            'actual_closing_amount' => '110000.00',
        ], $cashier);

        $recon = $this->reconciliationService->cashReconciliation($store, now());

        $this->assertSame('100000.00', (string) $recon['opening_cash']);
        $this->assertSame('20000.00', (string) $recon['cash_in']);
        $this->assertSame('10000.00', (string) $recon['cash_out']);
        $this->assertSame('110000.00', (string) $recon['expected_closing_cash']);
        $this->assertSame('110000.00', (string) $recon['counted_cash']);
        $this->assertSame('0.00', (string) $recon['variance']);
        $this->assertFalse($recon['has_variance']);
    }

    /* ------------------------------------------------------------------ */
    /*  5. Web Route & Controller Integration                             */
    /* ------------------------------------------------------------------ */

    public function test_reconciliation_web_view_accessible_to_manager(): void
    {
        $store = $this->makeStore();
        $manager = $this->makeUser($store, 'store_manager');

        $response = $this->actingAs($manager)
            ->get(route('pos.reports.reconciliation', ['store_slug' => $store->slug]));

        $response->assertOk();
        $response->assertViewIs('pos.reports.reconciliation');
        $response->assertSeeText(__('messages.business_reconciliation'));
    }

    public function test_reopen_period_web_route(): void
    {
        $store = $this->makeStore();
        $manager = $this->makeUser($store, 'store_manager');
        $cashier = $this->makeUser($store, 'staff');
        $closedDate = Carbon::yesterday();

        $closing = $this->closingService->create(
            store: $store,
            date: $closedDate,
            counted: ['cash' => '0'],
            explanation: null,
            actor: $cashier
        );
        $this->closingService->approve($store, $closing, $manager);

        $response = $this->actingAs($manager)
            ->post(route('pos.closing.reopen', ['store_slug' => $store->slug, 'closing' => $closing->id]), [
                'reason' => 'Reopening for manager verification',
            ]);

        $response->assertRedirect();
        $this->assertFalse($this->periodLock->isDateLocked($store, $closedDate));
    }
}
