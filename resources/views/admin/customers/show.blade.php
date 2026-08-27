@extends('layouts.admin.app')

@php
    $storeRouteParams = ['store_slug' => $store->slug];
    $isWholesale = ($membership?->role ?? 'retail_customer') === 'wholesale_customer';
    $initial = mb_substr($customer->name ?: 'C', 0, 1);
    $debt = (float) $debtBalance;
@endphp

@section('title', 'Customer: ' . $customer->name . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@section('content')
<div class="w-full space-y-2 sm:space-y-2.5">

    {{-- ============================================================
         PAGE HEADER — Eyebrow, Name, Subtitle & Action CTAs
         ============================================================ --}}
    <header class="w-full flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3.5 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
        <div class="min-w-0 flex items-center gap-3">
            <a href="{{ route('store.admin.customers.index', $storeRouteParams) }}"
               class="w-9 h-9 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 grid place-items-center text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition shadow-2xs shrink-0"
               title="{{ __('messages.back') }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div class="min-w-0">
                <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 text-[10px] sm:text-[11px] font-black uppercase tracking-wider border border-emerald-100 dark:border-emerald-900/60 mb-0.5">
                    <span>👤</span>
                    <span>Customer Profile</span>
                    <span class="text-slate-400 dark:text-slate-500">·</span>
                    <span class="font-normal normal-case text-slate-500 dark:text-slate-400">ID #{{ $customer->id }}</span>
                </div>
                <h1 class="text-base sm:text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2 truncate">
                    <span>{{ $customer->name }}</span>
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase
                        {{ $isWholesale ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' }}">
                        {{ $isWholesale ? 'Wholesale Customer' : 'Retail Customer' }}
                    </span>
                </h1>
                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                    📞 {{ $customer->phone ?: 'No phone' }} · Member since {{ $customer->created_at ? $customer->created_at->format('M d, Y') : '-' }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0 self-start sm:self-auto">
            @if ($debt > 0)
                <a href="{{ route('store.admin.receivables.show', array_merge($storeRouteParams, ['customer' => $customer->id])) }}"
                   class="px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-lg text-xs font-black bg-amber-600 hover:bg-amber-700 text-white shadow-2xs transition flex items-center gap-1.5 active:scale-95">
                    <span>💰</span>
                    <span>အကြွေးရှင်း/ငွေကောက်</span>
                </a>
            @endif

            <a href="{{ route('store.admin.receivables.statement', array_merge($storeRouteParams, ['customer' => $customer->id])) }}"
               target="_blank"
               class="px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 transition flex items-center gap-1.5 active:scale-95 shadow-2xs">
                <span>📄</span>
                <span>Statement Print</span>
            </a>
        </div>
    </header>

    {{-- ============================================================
         3 KEY KPI CARDS
         ============================================================ --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-1.5 sm:gap-2">
        {{-- Total Orders --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 truncate">ဝယ်ယူမှု အကြိမ်ရေ</span>
                <span class="text-xs">🧾</span>
            </div>
            <div class="text-lg sm:text-2xl font-black text-slate-900 dark:text-slate-100 mt-1 font-mono tracking-tight">
                {{ number_format($orderStats['total_orders']) }}
            </div>
            <div class="text-[10px] text-slate-400 mt-0.5">Completed POS Sales</div>
        </div>

        {{-- Total Spent --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 truncate">ဝယ်ယူမှု စုစုပေါင်း</span>
                <span class="text-xs">💎</span>
            </div>
            <div class="text-lg sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1 font-mono tracking-tight">
                {{ number_format((float) $orderStats['total_spent'], 0) }} <span class="text-xs font-normal">MMK</span>
            </div>
            <div class="text-[10px] text-emerald-600/80 dark:text-emerald-400/80 mt-0.5">Lifetime Customer Value</div>
        </div>

        {{-- Debt Balance --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 truncate">အကြွေးလက်ကျန်</span>
                <span class="text-xs">💰</span>
            </div>
            <div class="text-lg sm:text-2xl font-black {{ $debt > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white' }} mt-1 font-mono tracking-tight">
                {{ number_format($debt, 0) }} <span class="text-xs font-normal">MMK</span>
            </div>
            <div class="text-[10px] font-semibold mt-0.5 {{ $debt > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400' }}">
                {{ $debt > 0 ? 'Outstanding balance to collect' : 'All accounts settled' }}
            </div>
        </div>
    </div>

    {{-- ============================================================
         PROFILE OVERVIEW & RECENT ORDERS GRID
         ============================================================ --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-2 sm:gap-2.5 items-start">
        
        {{-- Left: Profile Details Box --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-3 sm:p-4 shadow-2xs space-y-3">
            <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                <span class="text-xs">👤</span>
                <h2 class="text-xs font-black uppercase tracking-wider text-slate-800 dark:text-slate-200">Customer Information</h2>
            </div>

            <div class="space-y-2 text-xs">
                <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-400 font-bold">Customer Name</span>
                    <span class="font-bold text-slate-900 dark:text-slate-100">{{ $customer->name }}</span>
                </div>

                <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-400 font-bold">Phone Number</span>
                    <span class="font-mono font-bold text-slate-900 dark:text-slate-100">{{ $customer->phone ?: '—' }}</span>
                </div>

                <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-400 font-bold">Email Address</span>
                    <span class="font-medium text-slate-600 dark:text-slate-300">{{ $customer->email ?: '—' }}</span>
                </div>

                <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-400 font-bold">Customer Tier</span>
                    <span class="font-bold text-slate-900 dark:text-slate-100">{{ $isWholesale ? 'Wholesale Partner' : 'Retail Shopper' }}</span>
                </div>

                <div class="flex items-center justify-between py-1.5">
                    <span class="text-slate-400 font-bold">Store Status</span>
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                        {{ $membership?->status ?? 'active' }}
                    </span>
                </div>
            </div>

            <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                <a href="{{ route('store.admin.receivables.show', array_merge($storeRouteParams, ['customer' => $customer->id])) }}"
                   class="w-full py-2 rounded-lg text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition flex items-center justify-center gap-1.5 shadow-2xs">
                    <span>📖</span>
                    <span>အကြွေးလယ်ဂျာ ကြည့်ရှုမည် (View Ledger)</span>
                </a>
            </div>
        </div>

        {{-- Right 2 Columns: Recent Orders Section --}}
        <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs overflow-hidden transition">
            <div class="px-3 py-2.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-xs">🧾</span>
                    <h2 class="text-xs font-black uppercase tracking-wider text-slate-800 dark:text-slate-200">Recent POS Orders</h2>
                </div>
                <span class="text-xs text-slate-400 font-mono">{{ $recentOrders->count() }} sales</span>
            </div>

            @if ($recentOrders->isEmpty())
                <div class="p-8 text-center text-slate-400 dark:text-slate-500 text-xs font-bold">
                    ဝယ်ယူမှု မှတ်တမ်း မရှိသေးပါ။ (No POS orders yet.)
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300 min-w-[550px]">
                        <thead class="bg-slate-50 dark:bg-slate-800/80 text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800">
                            <tr>
                                <th class="p-2.5">Receipt #</th>
                                <th class="p-2.5">Date</th>
                                <th class="p-2.5">Items</th>
                                <th class="p-2.5 text-center">Status</th>
                                <th class="p-2.5 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($recentOrders as $order)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition">
                                    <td class="p-2.5 font-mono font-bold text-slate-900 dark:text-slate-100">
                                        {{ $order->receipt_number }}
                                    </td>
                                    <td class="p-2.5 text-slate-500 dark:text-slate-400 whitespace-nowrap font-mono text-[11px]">
                                        {{ $order->posted_at?->format('M d, Y h:i A') ?? $order->created_at->format('M d, Y') }}
                                    </td>
                                    <td class="p-2.5 text-slate-600 dark:text-slate-300">
                                        {{ $order->items->count() }} items
                                    </td>
                                    <td class="p-2.5 text-center">
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase
                                            {{ $order->status === 'posted' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' }}">
                                            {{ $order->status }}
                                        </span>
                                    </td>
                                    <td class="p-2.5 text-right font-mono font-bold text-slate-900 dark:text-slate-100">
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
