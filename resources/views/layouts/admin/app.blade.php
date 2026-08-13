<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Panel - {{ config('app.name') }}</title>
    @php
        // Resolve the store independently in <head> (the body-level
        // $activeStore variable is computed further down the template).
        $headStore = $store ?? app(\App\Services\StoreContext::class)->getStore();
        $adminFaviconPath = ($headStore ?? null)?->setting?->favicon();
        $adminFaviconHref = $adminFaviconPath ? asset('storage/' . $adminFaviconPath) : asset('favicon.ico');
        // Performance: when no dedicated favicon_path exists, the model falls
        // back through the full-size logo assets — serving a ~135KB PNG as a
        // 16px browser tab icon is wasteful. Prefer the small static icons
        // unless an admin explicitly uploaded a dedicated favicon.
        $dedicatedFavicon = ($headStore ?? null)?->setting?->favicon_path;
        $appleTouchHref = $dedicatedFavicon
            ? asset('storage/' . $dedicatedFavicon)
            : asset('apple-touch-icon.png');
    @endphp
    @vite(['resources/css/admin.css', 'resources/js/app-admin.js'])
    {{-- Preload the three WOFF2 fonts so text renders without font-swap CLS. --}}
    <link rel="preload" as="font" type="font/woff2" crossorigin href="{{ Vite::asset('resources/assets/fonts/Roboto-Regular.woff2') }}">
    <link rel="preload" as="font" type="font/woff2" crossorigin href="{{ Vite::asset('resources/assets/fonts/NotoSansMyanmar-Regular.woff2') }}">
    <link rel="preload" as="font" type="font/woff2" crossorigin href="{{ Vite::asset('resources/assets/fonts/Outfit-Regular.woff2') }}">
    <link rel="icon" type="{{ $adminFaviconPath && str_ends_with($adminFaviconPath, '.webp') ? 'image/webp' : ($adminFaviconPath ? 'image/png' : 'image/x-icon') }}" href="{{ $adminFaviconHref }}">
    <link rel="apple-touch-icon" href="{{ $appleTouchHref }}">
    {{-- Match the storefront PWA theme so the browser status bar is light blue here too (no reliance on the edge-cached manifest) --}}
    <meta name="theme-color" content="#38bdf8">
    <script nonce="{{ $cspNonce }}">
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    @vite(['resources/css/admin.css', 'resources/js/app-admin.js'])
</head>
<body class="bg-slate-100 dark:bg-slate-950 text-gray-900 dark:text-slate-100 font-sans antialiased flex h-dvh overflow-hidden transition-colors duration-200"
    x-data="{
        sidebarOpen: false,
        calculatorOpen: false,
        calcDisplay: '0',
        calcLeft: null,
        calcOperator: null,
        calcWaitingForNext: false,
        sidebarCollapsed: localStorage.getItem('adminSidebar') === 'collapsed',
        viewportLg: window.innerWidth >= 1024,
        darkMode: localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches),
        openCalculator() {
            this.calculatorOpen = true;
            this.$nextTick(() => this.$refs.calculatorClose?.focus());
        },
        closeCalculator() {
            this.calculatorOpen = false;
        },
        resetCalculator() {
            this.calcDisplay = '0';
            this.calcLeft = null;
            this.calcOperator = null;
            this.calcWaitingForNext = false;
        },
        inputCalcDigit(value) {
            if (this.calcWaitingForNext) {
                this.calcDisplay = value === '.' ? '0.' : value;
                this.calcWaitingForNext = false;
                return;
            }
            if (value === '.' && this.calcDisplay.includes('.')) return;
            this.calcDisplay = this.calcDisplay === '0' && value !== '.' ? value : this.calcDisplay + value;
        },
        backspaceCalculator() {
            if (this.calcWaitingForNext || this.calcDisplay.length <= 1) {
                this.calcDisplay = '0';
                this.calcWaitingForNext = false;
                return;
            }
            this.calcDisplay = this.calcDisplay.slice(0, -1);
        },
        applyPercent(percent) {
            const current = Number(this.calcDisplay.replace(/,/g, ''));
            if (!Number.isFinite(current)) return;
            this.calcDisplay = this.formatCalcNumber(current * (percent / 100));
            this.calcWaitingForNext = true;
        },
        chooseCalcOperator(operator) {
            const current = Number(this.calcDisplay.replace(/,/g, ''));
            if (!Number.isFinite(current)) return;
            if (this.calcOperator && !this.calcWaitingForNext) {
                this.calculateResult();
            } else {
                this.calcLeft = current;
            }
            this.calcOperator = operator;
            this.calcWaitingForNext = true;
        },
        calculateResult() {
            if (!this.calcOperator || this.calcLeft === null) return;
            const right = Number(this.calcDisplay.replace(/,/g, ''));
            if (!Number.isFinite(right)) return;
            let result = this.calcLeft;
            if (this.calcOperator === '+') result += right;
            if (this.calcOperator === '-') result -= right;
            if (this.calcOperator === '*') result *= right;
            if (this.calcOperator === '/') {
                if (right === 0) {
                    this.calcDisplay = 'Error';
                    this.calcLeft = null;
                    this.calcOperator = null;
                    this.calcWaitingForNext = true;
                    return;
                }
                result /= right;
            }
            this.calcDisplay = this.formatCalcNumber(result);
            this.calcLeft = result;
            this.calcOperator = null;
            this.calcWaitingForNext = true;
        },
        formatCalcNumber(value) {
            if (!Number.isFinite(value)) return 'Error';
            return Number.parseFloat(value.toFixed(8)).toLocaleString('en-US', { maximumFractionDigits: 8 });
        },
        handleCalculatorKey(event) {
            if (!this.calculatorOpen) return;
            if (/^[0-9.]$/.test(event.key)) {
                event.preventDefault();
                this.inputCalcDigit(event.key);
            } else if (['+', '-', '*', '/'].includes(event.key)) {
                event.preventDefault();
                this.chooseCalcOperator(event.key);
            } else if (event.key === 'Enter' || event.key === '=') {
                event.preventDefault();
                this.calculateResult();
            } else if (event.key === 'Backspace') {
                event.preventDefault();
                this.backspaceCalculator();
            } else if (event.key === 'Escape') {
                event.preventDefault();
                this.closeCalculator();
            }
        },
        collapseSidebar() {
            this.sidebarCollapsed = true;
            localStorage.setItem('adminSidebar', 'collapsed');
            this.$dispatch('admin-sidebar-collapsed');
        },
        expandSidebar() {
            this.sidebarCollapsed = false;
            localStorage.setItem('adminSidebar', 'expanded');
        },
        toggleSidebarCollapsed() {
            if (this.sidebarCollapsed) {
                this.expandSidebar();
            } else {
                this.collapseSidebar();
            }
        },
        closeDrawer() {
            if (this.sidebarOpen) {
                this.sidebarOpen = false;
                this.$nextTick(() => this.$refs.menuButton?.focus());
            }
        },
        toggleDarkMode() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
            if (this.darkMode) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
    }"
    @keydown.window="handleCalculatorKey($event)"
    @keydown.escape.window="closeDrawer()"
    @resize.window="viewportLg = window.innerWidth >= 1024"
    x-init="
        if (darkMode) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    ">
    @php
        $activeStore = $store ?? app(\App\Services\StoreContext::class)->getStore();
        $currentSlug = request()->route('store_slug') ?? $activeStore?->slug;
        $hasStoreContext = filled($currentSlug);
        $storeRouteParams = $hasStoreContext ? ['store_slug' => $currentSlug] : [];
        $pendingOrderCount = $adminPendingOrderCount ?? 0;
        $canAccessStaffTools = $hasStoreContext && ($adminCanAccessStaffTools ?? false);
        $canManageSettings = $hasStoreContext && ($adminCanManageSettings ?? false);
        $canManageUsers = $hasStoreContext && ($adminCanManageUsers ?? false);
        $currentPath = request()->path();
        $adminStoreSetting = $activeStore?->setting;
    @endphp

    <aside :inert="!viewportLg && !sidebarOpen" :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full', sidebarCollapsed ? 'lg:w-20' : 'lg:w-72']"
        data-admin-alerts-url="{{ $hasStoreContext ? url('/store/' . $currentSlug . '/admin/alerts/check') : '' }}"
        data-admin-alerts-interval="30000"
        x-data="{
            catalogOpen: {{ Str::contains($currentPath, ['products', 'categories', 'brands', 'variant-presets']) ? 'true' : 'false' }},
            salesOpen: {{ Str::contains($currentPath, ['orders']) ? 'true' : 'false' }},
            wholesaleOpen: {{ Str::contains($currentPath, ['wholesale']) ? 'true' : 'false' }},
            contentOpen: {{ Str::contains($currentPath, ['blog', 'reviews']) ? 'true' : 'false' }},
            toolsOpen: {{ Str::contains($currentPath, ['glass-finder', 'import-history']) ? 'true' : 'false' }},
            settingsOpen: {{ Str::contains($currentPath, ['settings', 'banners']) ? 'true' : 'false' }},
            closeGroups() {
                this.catalogOpen = false;
                this.salesOpen = false;
                this.wholesaleOpen = false;
                this.contentOpen = false;
                this.toolsOpen = false;
                this.settingsOpen = false;
            },
            // Single-open accordion: opening one group closes the others;
            // clicking an already-open group closes it; clicking while the
            // sidebar is collapsed expands the sidebar and opens that group.
            toggleGroup(name) {
                if (this.sidebarCollapsed) {
                    this.expandSidebar();
                    this.closeGroups();
                    this[name + 'Open'] = true;
                    return;
                }
                if (this[name + 'Open']) {
                    this[name + 'Open'] = false;
                    return;
                }
                this.closeGroups();
                this[name + 'Open'] = true;
            }
        }"
        @admin-sidebar-collapsed.window="closeGroups()"
        class="fixed inset-y-0 left-0 z-30 w-72 bg-white dark:bg-slate-950 text-slate-800 dark:text-slate-100 transition-all duration-300 ease-in-out lg:static lg:translate-x-0 flex flex-col shadow-lg border-r border-slate-200/80 dark:border-slate-800/80 pb-[env(safe-area-inset-bottom)]">

        <div class="h-16 flex items-center justify-between px-4 font-bold border-b border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-slate-950/40">
            <span class="font-outfit text-violet-700 dark:text-violet-300 flex min-w-0 items-center gap-3" :class="sidebarCollapsed ? 'lg:justify-center lg:gap-0' : ''">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-gray-200 bg-white text-violet-600 shadow-lg shadow-violet-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-violet-300">
                    @if (!empty($adminStoreSetting?->adminLogo()))
                        <img
                            src="{{ asset('storage/' . $adminStoreSetting->adminLogo()) }}"
                            alt="{{ $adminStoreSetting->store_name ?: ($activeStore->name ?? 'Store') }} {{ __('messages.logo') }}"
                            class="h-full w-full object-contain p-0.5"
                            loading="lazy"
                        />
                    @else
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 13h6V4H4v9Zm0 7h6v-3H4v3Zm10 0h6v-9h-6v9Zm0-13h6V4h-6v3Z"/></svg>
                    @endif
                </span>
                <span class="min-w-0" :class="sidebarCollapsed ? 'lg:hidden' : ''">
                    <span class="block truncate text-base">{{ __('messages.admin_panel') }}</span>
                    <span class="block truncate text-xs font-medium text-slate-500 dark:text-slate-400">{{ $activeStore->name ?? 'DataPOS' }}</span>
                </span>
            </span>
            <button x-ref="sidebarClose" @click="closeDrawer()" class="lg:hidden inline-flex h-11 w-11 items-center justify-center rounded-xl text-gray-500 hover:bg-gray-200 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white focus:outline-none focus:ring-2 focus:ring-violet-500" aria-label="{{ __('messages.close_menu') }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M6 6l12 12M18 6 6 18" />
                </svg>
            </button>
        </div>

        <nav class="flex-1 px-3 py-4 space-y-1.5 overflow-y-auto text-sm" aria-label="{{ __('messages.admin_navigation') }}">
            <div>
                @php $isDashboard = request()->is('store/*/admin/dashboard') || request()->is('admin/dashboard'); @endphp
                <x-admin.nav-link variant="main"
                    :href="$hasStoreContext ? route('store.admin.dashboard', $storeRouteParams) : route('admin.dashboard')"
                    :route-name="$hasStoreContext ? 'store.admin.dashboard' : 'admin.dashboard'"
                    :active="$isDashboard"
                    :label="__('messages.admin_dashboard')">
                    <x-slot:icon>
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 13h8V3H3v10Zm0 8h8v-4H3v4Zm12 0h6V11h-6v10Zm0-14h6V3h-6v4Z"/></svg>
                    </x-slot:icon>
                </x-admin.nav-link>
            </div>

            @if (auth()->user()?->isPlatformOwner())
                @php $isStoreManagement = request()->is('admin/stores*'); @endphp
                <div>
                    <x-admin.nav-link variant="main"
                        :href="route('admin.stores.index')"
                        route-name="admin.stores.index"
                        :active="$isStoreManagement"
                        :label="__('messages.store_management')">
                        <x-slot:icon>
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21V8l9-5 9 5v13M9 21v-6h6v6M3 21h18"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>
                </div>
            @endif

            @if ($canAccessStaffTools)
                <x-admin.nav-group name="catalog" :label="__('messages.catalog')" icon-class="bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                    <x-slot:icon>
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 8.5 12 3 3 8.5m18 0-9 5.5m9-5.5V16l-9 5.5M3 8.5l9 5.5M3 8.5V16l9 5.5m0-7.5v7.5"/></svg>
                    </x-slot:icon>

                    @php $isProducts = request()->is('store/*/admin/products*') && !request()->is('store/*/admin/products/import'); @endphp
                    <x-admin.nav-link :href="route('store.admin.products.index', $storeRouteParams)" route-name="store.admin.products.index" :active="$isProducts" :label="__('messages.products')">
                        <x-slot:icon>
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 8h12l-1 12H7L6 8Zm3 0a3 3 0 0 1 6 0"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    @php $isCategories = request()->is('store/*/admin/categories*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.categories.index', $storeRouteParams)" route-name="store.admin.categories.index" :active="$isCategories" :label="__('messages.categories')">
                        <x-slot:icon>
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h7l2 2h9v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6Z"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    @php $isBrands = request()->is('store/*/admin/brands*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.brands.index', $storeRouteParams)" route-name="store.admin.brands.index" :active="$isBrands" :label="__('messages.brands')">
                        <x-slot:icon>
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13 11 4H4v7l9 9 7-7ZM7.5 7.5h.01"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    @php $isVariantPresets = request()->is('store/*/admin/variant-presets*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.variant-presets.index', $storeRouteParams)" route-name="store.admin.variant-presets.index" :active="$isVariantPresets" label="Variant Settings">
                        <x-slot:icon>
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h10M4 17h10M18 5v4M18 15v4M14 7h8M14 17h8"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    @php $isImport = request()->is('store/*/admin/products/import'); @endphp
                    <x-admin.nav-link :href="route('store.admin.products.import', $storeRouteParams)" route-name="store.admin.products.import" :active="$isImport" :label="__('messages.product_import')">
                        <x-slot:icon>
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v10m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>
                </x-admin.nav-group>

                <x-admin.nav-group name="sales" :label="__('messages.sales')" icon-class="bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                    <x-slot:icon>
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6h15l-1.5 8.5H8L6 3H3m6 16.5h.01M18 19.5h.01"/></svg>
                    </x-slot:icon>
                    @if ($pendingOrderCount > 0)
                        <x-slot:badge>
                            <span data-pending-order-count="{{ $pendingOrderCount }}" class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full font-bold shadow max-w-[3rem] truncate">{{ $pendingOrderCount }}</span>
                        </x-slot:badge>
                        <x-slot:corner-badge>
                            {{-- Collapsed desktop corner badge — stays visible without covering the icon --}}
                            <span data-pending-order-count="{{ $pendingOrderCount }}" x-cloak :class="sidebarCollapsed && viewportLg ? 'inline-flex' : 'hidden'"
                                class="absolute -top-1 -right-0.5 z-10 min-w-5 h-5 px-1 items-center justify-center rounded-full bg-red-500 text-white text-xs leading-none font-bold shadow-lg ring-2 ring-white dark:ring-slate-950">{{ $pendingOrderCount }}</span>
                        </x-slot:corner-badge>
                    @endif

                    @php $isOrders = request()->is('store/*/admin/orders*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.orders.index', $storeRouteParams)" route-name="store.admin.orders.index" :active="$isOrders" :label="__('messages.orders')">
                        <x-slot:icon>
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h6M9 9h6M9 13h6M7 3h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/></svg>
                        </x-slot:icon>
                        @if ($pendingOrderCount > 0)
                            <x-slot:badge>
                                <span data-pending-order-count="{{ $pendingOrderCount }}" class="bg-red-500 text-white text-xs px-1.5 rounded-full font-bold max-w-[3rem] truncate">{{ $pendingOrderCount }}</span>
                            </x-slot:badge>
                        @endif
                    </x-admin.nav-link>
                </x-admin.nav-group>

                <x-admin.nav-group name="wholesale" :label="__('messages.wholesale')" icon-class="bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300">
                    <x-slot:icon>
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h8m-8 4h8M7 7h10M6 3h12a2 2 0 0 1 2 2v14l-4-2-4 2-4-2-4 2V5a2 2 0 0 1 2-2Z"/></svg>
                    </x-slot:icon>

                    @php $isWholesale = request()->is('store/*/admin/wholesale*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.wholesale.applications.index', $storeRouteParams)" route-name="store.admin.wholesale.applications.index" :active="$isWholesale" :label="__('messages.wholesale_applications')">
                        <x-slot:icon>
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6V5a2 2 0 0 1 2-2h0a2 2 0 0 1 2 2v1m-9 4h14M5 6h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>
                </x-admin.nav-group>

                <x-admin.nav-group name="content" :label="__('messages.content')" icon-class="bg-fuchsia-100 text-fuchsia-700 dark:bg-fuchsia-500/15 dark:text-fuchsia-300">
                    <x-slot:icon>
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5h16M4 12h16M4 19h10"/></svg>
                    </x-slot:icon>

                    @php $isBlog = request()->is('store/*/admin/blog*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.blog.index', $storeRouteParams)" :active="$isBlog" :label="__('messages.blog_posts')" route-name="store.admin.blog.index">
                        <x-slot:icon>
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5h16v14H4V5Zm4 4h8M8 13h8M8 17h5"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    @php $isReviews = request()->is('store/*/admin/reviews*'); @endphp
                    <x-admin.nav-link :href="url('/store/' . ($activeStore->slug ?? '') . '/admin/reviews')" :active="$isReviews" :label="__('messages.product_reviews')" route-name="store.admin.reviews.index">
                        <x-slot:icon>
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1L12 16.9 6.6 19.8l1-6.1-4.4-4.3 6.1-.9L12 3Z"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>
                </x-admin.nav-group>

                <x-admin.nav-group name="tools" :label="__('messages.tools')" icon-class="bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300">
                    <x-slot:icon>
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.7 6.3a4 4 0 0 0-5.4 5.4L4 17v3h3l5.3-5.3a4 4 0 0 0 5.4-5.4l-2.4 2.4-3-3 2.4-2.4Z"/></svg>
                    </x-slot:icon>

                    @php $isGlass = request()->is('store/*/admin/glass-finder*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.glass-finder.index', $storeRouteParams)" route-name="store.admin.glass-finder.index" :active="$isGlass" :label="__('messages.glass_finder')">
                        <x-slot:icon>
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.3-4.3M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    @php $isPilotImport = request()->is('store/*/admin/pilot-import*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.pilot-import.index', $storeRouteParams)" route-name="store.admin.pilot-import.index" :active="$isPilotImport" :label="__('messages.pilot_import')">
                        <x-slot:icon>
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h10M4 12h10M4 17h6M15 8l5 5m0 0-5 5m5-5h-9"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    @php $isImportHistory = request()->is('store/*/admin/import-history*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.import-history.index', $storeRouteParams)" route-name="store.admin.import-history.index" :active="$isImportHistory" :label="__('messages.import_history')">
                        <x-slot:icon>
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v5l3 2M4 4v5h5M5.5 15a7 7 0 1 0 .8-7.8L4 9"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    @php $isBackups = request()->is('store/*/admin/backups*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.backups.index', $storeRouteParams)" route-name="store.admin.backups.index" :active="$isBackups" :label="__('messages.backups')">
                        <x-slot:icon>
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M13 6l6 6-6 6M19 12H5"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    @php $isPush = request()->is('store/*/admin/push') || request()->is('store/*/admin/push?*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.push.index', $storeRouteParams)" route-name="store.admin.push.index" :active="$isPush" label="Web Push">
                        <x-slot:icon>
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    @php $isPushHistory = request()->is('store/*/admin/push/history*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.push.history', $storeRouteParams)" route-name="store.admin.push.history" :active="$isPushHistory" label="Push History">
                        <x-slot:icon>
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>
                </x-admin.nav-group>

                @php $isBanners = request()->is('store/*/admin/banners*'); @endphp
                <x-admin.nav-link variant="direct"
                    :href="route('store.admin.banners.index', $storeRouteParams)"
                    route-name="store.admin.banners.index"
                    :active="$isBanners"
                    :label="__('messages.home_banners')">
                    <x-slot:icon>
                        {{-- Banner flag icon -- pole with waving pennant --}}
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 21V3m0 0h13l-2 4 2 4H5"/></svg>
                    </x-slot:icon>
                </x-admin.nav-link>

                @if ($canManageSettings)
                <x-admin.nav-group name="settings" :label="__('messages.settings')" icon-class="bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300">
                    <x-slot:icon>
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm8.5 4a7 7 0 0 0-.1-1l2-1.5-2-3.5-2.4 1a8 8 0 0 0-1.7-1L16 3h-4l-.3 3a8 8 0 0 0-1.7 1l-2.4-1-2 3.5 2 1.5a7 7 0 0 0 0 2l-2 1.5 2 3.5 2.4-1a8 8 0 0 0 1.7 1l.3 3h4l.3-3a8 8 0 0 0 1.7-1l2.4 1 2-3.5-2-1.5c.1-.3.1-.7.1-1Z"/></svg>
                    </x-slot:icon>
                        @if ($canManageSettings)
                            @php
                                $isSettingsGeneral = request()->is('store/*/admin/settings') || request()->is('store/*/admin/settings/');
                                $isSettingsContact = request()->is('store/*/admin/settings/contact');
                                $isSettingsDelivery = request()->is('store/*/admin/settings/delivery');
                                $isSettingsHowTo = request()->is('store/*/admin/settings/how-to-order');
                            @endphp
                            <x-admin.nav-link :href="route('store.admin.settings.edit', $storeRouteParams)" route-name="store.admin.settings.edit" :active="$isSettingsGeneral" :label="__('messages.settings_general')">
                                <x-slot:icon>
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M7 7v10M12 7v10M17 7v10M4 17h16"/></svg>
                                </x-slot:icon>
                            </x-admin.nav-link>
                            <x-admin.nav-link :href="route('store.admin.settings.section', [...$storeRouteParams, 'section' => 'contact'])" route-name="store.admin.settings.section" :active="$isSettingsContact" :label="__('messages.settings_contact')">
                                <x-slot:icon>
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.5 5.5 9 8l-1.5 2a10 10 0 0 0 6.5 6.5l2-1.5 2.5 2.5-1.5 3A16 16 0 0 1 3.5 7l3-1.5Z"/></svg>
                                </x-slot:icon>
                            </x-admin.nav-link>
                            <x-admin.nav-link :href="route('store.admin.settings.section', [...$storeRouteParams, 'section' => 'delivery'])" route-name="store.admin.settings.section" :active="$isSettingsDelivery" :label="__('messages.settings_delivery')">
                                <x-slot:icon>
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h11v9H3V7Zm11 3h4l3 3v3h-7v-6ZM7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm10 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/></svg>
                                </x-slot:icon>
                            </x-admin.nav-link>
                            <x-admin.nav-link :href="route('store.admin.settings.section', [...$storeRouteParams, 'section' => 'how-to-order'])" route-name="store.admin.settings.section" :active="$isSettingsHowTo" :label="__('messages.settings_how_to_order')">
                                <x-slot:icon>
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H7a3 3 0 0 0-3 3V5.5Zm0 0V22m4-14h8m-8 4h8"/></svg>
                                </x-slot:icon>
                            </x-admin.nav-link>
                        @endif

                        @if ($canManageUsers)
                            @php $isUsers = request()->is('store/*/admin/users*'); @endphp
                            <x-admin.nav-link :href="route('store.admin.users.index', $storeRouteParams)" route-name="store.admin.users.index" :active="$isUsers" :label="__('messages.users')">
                                <x-slot:icon>
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11a4 4 0 1 0-8 0m8 0a4 4 0 1 1-8 0m8 0c3 1 5 3 5 6v1H3v-1c0-3 2-5 5-6"/></svg>
                                </x-slot:icon>
                            </x-admin.nav-link>
                        @endif
                </x-admin.nav-group>
                @endif
            @endif
        </nav>
    </aside>

    <div x-show="sidebarOpen" x-transition.opacity.duration.200ms @click="closeDrawer()"
        class="fixed inset-0 z-20 bg-black/30 lg:hidden" aria-hidden="true"></div>

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white/90 dark:bg-slate-900/90 backdrop-blur border-b border-slate-200/80 dark:border-slate-800/80 h-[calc(4rem+env(safe-area-inset-top))] pt-[env(safe-area-inset-top)] flex items-center justify-between px-4 sm:px-6 transition-colors duration-200 gap-2 sticky top-0 z-10">
            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                <button x-ref="menuButton" @click="sidebarOpen = true; $nextTick(() => document.querySelector('aside [x-ref=sidebarClose]')?.focus())" class="lg:hidden inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-violet-500 flex-shrink-0" aria-label="{{ __('messages.open_menu') }}">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <button type="button" @click="toggleSidebarCollapsed()"
                    class="hidden lg:inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm hover:bg-slate-50 hover:text-violet-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-violet-300 focus:outline-none focus:ring-2 focus:ring-violet-500 flex-shrink-0"
                    :aria-label="sidebarCollapsed ? '{{ __('messages.expand_sidebar') }}' : '{{ __('messages.collapse_sidebar') }}'">
                    <svg x-show="!sidebarCollapsed" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h10M4 17h16" />
                    </svg>
                    <svg x-show="sidebarCollapsed" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M10 12h10M4 17h16" />
                    </svg>
                </button>

                <div class="font-bold text-gray-800 dark:text-slate-100 font-outfit text-sm sm:text-base truncate">
                    <span class="text-gray-500 dark:text-slate-400 font-normal hidden sm:inline">{{ __('messages.store') }}: </span>{{ $activeStore->name ?? 'Select Store' }}
                </div>
            </div>

            <div class="flex items-center gap-1.5 sm:gap-2 flex-shrink-0">
                {{-- Language switcher: inline on sm+ (mobile lives inside the More menu) --}}
                <div class="hidden sm:block">
                    <x-language-switcher id="admin-header" />
                </div>

                {{-- More actions (mobile only: view store, reload, calculator, language) --}}
                <div class="relative sm:hidden" x-data="{ moreOpen: false }" @click.outside="moreOpen = false" @keydown.escape.window="moreOpen = false">
                    <button type="button" @click="moreOpen = !moreOpen"
                        class="h-11 w-11 inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-violet-500 flex-shrink-0"
                        :aria-expanded="moreOpen.toString()" aria-haspopup="menu" aria-label="{{ __('messages.more_actions') }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <circle cx="5" cy="12" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="19" cy="12" r="1.6"/>
                        </svg>
                    </button>
                    <div x-show="moreOpen" x-transition x-cloak
                        class="absolute right-0 top-full z-30 mt-2 w-60 rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl shadow-slate-900/10 dark:border-slate-700 dark:bg-slate-900"
                        role="menu" aria-label="{{ __('messages.more_actions') }}">
                        @if ($hasStoreContext)
                            <a href="{{ url('/store/' . $currentSlug) }}" target="_blank" rel="noopener noreferrer" role="menuitem" @click="moreOpen = false"
                                class="w-full flex items-center gap-2.5 px-3 min-h-11 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                                <svg class="h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10.5 5 5h14l2 5.5M4 10.5V19a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8.5M3 10.5h18M8 21v-6h8v6" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 5v5.5m5-5.5v5.5M17 5v5.5" />
                                </svg>
                                {{ __('messages.view_commerce') }}
                            </a>
                        @endif
                        <button type="button" role="menuitem" @click="moreOpen = false; window.location.reload()"
                            class="w-full flex items-center gap-2.5 px-3 min-h-11 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                            <svg class="h-4 w-4 shrink-0 text-slate-500 dark:text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 6v5h-5M4 18v-5h5" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.1 9a7 7 0 0 1 11.5-2.6L20 8.8M4 15.2l2.4 2.4A7 7 0 0 0 17.9 15" />
                            </svg>
                            {{ __('messages.reload_page') }}
                        </button>
                        <button type="button" role="menuitem" @click="moreOpen = false; openCalculator()"
                            class="w-full flex items-center gap-2.5 px-3 min-h-11 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                            <svg class="h-4 w-4 shrink-0 text-blue-600 dark:text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <rect x="5" y="3" width="14" height="18" rx="2" stroke-width="2" />
                                <path stroke-linecap="round" stroke-width="2" d="M8 7h8M8 11h.01M12 11h.01M16 11h.01M8 15h.01M12 15h.01M16 15h.01" />
                            </svg>
                            {{ __('messages.calculator') }}
                        </button>
                        <div class="my-1 border-t border-slate-100 dark:border-slate-800"></div>
                        <div class="flex items-center justify-between px-2.5 py-1">
                            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('messages.language_switcher_label') }}</span>
                            <x-language-switcher id="admin-header-mobile" align="left" />
                        </div>
                    </div>
                </div>

                @if ($hasStoreContext)
                    <a href="{{ url('/store/' . $currentSlug) }}" target="_blank" rel="noopener noreferrer"
                        class="hidden sm:inline-flex h-11 w-11 sm:h-10 sm:w-10 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-500/15 dark:text-emerald-300 dark:hover:bg-emerald-500/25 transition items-center justify-center focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        aria-label="{{ __('messages.view_commerce') }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10.5 5 5h14l2 5.5M4 10.5V19a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8.5M3 10.5h18M8 21v-6h8v6" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 5v5.5m5-5.5v5.5M17 5v5.5" />
                        </svg>
                    </a>
                @endif

                <button @click="window.location.reload()" type="button"
                    class="hidden sm:inline-flex h-11 w-11 sm:h-10 sm:w-10 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600 transition items-center justify-center focus:outline-none focus:ring-2 focus:ring-violet-500"
                    aria-label="{{ __('messages.reload_page') }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 6v5h-5M4 18v-5h5" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.1 9a7 7 0 0 1 11.5-2.6L20 8.8M4 15.2l2.4 2.4A7 7 0 0 0 17.9 15" />
                    </svg>
                </button>

                <button @click="openCalculator()" type="button"
                    class="h-11 w-11 sm:h-10 sm:w-10 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-500/15 dark:text-blue-300 dark:hover:bg-blue-500/25 transition inline-flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-blue-500"
                    aria-label="{{ __('messages.calculator') }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <rect x="5" y="3" width="14" height="18" rx="2" stroke-width="2" />
                        <path stroke-linecap="round" stroke-width="2" d="M8 7h8M8 11h.01M12 11h.01M16 11h.01M8 15h.01M12 15h.01M16 15h.01" />
                    </svg>
                </button>

                <button @click="toggleDarkMode()" type="button"
                    class="h-11 w-11 sm:h-10 sm:w-10 rounded-lg bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-amber-400 hover:bg-gray-200 dark:hover:bg-slate-600 transition inline-flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-violet-500"
                    :aria-label="darkMode ? 'Switch to light mode' : 'Switch to dark mode'">
                    <svg x-show="!darkMode" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.8A8.5 8.5 0 1111.2 3a6.5 6.5 0 009.8 9.8z" />
                    </svg>
                    <svg x-show="darkMode" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v2m0 14v2m9-9h-2M5 12H3m15.36-6.36-1.42 1.42M7.06 16.94l-1.42 1.42m12.72 0-1.42-1.42M7.06 7.06 5.64 5.64" />
                        <circle cx="12" cy="12" r="4" stroke-width="2" />
                    </svg>
                </button>

                <span class="hidden sm:inline-block text-xs font-semibold text-gray-600 dark:text-slate-300 bg-gray-100 dark:bg-slate-700 px-2.5 py-1.5 rounded-lg border dark:border-slate-600 max-w-[140px] truncate">
                    {{ auth()->user()?->name }}
                </span>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="min-h-11 inline-flex items-center text-xs text-red-600 dark:text-red-400 font-semibold hover:underline whitespace-nowrap px-1">{{ __('messages.logout') }}</button>
                </form>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900/60 transition-colors duration-200">
            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>

    <div x-cloak x-show="calculatorOpen" x-transition.opacity
        class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/45 p-0 backdrop-blur-sm sm:items-center sm:p-3"
        role="dialog" aria-modal="true" aria-labelledby="admin-calculator-title"
        @click.self="closeCalculator()">
        <div x-show="calculatorOpen" x-transition
            class="admin-calculator w-screen max-w-none overflow-hidden rounded-t-[1.75rem] bg-white px-4 pt-4 pb-[calc(1rem+env(safe-area-inset-bottom))] shadow-2xl dark:bg-slate-900 sm:w-full sm:max-w-[360px] sm:rounded-[1.75rem] sm:p-5"
            style="width: 100dvw;">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-300" aria-hidden="true">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <rect x="5" y="3" width="14" height="18" rx="2" stroke-width="2" />
                            <path stroke-linecap="round" stroke-width="2" d="M8 7h8M8 11h.01M12 11h.01M16 11h.01M8 15h.01M12 15h.01M16 15h.01" />
                        </svg>
                    </span>
                    <h2 id="admin-calculator-title" class="text-sm font-black text-slate-700 dark:text-slate-100">{{ __('messages.calculator') }}</h2>
                </div>
                <button x-ref="calculatorClose" @click="closeCalculator()" type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    aria-label="{{ __('messages.close') }}">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M6 6l12 12M18 6 6 18" />
                    </svg>
                </button>
            </div>

            <div class="mb-4 flex min-h-24 items-center justify-end overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 px-4 text-right text-4xl font-black tabular-nums text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-50 sm:min-h-20">
                <span class="max-w-full truncate" x-text="calcDisplay"></span>
            </div>

            <div class="grid grid-cols-5 gap-2 pb-3">
                @foreach ([5, 10, 15, 20, 30] as $percent)
                    <button type="button" @click="applyPercent({{ $percent }})"
                        class="min-h-12 rounded-xl bg-slate-600 px-2 text-sm font-black text-white shadow-sm hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:hover:bg-slate-600 sm:min-h-11">
                        {{ $percent }}%
                    </button>
                @endforeach
            </div>

            <div class="grid grid-cols-4 gap-2">
                <button type="button" @click="resetCalculator()" class="min-h-16 rounded-xl sm:min-h-14 bg-red-400 text-xl font-black text-white shadow-sm hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-400">C</button>
                <button type="button" @click="backspaceCalculator()" class="min-h-16 rounded-xl sm:min-h-14 bg-amber-300 text-xl font-black text-white shadow-sm hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400" aria-label="{{ __('messages.backspace') }}">
                    <svg class="mx-auto h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 6H9l-5 6 5 6h11V6Zm-4 4-4 4m0-4 4 4"/></svg>
                </button>
                <button type="button" @click="chooseCalcOperator('/')" class="min-h-16 rounded-xl sm:min-h-14 bg-blue-500 text-xl font-black text-white shadow-sm hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">÷</button>
                <button type="button" @click="chooseCalcOperator('*')" class="min-h-16 rounded-xl sm:min-h-14 bg-blue-500 text-xl font-black text-white shadow-sm hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">×</button>

                @foreach ([7, 8, 9] as $digit)
                    <button type="button" @click="inputCalcDigit('{{ $digit }}')" class="min-h-16 rounded-xl sm:min-h-14 border border-slate-200 bg-slate-50 text-xl font-black text-slate-900 shadow-sm hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700">{{ $digit }}</button>
                @endforeach
                <button type="button" @click="chooseCalcOperator('-')" class="min-h-16 rounded-xl sm:min-h-14 bg-blue-500 text-xl font-black text-white shadow-sm hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">-</button>

                @foreach ([4, 5, 6] as $digit)
                    <button type="button" @click="inputCalcDigit('{{ $digit }}')" class="min-h-16 rounded-xl sm:min-h-14 border border-slate-200 bg-slate-50 text-xl font-black text-slate-900 shadow-sm hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700">{{ $digit }}</button>
                @endforeach
                <button type="button" @click="chooseCalcOperator('+')" class="min-h-16 rounded-xl sm:min-h-14 bg-blue-500 text-xl font-black text-white shadow-sm hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">+</button>

                @foreach ([1, 2, 3] as $digit)
                    <button type="button" @click="inputCalcDigit('{{ $digit }}')" class="min-h-16 rounded-xl sm:min-h-14 border border-slate-200 bg-slate-50 text-xl font-black text-slate-900 shadow-sm hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700">{{ $digit }}</button>
                @endforeach
                <button type="button" @click="calculateResult()" class="row-span-2 min-h-16 rounded-xl sm:min-h-14 bg-green-500 text-xl font-black text-white shadow-sm hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500">=</button>

                <button type="button" @click="inputCalcDigit('0')" class="min-h-16 rounded-xl sm:min-h-14 border border-slate-200 bg-slate-50 text-xl font-black text-slate-900 shadow-sm hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700">0</button>
                <button type="button" @click="inputCalcDigit('.')" class="min-h-16 rounded-xl sm:min-h-14 border border-slate-200 bg-slate-50 text-xl font-black text-slate-900 shadow-sm hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700">.</button>
                <button type="button" @click="applyPercent(100)" class="min-h-16 rounded-xl sm:min-h-14 bg-blue-500 text-xl font-black text-white shadow-sm hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">%</button>
            </div>
        </div>
    </div>

    {{-- Keep the admin content scroller stable across form submissions (save/edit/delete) --}}
    <script nonce="{{ $cspNonce }}">
        (function () {
            var KEY = 'adminScrollPos';
            var saved = null;
            try { saved = JSON.parse(sessionStorage.getItem(KEY)); } catch (e) {}
            var main = document.querySelector('main');
            // Restore only on the same route (form redirects come back to the same page)
            if (saved && saved.path === location.pathname && main) {
                var restore = function () { main.scrollTop = saved.top || 0; };
                restore();
                // Content can settle after first paint — re-apply once fully loaded
                window.addEventListener('load', restore);
            }
            try { sessionStorage.removeItem(KEY); } catch (e) {}
            window.addEventListener('pagehide', function () {
                var m = document.querySelector('main');
                if (!m) return;
                try { sessionStorage.setItem(KEY, JSON.stringify({ path: location.pathname, top: m.scrollTop })); } catch (e) {}
            });
        })();
    </script>
</body>
</html>
