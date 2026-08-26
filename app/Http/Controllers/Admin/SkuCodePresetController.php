<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SkuCodePreset;
use App\Services\StoreContext;
use App\Support\AdminListReturn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SkuCodePresetController extends Controller
{
    public function store(Request $request, string $store_slug, StoreContext $context): RedirectResponse|JsonResponse
    {
        $store = $context->getStore();

        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(['model', 'connector_spec', 'color', 'quality', 'capacity'])],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:150'],
            'color_hex' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['store_id'] = $store->id;
        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);
        $validated['is_active'] = $request->boolean('is_active', true);

        // Enforce uniqueness per store + type + code
        $exists = SkuCodePreset::where('store_id', $store->id)
            ->where('type', $validated['type'])
            ->where('code', $validated['code'])
            ->exists();

        if ($exists) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'This code already exists for this type.'], 422);
            }
            return back()->withErrors(['code' => 'This code already exists for this type.'])->withInput();
        }

        $preset = SkuCodePreset::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'preset' => $preset,
                'message' => 'SKU Code preset created successfully.',
            ]);
        }

        return redirect()->to(route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => 'sku-presets']) . '&type=' . $preset->type)
            ->with('success', 'SKU Code preset created successfully.');
    }

    public function update(Request $request, string $store_slug, SkuCodePreset $skuCodePreset, StoreContext $context): RedirectResponse|JsonResponse
    {
        $store = $context->getStore();

        if ($skuCodePreset->store_id !== $store->id) {
            abort(403, 'Unauthorized SKU code preset access.');
        }

        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(['model', 'connector_spec', 'color', 'quality', 'capacity'])],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:150'],
            'color_hex' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);
        $validated['is_active'] = $request->boolean('is_active', true);

        $exists = SkuCodePreset::where('store_id', $store->id)
            ->where('type', $validated['type'])
            ->where('code', $validated['code'])
            ->where('id', '!=', $skuCodePreset->id)
            ->exists();

        if ($exists) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'This code already exists for this type.'], 422);
            }
            return back()->withErrors(['code' => 'This code already exists for this type.'])->withInput();
        }

        $skuCodePreset->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'preset' => $skuCodePreset,
                'message' => 'SKU Code preset updated successfully.',
            ]);
        }

        return redirect()->to(route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => 'sku-presets']) . '&type=' . $skuCodePreset->type)
            ->with('success', 'SKU Code preset updated successfully.');
    }

    public function destroy(string $store_slug, SkuCodePreset $skuCodePreset, StoreContext $context): RedirectResponse|JsonResponse
    {
        $store = $context->getStore();

        if ($skuCodePreset->store_id !== $store->id) {
            abort(403, 'Unauthorized SKU code preset access.');
        }

        $type = $skuCodePreset->type;
        $skuCodePreset->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'SKU Code preset deleted successfully.',
            ]);
        }

        return redirect()->to(route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => 'sku-presets']) . '&type=' . $type)
            ->with('success', 'SKU Code preset deleted successfully.');
    }
}
