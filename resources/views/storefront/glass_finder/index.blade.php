@extends('layouts.storefront.app')

@section('noMainPadding', true)
@section('content')
<div class="space-y-[5px]">
@if ($banners->count() > 0)
    {{-- Glass-Finder Hero Carousel — Fully Responsive --}}
    <div x-data="{ activeSlide: 0, totalSlides: {{ $banners->count() }}, timer: null, init() { this.start(); }, start() { if (this.totalSlides > 1) this.timer = setInterval(() => this.next(), 5000); }, stop() { if (this.timer) clearInterval(this.timer); }, next() { this.activeSlide = (this.activeSlide + 1) % this.totalSlides; }, prev() { this.activeSlide = (this.activeSlide - 1 + this.totalSlides) % this.totalSlides; } }" @mouseenter="stop()" @mouseleave="start()" class="relative overflow-hidden bg-slate-950 shadow-2xl border border-white/10">
        @foreach ($banners as $index => $banner)
            @if ($banner->image_path)
                <div x-show="activeSlide === {{ $index }}" x-transition.opacity.duration.500ms class="absolute inset-0">
                    <img src="{{ asset('storage/' . $banner->image_path) }}" alt="{{ $banner->title }}" class="h-full w-full object-cover" data-img-fallback="hide-parent"/>
                </div>
            @endif
        @endforeach
        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-slate-950/20 to-transparent z-[1]"></div>
        <div class="relative z-10 min-h-[200px] sm:min-h-[240px] lg:min-h-[280px] flex flex-col justify-end p-3 sm:p-5">
            <div class="grid">
                @foreach ($banners as $index => $banner)
                    <div
                        :class="activeSlide === {{ $index }}
                            ? 'opacity-100 translate-y-0 pointer-events-auto'
                            : 'opacity-0 translate-y-1 pointer-events-none'"
                        :aria-hidden="activeSlide === {{ $index }} ? 'false' : 'true'"
                        class="max-w-xl col-start-1 row-start-1 transition-all duration-500 ease-out"
                    >
                        <h2 class="text-sm sm:text-xl lg:text-2xl font-black font-outfit leading-snug text-white drop-shadow-lg line-clamp-2 sm:line-clamp-1">{{ $banner->title }}</h2>
                        @if ($banner->link_url)
                            <a href="{{ $banner->link_url }}" class="sf-btn-3d-primary inline-flex items-center gap-1.5 mt-2 px-3 py-1.5 sm:px-4 sm:py-2 text-[11px] sm:text-xs rounded-lg">
                                {{ __('messages.view_detail') }}
                                <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
            @if ($banners->count() > 1)
                <div class="flex items-center justify-between mt-2 pt-2 border-t border-white/10">
                    <div class="flex items-center gap-1.5">
                        @foreach ($banners as $index => $banner)
                            <button @click="activeSlide = {{ $index }}" class="h-1.5 sm:h-2 rounded-full transition-all duration-400 focus:outline-none" :class="activeSlide === {{ $index }} ? 'w-5 sm:w-7 bg-sky-400 shadow-sm' : 'w-1.5 sm:w-2 bg-white/30 hover:bg-white/50'" title="Slide {{ $index + 1 }}"></button>
                        @endforeach
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button @click="prev()" class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-white/10 hover:bg-white/20 border border-white/15 flex items-center justify-center text-white transition active:scale-90 text-xs">&larr;</button>
                        <button @click="next()" class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-white/10 hover:bg-white/20 border border-white/15 flex items-center justify-center text-white transition active:scale-90 text-xs">&rarr;</button>
                    </div>
                </div>
            @endif
        </div>
    </div>
@else
    {{-- Default Glass-Finder Header --}}
    <div class="text-center max-w-xl mx-auto space-y-2">
        <div class="inline-flex items-center space-x-2 px-3.5 py-1 rounded-full bg-sky-500/10 text-sky-700 dark:text-sky-300 text-xs font-extrabold border border-sky-400/30">
            <span>📱 Smart Glass Compatibility Engine</span>
        </div>
        <h1 class="text-2xl sm:text-4xl font-black text-slate-900 dark:text-white font-outfit">
            {{ __('messages.glass_finder_title') }}
        </h1>
        <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-600 font-myanmar leading-relaxed">
            {{ __('messages.glass_finder_subtitle') }}
        </p>
    </div>
@endif

    {{-- Smart Search & Horizontal Scroll Filter Toolbar --}}
    <div
        x-data="{
            search: '{{ request('search', request('phone_model', request('glass_code'))) }}',
            brand: '{{ request('brand') }}',
            searchTimer: null,
            liveSearch() {
                clearTimeout(this.searchTimer);
                this.searchTimer = setTimeout(() => {
                    this.$refs.glassForm.submit();
                }, 500);
            }
        }"
        class="bg-white dark:bg-slate-900 rounded-2xl p-3 sm:p-5 lg:p-6 mx-auto border border-slate-200/90 dark:border-slate-800/80 shadow-2xl space-y-3"
    >
        <form method="GET" action="{{ url('/glass-finder') }}" class="w-full" x-ref="glassForm">
            @if(request('store_slug'))
                <input type="hidden" name="store_slug" value="{{ request('store_slug') }}" />
            @endif

            <div class="space-y-3">
                {{-- Row 1: Search + Action Buttons --}}
                <div class="flex items-center gap-2 sm:gap-2.5">
                    {{-- Smart Search Input --}}
                    <div class="flex-1 min-w-0">
                        <input
                            type="text"
                            name="search"
                            x-model="search"
                            @input.debounce.500ms="$refs.glassForm.submit()"
                            @keydown.enter.prevent="$refs.glassForm.submit()"
                            placeholder="{{ __('messages.glass_finder_search_placeholder') }}"
                            class="w-full rounded-xl border border-slate-300 dark:border-slate-700 px-3.5 py-3 sm:px-4 sm:py-2.5 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm font-bold focus:ring-2 focus:ring-sky-500 shadow-sm"
                        />
                    </div>

                    {{-- Submit Button --}}
                    <button type="submit" class="sf-btn-3d-primary shrink-0 px-5 sm:px-5 py-3 sm:py-2.5 font-extrabold text-sm rounded-xl flex items-center space-x-1 min-h-[44px]">
                        <span>{{ __('messages.search') }}</span>
                    </button>

                    {{-- Reset (X icon) --}}
                    @if(request()->anyFilled(['search', 'brand', 'phone_model', 'glass_code']))
                        <a href="{{ url('/glass-finder?store_slug=' . ($store?->slug ?? request('store_slug'))) }}" class="shrink-0 w-11 h-11 flex items-center justify-center bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-xl hover:bg-red-100 dark:hover:bg-red-900/50 transition min-h-[44px]" title="{{ __('messages.reset') }}" aria-label="{{ __('messages.reset') }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endif
                </div>

                {{-- Row 2: Brand Horizontal Scroll Chips --}}
                <div class="relative">
                    <div x-data="{ isDown: false, startX: 0, scrollLeft: 0 }" @mousedown="isDown = true; startX = $event.pageX - $el.offsetLeft; scrollLeft = $el.scrollLeft" @mouseleave="isDown = false" @mouseup="isDown = false" @mousemove="if(isDown){$event.preventDefault();const x=$event.pageX-$el.offsetLeft;const walk=(x-startX)*1.5;$el.scrollLeft=scrollLeft-walk}" class="overflow-x-auto pb-0.5 scrollbar-thin cursor-grab active:cursor-grabbing select-none">
                    <div class="flex items-center gap-1.5 sm:gap-2 py-0.5 min-w-max">
                        {{-- All Brands Chip --}}
                        <button
                            type="button"
                            @click="brand = ''; $nextTick(() => $el.closest('form').submit())"
                            class="shrink-0 px-3.5 sm:px-4 py-2.5 rounded-full text-sm font-extrabold transition-all duration-200 whitespace-nowrap min-h-[40px]"
                            :class="!brand ? 'sf-btn-3d-primary scale-105 border-0' : 'bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-600 border-2 border-slate-300 dark:border-slate-700 hover:border-sky-400 hover:text-sky-700 dark:hover:text-sky-300'"
                        >
                            ✨ {{ __('messages.all_brands') }}
                        </button>

                        @foreach ($brands as $b)
                            <button
                                type="button"
                                @click="brand = '{{ $b }}'; $nextTick(() => $el.closest('form').submit())"
                                class="shrink-0 px-3.5 sm:px-4 py-2.5 rounded-full text-sm font-extrabold transition-all duration-200 whitespace-nowrap min-h-[40px]"
                                :class="brand === '{{ $b }}' ? 'sf-btn-3d-primary scale-105 border-0' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-2 border-slate-300 dark:border-slate-700 hover:border-sky-400 hover:text-sky-700 dark:hover:text-sky-300'"
                            >
                                {{ $b }}
                            </button>
                        @endforeach
                    </div>
                    </div>
                </div>
                {{-- Hidden select to keep form submission working --}}
                <select name="brand" x-model="brand" class="hidden" aria-hidden="true" tabindex="-1">
                    <option value="">{{ __('messages.all_brands') }}</option>
                    @foreach ($brands as $b)
                        <option value="{{ $b }}">{{ $b }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    {{-- Grouped Compatibility Results --}}
    @if ($selectedItem || $compatibles->count() > 0)
        <div x-data="{ glassView: localStorage.getItem('glass_view') === 'table' ? 'table' : 'list' }" class="bg-white dark:bg-slate-900 rounded-2xl p-3 sm:p-5 lg:p-8 mx-auto border border-slate-200/90 dark:border-slate-800/80 shadow-2xl space-y-4 sm:space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200/60 dark:border-slate-800/60 pb-3">
                <div>
                    <h2 class="text-base sm:text-lg lg:text-xl font-black text-slate-900 dark:text-white font-outfit">
                        {{ __('messages.compat_results_title') }}
                    </h2>
                    <p class="text-[11px] sm:text-xs text-slate-600 dark:text-slate-600 font-myanmar">
                        {{ __('messages.compat_results_hint') }}
                    </p>
                </div>
                <span class="self-start sm:self-auto text-[11px] sm:text-xs font-extrabold text-sky-700 dark:text-sky-300 bg-sky-100 dark:bg-sky-950/80 px-3 py-1 rounded-full border border-sky-300 dark:border-sky-800">
                    {{ __('messages.items_found', ['count' => $compatibles->count()]) }}
                </span>
            </div>

            @if ($selectedItem)
                <div class="p-4 rounded-2xl bg-sky-50 dark:bg-sky-950/60 border border-sky-200 dark:border-sky-900/60 text-xs text-sky-950 dark:text-sky-200 flex flex-wrap items-center justify-between gap-2 shadow-sm">
                    <div>
                        {{ __('messages.searched_phone', ['phone' => $selectedItem->phone_model, 'brand' => $selectedItem->brand]) }}
                    </div>
                    <div>
                        {{ __('messages.glass_code') }}: <span class="font-mono font-extrabold px-2.5 py-0.5 rounded bg-white dark:bg-slate-800 text-sky-600 dark:text-sky-400 border border-sky-200 dark:border-sky-800">{{ $selectedItem->glass_code }}</span>
                    </div>
                </div>
            @endif

            {{-- View toggle: List / Table --}}
            <div class="flex items-center gap-1.5">
                <button
                    type="button"
                    @click="glassView = 'list'; localStorage.setItem('glass_view', 'list')"
                    :class="glassView === 'list' ? 'sf-btn-3d-primary' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:border-slate-300'"
                    class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-extrabold transition active:scale-95"
                    :aria-pressed="glassView === 'list'"
                >
                    <span aria-hidden="true">☰</span> {{ __('messages.view_list') }}
                </button>
                <button
                    type="button"
                    @click="glassView = 'table'; localStorage.setItem('glass_view', 'table')"
                    :class="glassView === 'table' ? 'sf-btn-3d-primary' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:border-slate-300'"
                    class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-extrabold transition active:scale-95"
                    :aria-pressed="glassView === 'table'"
                >
                    <span aria-hidden="true">▦</span> {{ __('messages.view_table') }}
                </button>
            </div>

            {{-- List view: full-width grouped cards (1 column) --}}
            <div x-show="glassView === 'list'" x-cloak class="space-y-2">
                @foreach ($groupedCompatibles as $normalizedCode => $groupItems)
                    @php
                        $displayCode = $groupItems->first()?->glass_code ?? $normalizedCode;
                        $uniqueModels = $groupItems->pluck('phone_model')->unique()->values();
                    @endphp
                    <div data-normalized-code="{{ $normalizedCode }}" class="p-3 sm:p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-4 shadow-sm">
                        {{-- Glass Code Header Card with Single Add-to-Cart Button --}}
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-100 dark:border-slate-800/80 pb-3 gap-2">
                            <div class="flex items-start space-x-2 min-w-0">
                                <span class="text-lg shrink-0">🛡️</span>
                                <div class="min-w-0">
                                    <h3 class="font-black text-sm text-slate-900 dark:text-white font-outfit">
                                        Glass Code: <span class="font-mono text-sky-600 dark:text-sky-400 font-extrabold text-base break-all">{{ $displayCode }}</span>
                                    </h3>
                                    <p class="text-xs text-slate-500 dark:text-slate-600 font-myanmar">
                                        {{ __('messages.models_compatible', ['count' => $uniqueModels->count()]) }} ({{ $uniqueModels->take(3)->implode(', ') }}{{ $uniqueModels->count() > 3 ? '...' : '' }})
                                    </p>
                                </div>
                            </div>

                            @php
                                $firstCompat = $groupItems->first();
                                $firstItemId = $firstCompat?->id;
                                $modelsSummary = $uniqueModels->take(3)->implode(', ');
                                $codeMsg = "မင်္ဂလာပါ။ Glass Request:\nဖုန်း: {$firstCompat?->phone_model}\nCode: {$displayCode}\nBrand: {$firstCompat?->brand}";
                                $codeViberUrl = \App\Support\ContactLinkBuilder::viberChatUrl($store?->setting?->viber_number, $codeMsg);
                                $codeViberIosUrl = \App\Support\ContactLinkBuilder::viberIosContactUrl($store?->setting?->viber_number, $codeMsg);
                                $codeTgUrl = \App\Support\ContactLinkBuilder::telegramUrl($store?->setting?->telegram_username, $codeMsg);
                            @endphp
                            {{-- Actions once per glass code: Add + Favorite + Viber + Telegram --}}
                            <div class="flex flex-wrap items-center gap-1.5 shrink-0 self-start sm:self-auto">
                                <button 
                                    @click.stop.prevent="$store.orderBuilder.addGlassCodeItem({{ json_encode($displayCode) }}, {{ json_encode($modelsSummary) }}, {{ $firstItemId ?? 'null' }})"
                                    type="button"
                                    class="sf-btn-3d-primary relative px-3 py-1.5 rounded-xl font-extrabold text-xs flex items-center space-x-1.5"
                                    title="{{ __('messages.add_glass_code_title') }}"
                                >
                                    <span aria-hidden="true">🛒</span>
                                    <span class="break-words">{{ __('messages.add_glass_code', ['code' => $displayCode]) }}</span>
                                    <span x-show="$store.orderBuilder && $store.orderBuilder.getCodeQty({{ json_encode($displayCode) }}) > 0" class="px-2 py-0.5 rounded-full bg-rose-500 text-white font-black text-xs border border-white shadow-sm" x-text="$store.orderBuilder.getCodeQty({{ json_encode($displayCode) }})"></span>
                                </button>
                                <button
                                    @click.stop.prevent="$store.favoritesStore.toggle({ id: 'glass_code_' + {{ json_encode($displayCode) }}, glass_finder_item_id: {{ $firstItemId ?? 'null' }}, name: {{ json_encode('Glass Code: ' . $displayCode . ($modelsSummary ? ' (' . $modelsSummary . ')' : '')) }}, brand: 'Glass', glass_code: {{ json_encode($displayCode) }}, url: '{{ url('/glass-finder?glass_code=' . urlencode($displayCode)) }}' })"
                                    type="button"
                                    class="w-8 h-8 rounded-xl bg-white dark:bg-slate-800 text-slate-600 hover:text-rose-500 border border-slate-200 dark:border-slate-700 flex items-center justify-center transition shrink-0"
                                    :class="{ 'text-rose-500 bg-rose-50 dark:bg-rose-950/80 border-rose-300 dark:border-rose-800': $store.favoritesStore && $store.favoritesStore.isFav('glass_code_' + {{ json_encode($displayCode) }}) }"
                                    title="Favorite"
                                >
                                    <svg class="w-4 h-4" :fill="($store.favoritesStore && $store.favoritesStore.isFav('glass_code_' + {{ json_encode($displayCode) }})) ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.684a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                    </svg>
                                </button>
                                @if ($codeViberUrl)
                                    <a href="{{ $codeViberUrl }}" data-ios-href="{{ $codeViberIosUrl }}" target="_blank" rel="noopener noreferrer" class="sf-btn-3d px-2.5 py-1.5 rounded-xl font-bold text-xs whitespace-nowrap">Viber</a>
                                @endif
                                @if ($codeTgUrl)
                                    <a href="{{ $codeTgUrl }}" target="_blank" rel="noopener noreferrer" class="sf-btn-3d px-2.5 py-1.5 rounded-xl font-bold text-xs whitespace-nowrap">Telegram</a>
                                @endif
                            </div>
                        </div>

                        {{-- Compatible Phone Models — responsive rows (wraps at any column width) --}}
                        <div class="divide-y divide-slate-100 dark:divide-slate-800/60 -mx-5 sm:mx-0 px-5 sm:px-0">
                            @foreach ($groupItems as $compat)
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 py-2.5 hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition rounded-lg">
                                    <span class="font-extrabold text-sky-600 dark:text-sky-400 text-xs w-20 shrink-0 truncate">{{ $compat->brand }}</span>
                                    <span class="font-bold text-slate-900 dark:text-white text-[12px] min-w-0 flex-1 basis-36">{{ $compat->phone_model }}</span>
                                    <span class="font-mono font-bold text-slate-700 dark:text-slate-300 text-[11px] shrink-0">{{ $compat->glass_code }}</span>
                                    <span class="ml-auto shrink-0">
                                        @if ($compat->isInStock())
                                            <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 whitespace-nowrap">
                                                In Stock
                                            </span>
                                        @else
                                            <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-100 dark:bg-rose-950/80 text-rose-700 dark:text-rose-300 whitespace-nowrap">
                                                Out of Stock
                                            </span>
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Table view: grouped by glass code — actions once per code, models listed below --}}
            <div x-show="glassView === 'table'" x-cloak class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm scrollbar-thin">
                <table class="w-full min-w-[640px] text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-800/60">
                            <th class="text-left py-3 px-3 font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-[11px]">Brand</th>
                            <th class="text-left py-3 px-3 font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-[11px]">Phone Model</th>
                            <th class="text-left py-3 px-3 font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-[11px]">Code</th>
                            <th class="text-center py-3 px-3 font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-[11px]">Status</th>
                            <th class="text-right py-3 px-3 font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-[11px]">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($groupedCompatibles as $normalizedCode => $groupItems)
                            @php
                                $displayCode = $groupItems->first()?->glass_code ?? $normalizedCode;
                                $firstCompat = $groupItems->first();
                                $firstItemId = $firstCompat?->id;
                                $uniqueModels = $groupItems->pluck('phone_model')->unique()->values();
                                $modelsSummary = $uniqueModels->take(3)->implode(', ');
                                $codeMsg = "မင်္ဂလာပါ။ Glass Request:\nဖုန်း: {$firstCompat?->phone_model}\nCode: {$displayCode}\nBrand: {$firstCompat?->brand}";
                                $codeViberUrl = \App\Support\ContactLinkBuilder::viberChatUrl($store?->setting?->viber_number, $codeMsg);
                                $codeViberIosUrl = \App\Support\ContactLinkBuilder::viberIosContactUrl($store?->setting?->viber_number, $codeMsg);
                                $codeTgUrl = \App\Support\ContactLinkBuilder::telegramUrl($store?->setting?->telegram_username, $codeMsg);
                            @endphp
                            <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-800/60">
                                <td colspan="4" class="py-2.5 px-3">
                                    <span class="font-black text-sm text-slate-900 dark:text-white font-outfit">🛡️ Glass Code: <span class="font-mono text-sky-600 dark:text-sky-400 font-extrabold break-all">{{ $displayCode }}</span></span>
                                    <span class="ml-2 text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ $uniqueModels->count() }} {{ __('messages.products') }}</span>
                                </td>
                                <td class="py-2.5 px-3 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            @click.stop.prevent="$store.orderBuilder.addGlassCodeItem({{ json_encode($displayCode) }}, {{ json_encode($modelsSummary) }}, {{ $firstItemId ?? 'null' }})"
                                            type="button"
                                            class="sf-btn-3d-primary relative w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                                            title="{{ __('messages.add_glass_code_title') }}"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                            <span x-show="$store.orderBuilder && $store.orderBuilder.getCodeQty({{ json_encode($displayCode) }}) > 0" class="absolute -top-1.5 -right-1.5 min-w-[16px] h-4 px-0.5 rounded-full bg-rose-500 text-white font-black text-[10px] flex items-center justify-center border border-white shadow-sm" x-text="$store.orderBuilder.getCodeQty({{ json_encode($displayCode) }})"></span>
                                        </button>
                                        <button
                                            @click.stop.prevent="$store.favoritesStore.toggle({ id: 'glass_code_' + {{ json_encode($displayCode) }}, glass_finder_item_id: {{ $firstItemId ?? 'null' }}, name: {{ json_encode('Glass Code: ' . $displayCode . ($modelsSummary ? ' (' . $modelsSummary . ')' : '')) }}, brand: 'Glass', glass_code: {{ json_encode($displayCode) }}, url: '{{ url('/glass-finder?glass_code=' . urlencode($displayCode)) }}' })"
                                            type="button"
                                            class="w-8 h-8 rounded-lg bg-white dark:bg-slate-800 text-slate-600 hover:text-rose-500 border border-slate-200 dark:border-slate-700 flex items-center justify-center transition shrink-0"
                                            :class="{ 'text-rose-500 bg-rose-50 dark:bg-rose-950/80 border-rose-300 dark:border-rose-800': $store.favoritesStore && $store.favoritesStore.isFav('glass_code_' + {{ json_encode($displayCode) }}) }"
                                            title="Favorite"
                                        >
                                            <svg class="w-4 h-4" :fill="($store.favoritesStore && $store.favoritesStore.isFav('glass_code_' + {{ json_encode($displayCode) }})) ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.684a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                            </svg>
                                        </button>
                                        @if ($codeViberUrl)
                                            <a href="{{ $codeViberUrl }}" data-ios-href="{{ $codeViberIosUrl }}" target="_blank" rel="noopener noreferrer" class="sf-btn-3d px-2.5 py-1.5 rounded-lg font-bold text-[11px] whitespace-nowrap">Viber</a>
                                        @endif
                                        @if ($codeTgUrl)
                                            <a href="{{ $codeTgUrl }}" target="_blank" rel="noopener noreferrer" class="sf-btn-3d px-2.5 py-1.5 rounded-lg font-bold text-[11px] whitespace-nowrap">Telegram</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @foreach ($groupItems as $compat)
                                <tr class="border-b border-slate-100 dark:border-slate-800/60 hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                                    <td class="py-2.5 px-3 font-extrabold text-sky-600 dark:text-sky-400 text-xs whitespace-nowrap">{{ $compat->brand }}</td>
                                    <td class="py-2.5 px-3 font-bold text-slate-900 dark:text-white text-[12px] whitespace-nowrap">{{ $compat->phone_model }}</td>
                                    <td class="py-2.5 px-3 font-mono font-bold text-slate-700 dark:text-slate-300 whitespace-nowrap">{{ $compat->glass_code }}</td>
                                    <td class="py-2.5 px-3 text-center">
                                        @if ($compat->isInStock())
                                            <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 whitespace-nowrap">In Stock</span>
                                        @else
                                            <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-100 dark:bg-rose-950/80 text-rose-700 dark:text-rose-300 whitespace-nowrap">Out of Stock</span>
                                        @endif
                                    </td>
                                    <td></td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
