<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GlassFinderItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\WholesaleApplication;
use App\POS\Services\CashierShiftService;
use App\Services\StoreContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Cache TTL for aggregation stats (seconds).
     * Dashboard counts don't need real-time freshness —
     * 60-second stale-while-revalidate reduces DB load significantly.
     */
    private const STATS_CACHE_TTL = 60;

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
            $stores = Store::all();
            return view('admin.dashboard_select_store', compact('stores'));
        }

        $store = $context->getStore()
            ?? Store::where('is_active', true)->where('is_primary', true)->orderBy('id')->first()
            ?? Store::first();

        if (!$store) {
            $store = new Store(['id' => 0, 'name' => 'Default Store', 'slug' => 'default']);
        }

        $storeId = $store->id;

        // ── Cached aggregation stats (60-second TTL) ──────────────────────
        $stats = Cache::remember('dashboard.stats.' . $storeId, self::STATS_CACHE_TTL, function () use ($storeId) {
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
                'todayOrders'        => Order::where('store_id', $storeId)->where('created_at', '>=', $todayStart)->count(),
                'todayRevenue'       => $this->revenueSumSince($storeId, $todayStart),
                'weekOrders'         => Order::where('store_id', $storeId)->where('created_at', '>=', $weekStart)->count(),
                'weekRevenue'        => $this->revenueSumSince($storeId, $weekStart),
                'monthOrders'        => Order::where('store_id', $storeId)->where('created_at', '>=', $monthStart)->count(),
                'monthRevenue'       => $this->revenueSumSince($storeId, $monthStart),
                'yearRevenue'        => $this->revenueSumSince($storeId, $yearStart),
                // Top 5 products by total quantity sold (from order line items).
                'topProducts'        => \App\Models\OrderItem::query()
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->where('orders.store_id', $storeId)
                    ->selectRaw('order_items.product_name as name, SUM(order_items.quantity) as qty, SUM(order_items.subtotal) as sales')
                    ->groupBy('order_items.product_name')
                    ->orderByDesc('qty')
                    ->take(5)
                    ->get(),
                // Last 12 months revenue series (for the bar chart).
                'monthlySeries'      => collect(range(11, 0))->map(function ($i) use ($storeId) {
                    $month = now()->subMonths($i);
                    $revenue = (float) Order::where('store_id', $storeId)
                        ->where('status', '!=', 'cancelled')
                        ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                        ->selectRaw('COALESCE(SUM(COALESCE(agreed_amount, total_amount)), 0) as revenue')
                        ->value('revenue');

                    return ['label' => $month->format('M y'), 'revenue' => $revenue];
                })->all(),
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
            'deliveredOrders' => 0,
            'topProducts' => collect(),
            'monthlySeries' => collect(range(11, 0))->map(fn ($i) => ['label' => now()->subMonths($i)->format('M y'), 'revenue' => 0])->all(),
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
     * Revenue (SUM of agreed amount when set, else the original total)
     * for orders created since a given timestamp — mirrors the order
     * export's revenue calculation so dashboard numbers match.
     * Cancelled orders are excluded (they are not revenue).
     */
    private function revenueSumSince(int $storeId, Carbon $since): float
    {
        return (float) Order::where('store_id', $storeId)
            ->where('status', '!=', 'cancelled')
            ->where('created_at', '>=', $since)
            ->selectRaw('COALESCE(SUM(COALESCE(agreed_amount, total_amount)), 0) as revenue')
            ->value('revenue');
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