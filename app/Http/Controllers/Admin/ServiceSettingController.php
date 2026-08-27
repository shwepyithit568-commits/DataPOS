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
            'exportUrl'
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
    public function export(Request $request, StoreContext $context): StreamedResponse
    {
        $store = $context->getStore();
        $tab = $request->query('tab', 'all');
        $validTabs = array_keys(ServiceSetting::TYPES);

        $query = ServiceSetting::where('store_id', $store->id)->with('parent');

        if ($tab !== 'all' && in_array($tab, $validTabs, true)) {
            $query->where('type', $tab);
        }

        $search = trim((string) $request->input('search', $request->input('q', '')));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('type')->orderBy('sort_order')->orderBy('name')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="service-settings-' . $tab . '-' . now()->format('Ymd-His') . '.csv"',
        ];

        return response()->streamDownload(function () use ($items) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            fputcsv($stream, [
                'Type',
                'Name',
                'Code',
                'Parent Brand',
                'Description',
                'Sort Order',
                'Status',
            ]);

            foreach ($items as $item) {
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
        }, 'service-settings-' . $tab . '-' . now()->format('Ymd-His') . '.csv', $headers);
    }

    /**
     * Download template for service settings.
     */
    public function downloadTemplate(Request $request): StreamedResponse
    {
        $tab = $request->query('tab', 'brand');
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="service-settings-template-' . $tab . '.csv"',
        ];

        return response()->streamDownload(function () use ($tab) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, [
                'Type',
                'Name',
                'Code',
                'Parent Brand',
                'Description',
                'Sort Order',
                'Status',
            ]);

            if ($tab === 'brand') {
                fputcsv($stream, ['brand', 'Apple', 'APPL', '', 'Apple products', '1', 'Active']);
                fputcsv($stream, ['brand', 'Samsung', 'SAMS', '', 'Samsung electronics', '2', 'Active']);
            } elseif ($tab === 'model') {
                fputcsv($stream, ['model', 'iPhone 15 Pro', 'IP15P', 'Apple', '6.1 inch OLED', '1', 'Active']);
                fputcsv($stream, ['model', 'Galaxy S24', 'GS24', 'Samsung', '6.2 inch AMOLED', '2', 'Active']);
            } else {
                fputcsv($stream, [$tab, 'Sample Name', 'SMPL', '', 'Sample description', '1', 'Active']);
            }

            fclose($stream);
        }, 'service-settings-template-' . $tab . '.csv', $headers);
    }

    /**
     * Import service settings from CSV file.
     */
    public function import(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'tab'  => ['nullable', 'string'],
        ]);

        $defaultType = $request->input('tab', 'brand');
        $validTypes = array_keys(ServiceSetting::TYPES);
        if (!in_array($defaultType, $validTypes, true)) {
            $defaultType = 'brand';
        }

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        if (! $handle) {
            return back()->withErrors(['file' => 'Could not open CSV file.']);
        }

        $imported = 0;
        $header = null;

        // Skip BOM if present
        $firstLine = fgets($handle);
        if ($firstLine !== false) {
            $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine);
            $header = str_getcsv($firstLine);
            $header = array_map(fn ($h) => strtolower(trim((string) $h)), $header);
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
            if ($name === '') {
                continue;
            }

            $type = strtolower(trim((string) ($data['type'] ?? $defaultType)));
            if (!in_array($type, $validTypes, true)) {
                $type = $defaultType;
            }

            $code = isset($data['code']) ? trim((string) $data['code']) : null;
            $parentBrandName = trim((string) ($data['parent brand'] ?? $data['parent_brand'] ?? $data['brand'] ?? ''));
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
            $sortOrder = isset($data['sort order']) ? (int) $data['sort order'] : (isset($data['sort_order']) ? (int) $data['sort_order'] : 0);
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

        return redirect()
            ->route('store.admin.service_settings.index', [...$context->getRouteParams(), 'tab' => $defaultType])
            ->with('success', "{$imported} " . __('messages.import_success'));
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
