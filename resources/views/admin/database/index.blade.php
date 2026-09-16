@extends('layouts.admin.app')

@section('title', __('messages.sidebar_database') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@php
    $storeRouteParams = ['store_slug' => $store->slug];
@endphp

@section('content')
<div class="w-full space-y-0.5 pb-6"
     x-data="{
        tableSearch: '',
        tableCategory: 'all',
        matchesTable(name, category) {
            const matchesSearch = !this.tableSearch || name.toLowerCase().includes(this.tableSearch.toLowerCase());
            const matchesCat = this.tableCategory === 'all' || category === this.tableCategory;
            return matchesSearch && matchesCat;
        }
     }">

    {{-- 1. Top Ultra-Dense Header Banner (Standard v4.1) --}}
    <div class="px-2 py-1.5 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 select-none transition">
        <div class="flex items-center gap-2 min-w-0">
            <a href="{{ route('store.admin.dashboard', $storeRouteParams) }}"
               class="h-6 w-6 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 flex items-center justify-center text-slate-500 transition active:scale-95 shrink-0"
               title="{{ __('messages.back') }}">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div class="w-6 h-6 rounded bg-cyan-600 text-white flex items-center justify-center font-bold text-xs shadow-2xs shrink-0">
                <span>🗄️</span>
            </div>
            <div class="flex items-center gap-1.5 min-w-0">
                <span class="text-[10px] font-bold text-cyan-600 dark:text-cyan-400 bg-cyan-50 dark:bg-cyan-950/60 px-1.5 py-0.5 rounded border border-cyan-200/50 dark:border-cyan-800/50 truncate max-w-[120px] sm:max-w-none">
                    {{ $store->name }}
                </span>
                <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white tracking-tight truncate">
                    {{ __('messages.database_title') }}
                </h1>
                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-mono hidden md:inline">
                    · {{ __('messages.database_subtitle') }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-1 sm:gap-1.5 shrink-0 self-end sm:self-auto">
            <a href="{{ route('store.admin.backups.index', $storeRouteParams) }}"
               class="sf-btn-3d h-7 px-2.5 rounded-md text-xs font-semibold inline-flex items-center gap-1 cursor-pointer">
                <span>💾</span>
                <span>{{ __('messages.backups') }}</span>
            </a>

            <a href="{{ route('store.admin.alerts.index', $storeRouteParams) }}"
               class="sf-btn-3d-primary h-7 px-2.5 rounded-md text-xs font-semibold inline-flex items-center gap-1 cursor-pointer">
                <span>🔔</span>
                <span>{{ __('messages.sidebar_alerts') }}</span>
            </a>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if (session('success'))
        <div class="p-2.5 rounded bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs font-medium text-emerald-800 dark:text-emerald-200 flex items-center gap-2">
            <span class="w-4 h-4 rounded-full bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 grid place-items-center text-xs font-bold">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="p-2.5 rounded bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-xs font-medium text-rose-800 dark:text-rose-200 flex items-center gap-2">
            <span class="w-4 h-4 rounded-full bg-rose-100 dark:bg-rose-900/60 text-rose-700 dark:text-rose-300 grid place-items-center text-xs font-bold">⚠️</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- 2. 4 Key Health KPI Cards (Standard v4.1 Centered Row-based) --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-0.5 sm:gap-1 select-none">
        {{-- Database Size --}}
        <div class="rounded border p-1.5 sm:p-2 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800"
             title="{{ __('messages.database_kpi_size') }}">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs shrink-0 border bg-cyan-50 dark:bg-cyan-950/60 text-cyan-600 dark:text-cyan-400 border-cyan-100 dark:border-cyan-900/50">
                💾
            </div>
            <div class="min-w-0 text-left">
                <div class="text-[10px] sm:text-[11px] font-bold truncate text-slate-500 dark:text-slate-400">
                    {{ __('messages.database_kpi_size') }}
                </div>
                <div class="text-xs sm:text-sm font-black text-cyan-600 dark:text-cyan-400 font-mono tracking-tight flex items-center gap-1">
                    <span>{{ $stats['file_size'] }}</span>
                </div>
            </div>
        </div>

        {{-- Total Schema Tables --}}
        <div class="rounded border p-1.5 sm:p-2 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800"
             title="{{ __('messages.database_kpi_tables') }}">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs shrink-0 border bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border-indigo-100 dark:border-indigo-900/50">
                🗄️
            </div>
            <div class="min-w-0 text-left">
                <div class="text-[10px] sm:text-[11px] font-bold truncate text-slate-500 dark:text-slate-400">
                    {{ __('messages.database_kpi_tables') }}
                </div>
                <div class="text-xs sm:text-sm font-black text-indigo-600 dark:text-indigo-400 font-mono tracking-tight flex items-center gap-1">
                    <span>{{ number_format($stats['total_tables']) }}</span>
                </div>
            </div>
        </div>

        {{-- Total Records --}}
        <div class="rounded border p-1.5 sm:p-2 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800"
             title="{{ __('messages.database_kpi_records') }}">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs shrink-0 border bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border-emerald-100 dark:border-emerald-900/50">
                📊
            </div>
            <div class="min-w-0 text-left">
                <div class="text-[10px] sm:text-[11px] font-bold truncate text-slate-500 dark:text-slate-400">
                    {{ __('messages.database_kpi_records') }}
                </div>
                <div class="text-xs sm:text-sm font-black text-emerald-600 dark:text-emerald-400 font-mono tracking-tight flex items-center gap-1">
                    <span>{{ number_format($stats['total_rows']) }}</span>
                </div>
            </div>
        </div>

        {{-- Integrity Health --}}
        <div class="rounded border p-1.5 sm:p-2 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800"
             title="{{ __('messages.database_kpi_integrity') }}">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs shrink-0 border bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border-emerald-100 dark:border-emerald-900/50">
                🛡️
            </div>
            <div class="min-w-0 text-left">
                <div class="text-[10px] sm:text-[11px] font-bold truncate text-slate-500 dark:text-slate-400">
                    {{ __('messages.database_kpi_integrity') }}
                </div>
                <div class="text-xs sm:text-sm font-black text-emerald-600 dark:text-emerald-400">
                    {{ __('messages.database_healthy') }} ✓
                </div>
            </div>
        </div>
    </div>

    {{-- 3. Optimization Action Tools --}}
    <div class="rounded bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-2 sm:p-2.5 shadow-2xs space-y-1.5">
        <div class="border-b border-slate-100 dark:border-slate-800 pb-1.5">
            <h2 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                <span>⚡</span>
                <span>{{ __('messages.database_tools_title') }}</span>
            </h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-0.5 sm:gap-1 pt-0.5">
            {{-- 1. VACUUM --}}
            <div class="rounded border border-slate-200 dark:border-slate-700/80 p-2 bg-slate-50/70 dark:bg-slate-800/40 flex flex-col justify-between space-y-2">
                <div>
                    <div class="flex items-center gap-1 text-cyan-700 dark:text-cyan-300 font-bold text-xs">
                        <span>🧹</span>
                        <span>{{ __('messages.database_vacuum_title') }}</span>
                    </div>
                    <p class="text-[10px] text-slate-600 dark:text-slate-400 mt-0.5 leading-relaxed">
                        {{ __('messages.database_vacuum_desc') }}
                    </p>
                </div>
                <form method="POST" action="{{ route('store.admin.database.vacuum', $storeRouteParams) }}">
                    @csrf
                    <button type="submit"
                            data-confirm="{{ __('messages.database_vacuum_confirm') }}"
                            class="sf-btn-3d-primary w-full h-7 px-2 rounded-md text-xs font-bold inline-flex items-center justify-center cursor-pointer">
                        {{ __('messages.database_btn_vacuum') }}
                    </button>
                </form>
            </div>

            {{-- 2. Re-Index & Analyze --}}
            <div class="rounded border border-slate-200 dark:border-slate-700/80 p-2 bg-slate-50/70 dark:bg-slate-800/40 flex flex-col justify-between space-y-2">
                <div>
                    <div class="flex items-center gap-1 text-indigo-700 dark:text-indigo-300 font-bold text-xs">
                        <span>⚡</span>
                        <span>{{ __('messages.database_optimize_title') }}</span>
                    </div>
                    <p class="text-[10px] text-slate-600 dark:text-slate-400 mt-0.5 leading-relaxed">
                        {{ __('messages.database_optimize_desc') }}
                    </p>
                </div>
                <form method="POST" action="{{ route('store.admin.database.optimize', $storeRouteParams) }}">
                    @csrf
                    <button type="submit"
                            class="sf-btn-3d-primary w-full h-7 px-2 rounded-md text-xs font-bold inline-flex items-center justify-center cursor-pointer">
                        {{ __('messages.database_btn_optimize') }}
                    </button>
                </form>
            </div>

            {{-- 3. Integrity Check --}}
            <div class="rounded border border-slate-200 dark:border-slate-700/80 p-2 bg-slate-50/70 dark:bg-slate-800/40 flex flex-col justify-between space-y-2">
                <div>
                    <div class="flex items-center gap-1 text-emerald-700 dark:text-emerald-300 font-bold text-xs">
                        <span>🔍</span>
                        <span>{{ __('messages.database_integrity_title') }}</span>
                    </div>
                    <p class="text-[10px] text-slate-600 dark:text-slate-400 mt-0.5 leading-relaxed">
                        {{ __('messages.database_integrity_desc') }}
                    </p>
                </div>
                <form method="POST" action="{{ route('store.admin.database.integrity', $storeRouteParams) }}">
                    @csrf
                    <button type="submit"
                            class="sf-btn-3d-success w-full h-7 px-2 rounded-md text-xs font-bold inline-flex items-center justify-center cursor-pointer">
                        {{ __('messages.database_btn_integrity') }}
                    </button>
                </form>
            </div>

            {{-- 4. Purge Cache --}}
            <div class="rounded border border-slate-200 dark:border-slate-700/80 p-2 bg-slate-50/70 dark:bg-slate-800/40 flex flex-col justify-between space-y-2">
                <div>
                    <div class="flex items-center gap-1 text-amber-700 dark:text-amber-300 font-bold text-xs">
                        <span>🗑</span>
                        <span>{{ __('messages.database_clear_cache_title') }}</span>
                    </div>
                    <p class="text-[10px] text-slate-600 dark:text-slate-400 mt-0.5 leading-relaxed">
                        {{ __('messages.database_clear_cache_desc') }}
                    </p>
                </div>
                <form method="POST" action="{{ route('store.admin.database.clear_cache', $storeRouteParams) }}">
                    @csrf
                    <button type="submit"
                            class="sf-btn-3d-gold w-full h-7 px-2 rounded-md text-xs font-bold inline-flex items-center justify-center cursor-pointer">
                        {{ __('messages.database_btn_clear_cache') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- 4. Database Schema Tables Breakdown (with search & category filters) --}}
    <div class="rounded bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-2xs overflow-hidden space-y-0">
        <div class="px-2 py-1.5 border-b border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5">
            <div>
                <h3 class="text-xs font-bold text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                    <span>📋 {{ __('messages.database_tables_title') }}</span>
                </h3>
            </div>

            {{-- Table Search and Category Filter --}}
            <div class="flex items-center gap-1.5">
                <input type="text" x-model="tableSearch" placeholder="{{ __('messages.database_search_placeholder') }}"
                       class="h-7 text-xs border border-slate-300 dark:border-slate-700 rounded px-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-cyan-500 w-40 sm:w-48">

                <select x-model="tableCategory"
                        class="h-7 text-xs border border-slate-300 dark:border-slate-700 rounded px-2 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-cyan-500">
                    <option value="all">{{ __('messages.database_cat_all') }}</option>
                    <option value="Sales & Orders">{{ __('messages.database_cat_sales') }}</option>
                    <option value="Inventory & Catalog">{{ __('messages.database_cat_inventory') }}</option>
                    <option value="Financial & Accounts">{{ __('messages.database_cat_financial') }}</option>
                    <option value="Security & Users">{{ __('messages.database_cat_security') }}</option>
                    <option value="System & Settings">{{ __('messages.database_cat_system') }}</option>
                </select>

                <span class="text-[11px] font-mono font-bold text-slate-500 bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded shrink-0">
                    {{ __('messages.database_table_count', ['count' => count($tables)]) }}
                </span>
            </div>
        </div>

        <div class="overflow-x-auto max-h-[460px] overflow-y-auto">
            <table class="w-full text-left text-xs">
                <thead class="sticky top-0 z-10">
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                        <th class="py-2.5 px-3.5 w-14">{{ __('messages.database_th_no') }}</th>
                        <th class="py-2.5 px-3.5">{{ __('messages.database_th_name') }}</th>
                        <th class="py-2.5 px-3.5">{{ __('messages.database_th_category') }}</th>
                        <th class="py-2.5 px-3.5 text-right">{{ __('messages.database_th_rows') }}</th>
                        <th class="py-2.5 px-3.5 text-center w-28">{{ __('messages.database_th_status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                    @foreach ($tables as $index => $t)
                        <tr x-show="matchesTable('{{ $t['name'] }}', '{{ $t['category'] }}')"
                            class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <td class="py-2 px-3.5 font-mono text-slate-400">{{ $index + 1 }}</td>
                            <td class="py-2 px-3.5 font-mono font-bold text-slate-900 dark:text-slate-100">
                                {{ $t['name'] }}
                            </td>
                            <td class="py-2 px-3.5">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase
                                    @if($t['category'] === 'Sales & Orders') bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300
                                    @elseif($t['category'] === 'Inventory & Catalog') bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300
                                    @elseif($t['category'] === 'Financial & Accounts') bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300
                                    @elseif($t['category'] === 'Security & Users') bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300
                                    @else bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 @endif">
                                    {{ $t['category'] }}
                                </span>
                            </td>
                            <td class="py-2 px-3.5 text-right font-mono font-bold text-slate-900 dark:text-slate-100 tabular-nums">
                                {{ number_format($t['rows']) }}
                            </td>
                            <td class="py-2 px-3.5 text-center">
                                <span class="text-emerald-600 dark:text-emerald-400 font-bold text-[11px] inline-flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    <span>{{ __('messages.database_status_active') }}</span>
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
