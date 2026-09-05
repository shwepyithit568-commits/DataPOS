@extends('layouts.storefront.app')

@section('noMainPadding', true)

@section('content')
@php
    $storeSlug = $store?->slug ?? request('store_slug');
    $storeDisplayName = $setting?->store_name ?? $store?->name ?? config('app.name');
    $user = auth()->user();
    $isWholesaleApproved = $user && (
        $user->isPlatformOwner() ||
        ($store && $user->getStoreRole($store->id) === 'wholesale_customer')
    );

    $phone = trim((string) ($setting?->phone ?? $store?->phone ?? ''));
    $ftViberUrl = \App\Support\ContactLinkBuilder::viberChatUrl($setting?->viber_number ?? $phone);
    $ftViberIosUrl = \App\Support\ContactLinkBuilder::viberIosContactUrl($setting?->viber_number ?? $phone);
    $ftTelegramUrl = \App\Support\ContactLinkBuilder::telegramUrl($setting?->telegram_username);

    // Dynamic icon resolver for categories
    $iconFor = function (string $name): string {
        $lower = strtolower($name);
        if (str_contains($lower, 'ဖုန်း') || str_contains($lower, 'phone') || str_contains($lower, 'mobile') || str_contains($lower, 'handset'))  return '📱';
        if (str_contains($lower, 'ကွန်ပြူတာ') || str_contains($lower, 'computer') || str_contains($lower, 'laptop') || str_contains($lower, 'macbook')) return '💻';
        if (str_contains($lower, 'glass') || str_contains($lower, 'မှန်') || str_contains($lower, 'screen'))                                 return '🛡️';
        if (str_contains($lower, 'charger') || str_contains($lower, 'cable') || str_contains($lower, 'အားသွင်း') || str_contains($lower, 'adapter')) return '🔌';
        if (str_contains($lower, 'tool') || str_contains($lower, 'service') || str_contains($lower, 'ပြင်ဆင်') || str_contains($lower, 'repair'))  return '🛠️';
        if (str_contains($lower, 'case') || str_contains($lower, 'cover') || str_contains($lower, 'အိတ်') || str_contains($lower, 'pouch'))       return '🖼️';
        if (str_contains($lower, 'camera') || str_contains($lower, 'cctv') || str_contains($lower, 'lens') || str_contains($lower, 'security'))       return '📷';
        if (str_contains($lower, 'spare') || str_contains($lower, 'အပိုပစ္စည်း') || str_contains($lower, 'part') || str_contains($lower, 'ic'))          return '🔧';
        if (str_contains($lower, 'audio') || str_contains($lower, 'speaker') || str_contains($lower, 'headphone') || str_contains($lower, 'earphone') || str_contains($lower, 'နားကြပ်')) return '🎧';
        if (str_contains($lower, 'power') || str_contains($lower, 'battery') || str_contains($lower, 'ဘက်ထရီ') || str_contains($lower, 'bank') || str_contains($lower, 'storage')) return '🔋';
        if (str_contains($lower, 'network') || str_contains($lower, 'router') || str_contains($lower, 'wifi') || str_contains($lower, 'လိုင်း')) return '📡';
        return '📦';
    };
@endphp

<div class="space-y-0.5 sm:space-y-1">

    {{-- =========================================================================
         1. HERO SECTION (Linn IT / Modern 2-Column Layout for Desktop)
            Desktop: Left Category Sidebar (25% / lg:col-span-1) | Right Wide Hero Slider (75% / lg:col-span-3)
            Mobile / Tablet: Full-Width Auto-Play Banner Slider
         ========================================================================= --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-3 sm:gap-4 items-stretch">

        {{-- [Desktop Left Column] 1 col = 25% Category Sidebar with Hover Subcategories Flyout --}}
        <div class="hidden lg:flex lg:col-span-1 min-w-0 flex-col rounded-2xl border border-slate-200/90 bg-white p-3.5 shadow-sm dark:border-slate-800 dark:bg-slate-900 relative z-30"
             x-data="{ activeHover: null, closeTimeout: null }"
             @mouseleave="closeTimeout = setTimeout(() => { activeHover = null }, 200)"
             @mouseenter="if (closeTimeout) clearTimeout(closeTimeout)"
        >
            <div class="flex items-center justify-between pb-2.5 mb-2 border-b border-slate-100 dark:border-slate-800 px-1">
                <span class="flex items-center gap-2 text-xs font-black uppercase tracking-wider text-slate-900 dark:text-white font-myanmar">
                    <span class="text-sky-500 text-sm">🗂️</span> {{ __('messages.categories') }}
                </span>
                <a href="{{ url('/products?store_slug=' . $storeSlug) }}" class="text-[11px] font-bold text-sky-600 dark:text-sky-400 hover:underline font-myanmar">
                    {{ __('messages.view_all') }} →
                </a>
            </div>

            <nav class="space-y-0.5 max-h-[440px] overflow-y-auto pr-0.5 select-none">
                @forelse ($categoryTree as $catRow)
                    @php
                        $cMain = $catRow->category;
                        $cIcon = ($cMain->icon && $cMain->icon !== 'NULL' && $cMain->icon !== 'null') ? $cMain->icon : $iconFor($cMain->name);
                        $hasChildren = $catRow->children->isNotEmpty();
                    @endphp
                    <div class="relative"
                         @mouseenter="if (closeTimeout) clearTimeout(closeTimeout); activeHover = {{ $cMain->id }}"
                    >
                        <a href="{{ url('/products?store_slug=' . $storeSlug . '&category_id=' . $cMain->id) }}"
                           class="group flex items-center justify-between gap-2 px-2.5 py-1.5 rounded-xl text-xs font-bold transition-all"
                           :class="activeHover === {{ $cMain->id }} ? 'bg-sky-50 dark:bg-slate-800 text-sky-600 dark:text-sky-400 font-black' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/60 hover:text-sky-600 dark:hover:text-sky-400'">
                            <span class="flex items-center gap-2.5 min-w-0">
                                <span class="text-base shrink-0 group-hover:scale-110 transition-transform">{{ $cIcon }}</span>
                                <span class="truncate font-myanmar">{{ $cMain->name }}</span>
                            </span>
                            <div class="flex items-center gap-1 shrink-0">
                                @if ($catRow->total > 0)
                                    <span class="text-[10px] font-black px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400 group-hover:bg-sky-100 group-hover:text-sky-700 dark:group-hover:bg-sky-950 dark:group-hover:text-sky-300">
                                        {{ number_format($catRow->total) }}
                                    </span>
                                @endif
                                @if ($hasChildren)
                                    <svg class="h-3 w-3 text-slate-400 group-hover:text-sky-500 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                @endif
                            </div>
                        </a>
                    </div>
                @empty
                    <div class="text-xs text-slate-400 p-2 font-myanmar">{{ __('messages.no_products_hint') }}</div>
                @endforelse
            </nav>

            {{-- Subcategory Flyout Panels (Full Height inset-y-0 directly matching Category Sidebar) --}}
            @foreach ($categoryTree as $catRow)
                @if ($catRow->children->isNotEmpty())
                    @php
                        $cMain = $catRow->category;
                        $cIcon = ($cMain->icon && $cMain->icon !== 'NULL' && $cMain->icon !== 'null') ? $cMain->icon : $iconFor($cMain->name);
                    @endphp
                    <div x-show="activeHover === {{ $cMain->id }}"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 translate-x-1"
                         x-transition:enter-end="opacity-100 translate-x-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 translate-x-0"
                         x-transition:leave-end="opacity-0 translate-x-1"
                         @mouseenter="if (closeTimeout) clearTimeout(closeTimeout); activeHover = {{ $cMain->id }}"
                         @mouseleave="closeTimeout = setTimeout(() => { activeHover = null }, 200)"
                         class="absolute left-full inset-y-0 ml-2 w-80 sm:w-88 md:w-96 bg-white/95 dark:bg-slate-900/95 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-2xl p-3.5 z-50 backdrop-blur-xl flex flex-col before:absolute before:-left-3 before:top-0 before:bottom-0 before:w-3 before:content-['']"
                         style="display: none;"
                    >
                        <div class="flex items-center justify-between pb-2 mb-2.5 border-b border-slate-100 dark:border-slate-800 px-1 shrink-0">
                            <span class="text-xs font-black text-slate-900 dark:text-white font-myanmar flex items-center gap-2 truncate">
                                <span class="text-base">{{ $cIcon }}</span>
                                <span class="truncate">{{ $cMain->name }}</span>
                            </span>
                            <a href="{{ url('/products?store_slug=' . $storeSlug . '&category_id=' . $cMain->id) }}" class="shrink-0 text-[11px] font-bold text-sky-600 dark:text-sky-400 hover:underline font-myanmar flex items-center gap-1">
                                <span>{{ __('messages.view_all') }}</span>
                                <span>→</span>
                            </a>
                        </div>

                        <div class="flex-1 overflow-y-auto pr-1 space-y-0.5 select-none scrollbar-thin scrollbar-thumb-slate-200 dark:scrollbar-thumb-slate-800">
                            @foreach ($catRow->children as $subCat)
                                @php
                                    $subIcon = ($subCat->icon && $subCat->icon !== 'NULL' && $subCat->icon !== 'null') ? $subCat->icon : '▫️';
                                @endphp
                                <a href="{{ url('/products?store_slug=' . $storeSlug . '&category_id=' . $subCat->id) }}"
                                   class="flex items-center justify-between gap-2 px-2.5 py-1.5 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-sky-50 dark:hover:bg-slate-800 hover:text-sky-600 dark:hover:text-sky-400 transition group/sub">
                                    <span class="flex items-center gap-2.5 min-w-0">
                                        <span class="text-xs shrink-0 text-slate-400 group-hover/sub:text-sky-500">{{ $subIcon }}</span>
                                        <span class="truncate font-myanmar">{{ $subCat->name }}</span>
                                    </span>
                                    <div class="flex items-center gap-1 shrink-0">
                                        @if ($subCat->products_count > 0)
                                            <span class="text-[10px] font-black px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400 group-hover/sub:bg-sky-100 group-hover/sub:text-sky-700 dark:group-hover/sub:bg-sky-950 dark:group-hover/sub:text-sky-300">
                                                {{ number_format($subCat->products_count) }}
                                            </span>
                                        @endif
                                        <svg class="h-3 w-3 text-slate-400 opacity-0 group-hover/sub:opacity-100 group-hover/sub:text-sky-500 transition-all group-hover/sub:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        {{-- [Desktop Right Column] 3 cols = 75% Wide Hero Banner Slider --}}
        <div class="col-span-1 lg:col-span-3 min-w-0 flex flex-col">
            @if (count($banners) > 0)
                <div 
                    x-data="{
                        activeSlide: 0,
                        totalSlides: {{ count($banners) }},
                        timer: null,
                        init() { this.start(); },
                        start() {
                            if (this.totalSlides > 1) {
                                this.timer = setInterval(() => { this.next(); }, 4000);
                            }
                        },
                        stop() { if (this.timer) clearInterval(this.timer); },
                        next() { this.activeSlide = (this.activeSlide + 1) % this.totalSlides; },
                        prev() { this.activeSlide = (this.activeSlide - 1 + this.totalSlides) % this.totalSlides; }
                    }"
                    @mouseenter="stop()"
                    @mouseleave="start()"
                    class="relative w-full h-[240px] sm:h-[300px] lg:h-[360px] xl:h-[380px] overflow-hidden rounded-2xl border border-slate-200/90 bg-slate-950 shadow-md dark:border-slate-800 group"
                    style="min-height: 240px;"
                >
                    @foreach ($banners as $index => $banner)
                        @if ($banner->image_path)
                            <a
                                href="{{ $banner->link_url ?: url('/products?store_slug=' . $storeSlug) }}"
                                x-show="activeSlide === {{ $index }}"
                                x-transition:enter="transition ease-out duration-300"
                                x-transition:enter-start="opacity-0 scale-[0.98]"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-200"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                class="absolute inset-0 block w-full h-full cursor-pointer"
                                aria-label="{{ $banner->title }}"
                            >
                                <img
                                    src="{{ asset('storage/' . $banner->image_path) }}"
                                    alt="{{ $banner->title }}"
                                    class="w-full h-full object-cover object-center transform transition duration-700 group-hover:scale-[1.02]"
                                    loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                                    decoding="async"
                                    data-img-fallback="hide-parent"
                                />
                                <div class="sr-only">
                                    <h2>{{ $banner->title }}</h2>
                                    <p>{{ $banner->description ?: __('messages.banner_fallback_caption', ['store' => $storeDisplayName]) }}</p>
                                </div>
                            </a>
                        @endif
                    @endforeach

                    {{-- Navigation Dots & Arrows Controls --}}
                    @if (count($banners) > 1)
                        <div class="absolute bottom-3.5 inset-x-0 z-20 flex items-center justify-between px-4 pointer-events-none">
                            <div class="flex items-center space-x-1.5 pointer-events-auto bg-slate-950/60 backdrop-blur-md px-3 py-1.5 rounded-full border border-white/15 shadow-sm">
                                @foreach ($banners as $index => $banner)
                                    <button
                                        type="button"
                                        @click.prevent.stop="activeSlide = {{ $index }}"
                                        class="h-1.5 rounded-full transition-all duration-300 focus:outline-none"
                                        :class="activeSlide === {{ $index }} ? 'w-6 bg-white shadow-xs' : 'w-1.5 bg-white/40 hover:bg-white/70'"
                                        title="Slide {{ $index + 1 }}"
                                    ></button>
                                @endforeach
                            </div>

                            <div class="hidden sm:flex items-center space-x-2 pointer-events-auto">
                                <button type="button" @click.prevent.stop="prev()" class="w-9 h-9 rounded-xl bg-slate-950/60 hover:bg-slate-950/90 backdrop-blur-md flex items-center justify-center text-white transition active:scale-90 text-sm border border-white/15 shadow-sm" title="Previous Slide">&larr;</button>
                                <button type="button" @click.prevent.stop="next()" class="w-9 h-9 rounded-xl bg-slate-950/60 hover:bg-slate-950/90 backdrop-blur-md flex items-center justify-center text-white transition active:scale-90 text-sm border border-white/15 shadow-sm" title="Next Slide">&rarr;</button>
                            </div>
                        </div>
                    @endif
                </div>
            @else
                {{-- Fallback Hero Banner --}}
                <div class="relative flex-1 min-h-[240px] sm:min-h-[300px] lg:min-h-[340px] p-6 sm:p-8 rounded-2xl border border-slate-200 dark:border-slate-800 bg-gradient-to-br from-sky-500/10 via-white to-violet-500/10 dark:from-slate-900 dark:via-slate-950 dark:to-slate-900 shadow-xs flex flex-col justify-center">
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300 self-start mb-3 font-myanmar">
                        ⚡ {{ __('messages.nationwide_shipping') }}
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-black font-outfit text-slate-900 dark:text-white">
                        {{ $storeDisplayName }}
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 font-myanmar leading-relaxed mt-2 max-w-xl">
                        {{ __('messages.hero_description') }}
                    </p>
                    <div class="flex flex-wrap gap-2.5 pt-4">
                        <a href="{{ url('/products?store_slug=' . $storeSlug) }}" class="px-5 py-2.5 bg-gradient-to-r from-violet-600 to-sky-600 hover:brightness-110 text-white font-bold text-xs rounded-xl shadow-sm transition active:scale-95 font-myanmar">
                            {{ __('messages.view_products') }} →
                        </a>
                        @if (store_can('storefront.glass_finder', $store))
                            <a href="{{ url('/glass-finder?store_slug=' . $storeSlug) }}" class="px-5 py-2.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-white font-bold text-xs rounded-xl hover:bg-slate-50 transition active:scale-95 font-myanmar">
                                {{ __('messages.glass_finder') }}
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Compact 4-Item Value & Service Trust Strip (Directly below Banner Image) --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-2.5 mt-2.5 sm:mt-3">
                <div class="flex items-center gap-2 sm:gap-2.5 p-2 sm:p-2.5 rounded-xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-2xs min-w-0">
                    <span class="flex h-7 w-7 sm:h-8 sm:w-8 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 text-sm sm:text-base border border-sky-200/70 dark:border-sky-900/50 shadow-2xs">⚡</span>
                    <div class="min-w-0">
                        <h4 class="text-[11px] sm:text-xs font-black text-slate-900 dark:text-white font-myanmar leading-tight truncate">{{ __('messages.fast_delivery') }}</h4>
                        <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 font-myanmar truncate font-medium">{{ __('messages.doorstep_and_bus_gate') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 sm:gap-2.5 p-2 sm:p-2.5 rounded-xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-2xs min-w-0">
                    <span class="flex h-7 w-7 sm:h-8 sm:w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400 text-sm sm:text-base border border-emerald-200/70 dark:border-emerald-900/50 shadow-2xs">🛡️</span>
                    <div class="min-w-0">
                        <h4 class="text-[11px] sm:text-xs font-black text-slate-900 dark:text-white font-myanmar leading-tight truncate">{{ __('messages.genuine_warranty') }}</h4>
                        <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 font-myanmar truncate font-medium">100% Original Brand</p>
                    </div>
                </div>
                @if (store_can('service.repair_jobs', $store))
                    <a href="{{ url('/service-tracking?store_slug=' . $storeSlug) }}" class="flex items-center gap-2 sm:gap-2.5 p-2 sm:p-2.5 rounded-xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-2xs group hover:border-violet-300 transition min-w-0">
                        <span class="flex h-7 w-7 sm:h-8 sm:w-8 shrink-0 items-center justify-center rounded-lg bg-violet-50 text-violet-600 dark:bg-violet-950/60 dark:text-violet-400 text-sm sm:text-base border border-violet-200/70 dark:border-violet-900/50 group-hover:scale-105 transition-transform shadow-2xs">🔧</span>
                        <div class="min-w-0">
                            <h4 class="text-[11px] sm:text-xs font-black text-slate-900 dark:text-white font-myanmar leading-tight group-hover:text-violet-600 truncate">{{ __('messages.nav_service_track') }}</h4>
                            <p class="text-[9px] sm:text-[10px] text-violet-600 dark:text-violet-400 font-myanmar truncate font-bold">{{ __('messages.check_repair_status') }}</p>
                        </div>
                    </a>
                @else
                    <div class="flex items-center gap-2 sm:gap-2.5 p-2 sm:p-2.5 rounded-xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-2xs min-w-0">
                        <span class="flex h-7 w-7 sm:h-8 sm:w-8 shrink-0 items-center justify-center rounded-lg bg-violet-50 text-violet-600 dark:bg-violet-950/60 dark:text-violet-400 text-sm sm:text-base border border-violet-200/70 dark:border-violet-900/50 shadow-2xs">💎</span>
                        <div class="min-w-0">
                            <h4 class="text-[11px] sm:text-xs font-black text-slate-900 dark:text-white font-myanmar leading-tight truncate">100% Quality</h4>
                            <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 font-myanmar truncate font-medium">Trusted & Certified</p>
                        </div>
                    </div>
                @endif
                <div x-data="{ directHelpOpen: false }">
                    <button type="button"
                            @click="directHelpOpen = true"
                            class="w-full h-full flex items-center gap-2 sm:gap-2.5 p-2 sm:p-2.5 rounded-xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-2xs min-w-0 text-left hover:border-sky-400 hover:shadow-sm transition active:scale-95 cursor-pointer group">
                        <span class="flex h-7 w-7 sm:h-8 sm:w-8 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 text-sm sm:text-base border border-sky-200/70 dark:border-sky-900/50 shadow-2xs group-hover:scale-105 transition-transform">💬</span>
                        <div class="min-w-0">
                            <h4 class="text-[11px] sm:text-xs font-black text-slate-900 dark:text-white font-myanmar leading-tight truncate group-hover:text-sky-600 transition-colors">{{ __('messages.direct_support') }}</h4>
                            <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 font-myanmar truncate font-medium">{{ __('messages.viber_telegram_chat') }}</p>
                        </div>
                    </button>

                    {{-- Direct Support Modal (Viber & Telegram Choice) --}}
                    <div x-show="directHelpOpen"
                         x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         @keydown.escape.window="directHelpOpen = false"
                         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm">
                        <div @click.away="directHelpOpen = false"
                             x-show="directHelpOpen"
                             x-transition:enter="transition ease-out duration-200 transform"
                             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150 transform"
                             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                             x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                             class="w-full max-w-sm rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-2xl p-5 sm:p-6 space-y-4 relative">
                            
                            {{-- Header & Close Button --}}
                            <div class="flex items-start justify-between gap-3">
                                <div class="space-y-1">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black text-white uppercase shadow-2xs border-0"
                                         style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;">
                                        <span>💬</span>
                                        <span>Direct Support</span>
                                    </div>
                                    <h3 class="text-base font-black text-slate-900 dark:text-white font-myanmar">
                                        တိုက်ရိုက် ဆက်သွယ် မေးမြန်းရန်
                                    </h3>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 font-myanmar leading-relaxed">
                                        လိုချင်သော ပစ္စည်း သို့မဟုတ် အချက်အလက်များကို အောက်ပါ Channel များဖြင့် အလွယ်တကူ မေးမြန်းနိုင်ပါသည်
                                    </p>
                                </div>
                                <button type="button"
                                        @click="directHelpOpen = false"
                                        class="w-8 h-8 shrink-0 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-900 dark:hover:text-white flex items-center justify-center text-sm transition cursor-pointer">
                                    ✕
                                </button>
                            </div>

                            {{-- Channel Buttons --}}
                            <div class="space-y-2.5 pt-1">
                                @if ($ftViberUrl)
                                    <a href="{{ $ftViberUrl }}"
                                       @if ($ftViberIosUrl) data-ios-href="{{ $ftViberIosUrl }}" @endif
                                       style="background: linear-gradient(135deg, #7360F2 0%, #5f4de0 100%) !important; color: #ffffff !important;"
                                       class="w-full min-h-[46px] flex items-center justify-center gap-2.5 px-4 py-3 rounded-2xl text-xs sm:text-sm font-black text-white shadow-md shadow-purple-500/25 hover:brightness-110 active:scale-95 transition cursor-pointer select-none border-0">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-white/20 shrink-0">
                                            <x-brand-icon brand="viber" class="h-4 w-4 fill-white text-white"/>
                                        </span>
                                        <span>Viber ဖြင့် တိုက်ရိုက် မေးမည်</span>
                                    </a>
                                @endif

                                @if ($ftTelegramUrl)
                                    <a href="{{ $ftTelegramUrl }}"
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       style="background: linear-gradient(135deg, #229ED9 0%, #0284c7 100%) !important; color: #ffffff !important;"
                                       class="w-full min-h-[46px] flex items-center justify-center gap-2.5 px-4 py-3 rounded-2xl text-xs sm:text-sm font-black text-white shadow-md shadow-sky-500/25 hover:brightness-110 active:scale-95 transition cursor-pointer select-none border-0">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-white/20 shrink-0">
                                            <x-brand-icon brand="telegram" class="h-4 w-4 fill-white text-white"/>
                                        </span>
                                        <span>Telegram ဖြင့် မေးမည်</span>
                                    </a>
                                @endif

                                @if ($phone)
                                    <a href="tel:{{ \App\Support\ContactLinkBuilder::normalizeMyanmarPhone($phone) }}"
                                       style="background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important; color: #ffffff !important;"
                                       class="w-full min-h-[46px] flex items-center justify-center gap-2.5 px-4 py-3 rounded-2xl text-xs sm:text-sm font-black text-white shadow-md shadow-emerald-500/25 hover:brightness-110 active:scale-95 transition cursor-pointer select-none border-0">
                                        <span class="text-base">📞</span>
                                        <span>ဖုန်းတိုက်ရိုက်ခေါ်မည် ({{ $phone }})</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- =========================================================================
         4. FLASH SALE DEALS (Shopee Flame Style with Live Timer Bar)
         ========================================================================= --}}
    @if (($flashSales->count() + $upcomingSales->count()) > 0)
        @php
            $activeWindowed = $flashSales->filter(fn ($p) => $p->sale_ends_at !== null);
            $evergreen = $flashSales->filter(fn ($p) => $p->sale_ends_at === null);
            $allDeals = $activeWindowed->concat($upcomingSales)->concat($evergreen)->take(20);
            $maxDealPercent = $allDeals->reduce(function (int $carry, $p): int {
                if ($p->old_price !== null && (float) $p->old_price > (float) $p->retail_price) {
                    $percent = (int) round((1 - (float) $p->retail_price / (float) $p->old_price) * 100);
                    return max($carry, $percent);
                }
                return $carry;
            }, 0);
        @endphp
        <div id="flash-sale-section" class="rounded-2xl p-3.5 sm:p-4 border border-rose-500/30 bg-gradient-to-br from-rose-500/5 via-fuchsia-500/5 to-violet-500/5 dark:from-rose-950/20 dark:via-fuchsia-950/20 dark:to-slate-900 shadow-sm">
            <div class="flex items-center justify-between gap-2 flex-wrap pb-2.5 border-b border-rose-200/60 dark:border-rose-900/40">
                <div class="flex items-center gap-2">
                    <h2 class="text-sm sm:text-lg font-black text-slate-900 dark:text-white font-outfit flex items-center gap-1.5">
                        <span class="inline-flex h-7 w-7 sm:h-8 sm:w-8 items-center justify-center rounded-xl bg-gradient-to-tr from-rose-500 to-fuchsia-500 text-white shadow-xs text-sm sm:text-base animate-pulse">
                            🔥
                        </span>
                        <span class="font-myanmar tracking-tight">{{ __('messages.flash_sale') }}</span>
                    </h2>
                    @if ($maxDealPercent > 0)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-rose-600 text-white text-[10px] sm:text-[11px] font-black font-outfit shadow-2xs">
                            -{{ $maxDealPercent }}%
                        </span>
                    @endif
                </div>

                @if ($flashTarget)
                    <div x-data="flashTimer({{ $flashTarget->timestamp * 1000 }})" class="flex items-center gap-1.5 bg-white dark:bg-slate-900 rounded-xl px-2.5 py-1 border border-rose-200 dark:border-rose-900/50 shadow-2xs font-myanmar">
                        <span class="text-[10px] sm:text-[11px] font-bold text-rose-600 dark:text-rose-400 whitespace-nowrap">
                            {{ $flashTargetStarts ? __('messages.starting_soon') : __('messages.sale_ends_in') }}:
                        </span>
                        <template x-if="!expired">
                            <span class="flex items-center gap-1 font-mono font-black text-xs sm:text-sm tabular-nums text-slate-900 dark:text-white">
                                <template x-if="days > 0">
                                    <span>
                                        <span class="bg-slate-900 dark:bg-slate-800 text-white rounded px-1 py-0.5" x-text="String(days).padStart(2,'0')"></span>
                                        <span class="text-[10px] text-slate-500">d</span>
                                    </span>
                                </template>
                                <span class="bg-slate-900 dark:bg-slate-800 text-white rounded px-1.5 py-0.5" x-text="String(hours).padStart(2,'0')"></span>
                                <span class="text-slate-400 font-bold">:</span>
                                <span class="bg-slate-900 dark:bg-slate-800 text-white rounded px-1.5 py-0.5" x-text="String(minutes).padStart(2,'0')"></span>
                                <span class="text-slate-400 font-bold">:</span>
                                <span class="bg-slate-900 dark:bg-slate-800 text-white rounded px-1.5 py-0.5" x-text="String(seconds).padStart(2,'0')"></span>
                            </span>
                        </template>
                        <span x-show="expired" x-cloak class="text-xs font-bold text-slate-500">00:00:00</span>
                    </div>
                @endif
            </div>

            {{-- Horizontal Deals Scroll (Touch Swipe Friendly) --}}
            <div
                x-data="{ isDown: false, startX: 0, scrollLeft: 0 }"
                @mousedown="isDown = true; startX = $event.pageX - $el.offsetLeft; scrollLeft = $el.scrollLeft"
                @mouseleave="isDown = false"
                @mouseup="isDown = false"
                @mousemove="if(isDown){$event.preventDefault();const x=$event.pageX-$el.offsetLeft;const walk=(x-startX)*1.5;$el.scrollLeft=scrollLeft-walk}"
                class="mt-3 flex overflow-x-auto gap-2.5 pb-2 pt-0.5 scrollbar-none cursor-grab active:cursor-grabbing select-none snap-x snap-mandatory"
            >
                @foreach ($allDeals as $deal)
                    @php
                        $isUpcoming = $upcomingSales->contains('id', $deal->id);
                        $dealDefaultVariant = $deal->defaultVariant();
                        $varImg = $dealDefaultVariant?->image_path;
                        $dealImage = ($varImg && $varImg !== 'NULL' && $varImg !== 'null') ? $varImg : $deal->image_path;
                        $dealPercent = ($deal->old_price !== null && (float) $deal->old_price > (float) $deal->retail_price)
                            ? (int) round((1 - (float) $deal->retail_price / (float) $deal->old_price) * 100)
                            : null;
                    @endphp
                    <a href="{{ url('/store/' . $storeSlug . '/product/' . $deal->slug) }}"
                       class="group shrink-0 snap-start w-36 sm:w-44 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-2xs hover:shadow-md hover:border-rose-400 dark:hover:border-rose-500 transition-all duration-200 overflow-hidden bg-white dark:bg-slate-900 flex flex-col">
                        <div class="relative w-full aspect-square bg-slate-100 dark:bg-slate-800 overflow-hidden">
                            @if ($dealImage && $dealImage !== 'NULL' && $dealImage !== 'null')
                                <img src="{{ asset('storage/' . $dealImage) }}" alt="{{ $deal->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200" loading="lazy" decoding="async">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-3xl">📦</div>
                            @endif
                            @if ($dealPercent !== null)
                                <span class="absolute top-1.5 left-1.5 px-1.5 py-0.5 rounded-md bg-rose-600 text-white text-[10px] font-black shadow-xs font-outfit">
                                    -{{ $dealPercent }}%
                                </span>
                            @endif
                            @if ($isUpcoming)
                                <span class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded-md bg-slate-900 text-white text-[9px] font-bold">
                                    {{ __('messages.starting_soon_short') }}
                                </span>
                            @endif
                        </div>
                        <div class="p-2 sm:p-2.5 flex-1 flex flex-col justify-between">
                            <h3 class="text-xs font-bold text-slate-900 dark:text-white leading-snug line-clamp-2 group-hover:text-rose-600 transition-colors font-myanmar">{{ $deal->name }}</h3>
                            <div class="pt-1.5 flex items-baseline justify-center flex-wrap gap-x-2 gap-y-0.5 text-center">
                                @if ($deal->old_price)
                                    <span class="text-xs sm:text-sm text-slate-400 dark:text-slate-500 line-through decoration-rose-500 font-outfit tabular-nums shrink-0 font-medium">
                                        {{ format_currency($deal->old_price, $store) }}
                                    </span>
                                @endif
                                <span class="text-sm sm:text-base font-black text-rose-600 dark:text-rose-400 font-outfit tabular-nums leading-tight">
                                    {{ format_currency($deal->retail_price, $store) }}
                                </span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
    <!-- end flash-sale-section -->

    {{-- =========================================================================
         5. FEATURED PRODUCTS SHOWCASE (လူကြိုက်များသော ပစ္စည်းများ)
         ========================================================================= --}}
    @if ($featuredProducts->count() > 0)
        <div class="space-y-3">
            {{-- Section Header Bar --}}
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-2">
                <div class="flex items-center gap-2">
                    <h2 class="text-sm sm:text-lg font-black text-slate-900 dark:text-white font-outfit flex items-center gap-2">
                        <span class="inline-flex h-7 w-7 sm:h-8 sm:w-8 items-center justify-center rounded-xl bg-gradient-to-tr from-amber-500 to-rose-500 text-white shadow-xs text-sm sm:text-base">
                            🔥
                        </span>
                        <span class="font-myanmar">{{ __('messages.featured_products') }}</span>
                    </h2>
                </div>

                <a href="{{ url('/products?store_slug=' . $storeSlug) }}" class="text-xs font-bold text-sky-600 dark:text-sky-400 hover:underline whitespace-nowrap font-myanmar">
                    {{ __('messages.view_all_products') }} →
                </a>
            </div>

            {{-- Featured Products Grid (2-col mobile, 3-col tablet, 5-col desktop, 6-col widescreen) --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 xl:grid-cols-5 2xl:grid-cols-6 gap-1.5 sm:gap-2 lg:gap-2.5">
                @foreach ($featuredProducts as $product)
                    <x-product-card 
                        :product="$product" 
                        :store="$store" 
                        :isWholesaleApproved="$isWholesaleApproved" 
                        :dense="true"
                    />
                @endforeach
            </div>
        </div>
    @endif

    {{-- =========================================================================
         6. MOST POPULAR CATEGORY CARDS (Uniform Dimension & Fast Grid)
         ========================================================================= --}}
    @if ($categoryTree->isNotEmpty())
        <div class="space-y-3">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2">
                <h2 class="text-sm sm:text-lg font-black text-slate-900 dark:text-white font-myanmar flex items-center gap-2">
                    <span class="inline-flex h-7 w-7 sm:h-8 sm:w-8 items-center justify-center rounded-xl bg-gradient-to-tr from-sky-500 to-indigo-500 text-white shadow-xs text-sm sm:text-base">
                        🏷️
                    </span>
                    <span>{{ __('messages.most_popular_category') }}</span>
                </h2>
                <a href="{{ url('/products?store_slug=' . $storeSlug) }}" class="text-xs font-bold text-sky-600 dark:text-sky-400 hover:underline font-myanmar">
                    {{ __('messages.view_all') }} →
                </a>
            </div>

            <div
                x-data="{ isDown: false, startX: 0, scrollLeft: 0 }"
                @mousedown="isDown = true; startX = $event.pageX - $el.offsetLeft; scrollLeft = $el.scrollLeft"
                @mouseleave="isDown = false"
                @mouseup="isDown = false"
                @mousemove="if(isDown){$event.preventDefault();const x=$event.pageX-$el.offsetLeft;const walk=(x-startX)*1.5;$el.scrollLeft=scrollLeft-walk}"
                class="flex overflow-x-auto gap-2.5 pb-2.5 pt-0.5 cursor-grab active:cursor-grabbing select-none scrollbar-none snap-x snap-mandatory"
            >
                @foreach ($categoryTree as $mainRow)
                    @php
                        $main = $mainRow->category;
                        $mainIcon = ($main->icon && $main->icon !== 'NULL' && $main->icon !== 'null') ? $main->icon : $iconFor($main->name);
                        $cover = $main->image_path ?: $mainRow->cover;
                    @endphp
                    <a href="{{ url('/products?store_slug=' . $storeSlug . '&category_id=' . $main->id) }}"
                       class="group shrink-0 snap-start w-32 sm:w-36 h-[160px] sm:h-[180px] flex flex-col justify-between rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-2 shadow-2xs hover:shadow-md hover:border-sky-400 dark:hover:border-sky-500 transition-all text-center"
                       aria-label="{{ $main->name }}">
                        
                        {{-- Uniform Fixed-Size Image/Icon Container --}}
                        <div class="w-full h-24 sm:h-28 rounded-xl bg-slate-100 dark:bg-slate-800 overflow-hidden flex items-center justify-center relative shrink-0">
                            @if ($cover && $cover !== 'NULL' && $cover !== 'null')
                                <img src="{{ asset('storage/' . $cover) }}" alt="{{ $main->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200" loading="lazy" decoding="async" data-img-fallback="hide-next" />
                                <span class="hidden w-full h-full items-center justify-center text-3xl">{{ $mainIcon }}</span>
                            @else
                                <div class="w-full h-full flex items-center justify-center text-3xl">{{ $mainIcon }}</div>
                            @endif
                            <span class="absolute bottom-1 right-1 px-1.5 py-0.5 rounded-md bg-slate-900/80 text-white text-[9px] font-black tabular-nums font-outfit shadow-xs">
                                {{ number_format($mainRow->total) }}
                            </span>
                        </div>

                        {{-- Uniform Fixed-Height Label Container for Consistent Baseline --}}
                        <div class="h-8 flex items-center justify-center px-0.5">
                            <h3 class="text-xs font-bold text-slate-800 dark:text-slate-200 line-clamp-1 leading-snug group-hover:text-sky-600 transition-colors font-myanmar">
                                {{ $main->name }}
                            </h3>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- =========================================================================
         7. NEW ARRIVALS SHOWCASE (အသစ်ရောက် ပစ္စည်းများ)
         ========================================================================= --}}
    @if (isset($newArrivals) && $newArrivals->count() > 0)
        <div class="space-y-3">
            {{-- Section Header Bar --}}
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-2">
                <div class="flex items-center gap-2">
                    <h2 class="text-sm sm:text-lg font-black text-slate-900 dark:text-white font-outfit flex items-center gap-2">
                        <span class="inline-flex h-7 w-7 sm:h-8 sm:w-8 items-center justify-center rounded-xl bg-gradient-to-tr from-violet-600 to-sky-500 text-white shadow-xs text-sm sm:text-base">
                            ✨
                        </span>
                        <span class="font-myanmar">{{ __('messages.new_arrivals') }}</span>
                    </h2>
                </div>

                <a href="{{ url('/products?store_slug=' . $storeSlug) }}" class="text-xs font-bold text-sky-600 dark:text-sky-400 hover:underline whitespace-nowrap font-myanmar">
                    {{ __('messages.view_all_products') }} →
                </a>
            </div>

            {{-- New Arrivals Products Grid (2-col mobile, 3-col tablet, 5-col desktop, 6-col widescreen) --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 xl:grid-cols-5 2xl:grid-cols-6 gap-1.5 sm:gap-2 lg:gap-2.5">
                @foreach ($newArrivals as $product)
                    <x-product-card 
                        :product="$product" 
                        :store="$store" 
                        :isWholesaleApproved="$isWholesaleApproved" 
                        :dense="true"
                    />
                @endforeach
            </div>
        </div>
    @endif

    @if (store_can('storefront.glass_finder', $store))
        {{-- =========================================================================
             8. GLASS FINDER CTA BANNER (Compact & High Conversion)
             ========================================================================= --}}
        <div class="rounded-2xl p-3.5 sm:p-4 border border-sky-300 dark:border-sky-900/50 bg-gradient-to-r from-sky-50 via-violet-50 to-purple-50 dark:from-slate-900 dark:via-slate-950 dark:to-slate-900 shadow-2xs flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-violet-600 to-sky-500 text-white flex items-center justify-center text-xl shadow-xs shrink-0">
                    📱
                </div>
                <div class="min-w-0">
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-sky-700 dark:text-sky-300 block font-myanmar">
                        ✨ {{ __('messages.fast_glass_match') }}
                    </span>
                    <h3 class="font-black text-xs sm:text-base text-slate-900 dark:text-white font-myanmar truncate">
                        {{ __('messages.glass_finder') }}
                    </h3>
                </div>
            </div>

            <a href="{{ url('/glass-finder?store_slug=' . $storeSlug) }}" class="btn-3d shrink-0 px-3.5 py-2 bg-gradient-to-r from-violet-600 to-sky-600 hover:brightness-110 text-white font-bold text-xs rounded-xl shadow-xs flex items-center gap-1 font-myanmar">
                <span>{{ __('messages.glass_finder') }}</span> →
            </a>
        </div>
    @endif

</div>
@endsection
