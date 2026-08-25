<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\WholesaleApplication;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class SystemAlertCenterController extends Controller
{
    public function index(string $store_slug, Request $request, StoreContext $context): View
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $storeRouteParams = ['store_slug' => $store->slug];
        $tab = $request->query('tab', 'all');

        // 1. Low Stock Products (inventory balance <= reorder_level or stock_status === 'out_of_stock')
        $allProducts = Product::where('store_id', $store->id)
            ->with('inventoryBalances')
            ->get();

        $lowStockProducts = [];
        foreach ($allProducts as $product) {
            $balances = $product->inventoryBalances;
            $qty = $balances->isNotEmpty()
                ? (float) $balances->sum('quantity_on_hand')
                : ($product->stock_status === 'in_stock' ? 1.0 : 0.0);

            $reorderLevel = (float) ($product->reorder_level ?? 5.0);

            if ($qty <= $reorderLevel || $product->stock_status === 'out_of_stock') {
                $lowStockProducts[] = [
                    'id'             => $product->id,
                    'name'           => $product->name,
                    'sku'            => $product->sku,
                    'stock_quantity' => $qty,
                    'reorder_level'  => $reorderLevel,
                    'retail_price'   => (float) $product->retail_price,
                    'stock_status'   => $product->stock_status,
                ];
            }
        }

        // 2. Pending Orders
        $pendingOrders = Order::where('store_id', $store->id)
            ->where('status', 'pending_contact')
            ->latest('created_at')
            ->get();

        // 3. Pending Wholesale Applications
        $pendingWholesale = WholesaleApplication::where('store_id', $store->id)
            ->where('status', 'pending')
            ->latest('created_at')
            ->get();

        // 4. Overdue Debt Accounts (> 30 days)
        $overdueDebts = [];
        try {
            $suppliers = \App\Models\Supplier::where('store_id', $store->id)
                ->whereRaw('total_credit - total_repaid > 0')
                ->get();

            foreach ($suppliers as $sup) {
                $unpaidPos = \App\POS\Models\PurchaseOrder::where('supplier_id', $sup->id)
                    ->where('status', 'received')
                    ->whereRaw('remaining_balance > 0')
                    ->get();

                foreach ($unpaidPos as $po) {
                    $age = (int) ($po->received_at ? $po->received_at->diffInDays(now()->startOfDay()) : 0);
                    if ($age > 30) {
                        $overdueDebts[] = [
                            'type'         => 'supplier',
                            'name'         => $sup->name,
                            'phone'        => $sup->phone,
                            'amount'       => (float) $po->remaining_balance,
                            'days_overdue' => $age,
                            'ref'          => $po->po_number,
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            // Supplier debt schema fallback
        }

        // 5. Security & Failed PIN Warnings (Last 7 days)
        $securityAlerts = AuditLog::where('store_id', $store->id)
            ->whereIn('action', ['pos_pin_failed', 'staff_role_deleted', 'staff_role_updated'])
            ->where('created_at', '>=', now()->subDays(7))
            ->latest('created_at')
            ->get();

        // 6. Today's Business Performance Summary
        $todayOrders = Order::where('store_id', $store->id)
            ->whereDate('created_at', today())
            ->get();

        $todaySalesCount = $todayOrders->count();
        $todayRevenue = (float) $todayOrders
            ->whereIn('status', ['confirmed', 'delivered'])
            ->sum(fn ($o) => $o->agreed_amount ?? $o->total_amount);

        // KPI stats
        $criticalAlertsCount = count($lowStockProducts) + count($pendingOrders) + count($pendingWholesale) + count($overdueDebts);

        $stats = [
            'critical_total'     => $criticalAlertsCount,
            'low_stock_count'    => count($lowStockProducts),
            'pending_orders'     => count($pendingOrders),
            'pending_wholesale'  => count($pendingWholesale),
            'overdue_debt_count' => count($overdueDebts),
            'security_warnings'  => count($securityAlerts),
            'today_sales'        => $todayRevenue,
            'today_orders_count' => $todaySalesCount,
        ];

        // Telegram Bot / Alert Settings from store settings or defaults
        $setting = $store->setting;

        return view('admin.alerts.index', compact(
            'store',
            'storeRouteParams',
            'stats',
            'tab',
            'lowStockProducts',
            'pendingOrders',
            'pendingWholesale',
            'overdueDebts',
            'securityAlerts',
            'setting'
        ));
    }

    /**
     * Send instant test ping notification to Telegram Bot.
     */
    public function testNotification(string $store_slug, Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        $validated = $request->validate([
            'telegram_bot_token' => ['nullable', 'string'],
            'telegram_chat_id'   => ['nullable', 'string'],
        ]);

        $botToken = $validated['telegram_bot_token'] ?? env('TELEGRAM_BOT_TOKEN');
        $chatId = $validated['telegram_chat_id'] ?? env('TELEGRAM_ALERT_CHAT_ID');

        if (empty($botToken) || empty($chatId)) {
            return back()->with('error', 'Please configure Telegram Bot Token and Chat ID to send test notifications. (Telegram Bot Token နှင့် Chat ID ထည့်သွင်းပေးပါ)');
        }

        $message = "🔔 <b>[DataPOS Alert Test]</b>\n"
                 . "🏪 <b>Store:</b> " . htmlspecialchars($store->name) . "\n"
                 . "⏰ <b>Time:</b> " . now()->format('d M Y, h:i A') . "\n"
                 . "✅ System Notification Channel is connected and active!";

        try {
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id'    => $chatId,
                'text'       => $message,
                'parse_mode' => 'HTML',
            ]);

            if ($response->successful()) {
                return back()->with('success', 'Telegram test alert delivered successfully! (Telegram သို့ သတိပေးချက် စမ်းသပ်ပေးပို့ပြီးပါပြီ)');
            }

            return back()->with('error', 'Telegram API Error: ' . ($response->json('description') ?? 'Failed to send message'));
        } catch (\Throwable $e) {
            return back()->with('error', 'Notification connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Generate and dispatch Today's Daily Business Summary Notification.
     */
    public function sendDailySummary(string $store_slug, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        $todayOrders = Order::where('store_id', $store->id)->whereDate('created_at', today())->get();
        $confirmedRev = $todayOrders->whereIn('status', ['confirmed', 'delivered'])->sum(fn ($o) => $o->agreed_amount ?? $o->total_amount);
        $pendingCount = $todayOrders->where('status', 'pending_contact')->count();
        $lowStockCount = Product::where('store_id', $store->id)
            ->where(function ($q) {
                $q->where('stock_status', 'out_of_stock')
                  ->orWhereHas('inventoryBalances', function ($bq) {
                      $bq->where('quantity_on_hand', '<=', 5);
                  });
            })->count();

        $summaryText = "📊 <b>[DataPOS Daily Business Summary]</b>\n"
                     . "🏪 <b>Store:</b> " . htmlspecialchars($store->name) . "\n"
                     . "📅 <b>Date:</b> " . now()->format('d M Y') . "\n\n"
                     . "💰 <b>Today's Confirmed Sales:</b> " . number_format($confirmedRev) . " Ks\n"
                     . "🛒 <b>Total Orders:</b> " . $todayOrders->count() . " orders\n"
                     . "⏳ <b>Pending Contact:</b> " . $pendingCount . " orders\n"
                     . "⚠️ <b>Low Stock Items:</b> " . $lowStockCount . " items\n\n"
                     . "<i>Generated by DataPOS System Monitor</i>";

        AuditLog::write(
            $store->id,
            'daily_summary_alert_dispatched',
            'store',
            $store->id,
            ['confirmed_revenue' => $confirmedRev, 'orders_count' => $todayOrders->count()],
            auth()->id(),
            request()->ip()
        );

        return back()->with('success', 'Daily Business Summary compiled successfully! (နေ့စဉ် အရောင်းနှင့် စတော့ အကျဉ်းချုပ် သတိပေးချက် ထုတ်ယူပြီးပါပြီ)');
    }

}
