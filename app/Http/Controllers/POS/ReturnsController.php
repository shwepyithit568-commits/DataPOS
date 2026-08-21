<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\POS\Models\PosReturn;
use App\POS\Models\PosSale;
use App\Services\StoreContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Sales Returns management (roadmap Phase 2).
 *
 * The refund posting machinery lives in PosReturnService (atomic return
 * document + `sales_return` ledger movements + cash/credit refunds + sale
 * status update).  This controller is the management layer on top of it:
 *
 *   GET /pos/returns            — history of all posted returns
 *   GET /pos/returns/new        — pick a posted sale to refund (leads to the
 *                                 existing sale-scoped refund form)
 *   GET /pos/returns/{return}   — detail of a single posted return
 *
 * Returns are immutable once posted (PosReturnService guarantees it), so no
 * store/update/destroy routes are exposed here.
 */
class ReturnsController extends Controller
{
    /** Whitelist of per-page sizes, consistent with the rest of the POS lists. */
    private const PER_PAGE_OPTIONS = [25, 50, 100];

    public function index(Request $request, StoreContext $context, string $store_slug): View
    {
        $store = $context->getStore();
        $storeRouteParams = $context->getRouteParams();
        $search = $request->input('search', '');

        $perPage = (int) $request->input('per_page', 25);
        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = 25;
        }

        $query = PosReturn::where('store_id', $store->id)
            ->with(['sale', 'items', 'cashier']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('refund_number', 'like', "%{$search}%")
                    ->orWhereHas('sale', function ($sq) use ($search) {
                        $sq->where('receipt_number', 'like', "%{$search}%");
                    });
            });
        }

        $returns = $query->orderByDesc('posted_at')->paginate($perPage);

        // Stat cards: one-shot aggregates over the store's posted returns.
        $summary = [
            'total'    => PosReturn::where('store_id', $store->id)->count(),
            'refunded' => number_format((float) PosReturn::where('store_id', $store->id)->sum('total'), 2, '.', ''),
            'today'    => PosReturn::where('store_id', $store->id)
                ->whereDate('posted_at', now()->toDateString())
                ->count(),
        ];

        return view('pos.returns.index', compact('store', 'storeRouteParams', 'returns', 'search', 'summary'));
    }

    /**
     * Pick the posted sale to return.  Clicking a row opens the existing
     * sale-scoped refund form (pos.refund.create) which pre-fills the
     * refundable quantities — no refund logic is duplicated here.
     */
    public function create(Request $request, StoreContext $context, string $store_slug): View
    {
        $store = $context->getStore();
        $storeRouteParams = $context->getRouteParams();
        $search = $request->input('search', '');

        $query = PosSale::where('store_id', $store->id)
            ->whereIn('status', ['posted', 'partially_refunded'])
            ->with(['customer']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        $sales = $query->orderByDesc('posted_at')->limit(50)->get();

        return view('pos.returns.select_sale', compact('store', 'storeRouteParams', 'sales', 'search'));
    }

    public function show(StoreContext $context, string $store_slug, PosReturn $return): View
    {
        $store = $context->getStore();

        if ((int) $return->store_id !== (int) $store->id) {
            abort(404);
        }

        $return->load(['sale', 'items', 'payments', 'cashier', 'customer']);

        return view('pos.returns.show', [
            'store' => $store,
            'storeRouteParams' => $context->getRouteParams(),
            'return' => $return,
        ]);
    }
}
