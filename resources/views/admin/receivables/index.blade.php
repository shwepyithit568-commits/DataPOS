@extends('layouts.admin.app')

@section('title', __('messages.sidebar_receivables') . ' - ' . ($store->name ?? 'DataPOS'))

@section('content')
<div class="space-y-6" x-data="{
    collectModalOpen: false,
    selectedCustomer: null,
    collectAmount: '',
    collectPaymentMethod: 'cash',
    collectReferenceNo: '',
    collectNotes: '',
    openCollectModal(customer) {
        this.selectedCustomer = customer;
        this.collectAmount = customer.balance;
        this.collectPaymentMethod = 'cash';
        this.collectReferenceNo = '';
        this.collectNotes = '';
        this.collectModalOpen = true;
    },
    closeCollectModal() {
        this.collectModalOpen = false;
        this.selectedCustomer = null;
    }
}">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                <a href="{{ route('store.admin.dashboard', ['store_slug' => $store->slug]) }}" class="hover:text-violet-600 dark:hover:text-violet-400">{{ __('messages.admin_dashboard') }}</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-200 font-semibold">{{ __('messages.sidebar_receivables') }}</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100 font-outfit mt-1">
                {{ __('messages.receivables_title') }}
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                {{ __('messages.receivables_subtitle') }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('store.admin.customers.index', ['store_slug' => $store->slug]) }}"
               class="inline-flex items-center gap-2 px-3.5 py-2 text-xs sm:text-sm font-semibold rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 shadow-sm transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span>{{ __('messages.sidebar_customer_directory') }}</span>
            </a>
        </div>
    </div>

    {{-- KPI Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Outstanding Receivables --}}
        <div class="p-4 sm:p-5 rounded-2xl border border-rose-100 bg-gradient-to-br from-rose-50/70 to-white dark:border-rose-900/40 dark:from-rose-950/20 dark:to-slate-900 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-rose-700 dark:text-rose-400 uppercase tracking-wider">{{ __('messages.receivables_total_outstanding') }}</span>
                <span class="p-2 rounded-xl bg-rose-100 text-rose-600 dark:bg-rose-900/50 dark:text-rose-300">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-rose-600 dark:text-rose-400 font-outfit">
                    {{ number_format((float) ($summary['total_outstanding'] ?? 0), 0) }} <span class="text-sm font-normal text-slate-500 dark:text-slate-400">Ks</span>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    {{ __('messages.receivables_uncollected_debt') }}
                </div>
            </div>
        </div>

        {{-- Customers with Outstanding Debt --}}
        <div class="p-4 sm:p-5 rounded-2xl border border-amber-100 bg-gradient-to-br from-amber-50/70 to-white dark:border-amber-900/40 dark:from-amber-950/20 dark:to-slate-900 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-amber-700 dark:text-amber-400 uppercase tracking-wider">{{ __('messages.receivables_customers_with_debt') }}</span>
                <span class="p-2 rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-900/50 dark:text-amber-300">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-amber-700 dark:text-amber-300 font-outfit">
                    {{ number_format((int) ($summary['customers_with_debt_count'] ?? 0)) }} <span class="text-sm font-normal text-slate-500 dark:text-slate-400">{{ __('messages.people') }}</span>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    {{ __('messages.receivables_active_debtors') }}
                </div>
            </div>
        </div>

        {{-- Collections Today --}}
        <div class="p-4 sm:p-5 rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-50/70 to-white dark:border-emerald-900/40 dark:from-emerald-950/20 dark:to-slate-900 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">{{ __('messages.receivables_collected_today') }}</span>
                <span class="p-2 rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-900/50 dark:text-emerald-300">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 font-outfit">
                    {{ number_format((float) ($summary['collected_today'] ?? 0), 0) }} <span class="text-sm font-normal text-slate-500 dark:text-slate-400">Ks</span>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    {{ __('messages.receivables_today_recovered') }}
                </div>
            </div>
        </div>

        {{-- Collections This Month --}}
        <div class="p-4 sm:p-5 rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50/70 to-white dark:border-indigo-900/40 dark:from-indigo-950/20 dark:to-slate-900 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-indigo-700 dark:text-indigo-400 uppercase tracking-wider">{{ __('messages.receivables_collected_this_month') }}</span>
                <span class="p-2 rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-300">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400 font-outfit">
                    {{ number_format((float) ($summary['collected_this_month'] ?? 0), 0) }} <span class="text-sm font-normal text-slate-500 dark:text-slate-400">Ks</span>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    {{ now()->translatedFormat('F Y') }}
                </div>
            </div>
        </div>
    </div>

    {{-- Filter and Search Bar --}}
    <div class="p-4 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm">
        <form method="GET" action="{{ route('store.admin.receivables.index', ['store_slug' => $store->slug]) }}" class="flex flex-col sm:flex-row gap-3 items-center justify-between">
            <div class="relative w-full sm:w-80">
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="{{ __('messages.search_customer_placeholder') }}"
                       class="w-full pl-10 pr-4 py-2 text-sm rounded-xl border border-slate-200 bg-slate-50/50 text-slate-900 dark:border-slate-700 dark:bg-slate-800/80 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:outline-none transition">
                <span class="absolute left-3 top-2.5 text-slate-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto overflow-x-auto pb-1 sm:pb-0">
                <a href="{{ route('store.admin.receivables.index', ['store_slug' => $store->slug, 'search' => request('search')]) }}"
                   class="px-3 py-1.5 text-xs font-semibold rounded-xl border transition whitespace-nowrap {{ !request('filter') ? 'bg-violet-600 text-white border-violet-600 shadow-sm' : 'border-slate-200 text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                    {{ __('messages.receivables_filter_active') }}
                </a>
                <a href="{{ route('store.admin.receivables.index', ['store_slug' => $store->slug, 'filter' => 'high_debt', 'search' => request('search')]) }}"
                   class="px-3 py-1.5 text-xs font-semibold rounded-xl border transition whitespace-nowrap {{ request('filter') === 'high_debt' ? 'bg-rose-600 text-white border-rose-600 shadow-sm' : 'border-slate-200 text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                    {{ __('messages.receivables_filter_high_debt') }} (>= ၁ သိန်း)
                </a>
                <a href="{{ route('store.admin.receivables.index', ['store_slug' => $store->slug, 'filter' => 'cleared', 'search' => request('search')]) }}"
                   class="px-3 py-1.5 text-xs font-semibold rounded-xl border transition whitespace-nowrap {{ request('filter') === 'cleared' ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'border-slate-200 text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                    {{ __('messages.receivables_filter_cleared') }}
                </a>
            </div>
        </form>
    </div>

    {{-- Receivables Table --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50/80 text-xs uppercase font-semibold text-slate-500 border-b border-slate-200/80 dark:bg-slate-800/60 dark:text-slate-400 dark:border-slate-800">
                    <tr>
                        <th class="px-5 py-3.5">{{ __('messages.customer') }}</th>
                        <th class="px-4 py-3.5">{{ __('messages.phone') }}</th>
                        <th class="px-4 py-3.5 text-right">{{ __('messages.receivables_total_incurred') }}</th>
                        <th class="px-4 py-3.5 text-right">{{ __('messages.receivables_total_paid') }}</th>
                        <th class="px-5 py-3.5 text-right">{{ __('messages.receivables_outstanding_balance') }}</th>
                        <th class="px-4 py-3.5">{{ __('messages.receivables_last_activity') }}</th>
                        <th class="px-5 py-3.5 text-right">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($customers as $cust)
                        @php
                            $bal = (float) $cust->balance;
                            $isOverdue = $bal > 0 && $cust->last_activity && \Carbon\Carbon::parse($cust->last_activity)->diffInDays(now()) > 30;
                        @endphp
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                            {{-- Customer Name --}}
                            <td class="px-5 py-3.5">
                                <a href="{{ route('store.admin.receivables.show', ['store_slug' => $store->slug, 'customer' => $cust->customer_id]) }}" class="font-bold text-slate-900 dark:text-slate-100 hover:text-violet-600 dark:hover:text-violet-400">
                                    {{ $cust->name }}
                                </a>
                                @if(!empty($cust->address))
                                    <div class="text-xs text-slate-400 truncate max-w-xs">{{ $cust->address }}</div>
                                @endif
                            </td>

                            {{-- Phone --}}
                            <td class="px-4 py-3.5 text-slate-600 dark:text-slate-300 font-mono text-xs">
                                {{ $cust->phone ?: '-' }}
                            </td>

                            {{-- Total Incurred --}}
                            <td class="px-4 py-3.5 text-right text-slate-500 dark:text-slate-400 font-mono text-xs">
                                {{ number_format((float) ($cust->total_debt_incurred ?? 0), 0) }} Ks
                            </td>

                            {{-- Total Paid / Collected --}}
                            <td class="px-4 py-3.5 text-right text-emerald-600 dark:text-emerald-400 font-mono text-xs">
                                {{ number_format((float) ($cust->total_collected ?? 0), 0) }} Ks
                            </td>

                            {{-- Current Balance Badge --}}
                            <td class="px-5 py-3.5 text-right">
                                @if ($bal > 0)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-black font-outfit {{ $isOverdue ? 'bg-rose-100 text-rose-700 border border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800' : 'bg-amber-100 text-amber-800 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800' }}">
                                        {{ number_format($bal, 0) }} Ks
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-100 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        {{ __('messages.receivables_settled') }}
                                    </span>
                                @endif
                            </td>

                            {{-- Last Activity --}}
                            <td class="px-4 py-3.5 text-xs text-slate-500 dark:text-slate-400">
                                @if ($cust->last_activity)
                                    <div>{{ \Carbon\Carbon::parse($cust->last_activity)->translatedFormat('d M Y') }}</div>
                                    <div class="text-[10px] text-slate-400">{{ \Carbon\Carbon::parse($cust->last_activity)->diffForHumans() }}</div>
                                @else
                                    -
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if ($bal > 0)
                                        <button type="button"
                                                @click="openCollectModal({
                                                    customer_id: {{ $cust->customer_id }},
                                                    name: '{{ addslashes($cust->name) }}',
                                                    phone: '{{ addslashes($cust->phone ?? '') }}',
                                                    balance: '{{ $cust->balance }}',
                                                    balance_formatted: '{{ number_format($bal, 0) }}'
                                                })"
                                                class="px-2.5 py-1.5 text-xs font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white shadow-sm transition">
                                            {{ __('messages.receivables_collect_btn') }}
                                        </button>
                                    @endif

                                    <a href="{{ route('store.admin.receivables.show', ['store_slug' => $store->slug, 'customer' => $cust->customer_id]) }}"
                                       class="p-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-600 dark:border-slate-700 dark:hover:bg-slate-800 dark:text-slate-300 transition"
                                       title="{{ __('messages.view_details') }}">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>

                                    <a href="{{ route('store.admin.receivables.statement', ['store_slug' => $store->slug, 'customer' => $cust->customer_id]) }}"
                                       target="_blank"
                                       class="p-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-600 dark:border-slate-700 dark:hover:bg-slate-800 dark:text-slate-300 transition"
                                       title="{{ __('messages.print_statement') }}">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-slate-400 dark:text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-slate-300 dark:text-slate-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span class="text-sm font-semibold">{{ __('messages.no_receivables_found') }}</span>
                                    <p class="text-xs text-slate-400 mt-0.5">{{ __('messages.no_receivables_found_sub') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($customers->hasPages())
            <div class="px-5 py-4 border-t border-slate-200/80 dark:border-slate-800">
                {{ $customers->appends(request()->query())->links() }}
            </div>
        @endif
    </div>

    {{-- Quick Debt Collection Modal --}}
    <div x-show="collectModalOpen"
         x-cloak
         x-transition.opacity.duration.200ms
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
         @keydown.escape.window="closeCollectModal()">
        <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-800 p-6 overflow-hidden"
             @click.outside="closeCollectModal()">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 font-outfit">
                    {{ __('messages.receivables_collect_payment_modal_title') }}
                </h3>
                <button type="button" @click="closeCollectModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form x-show="selectedCustomer" :action="'/store/{{ $store->slug }}/admin/receivables/' + (selectedCustomer ? selectedCustomer.customer_id : '') + '/collect'" method="POST" class="mt-4 space-y-4">
                @csrf

                {{-- Customer Info Preview --}}
                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/70 border border-slate-100 dark:border-slate-700/60">
                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.customer') }}:</div>
                    <div class="font-bold text-slate-900 dark:text-slate-100" x-text="selectedCustomer?.name"></div>
                    <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-200/60 dark:border-slate-700/60 text-xs">
                        <span class="text-slate-500 dark:text-slate-400">{{ __('messages.receivables_current_debt') }}:</span>
                        <span class="font-black text-rose-600 dark:text-rose-400 font-outfit" x-text="(selectedCustomer?.balance_formatted || '0') + ' Ks'"></span>
                    </div>
                </div>

                {{-- Amount Input --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.receivables_amount_to_collect') }} (Ks) *
                    </label>
                    <div class="relative">
                        <input type="number"
                               name="amount"
                               x-model="collectAmount"
                               step="any"
                               min="1"
                               :max="selectedCustomer?.balance"
                               required
                               class="w-full px-3.5 py-2.5 text-base font-bold font-outfit rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        <button type="button"
                                @click="collectAmount = selectedCustomer?.balance"
                                class="absolute right-2 top-2 px-2 py-1 text-[11px] font-bold rounded-md bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-950 dark:text-emerald-300">
                            {{ __('messages.receivables_pay_full') }}
                        </button>
                    </div>
                </div>

                {{-- Payment Method --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.payment_method') }}
                    </label>
                    <select name="payment_method"
                            x-model="collectPaymentMethod"
                            class="w-full px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 text-sm focus:outline-none">
                        <option value="cash">{{ __('messages.payment_method_cash') }} (ငွေသား)</option>
                        <option value="kpay">KPay (KBZPay)</option>
                        <option value="wave">WavePay</option>
                        <option value="bank">{{ __('messages.payment_method_bank') }} (ဘဏ်လွှဲ)</option>
                        <option value="other">{{ __('messages.payment_method_other') }}</option>
                    </select>
                </div>

                {{-- Reference No --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.reference_no') }} (Optional)
                    </label>
                    <input type="text"
                           name="reference_no"
                           x-model="collectReferenceNo"
                           placeholder="Txn ID / စလစ်နံပါတ်"
                           class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.notes') }} (Optional)
                    </label>
                    <input type="text"
                           name="notes"
                           x-model="collectNotes"
                           placeholder="{{ __('messages.receivables_collect_note_placeholder') }}"
                           class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="closeCollectModal()" class="px-4 py-2 text-xs sm:text-sm font-semibold rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit" class="px-5 py-2 text-xs sm:text-sm font-bold rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white shadow-md shadow-emerald-500/20 transition">
                        {{ __('messages.receivables_confirm_collection') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
