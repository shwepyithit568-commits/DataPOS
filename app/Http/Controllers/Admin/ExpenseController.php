<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\POS\Exceptions\PeriodLockedException;
use App\POS\Models\CashierShift;
use App\POS\Models\Expense;
use App\POS\Models\ExpenseCategory;
use App\POS\Services\PeriodLockService;
use App\POS\Support\ExpenseIdempotency;
use App\Services\StoreContext;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseController extends Controller
{
    public const PAYMENT_METHODS = [
        'cash'          => 'Cash (ငွေသား)',
        'kpay'          => 'KBZPay (KPay)',
        'wave'          => 'WavePay',
        'cbpay'         => 'CB Pay',
        'bank_transfer' => 'Bank Transfer (ဘဏ်လွှဲ)',
        'other'         => 'Other (အခြား)',
    ];

    public function index(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();
        $storeRouteParams = $context->getRouteParams();

        [$fromDate, $toDate, $preset] = $this->resolveDateRange($request);

        $search = trim((string) $request->query('search', ''));
        $categoryId = $request->query('category_id');
        $paymentMethod = $request->query('payment_method');
        $sort = (string) $request->query('sort', 'newest');
        $perPageParam = $request->query('per_page', '25');
        $perPage = $perPageParam === 'all' ? 1000 : max(10, min(100, (int) $perPageParam));

        $query = Expense::with(['category', 'recorder'])
            ->where('store_id', $store->id);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('expense_number', 'like', "%{$search}%")
                    ->orWhere('paid_to', 'like', "%{$search}%")
                    ->orWhere('reference_no', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('category', function ($catQ) use ($search) {
                        $catQ->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($categoryId)) {
            $query->where('expense_category_id', $categoryId);
        }

        if (! empty($paymentMethod)) {
            $query->where('payment_method', $paymentMethod);
        }

        if ($fromDate) {
            $query->whereDate('expense_date', '>=', $fromDate->toDateString());
        }

        if ($toDate) {
            $query->whereDate('expense_date', '<=', $toDate->toDateString());
        }

        match ($sort) {
            'oldest' => $query->orderBy('expense_date', 'asc')->orderBy('id', 'asc'),
            'amount_desc' => $query->orderBy('amount', 'desc')->orderBy('id', 'desc'),
            'amount_asc' => $query->orderBy('amount', 'asc')->orderBy('id', 'asc'),
            'title_asc' => $query->orderBy('title', 'asc'),
            default => $query->orderBy('expense_date', 'desc')->orderBy('id', 'desc'),
        };

        $totalFilteredAmount = (clone $query)->sum('amount');
        $expenses = $query->paginate($perPage)->withQueryString();

        // 4 KPI Metrics
        $todayStr = now()->toDateString();
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        $todayExpensesSum = Expense::where('store_id', $store->id)
            ->whereDate('expense_date', $todayStr)
            ->sum('amount');

        $thisMonthExpensesSum = Expense::where('store_id', $store->id)
            ->whereDate('expense_date', '>=', $startOfMonth)
            ->whereDate('expense_date', '<=', $endOfMonth)
            ->sum('amount');

        $topCategoryRow = Expense::where('store_id', $store->id)
            ->whereNotNull('expense_category_id')
            ->selectRaw('expense_category_id, SUM(amount) as total_spent')
            ->groupBy('expense_category_id')
            ->orderByDesc('total_spent')
            ->first();

        $topCategoryName = '—';
        $topCategoryAmount = 0;
        if ($topCategoryRow) {
            $topCat = ExpenseCategory::find($topCategoryRow->expense_category_id);
            if ($topCat) {
                $topCategoryName = $topCat->name;
                $topCategoryAmount = (float) $topCategoryRow->total_spent;
            }
        }

        $categories = ExpenseCategory::where('store_id', $store->id)
            ->active()
            ->ordered()
            ->get();

        $allCategoriesForFilter = ExpenseCategory::where('store_id', $store->id)
            ->ordered()
            ->get();

        $metrics = [
            'total_count'          => $expenses->total(),
            'total_filtered_sum'   => (float) $totalFilteredAmount,
            'today_sum'            => (float) $todayExpensesSum,
            'this_month_sum'       => (float) $thisMonthExpensesSum,
            'top_category_name'    => $topCategoryName,
            'top_category_amount'  => $topCategoryAmount,
        ];

        $exportXlsxUrl = route('store.admin.expenses.export', array_merge($storeRouteParams, array_filter([
            'search' => $search,
            'sort' => $sort,
            'category_id' => $categoryId,
            'payment_method' => $paymentMethod,
            'preset' => $preset,
            'date_from' => $fromDate?->toDateString(),
            'date_to' => $toDate?->toDateString(),
            'format' => 'xlsx',
        ])));

        $exportCsvUrl = route('store.admin.expenses.export', array_merge($storeRouteParams, array_filter([
            'search' => $search,
            'sort' => $sort,
            'category_id' => $categoryId,
            'payment_method' => $paymentMethod,
            'preset' => $preset,
            'date_from' => $fromDate?->toDateString(),
            'date_to' => $toDate?->toDateString(),
            'format' => 'csv',
        ])));

        $exportBaseUrl = route('store.admin.expenses.export', array_merge($storeRouteParams, request()->except(['page', 'format'])));

        return view('admin.expenses.index', compact(
            'store',
            'storeRouteParams',
            'expenses',
            'categories',
            'allCategoriesForFilter',
            'metrics',
            'search',
            'categoryId',
            'paymentMethod',
            'fromDate',
            'toDate',
            'preset',
            'sort',
            'exportBaseUrl',
            'exportXlsxUrl',
            'exportCsvUrl'
        ));
    }

    public function store(Request $request, StoreContext $context, string $store_slug): RedirectResponse
    {
        $store = $context->getStore();
        $storeRouteParams = $context->getRouteParams();

        $validated = $request->validate([
            'title'               => ['required', 'string', 'max:255'],
            'amount'              => ['required', 'decimal:0,2', 'min:0.01', 'max:999999999999'],
            'expense_date'        => ['required', 'date'],
            'expense_category_id' => [
                'nullable',
                Rule::exists('expense_categories', 'id')->where('store_id', $store->id),
            ],
            'payment_method'      => ['required', 'string', Rule::in(array_keys(self::PAYMENT_METHODS))],
            'payment_source'      => ['nullable', 'string', Rule::in(array_keys(Expense::PAYMENT_SOURCES))],
            'cashier_shift_id'    => [
                'nullable',
                Rule::exists('cashier_shifts', 'id')->where('store_id', $store->id),
            ],
            'client_transaction_id' => ['nullable', 'string', 'max:100'],
            'paid_to'             => ['nullable', 'string', 'max:255'],
            'reference_no'        => ['nullable', 'string', 'max:100'],
            'notes'               => ['nullable', 'string', 'max:2000'],
            'attachment'          => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp,pdf', 'max:5120'], // 5MB max
        ]);

        $clientTxId = ExpenseIdempotency::normalizeKey($validated['client_transaction_id'] ?? null);
        $paymentSource = $this->resolvePaymentSource($validated);
        $expenseDate = Carbon::parse($validated['expense_date'])->toDateString();

        // The shift the operator named — NOT yet validated. Resolving the real
        // shift can fail ("that shift has since closed"), and a retry of an
        // already-created expense must be answered from the stored row even when
        // the shift it was charged to is long closed.
        $payload = [
            'store_id' => $store->id,
            'title' => trim($validated['title']),
            'amount' => bcadd((string) $validated['amount'], '0', 2),
            'expense_date' => $expenseDate,
            'payment_method' => $validated['payment_method'],
            'payment_source' => $paymentSource,
            'cashier_shift_id' => $paymentSource === Expense::SOURCE_DRAWER
                ? ($validated['cashier_shift_id'] ?? null)
                : null,
            'recorded_by' => $request->user()?->id,
        ];

        // Idempotency is resolved BEFORE validation and the period lock. A browser
        // that lost the response retries the same key after the period may have
        // closed; that retry must replay the row it already created (read-only)
        // instead of surfacing a lock or "shift closed" error for an expense that
        // already exists.
        if ($clientTxId !== null) {
            $existing = $this->findByClientKey($store, $clientTxId);
            if ($existing) {
                return $this->replayOrConflict($existing, $payload, $storeRouteParams);
            }
        }

        $shiftId = $this->resolveDrawerShift($store, $validated, $paymentSource);

        try {
            app(PeriodLockService::class)->assertDateNotLocked($store, $expenseDate, 'expense');
        } catch (PeriodLockedException $e) {
            throw ValidationException::withMessages(['expense_date' => $e->getMessage()]);
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store("stores/{$store->id}/expenses", 'public');
        }

        try {
            $this->createExpenseWithLock($store, $payload, $expenseDate, $shiftId, [
                'expense_category_id' => $validated['expense_category_id'] ?? null,
                'paid_to' => $validated['paid_to'] ?? null,
                'reference_no' => $validated['reference_no'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'attachment_path' => $attachmentPath,
            ], $clientTxId);
        } catch (QueryException $e) {
            // Concurrent double-submit: the other request won the unique index.
            // Replay its row rather than leaking a raw driver error to the user.
            if ($clientTxId !== null && ExpenseIdempotency::isDuplicateKey($e)) {
                $winner = $this->findByClientKey($store, $clientTxId);
                if ($winner) {
                    return $this->replayOrConflict($winner, $payload, $storeRouteParams);
                }
            }

            report($e);

            throw ValidationException::withMessages([
                'title' => __('messages.expense_save_failed_retry'),
            ]);
        }

        return redirect()
            ->route('store.admin.expenses.index', $storeRouteParams)
            ->with('success', __('messages.expense_created_success'))
            // Tells the index view to retire the retry key before it issues the
            // next one, so the following expense is a new submission rather than
            // a replay of this one.
            ->with('expense_saved', true);
    }

    /**
     * Write the expense, holding the drawer shift's row lock so a concurrent
     * shift close can never finalise a drawer that is about to receive a
     * deduction it would not have counted.
     */
    private function createExpenseWithLock(Store $store, array $payload, string $expenseDate, ?int $shiftId, array $extra, ?string $clientTxId): Expense
    {
        return DB::transaction(function () use ($store, $payload, $expenseDate, $shiftId, $extra, $clientTxId) {
            if ($shiftId !== null) {
                $locked = CashierShift::where('store_id', $store->id)
                    ->whereKey($shiftId)
                    ->lockForUpdate()
                    ->first();

                if (! $locked || $locked->status !== 'open') {
                    throw ValidationException::withMessages([
                        'cashier_shift_id' => __('messages.shift_already_closed'),
                    ]);
                }
            }

            return Expense::create([
                'store_id' => $store->id,
                'cashier_shift_id' => $shiftId,
                'expense_category_id' => $extra['expense_category_id'] ? (int) $extra['expense_category_id'] : null,
                'expense_number' => Expense::generateExpenseNumber($store->id),
                'client_transaction_id' => $clientTxId,
                'request_fingerprint' => ExpenseIdempotency::fingerprint($payload),
                'title' => $payload['title'],
                'amount' => $payload['amount'],
                'status' => 'paid',
                'expense_date' => $expenseDate,
                'payment_method' => $payload['payment_method'],
                'payment_source' => $payload['payment_source'],
                'paid_to' => ! empty($extra['paid_to']) ? trim($extra['paid_to']) : null,
                'reference_no' => ! empty($extra['reference_no']) ? trim($extra['reference_no']) : null,
                'notes' => ! empty($extra['notes']) ? trim($extra['notes']) : null,
                'attachment_path' => $extra['attachment_path'],
                'recorded_by' => $payload['recorded_by'],
            ]);
        });
    }

    private function findByClientKey(Store $store, string $clientTxId): ?Expense
    {
        return Expense::where('store_id', $store->id)
            ->where('client_transaction_id', $clientTxId)
            ->first();
    }

    /**
     * Same key, same payload → replay the original. Same key, different payload
     * → 409 conflict: the operator changed values after a submission whose
     * outcome they could not see, and silently answering "saved" would leave the
     * stored row at the OLD values.
     */
    private function replayOrConflict(Expense $existing, array $payload, array $storeRouteParams): RedirectResponse
    {
        if (ExpenseIdempotency::resultFor($existing, $payload) === ExpenseIdempotency::RESULT_REPLAY) {
            return redirect()
                ->route('store.admin.expenses.index', $storeRouteParams)
                ->with('success', __('messages.expense_created_success'))
                ->with('expense_saved', true);
        }

        return redirect()
            ->route('store.admin.expenses.index', $storeRouteParams)
            ->with('error', __('messages.expense_duplicate_submission_conflict', [
                'number' => $existing->expense_number,
            ]));
    }

    /**
     * The payment source for a new/updated expense.
     *
     * An omitted source is defaulted deliberately (cash → safe, other → bank):
     * the drawer is only ever reduced when the operator says so explicitly.
     * A supplied source must already be in the enum — an unknown string is a
     * rejected request, never a silently "non-drawer" expense.
     */
    private function resolvePaymentSource(array $validated): string
    {
        $supplied = $validated['payment_source'] ?? null;

        if (empty($supplied)) {
            return $validated['payment_method'] === 'cash'
                ? Expense::SOURCE_SAFE
                : Expense::SOURCE_BANK;
        }

        return (string) $supplied;
    }

    /**
     * Validate and return the drawer shift an expense may be linked to.
     *
     * A drawer expense MUST name its shift; nothing is auto-assigned and no
     * "first open shift" is guessed. Returns null for every non-drawer source.
     */
    private function resolveDrawerShift(Store $store, array $validated, string $paymentSource): ?int
    {
        if ($paymentSource !== Expense::SOURCE_DRAWER) {
            return null;
        }

        if ($validated['payment_method'] !== 'cash') {
            throw ValidationException::withMessages([
                'payment_source' => __('messages.drawer_source_requires_cash'),
            ]);
        }

        $shiftId = $validated['cashier_shift_id'] ?? null;

        if (empty($shiftId)) {
            throw ValidationException::withMessages([
                'cashier_shift_id' => __('messages.drawer_shift_required'),
            ]);
        }

        $shift = CashierShift::where('store_id', $store->id)->whereKey($shiftId)->first();

        if (! $shift || $shift->status !== 'open') {
            throw ValidationException::withMessages([
                'cashier_shift_id' => __('messages.shift_already_closed'),
            ]);
        }

        $expenseDate = Carbon::parse($validated['expense_date'])->toDateString();
        $shiftDate = $shift->opened_at?->copy()->timezone(config('app.timezone'))->toDateString();

        // A shift cannot pay for something dated before it opened, and charging a
        // shift whose business date is already closed would silently rewrite an
        // approved period's drawer.
        if ($shiftDate !== null && $expenseDate < $shiftDate) {
            throw ValidationException::withMessages([
                'expense_date' => __('messages.expense_date_before_shift_open'),
            ]);
        }

        if ($shiftDate !== null) {
            try {
                app(PeriodLockService::class)->assertDateNotLocked($store, $shiftDate, 'expense');
            } catch (PeriodLockedException $e) {
                throw ValidationException::withMessages([
                    'cashier_shift_id' => __('messages.expense_shift_period_locked'),
                ]);
            }
        }

        return (int) $shift->id;
    }

    public function update(Request $request, StoreContext $context, string $store_slug, int|string $expense): RedirectResponse
    {
        $store = $context->getStore();
        $storeRouteParams = $context->getRouteParams();

        $expenseModel = Expense::where('store_id', $store->id)->where('id', $expense)->firstOrFail();

        $validated = $request->validate([
            'title'               => ['required', 'string', 'max:255'],
            'amount'              => ['required', 'decimal:0,2', 'min:0.01', 'max:999999999999'],
            'expense_date'        => ['required', 'date'],
            'expense_category_id' => [
                'nullable',
                Rule::exists('expense_categories', 'id')->where('store_id', $store->id),
            ],
            'payment_method'      => ['required', 'string', Rule::in(array_keys(self::PAYMENT_METHODS))],
            'payment_source'      => ['nullable', 'string', Rule::in(array_keys(Expense::PAYMENT_SOURCES))],
            'cashier_shift_id'    => [
                'nullable',
                Rule::exists('cashier_shifts', 'id')->where('store_id', $store->id),
            ],
            'paid_to'             => ['nullable', 'string', 'max:255'],
            'reference_no'        => ['nullable', 'string', 'max:100'],
            'notes'               => ['nullable', 'string', 'max:2000'],
            'attachment'          => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp,pdf', 'max:5120'],
            'remove_attachment'   => ['nullable', 'boolean'],
        ]);

        $paymentSource = $validated['payment_source'] ?? $expenseModel->payment_source;

        if (empty($paymentSource)) {
            $paymentSource = $expenseModel->payment_source
                ?? ($validated['payment_method'] === 'cash' ? Expense::SOURCE_SAFE : Expense::SOURCE_BANK);
        }

        $newDate = Carbon::parse($validated['expense_date'])->toDateString();
        $oldDate = $expenseModel->expense_date?->toDateString();

        // The shift the operator named, before any validation. An absent field
        // means "leave the link alone", not "detach it". The closed-shift guard
        // needs this raw value so it can tell whether the drawer attribution is
        // actually being changed.
        $requestedShiftId = $paymentSource === Expense::SOURCE_DRAWER
            ? (array_key_exists('cashier_shift_id', $validated)
                ? $validated['cashier_shift_id']
                : $expenseModel->cashier_shift_id)
            : null;

        // An expense that reduced an already-counted drawer cannot be rewritten.
        // This runs BEFORE shift validation so the operator is told the real
        // reason ("this expense is locked by a closed shift") instead of the
        // generic "that shift is closed".
        $this->assertClosedShiftAllowsEdit($expenseModel, $validated, $paymentSource, $requestedShiftId, $newDate, $oldDate);

        if ($paymentSource === Expense::SOURCE_DRAWER) {
            // A purely cosmetic edit must stay possible on a closed shift, so the
            // unchanged drawer link is carried over instead of being re-resolved
            // against a shift that is (correctly) no longer open.
            $shiftId = $this->drawerEditIsNeutral($expenseModel, $validated, $paymentSource, $requestedShiftId, $newDate, $oldDate)
                ? $expenseModel->cashier_shift_id
                : $this->resolveDrawerShift($store, [
                    'payment_method' => $validated['payment_method'],
                    'payment_source' => $paymentSource,
                    'cashier_shift_id' => $requestedShiftId,
                    'expense_date' => $newDate,
                ], $paymentSource);
        } else {
            $shiftId = null;
        }

        // Period lock: BOTH the period the expense is leaving and the one it is
        // moving into must be open.
        try {
            $periodLock = app(PeriodLockService::class);
            $periodLock->assertDateNotLocked($store, $expenseModel->expense_date, 'expense');
            if ($oldDate !== $newDate) {
                $periodLock->assertDateNotLocked($store, $newDate, 'expense');
            }
        } catch (PeriodLockedException $e) {
            throw ValidationException::withMessages([
                'expense_date' => $e->getMessage(),
            ]);
        }

        $attachmentPath = $expenseModel->attachment_path;
        if (! empty($validated['remove_attachment']) && $attachmentPath) {
            Storage::disk('public')->delete($attachmentPath);
            $attachmentPath = null;
        }

        if ($request->hasFile('attachment')) {
            if ($attachmentPath) {
                Storage::disk('public')->delete($attachmentPath);
            }
            $attachmentPath = $request->file('attachment')->store("stores/{$store->id}/expenses", 'public');
        }

        DB::transaction(function () use ($expenseModel, $store, $validated, $paymentSource, $shiftId, $newDate, $attachmentPath) {
            // Lock every shift whose drawer math this edit can move, in a stable
            // order so two concurrent edits cannot deadlock each other.
            $shiftIds = array_values(array_unique(array_filter([
                $expenseModel->cashier_shift_id,
                $shiftId,
            ])));

            sort($shiftIds);

            foreach ($shiftIds as $id) {
                CashierShift::where('store_id', $store->id)->whereKey($id)->lockForUpdate()->first();
            }

            $expenseModel->update([
                'cashier_shift_id'    => $shiftId,
                'expense_category_id' => $validated['expense_category_id'] ?? null,
                'title'               => trim($validated['title']),
                'amount'              => bcadd((string) $validated['amount'], '0', 2),
                'expense_date'        => $newDate,
                'payment_method'      => $validated['payment_method'],
                'payment_source'      => $paymentSource,
                'paid_to'             => ! empty($validated['paid_to']) ? trim($validated['paid_to']) : null,
                'reference_no'        => ! empty($validated['reference_no']) ? trim($validated['reference_no']) : null,
                'notes'               => ! empty($validated['notes']) ? trim($validated['notes']) : null,
                'attachment_path'     => $attachmentPath,
            ]);
        });

        return redirect()
            ->route('store.admin.expenses.index', $storeRouteParams)
            ->with('success', __('messages.expense_updated_success'));
    }

    /**
     * Does this edit leave everything the closed drawer was charged unchanged?
     *
     * Cosmetic fields (title, notes, category, attachment, paid-to, reference)
     * are deliberately not part of the comparison — those stay editable on a
     * signed-off expense.
     */
    private function drawerEditIsNeutral(
        Expense $expense,
        array $validated,
        string $paymentSource,
        ?int $shiftId,
        string $newDate,
        ?string $oldDate,
    ): bool {
        return $paymentSource === $expense->payment_source
            && (int) $shiftId === (int) $expense->cashier_shift_id
            && $validated['payment_method'] === $expense->payment_method
            && bccomp(bcadd((string) $validated['amount'], '0', 2), (string) $expense->amount, 2) === 0
            && $newDate === $oldDate;
    }

    /**
     * An expense that reduced an already-closed drawer cannot be rewritten in
     * place: the shift's counted figures were signed off against that expense,
     * so changing them silently rewrites a closed drawer. Cosmetic fields stay
     * editable; anything that moves money needs a reversal / audited adjustment.
     */
    private function assertClosedShiftAllowsEdit(
        Expense $expense,
        array $validated,
        string $paymentSource,
        ?int $shiftId,
        string $newDate,
        ?string $oldDate,
    ): void {
        $shift = $expense->deductionShift();

        if (! $shift || $shift->status !== 'closed') {
            return;
        }

        if (! $this->drawerEditIsNeutral($expense, $validated, $paymentSource, $shiftId, $newDate, $oldDate)) {
            throw ValidationException::withMessages([
                'amount' => __('messages.expense_closed_shift_locked', ['shift' => $shift->id]),
            ]);
        }
    }

    public function destroy(Request $request, StoreContext $context, string $store_slug, int|string $expense): RedirectResponse
    {
        $store = $context->getStore();
        $storeRouteParams = $context->getRouteParams();

        $expenseModel = Expense::where('store_id', $store->id)->where('id', $expense)->firstOrFail();

        // Deleting a deduction out of a closed drawer rewrites signed-off history.
        if ($expenseModel->isLockedByClosedShift()) {
            return back()->with('error', __(
                'messages.expense_closed_shift_locked',
                ['shift' => $expenseModel->cashier_shift_id]
            ));
        }

        // Period lock check
        try {
            app(PeriodLockService::class)->assertDateNotLocked($store, $expenseModel->expense_date, 'expense');
        } catch (PeriodLockedException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($expenseModel->attachment_path) {
            Storage::disk('public')->delete($expenseModel->attachment_path);
        }

        DB::transaction(function () use ($expenseModel, $store) {
            if ($expenseModel->cashier_shift_id !== null) {
                CashierShift::where('store_id', $store->id)
                    ->whereKey($expenseModel->cashier_shift_id)
                    ->lockForUpdate()
                    ->first();
            }

            $expenseModel->delete();
        });

        return redirect()
            ->route('store.admin.expenses.index', $storeRouteParams)
            ->with('success', __('messages.expense_deleted_success'));
    }

    public function export(Request $request, StoreContext $context): BinaryFileResponse|StreamedResponse
    {
        $store = $context->getStore();

        [$fromDate, $toDate, $preset] = $this->resolveDateRange($request);
        $search = trim((string) $request->query('search', ''));
        $categoryId = $request->query('category_id');
        $paymentMethod = $request->query('payment_method');
        $format = $request->query('format', 'xlsx');

        $query = Expense::with(['category', 'recorder'])
            ->where('store_id', $store->id);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('expense_number', 'like', "%{$search}%")
                    ->orWhere('paid_to', 'like', "%{$search}%")
                    ->orWhere('reference_no', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if (! empty($categoryId)) {
            $query->where('expense_category_id', $categoryId);
        }

        if (! empty($paymentMethod)) {
            $query->where('payment_method', $paymentMethod);
        }

        if ($fromDate) {
            $query->whereDate('expense_date', '>=', $fromDate->toDateString());
        }

        if ($toDate) {
            $query->whereDate('expense_date', '<=', $toDate->toDateString());
        }

        $expenses = $query->orderBy('expense_date', 'desc')->orderBy('id', 'desc')->get();

        if ($format === 'csv') {
            return $this->exportCsv($store, $expenses);
        }

        return $this->exportXlsx($store, $expenses, $fromDate, $toDate);
    }

    /**
     * Export Expenses as Formatted Excel (.xlsx).
     */
    private function exportXlsx(Store $store, $expenses, ?Carbon $fromDate, ?Carbon $toDate): BinaryFileResponse
    {
        $filename = 'Expenses_' . $store->slug . '_' . now()->format('Ymd_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'datapos_exp_');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Expenses');

        // Header Title Block
        $sheet->setCellValue('A1', $store->name . ' - ' . __('messages.expenses_title'));
        $periodText = ($fromDate && $toDate)
            ? $fromDate->format('d/m/Y') . ' - ' . $toDate->format('d/m/Y')
            : __('messages.all');
        $sheet->setCellValue('A2', __('messages.period') . ': ' . $periodText);
        $sheet->setCellValue('A3', __('messages.export_date') . ': ' . now()->format('d/m/Y h:i A') . ' | Total Count: ' . $expenses->count());

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('1E1B4B');
        $sheet->getStyle('A2:A3')->getFont()->setSize(10)->getColor()->setRGB('64748B');

        // Summary Box
        $totalSum = (float) $expenses->sum('amount');
        $sheet->setCellValue('A5', __('messages.expenses_total_filtered') . ': ' . number_format($totalSum, 2) . ' MMK');
        $sheet->getStyle('A5:D5')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A5:D5')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F1F5F9'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']],
            ],
        ]);

        $row = 7;

        // Table Header
        $headers = [
            'A' => __('messages.report_voucher_no') ?? 'Voucher No',
            'B' => __('messages.stock_ledger_date') ?? 'Date',
            'C' => __('messages.title') ?? 'Title',
            'D' => __('messages.category') ?? 'Category',
            'E' => __('messages.subtotal') ?? 'Amount (MMK)',
            'F' => __('messages.reports_payment_method') ?? 'Payment Method',
            'G' => __('messages.expense_paid_to') ?? 'Paid To',
            'H' => __('messages.expense_reference_no') ?? 'Reference No',
            'I' => __('messages.expense_recorded_by') ?? 'Recorded By',
            'J' => __('messages.notes') ?? 'Notes',
        ];

        foreach ($headers as $col => $title) {
            $sheet->setCellValue("{$col}{$row}", $title);
        }

        $sheet->getStyle("A{$row}:J{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '059669'], // Emerald 600
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getStyle("E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getRowDimension($row)->setRowHeight(22);

        $row++;

        foreach ($expenses as $exp) {
            $sheet->setCellValue("A{$row}", $exp->expense_number);
            $sheet->setCellValue("B{$row}", $exp->expense_date?->format('d/m/Y'));
            $sheet->setCellValue("C{$row}", $exp->title);
            $sheet->setCellValue("D{$row}", $exp->category?->name ?? '—');
            $sheet->setCellValue("E{$row}", (float) $exp->amount);
            $sheet->setCellValue("F{$row}", strtoupper($exp->payment_method));
            $sheet->setCellValue("G{$row}", $exp->paid_to ?? '-');
            $sheet->setCellValue("H{$row}", $exp->reference_no ?? '-');
            $sheet->setCellValue("I{$row}", $exp->recorder?->name ?? '-');
            $sheet->setCellValue("J{$row}", $exp->notes ?? '');

            $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('#,##0.00');

            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:J{$row}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
                ]);
            }

            $row++;
        }

        // Totals Row
        $sheet->setCellValue("A{$row}", __('messages.total'));
        $sheet->setCellValue("E{$row}", $totalSum);

        $sheet->getStyle("A{$row}:J{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '065F46']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'ECFDF5']],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '10B981']],
                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => '10B981']],
            ],
        ]);
        $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('#,##0.00');

        // Auto-fit columns
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Export Expenses as CSV with UTF-8 BOM.
     */
    private function exportCsv(Store $store, $expenses): StreamedResponse
    {
        $filename = 'expenses_' . $store->slug . '_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($expenses) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Voucher No',
                'Date',
                'Title',
                'Category',
                'Category Code',
                'Amount (MMK)',
                'Payment Method',
                'Paid To',
                'Reference No',
                'Recorded By',
                'Notes',
            ]);

            foreach ($expenses as $exp) {
                fputcsv($handle, [
                    $exp->expense_number,
                    $exp->expense_date?->format('Y-m-d'),
                    $exp->title,
                    $exp->category?->name ?? '—',
                    $exp->category?->code ?? '—',
                    number_format((float) $exp->amount, 2, '.', ''),
                    strtoupper($exp->payment_method),
                    $exp->paid_to ?? '',
                    $exp->reference_no ?? '',
                    $exp->recorder?->name ?? '',
                    $exp->notes ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Resolve date presets for expenses.
     */
    protected function resolveDateRange(Request $request): array
    {
        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->input('date_from'))
            : ($request->filled('expense_date_from') ? Carbon::parse($request->input('expense_date_from')) : null);

        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->input('date_to'))
            : ($request->filled('expense_date_to') ? Carbon::parse($request->input('expense_date_to')) : null);

        if ($dateFrom || $dateTo) {
            return [$dateFrom, $dateTo, 'custom'];
        }

        $preset = $request->query('preset');
        $now = today();

        if ($preset && $preset !== 'all' && $preset !== 'custom') {
            return match ($preset) {
                'today' => [$now->copy(), $now->copy(), 'today'],
                'yesterday' => [$now->copy()->subDay(), $now->copy()->subDay(), 'yesterday'],
                'this_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek(), 'this_week'],
                'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth(), 'this_month'],
                'last_month' => [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth(), 'last_month'],
                'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear(), 'this_year'],
                default => [null, null, 'all'],
            };
        }

        return [null, null, 'all'];
    }
}
