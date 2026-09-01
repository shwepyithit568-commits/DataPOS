@extends('layouts.admin.app')

@section('title', __('messages.pilot_import') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@php
    $storeRouteParams = ['store_slug' => $store->slug];
    $isDemoAllowed = app()->environment(['local', 'testing', 'uat']) || (bool) config('app.show_quick_login');
    $currentTab = $tab ?? 'scenarios';

    // Determine current store's matched business scenario
    $matchedScenarioKey = match ($store->business_type) {
        'mobile_sale_service' => 'mobile-sale-service',
        'mobile_accessories', 'mobile_phone' => 'mobile-accessories',
        'cctv_network_computer' => 'cctv-network-computer',
        'pharmacy' => 'pharmacy',
        'restaurant', 'food_dining' => 'restaurant',
        'agriculture_inputs' => 'diamond-stone-agri',
        'general_retail', 'fashion_retail' => 'general-retail',
        'tailoring_fashion', 'fashion_tailoring' => 'kl-fashion',
        default => 'mobile-sale-service',
    };

    // If store slug explicitly hints scenario
    if (str_contains($store->slug, 'kl-fashion') || str_contains($store->slug, 'tailor')) {
        $matchedScenarioKey = 'kl-fashion';
    } elseif (str_contains($store->slug, 'cctv') || str_contains($store->slug, 'computer')) {
        $matchedScenarioKey = 'cctv-network-computer';
    } elseif (str_contains($store->slug, 'mobile') || str_contains($store->slug, 'phone')) {
        $matchedScenarioKey = 'mobile-sale-service';
    }

    $spotlightScenario = $demoScenarios[$matchedScenarioKey] ?? ($demoScenarios['mobile-sale-service'] ?? reset($demoScenarios));
@endphp

@section('content')
<div class="w-full space-y-0.5 pb-6">

    {{-- 1. Compact Header & Action Bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 bg-white dark:bg-slate-900 px-3 py-2 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-8 h-8 rounded-lg bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300 grid place-items-center text-base font-bold shrink-0 shadow-2xs">
                📥
            </span>
            <div class="min-w-0">
                <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 truncate flex items-center gap-1.5">
                    <span>{{ __('messages.pilot_import_title') }}</span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-violet-50 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 border border-violet-200/60 dark:border-violet-800/60 hidden sm:inline-block">
                        {{ $store->name }}
                    </span>
                </h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                    {{ __('messages.pilot_import_subtitle') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-1.5 shrink-0">
            <a href="{{ route('store.admin.products.master-data', array_merge($storeRouteParams, ['tab' => 'seed-data'])) }}"
               class="h-7 px-2.5 rounded-md text-[11px] font-bold bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/50 dark:hover:bg-amber-900/50 text-amber-700 dark:text-amber-300 border border-amber-200/80 dark:border-amber-800/80 transition inline-flex items-center gap-1">
                <span>⚙️</span>
                <span>Master Data Seed</span>
            </a>
            <a href="{{ route('store.admin.products.import', $storeRouteParams) }}"
               class="h-7 px-2.5 rounded-md text-[11px] font-bold bg-violet-50 hover:bg-violet-100 dark:bg-violet-950/50 dark:hover:bg-violet-900/50 text-violet-700 dark:text-violet-300 border border-violet-200/80 dark:border-violet-800/80 transition inline-flex items-center gap-1">
                <span>📊</span>
                <span>{{ __('messages.pilot_import_btn_excel') }}</span>
            </a>
            <a href="{{ route('store.admin.backups.index', $storeRouteParams) }}"
               class="h-7 px-2.5 rounded-md text-[11px] font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200/80 dark:border-slate-700 transition inline-flex items-center gap-1">
                <span>💾</span>
                <span>{{ __('messages.backups') }}</span>
            </a>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if ($errors->any())
        <div class="p-2.5 rounded-lg bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-xs font-medium text-rose-800 dark:text-rose-200 space-y-1">
            @foreach ($errors->all() as $error)
                <div class="flex items-center gap-1.5">
                    <span>⚠️</span>
                    <span>{{ $error }}</span>
                </div>
            @endforeach
        </div>
    @endif

    @if (session('success'))
        <div class="p-2.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs font-semibold text-emerald-800 dark:text-emerald-200 flex items-center gap-2">
            <span class="w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 grid place-items-center text-xs font-bold shrink-0">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Centered 4 Key Stat Cards --}}
    <div class="overflow-x-auto scrollbar-thin pb-1">
        <div class="flex sm:grid sm:grid-cols-4 gap-0.5 sm:gap-1 min-w-[700px] sm:min-w-0">
            {{-- Products --}}
            <div class="flex-1 min-w-[170px] bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 px-3 py-2 rounded-lg shadow-2xs">
                <div class="flex items-center justify-center gap-2.5 sm:gap-3">
                    <span class="w-8 h-8 rounded-lg bg-violet-50 text-violet-600 dark:bg-violet-950/60 dark:text-violet-400 grid place-items-center text-base shrink-0 shadow-2xs">📦</span>
                    <div class="text-left">
                        <div class="text-base sm:text-lg font-black font-mono tracking-tight text-violet-600 dark:text-violet-400 tabular-nums leading-none">
                            {{ number_format($stats['products'] ?? 0) }}
                        </div>
                        <div class="text-[10px] font-bold text-slate-500 dark:text-slate-400 mt-0.5 leading-tight">{{ __('messages.pilot_import_kpi_products') }}</div>
                    </div>
                </div>
            </div>

            {{-- Categories / Brands --}}
            <div class="flex-1 min-w-[170px] bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 px-3 py-2 rounded-lg shadow-2xs">
                <div class="flex items-center justify-center gap-2.5 sm:gap-3">
                    <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400 grid place-items-center text-base shrink-0 shadow-2xs">🏷️</span>
                    <div class="text-left">
                        <div class="text-base sm:text-lg font-black font-mono tracking-tight text-emerald-600 dark:text-emerald-400 tabular-nums leading-none">
                            {{ number_format($stats['categories'] ?? 0) }} / {{ number_format($stats['brands'] ?? 0) }}
                        </div>
                        <div class="text-[10px] font-bold text-slate-500 dark:text-slate-400 mt-0.5 leading-tight">{{ __('messages.pilot_import_kpi_categories_brands') }}</div>
                    </div>
                </div>
            </div>

            {{-- Suppliers --}}
            <div class="flex-1 min-w-[170px] bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 px-3 py-2 rounded-lg shadow-2xs">
                <div class="flex items-center justify-center gap-2.5 sm:gap-3">
                    <span class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400 grid place-items-center text-base shrink-0 shadow-2xs">🏢</span>
                    <div class="text-left">
                        <div class="text-base sm:text-lg font-black font-mono tracking-tight text-amber-600 dark:text-amber-400 tabular-nums leading-none">
                            {{ number_format($stats['suppliers'] ?? 0) }}
                        </div>
                        <div class="text-[10px] font-bold text-slate-500 dark:text-slate-400 mt-0.5 leading-tight">{{ __('messages.pilot_import_kpi_suppliers') }}</div>
                    </div>
                </div>
            </div>

            {{-- Customers --}}
            <div class="flex-1 min-w-[170px] bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 px-3 py-2 rounded-lg shadow-2xs">
                <div class="flex items-center justify-center gap-2.5 sm:gap-3">
                    <span class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 grid place-items-center text-base shrink-0 shadow-2xs">👥</span>
                    <div class="text-left">
                        <div class="text-base sm:text-lg font-black font-mono tracking-tight text-sky-600 dark:text-sky-400 tabular-nums leading-none">
                            {{ number_format($stats['customers'] ?? 0) }}
                        </div>
                        <div class="text-[10px] font-bold text-slate-500 dark:text-slate-400 mt-0.5 leading-tight">{{ __('messages.pilot_import_kpi_customers') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($currentTab === 'scenarios')
        {{-- ========================================================================= --}}
        {{-- TAB: SCENARIOS & PRESETS SEEDER                                           --}}
        {{-- ========================================================================= --}}

        {{-- Dynamic Featured Spotlight Banner (Tailored to Current Store) --}}
        <div class="rounded-xl bg-white dark:bg-slate-900 border border-violet-200/90 dark:border-slate-800 p-3.5 sm:p-4 shadow-2xs relative overflow-hidden transition-colors">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 relative z-10">
                <div class="space-y-1.5 max-w-2xl">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="px-2 py-0.5 rounded-full bg-violet-100 dark:bg-violet-950/70 text-violet-800 dark:text-violet-300 text-[10px] font-black uppercase tracking-wider border border-violet-200 dark:border-violet-800/60">
                            ✨ Recommended For Current Store
                        </span>
                        <span class="px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/70 text-emerald-800 dark:text-emerald-300 text-[10px] font-bold border border-emerald-200 dark:border-emerald-800/80">
                            {{ $spotlightScenario['readiness'] ?? 'Core-ready' }}
                        </span>
                    </div>

                    <h2 class="text-sm sm:text-base font-black tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                        <span>
                            @if (str_contains($matchedScenarioKey, 'mobile')) 📱 🔌 🛠️ 💎
                            @elseif (str_contains($matchedScenarioKey, 'fashion')) 👗 ✂️ 🧵 🪡
                            @elseif (str_contains($matchedScenarioKey, 'cctv')) 📹 🌐 💻 🛠️
                            @else 🏬 📦 ✨
                            @endif
                        </span>
                        <span>{{ $spotlightScenario['label'] ?? 'Business Demo Scenario' }}</span>
                    </h2>

                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                        {{ $spotlightScenario['subtitle'] ?? '' }} — {{ $spotlightScenario['description'] ?? '' }}
                    </p>

                    <div class="flex flex-wrap gap-1.5 text-[11px] text-slate-700 dark:text-slate-200 pt-0.5">
                        @if (str_contains($matchedScenarioKey, 'mobile'))
                            <span class="px-2 py-0.5 rounded-md bg-slate-50 dark:bg-slate-800/90 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700/60">📱 စမတ်ဖုန်းများ (iPhone, Galaxy, Xiaomi)</span>
                            <span class="px-2 py-0.5 rounded-md bg-slate-50 dark:bg-slate-800/90 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700/60">🔌 ကြိုး/ခေါင်း/ပါဝါဘဏ် (Anker, Joyroom)</span>
                            <span class="px-2 py-0.5 rounded-md bg-slate-50 dark:bg-slate-800/90 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700/60">🛠️ ပြုပြင်ရေး ဝန်ဆောင်မှု & Spare Parts</span>
                            <span class="px-2 py-0.5 rounded-md bg-slate-50 dark:bg-slate-800/90 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700/60">💎 Digital Codes / PIN / Top-up</span>
                            <span class="px-2 py-0.5 rounded-md bg-slate-50 dark:bg-slate-800/90 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700/60">📦 Variant Matrix (Stock: 10 ခုစီ)</span>
                        @elseif (str_contains($matchedScenarioKey, 'fashion'))
                            <span class="px-2 py-0.5 rounded-md bg-slate-50 dark:bg-slate-800/90 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700/60">✂️ စက်ချုပ်ခ/မင်္ဂလာဝတ်စုံ</span>
                            <span class="px-2 py-0.5 rounded-md bg-slate-50 dark:bg-slate-800/90 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700/60">👗 ဂါဝန်/ရှပ်/ကုတ်/လုံချည်</span>
                            <span class="px-2 py-0.5 rounded-md bg-slate-50 dark:bg-slate-800/90 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700/60">🧵 ပိုး/ချည်/လင်နင် ပိတ်စ</span>
                            <span class="px-2 py-0.5 rounded-md bg-slate-50 dark:bg-slate-800/90 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700/60">🪡 အပ်ချည်/ဇစ်/ကြယ်သီး/စက်အပ်</span>
                        @elseif (str_contains($matchedScenarioKey, 'cctv'))
                            <span class="px-2 py-0.5 rounded-md bg-slate-50 dark:bg-slate-800/90 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700/60">📹 CCTV Cameras & NVR</span>
                            <span class="px-2 py-0.5 rounded-md bg-slate-50 dark:bg-slate-800/90 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700/60">🌐 Routers, Switches, Cat6</span>
                            <span class="px-2 py-0.5 rounded-md bg-slate-50 dark:bg-slate-800/90 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700/60">💻 Laptops, Desktop PC, SSD</span>
                            <span class="px-2 py-0.5 rounded-md bg-slate-50 dark:bg-slate-800/90 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700/60">🛠️ တပ်ဆင်ရေး Package ဝန်ဆောင်မှု</span>
                        @else
                            <span class="px-2 py-0.5 rounded-md bg-slate-50 dark:bg-slate-800/90 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700/60">📦 Complete Product Catalog</span>
                            <span class="px-2 py-0.5 rounded-md bg-slate-50 dark:bg-slate-800/90 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700/60">🏷️ Categorized Stock Ledger</span>
                        @endif
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row lg:flex-col gap-2 shrink-0">
                    <form method="POST" action="{{ route('store.admin.pilot-import.seed-store', $storeRouteParams) }}">
                        @csrf
                        <input type="hidden" name="scenario" value="{{ $matchedScenarioKey }}">
                        <input type="hidden" name="clean_old" value="1">
                        <input type="hidden" name="apply_store_identity" value="1">
                        <button type="submit"
                                onclick="return confirm('{{ __('messages.pilot_import_seed_confirm', ['scenario' => $spotlightScenario['label'], 'store' => $store->name]) }}')"
                                class="w-full h-9 px-4 rounded-lg bg-violet-600 hover:bg-violet-700 text-white font-black text-xs shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer active:scale-95">
                            <span>✨</span>
                            <span>{{ __('messages.pilot_import_btn_seed') }} (Clean Reset)</span>
                        </button>
                    </form>

                    @if ($demoScenariosEnabled)
                        <form method="POST" action="{{ route('store.admin.pilot-import.demo-scenarios.store', array_merge($storeRouteParams, ['scenario' => $matchedScenarioKey])) }}">
                            @csrf
                            <button type="submit"
                                    onclick="return confirm('{{ __('messages.pilot_import_demo_store_confirm', ['scenario' => $spotlightScenario['label']]) }}')"
                                    class="w-full h-9 px-4 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs border border-slate-200 dark:border-slate-700 transition flex items-center justify-center gap-1.5 cursor-pointer active:scale-95">
                                <span>🏬</span>
                                <span>{{ __('messages.pilot_import_btn_demo_store') }}</span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- 9 SME Presets Grid --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-3 shadow-2xs space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 border-b border-slate-100 dark:border-slate-800 pb-2">
                <div>
                    <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                        <span>🌱</span>
                        <span>{{ __('messages.pilot_import_seed_title') }}</span>
                    </h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                        {{ __('messages.pilot_import_seed_desc') }}
                    </p>
                </div>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60 w-fit">
                    <span>✓ {{ __('messages.pilot_import_live_test_ready') }}</span>
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2.5">
                @foreach ($demoScenarios as $key => $scenario)
                    @php
                        $isMatched = ($key === $matchedScenarioKey);
                        $isMobileScenario = str_contains($key, 'mobile');
                        $prodCount = $isMobileScenario ? 100 : 32;
                        $featCount = $isMobileScenario ? 25 : 6;
                        $promoCount = $isMobileScenario ? '35' : '6+';
                    @endphp
                    <div class="rounded-xl border {{ $isMatched ? 'border-violet-500 dark:border-violet-500 bg-violet-50/40 dark:bg-violet-950/20 ring-2 ring-violet-500/30 shadow-xs' : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30' }} p-3 flex flex-col justify-between space-y-2.5 hover:border-violet-300 dark:hover:border-violet-700 transition">
                        <div>
                            <div class="flex items-start justify-between gap-1.5">
                                <div class="min-w-0">
                                    <div class="text-xs font-black text-slate-900 dark:text-slate-100 flex items-center gap-1">
                                        <span class="truncate">{{ $scenario['label'] }}</span>
                                    </div>
                                    <div class="text-[11px] font-semibold text-violet-600 dark:text-violet-400 mt-0.5 truncate">{{ $scenario['subtitle'] }}</div>
                                </div>
                                <div class="flex flex-col items-end gap-1 shrink-0">
                                    <span class="text-[9px] font-bold uppercase px-1.5 py-0.5 rounded bg-violet-100 dark:bg-violet-950 text-violet-700 dark:text-violet-300 font-mono">
                                        {{ $key }}
                                    </span>
                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full {{ $scenario['readiness'] === 'Core-ready' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300' }}">
                                        {{ $scenario['readiness'] }}
                                    </span>
                                </div>
                            </div>

                            @if ($isMatched)
                                <div class="mt-1.5 inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-violet-100 dark:bg-violet-900/50 text-violet-800 dark:text-violet-200 text-[10px] font-black border border-violet-200 dark:border-violet-800">
                                    <span>✓</span>
                                    <span>{{ __('messages.pilot_import_current_scenario_badge') }}</span>
                                </div>
                            @endif

                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed line-clamp-2" title="{{ $scenario['description'] }}">
                                {{ $scenario['description'] }}
                            </p>

                            @if ($scenario['limitation'])
                                <p class="mt-1.5 rounded-md border border-amber-200 bg-amber-50 px-2 py-1 text-[10px] font-semibold leading-relaxed text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300">
                                    ⚠ {{ $scenario['limitation'] }}
                                </p>
                            @endif

                            {{-- Dynamic Stat Pills --}}
                            <div class="mt-2.5 grid grid-cols-3 gap-1 text-center">
                                <div class="rounded-lg border border-slate-200/80 bg-white px-1.5 py-1.5 dark:border-slate-700 dark:bg-slate-900">
                                    <div class="text-xs sm:text-sm font-black text-slate-900 dark:text-white font-mono">{{ $prodCount }}</div>
                                    <div class="text-[9px] font-bold text-slate-400">{{ __('messages.pilot_import_stat_products') }}</div>
                                </div>
                                <div class="rounded-lg border border-slate-200/80 bg-white px-1.5 py-1.5 dark:border-slate-700 dark:bg-slate-900">
                                    <div class="text-xs sm:text-sm font-black text-amber-600 font-mono">{{ $featCount }}</div>
                                    <div class="text-[9px] font-bold text-slate-400">{{ __('messages.pilot_import_stat_featured') }}</div>
                                </div>
                                <div class="rounded-lg border border-slate-200/80 bg-white px-1.5 py-1.5 dark:border-slate-700 dark:bg-slate-900">
                                    <div class="text-xs sm:text-sm font-black text-rose-600 font-mono">{{ $promoCount }}</div>
                                    <div class="text-[9px] font-bold text-slate-400">{{ __('messages.pilot_import_stat_promos') }}</div>
                                </div>
                            </div>

                            @if ($isMobileScenario)
                                <div class="mt-1.5 flex flex-wrap gap-1 text-[10px] text-slate-600 dark:text-slate-300 font-medium">
                                    <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800">📦 10 Variant Matrices (Stock: 10)</span>
                                    <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800">💎 25 Digital Codes/PIN</span>
                                </div>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('store.admin.pilot-import.seed-store', $storeRouteParams) }}" class="pt-2 border-t border-slate-200/80 dark:border-slate-700/80 space-y-1.5">
                            @csrf
                            <input type="hidden" name="scenario" value="{{ $key }}">

                            <label class="flex items-center gap-1.5 text-[11px] text-slate-600 dark:text-slate-300 cursor-pointer select-none">
                                <input type="checkbox" name="clean_old" value="1" checked class="rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                                <span class="font-medium">{{ __('messages.pilot_import_clean_old_label') }}</span>
                            </label>

                            <label class="flex items-start gap-1.5 text-[11px] text-slate-600 dark:text-slate-300 cursor-pointer select-none">
                                <input type="checkbox" name="apply_store_identity" value="1" class="mt-0.5 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                                <span>{{ __('messages.pilot_import_apply_identity_label') }}</span>
                            </label>

                            <div class="flex items-center gap-1.5 pt-0.5">
                                <button type="submit"
                                        onclick="return confirm('{{ __('messages.pilot_import_seed_confirm', ['scenario' => $scenario['label'], 'store' => $store->name]) }}')"
                                        class="flex-1 h-8 px-3 rounded-lg bg-violet-600 hover:bg-violet-700 text-white font-bold text-xs shadow-2xs transition flex items-center justify-center gap-1 cursor-pointer active:scale-95">
                                    <span>✨</span>
                                    <span>{{ __('messages.pilot_import_btn_seed') }}</span>
                                </button>

                                @if ($demoScenariosEnabled)
                                    <button type="submit"
                                            formaction="{{ route('store.admin.pilot-import.demo-scenarios.store', array_merge($storeRouteParams, ['scenario' => $key])) }}"
                                            onclick="return confirm('{{ __('messages.pilot_import_demo_store_confirm', ['scenario' => $scenario['label']]) }}')"
                                            title="{{ __('messages.pilot_import_btn_demo_store') }}"
                                            class="h-8 px-2.5 rounded-lg bg-slate-800 hover:bg-slate-900 dark:bg-slate-700 dark:hover:bg-slate-600 text-white text-xs font-bold shrink-0 transition cursor-pointer active:scale-95">
                                        <span>🏬 +</span>
                                    </button>
                                @endif
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Quick-Start Demo Store Creator (Local / UAT mode) --}}
        @if ($demoScenariosEnabled)
            <div class="rounded-lg bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-3 sm:p-4 shadow-2xs space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                    <div>
                        <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                            <span>🏬</span>
                            <span>{{ __('messages.pilot_import_demo_store_title') }}</span>
                        </h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                            {{ __('messages.pilot_import_demo_store_desc') }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                    @foreach ($demoScenarios as $key => $sc)
                        <form method="POST" action="{{ route('store.admin.pilot-import.demo-scenarios.store', array_merge($storeRouteParams, ['scenario' => $key])) }}"
                              class="p-2.5 rounded-lg border border-slate-200/80 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/30 flex items-center justify-between gap-2">
                            @csrf
                            <div class="min-w-0">
                                <div class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">{{ $sc['label'] }}</div>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400 font-mono truncate">store/{{ $key }}</div>
                            </div>
                            <button type="submit"
                                    onclick="return confirm('{{ __('messages.pilot_import_demo_store_confirm', ['scenario' => $sc['label']]) }}')"
                                    class="h-7 px-2.5 rounded-md bg-slate-800 hover:bg-slate-900 dark:bg-slate-700 dark:hover:bg-slate-600 text-white text-xs font-bold shrink-0 transition cursor-pointer active:scale-95">
                                + {{ __('messages.pilot_import_btn_demo_store') }}
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Danger Zone: Wipe / Clean Test Data --}}
        <div class="rounded-lg bg-white dark:bg-slate-900 border border-rose-200 dark:border-rose-900/60 p-3 sm:p-4 shadow-2xs space-y-2">
            <div class="flex items-center gap-2 text-rose-700 dark:text-rose-400 font-black text-xs sm:text-sm">
                <span>⚠️</span>
                <span>{{ __('messages.pilot_import_wipe_title') }}</span>
            </div>
            <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-relaxed">
                {{ __('messages.pilot_import_wipe_desc') }}
            </p>

            <form method="POST" action="{{ route('store.admin.pilot-import.clean-store-data', $storeRouteParams) }}">
                @csrf
                <button type="submit"
                        onclick="return confirm('{{ __('messages.pilot_import_wipe_confirm', ['store' => $store->name]) }}')"
                        class="h-8 px-3 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-2xs transition inline-flex items-center gap-1.5 cursor-pointer active:scale-95">
                    <span>🗑️</span>
                    <span>{{ __('messages.pilot_import_btn_wipe') }}</span>
                </button>
            </form>
        </div>

    @else
        {{-- ========================================================================= --}}
        {{-- TAB: CSV / EXCEL BATCH INGESTION (Products, Customers, Suppliers, Debt)   --}}
        {{-- ========================================================================= --}}

        @php
            $tabTitle = match ($currentTab) {
                'products' => __('messages.pilot_tab_products'),
                'customers' => __('messages.pilot_tab_customers'),
                'suppliers' => __('messages.pilot_tab_suppliers'),
                'debt' => __('messages.pilot_tab_debt'),
                default => strtoupper($currentTab),
            };
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-2 sm:gap-3">
            {{-- Upload Form --}}
            <div class="lg:col-span-1 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-3.5 shadow-2xs space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                    <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                        <span>📤</span>
                        <span>{{ $tabTitle }} Batch Upload</span>
                    </h3>
                    <a href="{{ route('store.admin.pilot-import.template', array_merge($storeRouteParams, ['tab' => $currentTab])) }}"
                       class="h-6 px-2 rounded text-[10px] font-bold bg-violet-50 hover:bg-violet-100 dark:bg-violet-950/60 dark:hover:bg-violet-900/60 text-violet-700 dark:text-violet-300 border border-violet-200/60 dark:border-violet-800/60 transition inline-flex items-center gap-1">
                        <span>📥</span>
                        <span>Template (.csv)</span>
                    </a>
                </div>

                <form method="POST" action="{{ route('store.admin.pilot-import.import', array_merge($storeRouteParams, ['tab' => $currentTab])) }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.pilot_upload_file') }}
                        </label>
                        <input type="file" name="file" required accept=".csv,.xlsx,.txt"
                               class="w-full text-xs text-slate-700 dark:text-slate-300 file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-bold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100 dark:file:bg-violet-950/60 dark:file:text-violet-300 border border-slate-200 dark:border-slate-700 rounded-lg p-1">
                    </div>

                    @if ($currentTab !== 'debt')
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('messages.pilot_duplicate_strategy') }}
                            </label>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <label class="flex items-center gap-1.5 p-2 rounded-lg border border-slate-200 dark:border-slate-700 cursor-pointer">
                                    <input type="radio" name="duplicate_strategy" value="skip" checked class="text-violet-600">
                                    <span class="font-medium text-slate-700 dark:text-slate-300">Skip</span>
                                </label>
                                <label class="flex items-center gap-1.5 p-2 rounded-lg border border-slate-200 dark:border-slate-700 cursor-pointer">
                                    <input type="radio" name="duplicate_strategy" value="update" class="text-violet-600">
                                    <span class="font-medium text-slate-700 dark:text-slate-300">Update</span>
                                </label>
                            </div>
                        </div>
                    @endif

                    <button type="submit"
                            class="w-full h-8 px-3 rounded-lg bg-violet-600 hover:bg-violet-700 text-white font-bold text-xs shadow-2xs transition flex items-center justify-center gap-1.5 cursor-pointer active:scale-95">
                        <span>🔍</span>
                        <span>{{ __('messages.pilot_preview_title') }}</span>
                    </button>
                </form>
            </div>

            {{-- Dry-Run Preview Panel --}}
            <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-3.5 shadow-2xs space-y-3">
                @if (session('import_preview'))
                    @php $preview = session('import_preview'); @endphp
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                        <div>
                            <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                                <span>📋</span>
                                <span>{{ __('messages.pilot_preview_title') }}</span>
                            </h3>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                {{ __('messages.pilot_preview_sub') }}
                            </p>
                        </div>

                        <form method="POST" action="{{ route('store.admin.pilot-import.confirm', array_merge($storeRouteParams, ['tab' => $currentTab])) }}">
                            @csrf
                            <input type="hidden" name="token" value="{{ $preview['token'] }}">
                            <button type="submit"
                                    class="h-8 px-4 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-2xs transition inline-flex items-center gap-1.5 cursor-pointer active:scale-95">
                                <span>✓</span>
                                <span>အတည်ပြု တင်သွင်းမည် (Confirm)</span>
                            </button>
                        </form>
                    </div>

                    {{-- Metrics --}}
                    <div class="grid grid-cols-4 gap-1.5 text-center">
                        <div class="rounded-lg border border-slate-200/80 bg-slate-50 dark:bg-slate-800/40 p-2">
                            <div class="text-sm font-black text-slate-900 dark:text-white font-mono">{{ $preview['total'] ?? 0 }}</div>
                            <div class="text-[10px] font-bold text-slate-400">Total Rows</div>
                        </div>
                        <div class="rounded-lg border border-emerald-200 bg-emerald-50/50 dark:bg-emerald-950/30 p-2">
                            <div class="text-sm font-black text-emerald-600 font-mono">{{ $preview['valid'] ?? ($preview['new_count'] ?? 0) }}</div>
                            <div class="text-[10px] font-bold text-emerald-600">Valid</div>
                        </div>
                        <div class="rounded-lg border border-amber-200 bg-amber-50/50 dark:bg-amber-950/30 p-2">
                            <div class="text-sm font-black text-amber-600 font-mono">{{ $preview['duplicates'] ?? ($preview['duplicate_count'] ?? 0) }}</div>
                            <div class="text-[10px] font-bold text-amber-600">Duplicates</div>
                        </div>
                        <div class="rounded-lg border border-rose-200 bg-rose-50/50 dark:bg-rose-950/30 p-2">
                            <div class="text-sm font-black text-rose-600 font-mono">{{ $preview['invalid'] ?? ($preview['invalid_count'] ?? 0) }}</div>
                            <div class="text-[10px] font-bold text-rose-600">Errors</div>
                        </div>
                    </div>

                    @if (!empty($preview['sample_rows']))
                        <div class="overflow-x-auto border border-slate-200 dark:border-slate-700 rounded-lg max-h-60 scrollbar-thin">
                            <table class="w-full text-[11px] text-left text-slate-700 dark:text-slate-300">
                                <thead class="bg-slate-100 dark:bg-slate-800 font-bold sticky top-0">
                                    <tr>
                                        @foreach (array_keys($preview['sample_rows'][0] ?? []) as $col)
                                            <th class="p-2 border-b border-slate-200 dark:border-slate-700 whitespace-nowrap">{{ $col }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-mono">
                                    @foreach ($preview['sample_rows'] as $row)
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                            @foreach ($row as $val)
                                                <td class="p-2 truncate max-w-40">{{ is_array($val) ? json_encode($val) : $val }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @else
                    <div class="p-8 text-center space-y-2 text-slate-400 dark:text-slate-500">
                        <span class="text-3xl">📁</span>
                        <div class="text-xs font-bold">{{ $tabTitle }} ဖိုင်ကို ရွေးချယ်ပြီး "Dry-Run Preview" နှိပ်ပါ</div>
                        <p class="text-[11px]">ဒေတာဘေ့စ်ထဲသို့ မသိမ်းမီ စာကြောင်းအရေအတွက်နှင့် အမှားအယွင်းများကို အစမ်းမြင်ကွင်းဖြင့် စစ်ဆေးပေးပါမည်။</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Import History Table --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-3.5 shadow-2xs space-y-2.5">
            <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 flex items-center gap-1.5 border-b border-slate-100 dark:border-slate-800 pb-2">
                <span>📜</span>
                <span>{{ __('messages.pilot_history_title') }}</span>
            </h3>

            @if ($histories->isEmpty())
                <div class="p-4 text-center text-xs text-slate-400 dark:text-slate-500">
                    တင်သွင်းမှု မှတ်တမ်း မရှိသေးပါ။
                </div>
            @else
                <div class="overflow-x-auto border border-slate-200/80 dark:border-slate-800 rounded-lg scrollbar-thin">
                    <table class="w-full text-xs text-left text-slate-700 dark:text-slate-300">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 font-bold border-b border-slate-200/80 dark:border-slate-800">
                            <tr>
                                <th class="p-2.5">Date</th>
                                <th class="p-2.5">Type</th>
                                <th class="p-2.5">File Name</th>
                                <th class="p-2.5">User</th>
                                <th class="p-2.5 text-center">Total</th>
                                <th class="p-2.5 text-center">Success</th>
                                <th class="p-2.5 text-center">Failed</th>
                                <th class="p-2.5 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-mono text-[11px]">
                            @foreach ($histories as $hist)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                    <td class="p-2.5 whitespace-nowrap">{{ $hist->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="p-2.5 uppercase font-bold text-violet-600">{{ $hist->import_type }}</td>
                                    <td class="p-2.5 truncate max-w-xs font-sans">{{ $hist->original_filename }}</td>
                                    <td class="p-2.5 font-sans">{{ $hist->user->name ?? 'System' }}</td>
                                    <td class="p-2.5 text-center font-bold">{{ $hist->total_rows }}</td>
                                    <td class="p-2.5 text-center font-bold text-emerald-600">{{ $hist->success_rows }}</td>
                                    <td class="p-2.5 text-center font-bold text-rose-600">{{ $hist->failed_rows }}</td>
                                    <td class="p-2.5 text-center font-sans">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $hist->status === 'completed' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300' }}">
                                            {{ $hist->status }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    @endif

</div>
@endsection
