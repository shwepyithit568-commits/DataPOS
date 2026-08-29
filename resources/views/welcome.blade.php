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
    $ftTelegramUrl = \App\Support\ContactLinkBuilder::telegramUrl($setting?->telegram_username);

    // Icon resolver helper for categories
    $iconFor = function (string $name): string {
        $lower = strtolower($name);
        if (str_contains($lower, 'ဖုန်း') || str_contains($lower, 'phone') || str_contains($lower, 'mobile'))  return '📱';
        if (str_contains($lower, 'ကွန်ပြူတာ') || str_contains($lower, 'computer') || str_contains($lower, 'laptop') || str_contains($lower, 'macbook')) return '💻';
        if (str_contains($lower, 'glass') || str_contains($lower, 'မှန်'))                                 return '🛡️';
        if (str_contains($lower, 'charger') || str_contains($lower, 'cable') || str_contains($lower, 'အားသွင်း')) return '🔌';
        if (str_contains($lower, 'tool') || str_contains($lower, 'service') || str_contains($lower, 'ပြင်ဆင်'))  return '🛠️';
        if (str_contains($lower, 'case') || str_contains($lower, 'cover') || str_contains($lower, 'အိတ်'))       return '🖼️';
        if (str_contains($lower, 'camera') || str_contains($lower, 'cctv') || str_contains($lower, 'lens'))       return '📷';
        if (str_contains($lower, 'spare part') || str_contains($lower, 'အပိုပစ္စည်း'))                          return '🔧';
        if (str_contains($lower, 'audio') || str_contains($lower, 'speaker') || str_contains($lower, 'headphone') || str_contains($lower, 'နားကြပ်')) return '🎧';
        if (str_contains($lower, 'network') || str_contains($lower, 'router') || str_contains($lower, 'လိုင်း')) return '📡';
        return '📦';
    };
@endphp

<div class="space-y-[2px] sm:space-y-4 lg:space-y-6">

    {{-- =========================================================================
         1. DESKTOP 3-COLUMN HERO SECTION (AliExpress / Amazon Pro Style)
            Left: 1 col (25%), Center: 2 cols (50%), Right: 1 col (25%)
         ========================================================================= --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-3 sm:gap-4 items-stretch">

        {{-- [Desktop Left Rail] Category Quick Navigation (1 col = 25%) --}}
        <div class="hidden lg:flex lg:col-span-1 min-w-0 flex-col rounded-2xl border border-slate-200 bg-white p-3.5 shadow-xs dark:border-slate-800 dark:bg-slate-900 justify-between">
            <div>
                <div class="flex items-center justify-between pb-2.5 mb-2 border-b border-slate-100 dark:border-slate-800 px-1">
                    <span class="flex items-center gap-2 text-xs font-black uppercase tracking-wider text-slate-900 dark:text-white font-myanmar">
                        <span class="text-sky-500">🗂️</span> {{ __('messages.categories') }}
                    </span>
                    <a href="{{ url('/products?store_slug=' . $storeSlug) }}" class="text-[11px] font-bold text-sky-600 dark:text-sky-400 hover:underline font-myanmar">
                        {{ __('messages.view_all') }} →
                    </a>
                </div>

                <nav class="space-y-1">
                    @forelse ($categoryTree->take(8) as $catRow)
                        @php
                            $cMain = $catRow->category;
                            $cIcon = $cMain->icon ?: $iconFor($cMain->name);
                        @endphp
                        <a href="{{ url('/products?store_slug=' . $storeSlug . '&category_id=' . $cMain->id) }}"
                           class="group flex items-center justify-between gap-2 px-2.5 py-2 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-sky-50 dark:hover:bg-slate-800 hover:text-sky-600 dark:hover:text-sky-400 transition-all">
                            <span class="flex items-center gap-2.5 min-w-0">
                                <span class="text-base shrink-0 group-hover:scale-110 transition-transform">{{ $cIcon }}</span>
                                <span class="truncate font-myanmar">{{ $cMain->name }}</span>
                            </span>
                            <span class="shrink-0 text-[10px] font-black px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400 group-hover:bg-sky-100 group-hover:text-sky-700 dark:group-hover:bg-sky-950 dark:group-hover:text-sky-300">
                                {{ number_format($catRow->total) }}
                            </span>
                        </a>
                    @empty
                        <div class="text-xs text-slate-400 p-2 font-myanmar">{{ __('messages.no_products_hint') }}</div>
                    @endforelse
                </nav>
            </div>

            {{-- Quick Glass Finder shortcut at bottom of rail --}}
            <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800">
                <a href="{{ url('/glass-finder?store_slug=' . $storeSlug) }}" class="flex items-center gap-2.5 p-2 rounded-xl bg-gradient-to-r from-violet-50 to-fuchsia-50 dark:from-violet-950/40 dark:to-fuchsia-950/40 text-violet-800 dark:text-violet-300 text-xs font-black border border-violet-200/60 dark:border-violet-800/60 hover:brightness-105 transition">
                    <span class="text-xl">📱</span>
                    <div class="min-w-0">
                        <span class="block text-[11px] leading-tight font-myanmar">{{ __('messages.find_model_glass') }}</span>
                        <span class="block text-[9px] text-violet-600 dark:text-violet-400 font-bold">{{ __('messages.glass_finder') }} →</span>
                    </div>
                </a>
            </div>
        </div>

        {{-- [Center Column] Main Interactive Carousel Slider (Mobile: full-width, Desktop: 2 cols = 50%) --}}
        <div class="col-span-1 lg:col-span-2 min-w-0 flex flex-col">
            @if ($banners->count() > 0)
                <div 
                    x-data="{
                        activeSlide: 0,
                        totalSlides: {{ $banners->count() }},
                        timer: null,
                        init() { this.start(); },
                        start() {
                            if (this.totalSlides > 1) {
                                this.timer = setInterval(() => { this.next(); }, 5000);
                            }
                        },
                        stop() { if (this.timer) clearInterval(this.timer); },
                        next() { this.activeSlide = (this.activeSlide + 1) % this.totalSlides; },
                        prev() { this.activeSlide = (this.activeSlide - 1 + this.totalSlides) % this.totalSlides; }
                    }"
                    @mouseenter="stop()"
                    @mouseleave="start()"
                    class="relative flex-1 min-h-[240px] sm:min-h-[280px] lg:min-h-[360px] overflow-hidden rounded-2xl border border-slate-200/80 bg-gradient-to-br from-slate-900 via-sky-950 to-violet-950 text-white shadow-md dark:border-slate-800 group flex flex-col justify-end"
                >
                    {{-- Ambient background glows --}}
                    <div class="absolute -top-20 -right-20 w-64 h-64 bg-sky-500/20 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="absolute -bottom-20 -left-20 w-64 h-64 bg-violet-500/20 rounded-full blur-3xl pointer-events-none"></div>

                    @foreach ($banners as $index => $banner)
                        @if ($banner->image_path)
                            <div
                                x-show="activeSlide === {{ $index }}"
                                x-transition.opacity.duration.500ms
                                class="absolute inset-0"
                            >
                                <img
                                    src="{{ asset('storage/' . $banner->image_path) }}"
                                    alt="{{ $banner->title }}"
                                    class="h-full w-full object-cover"
                                    loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                                    decoding="async"
                                    data-img-fallback="hide-parent"
                                />
                            </div>
                        @endif
                    @endforeach

                    {{-- Gradient Overlay for readable text --}}
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/95 via-slate-950/40 to-transparent z-[1]"></div>

                    {{-- Banner Content --}}
                    <div class="relative z-10 p-4 sm:p-6 lg:p-7">
                        <div class="grid">
                            @foreach ($banners as $index => $banner)
                                <div
                                    :class="activeSlide === {{ $index }}
                                        ? 'opacity-100 translate-y-0 pointer-events-auto'
                                        : 'opacity-0 translate-y-2 pointer-events-none'"
                                    :aria-hidden="activeSlide === {{ $index }} ? 'false' : 'true'"
                                    class="max-w-xl col-start-1 row-start-1 transition-all duration-500 ease-out"
                                >
                                    <div class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 mb-2 rounded-full text-[10px] font-black bg-gradient-to-r from-violet-600 to-fuchsia-500 text-white shadow-sm font-myanmar">
                                        <span>⚡ {{ __('messages.special_offer') }}</span>
                                    </div>

                                    <h2 class="text-base sm:text-2xl lg:text-3xl font-black font-outfit leading-tight tracking-tight text-white drop-shadow-md line-clamp-2">
                                        {{ $banner->title }}
                                    </h2>

                                    <p class="text-xs sm:text-sm text-slate-200 font-myanmar leading-relaxed drop-shadow-sm line-clamp-2 mt-1 font-medium">
                                        {{ $banner->description ?: __('messages.banner_fallback_caption', ['store' => $storeDisplayName]) }}
                                    </p>

                                    @if ($banner->link_url)
                                        <a href="{{ $banner->link_url }}" class="inline-flex items-center space-x-1.5 mt-3 px-4 py-2 bg-gradient-to-r from-violet-600 via-fuchsia-500 to-rose-500 hover:brightness-110 text-white font-extrabold text-xs rounded-xl shadow-md transition active:scale-95">
                                            <span>{{ __('messages.view_detail') }}</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                        </a>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        {{-- Controls & Indicators --}}
                        @if ($banners->count() > 1)
                            <div class="flex items-center justify-between mt-3 pt-2 border-t border-white/10">
                                <div class="flex items-center space-x-1.5">
                                    @foreach ($banners as $index => $banner)
                                        <button
                                            @click="activeSlide = {{ $index }}"
                                            class="h-1.5 rounded-full transition-all duration-300 focus:outline-none"
                                            :class="activeSlide === {{ $index }} ? 'w-6 bg-sky-400 shadow-sm' : 'w-1.5 bg-white/40 hover:bg-white/70'"
                                            title="Slide {{ $index + 1 }}"
                                        ></button>
                                    @endforeach
                                </div>

                                <div class="flex items-center space-x-1.5">
                                    <button @click="prev()" class="w-7 h-7 rounded-lg bg-white/20 hover:bg-white/30 backdrop-blur-sm flex items-center justify-center text-white transition active:scale-90 text-xs" title="Previous Slide">&larr;</button>
                                    <button @click="next()" class="w-7 h-7 rounded-lg bg-white/20 hover:bg-white/30 backdrop-blur-sm flex items-center justify-center text-white transition active:scale-90 text-xs" title="Next Slide">&rarr;</button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                {{-- Fallback Hero Banner --}}
                <div class="relative flex-1 p-6 sm:p-8 rounded-2xl border border-slate-200 dark:border-slate-800 bg-gradient-to-br from-sky-500/10 via-white to-violet-500/10 dark:from-slate-900 dark:via-slate-950 dark:to-slate-900 shadow-xs flex flex-col justify-center">
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300 self-start mb-3 font-myanmar">
                        ⚡ {{ __('messages.nationwide_shipping') }}
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-black font-outfit text-slate-900 dark:text-white">
                        {{ $storeDisplayName }}
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 font-myanmar leading-relaxed mt-2 max-w-lg">
                        {{ __('messages.hero_description') }}
                    </p>
                    <div class="flex flex-wrap gap-2.5 pt-4">
                        <a href="{{ url('/products?store_slug=' . $storeSlug) }}" class="px-5 py-2.5 bg-gradient-to-r from-violet-600 to-sky-600 hover:brightness-110 text-white font-bold text-xs rounded-xl shadow-sm transition active:scale-95 font-myanmar">
                            {{ __('messages.view_products') }} →
                        </a>
                        <a href="{{ url('/glass-finder?store_slug=' . $storeSlug) }}" class="px-5 py-2.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-white font-bold text-xs rounded-xl hover:bg-slate-50 transition active:scale-95 font-myanmar">
                            {{ __('messages.glass_finder') }}
                        </a>
                    </div>
                </div>
            @endif
        </div>

        {{-- [Desktop Right Column] User Perks & Service Hub (1 col = 25%) --}}
        <div class="hidden lg:flex lg:col-span-1 min-w-0 flex-col gap-3">
            
            {{-- User Welcome / Quick Login Card --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-violet-600 via-fuchsia-500 to-sky-500 text-white flex items-center justify-center font-bold text-sm shadow-xs shrink-0">
                        {{ $user ? mb_substr($user->name, 0, 1) : '👤' }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 font-myanmar font-semibold">{{ __('messages.welcome_greeting') }}</p>
                        <h4 class="text-xs font-black text-slate-900 dark:text-white truncate font-outfit">
                            {{ $user ? $user->name : __('messages.guest') }}
                        </h4>
                    </div>
                </div>

                <div class="mt-3 flex gap-2">
                    @auth
                        <a href="{{ url('/account?store_slug=' . $storeSlug) }}" class="flex-1 py-2 px-2.5 rounded-xl bg-sky-50 text-sky-700 dark:bg-sky-950/50 dark:text-sky-300 text-xs font-extrabold text-center hover:bg-sky-100 transition font-myanmar">
                            👤 {{ __('messages.account_and_orders') }}
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="flex-1 py-2 px-2.5 rounded-xl bg-gradient-to-r from-violet-600 to-sky-600 text-white text-xs font-bold text-center hover:brightness-110 shadow-2xs transition font-myanmar">
                            {{ __('messages.login') }}
                        </a>
                        <a href="{{ route('register') }}" class="flex-1 py-2 px-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold text-center hover:bg-slate-50 dark:hover:bg-slate-800 transition font-myanmar">
                            {{ __('messages.register') }}
                        </a>
                    @endauth
                </div>
            </div>

            {{-- Live Service Tracking Quick Lookup Box --}}
            <div class="rounded-2xl border border-violet-200 bg-gradient-to-br from-violet-50/80 to-purple-50/80 p-3.5 dark:border-violet-900/40 dark:bg-slate-900/80 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-1.5 text-xs font-extrabold text-violet-800 dark:text-violet-300 font-myanmar">
                        <span>🔧</span> {{ __('messages.nav_service_track') }}
                    </span>
                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-black">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Live
                    </span>
                </div>
                <p class="text-[11px] text-slate-600 dark:text-slate-400 font-myanmar leading-tight font-medium">
                    {{ __('messages.service_track_desc') }}
                </p>
                <a href="{{ url('/service-tracking?store_slug=' . $storeSlug) }}" class="inline-flex w-full items-center justify-center gap-1.5 py-2 rounded-xl bg-violet-600 text-white text-xs font-bold shadow-2xs hover:bg-violet-700 transition font-myanmar">
                    <span>{{ __('messages.click_to_check') }}</span> →
                </a>
            </div>

            {{-- Hotline & Direct Consultation Shortcut --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-3.5 dark:border-slate-800 dark:bg-slate-900 flex-1 flex flex-col justify-between">
                <div class="space-y-1">
                    <span class="text-[11px] font-bold text-slate-600 dark:text-slate-400 block font-myanmar">{{ __('messages.direct_help_prompt') }}</span>
                    @if ($phone)
                        <a href="tel:{{ $phone }}" class="font-mono font-bold text-slate-900 dark:text-white hover:text-sky-600 text-xs block">
                            📞 {{ $phone }}
                        </a>
                    @endif
                </div>

                <div class="flex gap-2 pt-2">
                    @if ($ftViberUrl)
                        <a href="{{ $ftViberUrl }}" target="_blank" rel="noopener noreferrer" class="flex-1 inline-flex items-center justify-center gap-1 py-1.5 rounded-lg border border-violet-300 bg-violet-50 text-violet-700 dark:bg-violet-950/40 dark:text-violet-300 text-xs font-bold hover:bg-violet-100 transition">
                            <x-brand-icon brand="viber" class="h-3 w-3 fill-current"/>
                            <span>Viber</span>
                        </a>
                    @endif
                    @if ($ftTelegramUrl)
                        <a href="{{ $ftTelegramUrl }}" target="_blank" rel="noopener noreferrer" class="flex-1 inline-flex items-center justify-center gap-1 py-1.5 rounded-lg border border-sky-300 bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300 text-xs font-bold hover:bg-sky-100 transition">
                            <x-brand-icon brand="telegram" class="h-3 w-3 fill-current"/>
                            <span>Telegram</span>
                        </a>
                    @endif
                </div>
            </div>

        </div>
    </div>

    {{-- =========================================================================
         1.1. MOBILE 1-TAP QUICK ACTION SHORTCUTS (5 Quick Action Pills)
         ========================================================================= --}}
    <div class="lg:hidden grid gap-1 py-1 px-0.5" style="grid-template-columns: repeat(5, minmax(0, 1fr));">
        <a href="{{ url('/products?store_slug=' . $storeSlug) }}" class="flex flex-col items-center gap-1 p-1 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 text-center shadow-2xs active:scale-95 transition">
            <span class="w-9 h-9 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center text-lg shrink-0">🛍️</span>
            <span class="text-[10px] font-bold text-slate-800 dark:text-slate-200 truncate w-full font-myanmar">{{ __('messages.products') }}</span>
        </a>
        <a href="{{ url('/glass-finder?store_slug=' . $storeSlug) }}" class="flex flex-col items-center gap-1 p-1 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 text-center shadow-2xs active:scale-95 transition">
            <span class="w-9 h-9 rounded-xl bg-violet-50 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 flex items-center justify-center text-lg shrink-0">📱</span>
            <span class="text-[10px] font-bold text-slate-800 dark:text-slate-200 truncate w-full font-myanmar">{{ __('messages.glass_finder') }}</span>
        </a>
        <a href="{{ url('/service-tracking?store_slug=' . $storeSlug) }}" class="flex flex-col items-center gap-1 p-1 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 text-center shadow-2xs active:scale-95 transition">
            <span class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-lg shrink-0 relative">
                🔧
                <span class="w-2 h-2 rounded-full bg-emerald-500 absolute top-1 right-1 animate-pulse"></span>
            </span>
            <span class="text-[10px] font-bold text-slate-800 dark:text-slate-200 truncate w-full font-myanmar">{{ __('messages.nav_service_track') }}</span>
        </a>
        <a href="{{ url('/how-to-order?store_slug=' . $storeSlug) }}" class="flex flex-col items-center gap-1 p-1 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 text-center shadow-2xs active:scale-95 transition">
            <span class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center text-lg shrink-0">📖</span>
            <span class="text-[10px] font-bold text-slate-800 dark:text-slate-200 truncate w-full font-myanmar">{{ __('messages.how_to_order') }}</span>
        </a>
        @if ($ftViberUrl || $ftTelegramUrl)
            <a href="{{ $ftViberUrl ?: $ftTelegramUrl }}" target="_blank" rel="noopener noreferrer" class="flex flex-col items-center gap-1 p-1 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 text-center shadow-2xs active:scale-95 transition">
                <span class="w-9 h-9 rounded-xl bg-fuchsia-50 dark:bg-fuchsia-950/60 text-fuchsia-600 dark:text-fuchsia-400 flex items-center justify-center text-lg shrink-0">💬</span>
                <span class="text-[10px] font-bold text-slate-800 dark:text-slate-200 truncate w-full font-myanmar">{{ __('messages.contact') }}</span>
            </a>
        @else
            <a href="{{ url('/account?store_slug=' . $storeSlug) }}" class="flex flex-col items-center gap-1 p-1 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 text-center shadow-2xs active:scale-95 transition">
                <span class="w-9 h-9 rounded-xl bg-fuchsia-50 dark:bg-fuchsia-950/60 text-fuchsia-600 dark:text-fuchsia-400 flex items-center justify-center text-lg shrink-0">👤</span>
                <span class="text-[10px] font-bold text-slate-800 dark:text-slate-200 truncate w-full font-myanmar">{{ __('messages.account') }}</span>
            </a>
        @endif
    </div>

    {{-- =========================================================================
         2. VALUE & SERVICE TRUST STRIP (AliExpress / Amazon Style)
         ========================================================================= --}}
    <div class="hidden lg:grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="flex items-center gap-3 p-3 sm:p-3.5 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-2xs">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 text-xl border border-sky-200 dark:border-sky-900/50">⚡</span>
            <div class="min-w-0">
                <h4 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white font-myanmar leading-tight">{{ __('messages.fast_delivery') }}</h4>
                <p class="text-[11px] text-slate-600 dark:text-slate-400 font-myanmar truncate font-medium">{{ __('messages.doorstep_and_bus_gate') }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3 p-3 sm:p-3.5 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-2xs">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400 text-xl border border-emerald-200 dark:border-emerald-900/50">🛡️</span>
            <div class="min-w-0">
                <h4 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white font-myanmar leading-tight">{{ __('messages.genuine_warranty') }}</h4>
                <p class="text-[11px] text-slate-600 dark:text-slate-400 font-myanmar truncate font-medium">100% Genuine Quality</p>
            </div>
        </div>
        <a href="{{ url('/service-tracking?store_slug=' . $storeSlug) }}" class="flex items-center gap-3 p-3 sm:p-3.5 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-2xs group hover:border-violet-300 transition">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-950/60 dark:text-violet-400 text-xl border border-violet-200 dark:border-violet-900/50 group-hover:scale-105 transition-transform">🔧</span>
            <div class="min-w-0">
                <h4 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white font-myanmar leading-tight group-hover:text-violet-600">{{ __('messages.nav_service_track') }}</h4>
                <p class="text-[11px] text-violet-600 dark:text-violet-400 font-myanmar truncate font-bold">{{ __('messages.check_repair_status') }}</p>
            </div>
        </a>
        <div class="flex items-center gap-3 p-3 sm:p-3.5 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-2xs">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400 text-xl border border-sky-200 dark:border-sky-900/50">💬</span>
            <div class="min-w-0">
                <h4 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white font-myanmar leading-tight">{{ __('messages.direct_support') }}</h4>
                <p class="text-[11px] text-slate-600 dark:text-slate-400 font-myanmar truncate font-medium">{{ __('messages.viber_telegram_chat') }}</p>
            </div>
        </div>
    </div>

    {{-- =========================================================================
         3. FLASH SALE DEALS (Countdown + Discount Cards)
         ========================================================================= --}}
    @if (($flashSales->count() + $upcomingSales->count()) > 0)
        @php
            $activeWindowed = $flashSales->filter(fn ($p) => $p->sale_ends_at !== null);
            $evergreen = $flashSales->filter(fn ($p) => $p->sale_ends_at === null);
            $allDeals = $activeWindowed->concat($upcomingSales)->concat($evergreen)->take(12);
            $maxDealPercent = $allDeals->reduce(function (int $carry, $p): int {
                if ($p->old_price !== null && (float) $p->old_price > (float) $p->retail_price) {
                    $percent = (int) round((1 - (float) $p->retail_price / (float) $p->old_price) * 100);
                    return max($carry, $percent);
                }
                return $carry;
            }, 0);
        @endphp
        <div id="flash-sale-section" class="rounded-2xl p-4 sm:p-5 border border-rose-500/30 bg-gradient-to-br from-rose-500/5 via-fuchsia-500/5 to-violet-500/5 dark:from-rose-950/20 dark:via-fuchsia-950/20 dark:to-slate-900 shadow-xs">
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div class="flex items-center gap-2">
                    <h2 class="text-base sm:text-xl font-black text-slate-900 dark:text-white font-outfit flex items-center gap-2">
                        <span class="inline-flex h-8 w-8 sm:h-9 sm:w-9 items-center justify-center rounded-xl bg-gradient-to-tr from-rose-500 to-fuchsia-500 text-white shadow-md text-base">
                            🔥
                        </span>
                        <span>{{ __('messages.flash_sale') }}</span>
                    </h2>
                    @if ($maxDealPercent > 0)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-rose-600 text-white text-[11px] font-black shadow-xs font-outfit">
                            -{{ $maxDealPercent }}% Max
                        </span>
                    @endif
                </div>

                @if ($flashTarget)
                    <div x-data="flashTimer({{ $flashTarget->timestamp * 1000 }})" class="flex items-center gap-1.5 bg-white dark:bg-slate-900 rounded-xl px-2.5 py-1.5 border border-rose-200 dark:border-rose-900/50 shadow-2xs font-myanmar">
                        <span class="text-[11px] font-bold text-rose-600 dark:text-rose-400 whitespace-nowrap">
                            {{ $flashTargetStarts ? __('messages.starting_soon') : __('messages.sale_ends_in') }}:
                        </span>
                        <template x-if="!expired">
                            <span class="flex items-center gap-1 font-mono font-black text-xs sm:text-sm tabular-nums">
                                <template x-if="days > 0">
                                    <span>
                                        <span class="bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded px-1 py-0.5" x-text="String(days).padStart(2,'0')"></span>
                                        <span class="text-xs text-slate-500">d</span>
                                    </span>
                                </template>
                                <span class="bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded px-1.5 py-0.5" x-text="String(hours).padStart(2,'0')"></span>
                                <span class="text-slate-500 font-bold">:</span>
                                <span class="bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded px-1.5 py-0.5" x-text="String(minutes).padStart(2,'0')"></span>
                                <span class="text-slate-500 font-bold">:</span>
                                <span class="bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded px-1.5 py-0.5" x-text="String(seconds).padStart(2,'0')"></span>
                            </span>
                        </template>
                        <span x-show="expired" x-cloak class="text-xs font-bold text-slate-500">00:00:00</span>
                    </div>
                @endif
            </div>

            {{-- Horizontal Deals Scroll --}}
            <div
                x-data="{ isDown: false, startX: 0, scrollLeft: 0 }"
                @mousedown="isDown = true; startX = $event.pageX - $el.offsetLeft; scrollLeft = $el.scrollLeft"
                @mouseleave="isDown = false"
                @mouseup="isDown = false"
                @mousemove="if(isDown){$event.preventDefault();const x=$event.pageX-$el.offsetLeft;const walk=(x-startX)*1.5;$el.scrollLeft=scrollLeft-walk}"
                class="mt-3.5 flex overflow-x-auto gap-2.5 pb-2 pt-0.5 scrollbar-none cursor-grab active:cursor-grabbing select-none"
            >
                @foreach ($allDeals as $deal)
                    @php
                        $isUpcoming = $upcomingSales->contains('id', $deal->id);
                        $dealDefaultVariant = $deal->defaultVariant();
                        $dealImage = $dealDefaultVariant?->image_path ?: $deal->image_path;
                        $dealPercent = ($deal->old_price !== null && (float) $deal->old_price > (float) $deal->retail_price)
                            ? (int) round((1 - (float) $deal->retail_price / (float) $deal->old_price) * 100)
                            : null;
                    @endphp
                    <a href="{{ url('/store/' . $storeSlug . '/product/' . $deal->slug) }}"
                       class="group shrink-0 w-36 sm:w-44 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xs hover:shadow-md hover:border-sky-400 dark:hover:border-sky-500 transition-all duration-200 overflow-hidden bg-white dark:bg-slate-900 flex flex-col">
                        <div class="relative h-28 sm:h-32 bg-slate-100 dark:bg-slate-800 overflow-hidden">
                            @if ($dealImage)
                                <img src="{{ asset('storage/' . $dealImage) }}" alt="{{ $deal->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200" loading="lazy" decoding="async">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-3xl">📦</div>
                            @endif
                            @if ($dealPercent !== null)
                                <span class="absolute top-1.5 left-1.5 px-1.5 py-0.5 rounded-md bg-rose-600 text-white text-xs font-black shadow-xs font-outfit">
                                    -{{ $dealPercent }}%
                                </span>
                            @endif
                            @if ($isUpcoming)
                                <span class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded-md bg-slate-900 text-white text-[10px] font-bold">
                                    {{ __('messages.starting_soon_short') }}
                                </span>
                            @endif
                        </div>
                        <div class="p-2 sm:p-2.5 flex-1 flex flex-col justify-between">
                            <h3 class="text-xs font-bold text-slate-900 dark:text-white leading-snug line-clamp-2 group-hover:text-sky-600 transition-colors font-myanmar">{{ $deal->name }}</h3>
                            <div class="pt-1.5">
                                @if ($deal->old_price)
                                    <span class="text-[11px] text-slate-500 line-through decoration-rose-500 block">{{ format_currency($deal->old_price, $store) }}</span>
                                @endif
                                <span class="text-xs sm:text-sm font-black text-rose-600 dark:text-rose-400">{{ format_currency($deal->retail_price, $store) }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
        <!-- end flash-sale-section -->
    @endif

    {{-- =========================================================================
         4. FEATURED PRODUCTS SHOWCASE (လူကြိုက်များသော ပစ္စည်းများ)
         ========================================================================= --}}
    @if ($featuredProducts->count() > 0)
        <div class="space-y-3.5">
            {{-- Section Header Bar --}}
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-2.5">
                <div class="flex items-center gap-2">
                    <h2 class="text-base sm:text-lg font-black text-slate-900 dark:text-white font-outfit flex items-center gap-2">
                        <span class="inline-flex h-7 w-7 sm:h-8 sm:w-8 items-center justify-center rounded-xl bg-gradient-to-tr from-amber-500 to-rose-500 text-white shadow-xs text-sm sm:text-base">
                            🔥
                        </span>
                        <span class="font-myanmar">{{ __('messages.featured_products') }}</span>
                    </h2>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 text-[11px] font-black font-outfit">
                        {{ $featuredProducts->count() }}
                    </span>
                </div>

                <a href="{{ url('/products?store_slug=' . $storeSlug) }}" class="text-xs font-bold text-sky-600 dark:text-sky-400 hover:underline whitespace-nowrap font-myanmar">
                    {{ __('messages.view_all_products') }} →
                </a>
            </div>

            {{-- Featured Products Grid --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2.5 sm:gap-3.5">
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
         5. MOST POPULAR CATEGORY CARDS (Uniform Dimension & Consistent Size)
         ========================================================================= --}}
    @if ($categoryTree->isNotEmpty())
        <div class="space-y-3.5">
            <div class="flex items-center justify-between">
                <h2 class="text-base sm:text-lg font-black text-slate-900 dark:text-white font-myanmar flex items-center gap-2">
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
                class="flex overflow-x-auto gap-3 pb-2.5 pt-0.5 cursor-grab active:cursor-grabbing select-none scrollbar-none"
            >
                @foreach ($categoryTree as $mainRow)
                    @php
                        $main = $mainRow->category;
                        $mainIcon = $main->icon ?: $iconFor($main->name);
                        $cover = $main->image_path ?: $mainRow->cover;
                    @endphp
                    <a href="{{ url('/products?store_slug=' . $storeSlug . '&category_id=' . $main->id) }}"
                       class="group shrink-0 w-36 sm:w-40 h-[180px] sm:h-[196px] flex flex-col justify-between rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-2.5 shadow-2xs hover:shadow-md hover:border-sky-400 dark:hover:border-sky-500 transition-all text-center"
                       aria-label="{{ $main->name }}">
                        
                        {{-- Uniform Fixed-Size Image/Icon Container --}}
                        <div class="w-full h-28 sm:h-32 rounded-xl bg-slate-100 dark:bg-slate-800 overflow-hidden flex items-center justify-center relative shrink-0">
                            @if ($cover)
                                <img src="{{ asset('storage/' . $cover) }}" alt="{{ $main->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200" loading="lazy" decoding="async" data-img-fallback="hide-next" />
                                <span class="hidden w-full h-full items-center justify-center text-3xl sm:text-4xl">{{ $mainIcon }}</span>
                            @else
                                <div class="w-full h-full flex items-center justify-center text-3xl sm:text-4xl">{{ $mainIcon }}</div>
                            @endif
                            <span class="absolute bottom-1 right-1 px-1.5 py-0.5 rounded-md bg-slate-900/80 text-white text-[10px] font-black tabular-nums font-outfit shadow-xs">
                                {{ number_format($mainRow->total) }}
                            </span>
                        </div>

                        {{-- Uniform Fixed-Height Label Container for Consistent Baseline --}}
                        <div class="h-10 flex items-center justify-center px-0.5 mt-1">
                            <h3 class="text-xs font-bold text-slate-800 dark:text-slate-200 line-clamp-2 leading-snug group-hover:text-sky-600 transition-colors font-myanmar">
                                {{ $main->name }}
                            </h3>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- =========================================================================
         6. NEW ARRIVALS SHOWCASE (အသစ်ရောက် ပစ္စည်းများ)
         ========================================================================= --}}
    @if (isset($newArrivals) && $newArrivals->count() > 0)
        <div class="space-y-3.5">
            {{-- Section Header Bar --}}
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-2.5">
                <div class="flex items-center gap-2">
                    <h2 class="text-base sm:text-lg font-black text-slate-900 dark:text-white font-outfit flex items-center gap-2">
                        <span class="inline-flex h-7 w-7 sm:h-8 sm:w-8 items-center justify-center rounded-xl bg-gradient-to-tr from-violet-600 to-sky-500 text-white shadow-xs text-sm sm:text-base">
                            ✨
                        </span>
                        <span class="font-myanmar">{{ __('messages.new_arrivals') }}</span>
                    </h2>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-violet-100 dark:bg-violet-950/60 text-violet-800 dark:text-violet-300 text-[11px] font-black font-outfit">
                        {{ $newArrivals->count() }}
                    </span>
                </div>

                <a href="{{ url('/products?store_slug=' . $storeSlug) }}" class="text-xs font-bold text-sky-600 dark:text-sky-400 hover:underline whitespace-nowrap font-myanmar">
                    {{ __('messages.view_all_products') }} →
                </a>
            </div>

            {{-- New Arrivals Products Grid --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2.5 sm:gap-3.5">
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

    {{-- =========================================================================
         6. GLASS FINDER CTA BANNER (Compact & High Conversion)
         ========================================================================= --}}
    <div class="rounded-2xl p-4 sm:p-5 border border-sky-300 dark:border-sky-900/50 bg-gradient-to-r from-sky-50 via-violet-50 to-purple-50 dark:from-slate-900 dark:via-slate-950 dark:to-slate-900 shadow-xs flex items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-gradient-to-tr from-violet-600 to-sky-500 text-white flex items-center justify-center text-xl sm:text-2xl shadow-xs shrink-0">
                📱
            </div>
            <div class="min-w-0">
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-sky-700 dark:text-sky-300 block font-myanmar">
                    ✨ {{ __('messages.fast_glass_match') }}
                </span>
                <h3 class="font-black text-sm sm:text-base text-slate-900 dark:text-white font-myanmar truncate">
                    {{ __('messages.glass_finder') }}
                </h3>
            </div>
        </div>

        <a href="{{ url('/glass-finder?store_slug=' . $storeSlug) }}" class="shrink-0 px-4 py-2 bg-gradient-to-r from-violet-600 to-sky-600 hover:brightness-110 text-white font-bold text-xs rounded-xl shadow-xs transition active:scale-95 flex items-center gap-1 font-myanmar">
            <span>{{ __('messages.glass_finder') }}</span> →
        </a>
    </div>

</div>
@endsection
