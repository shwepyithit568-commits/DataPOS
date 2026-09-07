@extends('layouts.storefront.app', ['title' => __('messages.glass_finder_title') . ' · ' . $store->name])

@section('main_padding', 'px-0.5 sm:px-3 lg:px-6 py-1 sm:py-3')

@section('content')
<div class="max-w-6xl mx-auto space-y-2 sm:space-y-3 pb-16 select-none font-sans">

    {{-- Hero Section: Left Banner + Right 3-Step 3D Guide (Matching Height) --}}
    <div class="flex flex-col lg:flex-row gap-2 sm:gap-2.5 items-stretch w-full">
        
        {{-- Left: Banner Image / Carousel --}}
        <div class="flex-1 min-w-0 flex flex-col w-full">
            @if ($banners->count() > 0)
                {{-- Glass-Finder Hero Carousel --}}
                <div x-data="{ activeSlide: 0, totalSlides: {{ $banners->count() }}, timer: null, init() { this.start(); }, start() { if (this.totalSlides > 1) this.timer = setInterval(() => this.next(), 5000); }, stop() { if (this.timer) clearInterval(this.timer); }, next() { this.activeSlide = (this.activeSlide + 1) % this.totalSlides; }, prev() { this.activeSlide = (this.activeSlide - 1 + this.totalSlides) % this.totalSlides; } }"
                     @mouseenter="stop()" @mouseleave="start()"
                     class="relative overflow-hidden bg-slate-950 rounded-lg sm:rounded-xl shadow-xs border border-slate-200/60 dark:border-white/10 w-full h-full min-h-[180px] sm:min-h-[200px] lg:min-h-[215px] flex flex-col justify-end">
                    @foreach ($banners as $index => $banner)
                        @if ($banner->image_path)
                            <div x-show="activeSlide === {{ $index }}" x-transition.opacity.duration.500ms class="absolute inset-0">
                                <img src="{{ asset('storage/' . $banner->image_path) }}" alt="{{ $banner->title }}" class="h-full w-full object-cover" data-img-fallback="hide-parent"/>
                            </div>
                        @endif
                    @endforeach
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-slate-950/25 to-transparent z-[1]"></div>
                    <div class="relative z-10 p-3 sm:p-4">
                        <div class="grid">
                            @foreach ($banners as $index => $banner)
                                <div
                                    :class="activeSlide === {{ $index }}
                                        ? 'opacity-100 translate-y-0 pointer-events-auto'
                                        : 'opacity-0 translate-y-1 pointer-events-none'"
                                    :aria-hidden="activeSlide === {{ $index }} ? 'false' : 'true'"
                                    class="max-w-xl col-start-1 row-start-1 transition-all duration-500 ease-out space-y-1"
                                >
                                    <h2 class="text-sm sm:text-base lg:text-lg font-black font-outfit leading-snug text-white drop-shadow-md line-clamp-2">{{ $banner->title }}</h2>
                                    @if ($banner->link_url)
                                        <a href="{{ $banner->link_url }}" class="sf-btn-3d-primary !flex-row inline-flex items-center gap-1.5 px-3 py-1 text-xs font-bold leading-none">
                                            <span>{{ __('messages.view_detail') }}</span>
                                            <span>→</span>
                                        </a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        @if ($banners->count() > 1)
                            <div class="flex items-center justify-between mt-2 pt-2 border-t border-white/10">
                                <div class="flex items-center gap-1.5">
                                    @foreach ($banners as $index => $banner)
                                        <button @click="activeSlide = {{ $index }}" class="h-1.5 rounded-full transition-all duration-300 focus:outline-none cursor-pointer" :class="activeSlide === {{ $index }} ? 'w-5 bg-sky-400' : 'w-1.5 bg-white/40 hover:bg-white/60'" title="Slide {{ $index + 1 }}"></button>
                                    @endforeach
                                </div>
                                <div class="flex items-center gap-1">
                                    <button @click="prev()" class="w-6 h-6 rounded bg-white/15 hover:bg-white/25 flex items-center justify-center text-white text-xs cursor-pointer">&larr;</button>
                                    <button @click="next()" class="w-6 h-6 rounded bg-white/15 hover:bg-white/25 flex items-center justify-center text-white text-xs cursor-pointer">&rarr;</button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                {{-- Fallback Header Card (Storefront Standard Layout) --}}
                <div class="bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-3.5 sm:p-5 border border-slate-200/90 dark:border-slate-800 shadow-xs h-full flex flex-col justify-center">
                    <div class="space-y-1">
                        <div class="sf-btn-3d active inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[11px] font-black pointer-events-none">
                            <span>🛡️ {{ $store->name ?? config('app.name') }}</span>
                            <span>·</span>
                            <span>{{ __('messages.glass_finder_title') }}</span>
                        </div>
                        <h1 class="text-base sm:text-xl font-black text-slate-900 dark:text-white font-outfit tracking-tight">
                            {{ __('messages.glass_finder_title') }}
                        </h1>
                        <p class="text-xs text-slate-600 dark:text-slate-400 font-myanmar leading-relaxed">
                            {{ __('messages.glass_finder_subtitle') }}
                        </p>
                    </div>
                </div>
            @endif
        </div>

        {{-- Right: 3-Step Guide as Button 3D Cards stacked vertically, matching banner height --}}
        <div class="w-full lg:w-80 xl:w-96 shrink-0 flex flex-col justify-between gap-1.5 sm:gap-2">
            {{-- Step 1 --}}
            <button
                type="button"
                onclick="document.getElementById('brand-select')?.focus(); document.getElementById('brand-select')?.scrollIntoView({behavior: 'smooth', block: 'center'});"
                class="sf-btn-3d w-full flex-1 !flex-row !justify-between items-center p-2.5 sm:p-3 rounded-lg text-left cursor-pointer group transition-all"
            >
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="w-6 h-6 rounded-full bg-sky-500 text-white font-black text-xs flex items-center justify-center shadow-2xs ring-2 ring-sky-200 dark:ring-sky-800 shrink-0">1</span>
                    <div class="min-w-0">
                        <div class="font-black text-xs sm:text-sm text-slate-800 dark:text-slate-100 font-myanmar group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors truncate">
                            {{ __('messages.glass_finder_step_1') }}
                        </div>
                        <div class="text-[10px] text-slate-500 dark:text-slate-400 font-myanmar truncate">
                            {{ __('messages.glass_finder_step_1_sub') }}
                        </div>
                    </div>
                </div>
                <span class="text-[11px] font-black text-sky-600 dark:text-sky-400 bg-sky-50 dark:bg-sky-950/70 px-2 py-0.5 rounded border border-sky-200 dark:border-sky-800/80 group-hover:translate-x-0.5 transition-transform shrink-0">
                    Step 1 →
                </span>
            </button>

            {{-- Step 2 --}}
            <button
                type="button"
                onclick="document.getElementById('phone-model-select')?.focus(); document.getElementById('phone-model-select')?.scrollIntoView({behavior: 'smooth', block: 'center'});"
                class="sf-btn-3d w-full flex-1 !flex-row !justify-between items-center p-2.5 sm:p-3 rounded-lg text-left cursor-pointer group transition-all"
            >
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="w-6 h-6 rounded-full bg-indigo-500 text-white font-black text-xs flex items-center justify-center shadow-2xs ring-2 ring-indigo-200 dark:ring-indigo-800 shrink-0">2</span>
                    <div class="min-w-0">
                        <div class="font-black text-xs sm:text-sm text-slate-800 dark:text-slate-100 font-myanmar group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors truncate">
                            {{ __('messages.glass_finder_step_2') }}
                        </div>
                        <div class="text-[10px] text-slate-500 dark:text-slate-400 font-myanmar truncate">
                            {{ __('messages.glass_finder_step_2_sub') }}
                        </div>
                    </div>
                </div>
                <span class="text-[11px] font-black text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/70 px-2 py-0.5 rounded border border-indigo-200 dark:border-indigo-800/80 group-hover:translate-x-0.5 transition-transform shrink-0">
                    Step 2 →
                </span>
            </button>

            {{-- Step 3 --}}
            <button
                type="button"
                onclick="document.getElementById('glass-items-section')?.scrollIntoView({behavior: 'smooth'});"
                class="sf-btn-3d w-full flex-1 !flex-row !justify-between items-center p-2.5 sm:p-3 rounded-lg text-left cursor-pointer group transition-all"
            >
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="w-6 h-6 rounded-full bg-emerald-500 text-white font-black text-xs flex items-center justify-center shadow-2xs ring-2 ring-emerald-200 dark:ring-emerald-800 shrink-0">3</span>
                    <div class="min-w-0">
                        <div class="font-black text-xs sm:text-sm text-slate-800 dark:text-slate-100 font-myanmar group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors truncate">
                            {{ __('messages.glass_finder_step_3') }}
                        </div>
                        <div class="text-[10px] text-slate-500 dark:text-slate-400 font-myanmar truncate">
                            {{ __('messages.glass_finder_step_3_sub') }}
                        </div>
                    </div>
                </div>
                <span class="text-[11px] font-black text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/70 px-2 py-0.5 rounded border border-emerald-200 dark:border-emerald-800/80 group-hover:translate-x-0.5 transition-transform shrink-0">
                    Step 3 ✓
                </span>
            </button>
        </div>

    </div>

    {{-- Smart Cascading Dropdown & Search Toolbar --}}
    <div
        x-data="{
            search: @js(request('search', '')),
            selectedBrand: @js(request('brand', '')),
            selectedModel: @js(request('phone_model', '')),
            brandModelsMap: @js($brandModelsMap),
            availableModels: [],
            init() {
                this.updateAvailableModels();
            },
            onBrandChange(brand) {
                this.selectedBrand = brand;
                this.selectedModel = '';
                this.updateAvailableModels();
                this.$refs.glassForm.submit();
            },
            onModelChange(model) {
                this.selectedModel = model;
                this.$refs.glassForm.submit();
            },
            updateAvailableModels() {
                if (this.selectedBrand && this.brandModelsMap[this.selectedBrand]) {
                    this.availableModels = this.brandModelsMap[this.selectedBrand];
                } else {
                    let all = [];
                    for (let b in this.brandModelsMap) {
                        (this.brandModelsMap[b] || []).forEach(m => {
                            if (!all.includes(m)) all.push(m);
                        });
                    }
                    all.sort();
                    this.availableModels = all;
                }
            }
        }"
        class="bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-3 sm:p-4 border border-slate-200/90 dark:border-slate-800 shadow-xs space-y-2.5"
    >
        <form method="GET" action="{{ url('/store/' . ($store?->slug ?? request('store_slug')) . '/glass-finder') }}" class="w-full space-y-2.5" x-ref="glassForm">
            @if(request('store_slug'))
                <input type="hidden" name="store_slug" value="{{ request('store_slug') }}" />
            @endif

            {{-- Row 1: Brand/Model Cascading Dropdowns + Smart Keyword Input --}}
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                
                {{-- 1. Brand Dropdown --}}
                <div class="sm:col-span-3">
                    <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-1 font-myanmar">
                        🏷️ {{ __('messages.glass_finder_select_brand') }}
                    </label>
                    <select
                        id="brand-select"
                        name="brand"
                        x-model="selectedBrand"
                        @change="onBrandChange($event.target.value)"
                        class="w-full rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 text-slate-900 dark:text-white px-2.5 py-1.5 text-xs font-bold focus:ring-1 focus:ring-sky-500 focus:border-sky-500 cursor-pointer shadow-2xs"
                    >
                        <option value="">✨ {{ __('messages.all_brands') }}</option>
                        @foreach ($brands as $b)
                            <option value="{{ $b }}" @selected(request('brand') === $b)>{{ $b }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- 2. Model Cascading Dropdown --}}
                <div class="sm:col-span-4">
                    <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-1 font-myanmar">
                        📱 {{ __('messages.glass_finder_select_model') }}
                    </label>
                    <select
                        id="phone-model-select"
                        name="phone_model"
                        x-model="selectedModel"
                        @change="onModelChange($event.target.value)"
                        class="w-full rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 text-slate-900 dark:text-white px-2.5 py-1.5 text-xs font-bold focus:ring-1 focus:ring-sky-500 focus:border-sky-500 cursor-pointer shadow-2xs"
                    >
                        <option value="">🔍 {{ __('messages.glass_finder_all_models') }}</option>
                        <template x-for="m in availableModels" :key="m">
                            <option :value="m" x-text="m" :selected="m === selectedModel"></option>
                        </template>
                    </select>
                </div>

                {{-- 3. Smart Search Input & Action Buttons --}}
                <div class="sm:col-span-5">
                    <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-1 font-myanmar">
                        🔎 {{ __('messages.search') }}
                    </label>
                    <div class="flex items-center gap-1.5">
                        <div class="relative flex-1 min-w-0">
                            <input
                                type="text"
                                name="search"
                                x-model="search"
                                @input.debounce.500ms="$refs.glassForm.submit()"
                                @keydown.enter.prevent="$refs.glassForm.submit()"
                                placeholder="{{ __('messages.glass_finder_search_placeholder') }}"
                                class="w-full rounded-md border border-slate-300 dark:border-slate-700 px-2.5 py-1.5 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-bold focus:ring-1 focus:ring-sky-500 focus:border-sky-500 shadow-2xs"
                            />
                            <span x-show="search" @click="search = ''; $refs.glassForm.submit()" class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer text-xs" title="Clear">✕</span>
                        </div>

                        <button type="submit" class="sf-btn-3d-primary !flex-row px-3 py-1.5 text-xs font-black leading-none inline-flex items-center gap-1 shrink-0 cursor-pointer shadow-xs">
                            <span>{{ __('messages.search') }}</span>
                        </button>

                        @if(request()->anyFilled(['search', 'brand', 'phone_model', 'glass_code']))
                            <a href="{{ url('/store/' . ($store?->slug ?? request('store_slug')) . '/glass-finder') }}"
                               class="sf-btn-3d-danger !flex-row px-2.5 py-1.5 text-xs font-bold leading-none inline-flex items-center shrink-0 cursor-pointer shadow-xs"
                               title="{{ __('messages.glass_finder_reset_filter') }}">
                                <span>✕</span>
                            </a>
                        @endif
                    </div>
                </div>

            </div>

            {{-- Row 2: Brand Horizontal Fast-Tap Pills --}}
            <div class="pt-1 border-t border-slate-100 dark:border-slate-800">
                <div class="overflow-x-auto pb-1 scrollbar-thin">
                    <div class="flex items-center gap-1 sm:gap-1.5 min-w-max">
                        <button
                            type="button"
                            @click="onBrandChange('')"
                            class="sf-btn-3d !flex-row px-2.5 py-1 text-[11px] font-extrabold leading-none rounded-md cursor-pointer"
                            :class="!selectedBrand ? 'active text-sky-600 dark:text-sky-400 border-sky-400 dark:border-sky-600' : ''"
                        >
                            ✨ {{ __('messages.all_brands') }}
                        </button>

                        @foreach ($brands as $b)
                            <button
                                type="button"
                                @click="onBrandChange('{{ $b }}')"
                                class="sf-btn-3d !flex-row px-2.5 py-1 text-[11px] font-extrabold leading-none rounded-md cursor-pointer"
                                :class="selectedBrand === '{{ $b }}' ? 'active text-sky-600 dark:text-sky-400 border-sky-400 dark:border-sky-600 ring-1 ring-sky-400/40' : ''"
                            >
                                {{ $b }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

        </form>
    </div>

    {{-- Grouped Compatibility Results Section --}}
    @if ($compatibles->isNotEmpty())
        <div id="glass-items-section"
             x-data="{ glassView: localStorage.getItem('glass_view') === 'table' ? 'table' : 'list' }"
             class="bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-3 sm:p-4 border border-slate-200/90 dark:border-slate-800 shadow-xs space-y-3">
            
            {{-- Toolbar: Results Heading & View Switcher --}}
            <div class="flex items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                <div class="flex items-center gap-2">
                    <span class="text-base">🛡️</span>
                    <div>
                        <h2 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white font-outfit">
                            @if (!empty($isInitialState))
                                {{ __('messages.glass_finder_catalog_title') }}
                            @else
                                {{ __('messages.compat_results_title') }}
                            @endif
                        </h2>
                        <p class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 font-myanmar">
                            @if (!empty($isInitialState))
                                {{ __('messages.glass_finder_catalog_subtitle') }}
                            @else
                                {{ __('messages.compat_results_hint') }}
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-[10px] sm:text-[11px] font-mono font-bold px-2 py-0.5 rounded bg-sky-50 dark:bg-sky-950/80 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                        {{ $groupedCompatibles->count() }} Codes ({{ $compatibles->count() }} Models)
                    </span>

                    {{-- View Switcher Buttons --}}
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            @click="glassView = 'list'; localStorage.setItem('glass_view', 'list')"
                            class="sf-btn-3d !flex-row px-2 py-1 text-xs font-bold leading-none cursor-pointer"
                            :class="glassView === 'list' ? 'active' : ''"
                            title="{{ __('messages.view_list') }}"
                        >
                            ☰
                        </button>
                        <button
                            type="button"
                            @click="glassView = 'table'; localStorage.setItem('glass_view', 'table')"
                            class="sf-btn-3d !flex-row px-2 py-1 text-xs font-bold leading-none cursor-pointer"
                            :class="glassView === 'table' ? 'active' : ''"
                            title="{{ __('messages.view_table') }}"
                        >
                            ▦
                        </button>
                    </div>
                </div>
            </div>

            @if ($selectedItem && empty($isInitialState))
                <div class="p-2.5 rounded-md bg-sky-50/80 dark:bg-sky-950/50 border border-sky-200/80 dark:border-sky-900/60 text-xs text-sky-950 dark:text-sky-200 flex flex-wrap items-center justify-between gap-2 shadow-2xs">
                    <div class="font-myanmar text-[11px] sm:text-xs">
                        {{ __('messages.searched_phone', ['phone' => $selectedItem->phone_model, 'brand' => $selectedItem->brand]) }}
                    </div>
                    <div class="text-[11px]">
                        {{ __('messages.glass_code') }}: <span class="font-mono font-black px-2 py-0.5 rounded bg-white dark:bg-slate-800 text-sky-600 dark:text-sky-400 border border-sky-300 dark:border-sky-800">{{ $selectedItem->glass_code }}</span>
                    </div>
                </div>
            @endif

            {{-- 1. List View: Grouped Glass Code Cards --}}
            <div x-show="glassView === 'list'" class="space-y-2.5">
                @foreach ($groupedCompatibles as $normalizedCode => $groupItems)
                    @php
                        $displayCode = $groupItems->first()?->glass_code ?? $normalizedCode;
                        $firstCompat = $groupItems->first();
                        $firstItemId = $firstCompat?->id;
                        $uniqueModels = $groupItems->pluck('phone_model')->unique()->values();
                        $modelsSummary = $uniqueModels->take(4)->implode(', ');
                        
                        $codeMsg = "မင်္ဂလာပါ။ Glass Request:\nဖုန်း: {$firstCompat?->phone_model}\nCode: {$displayCode}\nBrand: {$firstCompat?->brand}";
                        $codeViberUrl = \App\Support\ContactLinkBuilder::viberChatUrl($store?->setting?->viber_number, $codeMsg);
                        $codeViberIosUrl = \App\Support\ContactLinkBuilder::viberIosContactUrl($store?->setting?->viber_number, $codeMsg);
                        $codeTgUrl = \App\Support\ContactLinkBuilder::telegramUrl($store?->setting?->telegram_username, $codeMsg);
                    @endphp

                    <div data-normalized-code="{{ $normalizedCode }}" class="p-3 sm:p-3.5 rounded-lg sm:rounded-xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 space-y-2.5 shadow-2xs hover:border-sky-300 dark:hover:border-sky-800 transition">
                        
                        {{-- Glass Code Header with Quick Buy 3D CTA --}}
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800/80 gap-2">
                            <div class="space-y-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm">🛡️</span>
                                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider font-mono">Code:</span>
                                    <span class="font-mono text-sky-600 dark:text-sky-400 font-black text-sm sm:text-base px-2 py-0.5 rounded bg-sky-50 dark:bg-sky-950/80 border border-sky-200 dark:border-sky-800">
                                        {{ $displayCode }}
                                    </span>
                                    {{-- Feature Badges --}}
                                    <span class="inline-flex items-center text-[10px] font-bold px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                        💎 9H Glass
                                    </span>
                                    <span class="inline-flex items-center text-[10px] font-bold px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                        ✨ HD Clear
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-myanmar">
                                    {{ __('messages.models_compatible', ['count' => $uniqueModels->count()]) }}
                                </p>
                            </div>

                            {{-- Action Buttons: Quick Buy 3D + Fav + Viber / TG --}}
                            <div class="flex items-center gap-1.5 shrink-0 self-start sm:self-auto flex-wrap">
                                {{-- Quick Buy 3D Button --}}
                                <button 
                                    @click.stop.prevent="$store.orderBuilder.addGlassCodeItem({{ json_encode($displayCode) }}, {{ json_encode($modelsSummary) }}, {{ $firstItemId ?? 'null' }})"
                                    type="button"
                                    class="sf-btn-3d-gold !flex-row px-3 py-1.5 text-xs font-black leading-none inline-flex items-center gap-1.5 cursor-pointer shadow-xs"
                                    title="{{ __('messages.add_glass_code_title') }}"
                                >
                                    <span>🛒</span>
                                    <span>{{ __('messages.glass_finder_quick_buy') }}</span>
                                    <span x-show="$store.orderBuilder && $store.orderBuilder.getCodeQty({{ json_encode($displayCode) }}) > 0"
                                          x-cloak
                                          class="px-1.5 py-0.2 text-[10px] font-black rounded-full bg-rose-600 text-white shadow-2xs"
                                          x-text="$store.orderBuilder.getCodeQty({{ json_encode($displayCode) }})">
                                    </span>
                                </button>

                                {{-- Favorite Button --}}
                                <button
                                    @click.stop.prevent="$store.favoritesStore.toggle({ id: 'glass_code_' + {{ json_encode($displayCode) }}, glass_finder_item_id: {{ $firstItemId ?? 'null' }}, name: {{ json_encode('Glass Code: ' . $displayCode . ($modelsSummary ? ' (' . $modelsSummary . ')' : '')) }}, brand: 'Glass', glass_code: {{ json_encode($displayCode) }}, url: '{{ url('/glass-finder?glass_code=' . urlencode($displayCode)) }}' })"
                                    type="button"
                                    class="sf-btn-3d active !flex-row p-1.5 text-xs font-bold leading-none cursor-pointer"
                                    :class="{ 'text-rose-500 bg-rose-50 dark:bg-rose-950/80 border-rose-300 dark:border-rose-800': $store.favoritesStore && $store.favoritesStore.isFav('glass_code_' + {{ json_encode($displayCode) }}) }"
                                    title="Favorite"
                                >
                                    <span x-text="($store.favoritesStore && $store.favoritesStore.isFav('glass_code_' + {{ json_encode($displayCode) }})) ? '❤️' : '🤍'"></span>
                                </button>

                                {{-- Viber & Telegram Direct Request --}}
                                @if ($codeViberUrl)
                                    <a href="{{ $codeViberUrl }}" data-ios-href="{{ $codeViberIosUrl }}" target="_blank" rel="noopener noreferrer"
                                       class="sf-btn-3d-viber !flex-row px-2.5 py-1.5 text-xs font-bold leading-none inline-flex items-center gap-1 cursor-pointer">
                                        <span>Viber</span>
                                    </a>
                                @endif
                                @if ($codeTgUrl)
                                    <a href="{{ $codeTgUrl }}" target="_blank" rel="noopener noreferrer"
                                       class="sf-btn-3d-telegram !flex-row px-2.5 py-1.5 text-xs font-bold leading-none inline-flex items-center gap-1 cursor-pointer">
                                        <span>TG</span>
                                    </a>
                                @endif
                            </div>
                        </div>

                        {{-- Real-time Compatible Phone Models Grid / Chips --}}
                        <div class="space-y-1">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block font-myanmar">
                                📱 {{ __('messages.glass_finder_compatible_models_heading') }}:
                            </span>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($groupItems as $compat)
                                    <div class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-slate-50 dark:bg-slate-800/70 border border-slate-200/80 dark:border-slate-700/80 text-xs">
                                        <span class="font-extrabold text-sky-600 dark:text-sky-400 text-[10px] uppercase font-mono">{{ $compat->brand }}</span>
                                        <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">{{ $compat->phone_model }}</span>
                                        @if ($compat->isInStock())
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 ring-2 ring-emerald-200 dark:ring-emerald-950" title="In Stock"></span>
                                        @else
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 ring-2 ring-rose-200 dark:ring-rose-950" title="Out of Stock"></span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>

                    </div>
                @endforeach
            </div>

            {{-- 2. Table View --}}
            <div x-show="glassView === 'table'" x-cloak class="overflow-x-auto rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xs scrollbar-thin">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-800/60 text-[11px] text-slate-500 dark:text-slate-400">
                            <th class="text-left py-2 px-3 font-extrabold uppercase">Brand</th>
                            <th class="text-left py-2 px-3 font-extrabold uppercase">Phone Model</th>
                            <th class="text-left py-2 px-3 font-extrabold uppercase">Glass Code</th>
                            <th class="text-center py-2 px-3 font-extrabold uppercase">Status</th>
                            <th class="text-right py-2 px-3 font-extrabold uppercase">Quick Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($groupedCompatibles as $normalizedCode => $groupItems)
                            @php
                                $displayCode = $groupItems->first()?->glass_code ?? $normalizedCode;
                                $firstCompat = $groupItems->first();
                                $firstItemId = $firstCompat?->id;
                                $uniqueModels = $groupItems->pluck('phone_model')->unique()->values();
                                $modelsSummary = $uniqueModels->take(3)->implode(', ');
                            @endphp
                            {{-- Header row for each glass code --}}
                            <tr class="bg-sky-50/40 dark:bg-sky-950/30 font-bold border-t border-sky-100 dark:border-sky-900">
                                <td colspan="4" class="py-1.5 px-3">
                                    <span class="font-black text-slate-900 dark:text-white font-mono text-xs text-sky-700 dark:text-sky-300">
                                        🛡️ Code: {{ $displayCode }}
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-normal ml-2">({{ $uniqueModels->count() }} models compatible)</span>
                                </td>
                                <td class="py-1.5 px-3 text-right">
                                    <button 
                                        @click.stop.prevent="$store.orderBuilder.addGlassCodeItem({{ json_encode($displayCode) }}, {{ json_encode($modelsSummary) }}, {{ $firstItemId ?? 'null' }})"
                                        type="button"
                                        class="sf-btn-3d-gold !flex-row px-2 py-1 text-[11px] font-black leading-none inline-flex items-center gap-1 cursor-pointer"
                                        title="{{ __('messages.add_glass_code_title') }}"
                                    >
                                        <span>🛒</span>
                                        <span>{{ __('messages.glass_finder_quick_buy') }}</span>
                                        <span x-show="$store.orderBuilder && $store.orderBuilder.getCodeQty({{ json_encode($displayCode) }}) > 0"
                                              x-cloak
                                              class="px-1 text-[10px] rounded-full bg-rose-600 text-white"
                                              x-text="$store.orderBuilder.getCodeQty({{ json_encode($displayCode) }})">
                                        </span>
                                    </button>
                                </td>
                            </tr>
                            {{-- Individual model rows --}}
                            @foreach ($groupItems as $compat)
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                    <td class="py-1.5 px-3 font-extrabold text-sky-600 dark:text-sky-400 text-xs">{{ $compat->brand }}</td>
                                    <td class="py-1.5 px-3 font-bold text-slate-800 dark:text-slate-200">{{ $compat->phone_model }}</td>
                                    <td class="py-1.5 px-3 font-mono font-bold text-slate-600 dark:text-slate-300">{{ $compat->glass_code }}</td>
                                    <td class="py-1.5 px-3 text-center">
                                        @if ($compat->isInStock())
                                            <span class="inline-block px-1.5 py-0.2 rounded text-[10px] font-black bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300">In Stock</span>
                                        @else
                                            <span class="inline-block px-1.5 py-0.2 rounded text-[10px] font-black bg-rose-100 dark:bg-rose-950/80 text-rose-700 dark:text-rose-300">Out of Stock</span>
                                        @endif
                                    </td>
                                    <td class="py-1.5 px-3 text-right"></td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    @else
        {{-- Empty State --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-8 border border-slate-200/90 dark:border-slate-800 shadow-xs text-center space-y-3">
            <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 text-2xl flex items-center justify-center mx-auto text-slate-400">
                🔍
            </div>
            <div class="space-y-1">
                <h3 class="font-black text-sm sm:text-base text-slate-900 dark:text-white font-myanmar">
                    {{ __('messages.glass_finder_no_results') }}
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-myanmar">
                    ဖုန်းအမျိုးအစား သို့မဟုတ် မှန်ကုဒ်ကို ပြန်လည်စစ်ဆေးပါ (သို့မဟုတ်) ဆိုင်သို့ တိုက်ရိုက် မေးမြန်းနိုင်ပါသည်
                </p>
            </div>
            <div class="flex items-center justify-center gap-2 pt-2">
                <a href="{{ url('/store/' . ($store?->slug ?? request('store_slug')) . '/glass-finder') }}"
                   class="sf-btn-3d active !flex-row px-3 py-1.5 text-xs font-bold leading-none inline-flex items-center gap-1.5 cursor-pointer">
                    <span>🔄</span>
                    <span>{{ __('messages.glass_finder_reset_filter') }}</span>
                </a>
                @if ($store?->setting?->viber_number)
                    <a href="{{ \App\Support\ContactLinkBuilder::viberChatUrl($store?->setting?->viber_number, 'မင်္ဂလာပါ။ ဖုန်းမှန်မကွဲ အကြောင်း စုံစမ်းလိုပါသည် ခင်ဗျာ။') }}"
                       target="_blank" rel="noopener noreferrer"
                       class="sf-btn-3d-viber !flex-row px-3 py-1.5 text-xs font-bold leading-none inline-flex items-center gap-1.5 cursor-pointer">
                        <span>💬</span>
                        <span>{{ __('messages.glass_finder_ask_shop') }}</span>
                    </a>
                @endif
            </div>
        </div>
    @endif

</div>
@endsection
