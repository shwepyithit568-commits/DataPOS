@extends('layouts.admin.app')

@section('title', 'Customer: ' . $customer->name . ' - ' . $store->name)

@php
    $storeRouteParams = ['store_slug' => $store->slug];
    $isWholesale = ($membership?->role ?? 'retail_customer') === 'wholesale_customer';
    $initial = mb_substr($customer->name ?: 'C', 0, 1);
    $debt = (float) $debtBalance;
@endphp

@section('content')
<div class="w-full space-y-5 sm:space-y-6 pb-12">

    {{-- Top Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('store.admin.customers.index', $storeRouteParams) }}"
               class="w-10 h-10 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 grid place-items-center text-slate-600 dark:text-slate-300 hover:bg-slate-50 transition shadow-sm">
                ←
            </a>
            <div class="min-w-0">
                <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-400">
                    <a href="{{ route('store.admin.customers.index', $storeRouteParams) }}" class="hover:text-slate-600 dark:hover:text-slate-300 transition">
                        Customers
                    </a>
                    <span>/</span>
                    <span class="text-emerald-600 dark:text-emerald-400">Customer Profile</span>
                </div>
                <h1 class="text-lg sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2 truncate">
                    <span>{{ $customer->name }}</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase
                        {{ $isWholesale ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' }}">
                        {{ $isWholesale ? 'Wholesale Customer' : 'Retail Customer' }}
                    </span>
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">📞 {{ $customer->phone ?: 'No phone' }} · Member since {{ $customer->created_at ? $customer->created_at->format('M d, Y') : '-' }}</p>
            </div>
        </div>

        <div class="flex items-center gap-2.5 self-start sm:self-auto">
            @if ($debt > 0)
                <a href="{{ route('store.admin.receivables.show', array_merge($storeRouteParams, ['customer' => $customer->id])) }}"
                   class="px-4 py-2.5 rounded-2xl text-xs font-black bg-amber-600 hover:bg-amber-500 text-white shadow-lg shadow-amber-500/20 transition flex items-center gap-1.5">
                    <span>💰</span>
                    <span>အကြွေးရှင်း/ငွေကောက် (Settle Debt)</span>
                </a>
            @endif

            <a href="{{ route('store.admin.receivables.statement', array_merge($storeRouteParams, ['customer' => $customer->id])) }}"
               target="_blank"
               class="px-3.5 py-2.5 rounded-2xl text-xs font-bold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition flex items-center gap-1.5 shadow-sm">
                <span>📄</span>
                <span>Statement Print</span>
            </a>
        </div>
    </div>

    {{-- 3 Key KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 sm:gap-4">
        {{-- Total Orders --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-5 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">ဝယ်ယူမှု အကြိမ်ရေ (Orders)</p>
                <h3 class="text-2xl font-black text-slate-900 dark:text-white font-mono">{{ number_format($orderStats['total_orders']) }}</h3>
                <p class="text-[11px] text-slate-400 font-semibold mt-0.5">Completed POS Sales</p>
            </div>
            <span class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 grid place-items-center text-xl font-bold shadow-inner">
                🧾
            </span>
        </div>

        {{-- Total Spent --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-5 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">ဝယ်ယူမှု စုစုပေါင်း (Total Spent)</p>
                <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono">{{ number_format((float) $orderStats['total_spent'], 0) }} <span class="text-xs font-normal">MMK</span></h3>
                <p class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold mt-0.5">Lifetime Customer Value</p>
            </div>
            <span class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 grid place-items-center text-xl font-bold shadow-inner">
                💎
            </span>
        </div>

        {{-- Debt Balance --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-5 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">အကြွေးလက်ကျန် (Debt Balance)</p>
                <h3 class="text-2xl font-black {{ $debt > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white' }} font-mono">
                    {{ number_format($debt, 0) }} <span class="text-xs font-normal">MMK</span>
                </h3>
                <p class="text-[11px] font-semibold mt-0.5 {{ $debt > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400' }}">
                    {{ $debt > 0 ? 'Outstanding balance to collect' : 'All accounts settled' }}
                </p>
            </div>
            <span class="w-12 h-12 rounded-2xl {{ $debt > 0 ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400' : 'bg-slate-100 dark:bg-slate-800 text-slate-400' }} grid place-items-center text-xl font-bold shadow-inner">
                💰
            </span>
        </div>
    </div>

    {{-- Customer Profile Overview & Recent Orders --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Profile Details Box --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-5 sm:p-6 shadow-sm space-y-4">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2">
                <span>👤</span>
                <span>အချက်အလက် အသေးစိတ် (Customer Info)</span>
            </h3>

            <div class="space-y-3 text-xs">
                <div class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-400 font-bold">Customer Name</span>
                    <span class="font-black text-slate-900 dark:text-slate-100">{{ $customer->name }}</span>
                </div>

                <div class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-400 font-bold">Phone Number</span>
                    <span class="font-mono font-bold text-slate-900 dark:text-slate-100">{{ $customer->phone ?: '—' }}</span>
                </div>

                <div class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-400 font-bold">Email Address</span>
                    <span class="font-medium text-slate-600 dark:text-slate-300">{{ $customer->email ?: '—' }}</span>
                </div>

                <div class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-400 font-bold">Customer Tier</span>
                    <span class="font-bold text-slate-900 dark:text-slate-100">{{ $isWholesale ? 'Wholesale Partner' : 'Retail Shopper' }}</span>
                </div>

                <div class="flex items-center justify-between py-2">
                    <span class="text-slate-400 font-bold">Status</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                        {{ $membership?->status ?? 'active' }}
                    </span>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
                <a href="{{ route('store.admin.receivables.show', array_merge($storeRouteParams, ['customer' => $customer->id])) }}"
                   class="w-full py-2.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition flex items-center justify-center gap-2">
                    <span>📖</span>
                    <span>အကြွေးလယ်ဂျာ ကြည့်ရှုမည် (View Ledger)</span>
                </a>
            </div>
        </div>

        {{-- Recent Orders Section --}}
        <div class="lg:col-span-2 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-5 sm:p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2">
                    <span>🧾</span>
                    <span>လတ်တလော ဘောက်ချာများ (Recent POS Orders)</span>
                </h3>
                <span class="text-xs text-slate-400 font-mono">{{ $recentOrders->count() }} sales</span>
            </div>

            @if ($recentOrders->isEmpty())
                <div class="py-12 text-center text-slate-400 dark:text-slate-500 text-sm">
                    ဝယ်ယူမှု မှတ်တမ်း မရှိသေးပါ။ (No POS orders yet.)
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                                <th class="py-3 px-3">Receipt #</th>
                                <th class="py-3 px-3">Date</th>
                                <th class="py-3 px-3">Items</th>
                                <th class="py-3 px-3 text-center">Status</th>
                                <th class="py-3 px-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($recentOrders as $order)
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                                    <td class="py-3 px-3 font-mono font-bold text-slate-900 dark:text-slate-100">
                                        {{ $order->receipt_number }}
                                    </td>
                                    <td class="py-3 px-3 text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                        {{ $order->posted_at?->format('M d, Y h:i A') ?? $order->created_at->format('M d, Y') }}
                                    </td>
                                    <td class="py-3 px-3 text-slate-600 dark:text-slate-300">
                                        {{ $order->items->count() }} items
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase
                                            {{ $order->status === 'posted' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' }}">
                                            {{ $order->status }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-right font-mono font-black text-slate-900 dark:text-slate-100">
                                        {{ number_format((float) $order->total, 0) }} MMK
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
