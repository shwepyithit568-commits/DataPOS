<?php

namespace App\POS\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\PosPayment;
use App\POS\Models\PosSale;
use App\POS\Models\PosSaleItem;
use Carbon\Carbon;

class SalesAnalyticsService
{
    /**
     * Generate complete multi-dimensional sales analytics report.
     */
    public function generateReport(Store $store, Carbon $from, Carbon $to, string $channel = 'all'): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        // 1. KPI Summary
        $kpi = $this->calculateKpis($store, $from, $to, $channel);

        // 2. Timeline Series (Daily)
        $timeline = $this->generateTimeline($store, $from, $to, $channel);

        // 3. Hourly Distribution (24 Hours)
        $hourly = $this->generateHourlyDistribution($store, $from, $to, $channel);

        // 4. Day of Week Pattern
        $dayOfWeek = $this->generateDayOfWeekPattern($store, $from, $to, $channel);

        // 5. Top Selling Products
        $topProducts = $this->getTopSellingProducts($store, $from, $to, $channel, 10);

        // 6. Category & Brand Shares
        $categoryShare = $this->getCategoryShare($store, $from, $to, $channel);
        $brandShare = $this->getBrandShare($store, $from, $to, $channel);

        // 7. Cashier / Staff Performance (POS)
        $cashierPerformance = $this->getCashierPerformance($store, $from, $to);

        // 8. Payment Method Distribution
        $paymentMethods = $this->getPaymentMethodDistribution($store, $from, $to, $channel);

        // 9. Channel Comparison
        $channels = $this->getChannelComparison($store, $from, $to);

        return [
            'kpi'                => $kpi,
            'timeline'           => $timeline,
            'hourly'             => $hourly,
            'day_of_week'        => $dayOfWeek,
            'top_products'       => $topProducts,
            'category_share'     => $categoryShare,
            'brand_share'        => $brandShare,
            'cashier_performance'=> $cashierPerformance,
            'payment_methods'    => $paymentMethods,
            'channels'           => $channels,
            'date_range'         => [
                'from' => $from->toDateString(),
                'to'   => $to->toDateString(),
                'days' => $from->diffInDays($to) + 1,
            ],
        ];
    }

    /**
     * Calculate core KPIs across POS and Online channels.
     */
    protected function calculateKpis(Store $store, Carbon $from, Carbon $to, string $channel): array
    {
        $posRevenue = 0.0;
        $posDiscounts = 0.0;
        $posOrdersCount = 0;
        $posItemsCount = 0.0;
        $posCost = 0.0;

        if (in_array($channel, ['all', 'pos'], true)) {
            $posQuery = PosSale::where('store_id', $store->id)
                ->whereIn('status', ['posted', 'partially_refunded', 'refunded', 'reversed'])
                ->whereBetween('posted_at', [$from, $to]);

            $posRevenue = (float) $posQuery->sum('total');
            $posDiscounts = (float) $posQuery->sum('discount');
            $posOrdersCount = (int) $posQuery->count();

            // POS Items & Cost
            $posItemsData = PosSaleItem::join('pos_sales', 'pos_sale_items.pos_sale_id', '=', 'pos_sales.id')
                ->where('pos_sales.store_id', $store->id)
                ->whereIn('pos_sales.status', ['posted', 'partially_refunded', 'refunded', 'reversed'])
                ->whereBetween('pos_sales.posted_at', [$from, $to])
                ->selectRaw('SUM(pos_sale_items.quantity) as total_qty, SUM(pos_sale_items.quantity * pos_sale_items.unit_cost) as total_cost')
                ->first();

            $posItemsCount = (float) ($posItemsData->total_qty ?? 0);
            $posCost = (float) ($posItemsData->total_cost ?? 0);
        }

        $onlineRevenue = 0.0;
        $onlineDiscounts = 0.0;
        $onlineOrdersCount = 0;
        $onlineItemsCount = 0.0;
        $onlineCost = 0.0;

        if (in_array($channel, ['all', 'online'], true)) {
            $onlineOrders = Order::where('store_id', $store->id)
                ->whereIn('status', ['confirmed', 'delivered', 'completed'])
                ->whereBetween('created_at', [$from, $to])
                ->get(['id', 'total_amount', 'agreed_amount']);

            $onlineOrdersCount = $onlineOrders->count();
            foreach ($onlineOrders as $o) {
                $totalAmt = (float) ($o->total_amount ?? 0);
                $agreedAmt = (float) ($o->agreed_amount ?? $totalAmt);
                $rev = $agreedAmt > 0 ? $agreedAmt : $totalAmt;
                $onlineRevenue += $rev;
                if ($totalAmt > $agreedAmt && $agreedAmt > 0) {
                    $onlineDiscounts += ($totalAmt - $agreedAmt);
                }
            }

            // Online Items & Cost
            $onlineItemsData = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
                ->where('orders.store_id', $store->id)
                ->whereIn('orders.status', ['confirmed', 'delivered', 'completed'])
                ->whereBetween('orders.created_at', [$from, $to])
                ->selectRaw('SUM(order_items.quantity) as total_qty, SUM(order_items.quantity * COALESCE(products.purchase_cost, 0)) as total_cost')
                ->first();

            $onlineItemsCount = (float) ($onlineItemsData->total_qty ?? 0);
            $onlineCost = (float) ($onlineItemsData->total_cost ?? 0);
        }

        $netSales = $posRevenue + $onlineRevenue;
        $totalDiscounts = $posDiscounts + $onlineDiscounts;
        $grossSales = $netSales + $totalDiscounts;
        $totalOrders = $posOrdersCount + $onlineOrdersCount;
        $totalItems = $posItemsCount + $onlineItemsCount;
        $totalCost = $posCost + $onlineCost;
        $grossProfit = $netSales - $totalCost;
        $grossMargin = $netSales > 0 ? round(($grossProfit / $netSales) * 100, 2) : 0.0;
        $aov = $totalOrders > 0 ? round($netSales / $totalOrders, 2) : 0.0;

        return [
            'gross_sales'     => $grossSales,
            'discounts'       => $totalDiscounts,
            'net_sales'       => $netSales,
            'total_orders'    => $totalOrders,
            'total_items'     => $totalItems,
            'total_cost'      => $totalCost,
            'gross_profit'    => $grossProfit,
            'gross_margin'    => $grossMargin,
            'aov'             => $aov,
            'pos_revenue'     => $posRevenue,
            'pos_orders'      => $posOrdersCount,
            'online_revenue'  => $onlineRevenue,
            'online_orders'   => $onlineOrdersCount,
        ];
    }

    /**
     * Generate daily timeline data points for revenue & volume.
     */
    protected function generateTimeline(Store $store, Carbon $from, Carbon $to, string $channel): array
    {
        $timeline = [];
        $cursor = $from->copy();
        $daysCount = $from->diffInDays($to) + 1;

        // Initialize daily buckets
        while ($cursor->lte($to)) {
            $key = $cursor->format('Y-m-d');
            $timeline[$key] = [
                'date'        => $key,
                'label'       => $daysCount <= 7 ? $cursor->format('D, M j') : $cursor->format('M j'),
                'short_day'   => $cursor->format('D'),
                'revenue'     => 0.0,
                'orders'      => 0,
                'items'       => 0.0,
                'pos_revenue' => 0.0,
                'web_revenue' => 0.0,
            ];
            $cursor->addDay();
        }

        // Fill POS Sales
        if (in_array($channel, ['all', 'pos'], true)) {
            $posSales = PosSale::where('store_id', $store->id)
                ->whereIn('status', ['posted', 'partially_refunded', 'refunded', 'reversed'])
                ->whereBetween('posted_at', [$from, $to])
                ->get(['id', 'total', 'posted_at']);

            foreach ($posSales as $s) {
                if ($s->posted_at) {
                    $d = $s->posted_at->format('Y-m-d');
                    if (isset($timeline[$d])) {
                        $timeline[$d]['revenue'] += (float) $s->total;
                        $timeline[$d]['pos_revenue'] += (float) $s->total;
                        $timeline[$d]['orders'] += 1;
                    }
                }
            }
        }

        // Fill Online Orders
        if (in_array($channel, ['all', 'online'], true)) {
            $onlineSales = Order::where('store_id', $store->id)
                ->whereIn('status', ['confirmed', 'delivered', 'completed'])
                ->whereBetween('created_at', [$from, $to])
                ->get(['id', 'total_amount', 'agreed_amount', 'created_at']);

            foreach ($onlineSales as $s) {
                if ($s->created_at) {
                    $d = $s->created_at->format('Y-m-d');
                    $rev = (float) ($s->agreed_amount ?: $s->total_amount);
                    if (isset($timeline[$d])) {
                        $timeline[$d]['revenue'] += $rev;
                        $timeline[$d]['web_revenue'] += $rev;
                        $timeline[$d]['orders'] += 1;
                    }
                }
            }
        }

        $series = array_values($timeline);
        $maxRevenue = !empty($series) ? max(array_column($series, 'revenue')) : 1.0;
        $maxOrders = !empty($series) ? max(array_column($series, 'orders')) : 1;

        return [
            'series'      => $series,
            'max_revenue' => $maxRevenue > 0 ? $maxRevenue : 1.0,
            'max_orders'  => $maxOrders > 0 ? $maxOrders : 1,
        ];
    }

    /**
     * 24-Hour Peak Sales Distribution.
     */
    protected function generateHourlyDistribution(Store $store, Carbon $from, Carbon $to, string $channel): array
    {
        $hours = [];
        for ($h = 0; $h < 24; $h++) {
            $formattedHour = str_pad((string) $h, 2, '0', STR_PAD_LEFT) . ':00';
            $hours[$h] = [
                'hour'       => $h,
                'label'      => $formattedHour,
                'display'    => Carbon::createFromTime($h, 0)->format('g A'),
                'revenue'    => 0.0,
                'orders'     => 0,
            ];
        }

        // Aggregate POS hourly
        if (in_array($channel, ['all', 'pos'], true)) {
            $posSales = PosSale::where('store_id', $store->id)
                ->whereIn('status', ['posted', 'partially_refunded', 'refunded', 'reversed'])
                ->whereBetween('posted_at', [$from, $to])
                ->get(['id', 'total', 'posted_at']);

            foreach ($posSales as $row) {
                if ($row->posted_at) {
                    $hr = (int) $row->posted_at->format('G');
                    if (isset($hours[$hr])) {
                        $hours[$hr]['revenue'] += (float) $row->total;
                        $hours[$hr]['orders'] += 1;
                    }
                }
            }
        }

        // Aggregate Online hourly
        if (in_array($channel, ['all', 'online'], true)) {
            $onlineSales = Order::where('store_id', $store->id)
                ->whereIn('status', ['confirmed', 'delivered', 'completed'])
                ->whereBetween('created_at', [$from, $to])
                ->get(['id', 'total_amount', 'agreed_amount', 'created_at']);

            foreach ($onlineSales as $row) {
                if ($row->created_at) {
                    $hr = (int) $row->created_at->format('G');
                    $rev = (float) ($row->agreed_amount ?: $row->total_amount);
                    if (isset($hours[$hr])) {
                        $hours[$hr]['revenue'] += $rev;
                        $hours[$hr]['orders'] += 1;
                    }
                }
            }
        }

        $series = array_values($hours);
        $maxRev = !empty($series) ? max(array_column($series, 'revenue')) : 1.0;
        $maxOrders = !empty($series) ? max(array_column($series, 'orders')) : 1;

        // Find peak hour
        $peakHour = null;
        $peakRev = -1.0;
        foreach ($series as $item) {
            if ($item['revenue'] > $peakRev) {
                $peakRev = $item['revenue'];
                $peakHour = $item;
            }
        }

        return [
            'hours'       => $series,
            'max_revenue' => $maxRev > 0 ? $maxRev : 1.0,
            'max_orders'  => $maxOrders > 0 ? $maxOrders : 1,
            'peak_hour'   => $peakHour,
        ];
    }

    /**
     * Day of week sales pattern (Monday - Sunday).
     */
    protected function generateDayOfWeekPattern(Store $store, Carbon $from, Carbon $to, string $channel): array
    {
        $days = [
            1 => ['name' => 'Monday', 'short' => 'Mon', 'revenue' => 0.0, 'orders' => 0],
            2 => ['name' => 'Tuesday', 'short' => 'Tue', 'revenue' => 0.0, 'orders' => 0],
            3 => ['name' => 'Wednesday', 'short' => 'Wed', 'revenue' => 0.0, 'orders' => 0],
            4 => ['name' => 'Thursday', 'short' => 'Thu', 'revenue' => 0.0, 'orders' => 0],
            5 => ['name' => 'Friday', 'short' => 'Fri', 'revenue' => 0.0, 'orders' => 0],
            6 => ['name' => 'Saturday', 'short' => 'Sat', 'revenue' => 0.0, 'orders' => 0],
            7 => ['name' => 'Sunday', 'short' => 'Sun', 'revenue' => 0.0, 'orders' => 0],
        ];

        if (in_array($channel, ['all', 'pos'], true)) {
            $posSales = PosSale::where('store_id', $store->id)
                ->whereIn('status', ['posted', 'partially_refunded', 'refunded', 'reversed'])
                ->whereBetween('posted_at', [$from, $to])
                ->get(['id', 'total', 'posted_at']);

            foreach ($posSales as $row) {
                if ($row->posted_at) {
                    $idx = (int) $row->posted_at->dayOfWeekIso; // 1=Mon, 7=Sun
                    if (isset($days[$idx])) {
                        $days[$idx]['revenue'] += (float) $row->total;
                        $days[$idx]['orders'] += 1;
                    }
                }
            }
        }

        if (in_array($channel, ['all', 'online'], true)) {
            $onlineSales = Order::where('store_id', $store->id)
                ->whereIn('status', ['confirmed', 'delivered', 'completed'])
                ->whereBetween('created_at', [$from, $to])
                ->get(['id', 'total_amount', 'agreed_amount', 'created_at']);

            foreach ($onlineSales as $row) {
                if ($row->created_at) {
                    $idx = (int) $row->created_at->dayOfWeekIso; // 1=Mon, 7=Sun
                    $rev = (float) ($row->agreed_amount ?: $row->total_amount);
                    if (isset($days[$idx])) {
                        $days[$idx]['revenue'] += $rev;
                        $days[$idx]['orders'] += 1;
                    }
                }
            }
        }

        $series = array_values($days);
        $maxRev = !empty($series) ? max(array_column($series, 'revenue')) : 1.0;

        return [
            'days'        => $series,
            'max_revenue' => $maxRev > 0 ? $maxRev : 1.0,
        ];
    }

    /**
     * Top selling products by units and revenue.
     */
    public function getTopSellingProducts(Store $store, Carbon $from, Carbon $to, string $channel, int $limit = 10): array
    {
        $productAggregates = [];

        // 1. POS Items
        if (in_array($channel, ['all', 'pos'], true)) {
            $posItems = PosSaleItem::join('pos_sales', 'pos_sale_items.pos_sale_id', '=', 'pos_sales.id')
                ->where('pos_sales.store_id', $store->id)
                ->whereIn('pos_sales.status', ['posted', 'partially_refunded', 'refunded', 'reversed'])
                ->whereBetween('pos_sales.posted_at', [$from, $to])
                ->selectRaw('
                    pos_sale_items.product_id,
                    pos_sale_items.product_name,
                    pos_sale_items.sku,
                    SUM(pos_sale_items.quantity) as total_qty,
                    SUM(pos_sale_items.line_total) as total_revenue,
                    SUM(pos_sale_items.quantity * pos_sale_items.unit_cost) as total_cost
                ')
                ->groupBy('pos_sale_items.product_id', 'pos_sale_items.product_name', 'pos_sale_items.sku')
                ->get();

            foreach ($posItems as $item) {
                $pid = (int) $item->product_id;
                $key = $pid > 0 ? "prod_{$pid}" : 'sku_' . $item->sku;

                if (!isset($productAggregates[$key])) {
                    $productAggregates[$key] = [
                        'product_id'   => $pid,
                        'name'         => $item->product_name,
                        'sku'          => $item->sku,
                        'quantity'     => 0.0,
                        'revenue'      => 0.0,
                        'cost'         => 0.0,
                        'profit'       => 0.0,
                    ];
                }

                $productAggregates[$key]['quantity'] += (float) $item->total_qty;
                $productAggregates[$key]['revenue'] += (float) $item->total_revenue;
                $productAggregates[$key]['cost'] += (float) $item->total_cost;
            }
        }

        // 2. Online Items
        if (in_array($channel, ['all', 'online'], true)) {
            $onlineItems = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
                ->where('orders.store_id', $store->id)
                ->whereIn('orders.status', ['confirmed', 'delivered', 'completed'])
                ->whereBetween('orders.created_at', [$from, $to])
                ->selectRaw('
                    order_items.product_id,
                    order_items.product_name,
                    COALESCE(order_items.variant_sku, products.sku) as sku,
                    SUM(order_items.quantity) as total_qty,
                    SUM(order_items.subtotal) as total_revenue,
                    SUM(order_items.quantity * COALESCE(products.purchase_cost, 0)) as total_cost
                ')
                ->groupBy('order_items.product_id', 'order_items.product_name', 'order_items.variant_sku', 'products.sku')
                ->get();

            foreach ($onlineItems as $item) {
                $pid = (int) $item->product_id;
                $key = $pid > 0 ? "prod_{$pid}" : 'sku_' . $item->sku;

                if (!isset($productAggregates[$key])) {
                    $productAggregates[$key] = [
                        'product_id'   => $pid,
                        'name'         => $item->product_name,
                        'sku'          => $item->sku,
                        'quantity'     => 0.0,
                        'revenue'      => 0.0,
                        'cost'         => 0.0,
                        'profit'       => 0.0,
                    ];
                }

                $productAggregates[$key]['quantity'] += (float) $item->total_qty;
                $productAggregates[$key]['revenue'] += (float) $item->total_revenue;
                $productAggregates[$key]['cost'] += (float) $item->total_cost;
            }
        }

        // Calculate profit & sort by revenue descending
        $pids = array_filter(array_column($productAggregates, 'product_id'));
        $loadedProducts = !empty($pids)
            ? Product::where('store_id', $store->id)
                ->whereIn('id', $pids)
                ->with(['category', 'brand'])
                ->get()
                ->keyBy('id')
            : collect();

        foreach ($productAggregates as &$agg) {
            $agg['profit'] = round($agg['revenue'] - $agg['cost'], 2);
            $agg['margin'] = $agg['revenue'] > 0 ? round(($agg['profit'] / $agg['revenue']) * 100, 1) : 0.0;

            // Attach product metadata if exists
            $prod = $loadedProducts->get($agg['product_id']);
            $agg['category_name'] = $prod?->category?->name ?? 'General';
            $agg['brand_name'] = $prod?->brand?->name ?? '-';
            $agg['stock_status'] = $prod?->stock_status ?? 'unknown';
            $agg['image_url'] = $prod?->image_url ?? $prod?->featured_image_url ?? null;
        }
        unset($agg);

        uasort($productAggregates, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);

        return array_slice(array_values($productAggregates), 0, $limit);
    }

    /**
     * Category revenue shares.
     */
    protected function getCategoryShare(Store $store, Carbon $from, Carbon $to, string $channel): array
    {
        $categories = [];

        // POS
        if (in_array($channel, ['all', 'pos'], true)) {
            $posCat = PosSaleItem::join('pos_sales', 'pos_sale_items.pos_sale_id', '=', 'pos_sales.id')
                ->leftJoin('products', 'pos_sale_items.product_id', '=', 'products.id')
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->where('pos_sales.store_id', $store->id)
                ->whereIn('pos_sales.status', ['posted', 'partially_refunded', 'refunded', 'reversed'])
                ->whereBetween('pos_sales.posted_at', [$from, $to])
                ->selectRaw("COALESCE(categories.name, 'Uncategorized') as cat_name, SUM(pos_sale_items.line_total) as rev, SUM(pos_sale_items.quantity) as qty")
                ->groupBy('categories.name')
                ->get();

            foreach ($posCat as $row) {
                $cname = $row->cat_name ?: 'Uncategorized';
                if (!isset($categories[$cname])) {
                    $categories[$cname] = ['name' => $cname, 'revenue' => 0.0, 'quantity' => 0.0];
                }
                $categories[$cname]['revenue'] += (float) $row->rev;
                $categories[$cname]['quantity'] += (float) $row->qty;
            }
        }

        // Online
        if (in_array($channel, ['all', 'online'], true)) {
            $onlineCat = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->where('orders.store_id', $store->id)
                ->whereIn('orders.status', ['confirmed', 'delivered', 'completed'])
                ->whereBetween('orders.created_at', [$from, $to])
                ->selectRaw("COALESCE(categories.name, 'Uncategorized') as cat_name, SUM(order_items.subtotal) as rev, SUM(order_items.quantity) as qty")
                ->groupBy('categories.name')
                ->get();

            foreach ($onlineCat as $row) {
                $cname = $row->cat_name ?: 'Uncategorized';
                if (!isset($categories[$cname])) {
                    $categories[$cname] = ['name' => $cname, 'revenue' => 0.0, 'quantity' => 0.0];
                }
                $categories[$cname]['revenue'] += (float) $row->rev;
                $categories[$cname]['quantity'] += (float) $row->qty;
            }
        }

        $totalRev = array_sum(array_column($categories, 'revenue'));
        foreach ($categories as &$c) {
            $c['percent'] = $totalRev > 0 ? round(($c['revenue'] / $totalRev) * 100, 1) : 0.0;
        }
        unset($c);

        uasort($categories, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);

        return array_values($categories);
    }

    /**
     * Brand revenue shares.
     */
    protected function getBrandShare(Store $store, Carbon $from, Carbon $to, string $channel): array
    {
        $brands = [];

        // POS
        if (in_array($channel, ['all', 'pos'], true)) {
            $posBrands = PosSaleItem::join('pos_sales', 'pos_sale_items.pos_sale_id', '=', 'pos_sales.id')
                ->leftJoin('products', 'pos_sale_items.product_id', '=', 'products.id')
                ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
                ->where('pos_sales.store_id', $store->id)
                ->whereIn('pos_sales.status', ['posted', 'partially_refunded', 'refunded', 'reversed'])
                ->whereBetween('pos_sales.posted_at', [$from, $to])
                ->selectRaw("COALESCE(brands.name, 'No Brand') as b_name, SUM(pos_sale_items.line_total) as rev, SUM(pos_sale_items.quantity) as qty")
                ->groupBy('brands.name')
                ->get();

            foreach ($posBrands as $row) {
                $bname = $row->b_name ?: 'No Brand';
                if (!isset($brands[$bname])) {
                    $brands[$bname] = ['name' => $bname, 'revenue' => 0.0, 'quantity' => 0.0];
                }
                $brands[$bname]['revenue'] += (float) $row->rev;
                $brands[$bname]['quantity'] += (float) $row->qty;
            }
        }

        // Online
        if (in_array($channel, ['all', 'online'], true)) {
            $onlineBrands = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
                ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
                ->where('orders.store_id', $store->id)
                ->whereIn('orders.status', ['confirmed', 'delivered', 'completed'])
                ->whereBetween('orders.created_at', [$from, $to])
                ->selectRaw("COALESCE(brands.name, 'No Brand') as b_name, SUM(order_items.subtotal) as rev, SUM(order_items.quantity) as qty")
                ->groupBy('brands.name')
                ->get();

            foreach ($onlineBrands as $row) {
                $bname = $row->b_name ?: 'No Brand';
                if (!isset($brands[$bname])) {
                    $brands[$bname] = ['name' => $bname, 'revenue' => 0.0, 'quantity' => 0.0];
                }
                $brands[$bname]['revenue'] += (float) $row->rev;
                $brands[$bname]['quantity'] += (float) $row->qty;
            }
        }

        $totalRev = array_sum(array_column($brands, 'revenue'));
        foreach ($brands as &$b) {
            $b['percent'] = $totalRev > 0 ? round(($b['revenue'] / $totalRev) * 100, 1) : 0.0;
        }
        unset($b);

        uasort($brands, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);

        return array_values($brands);
    }

    /**
     * Cashier / Staff performance breakdown.
     */
    protected function getCashierPerformance(Store $store, Carbon $from, Carbon $to): array
    {
        $cashiers = PosSale::join('users', 'pos_sales.cashier_id', '=', 'users.id')
            ->where('pos_sales.store_id', $store->id)
            ->whereIn('pos_sales.status', ['posted', 'partially_refunded', 'refunded', 'reversed'])
            ->whereBetween('pos_sales.posted_at', [$from, $to])
            ->selectRaw('
                users.id as cashier_id,
                users.name as cashier_name,
                users.email as cashier_email,
                COUNT(pos_sales.id) as orders_count,
                SUM(pos_sales.total) as total_sales,
                SUM(pos_sales.discount) as total_discounts,
                AVG(pos_sales.total) as avg_order_value
            ')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('total_sales')
            ->get()
            ->map(function ($row) {
                return [
                    'cashier_id'      => (int) $row->cashier_id,
                    'name'            => $row->cashier_name,
                    'email'           => $row->cashier_email,
                    'orders_count'    => (int) $row->orders_count,
                    'total_sales'     => (float) $row->total_sales,
                    'total_discounts' => (float) $row->total_discounts,
                    'aov'             => round((float) $row->avg_order_value, 2),
                ];
            })
            ->toArray();

        return $cashiers;
    }

    /**
     * Payment method breakdown.
     */
    protected function getPaymentMethodDistribution(Store $store, Carbon $from, Carbon $to, string $channel): array
    {
        $methods = [];

        // POS Payments
        if (in_array($channel, ['all', 'pos'], true)) {
            $posPayments = PosPayment::join('pos_sales', 'pos_payments.pos_sale_id', '=', 'pos_sales.id')
                ->where('pos_sales.store_id', $store->id)
                ->whereIn('pos_sales.status', ['posted', 'partially_refunded', 'refunded', 'reversed'])
                ->whereBetween('pos_sales.posted_at', [$from, $to])
                ->selectRaw("pos_payments.method, SUM(pos_payments.amount) as total_amt, COUNT(pos_payments.id) as cnt")
                ->groupBy('pos_payments.method')
                ->get();

            foreach ($posPayments as $p) {
                $m = strtolower($p->method ?: 'cash');
                if (!isset($methods[$m])) {
                    $methods[$m] = ['method' => $m, 'amount' => 0.0, 'count' => 0];
                }
                $methods[$m]['amount'] += (float) $p->total_amt;
                $methods[$m]['count'] += (int) $p->cnt;
            }
        }

        // Online Payments
        if (in_array($channel, ['all', 'online'], true)) {
            $onlinePayments = Order::where('store_id', $store->id)
                ->whereIn('status', ['confirmed', 'delivered', 'completed'])
                ->whereBetween('created_at', [$from, $to])
                ->get(['id', 'contact_channel', 'total_amount', 'agreed_amount']);

            foreach ($onlinePayments as $p) {
                $m = strtolower($p->contact_channel ?: 'online');
                $amt = (float) ($p->agreed_amount ?: $p->total_amount);
                if (!isset($methods[$m])) {
                    $methods[$m] = ['method' => $m, 'amount' => 0.0, 'count' => 0];
                }
                $methods[$m]['amount'] += $amt;
                $methods[$m]['count'] += 1;
            }
        }

        $totalAmt = array_sum(array_column($methods, 'amount'));
        foreach ($methods as &$item) {
            $item['percent'] = $totalAmt > 0 ? round(($item['amount'] / $totalAmt) * 100, 1) : 0.0;
        }
        unset($item);

        uasort($methods, fn ($a, $b) => $b['amount'] <=> $a['amount']);

        return array_values($methods);
    }

    /**
     * Compare POS Counter vs Online Web Channels.
     */
    protected function getChannelComparison(Store $store, Carbon $from, Carbon $to): array
    {
        $posRevenue = (float) PosSale::where('store_id', $store->id)
            ->whereIn('status', ['posted', 'partially_refunded', 'refunded', 'reversed'])
            ->whereBetween('posted_at', [$from, $to])
            ->sum('total');

        $posCount = (int) PosSale::where('store_id', $store->id)
            ->whereIn('status', ['posted', 'partially_refunded', 'refunded', 'reversed'])
            ->whereBetween('posted_at', [$from, $to])
            ->count();

        $onlineOrders = Order::where('store_id', $store->id)
            ->whereIn('status', ['confirmed', 'delivered', 'completed'])
            ->whereBetween('created_at', [$from, $to])
            ->get(['id', 'total_amount', 'agreed_amount']);

        $onlineRevenue = 0.0;
        foreach ($onlineOrders as $o) {
            $onlineRevenue += (float) ($o->agreed_amount ?: $o->total_amount);
        }
        $onlineCount = $onlineOrders->count();

        $total = $posRevenue + $onlineRevenue;

        return [
            'pos' => [
                'name'    => 'POS Counter Sales',
                'revenue' => $posRevenue,
                'orders'  => $posCount,
                'percent' => $total > 0 ? round(($posRevenue / $total) * 100, 1) : 0.0,
            ],
            'online' => [
                'name'    => 'Online Storefront',
                'revenue' => $onlineRevenue,
                'orders'  => $onlineCount,
                'percent' => $total > 0 ? round(($onlineRevenue / $total) * 100, 1) : 0.0,
            ],
        ];
    }
}
