<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImportHistory;
use App\Models\ProductMasterPreset;
use App\Models\Store;
use App\Services\MasterPresetImportService;
use App\Services\StoreContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductMasterPresetController extends Controller
{
    public function store(Request $request, string $store_slug, StoreContext $context): RedirectResponse|JsonResponse
    {
        $store = $context->getStore();

        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(['connector_spec', 'color', 'shelf_location', 'warranty', 'return_policy'])],
            'code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:5000'],
            'color_hex' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['store_id'] = $store->id;
        $validated['code'] = !empty($validated['code']) ? strtoupper(trim($validated['code'])) : null;
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);
        $validated['is_active'] = $request->boolean('is_active', true);

        // If code is provided for connector or color or shelf, enforce uniqueness
        if (!empty($validated['code'])) {
            $exists = ProductMasterPreset::where('store_id', $store->id)
                ->where('type', $validated['type'])
                ->where('code', $validated['code'])
                ->exists();

            if ($exists) {
                if ($request->wantsJson()) {
                    return response()->json(['error' => 'This code already exists for this type.'], 422);
                }
                return back()->withErrors(['code' => 'This code already exists for this type.'])->withInput();
            }
        }

        $preset = ProductMasterPreset::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'preset' => $preset,
                'message' => __('messages.preset_created'),
            ]);
        }

        $tab = match ($preset->type) {
            'connector_spec' => 'connectors',
            'color' => 'colors',
            'shelf_location' => 'shelves',
            'warranty' => 'warranties',
            'return_policy' => 'return-policies',
            default => 'categories',
        };

        return redirect()->to(route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => $tab]))
            ->with('success', __('messages.preset_created'));
    }

    public function update(Request $request, string $store_slug, ProductMasterPreset $masterPreset, StoreContext $context): RedirectResponse|JsonResponse
    {
        $store = $context->getStore();

        if ($masterPreset->store_id !== $store->id) {
            abort(403, 'Unauthorized preset access.');
        }

        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(['connector_spec', 'color', 'shelf_location', 'warranty', 'return_policy'])],
            'code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:5000'],
            'color_hex' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['code'] = !empty($validated['code']) ? strtoupper(trim($validated['code'])) : null;
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);
        $validated['is_active'] = $request->boolean('is_active', true);

        if (!empty($validated['code'])) {
            $exists = ProductMasterPreset::where('store_id', $store->id)
                ->where('type', $validated['type'])
                ->where('code', $validated['code'])
                ->where('id', '!=', $masterPreset->id)
                ->exists();

            if ($exists) {
                if ($request->wantsJson()) {
                    return response()->json(['error' => 'This code already exists for this type.'], 422);
                }
                return back()->withErrors(['code' => 'This code already exists for this type.'])->withInput();
            }
        }

        $masterPreset->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'preset' => $masterPreset,
                'message' => __('messages.preset_updated'),
            ]);
        }

        $tab = match ($masterPreset->type) {
            'connector_spec' => 'connectors',
            'color' => 'colors',
            'shelf_location' => 'shelves',
            'warranty' => 'warranties',
            'return_policy' => 'return-policies',
            default => 'categories',
        };

        return redirect()->to(route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => $tab]))
            ->with('success', __('messages.preset_updated'));
    }

    public function destroy(string $store_slug, ProductMasterPreset $masterPreset, StoreContext $context): RedirectResponse|JsonResponse
    {
        $store = $context->getStore();

        if ($masterPreset->store_id !== $store->id) {
            abort(403, 'Unauthorized preset access.');
        }

        $type = $masterPreset->type;
        $masterPreset->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('messages.preset_deleted'),
            ]);
        }

        $tab = match ($type) {
            'connector_spec' => 'connectors',
            'color' => 'colors',
            'shelf_location' => 'shelves',
            'warranty' => 'warranties',
            'return_policy' => 'return-policies',
            default => 'categories',
        };

        return redirect()->to(route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => $tab]))
            ->with('success', __('messages.preset_deleted'));
    }

    /**
     * Export Master Presets as Excel (.xlsx) or CSV (.csv).
     */
    public function export(Request $request, string $store_slug, StoreContext $context): BinaryFileResponse|StreamedResponse
    {
        $store = $context->getStore();
        $type = $request->query('type');
        $format = strtolower((string) $request->query('format', 'xlsx'));
        $search = trim((string) $request->query('search', ''));

        $query = ProductMasterPreset::where('store_id', $store->id);
        if (!empty($type)) {
            $query->where('type', $type);
        }
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $presets = $query->orderBy('type')->orderBy('sort_order')->orderBy('name')->get();
        $typeName = $type ? ucfirst(str_replace('_', ' ', $type)) : 'Master_Presets';

        if ($format === 'csv') {
            return $this->exportCsv($presets, $typeName);
        }

        return $this->exportXlsx($store, $presets, $typeName);
    }

    private function exportCsv($presets, string $typeName): StreamedResponse
    {
        $filename = $typeName . '-' . now()->format('Ymd-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->streamDownload(function () use ($presets) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF"); // UTF-8 BOM
            fputcsv($stream, ['Type', 'Code', 'Name', 'Color Hex', 'Content/Description', 'Sort Order', 'Is Active']);

            foreach ($presets as $p) {
                fputcsv($stream, [
                    $p->type,
                    $p->code ?? '',
                    $p->name,
                    $p->color_hex ?? '',
                    $p->content ?? '',
                    $p->sort_order,
                    $p->is_active ? '1' : '0',
                ]);
            }

            fclose($stream);
        }, $filename, $headers);
    }

    private function exportXlsx(Store $store, $presets, string $typeName): BinaryFileResponse
    {
        $filename = $typeName . '_' . $store->slug . '_' . now()->format('Ymd_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'datapos_preset_');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($typeName, 0, 31));

        // Title block
        $sheet->setCellValue('A1', $store->name . ' - ' . $typeName . ' Export');
        $sheet->setCellValue('A2', 'Export Date: ' . now()->format('d/m/Y h:i A') . ' | Total Count: ' . $presets->count());
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('4C1D95');
        $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB('64748B');

        $row = 4;
        $headers = [
            'A' => 'Type',
            'B' => 'Code',
            'C' => 'Name',
            'D' => 'Color Hex',
            'E' => 'Content/Description',
            'F' => 'Sort Order',
            'G' => 'Status',
        ];

        foreach ($headers as $col => $title) {
            $sheet->setCellValue("{$col}{$row}", $title);
        }

        $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6D28D9']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(24);

        $row++;
        foreach ($presets as $p) {
            $sheet->setCellValue("A{$row}", $p->type);
            $sheet->setCellValue("B{$row}", $p->code ?? '');
            $sheet->setCellValue("C{$row}", $p->name);
            $sheet->setCellValue("D{$row}", $p->color_hex ?? '');
            $sheet->setCellValue("E{$row}", $p->content ?? '');
            $sheet->setCellValue("F{$row}", (int) $p->sort_order);
            $sheet->setCellValue("G{$row}", $p->is_active ? 'Active' : 'Inactive');

            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
                ]);
            }
            $row++;
        }

        $lastRow = max(4, $row - 1);
        $sheet->getStyle("A4:G{$lastRow}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
        ]);

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Import form view for Master Presets.
     */
    public function importForm(Request $request, string $store_slug, StoreContext $context): View
    {
        $store = $context->getStore();
        $type = $request->query('type', 'connector_spec');

        $histories = ImportHistory::where('store_id', $store->id)
            ->where('type', 'master_presets')
            ->with('user')
            ->latest()
            ->take(10)
            ->get();

        return view('admin.master_data.import', compact('store', 'histories', 'type'));
    }

    /**
     * Preview import file.
     */
    public function import(Request $request, string $store_slug, StoreContext $context, MasterPresetImportService $importer): RedirectResponse
    {
        $store = $context->getStore();
        $type = $request->input('type');

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:5120'],
            'type' => ['nullable', 'string'],
            'duplicate_strategy' => ['nullable', 'in:skip,update'],
        ]);

        $file = $request->file('file');
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
        $storedPath = $file->storeAs('imports/tmp', Str::uuid()->toString() . '-' . $safeName, 'local');

        try {
            $duplicateStrategy = $validated['duplicate_strategy'] ?? 'skip';
            $preview = $importer->preview(Storage::disk('local')->path($storedPath), $store, $duplicateStrategy, $type);
            $token = Str::random(40);

            session()->put("imports.master_presets.{$token}", [
                'path' => $storedPath,
                'filename' => $safeName,
                'duplicate_strategy' => $duplicateStrategy,
                'type' => $type,
            ]);

            return back()->with('import_preview', $preview + [
                'token' => $token,
                'filename' => $safeName,
                'duplicate_strategy' => $duplicateStrategy,
                'type' => $type,
            ]);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            Storage::disk('local')->delete($storedPath);
            return back()->withErrors(['file' => $e->getMessage()]);
        }
    }

    /**
     * Confirm and execute import.
     */
    public function confirmImport(Request $request, string $store_slug, StoreContext $context, MasterPresetImportService $importer): RedirectResponse
    {
        $store = $context->getStore();
        $token = (string) $request->input('token');
        $sessionKey = "imports.master_presets.{$token}";
        $data = session()->get($sessionKey);

        if (!$data || !Storage::disk('local')->exists($data['path'])) {
            return redirect()->route('store.admin.product-master-presets.import', ['store_slug' => $store->slug])
                ->withErrors(['file' => 'Import session expired. Please upload the file again.']);
        }

        $duplicateStrategy = $request->input('duplicate_strategy', $data['duplicate_strategy'] ?? 'skip');

        try {
            $result = $importer->import(
                Storage::disk('local')->path($data['path']),
                $store,
                $request->user(),
                $data['filename'],
                $duplicateStrategy,
                $data['type'] ?? null
            );

            session()->forget($sessionKey);
            Storage::disk('local')->delete($data['path']);

            $msg = "Import complete: {$result['imported']} created, {$result['updated']} updated, {$result['skipped_duplicate']} skipped, {$result['failed']} failed.";
            $tab = match ($data['type'] ?? '') {
                'connector_spec' => 'connectors',
                'color' => 'colors',
                'shelf_location' => 'shelves',
                'warranty' => 'warranties',
                'return_policy' => 'return-policies',
                default => 'connectors',
            };

            return redirect()->to(route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => $tab]))
                ->with('success', $msg);
        } catch (\Exception $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }
    }

    /**
     * Download sample import template.
     */
    public function downloadImportTemplate(Request $request, string $store_slug, StoreContext $context): StreamedResponse
    {
        $type = $request->query('type', 'connector_spec');
        $filename = "preset_template_{$type}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->streamDownload(function () use ($type) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['type', 'code', 'name', 'color_hex', 'content', 'sort_order', 'is_active']);

            if ($type === 'color') {
                fputcsv($stream, ['color', 'BLK', 'Black', '#000000', 'Standard black color', '1', '1']);
                fputcsv($stream, ['color', 'WHT', 'White', '#FFFFFF', 'Standard white color', '2', '1']);
            } elseif ($type === 'shelf_location') {
                fputcsv($stream, ['shelf_location', 'A1', 'Shelf A - Row 1', '', 'Front shelf left side', '1', '1']);
            } elseif ($type === 'warranty') {
                fputcsv($stream, ['warranty', '1Y', '1 Year Official Warranty', '', 'Parts & Service covered', '1', '1']);
            } elseif ($type === 'return_policy') {
                fputcsv($stream, ['return_policy', '7D', '7 Days Replacement', '', 'Return within 7 days with receipt', '1', '1']);
            } else {
                fputcsv($stream, ['connector_spec', 'TYPEC', 'USB Type-C', '', 'Fast charging USB-C spec', '1', '1']);
                fputcsv($stream, ['connector_spec', 'LTN', 'Lightning', '', 'Apple 8-pin lightning', '2', '1']);
            }

            fclose($stream);
        }, $filename, $headers);
    }
}
