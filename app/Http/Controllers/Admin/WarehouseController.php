<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\POS\Models\Branch;
use App\POS\Models\Warehouse;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function index(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();
        $warehouses = Warehouse::where('store_id', $store->id)
            ->with('branch')
            ->orderBy('name')
            ->get();

        $branches = Branch::where('store_id', $store->id)->orderBy('name')->get();

        return view('admin.warehouses.index', compact('store', 'warehouses', 'branches'));
    }

    public function store(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:100'],
            'code'      => ['nullable', 'string', 'max:32'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ]);

        Warehouse::create([
            'store_id'  => $store->id,
            'branch_id' => $validated['branch_id'] ?? null,
            'name'      => trim($validated['name']),
            'code'      => $validated['code'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', __('messages.warehouse_created'));
    }

    public function update(StoreContext $context, string $store_slug, Request $request, Warehouse $warehouse): RedirectResponse
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:100'],
            'code'      => ['nullable', 'string', 'max:32'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'is_active' => ['boolean'],
        ]);

        $warehouse->update($validated);

        return back()->with('success', __('messages.warehouse_updated'));
    }

    public function destroy(StoreContext $context, string $store_slug, Warehouse $warehouse): RedirectResponse
    {
        if ($warehouse->is_default) {
            return back()->withErrors(['warehouse' => __('messages.warehouse_delete_default')]);
        }

        $warehouse->delete();

        return back()->with('success', __('messages.warehouse_deleted'));
    }
}
