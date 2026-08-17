<?php

namespace App\POS\Http\Controllers;

use App\Http\Controllers\Controller;
use App\POS\Exceptions\InventoryException;
use App\POS\Services\ReconciliationService;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Opening-stock reconciliation (Phase 2.5).
 *
 * GET  /store/{slug}/pos/reconciliation          — live diff report (imported
 *                                                  opening stock vs ledger)
 * POST /store/{slug}/pos/reconciliation/approve  — manager-only: posts the
 *                                                  correction movements and
 *                                                  snapshots the report
 */
class InventoryReconciliationController extends Controller
{
    public function __construct(protected ReconciliationService $reconciliation)
    {
    }

    public function index(StoreContext $context): View
    {
        $store = $context->getStore();

        $report = $this->reconciliation->report($store);
        $history = $this->reconciliation->recent($store);

        return view('pos.reconciliation', compact('store', 'report', 'history'));
    }

    public function approve(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        try {
            $record = $this->reconciliation->approve($store, $request->user(), $request->input('review_notes'));
        } catch (InventoryException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('messages.reconciliation_approved') . ' — ' . $record->reconciliation_number);
    }
}
