<?php

namespace App\POS\Http\Controllers;

use App\Http\Controllers\Controller;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\DailyClosing;
use App\POS\Services\DailyClosingService;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Branch daily closing (SoT §18).
 *
 * GET  /store/{slug}/pos/closing            — expected vs counted + status
 * POST /store/{slug}/pos/closing            — create a pending closing
 * POST /store/{slug}/pos/closing/{closing}/approve — manager approval
 *
 * Create is staff+; approval is store_manager only (route middleware).
 */
class DailyClosingController extends Controller
{
    public function __construct(
        protected DailyClosingService $closings,
    ) {
    }

    public function index(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();

        $date = Carbon::parse($request->query('date', today()->toDateString()));

        $totals = $this->closings->expectedTotals($store, $date);
        $closing = $this->closings->forDate($store, $date);

        return view('pos.closing', compact('store', 'date', 'totals', 'closing'));
    }

    public function store(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        $user = $request->user();

        $data = $request->validate([
            'business_date' => ['required', 'date', 'before_or_equal:today'],
            // decimal (not plain numeric): the closing service compares with
            // bcmath, which throws a ValueError on scientific notation ("1e3").
            'counted.cash' => ['required', 'decimal:0,2', 'min:0'],
            'counted.kpay' => ['nullable', 'decimal:0,2', 'min:0'],
            'counted.wavepay' => ['nullable', 'decimal:0,2', 'min:0'],
            'counted.cb_pay' => ['nullable', 'decimal:0,2', 'min:0'],
            'counted.mmqr' => ['nullable', 'decimal:0,2', 'min:0'],
            'explanation' => ['nullable', 'string', 'max:2000'],
        ]);

        $counted = [
            'cash' => (string) $data['counted']['cash'],
            'kpay' => (string) ($data['counted']['kpay'] ?? 0),
            'wavepay' => (string) ($data['counted']['wavepay'] ?? 0),
            'cb_pay' => (string) ($data['counted']['cb_pay'] ?? 0),
            'mmqr' => (string) ($data['counted']['mmqr'] ?? 0),
        ];

        try {
            $closing = $this->closings->create(
                store: $store,
                date: Carbon::parse($data['business_date']),
                counted: $counted,
                explanation: $data['explanation'] ?? null,
                actor: $user,
            );
        } catch (InventoryException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('pos.closing.index', ['store_slug' => $store->slug, 'date' => $closing->business_date->toDateString()])
            ->with('success', __('messages.closing_created') . ' — ' . $closing->business_date->toDateString());
    }

    public function approve(Request $request, string $store_slug, DailyClosing $closing, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        // Cross-store tampering is a 404 (never reveals another store's data).
        if ((int) $closing->store_id !== (int) $store->id) {
            abort(404);
        }

        try {
            $this->closings->approve($store, $closing, $request->user());
        } catch (InventoryException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('messages.closing_approved') . ' — ' . $closing->business_date->toDateString());
    }
}
