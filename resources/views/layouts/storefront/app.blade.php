@php
    $cspNonce = $cspNonce ?? \Illuminate\Support\Facades\View::getShared()['cspNonce'] ?? '';
    $activeStoreContext = app(\App\Services\StoreContext::class)->getStore();
    $activeStoreSlug    = request('store_slug') ?? $activeStoreContext?->slug;
    $setting            = $activeStoreContext?->setting ?? $setting ?? null;
    $storeDisplayName   = $setting?->store_name ?? $activeStoreContext?->name ?? config('app.name');
    $sfColors           = $setting?->themeColors() ?? ['primary' => '#0ea5e9', 'accent' => '#7c3aed', 'header_bg' => '#ffffff', 'body_bg' => '#f8fafc', 'glow_style' => 'vivid', 'dark_mode' => 'auto'];

    // Draft-preview override (T3): the authenticated preview route injects the
    // store's draft ThemeConfig here. When present, render those tokens instead
    // of the published ones. storefront_settings is NEVER modified — anonymous
    // requests always resolve the published config because ThemeContext is
    // request-scoped and only the preview route sets it.
    $previewConfig = app(\App\Themes\ThemeContext::class)->activeConfig();
    if ($previewConfig) {
        $sfColors = [
            'primary'    => $previewConfig->themePrimaryColor,
            'accent'     => $previewConfig->themeAccentColor,
            'header_bg'  => $previewConfig->themeHeaderBg,
            'body_bg'    => $previewConfig->themeBodyBg,
            'glow_style' => $previewConfig->themeGlowStyle,
            'dark_mode'  => $previewConfig->themeDarkMode,
        ];
    }

    // Approved layout-component variants for the active theme (T5). In draft
    // preview the draft's preset drives them, so the preview reflects the
    // component composition too — never an arbitrary store-owner value.
    $activeThemePreset = $previewConfig?->themePreset
        ?? $setting?->theme_preset
        ?? \App\Themes\ThemeRegistry::getDefault()->id;
    $navStyle      = \App\Themes\ThemeComponents::resolve($activeThemePreset, 'nav_style');
    $headerVariant = \App\Themes\ThemeComponents::resolve($activeThemePreset, 'header_variant');

    $sfDarkMode         = $sfColors['dark_mode'] ?? 'auto';

    // Check luminance of header background to automatically ensure high-contrast text & icons
    $isDarkHeader = false;
    if (!empty($sfColors['header_bg']) && strlen($sfColors['header_bg']) === 7) {
        $r = hexdec(substr($sfColors['header_bg'], 1, 2));
        $g = hexdec(substr($sfColors['header_bg'], 3, 2));
        $b = hexdec(substr($sfColors['header_bg'], 5, 2));
        $lum = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
        $isDarkHeader = ($lum < 0.55);
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{
          darkMode: document.documentElement.classList.contains('dark'),
          toggleDarkMode() {
              this.darkMode = !this.darkMode;
              localStorage.setItem('darkMode', this.darkMode ? 'true' : 'false');
              if (this.darkMode) {
                  document.documentElement.classList.add('dark');
              } else {
                  document.documentElement.classList.remove('dark');
              }
          },
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
      }"
      x-on:touchstart.window.passive="onSwipeStart($event)"
      x-on:touchend.window.passive="onSwipeEnd($event)"
      :class="{ 'dark': darkMode }">
<head>
    <script nonce="{{ $cspNonce }}">
    (function() {
        var mode = {!! json_encode($sfDarkMode) !!};
        var isDark = false;
        if (mode === 'dark') {
            isDark = true;
        } else if (mode === 'light') {
            isDark = false;
        } else {
            var saved = localStorage.getItem('darkMode');
            if (saved !== null) {
                isDark = (saved === 'true');
            } else {
                isDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            }
        }
        if (isDark) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    })();
    </script>
    <x-currency-js-init :store="$activeStoreContext" />
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
    {{-- Preload fonts so text renders without font-swap CLS. --}}
    <link rel="preload" as="font" type="font/woff2" crossorigin href="{{ Vite::asset('resources/assets/fonts/Roboto-Regular.woff2') }}">
    <link rel="preload" as="font" type="font/ttf" crossorigin href="{{ Vite::asset('resources/assets/fonts/NotoSansMyanmar/NotoSansMyanmar-Regular.ttf') }}">
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
    @php
        $sfGlowStyle = $sfColors['glow_style'] ?? 'vivid';
        $glowLightOp = $sfGlowStyle === 'subtle' ? '0.07' : ($sfGlowStyle === 'none' ? '0' : '0.20');
        $glowDarkOp  = $sfGlowStyle === 'subtle' ? '0.09' : ($sfGlowStyle === 'none' ? '0' : '0.25');
        if ($previewConfig ?? null) {
            $fontFamilyCss = \App\Themes\ThemeRegistry::FONT_PRESETS[$previewConfig->fontPreset]['css']
                ?? \App\Themes\ThemeRegistry::FONT_PRESETS['outfit']['css'];
        } else {
            $fontFamilyCss = ($setting ?? null)?->fontFamilyCss() ?? "'Outfit', 'Pyidaungsu', system-ui, sans-serif";
        }
    @endphp
    <style>
        :root {
            --sf-font-family:    {!! $fontFamilyCss !!};
            --sf-primary:        {{ $sfColors['primary'] }};
            --sf-accent:         {{ $sfColors['accent'] }};
            --sf-primary-hover:  color-mix(in srgb, {{ $sfColors['primary'] }} 85%, #ffffff);
            --sf-primary-bevel:  color-mix(in srgb, {{ $sfColors['primary'] }} 70%, #000000);
            --sf-primary-active: color-mix(in srgb, {{ $sfColors['primary'] }} 85%, #000000);
            --sf-accent-hover:   color-mix(in srgb, {{ $sfColors['accent'] }} 85%, #ffffff);
            --sf-accent-bevel:   color-mix(in srgb, {{ $sfColors['accent'] }} 70%, #000000);
            --sf-accent-active:  color-mix(in srgb, {{ $sfColors['accent'] }} 85%, #000000);
            --sf-header-bg:      {{ $sfColors['header_bg'] }};
            --sf-header-bg-dark: color-mix(in srgb, {{ $sfColors['primary'] }} 15%, #0f172a);
            /* Theme-adapted page body backgrounds */
            --sf-body-bg:        {{ $sfColors['body_bg'] ?? '#f8fafc' }};
            --sf-body-bg-dark:   color-mix(in srgb, {{ $sfColors['primary'] }} 8%, #0b0f19);
            /* Ambient Glow Effect controls */
            --sf-glow-display:   {{ $sfGlowStyle === 'none' ? 'none' : 'block' }};
            --sf-glow-opacity:   {{ $glowLightOp }};
        }

        .dark:root,
        html.dark {
            --sf-glow-opacity:   {{ $glowDarkOp }};
            --sf-primary-bevel:  color-mix(in srgb, {{ $sfColors['primary'] }} 50%, #000000);
            --sf-accent-bevel:   color-mix(in srgb, {{ $sfColors['accent'] }} 50%, #000000);
        }

        /* ── Dynamic Page Body Background & Font ── */
        body {
            font-family: var(--sf-font-family) !important;
            background-color: var(--sf-body-bg) !important;
        }
        .dark body,
        html.dark body {
            background-color: var(--sf-body-bg-dark) !important;
        }

        /* ── Dynamic Header Theme ── */
        header .sf-header-main {
            background-color: var(--sf-header-bg) !important;
            transition: background-color 0.25s ease;
        }
        .dark header .sf-header-main,
        html.dark header .sf-header-main {
            background-color: var(--sf-header-bg-dark) !important;
        }

        @if ($isDarkHeader)
        /* High-contrast styling for main header bar when Admin selects a dark header template (e.g. Midnight or Royal Violet) */
        header .sf-header-main a.sf-brand-link span.font-outfit,
        header .sf-header-main span.font-outfit,
        header .sf-header-main div.text-slate-900,
        header .sf-header-main div.text-slate-700,
        header .sf-header-main div.text-slate-800 {
            color: #ffffff !important;
        }
        header .sf-header-main button.border-slate-200\/80,
        header .sf-header-main a.border-slate-200\/80,
        header .sf-header-main button.border-slate-300,
        header .sf-header-main a.border-slate-300 {
            background-color: rgba(255, 255, 255, 0.12) !important;
            border-color: rgba(255, 255, 255, 0.2) !important;
            color: #ffffff !important;
        }
        header .sf-header-main button svg,
        header .sf-header-main a svg:not(.text-rose-500) {
            color: #ffffff !important;
        }
        @endif

        /* ── Storefront Primary Desktop Navigation Bar ── */
        header .sf-desktop-nav-row {
            background-color: var(--sf-header-bg) !important;
            border-top: 1px solid rgba(0, 0, 0, 0.06) !important;
        }
        html.dark header .sf-desktop-nav-row {
            background-color: var(--sf-header-bg-dark) !important;
            border-top: 1px solid rgba(255, 255, 255, 0.08) !important;
        }

        header nav.sf-primary-nav {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
        }
        html.dark header nav.sf-primary-nav {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
        }

        /* ── Dropdown / Flyout Panels Protection (Always keep proper readable colors) ── */
        [role="menu"],
        [role="menu"] *,
        .sf-dropdown-menu,
        .sf-dropdown-menu * {
            text-shadow: none;
        }
        [role="menu"] .text-slate-800,
        [role="menu"] .text-slate-900 {
            color: #1e293b !important;
        }
        .dark [role="menu"] .text-slate-800,
        .dark [role="menu"] .text-slate-900,
        html.dark [role="menu"] .text-slate-800,
        html.dark [role="menu"] .text-slate-900 {
            color: #f8fafc !important;
        }
        [role="menu"] .text-slate-500,
        [role="menu"] .text-slate-600 {
            color: #64748b !important;
        }
        .dark [role="menu"] .text-slate-500,
        .dark [role="menu"] .text-slate-600,
        html.dark [role="menu"] .text-slate-500,
        html.dark [role="menu"] .text-slate-600 {
            color: #94a3b8 !important;
        }

        /* ── Primary Brand & Highlights (Buttons, Links, Active Pills, Badges) ── */
        .text-sky-600,
        .text-sky-500,
        .hover\:text-sky-600:hover,
        .dark .text-sky-400,
        .dark .text-sky-300 {
            color: var(--sf-primary) !important;
        }

        .border-sky-500,
        .border-sky-600,
        .focus\:ring-sky-500:focus,
        .focus-within\:border-sky-500:focus-within {
            border-color: var(--sf-primary) !important;
        }

        .bg-sky-500,
        .bg-sky-600 {
            background-color: var(--sf-primary) !important;
        }

        .bg-sky-50,
        .bg-sky-100 {
            background-color: color-mix(in srgb, var(--sf-primary) 12%, transparent) !important;
        }

        /* ── Accent Highlights (Search button, CTAs, Buy now, Badges) ── */
        .bg-gradient-to-r.from-violet-600.to-fuchsia-500,
        .bg-gradient-to-r.from-violet-600.to-sky-600,
        button[type="submit"].bg-gradient-to-r,
        .sf-btn-accent {
            background: var(--sf-accent) !important;
            color: #ffffff !important;
        }

        /* Standard custom classes */
        .sf-btn-primary    { background-color: var(--sf-primary) !important; color: #ffffff !important; }
        .sf-text-primary   { color: var(--sf-primary) !important; }
        .sf-border-primary { border-color: var(--sf-primary) !important; }
        .sf-bg-primary-tint { background-color: color-mix(in srgb, var(--sf-primary) 15%, transparent) !important; }

        /* ── Mobile Bottom Navigation 3D Tactile Buttons ── */
        .sf-nav-btn {
            position: relative;
            display: inline-flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
            transform: translateZ(0);
            transition: transform 120ms ease, border-bottom-width 120ms ease, box-shadow 120ms ease, background 120ms ease, color 120ms ease;
            border-radius: 8px;
            border: 1px solid rgba(203, 213, 225, 0.9);
            border-bottom: 3px solid #cbd5e1;
            background: linear-gradient(180deg, #ffffff 0%, #f1f5f9 100%);
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06), inset 0 1px 0 rgba(255, 255, 255, 0.95);
            color: #334155;
            text-decoration: none !important;
            min-height: 50px;
            padding: 4px 2px 2px 2px;
        }
        .sf-nav-btn:hover {
            background: linear-gradient(180deg, #ffffff 0%, #e2e8f0 100%);
            color: #0f172a;
        }
        .sf-nav-btn:active {
            transform: translateY(1.5px);
            border-bottom-width: 1.5px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05), inset 0 1px 2px rgba(0, 0, 0, 0.06);
        }
        .dark .sf-nav-btn,
        html.dark .sf-nav-btn {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            border: 1px solid rgba(51, 65, 85, 0.9);
            border-bottom: 3px solid #020617;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.08);
            color: #94a3b8;
        }
        .dark .sf-nav-btn:hover,
        html.dark .sf-nav-btn:hover {
            background: linear-gradient(180deg, #334155 0%, #1e293b 100%);
            color: #ffffff;
        }
        .dark .sf-nav-btn:active,
        html.dark .sf-nav-btn:active {
            transform: translateY(1.5px);
            border-bottom-width: 1.5px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.3), inset 0 1px 2px rgba(0, 0, 0, 0.4);
        }

        /* 1. Home Active (Sky Blue 3D) */
        .sf-nav-btn-home.active {
            background: linear-gradient(180deg, #0284c7 0%, #0369a1 100%) !important;
            color: #ffffff !important;
            border: 1px solid #38bdf8 !important;
            border-bottom: 3px solid #075985 !important;
            box-shadow: 0 2px 8px -1px rgba(2, 132, 199, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.4) !important;
        }

        /* 2. Products Active (Indigo Blue 3D) */
        .sf-nav-btn-products.active {
            background: linear-gradient(180deg, #4f46e5 0%, #4338ca 100%) !important;
            color: #ffffff !important;
            border: 1px solid #818cf8 !important;
            border-bottom: 3px solid #312e81 !important;
            box-shadow: 0 2px 8px -1px rgba(79, 70, 229, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.4) !important;
        }

        /* 3. Categories Active (Amber Orange 3D) */
        .sf-nav-btn-categories.active {
            background: linear-gradient(180deg, #d97706 0%, #b45309 100%) !important;
            color: #ffffff !important;
            border: 1px solid #fcd34d !important;
            border-bottom: 3px solid #78350f !important;
            box-shadow: 0 2px 8px -1px rgba(217, 119, 6, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.4) !important;
        }

        /* 4. Repair / Service Tracking Active (Purple / Violet 3D) */
        .sf-nav-btn-repair.active {
            background: linear-gradient(180deg, #7e22ce 0%, #6b21a8 100%) !important;
            color: #ffffff !important;
            border: 1px solid #c084fc !important;
            border-bottom: 3px solid #581c87 !important;
            box-shadow: 0 2px 8px -1px rgba(126, 34, 206, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.4) !important;
        }

        /* 5. Cart / Orders Active (Rose Crimson 3D) */
        .sf-nav-btn-cart.active {
            background: linear-gradient(180deg, #e11d48 0%, #be123c 100%) !important;
            color: #ffffff !important;
            border: 1px solid #fb7185 !important;
            border-bottom: 3px solid #881337 !important;
            box-shadow: 0 2px 8px -1px rgba(225, 29, 72, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.4) !important;
        }

        /* 6. Account / Login Active (Emerald Green 3D) */
        .sf-nav-btn-account.active,
        .sf-nav-btn-login.active {
            background: linear-gradient(180deg, #059669 0%, #047857 100%) !important;
            color: #ffffff !important;
            border: 1px solid #34d399 !important;
            border-bottom: 3px solid #064e3b !important;
            box-shadow: 0 2px 8px -1px rgba(5, 150, 105, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.4) !important;
        }

        /* 7. Glass Finder Active (Cyan 3D) */
        .sf-nav-btn-glass.active {
            background: linear-gradient(180deg, #0891b2 0%, #0e7490 100%) !important;
            color: #ffffff !important;
            border: 1px solid #67e8f9 !important;
            border-bottom: 3px solid #155e75 !important;
            box-shadow: 0 2px 8px -1px rgba(8, 145, 178, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.4) !important;
        }
    </style>
</head>

@php
    $homeUrl            = $activeStoreSlug ? url('/?store_slug=' . $activeStoreSlug) : url('/');
    $productsUrl        = $activeStoreSlug ? url('/products?store_slug=' . $activeStoreSlug) : url('/products');
    $glassFinderUrl     = $activeStoreSlug ? url('/glass-finder?store_slug=' . $activeStoreSlug) : url('/glass-finder');
    $browseUrl          = $activeStoreSlug ? url('/browse?store_slug=' . $activeStoreSlug) : url('/browse');
    $orderBuilderUrl    = $activeStoreSlug ? url('/order-builder?store_slug=' . $activeStoreSlug) : url('/order-builder');
    $howToOrderUrl      = $activeStoreSlug ? url('/how-to-order?store_slug=' . $activeStoreSlug) : url('/how-to-order');
    $blogUrl            = $activeStoreSlug ? url('/blog?store_slug=' . $activeStoreSlug) : url('/blog');
    $accountUrl         = $activeStoreSlug ? url('/account?store_slug=' . $activeStoreSlug) : url('/account');
    $favoritesUrl       = $activeStoreSlug ? url('/account/favorites?store_slug=' . $activeStoreSlug) : url('/account/favorites');
    $serviceTrackingUrl = $activeStoreSlug ? url('/service-tracking?store_slug=' . $activeStoreSlug) : url('/service-tracking');

    $isHome            = request()->is('/') || request()->fullUrl() === $homeUrl;
    $isProducts        = request()->is('products*') || request()->is('store/*/product/*');

    $isGlassFinder = request()->is('glass-finder*');
    $isServiceTracking = request()->is('service-tracking*') || request()->is('store/*/track/service*');
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

    $navResolver = app(\App\Services\StorefrontNavigationResolver::class);
    $desktopNavItems = $activeStoreContext ? $navResolver->resolveForPlacement($activeStoreContext, 'desktop') : collect();
    $mobileDrawerNavItems = $activeStoreContext ? $navResolver->resolveForPlacement($activeStoreContext, 'mobile_drawer') : collect();
    $mobileBottomNavItems = $activeStoreContext ? $navResolver->resolveForPlacement($activeStoreContext, 'mobile_bottom') : collect();
@endphp
<body class="bg-slate-100 dark:bg-slate-950 text-slate-900 dark:text-slate-100 font-sans antialiased min-h-screen relative selection:bg-sky-500 selection:text-white pb-[calc(env(safe-area-inset-bottom,0px)+6rem)] md:pb-8">

    {{-- Background Liquid Glow Circles — dynamically tinted with active theme colors & controlled by glow_style --}}
    <div class="fixed inset-0 pointer-events-none overflow-hidden z-0" style="display: var(--sf-glow-display, block);">
        <div class="absolute -top-40 -left-40 w-96 h-96 rounded-full blur-3xl transition-opacity duration-300" style="background-color: var(--sf-primary); opacity: var(--sf-glow-opacity, 0.20);"></div>
        <div class="absolute top-1/3 -right-40 w-96 h-96 rounded-full blur-3xl transition-opacity duration-300" style="background-color: var(--sf-accent); opacity: var(--sf-glow-opacity, 0.20);"></div>
        <div class="absolute -bottom-40 left-1/3 w-96 h-96 rounded-full blur-3xl transition-opacity duration-300" style="background-color: var(--sf-primary); opacity: var(--sf-glow-opacity, 0.15);"></div>
        <div class="absolute top-10 left-1/2 -translate-x-1/2 w-72 h-72 rounded-full blur-3xl transition-opacity duration-300" style="background-color: var(--sf-accent); opacity: var(--sf-glow-opacity, 0.15);"></div>
        <div class="absolute bottom-1/4 -left-24 w-72 h-72 rounded-full blur-3xl transition-opacity duration-300" style="background-color: var(--sf-primary); opacity: var(--sf-glow-opacity, 0.15);"></div>
        <div class="absolute top-1/2 right-10 w-64 h-64 rounded-full blur-3xl transition-opacity duration-300" style="background-color: var(--sf-accent); opacity: var(--sf-glow-opacity, 0.15);"></div>
    </div>

    {{-- Top utility bar (tablet/desktop only — scrolls away; contact info + account) --}}
    <div class="hidden md:block border-b border-slate-200/60 dark:border-slate-800/60 bg-white/95 dark:bg-slate-900/95 backdrop-blur">
        <div class="w-full px-3 sm:px-5 lg:px-8 h-9 flex items-center justify-between gap-4 text-xs font-semibold text-slate-500 dark:text-slate-600">
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
                    @if (store_can('storefront.customer_portal', $activeStoreContext))
                    <a href="{{ $accountUrl }}" class="flex items-center gap-1.5 whitespace-nowrap hover:text-sky-600 dark:hover:text-sky-400 transition">
                        <span aria-hidden="true">👤</span>
                        <span class="max-w-[10rem] truncate">{{ auth()->user()->name }}</span>
                    </a>
                    @endif
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
        <div class="bg-white/95 dark:bg-slate-900/95 backdrop-blur sf-header-main">
        <div class="w-full px-1 sm:px-5 lg:px-8 h-16 sm:h-[4.5rem] flex items-center gap-1.5 sm:gap-3 relative">
            {{-- Shop logo (mobile: centered via flex-1; desktop: snapped to far-left) --}}
            <a href="{{ $homeUrl }}" class="sf-brand-link flex min-w-0 flex-1 lg:flex-none items-center justify-center lg:justify-start lg:pr-4 group transition-transform duration-300 hover:scale-[1.02] active:scale-[0.98]">
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
                            <span class="flex items-center min-w-0">
                                <span class="max-w-[5rem] sm:max-w-[8rem] truncate font-outfit text-sm sm:text-base font-black leading-tight tracking-tight text-slate-900 transition-colors group-hover:text-sky-600 dark:text-white dark:group-hover:text-sky-400 lg:max-w-[13rem] lg:text-lg">{{ $storeDisplayName }}</span>
                                @include('storefront.components.header-accent-' . $headerVariant)
                            </span>
                            <span class="max-w-[5rem] sm:max-w-[8rem] truncate text-xs font-extrabold leading-none text-sky-600 dark:text-sky-400 lg:max-w-[13rem]">{{ $setting?->tagline ?: __('messages.default_tagline') }}</span>
                        </div>
                    </div>
                @endif
            </a>

            {{-- Inline search bar (Shopee/Lazada style) — desktop only. Mobile uses
                 the search icon → slide-down panel (and the hamburger menu). --}}
            <div class="hidden lg:block min-w-0 flex-1 px-4">
                <form action="{{ url('/products') }}" method="GET"
                      x-data="searchSuggestions('{{ $activeStoreSlug }}', '{{ url('/products/suggestions') }}', { categories: '{{ __('messages.categories') }}', brands: '{{ __('messages.brands') }}', products: '{{ __('messages.products') }}', trending: '{{ __('messages.trending_searches') }}' })"
                      @click.outside="open = false"
                      class="relative flex items-center gap-1.5 rounded-xl border-2 border-sky-500 bg-white py-1 pl-3 pr-1.5 shadow-sm transition focus-within:ring-2 focus-within:ring-sky-500/30 dark:border-sky-600 dark:bg-slate-800">
                    <input type="hidden" name="store_slug" value="{{ $activeStoreSlug }}">
                    <svg class="h-5 w-5 shrink-0 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" id="header-search-input" x-model="query" @input="onInput()" @focus="onFocus()" @keydown="onKeydown($event)" autocomplete="off" placeholder="{{ __('messages.search_products') }}" class="w-full min-w-0 bg-transparent px-1 text-sm outline-none text-slate-700 placeholder:text-slate-400 dark:text-slate-200 dark:placeholder:text-slate-500" role="combobox" aria-expanded="open ? 'true' : 'false'" aria-autocomplete="list" aria-controls="search-suggestions-panel" :aria-activedescendant="activeId() || undefined">
                    <button type="submit" class="sf-btn-3d active !flex-row shrink-0 px-5 py-1.5 text-xs font-black leading-none" aria-label="{{ __('messages.search') }}">
                        {{ __('messages.search') }}
                    </button>

                    @include('storefront.components.search-suggestions-dropdown')
                </form>
            </div>

            {{-- Right Header Actions --}}
            <div class="flex shrink-0 items-center gap-1 sm:gap-1.5 lg:gap-2">
                {{-- Favorites icon with live count badge (visible on all viewports) --}}
                <a href="{{ $favoritesUrl }}" class="group relative inline-flex h-10 w-10 sm:h-11 sm:w-11 items-center justify-center rounded-xl p-0 transition-all duration-150 transform hover:-translate-y-0.5 active:translate-y-0.5 select-none bg-gradient-to-b from-rose-50 to-rose-100/90 dark:from-rose-950/60 dark:to-rose-900/40 border border-rose-200 dark:border-rose-800/80 border-b-[3px] border-b-rose-300 dark:border-b-rose-700 text-rose-500 dark:text-rose-400 shadow-sm shadow-rose-500/10 hover:from-rose-100 hover:to-rose-200/90" title="{{ __('messages.favorites') }}" aria-label="{{ __('messages.favorites') }}">
                    <svg class="h-5 w-5 text-rose-500 dark:text-rose-400 transition-transform group-hover:scale-110 sm:h-5 sm:w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.684a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                    <span x-show="$store.favoritesStore && $store.favoritesStore.count > 0" x-transition class="absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] sm:min-w-[20px] sm:h-[20px] px-1 rounded-full bg-rose-500 text-white font-black text-[11px] flex items-center justify-center shadow-md shadow-rose-500/30 ring-2 ring-white dark:ring-slate-900" x-text="$store.favoritesStore ? $store.favoritesStore.count : 0"></span>
                </a>

                {{-- Language switcher (icon-only flag + dropdown — visible on all viewports) --}}
                <x-language-switcher id="storefront-header" btn-class="h-10 w-10 sm:h-11 sm:w-11 inline-flex items-center justify-center rounded-xl transition-all duration-150 transform hover:-translate-y-0.5 active:translate-y-0.5 select-none bg-gradient-to-b from-sky-50 to-blue-100/90 dark:from-sky-950/60 dark:to-blue-900/40 border border-sky-200 dark:border-sky-800/80 border-b-[3px] border-b-sky-300 dark:border-b-sky-700 shadow-sm shadow-sky-500/10 hover:from-sky-100 hover:to-blue-200/90 text-base focus:outline-none focus:ring-2 focus:ring-sky-500" />

                {{-- Compact Cart Icon with Badge (hidden on mobile — cart is in the bottom nav) --}}
                @if (store_can('storefront.online_ordering', $activeStoreContext))
                <a href="{{ $orderBuilderUrl }}" class="group relative hidden md:flex h-11 w-11 items-center justify-center rounded-xl p-0 transition-all duration-150 transform hover:-translate-y-0.5 active:translate-y-0.5 select-none bg-gradient-to-b from-emerald-50 to-teal-100/90 dark:from-emerald-950/60 dark:to-teal-900/40 border border-emerald-200 dark:border-emerald-800/80 border-b-[3px] border-b-emerald-300 dark:border-b-emerald-700 text-emerald-600 dark:text-emerald-400 shadow-sm shadow-emerald-500/10 hover:from-emerald-100 hover:to-teal-200/90" title="{{ __('messages.order_builder') }}">
                    <svg class="h-4 w-4 sm:h-5 sm:w-5 text-emerald-600 dark:text-emerald-400 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span x-show="$store.orderBuilder && $store.orderBuilder.totalCount > 0" class="absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] sm:min-w-[20px] sm:h-[20px] px-1 rounded-full bg-emerald-600 text-white font-black text-xs flex items-center justify-center shadow-md shadow-emerald-500/30 ring-2 ring-white dark:ring-slate-900" x-text="$store.orderBuilder ? $store.orderBuilder.totalCount : 0"></span>
                </a>
                @endif

                {{-- Dark Mode Toggle (compact icon — visible on all viewports) --}}
                <button @click="toggleDarkMode()" class="group inline-flex h-10 w-10 sm:h-11 sm:w-11 items-center justify-center rounded-xl p-0 transition-all duration-150 transform hover:-translate-y-0.5 active:translate-y-0.5 select-none bg-gradient-to-b from-amber-50 to-amber-100/90 dark:from-amber-950/60 dark:to-amber-900/40 border border-amber-200 dark:border-amber-800/80 border-b-[3px] border-b-amber-300 dark:border-b-amber-700 text-amber-600 dark:text-amber-400 shadow-sm shadow-amber-500/10 hover:from-amber-100 hover:to-amber-200/90" title="{{ __('messages.theme_toggle') }}">
                    <svg x-show="!darkMode" class="h-4 w-4 sm:h-5 sm:w-5 text-amber-600 dark:text-amber-400 transition-transform group-hover:rotate-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.8A8.5 8.5 0 1111.2 3a6.5 6.5 0 009.8 9.8z" />
                    </svg>
                    <svg x-show="darkMode" class="h-4 w-4 sm:h-5 sm:w-5 text-amber-400 transition-transform group-hover:rotate-45" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
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
                    class="group lg:hidden inline-flex h-10 w-10 sm:h-11 sm:w-11 items-center justify-center rounded-xl p-0 transition-all duration-150 transform hover:-translate-y-0.5 active:translate-y-0.5 select-none bg-gradient-to-b from-indigo-50 to-violet-100/90 dark:from-indigo-950/60 dark:to-violet-900/40 border border-indigo-200 dark:border-indigo-800/80 border-b-[3px] border-b-indigo-300 dark:border-b-indigo-700 text-indigo-600 dark:text-indigo-400 shadow-sm shadow-indigo-500/10 hover:from-indigo-100 hover:to-violet-200/90"
                    title="{{ __('messages.open_menu') }}"
                    aria-label="{{ __('messages.open_menu') }}"
                >
                    <svg x-show="!mobileMenuOpen" class="h-4 w-4 sm:h-5 sm:w-5 text-indigo-600 dark:text-indigo-400 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg x-show="mobileMenuOpen" class="h-4 w-4 sm:h-5 sm:w-5 text-indigo-600 dark:text-indigo-400 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Mobile search row: search bar + Always-Visible Categories Button (Shopee/AliExpress style) --}}
        <div class="lg:hidden border-t border-slate-200/60 dark:border-slate-800/60 bg-white/95 dark:bg-slate-900/95 px-2 sm:px-4 py-2"
             x-data="searchSuggestions('{{ $activeStoreSlug }}', '{{ url('/products/suggestions') }}', { categories: '{{ __('messages.categories') }}', brands: '{{ __('messages.brands') }}', products: '{{ __('messages.products') }}', trending: '{{ __('messages.trending_searches') }}' })"
             @click.outside="open = false">
            <div class="flex items-center gap-1.5 sm:gap-2">
                <form action="{{ url('/products') }}" method="GET" class="relative flex min-w-0 flex-1 items-center gap-1.5 rounded-xl border border-slate-200/90 bg-white px-2.5 h-10 text-xs shadow-2xs transition focus-within:border-sky-500 focus-within:ring-2 focus-within:ring-sky-500/20 dark:border-slate-700/80 dark:bg-slate-800 sm:text-sm">
                    <input type="hidden" name="store_slug" value="{{ $activeStoreSlug }}">
                    <svg class="h-4 w-4 shrink-0 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" x-model="query" @input="onInput()" @focus="onFocus()" @keydown="onKeydown($event)" x-ref="searchInput" autocomplete="off" placeholder="{{ __('messages.search_products') }}" class="w-full bg-transparent outline-none text-slate-700 placeholder:text-slate-400 dark:text-slate-200 dark:placeholder:text-slate-500 text-xs sm:text-sm" role="combobox" aria-expanded="open ? 'true' : 'false'" aria-autocomplete="list" aria-controls="mobile-search-suggestions" :aria-activedescendant="activeId() || undefined">
                    <button type="submit" class="sf-btn-3d active !flex-row shrink-0 h-7.5 px-3 text-xs font-black leading-none" aria-label="{{ __('messages.search') }}">
                        <span class="hidden sm:inline">{{ __('messages.search') }}</span>
                        <svg class="h-3.5 w-3.5 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
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
                        class="sf-btn-3d !flex-row shrink-0 h-10 px-2.5 sm:px-3 text-xs gap-1.5 {{ $isBrowse ? 'active font-black' : 'font-black' }}"
                        title="{{ __('messages.categories') }}"
                        aria-label="{{ __('messages.categories') }}"
                    >
                        <svg class="w-4 h-4 shrink-0 {{ $isBrowse ? 'text-white' : 'text-slate-600 dark:text-slate-300' }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <rect x="3" y="3.6" width="4" height="5.4" rx="2.7"/>
                            <rect x="9" y="3.6" width="12" height="5.4" rx="2.7"/>
                            <rect x="3" y="9.3" width="4" height="5.4" rx="2.7"/>
                            <rect x="9" y="9.3" width="12" height="5.4" rx="2.7"/>
                            <rect x="3" y="15" width="4" height="5.4" rx="2.7"/>
                            <rect x="9" y="15" width="12" height="5.4" rx="2.7"/>
                        </svg>
                        <span class="text-xs font-black whitespace-nowrap">{{ __('messages.categories') }}</span>
                    </a>
                @endif
            </div>
        </div>

        {{-- Row 2 (desktop only): Navigation — variant from the active theme's
             approved composition (T5): 'pill' | 'underline' --}}
        <div class="hidden lg:block border-t border-slate-200/60 dark:border-slate-800/60 bg-white dark:bg-slate-900 sf-desktop-nav-row">
            <div class="w-full px-3 sm:px-5 lg:px-8 py-2 flex flex-wrap items-center justify-center gap-x-5 gap-y-2">
                @include('storefront.components.nav-' . $navStyle)
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
            <div class="w-full px-4 py-3 sm:px-6 lg:px-8">
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

                    @include('storefront.components.search-suggestions-dropdown')
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
            class="lg:hidden fixed inset-y-0 left-0 z-[60] w-[86vw] max-w-sm flex flex-col bg-slate-50 dark:bg-slate-900 border-r-2 border-slate-300 dark:border-slate-800 shadow-2xl"
            role="dialog"
            aria-modal="true"
            aria-label="{{ __('messages.menu') }}"
        >
            {{-- Drawer header --}}
            <div class="flex h-16 shrink-0 items-center justify-between gap-3 border-b-2 border-slate-200 dark:border-slate-800 px-4 bg-white dark:bg-slate-900">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <div class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-b from-sky-500 to-sky-600 text-sm font-black text-white shadow-sm border border-sky-400/60 border-b-2 border-b-sky-800">
                        {{ mb_strtoupper(mb_substr($storeDisplayName, 0, 2)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-outfit text-sm font-black text-slate-900 dark:text-white leading-tight">{{ $storeDisplayName }}</p>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 shadow-xs animate-pulse"></span>
                            <span class="truncate text-[11px] font-bold text-slate-500 dark:text-slate-400 leading-none">{{ __('messages.menu') }}</span>
                        </div>
                    </div>
                </div>
                <button
                    type="button"
                    x-ref="drawerClose"
                    @click="mobileMenuOpen = false"
                    class="sf-btn-3d h-9 w-9 p-0 hover:text-rose-600 hover:border-rose-300 dark:hover:border-rose-800"
                    aria-label="{{ __('messages.close_menu') }}"
                    title="{{ __('messages.close_menu') }}"
                >
                    <svg class="h-4 w-4 transition-transform duration-200 group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Drawer body with nav links --}}
            <div class="flex-1 overflow-y-auto overscroll-contain px-3.5 py-4 space-y-3">
                {{-- Account / Auth Card --}}
                @auth
                    @if (store_can('storefront.customer_portal', $activeStoreContext))
                        <div class="rounded-xl border border-slate-200 dark:border-slate-800 border-b-[3px] border-b-slate-300 dark:border-b-slate-950 bg-white dark:bg-slate-800 p-3 shadow-xs">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-b from-sky-500 to-sky-600 text-white font-black text-xs border border-sky-400/50 border-b-2 border-b-sky-800 shadow-xs">
                                    {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 2)) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs font-black text-slate-900 dark:text-white">{{ auth()->user()->name }}</p>
                                    <p class="truncate text-[11px] font-bold text-sky-600 dark:text-sky-400">{{ __('messages.nav_account') }}</p>
                                </div>
                                <a href="{{ $accountUrl }}" @click="mobileMenuOpen = false" class="sf-btn-3d active !flex-row gap-1 h-8 px-3 text-xs font-black" aria-label="{{ __('messages.nav_account') }}">
                                    <span>{{ __('messages.view') ?? 'View' }}</span>
                                    <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 border-b-[3px] border-b-slate-300 dark:border-b-slate-950 bg-white dark:bg-slate-800 p-3.5 shadow-xs">
                        <div class="flex flex-col gap-2.5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-sky-100 dark:bg-sky-950 text-sky-700 dark:text-sky-300 text-xs font-black">🔑</span>
                                    <span class="text-xs font-black tracking-wide text-slate-800 dark:text-slate-100">{{ __('messages.nav_account') }}</span>
                                </div>
                                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ __('messages.welcome') ?? 'Welcome' }}</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <a href="{{ route('login') }}" @click="mobileMenuOpen = false" class="sf-btn-3d active h-9 text-xs font-black">
                                    <span>{{ __('messages.login') }}</span>
                                </a>
                                <a href="{{ route('register') }}" @click="mobileMenuOpen = false" class="sf-btn-3d h-9 text-xs font-black">
                                    <span>{{ __('messages.register') }}</span>
                                </a>
                            </div>
                        </div>
                    </div>
                @endauth

                {{-- Section Label --}}
                <div class="px-1 flex items-center justify-between text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    <span>{{ __('messages.menu') }}</span>
                    <span class="h-px flex-1 bg-slate-200 dark:bg-slate-800 ml-2"></span>
                </div>

                {{-- Mobile Nav Links (3D Embossed Push Button List) --}}
                <nav aria-label="{{ __('messages.menu') }}" data-mobile-nav class="grid grid-cols-1 gap-2">
                    @forelse ($mobileDrawerNavItems as $navItem)
                        <a
                            href="{{ $navItem->url }}"
                            @if ($navItem->is_external) target="{{ $navItem->target }}" rel="{{ $navItem->rel }}" @endif
                            @click="mobileMenuOpen = false"
                            class="sf-btn-3d !flex-row !justify-between p-2.5 {{ $navItem->is_active ? 'active' : '' }}"
                        >
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $navItem->is_active ? 'text-white' : 'text-slate-600 dark:text-slate-300' }}">
                                    <x-storefront.navigation-icon :name="$navItem->icon_key" class="h-5 w-5" />
                                </span>
                                <span class="text-xs font-black truncate">{{ $navItem->label }}</span>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0">
                                @if ($navItem->destination_key === 'cart')
                                    <span x-show="$store.orderBuilder && $store.orderBuilder.totalCount > 0"
                                          class="min-w-[20px] h-[20px] px-1.5 rounded-full bg-rose-500 text-white font-black text-[11px] flex items-center justify-center border border-rose-300/60 border-b-2 border-b-rose-800 shadow-xs"
                                          x-text="$store.orderBuilder.totalCount"></span>
                                @endif
                                <svg class="h-4 w-4 opacity-70 transition-transform duration-100 group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </a>
                    @empty
                        <a href="{{ $homeUrl }}" @click="mobileMenuOpen = false" class="sf-btn-3d !flex-row !justify-between p-2.5 {{ $isHome ? 'active' : '' }}">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $isHome ? 'text-white' : 'text-slate-600 dark:text-slate-300' }}">
                                    <x-storefront.navigation-icon name="home" class="h-5 w-5" />
                                </span>
                                <span class="text-xs font-black truncate">{{ __('messages.nav_home') }}</span>
                            </div>
                            <svg class="h-4 w-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                        <a href="{{ $productsUrl }}" @click="mobileMenuOpen = false" class="sf-btn-3d !flex-row !justify-between p-2.5 {{ $isProducts ? 'active' : '' }}">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $isProducts ? 'text-white' : 'text-slate-600 dark:text-slate-300' }}">
                                    <x-storefront.navigation-icon name="products" class="h-5 w-5" />
                                </span>
                                <span class="text-xs font-black truncate">{{ __('messages.nav_products') }}</span>
                            </div>
                            <svg class="h-4 w-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    @endforelse
                </nav>
            </div>

            {{-- Drawer footer / Quick store info & utilities (3D Push Buttons) --}}
            <div class="shrink-0 border-t-2 border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 space-y-2.5">
                {{-- Tri-lingual 3D Push Switcher inside Drawer --}}
                <form method="POST" action="{{ route('locale.update') }}" class="flex items-center justify-between gap-1.5 text-[11px] font-black">
                    @csrf
                    @php $currLoc = app()->getLocale(); @endphp
                    <button type="submit" name="locale" value="my" class="sf-btn-3d flex-1 py-1.5 {{ $currLoc === 'my' ? 'active font-black' : '' }}">
                        🇲🇲 မြန်မာ
                    </button>
                    <button type="submit" name="locale" value="en" class="sf-btn-3d flex-1 py-1.5 {{ $currLoc === 'en' ? 'active font-black' : '' }}">
                        🇬🇧 English
                    </button>
                    <button type="submit" name="locale" value="zh_CN" class="sf-btn-3d flex-1 py-1.5 {{ $currLoc === 'zh_CN' ? 'active font-black' : '' }}">
                        🇨🇳 中文
                    </button>
                </form>

                {{-- PWA Install Quick Action inside Drawer --}}
                <button type="button" @click="mobileMenuOpen = false; window.__promptPwaInstall && window.__promptPwaInstall()" class="sf-btn-3d !flex-row w-full justify-center gap-2 py-2 px-3 text-xs font-black bg-gradient-to-b from-rose-50 to-red-50/80 dark:from-rose-950/40 dark:to-red-950/20 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-900 border-b-[3px] border-b-rose-400 dark:border-b-rose-950 shadow-xs active:translate-y-0.5 active:border-b cursor-pointer">
                    <span class="text-sm">📲</span>
                    <span>{{ __('messages.pwa_install_btn') }} (Full Screen)</span>
                </button>

                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2 min-w-0">
                        @if ($setting?->phone)
                            <a href="tel:{{ $setting->phone }}" class="sf-btn-3d !flex-row gap-1.5 px-2.5 py-1.5 text-xs font-black">
                                <span class="text-emerald-500">📞</span>
                                <span class="truncate max-w-[120px]">{{ $setting->phone }}</span>
                            </a>
                        @endif
                    </div>
                    <div class="flex items-center gap-1.5 ml-auto">
                        <button
                            type="button"
                            @click="toggleDarkMode()"
                            class="sf-btn-3d h-8 w-8 p-0"
                            title="{{ __('messages.theme_toggle') }}"
                        >
                            <svg x-show="!darkMode" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.8A8.5 8.5 0 1111.2 3a6.5 6.5 0 009.8 9.8z" />
                            </svg>
                            <svg x-show="darkMode" class="h-4 w-4 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v2m0 14v2m9-9h-2M5 12H3m15.36-6.36-1.42 1.42M7.06 16.94l-1.42 1.42m12.72 0-1.42-1.42M7.06 7.06 5.64 5.64" />
                                <circle cx="12" cy="12" r="4" stroke-width="2" />
                            </svg>
                        </button>
                    </div>
                </div>
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

    {{-- Main Content Container (mobile: supports custom main_padding or standard gutters) --}}
    @php
        $defaultPadding = 'px-2 sm:px-5 lg:px-8 ' . ($__env->hasSection('noMainPadding') ? 'pt-0 pb-6' : 'py-6');
        $mainPadClass = $__env->yieldContent('main_padding', $defaultPadding);
    @endphp
    <main class="w-full {{ $mainPadClass }} relative z-10">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <x-storefront-footer
        :setting="$setting"
        :store="$activeStoreContext"
        :store-display-name="$storeDisplayName"
        :store-slug="$activeStoreSlug"
    />
    @if (!($hideFloatingFabs ?? false) && store_can('storefront.online_ordering', $activeStoreContext))
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
                <span class="text-xs font-extrabold text-sky-700 dark:text-sky-400 font-mono" x-text="typeof window.formatCurrency === 'function' ? window.formatCurrency($store.orderBuilder ? $store.orderBuilder.totalAmount : 0) : ($store.orderBuilder ? $store.orderBuilder.totalAmount.toLocaleString() : 0)"></span>
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
        class="md:hidden fixed bottom-0 inset-x-0 z-40 flex items-stretch text-xs transition-all duration-300 ease-out will-change-transform bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t-2 border-slate-200 dark:border-slate-800 shadow-[0_-4px_16px_rgba(15,23,42,0.06)] dark:shadow-[0_-4px_20px_rgba(0,0,0,0.5)] px-1.5 py-1.5 gap-1.5 pb-[calc(env(safe-area-inset-bottom,0px)+0.35rem)]"
    >
        @forelse ($mobileBottomNavItems as $navItem)
            @php
                $iconKey = $navItem->icon_key ?? $navItem->destination_key ?? 'home';
                $btnColorClass = match($iconKey) {
                    'home' => 'sf-nav-btn-home',
                    'products' => 'sf-nav-btn-products',
                    'categories' => 'sf-nav-btn-categories',
                    'glass' => 'sf-nav-btn-glass',
                    'repair' => 'sf-nav-btn-repair',
                    'cart' => 'sf-nav-btn-cart',
                    'account' => 'sf-nav-btn-account',
                    'login', 'register' => 'sf-nav-btn-login',
                    default => 'sf-nav-btn-home',
                };
                $inactiveColor = match($iconKey) {
                    'home' => 'text-sky-500 dark:text-sky-400',
                    'products' => 'text-indigo-500 dark:text-indigo-400',
                    'categories' => 'text-amber-500 dark:text-amber-400',
                    'glass' => 'text-cyan-500 dark:text-cyan-400',
                    'repair' => 'text-purple-600 dark:text-purple-400',
                    'cart' => 'text-rose-500 dark:text-rose-400',
                    'account' => 'text-emerald-500 dark:text-emerald-400',
                    'login', 'register' => 'text-teal-500 dark:text-teal-400',
                    default => 'text-sky-500 dark:text-sky-400',
                };
            @endphp
            <a href="{{ $navItem->url }}"
               @if ($navItem->is_external) target="{{ $navItem->target }}" rel="{{ $navItem->rel }}" @endif
               class="sf-nav-btn flex-1 {{ $btnColorClass }} {{ $navItem->is_active ? 'active' : '' }}"
               title="{{ $navItem->label }}">
                <span class="inline-flex h-6 w-6 items-center justify-center relative shrink-0 {{ $navItem->is_active ? 'text-white' : $inactiveColor }}">
                    <x-storefront.navigation-icon :name="$navItem->icon_key" class="h-5 w-5" />
                    @if ($navItem->destination_key === 'cart')
                        <span x-show="$store.orderBuilder && $store.orderBuilder.totalCount > 0" 
                              class="absolute -top-1.5 -right-2 min-w-[18px] h-[18px] px-1 rounded-full bg-rose-500 text-white font-black text-[10px] flex items-center justify-center border border-rose-300/60 border-b-2 border-b-rose-800 shadow-sm animate-pulse"
                              x-text="$store.orderBuilder.totalCount"></span>
                    @endif
                </span>
                <span class="text-[9.5px] sm:text-[10px] tracking-tight truncate max-w-full text-center leading-none mt-1 {{ $navItem->is_active ? 'font-black text-white' : 'font-bold text-slate-700 dark:text-slate-300' }}">
                    {{ $navItem->label }}
                </span>
            </a>
        @empty
            {{-- 1. Home --}}
            <a href="{{ $homeUrl }}" class="sf-nav-btn flex-1 sf-nav-btn-home {{ $isHome ? 'active' : '' }}" title="{{ __('messages.nav_home') }}">
                <span class="inline-flex h-6 w-6 items-center justify-center relative shrink-0 {{ $isHome ? 'text-white' : 'text-sky-500 dark:text-sky-400' }}">
                    <x-storefront.navigation-icon name="home" class="h-5 w-5" />
                </span>
                <span class="text-[9.5px] sm:text-[10px] tracking-tight truncate max-w-full text-center leading-none mt-1 {{ $isHome ? 'font-black text-white' : 'font-bold text-slate-700 dark:text-slate-300' }}">{{ __('messages.nav_home') }}</span>
            </a>

            {{-- 2. Products --}}
            <a href="{{ $productsUrl }}" class="sf-nav-btn flex-1 sf-nav-btn-products {{ $isProducts ? 'active' : '' }}" title="{{ __('messages.nav_products') }}">
                <span class="inline-flex h-6 w-6 items-center justify-center relative shrink-0 {{ $isProducts ? 'text-white' : 'text-indigo-500 dark:text-indigo-400' }}">
                    <x-storefront.navigation-icon name="products" class="h-5 w-5" />
                </span>
                <span class="text-[9.5px] sm:text-[10px] tracking-tight truncate max-w-full text-center leading-none mt-1 {{ $isProducts ? 'font-black text-white' : 'font-bold text-slate-700 dark:text-slate-300' }}">{{ __('messages.nav_products') }}</span>
            </a>

            {{-- 3. Service or Categories --}}
            @if (store_can('service.repair_jobs', $activeStoreContext))
                <a href="{{ $serviceTrackingUrl }}" class="sf-nav-btn flex-1 sf-nav-btn-repair {{ $isServiceTracking ? 'active' : '' }}" title="{{ __('messages.nav_service_track') }}">
                    <span class="inline-flex h-6 w-6 items-center justify-center relative shrink-0 {{ $isServiceTracking ? 'text-white' : 'text-purple-600 dark:text-purple-400' }}">
                        <x-storefront.navigation-icon name="repair" class="h-5 w-5" />
                    </span>
                    <span class="text-[9.5px] sm:text-[10px] tracking-tight truncate max-w-full text-center leading-none mt-1 {{ $isServiceTracking ? 'font-black text-white' : 'font-bold text-slate-700 dark:text-slate-300' }}">{{ __('messages.nav_service_track') }}</span>
                </a>
            @else
                <a href="{{ $browseUrl }}" class="sf-nav-btn flex-1 sf-nav-btn-categories {{ $isBrowse ? 'active' : '' }}" title="{{ __('messages.nav_categories') }}">
                    <span class="inline-flex h-6 w-6 items-center justify-center relative shrink-0 {{ $isBrowse ? 'text-white' : 'text-amber-500 dark:text-amber-400' }}">
                        <x-storefront.navigation-icon name="categories" class="h-5 w-5" />
                    </span>
                    <span class="text-[9.5px] sm:text-[10px] tracking-tight truncate max-w-full text-center leading-none mt-1 {{ $isBrowse ? 'font-black text-white' : 'font-bold text-slate-700 dark:text-slate-300' }}">{{ __('messages.nav_categories') }}</span>
                </a>
            @endif

            {{-- 4. Cart / Order --}}
            @if (store_can('storefront.online_ordering', $activeStoreContext))
            <a href="{{ $orderBuilderUrl }}" class="sf-nav-btn flex-1 sf-nav-btn-cart {{ $isOrderBuilder ? 'active' : '' }}" title="{{ __('messages.nav_cart') }}">
                <span class="inline-flex h-6 w-6 items-center justify-center relative shrink-0 {{ $isOrderBuilder ? 'text-white' : 'text-rose-500 dark:text-rose-400' }}">
                    <x-storefront.navigation-icon name="cart" class="h-5 w-5" />
                    <span x-show="$store.orderBuilder && $store.orderBuilder.totalCount > 0" 
                          class="absolute -top-1.5 -right-2 min-w-[18px] h-[18px] px-1 rounded-full bg-rose-500 text-white font-black text-[10px] flex items-center justify-center border border-rose-300/60 border-b-2 border-b-rose-800 shadow-sm animate-pulse"
                          x-text="$store.orderBuilder.totalCount"></span>
                </span>
                <span class="text-[9.5px] sm:text-[10px] tracking-tight truncate max-w-full text-center leading-none mt-1 {{ $isOrderBuilder ? 'font-black text-white' : 'font-bold text-slate-700 dark:text-slate-300' }}">{{ __('messages.nav_cart') }}</span>
            </a>
            @endif

            {{-- 5. Account or Login --}}
            @if (store_can('storefront.customer_portal', $activeStoreContext))
            @auth
                <a href="{{ $accountUrl }}" class="sf-nav-btn flex-1 sf-nav-btn-account {{ $isAccount ? 'active' : '' }}" title="{{ __('messages.nav_account') }}">
                    <span class="inline-flex h-6 w-6 items-center justify-center relative shrink-0 {{ $isAccount ? 'text-white' : 'text-emerald-500 dark:text-emerald-400' }}">
                        <x-storefront.navigation-icon name="account" class="h-5 w-5" />
                    </span>
                    <span class="text-[9.5px] sm:text-[10px] tracking-tight truncate max-w-full text-center leading-none mt-1 {{ $isAccount ? 'font-black text-white' : 'font-bold text-slate-700 dark:text-slate-300' }}">{{ __('messages.nav_account') }}</span>
                </a>
            @else
                <a href="{{ route('login') }}" class="sf-nav-btn flex-1 sf-nav-btn-login {{ $isAccount ? 'active' : '' }}" title="{{ __('messages.login') }}">
                    <span class="inline-flex h-6 w-6 items-center justify-center relative shrink-0 {{ $isAccount ? 'text-white' : 'text-emerald-500 dark:text-emerald-400' }}">
                        <x-storefront.navigation-icon name="login" class="h-5 w-5" />
                    </span>
                    <span class="text-[9.5px] sm:text-[10px] tracking-tight truncate max-w-full text-center leading-none mt-1 {{ $isAccount ? 'font-black text-white' : 'font-bold text-slate-700 dark:text-slate-300' }}">{{ __('messages.login') }}</span>
                </a>
            @endauth
            @endif
        @endforelse
    </div>
    </div>

    {{-- Floating Contact Button (mobile) — opens popup listing the chat/social channels Admin configured --}}
    @php
        $floatTelegram = \App\Support\ContactLinkBuilder::telegramUrl($setting?->telegram_username);
        $floatViber = \App\Support\ContactLinkBuilder::viberChatUrl($setting?->viber_number);
        $floatViberIos = \App\Support\ContactLinkBuilder::viberIosContactUrl($setting?->viber_number);
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
                class="absolute bottom-full right-0 mb-2.5 w-60 overflow-hidden rounded-2xl border-2 border-slate-200/90 bg-white/95 dark:border-slate-700/90 dark:bg-slate-900/95 shadow-2xl backdrop-blur-md"
                role="menu"
                :aria-expanded="chatOpen ? 'true' : 'false'"
            >
                <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 px-3 py-2 bg-slate-50/70 dark:bg-slate-800/70">
                    <span class="flex h-5 w-5 items-center justify-center rounded-md bg-gradient-to-b from-sky-400 to-blue-600 text-white text-[10px] shadow-xs border-b border-b-blue-800 font-black">💬</span>
                    <p class="text-[11px] font-extrabold uppercase tracking-wider text-slate-600 dark:text-slate-300 font-outfit">
                        {{ __('messages.chat_with_us') }}
                    </p>
                </div>
                <div class="p-1.5 space-y-1.5">
                    @foreach ($chatChannels as $channel)
                        @php
                            $href = $channel['href'] ?? '';
                            $brand = $channel['brand'] ?? '';
                            $isCall = str_starts_with($href, 'tel:');
                            $isViber = $brand === 'viber' || str_starts_with($href, 'viber://');
                            $isTelegram = $brand === 'telegram' || str_contains($href, 't.me') || str_starts_with($href, 'tg://');
                            $isFacebook = $brand === 'facebook' || str_contains($href, 'facebook.com');
                            $isYoutube = $brand === 'youtube' || str_contains($href, 'youtube.com');
                            $isTiktok = $brand === 'tiktok' || str_contains($href, 'tiktok.com');
                            $isCustomScheme = $isViber || $isCall || str_starts_with($href, 'sms:');
                            $viberIosHref = $isViber ? ($floatViberIos ?? $href) : null;

                            // Dedicated 3D tactile button styling per brand
                            $theme = match(true) {
                                $isViber => [
                                    'card' => 'border-purple-300/90 dark:border-purple-700/60 border-b-[3px] border-b-purple-600 dark:border-b-purple-900 bg-gradient-to-b from-purple-50/90 via-white to-purple-100/60 dark:from-purple-950/40 dark:via-slate-900 dark:to-purple-900/30 text-purple-700 dark:text-purple-300 hover:brightness-105 shadow-[0_2px_4px_rgba(147,51,234,0.12),inset_0_1px_0_rgba(255,255,255,0.85)]',
                                    'icon' => 'bg-gradient-to-b from-violet-500 to-purple-600 text-white border-b-2 border-b-purple-800 shadow-xs',
                                    'chevron' => 'text-purple-500 dark:text-purple-400',
                                ],
                                $isTelegram => [
                                    'card' => 'border-sky-300/90 dark:border-sky-700/60 border-b-[3px] border-b-blue-600 dark:border-b-blue-900 bg-gradient-to-b from-sky-50/90 via-white to-sky-100/60 dark:from-sky-950/40 dark:via-slate-900 dark:to-sky-900/30 text-sky-700 dark:text-sky-300 hover:brightness-105 shadow-[0_2px_4px_rgba(2,132,199,0.12),inset_0_1px_0_rgba(255,255,255,0.85)]',
                                    'icon' => 'bg-gradient-to-b from-sky-400 to-blue-600 text-white border-b-2 border-b-blue-800 shadow-xs',
                                    'chevron' => 'text-sky-500 dark:text-sky-400',
                                ],
                                $isCall => [
                                    'card' => 'border-emerald-300/90 dark:border-emerald-700/60 border-b-[3px] border-b-emerald-600 dark:border-b-emerald-900 bg-gradient-to-b from-emerald-50/90 via-white to-emerald-100/60 dark:from-emerald-950/40 dark:via-slate-900 dark:to-emerald-900/30 text-emerald-700 dark:text-emerald-300 hover:brightness-105 shadow-[0_2px_4px_rgba(16,185,129,0.12),inset_0_1px_0_rgba(255,255,255,0.85)]',
                                    'icon' => 'bg-gradient-to-b from-emerald-400 to-emerald-600 text-white border-b-2 border-b-emerald-800 shadow-xs',
                                    'chevron' => 'text-emerald-500 dark:text-emerald-400',
                                ],
                                $isFacebook => [
                                    'card' => 'border-blue-300/90 dark:border-blue-700/60 border-b-[3px] border-b-blue-600 dark:border-b-blue-900 bg-gradient-to-b from-blue-50/90 via-white to-blue-100/60 dark:from-blue-950/40 dark:via-slate-900 dark:to-blue-900/30 text-blue-700 dark:text-blue-300 hover:brightness-105 shadow-[0_2px_4px_rgba(37,99,235,0.12),inset_0_1px_0_rgba(255,255,255,0.85)]',
                                    'icon' => 'bg-gradient-to-b from-blue-500 to-blue-700 text-white border-b-2 border-b-blue-900 shadow-xs',
                                    'chevron' => 'text-blue-500 dark:text-blue-400',
                                ],
                                $isYoutube => [
                                    'card' => 'border-rose-300/90 dark:border-rose-700/60 border-b-[3px] border-b-rose-600 dark:border-b-rose-900 bg-gradient-to-b from-rose-50/90 via-white to-rose-100/60 dark:from-rose-950/40 dark:via-slate-900 dark:to-rose-900/30 text-rose-700 dark:text-rose-300 hover:brightness-105 shadow-[0_2px_4px_rgba(225,29,72,0.12),inset_0_1px_0_rgba(255,255,255,0.85)]',
                                    'icon' => 'bg-gradient-to-b from-rose-500 to-rose-700 text-white border-b-2 border-b-rose-900 shadow-xs',
                                    'chevron' => 'text-rose-500 dark:text-rose-400',
                                ],
                                default => [
                                    'card' => 'border-slate-300/90 dark:border-slate-700/60 border-b-[3px] border-b-slate-400 dark:border-b-slate-800 bg-gradient-to-b from-slate-50/90 via-white to-slate-100/60 dark:from-slate-800 dark:via-slate-900 dark:to-slate-800/40 text-slate-800 dark:text-slate-200 hover:brightness-105 shadow-[0_2px_4px_rgba(0,0,0,0.06),inset_0_1px_0_rgba(255,255,255,0.85)]',
                                    'icon' => 'bg-gradient-to-b from-slate-600 to-slate-800 text-white border-b-2 border-b-slate-900 shadow-xs',
                                    'chevron' => 'text-slate-400 dark:text-slate-500',
                                ],
                            };
                        @endphp
                        <div>
                            <a href="{{ $channel['href'] }}"
                               @if (! $isCustomScheme) target="_blank" rel="noopener noreferrer" @endif
                               @if ($viberIosHref) data-ios-href="{{ $viberIosHref }}" @endif
                               @click="chatOpen = false"
                               class="group w-full flex items-center gap-2.5 rounded-xl px-2.5 py-1.5 text-xs font-extrabold transition-all duration-150 cursor-pointer select-none border {{ $theme['card'] }} active:translate-y-0.5 active:border-b active:shadow-none"
                               role="menuitem">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $theme['icon'] }}">
                                    @if (! empty($channel['icon_url']))
                                        <img src="{{ $channel['icon_url'] }}" alt="" class="h-4 w-4 rounded object-contain" width="16" height="16" loading="lazy" decoding="async">
                                    @elseif (! empty($channel['brand']))
                                        <x-brand-icon :brand="$channel['brand']" class="h-4 w-4 text-white drop-shadow-xs"/>
                                    @else
                                        <span class="text-xs leading-none">{{ $channel['icon'] ?: '💬' }}</span>
                                    @endif
                                </span>
                                <span class="flex-1 font-extrabold truncate">{{ $channel['label'] }}</span>
                                @if ($isCall)
                                    <svg class="h-3.5 w-3.5 text-emerald-500 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M4.5 2.5a1 1 0 0 0-1 1v1c0 6 4.5 10.5 10.5 10.5h1a1 1 0 0 0 1-1v-2.3a1 1 0 0 0-.8-1l-2.4-.5a1 1 0 0 0-1 .3l-.8.8a8 8 0 0 1-3.7-3.7l.8-.8a1 1 0 0 0 .3-1l-.5-2.4a1 1 0 0 0-1-.8z"/></svg>
                                @else
                                    <svg class="h-3 w-3 {{ $theme['chevron'] }} shrink-0 transition-transform duration-150 group-hover:translate-x-0.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd"/></svg>
                                @endif
                            </a>
                            @if ($isViber)
                                @php
                                    $rawViberNum = trim((string) ($setting?->viber_number ?? ''));
                                @endphp
                                <div class="mt-1 px-2.5 py-1.5 rounded-xl bg-purple-50/70 dark:bg-purple-950/30 border border-purple-200/80 dark:border-purple-900/50 text-[10.5px] text-slate-600 dark:text-slate-300 space-y-1">
                                    @if ($rawViberNum)
                                        <div class="flex items-center justify-between gap-1 font-bold">
                                            <span class="font-mono text-purple-800 dark:text-purple-300 text-xs">📱 {{ $rawViberNum }}</span>
                                            <button type="button"
                                                    x-data="{ copied: false }"
                                                    @click.stop="window.__viberFallbackCopy ? window.__viberFallbackCopy('{{ $rawViberNum }}').then(() => { copied = true; setTimeout(() => copied = false, 2000); }) : (navigator.clipboard.writeText('{{ $rawViberNum }}'), copied = true, setTimeout(() => copied = false, 2000))"
                                                    class="px-2 py-0.5 rounded-md bg-white dark:bg-slate-800 border border-purple-200 dark:border-purple-800 text-[10px] font-bold text-purple-700 dark:text-purple-300 shadow-2xs hover:bg-purple-100 transition active:scale-95 cursor-pointer">
                                                <span x-show="!copied">📋 Copy</span>
                                                <span x-show="copied" x-cloak class="text-emerald-600">✓ Done</span>
                                            </button>
                                        </div>
                                    @endif
                                    <div class="flex items-center justify-between text-[10px] text-slate-400 dark:text-slate-500 pt-0.5 {{ $rawViberNum ? 'border-t border-purple-100 dark:border-purple-900/40' : '' }}">
                                        <span>{{ __('messages.viber_missing') }}</span>
                                        <a href="https://www.viber.com/download/" target="_blank" rel="noopener noreferrer" @click="chatOpen = false"
                                           class="font-bold text-purple-600 hover:text-purple-700 dark:text-purple-400 dark:hover:text-purple-300 transition">
                                            {{ __('messages.viber_install') }} →
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="relative">
                {{-- Glow halo behind the FAB for the "raised" floating look (subtle & compact) --}}
                <span aria-hidden="true" class="pointer-events-none absolute -inset-1 rounded-full bg-gradient-to-br {{ $floatTelegram ? 'from-sky-400/40 to-blue-500/40' : 'from-violet-400/40 to-fuchsia-500/40' }} blur-md opacity-60"></span>
                {{-- Attention pulse ring (decorative — must never block button clicks) --}}
                <span x-show="!chatOpen" x-cloak class="pointer-events-none absolute inset-0 rounded-full {{ $floatTelegram ? 'bg-sky-400/30' : 'bg-violet-400/30' }} animate-ping" aria-hidden="true"></span>
                <button
                    type="button"
                    @click.stop.prevent="chatOpen = !chatOpen"
                    aria-haspopup="true"
                    :aria-expanded="chatOpen ? 'true' : 'false'"
                    aria-label="{{ $floatLabel }}"
                    class="relative flex h-11 w-11 sm:h-12 sm:w-12 items-center justify-center rounded-full transition-all duration-150 hover:-translate-y-0.5 hover:scale-105 active:scale-95 active:translate-y-0.5 cursor-pointer select-none {{ $floatTelegram ? 'border border-sky-300/80 border-b-[3px] border-b-blue-700 bg-gradient-to-b from-sky-400 via-sky-500 to-blue-600 shadow-[0_4px_12px_rgba(2,132,199,0.35),inset_0_1px_0_rgba(255,255,255,0.45)]' : 'border border-purple-300/80 border-b-[3px] border-b-purple-800 bg-gradient-to-b from-violet-500 via-purple-600 to-fuchsia-600 shadow-[0_4px_12px_rgba(124,58,237,0.35),inset_0_1px_0_rgba(255,255,255,0.45)]' }}"
                >
                    <span x-show="!chatOpen" class="flex items-center justify-center transition-transform duration-150">
                        @if ($floatIconUrl)
                            <span class="flex h-11 w-11 sm:h-12 sm:w-12 items-center justify-center overflow-hidden rounded-full bg-white dark:bg-slate-800">
                                <img src="{{ $floatIconUrl }}" alt="{{ $floatLabel }}"
                                     class="h-full w-full object-cover"
                                     loading="eager" decoding="async"
                                     x-on:error="$el.style.display='none'; $el.parentElement.innerHTML = $el.parentElement.dataset.fallback"
                                     data-fallback="{!! '<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; viewBox=&quot;0 0 24 24&quot; fill=&quot;currentColor&quot; class=&quot;h-5 w-5 sm:h-5.5 sm:w-5.5 ' . ($floatTelegram ? 'text-sky-500' : 'text-violet-600') . '&quot; aria-hidden=&quot;true&quot;><path d=&quot;' . \App\Support\BrandIconPath::get($floatTelegram ? 'telegram' : 'viber') . '&quot;/></svg>' !!}">
                            </span>
                        @elseif ($floatIcon)
                            <span class="text-xl leading-none drop-shadow">{{ $floatIcon }}</span>
                        @else
                            <x-brand-icon :brand="$floatTelegram ? 'telegram' : 'viber'" class="h-5 w-5 sm:h-5.5 sm:w-5.5 text-white drop-shadow-md"/>
                        @endif
                    </span>
                    <span x-show="chatOpen" x-cloak class="flex items-center justify-center text-white text-base font-black leading-none">
                        ✕
                    </span>
                </button>
                {{-- Neat online dot indicator --}}
                <span class="absolute -top-0.5 -right-0.5 flex h-3 w-3 pointer-events-none">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
                    <span class="relative inline-flex h-3 w-3 rounded-full border-2 border-white bg-emerald-500 dark:border-slate-900 shadow-xs"></span>
                </span>
            </div>
        </div>
    @endif

    @stack('modals')

    @stack('scripts')

    {{-- Web Push frontend — loads after parsing (defer) so it never blocks
         first paint; reads the VAPID key + CSRF token from meta tags. --}}
    <script nonce="{{ $cspNonce }}">
        window.__pushLabels = {
            enabled: @json(__('messages.push_prefs_enabled')),
            disabled: @json(__('messages.push_prefs_disabled'))
        };
    </script>
    <script src="/js/push-notification.js?v={{ filemtime(public_path('js/push-notification.js')) }}" defer></script>

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
        style="display: none;"
        class="!hidden fixed bottom-[calc(env(safe-area-inset-bottom,0px)+5.5rem)] md:bottom-24 left-4 z-50 inline-flex h-10 w-10 sm:h-11 sm:w-11 items-center justify-center rounded-full border border-purple-300/80 border-b-[3px] border-b-purple-800 bg-gradient-to-b from-purple-500 via-violet-600 to-fuchsia-600 text-white shadow-[0_4px_12px_rgba(124,58,237,0.35),inset_0_1px_0_rgba(255,255,255,0.45)] transition-all duration-150 hover:-translate-y-0.5 hover:scale-105 active:scale-95 active:translate-y-0.5 cursor-pointer select-none push-bell-bounce"
        aria-label="{{ __('messages.push_enable_title') }}"
        title="{{ __('messages.push_enable_title') }}"
    >
        <svg class="h-4.5 w-4.5 sm:h-5 sm:w-5 text-white drop-shadow-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        <span id="push-notification-badge" class="hidden absolute -top-1 -right-1 min-w-[16px] h-[16px] px-1 rounded-full bg-rose-500 text-white font-black text-[9px] flex items-center justify-center border-2 border-white dark:border-slate-900 shadow-xs">1</span>
    </button>

    {{-- Web Push Notification Modal / Dialog --}}
    <div id="push-notification-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity duration-200" role="dialog" aria-modal="true">
        <div class="relative w-full max-w-sm rounded-2xl bg-white p-5 shadow-2xl dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 text-slate-900 dark:text-slate-100 space-y-4">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-xl bg-violet-100 dark:bg-violet-950/70 text-violet-600 dark:text-violet-400 grid place-items-center text-lg font-bold shadow-xs">
                        🔔
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white">{{ __('messages.sidebar_push_notifications') }}</h3>
                        <p id="push-modal-status-text" class="text-xs text-slate-500 dark:text-slate-400">အချိန်နှင့်တစ်ပြေးညီ သတင်းလွှာများ ရယူရန်</p>
                    </div>
                </div>
                <button type="button" id="push-modal-close" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-200">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed bg-slate-50 dark:bg-slate-800/60 p-3 rounded-xl border border-slate-100 dark:border-slate-800">
                <p class="font-bold text-slate-800 dark:text-slate-200 mb-1">📢 လက်ခံရရှိမည့် အသိပေးချက်များ:</p>
                <ul class="space-y-1 text-[11px] text-slate-500 dark:text-slate-400">
                    <li>• အထူးလျှော့စျေးနှင့် ပရိုမိုးရှင်း အစီအစဉ်များ</li>
                    <li>• ပစ္စည်းသစ် ရောက်ရှိကြောင်း သတင်းစကားများ</li>
                    <li>• အော်ဒါနှင့် ငွေပေးချေမှု အခြေအနေ အပ်ဒိတ်များ</li>
                </ul>
            </div>

            <div class="flex flex-col gap-2">
                <button type="button" id="push-modal-action-btn" class="w-full py-2.5 px-4 rounded-xl bg-violet-600 hover:bg-violet-700 active:scale-95 text-white font-bold text-xs shadow-md transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <span>🔔 အသိပေးချက်များ ဖွင့်မည်</span>
                </button>
                <button type="button" id="push-modal-dismiss-btn" class="w-full py-2 rounded-xl text-xs font-semibold text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800 transition cursor-pointer">
                    မလိုအပ်သေးပါ
                </button>
            </div>
        </div>
    </div>

    {{-- PWA install banner — cross-platform wizard
         Detects: iOS Safari, iOS Chrome, MIUI, Huawei, Samsung, Android Chrome,
                  Firefox Android, Chrome desktop, Edge desktop, Safari macOS, Firefox desktop
         Sits cleanly above the mobile bottom nav --}}
    <div id="pwa-install-banner" class="hidden fixed inset-x-3 bottom-[calc(env(safe-area-inset-bottom,0px)+5.5rem)] md:bottom-8 z-50 mx-auto max-w-md rounded-2xl border-2 border-slate-200/95 bg-white/95 shadow-2xl backdrop-blur-xl p-3.5 sm:p-4 md:inset-x-auto md:right-6 md:mx-0 dark:border-slate-700/95 dark:bg-slate-900/95" role="dialog" aria-live="polite" aria-label="{{ __('messages.pwa_install_title') }}">
        <div class="flex items-start gap-3">
            <img src="/icons/icon-192.png" alt="" class="h-11 w-11 shrink-0 rounded-xl shadow-xs border border-slate-200 dark:border-slate-700" width="44" height="44">
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-1.5">
                    <span class="inline-flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <p class="text-xs sm:text-sm font-black text-slate-900 dark:text-white leading-tight">{{ __('messages.pwa_install_title') }}</p>
                </div>
                <p class="mt-1 text-[11px] sm:text-xs font-semibold text-slate-600 dark:text-slate-300 leading-snug" id="pwa-desc-text">{{ __('messages.pwa_install_desc') }}</p>
                {{-- Platform-specific guide box (shown when native prompt unavailable) --}}
                <div id="pwa-platform-guide" class="hidden mt-2 p-2.5 rounded-xl bg-sky-50 dark:bg-sky-950/40 border border-sky-200/80 dark:border-sky-800 text-[11px] font-semibold text-sky-900 dark:text-sky-200 leading-snug"></div>
            </div>
            <button type="button" id="pwa-install-dismiss" class="shrink-0 rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-200 cursor-pointer" aria-label="{{ __('messages.close') }}">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="mt-3 flex items-center gap-2" id="pwa-install-actions">
            <button type="button" id="pwa-install-btn" class="sf-btn-3d flex-1 py-2 px-3 text-xs font-black bg-gradient-to-b from-rose-500 to-red-600 text-white border-rose-400/80 border-b-[3px] border-b-red-800 shadow-[0_2px_8px_rgba(225,29,72,0.3),inset_0_1px_0_rgba(255,255,255,0.4)] hover:brightness-110 active:translate-y-0.5 active:border-b cursor-pointer">
                📲 {{ __('messages.pwa_install_btn') }}
            </button>
            <button type="button" id="pwa-install-notnow" class="sf-btn-3d py-2 px-3 text-xs font-bold text-slate-600 dark:text-slate-300 active:translate-y-0.5 active:border-b cursor-pointer">
                {{ __('messages.pwa_install_notnow') }}
            </button>
        </div>
        {{-- Success toast (hidden by default) --}}
        <div id="pwa-success-toast" class="hidden mt-2 text-center text-xs font-bold text-emerald-700 dark:text-emerald-400">
            {{ __('messages.pwa_installed_success') }}
        </div>
    </div>

    {{-- PWA service-worker registration + cross-platform install-prompt handling --}}
    <script nonce="{{ $cspNonce }}">
        (function () {
            var KEY_DISMISSED    = 'pwa_dismissed';
            var KEY_DISMISSED_AT = 'pwa_dismissed_at';
            var KEY_INSTALLED    = 'pwa_installed';

            // ── Platform detection ────────────────────────────────────────────
            var ua = navigator.userAgent || '';

            var isStandalone = (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches)
                || window.navigator.standalone === true;

            var isIos          = /iPad|iPhone|iPod/.test(ua) && !window.MSStream;
            var isIosSafari    = isIos && /Safari/.test(ua) && !/CriOS|FxiOS|OPiOS|mercury/.test(ua);
            var isIosChrome    = isIos && /CriOS/.test(ua);
            var isMiui         = /MiuiBrowser/.test(ua) || /XiaoMi/.test(ua);
            var isHuawei       = /HuaweiBrowser/.test(ua) || /HMSCore/.test(ua);
            var isSamsung      = /SamsungBrowser/.test(ua);
            var isAndroid      = /Android/.test(ua);
            var isFirefox      = /Firefox|FxiOS/.test(ua) && !/Seamonkey/.test(ua);
            var isEdge         = /Edg\//.test(ua);
            var isChrome       = /Chrome/.test(ua) && !/Chromium/.test(ua) && !isEdge && !isSamsung && !isMiui && !isHuawei;
            var isSafariMac    = /^((?!Chrome|Chromium|Android|CriOS).)*Safari/.test(ua) && /Macintosh/.test(ua);
            var isAndroidChrome = isAndroid && isChrome && !isMiui && !isHuawei && !isSamsung;
            var isFirefoxAndroid = isAndroid && isFirefox;
            var hasChromePrompt  = !isIos && !isMiui && !isHuawei && !isSamsung && !isSafariMac && !isFirefox;

            // Per-platform guide text (injected from PHP translations via inline data)
            var GUIDES = {
                ios_safari:      '{{ __('messages.pwa_guide_ios_safari') }}',
                ios_chrome:      '{{ __('messages.pwa_guide_ios_chrome') }}',
                miui:            '{{ __('messages.pwa_guide_miui') }}',
                huawei:          '{{ __('messages.pwa_guide_huawei') }}',
                samsung:         '{{ __('messages.pwa_guide_samsung') }}',
                android_chrome:  '{{ __('messages.pwa_guide_android_chrome') }}',
                firefox_android: '{{ __('messages.pwa_guide_firefox_android') }}',
                chrome_desktop:  '{{ __('messages.pwa_guide_chrome_desktop') }}',
                edge_desktop:    '{{ __('messages.pwa_guide_edge_desktop') }}',
                safari_mac:      '{{ __('messages.pwa_guide_safari_mac') }}',
                firefox_desktop: '{{ __('messages.pwa_guide_firefox_desktop') }}',
                already:         '{{ __('messages.pwa_already_installed') }}',
                success:         '{{ __('messages.pwa_installed_success') }}'
            };

            function getPlatformKey() {
                if (isIosSafari)       return 'ios_safari';
                if (isIosChrome)       return 'ios_chrome';
                if (isMiui)            return 'miui';
                if (isHuawei)          return 'huawei';
                if (isSamsung)         return 'samsung';
                if (isFirefoxAndroid)  return 'firefox_android';
                if (isAndroidChrome)   return 'android_chrome';
                if (isEdge)            return 'edge_desktop';
                if (isChrome)          return 'chrome_desktop';
                if (isSafariMac)       return 'safari_mac';
                if (isFirefox)         return 'firefox_desktop';
                return null;
            }

            // ── Snooze / install state ───────────────────────────────────────
            // Reset legacy permanent dismissed flag
            try {
                var dismissedAt    = localStorage.getItem(KEY_DISMISSED_AT);
                var legacyDismissed = localStorage.getItem(KEY_DISMISSED);
                if (legacyDismissed === '1' && !dismissedAt) {
                    localStorage.removeItem(KEY_DISMISSED);
                } else if (dismissedAt) {
                    // Snooze 3 days
                    if (Date.now() - parseInt(dismissedAt, 10) >= 3 * 24 * 60 * 60 * 1000) {
                        localStorage.removeItem(KEY_DISMISSED);
                        localStorage.removeItem(KEY_DISMISSED_AT);
                    }
                }
            } catch (e) {}

            var dismissed = false, installed = false;
            try {
                dismissed = localStorage.getItem(KEY_DISMISSED) === '1';
                installed = localStorage.getItem(KEY_INSTALLED) === '1';
            } catch (e) {}

            // ── Service Worker registration ──────────────────────────────────
            var isSecure = location.protocol === 'https:'
                || location.hostname === 'localhost' || location.hostname === '127.0.0.1';
            if ('serviceWorker' in navigator && isSecure) {
                window.addEventListener('load', function () {
                    navigator.serviceWorker.register('/sw.js').catch(function () {});
                });
            }

            // ── DOM refs ─────────────────────────────────────────────────────
            var deferredPrompt = null;
            var banner       = document.getElementById('pwa-install-banner');
            var installBtn   = document.getElementById('pwa-install-btn');
            var notNowBtn    = document.getElementById('pwa-install-notnow');
            var dismissBtn   = document.getElementById('pwa-install-dismiss');
            var guideBox     = document.getElementById('pwa-platform-guide');
            var successToast = document.getElementById('pwa-success-toast');

            // ── Show guide for non-prompt platforms ──────────────────────────
            function showPlatformGuide(key) {
                if (!guideBox || !key || !GUIDES[key]) return;
                guideBox.textContent = GUIDES[key];
                guideBox.classList.remove('hidden');
                if (installBtn) installBtn.classList.add('hidden');
            }

            // ── Show banner ──────────────────────────────────────────────────
            function showBanner(forceGuide) {
                if (!banner || dismissed || installed || isStandalone) return;
                var pk = getPlatformKey();

                // Platforms that never fire beforeinstallprompt → show guide immediately
                var noNativePrompt = isIos || isMiui || isHuawei || isSamsung || isSafariMac || isFirefox;
                if (noNativePrompt || forceGuide) {
                    showPlatformGuide(pk);
                }
                banner.classList.remove('hidden');
            }

            // ── beforeinstallprompt (Chrome / Edge / Samsung on Android) ─────
            window.addEventListener('beforeinstallprompt', function (e) {
                e.preventDefault();
                deferredPrompt = e;
                if (navigator.serviceWorker && navigator.serviceWorker.controller) {
                    try { navigator.serviceWorker.controller.postMessage({ type: 'PWA_INSTALL_PROMPT' }); } catch (err) {}
                }
                showBanner(false);
            });

            // Auto-show for platforms without beforeinstallprompt
            if ((isIos || isMiui || isHuawei || isSamsung || isSafariMac || (isFirefox && !isAndroid))
                && !isStandalone && !dismissed && !installed) {
                setTimeout(function () { showBanner(true); }, 2000);
            }

            // ── Install button click ─────────────────────────────────────────
            if (installBtn) {
                installBtn.addEventListener('click', function () {
                    if (deferredPrompt) {
                        deferredPrompt.prompt();
                        deferredPrompt.userChoice.then(function (choice) {
                            if (choice.outcome === 'accepted') {
                                try { localStorage.setItem(KEY_INSTALLED, '1'); } catch (e) {}
                                installed = true;
                                if (successToast) {
                                    successToast.classList.remove('hidden');
                                    setTimeout(function () { if (banner) banner.classList.add('hidden'); }, 2500);
                                } else {
                                    if (banner) banner.classList.add('hidden');
                                }
                            } else {
                                if (banner) banner.classList.add('hidden');
                            }
                            deferredPrompt = null;
                        });
                    } else {
                        // No native prompt — fall through to guide
                        showPlatformGuide(getPlatformKey());
                    }
                });
            }

            // ── Snooze dismiss ───────────────────────────────────────────────
            function snoozeDismiss() {
                try {
                    localStorage.setItem(KEY_DISMISSED, '1');
                    localStorage.setItem(KEY_DISMISSED_AT, String(Date.now()));
                } catch (e) {}
                dismissed = true;
                if (banner) banner.classList.add('hidden');
            }
            if (notNowBtn) notNowBtn.addEventListener('click', snoozeDismiss);
            if (dismissBtn) dismissBtn.addEventListener('click', snoozeDismiss);

            // ── App installed event ──────────────────────────────────────────
            window.addEventListener('appinstalled', function () {
                try { localStorage.setItem(KEY_INSTALLED, '1'); } catch (e) {}
                installed = true;
                if (banner) banner.classList.add('hidden');
            });

            // ── Global manual trigger (Drawer button, etc.) ──────────────────
            window.__promptPwaInstall = function () {
                if (isStandalone || installed) {
                    if (banner) {
                        var guideEl = document.getElementById('pwa-platform-guide');
                        if (guideEl) { guideEl.textContent = GUIDES.already; guideEl.classList.remove('hidden'); }
                        if (installBtn) installBtn.classList.add('hidden');
                        banner.classList.remove('hidden');
                    }
                    return;
                }
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    deferredPrompt.userChoice.then(function (choice) {
                        if (choice.outcome === 'accepted') {
                            try { localStorage.setItem(KEY_INSTALLED, '1'); } catch (e) {}
                            installed = true;
                            if (successToast) successToast.classList.remove('hidden');
                            setTimeout(function () { if (banner) banner.classList.add('hidden'); }, 2500);
                        } else {
                            if (banner) banner.classList.add('hidden');
                        }
                        deferredPrompt = null;
                    });
                } else {
                    // Show guide for current platform
                    dismissed = false;
                    showBanner(true);
                }
            };
        })();
    </script>
</body>
</html>
