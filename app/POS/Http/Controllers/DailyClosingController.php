<?php

namespace App\POS\Http\Controllers;

use App\Http\Controllers\Controller;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\DailyClosing;
use App\POS\Services\DailyClosingService;
use App\POS\Services\StoreBusinessDateService;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Branch daily closing (SoT §18).
 *
 * GET  /store/{slug}/pos/closing                   — expected vs counted + status
 * POST /store/{slug}/pos/closing                   — create a pending closing
 * POST /store/{slug}/pos/closing/{closing}/approve — manager approval
 */
class DailyClosingController extends Controller
{
    public function __construct(
        protected DailyClosingService $closings,
        protected StoreBusinessDateService $businessDate,
    ) {
    }

    public function index(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();

        $date = $this->businessDate->parseDate($store, $request->query('date'));

        $totals = $this->closings->expectedTotals($store, $date);
        $closing = $this->closings->forDate($store, $date);

        return view('pos.closing', compact('store', 'date', 'totals', 'closing'));
    }

    public function store(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        $user = $request->user();

        // Anti-tampering check: client cannot forge calculated financial truth fields
        if ($request->hasAny(['expected_totals', 'differences', 'total_difference', 'opening_amount'])) {
            throw ValidationException::withMessages([
                'counted' => [__('messages.financial_truth_tampering_rejected')],
            ]);
        }

        $countedInput = $request->input('counted');
        if (!is_array($countedInput)) {
            throw ValidationException::withMessages([
                'counted' => [__('messages.invalid_counted_payload')],
            ]);
        }

        foreach ($countedInput as $val) {
            if (is_array($val) || is_object($val)) {
                throw ValidationException::withMessages([
                    'counted' => [__('messages.nested_counted_key_rejected')],
                ]);
            }
        }

        $countedKeys = array_keys($countedInput);
        $allowedCounted = DailyClosing::countedMethods();
        $unknownKeys = array_diff($countedKeys, $allowedCounted);
        if (!empty($unknownKeys)) {
            throw ValidationException::withMessages([
                'counted' => [__('messages.unknown_counted_payment_methods') . ': ' . implode(', ', $unknownKeys)],
            ]);
        }

        $data = $request->validate([
            'business_date' => ['required', 'date_format:Y-m-d'],
            'counted.cash' => ['required', 'decimal:0,2', 'min:0'],
            'counted.kpay' => ['nullable', 'decimal:0,2', 'min:0'],
            'counted.wavepay' => ['nullable', 'decimal:0,2', 'min:0'],
            'counted.cb_pay' => ['nullable', 'decimal:0,2', 'min:0'],
            'counted.mmqr' => ['nullable', 'decimal:0,2', 'min:0'],
            'explanation' => ['nullable', 'string', 'max:2000'],
        ]);

        $date = $this->businessDate->parseDate($store, $data['business_date']);
        $this->businessDate->assertNotFuture($store, $date);

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
                date: $date,
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
        $date = $this->businessDate->parseDate($store, $request->query('date'));
        try {
            $this->businessDate->assertNotFuture($store, $date);
        } catch (ValidationException) {
            abort(422, __('messages.future_date_not_allowed'));
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

        $dateString = $closing ? $closing->business_date->toDateString() : $request->query('date');
        $date = $this->businessDate->parseDate($store, $dateString);
        try {
            $this->businessDate->assertNotFuture($store, $date);
        } catch (ValidationException) {
            abort(422, __('messages.future_date_not_allowed'));
        }

        $type = (string) $request->query('type', $closing ? 'z' : 'x');
        if (! in_array($type, ['x', 'z'], true)) {
            $type = 'z';
        }

        $xData = null;
        $totals = null;

        if ($type === 'x') {
            $xData = $this->closings->xReport($store, $date, $request->user());
            $totals = $xData['totals'];
        } else {
            // Z-Report: requires persisted closing
            if (!$closing) {
                $closing = $this->closings->forDate($store, $date);
            }

            if (!$closing) {
                abort(404, __('messages.closing_not_found'));
            }

            // Always use persisted snapshot, never call expectedTotals() on reprint
            $totals = [
                'opening_amount' => (string) $closing->opening_amount,
                'expected' => $closing->expected_totals ?? [],
                'counted' => $closing->counted_totals ?? [],
                'differences' => $closing->differences ?? [],
                'total_difference' => (string) $closing->total_difference,
                'date' => $closing->business_date->toDateString(),
                'summary' => $closing->getSummarySnapshot(),
            ];
        }

        return view('pos.closing_print', compact('store', 'date', 'type', 'layout', 'closing', 'totals', 'xData'));
    }
}
