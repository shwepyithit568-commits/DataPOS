@extends('layouts.admin.app')

@section('content')
<div class="w-full space-y-5 sm:space-y-6">
    {{-- Header --}}
    <div class="admin-page-header">
        <div class="flex items-center gap-3">
            <a href="{{ route('store.admin.customers.index', $storeRouteParams) }}"
               class="text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300">← Back</a>
            <div>
                <h1 class="admin-page-title">👤 {{ $customer->name }}</h1>
                <p class="admin-page-sub">
                    {{ $store->name }}
                    · {{ $membership?->role === 'wholesale_customer' ? 'Wholesale' : 'Retail' }} Customer
                </p>
            </div>
        </div>
    </div>

    {{-- Profile + Debt Card --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        {{-- Profile --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl p-5 space-y-3 sm:col-span-2">
            <h2 class="font-semibold text-gray-900 dark:text-white text-sm">Profile</h2>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <div class="text-xs text-gray-500 dark:text-slate-400">Name</div>
                    <div class="font-medium text-gray-900 dark:text-slate-100">{{ $customer->name }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 dark:text-slate-400">Phone</div>
                    <div class="font-medium text-gray-900 dark:text-slate-100">{{ $customer->phone ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 dark:text-slate-400">Email</div>
                    <div class="font-medium text-gray-900 dark:text-slate-100">{{ $customer->email ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 dark:text-slate-400">Member Since</div>
                    <div class="font-medium text-gray-900 dark:text-slate-100">{{ $customer->created_at->format('M d, Y') }}</div>
                </div>
            </div>
        </div>

        {{-- Debt Balance --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl p-5 space-y-3">
            <h2 class="font-semibold text-gray-900 dark:text-white text-sm">Debt Balance</h2>
            <div class="text-2xl font-bold {{ (float) $debtBalance > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-400 dark:text-slate-500' }}">
                {{ number_format((float) $debtBalance, 0) }} <span class="text-sm font-normal">MMK</span>
            </div>
            @if ((float) $debtBalance > 0)
                <div class="text-xs text-amber-600 dark:text-amber-400">Outstanding balance</div>
            @else
                <div class="text-xs text-gray-400 dark:text-slate-500">No outstanding balance</div>
            @endif
        </div>
    </div>

    {{-- Order Summary Stats --}}
    <div class="admin-hairline-grid grid-cols-2 sm:grid-cols-3">
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-blue-600 dark:text-blue-400">Total Orders</div>
            <div class="admin-stat-value">{{ number_format($orderStats['total_orders']) }}</div>
        </div>
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-emerald-600 dark:text-emerald-400">Total Spent</div>
            <div class="admin-stat-value">{{ number_format((float) $orderStats['total_spent'], 0) }} MMK</div>
        </div>
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-violet-600 dark:text-violet-400">Customer Type</div>
            <div class="admin-stat-value text-sm">
                {{ $membership?->role === 'wholesale_customer' ? 'Wholesale' : 'Retail' }}
            </div>
        </div>
    </div>

    {{-- Recent Orders --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl overflow-hidden transition-colors duration-200">
        <div class="p-4 border-b dark:border-slate-700">
            <h2 class="font-semibold text-gray-900 dark:text-white text-sm">Recent POS Orders</h2>
        </div>

        @if ($recentOrders->isEmpty())
            <div class="p-8 text-center">
                <div class="text-4xl mb-3 opacity-40">🧾</div>
                <div class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-1">No orders yet</div>
                <div class="text-xs text-gray-500 dark:text-slate-400">POS orders will appear here.</div>
            </div>
        @else
            {{-- Mobile card view --}}
            <div class="sm:hidden divide-y dark:divide-slate-700">
                @foreach ($recentOrders as $order)
                    <div class="p-3.5 space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-sm text-gray-900 dark:text-slate-100">{{ $order->receipt_number }}</span>
                            <span class="px-2 py-0.5 text-xs font-bold rounded-full
                                {{ $order->status === 'posted' ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300' : 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300' }}">
                                {{ $order->status }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-slate-400">
                            <span>{{ $order->posted_at?->format('M d, Y H:i') ?? $order->created_at->format('M d, Y') }}</span>
                            <span class="font-semibold text-gray-900 dark:text-slate-100">{{ number_format((float) $order->total, 0) }} MMK</span>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Desktop table --}}
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600 dark:text-slate-300">
                    <thead class="bg-gray-50 dark:bg-slate-900/50 border-b dark:border-slate-700 text-xs font-semibold text-gray-700 dark:text-slate-200">
                        <tr>
                            <th class="p-3">Receipt #</th>
                            <th class="p-3">Date</th>
                            <th class="p-3">Items</th>
                            <th class="p-3">Status</th>
                            <th class="p-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-slate-700">
                        @foreach ($recentOrders as $order)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-700/50 transition">
                                <td class="p-3 font-bold text-gray-900 dark:text-slate-100">{{ $order->receipt_number }}</td>
                                <td class="p-3 text-xs text-gray-500 dark:text-slate-400 whitespace-nowrap">{{ $order->posted_at?->format('M d, Y H:i') ?? $order->created_at->format('M d, Y') }}</td>
                                <td class="p-3 text-xs text-gray-500 dark:text-slate-400">{{ $order->items->count() }} items</td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 text-xs font-bold rounded-full
                                        {{ $order->status === 'posted' ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300' : 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300' }}">
                                        {{ $order->status }}
                                    </span>
                                </td>
                                <td class="p-3 text-right font-semibold text-gray-900 dark:text-slate-100">{{ number_format((float) $order->total, 0) }} MMK</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
