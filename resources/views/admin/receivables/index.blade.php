@extends('layouts.admin.app')

@section('title', __('messages.sidebar_receivables') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
<div class="w-full space-y-0.5 pb-6"
     x-data="{
        viewMode: localStorage.getItem('admin_view_mode') || 'table',
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
     }"
     @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_view_mode', $event.detail)">

    {{-- ============================================================
         1. COMPACT PAGE HEADER (34px - 38px)
         ============================================================ --}}
    <header class="w-full flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 bg-white dark:bg-slate-900 rounded-lg px-2.5 py-1.5 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
        <div class="flex items-center gap-2 min-w-0">
            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-md bg-rose-50 dark:bg-rose-950/70 text-rose-600 dark:text-rose-400 grid place-items-center text-sm sm:text-base shrink-0 shadow-2xs">
                💳
            </div>
            <div class="min-w-0">
                <div class="flex items-center gap-1.5">
                    <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white tracking-tight truncate">
                        {{ __('messages.receivables_title') }}
                    </h1>
                    <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                        {{ $store->name }}
                    </span>
                </div>
                <p class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 truncate">
                    {{ __('messages.receivables_subtitle') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-1.5 shrink-0 self-start sm:self-auto">
            <a href="{{ route('store.admin.customers.index', ['store_slug' => $store->slug]) }}"
               class="h-7 px-2 sm:px-2.5 rounded-md text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition flex items-center gap-1 border border-slate-200/80 dark:border-slate-700 shadow-2xs cursor-pointer">
                <span>👥</span>
                <span>{{ __('messages.sidebar_customer_directory') }}</span>
            </a>
            <a href="{{ route('store.admin.debt_aging.index', ['store_slug' => $store->slug]) }}"
               class="h-7 px-2 sm:px-2.5 rounded-md text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition flex items-center gap-1 border border-slate-200/80 dark:border-slate-700 shadow-2xs cursor-pointer">
                <span>⏳</span>
                <span>{{ __('messages.sidebar_debt_aging') ?? 'Debt Aging' }}</span>
            </a>
        </div>
    </header>

    {{-- Flash Notifications & Errors --}}
    @if (session('success'))
        <div class="w-full p-2 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-2 shadow-2xs">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="w-full p-2 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-lg text-xs font-bold text-rose-800 dark:text-rose-300 flex items-center gap-2 shadow-2xs">
            <span>⚠️</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- ============================================================
         2. 4 CENTERED ROW-BASED STAT CARDS
         ============================================================ --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-0.5 sm:gap-1">
        {{-- Card 1: Total Outstanding --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg p-2 sm:p-2.5 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
            <div class="flex items-center justify-center gap-2.5 sm:gap-3">
                <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-700 dark:bg-rose-950/70 dark:text-rose-300 grid place-items-center text-sm shrink-0">
                    💳
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 truncate">{{ __('messages.receivables_total_outstanding') }}</p>
                    <div class="text-sm sm:text-base font-black text-rose-600 dark:text-rose-400 font-mono tracking-tight">
                        {{ format_currency($summary['total_outstanding'] ?? 0, $store) }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 2: Customers with Debt --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg p-2 sm:p-2.5 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
            <div class="flex items-center justify-center gap-2.5 sm:gap-3">
                <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300 grid place-items-center text-sm shrink-0">
                    👥
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 truncate">{{ __('messages.receivables_customers_with_debt') }}</p>
                    <div class="text-sm sm:text-base font-black text-amber-600 dark:text-amber-400 font-mono tracking-tight">
                        {{ number_format((int) ($summary['customers_with_debt_count'] ?? 0)) }} <span class="text-[10px] font-normal text-slate-500 dark:text-slate-400">{{ __('messages.people') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 3: Recovered Today --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg p-2 sm:p-2.5 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
            <div class="flex items-center justify-center gap-2.5 sm:gap-3">
                <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 grid place-items-center text-sm shrink-0">
                    ✅
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 truncate">{{ __('messages.receivables_collected_today') }}</p>
                    <div class="text-sm sm:text-base font-black text-emerald-600 dark:text-emerald-400 font-mono tracking-tight">
                        {{ format_currency($summary['collected_today'] ?? 0, $store) }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 4: Recovered This Month --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg p-2 sm:p-2.5 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
            <div class="flex items-center justify-center gap-2.5 sm:gap-3">
                <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 dark:bg-indigo-950/70 dark:text-indigo-300 grid place-items-center text-sm shrink-0">
                    📅
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 truncate">{{ __('messages.receivables_collected_this_month') }}</p>
                    <div class="text-sm sm:text-base font-black text-indigo-600 dark:text-indigo-400 font-mono tracking-tight">
                        {{ format_currency($summary['collected_this_month'] ?? 0, $store) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         3. UNIFIED ADMIN TOOLBAR
         ============================================================ --}}
    <x-admin.toolbar
        :search="request('search', $search)"
        :searchPlaceholder="__('messages.search_customer_placeholder')"
        :sort="request('sort', 'newest')"
        :sortOptions="[
            'newest' => __('messages.newest'),
            'oldest' => __('messages.oldest'),
        ]"
        :filters="[
            'filter' => [
                'label' => __('messages.status'),
                'options' => [
                    'all' => __('messages.receivables_filter_active'),
                    'high_debt' => __('messages.receivables_filter_high_debt'),
                    'cleared' => __('messages.receivables_filter_cleared'),
                ],
            ],
        ]"
        :viewMode="'table'"
        :showViewToggle="true"
        :showExportImport="true"
        :exportUrl="$exportUrl"
        :totalCount="$customers->total()"
        :paginator="$customers"
        :perPageOptions="[25 => '25', 50 => '50', 100 => '100', 'all' => 'All']"
    />

    {{-- ============================================================
         4. DUAL VIEWS: CARD GRID VIEW
         ============================================================ --}}
    <div x-show="viewMode === 'card' || viewMode === 'grid'" x-cloak id="receivables-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-1 sm:gap-1.5">
        @forelse ($customers as $cust)
            @php
                $bal = (float) $cust->balance;
                $isOverdue = $bal > 0 && $cust->last_activity && \Carbon\Carbon::parse($cust->last_activity)->diffInDays(now()) > 30;
            @endphp
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition flex flex-col justify-between space-y-2 group">
                
                <div class="space-y-1.5">
                    {{-- Card Top Row: Customer Name & Balance Badge --}}
                    <div class="flex items-start justify-between gap-1.5 border-b border-slate-100 dark:border-slate-800 pb-1.5">
                        <div class="min-w-0">
                            <a href="{{ route('store.admin.receivables.show', ['store_slug' => $store->slug, 'customer' => $cust->customer_id]) }}"
                               class="font-black text-xs text-slate-900 dark:text-slate-100 hover:text-emerald-600 dark:hover:text-emerald-400 block truncate">
                                {{ $cust->name }}
                            </a>
                            <p class="text-[10px] text-slate-400 font-mono mt-0.5">{{ $cust->phone ?: '-' }}</p>
                        </div>

                        <div>
                            @if ($bal > 0)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-black font-mono {{ $isOverdue ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800' : 'bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800' }}">
                                    {{ format_currency($bal, $store) }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold text-emerald-700 bg-emerald-50 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                    {{ __('messages.receivables_settled') }}
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Amounts Breakdown Box --}}
                    <div class="p-2 rounded-md bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 space-y-1 text-xs">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-500 dark:text-slate-400">{{ __('messages.receivables_total_incurred') }}:</span>
                            <span class="font-mono font-semibold text-slate-800 dark:text-slate-200">{{ format_currency((float) ($cust->total_debt_incurred ?? 0), $store) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-500 dark:text-slate-400">{{ __('messages.receivables_total_paid') }}:</span>
                            <span class="font-mono font-semibold text-emerald-600 dark:text-emerald-400">{{ format_currency((float) ($cust->total_collected ?? 0), $store) }}</span>
                        </div>
                        <div class="pt-1 border-t border-slate-200/60 dark:border-slate-700/60 flex items-center justify-between text-[10px] text-slate-400">
                            <span>{{ __('messages.receivables_last_activity') }}:</span>
                            <span>{{ $cust->last_activity ? \Carbon\Carbon::parse($cust->last_activity)->format('d/m/Y') : '-' }}</span>
                        </div>
                    </div>
                </div>

                {{-- Card Bottom Actions --}}
                <div class="pt-1 flex items-center gap-1">
                    @if ($bal > 0)
                        <button type="button"
                                @click="openCollectModal({
                                    customer_id: {{ $cust->customer_id }},
                                    name: '{{ addslashes($cust->name) }}',
                                    phone: '{{ addslashes($cust->phone ?? '') }}',
                                    balance: '{{ $cust->balance }}',
                                    balance_formatted: '{{ number_format($bal, 0) }}'
                                })"
                                class="flex-1 h-7 px-2 rounded-md text-xs font-black bg-emerald-600 hover:bg-emerald-500 text-white shadow-2xs transition flex items-center justify-center gap-1 cursor-pointer active:scale-95">
                            <span>💰</span>
                            <span>{{ __('messages.receivables_collect_btn') }}</span>
                        </button>
                    @endif

                    <a href="{{ route('store.admin.receivables.show', ['store_slug' => $store->slug, 'customer' => $cust->customer_id]) }}"
                       class="h-7 px-2.5 rounded-md text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition flex items-center justify-center flex-1">
                        {{ __('messages.view') }}
                    </a>

                    <a href="{{ route('store.admin.receivables.statement', ['store_slug' => $store->slug, 'customer' => $cust->customer_id]) }}"
                       target="_blank"
                       class="w-7 h-7 rounded-md bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 transition grid place-items-center shrink-0 cursor-pointer"
                       title="{{ __('messages.print_statement') }}">
                        📄
                    </a>
                </div>

            </div>
        @empty
            <div class="col-span-full py-10 text-center text-slate-400 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
                <span class="text-2xl">💳</span>
                <p class="text-xs font-black text-slate-700 dark:text-slate-200 mt-1">{{ __('messages.no_receivables_found') }}</p>
                <p class="text-[11px] text-slate-400 max-w-sm mx-auto mt-0.5">{{ __('messages.no_receivables_found_sub') }}</p>
            </div>
        @endforelse
    </div>

    {{-- ============================================================
         5. DUAL VIEWS: SPREADSHEET TABLE VIEW (Default)
         ============================================================ --}}
    <div x-show="viewMode === 'table'" x-cloak id="receivables-table" class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs overflow-hidden transition">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[750px] text-left text-xs text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800">
                    <tr>
                        <th class="py-1.5 px-2.5 whitespace-nowrap">{{ __('messages.customer') }}</th>
                        <th class="py-1.5 px-2.5 whitespace-nowrap">{{ __('messages.phone') }}</th>
                        <th class="py-1.5 px-2.5 whitespace-nowrap text-right">{{ __('messages.receivables_total_incurred') }}</th>
                        <th class="py-1.5 px-2.5 whitespace-nowrap text-right">{{ __('messages.receivables_total_paid') }}</th>
                        <th class="py-1.5 px-2.5 whitespace-nowrap text-right">{{ __('messages.receivables_outstanding_balance') }}</th>
                        <th class="py-1.5 px-2.5 whitespace-nowrap">{{ __('messages.receivables_last_activity') }}</th>
                        <th class="py-1.5 px-2.5 whitespace-nowrap text-right">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($customers as $cust)
                        @php
                            $bal = (float) $cust->balance;
                            $isOverdue = $bal > 0 && $cust->last_activity && \Carbon\Carbon::parse($cust->last_activity)->diffInDays(now()) > 30;
                        @endphp
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition">
                            {{-- Customer Name --}}
                            <td class="py-1.5 px-2.5 whitespace-nowrap">
                                <a href="{{ route('store.admin.receivables.show', ['store_slug' => $store->slug, 'customer' => $cust->customer_id]) }}"
                                   class="font-black text-xs text-slate-900 dark:text-slate-100 hover:text-emerald-600 dark:hover:text-emerald-400">
                                    {{ $cust->name }}
                                </a>
                            </td>

                            {{-- Phone --}}
                            <td class="py-1.5 px-2.5 font-mono text-[11px] text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                {{ $cust->phone ?: '-' }}
                            </td>

                            {{-- Total Incurred --}}
                            <td class="py-1.5 px-2.5 text-right font-mono text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                {{ format_currency((float) ($cust->total_debt_incurred ?? 0), $store) }}
                            </td>

                            {{-- Total Paid --}}
                            <td class="py-1.5 px-2.5 text-right font-mono font-semibold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                {{ format_currency((float) ($cust->total_collected ?? 0), $store) }}
                            </td>

                            {{-- Outstanding Balance Badge --}}
                            <td class="py-1.5 px-2.5 text-right whitespace-nowrap">
                                @if ($bal > 0)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-black font-mono {{ $isOverdue ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800' : 'bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800' }}">
                                        {{ format_currency($bal, $store) }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold text-emerald-700 bg-emerald-50 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        {{ __('messages.receivables_settled') }}
                                    </span>
                                @endif
                            </td>

                            {{-- Last Activity --}}
                            <td class="py-1.5 px-2.5 text-[11px] text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                @if ($cust->last_activity)
                                    <span>{{ \Carbon\Carbon::parse($cust->last_activity)->format('d/m/Y') }}</span>
                                @else
                                    -
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="py-1.5 px-2.5 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1">
                                    @if ($bal > 0)
                                        <button type="button"
                                                @click="openCollectModal({
                                                    customer_id: {{ $cust->customer_id }},
                                                    name: '{{ addslashes($cust->name) }}',
                                                    phone: '{{ addslashes($cust->phone ?? '') }}',
                                                    balance: '{{ $cust->balance }}',
                                                    balance_formatted: '{{ number_format($bal, 0) }}'
                                                })"
                                                class="h-6 px-2 rounded text-[10px] font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-2xs transition flex items-center gap-1 cursor-pointer active:scale-95">
                                            <span>💰</span>
                                            <span>{{ __('messages.receivables_collect_btn') }}</span>
                                        </button>
                                    @endif

                                    <a href="{{ route('store.admin.receivables.show', ['store_slug' => $store->slug, 'customer' => $cust->customer_id]) }}"
                                       class="h-6 px-2 rounded text-[10px] font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition flex items-center">
                                        {{ __('messages.view') }}
                                    </a>

                                    <a href="{{ route('store.admin.receivables.statement', ['store_slug' => $store->slug, 'customer' => $cust->customer_id]) }}"
                                       target="_blank"
                                       class="w-6 h-6 rounded text-[10px] font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition grid place-items-center cursor-pointer"
                                       title="{{ __('messages.print_statement') }}">
                                        📄
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-400">
                                <div class="space-y-1">
                                    <span class="text-2xl">💳</span>
                                    <p class="text-xs font-bold text-slate-600 dark:text-slate-300">{{ __('messages.no_receivables_found') }}</p>
                                    <p class="text-[11px] text-slate-400">{{ __('messages.no_receivables_found_sub') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Bottom Pagination --}}
    @if (method_exists($customers, 'links'))
        <div class="pt-0.5">{{ $customers->links() }}</div>
    @endif

    {{-- ============================================================
         6. QUICK DEBT COLLECTION MODAL
         ============================================================ --}}
    <div x-show="collectModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-3 bg-slate-900/60 backdrop-blur-xs overflow-y-auto"
         @keydown.escape.window="closeCollectModal()" @click.self="closeCollectModal()">
        <div class="relative w-full max-w-md rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-2xl space-y-3 text-slate-900 dark:text-slate-100 animate-in fade-in zoom-in-95 duration-150">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5">
                    <span>💰</span>
                    <span>{{ __('messages.receivables_collect_payment_modal_title') }}</span>
                </h3>
                <button type="button" @click="closeCollectModal()" class="w-6 h-6 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-slate-600 grid place-items-center text-xs font-bold transition cursor-pointer">✕</button>
            </div>

            <form x-show="selectedCustomer" :action="'/store/{{ $store->slug }}/admin/receivables/' + (selectedCustomer ? selectedCustomer.customer_id : '') + '/collect'" method="POST" enctype="multipart/form-data" class="space-y-2 text-xs">
                @csrf

                {{-- Customer Info Preview --}}
                <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/70 border border-slate-100 dark:border-slate-700/60 space-y-1">
                    <div class="text-[10px] text-slate-500 dark:text-slate-400">{{ __('messages.customer') }}:</div>
                    <div class="font-black text-xs text-slate-900 dark:text-slate-100" x-text="selectedCustomer?.name"></div>
                    <div class="flex items-center justify-between pt-1 border-t border-slate-200/60 dark:border-slate-700/60 text-xs">
                        <span class="text-slate-500 dark:text-slate-400">{{ __('messages.receivables_current_debt') }}:</span>
                        <span class="font-black text-rose-600 dark:text-rose-400 font-mono" x-text="window.formatCurrency ? window.formatCurrency(selectedCustomer?.balance) : selectedCustomer?.balance_formatted"></span>
                    </div>
                </div>

                {{-- Amount Input --}}
                <div>
                    <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">
                        {{ __('messages.receivables_amount_to_collect') }} <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number"
                               name="amount"
                               x-model="collectAmount"
                               step="any"
                               min="1"
                               :max="selectedCustomer?.balance"
                               required
                               class="w-full h-8 px-2.5 text-xs font-bold font-mono rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        <button type="button"
                                @click="collectAmount = selectedCustomer?.balance"
                                class="absolute right-1 top-1 h-6 px-2 text-[10px] font-bold rounded bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-950 dark:text-emerald-300 cursor-pointer transition">
                            {{ __('messages.receivables_pay_full') }}
                        </button>
                    </div>
                </div>

                {{-- Payment Method --}}
                <div>
                    <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">
                        {{ __('messages.payment_method') }}
                    </label>
                    <select name="payment_method"
                            x-model="collectPaymentMethod"
                            class="w-full h-8 px-2.5 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        <option value="cash">{{ __('messages.payment_method_cash') }}</option>
                        <option value="kpay">KPay</option>
                        <option value="wave">WavePay</option>
                        <option value="bank">{{ __('messages.payment_method_bank') }}</option>
                        <option value="other">{{ __('messages.payment_method_other') }}</option>
                    </select>
                </div>

                {{-- Reference No --}}
                <div>
                    <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">
                        {{ __('messages.reference_no') }}
                    </label>
                    <input type="text"
                           name="reference_no"
                           x-model="collectReferenceNo"
                           placeholder="Txn ID"
                           class="w-full h-8 px-2.5 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                </div>

                {{-- Payment Slip Upload --}}
                <div>
                    <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1 flex items-center justify-between">
                        <span>{{ __('messages.receivables_slip_image') }}</span>
                        <span class="text-[10px] font-normal text-slate-400">{{ __('messages.optional') ?? 'Optional' }}</span>
                    </label>
                    <input type="file"
                           name="slip_image"
                           accept="image/jpeg,image/png,image/webp,application/pdf"
                           class="block w-full text-xs text-slate-500 dark:text-slate-400 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-[11px] file:font-bold file:bg-emerald-50 file:text-emerald-700 dark:file:bg-emerald-950/70 dark:file:text-emerald-300 hover:file:bg-emerald-100 cursor-pointer border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-800">
                    <p class="text-[10px] text-slate-400 mt-0.5">{{ __('messages.receivables_slip_image_help') }}</p>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">
                        {{ __('messages.notes') }}
                    </label>
                    <input type="text"
                           name="notes"
                           x-model="collectNotes"
                           placeholder="{{ __('messages.receivables_collect_note_placeholder') }}"
                           class="w-full h-8 px-2.5 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="closeCollectModal()" class="flex-1 h-8 rounded-lg font-bold text-xs bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 transition cursor-pointer">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit" class="flex-1 h-8 rounded-lg font-black text-xs bg-emerald-600 hover:bg-emerald-500 text-white shadow-2xs transition cursor-pointer active:scale-95">
                        {{ __('messages.receivables_confirm_collection') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
