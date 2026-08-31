@extends('layouts.admin.app')

@section('title', __('messages.pilot_import') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2 sm:p-3 md:p-4')

@php
    $storeRouteParams = ['store_slug' => $store->slug];
    $isDemoAllowed = app()->environment(['local', 'testing', 'uat']) || (bool) config('app.show_quick_login');
@endphp

@section('content')
<div class="w-full space-y-2.5 sm:space-y-3 pb-8"
     x-data="{
        cleanModalOpen: false,
        selectedScenario: '{{ $store->business_type === 'agriculture_inputs' ? 'diamond-stone-agri' : 'mobile-accessories' }}'
     }">

    {{-- 1. Compact Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-3 sm:p-4 shadow-sm">
        <div class="flex items-center gap-3 min-w-0">
            <span class="w-10 h-10 rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300 grid place-items-center text-lg font-bold shrink-0 shadow-sm">
                📥
            </span>
            <div class="min-w-0">
                <h1 class="text-base sm:text-lg font-bold text-slate-900 dark:text-slate-100 truncate">
                    {{ __('messages.pilot_import_title') }}
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                    {{ __('messages.pilot_import_subtitle') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('store.admin.products.import', $storeRouteParams) }}"
               class="h-9 px-3 rounded-xl text-xs font-semibold bg-violet-50 hover:bg-violet-100 dark:bg-violet-950/50 dark:hover:bg-violet-900/50 text-violet-700 dark:text-violet-300 border border-violet-200 dark:border-violet-800 transition inline-flex items-center gap-1.5">
                <span>📊</span>
                <span>{{ __('messages.pilot_import_btn_excel') }}</span>
            </a>
            <a href="{{ route('store.admin.backups.index', $storeRouteParams) }}"
               class="h-9 px-3 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 transition inline-flex items-center gap-1.5">
                <span>💾</span>
                <span>{{ __('messages.backups') }}</span>
            </a>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if ($errors->any())
        <div class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-xs font-medium text-rose-800 dark:text-rose-200 space-y-1">
            @foreach ($errors->all() as $error)
                <div class="flex items-center gap-1.5">
                    <span>⚠️</span>
                    <span>{{ $error }}</span>
                </div>
            @endforeach
        </div>
    @endif

    @if (session('success'))
        <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs font-medium text-emerald-800 dark:text-emerald-200 flex items-center gap-2">
            <span class="w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 grid place-items-center text-xs font-bold">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- 2. 4 Key Stat KPI Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3">
        {{-- Total Products --}}
        <div class="rounded-xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-3 shadow-sm">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400">
                <span class="text-violet-600 dark:text-violet-400">{{ __('messages.pilot_import_kpi_products') }}</span>
                <span>📦</span>
            </div>
            <div class="mt-1 text-xl sm:text-2xl font-black font-mono tracking-tight text-violet-600 dark:text-violet-400 tabular-nums">
                {{ number_format($stats['products'] ?? 0) }}
            </div>
            <p class="text-[11px] text-slate-400 truncate">{{ __('messages.pilot_import_kpi_products_sub') }}</p>
        </div>

        {{-- Categories & Brands --}}
        <div class="rounded-xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-3 shadow-sm">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400">
                <span class="text-emerald-600 dark:text-emerald-400">{{ __('messages.pilot_import_kpi_categories_brands') }}</span>
                <span>🏷️</span>
            </div>
            <div class="mt-1 text-xl sm:text-2xl font-black font-mono tracking-tight text-emerald-600 dark:text-emerald-400 tabular-nums">
                {{ number_format($stats['categories'] ?? 0) }} / {{ number_format($stats['brands'] ?? 0) }}
            </div>
            <p class="text-[11px] text-slate-400 truncate">{{ __('messages.pilot_import_kpi_categories_brands_sub') }}</p>
        </div>

        {{-- Suppliers --}}
        <div class="rounded-xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-3 shadow-sm">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400">
                <span class="text-amber-600 dark:text-amber-400">{{ __('messages.pilot_import_kpi_suppliers') }}</span>
                <span>🏢</span>
            </div>
            <div class="mt-1 text-xl sm:text-2xl font-black font-mono tracking-tight text-amber-600 dark:text-amber-400 tabular-nums">
                {{ number_format($stats['suppliers'] ?? 0) }}
            </div>
            <p class="text-[11px] text-slate-400 truncate">{{ __('messages.pilot_import_kpi_suppliers_sub') }}</p>
        </div>

        {{-- Customers --}}
        <div class="rounded-xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-3 shadow-sm">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400">
                <span class="text-sky-600 dark:text-sky-400">{{ __('messages.pilot_import_kpi_customers') }}</span>
                <span>👥</span>
            </div>
            <div class="mt-1 text-xl sm:text-2xl font-black font-mono tracking-tight text-sky-600 dark:text-sky-400 tabular-nums">
                {{ number_format($stats['customers'] ?? 0) }}
            </div>
            <p class="text-[11px] text-slate-400 truncate">{{ __('messages.pilot_import_kpi_customers_sub') }}</p>
        </div>
    </div>

    {{-- 3. Dedicated Excel Import Link Info Banner --}}
    <div class="rounded-2xl bg-gradient-to-r from-violet-50 via-slate-50 to-indigo-50 dark:from-slate-900 dark:via-slate-900 dark:to-violet-950/40 border border-violet-200/80 dark:border-violet-900/60 p-3.5 sm:p-4 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-start sm:items-center gap-3">
            <span class="w-9 h-9 rounded-xl bg-violet-600 text-white grid place-items-center text-base shrink-0 shadow-sm">
                📊
            </span>
            <div>
                <h4 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-100">
                    {{ __('messages.pilot_import_banner_title') }}
                </h4>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                    {{ __('messages.pilot_import_banner_desc') }}
                </p>
            </div>
        </div>
        <a href="{{ route('store.admin.products.import', $storeRouteParams) }}"
           class="px-4 py-2 rounded-xl bg-violet-600 hover:bg-violet-700 text-white font-bold text-xs shadow-sm transition inline-flex items-center justify-center gap-1.5 shrink-0 active:scale-95">
            <span>{{ __('messages.pilot_import_banner_btn') }}</span>
            <span>→</span>
        </a>
    </div>

    {{-- 4. Myanmar SME Pilot Presets Seeder Section --}}
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    <span>🌱</span>
                    <span>{{ __('messages.pilot_import_seed_title') }}</span>
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    {{ __('messages.pilot_import_seed_desc') }}
                </p>
            </div>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60 w-fit">
                <span>✓ {{ __('messages.pilot_import_live_test_ready') }}</span>
            </span>
        </div>

        {{-- Preset Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach ($demoScenarios as $key => $scenario)
                <div class="rounded-xl border border-slate-200 dark:border-slate-800 p-3.5 flex flex-col justify-between space-y-3 hover:border-violet-300 dark:hover:border-violet-700 transition bg-slate-50/50 dark:bg-slate-800/30">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-900 dark:text-slate-100">{{ $scenario['label'] }}</span>
                            <div class="flex flex-col items-end gap-1">
                                <span class="text-[9px] font-bold uppercase px-2 py-0.5 rounded-md bg-violet-100 dark:bg-violet-950 text-violet-700 dark:text-violet-300 font-mono max-w-32 truncate" title="{{ $key }}">
                                    {{ $key }}
                                </span>
                                <span class="text-[9px] font-bold px-2 py-0.5 rounded-full {{ $scenario['readiness'] === 'Core-ready' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300' }}">
                                    {{ $scenario['readiness'] }}
                                </span>
                            </div>
                        </div>
                        <div class="text-xs font-semibold text-violet-600 dark:text-violet-400 mt-1">{{ $scenario['subtitle'] }}</div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed">
                            {{ $scenario['description'] }}
                        </p>
                        @if ($scenario['limitation'])
                            <p class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-2 text-[10px] font-semibold leading-relaxed text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300">
                                ⚠ {{ $scenario['limitation'] }}
                            </p>
                        @endif
                        <div class="mt-3 grid grid-cols-3 gap-1.5 text-center">
                            <div class="rounded-lg border border-slate-200 bg-white px-1.5 py-2 dark:border-slate-700 dark:bg-slate-900">
                                <div class="text-sm font-black text-slate-900 dark:text-white">32</div>
                                <div class="text-[9px] font-bold text-slate-400">{{ __('messages.pilot_import_stat_products') }}</div>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white px-1.5 py-2 dark:border-slate-700 dark:bg-slate-900">
                                <div class="text-sm font-black text-amber-600">6</div>
                                <div class="text-[9px] font-bold text-slate-400">{{ __('messages.pilot_import_stat_featured') }}</div>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white px-1.5 py-2 dark:border-slate-700 dark:bg-slate-900">
                                <div class="text-sm font-black text-rose-600">6+</div>
                                <div class="text-[9px] font-bold text-slate-400">{{ __('messages.pilot_import_stat_promos') }}</div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('store.admin.pilot-import.seed-store', $storeRouteParams) }}" class="pt-2 border-t border-slate-200/60 dark:border-slate-700/60 space-y-2">
                        @csrf
                        <input type="hidden" name="scenario" value="{{ $key }}">

                        <label class="flex items-center gap-2 text-[11px] text-slate-600 dark:text-slate-300 cursor-pointer">
                            <input type="checkbox" name="clean_old" value="1" class="rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                            <span>{{ __('messages.pilot_import_clean_old_label') }}</span>
                        </label>

                        <label class="flex items-start gap-2 text-[11px] text-slate-600 dark:text-slate-300 cursor-pointer">
                            <input type="checkbox" name="apply_store_identity" value="1" class="mt-0.5 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                            <span>{{ __('messages.pilot_import_apply_identity_label') }}</span>
                        </label>

                        <button type="submit"
                                onclick="return confirm('{{ __('messages.pilot_import_seed_confirm', ['scenario' => $scenario['label'], 'store' => $store->name]) }}')"
                                class="w-full py-2 px-3 rounded-xl bg-violet-600 hover:bg-violet-700 text-white font-bold text-xs shadow-sm transition flex items-center justify-center gap-1.5 cursor-pointer active:scale-95">
                            <span>✨</span>
                            <span>{{ __('messages.pilot_import_btn_seed') }}</span>
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>

    {{-- 5. Quick-Start Demo Store Creator (Local / UAT environment) --}}
    @if ($demoScenariosEnabled)
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                        <span>🏬</span>
                        <span>{{ __('messages.pilot_import_demo_store_title') }}</span>
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        {{ __('messages.pilot_import_demo_store_desc') }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                @foreach ($demoScenarios as $key => $sc)
                    <form method="POST" action="{{ route('store.admin.pilot-import.demo-scenarios.store', array_merge($storeRouteParams, ['scenario' => $key])) }}"
                          class="p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/30 flex items-center justify-between gap-2">
                        @csrf
                        <div class="min-w-0">
                            <div class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">{{ $sc['label'] }}</div>
                            <div class="text-[10px] text-slate-500 dark:text-slate-400 font-mono truncate">store/{{ $key }}</div>
                        </div>
                        <button type="submit"
                                onclick="return confirm('{{ __('messages.pilot_import_demo_store_confirm', ['scenario' => $sc['label']]) }}')"
                                class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-900 dark:bg-slate-700 dark:hover:bg-slate-600 text-white text-xs font-bold shrink-0 transition cursor-pointer active:scale-95">
                            + {{ __('messages.pilot_import_btn_demo_store') }}
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    @endif

    {{-- 6. Danger Zone: Wipe / Clean Test Data --}}
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-rose-200 dark:border-rose-900/60 p-4 sm:p-5 shadow-sm space-y-3">
        <div class="flex items-center gap-2.5 text-rose-700 dark:text-rose-400 font-bold text-sm">
            <span>⚠️</span>
            <span>{{ __('messages.pilot_import_wipe_title') }}</span>
        </div>
        <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
            {{ __('messages.pilot_import_wipe_desc') }}
        </p>

        <form method="POST" action="{{ route('store.admin.pilot-import.clean-store-data', $storeRouteParams) }}">
            @csrf
            <button type="submit"
                    onclick="return confirm('{{ __('messages.pilot_import_wipe_confirm', ['store' => $store->name]) }}')"
                    class="py-2.5 px-4 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-sm transition inline-flex items-center gap-2 cursor-pointer active:scale-95">
                <span>🗑️</span>
                <span>{{ __('messages.pilot_import_btn_wipe') }}</span>
            </button>
        </form>
    </div>

</div>
@endsection
