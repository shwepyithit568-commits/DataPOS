<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Store;
use App\POS\Enums\InventoryMovementType;
use App\POS\Models\Warehouse;
use App\POS\Services\StockLedgerService;
use App\Services\StoreContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockLedgerController extends Controller
{
    public function __construct(
        protected StockLedgerService $ledgerService,
    ) {
    }

    /**
     * Display the General Stock Movement Ledger Dashboard.
     */
    public function index(StoreContext $context, Request $request): View
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        [$from, $to, $preset] = $this->resolveDateRange($request);

        $filters = [
            'search' => $request->input('search'),
            'movement_type' => $request->input('movement_type'),
            'warehouse_id' => $request->input('warehouse_id'),
            'product_id' => $request->input('product_id'),
            'flow' => $request->input('flow', 'all'),
            'from' => $from,
            'to' => $to,
        ];

        $metrics = $this->ledgerService->getSummaryMetrics($store, $filters);
        $movements = $this->ledgerService->listMovements($store, $filters, 25);

        $warehouses = Warehouse::where('store_id', $store->id)->get();
        $movementTypes = InventoryMovementType::cases();

        $selectedProduct = !empty($filters['product_id'])
            ? Product::where('store_id', $store->id)->find($filters['product_id'])
            : null;

        return view('admin.stock_ledger.index', compact(
            'store',
            'movements',
            'metrics',
            'filters',
            'preset',
            'from',
            'to',
            'warehouses',
            'movementTypes',
            'selectedProduct'
        ));
    }

    /**
     * Display the Product-Specific Bin Card timeline with running balance.
     */
    public function binCard(StoreContext $context, Request $request): View
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $productId = (int) ($request->route('product') ?: $request->input('product_id'));

        if (!$productId) {
            $firstProduct = Product::where('store_id', $store->id)->orderBy('name')->first();
            $productId = $firstProduct?->id;
        }

        $product = Product::where('store_id', $store->id)
            ->with(['category', 'brand'])
            ->findOrFail($productId);

        [$from, $to, $preset] = $this->resolveDateRange($request);
        $warehouseId = $request->input('warehouse_id') ? (int) $request->input('warehouse_id') : null;

        $binCardData = $this->ledgerService->getProductBinCard($store, $product, $from, $to, $warehouseId);
        $warehouses = Warehouse::where('store_id', $store->id)->get();

        $products = Product::where('store_id', $store->id)->orderBy('name')->take(50)->get();

        return view('admin.stock_ledger.bin_card', compact(
            'store',
            'product',
            'binCardData',
            'preset',
            'from',
            'to',
            'warehouseId',
            'warehouses',
            'products'
        ));
    }

    /**
     * Export movements to CSV.
     */
    public function export(StoreContext $context, Request $request): StreamedResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        [$from, $to] = $this->resolveDateRange($request);

        $filters = [
            'search' => $request->input('search'),
            'movement_type' => $request->input('movement_type'),
            'warehouse_id' => $request->input('warehouse_id'),
            'product_id' => $request->input('product_id'),
            'flow' => $request->input('flow', 'all'),
            'from' => $from,
            'to' => $to,
        ];

        return $this->ledgerService->exportMovementsCsv($store, $filters);
    }

    /**
     * Printable A4 Bin Card.
     */
    public function printBinCard(StoreContext $context, Request $request): View
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $productId = (int) ($request->route('product') ?: $request->input('product_id'));
        $product = Product::where('store_id', $store->id)->findOrFail($productId);

        [$from, $to] = $this->resolveDateRange($request);
        $warehouseId = $request->input('warehouse_id') ? (int) $request->input('warehouse_id') : null;

        $binCardData = $this->ledgerService->getProductBinCard($store, $product, $from, $to, $warehouseId);

        return view('admin.stock_ledger.print_bin_card', compact('store', 'product', 'binCardData', 'from', 'to'));
    }

    /**
     * Resolve date range filter from request presets.
     */
    protected function resolveDateRange(Request $request): array
    {
        $preset = $request->input('preset', 'this_month');

        return match ($preset) {
            'today' => [now()->startOfDay(), now()->endOfDay(), 'today'],
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay(), 'yesterday'],
            '7days' => [now()->subDays(6)->startOfDay(), now()->endOfDay(), '7days'],
            '30days' => [now()->subDays(29)->startOfDay(), now()->endOfDay(), '30days'],
            'this_month' => [now()->startOfMonth()->startOfDay(), now()->endOfMonth()->endOfDay(), 'this_month'],
            'last_month' => [now()->subMonth()->startOfMonth()->startOfDay(), now()->subMonth()->endOfMonth()->endOfDay(), 'last_month'],
            'all' => [null, null, 'all'],
            'custom' => [
                $request->input('from') ? Carbon::parse($request->input('from'))->startOfDay() : now()->startOfMonth(),
                $request->input('to') ? Carbon::parse($request->input('to'))->endOfDay() : now()->endOfDay(),
                'custom',
            ],
            default => [now()->startOfMonth()->startOfDay(), now()->endOfMonth()->endOfDay(), 'this_month'],
        };
    }
}
