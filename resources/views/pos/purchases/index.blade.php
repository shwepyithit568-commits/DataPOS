@extends('layouts.admin.app')

@section('title', __('messages.po_list_title') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
<div class="w-full space-y-0.5 pb-6"
     x-data="{
         search: '',
         statusFilter: '{{ $status ?? '' }}',
         viewMode: localStorage.getItem('pos_purchases_view_mode') || 'table',
         matches(po) {
             const q = this.search.toLowerCase().trim();
             const matchesSearch = !q || (po.po_number && po.po_number.toLowerCase().includes(q)) || (po.supplier_name && po.supplier_name.toLowerCase().includes(q));
             const matchesStatus = !this.statusFilter || po.status === this.statusFilter;
             return matchesSearch && matchesStatus;
         }
     }"
     @view-changed.window="viewMode = $event.detail; localStorage.setItem('pos_purchases_view_mode', $event.detail)">

    {{-- 1. Top Header Banner (Ultra-Dense 36px) --}}
    <div class="px-2 py-1.5 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 select-none">
        <div class="flex items-center gap-2 min-w-0">
            <span class="w-7 h-7 rounded bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 border border-sky-200 dark:border-sky-800 grid place-items-center text-sm font-black shrink-0">
                🛒
            </span>
            <div class="min-w-0">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 truncate">
                        {{ __('messages.po_list_title') }}
                    </h1>
                    <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                        {{ $store->name }}
                    </span>
                </div>
                <p class="text-[10px] text-slate-400 font-mono truncate hidden sm:block">
                    {{ __('messages.receiving_subtitle') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-1.5 flex-wrap shrink-0">
            <a href="{{ url('/store/' . $store->slug . '/pos/purchases/payables') }}"
               class="h-7 px-2 rounded text-xs font-bold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 hover:bg-amber-100 dark:hover:bg-amber-900/50 transition inline-flex items-center gap-1 shadow-2xs">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                <span>{{ __('messages.payables_title') }}</span>
            </a>
            <a href="{{ url('/store/' . $store->slug . '/pos/purchases/returns') }}"
               class="h-7 px-2 rounded text-xs font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-700 transition inline-flex items-center gap-1 shadow-2xs">
                <span>↩️</span>
                <span>Returns</span>
            </a>
            <a href="{{ url('/store/' . $store->slug . '/pos/purchases/create') }}"
               class="h-7 px-2.5 rounded text-xs font-black bg-sky-600 hover:bg-sky-500 text-white shadow-2xs hover:shadow-sky-500/20 transition inline-flex items-center gap-1.5 active:scale-95 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                <span>+ {{ __('messages.po_new') }}</span>
            </a>
            <a href="{{ url('/store/' . $store->slug . '/pos') }}"
               class="h-7 px-2 rounded text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition inline-flex items-center gap-1 shadow-2xs">
                <span>←</span>
                <span>{{ __('messages.back_to_pos') }}</span>
            </a>
        </div>
    </div>

    {{-- 2. 4-Column Compact Centered Stat Cards --}}
    @php
        $totalOutstanding = $pos->sum(fn ($po) => (float) $po->remaining_balance);
        $pendingAndOrdered = ($statusCounts['pending'] ?? 0) + ($statusCounts['ordered'] ?? 0);
        $receivedCount = $statusCounts['received'] ?? 0;
        $totalPOs = $statusCounts->sum();
    @endphp
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-0.5 sm:gap-1">
        {{-- Card 1: Total POs --}}
        <div class="p-2 sm:p-2.5 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition">
            <div class="w-8 h-8 rounded-md bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 flex items-center justify-center text-sm font-black shrink-0">
                📋
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400 leading-none">
                    {{ __('messages.po_list_title') }}
                </p>
                <div class="text-xs sm:text-sm font-black font-mono text-slate-900 dark:text-slate-100 tabular-nums mt-0.5">
                    {{ number_format($totalPOs) }}
                </div>
            </div>
        </div>

        {{-- Card 2: Pending / In-Progress --}}
        <div class="p-2 sm:p-2.5 bg-white dark:bg-slate-900 rounded border border-amber-200/90 dark:border-amber-900/50 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition">
            <div class="w-8 h-8 rounded-md bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center text-sm font-black shrink-0">
                ⏳
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-wider text-amber-600 dark:text-amber-400 leading-none">
                    {{ __('messages.po_status_pending') }} / Ordered
                </p>
                <div class="text-xs sm:text-sm font-black font-mono text-amber-600 dark:text-amber-400 tabular-nums mt-0.5">
                    {{ number_format($pendingAndOrdered) }}
                </div>
            </div>
        </div>

        {{-- Card 3: Received / Ingested --}}
        <div class="p-2 sm:p-2.5 bg-white dark:bg-slate-900 rounded border border-emerald-200/90 dark:border-emerald-900/50 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition">
            <div class="w-8 h-8 rounded-md bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-sm font-black shrink-0">
                ✓
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400 leading-none">
                    {{ __('messages.po_status_received') }}
                </p>
                <div class="text-xs sm:text-sm font-black font-mono text-emerald-600 dark:text-emerald-400 tabular-nums mt-0.5">
                    {{ number_format($receivedCount) }}
                </div>
            </div>
        </div>

        {{-- Card 4: Outstanding Payables --}}
        <div class="p-2 sm:p-2.5 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition">
            <div class="w-8 h-8 rounded-md bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 flex items-center justify-center text-sm font-black shrink-0">
                💰
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400 leading-none">
                    {{ __('messages.payables_title') }}
                </p>
                <div class="text-xs sm:text-sm font-black font-mono {{ $totalOutstanding > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-slate-100' }} tabular-nums mt-0.5 truncate">
                    Ks {{ number_format($totalOutstanding, 0) }}
                </div>
            </div>
        </div>
    </div>

    {{-- 3. Advanced Toolbar with Search, Status Filter Chips & View Mode Toggle --}}
    <div class="p-1 sm:p-1.5 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs flex flex-col md:flex-row items-stretch md:items-center justify-between gap-1.5">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-1.5 flex-1 min-w-0">
            {{-- Live Search Box --}}
            <div class="relative flex-1 min-w-[180px] max-w-sm">
                <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="11" cy="11" r="8" stroke-width="2"/><line x1="21" y1="21" x2="16.65" y2="16.65" stroke-width="2"/></svg>
                </span>
                <input type="text" x-model="search"
                       placeholder="{{ __('messages.po_number') }} / {{ __('messages.po_supplier') }}..."
                       class="w-full h-7 pl-8 pr-2.5 rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs text-slate-900 dark:text-slate-100 outline-none focus:ring-1 focus:ring-sky-500 font-sans" />
            </div>

            {{-- Status Filter Chips --}}
            @php
                $statusTabs = [
                    '' => ['label' => __('messages.po_filter_all'), 'count' => $totalPOs],
                    'pending' => ['label' => __('messages.po_status_pending'), 'count' => $statusCounts['pending'] ?? 0],
                    'ordered' => ['label' => __('messages.po_status_ordered'), 'count' => $statusCounts['ordered'] ?? 0],
                    'received' => ['label' => __('messages.po_status_received'), 'count' => $statusCounts['received'] ?? 0],
                    'cancelled' => ['label' => __('messages.po_status_cancelled'), 'count' => $statusCounts['cancelled'] ?? 0],
                    'returned' => ['label' => __('messages.po_status_returned'), 'count' => $statusCounts['returned'] ?? 0],
                ];
            @endphp
            <div class="flex items-center gap-1 overflow-x-auto pb-0.5 scrollbar-none shrink-0">
                @foreach ($statusTabs as $k => $info)
                    <button type="button" @click="statusFilter = '{{ $k }}'"
                            class="h-7 px-2 rounded text-xs font-bold transition flex items-center gap-1 shrink-0 cursor-pointer"
                            :class="statusFilter === '{{ $k }}' ? 'bg-sky-600 text-white shadow-2xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'">
                        <span>{{ $info['label'] }}</span>
                        <span class="px-1 py-0.2 rounded-full text-[10px] font-mono"
                              :class="statusFilter === '{{ $k }}' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300'">
                            {{ number_format($info['count']) }}
                        </span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- View Mode Toggle --}}
        <div class="flex items-center gap-0.5 p-0.5 bg-slate-100 dark:bg-slate-800 rounded border border-slate-200 dark:border-slate-700 self-end md:self-auto shrink-0">
            <button type="button" @click="viewMode = 'table'; localStorage.setItem('pos_purchases_view_mode', 'table')"
                    class="h-6 px-2 rounded text-xs font-bold transition flex items-center gap-1 cursor-pointer"
                    :class="viewMode === 'table' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 shadow-2xs' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                <span>Table</span>
            </button>
            <button type="button" @click="viewMode = 'cards'; localStorage.setItem('pos_purchases_view_mode', 'cards')"
                    class="h-6 px-2 rounded text-xs font-bold transition flex items-center gap-1 cursor-pointer"
                    :class="viewMode === 'cards' || viewMode === 'card' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 shadow-2xs' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                <span>Cards</span>
            </button>
        </div>
    </div>

    {{-- Floating Action Button for Mobile --}}
    <a href="{{ url('/store/' . $store->slug . '/pos/purchases/create') }}"
       class="fixed bottom-5 right-5 z-40 sm:hidden w-12 h-12 rounded-full bg-gradient-to-r from-sky-600 to-indigo-600 text-white shadow-xl shadow-sky-900/40 flex items-center justify-center text-2xl font-bold active:scale-95 transition"
       title="{{ __('messages.po_new') }}">
        +
    </a>

    {{-- 4. Google Sheets Style Spreadsheet Table View --}}
    <div x-show="viewMode === 'table'" class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded shadow-2xs overflow-hidden transition">
        <div class="overflow-x-auto max-h-[72vh] overflow-y-auto divide-y divide-slate-200 dark:divide-slate-800">
            <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs border-b-2 border-slate-300 dark:border-slate-600 shadow-2xs select-none">
                    <tr class="text-[11px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider divide-x divide-slate-300 dark:divide-slate-700">
                        <th class="py-1.5 px-2.5 min-w-[160px]">{{ __('messages.po_number') }}</th>
                        <th class="py-1.5 px-2.5 min-w-[180px]">{{ __('messages.po_supplier') }}</th>
                        <th class="py-1.5 px-2.5 text-center w-36">{{ __('messages.reports_status') }}</th>
                        <th class="py-1.5 px-2.5 text-center w-24">{{ __('messages.reports_items') }}</th>
                        <th class="py-1.5 px-2.5 text-right min-w-[130px]">{{ __('messages.reports_value') }}</th>
                        <th class="py-1.5 px-2.5 text-right min-w-[130px]">{{ __('messages.receiving_total') }}</th>
                        <th class="py-1.5 px-2.5 text-right w-28">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900">
                    @forelse ($pos as $po)
                        @php
                            $poData = [
                                'id' => $po->id,
                                'po_number' => $po->po_number,
                                'supplier_name' => $po->supplier?->name ?? '',
                                'status' => $po->status,
                            ];
                        @endphp
                        <tr x-show="matches({{ Js::from($poData) }})"
                            class="divide-x divide-slate-200/80 dark:divide-slate-800 hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">
                            
                            {{-- PO Number & Date --}}
                            <td class="py-1.5 px-2.5">
                                <a href="{{ url('/store/' . $store->slug . '/pos/purchases/' . $po->id) }}" class="group block">
                                    <span class="font-mono font-black text-sky-600 dark:text-sky-400 group-hover:underline text-xs">
                                        {{ $po->po_number }}
                                    </span>
                                    <span class="text-[10px] text-slate-400 block mt-0.5 font-mono">
                                        {{ $po->created_at->format('d M Y, H:i') }}
                                    </span>
                                </a>
                            </td>

                            {{-- Supplier --}}
                            <td class="py-1.5 px-2.5">
                                @if ($po->supplier)
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="shrink-0 w-6 h-6 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 text-white grid place-items-center font-black text-[10px] select-none shadow-2xs">
                                            {{ mb_strtoupper(mb_substr(trim($po->supplier->name), 0, 1)) }}
                                        </span>
                                        <span class="font-bold text-slate-900 dark:text-slate-100 truncate text-xs" title="{{ $po->supplier->name }}">
                                            {{ $po->supplier->name }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-slate-400 font-mono">—</span>
                                @endif
                            </td>

                            {{-- Status Badges --}}
                            <td class="py-1.5 px-2.5 text-center whitespace-nowrap">
                                @php
                                    $statusPill = match($po->status) {
                                        'pending' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800',
                                        'ordered' => 'bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 border-sky-200 dark:border-sky-800',
                                        'received' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
                                        'cancelled' => 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400 border-slate-200 dark:border-slate-700',
                                        'returned' => 'bg-violet-50 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 border-violet-200 dark:border-violet-800',
                                        default => 'bg-slate-100 text-slate-600 border-slate-200'
                                    };
                                @endphp
                                <span class="inline-flex items-center px-1.5 py-0.2 rounded-full text-[10px] font-black uppercase border {{ $statusPill }}">
                                    {{ __('messages.po_status_' . $po->status) }}
                                </span>
                                @if ($po->status === 'received')
                                    @php
                                        $payPill = match($po->payment_status) {
                                            'paid' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
                                            'partial' => 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
                                            default => 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300'
                                        };
                                    @endphp
                                    <span class="inline-block mt-0.5 px-1.5 py-0.2 rounded text-[9px] font-bold uppercase {{ $payPill }}">
                                        {{ __('messages.po_payment_' . $po->payment_status) }}
                                    </span>
                                @endif
                            </td>

                            {{-- Items Count --}}
                            <td class="py-1.5 px-2.5 text-center font-mono font-bold">
                                <span class="inline-block px-1.5 py-0.2 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs">
                                    {{ $po->items->count() }}
                                </span>
                            </td>

                            {{-- Total Cost Valuation --}}
                            <td class="py-1.5 px-2.5 text-right font-mono font-black text-slate-900 dark:text-slate-100 whitespace-nowrap">
                                Ks {{ number_format((float) $po->total_cost, 0) }}
                            </td>

                            {{-- Remaining Balance / Payables --}}
                            <td class="py-1.5 px-2.5 text-right whitespace-nowrap">
                                @if ((float) $po->remaining_balance > 0)
                                    <span class="inline-flex items-center px-1.5 py-0.2 rounded-full bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800 text-xs font-black font-mono">
                                        Ks {{ number_format((float) $po->remaining_balance, 0) }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400 font-mono">—</span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="py-1.5 px-2.5 text-right whitespace-nowrap">
                                <a href="{{ url('/store/' . $store->slug . '/pos/purchases/' . $po->id) }}"
                                   class="px-2 py-1 rounded text-xs font-bold bg-sky-50 hover:bg-sky-100 dark:bg-sky-950/50 dark:hover:bg-sky-900/60 text-sky-600 dark:text-sky-300 transition inline-flex items-center gap-1 active:scale-95 cursor-pointer">
                                    <span>{{ __('messages.po_view') }}</span>
                                    <span>→</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-400">
                                <div class="text-3xl mb-2 opacity-55">🛒</div>
                                <div class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.po_none') }}</div>
                                <a href="{{ url('/store/' . $store->slug . '/pos/purchases/create') }}"
                                   class="mt-2 inline-flex items-center gap-1.5 px-3 py-1.5 rounded text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 transition shadow-xs cursor-pointer">
                                    + {{ __('messages.po_new') }}
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 5. Responsive Multi-Column Card Grid View --}}
    <div x-show="viewMode === 'cards' || viewMode === 'card'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-1.5 sm:gap-2">
        @forelse ($pos as $po)
            @php
                $poData = [
                    'id' => $po->id,
                    'po_number' => $po->po_number,
                    'supplier_name' => $po->supplier?->name ?? '',
                    'status' => $po->status,
                ];
                $statusPill = match($po->status) {
                    'pending' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800',
                    'ordered' => 'bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 border-sky-200 dark:border-sky-800',
                    'received' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
                    'cancelled' => 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400 border-slate-200 dark:border-slate-700',
                    'returned' => 'bg-violet-50 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 border-violet-200 dark:border-violet-800',
                    default => 'bg-slate-100 text-slate-600'
                };
            @endphp
            <div x-show="matches({{ Js::from($poData) }})"
                 class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg overflow-hidden shadow-2xs hover:border-sky-300 dark:hover:border-sky-600/50 hover:shadow-sm transition flex flex-col justify-between group">
                
                <div class="p-2.5 space-y-2">
                    {{-- Card Header: PO # + Status Pill --}}
                    <div class="flex items-start justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-1.5">
                        <div>
                            <span class="font-mono font-black text-sky-600 dark:text-sky-400 text-xs sm:text-sm block">
                                {{ $po->po_number }}
                            </span>
                            <span class="text-[10px] text-slate-400 font-mono block mt-0.5">
                                {{ $po->created_at->format('d M Y, H:i') }}
                            </span>
                        </div>
                        <span class="inline-flex items-center px-1.5 py-0.2 rounded-full text-[10px] font-black uppercase border {{ $statusPill }} shrink-0">
                            {{ __('messages.po_status_' . $po->status) }}
                        </span>
                    </div>

                    {{-- Supplier Information --}}
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Supplier</span>
                        @if ($po->supplier)
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span class="shrink-0 w-5 h-5 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 text-white grid place-items-center font-black text-[8px] select-none shadow-2xs">
                                    {{ mb_strtoupper(mb_substr(trim($po->supplier->name), 0, 1)) }}
                                </span>
                                <span class="font-bold text-xs text-slate-800 dark:text-slate-200 truncate" title="{{ $po->supplier->name }}">
                                    {{ $po->supplier->name }}
                                </span>
                            </div>
                        @else
                            <span class="text-xs text-slate-400 font-mono">—</span>
                        @endif
                    </div>

                    {{-- Numeric Comparison Metrics Box --}}
                    <div class="bg-slate-50 dark:bg-slate-800/60 p-1.5 rounded border border-slate-100 dark:border-slate-800 grid grid-cols-2 gap-1.5 text-xs">
                        <div>
                            <span class="text-[10px] text-slate-400 block uppercase font-bold">Items Qty</span>
                            <span class="font-mono font-bold text-slate-700 dark:text-slate-300">{{ $po->items->count() }} lines</span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] text-slate-400 block uppercase font-bold">Total Cost</span>
                            <span class="font-mono font-black text-slate-900 dark:text-slate-100">Ks {{ number_format((float) $po->total_cost, 0) }}</span>
                        </div>
                    </div>

                    @if ((float) $po->remaining_balance > 0)
                        <div class="flex items-center justify-between text-xs px-0.5">
                            <span class="text-[10px] text-amber-600 dark:text-amber-400 font-bold uppercase">Payables:</span>
                            <span class="font-mono font-black text-amber-600 dark:text-amber-400">Ks {{ number_format((float) $po->remaining_balance, 0) }}</span>
                        </div>
                    @endif
                </div>

                {{-- Card Footer Action --}}
                <div class="p-2 bg-slate-50/80 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end">
                    <a href="{{ url('/store/' . $store->slug . '/pos/purchases/' . $po->id) }}"
                       class="w-full text-center px-2 py-1 rounded bg-sky-50 hover:bg-sky-100 dark:bg-sky-950/60 dark:hover:bg-sky-900/60 text-sky-600 dark:text-sky-300 text-xs font-bold transition flex items-center justify-center gap-1 cursor-pointer">
                        <span>{{ __('messages.po_view') }}</span>
                        <span>→</span>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white dark:bg-slate-900 border border-dashed border-slate-200 dark:border-slate-800 p-8 rounded text-center text-slate-400 shadow-2xs">
                <div class="text-3xl mb-2 opacity-55">🛒</div>
                <div class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.po_none') }}</div>
                <a href="{{ url('/store/' . $store->slug . '/pos/purchases/create') }}"
                   class="mt-2 inline-flex items-center gap-1.5 px-3 py-1.5 rounded text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 transition shadow-xs cursor-pointer">
                    + {{ __('messages.po_new') }}
                </a>
            </div>
        @endforelse
    </div>

</div>
@endsection
