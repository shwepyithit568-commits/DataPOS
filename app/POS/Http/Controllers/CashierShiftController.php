<?php

namespace App\POS\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\CashierShift;
use App\POS\Models\Expense;
use App\POS\Models\ExpenseCategory;
use App\POS\Services\CashierShiftService;
use App\POS\Services\CustomerDebtService;
use App\POS\Services\PosSaleService;
use App\Services\StoreContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $shiftsEnabled = $store->hasCapability(\App\Capabilities\Capability::OPERATIONS_CASHIER_SHIFTS);

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

        return view('pos.index', compact('store', 'openShift', 'occupiedRegisters', 'summary', 'cart', 'cartTotals', 'todaySales', 'outstanding', 'outstandingTotal', 'expenseCategories'));
    }

    public function recordExpense(Request $request, StoreContext $context): JsonResponse|RedirectResponse
    {
        $store = $context->getStore();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999999'],
            'expense_category_id' => [
                'nullable',
                Rule::exists('expense_categories', 'id')->where('store_id', $store->id),
            ],
            'payment_method' => ['required', 'string', 'in:cash,kpay,wave,cbpay,bank_transfer,other'],
            'paid_to' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $title = trim($data['title']);
        $amount = (float) $data['amount'];
        $paymentMethod = $data['payment_method'];

        $expenseNumber = Expense::generateExpenseNumber($store->id);

        $expense = Expense::create([
            'store_id' => $store->id,
            'expense_category_id' => ! empty($data['expense_category_id']) ? (int) $data['expense_category_id'] : null,
            'expense_number' => $expenseNumber,
            'title' => $title,
            'amount' => $amount,
            'expense_date' => now()->toDateString(),
            'payment_method' => $paymentMethod,
            'paid_to' => ! empty($data['paid_to']) ? trim($data['paid_to']) : null,
            'reference_no' => null,
            'notes' => ! empty($data['notes']) ? trim($data['notes']) : null,
            'recorded_by' => auth()->id(),
        ]);

        // If paid in cash and cashier shift tracking is enabled, link cash_out event to current cashier shift
        $shiftsEnabled = $store->hasCapability(\App\Capabilities\Capability::OPERATIONS_CASHIER_SHIFTS);
        if ($paymentMethod === 'cash' && $shiftsEnabled) {
            $openShift = $this->shifts->openShiftFor($store, auth()->user());
            if ($openShift) {
                try {
                    $this->shifts->addCashEvent($openShift, [
                        'type' => 'cash_out',
                        'amount' => number_format($amount, 2, '.', ''),
                        'reason' => 'Expense: ' . $title . ($expense->expense_number ? ' (' . $expense->expense_number . ')' : ''),
                    ], auth()->user());
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Could not record cash_out event for pos expense: ' . $e->getMessage());
                }
            }
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

    public function open(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        $data = $request->validate([
            'register_name' => ['required', 'string', 'max:100'],
            // decimal (not plain numeric): bcmath rejects scientific notation
            // ("1e3") with a ValueError — this rule blocks it before the shift
            // service's bc* calls ever see it.
            'opening_cash' => ['nullable', 'decimal:0,2', 'min:0'],
            'branch_id' => ['nullable', 'integer', \Illuminate\Validation\Rule::exists('branches', 'id')->where('store_id', $store->id)],
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
