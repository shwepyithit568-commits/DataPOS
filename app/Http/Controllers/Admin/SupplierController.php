<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\ImportHistory;
use App\Services\SupplierImportService;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Supplier master data — full CRUD management page.
 */
class SupplierController extends Controller
{
    public function index(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();

        $query = Supplier::where('store_id', $store->id);

        // Search: name, phone, contact_person, email
        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('phone', 'like', '%' . $search . '%')
                  ->orWhere('contact_person', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        // Filter: has outstanding balance
        if ($request->filled('has_balance')) {
            if ($request->has_balance === 'yes') {
                $query->whereRaw('(total_credit - total_repaid) > 0');
            } elseif ($request->has_balance === 'no') {
                $query->whereRaw('(total_credit - total_repaid) <= 0');
            }
        }

        // Sorting
        $sort = $request->get('sort', 'newest');
        match ($sort) {
            'oldest'        => $query->oldest(),
            'name_asc'      => $query->orderBy('name', 'asc'),
            'name_desc'     => $query->orderBy('name', 'desc'),
            'most_owed'     => $query->orderByRaw('(total_credit - total_repaid) DESC'),
            default         => $query->latest(),
        };

        $suppliers = $query->paginate(25)->withQueryString();
        $totalCount = $suppliers->total();

        return view('admin.suppliers.index', compact('store', 'suppliers', 'totalCount'));
    }

    public function store(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        $validated = $request->validate([
            'name'           => ['bail', 'required', 'string', 'max:100', $this->uniqueNameRule($store->id)],
            'phone'          => ['nullable', 'string', 'max:32'],
            'email'          => ['nullable', 'email', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'address'        => ['nullable', 'string', 'max:255'],
            'notes'          => ['nullable', 'string', 'max:1000'],
        ]);

        $supplier = Supplier::create([
            'store_id'       => $store->id,
            'name'           => trim($validated['name']),
            'phone'          => $validated['phone'] ?? null,
            'email'          => $validated['email'] ?? null,
            'contact_person' => $validated['contact_person'] ?? null,
            'address'        => $validated['address'] ?? null,
            'notes'          => $validated['notes'] ?? null,
        ]);

        return back()
            ->with('success', __('messages.supplier_created'))
            ->with('highlight_supplier', $supplier->id);
    }

    public function edit(string $store_slug, Supplier $supplier, StoreContext $context): View
    {
        $store = $context->getStore();

        if ($supplier->store_id !== $store->id) {
            abort(403, 'Unauthorized supplier access.');
        }

        return view('admin.suppliers.edit', compact('store', 'supplier'));
    }

    public function update(Request $request, string $store_slug, Supplier $supplier, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        if ($supplier->store_id !== $store->id) {
            abort(403, 'Unauthorized supplier access.');
        }

        $validated = $request->validate([
            'name'           => ['bail', 'required', 'string', 'max:100', $this->uniqueNameRule($store->id, $supplier->id)],
            'phone'          => ['nullable', 'string', 'max:32'],
            'email'          => ['nullable', 'email', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'address'        => ['nullable', 'string', 'max:255'],
            'notes'          => ['nullable', 'string', 'max:1000'],
        ]);

        $supplier->update([
            'name'           => trim($validated['name']),
            'phone'          => $validated['phone'] ?? null,
            'email'          => $validated['email'] ?? null,
            'contact_person' => $validated['contact_person'] ?? null,
            'address'        => $validated['address'] ?? null,
            'notes'          => $validated['notes'] ?? null,
        ]);

        return redirect(url('/store/' . $store->slug . '/admin/suppliers'))
            ->with('success', __('messages.supplier_updated'));
    }

    public function destroy(string $store_slug, Supplier $supplier, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        if ($supplier->store_id !== $store->id) {
            abort(403, 'Unauthorized supplier access.');
        }

        // Safety guard: a supplier with purchase orders cannot be deleted
        $poCount = $supplier->purchaseOrders()->count();
        if ($poCount > 0) {
            return back()->withErrors([
                'supplier' => __('messages.supplier_delete_blocked', ['count' => $poCount]),
            ]);
        }

        $supplier->delete();

        return back()->with('success', __('messages.supplier_deleted'));
    }

    public function quickStore(Request $request, StoreContext $context): \Illuminate\Http\JsonResponse
    {
        $store = $context->getStore();

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        $supplier = Supplier::where('store_id', $store->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($validated['name']))])
            ->first();

        if (! $supplier) {
            $supplier = Supplier::create([
                'store_id' => $store->id,
                'name' => trim($validated['name']),
                'phone' => $validated['phone'] ?? null,
            ]);
        }

        return response()->json([
            'success' => true,
            'id' => $supplier->id,
            'name' => $supplier->name,
        ]);
    }
    /* ------------------------------------------------------------------ */
    /*  Import / Export                                                     */
    /* ------------------------------------------------------------------ */

    public function export(StoreContext $context): StreamedResponse
    {
        $store = $context->getStore();

        $suppliers = Supplier::where('store_id', $store->id)
            ->orderBy('name')
            ->get(['name', 'phone', 'email', 'contact_person', 'address', 'notes']);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="suppliers-' . now()->format('Ymd-His') . '.csv"',
        ];

        return response()->streamDownload(function () use ($suppliers) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "ï»¿");
            fputcsv($stream, ['Name', 'Phone', 'Email', 'Contact Person', 'Address', 'Notes']);

            foreach ($suppliers as $supplier) {
                fputcsv($stream, [
                    $supplier->name,
                    $supplier->phone ?? '',
                    $supplier->email ?? '',
                    $supplier->contact_person ?? '',
                    $supplier->address ?? '',
                    $supplier->notes ?? '',
                ]);
            }

            fclose($stream);
        }, 'suppliers-' . now()->format('Ymd-His') . '.csv', $headers);
    }

    public function importForm(StoreContext $context): View
    {
        $store = $context->getStore();
        $histories = ImportHistory::where('store_id', $store->id)
            ->where('type', 'suppliers')
            ->with('user')
            ->latest()
            ->take(10)
            ->get();

        return view('admin.suppliers.import', compact('store', 'histories'));
    }

    public function import(Request $request, StoreContext $context, SupplierImportService $importer): RedirectResponse
    {
        $store = $context->getStore();

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:5120'],
            'duplicate_strategy' => ['nullable', 'in:skip,update'],
        ]);

        $file = $request->file('file');
        $safeName = $this->safeImportFilename($file->getClientOriginalName());
        $storedPath = $file->storeAs(
            'imports/tmp',
            Str::uuid() . '-' . $safeName,
            'local'
        );

        try {
            $duplicateStrategy = $validated['duplicate_strategy'] ?? 'skip';
            $preview = $importer->preview(Storage::disk('local')->path($storedPath), $store, $duplicateStrategy);
            $token = Str::random(40);

            session()->put("imports.suppliers.{$token}", [
                'path' => $storedPath,
                'filename' => $safeName,
                'duplicate_strategy' => $duplicateStrategy,
            ]);

            return back()->with('import_preview', $preview + [
                'token' => $token,
                'filename' => $safeName,
                'duplicate_strategy' => $duplicateStrategy,
            ]);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            Storage::disk('local')->delete($storedPath);

            return back()->withErrors(['file' => $e->getMessage()]);
        }
    }

    public function confirmImport(Request $request, StoreContext $context, SupplierImportService $importer): RedirectResponse
    {
        $store = $context->getStore();

        $validated = $request->validate([
            'token' => ['required', 'string'],
            'duplicate_strategy' => ['required', 'in:skip,update'],
        ]);

        $sessionKey = "imports.suppliers.{$validated['token']}";
        $pendingImport = session()->pull($sessionKey);

        if (! $pendingImport || empty($pendingImport['path'])) {
            return back()->withErrors(['file' => 'Import preview expired. Please upload the file again.']);
        }

        $storedPath = $pendingImport['path'];

        try {
            $result = $importer->import(
                Storage::disk('local')->path($storedPath),
                $store,
                $request->user(),
                $pendingImport['filename'] ?? 'suppliers-import.csv',
                $validated['duplicate_strategy']
            );

            $summary = "Import Completed - Total: {$result['total']} | Imported: {$result['imported']} | Updated: {$result['updated']} | Skipped Duplicate: {$result['skipped_duplicate']} | Failed: {$result['failed']}";

            return back()->with('import_result', $result)->with('success', $summary);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        } finally {
            Storage::disk('local')->delete($storedPath);
        }
    }

    public function downloadImportTemplate(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="supplier-import-template.xlsx"',
        ];

        return response()->streamDownload(function () {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Suppliers');
            $sheet->fromArray([
                ['name', 'phone', 'email', 'contact_person', 'address', 'notes'],
                ['Mobile Hub Trading', '09-1234567', 'info@mobilehub.com', 'U Aung', 'Yangon, Myanmar', 'Main supplier for accessories'],
            ]);

            $instructionSheet = $spreadsheet->createSheet();
            $instructionSheet->setTitle('Instructions');
            $instructionSheet->fromArray([
                ['Instruction', 'Value'],
                ['Required columns', 'name'],
                ['Optional columns', 'phone, email, contact_person, address, notes'],
                ['Duplicate rule', 'Suppliers are matched by name (case-insensitive). Existing suppliers are skipped or updated depending on the chosen strategy.'],
                ['Store assignment', 'The system always uses the current admin store. Do not add store_id.'],
            ]);

            $spreadsheet->setActiveSheetIndex(0);
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'supplier-import-template.xlsx', $headers);
    }

    private function safeImportFilename(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $name = Str::slug(pathinfo($filename, PATHINFO_FILENAME)) ?: 'suppliers-import';

        return $name . '-' . now()->format('YmdHis') . '.' . $extension;
    }

    private function uniqueNameRule(int $storeId, ?int $ignoreId = null): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($storeId, $ignoreId) {
            $normalized = mb_strtolower(trim((string) $value));

            if ($normalized === '') {
                $fail(__('messages.supplier_name_required'));

                return;
            }

            $query = Supplier::where('store_id', $storeId)
                ->whereRaw('LOWER(TRIM(name)) = ?', [$normalized]);

            if ($ignoreId !== null) {
                $query->where('id', '!=', $ignoreId);
            }

            if ($query->exists()) {
                $fail(__('messages.supplier_name_taken'));
            }
        };
    }

    /**
     * Supplier aging report � shows outstanding debt broken into 30/60/90+ day buckets.
     */
    public function agingReport(StoreContext $context): \Illuminate\View\View
    {
        $store = $context->getStore();
        $today = now()->startOfDay();

        // Fetch suppliers with outstanding balances
        $suppliers = Supplier::where('store_id', $store->id)
            ->whereRaw('total_credit - total_repaid > 0')
            ->get();

        $agingData = [];

        foreach ($suppliers as $supplier) {
            // Get unpaid POs for this supplier, ordered by oldest first
            $unpaidPos = \App\POS\Models\PurchaseOrder::where('supplier_id', $supplier->id)
                ->where('status', 'received')
                ->whereRaw('remaining_balance > 0')
                ->orderBy('received_at')
                ->get();

            $buckets = [
                'current'   => 0,  // 0-30 days
                '31_60'     => 0,  // 31-60 days
                '61_90'     => 0,  // 61-90 days
                'over_90'   => 0,  // 90+ days
            ];

            $totalOutstanding = 0;

            foreach ($unpaidPos as $po) {
                $age = $today->diffInDays($po->received_at ?? $po->created_at);
                $amount = (float) $po->remaining_balance;

                if ($age <= 30) {
                    $buckets['current'] += $amount;
                } elseif ($age <= 60) {
                    $buckets['31_60'] += $amount;
                } elseif ($age <= 90) {
                    $buckets['61_90'] += $amount;
                } else {
                    $buckets['over_90'] += $amount;
                }

                $totalOutstanding += $amount;
            }

            if ($totalOutstanding > 0) {
                $agingData[] = [
                    'supplier'    => $supplier,
                    'buckets'     => $buckets,
                    'total'       => $totalOutstanding,
                    'po_count'    => $unpaidPos->count(),
                ];
            }
        }

        // Sort by total outstanding (highest first)
        usort($agingData, fn($a, $b) => $b['total'] <=> $a['total']);

        // Summary totals
        $summary = [
            'total_outstanding' => array_sum(array_column($agingData, 'total')),
            'total_current'     => array_sum(array_column($agingData, 'buckets.current')),
            'total_31_60'       => array_sum(array_column($agingData, 'buckets.31_60')),
            'total_61_90'       => array_sum(array_column($agingData, 'buckets.61_90')),
            'total_over_90'     => array_sum(array_column($agingData, 'buckets.over_90')),
            'supplier_count'    => count($agingData),
        ];

        return view('admin.suppliers.aging', compact('store', 'agingData', 'summary'));
    }
}
