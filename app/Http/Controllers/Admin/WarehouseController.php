<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\POS\Models\Branch;
use App\POS\Models\Warehouse;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function index(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();
        $storeRouteParams = ['store_slug' => $store->slug];

        $search = trim($request->input('search', ''));
        $status = $request->input('status', '');
        $branchId = $request->input('branch_id', '');
        $sort = $request->input('sort', 'name');

        $query = Warehouse::where('store_id', $store->id)
            ->with('branch')
            ->withCount(['balances as active_products_count' => function ($q) {
                $q->where('quantity_on_hand', '>', 0);
            }])
            ->withSum(['balances as total_stock_quantity' => function ($q) {
                $q->where('quantity_on_hand', '>', 0);
            }], 'quantity_on_hand');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($status !== '' && $status !== null) {
            if ($status === 'active' || $status === '1') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive' || $status === '0') {
                $query->where('is_active', false);
            }
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        switch ($sort) {
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'code_asc':
                $query->orderBy('code', 'asc');
                break;
            case 'code_desc':
                $query->orderBy('code', 'desc');
                break;
            case 'products_desc':
                $query->orderByDesc('active_products_count');
                break;
            case 'stock_desc':
                $query->orderByDesc('total_stock_quantity');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'name':
            default:
                $query->orderBy('name', 'asc');
                break;
        }

        $warehouses = $query->get();

        $stats = [
            'total' => Warehouse::where('store_id', $store->id)->count(),
            'active' => Warehouse::where('store_id', $store->id)->where('is_active', true)->count(),
            'inactive' => Warehouse::where('store_id', $store->id)->where('is_active', false)->count(),
            'branches' => Branch::where('store_id', $store->id)->count(),
            'default_warehouse' => Warehouse::where('store_id', $store->id)->where('is_default', true)->value('name') ?? 'Default',
        ];

        $branches = Branch::where('store_id', $store->id)->orderBy('name')->get();

        $filters = [
            'search' => $search,
            'status' => $status,
            'branch_id' => $branchId,
            'sort' => $sort,
        ];

        return view('admin.warehouses.index', compact('store', 'warehouses', 'branches', 'storeRouteParams', 'stats', 'filters', 'search', 'status', 'sort'));
    }

    public function store(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:100'],
            'code'      => ['nullable', 'string', 'max:32'],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('store_id', $store->id)],
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
        $store = $context->getStore();

        if ($warehouse->store_id !== $store->id) {
            abort(403, 'Unauthorized warehouse access.');
        }

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:100'],
            'code'      => ['nullable', 'string', 'max:32'],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('store_id', $store->id)],
            'is_active' => ['boolean'],
        ]);

        $warehouse->update($validated);

        return back()->with('success', __('messages.warehouse_updated'));
    }

    public function destroy(StoreContext $context, string $store_slug, Warehouse $warehouse): RedirectResponse
    {
        $store = $context->getStore();

        if ($warehouse->store_id !== $store->id) {
            abort(403, 'Unauthorized warehouse access.');
        }

        if ($warehouse->is_default) {
            return back()->withErrors(['warehouse' => __('messages.warehouse_delete_default')]);
        }

        $warehouse->delete();

        return back()->with('success', __('messages.warehouse_deleted'));
    }
}
