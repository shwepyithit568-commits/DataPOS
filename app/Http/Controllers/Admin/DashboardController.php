<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GlassFinderItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Models\WholesaleApplication;
use App\POS\Services\CashierShiftService;
use App\Services\StoreContext;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Cache TTL for aggregation stats (seconds).
     * Dashboard counts don't need real-time freshness —
     * 60-second stale-while-revalidate reduces DB load significantly.
     */
    private const STATS_CACHE_TTL = 60;

    /**
     * Online order statuses that represent real revenue: the shop has accepted
     * the order. `pending_contact` and `cancelled` are excluded.
     */
    private const REVENUE_ORDER_STATUSES = ['confirmed', 'delivered'];

    public function __construct(
        protected CashierShiftService $shifts,
    ) {
    }

    public function index(StoreContext $context): View
    {
        $user = auth()->user();

        if (!$user->isPlatformOwner() && !$context->getStore()) {
            abort(403, 'Platform owner access required.');
        }

        // If user is platform owner and no specific store context, load store selector list
        if ($user->isPlatformOwner() && !$context->getStore()) {
            $totalStores = Store::count();
            $activeStores = Store::where('is_active', true)->count();
            $inactiveStores = Store::where('is_active', false)->count();
            $totalUsers = User::count();
            $stores = Store::withCount(['products'])->orderBy('name')->get();
            return view('admin.dashboard_select_store', compact(
                'stores',
                'totalStores',
                'activeStores',
                'inactiveStores',
                'totalUsers'
            ));
        }

        $store = $context->getStore()
            ?? Store::where('is_active', true)->where('is_primary', true)->orderBy('id')->first()
            ?? Store::first();

        if (!$store) {
            $store = new Store(['id' => 0, 'name' => 'Default Store', 'slug' => 'default']);
        }

        $storeId = $store->id;

        // ── Cached aggregation stats (60-second TTL) ──────────────────────
        $stats = Cache::remember('dashboard.stats.v2.' . $storeId, self::STATS_CACHE_TTL, function () use ($storeId) {
            $todayStart = now()->startOfDay();
            $weekStart = now()->startOfWeek(Carbon::MONDAY);
            $monthStart = now()->startOfMonth();
            $yearStart = now()->startOfYear();

            return [
                'totalProducts'      => Product::where('store_id', $storeId)->count(),
                'inStockProducts'    => Product::where('store_id', $storeId)->where('stock_status', 'in_stock')->count(),
                'outOfStockProducts' => Product::where('store_id', $storeId)->where('stock_status', 'out_of_stock')->count(),
                'pendingOrders'      => Order::where('store_id', $storeId)->where('status', 'pending_contact')->count(),
                'confirmedOrders'    => Order::where('store_id', $storeId)->where('status', 'confirmed')->count(),
                'deliveredOrders'    => Order::where('store_id', $storeId)->where('status', 'delivered')->count(),
                'cancelledOrders'    => Order::where('store_id', $storeId)->where('status', 'cancelled')->count(),
                'pendingWholesale'   => WholesaleApplication::where('store_id', $storeId)->where('status', 'pending')->count(),
                'glassFinderItems'   => GlassFinderItem::where('store_id', $storeId)->count(),
                'todayOrders'        => $this->ordersSince($storeId, $todayStart),
                'todayRevenue'       => $this->revenueSumSince($storeId, $todayStart),
                'weekOrders'         => $this->ordersSince($storeId, $weekStart),
                'weekRevenue'        => $this->revenueSumSince($storeId, $weekStart),
                'monthOrders'        => $this->ordersSince($storeId, $monthStart),
                'monthRevenue'       => $this->revenueSumSince($storeId, $monthStart),
                'yearRevenue'        => $this->revenueSumSince($storeId, $yearStart),
                // Expenses (Outflow)
                'todayExpense'       => (float) \App\POS\Models\Expense::where('store_id', $storeId)->where('expense_date', '>=', $todayStart)->sum('amount'),
                'monthExpense'       => (float) \App\POS\Models\Expense::where('store_id', $storeId)->where('expense_date', '>=', $monthStart)->sum('amount'),
                // Repairs & Service Center
                'activeRepairs'      => \App\POS\Models\ServiceJob::where('store_id', $storeId)->whereNotIn('status', ['delivered', 'cancelled', 'unrepairable'])->count(),
                'readyRepairs'       => \App\POS\Models\ServiceJob::where('store_id', $storeId)->where('status', 'ready')->count(),
                // Online Ecommerce Catalog
                'ecommerceProducts'  => Product::where('store_id', $storeId)->where('is_ecommerce', true)->count(),
                // Customer Receivables (AR)
                'totalCustomerDebt'  => max(0, (float) \App\POS\Models\CustomerLedgerEntry::where('store_id', $storeId)->sum('amount')),
                // Top 5 products by quantity sold - online order line items
                // merged with posted POS counter sales, so the ranking shares
                // the same revenue scope as the stat cards and the 7-day chart.
                'topProducts'        => DB::select(
                    'SELECT name, SUM(qty) AS qty, SUM(amount) AS sales FROM ('
                    . ' SELECT oi.product_name AS name, SUM(oi.quantity) AS qty, SUM(oi.subtotal) AS amount'
                    . '   FROM order_items oi JOIN orders o ON o.id = oi.order_id'
                    . '  WHERE o.store_id = ? AND o.status != ? GROUP BY oi.product_name'
                    . ' UNION ALL'
                    . ' SELECT psi.product_name AS name, SUM(psi.quantity) AS qty, SUM(psi.line_total) AS amount'
                    . "   FROM pos_sale_items psi JOIN pos_sales ps ON ps.id = psi.pos_sale_id"
                    . "  WHERE ps.store_id = ? AND ps.status IN ('posted', 'partially_refunded', 'refunded') GROUP BY psi.product_name"
                    . ') AS combined_sales GROUP BY name ORDER BY qty DESC LIMIT 5',
                    [$storeId, 'cancelled', $storeId]
                ),
                // Last 12 months revenue series (online orders + posted POS
                // sales), same scope as the stat cards and the 7-day chart.
                'monthlySeries'      => collect(range(11, 0))->map(function ($i) use ($storeId) {
                    $month = now()->subMonths($i);
                    $revenue = $this->revenueSumBetween($storeId, $month->copy()->startOfMonth(), $month->copy()->endOfMonth());

                    return ['label' => $month->format('M y'), 'revenue' => $revenue];
                })->all(),
                // 1. Last 7 Days Daily Revenue & Order Series
                'last7DaysSeries'    => collect(range(6, 0))->map(function ($i) use ($storeId) {
                    $day = now()->subDays($i);
                    $dayStart = $day->copy()->startOfDay();
                    $dayEnd = $day->copy()->endOfDay();

                    return [
                        'day' => $day->format('D'),
                        'date' => $day->format('d M'),
                        'revenue' => $this->revenueSumBetween($storeId, $dayStart, $dayEnd),
                        'orders' => $this->ordersBetween($storeId, $dayStart, $dayEnd),
                    ];
                })->all(),
                // 2. Payment Method Mix (Last 30 Days)
                'paymentBreakdown'   => (function () use ($storeId) {
                    $payments = \App\POS\Models\PosPayment::query()
                        ->join('pos_sales', 'pos_sales.id', '=', 'pos_payments.pos_sale_id')
                        ->where('pos_sales.store_id', $storeId)
                        ->where('pos_sales.posted_at', '>=', now()->subDays(30))
                        ->selectRaw('pos_payments.method as method, SUM(pos_payments.amount) as total')
                        ->groupBy('pos_payments.method')
                        ->get();

                    $total = $payments->sum('total');
                    if ($total <= 0) {
                        return [
                            ['name' => 'Cash (ငွေသား)', 'amount' => 0, 'percent' => 0, 'color' => '#10b981'],
                            ['name' => 'KBZPay', 'amount' => 0, 'percent' => 0, 'color' => '#0284c7'],
                            ['name' => 'WavePay', 'amount' => 0, 'percent' => 0, 'color' => '#eab308'],
                            ['name' => 'Banking (CB/AYA)', 'amount' => 0, 'percent' => 0, 'color' => '#8b5cf6'],
                        ];
                    }

                    $colors = ['cash' => '#10b981', 'kpay' => '#0284c7', 'kbz_pay' => '#0284c7', 'wave' => '#eab308', 'wave_pay' => '#eab308', 'banking' => '#8b5cf6', 'credit' => '#f43f5e'];
                    return $payments->map(function ($p) use ($total, $colors) {
                        $key = strtolower($p->method);
                        return [
                            'name' => ucfirst(str_replace('_', ' ', $p->method)),
                            'amount' => (float) $p->total,
                            'percent' => round(($p->total / $total) * 100),
                            'color' => $colors[$key] ?? '#64748b',
                        ];
                    })->values()->all();
                })(),
                // 3. Expense Breakdown by Category (This Month)
                'expenseBreakdown'   => (function () use ($storeId) {
                    $monthStart = now()->startOfMonth();
                    $expenses = \App\POS\Models\Expense::query()
                        ->leftJoin('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
                        ->where('expenses.store_id', $storeId)
                        ->where('expenses.expense_date', '>=', $monthStart)
                        ->selectRaw('COALESCE(expense_categories.name, "General (အထွေထွေ)") as name, SUM(expenses.amount) as total')
                        ->groupBy('name')
                        ->orderByDesc('total')
                        ->take(4)
                        ->get();

                    $total = $expenses->sum('total');
                    if ($total <= 0) {
                        return [
                            ['name' => 'Operations (လည်ပတ်စရိတ်)', 'amount' => 0, 'percent' => 0, 'color' => '#d946ef'],
                            ['name' => 'Shop Rent & Utilities (ဆိုင်လခ/မီတာ)', 'amount' => 0, 'percent' => 0, 'color' => '#8b5cf6'],
                            ['name' => 'Salary & Staff (လစာ/ရိက္ခာ)', 'amount' => 0, 'percent' => 0, 'color' => '#06b6d4'],
                            ['name' => 'Logistics (သယ်ယူပို့ဆောင်ရေး)', 'amount' => 0, 'percent' => 0, 'color' => '#f59e0b'],
                        ];
                    }

                    $colors = ['#d946ef', '#8b5cf6', '#06b6d4', '#f59e0b'];
                    return $expenses->map(function ($e, $idx) use ($total, $colors) {
                        return [
                            'name' => $e->name,
                            'amount' => (float) $e->total,
                            'percent' => round(($e->total / $total) * 100),
                            'color' => $colors[$idx % count($colors)],
                        ];
                    })->all();
                })(),
                // 4. Service & Repair Jobs Pipeline
                'servicePipeline'    => [
                    'received'    => \App\POS\Models\ServiceJob::where('store_id', $storeId)->whereIn('status', ['received', 'diagnosing'])->count(),
                    'in_progress' => \App\POS\Models\ServiceJob::where('store_id', $storeId)->whereIn('status', ['awaiting_approval', 'awaiting_parts', 'in_repair'])->count(),
                    'ready'       => \App\POS\Models\ServiceJob::where('store_id', $storeId)->where('status', 'ready')->count(),
                    'delivered'   => \App\POS\Models\ServiceJob::where('store_id', $storeId)->where('status', 'delivered')->where('created_at', '>=', now()->startOfMonth())->count(),
                ],
            ];
        });

        // Merge defaults so a stale cache entry (written before these keys
        // existed) never leaves the view with missing stats.
        $stats = array_merge([
            'todayOrders' => 0,
            'todayRevenue' => 0,
            'weekOrders' => 0,
            'weekRevenue' => 0,
            'monthOrders' => 0,
            'monthRevenue' => 0,
            'yearRevenue' => 0,
            'todayExpense' => 0,
            'monthExpense' => 0,
            'activeRepairs' => 0,
            'readyRepairs' => 0,
            'ecommerceProducts' => 0,
            'totalCustomerDebt' => 0,
            'deliveredOrders' => 0,
            'topProducts' => [],
            'monthlySeries' => collect(range(11, 0))->map(fn ($i) => ['label' => now()->subMonths($i)->format('M y'), 'revenue' => 0])->all(),
            'last7DaysSeries' => collect(range(6, 0))->map(fn ($i) => ['day' => now()->subDays($i)->format('D'), 'date' => now()->subDays($i)->format('d M'), 'revenue' => 0, 'orders' => 0])->all(),
            'paymentBreakdown' => [],
            'expenseBreakdown' => [],
            'servicePipeline' => ['received' => 0, 'in_progress' => 0, 'ready' => 0, 'delivered' => 0],
        ], $stats);

        // Recent Activity with eager loading (not cached — needs freshness)
        $recentOrders = Order::where('store_id', $storeId)->with('user')->latest()->take(5)->get();
        $recentWholesale = WholesaleApplication::where('store_id', $storeId)->with('user')->latest()->take(5)->get();
        $recentProducts = Product::where('store_id', $storeId)->with(['category', 'brand'])->latest()->take(5)->get();

        // ── Overdue supplier payables ──────────────────────────────────────
        $overdueData = $this->overduePayables($storeId);

        // Staff-tool access + open cashier shift for the current user — powers
        // the POS quick-action strip in the dashboard header. (Computed here
        // because layout-composer vars don't reach the child dashboard view.)
        $canAccessStaffTools = $user->hasStoreRole($store->id, ['store_manager', 'staff']);
        $canManageSettings = $user->hasStoreRole($store->id, 'store_manager');
        $openShift = $canAccessStaffTools ? $this->shifts->openShiftFor($store, $user) : null;

        return view('admin.dashboard', compact(
            'store',
            'stats',
            'recentOrders',
            'recentWholesale',
            'recentProducts',
            'openShift',
            'canAccessStaffTools',
            'canManageSettings',
            'overdueData'
        ) + $stats);
    }

    /**
     * Total store revenue since a timestamp: online orders (agreed amount
     * when set, else the original total; cancelled excluded) plus posted
     * POS counter sales. Same scope as the 7-day chart so the stat cards,
     * monthly chart and top products never disagree.
     */
    private function revenueSumSince(int $storeId, CarbonInterface $since): float
    {
        return $this->revenueSumBetween($storeId, $since, null);
    }

    /**
     * Revenue inside a window (or since a point when $until is null):
     * online orders + posted POS counter sales minus returns.
     *
     * Online orders only count once the shop has confirmed them: a
     * `pending_contact` order is a request the counter has not agreed a price
     * for yet, and counting it made "today's revenue" show money nobody has
     * earned or collected.
     */
    private function revenueSumBetween(int $storeId, CarbonInterface $start, ?CarbonInterface $until): float
    {
        $webQuery = Order::where('store_id', $storeId)->whereIn('status', self::REVENUE_ORDER_STATUSES);
        $posQuery = \App\POS\Models\PosSale::where('store_id', $storeId)->whereIn('status', ['posted', 'partially_refunded', 'refunded']);
        $retQuery = \App\POS\Models\PosReturn::where('store_id', $storeId)->where('status', 'posted');

        if ($until) {
            $webQuery->whereBetween('created_at', [$start, $until]);
            $posQuery->whereBetween('posted_at', [$start, $until]);
            $retQuery->whereBetween('posted_at', [$start, $until]);
        } else {
            $webQuery->where('created_at', '>=', $start);
            $posQuery->where('posted_at', '>=', $start);
            $retQuery->where('posted_at', '>=', $start);
        }

        $web = (float) $webQuery
            ->selectRaw('COALESCE(SUM(COALESCE(agreed_amount, total_amount)), 0) as revenue')
            ->value('revenue');
        $pos = (float) $posQuery->sum('total');
        $ret = (float) $retQuery->sum('total');

        return max(0.0, ($web + $pos) - $ret);
    }

    /**
     * Bill count inside a window: online orders + POS sales (including
     * partially/fully refunded sales to track total customer transactions).
     */
    private function ordersBetween(int $storeId, CarbonInterface $start, ?CarbonInterface $until): int
    {
        $webQuery = Order::where('store_id', $storeId)->where('status', '!=', 'cancelled');
        $posQuery = \App\POS\Models\PosSale::where('store_id', $storeId)->whereIn('status', ['posted', 'partially_refunded', 'refunded']);

        if ($until) {
            $webQuery->whereBetween('created_at', [$start, $until]);
            $posQuery->whereBetween('posted_at', [$start, $until]);
        } else {
            $webQuery->where('created_at', '>=', $start);
            $posQuery->where('posted_at', '>=', $start);
        }

        return $webQuery->count() + $posQuery->count();
    }

    /**
     * Bill count since a timestamp: online orders + POS sales,
     * matching the revenue scope above.
     */
    private function ordersSince(int $storeId, CarbonInterface $since): int
    {
        return $this->ordersBetween($storeId, $since, null);
    }

    /**
     * Compute overdue supplier payables summary for the dashboard.
     */
    private function overduePayables(int $storeId): array
    {
        $today = now()->startOfDay();

        $suppliers = \App\Models\Supplier::where('store_id', $storeId)
            ->whereRaw('total_credit - total_repaid > 0')
            ->get();

        $overdueSuppliers = [];
        $totalOverdue = 0;
        $overdueCount = 0;

        foreach ($suppliers as $supplier) {
            $unpaidPos = \App\POS\Models\PurchaseOrder::where('supplier_id', $supplier->id)
                ->where('status', 'received')
                ->whereRaw('remaining_balance > 0')
                ->get();

            $totalOutstanding = 0;
            $maxAgeDays = 0;

            foreach ($unpaidPos as $po) {
                $age = (int) $po->received_at->diffInDays($today);
                $amount = (float) $po->remaining_balance;
                $totalOutstanding += $amount;
                $maxAgeDays = max($maxAgeDays, $age);
            }

            if ($totalOutstanding > 0 && $maxAgeDays > 30) {
                $overdueSuppliers[] = [
                    'id'       => $supplier->id,
                    'name'     => $supplier->name,
                    'amount'   => $totalOutstanding,
                    'age_days' => $maxAgeDays,
                    'po_count' => $unpaidPos->count(),
                ];
                $totalOverdue += $totalOutstanding;
                $overdueCount++;
            }
        }

        usort($overdueSuppliers, fn($a, $b) => $b['age_days'] <=> $a['age_days']);

        return [
            'total_overdue'     => $totalOverdue,
            'overdue_count'     => $overdueCount,
            'overdue_suppliers' => array_slice($overdueSuppliers, 0, 5),
        ];
    }
}
