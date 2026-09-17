<?php

namespace Tests\Feature\POS;

use App\Models\Store;
use App\Models\User;
use App\POS\Models\CashierShift;
use App\POS\Models\DailyClosing;
use App\POS\Models\Expense;
use App\POS\Services\CashierShiftService;
use App\POS\Services\DailyClosingService;
use App\POS\Services\ExpenseCashAttribution;
use App\POS\Support\ExpenseIdempotency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Remaining Closing Cash-Out defects found after the 4de2cb3 pass.
 *
 * The C/F suite (ClosingCashOutAuditTest) covers the cash rules at the service
 * level. This file covers the gaps that were still open:
 *
 *  A. Browser-level retry protection — the POS modal sent no retry key at all,
 *     so a lost response or a double-click recorded the expense twice; and the
 *     server answered "saved" when the same key arrived with DIFFERENT values.
 *  B. Drawer/shift linkage — unvalidated payment_source, a 'drawer' row written
 *     with no shift when the cashier-shifts capability is off, and a silently
 *     guessed drawer when the cashier held two open shifts.
 *  C. Detail UI vs posted status — the drawer badge was decided by
 *     "has a shift id OR claims drawer", so unpaid and void rows were shown as
 *     deducted even though the ledger never deducted them.
 *  D. Closed-period / snapshot integrity — edits that rewrite a closed drawer,
 *     and an approved closing whose detail came from live (today's) rows.
 */
class ClosingCashOutRemainingDefectsTest extends TestCase
{
    use RefreshDatabase;

    private DailyClosingService $closings;
    private CashierShiftService $shifts;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-17 12:00:00');

        $this->closings = app(DailyClosingService::class);
        $this->shifts = app(CashierShiftService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                            */
    /* ------------------------------------------------------------------ */

    private function makeStore(string $slug = 'rd-store', bool $shiftsCapability = true): Store
    {
        return Store::create([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'is_active' => true,
            'business_profile' => 'retail_store',
            'operation_mode' => 'omnichannel',
            'capabilities_override' => [
                \App\Capabilities\Capability::OPERATIONS_CASHIER_SHIFTS => $shiftsCapability,
            ],
        ]);
    }

    private function user(Store $store, string $role, string $name = 'User'): User
    {
        $roleMap = ['cashier' => 'staff', 'manager' => 'store_manager', 'owner' => 'store_owner'];
        $storeRole = $roleMap[$role] ?? $role;

        $user = User::create([
            'name' => $name . ' ' . Str::random(4),
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => $storeRole === 'store_owner' ? 'admin' : 'customer',
        ]);
        $user->stores()->attach($store->id, ['role' => $storeRole, 'status' => 'active']);

        return $user;
    }

    private function rawExpense(array $overrides = []): Expense
    {
        return Expense::create(array_merge([
            'store_id' => $overrides['store_id'],
            'expense_number' => 'EXP-' . Str::random(8),
            'title' => 'Legacy row',
            'amount' => '5000.00',
            'status' => 'paid',
            'expense_date' => '2026-09-17',
            'payment_method' => 'cash',
            'payment_source' => null,
            'recorded_by' => null,
        ], $overrides));
    }

    /* ================================================================== */
    /*  A. Browser retry protection                                        */
    /* ================================================================== */

    /**
     * A01 — the POS modal must actually carry a retry key. Without one the
     * endpoint has nothing to deduplicate on, which is exactly how the same
     * cash-out got recorded twice in UAT.
     */
    public function test_a01_pos_expense_submit_sends_client_transaction_id(): void
    {
        $store = $this->makeStore('a01-store');
        $cashier = $this->user($store, 'cashier');
        $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => '100000.00'], $cashier);

        // The POS screen still renders.
        $this->actingAs($cashier)->get("/store/{$store->slug}/pos")->assertOk();

        // The modal builds its POST body in JS. Without this line the endpoint
        // has nothing to deduplicate on — which is exactly how the same cash-out
        // got recorded twice in UAT.
        $js = file_get_contents(resource_path('js/app-admin.js'));
        $this->assertStringContainsString('client_transaction_id: this.ensureExpenseKey()', $js);
        $this->assertStringContainsString('ensureExpenseKey()', $js);
        $this->assertStringContainsString('clearExpenseKey()', $js);
        $this->assertStringContainsString('startNewExpenseAfterConflict()', $js);

        // The conflict/recovery affordance is actually rendered in the modal.
        $modal = file_get_contents(resource_path('views/pos/partials/modal-quick-tools.blade.php'));
        $this->assertStringContainsString('startNewExpenseAfterConflict()', $modal);
        $this->assertStringContainsString('expenseConflict', $modal);
    }

    /**
     * A02 — the ADMIN create form must be double-submit protected too, not just
     * the POS fetch flow.
     */
    public function test_a02_admin_expense_form_carries_retry_key(): void
    {
        $store = $this->makeStore('a02-store');
        $manager = $this->user($store, 'manager');

        $page = $this->actingAs($manager)->get("/store/{$store->slug}/admin/expenses");
        $page->assertOk();
        $page->assertSee('name="client_transaction_id"', false);
        $page->assertSee('dataposExpenseKey', false);
    }

    /**
     * A03 — same key, same payload, submitted twice over HTTP: exactly one row,
     * and the second answer is an explicit replay (not a fresh write).
     */
    public function test_a03_same_key_same_payload_replays_once(): void
    {
        $store = $this->makeStore('a03-store');
        $manager = $this->user($store, 'manager');

        $payload = [
            'title' => 'Cleaning supplies',
            'amount' => '5000.00',
            'expense_date' => '2026-09-17',
            'payment_method' => 'cash',
            'payment_source' => Expense::SOURCE_SAFE,
            'client_transaction_id' => 'retry-key-a03',
        ];

        $this->actingAs($manager)->post("/store/{$store->slug}/admin/expenses", $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('expenses', 1);

        // Simulates the browser re-sending because the first response was lost.
        $this->actingAs($manager)->post("/store/{$store->slug}/admin/expenses", $payload)->assertSessionHasNoErrors();

        $this->assertDatabaseCount('expenses', 1);
    }

    /**
     * A04 — same key, DIFFERENT payload must be a conflict, not a silent
     * "saved". The operator edited the amount after a submission whose outcome
     * they never saw; answering success would leave the stored row at the old
     * amount while they believe the new one was recorded.
     */
    public function test_a04_same_key_different_payload_is_conflict_not_silent_overwrite(): void
    {
        $store = $this->makeStore('a04-store');
        $manager = $this->user($store, 'manager');

        $base = [
            'title' => 'Cleaning supplies',
            'amount' => '5000.00',
            'expense_date' => '2026-09-17',
            'payment_method' => 'cash',
            'payment_source' => Expense::SOURCE_SAFE,
            'client_transaction_id' => 'retry-key-a04',
        ];

        $this->actingAs($manager)->post("/store/{$store->slug}/admin/expenses", $base)->assertSessionHasNoErrors();

        $edited = array_merge($base, ['amount' => '9000.00']);
        $response = $this->actingAs($manager)->post("/store/{$store->slug}/admin/expenses", $edited);

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('expenses', 1);
        $this->assertSame('5000.00', Expense::first()->amount);
    }

    /**
     * A05 — a DIFFERENT key with the same values is a legitimate second
     * expense (two identical 5,000 purchases really do happen) and must not be
     * swallowed by deduplication.
     */
    public function test_a05_different_key_same_amount_creates_second_expense(): void
    {
        $store = $this->makeStore('a05-store');
        $manager = $this->user($store, 'manager');

        foreach (['key-a05-1', 'key-a05-2'] as $key) {
            $this->actingAs($manager)->post("/store/{$store->slug}/admin/expenses", [
                'title' => 'Cleaning supplies',
                'amount' => '5000.00',
                'expense_date' => '2026-09-17',
                'payment_method' => 'cash',
                'payment_source' => Expense::SOURCE_SAFE,
                'client_transaction_id' => $key,
            ])->assertSessionHasNoErrors();
        }

        $this->assertDatabaseCount('expenses', 2);
    }

    /**
     * A06 — the loser of a unique-index race must be answered from the winner's
     * row, never with a raw "UNIQUE constraint failed" / duplicate-entry error.
     * The competitor's row is inserted directly so the race is deterministic.
     */
    public function test_a06_unique_race_winner_is_replayed_instead_of_raw_db_error(): void
    {
        $store = $this->makeStore('a06-store');
        $manager = $this->user($store, 'manager');

        $payload = [
            'title' => 'Race row',
            'amount' => '5000.00',
            'expense_date' => '2026-09-17',
            'payment_method' => 'cash',
            'payment_source' => Expense::SOURCE_SAFE,
            'client_transaction_id' => 'race-key-a06',
        ];

        // Pre-seed the winning row exactly as a concurrent request would.
        $fingerprint = ExpenseIdempotency::fingerprint([
            'store_id' => $store->id,
            'title' => $payload['title'],
            'amount' => $payload['amount'],
            'expense_date' => $payload['expense_date'],
            'payment_method' => $payload['payment_method'],
            'payment_source' => $payload['payment_source'],
            'cashier_shift_id' => null,
            'recorded_by' => $manager->id,
        ]);

        $this->rawExpense([
            'store_id' => $store->id,
            'title' => $payload['title'],
            'client_transaction_id' => 'race-key-a06',
            'request_fingerprint' => $fingerprint,
            'recorded_by' => $manager->id,
        ]);

        $response = $this->actingAs($manager)->post("/store/{$store->slug}/admin/expenses", $payload);

        $response->assertSessionHasNoErrors();
        $response->assertSessionMissing('error');
        $this->assertDatabaseCount('expenses', 1);
    }

    /**
     * A07 — SQLite reports the duplicate key as code 19, MySQL as 1062. The
     * detector must accept both, or the same code path behaves differently in
     * the test suite than in production.
     */
    public function test_a07_duplicate_key_detection_covers_sqlite_and_mysql(): void
    {
        $mysqlStyle = new \Illuminate\Database\QueryException(
            'mysql',
            'insert into expenses ...',
            [],
            new \PDOException("SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '6-key' for key 'expenses_store_client_tx_unique'")
        );
        $mysqlStyle->errorInfo = ['23000', 1062, "Duplicate entry '6-key' for key 'expenses_store_client_tx_unique'"];

        $sqliteStyle = new \Illuminate\Database\QueryException(
            'sqlite',
            'insert into "expenses" ...',
            [],
            new \PDOException('SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: expenses.store_id, expenses.client_transaction_id')
        );
        $sqliteStyle->errorInfo = ['23000', 19, 'UNIQUE constraint failed: expenses.store_id, expenses.client_transaction_id'];

        $unrelated = new \Illuminate\Database\QueryException(
            'mysql',
            'insert ...',
            [],
            new \PDOException("SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax")
        );
        $unrelated->errorInfo = ['42000', 1064, 'Syntax error'];

        $this->assertTrue(ExpenseIdempotency::isDuplicateKey($mysqlStyle));
        $this->assertTrue(ExpenseIdempotency::isDuplicateKey($sqliteStyle));
        $this->assertFalse(ExpenseIdempotency::isDuplicateKey($unrelated));
    }

    /**
     * A08 — a retry that arrives AFTER the business date was closed must replay
     * the expense it already created, not fail with a period-lock error. The
     * expense exists; telling the cashier "locked" is how a duplicate gets typed
     * in by hand.
     */
    public function test_a08_retry_after_period_lock_replays_readonly(): void
    {
        $store = $this->makeStore('a08-store');
        $manager = $this->user($store, 'manager');
        $cashier = $this->user($store, 'cashier');
        $date = Carbon::parse('2026-09-17');

        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => '100000.00'], $cashier);

        $payload = [
            'title' => 'Cleaning supplies',
            'amount' => '5000.00',
            'expense_date' => $date->toDateString(),
            'payment_method' => 'cash',
            'payment_source' => Expense::SOURCE_DRAWER,
            'cashier_shift_id' => $shift->id,
            'client_transaction_id' => 'retry-key-a08',
        ];

        $this->actingAs($manager)->post("/store/{$store->slug}/admin/expenses", $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('expenses', 1);

        // Now close the day (and the shift) so the period is locked.
        $this->shifts->closeShift($shift->fresh(), ['actual_closing_amount' => '95000.00'], $cashier);
        $closing = $this->closings->create(
            store: $store,
            date: $date,
            counted: ['cash' => '95000.00', 'kpay' => '0.00', 'wavepay' => '0.00', 'cb_pay' => '0.00', 'mmqr' => '0.00'],
            explanation: null,
            actor: $cashier,
        );
        $this->closings->approve($store, $closing, $manager);

        // The lost-response retry finally arrives.
        $response = $this->actingAs($manager)->post("/store/{$store->slug}/admin/expenses", $payload);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
        $this->assertDatabaseCount('expenses', 1);
    }

    /**
     * A09 — never leak a driver/stack message to the browser. An unexpected
     * failure must produce the translated retry message.
     */
    public function test_a09_pos_expense_errors_do_not_leak_raw_driver_messages(): void
    {
        $posController = file_get_contents(app_path('POS/Http/Controllers/CashierShiftController.php'));

        // The old broad catch returned $e->getMessage() straight to the client.
        $this->assertStringNotContainsString("catch (\\Exception \$e) {\n            if (\$request->expectsJson()) {\n                return response()->json(['error' => \$e->getMessage()]", $posController);
        $this->assertStringContainsString("__('messages.expense_save_failed_retry')", $posController);
    }

    /**
     * A10 — a written expense retires its retry key BEFORE the next key is
     * issued, and a rejected submission does not retire it.
     *
     * Proven in a real browser: clearing the key from a separate DOMContentLoaded
     * handler ran AFTER Alpine had already filled the hidden input, leaving that
     * input holding a key the server had already stored. The next legitimate
     * expense then arrived as "same key, different payload" and was refused as a
     * duplicate submission.
     */
    public function test_a10_written_expense_retires_its_key_before_the_next_is_issued(): void
    {
        $store = $this->makeStore('a10-store');
        $manager = $this->user($store, 'manager');

        // Rejected submission: nothing was written, so the operator's retry is
        // still the same logical expense and the key must survive.
        $this->actingAs($manager)->post("/store/{$store->slug}/admin/expenses", [
            'title' => 'Rejected',
            'amount' => '0',
            'expense_date' => '2026-09-17',
            'payment_method' => 'cash',
            'payment_source' => Expense::SOURCE_SAFE,
            'client_transaction_id' => 'key-a10-rejected',
        ])->assertSessionHasErrors();

        $this->assertFalse(session()->has('expense_saved'));

        // Written expense: the key is spent and the index is told to retire it.
        $this->actingAs($manager)->post("/store/{$store->slug}/admin/expenses", [
            'title' => 'Accepted',
            'amount' => '5000.00',
            'expense_date' => '2026-09-17',
            'payment_method' => 'cash',
            'payment_source' => Expense::SOURCE_SAFE,
            'client_transaction_id' => 'key-a10-accepted',
        ])->assertSessionHas('expense_saved', true);

        $page = $this->actingAs($manager)->get("/store/{$store->slug}/admin/expenses");
        $page->assertOk();

        $html = $page->getContent();

        $this->assertStringContainsString("window.dataposClearExpenseKey('{$store->id}');", $html);

        // Retire must be ordered before issue inside the same expression.
        $clearAt = strpos($html, "window.dataposClearExpenseKey('{$store->id}');");
        $issueAt = strpos($html, "window.dataposExpenseKey('{$store->id}')", $clearAt);
        $this->assertNotFalse($issueAt, 'The form must still issue a key after retiring the spent one.');
        $this->assertLessThan($issueAt, $clearAt, 'The spent key must be retired before a new one is issued.');
    }

    /**
     * A11 — without a save, the index must not retire anything: the key in the
     * form is still the one protecting the expense being typed.
     */
    public function test_a11_index_without_a_save_does_not_retire_the_key(): void
    {
        $store = $this->makeStore('a11-store');
        $manager = $this->user($store, 'manager');

        $page = $this->actingAs($manager)->get("/store/{$store->slug}/admin/expenses");
        $page->assertOk();

        $this->assertStringNotContainsString(
            "window.dataposClearExpenseKey('{$store->id}');",
            $page->getContent()
        );
        $this->assertStringContainsString(
            "window.dataposExpenseKey('{$store->id}')",
            $page->getContent()
        );
    }

    /* ================================================================== */
    /*  B. Drawer / shift linkage                                          */
    /* ================================================================== */

    /**
     * B01 — an unknown payment_source string must be rejected, not silently
     * stored and then treated as "not the drawer".
     */
    public function test_b01_unknown_payment_source_is_rejected(): void
    {
        $store = $this->makeStore('b01-store');
        $manager = $this->user($store, 'manager');

        $response = $this->actingAs($manager)->post("/store/{$store->slug}/admin/expenses", [
            'title' => 'Suspicious source',
            'amount' => '5000.00',
            'expense_date' => '2026-09-17',
            'payment_method' => 'cash',
            'payment_source' => 'backdoor_drawer',
        ]);

        $response->assertSessionHasErrors('payment_source');
        $this->assertDatabaseCount('expenses', 0);
    }

    /**
     * B02 — with the cashier-shifts capability OFF there is no drawer to
     * attribute to. The POS must NOT write a 'drawer' row with a NULL shift:
     * that is an unattributable claim on a drawer, and it makes the daily
     * report's unresolved bucket look like an accountability gap that isn't
     * real.
     */
    public function test_b02_pos_cash_expense_without_shift_capability_is_not_a_drawer_claim(): void
    {
        $store = $this->makeStore('b02-store', shiftsCapability: false);
        $cashier = $this->user($store, 'cashier');

        $response = $this->actingAs($cashier)->postJson("/store/{$store->slug}/pos/expenses", [
            'title' => 'Cash purchase without shift tracking',
            'amount' => '5000.00',
            'payment_method' => 'cash',
        ]);

        $response->assertOk();

        $expense = Expense::firstOrFail();
        $this->assertNull($expense->cashier_shift_id, 'No shift can exist when the capability is off.');
        $this->assertNotSame(
            Expense::SOURCE_DRAWER,
            $expense->payment_source,
            'A cash expense must not claim the drawer when no drawer is being tracked.'
        );
        $this->assertContains($expense->payment_source, ExpenseCashAttribution::CONFIRMED_NON_DRAWER_SOURCES);

        // And the daily report must agree: nothing was taken from a drawer.
        $totals = $this->closings->expectedTotals($store, Carbon::parse('2026-09-17'));
        $this->assertSame('0.00', $totals['summary']['drawer_expenses']);
        $this->assertSame('0.00', $totals['expected']['cash']);
        $this->assertTrue($totals['summary']['is_reconciled']);
    }

    /**
     * B03 — two open shifts means the paying drawer is ambiguous. The POS must
     * refuse instead of charging "the latest" one.
     */
    public function test_b03_pos_cash_expense_with_two_open_shifts_is_refused(): void
    {
        $store = $this->makeStore('b03-store');
        $cashier = $this->user($store, 'cashier');

        $this->shifts->openShift($store, ['register_name' => 'REG-A', 'opening_cash' => '100000.00'], $cashier);
        $this->shifts->openShift($store, ['register_name' => 'REG-B', 'opening_cash' => '50000.00'], $cashier);

        $response = $this->actingAs($cashier)->postJson("/store/{$store->slug}/pos/expenses", [
            'title' => 'Ambiguous drawer',
            'amount' => '5000.00',
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('expenses', 0);
        $this->assertStringContainsString(
            __('messages.pos_expense_shift_ambiguous'),
            (string) $response->json('error')
        );
    }

    /**
     * B04 — the daily deduction for a day must equal the sum of that day's
     * shifts' own deductions. Different attribution rules between the two
     * reports is how the same expense gets counted in one and missed in the
     * other.
     */
    public function test_b04_daily_deduction_equals_sum_of_shift_deductions(): void
    {
        $store = $this->makeStore('b04-store');
        $cashier = $this->user($store, 'cashier');

        $shiftA = $this->shifts->openShift($store, ['register_name' => 'REG-A', 'opening_cash' => '100000.00'], $cashier);
        $shiftB = $this->shifts->openShift($store, ['register_name' => 'REG-B', 'opening_cash' => '200000.00'], $cashier);

        $this->rawExpense([
            'store_id' => $store->id,
            'title' => 'A supplies',
            'amount' => '5000.00',
            'payment_source' => Expense::SOURCE_DRAWER,
            'cashier_shift_id' => $shiftA->id,
            'recorded_by' => $cashier->id,
        ]);
        $this->rawExpense([
            'store_id' => $store->id,
            'title' => 'B supplies',
            'amount' => '7000.00',
            'payment_source' => Expense::SOURCE_DRAWER,
            'cashier_shift_id' => $shiftB->id,
            'recorded_by' => $cashier->id,
        ]);

        $daily = $this->closings->expectedTotals($store, Carbon::parse('2026-09-17'));
        $this->assertSame('12000.00', $daily['summary']['drawer_expenses']);

        // Close only shift A: its own maths must equal its own share.
        $closedA = $this->shifts->closeShift($shiftA->fresh(), ['actual_closing_amount' => '95000.00'], $cashier);
        $this->assertSame('95000.00', (string) $closedA->expected_closing_amount);

        // Close only shift B: likewise.
        $closedB = $this->shifts->closeShift($shiftB->fresh(), ['actual_closing_amount' => '193000.00'], $cashier);
        $this->assertSame('193000.00', (string) $closedB->expected_closing_amount);

        // Sum of the shift expectations == the daily expectation for the same scope.
        $sum = bcadd((string) $closedA->expected_closing_amount, (string) $closedB->expected_closing_amount, 2);
        $this->assertSame($daily['expected']['cash'], $sum);
    }

    /**
     * B05 — a legacy row with NO payment source is not proof that the safe paid
     * it. It must be reported as unresolved, kept out of both the drawer
     * deduction and the confirmed non-drawer bucket, and must stop the period
     * being called reconciled.
     */
    public function test_b05_null_source_is_unresolved_not_safe(): void
    {
        $store = $this->makeStore('b05-store');
        $cashier = $this->user($store, 'cashier');

        $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => '100000.00'], $cashier);

        $this->rawExpense([
            'store_id' => $store->id,
            'title' => 'Legacy unknown source',
            'amount' => '5000.00',
            'payment_source' => null,
            'cashier_shift_id' => null,
            'recorded_by' => $cashier->id,
        ]);

        $totals = $this->closings->expectedTotals($store, Carbon::parse('2026-09-17'));
        $summary = $totals['summary'];

        $this->assertSame('0.00', $summary['drawer_expenses'], 'An unproven source must never be deducted.');
        $this->assertSame('0.00', $summary['other_cash_expenses'], 'A NULL source is not proof of "safe".');
        $this->assertSame('5000.00', $summary['unresolved_cash_expenses']);
        $this->assertSame(1, $summary['unresolved_expense_count']);
        $this->assertFalse($summary['is_reconciled'], 'An unresolved cash-out blocks a balanced/final claim.');

        // Expected cash stays at the full opening: nothing was guessed out of it.
        $this->assertSame('100000.00', $totals['expected']['cash']);

        // Its own row state matches the money maths.
        $row = $totals['expenses']->firstWhere('title', 'Legacy unknown source');
        $this->assertSame(ExpenseCashAttribution::STATE_UNRESOLVED, $row->cash_out_state);
    }

    /**
     * B06 — an expense that CLAIMS the drawer but names no shift is unresolved
     * too: the claim is real, the drawer is not provable.
     */
    public function test_b06_drawer_claim_without_shift_is_unresolved(): void
    {
        $store = $this->makeStore('b06-store');
        $cashier = $this->user($store, 'cashier');

        $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => '100000.00'], $cashier);

        $this->rawExpense([
            'store_id' => $store->id,
            'title' => 'Drawer claim, no shift',
            'amount' => '5000.00',
            'payment_source' => Expense::SOURCE_DRAWER,
            'cashier_shift_id' => null,
            'recorded_by' => $cashier->id,
        ]);

        $summary = $this->closings->expectedTotals($store, Carbon::parse('2026-09-17'))['summary'];

        $this->assertSame('0.00', $summary['drawer_expenses']);
        $this->assertSame('5000.00', $summary['unresolved_cash_expenses']);
        $this->assertFalse($summary['is_reconciled']);
    }

    /**
     * B07 — deleting a deduction out of a CLOSED drawer must be refused. The
     * shift was counted against that expense.
     */
    public function test_b07_delete_of_expense_deducted_from_closed_shift_is_refused(): void
    {
        $store = $this->makeStore('b07-store');
        $manager = $this->user($store, 'manager');
        $cashier = $this->user($store, 'cashier');

        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => '100000.00'], $cashier);

        $expense = $this->rawExpense([
            'store_id' => $store->id,
            'title' => 'Drawer supplies',
            'amount' => '5000.00',
            'payment_source' => Expense::SOURCE_DRAWER,
            'cashier_shift_id' => $shift->id,
            'recorded_by' => $manager->id,
        ]);

        $this->shifts->closeShift($shift->fresh(), ['actual_closing_amount' => '95000.00'], $cashier);

        $response = $this->actingAs($manager)->delete("/store/{$store->slug}/admin/expenses/{$expense->id}");

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
    }

    /**
     * B08 — editing the amount of an expense that was deducted from a closed
     * shift must be refused; cosmetic fields stay editable.
     */
    public function test_b08_amount_edit_on_closed_shift_is_refused_but_cosmetics_allowed(): void
    {
        $store = $this->makeStore('b08-store');
        $manager = $this->user($store, 'manager');
        $cashier = $this->user($store, 'cashier');

        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => '100000.00'], $cashier);

        $expense = $this->rawExpense([
            'store_id' => $store->id,
            'title' => 'Drawer supplies',
            'amount' => '5000.00',
            'payment_source' => Expense::SOURCE_DRAWER,
            'cashier_shift_id' => $shift->id,
            'recorded_by' => $manager->id,
        ]);

        $this->shifts->closeShift($shift->fresh(), ['actual_closing_amount' => '95000.00'], $cashier);

        $base = [
            'title' => 'Drawer supplies',
            'expense_date' => '2026-09-17',
            'payment_method' => 'cash',
            'payment_source' => Expense::SOURCE_DRAWER,
            'cashier_shift_id' => $shift->id,
        ];

        // Financial change → refused, stored value untouched.
        $this->actingAs($manager)
            ->put("/store/{$store->slug}/admin/expenses/{$expense->id}", array_merge($base, ['amount' => '8000.00']))
            ->assertSessionHasErrors('amount');

        $this->assertSame('5000.00', $expense->fresh()->amount);

        // Cosmetic change → allowed.
        $this->actingAs($manager)
            ->put("/store/{$store->slug}/admin/expenses/{$expense->id}", array_merge($base, [
                'amount' => '5000.00',
                'notes' => 'Clarified note',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('Clarified note', $expense->fresh()->notes);
        $this->assertSame('5000.00', $expense->fresh()->amount);
    }

    /**
     * B09 — moving an expense onto a shift whose business date is already
     * closed must be refused: it would rewrite an approved period's drawer.
     */
    public function test_b09_cannot_link_expense_to_shift_in_closed_period(): void
    {
        $store = $this->makeStore('b09-store');
        $manager = $this->user($store, 'manager');
        $cashier = $this->user($store, 'cashier');
        $date = Carbon::parse('2026-09-17');

        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => '100000.00'], $cashier);

        // Approve the day WITHOUT closing the shift, so the only guard in play
        // is the period lock on the shift's business date.
        $closing = $this->closings->create(
            store: $store,
            date: $date,
            counted: ['cash' => '100000.00', 'kpay' => '0.00', 'wavepay' => '0.00', 'cb_pay' => '0.00', 'mmqr' => '0.00'],
            explanation: null,
            actor: $cashier,
        );
        $this->closings->approve($store, $closing, $manager);

        $response = $this->actingAs($manager)->post("/store/{$store->slug}/admin/expenses", [
            'title' => 'Late drawer charge',
            'amount' => '5000.00',
            'expense_date' => $date->toDateString(),
            'payment_method' => 'cash',
            'payment_source' => Expense::SOURCE_DRAWER,
            'cashier_shift_id' => $shift->id,
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('expenses', 0);
    }

    /* ================================================================== */
    /*  C. Detail UI vs posted status                                      */
    /* ================================================================== */

    /**
     * C01 — the drawer badge must follow the ledger, not just the presence of a
     * shift id. An UNPAID row with a shift attached was shown as "deducted"
     * while the close maths never subtracted it.
     */
    public function test_c01_unpaid_and_void_rows_are_never_marked_deducted(): void
    {
        $store = $this->makeStore('c01b-store');
        $cashier = $this->user($store, 'cashier');

        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => '100000.00'], $cashier);

        $unpaid = $this->rawExpense([
            'store_id' => $store->id,
            'title' => 'Unpaid but shift-linked',
            'amount' => '5000.00',
            'status' => 'unpaid',
            'payment_source' => Expense::SOURCE_DRAWER,
            'cashier_shift_id' => $shift->id,
            'recorded_by' => $cashier->id,
        ]);

        $void = $this->rawExpense([
            'store_id' => $store->id,
            'title' => 'Void but shift-linked',
            'amount' => '3000.00',
            'status' => 'void',
            'payment_source' => Expense::SOURCE_DRAWER,
            'cashier_shift_id' => $shift->id,
            'recorded_by' => $cashier->id,
        ]);

        $totals = $this->closings->expectedTotals($store, Carbon::parse('2026-09-17'));

        $this->assertSame('0.00', $totals['summary']['drawer_expenses']);

        $this->assertSame(ExpenseCashAttribution::STATE_UNPAID, $unpaid->fresh()->cash_out_state ?? ExpenseCashAttribution::stateFor($unpaid->fresh(), [$shift->id]));
        $this->assertSame(ExpenseCashAttribution::STATE_VOID, ExpenseCashAttribution::stateFor($void->fresh(), [$shift->id]));

        $rows = $totals['expenses']->keyBy('title');
        $this->assertSame(ExpenseCashAttribution::STATE_UNPAID, $rows['Unpaid but shift-linked']->cash_out_state);
        $this->assertSame(ExpenseCashAttribution::STATE_VOID, $rows['Void but shift-linked']->cash_out_state);
    }

    /**
     * C02 — a 'safe' expense that happens to carry a shift id (legacy shape) is
     * NOT deducted, so its row must not claim it was.
     */
    public function test_c02_non_drawer_source_with_shift_id_is_not_deducted(): void
    {
        $store = $this->makeStore('c02b-store');
        $cashier = $this->user($store, 'cashier');

        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => '100000.00'], $cashier);

        $expense = $this->rawExpense([
            'store_id' => $store->id,
            'title' => 'Safe paid, shift attached',
            'amount' => '5000.00',
            'payment_source' => Expense::SOURCE_SAFE,
            'cashier_shift_id' => $shift->id,
            'recorded_by' => $cashier->id,
        ]);

        $state = ExpenseCashAttribution::stateFor($expense->fresh(), [$shift->id]);

        $this->assertSame(ExpenseCashAttribution::STATE_NON_DRAWER, $state);
        $this->assertFalse(ExpenseCashAttribution::isDeducted($state));

        $totals = $this->closings->expectedTotals($store, Carbon::parse('2026-09-17'));
        $this->assertSame('0.00', $totals['summary']['drawer_expenses']);
        $this->assertSame('100000.00', $totals['expected']['cash']);
    }

    /**
     * C03 — the rows the UI marks "deducted" must add up to the drawer
     * subtotal. This is the invariant that keeps the badge honest.
     */
    public function test_c03_sum_of_deducted_rows_equals_drawer_subtotal(): void
    {
        $store = $this->makeStore('c03b-store');
        $cashier = $this->user($store, 'cashier');

        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => '100000.00'], $cashier);

        $this->rawExpense([
            'store_id' => $store->id,
            'title' => 'Deducted A',
            'amount' => '5000.00',
            'payment_source' => Expense::SOURCE_DRAWER,
            'cashier_shift_id' => $shift->id,
            'recorded_by' => $cashier->id,
        ]);
        $this->rawExpense([
            'store_id' => $store->id,
            'title' => 'Not deducted (safe)',
            'amount' => '2000.00',
            'payment_source' => Expense::SOURCE_SAFE,
            'cashier_shift_id' => null,
            'recorded_by' => $cashier->id,
        ]);
        $this->rawExpense([
            'store_id' => $store->id,
            'title' => 'Not deducted (unpaid)',
            'amount' => '4000.00',
            'status' => 'unpaid',
            'payment_source' => Expense::SOURCE_DRAWER,
            'cashier_shift_id' => $shift->id,
            'recorded_by' => $cashier->id,
        ]);
        $this->rawExpense([
            'store_id' => $store->id,
            'title' => 'Not deducted (unresolved)',
            'amount' => '7000.00',
            'payment_source' => null,
            'cashier_shift_id' => null,
            'recorded_by' => $cashier->id,
        ]);

        $totals = $this->closings->expectedTotals($store, Carbon::parse('2026-09-17'));

        $markedTotal = '0.00';
        foreach ($totals['expenses'] as $row) {
            if (ExpenseCashAttribution::isDeducted($row->cash_out_state)) {
                $markedTotal = bcadd($markedTotal, (string) $row->amount, 2);
            }
        }

        $this->assertSame('5000.00', $markedTotal);
        $this->assertSame($totals['summary']['drawer_expenses'], $markedTotal);
    }

    /**
     * C04 — every state the detail table can render must have a real label in
     * all three languages. A missing key renders as the raw key name on screen.
     */
    public function test_c04_all_expense_states_have_tri_lingual_labels(): void
    {
        $states = [
            ExpenseCashAttribution::STATE_DEDUCTED,
            ExpenseCashAttribution::STATE_UNPAID,
            ExpenseCashAttribution::STATE_VOID,
            ExpenseCashAttribution::STATE_NON_DRAWER,
            ExpenseCashAttribution::STATE_UNRESOLVED,
            ExpenseCashAttribution::STATE_NON_CASH,
        ];

        foreach (['my', 'en', 'zh_CN'] as $locale) {
            $messages = require base_path("lang/{$locale}/messages.php");

            foreach ($states as $state) {
                app()->setLocale($locale);
                $label = ExpenseCashAttribution::stateLabel($state);

                $this->assertNotSame('', trim($label), "Empty label for {$state} in {$locale}");
                $this->assertNotSame(
                    'messages.expense_state_' . $state,
                    $label,
                    "Missing translation key for {$state} in {$locale}"
                );
                $this->assertArrayHasKey('expense_state_' . $state, $messages, "Key missing from lang/{$locale}");
            }
        }

        app()->setLocale('en');
    }

    /**
     * C05 — the modal opener must stop propagation.
     *
     * Proven in a real browser: the detail modal carries
     * `@click.away="showExpenseModal = false"`, which Alpine registers on the
     * DOCUMENT. A click on the opener therefore reached the document after the
     * opener's own handler and immediately undid it — the modal opened and closed
     * in the same tick, so the breakdown could never be seen at all. Measured
     * event order: BTN-CAPTURE false → BTN-BUBBLE true → DOC-BUBBLE false.
     */
    public function test_c05_expense_detail_openers_stop_propagation(): void
    {
        foreach (['pos/closing.blade.php', 'pos/closing_x_report.blade.php'] as $view) {
            $src = file_get_contents(resource_path("views/{$view}"));

            // The hazard only exists where the modal is guarded by click.away.
            $this->assertStringContainsString(
                '@click.away="showExpenseModal = false"',
                $src,
                "{$view} no longer uses a click.away guard — revisit this test."
            );

            $this->assertDoesNotMatchRegularExpression(
                '/@click="showExpenseModal = true"/',
                $src,
                "{$view} opens showExpenseModal without @click.stop, so click.away closes it in the same click."
            );

            $this->assertMatchesRegularExpression(
                '/@click\.stop="showExpenseModal = true"/',
                $src,
                "{$view} must open showExpenseModal with @click.stop."
            );
        }
    }

    /* ================================================================== */
    /*  D. Closed-period / snapshot integrity                              */
    /* ================================================================== */

    /**
     * D01 — an approved closing must not silently change when its source rows
     * are edited afterwards: the totals live in the snapshot.
     */
    public function test_d01_approved_closing_totals_do_not_follow_later_edits(): void
    {
        $store = $this->makeStore('d01b-store');
        $manager = $this->user($store, 'manager');
        $cashier = $this->user($store, 'cashier');
        $date = Carbon::parse('2026-09-17');

        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => '100000.00'], $cashier);

        $expense = $this->rawExpense([
            'store_id' => $store->id,
            'title' => 'Drawer supplies',
            'amount' => '5000.00',
            'payment_source' => Expense::SOURCE_DRAWER,
            'cashier_shift_id' => $shift->id,
            'recorded_by' => $manager->id,
        ]);

        $this->shifts->closeShift($shift->fresh(), ['actual_closing_amount' => '95000.00'], $cashier);

        $closing = $this->closings->create(
            store: $store,
            date: $date,
            counted: ['cash' => '95000.00', 'kpay' => '0.00', 'wavepay' => '0.00', 'cb_pay' => '0.00', 'mmqr' => '0.00'],
            explanation: null,
            actor: $cashier,
        );
        $this->closings->approve($store, $closing, $manager);

        $approvedExpected = (string) $closing->fresh()->expected_totals['cash'];

        // A later direct data change must not move the approved document.
        $expense->forceFill(['amount' => '9000.00'])->save();

        $this->assertSame($approvedExpected, (string) $closing->fresh()->expected_totals['cash']);
        $this->assertSame('95000.00', (string) $closing->fresh()->expected_totals['cash']);
    }

    /**
     * D02 — a closing created now must persist the expense detail it was based
     * on, so an approved closing can explain itself without re-querying live
     * rows.
     */
    public function test_d02_closing_persists_frozen_expense_detail(): void
    {
        $store = $this->makeStore('d02b-store');
        $manager = $this->user($store, 'manager');
        $cashier = $this->user($store, 'cashier');
        $date = Carbon::parse('2026-09-17');

        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => '100000.00'], $cashier);

        $this->rawExpense([
            'store_id' => $store->id,
            'title' => 'Drawer supplies',
            'amount' => '5000.00',
            'payment_source' => Expense::SOURCE_DRAWER,
            'cashier_shift_id' => $shift->id,
            'recorded_by' => $manager->id,
        ]);

        $this->shifts->closeShift($shift->fresh(), ['actual_closing_amount' => '95000.00'], $cashier);

        $closing = $this->closings->create(
            store: $store,
            date: $date,
            counted: ['cash' => '95000.00', 'kpay' => '0.00', 'wavepay' => '0.00', 'cb_pay' => '0.00', 'mmqr' => '0.00'],
            explanation: null,
            actor: $cashier,
        );

        $this->assertTrue($closing->hasFrozenExpenseDetail());

        $rows = $closing->frozenExpenseRows();
        $this->assertCount(1, $rows);
        $this->assertSame('Drawer supplies', $rows[0]['title']);
        $this->assertSame('5000.00', $rows[0]['amount']);
        $this->assertSame(ExpenseCashAttribution::STATE_DEDUCTED, $rows[0]['state']);

        // The frozen deduction matches the frozen total.
        $frozenDeducted = '0.00';
        foreach ($rows as $row) {
            if ($row['state'] === ExpenseCashAttribution::STATE_DEDUCTED) {
                $frozenDeducted = bcadd($frozenDeducted, (string) $row['amount'], 2);
            }
        }
        $this->assertSame(
            (string) $closing->getSummarySnapshot()['drawer_expenses'],
            $frozenDeducted
        );
    }

    /**
     * D03 — a legacy closing with no frozen detail must report that it has
     * none, rather than borrowing today's live rows as historical proof.
     */
    public function test_d03_legacy_closing_without_detail_reports_unavailable(): void
    {
        $store = $this->makeStore('d03b-store');
        $manager = $this->user($store, 'manager');
        $cashier = $this->user($store, 'cashier');

        $closing = DailyClosing::create([
            'store_id' => $store->id,
            'branch_id' => null,
            'business_date' => '2026-09-17',
            'closing_user_id' => $cashier->id,
            'opening_amount' => '100000.00',
            'expected_totals' => ['cash' => '95000.00'],
            'counted_totals' => ['cash' => '95000.00'],
            'differences' => ['cash' => '0.00'],
            'summary_snapshot' => ['version' => 1, 'metrics' => ['drawer_expenses' => '5000.00', 'expected_cash' => '95000.00']],
            'total_difference' => '0.00',
            'approval_status' => 'approved',
            'closed_at' => now(),
            'approved_at' => now(),
            'approver_id' => $manager->id,
            'created_by' => $cashier->id,
        ]);

        $this->assertFalse($closing->hasFrozenExpenseDetail());
        $this->assertNull($closing->frozenExpenseRows());
        $this->assertSame('5000.00', (string) $closing->getSummarySnapshot()['drawer_expenses']);

        // The page must say so rather than render live rows.
        $page = $this->actingAs($manager)->get("/store/{$store->slug}/pos/closing?date=2026-09-17");
        $page->assertOk();
        $page->assertSee(__('messages.expense_detail_unavailable'));
    }

    /**
     * D04 — a pending closing shows live detail (that IS the current truth) and
     * the detail actually rendered is the one the maths used.
     */
    public function test_d04_pending_closing_renders_live_detail_and_deduction_matches(): void
    {
        $store = $this->makeStore('d04b-store');
        $manager = $this->user($store, 'manager');
        $cashier = $this->user($store, 'cashier');
        $date = Carbon::parse('2026-09-17');

        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => '100000.00'], $cashier);

        $this->rawExpense([
            'store_id' => $store->id,
            'title' => 'Visible drawer expense',
            'amount' => '5000.00',
            'payment_source' => Expense::SOURCE_DRAWER,
            'cashier_shift_id' => $shift->id,
            'recorded_by' => $manager->id,
        ]);

        $closing = $this->closings->create(
            store: $store,
            date: $date,
            counted: ['cash' => '95000.00', 'kpay' => '0.00', 'wavepay' => '0.00', 'cb_pay' => '0.00', 'mmqr' => '0.00'],
            explanation: null,
            actor: $cashier,
        );

        $this->assertTrue($closing->isPending());

        $page = $this->actingAs($manager)->get("/store/{$store->slug}/pos/closing?date=2026-09-17");
        $page->assertOk();
        $page->assertSee('Visible drawer expense');
        $page->assertSee(__('messages.expense_state_deducted'));
    }

    /**
     * D05 — a cashier who cannot reach the closing screen must not be able to
     * mutate expenses through it either, and the store boundary holds.
     */
    public function test_d05_cross_store_expense_mutation_is_blocked(): void
    {
        $storeA = $this->makeStore('d05a-store');
        $storeB = $this->makeStore('d05b-store');
        $managerB = $this->user($storeB, 'manager');

        $expenseA = $this->rawExpense([
            'store_id' => $storeA->id,
            'title' => 'Store A expense',
            'amount' => '5000.00',
            'payment_source' => Expense::SOURCE_SAFE,
        ]);

        $this->actingAs($managerB)
            ->delete("/store/{$storeB->slug}/admin/expenses/{$expenseA->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('expenses', ['id' => $expenseA->id]);
    }

    /**
     * D06 — the shift row must be locked while an expense is being written, so
     * a concurrent shift close cannot finalise a drawer that is about to be
     * charged. Behavioural proof: an expense written against a shift that was
     * closed in the same instant is refused, not silently attached.
     */
    public function test_d06_expense_cannot_be_written_to_a_shift_closed_midflight(): void
    {
        $store = $this->makeStore('d06b-store');
        $manager = $this->user($store, 'manager');
        $cashier = $this->user($store, 'cashier');

        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => '100000.00'], $cashier);

        // Close the shift first, then attempt a drawer expense against it.
        $this->shifts->closeShift($shift->fresh(), ['actual_closing_amount' => '100000.00'], $cashier);

        $response = $this->actingAs($manager)->post("/store/{$store->slug}/admin/expenses", [
            'title' => 'Too late',
            'amount' => '5000.00',
            'expense_date' => '2026-09-17',
            'payment_method' => 'cash',
            'payment_source' => Expense::SOURCE_DRAWER,
            'cashier_shift_id' => $shift->id,
        ]);

        $response->assertSessionHasErrors('cashier_shift_id');
        $this->assertDatabaseCount('expenses', 0);

        // The closed drawer's maths is unchanged.
        $this->assertSame('100000.00', (string) $shift->fresh()->expected_closing_amount);
    }

    /**
     * D07 — the closing page and the shift close must agree on the same scope
     * for the same drawer. If they disagree, one of them is showing a number
     * the other never calculated.
     */
    public function test_d07_daily_page_and_shift_close_agree_on_drawer_total(): void
    {
        $store = $this->makeStore('d07b-store');
        $manager = $this->user($store, 'manager');
        $cashier = $this->user($store, 'cashier');
        $date = Carbon::parse('2026-09-17');

        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-1', 'opening_cash' => '100000.00'], $cashier);

        $this->rawExpense([
            'store_id' => $store->id,
            'title' => 'Drawer supplies',
            'amount' => '5000.00',
            'payment_source' => Expense::SOURCE_DRAWER,
            'cashier_shift_id' => $shift->id,
            'recorded_by' => $manager->id,
        ]);

        $dailyBefore = $this->closings->expectedTotals($store, $date);

        $closed = $this->shifts->closeShift($shift->fresh(), ['actual_closing_amount' => '95000.00'], $cashier);

        $this->assertSame($dailyBefore['expected']['cash'], (string) $closed->expected_closing_amount);

        $page = $this->actingAs($manager)->get("/store/{$store->slug}/pos/closing?date={$date->toDateString()}");
        $page->assertOk();
        $page->assertSee('Drawer supplies');
    }
}
