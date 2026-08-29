<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImportHistory;
use App\Models\Store;
use App\Models\VariantPreset;
use App\Services\StoreContext;
use App\Services\VariantPresetImportService;
use App\Support\AdminListReturn;
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

class VariantPresetController extends Controller
{
    public function index(StoreContext $context): View
    {
        $store = $context->getStore();
        $presets = $this->presetQuery($store->id)->get();

        return view('admin.variant_presets.index', compact('store', 'presets'));
    }

    public function store(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        $validated = $this->validatePreset($request, $store->id);

        VariantPreset::create([
            'store_id' => $store->id,
            'name' => $validated['name'],
            'category_family' => $validated['category_family'] ?? null,
            'options' => $this->normalizeOptions($validated['options'] ?? []),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return redirect(AdminListReturn::resolve('admin_variant_presets_return', '/store/' . $store->slug . '/admin/variant-presets'))
            ->with('success', __('messages.variant_preset_created'));
    }

    public function edit(string $store_slug, VariantPreset $variantPreset, StoreContext $context): View
    {
        $store = $context->getStore();
        $this->authorizeStorePreset($variantPreset, $store->id);

        $presets = $this->presetQuery($store->id)->get();

        return view('admin.variant_presets.index', [
            'store' => $store,
            'presets' => $presets,
            'editingPreset' => $variantPreset,
        ]);
    }

    public function update(Request $request, string $store_slug, VariantPreset $variantPreset, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        $this->authorizeStorePreset($variantPreset, $store->id);

        $validated = $this->validatePreset($request, $store->id, $variantPreset->id);

        $variantPreset->update([
            'name' => $validated['name'],
            'category_family' => $validated['category_family'] ?? null,
            'options' => $this->normalizeOptions($validated['options'] ?? []),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return redirect(AdminListReturn::resolve('admin_variant_presets_return', '/store/' . $store->slug . '/admin/variant-presets'))
            ->with('success', __('messages.variant_preset_updated'));
    }

    public function destroy(string $store_slug, VariantPreset $variantPreset, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        $this->authorizeStorePreset($variantPreset, $store->id);

        $variantPreset->delete();

        return redirect(AdminListReturn::resolve('admin_variant_presets_return', '/store/' . $store->slug . '/admin/variant-presets'))
            ->with('success', __('messages.variant_preset_deleted'));
    }

    public function duplicate(string $store_slug, VariantPreset $variantPreset, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        $this->authorizeStorePreset($variantPreset, $store->id);

        $copy = VariantPreset::create([
            'store_id' => $store->id,
            'name' => $this->uniqueCopyName($store->id, $variantPreset->name),
            'category_family' => $variantPreset->category_family,
            'options' => $variantPreset->options ?? [],
            'sort_order' => ((int) VariantPreset::where('store_id', $store->id)->max('sort_order')) + 1,
        ]);

        return redirect('/store/' . $store->slug . '/admin/variant-presets/' . $copy->id . '/edit')
            ->with('success', __('messages.variant_preset_duplicated'));
    }

    public function move(Request $request, string $store_slug, VariantPreset $variantPreset, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        $this->authorizeStorePreset($variantPreset, $store->id);

        $validated = $request->validate([
            'direction' => ['required', Rule::in(['up', 'down'])],
        ]);

        $neighbor = VariantPreset::where('store_id', $store->id)
            ->where('id', '!=', $variantPreset->id)
            ->when(
                $validated['direction'] === 'up',
                fn ($query) => $query->where('sort_order', '<=', $variantPreset->sort_order)->orderByDesc('sort_order')->orderByDesc('id'),
                fn ($query) => $query->where('sort_order', '>=', $variantPreset->sort_order)->orderBy('sort_order')->orderBy('id')
            )
            ->first();

        if ($neighbor) {
            $currentSort = $variantPreset->sort_order;
            $variantPreset->update(['sort_order' => $neighbor->sort_order]);
            $neighbor->update(['sort_order' => $currentSort]);
        }

        return redirect(AdminListReturn::resolve('admin_variant_presets_return', '/store/' . $store->slug . '/admin/variant-presets'))
            ->with('success', __('messages.variant_preset_order_updated'));
    }

    /**
     * Export Variant Presets as Excel (.xlsx) or CSV (.csv).
     */
    public function export(Request $request, StoreContext $context): BinaryFileResponse|StreamedResponse
    {
        $store = $context->getStore();
        $format = strtolower((string) $request->query('format', 'xlsx'));
        $family = $request->query('family');
        $search = trim((string) $request->query('search', ''));

        $query = VariantPreset::where('store_id', $store->id);
        if (!empty($family)) {
            $query->where('category_family', $family);
        }
        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        $presets = $query->orderBy('sort_order')->orderBy('name')->get();

        if ($format === 'csv') {
            return $this->exportCsv($presets);
        }

        return $this->exportXlsx($store, $presets);
    }

    private function exportCsv($presets): StreamedResponse
    {
        $filename = 'Variant_Presets-' . now()->format('Ymd-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->streamDownload(function () use ($presets) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Name', 'Category Family', 'Option Name', 'Option Values', 'Sort Order']);

            foreach ($presets as $p) {
                $optName = '';
                $optValues = [];
                if (!empty($p->options)) {
                    foreach ($p->options as $opt) {
                        $optName = $opt['name'] ?? '';
                        if (isset($opt['values']) && is_array($opt['values'])) {
                            $optValues = array_merge($optValues, $opt['values']);
                        }
                    }
                }

                fputcsv($stream, [
                    $p->name,
                    $p->category_family ?? '',
                    $optName,
                    implode(', ', $optValues),
                    $p->sort_order,
                ]);
            }

            fclose($stream);
        }, $filename, $headers);
    }

    private function exportXlsx(Store $store, $presets): BinaryFileResponse
    {
        $filename = 'Variant_Presets_' . $store->slug . '_' . now()->format('Ymd_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'datapos_vpreset_');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Variant Presets');

        // Title Block
        $sheet->setCellValue('A1', $store->name . ' - Variant Settings & Presets Export');
        $sheet->setCellValue('A2', 'Export Date: ' . now()->format('d/m/Y h:i A') . ' | Total Presets: ' . $presets->count());
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('4C1D95');
        $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB('64748B');

        $row = 4;
        $headers = [
            'A' => 'Preset Name',
            'B' => 'Category Family',
            'C' => 'Option Name',
            'D' => 'Option Values',
            'E' => 'Sort Order',
        ];

        foreach ($headers as $col => $title) {
            $sheet->setCellValue("{$col}{$row}", $title);
        }

        $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6D28D9']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(24);

        $row++;
        foreach ($presets as $p) {
            $optName = '';
            $optValues = [];
            if (!empty($p->options)) {
                foreach ($p->options as $opt) {
                    $optName = $opt['name'] ?? '';
                    if (isset($opt['values']) && is_array($opt['values'])) {
                        $optValues = array_merge($optValues, $opt['values']);
                    }
                }
            }

            $sheet->setCellValue("A{$row}", $p->name);
            $sheet->setCellValue("B{$row}", $p->category_family ?? '');
            $sheet->setCellValue("C{$row}", $optName);
            $sheet->setCellValue("D{$row}", implode(', ', $optValues));
            $sheet->setCellValue("E{$row}", (int) $p->sort_order);

            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
                ]);
            }
            $row++;
        }

        $lastRow = max(4, $row - 1);
        $sheet->getStyle("A4:E{$lastRow}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
        ]);

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Import form view for Variant Presets.
     */
    public function importForm(StoreContext $context): View
    {
        $store = $context->getStore();

        $histories = ImportHistory::where('store_id', $store->id)
            ->where('type', 'variant_presets')
            ->with('user')
            ->latest()
            ->take(10)
            ->get();

        return view('admin.variant_presets.import', compact('store', 'histories'));
    }

    /**
     * Preview import file.
     */
    public function import(Request $request, StoreContext $context, VariantPresetImportService $importer): RedirectResponse
    {
        $store = $context->getStore();

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:5120'],
            'duplicate_strategy' => ['nullable', 'in:skip,update'],
        ]);

        $file = $request->file('file');
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
        $storedPath = $file->storeAs('imports/tmp', Str::uuid()->toString() . '-' . $safeName, 'local');

        try {
            $duplicateStrategy = $validated['duplicate_strategy'] ?? 'skip';
            $preview = $importer->preview(Storage::disk('local')->path($storedPath), $store, $duplicateStrategy);
            $token = Str::random(40);

            session()->put("imports.variant_presets.{$token}", [
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

    /**
     * Confirm and execute import.
     */
    public function confirmImport(Request $request, StoreContext $context, VariantPresetImportService $importer): RedirectResponse
    {
        $store = $context->getStore();
        $token = (string) $request->input('token');
        $sessionKey = "imports.variant_presets.{$token}";
        $data = session()->get($sessionKey);

        if (!$data || !Storage::disk('local')->exists($data['path'])) {
            return redirect()->route('store.admin.variant-presets.import', ['store_slug' => $store->slug])
                ->withErrors(['file' => 'Import session expired. Please upload the file again.']);
        }

        $duplicateStrategy = $request->input('duplicate_strategy', $data['duplicate_strategy'] ?? 'skip');

        try {
            $result = $importer->import(
                Storage::disk('local')->path($data['path']),
                $store,
                $request->user(),
                $data['filename'],
                $duplicateStrategy
            );

            session()->forget($sessionKey);
            Storage::disk('local')->delete($data['path']);

            $msg = "Import complete: {$result['imported']} created, {$result['updated']} updated, {$result['skipped_duplicate']} skipped, {$result['failed']} failed.";

            return redirect()->to(route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => 'variant-presets']))
                ->with('success', $msg);
        } catch (\Exception $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }
    }

    /**
     * Download sample import template.
     */
    public function downloadImportTemplate(StoreContext $context): StreamedResponse
    {
        $filename = 'variant_preset_template.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->streamDownload(function () {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['name', 'category_family', 'option_name', 'option_values', 'sort_order']);
            fputcsv($stream, ['Storage Capacity', 'mobile', 'Internal Storage', '64GB, 128GB, 256GB, 512GB', '1']);
            fputcsv($stream, ['RAM & Memory', 'computer', 'RAM', '8GB, 16GB, 32GB', '2']);
            fputcsv($stream, ['Standard Colors', 'accessories', 'Color', 'Black, Silver, Gold, Midnight Green', '3']);
            fclose($stream);
        }, $filename, $headers);
    }

    private function validatePreset(Request $request, int $storeId, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('variant_presets', 'name')
                    ->where('store_id', $storeId)
                    ->ignore($ignoreId),
            ],
            'category_family' => ['nullable', Rule::in(['mobile', 'accessories', 'cctv', 'computer', 'network', 'fashion'])],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'options' => ['required', 'array', 'min:1', 'max:20'],
            'options.*.name' => ['required', 'string', 'max:100'],
            'options.*.sku_suffix' => ['nullable', 'string', 'max:50'],
            'options.*.retail_price_adjustment' => ['nullable', 'numeric', 'min:-999999999', 'max:999999999'],
            'options.*.wholesale_price_adjustment' => ['nullable', 'numeric', 'min:-999999999', 'max:999999999'],
            'options.*.stock_status' => ['required', 'in:in_stock,out_of_stock'],
        ]);
    }

    private function normalizeOptions(array $options): array
    {
        return collect($options)
            ->map(fn (array $option): array => [
                'name' => trim((string) ($option['name'] ?? '')),
                'sku_suffix' => trim((string) ($option['sku_suffix'] ?? '')),
                'retail_price_adjustment' => (float) ($option['retail_price_adjustment'] ?? 0),
                'wholesale_price_adjustment' => (float) ($option['wholesale_price_adjustment'] ?? 0),
                'stock_status' => $option['stock_status'] ?? 'in_stock',
            ])
            ->filter(fn (array $option): bool => $option['name'] !== '')
            ->values()
            ->all();
    }

    private function presetQuery(int $storeId)
    {
        return VariantPreset::where('store_id', $storeId)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    private function authorizeStorePreset(VariantPreset $variantPreset, int $storeId): void
    {
        if ($variantPreset->store_id !== $storeId) {
            abort(403, 'Unauthorized variant preset access.');
        }
    }

    private function uniqueCopyName(int $storeId, string $name): string
    {
        $base = $name . ' Copy';
        $candidate = $base;
        $suffix = 2;

        while (VariantPreset::where('store_id', $storeId)->where('name', $candidate)->exists()) {
            $candidate = $base . ' ' . $suffix++;
        }

        return $candidate;
    }
}
