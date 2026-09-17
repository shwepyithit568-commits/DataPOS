<?php

namespace App\POS\Http\Controllers;

use App\Capabilities\Capability;
use App\Http\Controllers\Controller;
use App\Models\Store;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\CashierShift;
use App\POS\Models\Expense;
use App\POS\Models\ExpenseCategory;
use App\POS\Models\ServiceJob;
use App\POS\Services\CashierShiftService;
use App\POS\Services\CustomerDebtService;
use App\POS\Services\PosSaleService;
use App\POS\Support\ExpenseIdempotency;
use App\Services\StoreContext;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * First /pos module — cashier shifts + opening cash (target-design §2.10).
 *
 * Routes are statically registered under /store/{store_slug}/pos with
 * ResolveStoreContext + EnsureStoreAccess (store_manager/staff) — the backend
 * authorization stays authoritative; the shift id is always re-validated
 * against the resolved store to block cross-store tampering.
 */
class CashierShiftController extends Controller
{
    public function __construct(
        protected CashierShiftService $shifts,
        protected PosSaleService $sales,
        protected CustomerDebtService $debts,
    ) {
    }

    public function index(StoreContext $context): View
    {
        $store = $context->getStore();
        $user = auth()->user();

        $shiftsEnabled = $store->hasCapability(Capability::OPERATIONS_CASHIER_SHIFTS);

        // Only the cashier's OWN open shift is shown when shift tracking is enabled.
        $openShift = $shiftsEnabled ? $this->shifts->openShiftFor($store, $user) : null;

        // Open shifts held by OTHER cashiers — surfaced so the page can say
        // "Register X is in use" instead of a silent "no shift" + rejection.
        $occupiedRegisters = $shiftsEnabled
            ? CashierShift::query()
                ->with('cashier')
                ->where('store_id', $store->id)
                ->where('status', 'open')
                ->when($openShift, fn ($q) => $q->where('id', '!=', $openShift->id))
                ->orderBy('register_name')
                ->get()
            : collect();

        $summary = $shiftsEnabled
            ? $this->shifts->dailySummary($store, now())
            : [
                'shifts' => collect(),
                'shift_count' => 0,
                'opening_cash' => '0.00',
                'cash_sales' => '0.00',
                'cash_refunds' => '0.00',
                'cash_in' => '0.00',
                'cash_out' => '0.00',
                'expected' => '0.00',
                'actual' => '0.00',
                'difference' => '0.00',
            ];

        $cart = $this->sales->cartResolved($store);
        $cartTotals = $this->sales->cartTotals($store);
        $todaySales = $this->sales->todaySales($store);
        $outstanding = $this->debts->outstandingCustomers($store);
        $outstandingTotal = array_reduce($outstanding, fn ($carry, $c) => bcadd($carry, $c['balance'], 2), '0');

        $expenseCategories = ExpenseCategory::query()
            ->where('store_id', $store->id)
            ->active()
            ->ordered()
            ->get(['id', 'name', 'code']);

        if ($expenseCategories->isEmpty()) {
            $defaultCats = \Database\Seeders\ExpenseCategorySeeder::DEFAULT_CATEGORIES;
            foreach ($defaultCats as $cat) {
                ExpenseCategory::firstOrCreate(
                    ['store_id' => $store->id, 'code' => $cat['code']],
                    [
                        'name' => $cat['name'],
                        'description' => $cat['description'],
                        'color' => $cat['color'],
                        'sort_order' => $cat['sort_order'],
                        'is_active' => $cat['is_active'],
                    ]
                );
            }
            $expenseCategories = ExpenseCategory::query()
                ->where('store_id', $store->id)
                ->active()
                ->ordered()
                ->get(['id', 'name', 'code']);
        }

        $serviceEnabled = $store->hasCapability(Capability::SERVICE_REPAIR_JOBS);
        $recentRepairs = $serviceEnabled
            ? ServiceJob::query()
                ->where('store_id', $store->id)
                ->with(['customer', 'technician', 'payments'])
                ->latest('id')
                ->take(25)
                ->get()
            : collect();
        $activeRepairsCount = $serviceEnabled
            ? ServiceJob::where('store_id', $store->id)
                ->whereNotIn('status', ['delivered', 'cancelled', 'unrepairable'])
                ->count()
            : 0;
        $readyRepairsCount = $serviceEnabled
            ? ServiceJob::where('store_id', $store->id)
                ->where('status', 'ready')
                ->count()
            : 0;

        return view('pos.index', compact(
            'store', 'openShift', 'occupiedRegisters', 'summary', 'cart', 'cartTotals',
            'todaySales', 'outstanding', 'outstandingTotal', 'expenseCategories',
            'recentRepairs', 'activeRepairsCount', 'readyRepairsCount'
        ));
    }

    public function recordExpense(Request $request, StoreContext $context): JsonResponse|RedirectResponse
    {
        $store = $context->getStore();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'decimal:0,2', 'min:0.01', 'max:999999999999'],
            'expense_category_id' => [
                'nullable',
                Rule::exists('expense_categories', 'id')->where('store_id', $store->id),
            ],
            'payment_method' => ['required', 'string', 'in:cash,kpay,wave,cbpay,bank_transfer,other'],
            'paid_to' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'client_transaction_id' => ['nullable', 'string', 'max:100'],
        ]);

        $title = trim($data['title']);
        $amount = bcadd((string) $data['amount'], '0', 2);
        $paymentMethod = $data['payment_method'];
        $clientTxId = ExpenseIdempotency::normalizeKey($data['client_transaction_id'] ?? null);
        $isCash = $paymentMethod === 'cash';
        $expenseDate = now()->toDateString();

        $shiftsEnabled = $store->hasCapability(Capability::OPERATIONS_CASHIER_SHIFTS);
        $openShift = null;
        $paymentSource = $isCash ? Expense::SOURCE_SAFE : Expense::SOURCE_BANK;

        if ($isCash && $shiftsEnabled) {
            $openShifts = $this->shifts->openShiftsFor($store, auth()->user());

            if ($openShifts->isEmpty()) {
                return $this->expenseError($request, 'payment_method', __('messages.pos_expense_shift_required'));
            }

            // A cashier can hold one open shift per register. Picking "the latest"
            // would charge a drawer that may not be the one the cash came from, so
            // an ambiguous drawer is refused instead of guessed.
            if ($openShifts->count() > 1) {
                return $this->expenseError($request, 'payment_method', __('messages.pos_expense_shift_ambiguous'));
            }

            $openShift = $openShifts->first();

            // The shift's own business date must still be open. Charging a drawer
            // whose day was already closed and approved would move a signed-off
            // figure, and the shift close (which is date-independent) would then
            // disagree with the period it belongs to.
            $shiftDate = $openShift->opened_at?->copy()->timezone(config('app.timezone'))->toDateString();

            if ($shiftDate !== null) {
                try {
                    app(\App\POS\Services\PeriodLockService::class)->assertDateNotLocked($store, $shiftDate, 'expense');
                } catch (\App\POS\Exceptions\PeriodLockedException $e) {
                    return $this->expenseError($request, 'payment_method', __('messages.expense_shift_period_locked'));
                }
            }

            // Only now is the drawer provable. When the cashier-shifts capability
            // is off there is no drawer to attribute to, so the expense is booked
            // as a confirmed non-drawer outflow — never a 'drawer' row with a NULL
            // shift, which would be an unattributable claim on the drawer.
            $paymentSource = Expense::SOURCE_DRAWER;
        }

        $payload = [
            'store_id' => $store->id,
            'title' => $title,
            'amount' => $amount,
            'expense_date' => $expenseDate,
            'payment_method' => $paymentMethod,
            'payment_source' => $paymentSource,
            'cashier_shift_id' => $openShift?->id,
            'recorded_by' => auth()->id(),
        ];

        // Idempotency before the period lock: a retry of an already-recorded
        // expense must replay the stored row even if the day has closed since.
        if ($clientTxId !== null) {
            $existing = $this->findByClientKey($store, $clientTxId);

            if ($existing) {
                return $this->expenseReplayOrConflict($request, $existing, $payload);
            }
        }

        try {
            app(\App\POS\Services\PeriodLockService::class)->assertDateNotLocked($store, $expenseDate, 'expense');
        } catch (\App\POS\Exceptions\PeriodLockedException $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }
            return back()->withInput()->withErrors(['expense' => $e->getMessage()])->with('error', $e->getMessage());
        }

        try {
            $expense = DB::transaction(function () use ($store, $data, $payload, $openShift, $clientTxId) {
                if ($openShift !== null) {
                    // Hold the drawer's row lock: an expense must not land on a
                    // shift that is being closed in the same instant, or it would
                    // be deducted from a drawer that never counted it.
                    $locked = CashierShift::where('store_id', $store->id)
                        ->whereKey($openShift->id)
                        ->lockForUpdate()
                        ->first();

                    if (! $locked || $locked->status !== 'open') {
                        throw new InventoryException(__('messages.shift_already_closed'));
                    }
                }

                $expense = Expense::create([
                    'store_id' => $store->id,
                    'cashier_shift_id' => $openShift?->id,
                    'expense_category_id' => ! empty($data['expense_category_id']) ? (int) $data['expense_category_id'] : null,
                    'expense_number' => Expense::generateExpenseNumber($store->id),
                    'client_transaction_id' => $clientTxId,
                    'request_fingerprint' => ExpenseIdempotency::fingerprint($payload),
                    'title' => $payload['title'],
                    'amount' => $payload['amount'],
                    'status' => 'paid',
                    'expense_date' => $payload['expense_date'],
                    'payment_method' => $payload['payment_method'],
                    'payment_source' => $payload['payment_source'],
                    'paid_to' => ! empty($data['paid_to']) ? trim($data['paid_to']) : null,
                    'reference_no' => null,
                    'notes' => ! empty($data['notes']) ? trim($data['notes']) : null,
                    'recorded_by' => $payload['recorded_by'],
                ]);

                // NOTE: We intentionally do NOT increment shift.cash_out here.
                // Cash expenses are tracked in the `expenses` table as the single
                // authoritative source with payment_source='drawer' and
                // cashier_shift_id. This prevents double-counting against manual
                // non-expense cash-outs (safe drops).

                return $expense;
            });
        } catch (QueryException $e) {
            // Concurrent double-submit: the other request won the unique index.
            if ($clientTxId !== null && ExpenseIdempotency::isDuplicateKey($e)) {
                $winner = $this->findByClientKey($store, $clientTxId);

                if ($winner) {
                    return $this->expenseReplayOrConflict($request, $winner, $payload);
                }
            }

            report($e);

            return $this->expenseError($request, 'expense', __('messages.expense_save_failed_retry'));
        } catch (InventoryException $e) {
            return $this->expenseError($request, 'payment_method', $e->getMessage());
        } catch (\Throwable $e) {
            // Never surface a driver/stack message to the POS screen.
            report($e);

            return $this->expenseError($request, 'expense', __('messages.expense_save_failed_retry'));
        }

        \App\Models\AuditLog::write(
            storeId: $store->id,
            action: 'pos_expense_recorded',
            entityType: 'expense',
            entityId: $expense->id,
            metadata: [
                'expense_number' => $expense->expense_number,
                'title' => $expense->title,
                'amount' => (string) $expense->amount,
                'payment_method' => $expense->payment_method,
            ],
            actorId: auth()->id(),
            ipAddress: $request->ip(),
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('messages.expense_created_success'),
                'expense' => $expense,
            ]);
        }

        return back()->with('success', __('messages.expense_created_success'));
    }

    /**
     * Return a POS expense failure in whichever shape the caller understands.
     *
     * Callers only ever see a translated, actionable message — never a driver or
     * stack message.
     */
    private function expenseError(Request $request, string $field, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => $message, 'field' => $field], 422);
        }

        return back()->withInput()->withErrors([$field => $message])->with('error', $message);
    }

    private function findByClientKey(Store $store, string $clientTxId): ?Expense
    {
        return Expense::where('store_id', $store->id)
            ->where('client_transaction_id', $clientTxId)
            ->first();
    }

    /**
     * Answer a resubmission that carries a client_transaction_id we already hold.
     *
     * Same payload → the original row is returned as a successful replay (the
     * browser lost the first response, nothing new was written).
     * Different payload → 409. The operator edited values after a submission
     * whose outcome they could not see; replying "saved" would leave the stored
     * row at the OLD values, and writing a second row would double-deduct the
     * drawer.
     */
    private function expenseReplayOrConflict(Request $request, Expense $existing, array $payload): JsonResponse|RedirectResponse
    {
        if (ExpenseIdempotency::resultFor($existing, $payload) === ExpenseIdempotency::RESULT_REPLAY) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => __('messages.expense_created_success'),
                    'expense' => $existing,
                    'idempotent_replay' => true,
                ]);
            }

            return back()->with('success', __('messages.expense_created_success'));
        }

        $message = __('messages.expense_duplicate_submission_conflict', [
            'number' => $existing->expense_number,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'conflict' => true,
                'error' => $message,
                'expense' => $existing,
            ], 409);
        }

        return back()->withInput()->withErrors(['title' => $message])->with('error', $message);
    }

    public function open(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        $data = $request->validate([
            'register_name' => ['required', 'string', 'max:100'],
            // decimal (not plain numeric): bcmath rejects scientific notation
            // ("1e3") with a ValueError — this rule blocks it before the shift
            // service's bc* calls ever see it.
            'opening_cash' => ['nullable', 'decimal:0,2', 'min:0'],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->where('store_id', $store->id)],
        ]);

        try {
            $this->shifts->openShift($store, $data, auth()->user());
        } catch (\App\POS\Exceptions\PeriodLockedException $e) {
            return back()->withErrors(['shift' => $e->getMessage()]);
        } catch (InventoryException $e) {
            return back()->withErrors(['shift' => $e->getMessage()]);
        }

        return redirect()->route('pos.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.shift_opened'));
    }

    public function cashEvent(Request $request, string $store_slug, CashierShift $shift, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        $this->authorizeShift($shift, $store);

        $data = $request->validate([
            'type' => ['required', 'in:cash_in,cash_out'],
            'amount' => ['required', 'decimal:0,2', 'gt:0'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->shifts->addCashEvent($shift, $data, auth()->user());
        } catch (InventoryException $e) {
            return back()->withErrors(['shift' => $e->getMessage()]);
        }

        return back()->with('success', __('messages.cash_event_recorded'));
    }

    public function close(Request $request, string $store_slug, CashierShift $shift, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        $this->authorizeShift($shift, $store);

        $data = $request->validate([
            'actual_closing_amount' => ['required', 'decimal:0,2', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'variance_reason' => ['nullable', 'string', 'max:1000'],
            'manager_approval' => ['nullable', 'boolean'],
            'manager_signoff_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        try {
            $this->shifts->closeShift($shift, $data, auth()->user());
        } catch (\App\POS\Exceptions\PeriodLockedException $e) {
            return back()->withErrors(['shift' => $e->getMessage()]);
        } catch (InventoryException $e) {
            return back()->withErrors(['shift' => $e->getMessage()]);
        }

        return back()->with('success', __('messages.shift_closed'));
    }

    private function authorizeShift(CashierShift $shift, Store $store): void
    {
        if ((int) $shift->store_id !== (int) $store->id) {
            abort(403, 'Unauthorized store shift.');
        }
    }
}
