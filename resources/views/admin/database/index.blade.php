@extends('layouts.admin.app')

@section('title', __('messages.sidebar_database') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2 sm:p-3 md:p-4')

@php
    $storeRouteParams = ['store_slug' => $store->slug];
@endphp

@section('content')
<div class="w-full space-y-2.5 sm:space-y-3 pb-8"
     x-data="{
        tableSearch: '',
        tableCategory: 'all',
        matchesTable(name, category) {
            const matchesSearch = !this.tableSearch || name.toLowerCase().includes(this.tableSearch.toLowerCase());
            const matchesCat = this.tableCategory === 'all' || category === this.tableCategory;
            return matchesSearch && matchesCat;
        }
     }">

    {{-- 1. Top Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-3 sm:p-4 shadow-sm">
        <div class="flex items-center gap-3 min-w-0">
            <span class="w-10 h-10 rounded-xl bg-cyan-100 text-cyan-700 dark:bg-cyan-500/15 dark:text-cyan-300 grid place-items-center text-lg font-bold shrink-0 shadow-sm">
                🗄️
            </span>
            <div class="min-w-0">
                <h1 class="text-base sm:text-lg font-bold text-slate-900 dark:text-slate-100 truncate">
                    {{ __('messages.database_title') }}
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                    {{ __('messages.database_subtitle') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('store.admin.backups.index', $storeRouteParams) }}"
               class="h-9 px-3 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 transition inline-flex items-center gap-1.5">
                <span>💾</span>
                <span>{{ __('messages.backups') }}</span>
            </a>

            <a href="{{ route('store.admin.alerts.index', $storeRouteParams) }}"
               class="h-9 px-3 rounded-xl text-xs font-semibold bg-cyan-600 hover:bg-cyan-700 text-white shadow-sm transition inline-flex items-center gap-1.5">
                <span>🔔</span>
                <span>{{ __('messages.sidebar_alerts') }}</span>
            </a>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if (session('success'))
        <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs font-medium text-emerald-800 dark:text-emerald-200 flex items-center gap-2">
            <span class="w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 grid place-items-center text-xs font-bold">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-xs font-medium text-rose-800 dark:text-rose-200 flex items-center gap-2">
            <span class="w-5 h-5 rounded-full bg-rose-100 dark:bg-rose-900/60 text-rose-700 dark:text-rose-300 grid place-items-center text-xs font-bold">⚠️</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- 2. 4 Key Health KPI Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3">
        {{-- Database Size --}}
        <div class="rounded-xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-3 shadow-sm">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400">
                <span class="text-cyan-600 dark:text-cyan-400">{{ __('messages.database_kpi_size') }}</span>
                <span>💾</span>
            </div>
            <div class="mt-1 text-xl sm:text-2xl font-black font-mono tracking-tight text-cyan-600 dark:text-cyan-400 tabular-nums">
                {{ $stats['file_size'] }}
            </div>
            <p class="text-[11px] text-slate-400 font-mono truncate">{{ $stats['driver'] }} {{ __('messages.database_storage_engine') }}</p>
        </div>

        {{-- Total Schema Tables --}}
        <div class="rounded-xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-3 shadow-sm">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400">
                <span class="text-indigo-600 dark:text-indigo-400">{{ __('messages.database_kpi_tables') }}</span>
                <span>🗄️</span>
            </div>
            <div class="mt-1 text-xl sm:text-2xl font-black font-mono tracking-tight text-indigo-600 dark:text-indigo-400 tabular-nums">
                {{ number_format($stats['total_tables']) }}
            </div>
            <p class="text-[11px] text-slate-400 truncate">{{ __('messages.database_kpi_tables_sub') }}</p>
        </div>

        {{-- Total Records --}}
        <div class="rounded-xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-3 shadow-sm">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400">
                <span class="text-emerald-600 dark:text-emerald-400">{{ __('messages.database_kpi_records') }}</span>
                <span>📊</span>
            </div>
            <div class="mt-1 text-xl sm:text-2xl font-black font-mono tracking-tight text-emerald-600 dark:text-emerald-400 tabular-nums">
                {{ number_format($stats['total_rows']) }}
            </div>
            <p class="text-[11px] text-slate-400 truncate">{{ __('messages.database_kpi_records_sub') }}</p>
        </div>

        {{-- Integrity Health --}}
        <div class="rounded-xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-3 shadow-sm">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400">
                <span class="text-emerald-600 dark:text-emerald-400">{{ __('messages.database_kpi_integrity') }}</span>
                <span>🛡️</span>
            </div>
            <div class="mt-1 text-xl sm:text-2xl font-black font-mono tracking-tight text-emerald-600 dark:text-emerald-400">
                {{ __('messages.database_healthy') }} ✓
            </div>
            <p class="text-[11px] text-slate-400 truncate font-mono">{{ $stats['integrity_status'] }} {{ __('messages.database_verified') }}</p>
        </div>
    </div>

    {{-- 3. Optimization Action Tools --}}
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm space-y-3.5">
        <div class="border-b border-slate-100 dark:border-slate-800 pb-2.5">
            <h2 class="text-sm sm:text-base font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                <span>⚡</span>
                <span>{{ __('messages.database_tools_title') }}</span>
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                {{ __('messages.database_tools_sub') }}
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-1">
            {{-- 1. VACUUM --}}
            <div class="rounded-xl border border-slate-200 dark:border-slate-700/80 p-3.5 bg-slate-50/70 dark:bg-slate-800/40 flex flex-col justify-between space-y-3">
                <div>
                    <div class="flex items-center gap-1.5 text-cyan-700 dark:text-cyan-300 font-bold text-xs">
                        <span>🧹</span>
                        <span>{{ __('messages.database_vacuum_title') }}</span>
                    </div>
                    <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">
                        {{ __('messages.database_vacuum_desc') }}
                    </p>
                </div>
                <form method="POST" action="{{ route('store.admin.database.vacuum', $storeRouteParams) }}">
                    @csrf
                    <button type="submit"
                            onclick="return confirm('{{ __('messages.database_vacuum_confirm') }}')"
                            class="w-full py-2 px-3 rounded-lg text-xs font-bold bg-cyan-600 hover:bg-cyan-500 text-white shadow-sm transition active:scale-95 text-center cursor-pointer">
                        {{ __('messages.database_btn_vacuum') }}
                    </button>
                </form>
            </div>

            {{-- 2. Re-Index & Analyze --}}
            <div class="rounded-xl border border-slate-200 dark:border-slate-700/80 p-3.5 bg-slate-50/70 dark:bg-slate-800/40 flex flex-col justify-between space-y-3">
                <div>
                    <div class="flex items-center gap-1.5 text-indigo-700 dark:text-indigo-300 font-bold text-xs">
                        <span>⚡</span>
                        <span>{{ __('messages.database_optimize_title') }}</span>
                    </div>
                    <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">
                        {{ __('messages.database_optimize_desc') }}
                    </p>
                </div>
                <form method="POST" action="{{ route('store.admin.database.optimize', $storeRouteParams) }}">
                    @csrf
                    <button type="submit"
                            class="w-full py-2 px-3 rounded-lg text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow-sm transition active:scale-95 text-center cursor-pointer">
                        {{ __('messages.database_btn_optimize') }}
                    </button>
                </form>
            </div>

            {{-- 3. Integrity Check --}}
            <div class="rounded-xl border border-slate-200 dark:border-slate-700/80 p-3.5 bg-slate-50/70 dark:bg-slate-800/40 flex flex-col justify-between space-y-3">
                <div>
                    <div class="flex items-center gap-1.5 text-emerald-700 dark:text-emerald-300 font-bold text-xs">
                        <span>🔍</span>
                        <span>{{ __('messages.database_integrity_title') }}</span>
                    </div>
                    <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">
                        {{ __('messages.database_integrity_desc') }}
                    </p>
                </div>
                <form method="POST" action="{{ route('store.admin.database.integrity', $storeRouteParams) }}">
                    @csrf
                    <button type="submit"
                            class="w-full py-2 px-3 rounded-lg text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-sm transition active:scale-95 text-center cursor-pointer">
                        {{ __('messages.database_btn_integrity') }}
                    </button>
                </form>
            </div>

            {{-- 4. Purge Cache --}}
            <div class="rounded-xl border border-slate-200 dark:border-slate-700/80 p-3.5 bg-slate-50/70 dark:bg-slate-800/40 flex flex-col justify-between space-y-3">
                <div>
                    <div class="flex items-center gap-1.5 text-amber-700 dark:text-amber-300 font-bold text-xs">
                        <span>🗑</span>
                        <span>{{ __('messages.database_clear_cache_title') }}</span>
                    </div>
                    <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">
                        {{ __('messages.database_clear_cache_desc') }}
                    </p>
                </div>
                <form method="POST" action="{{ route('store.admin.database.clear_cache', $storeRouteParams) }}">
                    @csrf
                    <button type="submit"
                            class="w-full py-2 px-3 rounded-lg text-xs font-bold bg-amber-600 hover:bg-amber-500 text-white shadow-sm transition active:scale-95 text-center cursor-pointer">
                        {{ __('messages.database_btn_clear_cache') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- 4. Database Schema Tables Breakdown (with search & category filters) --}}
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden space-y-0">
        <div class="p-3 sm:p-4 border-b border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    <span>📋 {{ __('messages.database_tables_title') }}</span>
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">{{ __('messages.database_tables_sub') }}</p>
            </div>

            {{-- Table Search and Category Filter --}}
            <div class="flex items-center gap-2">
                <input type="text" x-model="tableSearch" placeholder="{{ __('messages.database_search_placeholder') }}"
                       class="text-xs border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-1.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-cyan-500 w-44">

                <select x-model="tableCategory"
                        class="text-xs border border-slate-300 dark:border-slate-700 rounded-xl px-2.5 py-1.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-cyan-500">
                    <option value="all">{{ __('messages.database_cat_all') }}</option>
                    <option value="Sales & Orders">{{ __('messages.database_cat_sales') }}</option>
                    <option value="Inventory & Catalog">{{ __('messages.database_cat_inventory') }}</option>
                    <option value="Financial & Accounts">{{ __('messages.database_cat_financial') }}</option>
                    <option value="Security & Users">{{ __('messages.database_cat_security') }}</option>
                    <option value="System & Settings">{{ __('messages.database_cat_system') }}</option>
                </select>

                <span class="text-xs font-mono font-bold text-slate-400 bg-slate-100 dark:bg-slate-800 px-2.5 py-1.5 rounded-xl shrink-0">
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
