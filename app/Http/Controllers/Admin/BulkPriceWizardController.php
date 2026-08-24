<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\POS\Services\BulkPriceWizardService;
use App\Services\StoreContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BulkPriceWizardController extends Controller
{
    public function __construct(
        protected BulkPriceWizardService $wizardService
    ) {
    }

    /**
     * Display the Bulk Price Wizard screen.
     */
    public function index(StoreContext $context, Request $request): View
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $filters = [
            'category_id' => $request->input('category_id'),
            'brand_id' => $request->input('brand_id'),
            'supplier_id' => $request->input('supplier_id'),
            'stock_status' => $request->input('stock_status'),
            'cost_filter' => $request->input('cost_filter'),
            'search' => $request->input('search'),
        ];

        $stats = $this->wizardService->getStatistics($store);
        $filterOptions = $this->wizardService->getFilterOptions($store);
        $products = $this->wizardService->getProducts($store, $filters);

        return view('admin.price_wizard.index', compact(
            'store',
            'stats',
            'filterOptions',
            'products',
            'filters'
        ));
    }

    /**
     * Calculate price via AJAX for preview validation.
     */
    public function calculate(StoreContext $context, Request $request): JsonResponse
    {
        $store = $context->getStore();
        if (!$store) {
            return response()->json(['error' => 'Store not found'], 404);
        }

        $validated = $request->validate([
            'cost' => 'required|numeric|min:0',
            'current_price' => 'required|numeric|min:0',
            'mode' => 'required|string',
            'value' => 'required|numeric',
            'rounding' => 'nullable|string',
        ]);

        $newPrice = $this->wizardService->calculateNewPrice(
            (float) $validated['cost'],
            (float) $validated['current_price'],
            $validated['mode'],
            (float) $validated['value'],
            $validated['rounding'] ?? 'none'
        );

        $delta = $newPrice - (float) $validated['current_price'];
        $deltaPercent = (float) $validated['current_price'] > 0
            ? round(($delta / (float) $validated['current_price']) * 100, 2)
            : 0;

        $newMargin = $newPrice > 0 && (float) $validated['cost'] > 0
            ? round((($newPrice - (float) $validated['cost']) / $newPrice) * 100, 2)
            : 0;

        return response()->json([
            'new_price' => $newPrice,
            'delta' => $delta,
            'delta_percent' => $deltaPercent,
            'new_margin' => $newMargin,
            'is_below_cost' => (float) $validated['cost'] > 0 && $newPrice < (float) $validated['cost'],
        ]);
    }

    /**
     * Apply bulk price changes to database.
     */
    public function apply(StoreContext $context, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.retail_price' => 'nullable|numeric|min:0',
            'items.*.wholesale_price' => 'nullable|numeric|min:0',
            'items.*.old_price' => 'nullable|numeric|min:0',
            'sync_variants' => 'nullable|boolean',
            'set_old_price' => 'nullable|boolean',
        ]);

        $options = [
            'sync_variants' => $request->boolean('sync_variants', true),
            'set_old_price' => $request->boolean('set_old_price', false),
        ];

        $result = $this->wizardService->applyBulkUpdate(
            $store,
            $validated['items'],
            $options,
            $request->user(),
            $request->ip()
        );

        if (!$result['success']) {
            return redirect()->back()
                ->withErrors(['error' => $result['message']])
                ->withInput();
        }

        $message = __('messages.price_wizard_success_updated', ['count' => $result['updated_count']]);
        if (!empty($result['warnings'])) {
            $message .= ' (' . count($result['warnings']) . ' warnings noted)';
        }

        return redirect()->route('store.admin.price_wizard.index', ['store_slug' => $store->slug])
            ->with('success', $message);
    }

    /**
     * Export price list to CSV.
     */
    public function export(StoreContext $context, Request $request): StreamedResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $filters = [
            'category_id' => $request->input('category_id'),
            'brand_id' => $request->input('brand_id'),
            'supplier_id' => $request->input('supplier_id'),
            'stock_status' => $request->input('stock_status'),
            'cost_filter' => $request->input('cost_filter'),
            'search' => $request->input('search'),
        ];

        return $this->wizardService->exportCsv($store, $filters);
    }
}
