<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\WholesaleApplication;
use App\Services\StoreContext;
use Illuminate\Http\JsonResponse;

/**
 * Lightweight polling endpoint backing the admin "new order / wholesale
 * application" alerts (chime sound + browser notification).
 *
 * The client polls this every ~30s and compares max_order_id / max_app_id
 * against its last-seen values to detect fresh arrivals.
 */
class AdminAlertController extends Controller
{
    public function check(StoreContext $context): JsonResponse
    {
        $store = $context->getStore();

        abort_unless($store, 404, 'Store not found.');

        $pendingOrders = Order::where('store_id', $store->id)
            ->where('status', 'pending_contact')
            ->count();

        $pendingWholesale = WholesaleApplication::where('store_id', $store->id)
            ->where('status', 'pending')
            ->count();

        $todayOrders = Order::where('store_id', $store->id)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        // Overdue supplier payables (> 30 days old)
        $overdueSuppliers = \App\Models\Supplier::where('store_id', $store->id)
            ->whereRaw('total_credit - total_repaid > 0')
            ->get();
        $overdueCount = 0;
        $totalOverdue = 0;
        foreach ($overdueSuppliers as $sup) {
            $unpaidPos = \App\POS\Models\PurchaseOrder::where('supplier_id', $sup->id)
                ->where('status', 'received')
                ->whereRaw('remaining_balance > 0')
                ->get();
            foreach ($unpaidPos as $po) {
                $age = (int) $po->received_at->diffInDays(now()->startOfDay());
                if ($age > 30) {
                    $totalOverdue += (float) $po->remaining_balance;
                    $overdueCount++;
                    break; // count supplier once
                }
            }
        }

        $latestOrder = Order::where('store_id', $store->id)->latest('id')->first();
        $latestApp = WholesaleApplication::where('store_id', $store->id)->latest('id')->first();

        return response()->json([
            'pending_orders' => $pendingOrders,
            'pending_wholesale' => $pendingWholesale,
            'today_orders' => $todayOrders,
            'max_order_id' => $latestOrder?->id ?? 0,
            'max_app_id' => $latestApp?->id ?? 0,
            'latest_order' => $latestOrder ? [
                'order_number' => $latestOrder->order_number,
                'customer_name' => $latestOrder->customer_name,
                'total' => $latestOrder->agreed_amount ?? $latestOrder->total_amount,
            ] : null,
            'overdue_count' => $overdueCount,
            'total_overdue' => $totalOverdue,
            'latest_app' => $latestApp ? [
                'business_name' => $latestApp->business_name,
                'phone' => $latestApp->phone,
            ] : null,
        ]);
    }
}
