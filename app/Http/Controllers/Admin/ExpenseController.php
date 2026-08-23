<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\POS\Models\Expense;
use App\POS\Models\ExpenseCategory;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
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

        $search = trim((string) $request->query('search', ''));
        $categoryId = $request->query('category_id');
        $paymentMethod = $request->query('payment_method');
        $dateFrom = $request->query('expense_date_from', $request->query('date_from'));
        $dateTo = $request->query('expense_date_to', $request->query('date_to'));
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

        if (! empty($dateFrom)) {
            $query->whereDate('expense_date', '>=', $dateFrom);
        }

        if (! empty($dateTo)) {
            $query->whereDate('expense_date', '<=', $dateTo);
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
            'dateFrom',
            'dateTo',
            'sort'
        ));
    }

    public function store(Request $request, StoreContext $context, string $store_slug): RedirectResponse
    {
        $store = $context->getStore();
        $storeRouteParams = $context->getRouteParams();

        $validated = $request->validate([
            'title'               => ['required', 'string', 'max:255'],
            'amount'              => ['required', 'numeric', 'min:0.01', 'max:999999999999'],
            'expense_date'        => ['required', 'date'],
            'expense_category_id' => [
                'nullable',
                Rule::exists('expense_categories', 'id')->where('store_id', $store->id),
            ],
            'payment_method'      => ['required', 'string', 'max:50'],
            'paid_to'             => ['nullable', 'string', 'max:255'],
            'reference_no'        => ['nullable', 'string', 'max:100'],
            'notes'               => ['nullable', 'string', 'max:2000'],
            'attachment'          => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp,pdf', 'max:5120'], // 5MB max
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store("stores/{$store->id}/expenses", 'public');
        }

        $expenseNumber = Expense::generateExpenseNumber($store->id);

        Expense::create([
            'store_id'            => $store->id,
            'expense_category_id' => $validated['expense_category_id'] ?? null,
            'expense_number'      => $expenseNumber,
            'title'               => trim($validated['title']),
            'amount'              => (float) $validated['amount'],
            'expense_date'        => $validated['expense_date'],
            'payment_method'      => $validated['payment_method'],
            'paid_to'             => ! empty($validated['paid_to']) ? trim($validated['paid_to']) : null,
            'reference_no'        => ! empty($validated['reference_no']) ? trim($validated['reference_no']) : null,
            'notes'               => ! empty($validated['notes']) ? trim($validated['notes']) : null,
            'attachment_path'     => $attachmentPath,
            'recorded_by'         => $request->user()?->id,
        ]);

        return redirect()
            ->route('store.admin.expenses.index', $storeRouteParams)
            ->with('success', __('messages.expense_created_success'));
    }

    public function update(Request $request, StoreContext $context, string $store_slug, int|string $expense): RedirectResponse
    {
        $store = $context->getStore();
        $storeRouteParams = $context->getRouteParams();

        $expenseModel = Expense::where('store_id', $store->id)->where('id', $expense)->firstOrFail();

        $validated = $request->validate([
            'title'               => ['required', 'string', 'max:255'],
            'amount'              => ['required', 'numeric', 'min:0.01', 'max:999999999999'],
            'expense_date'        => ['required', 'date'],
            'expense_category_id' => [
                'nullable',
                Rule::exists('expense_categories', 'id')->where('store_id', $store->id),
            ],
            'payment_method'      => ['required', 'string', 'max:50'],
            'paid_to'             => ['nullable', 'string', 'max:255'],
            'reference_no'        => ['nullable', 'string', 'max:100'],
            'notes'               => ['nullable', 'string', 'max:2000'],
            'attachment'          => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp,pdf', 'max:5120'],
            'remove_attachment'   => ['nullable', 'boolean'],
        ]);

        $attachmentPath = $expenseModel->attachment_path;

        if ($request->boolean('remove_attachment') && $attachmentPath) {
            Storage::disk('public')->delete($attachmentPath);
            $attachmentPath = null;
        }

        if ($request->hasFile('attachment')) {
            if ($attachmentPath) {
                Storage::disk('public')->delete($attachmentPath);
            }
            $attachmentPath = $request->file('attachment')->store("stores/{$store->id}/expenses", 'public');
        }

        $expenseModel->update([
            'expense_category_id' => $validated['expense_category_id'] ?? null,
            'title'               => trim($validated['title']),
            'amount'              => (float) $validated['amount'],
            'expense_date'        => $validated['expense_date'],
            'payment_method'      => $validated['payment_method'],
            'paid_to'             => ! empty($validated['paid_to']) ? trim($validated['paid_to']) : null,
            'reference_no'        => ! empty($validated['reference_no']) ? trim($validated['reference_no']) : null,
            'notes'               => ! empty($validated['notes']) ? trim($validated['notes']) : null,
            'attachment_path'     => $attachmentPath,
        ]);

        return redirect()
            ->route('store.admin.expenses.index', $storeRouteParams)
            ->with('success', __('messages.expense_updated_success'));
    }

    public function destroy(StoreContext $context, string $store_slug, int|string $expense): RedirectResponse
    {
        $store = $context->getStore();
        $storeRouteParams = $context->getRouteParams();

        $expenseModel = Expense::where('store_id', $store->id)->where('id', $expense)->firstOrFail();

        if ($expenseModel->attachment_path) {
            Storage::disk('public')->delete($expenseModel->attachment_path);
        }

        $expenseModel->delete();

        return redirect()
            ->route('store.admin.expenses.index', $storeRouteParams)
            ->with('success', __('messages.expense_deleted_success'));
    }

    public function export(Request $request, StoreContext $context): StreamedResponse
    {
        $store = $context->getStore();

        $search = trim((string) $request->query('search', ''));
        $categoryId = $request->query('category_id');
        $paymentMethod = $request->query('payment_method');
        $dateFrom = $request->query('expense_date_from', $request->query('date_from'));
        $dateTo = $request->query('expense_date_to', $request->query('date_to'));

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

        if (! empty($dateFrom)) {
            $query->whereDate('expense_date', '>=', $dateFrom);
        }

        if (! empty($dateTo)) {
            $query->whereDate('expense_date', '<=', $dateTo);
        }

        $expenses = $query->orderBy('expense_date', 'desc')->orderBy('id', 'desc')->get();

        $filename = 'expenses_' . $store->slug . '_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($expenses) {
            $handle = fopen('php://output', 'w');
            // Add UTF-8 BOM for Excel compatibility with Myanmar unicode text
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
                    number_format($exp->amount, 2, '.', ''),
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
}
