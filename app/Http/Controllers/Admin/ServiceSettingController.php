<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\POS\Models\ServiceSetting;
use App\Services\StoreContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ServiceSettingController extends Controller
{
    public function index(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();
        $storeRouteParams = $context->getRouteParams();

        $validTabs = array_keys(ServiceSetting::TYPES);
        $tab = $request->query('tab', 'brand');
        if (!in_array($tab, $validTabs, true)) {
            $tab = 'brand';
        }

        $search = trim((string) $request->input('search', $request->input('q', '')));
        $sort = $request->input('sort', 'newest');
        $statusFilter = $request->input('status', 'all');

        $grouped = ServiceSetting::allGroupedFor($store->id);
        $brands = $grouped['brand'] ?? collect();

        // If filtering the active tab collection
        if ($search !== '' || $statusFilter !== 'all' || $sort !== 'newest') {
            $collection = $grouped[$tab];

            if ($search !== '') {
                $collection = $collection->filter(function ($item) use ($search) {
                    return str_contains(mb_strtolower($item->name), mb_strtolower($search))
                        || str_contains(mb_strtolower((string) $item->code), mb_strtolower($search))
                        || str_contains(mb_strtolower((string) $item->description), mb_strtolower($search));
                });
            }

            if ($statusFilter === 'active') {
                $collection = $collection->where('is_active', true);
            } elseif ($statusFilter === 'inactive') {
                $collection = $collection->where('is_active', false);
            }

            if ($sort === 'oldest') {
                $collection = $collection->sortBy('id');
            } elseif ($sort === 'name_asc') {
                $collection = $collection->sortBy('name');
            } elseif ($sort === 'name_desc') {
                $collection = $collection->sortByDesc('name');
            }

            $grouped[$tab] = $collection->values();
        }

        $types = ServiceSetting::TYPES;
        $exportUrl = route('store.admin.service_settings.export', array_merge($storeRouteParams, ['tab' => $tab, 'search' => $search]));

        $currentTabItems = $grouped[$tab] ?? collect();
        $tabTotal = count($currentTabItems);
        $tabActive = collect($currentTabItems)->where('is_active', true)->count();
        $tabInactive = collect($currentTabItems)->where('is_active', false)->count();
        $allTotal = 0;
        foreach (ServiceSetting::TYPES as $t => $label) {
            $allTotal += count($grouped[$t] ?? []);
        }

        $stats = [
            'tab_total'    => $tabTotal,
            'tab_active'   => $tabActive,
            'tab_inactive' => $tabInactive,
            'all_total'    => $allTotal,
        ];

        return view('admin.service_settings.index', compact(
            'store',
            'storeRouteParams',
            'tab',
            'search',
            'sort',
            'statusFilter',
            'grouped',
            'types',
            'brands',
            'exportUrl',
            'stats'
        ));
    }

    public function store(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        $validTypes = array_keys(ServiceSetting::TYPES);

        $validated = $request->validate([
            'type'        => ['required', 'string', Rule::in($validTypes)],
            'name'        => ['required', 'string', 'max:120'],
            'code'        => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order'  => ['nullable', 'integer'],
            'is_active'   => ['nullable', 'boolean'],
            'parent_id'   => ['nullable', 'exists:service_settings,id'],
        ]);

        ServiceSetting::create([
            'store_id'    => $store->id,
            'type'        => $validated['type'],
            'name'        => trim($validated['name']),
            'code'        => isset($validated['code']) ? trim($validated['code']) : null,
            'description' => $validated['description'] ?? null,
            'sort_order'  => (int) ($validated['sort_order'] ?? 0),
            'is_active'   => $request->boolean('is_active', true),
            'parent_id'   => $validated['parent_id'] ?? null,
        ]);

        return redirect()
            ->route('store.admin.service_settings.index', [...$context->getRouteParams(), 'tab' => $validated['type']])
            ->with('success', __('messages.saved_successfully'));
    }

    public function update(Request $request, StoreContext $context, string $store_slug, ServiceSetting $service_setting): RedirectResponse
    {
        $store = $context->getStore();
        if ($service_setting->store_id !== $store->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:120'],
            'code'        => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order'  => ['nullable', 'integer'],
            'is_active'   => ['nullable', 'boolean'],
            'parent_id'   => ['nullable', 'exists:service_settings,id'],
        ]);

        $service_setting->update([
            'name'        => trim($validated['name']),
            'code'        => isset($validated['code']) ? trim($validated['code']) : null,
            'description' => $validated['description'] ?? null,
            'sort_order'  => (int) ($validated['sort_order'] ?? 0),
            'is_active'   => $request->boolean('is_active', true),
            'parent_id'   => $validated['parent_id'] ?? null,
        ]);

        return redirect()
            ->route('store.admin.service_settings.index', [...$context->getRouteParams(), 'tab' => $service_setting->type])
            ->with('success', __('messages.saved_successfully'));
    }

    public function destroy(StoreContext $context, string $store_slug, ServiceSetting $service_setting): RedirectResponse
    {
        $store = $context->getStore();
        if ($service_setting->store_id !== $store->id) {
            abort(404);
        }

        $type = $service_setting->type;
        $service_setting->delete();

        return redirect()
            ->route('store.admin.service_settings.index', [...$context->getRouteParams(), 'tab' => $type])
            ->with('success', __('messages.deleted_successfully'));
    }

    /**
     * Export master settings as UTF-8 BOM CSV.
     */
    /**
     * Export master settings as Multi-Sheet Excel (.xlsx) or UTF-8 BOM CSV (.csv).
     */
    public function export(Request $request, StoreContext $context): Response
    {
        $store = $context->getStore();
        $tab = $request->query('tab', 'brand');
        $format = strtolower((string) $request->query('format', 'csv'));
        $scope = $request->query('scope', ($tab === 'all' ? 'all' : 'tab'));
        $validTypes = array_keys(ServiceSetting::TYPES);

        $search = trim((string) $request->input('search', $request->input('q', '')));

        $typeLabels = [
            'brand'     => 'Brands',
            'category'  => 'Categories',
            'model'     => 'Models',
            'color'     => 'Colors',
            'storage'   => 'Storage',
            'defect'    => 'Defects',
            'accessory' => 'Accessories',
            'status'    => 'Statuses',
        ];

        // 1. ALL CATEGORIES EXPORT (Multi-Sheet Excel or Consolidated CSV)
        if ($scope === 'all' || $tab === 'all') {
            if ($format === 'xlsx') {
                $filename = 'Service_Settings_All_Masters_' . $store->slug . '_' . now()->format('Ymd_His') . '.xlsx';
                $tempFile = tempnam(sys_get_temp_dir(), 'datapos_svc_all_');

                $spreadsheet = new Spreadsheet();

                // Sheet 1: Summary / All-in-one overview
                $summarySheet = $spreadsheet->getActiveSheet();
                $summarySheet->setTitle('All Settings');
                $summarySheet->setCellValue('A1', $store->name . ' - Service Settings (All 8 Categories)');
                $summarySheet->setCellValue('A2', 'Export Date: ' . now()->format('d/m/Y h:i A'));
                $summarySheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('4C1D95');
                $summarySheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB('64748B');

                $summaryHeaders = ['A' => 'Type', 'B' => 'Name', 'C' => 'Code', 'D' => 'Parent Brand', 'E' => 'Description', 'F' => 'Sort Order', 'G' => 'Status'];
                foreach ($summaryHeaders as $col => $title) {
                    $summarySheet->setCellValue("{$col}4", $title);
                }
                $summarySheet->getStyle('A4:G4')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '6D28D9']],
                ]);

                $allQuery = ServiceSetting::where('store_id', $store->id)->with('parent');
                if ($search !== '') {
                    $allQuery->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                }
                $allItems = $allQuery->orderBy('type')->orderBy('sort_order')->orderBy('name')->get();

                $row = 5;
                foreach ($allItems as $item) {
                    $summarySheet->setCellValue("A{$row}", $item->type);
                    $summarySheet->setCellValue("B{$row}", $item->name);
                    $summarySheet->setCellValue("C{$row}", $item->code ?? '');
                    $summarySheet->setCellValue("D{$row}", $item->parent?->name ?? '');
                    $summarySheet->setCellValue("E{$row}", $item->description ?? '');
                    $summarySheet->setCellValue("F{$row}", $item->sort_order);
                    $summarySheet->setCellValue("G{$row}", $item->is_active ? 'Active' : 'Inactive');
                    $row++;
                }
                foreach (range('A', 'G') as $col) {
                    $summarySheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Dedicated Sheets for each Category
                foreach ($validTypes as $tKey) {
                    $sheetTitle = $typeLabels[$tKey] ?? ucfirst($tKey);
                    $catSheet = $spreadsheet->createSheet();
                    $catSheet->setTitle(substr($sheetTitle, 0, 30));

                    $catSheet->setCellValue('A1', $store->name . ' - ' . $sheetTitle);
                    $catSheet->setCellValue('A2', 'Category Code: ' . $tKey);
                    $catSheet->getStyle('A1')->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('4C1D95');
                    $catSheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB('64748B');

                    $cHeaders = ['A' => 'Name', 'B' => 'Code', 'C' => 'Parent Brand', 'D' => 'Description', 'E' => 'Sort Order', 'F' => 'Status'];
                    foreach ($cHeaders as $col => $title) {
                        $catSheet->setCellValue("{$col}4", $title);
                    }
                    $catSheet->getStyle('A4:F4')->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4C1D95']],
                    ]);

                    $catItems = $allItems->where('type', $tKey);
                    $cRow = 5;
                    foreach ($catItems as $cItem) {
                        $catSheet->setCellValue("A{$cRow}", $cItem->name);
                        $catSheet->setCellValue("B{$cRow}", $cItem->code ?? '');
                        $catSheet->setCellValue("C{$cRow}", $cItem->parent?->name ?? '');
                        $catSheet->setCellValue("D{$cRow}", $cItem->description ?? '');
                        $catSheet->setCellValue("E{$cRow}", $cItem->sort_order);
                        $catSheet->setCellValue("F{$cRow}", $cItem->is_active ? 'Active' : 'Inactive');
                        $cRow++;
                    }
                    foreach (range('A', 'F') as $col) {
                        $catSheet->getColumnDimension($col)->setAutoSize(true);
                    }
                }

                $spreadsheet->setActiveSheetIndex(0);
                $writer = new Xlsx($spreadsheet);
                $writer->save($tempFile);

                return response()->download($tempFile, $filename, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ])->deleteFileAfterSend(true);
            }

            // Consolidated CSV export
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="service-settings-all-masters-' . now()->format('Ymd-His') . '.csv"',
            ];

            return response()->streamDownload(function () use ($store, $search) {
                $stream = fopen('php://output', 'w');
                fwrite($stream, "\xEF\xBB\xBF");
                fputcsv($stream, ['Type', 'Name', 'Code', 'Parent Brand', 'Description', 'Sort Order', 'Status']);

                $q = ServiceSetting::where('store_id', $store->id)->with('parent');
                if ($search !== '') {
                    $q->where(function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                }
                foreach ($q->orderBy('type')->orderBy('sort_order')->orderBy('name')->cursor() as $item) {
                    fputcsv($stream, [
                        $item->type,
                        $item->name,
                        $item->code ?? '',
                        $item->parent?->name ?? '',
                        $item->description ?? '',
                        $item->sort_order,
                        $item->is_active ? 'Active' : 'Inactive',
                    ]);
                }
                fclose($stream);
            }, 'service-settings-all-masters-' . now()->format('Ymd-His') . '.csv', $headers);
        }

        // 2. SINGLE TAB EXPORT
        if (!in_array($tab, $validTypes, true)) {
            $tab = 'brand';
        }

        $query = ServiceSetting::where('store_id', $store->id)->where('type', $tab)->with('parent');
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }
        $items = $query->orderBy('sort_order')->orderBy('name')->get();

        if ($format === 'xlsx') {
            $filename = 'Service_Settings_' . $tab . '_' . $store->slug . '_' . now()->format('Ymd_His') . '.xlsx';
            $tempFile = tempnam(sys_get_temp_dir(), 'datapos_svc_' . $tab . '_');

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle(substr($typeLabels[$tab] ?? ucfirst($tab), 0, 30));

            $sheet->setCellValue('A1', $store->name . ' - ' . ($typeLabels[$tab] ?? ucfirst($tab)) . ' Export');
            $sheet->setCellValue('A2', 'Export Date: ' . now()->format('d/m/Y h:i A') . ' | Total Count: ' . $items->count());
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('4C1D95');
            $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB('64748B');

            $row = 4;
            $headers = ['A' => 'Name', 'B' => 'Code', 'C' => 'Parent Brand', 'D' => 'Description', 'E' => 'Sort Order', 'F' => 'Status'];
            foreach ($headers as $col => $title) {
                $sheet->setCellValue("{$col}{$row}", $title);
            }
            $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '6D28D9']],
            ]);

            $row++;
            foreach ($items as $item) {
                $sheet->setCellValue("A{$row}", $item->name);
                $sheet->setCellValue("B{$row}", $item->code ?? '');
                $sheet->setCellValue("C{$row}", $item->parent?->name ?? '');
                $sheet->setCellValue("D{$row}", $item->description ?? '');
                $sheet->setCellValue("E{$row}", $item->sort_order);
                $sheet->setCellValue("F{$row}", $item->is_active ? 'Active' : 'Inactive');
                $row++;
            }

            foreach (range('A', 'F') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);

            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        }

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="service-settings-' . $tab . '-' . now()->format('Ymd-His') . '.csv"',
        ];

        return response()->streamDownload(function () use ($items) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Name', 'Code', 'Parent Brand', 'Description', 'Sort Order', 'Status']);

            foreach ($items as $item) {
                fputcsv($stream, [
                    $item->name,
                    $item->code ?? '',
                    $item->parent?->name ?? '',
                    $item->description ?? '',
                    $item->sort_order,
                    $item->is_active ? 'Active' : 'Inactive',
                ]);
            }

            fclose($stream);
        }, 'service-settings-' . $tab . '-' . now()->format('Ymd-His') . '.csv', $headers);
    }

    /**
     * Download template for service settings (Current Tab or All Categories Multi-Sheet).
     */
    public function downloadTemplate(Request $request): Response
    {
        $tab = $request->query('tab', 'brand');
        $format = strtolower((string) $request->query('format', 'csv'));
        $scope = $request->query('scope', ($tab === 'all' ? 'all' : 'tab'));

        $typeMeta = [
            'brand'     => ['title' => 'Brands', 'samples' => [['Apple', 'APPL', '', 'Apple products', 1, 'Active'], ['Samsung', 'SAMS', '', 'Samsung electronics', 2, 'Active']]],
            'category'  => ['title' => 'Categories', 'samples' => [['Smartphone', 'PHN', '', 'Mobile phones', 1, 'Active'], ['Laptop', 'LPT', '', 'Notebook computers', 2, 'Active']]],
            'model'     => ['title' => 'Models', 'samples' => [['iPhone 15 Pro', 'IP15P', 'Apple', '6.1 inch OLED', 1, 'Active'], ['Galaxy S24', 'GS24', 'Samsung', '6.2 inch AMOLED', 2, 'Active']]],
            'color'     => ['title' => 'Colors', 'samples' => [['Space Black', 'BLK', '', '', 1, 'Active'], ['Natural Titanium', 'NAT', '', '', 2, 'Active']]],
            'storage'   => ['title' => 'Storage', 'samples' => [['128 GB', '128', '', '', 1, 'Active'], ['256 GB', '256', '', '', 2, 'Active']]],
            'defect'    => ['title' => 'Defects', 'samples' => [['Screen Broken / No Display', 'SCR', '', 'Display or touch defect', 1, 'Active'], ['Battery Draining / Dead', 'BAT', '', 'Battery replacement', 2, 'Active']]],
            'accessory' => ['title' => 'Accessories', 'samples' => [['Charger & Cable', 'CHG', '', '', 1, 'Active'], ['Phone Case', 'CSE', '', '', 2, 'Active']]],
            'status'    => ['title' => 'Statuses', 'samples' => [['Diagnosing', 'DIAG', '', 'Under inspection', 1, 'Active'], ['In Repair', 'REP', '', 'Technician working', 2, 'Active']]],
        ];

        // 1. ALL CATEGORIES MULTI-SHEET TEMPLATE
        if ($scope === 'all' || $tab === 'all') {
            if ($format === 'xlsx') {
                $filename = 'Service_Settings_Template_All_Masters.xlsx';
                $tempFile = tempnam(sys_get_temp_dir(), 'datapos_tpl_all_');

                $spreadsheet = new Spreadsheet();
                $sheetIdx = 0;
                foreach ($typeMeta as $tKey => $meta) {
                    $sheet = $sheetIdx === 0 ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
                    $sheet->setTitle(substr($meta['title'], 0, 30));

                    $sheet->setCellValue('A1', 'Service Settings Template - ' . $meta['title']);
                    $sheet->setCellValue('A2', 'Category Code: ' . $tKey . ' (Add rows below)');
                    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('4C1D95');
                    $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB('64748B');

                    $headers = ['A' => 'Name', 'B' => 'Code', 'C' => 'Parent Brand', 'D' => 'Description', 'E' => 'Sort Order', 'F' => 'Status'];
                    foreach ($headers as $col => $title) {
                        $sheet->setCellValue("{$col}4", $title);
                    }
                    $sheet->getStyle('A4:F4')->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '6D28D9']],
                    ]);

                    $r = 5;
                    foreach ($meta['samples'] as $sample) {
                        $sheet->setCellValue("A{$r}", $sample[0]);
                        $sheet->setCellValue("B{$r}", $sample[1]);
                        $sheet->setCellValue("C{$r}", $sample[2]);
                        $sheet->setCellValue("D{$r}", $sample[3]);
                        $sheet->setCellValue("E{$r}", $sample[4]);
                        $sheet->setCellValue("F{$r}", $sample[5]);
                        $r++;
                    }

                    foreach (range('A', 'F') as $col) {
                        $sheet->getColumnDimension($col)->setAutoSize(true);
                    }
                    $sheetIdx++;
                }

                $spreadsheet->setActiveSheetIndex(0);
                $writer = new Xlsx($spreadsheet);
                $writer->save($tempFile);

                return response()->download($tempFile, $filename, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ])->deleteFileAfterSend(true);
            }

            // CSV all template
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="service-settings-template-all.csv"',
            ];

            return response()->streamDownload(function () {
                $stream = fopen('php://output', 'w');
                fwrite($stream, "\xEF\xBB\xBF");
                fputcsv($stream, ['Type', 'Name', 'Code', 'Parent Brand', 'Description', 'Sort Order', 'Status']);
                fputcsv($stream, ['brand', 'Apple', 'APPL', '', 'Apple products', '1', 'Active']);
                fputcsv($stream, ['category', 'Smartphone', 'PHN', '', 'Mobile phones', '1', 'Active']);
                fputcsv($stream, ['model', 'iPhone 15 Pro', 'IP15P', 'Apple', '6.1 inch OLED', '1', 'Active']);
                fputcsv($stream, ['color', 'Space Black', 'BLK', '', '', '1', 'Active']);
                fputcsv($stream, ['defect', 'Screen Broken', 'SCR', '', 'Display defect', '1', 'Active']);
                fclose($stream);
            }, 'service-settings-template-all.csv', $headers);
        }

        // 2. SINGLE TAB TEMPLATE
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="service-settings-template-' . $tab . '.csv"',
        ];

        return response()->streamDownload(function () use ($tab, $typeMeta) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Name', 'Code', 'Parent Brand', 'Description', 'Sort Order', 'Status']);

            $samples = $typeMeta[$tab]['samples'] ?? [['Sample Name', 'SMPL', '', 'Sample description', 1, 'Active']];
            foreach ($samples as $sample) {
                fputcsv($stream, $sample);
            }

            fclose($stream);
        }, 'service-settings-template-' . $tab . '.csv', $headers);
    }

    /**
     * Import service settings from Multi-Sheet Excel (.xlsx) or CSV file.
     */
    public function import(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        $request->validate([
            'file'  => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
            'tab'   => ['nullable', 'string'],
            'scope' => ['nullable', 'string'],
        ]);

        $defaultType = $request->input('tab', 'brand');
        $scope = $request->input('scope', 'tab');
        $validTypes = array_keys(ServiceSetting::TYPES);
        if (!in_array($defaultType, $validTypes, true)) {
            $defaultType = 'brand';
        }

        $typeSheetAliases = [
            'brand'       => 'brand',
            'brands'      => 'brand',
            'category'    => 'category',
            'categories'  => 'category',
            'model'       => 'model',
            'models'      => 'model',
            'color'       => 'color',
            'colors'      => 'color',
            'storage'     => 'storage',
            'storages'    => 'storage',
            'defect'      => 'defect',
            'defects'     => 'defect',
            'accessory'   => 'accessory',
            'accessories' => 'accessory',
            'status'      => 'status',
            'statuses'    => 'status',
        ];

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        $imported = 0;

        if (in_array($ext, ['xlsx', 'xls'], true)) {
            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
                $sheetNames = $spreadsheet->getSheetNames();

                // Check for multi-sheet match
                $matchedSheets = [];
                foreach ($sheetNames as $name) {
                    $norm = strtolower(trim($name));
                    if (isset($typeSheetAliases[$norm])) {
                        $matchedSheets[$name] = $typeSheetAliases[$norm];
                    }
                }

                if (!empty($matchedSheets) && $scope !== 'current_only') {
                    // Multi-sheet import
                    foreach ($matchedSheets as $sheetName => $sheetType) {
                        $worksheet = $spreadsheet->getSheetByName($sheetName);
                        if (!$worksheet) continue;
                        $rows = $worksheet->toArray(null, true, false, false);
                        if (empty($rows)) continue;

                        $rawHeader = array_shift($rows);
                        $headerMap = [];
                        foreach ($rawHeader as $idx => $colName) {
                            $norm = strtolower(trim((string) $colName));
                            $norm = preg_replace('/[^a-z0-9]+/', '_', $norm);
                            $headerMap[trim($norm, '_')] = $idx;
                        }

                        foreach ($rows as $row) {
                            if (empty($row) || (count($row) === 1 && trim($row[0]) === '')) continue;
                            $nameIdx = $headerMap['name'] ?? 0;
                            $name = trim((string) ($row[$nameIdx] ?? ''));
                            if ($name === '' || strtolower($name) === 'name') continue;

                            $codeIdx = $headerMap['code'] ?? null;
                            $code = $codeIdx !== null && isset($row[$codeIdx]) ? trim((string) $row[$codeIdx]) : null;

                            $descIdx = $headerMap['description'] ?? null;
                            $desc = $descIdx !== null && isset($row[$descIdx]) ? trim((string) $row[$descIdx]) : null;

                            $sortIdx = $headerMap['sort_order'] ?? null;
                            $sort = $sortIdx !== null && isset($row[$sortIdx]) ? (int) $row[$sortIdx] : 0;

                            $statusIdx = $headerMap['status'] ?? null;
                            $statusStr = $statusIdx !== null && isset($row[$statusIdx]) ? strtolower(trim((string) $row[$statusIdx])) : 'active';
                            $isActive = !in_array($statusStr, ['0', 'inactive', 'false', 'off', 'no'], true);

                            $parentId = null;
                            $pBrandIdx = $headerMap['parent_brand'] ?? $headerMap['brand'] ?? null;
                            $parentBrandName = $pBrandIdx !== null && isset($row[$pBrandIdx]) ? trim((string) $row[$pBrandIdx]) : '';
                            if ($parentBrandName !== '') {
                                $parentSetting = ServiceSetting::firstOrCreate([
                                    'store_id' => $store->id,
                                    'type'     => 'brand',
                                    'name'     => $parentBrandName,
                                ], ['is_active' => true]);
                                $parentId = $parentSetting->id;
                            }

                            ServiceSetting::updateOrCreate([
                                'store_id' => $store->id,
                                'type'     => $sheetType,
                                'name'     => $name,
                            ], [
                                'code'        => $code ?: null,
                                'description' => $desc ?: null,
                                'sort_order'  => $sort,
                                'is_active'   => $isActive,
                                'parent_id'   => $parentId,
                            ]);
                            $imported++;
                        }
                    }
                } else {
                    // Single sheet XLSX (active worksheet)
                    $worksheet = $spreadsheet->getActiveSheet();
                    $rows = $worksheet->toArray(null, true, false, false);
                    if (!empty($rows)) {
                        $rawHeader = array_shift($rows);
                        $headerMap = [];
                        foreach ($rawHeader as $idx => $colName) {
                            $norm = strtolower(trim((string) $colName));
                            $norm = preg_replace('/[^a-z0-9]+/', '_', $norm);
                            $headerMap[trim($norm, '_')] = $idx;
                        }

                        foreach ($rows as $row) {
                            if (empty($row) || (count($row) === 1 && trim($row[0]) === '')) continue;
                            $nameIdx = $headerMap['name'] ?? 0;
                            $name = trim((string) ($row[$nameIdx] ?? ''));
                            if ($name === '' || strtolower($name) === 'name') continue;

                            $type = $defaultType;
                            $typeIdx = $headerMap['type'] ?? $headerMap['category_type'] ?? null;
                            if ($typeIdx !== null && isset($row[$typeIdx])) {
                                $tVal = strtolower(trim((string) $row[$typeIdx]));
                                if (isset($typeSheetAliases[$tVal])) {
                                    $type = $typeSheetAliases[$tVal];
                                } elseif (in_array($tVal, $validTypes, true)) {
                                    $type = $tVal;
                                }
                            }

                            $codeIdx = $headerMap['code'] ?? null;
                            $code = $codeIdx !== null && isset($row[$codeIdx]) ? trim((string) $row[$codeIdx]) : null;

                            $descIdx = $headerMap['description'] ?? null;
                            $desc = $descIdx !== null && isset($row[$descIdx]) ? trim((string) $row[$descIdx]) : null;

                            $sortIdx = $headerMap['sort_order'] ?? null;
                            $sort = $sortIdx !== null && isset($row[$sortIdx]) ? (int) $row[$sortIdx] : 0;

                            $statusIdx = $headerMap['status'] ?? null;
                            $statusStr = $statusIdx !== null && isset($row[$statusIdx]) ? strtolower(trim((string) $row[$statusIdx])) : 'active';
                            $isActive = !in_array($statusStr, ['0', 'inactive', 'false', 'off', 'no'], true);

                            $parentId = null;
                            $pBrandIdx = $headerMap['parent_brand'] ?? $headerMap['brand'] ?? null;
                            $parentBrandName = $pBrandIdx !== null && isset($row[$pBrandIdx]) ? trim((string) $row[$pBrandIdx]) : '';
                            if ($parentBrandName !== '') {
                                $parentSetting = ServiceSetting::firstOrCreate([
                                    'store_id' => $store->id,
                                    'type'     => 'brand',
                                    'name'     => $parentBrandName,
                                ], ['is_active' => true]);
                                $parentId = $parentSetting->id;
                            }

                            ServiceSetting::updateOrCreate([
                                'store_id' => $store->id,
                                'type'     => $type,
                                'name'     => $name,
                            ], [
                                'code'        => $code ?: null,
                                'description' => $desc ?: null,
                                'sort_order'  => $sort,
                                'is_active'   => $isActive,
                                'parent_id'   => $parentId,
                            ]);
                            $imported++;
                        }
                    }
                }
                $spreadsheet->disconnectWorksheets();
            } catch (\Throwable $e) {
                return back()->withErrors(['file' => 'Error reading Excel file: ' . $e->getMessage()]);
            }
        } else {
            // CSV / TXT import
            $handle = fopen($file->getRealPath(), 'r');
            if (! $handle) {
                return back()->withErrors(['file' => 'Could not open CSV file.']);
            }

            $firstLine = fgets($handle);
            $header = null;
            if ($firstLine !== false) {
                $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine);
                $header = str_getcsv($firstLine);
                $header = array_map(function ($h) {
                    $norm = strtolower(trim((string) $h));
                    return trim(preg_replace('/[^a-z0-9]+/', '_', $norm), '_');
                }, $header);
            }

            while (($row = fgetcsv($handle)) !== false) {
                if (empty($row) || (count($row) === 1 && trim($row[0]) === '')) {
                    continue;
                }

                $data = [];
                if ($header) {
                    foreach ($header as $i => $col) {
                        $data[$col] = $row[$i] ?? null;
                    }
                } else {
                    $data['name'] = $row[0] ?? null;
                }

                $name = trim((string) ($data['name'] ?? ''));
                if ($name === '' || strtolower($name) === 'name') {
                    continue;
                }

                $rawType = strtolower(trim((string) ($data['type'] ?? $data['category_type'] ?? '')));
                if (isset($typeSheetAliases[$rawType])) {
                    $type = $typeSheetAliases[$rawType];
                } elseif (in_array($rawType, $validTypes, true)) {
                    $type = $rawType;
                } else {
                    $type = $defaultType;
                }

                $code = isset($data['code']) ? trim((string) $data['code']) : null;
                $parentBrandName = trim((string) ($data['parent_brand'] ?? $data['brand'] ?? ''));
                $parentId = null;
                if ($parentBrandName !== '') {
                    $parentSetting = ServiceSetting::firstOrCreate([
                        'store_id' => $store->id,
                        'type'     => 'brand',
                        'name'     => $parentBrandName,
                    ], [
                        'is_active' => true,
                    ]);
                    $parentId = $parentSetting->id;
                }

                $description = $data['description'] ?? null;
                $sortOrder = isset($data['sort_order']) ? (int) $data['sort_order'] : 0;
                $statusRaw = strtolower(trim((string) ($data['status'] ?? 'active')));
                $isActive = !in_array($statusRaw, ['0', 'inactive', 'false', 'off', 'no'], true);

                ServiceSetting::updateOrCreate(
                    [
                        'store_id' => $store->id,
                        'type'     => $type,
                        'name'     => $name,
                    ],
                    [
                        'code'        => $code ?: null,
                        'description' => $description ?: null,
                        'sort_order'  => $sortOrder,
                        'is_active'   => $isActive,
                        'parent_id'   => $parentId,
                    ]
                );

                $imported++;
            }

            fclose($handle);
        }

        return redirect()
            ->route('store.admin.service_settings.index', [...$context->getRouteParams(), 'tab' => $defaultType])
            ->with('success', __('messages.service_settings_imported_success', ['count' => $imported]));
    }

    /**
     * AJAX quick-add for inline "+" buttons on the Repair Ticket Create/Edit page.
     */
    public function quickAdd(Request $request, StoreContext $context): JsonResponse
    {
        $store = $context->getStore();
        $validTypes = array_keys(ServiceSetting::TYPES);

        $validated = $request->validate([
            'type'      => ['required', 'string', Rule::in($validTypes)],
            'name'      => ['required', 'string', 'max:120'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        $name = trim($validated['name']);

        // Look up if already exists
        /** @var ServiceSetting|null $existing */
        $existing = ServiceSetting::where('store_id', $store->id)
            ->where('type', $validated['type'])
            ->where('name', $name)
            ->first();

        if ($existing) {
            if (!$existing->is_active) {
                $existing->update(['is_active' => true]);
            }
            return response()->json([
                'success' => true,
                'item' => [
                    'id'   => $existing->id,
                    'name' => $existing->name,
                    'type' => $existing->type,
                ],
            ]);
        }

        $highestSort = (int) ServiceSetting::where('store_id', $store->id)
            ->where('type', $validated['type'])
            ->max('sort_order');

        $item = ServiceSetting::create([
            'store_id'   => $store->id,
            'type'       => $validated['type'],
            'name'       => $name,
            'is_active'  => true,
            'sort_order' => $highestSort + 1,
            'parent_id'  => $validated['parent_id'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'item' => [
                'id'   => $item->id,
                'name' => $item->name,
                'type' => $item->type,
            ],
        ]);
    }
}
