<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\POS\Models\StockTransfer;
use App\POS\Models\StockTransferItem;
use App\POS\Models\Warehouse;
use App\POS\Models\InventoryBalance;
use App\POS\Services\InventoryService;
use App\POS\Services\StoreContext;
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
        $search = $request->input('search', '');
        $status = $request->input('status', '');
        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');
        $perPage = (int) $request->input('per_page', 25);

        $query = StockTransfer::where('store_id', $store->id)
            ->with(['fromWarehouse', 'toWarehouse', 'items.product']);

        if ($search) {
            $query->where('transfer_number', 'like', "%{$search}%");
        }
        if ($status) {
            $query->where('status', $status);
        }

        $transfers = $query->orderBy($sort, $direction)->paginate($perPage);

        return view('pos.transfers.index', compact('store', 'transfers', 'search', 'status'));
    }

    public function create(StoreContext $context, string $store_slug): View
    {
        $store = $context->getStore();
        $warehouses = Warehouse::where('store_id', $store->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $warehouseIds = $warehouses->pluck('id')->toArray();
        $products = InventoryBalance::where('store_id', $store->id)
            ->whereIn('warehouse_id', $warehouseIds)
            ->where('quantity_on_hand', '>', 0)
            ->with('product')
            ->get()
            ->groupBy('warehouse_id');

        return view('pos.transfers.create', compact('store', 'warehouses', 'products'));
    }

    public function store(Request $request, StoreContext $context, string $store_slug): RedirectResponse
    {
        $store = $context->getStore();

        $validated = $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
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
        $transfer->load(['fromWarehouse', 'toWarehouse', 'items.product', 'creator']);
        return view('pos.transfers.show', ['store' => $context->getStore(), 'transfer' => $transfer]);
    }

    public function ship(StoreContext $context, string $store_slug, StockTransfer $transfer): RedirectResponse
    {
        if ($transfer->status !== 'pending') {
            return back()->withErrors(['status' => __('messages.transfer_invalid_status')]);
        }
        $transfer->update(['status' => 'in_transit', 'shipped_at' => now()]);
        return back()->with('success', __('messages.transfer_shipped'));
    }

    public function receive(StoreContext $context, string $store_slug, StockTransfer $transfer): RedirectResponse
    {
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
        if (! in_array($transfer->status, ['pending', 'in_transit'])) {
            return back()->withErrors(['status' => __('messages.transfer_invalid_status')]);
        }
        $transfer->update(['status' => 'cancelled']);
        return back()->with('success', __('messages.transfer_cancelled'));
    }
}
