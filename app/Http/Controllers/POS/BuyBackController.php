<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\POS\Models\BuyBack;
use App\POS\Models\BuyBackItem;
use App\POS\Models\Warehouse;
use App\POS\Services\InventoryService;
use App\POS\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BuyBackController extends Controller
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

        $query = BuyBack::where('store_id', $store->id)
            ->with(['creator', 'items.product']);

        if ($search) {
            $query->where('buyback_number', 'like', "%{$search}%");
        }
        if ($status) {
            $query->where('status', $status);
        }

        $buybacks = $query->orderBy($sort, $direction)->paginate($perPage);

        return view('pos.buybacks.index', compact('store', 'buybacks', 'search', 'status'));
    }

    public function create(StoreContext $context, string $store_slug): View
    {
        $store = $context->getStore();
        $customers = Customer::where('store_id', $store->id)->orderBy('name')->get();
        $warehouse = Warehouse::where('store_id', $store->id)->where('is_default', true)->first();
        $products = Product::where('store_id', $store->id)->where('is_active', true)->orderBy('name')->get();

        return view('pos.buybacks.create', compact('store', 'customers', 'products', 'warehouse'));
    }

    public function store(Request $request, StoreContext $context, string $store_slug): RedirectResponse
    {
        $store = $context->getStore();

        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'reason' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $buyback = DB::transaction(function () use ($store, $validated) {
            $totalValue = collect($validated['items'])->sum(fn ($item) => $item['unit_price'] * $item['quantity']);

            $buyback = BuyBack::create([
                'store_id' => $store->id,
                'buyback_number' => BuyBack::generateNumber($store->id),
                'customer_id' => $validated['customer_id'] ?? null,
                'total_value' => $totalValue,
                'refund_amount' => $totalValue,
                'status' => 'pending',
                'reason' => $validated['reason'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                BuyBackItem::create([
                    'buy_back_id' => $buyback->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ]);
            }

            return $buyback;
        });

        return redirect()
            ->route('pos.buybacks.show', [...$context->getRouteParams(), 'buyback' => $buyback->id])
            ->with('success', __('messages.buyback_created'));
    }

    public function show(StoreContext $context, string $store_slug, BuyBack $buyback): View
    {
        $buyback->load(['creator', 'items.product']);
        return view('pos.buybacks.show', ['store' => $context->getStore(), 'buyback' => $buyback]);
    }

    public function complete(StoreContext $context, string $store_slug, BuyBack $buyback): RedirectResponse
    {
        if ($buyback->status !== 'pending') {
            return back()->withErrors(['status' => __('messages.buyback_invalid_status')]);
        }

        $warehouse = Warehouse::where('store_id', $buyback->store_id)->where('is_default', true)->first();

        DB::transaction(function () use ($buyback, $warehouse) {
            foreach ($buyback->items as $item) {
                $this->inventory->postMovement([
                    'store_id' => $buyback->store_id,
                    'product_id' => $item->product_id,
                    'warehouse_id' => $warehouse?->id,
                    'movement_type' => 'sales_return',
                    'quantity_delta' => $item->quantity,
                    'unit_cost' => $item->unit_price,
                    'client_transaction_id' => "buyback:{$buyback->id}:{$item->product_id}",
                    'posted_by' => auth()->id(),
                ]);
            }
            $buyback->update(['status' => 'completed']);
        });

        return back()->with('success', __('messages.buyback_completed'));
    }

    public function cancel(StoreContext $context, string $store_slug, BuyBack $buyback): RedirectResponse
    {
        if ($buyback->status !== 'pending') {
            return back()->withErrors(['status' => __('messages.buyback_invalid_status')]);
        }
        $buyback->update(['status' => 'cancelled']);
        return back()->with('success', __('messages.buyback_cancelled'));
    }
}
