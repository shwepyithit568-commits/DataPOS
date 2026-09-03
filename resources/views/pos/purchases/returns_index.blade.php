@extends('layouts.admin.app')

@section('title', __('messages.po_returns_title') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
@php
    $fmtQty = static function ($qty): string {
        $f = (float) $qty;
        if ($f == (int) $f) {
            return (string) (int) $f;
        }
        return rtrim(rtrim(number_format($f, 3, '.', ''), '0'), '.');
    };
@endphp

<div class="w-full space-y-0.5 pb-6"
     x-data="{
        viewMode: localStorage.getItem('pos_returns_view_mode') || 'table',
        setView(mode) {
            this.viewMode = mode;
            localStorage.setItem('pos_returns_view_mode', mode);
        },
        // Modal State for New Purchase Return
        modalOpen: false,
        receivedOrders: @js($receivedOrders ?? []),
        poSearch: '',
        selectedPo: null,
        returnReason: '',
        isSubmitting: false,

        get filteredOrders() {
            const q = this.poSearch.toLowerCase().trim();
            if (!q) return this.receivedOrders;
            return this.receivedOrders.filter(po => 
                (po.po_number && po.po_number.toLowerCase().includes(q)) ||
                (po.supplier_name && po.supplier_name.toLowerCase().includes(q))
            );
        },

        openNewReturn() {
            this.modalOpen = true;
            this.poSearch = '';
            this.selectedPo = null;
            this.returnReason = '';
            this.isSubmitting = false;
        },

        selectPo(po) {
            this.selectedPo = {
                ...po,
                items: po.items.map(i => ({ ...i, return_qty: '0' }))
            };
        },

        retMax(item) {
            item.return_qty = String(item.returnable_qty);
        },

        retZero(item) {
            item.return_qty = '0';
        },

        retMaxAll() {
            if (!this.selectedPo) return;
            this.selectedPo.items.forEach(i => i.return_qty = String(i.returnable_qty));
        },

        retZeroAll() {
            if (!this.selectedPo) return;
            this.selectedPo.items.forEach(i => i.return_qty = '0');
        },

        get activeReturnItems() {
            if (!this.selectedPo) return [];
            return this.selectedPo.items.filter(i => (parseFloat(i.return_qty) || 0) > 0);
        },

        get totalReturnQty() {
            return this.activeReturnItems.reduce((s, i) => s + (parseFloat(i.return_qty) || 0), 0);
        },

        get totalReturnValue() {
            return this.activeReturnItems.reduce((s, i) => s + (parseFloat(i.return_qty) || 0) * (parseFloat(i.unit_cost) || 0), 0);
        },

        get canSubmit() {
            return this.activeReturnItems.length > 0 && !this.isSubmitting;
        },

        fmt(n) { return typeof window.formatCurrency === 'function' ? window.formatCurrency(n) : Number(n).toLocaleString(); },
        fmtQty(n) { return typeof window.formatQuantity === 'function' ? window.formatQuantity(n) : String(n); },

        submitReturn() {
            if (!this.canSubmit) return;
            this.isSubmitting = true;
            this.$refs.returnForm.submit();
        }
     }">

    {{-- 1. Compact Header Banner (34px - 38px) --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-8 h-8 rounded-lg bg-orange-50 dark:bg-orange-950/50 text-orange-600 dark:text-orange-400 grid place-items-center text-base font-bold shadow-xs flex-shrink-0">
                ↩️
            </span>
            <div class="min-w-0">
                <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5 truncate">
                    <span>{{ __('messages.po_returns_title') }}</span>
                    <span class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 hidden sm:inline">({{ $store->name }})</span>
                </h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                    {{ __('messages.po_returns_subtitle') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-1.5 self-start sm:self-auto shrink-0 flex-wrap">
            {{-- Primary Action: New Return Button --}}
            <button type="button" @click.stop="openNewReturn()"
                    class="h-7 px-3 rounded-md bg-orange-600 hover:bg-orange-500 text-white text-xs font-black shadow-2xs hover:shadow-orange-500/20 transition inline-flex items-center gap-1.5 active:scale-95 cursor-pointer">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M12 4v16m8-8H4"/>
                </svg>
                <span>{{ __('messages.po_return_new_btn') }}</span>
            </button>

            <a href="{{ url('/store/' . $store->slug . '/pos/purchases') }}"
               class="h-7 px-2.5 rounded-md bg-sky-50 dark:bg-sky-950/50 text-sky-700 dark:text-sky-300 hover:bg-sky-100 dark:hover:bg-sky-900/60 border border-sky-200 dark:border-sky-800 text-xs font-bold transition inline-flex items-center gap-1.5 active:scale-95 cursor-pointer">
                <span>🛒</span>
                <span class="hidden sm:inline">{{ __('messages.po_list_title') }}</span>
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
        {{-- Card 1: Total Returns Records --}}
        <div role="listitem" class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-orange-100 text-orange-600 dark:bg-orange-950/70 dark:text-orange-300 shadow-inner text-xs sm:text-sm font-bold">
                ↩️
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-outfit">
                    {{ number_format($summary['total_count'] ?? $totalCount) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.po_returns_total_count') }}
                </p>
            </div>
        </div>

        {{-- Card 2: Total Items Returned --}}
        <div role="listitem" class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-sky-100 text-sky-600 dark:bg-sky-950/70 dark:text-sky-300 shadow-inner text-xs sm:text-sm font-bold">
                📦
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-outfit">
                    {{ format_quantity($summary['total_qty'] ?? 0, $store) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.po_returns_total_qty') }}
                </p>
            </div>
        </div>

        {{-- Card 3: Total Return Value --}}
        <div role="listitem" class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-amber-100 text-amber-600 dark:bg-amber-950/70 dark:text-amber-300 shadow-inner text-xs sm:text-sm font-bold">
                💰
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-amber-600 dark:text-amber-400 leading-none tabular-nums font-outfit truncate">
                    {{ format_currency($summary['total_cost'] ?? 0, $store) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.po_returns_total_val') }}
                </p>
            </div>
        </div>

        {{-- Card 4: Suppliers Involved --}}
        <div role="listitem" class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-emerald-100 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-300 shadow-inner text-xs sm:text-sm font-bold">
                🏭
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-outfit">
                    {{ number_format($summary['suppliers_count'] ?? 0) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.po_returns_suppliers_count') }}
                </p>
            </div>
        </div>
    </div>

    {{-- 3. Interactive Toolbar Standard (Search, Sort, Excel Export, View Switcher) --}}
    <div class="bg-white dark:bg-slate-900 px-2.5 py-1 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col md:flex-row md:items-center md:justify-between gap-1">
        {{-- Left: Search Bar & Sort Dropdown --}}
        <form method="GET" action="{{ url('/store/' . $store->slug . '/pos/purchases/returns') }}" class="flex flex-wrap items-center gap-1.5 flex-1 min-w-0">
            <div class="relative min-w-[180px] sm:min-w-[260px] flex-1 max-w-sm">
                <input type="text"
                       name="search"
                       value="{{ $search ?? request('search') }}"
                       placeholder="{{ __('messages.po_return_search') }}"
                       class="w-full h-7 pl-8 pr-2.5 rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-semibold text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-sky-500 focus:bg-white dark:focus:bg-slate-900 transition" />
                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
            </div>

            <select name="sort" onchange="this.form.submit()"
                    class="h-7 rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-2 text-xs font-bold text-slate-700 dark:text-slate-300 outline-none focus:ring-1 focus:ring-sky-500">
                <option value="newest" {{ ($sort ?? request('sort')) === 'newest' ? 'selected' : '' }}>{{ __('messages.po_sort_newest') }}</option>
                <option value="oldest" {{ ($sort ?? request('sort')) === 'oldest' ? 'selected' : '' }}>{{ __('messages.po_sort_oldest') }}</option>
                <option value="highest" {{ ($sort ?? request('sort')) === 'highest' ? 'selected' : '' }}>{{ __('messages.po_sort_highest') }}</option>
                <option value="lowest" {{ ($sort ?? request('sort')) === 'lowest' ? 'selected' : '' }}>{{ __('messages.po_sort_lowest') }}</option>
            </select>

            @if(!empty($search))
                <a href="{{ url('/store/' . $store->slug . '/pos/purchases/returns') }}"
                   class="h-7 px-2 rounded-md bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-600 dark:text-slate-400 text-xs font-bold inline-flex items-center gap-1 transition">
                    ✕ {{ __('messages.clear') }}
                </a>
            @endif
        </form>

        {{-- Right: Excel Export & View Mode Switcher --}}
        <div class="flex items-center gap-1 shrink-0 self-end md:self-auto">
            <a href="{{ url('/store/' . $store->slug . '/pos/purchases/returns/export' . (request()->getQueryString() ? '?' . request()->getQueryString() : '')) }}"
               class="h-7 px-2.5 rounded-md border border-emerald-300 dark:border-emerald-800/80 bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 text-xs font-black shadow-2xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>
                </svg>
                <span>{{ __('messages.po_returns_export_excel') }}</span>
            </a>

            <div class="flex items-center gap-0.5 bg-slate-100 dark:bg-slate-800 p-0.5 rounded-md border border-slate-200 dark:border-slate-700">
                <button type="button" @click="setView('table')"
                        class="h-6 px-2 rounded text-[11px] font-bold transition flex items-center gap-1 cursor-pointer"
                        :class="viewMode === 'table' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-400 shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'">
                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                    <span>Table</span>
                </button>
                <button type="button" @click="setView('cards')"
                        class="h-6 px-2 rounded text-[11px] font-bold transition flex items-center gap-1 cursor-pointer"
                        :class="viewMode === 'cards' || viewMode === 'card' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-400 shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'">
                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    <span>Cards</span>
                </button>
            </div>
        </div>
    </div>

    {{-- 4. Google Sheets Style Spreadsheet Table View --}}
    <div x-show="viewMode === 'table'" class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden transition">
        <div class="overflow-x-auto max-h-[72vh] overflow-y-auto divide-y divide-slate-200 dark:divide-slate-800">
            <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs border-b-2 border-slate-300 dark:border-slate-600 shadow-2xs select-none">
                    <tr class="text-[11px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider divide-x divide-slate-300 dark:divide-slate-700">
                        <th class="py-2 px-2.5 min-w-[140px]">{{ __('messages.po_return_col_number') }}</th>
                        <th class="py-2 px-2.5 min-w-[140px]">{{ __('messages.po_col_po_number') }}</th>
                        <th class="py-2 px-2.5 min-w-[170px]">{{ __('messages.supplier_col_name') }}</th>
                        <th class="py-2 px-2.5 text-right w-24">{{ __('messages.reports_qty') }}</th>
                        <th class="py-2 px-2.5 text-right min-w-[130px]">{{ __('messages.reports_value') }}</th>
                        <th class="py-2 px-2.5 min-w-[150px]">{{ __('messages.po_return_col_reason') }}</th>
                        <th class="py-2 px-2.5 text-center w-24">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900">
                    @forelse ($returns as $return)
                        <tr class="divide-x divide-slate-200/80 dark:divide-slate-800 hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">
                            {{-- Return Number & Date --}}
                            <td class="py-2 px-2.5">
                                <span class="font-mono font-black text-orange-600 dark:text-orange-400 block text-xs">
                                    {{ $return->return_number }}
                                </span>
                                <span class="text-[10px] text-slate-400 block mt-0.5 font-mono">
                                    {{ $return->returned_at?->format('d M Y, H:i') ?? '—' }}
                                </span>
                            </td>

                            {{-- Linked Purchase Order --}}
                            <td class="py-2 px-2.5">
                                @if ($return->purchaseOrder)
                                    <a href="{{ url('/store/' . $store->slug . '/pos/purchases/' . $return->purchaseOrder->id) }}"
                                       class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800 font-mono font-bold text-xs hover:underline">
                                        <span>🛒</span>
                                        <span>{{ $return->purchaseOrder->po_number }}</span>
                                    </a>
                                @else
                                    <span class="text-slate-400 font-mono">—</span>
                                @endif
                            </td>

                            {{-- Supplier Name --}}
                            <td class="py-2 px-2.5">
                                @if ($return->supplier)
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        <span class="shrink-0 w-6 h-6 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 text-white grid place-items-center font-black text-[9px] select-none shadow-2xs">
                                            {{ mb_strtoupper(mb_substr(trim($return->supplier->name), 0, 1)) }}
                                        </span>
                                        <span class="font-bold text-slate-900 dark:text-slate-100 truncate text-xs" title="{{ $return->supplier->name }}">
                                            {{ $return->supplier->name }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-slate-400 font-mono">—</span>
                                @endif
                            </td>

                            {{-- Returned Quantity (Soft highlighted & Clean $fmtQty) --}}
                            <td class="py-2 px-2.5 text-right font-mono font-black text-slate-900 dark:text-slate-100 bg-slate-50/40 dark:bg-slate-800/30">
                                {{ format_quantity($return->total_quantity, $store) }}
                            </td>

                            {{-- Returned Value (Bold Amber) --}}
                            <td class="py-2 px-2.5 text-right font-mono font-black text-amber-600 dark:text-amber-400 whitespace-nowrap">
                                {{ format_currency($return->total_cost, $store) }}
                            </td>

                            {{-- Reason --}}
                            <td class="py-2 px-2.5">
                                <span class="text-xs text-slate-600 dark:text-slate-300 block truncate max-w-xs" title="{{ $return->reason }}">
                                    {{ $return->reason ?: '—' }}
                                </span>
                            </td>

                            {{-- Actions --}}
                            <td class="py-2 px-2.5 text-center whitespace-nowrap">
                                @if ($return->purchaseOrder)
                                    <a href="{{ url('/store/' . $store->slug . '/pos/purchases/' . $return->purchaseOrder->id) }}"
                                       class="h-6 px-2 rounded text-[11px] font-bold bg-sky-50 dark:bg-sky-950/60 hover:bg-sky-100 dark:hover:bg-sky-900/60 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800 transition inline-flex items-center gap-1 active:scale-95">
                                        <span>{{ __('messages.po_return_view_po') }}</span>
                                        <span>→</span>
                                    </a>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-400">
                                <div class="text-3xl mb-2 opacity-55">🔄</div>
                                <div class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.po_return_none') }}</div>
                                <div class="flex items-center justify-center gap-2 mt-2">
                                    <button type="button" @click="openNewReturn()"
                                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-bold text-white bg-orange-600 hover:bg-orange-500 transition shadow-sm">
                                        <span>↩️</span>
                                        <span>{{ __('messages.po_return_new_btn') }}</span>
                                    </button>
                                    <a href="{{ url('/store/' . $store->slug . '/pos/purchases') }}"
                                       class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 transition">
                                        <span>🛒</span>
                                        <span>{{ __('messages.po_list_title') }}</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($returns->hasPages())
            <div class="p-2 border-t border-slate-100 dark:border-slate-800 text-xs">{{ $returns->links() }}</div>
        @endif
    </div>

    {{-- 5. Responsive Multi-Column Card Grid View (gap-0.5 sm:gap-1) --}}
    <div x-show="viewMode === 'cards' || viewMode === 'card'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-0.5 sm:gap-1">
        @forelse ($returns as $return)
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg overflow-hidden shadow-2xs hover:border-orange-300 dark:hover:border-orange-600/50 hover:shadow-sm transition flex flex-col justify-between group">
                <div class="p-2.5 space-y-2">
                    {{-- Card Header: Return # + PO Badge --}}
                    <div class="flex items-start justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-1.5">
                        <div>
                            <span class="font-mono font-black text-orange-600 dark:text-orange-400 text-xs block">
                                {{ $return->return_number }}
                            </span>
                            <span class="text-[10px] text-slate-400 font-mono block mt-0.5">
                                {{ $return->returned_at?->format('d M Y, H:i') ?? '—' }}
                            </span>
                        </div>
                        @if ($return->purchaseOrder)
                            <a href="{{ url('/store/' . $store->slug . '/pos/purchases/' . $return->purchaseOrder->id) }}"
                               class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800 font-mono font-bold text-[10px] hover:underline">
                                <span>PO:</span>
                                <span>{{ $return->purchaseOrder->po_number }}</span>
                            </a>
                        @endif
                    </div>

                    {{-- Supplier Information --}}
                    <div>
                        <span class="text-[9px] text-slate-400 uppercase font-bold block">{{ __('messages.supplier_col_name') }}</span>
                        @if ($return->supplier)
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span class="shrink-0 w-5 h-5 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 text-white grid place-items-center font-black text-[9px] select-none shadow-2xs">
                                    {{ mb_strtoupper(mb_substr(trim($return->supplier->name), 0, 1)) }}
                                </span>
                                <span class="font-bold text-xs text-slate-800 dark:text-slate-200 truncate" title="{{ $return->supplier->name }}">
                                    {{ $return->supplier->name }}
                                </span>
                            </div>
                        @else
                            <span class="text-xs text-slate-400 font-mono">—</span>
                        @endif
                    </div>

                    {{-- Financial Stats Box --}}
                    <div class="bg-slate-50 dark:bg-slate-800/60 p-1.5 rounded-md border border-slate-100 dark:border-slate-800 grid grid-cols-2 gap-1.5 text-xs">
                        <div>
                            <span class="text-[9px] text-slate-400 block uppercase font-bold">{{ __('messages.reports_qty') }}</span>
                            <span class="font-mono font-black text-slate-800 dark:text-slate-200">{{ format_quantity($return->total_quantity, $store) }}</span>
                        </div>
                        <div class="text-right">
                            <span class="text-[9px] text-slate-400 block uppercase font-bold">{{ __('messages.reports_value') }}</span>
                            <span class="font-mono font-black text-amber-600 dark:text-amber-400">{{ format_currency($return->total_cost, $store) }}</span>
                        </div>
                    </div>

                    @if ($return->reason)
                        <p class="text-[10px] text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-800/40 p-1 rounded border border-slate-100 dark:border-slate-800 italic line-clamp-2">
                            "{{ $return->reason }}"
                        </p>
                    @endif
                </div>

                {{-- Card Footer Action --}}
                <div class="p-1.5 bg-slate-50/80 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end">
                    @if ($return->purchaseOrder)
                        <a href="{{ url('/store/' . $store->slug . '/pos/purchases/' . $return->purchaseOrder->id) }}"
                           class="w-full text-center px-2 py-1 rounded bg-sky-50 hover:bg-sky-100 dark:bg-sky-950/60 dark:hover:bg-sky-900/60 text-sky-600 dark:text-sky-300 text-xs font-bold transition flex items-center justify-center gap-1">
                            <span>{{ __('messages.po_return_view_po') }}</span>
                            <span>→</span>
                        </a>
                    @else
                        <span class="text-xs text-slate-400">—</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white dark:bg-slate-900 border border-dashed border-slate-200 dark:border-slate-800 p-8 rounded-lg text-center text-slate-400 shadow-2xs">
                <div class="text-3xl mb-2 opacity-55">🔄</div>
                <div class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.po_return_none') }}</div>
                <div class="flex items-center justify-center gap-2 mt-2">
                    <button type="button" @click.stop="openNewReturn()"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-bold text-white bg-orange-600 hover:bg-orange-500 transition shadow-sm cursor-pointer">
                        <span>↩️</span>
                        <span>{{ __('messages.po_return_new_btn') }}</span>
                    </button>
                    <a href="{{ url('/store/' . $store->slug . '/pos/purchases') }}"
                       class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 transition">
                        <span>🛒</span>
                        <span>{{ __('messages.po_list_title') }}</span>
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Pagination for Card view --}}
    @if ($returns->hasPages())
        <div x-show="viewMode === 'cards' || viewMode === 'card'" class="p-2 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg text-xs">
            {{ $returns->links() }}
        </div>
    @endif

    {{-- ── 6. New Purchase Return Modal Dialog ─────────────────────────── --}}
    <div x-show="modalOpen" x-cloak
         @click.self="modalOpen = false"
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-2.5 sm:p-4 bg-black/60 backdrop-blur-xs"
         role="dialog" aria-modal="true" @keydown.escape.window="modalOpen = false">
         
        <div class="relative w-full max-w-2xl bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col max-h-[90vh]">

            {{-- Modal Header --}}
            <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/80 dark:bg-slate-800/50 shrink-0">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="w-7 h-7 rounded-lg bg-orange-100 dark:bg-orange-950/60 text-orange-600 dark:text-orange-400 grid place-items-center text-sm font-bold shrink-0">
                        ↩️
                    </span>
                    <div>
                        <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white truncate">
                            <span x-show="!selectedPo">{{ __('messages.po_return_select_po') }}</span>
                            <span x-show="selectedPo" x-text="'{{ __('messages.po_return_title') }} · ' + (selectedPo ? selectedPo.po_number : '')"></span>
                        </h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate" x-show="selectedPo" x-text="selectedPo ? (selectedPo.supplier_name + ' (' + selectedPo.received_at + ')') : ''"></p>
                    </div>
                </div>

                <div class="flex items-center gap-1.5">
                    <button type="button" x-show="selectedPo" @click="selectedPo = null"
                            class="h-6 px-2 rounded text-[11px] font-bold text-sky-600 dark:text-sky-400 hover:bg-sky-50 dark:hover:bg-sky-950/40 transition inline-flex items-center gap-1 cursor-pointer">
                        <span>←</span>
                        <span>{{ __('messages.po_return_change_po') }}</span>
                    </button>
                    <button type="button" @click="modalOpen = false"
                            class="w-6 h-6 rounded-md grid place-items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition text-xs font-black cursor-pointer">
                        ✕
                    </button>
                </div>
            </div>

            {{-- Step 1: Select Received PO --}}
            <div x-show="!selectedPo" class="p-3 sm:p-4 space-y-3 overflow-y-auto flex-1">
                {{-- Search Input --}}
                <div class="relative">
                    <input type="text" x-model="poSearch"
                           placeholder="{{ __('messages.po_return_select_po_search') }}"
                           class="w-full h-8 pl-8 pr-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-semibold text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-orange-500 focus:bg-white dark:focus:bg-slate-900 transition" />
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                </div>

                {{-- POs List --}}
                <div class="space-y-1.5 max-h-[55vh] overflow-y-auto pr-1">
                    <template x-for="po in filteredOrders" :key="po.id">
                        <div @click="selectPo(po)"
                             class="p-2.5 rounded-xl border border-slate-200/90 dark:border-slate-800 bg-white dark:bg-slate-900 hover:border-orange-400 dark:hover:border-orange-500 hover:shadow-xs cursor-pointer transition flex items-center justify-between gap-3 group">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-black text-sky-600 dark:text-sky-400 text-xs sm:text-sm" x-text="po.po_number"></span>
                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 font-bold">Received</span>
                                </div>
                                <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate mt-0.5" x-text="po.supplier_name"></p>
                                <p class="text-[10px] text-slate-400 font-mono" x-text="po.received_at + ' · ' + po.items_count + ' {{ __('messages.reports_items') }}'"></p>
                            </div>

                            <div class="text-right shrink-0">
                                <span class="block font-mono font-black text-slate-900 dark:text-slate-100 text-xs sm:text-sm" x-text="fmt(po.total_cost)"></span>
                                <span class="mt-1 inline-flex items-center gap-1 text-[11px] font-bold text-orange-600 dark:text-orange-400 group-hover:underline">
                                    <span>{{ __('messages.po_return_new_btn') }}</span>
                                    <span>→</span>
                                </span>
                            </div>
                        </div>
                    </template>

                    <div x-show="filteredOrders.length === 0" class="py-8 text-center text-slate-400">
                        <div class="text-2xl mb-1 opacity-50">🔍</div>
                        <p class="text-xs font-bold">{{ __('messages.po_return_no_returnable_pos') }}</p>
                    </div>
                </div>
            </div>

            {{-- Step 2: Return Items Steppers & Reason Form --}}
            <template x-if="selectedPo">
                <form x-ref="returnForm" method="POST" :action="`{{ url('/store/' . $store->slug . '/pos/purchases') }}/${selectedPo.id}/return`"
                      @submit.prevent="submitReturn()" class="flex flex-col flex-1 overflow-hidden">
                    @csrf

                    {{-- Scrollable Items List --}}
                    <div class="p-3 sm:p-4 space-y-2 overflow-y-auto flex-1 max-h-[52vh]">
                        {{-- Bulk Actions Bar --}}
                        <div class="flex items-center justify-between gap-2 pb-1 border-b border-slate-100 dark:border-slate-800">
                            <span class="text-xs font-bold text-slate-500 dark:text-slate-400"
                                  x-text="activeReturnItems.length > 0 ? (activeReturnItems.length + ' {{ __('messages.reports_items') }} selected') : '{{ __('messages.po_return_qty') }}'"></span>
                            <div class="flex items-center gap-1.5">
                                <button type="button" @click="retMaxAll()"
                                        class="rounded-md px-2 py-0.5 text-[11px] font-black bg-orange-100 dark:bg-orange-950/60 text-orange-700 dark:text-orange-400 hover:bg-orange-200 dark:hover:bg-orange-900/60 transition cursor-pointer">
                                    MAX ALL
                                </button>
                                <button type="button" @click="retZeroAll()"
                                        class="rounded-md px-2 py-0.5 text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 transition cursor-pointer">
                                    ✕ 0
                                </button>
                            </div>
                        </div>

                        <template x-for="(item, idx) in selectedPo.items" :key="item.id">
                            <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 p-2.5 transition"
                                 :class="(parseFloat(item.return_qty) || 0) > 0 ? 'ring-1.5 ring-orange-500 dark:ring-orange-400 bg-orange-50/20 dark:bg-orange-950/20' : ''">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0 flex-1">
                                        <p class="font-bold text-xs sm:text-sm text-slate-900 dark:text-slate-100 truncate" x-text="item.name"></p>
                                        <p class="text-[11px] text-slate-400 mt-0.5">
                                            <span>{{ __('messages.po_returnable_qty') }}:</span>
                                            <span class="font-bold text-slate-700 dark:text-slate-300 font-mono" x-text="fmtQty(item.returnable_qty)"></span>
                                            <span>· </span>
                                            <span class="font-mono" x-text="fmt(item.unit_cost)"></span>
                                        </p>
                                    </div>

                                    <span class="text-xs font-mono font-black text-orange-600 dark:text-orange-400 shrink-0"
                                          x-show="(parseFloat(item.return_qty) || 0) > 0"
                                          x-text="fmt((parseFloat(item.return_qty) || 0) * (parseFloat(item.unit_cost) || 0))"></span>
                                </div>

                                <div class="flex items-center gap-2 mt-2">
                                    <div class="flex items-center rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 overflow-hidden h-7 focus-within:ring-1 focus-within:ring-orange-500 transition">
                                        <button type="button" @click="retZero(item)" tabindex="-1"
                                                class="w-7 h-7 grid place-items-center text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition text-[11px] font-black cursor-pointer">0</button>
                                        <input type="number" step="0.001" min="0" :max="item.returnable_qty" x-model="item.return_qty" inputmode="decimal"
                                               class="w-16 text-center text-xs font-black bg-transparent outline-none tabular-nums text-slate-900 dark:text-slate-100"/>
                                        <button type="button" @click="retMax(item)" tabindex="-1"
                                                class="w-8 h-7 grid place-items-center text-orange-600 dark:text-orange-400 text-[10px] font-black hover:bg-orange-50 dark:hover:bg-orange-950/40 transition cursor-pointer">MAX</button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Hidden dynamic inputs for ONLY active return items (to strictly pass server validation) --}}
                    <template x-for="(item, idx) in activeReturnItems" :key="'active-' + item.id">
                        <div>
                            <input type="hidden" :name="`items[${idx}][product_id]`" :value="item.product_id">
                            <input type="hidden" :name="`items[${idx}][product_variant_id]`" :value="item.product_variant_id || ''">
                            <input type="hidden" :name="`items[${idx}][quantity]`" :value="item.return_qty">
                        </div>
                    </template>

                    {{-- Footer Summary & Submission --}}
                    <div class="p-3 sm:p-4 border-t border-slate-100 dark:border-slate-800 space-y-2.5 bg-white dark:bg-slate-900 shrink-0">
                        {{-- Return Total Banner --}}
                        <div class="flex items-center justify-between rounded-lg bg-orange-50 dark:bg-orange-950/40 border border-orange-200 dark:border-orange-900/50 px-3 py-1.5"
                             x-show="activeReturnItems.length > 0">
                            <span class="text-xs font-bold text-orange-700 dark:text-orange-400">
                                {{ __('messages.receiving_total') }}: <span x-text="activeReturnItems.length"></span> {{ __('messages.reports_items') }} (<span x-text="fmtQty(totalReturnQty)"></span> units)
                            </span>
                            <span class="font-mono font-black text-orange-700 dark:text-orange-400 text-xs sm:text-sm" x-text="fmt(totalReturnValue)"></span>
                        </div>

                        {{-- Reason input --}}
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">{{ __('messages.po_return_reason') }}</label>
                            <input type="text" name="reason" x-model="returnReason" maxlength="500"
                                   placeholder="{{ __('messages.po_return_reason_placeholder') }}"
                                   class="w-full h-8 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-2.5 text-xs text-slate-900 dark:text-slate-100 focus:ring-1 focus:ring-orange-500 outline-none transition" />
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-2 pt-1">
                            <button type="button" @click="selectedPo = null"
                                    class="rounded-lg px-3 h-8 text-xs font-bold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition cursor-pointer">
                                {{ __('messages.cancel') }}
                            </button>
                            <button type="submit" :disabled="!canSubmit"
                                    :class="canSubmit ? 'bg-orange-600 hover:bg-orange-500 text-white cursor-pointer active:scale-95' : 'bg-slate-200 dark:bg-slate-800 text-slate-400 cursor-not-allowed'"
                                    class="flex-1 rounded-lg px-4 h-8 text-xs font-black shadow-2xs transition inline-flex items-center justify-center gap-1.5">
                                <svg x-show="isSubmitting" x-cloak class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                                <span x-text="isSubmitting ? '…' : '🔄 {{ __('messages.po_return_confirm') }}'"></span>
                            </button>
                        </div>
                    </div>
                </form>
            </template>
        </div>
    </div>

</div>
@endsection
