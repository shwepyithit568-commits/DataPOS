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
        <div class="hidden lg:flex lg:col-span-1 min-w-0 flex-col rounded-md sm:rounded-lg border border-slate-200/90 bg-white p-3.5 shadow-sm dark:border-slate-800 dark:bg-slate-900 relative z-30"
             x-data="{ activeHover: null, closeTimeout: null }"
             @mouseleave="closeTimeout = setTimeout(() => { activeHover = null }, 200)"
             @mouseenter="if (closeTimeout) clearTimeout(closeTimeout)"
        >
            <div class="flex items-center justify-between pb-2.5 mb-2 border-b border-slate-100 dark:border-slate-800 px-1">
                <span class="flex items-center gap-2 text-base sm:text-lg font-black uppercase tracking-wider text-slate-900 dark:text-white font-myanmar">
                    <span class="text-sky-500 text-lg">🗂️</span> {{ __('messages.categories') }}
                </span>
                <a href="{{ url('/products?store_slug=' . $storeSlug) }}" class="sf-btn-3d active !flex-row px-3 py-1 text-xs sm:text-sm font-black font-myanmar leading-none">
                    {{ __('messages.view_all') }} →
                </a>
            </div>

            <nav class="hero-cat-nav space-y-1 overflow-y-auto pr-0.5 select-none scrollbar-thin" style="max-height: 384px;">
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
                           class="sf-btn-3d w-full !flex-row !justify-between px-3 py-1.5 rounded-md text-sm sm:text-[15px] font-black transition-all group"
                           :class="activeHover === {{ $cMain->id }} ? 'active' : ''">
                            <span class="flex items-center gap-2 min-w-0 flex-1 text-left">
                                <span class="text-base shrink-0 group-hover:scale-110 transition-transform">{{ $cIcon }}</span>
                                <span class="truncate font-black text-sm sm:text-[15px]">{{ $cMain->name }}</span>
                            </span>
                            @if ($hasChildren)
                                <div class="flex items-center shrink-0">
                                    <svg class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" :class="activeHover === {{ $cMain->id }} ? 'text-white' : 'text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                            @endif
                        </a>
                    </div>
                @empty
                    <div class="text-sm font-bold text-slate-400 p-2 font-myanmar">{{ __('messages.no_products_hint') }}</div>
                @endforelse
            </nav>

            {{-- Subcategory Flyout Panels (Compact Width & Pure Localized Microcopy) --}}
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
                         class="absolute left-full inset-y-0 ml-2 w-56 sm:w-64 bg-white dark:bg-slate-900 rounded-md sm:rounded-lg border-2 border-slate-200 dark:border-slate-800 shadow-2xl p-2.5 sm:p-3 z-50 flex flex-col before:absolute before:-left-3 before:top-0 before:bottom-0 before:w-3 before:content-['']"
                         style="display: none;"
                    >
                        <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-100 dark:border-slate-800 px-1 shrink-0">
                            <span class="text-sm sm:text-base font-black text-slate-900 dark:text-white flex items-center gap-2 truncate">
                                <span class="text-base sm:text-lg">{{ $cIcon }}</span>
                                <span class="truncate">{{ $cMain->name }}</span>
                            </span>
                            <a href="{{ url('/products?store_slug=' . $storeSlug . '&category_id=' . $cMain->id) }}" class="sf-btn-3d active !flex-row px-2.5 py-1 text-xs font-black font-myanmar leading-none">
                                <span>{{ __('messages.view_all') }}</span>
                                <span>→</span>
                            </a>
                        </div>

                        <div class="flex-1 overflow-y-auto pr-1 space-y-1 select-none scrollbar-thin">
                            @foreach ($catRow->children as $subCat)
                                @php
                                    $subIcon = ($subCat->icon && $subCat->icon !== 'NULL' && $subCat->icon !== 'null') ? $subCat->icon : '▫️';
                                @endphp
                                <a href="{{ url('/products?store_slug=' . $storeSlug . '&category_id=' . $subCat->id) }}"
                                   class="sf-btn-3d w-full !flex-row !justify-between px-3 py-1.5 rounded-md text-sm sm:text-[15px] font-black transition-all group/sub">
                                    <span class="flex items-center gap-2 min-w-0 flex-1 text-left">
                                        <span class="text-sm shrink-0 text-slate-500 group-hover/sub:text-sky-500">{{ $subIcon }}</span>
                                        <span class="truncate font-black">{{ $subCat->name }}</span>
                                    </span>
                                    <div class="flex items-center shrink-0">
                                        <svg class="h-3.5 w-3.5 text-slate-400 opacity-0 group-hover/sub:opacity-100 group-hover/sub:text-sky-500 transition-all group-hover/sub:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
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
                    class="relative w-full h-[240px] sm:h-[300px] lg:h-[360px] xl:h-[380px] overflow-hidden rounded-md sm:rounded-lg border border-slate-200/90 bg-slate-950 shadow-md dark:border-slate-800 group"
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
                                <button type="button" @click.prevent.stop="prev()" class="w-9 h-9 rounded-md bg-slate-950/60 hover:bg-slate-950/90 backdrop-blur-md flex items-center justify-center text-white transition active:scale-90 text-sm border border-white/15 shadow-sm" title="Previous Slide">&larr;</button>
                                <button type="button" @click.prevent.stop="next()" class="w-9 h-9 rounded-md bg-slate-950/60 hover:bg-slate-950/90 backdrop-blur-md flex items-center justify-center text-white transition active:scale-90 text-sm border border-white/15 shadow-sm" title="Next Slide">&rarr;</button>
                            </div>
                        </div>
                    @endif
                </div>
            @else
                {{-- Fallback Hero Banner --}}
                <div class="relative flex-1 min-h-[240px] sm:min-h-[300px] lg:min-h-[340px] p-6 sm:p-8 rounded-md sm:rounded-lg border border-slate-200 dark:border-slate-800 bg-gradient-to-br from-sky-500/10 via-white to-violet-500/10 dark:from-slate-900 dark:via-slate-950 dark:to-slate-900 shadow-xs flex flex-col justify-center">
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
                        <a href="{{ url('/products?store_slug=' . $storeSlug) }}" class="sf-btn-3d active !flex-row px-5 py-2.5 text-xs font-black rounded-md font-myanmar">
                            {{ __('messages.view_products') }} →
                        </a>
                        @if (store_can('storefront.glass_finder', $store))
                            <a href="{{ url('/glass-finder?store_slug=' . $storeSlug) }}" class="sf-btn-3d !flex-row px-5 py-2.5 text-xs font-black rounded-md font-myanmar">
                                {{ __('messages.glass_finder') }}
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Compact 4-Item Value & Service Trust Strip (Directly below Banner Image) --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-2.5 mt-2.5 sm:mt-3">
                <a href="{{ url('/products?store_slug=' . $storeSlug) }}" class="sf-btn-3d !flex-row !justify-start text-left p-2.5 sm:p-3 rounded-md sm:rounded-lg gap-2.5 sm:gap-3 min-w-0 group">
                    <span class="flex h-9 w-9 sm:h-10 sm:w-10 shrink-0 items-center justify-center rounded-md bg-sky-100 dark:bg-sky-950 text-sky-600 dark:text-sky-400 text-lg sm:text-xl border border-sky-300/60 dark:border-sky-800 shadow-2xs group-hover:scale-105 transition-transform">⚡</span>
                    <div class="min-w-0">
                        <h4 class="text-sm sm:text-base font-black text-slate-900 dark:text-white font-myanmar leading-tight truncate">{{ __('messages.fast_delivery') }}</h4>
                        <p class="text-xs sm:text-[13px] text-slate-600 dark:text-slate-300 font-myanmar truncate font-bold mt-0.5">{{ __('messages.doorstep_and_bus_gate') }}</p>
                    </div>
                </a>
                <a href="{{ url('/products?store_slug=' . $storeSlug) }}" class="sf-btn-3d !flex-row !justify-start text-left p-2.5 sm:p-3 rounded-md sm:rounded-lg gap-2.5 sm:gap-3 min-w-0 group">
                    <span class="flex h-9 w-9 sm:h-10 sm:w-10 shrink-0 items-center justify-center rounded-md bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 text-lg sm:text-xl border border-emerald-300/60 dark:border-emerald-800 shadow-2xs group-hover:scale-105 transition-transform">🛡️</span>
                    <div class="min-w-0">
                        <h4 class="text-sm sm:text-base font-black text-slate-900 dark:text-white font-myanmar leading-tight truncate">{{ __('messages.genuine_warranty') }}</h4>
                        <p class="text-xs sm:text-[13px] text-slate-600 dark:text-slate-300 font-myanmar truncate font-bold mt-0.5">100% Original Brand</p>
                    </div>
                </a>
                @if (store_can('service.repair_jobs', $store))
                    <a href="{{ url('/service-tracking?store_slug=' . $storeSlug) }}" class="sf-btn-3d !flex-row !justify-start text-left p-2.5 sm:p-3 rounded-md sm:rounded-lg gap-2.5 sm:gap-3 min-w-0 group">
                        <span class="flex h-9 w-9 sm:h-10 sm:w-10 shrink-0 items-center justify-center rounded-md bg-violet-100 dark:bg-violet-950 text-violet-600 dark:text-violet-400 text-lg sm:text-xl border border-violet-300/60 dark:border-violet-800 shadow-2xs group-hover:scale-105 transition-transform">🔧</span>
                        <div class="min-w-0">
                            <h4 class="text-sm sm:text-base font-black text-slate-900 dark:text-white font-myanmar leading-tight group-hover:text-violet-600 truncate">{{ __('messages.nav_service_track') }}</h4>
                            <p class="text-xs sm:text-[13px] text-violet-600 dark:text-violet-400 font-myanmar truncate font-bold mt-0.5">{{ __('messages.check_repair_status') }}</p>
                        </div>
                    </a>
                @else
                    <div class="sf-btn-3d !flex-row !justify-start text-left p-2.5 sm:p-3 rounded-md sm:rounded-lg gap-2.5 sm:gap-3 min-w-0">
                        <span class="flex h-9 w-9 sm:h-10 sm:w-10 shrink-0 items-center justify-center rounded-md bg-violet-100 dark:bg-violet-950 text-violet-600 dark:text-violet-400 text-lg sm:text-xl border border-violet-300/60 dark:border-violet-800 shadow-2xs">💎</span>
                        <div class="min-w-0">
                            <h4 class="text-sm sm:text-base font-black text-slate-900 dark:text-white font-myanmar leading-tight truncate">100% Quality</h4>
                            <p class="text-xs sm:text-[13px] text-slate-600 dark:text-slate-300 font-myanmar truncate font-bold mt-0.5">Trusted & Certified</p>
                        </div>
                    </div>
                @endif
                <div x-data="{ directHelpOpen: false }">
                    <button type="button"
                            @click="directHelpOpen = true"
                            class="sf-btn-3d !flex-row !justify-start text-left w-full h-full p-2.5 sm:p-3 rounded-md sm:rounded-lg gap-2.5 sm:gap-3 min-w-0 cursor-pointer group">
                        <span class="flex h-9 w-9 sm:h-10 sm:w-10 shrink-0 items-center justify-center rounded-md bg-sky-100 dark:bg-sky-950 text-sky-600 dark:text-sky-400 text-lg sm:text-xl border border-sky-300/60 dark:border-sky-800 shadow-2xs group-hover:scale-105 transition-transform">💬</span>
                        <div class="min-w-0">
                            <h4 class="text-sm sm:text-base font-black text-slate-900 dark:text-white font-myanmar leading-tight truncate group-hover:text-sky-600 transition-colors">{{ __('messages.direct_support') }}</h4>
                            <p class="text-xs sm:text-[13px] text-slate-600 dark:text-slate-300 font-myanmar truncate font-bold mt-0.5">{{ __('messages.viber_telegram_chat') }}</p>
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
                             class="w-full max-w-sm rounded-xl sm:rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-2xl p-5 sm:p-6 space-y-4 relative">
                            
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
                                       style="background: linear-gradient(135deg, #7360F2 0%, #5f4de0 100%) !important; color: #ffffff !important; border-bottom: 3px solid #4a3cb8 !important;"
                                       class="sf-btn-3d w-full min-h-[46px] !flex-row items-center justify-center gap-2.5 px-4 py-3 rounded-md text-xs sm:text-sm font-black text-white shadow-md shadow-purple-500/25 cursor-pointer select-none">
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
                                       style="background: linear-gradient(135deg, #229ED9 0%, #0284c7 100%) !important; color: #ffffff !important; border-bottom: 3px solid #0369a1 !important;"
                                       class="sf-btn-3d w-full min-h-[46px] !flex-row items-center justify-center gap-2.5 px-4 py-3 rounded-md text-xs sm:text-sm font-black text-white shadow-md shadow-sky-500/25 cursor-pointer select-none">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-white/20 shrink-0">
                                            <x-brand-icon brand="telegram" class="h-4 w-4 fill-white text-white"/>
                                        </span>
                                        <span>Telegram ဖြင့် မေးမည်</span>
                                    </a>
                                @endif

                                @if ($phone)
                                    <a href="tel:{{ \App\Support\ContactLinkBuilder::normalizeMyanmarPhone($phone) }}"
                                       style="background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important; color: #ffffff !important; border-bottom: 3px solid #047857 !important;"
                                       class="sf-btn-3d w-full min-h-[46px] !flex-row items-center justify-center gap-2.5 px-4 py-3 rounded-md text-xs sm:text-sm font-black text-white shadow-md shadow-emerald-500/25 cursor-pointer select-none">
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
        <div id="flash-sale-section"
            x-data="{
                isDown: false,
                startX: 0,
                scrollLeft: 0,
                hasDragged: false,
                canScrollLeft: false,
                canScrollRight: true,
                updateScrollState() {
                    const el = this.$refs.track;
                    if (!el) return;
                    this.canScrollLeft = el.scrollLeft > 4;
                    this.canScrollRight = el.scrollLeft < (el.scrollWidth - el.clientWidth - 4);
                },
                scrollBy(distance) {
                    const el = this.$refs.track;
                    if (!el) return;
                    el.scrollBy({ left: distance, behavior: 'smooth' });
                    setTimeout(() => this.updateScrollState(), 350);
                }
            }"
            x-init="$nextTick(() => updateScrollState())"
            class="rounded-md sm:rounded-lg p-2.5 sm:p-4 border border-rose-500/30 bg-gradient-to-br from-rose-500/5 to-violet-500/5 dark:from-rose-950/20 dark:to-slate-900 shadow-2xs"
        >
            <div class="flex items-center justify-between gap-1.5 sm:gap-2 pb-2 border-b border-rose-200/60 dark:border-rose-900/40">
                <div class="flex items-center gap-1.5 sm:gap-2 min-w-0">
                    <h2 class="text-sm sm:text-lg font-black text-slate-900 dark:text-white font-outfit flex items-center gap-1.5 shrink-0">
                        <span class="inline-flex h-6 w-6 sm:h-8 sm:w-8 items-center justify-center rounded-md bg-gradient-to-tr from-rose-500 to-fuchsia-500 text-white shadow-xs text-xs sm:text-base">
                            🔥
                        </span>
                        <span class="font-myanmar tracking-tight">{{ __('messages.flash_sale') }}</span>
                    </h2>
                    @if ($maxDealPercent > 0)
                        <span class="inline-flex items-center px-1.5 sm:px-2 py-0.5 rounded-md bg-rose-600 text-white text-[9.5px] sm:text-[11px] font-black font-outfit shadow-2xs shrink-0">
                            -{{ $maxDealPercent }}%
                        </span>
                    @endif
                </div>

                <div class="flex items-center gap-1 sm:gap-2 shrink-0">
                    @if ($flashTarget)
                        <div x-data="flashTimer({{ $flashTarget->timestamp * 1000 }})" class="flex items-center gap-1 sm:gap-1.5 bg-white dark:bg-slate-900 rounded-md px-2 sm:px-2.5 py-0.5 sm:py-1 border border-rose-200 dark:border-rose-900/50 shadow-2xs font-myanmar text-[10px] sm:text-[11px]">
                            <span class="font-bold text-rose-600 dark:text-rose-400 whitespace-nowrap">
                                {{ $flashTargetStarts ? __('messages.starting_soon') : __('messages.sale_ends_in') }}:
                            </span>
                            <template x-if="!expired">
                                <span class="flex items-center gap-0.5 sm:gap-1 font-mono font-black text-xs sm:text-sm tabular-nums text-slate-900 dark:text-white">
                                    <template x-if="days > 0">
                                        <span>
                                            <span class="bg-slate-900 dark:bg-slate-800 text-white rounded px-1 py-0.5" x-text="String(days).padStart(2,'0')"></span>
                                            <span class="text-[9px] text-slate-500">d</span>
                                        </span>
                                    </template>
                                    <span class="bg-slate-900 dark:bg-slate-800 text-white rounded px-1 sm:px-1.5 py-0.5" x-text="String(hours).padStart(2,'0')"></span>
                                    <span class="text-slate-400 font-bold">:</span>
                                    <span class="bg-slate-900 dark:bg-slate-800 text-white rounded px-1 sm:px-1.5 py-0.5" x-text="String(minutes).padStart(2,'0')"></span>
                                    <span class="text-slate-400 font-bold">:</span>
                                    <span class="bg-slate-900 dark:bg-slate-800 text-white rounded px-1 sm:px-1.5 py-0.5" x-text="String(seconds).padStart(2,'0')"></span>
                                </span>
                            </template>
                            <span x-show="expired" x-cloak class="text-xs font-bold text-slate-500">00:00:00</span>
                        </div>
                    @endif

                    {{-- Smooth Scroll Prev/Next Buttons (Desktop/Tablet only, touch users swipe) --}}
                    <div class="hidden sm:flex items-center gap-1">
                        <button
                            type="button"
                            @click="scrollBy(-320)"
                            class="sf-btn-3d w-7 h-7 sm:w-8 sm:h-8 !p-0 items-center justify-center text-xs font-black text-slate-700 dark:text-slate-300 disabled:opacity-30 disabled:pointer-events-none transition-opacity"
                            :disabled="!canScrollLeft"
                            aria-label="Scroll left"
                        >
                            ❮
                        </button>

                        <button
                            type="button"
                            @click="scrollBy(320)"
                            class="sf-btn-3d w-7 h-7 sm:w-8 sm:h-8 !p-0 items-center justify-center text-xs font-black text-slate-700 dark:text-slate-300 disabled:opacity-30 disabled:pointer-events-none transition-opacity"
                            :disabled="!canScrollRight"
                            aria-label="Scroll right"
                        >
                            ❯
                        </button>
                    </div>
                </div>
            </div>

            {{-- Horizontal Deals Scroll (Touch Swipe Friendly & Smooth Drag) --}}
            <div
                x-ref="track"
                @scroll.passive="updateScrollState()"
                @mousedown="isDown = true; hasDragged = false; startX = $event.pageX - $el.offsetLeft; scrollLeft = $el.scrollLeft"
                @mouseleave="isDown = false"
                @mouseup="setTimeout(() => { hasDragged = false; isDown = false; }, 50)"
                @mousemove="if(isDown){
                    const x = $event.pageX - $el.offsetLeft;
                    const walk = x - startX;
                    if (Math.abs(walk) > 4) {
                        hasDragged = true;
                        $event.preventDefault();
                        $el.scrollLeft = scrollLeft - walk;
                    }
                }"
                @click.capture="if(hasDragged) { $event.preventDefault(); $event.stopPropagation(); }"
                class="mt-2.5 flex overflow-x-auto gap-1 sm:gap-1.5 pb-2 pt-0.5 select-none scrollbar-none overscroll-x-contain"
                :class="isDown ? 'cursor-grabbing snap-none' : 'cursor-grab scroll-smooth snap-x snap-proximity'"
                style="-webkit-overflow-scrolling: touch;"
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
                       class="group shrink-0 snap-start w-36 sm:w-44 rounded-none border border-slate-200/90 dark:border-slate-800 shadow-2xs hover:shadow-md hover:border-rose-400 dark:hover:border-rose-500 transition-all duration-200 overflow-hidden bg-white dark:bg-slate-900 flex flex-col">
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

                <a href="{{ url('/products?store_slug=' . $storeSlug) }}" class="sf-btn-3d active !flex-row px-2.5 sm:px-3 py-1 text-xs font-black font-myanmar leading-none shrink-0">
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
        <div
            x-data="{
                isDown: false,
                startX: 0,
                scrollLeft: 0,
                hasDragged: false,
                canScrollLeft: false,
                canScrollRight: true,
                updateScrollState() {
                    const el = this.$refs.track;
                    if (!el) return;
                    this.canScrollLeft = el.scrollLeft > 4;
                    this.canScrollRight = el.scrollLeft < (el.scrollWidth - el.clientWidth - 4);
                },
                scrollBy(distance) {
                    const el = this.$refs.track;
                    if (!el) return;
                    el.scrollBy({ left: distance, behavior: 'smooth' });
                    setTimeout(() => this.updateScrollState(), 350);
                }
            }"
            x-init="$nextTick(() => updateScrollState())"
            class="space-y-2 relative"
        >
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2">
                <h2 class="text-sm sm:text-lg font-black text-slate-900 dark:text-white font-myanmar flex items-center gap-2">
                    <span class="inline-flex h-7 w-7 sm:h-8 sm:w-8 items-center justify-center rounded-xl bg-gradient-to-tr from-sky-500 to-indigo-500 text-white shadow-xs text-sm sm:text-base">
                        🏷️
                    </span>
                    <span>{{ __('messages.most_popular_category') }}</span>
                </h2>

                <div class="flex items-center gap-1.5">
                    {{-- Smooth Scroll Prev/Next Buttons (Desktop/Tablet only) --}}
                    <div class="hidden sm:flex items-center gap-1">
                        <button
                            type="button"
                            @click="scrollBy(-280)"
                            class="sf-btn-3d w-7 h-7 sm:w-8 sm:h-8 !p-0 flex items-center justify-center text-xs font-black text-slate-700 dark:text-slate-300 disabled:opacity-30 disabled:pointer-events-none transition-opacity"
                            :disabled="!canScrollLeft"
                            aria-label="Scroll left"
                        >
                            ❮
                        </button>

                        <button
                            type="button"
                            @click="scrollBy(280)"
                            class="sf-btn-3d w-7 h-7 sm:w-8 sm:h-8 !p-0 flex items-center justify-center text-xs font-black text-slate-700 dark:text-slate-300 disabled:opacity-30 disabled:pointer-events-none transition-opacity"
                            :disabled="!canScrollRight"
                            aria-label="Scroll right"
                        >
                            ❯
                        </button>
                    </div>

                    <a href="{{ url('/browse?store_slug=' . $storeSlug) }}" class="sf-btn-3d active !flex-row px-2.5 sm:px-3 py-1 text-xs font-black font-myanmar leading-none shrink-0 ml-1">
                        {{ __('messages.view_all') }} →
                    </a>
                </div>
            </div>

            <div
                x-ref="track"
                @scroll.passive="updateScrollState()"
                @mousedown="isDown = true; hasDragged = false; startX = $event.pageX - $el.offsetLeft; scrollLeft = $el.scrollLeft"
                @mouseleave="isDown = false"
                @mouseup="setTimeout(() => { hasDragged = false; isDown = false; }, 50)"
                @mousemove="if(isDown){
                    const x = $event.pageX - $el.offsetLeft;
                    const walk = x - startX;
                    if (Math.abs(walk) > 4) {
                        hasDragged = true;
                        $event.preventDefault();
                        $el.scrollLeft = scrollLeft - walk;
                    }
                }"
                @click.capture="if(hasDragged) { $event.preventDefault(); $event.stopPropagation(); }"
                class="flex overflow-x-auto gap-2 sm:gap-2.5 px-0.5 pt-1 pb-2.5 select-none scrollbar-none overscroll-x-contain"
                :class="isDown ? 'cursor-grabbing snap-none' : 'cursor-grab scroll-smooth snap-x snap-proximity'"
                style="-webkit-overflow-scrolling: touch;"
            >
                @foreach ($categoryTree as $mainRow)
                    @php
                        $main = $mainRow->category;
                        $mainIcon = ($main->icon && $main->icon !== 'NULL' && $main->icon !== 'null') ? $main->icon : $iconFor($main->name);
                        $cover = $main->image_path ?: $mainRow->cover;
                    @endphp
                    <a href="{{ url('/browse?store_slug=' . $storeSlug . '&category_id=' . $main->id) }}"
                       class="sf-btn-3d group shrink-0 snap-start w-28 sm:w-32 aspect-[3/4] flex flex-col p-0 relative text-center overflow-hidden select-none transition-all duration-150"
                       aria-label="{{ $main->name }}">
                        
                        {{-- Count Badge in top right corner (Browse style) --}}
                        <span class="absolute top-1 right-1 rounded-full px-1.5 py-0.2 text-[8px] sm:text-[9px] font-black z-10 shadow-xs bg-black/60 text-white backdrop-blur-xs tabular-nums font-outfit">
                            {{ number_format($mainRow->total) }}
                        </span>

                        {{-- Upper 3/4: Edge-to-edge full bleed image with no side borders --}}
                        <div class="relative w-full h-[77%] sm:h-[80%] bg-slate-100 dark:bg-slate-800/90 overflow-hidden shrink-0 border-b border-slate-200/50 dark:border-slate-700/50 flex items-center justify-center">
                            @if ($cover && $cover !== 'NULL' && $cover !== 'null')
                                <img src="{{ asset('storage/' . $cover) }}" alt="{{ $main->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200 pointer-events-none" loading="lazy" decoding="async" data-img-fallback="hide-next" />
                                <span class="hidden w-full h-full items-center justify-center text-2xl sm:text-3xl">{{ $mainIcon }}</span>
                            @else
                                <div class="w-full h-full flex items-center justify-center text-2xl sm:text-3xl">{{ $mainIcon }}</div>
                            @endif
                        </div>

                        {{-- Lower 1/4: Category Name strip --}}
                        <div class="flex-1 w-full flex items-center justify-center px-1 py-0 min-h-0 bg-white dark:bg-slate-800">
                            <span class="block text-[9.5px] sm:text-[10.5px] font-black leading-tight line-clamp-2 w-full text-center text-slate-800 dark:text-slate-200 group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors font-myanmar">
                                {{ $main->name }}
                            </span>
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

                <a href="{{ url('/products?store_slug=' . $storeSlug) }}" class="sf-btn-3d active !flex-row px-2.5 sm:px-3 py-1 text-xs font-black font-myanmar leading-none shrink-0">
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

            <a href="{{ url('/glass-finder?store_slug=' . $storeSlug) }}" class="sf-btn-3d active shrink-0 !flex-row px-4 py-2 text-xs font-black rounded-xl font-myanmar leading-none shadow-xs">
                <span>{{ __('messages.glass_finder') }}</span> →
            </a>
        </div>
    @endif

</div>
@endsection
