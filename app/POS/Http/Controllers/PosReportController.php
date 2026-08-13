<?php

namespace App\POS\Http\Controllers;

use App\Http\Controllers\Controller;
use App\POS\Services\PosReportService;
use App\Services\StoreContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Minimal reports (target-design §2.10 — sales / cash / stock).
 *
 * All numbers are DERIVED at request time from the authoritative sources:
 * posted pos_sales (+ payments), cashier shifts, and the inventory ledger
 * cache. No new tables — read-only views.
 */
class PosReportController extends Controller
{
    public function __construct(
        protected PosReportService $reports,
    ) {
    }

    public function sales(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();

        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'cashier_id' => ['nullable', 'integer'],
        ]);

        $from = Carbon::parse($data['from'] ?? today()->subDays(6));
        $to = Carbon::parse($data['to'] ?? today());

        $report = $this->reports->salesReport($store, $from, $to, $data['cashier_id'] ?? null);

        $cashiers = \App\Models\User::query()
            ->whereHas('stores', fn ($q) => $q->where('stores.id', $store->id)->whereIn('store_user.role', ['store_manager', 'staff']))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('pos.reports.sales', compact('store', 'from', 'to', 'report', 'cashiers'));
    }

    public function cash(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();

        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = Carbon::parse($data['from'] ?? today()->subDays(6));
        $to = Carbon::parse($data['to'] ?? today());

        $report = $this->reports->cashReport($store, $from, $to);

        return view('pos.reports.cash', compact('store', 'from', 'to', 'report'));
    }

    public function stock(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();

        $report = $this->reports->stockReport($store, $request->query('q'));

        return view('pos.reports.stock', compact('store', 'report'));
    }
}
