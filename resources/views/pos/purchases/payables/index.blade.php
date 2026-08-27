@extends('layouts.admin.app')

@section('title', __('messages.sidebar_payables') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@section('content')
@php
    $totalSuppliersWithDebt = count($suppliers);
    $totalUnpaidOrders = $suppliers->sum('unpaid_count');
    $highestDebtItem = $suppliers->first();
    $highestDebtVal = $highestDebtItem ? (float) $highestDebtItem['balance'] : 0;
    $highestDebtSupplierName = $highestDebtItem ? $highestDebtItem['supplier']->name : '—';
@endphp

<div class="w-full space-y-2 sm:space-y-2.5"
     x-data="{
         search: '',
         sort: 'highest',
         viewMode: localStorage.getItem('datapos_payables_view_mode') || 'table',
         setViewMode(mode) {
             this.viewMode = mode;
             localStorage.setItem('datapos_payables_view_mode', mode);
         },
         suppliers: {{ Js::from($suppliers->map(function ($s, $idx) {
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
                 'view_url' => url('/store/' . request()->route('store_slug') . '/pos/purchases/payables/' . $s['supplier']->id),
             ];
         })) }},
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
             } else if (this.sort === 'oldest_date') {
                 list.sort((a, b) => a.oldest_timestamp - b.oldest_timestamp);
             }
             return list;
         }
     }">

    {{-- 1. Top Header Banner --}}
    <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-8 h-8 rounded-lg bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800 grid place-items-center text-sm font-black shrink-0">
                💳
            </span>
            <div class="min-w-0">
                <h1 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 truncate">
                    {{ __('messages.sidebar_payables') }}
                </h1>
                <p class="text-[11px] text-slate-400 font-mono truncate">
                    {{ $store->name }} — {{ __('messages.payables_subtitle') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap shrink-0">
            <a href="{{ route('pos.purchases.payables.export', ['store_slug' => $store->slug]) }}"
               class="px-2.5 py-1.5 rounded-lg text-xs font-bold text-sky-700 dark:text-sky-300 bg-sky-50 dark:bg-sky-950/40 border border-sky-200 dark:border-sky-800 hover:bg-sky-100 dark:hover:bg-sky-900/50 transition flex items-center gap-1 shadow-2xs">
                <span>📊</span>
                <span>{{ __('messages.export') ?? 'Export' }} CSV</span>
            </a>
            <a href="{{ url('/store/' . $store->slug . '/pos/purchases/export?format=excel') }}"
               class="px-2.5 py-1.5 rounded-lg text-xs font-bold text-violet-700 dark:text-violet-300 bg-violet-50 dark:bg-violet-950/40 border border-violet-200 dark:border-violet-800 hover:bg-violet-100 dark:hover:bg-violet-900/50 transition flex items-center gap-1 shadow-2xs">
                <span>📁</span>
                <span>{{ __('messages.export_excel') }}</span>
            </a>
            <a href="{{ url('/store/' . $store->slug . '/pos/purchases/export?format=pdf') }}"
               class="px-2.5 py-1.5 rounded-lg text-xs font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition flex items-center gap-1 shadow-2xs">
                <span>📄</span>
                <span>{{ __('messages.export_pdf') }}</span>
            </a>
            <a href="{{ url('/store/' . $store->slug . '/pos/purchases') }}"
               class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition flex items-center gap-1 shadow-2xs">
                <span>🛒</span>
                <span>{{ __('messages.po_list_title') }}</span>
            </a>
            <a href="{{ url('/store/' . $store->slug . '/pos') }}"
               class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition flex items-center gap-1 shadow-2xs">
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
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-2.5">
        {{-- Card 1: Total Outstanding Payables --}}
        <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-rose-200/80 dark:border-rose-900/50 shadow-2xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">{{ __('messages.payables_total_outstanding') }}</span>
                <span class="w-6 h-6 rounded bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 grid place-items-center text-xs">💰</span>
            </div>
            <div class="mt-1">
                <p class="text-base sm:text-lg font-black text-rose-600 dark:text-rose-400 font-mono truncate">
                    Ks {{ number_format((float) $totalOutstanding) }}
                </p>
                <span class="text-[10px] text-slate-400 block mt-0.5">Total Unsettled Debt</span>
            </div>
        </div>

        {{-- Card 2: Suppliers With Debt --}}
        <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ __('messages.payables_suppliers_with_debt') }}</span>
                <span class="w-6 h-6 rounded bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 grid place-items-center text-xs">🏭</span>
            </div>
            <div class="mt-1">
                <p class="text-base sm:text-lg font-black text-slate-900 dark:text-slate-100 font-mono">{{ number_format($totalSuppliersWithDebt) }}</p>
                <span class="text-[10px] text-slate-400 block mt-0.5">Vendors Owed</span>
            </div>
        </div>

        {{-- Card 3: Total Unpaid POs --}}
        <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ __('messages.payables_total_unpaid_pos') }}</span>
                <span class="w-6 h-6 rounded bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 grid place-items-center text-xs">📋</span>
            </div>
            <div class="mt-1">
                <p class="text-base sm:text-lg font-black text-slate-900 dark:text-slate-100 font-mono">{{ number_format($totalUnpaidOrders) }}</p>
                <span class="text-[10px] text-slate-400 block mt-0.5">Open Invoices</span>
            </div>
        </div>

        {{-- Card 4: Highest Single Debt --}}
        <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ __('messages.payables_highest_debt') }}</span>
                <span class="w-6 h-6 rounded bg-violet-50 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 grid place-items-center text-xs">⚠️</span>
            </div>
            <div class="mt-1">
                <p class="text-base sm:text-lg font-black text-slate-900 dark:text-slate-100 font-mono truncate">
                    Ks {{ number_format($highestDebtVal) }}
                </p>
                <span class="text-[10px] text-slate-400 block mt-0.5 truncate" title="{{ $highestDebtSupplierName }}">
                    {{ $highestDebtSupplierName }}
                </span>
            </div>
        </div>
    </div>

    {{-- 3. Advanced Toolbar with Search, Sort & View Mode Switcher --}}
    <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col md:flex-row items-stretch md:items-center justify-between gap-2.5">
        {{-- Live Search Box --}}
        <div class="relative flex-1 min-w-0">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="11" cy="11" r="8" stroke-width="2"/><line x1="21" y1="21" x2="16.65" y2="16.65" stroke-width="2"/></svg>
            </span>
            <input type="text" x-model="search" placeholder="{{ __('messages.payables_search') }}"
                   class="w-full pl-9 pr-8 py-1.5 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-rose-500 font-medium">
            <button x-show="search.length > 0" @click="search = ''" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 text-sm">
                &times;
            </button>
        </div>

        {{-- Controls Group: Sort & View Toggle --}}
        <div class="flex items-center gap-2 shrink-0">
            {{-- Sort Dropdown --}}
            <div class="relative">
                <select x-model="sort"
                        class="text-xs font-semibold rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2.5 py-1.5 text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-rose-500">
                    <option value="highest">💰 {{ __('messages.po_sort_highest') }}</option>
                    <option value="lowest">📉 {{ __('messages.po_sort_lowest') }}</option>
                    <option value="most_unpaid">📋 {{ __('messages.payables_unpaid_pos') }}</option>
                    <option value="oldest_date">⏳ {{ __('messages.payables_oldest') }}</option>
                    <option value="name_asc">🔤 {{ __('messages.payables_supplier') }} (A-Z)</option>
                </select>
            </div>

            {{-- View Mode Toggle --}}
            <div class="flex items-center bg-slate-100 dark:bg-slate-800 p-0.5 rounded-lg border border-slate-200 dark:border-slate-700">
                <button type="button" @click="setViewMode('table')"
                        :class="viewMode === 'table' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 shadow-2xs font-bold' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                        class="px-2.5 py-1 rounded-md text-xs transition flex items-center gap-1">
                    <span>📑</span>
                    <span class="hidden sm:inline">Table</span>
                </button>
                <button type="button" @click="setViewMode('cards')"
                        :class="viewMode === 'cards' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 shadow-2xs font-bold' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                        class="px-2.5 py-1 rounded-md text-xs transition flex items-center gap-1">
                    <span>🎴</span>
                    <span class="hidden sm:inline">Cards</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Empty State (No debt) --}}
    <div x-show="filteredSuppliers.length === 0" x-cloak
         class="p-8 bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-2xs text-center">
        <div class="w-12 h-12 mx-auto rounded-full bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 grid place-items-center text-xl mb-2">
            🎉
        </div>
        <h3 class="text-sm font-black text-slate-800 dark:text-slate-200">{{ __('messages.payables_none') }}</h3>
        <p class="text-xs text-slate-400 mt-1">{{ __('messages.payables_all_clear') }}</p>
    </div>

    {{-- 4. Google Sheets-Style Spreadsheet Table View --}}
    <div x-show="viewMode === 'table' && filteredSuppliers.length > 0"
         class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-xl shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse font-sans">
                <thead class="bg-slate-100 dark:bg-slate-800/90 text-slate-700 dark:text-slate-300 font-bold uppercase text-[11px] sticky top-0 z-10 border-b border-slate-200 dark:border-slate-700">
                    <tr class="divide-x divide-slate-200 dark:divide-slate-700">
                        <th class="p-2.5 text-center w-10">#</th>
                        <th class="p-2.5 min-w-[200px]">{{ __('messages.payables_supplier') }}</th>
                        <th class="p-2.5 min-w-[150px]">{{ __('messages.payables_contact') }}</th>
                        <th class="p-2.5 text-center min-w-[120px]">{{ __('messages.payables_unpaid_pos') }}</th>
                        <th class="p-2.5 text-right min-w-[150px]">{{ __('messages.payables_outstanding') }}</th>
                        <th class="p-2.5 text-center min-w-[130px]">{{ __('messages.payables_oldest') }}</th>
                        <th class="p-2.5 text-center w-28">{{ __('messages.action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200">
                    <template x-for="(s, index) in filteredSuppliers" :key="s.id">
                        <tr class="divide-x divide-slate-200/60 dark:divide-slate-800 hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">
                            {{-- Index --}}
                            <td class="p-2.5 text-center font-mono font-bold text-slate-400" x-text="index + 1"></td>

                            {{-- Supplier Name & Avatar --}}
                            <td class="p-2.5">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="shrink-0 w-7 h-7 rounded-lg bg-gradient-to-br from-rose-500 to-amber-500 text-white grid place-items-center font-black text-xs select-none shadow-2xs"
                                          x-text="s.name.charAt(0).toUpperCase()"></span>
                                    <div class="min-w-0">
                                        <a :href="s.view_url" class="font-bold text-xs text-slate-900 dark:text-slate-100 hover:text-rose-600 dark:hover:text-rose-400 truncate block" x-text="s.name"></a>
                                    </div>
                                </div>
                            </td>

                            {{-- Contact Person & Phone --}}
                            <td class="p-2.5">
                                <span class="block font-medium text-slate-700 dark:text-slate-300 truncate" x-text="s.contact_person ? s.contact_person : '—'"></span>
                                <template x-if="s.phone">
                                    <a :href="'tel:' + s.phone" class="text-[11px] font-mono text-sky-600 dark:text-sky-400 hover:underline block truncate" x-text="s.phone"></a>
                                </template>
                            </td>

                            {{-- Unpaid POs count badge --}}
                            <td class="p-2.5 text-center">
                                <a :href="s.view_url" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 border border-sky-200 dark:border-sky-800 hover:bg-sky-100 transition font-mono">
                                    <span x-text="s.unpaid_count"></span>
                                    <span>POs</span>
                                </a>
                            </td>

                            {{-- Outstanding Debt --}}
                            <td class="p-2.5 text-right font-mono font-black text-xs text-rose-600 dark:text-rose-400 whitespace-nowrap"
                                x-text="'Ks ' + Number(s.balance).toLocaleString()"></td>

                            {{-- Oldest Unpaid Date & Aging Tag --}}
                            <td class="p-2.5 text-center">
                                <span class="block font-mono text-[11px] text-slate-600 dark:text-slate-400 font-bold" x-text="s.oldest_date"></span>
                                <template x-if="s.days_old > 30">
                                    <span class="inline-block mt-0.5 px-1.5 py-0.2 rounded text-[9px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300" x-text="s.days_old + ' days ago'"></span>
                                </template>
                            </td>

                            {{-- Actions --}}
                            <td class="p-2.5 text-center">
                                <a :href="s.view_url"
                                   style="color: #ffffff !important;"
                                   class="px-2.5 py-1 rounded-lg text-xs font-bold bg-gradient-to-r from-rose-600 to-rose-700 hover:from-rose-500 hover:to-rose-600 shadow-2xs transition inline-flex items-center gap-1">
                                    <span style="color: #ffffff !important;">💳</span>
                                    <span style="color: #ffffff !important;">{{ __('messages.payables_pay') }}</span>
                                </a>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- 5. Responsive Multi-Column Card Grid (grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4) --}}
    <div x-show="viewMode === 'cards' && filteredSuppliers.length > 0"
         class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2.5">
        <template x-for="s in filteredSuppliers" :key="'card-' + s.id">
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 p-3 shadow-2xs hover:shadow-md transition flex flex-col justify-between space-y-2.5 group">
                {{-- Card Header --}}
                <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="shrink-0 w-8 h-8 rounded-lg bg-gradient-to-br from-rose-500 to-amber-500 text-white grid place-items-center font-black text-sm select-none shadow-2xs"
                              x-text="s.name.charAt(0).toUpperCase()"></span>
                        <div class="min-w-0">
                            <a :href="s.view_url" class="font-bold text-xs text-slate-900 dark:text-slate-100 hover:text-rose-600 dark:hover:text-rose-400 truncate block" x-text="s.name"></a>
                            <span class="block text-[10px] text-slate-400 truncate" x-text="s.contact_person ? s.contact_person : 'No Contact'"></span>
                        </div>
                    </div>

                    {{-- Unpaid POs Pill --}}
                    <span class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-bold font-mono bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 border border-sky-200 dark:border-sky-800"
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
                <div class="p-2 rounded-lg bg-rose-50/60 dark:bg-rose-950/30 border border-rose-100 dark:border-rose-900/50 flex items-center justify-between">
                    <div>
                        <span class="text-[9px] uppercase font-bold text-rose-500 block">{{ __('messages.payables_outstanding') }}</span>
                        <span class="text-sm font-black font-mono text-rose-600 dark:text-rose-400" x-text="'Ks ' + Number(s.balance).toLocaleString()"></span>
                    </div>
                    <div class="text-right">
                        <span class="text-[9px] uppercase font-bold text-slate-400 block">{{ __('messages.payables_oldest') }}</span>
                        <span class="text-[10px] font-mono font-bold text-slate-700 dark:text-slate-300" x-text="s.oldest_date"></span>
                    </div>
                </div>

                {{-- Action Button --}}
                <div>
                    <a :href="s.view_url"
                       class="w-full py-1.5 rounded-lg text-xs font-bold text-white bg-gradient-to-r from-rose-600 to-rose-700 hover:from-rose-500 hover:to-rose-600 shadow-2xs transition flex items-center justify-center gap-1.5">
                        <span>💳</span>
                        <span>{{ __('messages.payables_view_details') }}</span>
                    </a>
                </div>
            </div>
        </template>
    </div>
</div>
@endsection