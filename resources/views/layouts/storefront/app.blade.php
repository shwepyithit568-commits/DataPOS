<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{
    darkMode: localStorage.getItem('darkMode') === 'true',
    mobileMenuOpen: false,
    swipeStartX: 0,
    swipeStartY: 0,
    onSwipeStart(e) {
        this.swipeStartX = e.touches[0].clientX;
        this.swipeStartY = e.touches[0].clientY;
    },
    onSwipeEnd(e) {
        const dx = e.changedTouches[0].clientX - this.swipeStartX;
        const dy = e.changedTouches[0].clientY - this.swipeStartY;
        if (Math.abs(dx) < 60 || Math.abs(dx) <= Math.abs(dy)) return;
        if (!this.mobileMenuOpen && this.swipeStartX <= 32 && dx > 0) {
            this.mobileMenuOpen = true;
        } else if (this.mobileMenuOpen && dx < 0) {
            this.mobileMenuOpen = false;
        }
    }
}" x-on:touchstart.window.passive="onSwipeStart($event)" x-on:touchend.window.passive="onSwipeEnd($event)" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Force fresh page fetches in in-app browsers / WebViews that ignore HTTP cache headers --}}
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>{{ $ogTitle ?? config('app.name') }}</title>

    {{-- SEO Meta Tags --}}
    <meta name="description" content="{{ $metaDescription ?? ($setting?->store_name ?? config('app.name')) . ' — ' . __('messages.welcome') }}">
    <meta name="keywords" content="{{ $metaKeywords ?? ('phone glass, phone repair, mobile accessories, Myanmar, ' . ($setting?->store_name ?? '')) }}">
    {{-- $canonicalUrl lets pages emit a clean URL (e.g. the product page strips
         query params); the fallback keeps every other page unchanged. --}}
    <link rel="canonical" href="{{ $canonicalUrl ?? url()->current() }}" />
    {{-- $robots defaults to index,follow for storefront pages; error pages use
         their own standalone templates and never render this layout. --}}
    <meta name="robots" content="{{ $robots ?? 'index,follow' }}" />

    {{-- Open Graph / Social --}}
    <meta property="og:title" content="{{ $ogTitle ?? ($title ?? config('app.name')) }}" />
    <meta property="og:description" content="{{ $metaDescription ?? ($setting?->store_name ?? config('app.name')) . ' — ' . __('messages.welcome') }}" />
    <meta property="og:url" content="{{ $canonicalUrl ?? url()->current() }}" />
    <meta property="og:type" content="{{ $ogType ?? 'website' }}" />
    <meta property="og:site_name" content="{{ config('app.name') }}" />
    @if (!empty($ogImage))
        <meta property="og:image" content="{{ $ogImage }}" />
    @elseif (!empty(($setting ?? null)?->storefrontLogo()))
        <meta property="og:image" content="{{ asset('storage/' . $setting->storefrontLogo()) }}" />
    @endif

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="{{ !empty($ogImage) ? 'summary_large_image' : 'summary' }}" />
    <meta name="twitter:title" content="{{ $ogTitle ?? ($title ?? config('app.name')) }}" />
    <meta name="twitter:description" content="{{ $metaDescription ?? ($setting?->store_name ?? config('app.name')) . ' — ' . __('messages.welcome') }}" />
    @if (!empty($ogImage))
        <meta name="twitter:image" content="{{ $ogImage }}" />
    @endif

    {{-- Favicon / app icon — dedicated asset with documented fallback chain --}}
    @php
        // ($setting ?? null) keeps this safe on pages that render the layout
        // without a $setting variable (e.g. product detail).
        $faviconPath = ($setting ?? null)?->favicon();
        $faviconHref = $faviconPath ? asset('storage/' . $faviconPath) : asset('favicon.ico');
        // Performance: without a dedicated favicon_path the model falls back
        // through full-size logo assets (~135KB PNG). Serve the small static
        // icons for the browser tab + iOS home screen instead.
        $dedicatedFavicon = ($setting ?? null)?->favicon_path;
        $appleTouchHref = $dedicatedFavicon
            ? asset('storage/' . $dedicatedFavicon)
            : asset('apple-touch-icon.png');
    @endphp
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Preload the three WOFF2 fonts so text renders without font-swap CLS. --}}
    <link rel="preload" as="font" type="font/woff2" crossorigin href="{{ Vite::asset('resources/assets/fonts/Roboto-Regular.woff2') }}">
    <link rel="preload" as="font" type="font/woff2" crossorigin href="{{ Vite::asset('resources/assets/fonts/NotoSansMyanmar-Regular.woff2') }}">
    <link rel="preload" as="font" type="font/woff2" crossorigin href="{{ Vite::asset('resources/assets/fonts/Outfit-Regular.woff2') }}">
    <link rel="icon" type="{{ $faviconPath && str_ends_with($faviconPath, '.webp') ? 'image/webp' : ($faviconPath ? 'image/png' : 'image/x-icon') }}" href="{{ $faviconHref }}">
    <link rel="apple-touch-icon" href="{{ $appleTouchHref }}">

    {{-- PWA / Installable Web App metadata --}}
    <meta name="theme-color" content="#38bdf8">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    {{-- VAPID public key for the browser Push API subscription (Web Push) --}}
    <meta name="vapid-public-key" content="{{ config('webpush.vapid.public_key') }}">
</head>
@php
    $activeStoreContext = app(\App\Services\StoreContext::class)->getStore();
    $activeStoreSlug = request('store_slug') ?? $activeStoreContext?->slug;
    $setting = $activeStoreContext?->setting ?? $setting ?? null;
    $storeDisplayName = $setting?->store_name ?? $activeStoreContext?->name ?? config('app.name');

    $homeUrl = $activeStoreSlug ? url('/?store_slug=' . $activeStoreSlug) : url('/');
    $productsUrl = $activeStoreSlug ? url('/products?store_slug=' . $activeStoreSlug) : url('/products');
    $glassFinderUrl = $activeStoreSlug ? url('/glass-finder?store_slug=' . $activeStoreSlug) : url('/glass-finder');
    $browseUrl = $activeStoreSlug ? url('/browse?store_slug=' . $activeStoreSlug) : url('/browse');
    $orderBuilderUrl = $activeStoreSlug ? url('/order-builder?store_slug=' . $activeStoreSlug) : url('/order-builder');
    $howToOrderUrl = $activeStoreSlug ? url('/how-to-order?store_slug=' . $activeStoreSlug) : url('/how-to-order');
    $blogUrl = $activeStoreSlug ? url('/blog?store_slug=' . $activeStoreSlug) : url('/blog');
    $accountUrl = $activeStoreSlug ? url('/account?store_slug=' . $activeStoreSlug) : url('/account');
    $favoritesUrl = $activeStoreSlug ? url('/account/favorites?store_slug=' . $activeStoreSlug) : url('/account/favorites');

    $isHome = request()->is('/') || request()->fullUrl() === $homeUrl;
    $isProducts = request()->is('products*') || request()->is('store/*/product/*');
    $isGlassFinder = request()->is('glass-finder*');
    $isBrowse = request()->is('browse*');
    $isOrderBuilder = request()->is('order-builder*');
    $isHowToOrder = request()->is('how-to-order*');
    $isAccount = request()->is('account*');
    $isBlog = request()->is('blog*');

    // Header category menu — categories with products, sorted by product count (Shopwise style).
    // Grouped Main → Sub: mains are listed when they (or their subs) have products.
    // Counts + flyouts cover ONLINE products only — counter-only items
    // (is_ecommerce=false) do not advertise categories on the storefront.
    $navAllCategories = $activeStoreContext
        ? \App\Models\Category::where('store_id', $activeStoreContext->id)
            ->withCount(['products' => fn ($q) => $q->where('is_ecommerce', true)])
            ->get()
        : collect();
    $navCategories = $navAllCategories
        ->filter(fn ($category) => $category->products_count > 0)
        ->sortByDesc('products_count')
        ->values();
    $navCategoryTree = $navAllCategories
        ->whereNull('parent_id')
        ->map(function ($main) use ($navCategories) {
            $children = $navCategories
                ->where('parent_id', $main->id)
                ->sortByDesc('products_count')
                ->values();
            return (object) [
                'category' => $main,
                'children' => $children,
                'total' => $main->products_count + $children->sum('products_count'),
            ];
        })
        ->filter(fn ($row) => $row->category->products_count > 0 || $row->children->isNotEmpty())
        ->sortByDesc('total')
        ->values();

    // Brands per category (for the hover flyout in the header category menu).
    $navBrandsByCategory = $activeStoreContext
        ? \App\Models\Product::where('store_id', $activeStoreContext->id)
            ->where('is_ecommerce', true)
            ->whereNotNull('brand_id')
            ->whereNotNull('category_id')
            ->with('brand')
            ->get()
            ->groupBy('category_id')
            ->map(fn ($products) => $products
                ->groupBy('brand_id')
                ->map(fn ($ps) => ['brand' => $ps->first()->brand, 'count' => $ps->count()])
                ->filter(fn ($row) => $row['brand'])
                ->values())
        : collect();
@endphp
<body class="bg-slate-100 dark:bg-slate-950 text-slate-900 dark:text-slate-100 font-sans antialiased min-h-screen relative selection:bg-sky-500 selection:text-white pb-[calc(env(safe-area-inset-bottom,0px)+6rem)] md:pb-8">

    {{-- Background Liquid Glow Circles --}}
    <div class="fixed inset-0 pointer-events-none overflow-hidden z-0">
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-sky-500/20 dark:bg-sky-500/25 rounded-full blur-3xl"></div>
        <div class="absolute top-1/3 -right-40 w-96 h-96 bg-purple-500/20 dark:bg-purple-500/25 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 left-1/3 w-96 h-96 bg-cyan-500/20 dark:bg-cyan-500/25 rounded-full blur-3xl"></div>
        <div class="absolute top-10 left-1/2 -translate-x-1/2 w-72 h-72 bg-fuchsia-500/15 dark:bg-fuchsia-500/20 rounded-full blur-3xl"></div>
        <div class="absolute bottom-1/4 -left-24 w-72 h-72 bg-amber-400/15 dark:bg-amber-400/20 rounded-full blur-3xl"></div>
        <div class="absolute top-1/2 right-10 w-64 h-64 bg-emerald-400/15 dark:bg-emerald-400/20 rounded-full blur-3xl"></div>
    </div>

    {{-- Top utility bar (tablet/desktop only — scrolls away; contact info + account) --}}
    <div class="hidden md:block border-b border-slate-200/60 dark:border-slate-800/60 bg-white/95 dark:bg-slate-900/95 backdrop-blur">
        <div class="max-w-7xl mx-auto px-3 sm:px-5 lg:px-8 h-9 flex items-center justify-between gap-4 text-xs font-semibold text-slate-500 dark:text-slate-600">
            <div class="flex items-center gap-4 min-w-0">
                @if ($setting?->phone)
                    <a href="tel:{{ $setting->phone }}" class="flex items-center gap-1.5 whitespace-nowrap hover:text-sky-600 dark:hover:text-sky-400 transition">
                        <span aria-hidden="true">📞</span>
                        <span>{{ $setting->phone }}</span>
                    </a>
                @endif
                @php
                    $topViber = \App\Support\ContactLinkBuilder::viberChatUrl($setting?->viber_number);
                    $topViberIos = \App\Support\ContactLinkBuilder::viberIosContactUrl($setting?->viber_number);
                    $topTelegram = \App\Support\ContactLinkBuilder::telegramUrl($setting?->telegram_username);
                @endphp
                @if ($topViber)
                    <a href="{{ $topViber }}" data-ios-href="{{ $topViberIos }}" target="_blank" rel="noopener noreferrer" class="hidden sm:flex items-center gap-1.5 whitespace-nowrap hover:text-sky-600 dark:hover:text-sky-400 transition">
                        <x-brand-icon brand="viber" class="h-4 w-4 shrink-0 text-violet-600 dark:text-violet-400"/>
                        <span>Viber</span>
                    </a>
                @endif
                @if ($topTelegram)
                    <a href="{{ $topTelegram }}" target="_blank" rel="noopener noreferrer" class="hidden sm:flex items-center gap-1.5 whitespace-nowrap hover:text-sky-600 dark:hover:text-sky-400 transition">
                        <x-brand-icon brand="telegram" class="h-4 w-4 shrink-0 text-sky-500 dark:text-sky-400"/>
                        <span>Telegram</span>
                    </a>
                @endif
                @if ($setting?->opening_hours)
                    <span class="hidden lg:flex items-center gap-1.5 whitespace-nowrap">
                        <span aria-hidden="true">🕒</span>
                        <span class="truncate max-w-[16rem]">{{ $setting->opening_hours }}</span>
                    </span>
                @endif
            </div>
            <div class="flex items-center gap-4 shrink-0">
                @auth
                    <a href="{{ $accountUrl }}" class="flex items-center gap-1.5 whitespace-nowrap hover:text-sky-600 dark:hover:text-sky-400 transition">
                        <span aria-hidden="true">👤</span>
                        <span class="max-w-[10rem] truncate">{{ auth()->user()->name }}</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex items-center gap-1.5 whitespace-nowrap hover:text-rose-600 dark:hover:text-rose-400 transition">
                            <span aria-hidden="true">🚪</span>
                            <span>{{ __('messages.logout') }}</span>
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="flex items-center gap-1.5 whitespace-nowrap hover:text-sky-600 dark:hover:text-sky-400 transition">
                        <span aria-hidden="true">🔑</span>
                        <span>{{ __('messages.login') }}</span>
                    </a>
                    <a href="{{ route('register') }}" class="flex items-center gap-1.5 whitespace-nowrap hover:text-sky-600 dark:hover:text-sky-400 transition">
                        <span aria-hidden="true">📝</span>
                        <span>{{ __('messages.register') }}</span>
                    </a>
                @endauth
            </div>
        </div>
    </div>

    {{-- Header --}}
    <header x-data="{ searchOpen: false }" class="sticky top-0 border-b border-white/50 shadow-sm dark:border-slate-800/80" :class="mobileMenuOpen ? 'z-[70]' : 'z-30'" @click.outside="searchOpen = false">
        <div class="bg-white/95 dark:bg-slate-900/95 backdrop-blur">
        <div class="max-w-7xl mx-auto px-1 sm:px-5 lg:px-8 h-16 sm:h-[4.5rem] flex items-center gap-1.5 sm:gap-3 relative">
            {{-- Left: Category menu (Linn IT Mart style icon button) --}}
            <div class="flex shrink-0 items-center">

                {{-- Shopwise-style Category Menu (left of header) — desktop opens the flyout; mobile is a shortcut to /browse --}}
                @if ($navCategories->count() > 0)
                    <div class="relative shrink-0" x-data="{ catOpen: false, activeCat: {{ $navCategoryTree->first()?->category->id ?? 0 }}, catIds: [{{ $navCategoryTree->pluck('category.id')->implode(',') }}], moveCat(dir) { const ids = this.catIds; if (!ids.length) return; const i = ids.indexOf(this.activeCat); let n = i + dir; if (n < 0) n = ids.length - 1; else if (n >= ids.length) n = 0; this.activeCat = ids[n]; this.focusRailAt(ids.indexOf(this.activeCat)); }, moveTo(i) { const ids = this.catIds; if (!ids.length) return; if (i < 0) i = 0; else if (i >= ids.length) i = ids.length - 1; this.activeCat = ids[i]; this.focusRailAt(i); }, focusRailAt(i) { this.$nextTick(() => { const btns = this.$refs.catRail ? this.$refs.catRail.querySelectorAll('button') : []; if (btns[i]) btns[i].focus(); }); } }" @mouseenter="catOpen = true" @mouseleave="catOpen = false" @click.outside="catOpen = false" @keydown.escape.window="catOpen = false">
                        {{-- Desktop (lg+): hover/click toggles the category flyout (mobile has a Categories shortcut beside the search bar) --}}
                        <button
                            type="button"
                            @click="catOpen = !catOpen"
                            :aria-expanded="catOpen ? 'true' : 'false'"
                            aria-haspopup="menu"
                            aria-label="{{ __('messages.categories') }}"
                            title="{{ __('messages.categories') }}"
                            class="hidden lg:inline-flex h-9 w-9 sm:h-10 sm:w-10 lg:h-auto lg:w-auto items-center justify-center gap-1.5 lg:gap-2 rounded-xl border px-0 lg:px-3.5 py-2 text-xs font-extrabold transition focus:outline-none focus:ring-2 focus:ring-sky-500"
                            :class="catOpen ? 'border-transparent bg-gradient-to-r from-violet-600 to-fuchsia-500 text-white shadow-md shadow-sky-500/25' : 'border-slate-200/80 bg-white text-slate-700 shadow-sm hover:bg-slate-100 hover:text-sky-600 dark:border-slate-700/80 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 dark:hover:text-sky-300'"
                        >
                            {{-- Categories icon (3 rows of rounded pills — Linn IT Mart style) --}}
                            <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <rect x="3" y="3.6" width="4" height="5.4" rx="2.7"/>
                                <rect x="9" y="3.6" width="12" height="5.4" rx="2.7"/>
                                <rect x="3" y="9.3" width="4" height="5.4" rx="2.7"/>
                                <rect x="9" y="9.3" width="12" height="5.4" rx="2.7"/>
                                <rect x="3" y="15" width="4" height="5.4" rx="2.7"/>
                                <rect x="9" y="15" width="12" height="5.4" rx="2.7"/>
                            </svg>
                            <span class="hidden lg:inline font-extrabold text-xs">{{ __('messages.categories') }}</span>
                            <svg class="hidden lg:block w-3.5 h-3.5 shrink-0 text-slate-600 transition-transform duration-200" :class="catOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div
                            x-show="catOpen"
                            x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            class="absolute left-0 top-full mt-2 z-50 w-[46rem] max-w-[calc(100vw-2rem)] rounded-2xl border border-slate-200 bg-white/95 backdrop-blur-xl shadow-2xl dark:border-slate-700 dark:bg-slate-900/95"
                            role="menu"
                        >
                            {{-- Mega menu: left rail + right panel (AliExpress/Shopee style) --}}
                            <div class="flex items-center justify-between gap-2 px-4 py-3 border-b border-slate-100 dark:border-slate-800">
                                <span class="flex items-center gap-2 text-sm font-black text-slate-800 dark:text-slate-100">
                                    <span class="w-7 h-7 shrink-0 rounded-lg bg-gradient-to-br from-violet-600 to-fuchsia-500 text-white flex items-center justify-center text-sm shadow-sm" aria-hidden="true">🗂️</span>
                                    {{ __('messages.categories') }}
                                </span>
                                <a href="{{ $browseUrl }}" class="inline-flex items-center gap-0.5 whitespace-nowrap text-xs font-extrabold text-sky-600 dark:text-sky-400 hover:text-sky-700 dark:hover:text-sky-300 transition" role="menuitem">
                                    {{ __('messages.view_all_products') }} →
                                </a>
                            </div>
                            <div class="flex h-[26rem] max-h-[70vh]">
                                {{-- Left rail: main categories (hover/click swaps the right panel) --}}
                                <nav x-ref="catRail" aria-label="{{ __('messages.categories') }}" class="w-52 shrink-0 border-r border-slate-100 dark:border-slate-800 p-1.5 space-y-0.5 overflow-y-auto scrollbar-thin" @keydown.arrow-down.prevent="moveCat(1)" @keydown.arrow-up.prevent="moveCat(-1)" @keydown.home.prevent="moveTo(0)" @keydown.end.prevent="moveTo(catIds.length - 1)">
                                    @foreach ($navCategoryTree as $navMainRow)
                                        @php $navMain = $navMainRow->category; @endphp
                                        <button
                                            type="button"
                                            @click="activeCat = {{ $navMain->id }}"
                                            @mouseenter="activeCat = {{ $navMain->id }}"
                                            :class="activeCat === {{ $navMain->id }} ? 'bg-sky-50 dark:bg-slate-800 text-sky-700 dark:text-sky-300 border-sky-600 shadow-sm' : 'border-transparent text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/60'"
                                            class="w-full flex items-center gap-2.5 rounded-xl border-l-[3px] px-2 py-1.5 text-left text-sm font-extrabold transition"
                                            role="menuitem"
                                            :aria-current="activeCat === {{ $navMain->id }} ? 'true' : 'false'"
                                        >
                                            <span class="w-9 h-9 shrink-0 rounded-lg overflow-hidden bg-sky-100 dark:bg-slate-700 flex items-center justify-center text-base shadow-sm" aria-hidden="true">
                                                @if ($navMain->image_path)
                                                    <img src="{{ asset('storage/' . $navMain->image_path) }}" alt="" class="w-full h-full object-cover" loading="lazy" decoding="async" data-img-fallback="hide-next">
                                                    <span class="hidden w-full h-full items-center justify-center">{{ $navMain->icon ?: '📦' }}</span>
                                                @else
                                                    {{ $navMain->icon ?: '📦' }}
                                                @endif
                                            </span>
                                            <span class="flex-1 min-w-0 truncate">{{ $navMain->name }}</span>
                                            <span class="shrink-0 text-[10px] font-black text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 rounded-full px-1.5 py-0.5">{{ number_format($navMainRow->total) }}</span>
                                        </button>
                                    @endforeach
                                </nav>
                                {{-- Right panel: brands + sub-categories of the active main (swaps on hover/click) --}}
                                <div class="flex-1 min-w-0 overflow-y-auto scrollbar-thin p-3">
                                    @foreach ($navCategoryTree as $navMainRow)
                                        @php
                                            $navMain = $navMainRow->category;
                                            $mainBrandRows = $navBrandsByCategory[$navMain->id] ?? collect();
                                        @endphp
                                        <div x-show="activeCat === {{ $navMain->id }}" x-cloak class="space-y-3.5">
                                            {{-- Sticky panel header --}}
                                            <div class="sticky top-0 z-10 -mx-3 px-3 py-2.5 bg-white/95 dark:bg-slate-900/95 backdrop-blur border-b border-slate-100 dark:border-slate-800 flex items-center gap-2.5">
                                                <span class="w-9 h-9 shrink-0 rounded-xl bg-gradient-to-br from-violet-600 to-fuchsia-500 text-white flex items-center justify-center text-base shadow-sm" aria-hidden="true">{{ $navMain->icon ?: '📦' }}</span>
                                                <div class="min-w-0">
                                                    <h3 class="text-sm font-black text-slate-900 dark:text-white leading-tight truncate">{{ $navMain->name }}</h3>
                                                    <p class="text-[11px] font-bold text-slate-600 dark:text-slate-500">{{ number_format($navMainRow->total) }} {{ __('messages.products') }}</p>
                                                </div>
                                                <a href="{{ url('/products?category_id=' . $navMain->id . ($activeStoreSlug ? '&store_slug=' . $activeStoreSlug : '')) }}" class="ml-auto shrink-0 inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-gradient-to-r from-violet-600 to-fuchsia-500 text-white text-[11px] font-extrabold shadow-sm hover:brightness-110 active:scale-95 transition" role="menuitem">
                                                    👀 <span class="whitespace-nowrap">{{ __('messages.view_all_products') }}</span>
                                                </a>
                                            </div>

                                            {{-- Brands grid --}}
                                            @if ($mainBrandRows->isNotEmpty())
                                                <div>
                                                    <h4 class="text-[11px] font-black uppercase tracking-wider text-slate-600 dark:text-slate-500 mb-2 flex items-center gap-1">
                                                        <span aria-hidden="true">🏷️</span> {{ __('messages.brands') }}
                                                    </h4>
                                                    <div class="grid grid-cols-2 gap-1.5">
                                                        @foreach ($mainBrandRows as $brandRow)
                                                            @php $flyBrand = $brandRow['brand']; @endphp
                                                            <a href="{{ url('/products?category_id=' . $navMain->id . '&brand_id=' . $flyBrand->id . ($activeStoreSlug ? '&store_slug=' . $activeStoreSlug : '')) }}"
                                                               class="flex items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 pl-1.5 pr-2 py-1.5 transition hover:border-sky-400 dark:hover:border-sky-500/60 active:scale-95"
                                                               role="menuitem"
                                                            >
                                                                <span class="w-8 h-8 shrink-0 rounded-lg overflow-hidden bg-sky-100 dark:bg-slate-700 flex items-center justify-center text-sm" aria-hidden="true">
                                                                    @if ($flyBrand->logo_path)
                                                                        <img src="{{ asset('storage/' . $flyBrand->logo_path) }}" alt="" class="w-full h-full object-cover" loading="lazy" decoding="async" data-img-fallback="hide-next">
                                                                        <span class="hidden w-full h-full items-center justify-center text-[10px] font-black">{{ mb_substr($flyBrand->name, 0, 1) }}</span>
                                                                    @else
                                                                        <span class="text-[10px] font-black">{{ mb_substr($flyBrand->name, 0, 1) }}</span>
                                                                    @endif
                                                                </span>
                                                                <span class="min-w-0">
                                                                    <span class="block font-black text-xs leading-tight truncate text-slate-800 dark:text-slate-100">{{ $flyBrand->name }}</span>
                                                                    <span class="block text-[10px] font-bold text-slate-500 dark:text-slate-400">{{ number_format($brandRow['count']) }} {{ __('messages.products') }}</span>
                                                                </span>
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif

                                            {{-- Sub-categories glued grid --}}
                                            @if ($navMainRow->children->isNotEmpty())
                                                <div>
                                                    <h4 class="text-[11px] font-black uppercase tracking-wider text-slate-600 dark:text-slate-500 mb-2 flex items-center gap-1">
                                                        <span aria-hidden="true">📂</span> {{ __('messages.sub_categories') }}
                                                    </h4>
                                                    <div class="grid grid-cols-2 gap-px bg-slate-200 dark:bg-slate-800 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden">
                                                        @foreach ($navMainRow->children as $navSub)
                                                            <a href="{{ url('/products?category_id=' . $navSub->id . ($activeStoreSlug ? '&store_slug=' . $activeStoreSlug : '')) }}"
                                                               class="bg-white dark:bg-slate-900 p-2.5 flex items-center gap-2 min-h-[52px] hover:bg-sky-50 dark:hover:bg-slate-800 transition group"
                                                               role="menuitem"
                                                            >
                                                                <span class="w-7 h-7 shrink-0 rounded-md bg-sky-100 dark:bg-slate-800 flex items-center justify-center text-sm group-hover:scale-110 transition" aria-hidden="true">📁</span>
                                                                <span class="min-w-0">
                                                                    <span class="block text-[11px] font-extrabold text-slate-800 dark:text-slate-100 leading-tight truncate">{{ $navSub->name }}</span>
                                                                    <span class="block text-[10px] font-bold text-slate-600 dark:text-slate-500">{{ number_format($navSub->products_count) }} {{ __('messages.products') }}</span>
                                                                </span>
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif

                                            {{-- Fallback when a main has no subs/brands of its own --}}
                                            @if ($mainBrandRows->isEmpty() && $navMainRow->children->isEmpty())
                                                <a href="{{ url('/products?category_id=' . $navMain->id . ($activeStoreSlug ? '&store_slug=' . $activeStoreSlug : '')) }}" class="block p-3 rounded-xl bg-sky-50 dark:bg-slate-800 border border-sky-200 dark:border-slate-700 text-center text-xs font-extrabold text-sky-700 dark:text-sky-300 transition active:scale-[0.98]" role="menuitem">
                                                    👀 {{ __('messages.view_all_products') }}
                                                </a>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            {{-- Footer: browse all --}}
                            <div class="border-t border-slate-100 dark:border-slate-800 px-4 py-2.5">
                                <a href="{{ $productsUrl }}" class="flex items-center justify-center gap-1 text-xs font-black text-sky-600 dark:text-sky-400 hover:text-sky-700 transition" role="menuitem">
                                    <span>👀</span> <span>{{ __('messages.view_all_products') }}</span>
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Center: Shop logo (flex-1 keeps it centered between the left/right action groups; truncates on small screens) --}}
            <a href="{{ $homeUrl }}" class="flex min-w-0 flex-1 items-center justify-center group transition-transform duration-300 hover:scale-[1.02] active:scale-[0.98]">
                @if (!empty(($setting ?? null)?->storefrontLogo()))
                    <img
                        src="{{ asset('storage/' . $setting->storefrontLogo()) }}"
                        alt="{{ $storeDisplayName }}"
                        class="w-[160px] h-[44px] sm:w-[200px] sm:h-[52px] lg:w-[240px] lg:h-[60px] object-contain drop-shadow-md transition-transform duration-300 group-hover:scale-105"
                    />
                @else
                    <div class="flex items-center gap-2 sm:gap-2.5 min-w-0">
                        <div class="flex h-10 w-10 sm:h-11 sm:w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-tr from-violet-600 via-fuchsia-500 to-rose-500 text-white shadow-lg shadow-sky-500/30 ring-2 ring-white/20 transition-transform duration-300 group-hover:scale-105">
                            <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="flex flex-col leading-tight min-w-0">
                            <span class="max-w-[5rem] sm:max-w-[8rem] truncate font-outfit text-sm sm:text-base font-black leading-tight tracking-tight text-slate-900 transition-colors group-hover:text-sky-600 dark:text-white dark:group-hover:text-sky-400 lg:max-w-[13rem] lg:text-lg">{{ $storeDisplayName }}</span>
                            <span class="max-w-[5rem] sm:max-w-[8rem] truncate text-xs font-extrabold leading-none text-sky-600 dark:text-sky-400 lg:max-w-[13rem]">{{ $setting?->tagline ?: __('messages.default_tagline') }}</span>
                        </div>
                    </div>
                @endif
            </a>

            {{-- Right Header Actions --}}
            <div class="flex shrink-0 items-center gap-1 sm:gap-1.5 lg:gap-2">
                {{-- Search icon button (opens full-width search bar below the header) --}}
                <button
                    type="button"
                    @click="searchOpen = !searchOpen; mobileMenuOpen = false; if (searchOpen) $nextTick(() => document.getElementById('desktop-search-input') && document.getElementById('desktop-search-input').focus())"
                    :aria-expanded="searchOpen ? 'true' : 'false'"
                    aria-label="{{ __('messages.search_products') }}"
                    title="{{ __('messages.search_products') }}"
                    class="hidden lg:inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200/80 bg-white text-slate-700 shadow-sm transition hover:bg-slate-100 hover:text-sky-600 focus:outline-none focus:ring-2 focus:ring-sky-500 dark:border-slate-700/80 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 dark:hover:text-sky-300"
                >
                    <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>

                {{-- Favorites icon with live count badge (visible on all viewports) --}}
                <a href="{{ $favoritesUrl }}" class="group relative inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200/80 bg-white text-slate-700 shadow-sm transition hover:bg-slate-100 dark:border-slate-700/80 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700" title="{{ __('messages.favorites') }}" aria-label="{{ __('messages.favorites') }}">
                    <svg class="h-5 w-5 text-rose-500 transition-transform group-hover:scale-125 sm:h-6 sm:w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.684a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                    <span x-show="$store.favoritesStore && $store.favoritesStore.count > 0" x-transition class="absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] sm:min-w-[20px] sm:h-[20px] px-1 rounded-full bg-rose-500 text-white font-black text-[11px] flex items-center justify-center shadow-md shadow-rose-500/30 ring-2 ring-white dark:ring-slate-900" x-text="$store.favoritesStore ? $store.favoritesStore.count : 0"></span>
                </a>

                {{-- Language switcher (icon-only flag + dropdown — visible on all viewports) --}}
                <x-language-switcher id="storefront-header" />

                {{-- Compact Cart Icon with Badge (hidden on mobile — cart is in the bottom nav) --}}
                <a href="{{ $orderBuilderUrl }}" class="group relative hidden md:flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200/80 bg-white text-slate-700 shadow-sm transition hover:bg-slate-100 dark:border-slate-700/80 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700" title="{{ __('messages.order_builder') }}">
                    <svg class="h-4 w-4 text-slate-700 transition-colors group-hover:text-sky-500 dark:text-slate-200 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span x-show="$store.orderBuilder && $store.orderBuilder.totalCount > 0" class="absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] sm:min-w-[20px] sm:h-[20px] px-1 rounded-full bg-gradient-to-r from-violet-600 to-fuchsia-500 text-white font-black text-xs sm:text-xs flex items-center justify-center shadow-md shadow-sky-500/30" x-text="$store.orderBuilder ? $store.orderBuilder.totalCount : 0"></span>
                </a>

                {{-- Dark Mode Toggle (compact icon — visible on all viewports) --}}
                <button @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200/80 bg-white text-slate-700 shadow-sm transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500 dark:border-slate-700/80 dark:bg-slate-800 dark:text-amber-400 dark:hover:bg-slate-700" title="{{ __('messages.theme_toggle') }}">
                    <svg x-show="!darkMode" class="h-4 w-4 sm:h-5 sm:w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.8A8.5 8.5 0 1111.2 3a6.5 6.5 0 009.8 9.8z" />
                    </svg>
                    <svg x-show="darkMode" class="h-4 w-4 sm:h-5 sm:w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v2m0 14v2m9-9h-2M5 12H3m15.36-6.36-1.42 1.42M7.06 16.94l-1.42 1.42m12.72 0-1.42-1.42M7.06 7.06 5.64 5.64" />
                        <circle cx="12" cy="12" r="4" stroke-width="2" />
                    </svg>
                </button>

                {{-- Mobile Hamburger Menu Toggle (below lg only) — far right --}}
                <button
                    type="button"
                    @click="mobileMenuOpen = !mobileMenuOpen; searchOpen = false"
                    :aria-expanded="mobileMenuOpen ? 'true' : 'false'"
                    aria-controls="storefront-mobile-nav"
                    data-mobile-menu-button
                    class="lg:hidden inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200/80 bg-white text-slate-700 shadow-sm transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500 dark:border-slate-700/80 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    title="{{ __('messages.open_menu') }}"
                    aria-label="{{ __('messages.open_menu') }}"
                >
                    <svg x-show="!mobileMenuOpen" class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg x-show="mobileMenuOpen" class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Mobile search row: search bar + Categories shortcut (always visible below the header — Shopee/AliExpress style) --}}
        <div class="lg:hidden border-t border-slate-200/60 dark:border-slate-800/60 bg-white/95 dark:bg-slate-900/95 px-1 sm:px-5 pb-2.5 pt-1.5"
             x-data="searchSuggestions('{{ $activeStoreSlug }}', '{{ url('/products/suggestions') }}', { categories: '{{ __('messages.categories') }}', brands: '{{ __('messages.brands') }}', products: '{{ __('messages.products') }}', trending: '{{ __('messages.trending_searches') }}' })"
             @click.outside="open = false">
            <div class="flex items-center gap-1.5 sm:gap-2">
                <form action="{{ url('/products') }}" method="GET" class="relative flex min-w-0 flex-1 items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3 h-11 text-xs shadow-sm transition focus-within:border-sky-500 focus-within:ring-1 focus-within:ring-sky-500/30 dark:border-slate-600 dark:bg-slate-800 sm:text-sm">
                <input type="hidden" name="store_slug" value="{{ $activeStoreSlug }}">
                <svg class="h-4 w-4 shrink-0 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" x-model="query" @input="onInput()" @focus="onFocus()" @keydown="onKeydown($event)" x-ref="searchInput" autocomplete="off" placeholder="{{ __('messages.search_products') }}" class="w-full bg-transparent outline-none text-slate-700 placeholder:text-slate-500 dark:text-slate-200 dark:placeholder:text-slate-400" role="combobox" aria-expanded="open ? 'true' : 'false'" aria-autocomplete="list" aria-controls="mobile-search-suggestions" :aria-activedescendant="activeId() || undefined">
                <button type="submit" class="shrink-0 rounded-lg bg-gradient-to-r from-violet-600 to-fuchsia-500 px-3.5 py-1.5 text-xs font-extrabold text-white shadow-sm transition active:scale-95">
                    {{ __('messages.search') }}
                </button>

                {{-- Live search suggestions dropdown (categories · brands · products) --}}
                <div
                    id="mobile-search-suggestions"
                    x-show="open"
                    x-cloak
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="absolute left-0 right-0 top-full z-50 mt-2 max-h-[70vh] overflow-y-auto overscroll-contain rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-800"
                    role="listbox"
                >
                    {{-- Trending searches (chips, shown before the user types) --}}
                    <template x-if="query.trim().length === 0 && trending.length > 0">
                        <div class="border-b border-slate-100 dark:border-slate-700/60">
                            <x-search-section-header>
                                <span x-text="labels.trending"></span>
                            </x-search-section-header>
                            <div class="flex flex-wrap gap-2 px-3 py-2.5" role="group" :aria-label="labels.trending">
                                <template x-for="t in trending" :key="t.type + '-' + t.label">
                                    <button
                                        type="button"
                                        @click="pickTrending(t)"
                                        class="inline-flex max-w-full items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-600 active:scale-95 dark:border-slate-600 dark:bg-slate-700/60 dark:text-slate-200 dark:hover:border-sky-500/60 dark:hover:bg-slate-600 dark:hover:text-sky-300"
                                    >
                                        <span aria-hidden="true" x-text="t.type === 'category' ? '🗂️' : '🏷️'"></span>
                                        <span class="max-w-[10rem] truncate" x-text="t.label"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- Categories section --}}
                    <template x-if="categories.length > 0">
                        <div>
                            <x-search-section-header>
                                <span x-text="labels.categories"></span> (<span x-text="categories.length"></span>)
                            </x-search-section-header>
                            <template x-for="c in categories" :key="'c' + c.id">
                                <a
                                    :id="'sug-c-' + c.id"
                                    :href="c.url"
                                    @click="open = false"
                                    @mouseenter="activeIndex = c._i"
                                    :class="activeIndex === c._i ? 'bg-sky-50 dark:bg-slate-700' : 'hover:bg-slate-50 dark:hover:bg-slate-700/60'"
                                    class="flex items-center gap-3 border-b border-slate-100 px-3 py-2.5 transition last:border-0 dark:border-slate-700/60"
                                    role="option"
                                    :aria-selected="activeIndex === c._i ? 'true' : 'false'"
                                >
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-base dark:bg-slate-700" aria-hidden="true">
                                        <span x-text="c.icon || '🗂️'"></span>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-xs font-bold text-slate-800 dark:text-slate-100" x-text="c.name"></span>
                                        <span class="mt-0.5 block text-[11px] font-semibold text-slate-400 dark:text-slate-500">
                                            <span x-text="c.count"></span> <span>{{ __('messages.products') }}</span>
                                        </span>
                                    </span>
                                    <svg class="h-4 w-4 shrink-0 text-slate-300 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </template>
                        </div>
                    </template>

                    {{-- Brands section --}}
                    <template x-if="brands.length > 0">
                        <div>
                            <x-search-section-header>
                                <span x-text="labels.brands"></span> (<span x-text="brands.length"></span>)
                            </x-search-section-header>
                            <template x-for="b in brands" :key="'b' + b.id">
                                <a
                                    :id="'sug-b-' + b.id"
                                    :href="b.url"
                                    @click="open = false"
                                    @mouseenter="activeIndex = b._i"
                                    :class="activeIndex === b._i ? 'bg-sky-50 dark:bg-slate-700' : 'hover:bg-slate-50 dark:hover:bg-slate-700/60'"
                                    class="flex items-center gap-3 border-b border-slate-100 px-3 py-2.5 transition last:border-0 dark:border-slate-700/60"
                                    role="option"
                                    :aria-selected="activeIndex === b._i ? 'true' : 'false'"
                                >
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-base dark:bg-slate-700" aria-hidden="true">🏷️</span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-xs font-bold text-slate-800 dark:text-slate-100" x-text="b.name"></span>
                                        <span class="mt-0.5 block text-[11px] font-semibold text-slate-400 dark:text-slate-500">
                                            <span x-text="b.count"></span> <span>{{ __('messages.products') }}</span>
                                        </span>
                                    </span>
                                    <svg class="h-4 w-4 shrink-0 text-slate-300 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </template>
                        </div>
                    </template>

                    {{-- Products section --}}
                    <template x-if="products.length > 0">
                        <div>
                            <x-search-section-header>
                                <span x-text="labels.products"></span> (<span x-text="products.length"></span>)
                            </x-search-section-header>
                            <template x-for="p in products" :key="'p' + p.id">
                                <a
                                    :id="'sug-p-' + p.id"
                                    :href="p.url"
                                    @click="open = false"
                                    @mouseenter="activeIndex = p._i"
                                    :class="activeIndex === p._i ? 'bg-sky-50 dark:bg-slate-700' : 'hover:bg-slate-50 dark:hover:bg-slate-700/60'"
                                    class="flex items-center gap-3 border-b border-slate-100 px-3 py-2.5 transition last:border-0 dark:border-slate-700/60"
                                    role="option"
                                    :aria-selected="activeIndex === p._i ? 'true' : 'false'"
                                >
                                    <img :src="p.image" alt="" loading="lazy" decoding="async" class="h-11 w-11 shrink-0 rounded-lg bg-slate-100 object-cover dark:bg-slate-700" x-show="p.image">
                                    <span x-show="!p.image" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-lg dark:bg-slate-700" aria-hidden="true">📦</span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-xs font-bold text-slate-800 dark:text-slate-100" x-text="p.name"></span>
                                        <span class="mt-0.5 block text-sm font-black text-rose-600 dark:text-rose-400">
                                            <span x-text="p.price"></span>
                                            <span x-show="p.old_price" class="ml-1.5 align-middle text-[11px] font-semibold text-slate-400 line-through dark:text-slate-500" x-text="p.old_price"></span>
                                        </span>
                                    </span>
                                    <svg class="h-4 w-4 shrink-0 text-slate-300 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </template>
                        </div>
                    </template>

                    <div x-show="loading" class="px-4 py-3.5 text-center text-xs font-bold text-slate-400 dark:text-slate-500">
                        <span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-sky-500 border-t-transparent align-middle"></span>
                        <span class="ml-1.5 align-middle">{{ __('messages.loading') }}</span>
                    </div>
                    <div x-show="!loading && !hasAny() && query.trim().length > 0" class="px-4 py-3.5 text-center text-xs font-bold text-slate-400 dark:text-slate-500">
                        {{ __('messages.no_products_found') }}
                    </div>
                </div>
                </form>
                @if ($navCategories->count() > 0)
                    <a
                        href="{{ $browseUrl }}"
                        class="shrink-0 inline-flex h-11 items-center justify-center gap-1 rounded-xl border border-slate-200/80 bg-white px-2 text-slate-700 shadow-sm transition hover:bg-slate-100 hover:text-sky-600 active:scale-95 {{ $isBrowse ? 'border-sky-400 ring-2 ring-sky-500/40 text-sky-600' : '' }} dark:border-slate-700/80 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 dark:hover:text-sky-300"
                        title="{{ __('messages.categories') }}"
                        aria-label="{{ __('messages.categories') }}"
                    >
                        <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <rect x="3" y="3.6" width="4" height="5.4" rx="2.7"/>
                            <rect x="9" y="3.6" width="12" height="5.4" rx="2.7"/>
                            <rect x="3" y="9.3" width="4" height="5.4" rx="2.7"/>
                            <rect x="9" y="9.3" width="12" height="5.4" rx="2.7"/>
                            <rect x="3" y="15" width="4" height="5.4" rx="2.7"/>
                            <rect x="9" y="15" width="12" height="5.4" rx="2.7"/>
                        </svg>
                        <span class="hidden min-[400px]:inline text-xs font-extrabold whitespace-nowrap">{{ __('messages.categories') }}</span>
                    </a>
                @endif
            </div>
        </div>

        {{-- Row 2 (desktop only): Navigation (Linn IT Mart style) --}}
        <div class="hidden lg:block border-t border-slate-200/60 dark:border-slate-800/60 bg-white dark:bg-slate-900">
            <div class="max-w-7xl mx-auto px-3 sm:px-5 lg:px-8 py-2 flex flex-wrap items-center justify-center gap-x-5 gap-y-2">
                {{-- Desktop Navigation --}}
                <nav aria-label="Storefront primary navigation" class="flex items-center gap-1 rounded-2xl border border-slate-200/80 bg-white p-1 text-sm font-extrabold shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <a href="{{ $homeUrl }}" class="rounded-xl px-3 py-2 transition {{ $isHome ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-700 hover:bg-slate-100 hover:text-sky-700 dark:text-slate-200 dark:hover:bg-slate-800 dark:hover:text-sky-300' }}">{{ __('messages.home') }}</a>
                    <a href="{{ $productsUrl }}" class="rounded-xl px-3 py-2 transition {{ $isProducts ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-700 hover:bg-slate-100 hover:text-sky-700 dark:text-slate-200 dark:hover:bg-slate-800 dark:hover:text-sky-300' }}">{{ __('messages.products') }}</a>
                    <a href="{{ $glassFinderUrl }}" class="inline-flex items-center gap-1 rounded-xl px-3 py-2 transition {{ $isGlassFinder ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-700 hover:bg-slate-100 hover:text-sky-700 dark:text-slate-200 dark:hover:bg-slate-800 dark:hover:text-sky-300' }}">
                        <span aria-hidden="true">📱</span>
                        <span>{{ __('messages.glass_finder') }}</span>
                    </a>
                    <a href="{{ $howToOrderUrl }}" class="inline-flex items-center gap-1 rounded-xl px-3 py-2 transition {{ $isHowToOrder ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-700 hover:bg-slate-100 hover:text-sky-700 dark:text-slate-200 dark:hover:bg-slate-800 dark:hover:text-sky-300' }}">
                        <span aria-hidden="true">📖</span>
                        <span>{{ __('messages.how_to_order') }}</span>
                    </a>
                    <a href="{{ $blogUrl }}" class="inline-flex items-center gap-1 rounded-xl px-3 py-2 transition {{ $isBlog ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-700 hover:bg-slate-100 hover:text-sky-700 dark:text-slate-200 dark:hover:bg-slate-800 dark:hover:text-sky-300' }}">
                        <span aria-hidden="true">📝</span>
                        <span>{{ __('messages.blog') }}</span>
                    </a>
                </nav>
            </div>
        </div>

        {{-- Search overlay panel (full-width bar below the header, toggled by the search icon) --}}
        <div
            x-show="searchOpen"
            x-cloak
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute left-0 right-0 top-full z-40 border-b border-slate-200/70 bg-white/95 shadow-xl backdrop-blur-xl dark:border-slate-800/70 dark:bg-slate-900/95"
            x-data="searchSuggestions('{{ $activeStoreSlug }}', '{{ url('/products/suggestions') }}', { categories: '{{ __('messages.categories') }}', brands: '{{ __('messages.brands') }}', products: '{{ __('messages.products') }}', trending: '{{ __('messages.trending_searches') }}' })"
            @click.outside="open = false"
        >
            <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
                <form action="{{ url('/products') }}" method="GET" class="relative flex items-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-2 text-xs shadow-sm transition focus-within:border-sky-500 focus-within:ring-1 focus-within:ring-sky-500/30 dark:border-slate-600 dark:bg-slate-800 sm:text-sm">
                    <input type="hidden" name="store_slug" value="{{ $activeStoreSlug }}">
                    <svg class="h-4 w-4 shrink-0 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" id="desktop-search-input" x-model="query" @input="onInput()" @focus="onFocus()" @keydown="onKeydown($event)" x-ref="searchInput" autocomplete="off" placeholder="{{ __('messages.search_products') }}" class="w-full bg-transparent outline-none text-slate-700 placeholder:text-slate-600 dark:text-slate-200 dark:placeholder:text-slate-500" role="combobox" aria-expanded="open ? 'true' : 'false'" aria-autocomplete="list" aria-controls="desktop-search-suggestions" :aria-activedescendant="activeId() || undefined">
                    <button type="submit" class="shrink-0 rounded-xl bg-gradient-to-r from-violet-600 to-fuchsia-500 px-4 py-2 text-xs font-extrabold text-white shadow-md shadow-sky-500/20 transition active:scale-95">
                        {{ __('messages.search') }}
                    </button>
                    <button type="button" @click="searchOpen = false" class="shrink-0 rounded-xl px-2 py-2 text-slate-600 transition hover:text-slate-600 dark:hover:text-slate-200" aria-label="{{ __('messages.close_menu') }}" title="{{ __('messages.close_menu') }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>

                    {{-- Live search suggestions dropdown (categories · brands · products) --}}
                    <div
                        id="desktop-search-suggestions"
                        x-show="open"
                        x-cloak
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="absolute left-0 right-0 top-full z-50 mt-2 max-h-[70vh] overflow-y-auto overscroll-contain rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-800"
                        role="listbox"
                    >
                        {{-- Trending searches (chips, shown before the user types) --}}
                        <template x-if="query.trim().length === 0 && trending.length > 0">
                            <div class="border-b border-slate-100 dark:border-slate-700/60">
                                <x-search-section-header>
                                    <span x-text="labels.trending"></span>
                                </x-search-section-header>
                                <div class="flex flex-wrap gap-2 px-3 py-2.5" role="group" :aria-label="labels.trending">
                                    <template x-for="t in trending" :key="t.type + '-' + t.label">
                                        <button
                                            type="button"
                                            @click="pickTrending(t)"
                                            class="inline-flex max-w-full items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-600 active:scale-95 dark:border-slate-600 dark:bg-slate-700/60 dark:text-slate-200 dark:hover:border-sky-500/60 dark:hover:bg-slate-600 dark:hover:text-sky-300"
                                        >
                                            <span aria-hidden="true" x-text="t.type === 'category' ? '🗂️' : '🏷️'"></span>
                                            <span class="max-w-[10rem] truncate" x-text="t.label"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>

                        {{-- Categories section --}}
                        <template x-if="categories.length > 0">
                            <div>
                                <x-search-section-header>
                                    <span x-text="labels.categories"></span> (<span x-text="categories.length"></span>)
                                </x-search-section-header>
                                <template x-for="c in categories" :key="'c' + c.id">
                                    <a
                                        :id="'sug-c-' + c.id"
                                        :href="c.url"
                                        @click="open = false"
                                        @mouseenter="activeIndex = c._i"
                                        :class="activeIndex === c._i ? 'bg-sky-50 dark:bg-slate-700' : 'hover:bg-slate-50 dark:hover:bg-slate-700/60'"
                                        class="flex items-center gap-3 border-b border-slate-100 px-3 py-2.5 transition last:border-0 dark:border-slate-700/60"
                                        role="option"
                                        :aria-selected="activeIndex === c._i ? 'true' : 'false'"
                                    >
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-base dark:bg-slate-700" aria-hidden="true">
                                            <span x-text="c.icon || '🗂️'"></span>
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-xs font-bold text-slate-800 dark:text-slate-100" x-text="c.name"></span>
                                            <span class="mt-0.5 block text-[11px] font-semibold text-slate-400 dark:text-slate-500">
                                                <span x-text="c.count"></span> <span>{{ __('messages.products') }}</span>
                                            </span>
                                        </span>
                                        <svg class="h-4 w-4 shrink-0 text-slate-300 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </template>
                            </div>
                        </template>

                        {{-- Brands section --}}
                        <template x-if="brands.length > 0">
                            <div>
                                <x-search-section-header>
                                    <span x-text="labels.brands"></span> (<span x-text="brands.length"></span>)
                                </x-search-section-header>
                                <template x-for="b in brands" :key="'b' + b.id">
                                    <a
                                        :id="'sug-b-' + b.id"
                                        :href="b.url"
                                        @click="open = false"
                                        @mouseenter="activeIndex = b._i"
                                        :class="activeIndex === b._i ? 'bg-sky-50 dark:bg-slate-700' : 'hover:bg-slate-50 dark:hover:bg-slate-700/60'"
                                        class="flex items-center gap-3 border-b border-slate-100 px-3 py-2.5 transition last:border-0 dark:border-slate-700/60"
                                        role="option"
                                        :aria-selected="activeIndex === b._i ? 'true' : 'false'"
                                    >
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-base dark:bg-slate-700" aria-hidden="true">🏷️</span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-xs font-bold text-slate-800 dark:text-slate-100" x-text="b.name"></span>
                                            <span class="mt-0.5 block text-[11px] font-semibold text-slate-400 dark:text-slate-500">
                                                <span x-text="b.count"></span> <span>{{ __('messages.products') }}</span>
                                            </span>
                                        </span>
                                        <svg class="h-4 w-4 shrink-0 text-slate-300 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </template>
                            </div>
                        </template>

                        {{-- Products section --}}
                        <template x-if="products.length > 0">
                            <div>
                                <x-search-section-header>
                                    <span x-text="labels.products"></span> (<span x-text="products.length"></span>)
                                </x-search-section-header>
                                <template x-for="p in products" :key="'p' + p.id">
                                    <a
                                        :id="'sug-p-' + p.id"
                                        :href="p.url"
                                        @click="open = false"
                                        @mouseenter="activeIndex = p._i"
                                        :class="activeIndex === p._i ? 'bg-sky-50 dark:bg-slate-700' : 'hover:bg-slate-50 dark:hover:bg-slate-700/60'"
                                        class="flex items-center gap-3 border-b border-slate-100 px-3 py-2.5 transition last:border-0 dark:border-slate-700/60"
                                        role="option"
                                        :aria-selected="activeIndex === p._i ? 'true' : 'false'"
                                    >
                                        <img :src="p.image" alt="" loading="lazy" decoding="async" class="h-11 w-11 shrink-0 rounded-lg bg-slate-100 object-cover dark:bg-slate-700" x-show="p.image">
                                        <span x-show="!p.image" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-lg dark:bg-slate-700" aria-hidden="true">📦</span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-xs font-bold text-slate-800 dark:text-slate-100" x-text="p.name"></span>
                                            <span class="mt-0.5 block text-sm font-black text-rose-600 dark:text-rose-400">
                                                <span x-text="p.price"></span>
                                                <span x-show="p.old_price" class="ml-1.5 align-middle text-[11px] font-semibold text-slate-400 line-through dark:text-slate-500" x-text="p.old_price"></span>
                                            </span>
                                        </span>
                                        <svg class="h-4 w-4 shrink-0 text-slate-300 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </template>
                            </div>
                        </template>

                        <div x-show="loading" class="px-4 py-3.5 text-center text-xs font-bold text-slate-400 dark:text-slate-500">
                            <span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-sky-500 border-t-transparent align-middle"></span>
                            <span class="ml-1.5 align-middle">{{ __('messages.loading') }}</span>
                        </div>
                        <div x-show="!loading && !hasAny() && query.trim().length > 0" class="px-4 py-3.5 text-center text-xs font-bold text-slate-400 dark:text-slate-500">
                            {{ __('messages.no_products_found') }}
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Mobile Menu Slide-Down Panel (below lg, toggled by hamburger) --}}
        </div>{{-- /glass-header (header rows) --}}

        {{-- Mobile Left Drawer (slide-in from the left; ☰ button or swipe right from the left edge opens it) --}}
        <div
            x-show="mobileMenuOpen"
            x-cloak
            id="storefront-mobile-nav"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            x-effect="document.body.classList.toggle('overflow-hidden', mobileMenuOpen); if (mobileMenuOpen) $nextTick(() => $refs.drawerClose && $refs.drawerClose.focus())"
            @keyup.escape.window="mobileMenuOpen = false"
            class="lg:hidden fixed inset-y-0 left-0 z-[60] w-[86vw] max-w-sm flex flex-col bg-white shadow-2xl dark:bg-slate-900"
            role="dialog"
            aria-modal="true"
            aria-label="{{ __('messages.menu') }}"
        >
            {{-- Drawer header --}}
            <div class="flex h-16 shrink-0 items-center gap-2 border-b border-slate-200/80 px-4 dark:border-slate-800/80">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-tr from-violet-600 to-fuchsia-500 text-sm font-black text-white shadow-md">AT</div>
                <div class="min-w-0 flex-1">
                    <p class="truncate font-outfit text-sm font-black text-slate-900 dark:text-white">{{ $storeDisplayName }}</p>
                    <p class="truncate text-[11px] font-bold text-sky-600 dark:text-sky-400">{{ __('messages.menu') }}</p>
                </div>
                <button
                    type="button"
                    x-ref="drawerClose"
                    @click="mobileMenuOpen = false"
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-100 active:scale-95 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                    aria-label="{{ __('messages.close_menu') }}"
                    title="{{ __('messages.close_menu') }}"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto overscroll-contain px-4 py-4 space-y-3">
                {{-- Mobile Menu Utility: Account / Login --}}
                <div class="flex items-center gap-2">
                    {{-- Account / Login --}}
                    @auth
                        <a href="{{ $accountUrl }}" @click="mobileMenuOpen = false" class="flex h-11 min-w-0 flex-1 items-center gap-2 rounded-2xl border border-violet-400 bg-gradient-to-r from-violet-600 to-fuchsia-500 px-3 text-sm font-extrabold text-white shadow-md shadow-sky-500/20 transition hover:brightness-110 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-sky-500/60">
                            <span aria-hidden="true">👤</span>
                            <span class="flex-1 truncate text-left">{{ auth()->user()->name }}</span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" @click="mobileMenuOpen = false" class="flex h-11 min-w-0 flex-1 items-center gap-2 rounded-2xl border border-slate-200 dark:border-slate-700 bg-gradient-to-r from-violet-600 to-fuchsia-500 px-3 text-sm font-extrabold text-white shadow-md shadow-sky-500/20 transition hover:brightness-110 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-sky-500/60">
                            <span aria-hidden="true">🔑</span>
                            <span class="flex-1 truncate text-left">{{ __('messages.login') }}</span>
                        </a>
                    @endauth
                </div>

                {{-- Mobile Nav Links --}}
                <nav aria-label="{{ __('messages.menu') }}" data-mobile-nav class="grid grid-cols-1 gap-1">
                    <a href="{{ $homeUrl }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-extrabold transition {{ $isHome ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800' }}">
                        <span aria-hidden="true">🏠</span>
                        <span>{{ __('messages.home') }}</span>
                    </a>
                    <a href="{{ $productsUrl }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-extrabold transition {{ $isProducts ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800' }}">
                        <span aria-hidden="true">📦</span>
                        <span>{{ __('messages.products') }}</span>
                    </a>
                    <a href="{{ $browseUrl }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-extrabold transition {{ $isBrowse ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800' }}">
                        <span aria-hidden="true">🗂️</span>
                        <span>{{ __('messages.categories') }}</span>
                    </a>
                    <a href="{{ $glassFinderUrl }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-extrabold transition {{ $isGlassFinder ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800' }}">
                        <span aria-hidden="true">📱</span>
                        <span>{{ __('messages.glass_finder') }}</span>
                    </a>
                    <a href="{{ $howToOrderUrl }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-extrabold transition {{ $isHowToOrder ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800' }}">
                        <span aria-hidden="true">📖</span>
                        <span>{{ __('messages.how_to_order') }}</span>
                    </a>
                    <a href="{{ $blogUrl }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-extrabold transition {{ $isBlog ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800' }}">
                        <span aria-hidden="true">📝</span>
                        <span>{{ __('messages.blog') }}</span>
                    </a>
                </nav>
            </div>
        </div>

        {{-- Drawer backdrop (tap to close) --}}
        <div
            x-show="mobileMenuOpen"
            x-cloak
            x-transition.opacity.duration.300ms
            @click="mobileMenuOpen = false"
            class="lg:hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm"
            aria-hidden="true"
        ></div>
    </header>

    {{-- Main Content Container (mobile: tight 4px gutters for a full-width feel; desktop keeps comfortable padding) --}}
    @php $mainPad = $__env->hasSection('noMainPadding') ? 'pt-0 pb-6' : 'py-6'; @endphp
    <main class="max-w-7xl mx-auto px-1 sm:px-6 lg:px-8 {{ $mainPad }} relative z-10">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <x-storefront-footer
        :setting="$setting"
        :store="$activeStoreContext"
        :store-display-name="$storeDisplayName"
        :store-slug="$activeStoreSlug"
    />
    @if (!($hideFloatingFabs ?? false))
    <div
        x-show="$store.orderBuilder && $store.orderBuilder.totalCount > 0"
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="opacity-0 translate-y-8 scale-90"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-8 scale-90"
        x-data="{
            dragging: false,
            moved: false,
            startX: 0, startY: 0,
            offsetX: 0, offsetY: 0,
            posX: null, posY: null,
            init() {
                const saved = localStorage.getItem('cartWidgetPos');
                if (saved) {
                    try {
                        const p = JSON.parse(saved);
                        if (p.x !== null && p.y !== null) {
                            this.posX = p.x; this.posY = p.y;
                            this.$el.style.left = p.x + 'px';
                            this.$el.style.top = p.y + 'px';
                            this.$el.style.right = 'auto';
                            this.$el.style.bottom = 'auto';
                        }
                    } catch(e) {}
                }
            },
            down(e) {
                this.dragging = true;
                this.moved = false;
                const t = e.touches ? e.touches[0] : e;
                this.startX = t.clientX;
                this.startY = t.clientY;
            },
            move(e) {
                if (!this.dragging) return;
                const t = e.touches ? e.touches[0] : e;
                const dx = t.clientX - this.startX;
                const dy = t.clientY - this.startY;
                if (Math.abs(dx) > 6 || Math.abs(dy) > 6) this.moved = true;
                const rect = this.$el.getBoundingClientRect();
                let nx = t.clientX - rect.width / 2;
                let ny = t.clientY - rect.height / 2;
                nx = Math.max(8, Math.min(window.innerWidth - rect.width - 8, nx));
                ny = Math.max(60, Math.min(window.innerHeight - rect.height - 80, ny));
                this.$el.style.left = nx + 'px';
                this.$el.style.top = ny + 'px';
                this.$el.style.right = 'auto';
                this.$el.style.bottom = 'auto';
                this.posX = nx; this.posY = ny;
                if (e.touches) e.preventDefault();
            },
            up() {
                if (!this.dragging) return;
                this.dragging = false;
                if (this.moved && this.posX !== null) {
                    localStorage.setItem('cartWidgetPos', JSON.stringify({ x: this.posX, y: this.posY }));
                }
            }
        }"
        x-init="init()"
        @touchstart.passive="down($event)"
        @touchmove.prevent="move($event)"
        @touchend="up()"
        @mousedown="down($event)"
        @mousemove="dragging && move($event)"
        @mouseup="up()"
        @mouseleave="dragging && up()"
        :class="dragging ? 'cursor-grabbing' : 'cursor-grab'"
        :style="moved ? 'user-select: none;' : ''"
        class="fixed bottom-[calc(env(safe-area-inset-bottom,0px)+10.625rem)] md:bottom-6 right-4 z-40 select-none"
    >
        <a
            href="{{ $orderBuilderUrl }}"
            @click.prevent="if (!moved) window.location.href = '{{ $orderBuilderUrl }}'"
            class="group relative flex items-center space-x-3 px-4 py-3 rounded-2xl bg-white/95 dark:bg-slate-800/95 backdrop-blur-xl border border-sky-400/40 dark:border-sky-500/50 shadow-2xl shadow-sky-500/20 dark:shadow-sky-500/40 hover:scale-105 active:scale-95 transition-all duration-300"
        >
            {{-- Glowing 3D Cart Icon Circle --}}
            <div class="relative w-11 h-11 rounded-xl bg-gradient-to-tr from-violet-600 via-fuchsia-500 to-rose-500 flex items-center justify-center text-white shadow-lg shadow-sky-500/50 group-hover:rotate-6 transition-transform">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                {{-- Animated Badge Count --}}
                <span class="absolute -top-2 -right-2 min-w-[22px] h-[22px] px-1.5 rounded-full bg-rose-500 text-white font-black text-xs flex items-center justify-center border-2 border-white dark:border-slate-900 shadow-md animate-pulse" x-text="$store.orderBuilder ? $store.orderBuilder.totalCount : 0"></span>
            </div>

            <div class="flex flex-col text-left">
                <span class="text-xs font-black text-slate-900 dark:text-white font-outfit tracking-wide group-hover:text-sky-700 dark:group-hover:text-sky-300 transition-colors">
                    {{ __('messages.order_builder') }}
                </span>
                <span class="text-xs font-extrabold text-sky-700 dark:text-sky-400 font-mono" x-text="'Ks ' + ($store.orderBuilder ? $store.orderBuilder.totalAmount.toLocaleString() : 0)"></span>
            </div>
        </a>
    </div>
    @endif

    {{-- Mobile Bottom Touch-Friendly Floating Glass Navigation Bar --}}
    <div
        x-data="{
            navHidden: false,
            lastY: 0,
            init() {
                this.lastY = window.scrollY;
                window.addEventListener('scroll', () => {
                    const y = window.scrollY;
                    const delta = y - this.lastY;
                    if (y <= 80) {
                        this.navHidden = false;
                    } else if (delta > 6) {
                        this.navHidden = true;
                    } else if (delta < -6) {
                        this.navHidden = false;
                    }
                    this.lastY = y;
                }, { passive: true });
            }
        }"
        :class="navHidden ? 'translate-y-[180%] opacity-0 pointer-events-none' : 'translate-y-0 opacity-100'"
        class="md:hidden fixed bottom-0 inset-x-0 z-40 flex items-stretch text-xs transition-all duration-300 ease-out will-change-transform bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl border-t border-slate-200 dark:border-slate-800 shadow-[0_-6px_24px_rgba(15,23,42,0.08)] pb-[env(safe-area-inset-bottom)]"
    >
        <a href="{{ $homeUrl }}" class="group relative flex flex-1 flex-col items-center justify-center gap-1 py-2 min-h-[52px] transition-all duration-200 {{ $isHome ? 'text-sky-700 dark:text-sky-300' : 'text-slate-500 dark:text-slate-600 hover:text-slate-800 dark:hover:text-slate-200 active:scale-95' }}">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl transition-all duration-200 {{ $isHome ? 'bg-gradient-to-br from-sky-500 to-blue-600 text-white shadow-lg shadow-sky-500/40 scale-110' : 'bg-sky-100 dark:bg-sky-950/60 text-sky-600 dark:text-sky-300 group-hover:bg-sky-200 dark:group-hover:bg-sky-900/60' }}">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/></svg>
            </span>
            <span class="text-xs font-bold tracking-tight {{ $isHome ? 'font-black' : '' }}">{{ __('messages.nav_home') }}</span>
        </a>
        <a href="{{ $productsUrl }}" class="group relative flex flex-1 flex-col items-center justify-center gap-1 py-2 min-h-[52px] transition-all duration-200 {{ $isProducts ? 'text-violet-700 dark:text-violet-300' : 'text-slate-500 dark:text-slate-600 hover:text-slate-800 dark:hover:text-slate-200 active:scale-95' }}">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl transition-all duration-200 {{ $isProducts ? 'bg-gradient-to-br from-violet-600 to-fuchsia-500 text-white shadow-lg shadow-violet-500/40 scale-110' : 'bg-violet-100 dark:bg-violet-950/60 text-violet-600 dark:text-violet-300 group-hover:bg-violet-200 dark:group-hover:bg-violet-900/60' }}">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>
            </span>
            <span class="text-xs font-bold tracking-tight {{ $isProducts ? 'font-black' : '' }}">{{ __('messages.nav_products') }}</span>
        </a>
        <a href="{{ $glassFinderUrl }}" class="group relative flex flex-1 flex-col items-center justify-center gap-1 py-2 min-h-[52px] transition-all duration-200 {{ $isGlassFinder ? 'text-cyan-700 dark:text-cyan-300' : 'text-slate-500 dark:text-slate-600 hover:text-slate-800 dark:hover:text-slate-200 active:scale-95' }}">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl transition-all duration-200 {{ $isGlassFinder ? 'bg-gradient-to-br from-cyan-500 to-teal-500 text-white shadow-lg shadow-cyan-500/40 scale-110' : 'bg-cyan-100 dark:bg-cyan-950/60 text-cyan-600 dark:text-cyan-300 group-hover:bg-cyan-200 dark:group-hover:bg-cyan-900/60' }}">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
            </span>
            <span class="text-xs font-bold tracking-tight {{ $isGlassFinder ? 'font-black' : '' }}">{{ __('messages.nav_glass_finder') }}</span>
        </a>
        <a href="{{ $orderBuilderUrl }}" class="group relative flex flex-1 flex-col items-center justify-center gap-1 py-2 min-h-[52px] transition-all duration-200 {{ $isOrderBuilder ? 'text-rose-700 dark:text-rose-300' : 'text-slate-500 dark:text-slate-600 hover:text-slate-800 dark:hover:text-slate-200 active:scale-95' }}">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl relative transition-all duration-200 {{ $isOrderBuilder ? 'bg-gradient-to-br from-rose-500 to-red-500 text-white shadow-lg shadow-rose-500/40 scale-110' : 'bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-300 group-hover:bg-rose-200 dark:group-hover:bg-rose-900/60' }}">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/></svg>
                <span x-show="$store.orderBuilder && $store.orderBuilder.totalCount > 0" 
                      class="absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] px-1 rounded-full bg-gradient-to-br from-rose-500 to-rose-600 text-white font-black text-xs flex items-center justify-center border border-white/40 shadow-lg"
                      x-text="$store.orderBuilder.totalCount"></span>
            </span>
            <span class="text-xs font-bold tracking-tight {{ $isOrderBuilder ? 'font-black' : '' }}">{{ __('messages.nav_cart') }}</span>
        </a>
        @auth
            <a href="{{ $accountUrl }}" class="group relative flex flex-1 flex-col items-center justify-center gap-1 py-2 min-h-[52px] transition-all duration-200 {{ $isAccount ? 'text-emerald-700 dark:text-emerald-300' : 'text-slate-500 dark:text-slate-600 hover:text-slate-800 dark:hover:text-slate-200 active:scale-95' }}">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl transition-all duration-200 {{ $isAccount ? 'bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-lg shadow-emerald-500/40 scale-110' : 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-300 group-hover:bg-emerald-200 dark:group-hover:bg-emerald-900/60' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                </span>
                <span class="text-xs font-bold tracking-tight {{ $isAccount ? 'font-black' : '' }}">{{ __('messages.nav_account') }}</span>
            </a>
        @else
            <a href="{{ route('login') }}" class="group relative flex flex-1 flex-col items-center justify-center gap-1 py-2 min-h-[52px] transition-all duration-200 {{ $isAccount ? 'text-amber-700 dark:text-amber-300' : 'text-slate-500 dark:text-slate-600 hover:text-slate-800 dark:hover:text-slate-200 active:scale-95' }}">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl transition-all duration-200 {{ $isAccount ? 'bg-gradient-to-br from-amber-500 to-orange-500 text-white shadow-lg shadow-amber-500/40 scale-110' : 'bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-300 group-hover:bg-amber-200 dark:group-hover:bg-amber-900/60' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/></svg>
                </span>
                <span class="text-xs font-bold tracking-tight {{ $isAccount ? 'font-black' : '' }}">{{ __('messages.login') }}</span>
            </a>
        @endauth
    </div>

    {{-- Floating Contact Button (mobile) — opens popup listing the chat/social channels Admin configured --}}
    @php
        $floatTelegram = \App\Support\ContactLinkBuilder::telegramUrl($setting?->telegram_username);
        $floatViber = \App\Support\ContactLinkBuilder::viberChatUrl($setting?->viber_number);
        $floatLabel = trim((string) ($setting?->chat_button_label ?? '')) ?: __('messages.chat_with_us');
        $floatIcon = trim((string) ($setting?->chat_button_icon ?? ''));
        $floatIconPath = trim((string) ($setting?->chat_button_icon_path ?? ''));
        $floatIconUrl = \App\Support\StorefrontAsset::imageUrl($floatIconPath);

        // Only allow safe schemes — never render javascript:/data: etc.
        $floatSafeUrl = function ($url) {
            $url = trim((string) $url);
            return preg_match('#^(https?://|viber://|tel:|tg://)#i', $url) ? $url : null;
        };

        // Auto channels (Viber / Telegram / socials / custom button URL) — used
        // only when Admin has not configured their own chat_channels list.
        $autoChannels = [];
        if ($floatViber) {
            $autoChannels[] = ['label' => 'Viber', 'brand' => 'viber', 'icon' => '💬', 'icon_path' => null, 'href' => $floatViber, 'color' => 'text-violet-600 dark:text-violet-300', 'hoverBg' => 'hover:bg-violet-50 dark:hover:bg-violet-950/30'];
        }
        if ($floatTelegram) {
            $autoChannels[] = ['label' => 'Telegram', 'brand' => 'telegram', 'icon' => '✈️', 'icon_path' => null, 'href' => $floatTelegram, 'color' => 'text-sky-600 dark:text-sky-300', 'hoverBg' => 'hover:bg-sky-50 dark:hover:bg-sky-950/30'];
        }
        if ($fbUrl = $floatSafeUrl($setting?->facebook_url)) {
            $autoChannels[] = ['label' => __('messages.facebook'), 'brand' => 'facebook', 'icon' => '📘', 'icon_path' => null, 'href' => $fbUrl, 'color' => 'text-blue-600 dark:text-blue-300', 'hoverBg' => 'hover:bg-blue-50 dark:hover:bg-blue-950/30'];
        }
        if ($ytUrl = $floatSafeUrl($setting?->youtube_url)) {
            $autoChannels[] = ['label' => __('messages.youtube'), 'brand' => 'youtube', 'icon' => '📺', 'icon_path' => null, 'href' => $ytUrl, 'color' => 'text-red-600 dark:text-red-300', 'hoverBg' => 'hover:bg-red-50 dark:hover:bg-red-950/30'];
        }
        if ($ttUrl = $floatSafeUrl($setting?->tiktok_url)) {
            $autoChannels[] = ['label' => __('messages.tiktok'), 'brand' => 'tiktok', 'icon' => '🎵', 'icon_path' => null, 'href' => $ttUrl, 'color' => 'text-slate-700 dark:text-slate-200', 'hoverBg' => 'hover:bg-slate-50 dark:hover:bg-slate-950/30'];
        }

        // Admin-configured channels (Settings → Contact → Chat Channels) take
        // precedence; fall back to the auto list when none are set.
        $adminChannels = collect($setting?->chat_channels ?? [])
            ->map(fn ($channel) => [
                'label' => trim((string) ($channel['label'] ?? '')) ?: __('messages.chat_with_us'),
                'icon' => $channel['icon'] ?? null,
                'icon_path' => $channel['icon_path'] ?? null,
                'icon_url' => \App\Support\StorefrontAsset::imageUrl($channel['icon_path'] ?? null),
                'href' => $floatSafeUrl($channel['href'] ?? null),
                'hoverBg' => 'hover:bg-slate-100 dark:hover:bg-slate-700',
            ])
            ->filter(fn ($channel) => $channel['href'] !== null)
            ->values()
            ->all();
        $chatChannels = $adminChannels ?: $autoChannels;
    @endphp
    @if ($chatChannels && !($hideFloatingFabs ?? false))
        <div
            class="fixed bottom-[calc(env(safe-area-inset-bottom,0px)+5.5rem)] right-4 z-50"
            data-draggable-fab="chat"
            x-data="{ chatOpen: false, hoverable: window.matchMedia('(hover: hover)').matches }"
            @click.outside="chatOpen = false"
            @mouseenter="hoverable && (chatOpen = true)"
            @mouseleave="hoverable && (chatOpen = false)"
        >
            <div
                x-show="chatOpen"
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                class="absolute bottom-full right-0 mb-3 w-60 overflow-hidden rounded-2xl border border-slate-200/80 bg-white/95 dark:border-slate-700/80 dark:bg-slate-800/95 shadow-xl ring-1 ring-black/5 backdrop-blur-md"
                role="menu"
                :aria-expanded="chatOpen ? 'true' : 'false'"
            >
                <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-700/60 px-4 py-2.5">
                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-gradient-to-br from-violet-500 to-sky-500 text-xs">💬</span>
                    <p class="text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-600 font-outfit">
                        {{ __('messages.chat_with_us') }}
                    </p>
                </div>
                <div class="p-1.5">
                    @foreach ($chatChannels as $channel)
                        @php
                            $isCall = str_starts_with($channel['href'] ?? '', 'tel:');
                            $iconTint = $isCall ? 'bg-emerald-50 dark:bg-emerald-950/40' : 'bg-slate-100 dark:bg-slate-700/60';
                            // Viber rows (auto brand or admin viber:// channel) get the
                            // "Get Viber" not-installed fallback, matching the footer.
                            $isViber = ($channel['brand'] ?? null) === 'viber' || str_starts_with($channel['href'] ?? '', 'viber://');
                        @endphp
                        <div @if ($isViber) class="rounded-xl pb-1 transition hover:bg-slate-50 dark:hover:bg-slate-700/40" @endif>
                            <a href="{{ $channel['href'] }}" target="_blank" rel="noopener noreferrer" @click="chatOpen = false"
                               class="group w-full flex items-center gap-3 rounded-xl px-2.5 py-2 text-sm font-semibold transition {{ $channel['hoverBg'] ?? 'hover:bg-slate-100 dark:hover:bg-slate-700' }}"
                               role="menuitem">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $iconTint }}">
                                    @if (! empty($channel['icon_url']))
                                        <img src="{{ $channel['icon_url'] }}" alt="" class="h-5 w-5 rounded object-contain" width="20" height="20" loading="lazy" decoding="async">
                                    @elseif (! empty($channel['brand']))
                                        <x-brand-icon :brand="$channel['brand']" class="h-5 w-5 {{ $channel['color'] ?? '' }}"/>
                                    @else
                                        <span class="text-base leading-none">{{ $channel['icon'] ?: '💬' }}</span>
                                    @endif
                                </span>
                                <span class="flex-1 {{ $channel['color'] ?? 'text-slate-700 dark:text-slate-200' }}">{{ $channel['label'] }}</span>
                                @if ($isCall)
                                    <svg class="h-4 w-4 text-emerald-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M4.5 2.5a1 1 0 0 0-1 1v1c0 6 4.5 10.5 10.5 10.5h1a1 1 0 0 0 1-1v-2.3a1 1 0 0 0-.8-1l-2.4-.5a1 1 0 0 0-1 .3l-.8.8a8 8 0 0 1-3.7-3.7l.8-.8a1 1 0 0 0 .3-1l-.5-2.4a1 1 0 0 0-1-.8z"/></svg>
                                @endif
                            </a>
                            @if ($isViber)
                                <p class="px-2.5 pb-1.5 text-[11px] text-slate-400 dark:text-slate-500">
                                    {{ __('messages.viber_missing') }}
                                    <a href="https://www.viber.com/download/" target="_blank" rel="noopener noreferrer" @click="chatOpen = false"
                                       class="font-bold text-sky-600 transition hover:text-sky-500 dark:text-sky-400 dark:hover:text-sky-300">{{ __('messages.viber_install') }} →</a>
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="relative">
                {{-- Glow halo behind the FAB for the "raised" floating look --}}
                <span aria-hidden="true" class="pointer-events-none absolute -inset-2 rounded-full bg-gradient-to-br {{ $floatTelegram ? 'from-sky-400/30 to-blue-500/30' : 'from-violet-400/30 to-fuchsia-500/30' }} blur-xl"></span>
                {{-- Attention pulse ring (decorative — must never block button clicks) --}}
                <span x-show="!chatOpen" x-cloak class="pointer-events-none absolute inset-0 rounded-full {{ $floatTelegram ? 'bg-sky-400/50' : 'bg-violet-400/50' }} animate-ping" aria-hidden="true"></span>
                <button
                    type="button"
                    @click.stop.prevent="chatOpen = !chatOpen"
                    aria-haspopup="true"
                    :aria-expanded="chatOpen ? 'true' : 'false'"
                    aria-label="{{ $floatLabel }}"
                    class="relative flex h-16 w-16 items-center justify-center rounded-full shadow-2xl ring-1 ring-black/10 transition duration-200 hover:-translate-y-1 hover:scale-110 active:scale-95 dark:ring-white/15"
                >
                    @if ($floatIconUrl)
                        <span class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-full bg-white dark:bg-slate-800">
                            <img src="{{ $floatIconUrl }}" alt="{{ $floatLabel }}"
                                 class="h-full w-full object-cover"
                                 loading="eager" decoding="async"
                                 x-on:error="$el.style.display='none'; $el.parentElement.innerHTML = $el.parentElement.dataset.fallback"
                                 data-fallback="{!! '<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; viewBox=&quot;0 0 24 24&quot; fill=&quot;currentColor&quot; class=&quot;h-8 w-8 ' . ($floatTelegram ? 'text-sky-500' : 'text-violet-600') . '&quot; aria-hidden=&quot;true&quot;><path d=&quot;' . \App\Support\BrandIconPath::get($floatTelegram ? 'telegram' : 'viber') . '&quot;/></svg>' !!}">
                        </span>
                    @elseif ($floatIcon)
                        <span class="flex h-16 w-16 items-center justify-center rounded-full bg-white text-3xl leading-none shadow-inner dark:bg-slate-800">{{ $floatIcon }}</span>
                    @else
                        <span class="flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br {{ $floatTelegram ? 'from-sky-400 via-sky-500 to-blue-600' : 'from-violet-400 via-violet-500 to-fuchsia-600' }} shadow-inner">
                            <x-brand-icon :brand="$floatTelegram ? 'telegram' : 'viber'" class="h-8 w-8 text-white drop-shadow-lg"/>
                        </span>
                    @endif
                </button>
                {{-- Tiny online dot indicator --}}
                <span class="absolute right-1 top-1 flex h-3.5 w-3.5">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex h-3.5 w-3.5 rounded-full border-2 border-white bg-emerald-500 dark:border-slate-800"></span>
                </span>
            </div>
        </div>
    @endif

    @stack('modals')

    @stack('scripts')

    {{-- Web Push frontend — loads after parsing (defer) so it never blocks
         first paint; reads the VAPID key + CSRF token from meta tags. --}}
    <script src="/js/push-notification.js" defer></script>

    {{-- Viber deep-link fallback — shows the number + copy button when the
         viber:// scheme cannot open (embedded browsers, Viber not installed). --}}
    <script src="/js/viber-fallback.js" defer></script>

    {{-- Draggable floating buttons — press + drag to reposition the
         notification bell and the chat FAB (position saved locally). --}}
    <script src="/js/draggable-fabs.js" defer></script>

    {{-- Bell bounce on first appearance (subtle, plays once) --}}
    <style>
        .push-bell-bounce { animation: push-bell-bounce 1.2s ease 2; }
        @keyframes push-bell-bounce {
            0%, 100% { transform: translateY(0); }
            25% { transform: translateY(-8px); }
            50% { transform: translateY(0); }
            75% { transform: translateY(-4px); }
        }
    </style>

    {{-- Keep scroll position stable across full-page reloads (filters, pagination, forms) --}}
    <script nonce="{{ $cspNonce }}">
        (function () {
            var KEY = 'scrollPos';
            var saved = null;
            try { saved = JSON.parse(sessionStorage.getItem(KEY)); } catch (e) {}
            // Restore only on the same route (filter submits, pagination) — other pages start at top
            if (saved && saved.path === location.pathname && !location.hash && saved.y > 0) {
                var restore = function () { window.scrollTo(0, saved.y); };
                restore();
                // Fonts can grow the page after first paint — re-apply once fully loaded
                window.addEventListener('load', restore);
            }
            try { sessionStorage.removeItem(KEY); } catch (e) {}
            window.addEventListener('pagehide', function () {
                try { sessionStorage.setItem(KEY, JSON.stringify({ path: location.pathname, y: window.scrollY })); } catch (e) {}
            });
        })();
    </script>
    {{-- Web Push notification bell — appears after the visitor has browsed
         5+ pages, bottom-left above the mobile nav. Hidden permanently once
         notifications are granted or denied. See public/js/push-notification.js. --}}
    <button
        type="button"
        id="push-notification-bell"
        class="hidden fixed bottom-[calc(env(safe-area-inset-bottom,0px)+5.5rem)] md:bottom-24 left-4 z-50 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-600 to-fuchsia-500 text-white shadow-2xl shadow-violet-500/40 ring-1 ring-white/20 transition hover:scale-110 active:scale-95 push-bell-bounce"
        aria-label="{{ __('messages.push_enable_title') }}"
        title="{{ __('messages.push_enable_title') }}"
    >
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        <span id="push-notification-badge" class="hidden absolute -top-1.5 -right-1.5 min-w-[20px] h-[20px] px-1 rounded-full bg-rose-500 text-white font-black text-[11px] flex items-center justify-center border-2 border-white dark:border-slate-900 shadow-md">1</span>
    </button>

    {{-- PWA install banner — appears after the 3rd visit when the browser
         offers installation (beforeinstallprompt); "Not now" dismisses
         permanently via localStorage. Sits above the mobile bottom nav. --}}
    <div id="pwa-install-banner" class="hidden fixed inset-x-3 bottom-24 z-50 mx-auto max-w-md rounded-2xl border border-slate-200 bg-white/95 shadow-2xl backdrop-blur-xl p-4 md:inset-x-auto md:right-6 md:bottom-6 md:mx-0 dark:border-slate-700 dark:bg-slate-900/95" role="dialog" aria-live="polite" aria-label="{{ __('messages.pwa_install_title') }}">
        <div class="flex items-start gap-3">
            <img src="/icons/icon-192.png" alt="" class="h-12 w-12 shrink-0 rounded-xl shadow-sm ring-1 ring-slate-200 dark:ring-slate-700" width="48" height="48">
            <div class="min-w-0 flex-1">
                <p class="text-sm font-black text-slate-900 dark:text-white">{{ __('messages.pwa_install_title') }}</p>
                <p class="mt-0.5 text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('messages.pwa_install_desc') }}</p>
            </div>
            <button type="button" id="pwa-install-dismiss" class="shrink-0 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-200" aria-label="{{ __('messages.close') }}">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="mt-3 flex items-center gap-2">
            <button type="button" id="pwa-install-btn" class="flex-1 rounded-xl bg-gradient-to-r from-rose-600 to-red-600 px-4 py-2.5 text-sm font-extrabold text-white shadow-md shadow-red-600/25 transition hover:brightness-110 active:scale-95">
                📲 {{ __('messages.pwa_install_btn') }}
            </button>
            <button type="button" id="pwa-install-notnow" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600 transition hover:bg-slate-100 active:scale-95 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                {{ __('messages.pwa_install_notnow') }}
            </button>
        </div>
    </div>

    {{-- PWA service-worker registration + install-prompt handling --}}
    <script nonce="{{ $cspNonce }}">
        (function () {
            var KEY_VISITS = 'pwa_visits';
            var KEY_DISMISSED = 'pwa_dismissed';
            var KEY_INSTALLED = 'pwa_installed';

            // 1. Count this page load as one visit.
            var visits = 999; // storage blocked → eligible immediately
            try {
                visits = parseInt(localStorage.getItem(KEY_VISITS) || '0', 10) + 1;
                localStorage.setItem(KEY_VISITS, String(visits));
            } catch (e) {}

            var dismissed = false, installed = false;
            try {
                dismissed = localStorage.getItem(KEY_DISMISSED) === '1';
                installed = localStorage.getItem(KEY_INSTALLED) === '1';
            } catch (e) {}

            var isStandalone = window.matchMedia && window.matchMedia('(display-mode: standalone)').matches
                || window.navigator.standalone === true;

            // 2. Register the service worker (root scope covers the whole site).
            var isSecure = location.protocol === 'https:'
                || location.hostname === 'localhost' || location.hostname === '127.0.0.1';
            if ('serviceWorker' in navigator && isSecure) {
                window.addEventListener('load', function () {
                    navigator.serviceWorker.register('/sw.js').catch(function () {});
                });
            }

            var deferredPrompt = null;
            var banner = document.getElementById('pwa-install-banner');
            var installBtn = document.getElementById('pwa-install-btn');
            var notNowBtn = document.getElementById('pwa-install-notnow');
            var dismissBtn = document.getElementById('pwa-install-dismiss');

            function showBanner() {
                if (!banner || dismissed || installed || isStandalone) return;
                if (!deferredPrompt || visits < 3) return; // 3rd visit + installable
                banner.classList.remove('hidden');
            }

            window.addEventListener('beforeinstallprompt', function (e) {
                e.preventDefault();
                deferredPrompt = e;
                if (navigator.serviceWorker && navigator.serviceWorker.controller) {
                    try {
                        navigator.serviceWorker.controller.postMessage({ type: 'PWA_INSTALL_PROMPT' });
                    } catch (err) {}
                }
                showBanner();
            });

            if (installBtn) installBtn.addEventListener('click', function () {
                if (!deferredPrompt) return;
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then(function (choice) {
                    if (choice.outcome === 'accepted') {
                        try { localStorage.setItem(KEY_INSTALLED, '1'); } catch (e) {}
                        installed = true;
                    }
                    if (banner) banner.classList.add('hidden');
                    deferredPrompt = null;
                });
            });

            function dismissForever() {
                try { localStorage.setItem(KEY_DISMISSED, '1'); } catch (e) {}
                dismissed = true;
                if (banner) banner.classList.add('hidden');
            }
            if (notNowBtn) notNowBtn.addEventListener('click', dismissForever);
            if (dismissBtn) dismissBtn.addEventListener('click', dismissForever);

            window.addEventListener('appinstalled', function () {
                try { localStorage.setItem(KEY_INSTALLED, '1'); } catch (e) {}
                installed = true;
                if (banner) banner.classList.add('hidden');
            });
        })();
    </script>
</body>
</html>
