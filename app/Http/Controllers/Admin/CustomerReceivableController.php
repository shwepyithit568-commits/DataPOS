<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\CustomerLedgerEntry;
use App\POS\Services\CustomerDebtService;
use App\Services\StoreContext;
use App\Support\ImageOptimizer;
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

class CustomerReceivableController extends Controller
{
    public function __construct(
        protected CustomerDebtService $debts,
    ) {
    }

    /**
     * Display receivables dashboard & customer debt listing.
     */
    public function index(StoreContext $context, Request $request): View
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $search = trim((string) $request->input('search', $request->input('q', '')));
        $filter = $request->input('filter', 'all');
        $perPage = $request->input('per_page', 25);
        $perPageCount = ($perPage === 'all' || (int) $perPage > 1000) ? 10000 : (int) $perPage;

        $summary = $this->debts->getReceivablesSummary($store);
        $customers = $this->debts->listCustomersWithBalancesPaginated($store, $search, $filter, $perPageCount);
        $customers->appends($request->except('page'));

        $exportUrl = route('store.admin.receivables.export', array_merge($context->getRouteParams(), request()->except(['page'])));

        return view('admin.receivables.index', compact('store', 'summary', 'customers', 'search', 'filter', 'exportUrl'));
    }

    /**
     * Export receivables as Excel (.xlsx) or CSV with UTF-8 BOM.
     */
    public function exportCsv(StoreContext $context, Request $request): BinaryFileResponse|StreamedResponse
    {
        return $this->export($context, $request);
    }

    /**
     * Export receivables as Excel (.xlsx) or CSV with UTF-8 BOM.
     */
    public function export(StoreContext $context, Request $request): BinaryFileResponse|StreamedResponse
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $search = trim((string) $request->input('search', $request->input('q', '')));
        $filter = $request->input('filter', 'all');

        $customers = $this->debts->listCustomersWithBalancesPaginated($store, $search, $filter, 10000);
        $format = strtolower((string) $request->query('format', 'xlsx'));

        if ($format === 'csv') {
            $filename = 'receivables-' . $store->slug . '-' . now()->format('Ymd-His') . '.csv';

            return response()->streamDownload(function () use ($store, $customers) {
                $stream = fopen('php://output', 'w');
                fwrite($stream, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

                fputcsv($stream, ['Customer Receivables & Debts', $this->csvCell($store->name)]);
                fputcsv($stream, ['Export Date', now()->format('Y-m-d H:i:s')]);
                fputcsv($stream, []);

                fputcsv($stream, [
                    '#',
                    'Customer Name',
                    'Phone',
                    'Total Incurred',
                    'Total Paid',
                    'Outstanding Balance',
                    'Status',
                    'Last Activity',
                ]);

                foreach ($customers as $idx => $cust) {
                    $bal = (float) $cust->balance;
                    $status = $bal > 0 ? 'Active Debt' : 'Settled';

                    fputcsv($stream, [
                        $idx + 1,
                        $this->csvCell($cust->name),
                        $this->csvCell($cust->phone ?? ''),
                        number_format((float) ($cust->total_debt_incurred ?? 0), 2, '.', ''),
                        number_format((float) ($cust->total_collected ?? 0), 2, '.', ''),
                        number_format($bal, 2, '.', ''),
                        $status,
                        $cust->last_activity ? \Carbon\Carbon::parse($cust->last_activity)->format('Y-m-d H:i') : '',
                    ]);
                }

                fclose($stream);
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]);
        }

        // PhpSpreadsheet XLSX export
        $filename = 'Receivables_' . $store->slug . '_' . now()->format('Ymd_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'datapos_receivables_');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Receivables');

        // Header Title Block
        $sheet->setCellValue('A1', $store->name . ' - ' . __('messages.receivables_title'));
        $sheet->setCellValue('A2', 'Export Date: ' . now()->format('d/m/Y h:i A') . ' | Total Count: ' . $customers->count());
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('059669'); // Emerald green
        $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB('64748B');

        // Table Columns
        $columns = [
            'A' => '#',
            'B' => 'Customer Name',
            'C' => 'Phone',
            'D' => 'Total Incurred',
            'E' => 'Total Paid',
            'F' => 'Outstanding Balance',
            'G' => 'Status',
            'H' => 'Last Activity',
        ];

        $headerRow = 4;
        foreach ($columns as $col => $title) {
            $sheet->setCellValue("{$col}{$headerRow}", $title);
        }

        // Style Table Header (Emerald Theme)
        $sheet->getStyle("A{$headerRow}:H{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '059669']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '047857']]],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(26);

        $row = 5;
        foreach ($customers as $idx => $cust) {
            $bal = (float) $cust->balance;
            $status = $bal > 0 ? 'Active Debt' : 'Settled';

            $sheet->setCellValue("A{$row}", $idx + 1);
            $sheet->setCellValue("B{$row}", $cust->name);
            $sheet->setCellValueExplicit("C{$row}", $cust->phone ?: '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("D{$row}", (float) ($cust->total_debt_incurred ?? 0));
            $sheet->setCellValue("E{$row}", (float) ($cust->total_collected ?? 0));
            $sheet->setCellValue("F{$row}", $bal);
            $sheet->setCellValue("G{$row}", $status);
            $sheet->setCellValue("H{$row}", $cust->last_activity ? \Carbon\Carbon::parse($cust->last_activity)->format('d/m/Y H:i') : '-');

            $sheet->getStyle("D{$row}:F{$row}")->getNumberFormat()->setFormatCode('#,##0.00');

            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:H{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
            }

            if ($bal > 0) {
                $sheet->getStyle("F{$row}")->getFont()->setBold(true)->getColor()->setRGB('E11D48'); // Rose
            }

            $sheet->getStyle("A{$row}:H{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_HAIR)->getColor()->setRGB('E2E8F0');
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getRowDimension($row)->setRowHeight(20);
            $row++;
        }

        foreach (array_keys($columns) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function csvCell(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (in_array(substr($value, 0, 1), ['=', '+', '-', '@'], true)) {
            return "'" . $value;
        }

        return $value;
    }

    /**
     * Display detailed customer ledger timeline & collection form.
     */
    public function show(StoreContext $context, string $store_slug, int $customer): View
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $customerUser = User::find($customer);
        if (! $customerUser) {
            abort(404, 'Customer not found.');
        }

        $hasStoreAssociation = $customerUser->stores()->where('stores.id', $store->id)->exists()
            || CustomerLedgerEntry::where('store_id', $store->id)->where('customer_id', $customerUser->id)->exists();

        if (! $hasStoreAssociation) {
            abort(404, 'Customer not found in this store.');
        }

        $balance = $this->debts->balanceFor($store->id, $customerUser->id);
        $history = $this->debts->history($store, $customerUser->id, 100);

        $totalIncurred = CustomerLedgerEntry::query()
            ->where('store_id', $store->id)
            ->where('customer_id', $customerUser->id)
            ->where('amount', '>', 0)
            ->sum('amount');

        $totalCollected = CustomerLedgerEntry::query()
            ->where('store_id', $store->id)
            ->where('customer_id', $customerUser->id)
            ->where('type', CustomerLedgerEntry::TYPE_COLLECTION)
            ->sum(\Illuminate\Support\Facades\DB::raw('ABS(amount)'));

        return view('admin.receivables.show', [
            'store' => $store,
            'customer' => $customerUser,
            'balance' => $balance,
            'history' => $history,
            'totalIncurred' => number_format((float) $totalIncurred, 2, '.', ''),
            'totalCollected' => number_format((float) $totalCollected, 2, '.', ''),
        ]);
    }

    /**
     * Record a debt collection payment from customer.
     */
    public function collect(Request $request, StoreContext $context, string $store_slug, int $customer): RedirectResponse
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $customerUser = User::find($customer);
        if (! $customerUser) {
            abort(404, 'Customer not found.');
        }

        $hasStoreAssociation = $customerUser->stores()->where('stores.id', $store->id)->exists()
            || CustomerLedgerEntry::where('store_id', $store->id)->where('customer_id', $customerUser->id)->exists();

        if (! $hasStoreAssociation) {
            abort(404, 'Customer not found in this store.');
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['nullable', 'string', 'in:cash,kpay,wave,bank,other'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:255'],
            'slip_image' => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp,pdf', 'max:5120'],
        ]);

        $outstanding = $this->debts->balanceFor($store->id, $customerUser->id);
        if (bccomp((string) $data['amount'], $outstanding, 2) > 0) {
            return back()->withInput()->with('error', __('messages.receivables_collect_exceeds_error', [
                'max' => number_format((float) $outstanding, 0),
            ]));
        }

        $slipPath = null;
        if ($request->hasFile('slip_image') && $request->file('slip_image')->isValid()) {
            $file = $request->file('slip_image');
            $ext = strtolower($file->getClientOriginalExtension());
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $slipPath = ImageOptimizer::store($file, "debt_slips/{$store->id}");
            } else {
                $slipPath = $file->store("debt_slips/{$store->id}", 'public');
            }
        }

        $note = trim(($data['payment_method'] ? strtoupper($data['payment_method']) . ' ' : '') .
            (($data['reference_no'] ?? null) ? "(Ref: {$data['reference_no']}) " : '') .
            ($data['notes'] ?? ''));

        try {
            $this->debts->collect(
                store: $store,
                customerId: $customerUser->id,
                amount: (string) $data['amount'],
                actor: $request->user(),
                notes: $note ?: 'Customer debt collection',
                slipImage: $slipPath,
            );
        } catch (InventoryException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('store.admin.receivables.show', ['store_slug' => $store->slug, 'customer' => $customerUser->id])
            ->with('success', __('messages.debt_collected') . ' — ' . format_currency((float) $data['amount'], $store));
    }

    /**
     * Render printable customer statement (A4 / 80mm).
     */
    public function statement(StoreContext $context, Request $request, string $store_slug, int $customer): View
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $customerUser = User::find($customer);
        if (! $customerUser) {
            abort(404, 'Customer not found.');
        }

        $hasStoreAssociation = $customerUser->stores()->where('stores.id', $store->id)->exists()
            || CustomerLedgerEntry::where('store_id', $store->id)->where('customer_id', $customerUser->id)->exists();

        if (! $hasStoreAssociation) {
            abort(404, 'Customer not found in this store.');
        }

        $balance = $this->debts->balanceFor($store->id, $customerUser->id);
        $history = $this->debts->history($store, $customerUser->id, 200);

        $format = $request->input('format', 'a4'); // 'a4' or 'thermal'

        return view('admin.receivables.statement', [
            'store' => $store,
            'customer' => $customerUser,
            'balance' => $balance,
            'history' => $history,
            'format' => $format,
        ]);
    }
}
