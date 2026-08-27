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

            fputcsv($handle, [__('messages.report_valuation_title'), $store->name]);
            fputcsv($handle, [__('messages.export_date'), now()->toFormattedDateString() . ' ' . now()->format('h:i A')]);
            fputcsv($handle, [__('messages.report_total_skus'), $metrics['total_items_count']]);
            fputcsv($handle, [__('messages.report_total_onhand_units'), number_format($metrics['total_units'], 2)]);
            fputcsv($handle, [__('messages.report_cost_value'), number_format($metrics['total_cost_value'], 2)]);
            fputcsv($handle, [__('messages.report_retail_value'), number_format($metrics['total_retail_value'], 2)]);
            fputcsv($handle, [__('messages.report_wholesale_value'), number_format($metrics['total_wholesale_value'], 2)]);
            fputcsv($handle, [__('messages.report_potential_profit'), number_format($metrics['potential_profit'], 2)]);
            fputcsv($handle, [__('messages.report_potential_margin'), $metrics['potential_margin'] . '%']);
            fputcsv($handle, []);

            $stockStatusLabel = function (string $status): string {
                return match ($status) {
                    'in_stock' => __('messages.in_stock'),
                    'low_stock' => __('messages.low_stock'),
                    'out_of_stock' => __('messages.out_of_stock'),
                    default => ucfirst(str_replace('_', ' ', $status)),
                };
            };

            fputcsv($handle, [
                __('messages.sku'),
                __('messages.product'),
                __('messages.category'),
                __('messages.brand'),
                __('messages.report_onhand_qty'),
                __('messages.stock_ledger_unit_cost'),
                __('messages.report_total_cost_value'),
                __('messages.report_retail_price'),
                __('messages.report_total_retail_value'),
                __('messages.report_wholesale_price'),
                __('messages.report_total_wholesale_value'),
                __('messages.report_potential_profit'),
                __('messages.report_gross_margin'),
                __('messages.report_stock_status'),
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
                    $stockStatusLabel($p->stock_status ?? 'in_stock'),
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
