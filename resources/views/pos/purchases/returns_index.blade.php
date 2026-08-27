@extends('layouts.pos.app')

@section('title', __('messages.sidebar_returns') . ' - ' . $store->name)

@section('content')
<div class="w-full space-y-2 sm:space-y-2.5"
     x-data="{
         search: '{{ request('search', '') }}',
         viewMode: localStorage.getItem('pos_returns_view_mode') || 'table',
         matches(ret) {
             const q = this.search.toLowerCase().trim();
             if (!q) return true;
             return (ret.return_number && ret.return_number.toLowerCase().includes(q)) ||
                    (ret.po_number && ret.po_number.toLowerCase().includes(q)) ||
                    (ret.supplier_name && ret.supplier_name.toLowerCase().includes(q)) ||
                    (ret.reason && ret.reason.toLowerCase().includes(q));
         }
     }"
     @view-changed.window="viewMode = $event.detail; localStorage.setItem('pos_returns_view_mode', $event.detail)">

    {{-- 1. Top Header Banner --}}
    <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-8 h-8 rounded-lg bg-orange-50 dark:bg-orange-950/60 text-orange-600 dark:text-orange-400 border border-orange-200 dark:border-orange-800 grid place-items-center text-sm font-black shrink-0">
                ↩️
            </span>
            <div class="min-w-0">
                <h1 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 truncate">
                    {{ __('messages.sidebar_returns') }}
                </h1>
                <p class="text-[11px] text-slate-400 font-mono truncate">
                    {{ $store->name }} — {{ __('messages.sidebar_returns_sub') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap shrink-0">
            <a href="{{ url('/store/' . $store->slug . '/pos/purchases') }}"
               class="px-3 py-1.5 rounded-lg text-xs font-bold text-sky-700 dark:text-sky-300 bg-sky-50 dark:bg-sky-950/40 border border-sky-200 dark:border-sky-800 hover:bg-sky-100 dark:hover:bg-sky-900/50 transition flex items-center gap-1.5 shadow-2xs">
                <span>🛒</span>
                <span>{{ __('messages.po_list_title') }}</span>
            </a>
            <a href="{{ url('/store/' . $store->slug . '/pos') }}"
               class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition flex items-center gap-1.5 shadow-2xs">
                <span>←</span>
                <span>{{ __('messages.back_to_pos') }}</span>
            </a>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if (session('success'))
        <div class="p-2.5 sm:p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs text-emerald-700 dark:text-emerald-300 flex items-start gap-2 shadow-2xs">
            <span class="text-sm font-bold shrink-0">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="p-2.5 sm:p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-lg text-xs text-rose-700 dark:text-rose-300 flex items-start gap-2 shadow-2xs">
            <span class="text-sm font-bold shrink-0">⚠️</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- 2. 4-Column Compact KPI Summary Cards --}}
    @php
        $totalReturnsCount = $returns->total();
        $totalReturnedQty = $returns->sum('total_quantity');
        $totalReturnValue = $returns->sum('total_cost');
        $uniqueSuppliers = $returns->pluck('supplier_id')->filter()->unique()->count();
    @endphp
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-2.5">
        {{-- Card 1: Total Returns Records --}}
        <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ __('messages.po_returns_total_count') }}</span>
                <span class="w-6 h-6 rounded bg-orange-50 dark:bg-orange-950/60 text-orange-600 dark:text-orange-400 grid place-items-center text-xs">↩️</span>
            </div>
            <div class="mt-1">
                <p class="text-base sm:text-lg font-black text-slate-900 dark:text-slate-100 font-mono">{{ number_format($totalReturnsCount) }}</p>
                <span class="text-[10px] text-slate-400 block mt-0.5">Return Transactions</span>
            </div>
        </div>

        {{-- Card 2: Total Items Returned --}}
        <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ __('messages.po_returns_total_qty') }}</span>
                <span class="w-6 h-6 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 grid place-items-center text-xs">📦</span>
            </div>
            <div class="mt-1">
                <p class="text-base sm:text-lg font-black text-slate-900 dark:text-slate-100 font-mono">{{ number_format((float) $totalReturnedQty, 2) }}</p>
                <span class="text-[10px] text-slate-400 block mt-0.5">Total Quantity Out</span>
            </div>
        </div>

        {{-- Card 3: Total Return Value --}}
        <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-amber-200/80 dark:border-amber-900/50 shadow-2xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">{{ __('messages.po_returns_total_val') }}</span>
                <span class="w-6 h-6 rounded bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 grid place-items-center text-xs">💰</span>
            </div>
            <div class="mt-1">
                <p class="text-base sm:text-lg font-black text-amber-600 dark:text-amber-400 font-mono truncate">
                    Ks {{ number_format((float) $totalReturnValue, 0) }}
                </p>
                <span class="text-[10px] text-slate-400 block mt-0.5">Value Deducted</span>
            </div>
        </div>

        {{-- Card 4: Suppliers Involved --}}
        <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ __('messages.po_returns_suppliers_count') }}</span>
                <span class="w-6 h-6 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 grid place-items-center text-xs">🏭</span>
            </div>
            <div class="mt-1">
                <p class="text-base sm:text-lg font-black text-slate-900 dark:text-slate-100 font-mono">{{ number_format($uniqueSuppliers) }}</p>
                <span class="text-[10px] text-slate-400 block mt-0.5">Impacted Vendors</span>
            </div>
        </div>
    </div>

    {{-- 3. Advanced Toolbar with Search, Sort & View Mode Switcher --}}
    <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col md:flex-row items-stretch md:items-center justify-between gap-2.5">
        <form method="GET" action="{{ url('/store/' . $store->slug . '/pos/purchases/returns') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 flex-1 min-w-0">
            {{-- Live Search Input --}}
            <div class="relative flex-1 min-w-[200px] max-w-md">
                <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="11" cy="11" r="8" stroke-width="2"/><line x1="21" y1="21" x2="16.65" y2="16.65" stroke-width="2"/></svg>
                </span>
                <input type="text" name="search" value="{{ request('search') }}" x-model="search"
                       placeholder="{{ __('messages.po_return_search') }}"
                       class="w-full pl-8 pr-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-sky-500 font-sans" />
            </div>

            {{-- Sort Dropdown --}}
            <select name="sort" onchange="this.form.submit()"
                    class="rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-3 py-1.5 text-xs text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-sky-500 outline-none">
                <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>{{ __('messages.po_sort_newest') }}</option>
                <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>{{ __('messages.po_sort_oldest') }}</option>
                <option value="highest" {{ request('sort') === 'highest' ? 'selected' : '' }}>{{ __('messages.po_sort_highest') }}</option>
                <option value="lowest" {{ request('sort') === 'lowest' ? 'selected' : '' }}>{{ __('messages.po_sort_lowest') }}</option>
            </select>

            <button type="submit" class="px-3.5 py-1.5 rounded-lg text-xs font-bold bg-sky-600 hover:bg-sky-500 text-white transition shadow-2xs">
                {{ __('messages.search') }}
            </button>
        </form>

        {{-- View Mode Toggle --}}
        <div class="flex items-center gap-1 p-0.5 bg-slate-100 dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 self-end md:self-auto shrink-0">
            <button type="button" @click="viewMode = 'table'; localStorage.setItem('pos_returns_view_mode', 'table')"
                    class="px-2.5 py-1 rounded text-xs font-bold transition flex items-center gap-1"
                    :class="viewMode === 'table' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 shadow-2xs' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                <span>Table</span>
            </button>
            <button type="button" @click="viewMode = 'cards'; localStorage.setItem('pos_returns_view_mode', 'cards')"
                    class="px-2.5 py-1 rounded text-xs font-bold transition flex items-center gap-1"
                    :class="viewMode === 'cards' || viewMode === 'card' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 shadow-2xs' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                <span>Cards</span>
            </button>
        </div>
    </div>

    {{-- 4. Google Sheets Style Spreadsheet Table View --}}
    <div x-show="viewMode === 'table'" class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden transition">
        <div class="overflow-x-auto max-h-[72vh] overflow-y-auto divide-y divide-slate-200 dark:divide-slate-800">
            <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs border-b-2 border-slate-300 dark:border-slate-600 shadow-2xs select-none">
                    <tr class="text-[11px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider divide-x divide-slate-300 dark:divide-slate-700">
                        <th class="py-2.5 px-3 min-w-[150px]">{{ __('messages.po_return_col_number') }}</th>
                        <th class="py-2.5 px-3 min-w-[150px]">{{ __('messages.po_col_po_number') }}</th>
                        <th class="py-2.5 px-3 min-w-[180px]">{{ __('messages.supplier_col_name') }}</th>
                        <th class="py-2.5 px-3 text-right w-28">{{ __('messages.reports_qty') }}</th>
                        <th class="py-2.5 px-3 text-right min-w-[140px]">{{ __('messages.reports_value') }}</th>
                        <th class="py-2.5 px-3 min-w-[160px]">{{ __('messages.po_return_col_reason') }}</th>
                        <th class="py-2.5 px-3 text-right w-28">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900">
                    @forelse ($returns as $return)
                        @php
                            $returnData = [
                                'id' => $return->id,
                                'return_number' => $return->return_number,
                                'po_number' => $return->purchaseOrder?->po_number ?? '',
                                'supplier_name' => $return->supplier?->name ?? '',
                                'reason' => $return->reason ?? '',
                            ];
                        @endphp
                        <tr x-show="matches({{ Js::from($returnData) }})"
                            class="divide-x divide-slate-200/80 dark:divide-slate-800 hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">
                            
                            {{-- Return Number & Date --}}
                            <td class="py-2.5 px-3">
                                <span class="font-mono font-black text-orange-600 dark:text-orange-400 block text-xs sm:text-sm">
                                    {{ $return->return_number }}
                                </span>
                                <span class="text-[10px] text-slate-400 block mt-0.5 font-mono">
                                    {{ $return->returned_at?->format('d M Y, H:i') ?? '—' }}
                                </span>
                            </td>

                            {{-- Linked Purchase Order --}}
                            <td class="py-2.5 px-3">
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
                            <td class="py-2.5 px-3">
                                @if ($return->supplier)
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="shrink-0 w-7 h-7 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 text-white grid place-items-center font-black text-[10px] select-none shadow-2xs">
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

                            {{-- Returned Quantity --}}
                            <td class="py-2.5 px-3 text-right font-mono font-bold">
                                {{ number_format((float) $return->total_quantity, 2) }}
                            </td>

                            {{-- Returned Value --}}
                            <td class="py-2.5 px-3 text-right font-mono font-black text-amber-600 dark:text-amber-400 whitespace-nowrap">
                                Ks {{ number_format((float) $return->total_cost, 0) }}
                            </td>

                            {{-- Reason --}}
                            <td class="py-2.5 px-3">
                                <span class="text-xs text-slate-600 dark:text-slate-300 block truncate max-w-xs" title="{{ $return->reason }}">
                                    {{ $return->reason ?: '—' }}
                                </span>
                            </td>

                            {{-- Actions --}}
                            <td class="py-2.5 px-3 text-right whitespace-nowrap">
                                @if ($return->purchaseOrder)
                                    <a href="{{ url('/store/' . $store->slug . '/pos/purchases/' . $return->purchaseOrder->id) }}"
                                       class="px-2.5 py-1 rounded text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition inline-flex items-center gap-1 active:scale-95">
                                        <span>View PO</span>
                                        <span>→</span>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-400">
                                <div class="text-3xl mb-2 opacity-55">🔄</div>
                                <div class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.po_return_none') }}</div>
                                <a href="{{ url('/store/' . $store->slug . '/pos/purchases') }}"
                                   class="mt-2 inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 transition shadow-sm">
                                    🛒 {{ __('messages.po_list_title') }}
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($returns->hasPages())
            <div class="p-3 border-t border-slate-100 dark:border-slate-800 text-xs">{{ $returns->links() }}</div>
        @endif
    </div>

    {{-- 5. Responsive Multi-Column Card Grid View --}}
    <div x-show="viewMode === 'cards' || viewMode === 'card'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2.5 sm:gap-3">
        @forelse ($returns as $return)
            @php
                $returnData = [
                    'id' => $return->id,
                    'return_number' => $return->return_number,
                    'po_number' => $return->purchaseOrder?->po_number ?? '',
                    'supplier_name' => $return->supplier?->name ?? '',
                    'reason' => $return->reason ?? '',
                ];
            @endphp
            <div x-show="matches({{ Js::from($returnData) }})"
                 class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl overflow-hidden shadow-2xs hover:border-orange-300 dark:hover:border-orange-600/50 hover:shadow-sm transition flex flex-col justify-between group">
                
                <div class="p-3 space-y-2.5">
                    {{-- Card Header: Return # + Date --}}
                    <div class="flex items-start justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                        <div>
                            <span class="font-mono font-black text-orange-600 dark:text-orange-400 text-sm block">
                                {{ $return->return_number }}
                            </span>
                            <span class="text-[10px] text-slate-400 font-mono block mt-0.5">
                                {{ $return->returned_at?->format('d M Y, H:i') ?? '—' }}
                            </span>
                        </div>
                        @if ($return->purchaseOrder)
                            <a href="{{ url('/store/' . $store->slug . '/pos/purchases/' . $return->purchaseOrder->id) }}"
                               class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800 font-mono font-bold text-[10px] hover:underline">
                                <span>PO:</span>
                                <span>{{ $return->purchaseOrder->po_number }}</span>
                            </a>
                        @endif
                    </div>

                    {{-- Supplier Information --}}
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Supplier</span>
                        @if ($return->supplier)
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="shrink-0 w-6 h-6 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 text-white grid place-items-center font-black text-[9px] select-none shadow-2xs">
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
                    <div class="bg-slate-50 dark:bg-slate-800/60 p-2 rounded-lg border border-slate-100 dark:border-slate-800 grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <span class="text-[10px] text-slate-400 block uppercase font-bold">Returned Qty</span>
                            <span class="font-mono font-bold text-slate-700 dark:text-slate-300">{{ number_format((float) $return->total_quantity, 2) }}</span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] text-slate-400 block uppercase font-bold">Total Value</span>
                            <span class="font-mono font-black text-amber-600 dark:text-amber-400">Ks {{ number_format((float) $return->total_cost, 0) }}</span>
                        </div>
                    </div>

                    @if ($return->reason)
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-800/40 p-1.5 rounded border border-slate-100 dark:border-slate-800 italic line-clamp-2">
                            "{{ $return->reason }}"
                        </p>
                    @endif
                </div>

                {{-- Card Footer Action --}}
                <div class="p-2.5 bg-slate-50/80 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end">
                    @if ($return->purchaseOrder)
                        <a href="{{ url('/store/' . $store->slug . '/pos/purchases/' . $return->purchaseOrder->id) }}"
                           class="w-full text-center px-3 py-1.5 rounded-lg bg-sky-50 hover:bg-sky-100 dark:bg-sky-950/60 dark:hover:bg-sky-900/60 text-sky-600 dark:text-sky-300 text-xs font-bold transition flex items-center justify-center gap-1.5">
                            <span>View Original PO</span>
                            <span>→</span>
                        </a>
                    @else
                        <span class="text-xs text-slate-400">—</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white dark:bg-slate-900 border border-dashed border-slate-200 dark:border-slate-800 p-8 rounded-xl text-center text-slate-400 shadow-2xs">
                <div class="text-3xl mb-2 opacity-55">🔄</div>
                <div class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.po_return_none') }}</div>
                <a href="{{ url('/store/' . $store->slug . '/pos/purchases') }}"
                   class="mt-2 inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 transition shadow-sm">
                    🛒 {{ __('messages.po_list_title') }}
                </a>
            </div>
        @endforelse
    </div>

    {{-- Pagination for Card view --}}
    @if ($returns->hasPages())
        <div x-show="viewMode === 'cards' || viewMode === 'card'" class="p-3 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg text-xs">
            {{ $returns->links() }}
        </div>
    @endif

</div>
@endsection
