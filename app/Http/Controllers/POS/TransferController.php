<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\POS\Models\StockTransfer;
use App\POS\Models\StockTransferItem;
use App\POS\Models\Warehouse;
use App\POS\Models\InventoryBalance;
use App\POS\Services\InventoryService;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TransferController extends Controller
{
    public function __construct(
        protected InventoryService $inventory
    ) {}

    public function index(Request $request, StoreContext $context, string $store_slug): View
    {
        $store = $context->getStore();
        $storeRouteParams = $context->getRouteParams();
        $search = $request->input('search', '');
        $status = $request->input('status', '');
        $sort = $request->input('sort', 'newest');
        $fromWarehouse = $request->input('from_warehouse_id', '');
        $toWarehouse = $request->input('to_warehouse_id', '');
        $dateFrom = $request->input('date_from', '');
        $dateTo = $request->input('date_to', '');
        $perPage = (int) $request->input('per_page', 25);
        if ($perPage <= 0 || $request->input('per_page') === 'all') {
            $perPage = 1000;
        }

        $stats = [
            'total' => StockTransfer::where('store_id', $store->id)->count(),
            'pending' => StockTransfer::where('store_id', $store->id)->where('status', 'pending')->count(),
            'in_transit' => StockTransfer::where('store_id', $store->id)->where('status', 'in_transit')->count(),
            'completed' => StockTransfer::where('store_id', $store->id)->where('status', 'completed')->count(),
            'cancelled' => StockTransfer::where('store_id', $store->id)->where('status', 'cancelled')->count(),
        ];

        $warehouses = Warehouse::where('store_id', $store->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $query = StockTransfer::where('store_id', $store->id)
            ->with(['fromWarehouse', 'toWarehouse', 'items.product', 'creator']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('transfer_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }
        if ($status) {
            $query->where('status', $status);
        }
        if ($fromWarehouse) {
            $query->where('from_warehouse_id', $fromWarehouse);
        }
        if ($toWarehouse) {
            $query->where('to_warehouse_id', $toWarehouse);
        }
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'number_asc':
                $query->orderBy('transfer_number', 'asc');
                break;
            case 'number_desc':
                $query->orderBy('transfer_number', 'desc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $transfers = $query->paginate($perPage)->withQueryString();

        $filters = [
            'search' => $search,
            'status' => $status,
            'sort' => $sort,
            'from_warehouse_id' => $fromWarehouse,
            'to_warehouse_id' => $toWarehouse,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];

        return view('pos.transfers.index', compact(
            'store',
            'storeRouteParams',
            'transfers',
            'stats',
            'warehouses',
            'filters',
            'search',
            'status'
        ));
    }

    public function create(StoreContext $context, string $store_slug): View
    {
        $store = $context->getStore();
        $storeRouteParams = $context->getRouteParams();
        $warehouses = Warehouse::where('store_id', $store->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $warehouseIds = $warehouses->pluck('id')->toArray();
        $products = InventoryBalance::where('store_id', $store->id)
            ->whereIn('warehouse_id', $warehouseIds)
            ->where('quantity_on_hand', '>', 0)
            ->with(['product.category', 'product.brand'])
            ->get()
            ->groupBy('warehouse_id');

        $categories = \App\Models\Category::where('store_id', $store->id)->orderBy('name')->get();
        $brands = \App\Models\Brand::where('store_id', $store->id)->orderBy('name')->get();

        return view('pos.transfers.create', compact('store', 'storeRouteParams', 'warehouses', 'products', 'categories', 'brands'));
    }

    public function store(Request $request, StoreContext $context, string $store_slug): RedirectResponse
    {
        $store = $context->getStore();

        $validated = $request->validate([
            'from_warehouse_id' => ['required', \Illuminate\Validation\Rule::exists('warehouses', 'id')->where('store_id', $store->id)],
            'to_warehouse_id' => ['required', \Illuminate\Validation\Rule::exists('warehouses', 'id')->where('store_id', $store->id), 'different:from_warehouse_id'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', \Illuminate\Validation\Rule::exists('products', 'id')->where('store_id', $store->id)],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
        ]);

        $transfer = DB::transaction(function () use ($store, $validated) {
            $transfer = StockTransfer::create([
                'store_id' => $store->id,
                'transfer_number' => StockTransfer::generateNumber($store->id),
                'from_warehouse_id' => $validated['from_warehouse_id'],
                'to_warehouse_id' => $validated['to_warehouse_id'],
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $balance = InventoryBalance::where('store_id', $store->id)
                    ->where('warehouse_id', $validated['from_warehouse_id'])
                    ->where('product_id', $item['product_id'])
                    ->first();

                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $balance?->unit_cost_avg ?? 0,
                ]);
            }

            return $transfer;
        });

        return redirect()
            ->route('pos.transfers.show', [...$context->getRouteParams(), 'transfer' => $transfer->id])
            ->with('success', __('messages.transfer_created'));
    }

    public function show(StoreContext $context, string $store_slug, StockTransfer $transfer): View
    {
        $store = $context->getStore();
        if ((int) $transfer->store_id !== (int) $store->id) {
            abort(403, 'Unauthorized store transfer.');
        }

        $transfer->load(['fromWarehouse', 'toWarehouse', 'items.product', 'creator']);
        return view('pos.transfers.show', ['store' => $store, 'storeRouteParams' => $context->getRouteParams(), 'transfer' => $transfer]);
    }

    public function ship(StoreContext $context, string $store_slug, StockTransfer $transfer): RedirectResponse
    {
        $store = $context->getStore();
        if ((int) $transfer->store_id !== (int) $store->id) {
            abort(403, 'Unauthorized store transfer.');
        }

        if ($transfer->status !== 'pending') {
            return back()->withErrors(['status' => __('messages.transfer_invalid_status')]);
        }
        $transfer->update(['status' => 'in_transit', 'shipped_at' => now()]);
        return back()->with('success', __('messages.transfer_shipped'));
    }

    public function receive(StoreContext $context, string $store_slug, StockTransfer $transfer): RedirectResponse
    {
        $store = $context->getStore();
        if ((int) $transfer->store_id !== (int) $store->id) {
            abort(403, 'Unauthorized store transfer.');
        }

        if ($transfer->status !== 'in_transit') {
            return back()->withErrors(['status' => __('messages.transfer_invalid_status')]);
        }

        DB::transaction(function () use ($transfer) {
            foreach ($transfer->items as $item) {
                $this->inventory->postMovement([
                    'store_id' => $transfer->store_id,
                    'product_id' => $item->product_id,
                    'warehouse_id' => $transfer->from_warehouse_id,
                    'movement_type' => 'transfer_out',
                    'quantity_delta' => -$item->quantity,
                    'unit_cost' => $item->unit_cost,
                    'client_transaction_id' => "trf_out:{$transfer->id}:{$item->product_id}",
                    'posted_by' => auth()->id(),
                ]);

                $this->inventory->postMovement([
                    'store_id' => $transfer->store_id,
                    'product_id' => $item->product_id,
                    'warehouse_id' => $transfer->to_warehouse_id,
                    'movement_type' => 'transfer_in',
                    'quantity_delta' => $item->quantity,
                    'unit_cost' => $item->unit_cost,
                    'client_transaction_id' => "trf_in:{$transfer->id}:{$item->product_id}",
                    'posted_by' => auth()->id(),
                ]);
            }

            $transfer->update(['status' => 'completed', 'received_at' => now()]);
        });

        return back()->with('success', __('messages.transfer_received'));
    }

    public function cancel(StoreContext $context, string $store_slug, StockTransfer $transfer): RedirectResponse
    {
        $store = $context->getStore();
        if ((int) $transfer->store_id !== (int) $store->id) {
            abort(403, 'Unauthorized store transfer.');
        }

        if (! in_array($transfer->status, ['pending', 'in_transit'])) {
            return back()->withErrors(['status' => __('messages.transfer_invalid_status')]);
        }
        $transfer->update(['status' => 'cancelled']);
        return back()->with('success', __('messages.transfer_cancelled'));
    }
}
