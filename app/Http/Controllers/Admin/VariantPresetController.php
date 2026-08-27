<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VariantPreset;
use App\Services\StoreContext;
use App\Support\AdminListReturn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

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
