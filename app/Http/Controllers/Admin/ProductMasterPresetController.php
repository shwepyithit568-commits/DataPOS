<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductMasterPreset;
use App\Services\StoreContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
}
