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

        $countedKeys = array_keys($request->input('counted', []));
        $allowedCounted = DailyClosing::countedMethods();
        $unknownKeys = array_diff($countedKeys, $allowedCounted);
        if (!empty($unknownKeys)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'counted' => 'Unknown counted payment key(s): ' . implode(', ', $unknownKeys),
            ]);
        }

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

    public function xReport(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();
        $dateInput = $request->query('date', today()->toDateString());
        $date = Carbon::parse($dateInput);

        if ($date->isFuture()) {
            abort(422, __('messages.future_date_not_allowed') ?? 'Cannot generate X-Report for a future date.');
        }

        $xData = $this->closings->xReport($store, $date, $request->user());

        return view('pos.closing_x_report', compact('store', 'date', 'xData'));
    }

    public function print(Request $request, string $store_slug, StoreContext $context, ?DailyClosing $closing = null): View
    {
        $store = $context->getStore();

        if ($closing && (int) $closing->store_id !== (int) $store->id) {
            abort(404);
        }

        $allowedLayouts = ['58mm', '80mm', 'a5_portrait', 'a5_landscape', 'a4_portrait', 'a4_landscape'];
        $layout = (string) $request->query('layout', '80mm');
        if (! in_array($layout, $allowedLayouts, true)) {
            $layout = '80mm';
        }

        $dateString = $closing ? $closing->business_date->toDateString() : $request->query('date', today()->toDateString());
        $date = Carbon::parse($dateString);

        if ($date->isFuture()) {
            abort(422, 'Cannot print report for a future date.');
        }

        $type = (string) $request->query('type', $closing ? 'z' : 'x');
        if (! in_array($type, ['x', 'z'], true)) {
            $type = 'z';
        }

        // If closing is not injected directly in route, try finding one for date if type is z
        if (! $closing && $type === 'z') {
            $closing = $this->closings->forDate($store, $date);
        }

        $xData = null;
        $totals = null;

        if ($type === 'x') {
            $xData = $this->closings->xReport($store, $date, $request->user());
            $totals = $xData['totals'];
        } else {
            // Z-Report
            if ($closing) {
                // Use snapshotted values from daily_closing record
                $totals = [
                    'opening_amount' => (string) $closing->opening_amount,
                    'expected' => $closing->expected_totals ?? [],
                    'counted' => $closing->counted_totals ?? [],
                    'differences' => $closing->differences ?? [],
                    'total_difference' => (string) $closing->total_difference,
                    'date' => $closing->business_date->toDateString(),
                    'summary' => $this->closings->expectedTotals($store, $date)['summary'] ?? [],
                ];
            } else {
                // If closing not yet submitted, calculate current totals
                $calcTotals = $this->closings->expectedTotals($store, $date);
                $totals = [
                    'opening_amount' => $calcTotals['opening_amount'],
                    'expected' => $calcTotals['expected'],
                    'counted' => [],
                    'differences' => [],
                    'total_difference' => '0.00',
                    'date' => $date->toDateString(),
                    'summary' => $calcTotals['summary'],
                ];
            }
        }

        return view('pos.closing_print', compact('store', 'date', 'type', 'layout', 'closing', 'totals', 'xData'));
    }

    public function reopen(Request $request, string $store_slug, DailyClosing $closing, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        if ((int) $closing->store_id !== (int) $store->id) {
            abort(404);
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        try {
            app(\App\POS\Services\PeriodLockService::class)->reopenPeriod(
                store: $store,
                date: $closing->business_date,
                user: $request->user(),
                reason: $data['reason']
            );
        } catch (InventoryException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('messages.period_reopened') . ' — ' . $closing->business_date->toDateString());
    }
}
