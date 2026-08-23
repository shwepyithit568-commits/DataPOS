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

        $search = trim((string) $request->query('q', ''));
        $grouped = ServiceSetting::allGroupedFor($store->id);
        $brands = $grouped['brand'] ?? collect();

        // If searching, filter the active tab collection
        if ($search !== '') {
            $grouped[$tab] = $grouped[$tab]->filter(function ($item) use ($search) {
                return str_contains(mb_strtolower($item->name), mb_strtolower($search))
                    || str_contains(mb_strtolower((string) $item->code), mb_strtolower($search))
                    || str_contains(mb_strtolower((string) $item->description), mb_strtolower($search));
            })->values();
        }

        $types = ServiceSetting::TYPES;

        return view('admin.service_settings.index', compact(
            'store',
            'storeRouteParams',
            'tab',
            'search',
            'grouped',
            'types',
            'brands'
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
