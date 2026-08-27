<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\POS\Models\Expense;
use App\POS\Models\ExpenseCategory;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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

    public function destroy(Request $request, StoreContext $context, string $store_slug, int|string $expense): RedirectResponse
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
        $preset = $request->query('preset');
        $now = today();

        if ($preset) {
            return match ($preset) {
                'today' => [$now->copy(), $now->copy(), 'today'],
                'yesterday' => [$now->copy()->subDay(), $now->copy()->subDay(), 'yesterday'],
                'this_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek(), 'this_week'],
                'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth(), 'this_month'],
                'last_month' => [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth(), 'last_month'],
                'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear(), 'this_year'],
                'all' => [null, null, 'all'],
                default => [null, null, 'all'],
            };
        }

        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->input('date_from'))
            : ($request->filled('expense_date_from') ? Carbon::parse($request->input('expense_date_from')) : null);

        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->input('date_to'))
            : ($request->filled('expense_date_to') ? Carbon::parse($request->input('expense_date_to')) : null);

        if ($dateFrom || $dateTo) {
            return [$dateFrom, $dateTo, 'custom'];
        }

        return [null, null, 'all'];
    }
}
