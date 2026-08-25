<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\POS\Services\InventoryValuationService;
use App\Services\StoreContext;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryValuationController extends Controller
{
    public function __construct(
        protected InventoryValuationService $valuationService,
    ) {
    }

    /**
     * Display inventory valuation dashboard.
     */
    public function index(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $filters = [
            'search'       => $request->query('search'),
            'category_id'  => $request->query('category_id'),
            'brand_id'     => $request->query('brand_id'),
            'stock_status' => $request->query('stock_status'),
            'sort'         => $request->query('sort', 'cost_value_desc'),
            'page'         => $request->query('page', 1),
        ];

        $perPage = $request->query('per_page', 25);
        if (! in_array((string) $perPage, ['25', '50', '100', 'all'], true)) {
            $perPage = 25;
        }

        $metrics = $this->valuationService->getValuationMetrics($store);
        $categoryBreakdown = $this->valuationService->getCategoryValuation($store);
        $brandBreakdown = $this->valuationService->getBrandValuation($store);
        $products = $this->valuationService->getValuationProducts($store, $filters, $perPage);

        $categories = Category::where('store_id', $store->id)->orderBy('name')->get(['id', 'name']);
        $brands = Brand::where('store_id', $store->id)->orderBy('name')->get(['id', 'name']);

        return view('admin.inventory_valuation.index', compact(
            'store',
            'metrics',
            'categoryBreakdown',
            'brandBreakdown',
            'products',
            'categories',
            'brands',
            'filters',
            'perPage'
        ));
    }

    /**
     * Export Inventory Valuation to CSV (UTF-8 BOM).
     */
    public function exportCsv(Request $request, StoreContext $context): StreamedResponse
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $filters = [
            'search'       => $request->query('search'),
            'category_id'  => $request->query('category_id'),
            'brand_id'     => $request->query('brand_id'),
            'stock_status' => $request->query('stock_status'),
            'sort'         => $request->query('sort', 'cost_value_desc'),
        ];

        $metrics = $this->valuationService->getValuationMetrics($store);
        $products = $this->valuationService->getValuationProducts($store, $filters, 'all');

        $filename = 'inventory-valuation-' . $store->slug . '-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($store, $metrics, $products) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($handle, ['Inventory Valuation Report', $store->name]);
            fputcsv($handle, ['Generated Date', now()->toFormattedDateString() . ' ' . now()->format('h:i A')]);
            fputcsv($handle, ['Total Unique SKUs', $metrics['total_items_count']]);
            fputcsv($handle, ['Total On-Hand Units', number_format($metrics['total_units'], 2)]);
            fputcsv($handle, ['Total Inventory Value at Cost (Ks)', number_format($metrics['total_cost_value'], 2)]);
            fputcsv($handle, ['Total Value at Retail Price (Ks)', number_format($metrics['total_retail_value'], 2)]);
            fputcsv($handle, ['Total Value at Wholesale Price (Ks)', number_format($metrics['total_wholesale_value'], 2)]);
            fputcsv($handle, ['Potential Gross Profit (Ks)', number_format($metrics['potential_profit'], 2)]);
            fputcsv($handle, ['Potential Gross Margin %', $metrics['potential_margin'] . '%']);
            fputcsv($handle, []);

            fputcsv($handle, [
                'SKU',
                'Product Name',
                'Category',
                'Brand',
                'On-Hand Qty',
                'Unit Cost (Ks)',
                'Total Cost Value (Ks)',
                'Retail Price (Ks)',
                'Total Retail Value (Ks)',
                'Wholesale Price (Ks)',
                'Total Wholesale Value (Ks)',
                'Potential Profit (Ks)',
                'Margin %',
                'Stock Status',
            ]);

            foreach ($products as $p) {
                fputcsv($handle, [
                    $p->sku ?? '-',
                    $p->name,
                    $p->category?->name ?? 'General',
                    $p->brand?->name ?? '-',
                    $p->computed_qty,
                    number_format($p->computed_cost, 2),
                    number_format($p->computed_cost_value, 2),
                    number_format((float) $p->retail_price, 2),
                    number_format($p->computed_retail_value, 2),
                    number_format((float) ($p->wholesale_price ?? $p->retail_price), 2),
                    number_format($p->computed_wholesale_value, 2),
                    number_format($p->computed_profit, 2),
                    $p->computed_margin . '%',
                    $p->stock_status ?? 'in_stock',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Print A4 Inventory Valuation Statement.
     */
    public function printReport(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $filters = [
            'search'       => $request->query('search'),
            'category_id'  => $request->query('category_id'),
            'brand_id'     => $request->query('brand_id'),
            'stock_status' => $request->query('stock_status'),
            'sort'         => $request->query('sort', 'cost_value_desc'),
        ];

        $metrics = $this->valuationService->getValuationMetrics($store);
        $categoryBreakdown = $this->valuationService->getCategoryValuation($store);
        $products = $this->valuationService->getValuationProducts($store, $filters, 'all');

        return view('admin.inventory_valuation.print', compact(
            'store',
            'metrics',
            'categoryBreakdown',
            'products'
        ));
    }
}
