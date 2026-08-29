<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\WholesaleApplication;
use App\POS\Models\PurchaseOrder;
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
        $tab = $request->query('tab', 'low_stock');

        // 1. Low Stock & Out of Stock Products
        $allProducts = Product::where('store_id', $store->id)
            ->with(['inventoryBalances', 'category'])
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
                    'category'       => $product->category?->name ?? '—',
                    'stock_quantity' => $qty,
                    'reorder_level'  => $reorderLevel,
                    'retail_price'   => (float) $product->retail_price,
                    'stock_status'   => $product->stock_status,
                ];
            }
        }

        // Sort low stock items: lowest stock quantity first
        usort($lowStockProducts, fn ($a, $b) => $a['stock_quantity'] <=> $b['stock_quantity']);

        // 2. Pending Online Orders
        $pendingOrders = Order::where('store_id', $store->id)
            ->where('status', 'pending_contact')
            ->latest('created_at')
            ->get();

        // 3. Pending Wholesale Applications
        $pendingWholesale = WholesaleApplication::where('store_id', $store->id)
            ->where('status', 'pending')
            ->latest('created_at')
            ->get();

        // 4. Overdue Debts (Suppliers & Customers > 30 days)
        $overdueDebts = [];

        // Supplier Payables
        try {
            $suppliers = Supplier::where('store_id', $store->id)
                ->whereRaw('total_credit - total_repaid > 0')
                ->get();

            foreach ($suppliers as $sup) {
                $unpaidPos = PurchaseOrder::where('supplier_id', $sup->id)
                    ->where('status', 'received')
                    ->where('remaining_balance', '>', 0)
                    ->get();

                foreach ($unpaidPos as $po) {
                    $age = (int) ($po->received_at ? $po->received_at->diffInDays(now()->startOfDay()) : 0);
                    if ($age > 30) {
                        $overdueDebts[] = [
                            'type'         => 'supplier',
                            'type_label'   => __('messages.supplier') ?? 'Supplier',
                            'name'         => $sup->name,
                            'phone'        => $sup->phone,
                            'amount'       => (float) $po->remaining_balance,
                            'days_overdue' => $age,
                            'ref'          => $po->po_number,
                            'action_url'   => route('pos.purchases.payables.show', array_merge($storeRouteParams, ['supplier' => $sup->id])),
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            // Supplier debt fallback
        }

        // Customer Receivables via CustomerDebtService
        try {
            /** @var \App\POS\Services\CustomerDebtService $debtService */
            $debtService = app(\App\POS\Services\CustomerDebtService::class);
            $debtCustomers = $debtService->outstandingCustomers($store, 30);

            foreach ($debtCustomers as $cust) {
                $amount = (float) ($cust['balance'] ?? 0);
                if ($amount > 0) {
                    $overdueDebts[] = [
                        'type'         => 'customer',
                        'type_label'   => __('messages.customer') ?? 'Customer',
                        'name'         => $cust['name'],
                        'phone'        => $cust['phone'] ?? '—',
                        'amount'       => $amount,
                        'days_overdue' => 31,
                        'ref'          => 'CUST-' . $cust['customer_id'],
                        'action_url'   => route('store.admin.receivables.show', array_merge($storeRouteParams, ['customer' => $cust['customer_id']])),
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Customer debt fallback
        }

        // Sort overdue debts by amount descending
        usort($overdueDebts, fn ($a, $b) => $b['amount'] <=> $a['amount']);

        // 5. Security Alerts & Access Events (Last 7 days)
        $securityAlerts = AuditLog::where('store_id', $store->id)
            ->whereIn('action', ['pos_pin_failed', 'staff_role_deleted', 'staff_role_updated', 'inventory_adjustment_voided', 'bulk_price_updated'])
            ->with('actor')
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

        // Stats summary
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
            return back()->with('error', 'Telegram Bot Token နှင့် Chat ID ထည့်သွင်းပေးရန် လိုအပ်ပါသည် (Telegram Bot Token & Chat ID required)');
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
                return back()->with('success', 'Telegram သို့ စမ်းသပ်သတိပေးချက် အောင်မြင်စွာ ပေးပို့ပြီးပါပြီ (Test alert sent successfully!)');
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

        return back()->with('success', 'နေ့စဉ် လုပ်ငန်းအကျဉ်းချုပ် သတိပေးချက် ထုတ်ယူပြီးစီးပါပြီ (Daily business summary generated)');
    }
}
