<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\POS\Services\FinancialTransactionService;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CashBankTransactionController extends Controller
{
    public function __construct(
        protected FinancialTransactionService $transactionService
    ) {
    }

    /**
     * Display the Cash & Bank Transactions Register dashboard.
     */
    public function index(StoreContext $context, Request $request): View
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $filters = [
            'account_id' => $request->input('account_id'),
            'type' => $request->input('type'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'search' => $request->input('search'),
            'preset' => $request->input('preset', 'this_month'),
        ];

        // Apply date preset if dates are not explicitly supplied
        if (empty($filters['date_from']) && empty($filters['date_to'])) {
            $now = now();
            switch ($filters['preset']) {
                case 'today':
                    $filters['date_from'] = $now->format('Y-m-d');
                    $filters['date_to'] = $now->format('Y-m-d');
                    break;
                case 'yesterday':
                    $y = $now->copy()->subDay();
                    $filters['date_from'] = $y->format('Y-m-d');
                    $filters['date_to'] = $y->format('Y-m-d');
                    break;
                case '7days':
                    $filters['date_from'] = $now->copy()->subDays(6)->format('Y-m-d');
                    $filters['date_to'] = $now->format('Y-m-d');
                    break;
                case '30days':
                    $filters['date_from'] = $now->copy()->subDays(29)->format('Y-m-d');
                    $filters['date_to'] = $now->format('Y-m-d');
                    break;
                case 'this_month':
                    $filters['date_from'] = $now->copy()->startOfMonth()->format('Y-m-d');
                    $filters['date_to'] = $now->copy()->endOfMonth()->format('Y-m-d');
                    break;
                case 'last_month':
                    $lm = $now->copy()->subMonth();
                    $filters['date_from'] = $lm->copy()->startOfMonth()->format('Y-m-d');
                    $filters['date_to'] = $lm->copy()->endOfMonth()->format('Y-m-d');
                    break;
                case 'all':
                default:
                    // No date filter
                    break;
            }
        }

        $perPage = (int) $request->input('per_page', 25);
        if (!in_array($perPage, [15, 25, 50, 100], true)) {
            $perPage = 25;
        }

        $stats = $this->transactionService->getStatistics($store, $filters['date_from'] ?? null, $filters['date_to'] ?? null);
        $accounts = $this->transactionService->getAccounts($store);
        $transactions = $this->transactionService->listTransactions($store, $filters, $perPage);
        $transactions->appends($request->query());

        $sortOptions = [
            'newest' => __('messages.sort_newest') ?? 'Newest First',
            'oldest' => __('messages.sort_oldest') ?? 'Oldest First',
        ];
        $exportBaseUrl = route('store.admin.transactions.export', ['store_slug' => $store->slug]);

        $toolbarFilters = [
            'type' => [
                'label' => __('messages.type'),
                'options' => [
                    'deposit' => __('messages.transactions_type_deposit'),
                    'withdrawal' => __('messages.transactions_type_withdrawal'),
                    'transfer' => __('messages.transactions_type_transfer'),
                ],
            ],
            'account_id' => [
                'label' => __('messages.transactions_all_accounts'),
                'options' => $accounts->pluck('name', 'id')->toArray(),
            ],
        ];

        return view('admin.transactions.index', compact(
            'store',
            'stats',
            'accounts',
            'transactions',
            'filters',
            'toolbarFilters',
            'perPage',
            'sortOptions',
            'exportBaseUrl'
        ));
    }

    /**
     * Record a Deposit (Cash In).
     */
    public function deposit(StoreContext $context, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $validated = $request->validate([
            'to_account_id' => 'required|exists:financial_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'category' => 'nullable|string|max:100',
            'payer_or_payee' => 'nullable|string|max:150',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'transaction_date' => 'nullable|date',
        ]);

        $this->transactionService->recordDeposit($store, $validated, $request->user());

        return redirect()
            ->route('store.admin.transactions.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.transactions_deposit_success'));
    }

    /**
     * Record a Withdrawal (Cash Out).
     */
    public function withdraw(StoreContext $context, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $validated = $request->validate([
            'from_account_id' => 'required|exists:financial_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'category' => 'nullable|string|max:100',
            'payer_or_payee' => 'nullable|string|max:150',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'transaction_date' => 'nullable|date',
        ]);

        $this->transactionService->recordWithdrawal($store, $validated, $request->user());

        return redirect()
            ->route('store.admin.transactions.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.transactions_withdraw_success'));
    }

    /**
     * Record an Internal Fund Transfer.
     */
    public function transfer(StoreContext $context, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $validated = $request->validate([
            'from_account_id' => 'required|exists:financial_accounts,id',
            'to_account_id' => 'required|exists:financial_accounts,id|different:from_account_id',
            'amount' => 'required|numeric|min:0.01',
            'fee' => 'nullable|numeric|min:0',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'transaction_date' => 'nullable|date',
        ]);

        $this->transactionService->recordTransfer($store, $validated, $request->user());

        return redirect()
            ->route('store.admin.transactions.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.transactions_transfer_success'));
    }

    /**
     * Create a new Financial Account.
     */
    public function storeAccount(StoreContext $context, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'account_type' => 'required|in:cash,mobile_wallet,bank_account,other',
            'account_number' => 'nullable|string|max:80',
            'account_holder' => 'nullable|string|max:120',
            'opening_balance' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $this->transactionService->createAccount($store, $validated, $request->user());

        return redirect()
            ->route('store.admin.transactions.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.transactions_account_created'));
    }

    /**
     * Export transactions to CSV or XLSX.
     */
    public function export(StoreContext $context, Request $request): BinaryFileResponse|StreamedResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $filters = [
            'account_id' => $request->input('account_id'),
            'type' => $request->input('type'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'search' => $request->input('search'),
            'preset' => $request->input('preset'),
        ];

        // Apply date preset if dates are not explicitly supplied
        if (empty($filters['date_from']) && empty($filters['date_to']) && !empty($filters['preset']) && $filters['preset'] !== 'all') {
            $now = now();
            switch ($filters['preset']) {
                case 'today':
                    $filters['date_from'] = $now->format('Y-m-d');
                    $filters['date_to'] = $now->format('Y-m-d');
                    break;
                case 'yesterday':
                    $y = $now->copy()->subDay();
                    $filters['date_from'] = $y->format('Y-m-d');
                    $filters['date_to'] = $y->format('Y-m-d');
                    break;
                case '7days':
                    $filters['date_from'] = $now->copy()->subDays(6)->format('Y-m-d');
                    $filters['date_to'] = $now->format('Y-m-d');
                    break;
                case '30days':
                    $filters['date_from'] = $now->copy()->subDays(29)->format('Y-m-d');
                    $filters['date_to'] = $now->format('Y-m-d');
                    break;
                case 'this_month':
                    $filters['date_from'] = $now->copy()->startOfMonth()->format('Y-m-d');
                    $filters['date_to'] = $now->copy()->endOfMonth()->format('Y-m-d');
                    break;
                case 'last_month':
                    $lm = $now->copy()->subMonth();
                    $filters['date_from'] = $lm->copy()->startOfMonth()->format('Y-m-d');
                    $filters['date_to'] = $lm->copy()->endOfMonth()->format('Y-m-d');
                    break;
            }
        }

        $format = strtolower((string) $request->input('format', 'csv'));
        if ($format === 'xlsx') {
            return $this->exportXlsx($store, $filters);
        }

        return $this->transactionService->exportCsv($store, $filters);
    }

    /**
     * Generate Styled Excel (.xlsx) file using PhpSpreadsheet.
     */
    protected function exportXlsx($store, array $filters): BinaryFileResponse
    {
        $query = FinancialTransaction::where('store_id', $store->id)
            ->with(['fromAccount', 'toAccount', 'recorder']);

        if (!empty($filters['account_id'])) {
            $accId = (int) $filters['account_id'];
            $query->where(function ($q) use ($accId) {
                $q->where('from_account_id', $accId)
                    ->orWhere('to_account_id', $accId);
            });
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('transaction_date', '>=', \Carbon\Carbon::parse($filters['date_from'])->startOfDay());
        }

        if (!empty($filters['date_to'])) {
            $query->where('transaction_date', '<=', \Carbon\Carbon::parse($filters['date_to'])->endOfDay());
        }

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                    ->orWhere('reference_no', 'like', "%{$search}%")
                    ->orWhere('payer_or_payee', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $transactions = $query->latest('transaction_date')->latest('id')->get();
        $filename = 'financial-transactions-' . ($store->slug ?? 'store') . '-' . date('Ymd-His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'datapos_trans_');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Transactions');

        $headers = [
            '#',
            'Transaction #',
            'Date & Time',
            'Type',
            'From Account',
            'To Account',
            'Category / Purpose',
            'Amount',
            'Fee',
            'Reference / Slip No',
            'Payer / Payee',
            'Recorded By',
            'Notes',
        ];
        $sheet->fromArray([$headers], null, 'A1');

        $sheet->getStyle('A1:M1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5'], // Indigo-600
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(26);

        $rowNumber = 2;
        $totalAmount = 0;
        $totalFee = 0;

        foreach ($transactions as $index => $t) {
            $amount = (float) $t->amount;
            $fee = (float) $t->fee;
            $totalAmount += $amount;
            $totalFee += $fee;

            $sheet->fromArray([[
                $index + 1,
                $t->transaction_number,
                $t->transaction_date ? $t->transaction_date->format('Y-m-d H:i') : '',
                strtoupper($t->type),
                $t->fromAccount?->name ?? '-',
                $t->toAccount?->name ?? '-',
                $t->category ?? '-',
                $amount,
                $fee,
                $t->reference_no ?? '-',
                $t->payer_or_payee ?? '-',
                $t->recorder?->name ?? '-',
                $t->notes ?? '',
            ]], null, 'A' . $rowNumber);

            $rowNumber++;
        }

        // Summary Total Row
        if ($transactions->count() > 0) {
            $sheet->setCellValue('A' . $rowNumber, 'TOTAL');
            $sheet->mergeCells("A{$rowNumber}:G{$rowNumber}");
            $sheet->setCellValue('H' . $rowNumber, $totalAmount);
            $sheet->setCellValue('I' . $rowNumber, $totalFee);

            $sheet->getStyle("A{$rowNumber}:M{$rowNumber}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 11],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F1F5F9'],
                ],
                'borders' => [
                    'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']],
                    'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => '94A3B8']],
                ],
            ]);
            $sheet->getStyle('A' . $rowNumber)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        // Auto-fit columns
        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Printable voucher view.
     */
    public function printVoucher(StoreContext $context, string $store_slug, int|string|FinancialTransaction $transaction): View
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        if (!($transaction instanceof FinancialTransaction)) {
            $transaction = FinancialTransaction::where('store_id', $store->id)->findOrFail($transaction);
        } elseif ($transaction->store_id !== $store->id) {
            abort(404);
        }

        $transaction->load(['fromAccount', 'toAccount', 'recorder']);

        return view('admin.transactions.voucher', compact('store', 'transaction'));
    }
}
