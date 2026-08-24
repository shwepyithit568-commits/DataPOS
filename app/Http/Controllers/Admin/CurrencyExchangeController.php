<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\POS\Services\CurrencyExchangeService;
use App\Services\StoreContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CurrencyExchangeController extends Controller
{
    public function __construct(
        protected CurrencyExchangeService $exchangeService
    ) {
    }

    /**
     * Display the Exchange Rates board & conversion tools.
     */
    public function index(StoreContext $context): View
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $currencies = $this->exchangeService->getCurrencies($store);
        $stats = $this->exchangeService->getSummaryStats($store);

        return view('admin.exchange_rates.index', compact('store', 'currencies', 'stats'));
    }

    /**
     * Store a new foreign currency.
     */
    public function store(StoreContext $context, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('currencies', 'code')->where('store_id', $store->id),
            ],
            'name' => 'required|string|max:100',
            'symbol' => 'nullable|string|max:20',
            'exchange_rate' => 'required|numeric|min:0.0001',
            'is_active' => 'nullable|boolean',
        ]);

        $this->exchangeService->saveCurrency($store, $validated, null, $request->user());

        return redirect()->route('store.admin.exchange_rates.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.exchange_currency_created'));
    }

    /**
     * Update an existing currency.
     */
    public function update(StoreContext $context, string $store_slug, int|string $currency, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $curr = Currency::where('store_id', $store->id)->findOrFail($currency);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'symbol' => 'nullable|string|max:20',
            'exchange_rate' => 'required|numeric|min:0.0001',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['code'] = $curr->code;
        $this->exchangeService->saveCurrency($store, $validated, $curr, $request->user());

        return redirect()->route('store.admin.exchange_rates.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.exchange_rates_updated'));
    }

    /**
     * 1-Click bulk update all daily rates.
     */
    public function bulkUpdate(StoreContext $context, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $validated = $request->validate([
            'rates' => 'required|array',
            'rates.*' => 'required|numeric|min:0.0001',
        ]);

        $this->exchangeService->bulkUpdateRates($store, $validated['rates'], $request->user());

        return redirect()->route('store.admin.exchange_rates.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.exchange_rates_bulk_updated'));
    }

    /**
     * Delete a foreign currency.
     */
    public function destroy(StoreContext $context, string $store_slug, int|string $currency, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $curr = Currency::where('store_id', $store->id)->findOrFail($currency);

        if ($curr->is_base) {
            return back()->withErrors(['error' => __('messages.exchange_cannot_delete_base')]);
        }

        $this->exchangeService->deleteCurrency($store, $curr, $request->user());

        return redirect()->route('store.admin.exchange_rates.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.exchange_currency_deleted'));
    }

    /**
     * JSON conversion helper.
     */
    public function convert(StoreContext $context, Request $request): JsonResponse
    {
        $store = $context->getStore();
        if (!$store) {
            return response()->json(['error' => 'Store not found'], 404);
        }

        $amount = (float) $request->input('amount', 1.0);
        $from = (string) $request->input('from', 'USD');
        $to = (string) $request->input('to', 'MMK');

        $result = $this->exchangeService->convert($store, $amount, $from, $to);

        return response()->json([
            'amount' => $amount,
            'from' => $from,
            'to' => $to,
            'result' => $result,
        ]);
    }
}
