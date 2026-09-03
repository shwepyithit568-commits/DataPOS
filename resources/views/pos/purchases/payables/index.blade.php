@extends('layouts.admin.app')

@section('title', __('messages.sidebar_payables') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
@php
    $totalSuppliersWithDebt = count($suppliers);
    $totalUnpaidOrders = count($unpaidOrders);
    $highestDebtItem = $suppliers->first();
    $highestDebtVal = $highestDebtItem ? (float) $highestDebtItem['balance'] : 0;
    $highestDebtSupplierName = $highestDebtItem ? $highestDebtItem['supplier']->name : '—';
@endphp

<div class="w-full space-y-0.5 pb-6"
     x-data="{
         activeTab: localStorage.getItem('datapos_payables_tab') || 'vouchers',
         setTab(tab) {
             this.activeTab = tab;
             localStorage.setItem('datapos_payables_tab', tab);
         },
         viewMode: localStorage.getItem('datapos_payables_view_mode') || 'table',
         setViewMode(mode) {
             this.viewMode = mode;
             localStorage.setItem('datapos_payables_view_mode', mode);
         },
         search: '',
         sort: 'highest',
         statusFilter: 'all',

         // Specific PO Quick Pay Modal State
         payModalOpen: false,
         payPoId: null,
         payPoNumber: '',
         payPoBalance: 0,
         payAmount: '',
         payReference: '',
         slipPreviews: [],
         openPoPay(id, number, balance) {
             this.payPoId = id;
             this.payPoNumber = number;
             this.payPoBalance = balance;
             this.payAmount = balance;
             this.payReference = '';
             this.slipPreviews = [];
             this.payModalOpen = true;
         },
         addSlipImages(event) {
             const files = Array.from(event.target.files);
             const remaining = 4 - this.slipPreviews.length;
             files.slice(0, remaining).forEach(file => {
                 if (!file.type.startsWith('image/')) return;
                 const reader = new FileReader();
                 reader.onload = (e) => {
                     this.slipPreviews.push({ url: e.target.result, name: file.name });
                 };
                 reader.readAsDataURL(file);
             });
         },
         removeSlip(idx) {
             this.slipPreviews.splice(idx, 1);
         },
         fmt(n) { return typeof window.formatCurrency === 'function' ? window.formatCurrency(n) : Number(n).toLocaleString(); },
         fmtQty(n) { return typeof window.formatQuantity === 'function' ? window.formatQuantity(n) : String(n); },

         // Vouchers Data
         vouchers: {{ Js::from($unpaidOrders->map(function ($po) use ($store) {
             return [
                 'id' => $po->id,
                 'po_number' => $po->po_number,
                 'supplier_id' => $po->supplier_id,
                 'supplier_name' => $po->supplier?->name ?? '—',
                 'date' => $po->received_at?->format('d M Y') ?? $po->created_at->format('d M Y'),
                 'timestamp' => $po->received_at ? $po->received_at->timestamp : $po->created_at->timestamp,
                 'days_old' => $po->received_at ? (int) $po->received_at->diffInDays(now()) : (int) $po->created_at->diffInDays(now()),
                 'total_cost' => (float) $po->total_cost,
                 'paid_amount' => (float) $po->paid_amount,
                 'remaining_balance' => (float) $po->remaining_balance,
                 'payment_status' => $po->payment_status,
                 'items_count' => $po->items->count(),
                 'view_url' => url('/store/' . $store->slug . '/pos/purchases/' . $po->id),
                 'supplier_url' => $po->supplier_id ? url('/store/' . $store->slug . '/pos/purchases/payables/' . $po->supplier_id) : '#',
             ];
         })) }},

         // Suppliers Data
         suppliers: {{ Js::from($suppliers->map(function ($s) use ($store) {
             return [
                 'id' => $s['supplier']->id,
                 'name' => $s['supplier']->name,
                 'contact_person' => $s['supplier']->contact_person ?? '',
                 'phone' => $s['supplier']->phone ?? '',
                 'unpaid_count' => (int) $s['unpaid_count'],
                 'balance' => (float) $s['balance'],
                 'oldest_date' => $s['oldest_unpaid_date'] ? $s['oldest_unpaid_date']->format('d M Y') : '—',
                 'oldest_timestamp' => $s['oldest_unpaid_date'] ? $s['oldest_unpaid_date']->timestamp : 0,
                 'days_old' => $s['oldest_unpaid_date'] ? (int) $s['oldest_unpaid_date']->diffInDays(now()) : 0,
                 'view_url' => url('/store/' . $store->slug . '/pos/purchases/payables/' . $s['supplier']->id),
             ];
         })) }},

         get filteredVouchers() {
             const q = this.search.trim().toLowerCase();
             let list = this.vouchers.filter(v => {
                 if (this.statusFilter === 'unpaid' && v.payment_status !== 'unpaid') return false;
                 if (this.statusFilter === 'partial' && v.payment_status !== 'partial') return false;
                 if (!q) return true;
                 return v.po_number.toLowerCase().includes(q)
                     || v.supplier_name.toLowerCase().includes(q);
             });

             if (this.sort === 'highest') {
                 list.sort((a, b) => b.remaining_balance - a.remaining_balance);
             } else if (this.sort === 'lowest') {
                 list.sort((a, b) => a.remaining_balance - b.remaining_balance);
             } else if (this.sort === 'newest') {
                 list.sort((a, b) => b.timestamp - a.timestamp);
             } else if (this.sort === 'oldest' || this.sort === 'oldest_date') {
                 list.sort((a, b) => a.timestamp - b.timestamp);
             } else if (this.sort === 'name_asc') {
                 list.sort((a, b) => a.supplier_name.localeCompare(b.supplier_name));
             }
             return list;
         },

         get filteredSuppliers() {
             const q = this.search.trim().toLowerCase();
             let list = this.suppliers.filter(s => {
                 if (!q) return true;
                 return s.name.toLowerCase().includes(q)
                     || s.phone.toLowerCase().includes(q)
                     || s.contact_person.toLowerCase().includes(q);
             });

             if (this.sort === 'highest') {
                 list.sort((a, b) => b.balance - a.balance);
             } else if (this.sort === 'lowest') {
                 list.sort((a, b) => a.balance - b.balance);
             } else if (this.sort === 'most_unpaid') {
                 list.sort((a, b) => b.unpaid_count - a.unpaid_count);
             } else if (this.sort === 'name_asc') {
                 list.sort((a, b) => a.name.localeCompare(b.name));
             } else if (this.sort === 'oldest_date' || this.sort === 'oldest') {
                 list.sort((a, b) => a.oldest_timestamp - b.oldest_timestamp);
             }
             return list;
         }
     }"
     @keydown.escape.window="payModalOpen = false">

    {{-- 1. Compact Header Banner (34px - 38px) --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-8 h-8 rounded-lg bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 grid place-items-center text-base font-bold shadow-xs flex-shrink-0">
                💳
            </span>
            <div class="min-w-0">
                <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5 truncate">
                    <span>{{ __('messages.sidebar_payables') }}</span>
                    <span class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 hidden sm:inline">({{ $store->name }})</span>
                </h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                    {{ __('messages.payables_subtitle') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-1.5 self-start sm:self-auto shrink-0 flex-wrap">
            <a href="{{ url('/store/' . $store->slug . '/pos/purchases') }}"
               class="h-7 px-2.5 rounded-md bg-sky-50 dark:bg-sky-950/50 text-sky-700 dark:text-sky-300 hover:bg-sky-100 dark:hover:bg-sky-900/60 border border-sky-200 dark:border-sky-800 text-xs font-bold transition inline-flex items-center gap-1.5 active:scale-95 cursor-pointer">
                <span>🛒</span>
                <span class="hidden sm:inline">{{ __('messages.po_list_title') }}</span>
            </a>
            <a href="{{ url('/store/' . $store->slug . '/pos/purchases/returns') }}"
               class="h-7 px-2.5 rounded-md bg-orange-50 dark:bg-orange-950/50 text-orange-700 dark:text-orange-300 hover:bg-orange-100 dark:hover:bg-orange-900/60 border border-orange-200 dark:border-orange-800 text-xs font-bold transition inline-flex items-center gap-1.5 active:scale-95 cursor-pointer">
                <span>↩️</span>
                <span class="hidden sm:inline">{{ __('messages.po_returns_title') }}</span>
            </a>
            <a href="{{ url('/store/' . $store->slug . '/pos') }}"
               class="h-7 px-2.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-xs font-bold transition inline-flex items-center gap-1 active:scale-95 cursor-pointer">
                <span>←</span>
                <span>{{ __('messages.back_to_pos') }}</span>
            </a>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if (session('success'))
        <div class="p-2.5 sm:p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs text-emerald-700 dark:text-emerald-300 flex items-start justify-between gap-2 shadow-2xs"
             x-data="{ show: true }" x-show="show">
            <div class="flex items-center gap-2">
                <span class="text-sm font-bold shrink-0">✓</span>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="show = false" class="text-emerald-500 hover:text-emerald-700 font-black text-xs">✕</button>
        </div>
    @endif
    @if (session('error'))
        <div class="p-2.5 sm:p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-lg text-xs text-rose-700 dark:text-rose-300 flex items-start justify-between gap-2 shadow-2xs"
             x-data="{ show: true }" x-show="show">
            <div class="flex items-center gap-2">
                <span class="text-sm font-bold shrink-0">⚠️</span>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" @click="show = false" class="text-rose-500 hover:text-rose-700 font-black text-xs">✕</button>
        </div>
    @endif

    {{-- 2. Row-Based Center-Aligned Summary Stat Cards (gap-0.5 sm:gap-1) --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-0.5 sm:gap-1" role="list">
        {{-- Card 1: Total Outstanding Payables --}}
        <div role="listitem" class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-rose-100 text-rose-600 dark:bg-rose-950/70 dark:text-rose-300 shadow-inner text-xs sm:text-sm font-bold">
                💰
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-rose-600 dark:text-rose-400 leading-none tabular-nums font-outfit truncate">
                    {{ format_currency($totalOutstanding, $store) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.payables_total_outstanding') }}
                </p>
            </div>
        </div>

        {{-- Card 2: Suppliers With Debt --}}
        <div role="listitem" class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-amber-100 text-amber-600 dark:bg-amber-950/70 dark:text-amber-300 shadow-inner text-xs sm:text-sm font-bold">
                🏭
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-outfit">
                    {{ number_format($totalSuppliersWithDebt) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.payables_suppliers_with_debt') }}
                </p>
            </div>
        </div>

        {{-- Card 3: Total Unpaid PO Vouchers --}}
        <div role="listitem" class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-sky-100 text-sky-600 dark:bg-sky-950/70 dark:text-sky-300 shadow-inner text-xs sm:text-sm font-bold">
                📋
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-outfit">
                    {{ number_format($totalUnpaidOrders) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.payables_total_unpaid_pos') }}
                </p>
            </div>
        </div>

        {{-- Card 4: Highest Single Debt --}}
        <div role="listitem" class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-violet-100 text-violet-600 dark:bg-violet-950/70 dark:text-violet-300 shadow-inner text-xs sm:text-sm font-bold">
                ⚠️
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-outfit truncate">
                    {{ format_currency($highestDebtVal, $store) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider" title="{{ $highestDebtSupplierName }}">
                    {{ __('messages.payables_highest_debt') }}
                </p>
            </div>
        </div>
    </div>

    {{-- 3. Interactive Toolbar (Tab Switcher, Search, Status Filter, Sort, Excel Export, View Mode) --}}
    <div class="bg-white dark:bg-slate-900 px-2.5 py-1 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col md:flex-row md:items-center md:justify-between gap-1">
        {{-- Left: Tab Switcher & Search Bar & Status Filter --}}
        <div class="flex flex-wrap items-center gap-1.5 flex-1 min-w-0">
            {{-- Tab Switcher Pills --}}
            <div class="flex items-center bg-slate-100 dark:bg-slate-800 p-0.5 rounded-md border border-slate-200 dark:border-slate-700">
                <button type="button" @click="setTab('vouchers')"
                        class="h-6 px-2 rounded text-[11px] font-bold transition flex items-center gap-1 cursor-pointer"
                        :class="activeTab === 'vouchers' ? 'bg-white dark:bg-slate-700 text-rose-600 dark:text-rose-400 shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'">
                    <span>📋</span>
                    <span>{{ __('messages.payables_tab_vouchers') }}</span>
                    <span class="ml-0.5 px-1 rounded-full text-[9px] font-mono bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800"
                          x-text="vouchers.length"></span>
                </button>
                <button type="button" @click="setTab('suppliers')"
                        class="h-6 px-2 rounded text-[11px] font-bold transition flex items-center gap-1 cursor-pointer"
                        :class="activeTab === 'suppliers' ? 'bg-white dark:bg-slate-700 text-rose-600 dark:text-rose-400 shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'">
                    <span>🏭</span>
                    <span>{{ __('messages.payables_tab_suppliers') }}</span>
                    <span class="ml-0.5 px-1 rounded-full text-[9px] font-mono bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300"
                          x-text="suppliers.length"></span>
                </button>
            </div>

            {{-- Live Search Input --}}
            <div class="relative min-w-[170px] sm:min-w-[220px] flex-1 max-w-xs">
                <input type="text"
                       x-model="search"
                       :placeholder="activeTab === 'vouchers' ? '{{ __('messages.payables_search_vouchers') }}' : '{{ __('messages.payables_search') }}'"
                       class="w-full h-7 pl-8 pr-7 rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-semibold text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-rose-500 focus:bg-white dark:focus:bg-slate-900 transition" />
                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                <button x-show="search.length > 0" @click="search = ''" class="absolute right-2 top-1.5 text-slate-400 hover:text-slate-600 text-xs font-black cursor-pointer">
                    ✕
                </button>
            </div>

            {{-- Status Filter for Vouchers Tab --}}
            <template x-if="activeTab === 'vouchers'">
                <div class="flex items-center gap-1">
                    <button type="button" @click="statusFilter = 'all'"
                            class="h-7 px-2 rounded-md text-[11px] font-bold border transition cursor-pointer"
                            :class="statusFilter === 'all' ? 'bg-slate-800 text-white dark:bg-white dark:text-slate-900 border-transparent shadow-2xs' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700'">
                        {{ __('messages.payables_filter_all') }}
                    </button>
                    <button type="button" @click="statusFilter = 'unpaid'"
                            class="h-7 px-2 rounded-md text-[11px] font-bold border transition cursor-pointer"
                            :class="statusFilter === 'unpaid' ? 'bg-rose-600 text-white border-transparent shadow-2xs' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-900/60'">
                        {{ __('messages.payables_filter_unpaid') }}
                    </button>
                    <button type="button" @click="statusFilter = 'partial'"
                            class="h-7 px-2 rounded-md text-[11px] font-bold border transition cursor-pointer"
                            :class="statusFilter === 'partial' ? 'bg-amber-600 text-white border-transparent shadow-2xs' : 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-900/60'">
                        {{ __('messages.payables_filter_partial') }}
                    </button>
                </div>
            </template>

            {{-- Sort Dropdown --}}
            <select x-model="sort"
                    class="h-7 rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-2 text-xs font-bold text-slate-700 dark:text-slate-300 outline-none focus:ring-1 focus:ring-rose-500">
                <option value="highest">💰 {{ __('messages.po_sort_highest') }}</option>
                <option value="lowest">📉 {{ __('messages.po_sort_lowest') }}</option>
                <option value="newest" x-show="activeTab === 'vouchers'">⏳ {{ __('messages.po_sort_newest') }}</option>
                <option value="oldest">⏳ {{ __('messages.payables_oldest') }}</option>
                <option value="name_asc">🔤 {{ __('messages.payables_supplier') }} (A-Z)</option>
            </select>
        </div>

        {{-- Right: Excel/CSV Export & View Mode Switcher --}}
        <div class="flex items-center gap-1 shrink-0 self-end md:self-auto">
            <a :href="`{{ route('pos.purchases.payables.export', ['store_slug' => $store->slug]) }}?format=excel&type=${activeTab}`"
               class="h-7 px-2.5 rounded-md border border-emerald-300 dark:border-emerald-800/80 bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 text-xs font-black shadow-2xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>
                </svg>
                <span>Excel</span>
            </a>

            <a :href="`{{ route('pos.purchases.payables.export', ['store_slug' => $store->slug]) }}?format=csv&type=${activeTab}`"
               class="h-7 px-2 rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition inline-flex items-center gap-1 cursor-pointer">
                <span>CSV</span>
            </a>

            <div class="flex items-center gap-0.5 bg-slate-100 dark:bg-slate-800 p-0.5 rounded-md border border-slate-200 dark:border-slate-700">
                <button type="button" @click="setViewMode('table')"
                        class="h-6 px-2 rounded text-[11px] font-bold transition flex items-center gap-1 cursor-pointer"
                        :class="viewMode === 'table' ? 'bg-white dark:bg-slate-700 text-rose-600 dark:text-rose-400 shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'">
                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                    <span>Table</span>
                </button>
                <button type="button" @click="setViewMode('cards')"
                        class="h-6 px-2 rounded text-[11px] font-bold transition flex items-center gap-1 cursor-pointer"
                        :class="viewMode === 'cards' || viewMode === 'card' ? 'bg-white dark:bg-slate-700 text-rose-600 dark:text-rose-400 shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'">
                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    <span>Cards</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ── TAB 1: UNPAID VOUCHERS (မချေသေးသော ကုန်ကြွေးဘောင်ချာများ) ────────────────── --}}
    <div x-show="activeTab === 'vouchers'">
        {{-- Empty State (No unpaid vouchers) --}}
        <div x-show="filteredVouchers.length === 0" x-cloak
             class="p-8 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs text-center">
            <div class="w-12 h-12 mx-auto rounded-full bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 grid place-items-center text-xl mb-2">
                🎉
            </div>
            <h3 class="text-xs sm:text-sm font-black text-slate-800 dark:text-slate-200">{{ __('messages.payables_voucher_none') }}</h3>
            <p class="text-xs text-slate-400 mt-0.5">{{ __('messages.payables_all_clear') }}</p>
            <div class="mt-2.5 flex items-center justify-center gap-1.5">
                <a href="{{ url('/store/' . $store->slug . '/pos/purchases') }}"
                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 transition shadow-2xs">
                    <span>🛒</span>
                    <span>{{ __('messages.po_list_title') }}</span>
                </a>
            </div>
        </div>

        {{-- Table View for Vouchers --}}
        <div x-show="viewMode === 'table' && filteredVouchers.length > 0"
             class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden transition">
            <div class="overflow-x-auto max-h-[72vh] overflow-y-auto divide-y divide-slate-200 dark:divide-slate-800">
                <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                    <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs border-b-2 border-slate-300 dark:border-slate-600 shadow-2xs select-none">
                        <tr class="text-[11px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider divide-x divide-slate-300 dark:divide-slate-700">
                            <th class="py-2 px-2.5 text-center w-10">#</th>
                            <th class="py-2 px-2.5 min-w-[140px]">{{ __('messages.payables_voucher_number') }}</th>
                            <th class="py-2 px-2.5 min-w-[180px]">{{ __('messages.supplier_col_name') }}</th>
                            <th class="py-2 px-2.5 text-center min-w-[110px]">{{ __('messages.reports_date') }}</th>
                            <th class="py-2 px-2.5 text-right min-w-[120px]">{{ __('messages.po_total_cost') }}</th>
                            <th class="py-2 px-2.5 text-right min-w-[120px]">{{ __('messages.payables_paid') }}</th>
                            <th class="py-2 px-2.5 text-right min-w-[130px]">{{ __('messages.payables_outstanding') }}</th>
                            <th class="py-2 px-2.5 text-center w-24">{{ __('messages.status') }}</th>
                            <th class="py-2 px-2.5 text-center w-24">{{ __('messages.action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900">
                        <template x-for="(v, index) in filteredVouchers" :key="v.id">
                            <tr class="divide-x divide-slate-200/80 dark:divide-slate-800 hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">
                                <td class="py-2 px-2.5 text-center font-mono font-bold text-slate-400" x-text="index + 1"></td>

                                {{-- PO Voucher # --}}
                                <td class="py-2 px-2.5">
                                    <a :href="v.view_url" class="font-mono font-black text-xs text-sky-600 dark:text-sky-400 hover:underline block" x-text="v.po_number"></a>
                                    <span class="text-[10px] text-slate-400 font-mono" x-text="v.items_count + ' items'"></span>
                                </td>

                                {{-- Supplier --}}
                                <td class="py-2 px-2.5">
                                    <a :href="v.supplier_url" class="font-bold text-xs text-slate-900 dark:text-slate-100 hover:text-rose-600 dark:hover:text-rose-400 truncate block" x-text="v.supplier_name"></a>
                                </td>

                                {{-- Received Date & Aging --}}
                                <td class="py-2 px-2.5 text-center">
                                    <span class="block font-mono text-[11px] text-slate-600 dark:text-slate-400 font-bold" x-text="v.date"></span>
                                    <template x-if="v.days_old > 30">
                                        <span class="inline-block mt-0.5 px-1.5 py-0.2 rounded text-[9px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300" x-text="v.days_old + ' days ago'"></span>
                                    </template>
                                </td>

                                {{-- Total Invoice Amount --}}
                                <td class="py-2 px-2.5 text-right font-mono font-bold text-slate-800 dark:text-slate-200"
                                    x-text="fmt(v.total_cost)"></td>

                                {{-- Paid Amount --}}
                                <td class="py-2 px-2.5 text-right font-mono text-emerald-600 dark:text-emerald-400 font-bold"
                                    x-text="fmt(v.paid_amount)"></td>

                                {{-- Remaining Balance (Soft Rose Highlight & Bold) --}}
                                <td class="py-2 px-2.5 text-right font-mono font-black text-rose-600 dark:text-rose-400 bg-rose-50/30 dark:bg-rose-950/20 whitespace-nowrap"
                                    x-text="fmt(v.remaining_balance)"></td>

                                {{-- Payment Status Pill --}}
                                <td class="py-2 px-2.5 text-center whitespace-nowrap">
                                    <template x-if="v.payment_status === 'unpaid'">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                            {{ __('messages.payables_filter_unpaid') }}
                                        </span>
                                    </template>
                                    <template x-if="v.payment_status === 'partial'">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                            {{ __('messages.payables_filter_partial') }}
                                        </span>
                                    </template>
                                </td>

                                {{-- Pay Action Button --}}
                                <td class="py-2 px-2.5 text-center whitespace-nowrap">
                                    <button type="button"
                                            @click.stop="openPoPay(v.id, v.po_number, v.remaining_balance)"
                                            class="h-6 px-2.5 rounded text-[11px] font-bold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 shadow-2xs transition inline-flex items-center gap-1 active:scale-95 cursor-pointer">
                                        <span>💳</span>
                                        <span>{{ __('messages.payables_pay_now') }}</span>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Cards View for Vouchers --}}
        <div x-show="(viewMode === 'cards' || viewMode === 'card') && filteredVouchers.length > 0"
             class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-0.5 sm:gap-1">
            <template x-for="v in filteredVouchers" :key="'card-v-' + v.id">
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg overflow-hidden shadow-2xs hover:border-rose-300 dark:hover:border-rose-600/50 hover:shadow-sm transition flex flex-col justify-between group">
                    <div class="p-2.5 space-y-2">
                        {{-- Card Header: PO # + Status Pill --}}
                        <div class="flex items-start justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-1.5">
                            <div>
                                <a :href="v.view_url" class="font-mono font-black text-sky-600 dark:text-sky-400 text-xs hover:underline block" x-text="v.po_number"></a>
                                <span class="text-[10px] text-slate-400 font-mono block mt-0.5" x-text="v.date"></span>
                            </div>

                            <span class="px-1.5 py-0.5 rounded-full text-[9px] font-bold uppercase"
                                  :class="v.payment_status === 'unpaid' ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800'"
                                  x-text="v.payment_status === 'unpaid' ? 'Unpaid' : 'Partial'"></span>
                        </div>

                        {{-- Supplier --}}
                        <div>
                            <span class="text-[9px] text-slate-400 uppercase font-bold block">{{ __('messages.supplier_col_name') }}</span>
                            <a :href="v.supplier_url" class="font-bold text-xs text-slate-800 dark:text-slate-200 hover:text-rose-600 truncate block mt-0.5" x-text="v.supplier_name"></a>
                        </div>

                        {{-- Financial Box --}}
                        <div class="p-1.5 rounded-md bg-rose-50/60 dark:bg-rose-950/30 border border-rose-100 dark:border-rose-900/50 flex items-center justify-between text-xs">
                            <div>
                                <span class="text-[9px] uppercase font-bold text-rose-500 block">{{ __('messages.payables_outstanding') }}</span>
                                <span class="text-xs sm:text-sm font-black font-mono text-rose-600 dark:text-rose-400" x-text="fmt(v.remaining_balance)"></span>
                            </div>
                            <div class="text-right">
                                <span class="text-[9px] uppercase font-bold text-slate-400 block">{{ __('messages.po_total_cost') }}</span>
                                <span class="text-[10px] font-mono text-slate-700 dark:text-slate-300" x-text="fmt(v.total_cost)"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Card Footer Action --}}
                    <div class="p-1.5 bg-slate-50/80 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end">
                        <button type="button"
                                @click.stop="openPoPay(v.id, v.po_number, v.remaining_balance)"
                                class="w-full text-center px-2 py-1 rounded bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-bold transition flex items-center justify-center gap-1 active:scale-95 shadow-2xs cursor-pointer">
                            <span>💳</span>
                            <span>{{ __('messages.payables_pay_now') }}</span>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- ── TAB 2: BY SUPPLIER (ကုန်သွင်းသူအလိုက် စာရင်းချုပ်) ────────────────────── --}}
    <div x-show="activeTab === 'suppliers'">
        {{-- Empty State --}}
        <div x-show="filteredSuppliers.length === 0" x-cloak
             class="p-8 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs text-center">
            <div class="w-12 h-12 mx-auto rounded-full bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 grid place-items-center text-xl mb-2">
                🎉
            </div>
            <h3 class="text-xs sm:text-sm font-black text-slate-800 dark:text-slate-200">{{ __('messages.payables_none') }}</h3>
            <p class="text-xs text-slate-400 mt-0.5">{{ __('messages.payables_all_clear') }}</p>
        </div>

        {{-- Table View for Suppliers --}}
        <div x-show="viewMode === 'table' && filteredSuppliers.length > 0"
             class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden transition">
            <div class="overflow-x-auto max-h-[72vh] overflow-y-auto divide-y divide-slate-200 dark:divide-slate-800">
                <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                    <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs border-b-2 border-slate-300 dark:border-slate-600 shadow-2xs select-none">
                        <tr class="text-[11px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider divide-x divide-slate-300 dark:divide-slate-700">
                            <th class="py-2 px-2.5 text-center w-10">#</th>
                            <th class="py-2 px-2.5 min-w-[180px]">{{ __('messages.payables_supplier') }}</th>
                            <th class="py-2 px-2.5 min-w-[140px]">{{ __('messages.payables_contact') }}</th>
                            <th class="py-2 px-2.5 text-center min-w-[110px]">{{ __('messages.payables_unpaid_pos') }}</th>
                            <th class="py-2 px-2.5 text-right min-w-[140px]">{{ __('messages.payables_outstanding') }}</th>
                            <th class="py-2 px-2.5 text-center min-w-[120px]">{{ __('messages.payables_oldest') }}</th>
                            <th class="py-2 px-2.5 text-center w-24">{{ __('messages.action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900">
                        <template x-for="(s, index) in filteredSuppliers" :key="s.id">
                            <tr class="divide-x divide-slate-200/80 dark:divide-slate-800 hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">
                                <td class="py-2 px-2.5 text-center font-mono font-bold text-slate-400" x-text="index + 1"></td>

                                {{-- Supplier Name & Avatar --}}
                                <td class="py-2 px-2.5">
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        <span class="shrink-0 w-6 h-6 rounded-full bg-gradient-to-br from-rose-500 to-amber-500 text-white grid place-items-center font-black text-[9px] select-none shadow-2xs"
                                              x-text="s.name.charAt(0).toUpperCase()"></span>
                                        <div class="min-w-0">
                                            <a :href="s.view_url" class="font-bold text-xs text-slate-900 dark:text-slate-100 hover:text-rose-600 dark:hover:text-rose-400 truncate block" x-text="s.name"></a>
                                        </div>
                                    </div>
                                </td>

                                {{-- Contact Person & Phone --}}
                                <td class="py-2 px-2.5">
                                    <span class="block font-medium text-slate-700 dark:text-slate-300 truncate text-xs" x-text="s.contact_person ? s.contact_person : '—'"></span>
                                    <template x-if="s.phone">
                                        <a :href="'tel:' + s.phone" class="text-[10px] font-mono text-sky-600 dark:text-sky-400 hover:underline block truncate" x-text="s.phone"></a>
                                    </template>
                                </td>

                                {{-- Unpaid POs count badge --}}
                                <td class="py-2 px-2.5 text-center">
                                    <a :href="s.view_url" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[11px] font-bold bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 border border-sky-200 dark:border-sky-800 hover:bg-sky-100 transition font-mono">
                                        <span x-text="s.unpaid_count"></span>
                                        <span>POs</span>
                                    </a>
                                </td>

                                {{-- Outstanding Debt (Soft highlighted & Bold Rose Red) --}}
                                <td class="py-2 px-2.5 text-right font-mono font-black text-rose-600 dark:text-rose-400 whitespace-nowrap bg-rose-50/30 dark:bg-rose-950/20"
                                    x-text="fmt(s.balance)"></td>

                                {{-- Oldest Unpaid Date & Aging Tag --}}
                                <td class="py-2 px-2.5 text-center">
                                    <span class="block font-mono text-[11px] text-slate-600 dark:text-slate-400 font-bold" x-text="s.oldest_date"></span>
                                    <template x-if="s.days_old > 30">
                                        <span class="inline-block mt-0.5 px-1.5 py-0.2 rounded text-[9px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300" x-text="s.days_old + ' days ago'"></span>
                                    </template>
                                </td>

                                {{-- Actions --}}
                                <td class="py-2 px-2.5 text-center whitespace-nowrap">
                                    <a :href="s.view_url"
                                       class="h-6 px-2.5 rounded text-[11px] font-bold bg-rose-600 hover:bg-rose-500 text-white transition inline-flex items-center gap-1 active:scale-95 shadow-2xs">
                                        <span>💳</span>
                                        <span>{{ __('messages.payables_pay') }}</span>
                                    </a>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Cards View for Suppliers --}}
        <div x-show="(viewMode === 'cards' || viewMode === 'card') && filteredSuppliers.length > 0"
             class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-0.5 sm:gap-1">
            <template x-for="s in filteredSuppliers" :key="'card-' + s.id">
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg overflow-hidden shadow-2xs hover:border-rose-300 dark:hover:border-rose-600/50 hover:shadow-sm transition flex flex-col justify-between group">
                    <div class="p-2.5 space-y-2">
                        {{-- Card Header: Supplier Avatar + Unpaid POs Pill --}}
                        <div class="flex items-start justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-1.5">
                            <div class="flex items-center gap-1.5 min-w-0">
                                <span class="shrink-0 w-6 h-6 rounded-full bg-gradient-to-br from-rose-500 to-amber-500 text-white grid place-items-center font-black text-[9px] select-none shadow-2xs"
                                      x-text="s.name.charAt(0).toUpperCase()"></span>
                                <div class="min-w-0">
                                    <a :href="s.view_url" class="font-bold text-xs text-slate-900 dark:text-slate-100 hover:text-rose-600 dark:hover:text-rose-400 truncate block" x-text="s.name"></a>
                                    <span class="block text-[10px] text-slate-400 truncate" x-text="s.contact_person ? s.contact_person : 'No Contact'"></span>
                                </div>
                            </div>

                            <span class="shrink-0 px-1.5 py-0.5 rounded-full text-[10px] font-bold font-mono bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 border border-sky-200 dark:border-sky-800"
                                  x-text="s.unpaid_count + ' POs'"></span>
                        </div>

                        {{-- Phone --}}
                        <template x-if="s.phone">
                            <div class="text-[11px] font-mono text-slate-500 dark:text-slate-400 flex items-center gap-1 truncate">
                                <span>📞</span>
                                <a :href="'tel:' + s.phone" class="hover:text-sky-600 dark:hover:text-sky-400 hover:underline truncate" x-text="s.phone"></a>
                            </div>
                        </template>

                        {{-- Debt Box & Oldest PO Date --}}
                        <div class="p-1.5 rounded-md bg-rose-50/60 dark:bg-rose-950/30 border border-rose-100 dark:border-rose-900/50 flex items-center justify-between text-xs">
                            <div>
                                <span class="text-[9px] uppercase font-bold text-rose-500 block">{{ __('messages.payables_outstanding') }}</span>
                                <span class="text-xs sm:text-sm font-black font-mono text-rose-600 dark:text-rose-400" x-text="fmt(s.balance)"></span>
                            </div>
                            <div class="text-right">
                                <span class="text-[9px] uppercase font-bold text-slate-400 block">{{ __('messages.payables_oldest') }}</span>
                                <span class="text-[10px] font-mono font-bold text-slate-700 dark:text-slate-300" x-text="s.oldest_date"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Card Footer Action --}}
                    <div class="p-1.5 bg-slate-50/80 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end">
                        <a :href="s.view_url"
                           class="w-full text-center px-2 py-1 rounded bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold transition flex items-center justify-center gap-1 active:scale-95 shadow-2xs">
                            <span>💳</span>
                            <span>{{ __('messages.payables_view_details') }}</span>
                        </a>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Specific PO Pay Modal Dialog --}}
    <div x-show="payModalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-3 sm:p-4 bg-black/60 backdrop-blur-xs"
         role="dialog" aria-modal="true">
        <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 sm:p-5 shadow-2xl"
             @click.outside="payModalOpen = false">

            {{-- Modal Header --}}
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5 mb-3">
                <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-md bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 grid place-items-center text-xs font-black">💳</span>
                    <div>
                        <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100">{{ __('messages.payables_pay_specific') }}</h3>
                        <p class="text-[10px] font-mono text-slate-400">PO: <span class="font-bold text-sky-600 dark:text-sky-400" x-text="payPoNumber"></span></p>
                    </div>
                </div>
                <button type="button" @click="payModalOpen = false" class="w-6 h-6 rounded-md grid place-items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition text-xs font-black cursor-pointer">&times;</button>
            </div>

            {{-- multipart/form-data for file uploads --}}
            <form :action="`/store/{{ $store->slug }}/pos/purchases/${payPoId}/pay`"
                  method="POST"
                  enctype="multipart/form-data"
                  class="space-y-3">
                @csrf

                {{-- Amount --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.payables_amount') }}
                    </label>
                    <input type="number" name="amount" step="any" min="1"
                           :max="payPoBalance"
                           x-model="payAmount"
                           required
                           class="w-full h-8 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-3 text-xs font-mono font-bold text-slate-900 dark:text-slate-100 outline-none focus:ring-1 focus:ring-emerald-500">
                </div>

                {{-- Reference --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.receiving_reference') }}
                    </label>
                    <input type="text" name="reference" maxlength="120"
                           x-model="payReference"
                           placeholder="e.g. KPay, Wave, Bank Transfer #1234"
                           class="w-full h-8 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-3 text-xs text-slate-900 dark:text-slate-100 outline-none focus:ring-1 focus:ring-emerald-500">
                </div>

                {{-- Slip Images Upload (up to 4) --}}
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="text-[11px] font-bold text-slate-700 dark:text-slate-300">
                            {{ __('messages.payables_slip_images') }}
                            <span class="text-slate-400 font-normal ml-1">({{ __('messages.payables_slip_optional') }}, max 4)</span>
                        </label>
                        <span class="text-[10px] font-mono text-slate-400" x-text="slipPreviews.length + '/4'"></span>
                    </div>

                    {{-- Preview Grid --}}
                    <div class="grid grid-cols-4 gap-1.5 mb-2" x-show="slipPreviews.length > 0">
                        <template x-for="(slip, idx) in slipPreviews" :key="idx">
                            <div class="relative group rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 aspect-square">
                                <img :src="slip.url" :alt="slip.name" class="w-full h-full object-cover">
                                <button type="button"
                                        @click.stop="removeSlip(idx)"
                                        class="absolute top-0.5 right-0.5 w-5 h-5 rounded-full bg-rose-600 text-white grid place-items-center text-[10px] font-black opacity-0 group-hover:opacity-100 transition shadow cursor-pointer z-10">
                                    ✕
                                </button>
                            </div>
                        </template>
                    </div>

                    {{-- Upload Button (visible when < 4 images) --}}
                    <label x-show="slipPreviews.length < 4"
                           class="flex flex-col items-center justify-center gap-1 w-full py-3.5 border-2 border-dashed border-slate-300 dark:border-slate-600 hover:border-emerald-400 dark:hover:border-emerald-600 rounded-lg cursor-pointer bg-slate-50 dark:bg-slate-800/50 hover:bg-emerald-50/30 dark:hover:bg-emerald-950/20 transition-colors group">
                        <span class="text-xl leading-none">📸</span>
                        <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 group-hover:text-emerald-600 dark:group-hover:text-emerald-400">
                            {{ __('messages.payables_slip_click') }}
                        </span>
                        <span class="text-[9px] text-slate-400">JPG, PNG, WEBP &middot; max 5MB each</span>
                        <input id="slip_images_input"
                               type="file"
                               name="slip_images[]"
                               accept="image/jpeg,image/jpg,image/png,image/webp"
                               multiple
                               class="sr-only"
                               @change="addSlipImages($event)">
                    </label>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="payModalOpen = false"
                            class="h-8 px-3 rounded-lg text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition cursor-pointer">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit"
                            class="h-8 px-4 rounded-lg text-xs font-black text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 shadow-2xs transition cursor-pointer inline-flex items-center gap-1 active:scale-95">
                        <span>💳</span>
                        <span>{{ __('messages.payables_pay') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection