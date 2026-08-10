@extends('layouts.admin.app')

@section('content')
<div class="space-y-5 sm:space-y-6">
    <div class="admin-page-header">
        <div>
            <h1 class="admin-page-title">Admin Dashboard</h1>
            <p class="admin-page-sub">{{ $store->name }} — overview of today's operations</p>
        </div>
    </div>

    {{-- Quick Actions: one clear primary action, calm secondaries --}}
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ url('/store/' . $store->slug . '/admin/products/create') }}" class="admin-primary-btn">{{ __('messages.add_product') }}</a>
        <a href="{{ route('store.admin.orders.index', ['store_slug' => $store->slug]) }}" class="admin-secondary-btn">{{ __('messages.manage_orders') }}</a>
        <a href="{{ route('store.admin.wholesale.applications.index', ['store_slug' => $store->slug]) }}" class="admin-secondary-btn">{{ __('messages.wholesale_apps') }}</a>
        <a href="{{ route('store.admin.glass-finder.index', ['store_slug' => $store->slug]) }}" class="admin-secondary-btn">{{ __('messages.glass_finder') }}</a>
        @if ($adminCanManageSettings ?? false)
            <a href="{{ route('store.admin.settings.edit', ['store_slug' => $store->slug]) }}" class="admin-secondary-btn">{{ __('messages.store_settings') }}</a>
        @endif
    </div>

    {{-- PRIMARY: Today's operations --}}
    <section aria-label="Today's operations">
        <div class="admin-section-head">
            <h2 class="admin-section-title">Today's Operations</h2>
            <span class="admin-section-sub">Primary daily metrics</span>
        </div>
        <div class="admin-hairline-grid grid-cols-2 lg:grid-cols-4">
            <div class="admin-hairline-cell">
                <div class="admin-stat-label">
                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-violet-500" aria-hidden="true"></span>
                    Today Orders
                </div>
                <div class="admin-stat-value text-violet-700 dark:text-violet-300" data-today-orders-stat>{{ number_format($todayOrders) }}</div>
                <div class="admin-stat-sub">Revenue: Ks {{ number_format($todayRevenue) }}</div>
            </div>
            <div class="admin-hairline-cell">
                <div class="admin-stat-label">
                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-violet-500" aria-hidden="true"></span>
                    Today Revenue
                </div>
                <div class="admin-stat-value text-violet-700 dark:text-violet-300">Ks {{ number_format($todayRevenue) }}</div>
                <div class="admin-stat-sub">{{ number_format($todayOrders) }} orders today</div>
            </div>
            <div class="admin-hairline-cell">
                <div class="admin-stat-label">
                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-amber-500" aria-hidden="true"></span>
                    Pending Orders
                </div>
                <div class="admin-stat-value" data-pending-orders-stat>{{ number_format($pendingOrders) }}</div>
                <div class="admin-stat-sub">Awaiting first contact</div>
            </div>
            <div class="admin-hairline-cell">
                <div class="admin-stat-label">
                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
                    This Month Revenue
                </div>
                <div class="admin-stat-value">Ks {{ number_format($monthRevenue) }}</div>
                <div class="admin-stat-sub">{{ number_format($monthOrders) }} orders this month</div>
            </div>
        </div>
    </section>

    {{-- SECONDARY: Order status --}}
    <section aria-label="Order status">
        <div class="admin-section-head">
            <h2 class="admin-section-title">Order Status</h2>
            <span class="admin-section-sub">Weekly volume and lifecycle</span>
        </div>
        <div class="admin-hairline-grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5">
            <div class="admin-hairline-cell">
                <div class="admin-stat-label">This Week Orders</div>
                <div class="admin-stat-value">{{ number_format($weekOrders) }}</div>
                <div class="admin-stat-sub">Revenue: Ks {{ number_format($weekRevenue) }}</div>
            </div>
            <div class="admin-hairline-cell">
                <div class="admin-stat-label">This Week Revenue</div>
                <div class="admin-stat-value">Ks {{ number_format($weekRevenue) }}</div>
                <div class="admin-stat-sub">Since Monday</div>
            </div>
            <div class="admin-hairline-cell">
                <div class="admin-stat-label">Confirmed Orders</div>
                <div class="admin-stat-value">{{ number_format($confirmedOrders) }}</div>
                <div class="admin-stat-sub">All time</div>
            </div>
            <div class="admin-hairline-cell">
                <div class="admin-stat-label">Delivered Orders</div>
                <div class="admin-stat-value">{{ number_format($deliveredOrders) }}</div>
                <div class="admin-stat-sub">All time</div>
            </div>
            <div class="admin-hairline-cell">
                <div class="admin-stat-label">Cancelled Orders</div>
                <div class="admin-stat-value" data-cancelled-orders-stat>{{ number_format($cancelledOrders) }}</div>
                <div class="admin-stat-sub">All time</div>
            </div>
        </div>
    </section>

    {{-- INVENTORY --}}
    <section aria-label="Inventory">
        <div class="admin-section-head">
            <h2 class="admin-section-title">Inventory</h2>
            <span class="admin-section-sub">Catalog and glass finder</span>
        </div>
        <div class="admin-hairline-grid grid-cols-2 md:grid-cols-4">
            <div class="admin-hairline-cell">
                <div class="admin-stat-label">Total Products</div>
                <div class="admin-stat-value">{{ number_format($totalProducts) }}</div>
                <div class="admin-stat-sub">Whole catalog</div>
            </div>
            <div class="admin-hairline-cell">
                <div class="admin-stat-label">In Stock Products</div>
                <div class="admin-stat-value">{{ number_format($inStockProducts) }}</div>
                <div class="admin-stat-sub">Available to sell</div>
            </div>
            <div class="admin-hairline-cell">
                <div class="admin-stat-label">Out of Stock</div>
                <div class="admin-stat-value">{{ number_format($outOfStockProducts) }}</div>
                <div class="admin-stat-sub">Needs restocking</div>
            </div>
            <div class="admin-hairline-cell">
                <div class="admin-stat-label">Glass Finder Items</div>
                <div class="admin-stat-value">{{ number_format($glassFinderItems) }}</div>
                <div class="admin-stat-sub">Lookup database</div>
            </div>
        </div>
    </section>

    {{-- BUSINESS --}}
    <section aria-label="Business">
        <div class="admin-section-head">
            <h2 class="admin-section-title">Business</h2>
            <span class="admin-section-sub">Wholesale pipeline and annual revenue</span>
        </div>
        <div class="admin-hairline-grid grid-cols-2">
            <div class="admin-hairline-cell">
                <div class="admin-stat-label">
                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-amber-500" aria-hidden="true"></span>
                    Pending Wholesale
                </div>
                <div class="admin-stat-value" data-pending-wholesale-stat>{{ number_format($pendingWholesale) }}</div>
                <div class="admin-stat-sub">Applications to review</div>
            </div>
            <div class="admin-hairline-cell">
                <div class="admin-stat-label">Year Revenue</div>
                <div class="admin-stat-value">Ks {{ number_format($yearRevenue) }}</div>
                <div class="admin-stat-sub">This calendar year</div>
            </div>
        </div>
    </section>

    {{-- Monthly Revenue Chart + Top Products --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-5">
        <div class="lg:col-span-2 admin-panel p-4 sm:p-5">
            <div class="admin-section-head mb-4">
                <h3 class="admin-section-title">📈 Monthly Revenue Report <span class="admin-section-sub font-medium">(Ks · last 12 months)</span></h3>
            </div>
            @php
                $chartTotal = array_sum(array_column($monthlySeries, 'revenue'));
                $chartMax = max(array_column($monthlySeries, 'revenue')) ?: 1;
            @endphp

            @if ($chartTotal == 0)
                {{-- Compact, useful empty state instead of a near-empty chart --}}
                <div class="admin-empty">
                    <span class="admin-empty-icon" aria-hidden="true">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 19V5m0 14h16M8 15l3-4 3 2 4-6"/></svg>
                    </span>
                    <p class="admin-empty-title">No sales data yet</p>
                    <p class="admin-empty-sub">Sales data will appear here after completed orders.</p>
                </div>
            @else
                <div class="h-40 sm:h-44">
                    <div class="flex gap-px sm:gap-1.5 h-full">
                        @foreach ($monthlySeries as $i => $m)
                            <div class="flex-1 flex flex-col min-w-0 h-full" title="{{ $m['label'] }}: Ks {{ number_format($m['revenue']) }}">
                                {{-- Bar zone (flex-1) keeps the label row below so a 100% bar never overlaps it --}}
                                <div class="flex-1 flex items-end min-h-0">
                                    <div class="w-full rounded-t-md bg-gradient-to-t from-violet-600 to-fuchsia-500 transition-all duration-300 hover:from-violet-500 hover:to-fuchsia-400"
                                        style="height: {{ max(2, round(($m['revenue'] / $chartMax) * 100)) }}%"></div>
                                </div>
                                {{-- Full label on sm+; every-2nd abbreviated month on phones so it stays readable --}}
                                <span class="hidden sm:block text-xs font-semibold text-slate-400 dark:text-slate-500 leading-tight w-full text-center truncate px-0.5 mt-1">{{ $m['label'] }}</span>
                                <span class="sm:hidden text-xs font-semibold text-slate-400 dark:text-slate-500 leading-tight w-full text-center truncate px-0.5 mt-1 {{ $loop->even ? '' : 'invisible' }}">{{ \Illuminate\Support\Str::of($m['label'])->before(' ') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-3 tabular-nums">Total (12 mo): Ks {{ number_format($chartTotal) }}</p>
            @endif
        </div>

        <div class="admin-panel p-4 sm:p-5">
            <div class="admin-section-head mb-3">
                <h3 class="admin-section-title">🏆 Top Products <span class="admin-section-sub font-medium">(by qty)</span></h3>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                @forelse ($topProducts as $i => $tp)
                    <div class="flex items-center justify-between gap-2 py-2.5">
                        <div class="min-w-0 flex items-center gap-2">
                            <span class="shrink-0 w-5 h-5 rounded-full text-xs font-black text-white flex items-center justify-center {{ $i === 0 ? 'bg-amber-500' : ($i === 1 ? 'bg-slate-400' : ($i === 2 ? 'bg-orange-600' : 'bg-gray-300 dark:bg-slate-600')) }}">{{ $i + 1 }}</span>
                            <span class="font-bold text-gray-900 dark:text-slate-100 truncate" title="{{ $tp->name }}">{{ $tp->name }}</span>
                        </div>
                        <div class="shrink-0 text-right">
                            <span class="font-black text-violet-600 dark:text-violet-400 tabular-nums">{{ number_format($tp->qty) }} pcs</span>
                            <span class="block text-gray-400 dark:text-slate-500 tabular-nums">Ks {{ number_format($tp->sales) }}</span>
                        </div>
                    </div>
                @empty
                    <div class="admin-empty mt-2">
                        <p class="admin-empty-title">No sales yet</p>
                        <p class="admin-empty-sub">Best sellers appear once orders are placed.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Recent Activity — natural content heights (items-start) --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-5 items-start">
        {{-- Recent Orders --}}
        <div class="admin-panel p-4 sm:p-5">
            <div class="admin-section-head mb-2">
                <h3 class="admin-section-title">Recent Order Requests</h3>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                @forelse ($recentOrders as $order)
                    <div class="flex items-center justify-between gap-2 py-2.5">
                        <div class="min-w-0">
                            <div class="flex items-baseline gap-1.5 min-w-0">
                                <span class="font-bold text-violet-600 dark:text-violet-400 truncate">{{ $order->order_number }}</span>
                                <span class="text-slate-300 dark:text-slate-600 shrink-0" aria-hidden="true">•</span>
                                <span class="text-gray-700 dark:text-slate-200 truncate">{{ $order->customer_name }}</span>
                            </div>
                            @if ($order->status === 'pending_contact' && $order->created_at->lt(now()->subHours(2)))
                                <div class="mt-1">
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 text-xs font-bold">⏳ 2h+ uncontacted</span>
                                </div>
                            @endif
                        </div>
                        <span class="shrink-0 font-bold text-gray-800 dark:text-slate-200 tabular-nums" title="Ks {{ number_format($order->total_amount) }}">Ks {{ number_format($order->total_amount) }}</span>
                    </div>
                @empty
                    <div class="admin-empty mt-2">
                        <p class="admin-empty-title">No recent orders</p>
                        <p class="admin-empty-sub">New order requests will show up here.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Recent Wholesale Applications --}}
        <div class="admin-panel p-4 sm:p-5">
            <div class="admin-section-head mb-2">
                <h3 class="admin-section-title">Recent Wholesale Applications</h3>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                @forelse ($recentWholesale as $app)
                    <div class="flex items-center justify-between gap-2 py-2.5">
                        <div class="min-w-0">
                            <div class="truncate font-bold text-gray-900 dark:text-slate-100" title="{{ $app->business_name }}">{{ $app->business_name }}</div>
                            <div class="truncate text-gray-500 dark:text-slate-400 mt-0.5">{{ $app->phone }}</div>
                        </div>
                        <span class="shrink-0 uppercase font-bold text-amber-600 dark:text-amber-400">{{ $app->status }}</span>
                    </div>
                @empty
                    <div class="admin-empty mt-2">
                        <p class="admin-empty-title">No recent applications</p>
                        <p class="admin-empty-sub">Wholesale sign-ups will show up here.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Recently Added Products --}}
        <div class="admin-panel p-4 sm:p-5">
            <div class="admin-section-head mb-2">
                <h3 class="admin-section-title">Recently Added Products</h3>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                @forelse ($recentProducts as $prod)
                    <div class="flex items-center justify-between gap-2 py-2.5">
                        <div class="min-w-0">
                            <div class="truncate font-bold text-gray-900 dark:text-slate-100" title="{{ $prod->name }}">{{ $prod->name }}</div>
                            <div class="truncate text-gray-500 dark:text-slate-400 mt-0.5">SKU: {{ $prod->sku }}</div>
                        </div>
                        <span class="shrink-0 font-semibold text-violet-600 dark:text-violet-400 tabular-nums" title="Ks {{ number_format($prod->retail_price) }}">Ks {{ number_format($prod->retail_price) }}</span>
                    </div>
                @empty
                    <div class="admin-empty mt-2">
                        <p class="admin-empty-title">No recent products</p>
                        <p class="admin-empty-sub">Products you add will show up here.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
