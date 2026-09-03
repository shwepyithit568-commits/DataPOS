@extends('layouts.admin.app')

@php
    $storeRouteParams = ['store_slug' => $store->slug];
    $isWholesale = ($membership?->role ?? 'retail_customer') === 'wholesale_customer';
    $initial = mb_substr($customer->name ?: 'C', 0, 1);
    $debt = (float) $debtBalance;
@endphp

@section('title', 'Customer: ' . $customer->name . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
<div class="w-full space-y-0.5 pb-6">

    {{-- ============================================================
         PAGE HEADER — Eyebrow, Name, Subtitle & Action CTAs
         ============================================================ --}}
    <header class="w-full flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 bg-white dark:bg-slate-900 rounded-lg px-2.5 py-1.5 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
        <div class="min-w-0 flex items-center gap-2.5">
            <a href="{{ route('store.admin.customers.index', $storeRouteParams) }}"
               class="w-7 h-7 sm:w-8 sm:h-8 rounded-md bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 grid place-items-center text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition shadow-2xs shrink-0 cursor-pointer"
               title="{{ __('messages.back') }}">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div class="min-w-0">
                <div class="flex items-center gap-1.5">
                    <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white tracking-tight truncate">
                        {{ $customer->name }}
                    </h1>
                    <span class="px-1.5 py-0.2 rounded text-[10px] font-bold uppercase
                        {{ $isWholesale ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' }}">
                        {{ $isWholesale ? __('messages.customer_type_wholesale') : __('messages.customer_type_retail') }}
                    </span>
                    <span class="text-[10px] font-mono text-slate-400 dark:text-slate-500">#{{ $customer->id }}</span>
                </div>
                <p class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 truncate">
                    📞 {{ $customer->phone ?: __('messages.customer_no_phone') }} · {{ __('messages.customer_joined') }} {{ $customer->created_at ? $customer->created_at->format('d/m/Y') : '-' }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-1.5 shrink-0 self-start sm:self-auto">
            @if ($debt > 0)
                <a href="{{ route('store.admin.receivables.show', array_merge($storeRouteParams, ['customer' => $customer->id])) }}"
                   class="h-7 px-2.5 rounded-md text-xs font-black bg-amber-600 hover:bg-amber-500 text-white shadow-2xs transition flex items-center gap-1 active:scale-95 cursor-pointer">
                    <span>💰</span>
                    <span>{{ __('messages.customer_collect_debt') }}</span>
                </a>
            @endif

            <a href="{{ route('store.admin.receivables.statement', array_merge($storeRouteParams, ['customer' => $customer->id])) }}"
               target="_blank"
               class="h-7 px-2.5 rounded-md text-xs font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 transition flex items-center gap-1 active:scale-95 shadow-2xs cursor-pointer">
                <span>📄</span>
                <span>Statement</span>
            </a>
        </div>
    </header>

    {{-- ============================================================
         3 KEY KPI CARDS — Centered Row Layout
         ============================================================ --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-0.5 sm:gap-1">
        {{-- Total Orders --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg p-2 sm:p-2.5 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
            <div class="flex items-center justify-center gap-2.5 sm:gap-3">
                <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 grid place-items-center text-sm shrink-0">
                    🧾
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 truncate">{{ __('messages.customer_orders_count') }}</p>
                    <div class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 font-mono tracking-tight">
                        {{ number_format($orderStats['total_orders']) }} {{ __('messages.customer_times') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Total Spent --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg p-2 sm:p-2.5 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
            <div class="flex items-center justify-center gap-2.5 sm:gap-3">
                <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 grid place-items-center text-sm shrink-0">
                    💎
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 truncate">{{ __('messages.customer_total_spent') }}</p>
                    <div class="text-sm sm:text-base font-black text-emerald-600 dark:text-emerald-400 font-mono tracking-tight">
                        {{ format_currency((float) $orderStats['total_spent'], $store) }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Debt Balance --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg p-2 sm:p-2.5 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
            <div class="flex items-center justify-center gap-2.5 sm:gap-3">
                <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300 grid place-items-center text-sm shrink-0">
                    💰
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 truncate">{{ __('messages.customer_debt') }}</p>
                    <div class="text-sm sm:text-base font-black {{ $debt > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white' }} font-mono tracking-tight">
                        {{ format_currency($debt, $store) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         PROFILE OVERVIEW & RECENT ORDERS GRID
         ============================================================ --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-0.5 sm:gap-1 items-start">
        
        {{-- Left: Profile Details Box --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 shadow-2xs space-y-2">
            <div class="flex items-center gap-1.5 pb-1.5 border-b border-slate-100 dark:border-slate-800">
                <span class="text-xs">👤</span>
                <h2 class="text-xs font-black uppercase tracking-wider text-slate-800 dark:text-slate-200">{{ __('messages.customer_info') }}</h2>
            </div>

            <div class="space-y-1.5 text-xs">
                <div class="flex items-center justify-between py-1 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-400 font-bold">{{ __('messages.customer_name') }}</span>
                    <span class="font-bold text-slate-900 dark:text-slate-100">{{ $customer->name }}</span>
                </div>

                <div class="flex items-center justify-between py-1 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-400 font-bold">{{ __('messages.customer_phone') }}</span>
                    <span class="font-mono font-bold text-slate-900 dark:text-slate-100">{{ $customer->phone ?: '-' }}</span>
                </div>

                <div class="flex items-center justify-between py-1 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-400 font-bold">{{ __('messages.customer_email_label') }}</span>
                    <span class="font-medium text-slate-600 dark:text-slate-300 truncate max-w-[170px]">{{ $customer->email ?: '-' }}</span>
                </div>

                <div class="flex items-center justify-between py-1 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-400 font-bold">{{ __('messages.customer_type') }}</span>
                    <span class="font-bold text-slate-900 dark:text-slate-100">{{ $isWholesale ? __('messages.customer_type_wholesale') : __('messages.customer_type_retail') }}</span>
                </div>

                <div class="flex items-center justify-between py-1">
                    <span class="text-slate-400 font-bold">{{ __('messages.customer_status_label') }}</span>
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                        {{ $membership?->status ?? 'active' }}
                    </span>
                </div>
            </div>

            <div class="pt-1.5 border-t border-slate-100 dark:border-slate-800">
                <a href="{{ route('store.admin.receivables.show', array_merge($storeRouteParams, ['customer' => $customer->id])) }}"
                   class="w-full h-7 rounded-md text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition flex items-center justify-center gap-1 shadow-2xs cursor-pointer">
                    <span>📖</span>
                    <span>{{ __('messages.customer_ledger_btn') }}</span>
                </a>
            </div>
        </div>

        {{-- Right 2 Columns: Recent Orders Section --}}
        <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs overflow-hidden transition">
            <div class="px-2.5 py-1.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-1.5">
                    <span class="text-xs">🧾</span>
                    <h2 class="text-xs font-black uppercase tracking-wider text-slate-800 dark:text-slate-200">{{ __('messages.customer_sales_history') }}</h2>
                </div>
                <span class="text-[11px] text-slate-400 font-mono">{{ $recentOrders->count() }} orders</span>
            </div>

            @if ($recentOrders->isEmpty())
                <div class="p-6 text-center text-slate-400 dark:text-slate-500 text-xs font-bold">
                    {{ __('messages.customer_empty') }}
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300 min-w-[500px]">
                        <thead class="bg-slate-50 dark:bg-slate-800/80 text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800">
                            <tr>
                                <th class="py-1.5 px-2.5">Receipt #</th>
                                <th class="py-1.5 px-2.5">Date</th>
                                <th class="py-1.5 px-2.5">Items</th>
                                <th class="py-1.5 px-2.5 text-center">Status</th>
                                <th class="py-1.5 px-2.5 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($recentOrders as $order)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition">
                                    <td class="py-1.5 px-2.5 font-mono font-bold text-slate-900 dark:text-slate-100">
                                        {{ $order->receipt_number }}
                                    </td>
                                    <td class="py-1.5 px-2.5 text-slate-500 dark:text-slate-400 whitespace-nowrap font-mono text-[11px]">
                                        {{ $order->posted_at?->format('d/m/Y h:i A') ?? $order->created_at->format('d/m/Y') }}
                                    </td>
                                    <td class="py-1.5 px-2.5 text-slate-600 dark:text-slate-300">
                                        {{ $order->items->count() }} items
                                    </td>
                                    <td class="py-1.5 px-2.5 text-center">
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase
                                            {{ $order->status === 'posted' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' }}">
                                            {{ $order->status }}
                                        </span>
                                    </td>
                                    <td class="py-1.5 px-2.5 text-right font-mono font-bold text-slate-900 dark:text-slate-100">
                                        {{ format_currency($order->total, $store) }}
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
