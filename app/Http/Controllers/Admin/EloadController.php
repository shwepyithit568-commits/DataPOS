<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EloadAccount;
use App\Models\EloadTransaction;
use App\Models\Store;
use App\POS\Services\EloadService;
use App\Services\StoreContext;
use Illuminate\Http\JsonResponse;
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

class EloadController extends Controller
{
    public function __construct(
        protected EloadService $eloadService
    ) {
    }

    /**
     * E-Load Dashboard & Register Index.
     */
    public function index(Request $request, string $store_slug, StoreContext $context): View
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $perPage = $request->input('per_page', 25);
        if ($perPage === 'all') {
            $perPage = 100000;
        } else {
            $perPage = (int) $perPage;
            if (! in_array($perPage, [20, 25, 50, 100, 200, 100000], true)) {
                $perPage = 25;
            }
        }

        $stats = $this->eloadService->getSummaryStats($store);
        $transactions = $this->eloadService->getTransactions($store, $request->all(), $perPage);
        $accounts = $this->eloadService->getAccounts($store);

        $exportUrl = route('store.admin.eload.export', array_merge(
            ['store_slug' => $store->slug],
            request()->only(['search', 'operator', 'type', 'payment_method', 'status', 'occurred_at_from', 'occurred_at_to', 'sort'])
        ));

        return view('admin.eload.index', compact('store', 'stats', 'transactions', 'accounts', 'exportUrl'));
    }

    /**
     * Export E-Load transactions to XLSX or CSV.
     */
    public function export(Request $request, string $store_slug, StoreContext $context): BinaryFileResponse|StreamedResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $format = strtolower((string) $request->input('format', 'xlsx'));
        $filters = $request->all();

        $query = EloadTransaction::where('store_id', $store->id)
            ->with(['account', 'cashier']);

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('phone_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('ref_no', 'like', "%{$search}%")
                  ->orWhere('package_name', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['operator'])) {
            $query->where('operator', strtolower($filters['operator']));
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['occurred_at_from'])) {
            $query->whereDate('occurred_at', '>=', $filters['occurred_at_from']);
        }
        if (!empty($filters['occurred_at_to'])) {
            $query->whereDate('occurred_at', '<=', $filters['occurred_at_to']);
        }

        $sort = $filters['sort'] ?? 'newest';
        match ($sort) {
            'oldest'      => $query->orderBy('occurred_at', 'asc')->orderBy('id', 'asc'),
            'amount_desc' => $query->orderBy('amount', 'desc'),
            'amount_asc'  => $query->orderBy('amount', 'asc'),
            'profit_desc' => $query->orderBy('profit', 'desc'),
            default       => $query->orderBy('occurred_at', 'desc')->orderBy('id', 'desc'),
        };

        $transactions = $query->get();

        if ($format === 'csv') {
            return $this->exportCsv($store, $transactions);
        }

        return $this->exportXlsx($store, $transactions);
    }

    private function exportCsv(Store $store, $transactions): StreamedResponse
    {
        $filename = 'eload_' . $store->slug . '_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->streamDownload(function () use ($transactions) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF"); // UTF-8 BOM

            fputcsv($stream, [
                '#',
                'Date & Time',
                'Ref No',
                'Operator',
                'Phone Number',
                'Customer',
                'Type',
                'Package / Description',
                'Amount (MMK)',
                'Cost (MMK)',
                'Profit (MMK)',
                'Payment Method',
                'Status',
                'Cashier',
            ]);

            foreach ($transactions as $index => $tx) {
                fputcsv($stream, [
                    $index + 1,
                    $tx->occurred_at?->format('d/m/Y H:i') ?? '',
                    $tx->ref_no,
                    strtoupper($tx->operator),
                    $tx->phone_number,
                    $tx->customer_name ?: 'Walk-in',
                    $tx->typeLabel(),
                    $tx->package_name ?: 'Top-up',
                    (float) $tx->amount,
                    (float) $tx->cost,
                    (float) $tx->profit,
                    strtoupper($tx->payment_method),
                    ucfirst($tx->status),
                    $tx->cashier?->name ?? '',
                ]);
            }

            fclose($stream);
        }, $filename, $headers);
    }

    private function exportXlsx(Store $store, $transactions): BinaryFileResponse
    {
        $filename = 'eload_' . $store->slug . '_' . now()->format('Ymd_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'datapos_eload_');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('E-Load Transactions');

        // 1. Title Block
        $sheet->setCellValue('A1', $store->name . ' - E-Load & Mobile Top-up Report');
        $sheet->setCellValue('A2', 'Export Date: ' . now()->format('d/m/Y h:i A') . ' | Total Transactions: ' . $transactions->count() . ' | Total Volume: Ks ' . number_format((float) $transactions->sum('amount')) . ' | Total Profit: Ks ' . number_format((float) $transactions->sum('profit')));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('0284C7'); // Sky-600
        $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB('64748B');

        // 2. Table Headers
        $headers = [
            'A4' => '#',
            'B4' => 'Date & Time',
            'C4' => 'Ref No',
            'D4' => 'Operator',
            'E4' => 'Phone Number',
            'F4' => 'Customer',
            'G4' => 'Type',
            'H4' => 'Package / Description',
            'I4' => 'Amount (MMK)',
            'J4' => 'Cost (MMK)',
            'K4' => 'Profit (MMK)',
            'L4' => 'Payment Method',
            'M4' => 'Status',
            'N4' => 'Cashier',
        ];

        foreach ($headers as $cell => $headerText) {
            $sheet->setCellValue($cell, $headerText);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0284C7']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BAE6FD']]],
        ];
        $sheet->getStyle('A4:N4')->applyFromArray($headerStyle);
        $sheet->getRowDimension(4)->setRowHeight(24);

        // 3. Data Rows
        $row = 5;
        foreach ($transactions as $index => $tx) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $tx->occurred_at?->format('d/m/Y H:i') ?? '—');
            $sheet->setCellValue('C' . $row, $tx->ref_no);
            $sheet->setCellValue('D' . $row, strtoupper($tx->operator));
            $sheet->setCellValue('E' . $row, $tx->phone_number);
            $sheet->setCellValue('F' . $row, $tx->customer_name ?: 'Walk-in');
            $sheet->setCellValue('G' . $row, $tx->typeLabel());
            $sheet->setCellValue('H' . $row, $tx->package_name ?: 'Top-up');
            $sheet->setCellValue('I' . $row, (float) $tx->amount);
            $sheet->setCellValue('J' . $row, (float) $tx->cost);
            $sheet->setCellValue('K' . $row, (float) $tx->profit);
            $sheet->setCellValue('L' . $row, strtoupper($tx->payment_method));
            $sheet->setCellValue('M' . $row, ucfirst($tx->status));
            $sheet->setCellValue('N' . $row, $tx->cashier?->name ?? '—');

            // Row Zebra Striping
            if ($index % 2 === 1) {
                $sheet->getStyle('A' . $row . ':N' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }

            // Alignments & Number formats
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C' . $row)->getFont()->setBold(true)->getColor()->setRGB('0369A1');
            $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('I' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('I' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('J' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('J' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('K' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('K' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('K' . $row)->getFont()->setBold(true)->getColor()->setRGB('059669'); // Emerald
            $sheet->getStyle('L' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('M' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->getStyle('A' . $row . ':N' . $row)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E2E8F0');

            $row++;
        }

        // 4. Totals Footer
        if ($transactions->isNotEmpty()) {
            $sheet->setCellValue('A' . $row, 'TOTAL');
            $sheet->mergeCells('A' . $row . ':H' . $row);
            $sheet->setCellValue('I' . $row, (float) $transactions->sum('amount'));
            $sheet->setCellValue('J' . $row, (float) $transactions->sum('cost'));
            $sheet->setCellValue('K' . $row, (float) $transactions->sum('profit'));

            $footerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => '0F172A'], 'size' => 10],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0F2FE']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_RIGHT],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BAE6FD']]],
            ];
            $sheet->getStyle('A' . $row . ':N' . $row)->applyFromArray($footerStyle);
            $sheet->getStyle('I' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('J' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('K' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        }

        // 5. Auto-size columns
        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Store a new E-Load transaction.
     */
    public function store(Request $request, string $store_slug, StoreContext $context): JsonResponse|RedirectResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $validated = $request->validate([
            'operator'         => ['required', 'string', 'in:mpt,atom,ooredoo,mytel,other'],
            'phone_number'     => ['required', 'string', 'min:5', 'max:50'],
            'amount'           => ['required', 'numeric', 'min:100', 'max:50000000'],
            'type'             => ['nullable', 'string', 'in:topup,data_pack,pin_code,sim_card,bill_payment'],
            'package_name'     => ['nullable', 'string', 'max:150'],
            'customer_name'    => ['nullable', 'string', 'max:100'],
            'payment_method'   => ['nullable', 'string', 'in:cash,kpay,wavepay,cbpay,ayapay,other'],
            'eload_account_id' => ['nullable', 'integer', \Illuminate\Validation\Rule::exists('eload_accounts', 'id')->where('store_id', $store->id)],
            'cost'             => ['nullable', 'numeric', 'min:0'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes'            => ['nullable', 'string', 'max:500'],
            'occurred_at'      => ['nullable', 'date'],
        ]);

        $transaction = $this->eloadService->createTransaction($store, $validated, $request->user());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'     => true,
                'message'     => __('messages.eload_success_msg', ['amount' => number_format((float) $transaction->amount), 'phone' => $transaction->phone_number]),
                'transaction' => $transaction,
            ]);
        }

        return back()->with('success', __('messages.eload_success_msg', [
            'amount' => number_format((float) $transaction->amount),
            'phone'  => $transaction->phone_number,
        ]));
    }

    /**
     * Refill operator float balance.
     */
    public function refill(Request $request, string $store_slug, StoreContext $context): JsonResponse|RedirectResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $validated = $request->validate([
            'eload_account_id' => ['required', 'integer', \Illuminate\Validation\Rule::exists('eload_accounts', 'id')->where('store_id', $store->id)],
            'amount'           => ['required', 'numeric', 'min:100', 'max:50000000'],
            'notes'            => ['nullable', 'string', 'max:255'],
        ]);

        $account = EloadAccount::where('store_id', $store->id)->findOrFail($validated['eload_account_id']);
        $this->eloadService->refillAccount($account, (float) $validated['amount'], $validated['notes'] ?? null);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('messages.eload_refill_success', ['operator' => strtoupper($account->operator), 'amount' => number_format($validated['amount'])]),
            ]);
        }

        return back()->with('success', __('messages.eload_refill_success', [
            'operator' => strtoupper($account->operator),
            'amount'   => number_format($validated['amount']),
        ]));
    }

    /**
     * Create or update an operator account.
     */
    public function saveAccount(Request $request, string $store_slug, StoreContext $context): JsonResponse|RedirectResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $validated = $request->validate([
            'id'               => ['nullable', 'integer'],
            'operator'         => ['required', 'string', 'in:mpt,atom,ooredoo,mytel,other'],
            'name'             => ['required', 'string', 'max:100'],
            'phone_number'     => ['nullable', 'string', 'max:50'],
            'balance'          => ['nullable', 'numeric', 'min:0'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active'        => ['nullable', 'boolean'],
        ]);

        $account = $this->eloadService->saveAccount($store, $validated, $request->input('id'));

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'account' => $account,
                'message' => 'Operator account saved successfully.',
            ]);
        }

        return back()->with('success', 'Operator account saved successfully.');
    }

    /**
     * Delete an operator account.
     */
    public function deleteAccount(Request $request, string $store_slug, int $id, StoreContext $context): JsonResponse|RedirectResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $account = EloadAccount::where('store_id', $store->id)->findOrFail($id);
        $this->eloadService->deleteAccount($account);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Operator account removed.',
            ]);
        }

        return back()->with('success', 'Operator account removed.');
    }

    /**
     * Update transaction status (e.g. refund / void).
     */
    public function updateStatus(Request $request, string $store_slug, int $id, StoreContext $context): JsonResponse|RedirectResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:completed,pending,failed,refunded'],
        ]);

        $transaction = EloadTransaction::where('store_id', $store->id)->findOrFail($id);
        $this->eloadService->updateStatus($transaction, $validated['status']);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Transaction status updated.',
            ]);
        }

        return back()->with('success', 'Transaction status updated.');
    }

    /**
     * Render printable thermal receipt slip.
     */
    public function printSlip(Request $request, string $store_slug, int $id, StoreContext $context): View
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $transaction = EloadTransaction::where('store_id', $store->id)
            ->with(['account', 'cashier'])
            ->findOrFail($id);

        return view('admin.eload._slip', compact('store', 'transaction'));
    }
}
