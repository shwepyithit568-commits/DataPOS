<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\POS\Models\PosSale;
use App\POS\Services\CustomerDebtService;
use App\Services\StoreContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerDirectoryController extends Controller
{
    public function __construct(
        protected CustomerDebtService $debts,
    ) {}

    /**
     * List all customers (users with retail_customer / wholesale_customer
     * role in this store's store_user pivot).
     */
    public function index(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();
        $storeRouteParams = $context->getRouteParams();

        $query = User::query()
            ->whereHas('stores', fn ($q) => $q
                ->where('stores.id', $store->id)
                ->whereIn('store_user.role', ['retail_customer', 'wholesale_customer'])
                ->where('store_user.status', 'active'))
            ->with(['stores' => function ($rel) use ($store) {
                $rel->where('stores.id', $store->id);
            }]);

        // Search by name, phone, or email
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($w) use ($search) {
                $w->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by customer role — must be inside whereHas, not on main query
        if ($request->filled('role') && in_array($request->input('role'), ['retail_customer', 'wholesale_customer'])) {
            $filterRole = $request->input('role');
            $query->whereHas('stores', function ($fq) use ($store, $filterRole) {
                $fq->where('stores.id', $store->id)
                  ->where('store_user.role', $filterRole)
                  ->where('store_user.status', 'active');
            });
        }

        // Sorting
        $sort = $request->get('sort', 'name');
        match ($sort) {
            'newest'   => $query->latest('users.created_at'),
            'phone'    => $query->orderBy('phone'),
            default    => $query->orderBy('name'),
        };

        // Stats (before paginate) — use separate whereHas calls for each role filter
        $baseCustomerQuery = fn ($role) => User::query()
            ->whereHas('stores', function ($q) use ($store, $role) {
                $q->where('stores.id', $store->id)
                  ->where('store_user.role', $role)
                  ->where('store_user.status', 'active');
            });

        $stats = [
            'total'     => $query->count(),
            'retail'    => $baseCustomerQuery('retail_customer')->count(),
            'wholesale' => $baseCustomerQuery('wholesale_customer')->count(),
            'debt'      => 0,
        ];

        // Count customers with outstanding debt — query the ledger directly
        $stats['debt'] = \App\POS\Models\CustomerLedgerEntry::query()
            ->where('store_id', $store->id)
            ->groupBy('customer_id')
            ->havingRaw('ABS(SUM(amount)) > 0')
            ->count();

        $perPage = request('per_page') === 'all' ? 100000 : (int) request('per_page', 25);
        $customers = $query->paginate($perPage)->withQueryString();
        $totalCount = $customers->total();

        // Enrich with debt balance for each customer
        $customers->getCollection()->transform(function (User $user) use ($store) {
            $user->debt_balance = $this->debts->balanceFor($store->id, $user->id);
            return $user;
        });

        return view('admin.customers.index', compact('store', 'storeRouteParams', 'customers', 'totalCount', 'stats'));
    }

    /**
     * Customer detail: profile info, recent POS orders, debt balance.
     */
    public function show(StoreContext $context, string $store_slug, User $customer): View
    {
        $store = $context->getStore();
        $storeRouteParams = $context->getRouteParams();

        // Verify this customer belongs to this store
        $hasMembership = $customer->stores()
            ->where('stores.id', $store->id)
            ->whereIn('store_user.role', ['retail_customer', 'wholesale_customer'])
            ->exists();

        if (! $hasMembership) {
            abort(404);
        }

        $customer->load('stores');

        // Debt balance
        $debtBalance = $this->debts->balanceFor($store->id, $customer->id);

        // Recent POS orders
        $recentOrders = PosSale::where('store_id', $store->id)
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['posted', 'partially_refunded'])
            ->with('items')
            ->latest()
            ->limit(10)
            ->get();

        // Summary stats
        $orderStats = [
            'total_orders' => PosSale::where('store_id', $store->id)
                ->where('customer_id', $customer->id)
                ->whereIn('status', ['posted', 'partially_refunded', 'refunded'])
                ->count(),
            'total_spent' => number_format(
                (float) PosSale::where('store_id', $store->id)
                    ->where('customer_id', $customer->id)
                    ->whereIn('status', ['posted', 'partially_refunded'])
                    ->sum('total'),
                2, '.', ''
            ),
        ];

        // Membership info
        $membership = $customer->getStoreMembership($store->id);

        return view('admin.customers.show', compact(
            'store', 'storeRouteParams', 'customer',
            'debtBalance', 'recentOrders', 'orderStats', 'membership'
        ));
    }
}
