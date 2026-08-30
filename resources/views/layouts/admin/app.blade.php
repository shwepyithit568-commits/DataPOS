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

        // Restrained brand accent (T8): the active sidebar link follows the
        // store's theme primary color. Semantic (danger/success/warning) colors
        // stay system-controlled — branding never overrides operational signals.
        $headSetting = ($headStore ?? null)?->setting;
        $adminAccent = $headSetting?->theme_primary_color
            ?: ($headSetting
                ? \App\Themes\ThemeRegistry::get($headSetting->theme_preset)->primaryColor()
                : '#7c3aed');
    @endphp
    @vite(['resources/css/admin.css', 'resources/js/app-admin.js'])
    <style>
        :root { --admin-accent: {{ $adminAccent }}; }
        /* Restrained: only the active sidebar nav link uses the brand accent */
        aside a.bg-violet-600,
        aside a.bg-violet-600:hover {
            background-color: var(--admin-accent) !important;
        }
        @media (min-width: 1024px) {
            aside.lg\:w-20 {
                transition: width 220ms cubic-bezier(0.4, 0, 0.2, 1), box-shadow 220ms ease;
            }
            aside.lg\:w-20:hover {
                width: 18rem !important;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
                z-index: 40;
            }
            aside.lg\:w-20:hover .lg\:hidden {
                display: block !important;
            }
            aside.lg\:w-20:hover span[x-show="!sidebarCollapsed"] {
                display: flex !important;
            }
            aside.lg\:w-20:hover .lg\:justify-center {
                justify-content: flex-start !important;
            }
            aside.lg\:w-20:hover button.lg\:justify-center {
                justify-content: space-between !important;
            }
        }
    </style>
    {{-- Preload fonts so text renders without font-swap CLS. --}}
    <link rel="preload" as="font" type="font/woff2" crossorigin href="{{ Vite::asset('resources/assets/fonts/Roboto-Regular.woff2') }}">
    <link rel="preload" as="font" type="font/ttf" crossorigin href="{{ Vite::asset('resources/assets/fonts/NotoSansMyanmar/NotoSansMyanmar-Regular.ttf') }}">
    <link rel="preload" as="font" type="font/woff2" crossorigin href="{{ Vite::asset('resources/assets/fonts/Outfit-Regular.woff2') }}">
    <link rel="icon" type="{{ $adminFaviconPath && str_ends_with($adminFaviconPath, '.webp') ? 'image/webp' : ($adminFaviconPath ? 'image/png' : 'image/x-icon') }}" href="{{ $adminFaviconHref }}">
    <link rel="apple-touch-icon" href="{{ $appleTouchHref }}">
    <meta name="vapid-public-key" content="{{ config('webpush.vapid.public_key') }}">
    {{-- Match the storefront PWA theme so the browser status bar is light blue here too (no reliance on the edge-cached manifest) --}}
    <meta name="theme-color" content="#38bdf8">
    <script nonce="{{ $cspNonce }}">
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <x-currency-js-init :store="$headStore ?? null" />
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
        sidebarHovered: false,
        sidebarHoverTimer: null,
        viewportLg: window.innerWidth >= 1024,
        darkMode: localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches),
        onSidebarMouseEnter() {
            if (!this.viewportLg || !this.sidebarCollapsed) return;
            if (this.sidebarHoverTimer) clearTimeout(this.sidebarHoverTimer);
            this.sidebarHovered = true;
        },
        onSidebarMouseLeave() {
            if (!this.viewportLg || !this.sidebarCollapsed) return;
            this.sidebarHoverTimer = setTimeout(() => {
                this.sidebarHovered = false;
                this.closeGroups();
            }, 180);
        },
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
            this.sidebarHovered = false;
            localStorage.setItem('adminSidebar', 'collapsed');
            this.$dispatch('admin-sidebar-collapsed');
        },
        expandSidebar() {
            this.sidebarCollapsed = false;
            this.sidebarHovered = false;
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
            posOpen: {{ (request()->routeIs('pos.index', 'pos.closing.*', 'pos.returns.*', 'pos.buybacks.*', 'store.admin.eload.*') || (request()->is('store/*/pos', 'store/*/pos/', 'store/*/pos/closing*', 'store/*/pos/returns*', 'store/*/pos/buy-back*') && !request()->is('store/*/admin/*'))) ? 'true' : 'false' }},
            inventoryOpen: {{ (((Str::contains($currentPath, 'products') && !Str::contains($currentPath, 'web-products')) || Str::contains($currentPath, ['admin/categories', 'brands', 'variant-presets', 'pos/opening-stock', 'pos/adjustments', 'pos/reconciliation', 'stock-count', 'stock-ledger', 'price-wizard', 'barcode', 'warranty', 'pos/reports/stock'])) && !Str::contains($currentPath, 'expense-categories')) ? 'true' : 'false' }},
            purchasingOpen: {{ Str::contains($currentPath, ['suppliers', 'pos/purchases', 'pos/transfers', 'warehouses']) ? 'true' : 'false' }},
            ecommerceOpen: {{ Str::contains($currentPath, ['orders', 'reviews', 'banners', 'blog', 'glass-finder', 'push', 'promotions', 'web-products']) ? 'true' : 'false' }},
            customersOpen: {{ Str::contains($currentPath, ['customers', 'wholesale', 'membership']) ? 'true' : 'false' }},
            serviceOpen: {{ Str::contains($currentPath, ['repairs', 'service-jobs', 'spare-parts', 'service-settings']) ? 'true' : 'false' }},
            financeOpen: {{ Str::contains($currentPath, ['expenses', 'expense-categories', 'receivables', 'payables', 'profit-loss', 'transactions']) ? 'true' : 'false' }},
            reportsOpen: {{ ((Str::contains($currentPath, ['pos/reports/sales', 'pos/reports/cash', 'pos/reports/services', 'sales-analytics', 'inventory-valuation', 'debt-aging', 'aging-report'])) && !Str::contains($currentPath, 'pos/reports/stock')) ? 'true' : 'false' }},
            securityOpen: {{ Str::contains($currentPath, ['security', 'roles', 'users', 'audit-logs']) ? 'true' : 'false' }},
            maintenanceOpen: {{ Str::contains($currentPath, ['alerts', 'database', 'backups', 'pilot-import', 'import-history', 'admin/sync']) ? 'true' : 'false' }},
            setupOpen: {{ (Str::contains($currentPath, ['settings', 'branches', 'printers', 'vouchers', 'exchange-rates']) && !Str::contains($currentPath, 'service-settings')) ? 'true' : 'false' }},

            closeGroups() {
                this.posOpen = false;
                this.inventoryOpen = false;
                this.purchasingOpen = false;
                this.ecommerceOpen = false;
                this.customersOpen = false;
                this.serviceOpen = false;
                this.financeOpen = false;
                this.reportsOpen = false;
                this.setupOpen = false;
                this.securityOpen = false;
                this.maintenanceOpen = false;
            },
            activeHoverGroup: null,
            hoverTimer: null,
            setHoverGroup(name) {
                if (!this.viewportLg) return;
                if (this.hoverTimer) clearTimeout(this.hoverTimer);
                this.activeHoverGroup = name;
            },
            clearHoverGroup() {
                if (!this.viewportLg) return;
                if (this.hoverTimer) clearTimeout(this.hoverTimer);
                this.hoverTimer = setTimeout(() => {
                    this.activeHoverGroup = null;
                }, 120);
            },
            cancelHoverTimer() {
                if (this.hoverTimer) clearTimeout(this.hoverTimer);
            },
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
        class="fixed inset-y-0 left-0 z-30 w-72 bg-white dark:bg-slate-950 text-slate-800 dark:text-slate-100 transition-all duration-300 ease-in-out lg:static lg:translate-x-0 flex flex-col shadow-lg border-r border-slate-200/80 dark:border-slate-800/80 pb-[env(safe-area-inset-bottom)] lg:overflow-visible">

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

        <nav class="flex-1 px-3 py-4 space-y-1.5 overflow-y-auto lg:overflow-visible text-sm" aria-label="{{ __('messages.admin_navigation') }}">
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
                @php $isThemeGovernance = request()->is('admin/theme-governance*'); @endphp
                <div>
                    <x-admin.nav-link variant="main"
                        :href="route('admin.theme-governance.index')"
                        route-name="admin.theme-governance.index"
                        :active="$isThemeGovernance"
                        :label="__('messages.theme_governance')">
                        <x-slot:icon>
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>
                </div>
            @endif

            @if ($canAccessStaffTools)
                <x-admin.nav-group name="pos" :label="__('messages.sidebar_pos_group')" icon-class="bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300">
                    <x-slot:icon>
                        {{-- Cash register / POS terminal icon --}}
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M4 5h16v5H4V5Zm2 5v9h12v-9M7 12h2m-2 4h2m5-4h3m-3 4h3"/></svg>
                    </x-slot:icon>

                    @php $isPosHome = request()->routeIs('pos.index') || ((request()->is('store/*/pos') || request()->is('store/*/pos/')) && !request()->is('store/*/admin/*')); @endphp
                    <x-admin.nav-link :href="route('pos.index', $storeRouteParams)" route-name="pos.index" :active="$isPosHome" :label="__('messages.pos_sale')">
                        <x-slot:icon>
                            {{-- Credit-card / checkout icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5h16v14H4V5Zm0 6h16M7 15h4"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    @php $isPosClosing = request()->routeIs('pos.closing.*') || (request()->is('store/*/pos/closing*') && !request()->is('store/*/admin/*')); @endphp
                    <x-admin.nav-link :href="route('pos.closing.index', $storeRouteParams)" route-name="pos.closing.index" :active="$isPosClosing" :label="__('messages.closing_title')">
                        <x-slot:icon>
                            {{-- Clipboard-check icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 9 2 2 4-4"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    @php $isSalesReturns = request()->routeIs('pos.returns.*') || (request()->is('store/*/pos/returns*') && !request()->is('store/*/admin/*')); @endphp
                    <x-admin.nav-link :href="route('pos.returns.index', $storeRouteParams)" route-name="pos.returns.index" :active="$isSalesReturns" :label="__('messages.sidebar_sales_returns')">
                        <x-slot:icon>
                            {{-- Return / rotate-left icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l-4-4m0 0l4-4m-4 4h11a4 4 0 010 8h-1"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    @php $isBuyBack = request()->routeIs('pos.buybacks.*') || (request()->is('store/*/pos/buy-back*') && !request()->is('store/*/admin/*')); @endphp
                    <x-admin.nav-link :href="route('pos.buybacks.index', $storeRouteParams)" route-name="pos.buybacks.index" :active="$isBuyBack" :label="__('messages.sidebar_buy_back')">
                        <x-slot:icon>
                            {{-- Undo / buy-back icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12a9 9 0 109-9m-9 9h9m0 0V3"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    @if (store_can('operations.eload', $activeStore))
                        @php $isEload = request()->routeIs('store.admin.eload.*') || request()->is('store/*/admin/eload*'); @endphp
                        <x-admin.nav-link :href="route('store.admin.eload.index', $storeRouteParams)" route-name="store.admin.eload.index" :active="$isEload" :label="__('messages.sidebar_eload')">
                            <x-slot:icon>
                                {{-- Phone / topup signal icon --}}
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2zM12 7v4m-2-2h4"/></svg>
                            </x-slot:icon>
                        </x-admin.nav-link>
                    @endif
                </x-admin.nav-group>

                <x-admin.nav-group name="inventory" :label="__('messages.sidebar_inventory')" icon-class="bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300"><!-- SIDEBAR_MARKER_2026 -->
                    <x-slot:icon>
                        {{-- Cubes / package icon --}}
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 8.5 12 3 3 8.5m18 0-9 5.5m9-5.5V16l-9 5.5M3 8.5l9 5.5M3 8.5V16l9 5.5m0-7.5v7.5"/></svg>
                    </x-slot:icon>

                    @php $isMasterData = request()->is('store/*/admin/products/master-data*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.products.master-data', $storeRouteParams)" route-name="store.admin.products.master-data" :active="$isMasterData" :label="__('messages.master_data')">
                        <x-slot:icon>
                            {{-- Layers icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3l9 5-9 5-9-5 9-5Zm-9 10 9 5 9-5M3 17l9 5 9-5"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    @php $isProducts = request()->is('store/*/admin/products*') && ! request()->is('store/*/admin/products/import') && ! request()->is('store/*/admin/products/master-data*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.products.index', $storeRouteParams)" route-name="store.admin.products.index" :active="$isProducts" :label="__('messages.products')">
                        <x-slot:icon>
                            {{-- Shopping-bag icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 8h12l-1 12H7L6 8Zm3 0a3 3 0 0 1 6 0"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    @if (store_can('catalog.barcode_printing', $activeStore))
                        @php $isBarcode = request()->is('store/*/admin/barcode*'); @endphp
                        <x-admin.nav-link :href="route('store.admin.barcode.index', $storeRouteParams)" route-name="store.admin.barcode.index" :active="$isBarcode" :label="__('messages.sidebar_barcode')">
                            <x-slot:icon>
                                {{-- Barcode scan icon --}}
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-16v16M4 4v16m4-16v16m8-16v16"/></svg>
                            </x-slot:icon>
                        </x-admin.nav-link>
                    @endif

                    @if (store_can('catalog.price_wizard', $activeStore))
                        @php $isPriceWizard = request()->is('store/*/admin/price-wizard*'); @endphp
                        <x-admin.nav-link :href="route('store.admin.price_wizard.index', $storeRouteParams)" route-name="store.admin.price_wizard.index" :active="$isPriceWizard" :label="__('messages.sidebar_price_wizard')">
                            <x-slot:icon>
                                {{-- Wand / Magic calculator icon --}}
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.64 3.64-1.28-1.28a1.21 1.21 0 0 0-1.72 0L2.36 18.64a1.21 1.21 0 0 0 0 1.72l1.28 1.28a1.2 1.2 0 0 0 1.72 0L21.64 5.36a1.2 1.2 0 0 0 0-1.72Z"/><path d="m14 7 3 3"/><path d="M5 6v4"/><path d="M19 14v4"/><path d="M10 2v2"/><path d="M7 8H3"/><path d="M21 16h-4"/><path d="M11 3H9"/></svg>
                            </x-slot:icon>
                        </x-admin.nav-link>
                    @endif

                    @if (store_can('service.warranty_tracking', $activeStore))
                        @php $isWarranty = request()->is('store/*/admin/warranty*'); @endphp
                        <x-admin.nav-link :href="route('store.admin.warranty.index', $storeRouteParams)" route-name="store.admin.warranty.index" :active="$isWarranty" :label="__('messages.sidebar_warranty')">
                            <x-slot:icon>
                                {{-- Shield check warranty icon --}}
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                            </x-slot:icon>
                        </x-admin.nav-link>
                    @endif

                    @php $isStockLedger = request()->is('store/*/admin/stock-ledger*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.stock_ledger.index', $storeRouteParams)" route-name="store.admin.stock_ledger.index" :active="$isStockLedger" :label="__('messages.sidebar_stock_ledger')">
                        <x-slot:icon>
                            {{-- Timeline / Ledger / Bin Card icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20v-6M6 20V10M18 20V4"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    @php $isStockBalance = request()->is('store/*/pos/reports/stock*'); @endphp
                    <x-admin.nav-link :href="route('pos.reports.stock', $storeRouteParams)" route-name="pos.reports.stock" :active="$isStockBalance" :label="__('messages.sidebar_stock_balance')">
                        <x-slot:icon>
                            {{-- Package / stock balance box icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7.5 12 3 4 7.5M20 7.5 12 12m8-4.5v9l-8 4.5M12 12 4 7.5M12 12v9M4 7.5v9l8 4.5"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    @php $isStockCount = request()->is('store/*/admin/stock-count*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.stock_count.index', $storeRouteParams)" route-name="store.admin.stock_count.index" :active="$isStockCount" :label="__('messages.sidebar_stock_count')">
                        <x-slot:icon>
                            {{-- Clipboard checklist stock count icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="m9 14 2 2 4-4"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    @php $isStockAdjustments = request()->is('store/*/pos/adjustments*'); @endphp
                    <x-admin.nav-link :href="route('pos.adjustments.index', $storeRouteParams)" route-name="pos.adjustments.index" :active="$isStockAdjustments" :label="__('messages.sidebar_stock_adjustments')">
                        <x-slot:icon>
                            {{-- Adjustments icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.7 6.3a4 4 0 0 0-5.4 5.4L4 17v3h3l5.3-5.3a4 4 0 0 0 5.4-5.4l-2.4 2.4-3-3 2.4-2.4Z"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    @php $isReconciliation = request()->is('store/*/pos/reconciliation*'); @endphp
                    <x-admin.nav-link :href="route('pos.reconciliation.index', $storeRouteParams)" route-name="pos.reconciliation.index" :active="$isReconciliation" :label="__('messages.sidebar_stock_reconciliation')">
                        <x-slot:icon>
                            {{-- Balance scales icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="M7 21h10"/><path d="M12 3v18"/><path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    @php $isOpeningStock = request()->is('store/*/pos/opening-stock*'); @endphp
                    <x-admin.nav-link :href="route('pos.opening-stock.index', $storeRouteParams)" route-name="pos.opening-stock.index" :active="$isOpeningStock" :label="__('messages.sidebar_opening_stock')">
                        <x-slot:icon>
                            {{-- Package box open icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m8 11 4 4 4-4"/><path d="M21 8.5V17a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8.5"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    @php $isImport = request()->is('store/*/admin/products/import'); @endphp
                    <x-admin.nav-link :href="route('store.admin.products.import', $storeRouteParams)" route-name="store.admin.products.import" :active="$isImport" :label="__('messages.product_import')">
                        <x-slot:icon>
                            {{-- Download/import icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v10m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>
                </x-admin.nav-group>

                <x-admin.nav-group name="purchasing" :label="__('messages.sidebar_purchasing')" icon-class="bg-orange-100 text-orange-700 dark:bg-orange-500/15 dark:text-orange-300">
                    <x-slot:icon>
                        {{-- Truck / shipping icon --}}
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M1 3h15v13H1zM16 8h4l3 3v5h-7zM5.5 18a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm11 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z"/></svg>
                    </x-slot:icon>

                    @php $isSuppliers = request()->is('store/*/admin/suppliers*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.suppliers.index', $storeRouteParams)" route-name="store.admin.suppliers.index" :active="$isSuppliers" :label="__('messages.sidebar_suppliers')">
                        <x-slot:icon>
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11a4 4 0 1 0-8 0m8 0a4 4 0 1 1-8 0m8 0c3 1 5 3 5 6v1H3v-1c0-3 2-5 5-6"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>
                    @php $isReturns = request()->is('store/*/pos/purchases/returns*'); @endphp
                    @php $isPayables = request()->is('store/*/pos/purchases/payables*') || request()->is('store/*/admin/payables*'); @endphp
                    @php $isPurchases = (request()->is('store/*/admin/purchases*') || request()->is('store/*/pos/purchases*')) && ! $isReturns && ! $isPayables; @endphp
                    <x-admin.nav-link :href="route('pos.purchases.index', $storeRouteParams)" route-name="pos.purchases.index" :active="$isPurchases" :label="__('messages.sidebar_purchases')">
                        <x-slot:icon>
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>
                    <x-admin.nav-link :href="route('pos.purchases.returns', $storeRouteParams)" route-name="pos.purchases.returns" :active="$isReturns" :label="__('messages.sidebar_purchase_returns')">
                        <x-slot:icon>
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l-4-4m0 0l4-4m-4 4h11a4 4 0 010 8h-1"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>
                    <x-admin.nav-link :href="route('pos.purchases.payables', $storeRouteParams)" route-name="pos.purchases.payables" :active="$isPayables" :label="__('messages.sidebar_payables')">
                        <x-slot:icon>
                            {{-- Receipt/money icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 7h20M2 7v10a2 2 0 002 2h16a2 2 0 002-2V7M10 11h4M10 15h2"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>
                    @if (store_can('inventory.transfers', $activeStore))
                        @php $isTransfers = request()->is('store/*/pos/transfers*'); @endphp
                        <x-admin.nav-link :href="route('pos.transfers.index', $storeRouteParams)" route-name="pos.transfers.index" :active="$isTransfers" :label="__('messages.sidebar_transfers')">
                            <x-slot:icon>
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                            </x-slot:icon>
                        </x-admin.nav-link>
                    @endif
                    @if (store_can('operations.warehouses', $activeStore))
                        @php $isWarehouses = request()->is('store/*/admin/warehouses*'); @endphp
                        <x-admin.nav-link :href="route('store.admin.warehouses.index', $storeRouteParams)" route-name="store.admin.warehouses.index" :active="$isWarehouses" :label="__('messages.sidebar_warehouses')">
                            <x-slot:icon>
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </x-slot:icon>
                        </x-admin.nav-link>
                    @endif
                </x-admin.nav-group>

                <x-admin.nav-group name="ecommerce" :label="__('messages.sidebar_ecommerce')" icon-class="bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                    <x-slot:icon>
                        {{-- Storefront icon --}}
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M3 10.5 5 5h14l2 5.5M4 10.5V19a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8.5M3 10.5h18M8 21v-6h8v6"/></svg>
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
                            {{-- Document icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h6M9 9h6M9 13h6M7 3h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/></svg>
                        </x-slot:icon>
                        @if ($pendingOrderCount > 0)
                            <x-slot:badge>
                                <span data-pending-order-count="{{ $pendingOrderCount }}" class="bg-red-500 text-white text-xs px-1.5 rounded-full font-bold max-w-[3rem] truncate">{{ $pendingOrderCount }}</span>
                            </x-slot:badge>
                        @endif
                    </x-admin.nav-link>

                    @php $isWebProducts = request()->is('store/*/admin/web-products*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.web_products.index', $storeRouteParams)" route-name="store.admin.web_products.index" :active="$isWebProducts" :label="__('messages.sidebar_web_products')">
                        <x-slot:icon>
                            {{-- Globe / Storefront web icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    @php $isPromotions = request()->is('store/*/admin/promotions*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.promotions.index', $storeRouteParams)" route-name="store.admin.promotions.index" :active="$isPromotions" :label="__('messages.sidebar_promotions')">
                        <x-slot:icon>
                            {{-- Tag / Coupon icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    @if (store_can('storefront.reviews', $activeStore))
                        @php $isReviews = request()->is('store/*/admin/reviews*'); @endphp
                        <x-admin.nav-link :href="url('/store/' . ($activeStore->slug ?? '') . '/admin/reviews')" :active="$isReviews" :label="__('messages.product_reviews')" route-name="store.admin.reviews.index">
                            <x-slot:icon>
                                {{-- Star icon --}}
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1L12 16.9 6.6 19.8l1-6.1-4.4-4.3 6.1-.9L12 3Z"/></svg>
                            </x-slot:icon>
                        </x-admin.nav-link>
                    @endif

                    @php $isBanners = request()->is('store/*/admin/banners*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.banners.index', $storeRouteParams)" route-name="store.admin.banners.index" :active="$isBanners" :label="__('messages.home_banners')">
                        <x-slot:icon>
                            {{-- Banner flag icon -- pole with waving pennant --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 21V3m0 0h13l-2 4 2 4H5"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    @if (store_can('storefront.blog', $activeStore))
                        @php $isBlog = request()->is('store/*/admin/blog*'); @endphp
                        <x-admin.nav-link :href="route('store.admin.blog.index', $storeRouteParams)" :active="$isBlog" :label="__('messages.blog_posts')" route-name="store.admin.blog.index">
                            <x-slot:icon>
                                {{-- Document-lines icon --}}
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5h16v14H4V5Zm4 4h8M8 13h8M8 17h5"/></svg>
                            </x-slot:icon>
                        </x-admin.nav-link>
                    @endif

                    @if (store_can('storefront.glass_finder', $activeStore))
                        @php $isGlass = request()->is('store/*/admin/glass-finder*'); @endphp
                        <x-admin.nav-link :href="route('store.admin.glass-finder.index', $storeRouteParams)" route-name="store.admin.glass-finder.index" :active="$isGlass" :label="__('messages.glass_finder')">
                            <x-slot:icon>
                                {{-- Magnifier icon --}}
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.3-4.3M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z"/></svg>
                            </x-slot:icon>
                        </x-admin.nav-link>
                    @endif

                    @php $isPush = request()->is('store/*/admin/push') || request()->is('store/*/admin/push?*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.push.index', $storeRouteParams)" route-name="store.admin.push.index" :active="$isPush" :label="__('messages.sidebar_web_push')">
                        <x-slot:icon>
                            {{-- Bell icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    @php $isPushHistory = request()->is('store/*/admin/push/history*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.push.history', $storeRouteParams)" route-name="store.admin.push.history" :active="$isPushHistory" :label="__('messages.sidebar_push_history')">
                        <x-slot:icon>
                            {{-- Clock icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>
                </x-admin.nav-group>

                <x-admin.nav-group name="customers" :label="__('messages.sidebar_customers')" icon-class="bg-teal-100 text-teal-700 dark:bg-teal-500/15 dark:text-teal-300">
                    <x-slot:icon>
                        {{-- Users icon --}}
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M16 11a4 4 0 1 0-8 0m8 0a4 4 0 1 1-8 0m8 0c3 1 5 3 5 6v1H3v-1c0-3 2-5 5-6"/></svg>
                    </x-slot:icon>

                    @php $isCustomers = request()->is('store/*/admin/customers*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.customers.index', $storeRouteParams)" route-name="store.admin.customers.index" :active="$isCustomers" :label="__('messages.sidebar_customer_directory')">
                        <x-slot:icon>
                            {{-- Users icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11a4 4 0 1 0-8 0m8 0a4 4 0 1 1-8 0m8 0c3 1 5 3 5 6v1H3v-1c0-3 2-5 5-6"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    @if (store_can('commerce.customer_debt', $activeStore))
                        @php $isReceivables = request()->is('store/*/admin/receivables*'); @endphp
                        <x-admin.nav-link :href="route('store.admin.receivables.index', $storeRouteParams)" route-name="store.admin.receivables.index" :active="$isReceivables" :label="__('messages.sidebar_receivables')">
                            <x-slot:icon>
                                {{-- Receipt / debt collection icon --}}
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                            </x-slot:icon>
                        </x-admin.nav-link>
                    @endif

                    @if (store_can('commerce.wholesale_pricing', $activeStore))
                        @php $isWholesale = request()->is('store/*/admin/wholesale*'); @endphp
                        <x-admin.nav-link :href="route('store.admin.wholesale.applications.index', $storeRouteParams)" route-name="store.admin.wholesale.applications.index" :active="$isWholesale" :label="__('messages.wholesale_applications')">
                            <x-slot:icon>
                                {{-- Envelope/document-check icon --}}
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6V5a2 2 0 0 1 2-2h0a2 2 0 0 1 2 2v1m-9 4h14M5 6h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z"/></svg>
                            </x-slot:icon>
                        </x-admin.nav-link>
                    @endif

                    @if (store_can('commerce.loyalty_points', $activeStore))
                        @php $isMembership = request()->is('store/*/admin/membership*'); @endphp
                        <x-admin.nav-link :href="route('store.admin.membership.index', $storeRouteParams)" route-name="store.admin.membership.index" :active="$isMembership" :label="__('messages.sidebar_membership')">
                            <x-slot:icon>
                                {{-- VIP Crown / Medal Icon --}}
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2 4 3 12h14l3-12-6 7-4-7-4 7-6-7zm3 16h14"/></svg>
                            </x-slot:icon>
                        </x-admin.nav-link>
                    @endif
                </x-admin.nav-group>

                @if (store_can('service.repair_jobs', $activeStore))
                    <x-admin.nav-group name="service" :label="__('messages.sidebar_service')" icon-class="bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300">
                        <x-slot:icon>
                            {{-- Wrench / build icon --}}
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L4 17v3h3l5.3-5.3a4 4 0 0 0 5.4-5.4l-2.4 2.4-3-3 2.4-2.4Z"/></svg>
                        </x-slot:icon>

                        @php $isRepairs = request()->is('store/*/admin/repairs*') || request()->is('store/*/admin/service-jobs*'); @endphp
                        <x-admin.nav-link :href="route('store.admin.repairs.index', $storeRouteParams)" route-name="store.admin.repairs.index" :active="$isRepairs" :label="__('messages.sidebar_repair_center')">
                            <x-slot:icon>
                                {{-- Wrench icon --}}
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.7 6.3a4 4 0 0 0-5.4 5.4L4 17v3h3l5.3-5.3a4 4 0 0 0 5.4-5.4l-2.4 2.4-3-3 2.4-2.4Z"/></svg>
                            </x-slot:icon>
                        </x-admin.nav-link>
                        @php $isSpareParts = request()->is('store/*/admin/spare-parts*'); @endphp
                        <x-admin.nav-link :href="route('store.admin.spare_parts.index', $storeRouteParams)" route-name="store.admin.spare_parts.index" :active="$isSpareParts" :label="__('messages.sidebar_spare_parts')">
                            <x-slot:icon>
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                            </x-slot:icon>
                        </x-admin.nav-link>
                        @php $isServiceSettings = request()->is('store/*/admin/service-settings*'); @endphp
                        <x-admin.nav-link :href="route('store.admin.service_settings.index', $storeRouteParams)" route-name="store.admin.service_settings.index" :active="$isServiceSettings" :label="__('messages.sidebar_service_settings')">
                            <x-slot:icon>
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                            </x-slot:icon>
                        </x-admin.nav-link>
                    </x-admin.nav-group>
                @endif

                <x-admin.nav-group name="finance" :label="__('messages.sidebar_finance')" icon-class="bg-green-100 text-green-700 dark:bg-green-500/15 dark:text-green-300">
                    <x-slot:icon>
                        {{-- Banknote icon --}}
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/></svg>
                    </x-slot:icon>

                    @php $isProfitLoss = request()->is('store/*/admin/profit-loss*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.profit_loss.index', $storeRouteParams)" route-name="store.admin.profit_loss.index" :active="$isProfitLoss" :label="__('messages.sidebar_profit_loss')">
                        <x-slot:icon>
                            {{-- Trending chart profit / loss icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    @php $isExpensesAdmin = request()->is('store/*/admin/expenses*') && !request()->is('store/*/admin/expense-categories*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.expenses.index', $storeRouteParams)" route-name="store.admin.expenses.index" :active="$isExpensesAdmin" :label="__('messages.sidebar_expenses')">
                        <x-slot:icon>
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    @php $isExpenseCategories = request()->is('store/*/admin/expense-categories*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.expense_categories.index', $storeRouteParams)" route-name="store.admin.expense_categories.index" :active="$isExpenseCategories" :label="__('messages.sidebar_expense_categories')">
                        <x-slot:icon>
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h7"/><circle cx="17" cy="18" r="3"/><path d="M17 17v2m-1-1h2"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    @php $isTransactions = request()->is('store/*/admin/transactions*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.transactions.index', $storeRouteParams)" route-name="store.admin.transactions.index" :active="$isTransactions" :label="__('messages.sidebar_transactions')">
                        <x-slot:icon>
                            {{-- Bank / Currency Exchange / Transfer Icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>
                </x-admin.nav-group>

                <x-admin.nav-group name="reports" :label="__('messages.sidebar_reports')" icon-class="bg-cyan-100 text-cyan-700 dark:bg-cyan-500/15 dark:text-cyan-300">
                    <x-slot:icon>
                        {{-- Bar-chart icon --}}
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M4 20V10m6 10V4m6 16v-7m4 7H2"/></svg>
                    </x-slot:icon>

                    {{-- 1. POS Sales Report --}}
                    @php $isPosSales = request()->routeIs('pos.reports.sales*'); @endphp
                    <x-admin.nav-link :href="route('pos.reports.sales', $storeRouteParams)" route-name="pos.reports.sales" :active="$isPosSales" :label="__('messages.reports_sales')">
                        <x-slot:icon>
                            {{-- Receipt / Bill icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1Z"/><path d="M14 8H8"/><path d="M16 12H8"/><path d="M13 16H8"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    {{-- 2. Sales Analytics & Deep Charts --}}
                    @php $isSalesAnalytics = request()->routeIs('store.admin.sales_analytics.*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.sales_analytics.index', $storeRouteParams)" route-name="store.admin.sales_analytics.index" :active="$isSalesAnalytics" :label="__('messages.sidebar_sales_analytics')">
                        <x-slot:icon>
                            {{-- Deep Analytics / Chart icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    {{-- 3. Cash Drawer Shift Report --}}
                    @php $isPosCash = request()->routeIs('pos.reports.cash*'); @endphp
                    <x-admin.nav-link :href="route('pos.reports.cash', $storeRouteParams)" route-name="pos.reports.cash" :active="$isPosCash" :label="__('messages.reports_cash')">
                        <x-slot:icon>
                            {{-- Cash Drawer / Vault icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    {{-- 4. Inventory Valuation Report --}}
                    @php $isInventoryValuation = request()->routeIs('store.admin.inventory_valuation.*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.inventory_valuation.index', $storeRouteParams)" route-name="store.admin.inventory_valuation.index" :active="$isInventoryValuation" :label="__('messages.sidebar_inventory_valuation')">
                        <x-slot:icon>
                            {{-- Calculator / Valuation icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    {{-- 6. Debt Aging Analysis Report --}}
                    @php $isDebtAging = request()->routeIs('store.admin.debt_aging.*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.debt_aging.index', $storeRouteParams)" route-name="store.admin.debt_aging.index" :active="$isDebtAging" :label="__('messages.sidebar_aging_report')">
                        <x-slot:icon>
                            {{-- Clock / Overdue debt icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    {{-- 7. Service & Repair Report --}}
                    @php $isPosServices = request()->routeIs('pos.reports.services*'); @endphp
                    <x-admin.nav-link :href="route('pos.reports.services', $storeRouteParams)" route-name="pos.reports.services" :active="$isPosServices" :label="__('messages.sidebar_report_services')">
                        <x-slot:icon>
                            {{-- Wrench / Tool icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>
                </x-admin.nav-group>

                <x-admin.nav-group name="security" :label="__('messages.sidebar_security')" icon-class="bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300">
                    <x-slot:icon>
                        {{-- Shield icon --}}
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M12 3l8 3v6c0 4.5-3.2 7.8-8 9-4.8-1.2-8-4.5-8-9V6l8-3Z"/></svg>
                    </x-slot:icon>

                    {{-- Staff Roles & Granular Permissions --}}
                    @php $isStaffRoles = request()->routeIs('store.admin.roles.*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.roles.index', $storeRouteParams)" route-name="store.admin.roles.index" :active="$isStaffRoles" :label="__('messages.sidebar_roles')">
                        <x-slot:icon>
                            {{-- Key / Permissions icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 5 4 4"/><path d="M13 7 8.7 11.3a2 2 0 0 0-.58 1.23l-.8 4.7 4.7-.8a2 2 0 0 0 1.23-.58L17.5 11.5"/><circle cx="6" cy="6" r="3"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    @if ($canManageUsers)
                        @php $isUsers = request()->is('store/*/admin/users*'); @endphp
                        <x-admin.nav-link :href="route('store.admin.users.index', $storeRouteParams)" route-name="store.admin.users.index" :active="$isUsers" :label="__('messages.users')">
                            <x-slot:icon>
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11a4 4 0 1 0-8 0m8 0a4 4 0 1 1-8 0m8 0c3 1 5 3 5 6v1H3v-1c0-3 2-5 5-6"/></svg>
                            </x-slot:icon>
                        </x-admin.nav-link>
                    @endif

                    {{-- System Audit Trail Logs --}}
                    @php $isAuditLogs = request()->routeIs('store.admin.audit-logs.*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.audit-logs.index', $storeRouteParams)" route-name="store.admin.audit-logs.index" :active="$isAuditLogs" :label="__('messages.sidebar_audit_logs')">
                        <x-slot:icon>
                            {{-- Clipboard / Activity Log icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="M9 14h6"/><path d="M9 18h6"/><path d="M9 10h6"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>
                </x-admin.nav-group>

                <x-admin.nav-group name="maintenance" :label="__('messages.sidebar_maintenance')" icon-class="bg-slate-200 text-slate-600 dark:bg-slate-700/40 dark:text-slate-300">
                    <x-slot:icon>
                        {{-- Database icon --}}
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M4 6v12c0 1.7 3.6 3 8 3s8-1.3 8-3V6M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/></svg>
                    </x-slot:icon>

                    {{-- System Alert Center & Notifications --}}
                    @php $isAlerts = request()->routeIs('store.admin.alerts.*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.alerts.index', $storeRouteParams)" route-name="store.admin.alerts.index" :active="$isAlerts" :label="__('messages.sidebar_alerts')">
                        <x-slot:icon>
                            {{-- Bell / Alert icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    {{-- Database Tools & Optimizer --}}
                    @php $isDatabase = request()->routeIs('store.admin.database.*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.database.index', $storeRouteParams)" route-name="store.admin.database.index" :active="$isDatabase" :label="__('messages.sidebar_database')">
                        <x-slot:icon>
                            {{-- Database/Wrench icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    {{-- Offline Sync Manager --}}
                    @php $isSyncManager = request()->routeIs('store.admin.sync.*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.sync.index', $storeRouteParams)" route-name="store.admin.sync.index" :active="$isSyncManager" :label="__('messages.sync_manager')">
                        <x-slot:icon>
                            {{-- Refresh / Sync Arrow icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    @php $isBackups = request()->is('store/*/admin/backups*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.backups.index', $storeRouteParams)" route-name="store.admin.backups.index" :active="$isBackups" :label="__('messages.backups')">
                        <x-slot:icon>
                            {{-- Arrow-backup icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M13 6l6 6-6 6M19 12H5"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    @php $isPilotImport = request()->is('store/*/admin/pilot-import*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.pilot-import.index', $storeRouteParams)" route-name="store.admin.pilot-import.index" :active="$isPilotImport" :label="__('messages.pilot_import')">
                        <x-slot:icon>
                            {{-- Import/upload icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h10M4 12h10M4 17h6M15 8l5 5m0 0-5 5m5-5h-9"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>

                    @php $isImportHistory = request()->is('store/*/admin/import-history*'); @endphp
                    <x-admin.nav-link :href="route('store.admin.import-history.index', $storeRouteParams)" route-name="store.admin.import-history.index" :active="$isImportHistory" :label="__('messages.import_history')">
                        <x-slot:icon>
                            {{-- History icon --}}
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v5l3 2M4 4v5h5M5.5 15a7 7 0 1 0 .8-7.8L4 9"/></svg>
                        </x-slot:icon>
                    </x-admin.nav-link>
                </x-admin.nav-group>

                @if ($canManageSettings)
                <x-admin.nav-group name="setup" :label="__('messages.sidebar_setup')" icon-class="bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300">
                    <x-slot:icon>
                        {{-- Gear icon --}}
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm8.5 4a7 7 0 0 0-.1-1l2-1.5-2-3.5-2.4 1a8 8 0 0 0-1.7-1L16 3h-4l-.3 3a8 8 0 0 0-1.7 1l-2.4-1-2 3.5 2 1.5a7 7 0 0 0 0 2l-2 1.5 2 3.5 2.4-1a8 8 0 0 0 1.7 1l.3 3h4l.3-3a8 8 0 0 0 1.7-1l2.4 1 2-3.5-2-1.5c.1-.3.1-.7.1-1Z"/></svg>
                    </x-slot:icon>
                        @php
                            $isSettings = request()->routeIs('store.admin.settings.*') || request()->is('store/*/admin/settings*');
                            $isTheme = request()->routeIs('store.admin.theme.*') || (request()->routeIs('store.admin.settings.section') && request()->route('section') === 'appearance');
                        @endphp
                        <x-admin.nav-link :href="route('store.admin.theme.index', $storeRouteParams)" route-name="store.admin.theme.index" :active="$isTheme" label="Theme & Branding">
                            <x-slot:icon>
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.563-2.512 5.563-5.563C22 6.5 17.5 2 12 2Z"/></svg>
                            </x-slot:icon>
                        </x-admin.nav-link>
                        <x-admin.nav-link :href="route('store.admin.settings.edit', $storeRouteParams)" route-name="store.admin.settings.edit" :active="$isSettings && !$isTheme" :label="__('messages.settings_storefront_settings')">
                            <x-slot:icon>
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M7 7v10M12 7v10M17 7v10M4 17h16"/></svg>
                            </x-slot:icon>
                        </x-admin.nav-link>

                        @if (store_can('operations.branches', $activeStore))
                            @php $isBranches = request()->is('store/*/admin/branches*'); @endphp
                            <x-admin.nav-link :href="route('store.admin.branches.index', $storeRouteParams)" route-name="store.admin.branches.index" :active="$isBranches" :label="__('messages.sidebar_branches')">
                                <x-slot:icon>
                                    {{-- Branch / Location Multi-Store Icon --}}
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M3 7v1a3 3 0 0 0 6 0V7m0 1a3 3 0 0 0 6 0V7m0 1a3 3 0 0 0 6 0V7H3l2-4h14l2 4M5 21V10.85M19 21V10.85M9 21v-4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v4"/></svg>
                                </x-slot:icon>
                            </x-admin.nav-link>
                        @endif
                        @php $isPrinters = request()->is('store/*/admin/printers*'); @endphp
                        <x-admin.nav-link :href="route('store.admin.printers.index', $storeRouteParams)" route-name="store.admin.printers.index" :active="$isPrinters" :label="__('messages.sidebar_printers')">
                            <x-slot:icon>
                                {{-- Thermal Printer Icon --}}
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 9V3a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v6"/><rect x="6" y="14" width="12" height="8" rx="1"/></svg>
                            </x-slot:icon>
                        </x-admin.nav-link>
                        @php $isVouchers = request()->is('store/*/admin/vouchers*'); @endphp
                        <x-admin.nav-link :href="route('store.admin.vouchers.index', $storeRouteParams)" route-name="store.admin.vouchers.index" :active="$isVouchers" :label="__('messages.sidebar_vouchers')">
                            <x-slot:icon>
                                {{-- Voucher / Receipt Designer Icon --}}
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 6v12"/></svg>
                            </x-slot:icon>
                        </x-admin.nav-link>
                        @php $isExchangeRates = request()->is('store/*/admin/exchange-rates*'); @endphp
                        <x-admin.nav-link :href="route('store.admin.exchange_rates.index', $storeRouteParams)" route-name="store.admin.exchange_rates.index" :active="$isExchangeRates" :label="__('messages.sidebar_exchange_rates')">
                            <x-slot:icon>
                                {{-- Currency Exchange Icon --}}
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                            </x-slot:icon>
                        </x-admin.nav-link>
                </x-admin.nav-group>
                @endif
            @endif
        </nav>
    </aside>

    <div x-show="sidebarOpen" x-transition.opacity.duration.200ms @click="closeDrawer()"
        class="fixed inset-0 z-20 bg-black/30 lg:hidden" aria-hidden="true"></div>

    <div class="flex-1 flex flex-col overflow-hidden">
        @php
            $supportService = app(\App\Services\SupportAccessService::class);
            $isSupportActive = $supportService->isSupportModeActive();
            $supportReason = $supportService->getSupportReason();
        @endphp

        @if ($isSupportActive)
            <div class="bg-amber-500 text-slate-950 px-4 py-2 text-xs font-bold flex items-center justify-between shadow-sm z-30 shrink-0">
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-slate-950 text-amber-400 rounded text-[10px] uppercase tracking-wider font-extrabold">Support Mode Active</span>
                    <span>Super Admin Assisting Store &bull; Reason: <span class="font-normal italic">{{ $supportReason }}</span></span>
                </div>
                <form method="POST" action="{{ route('admin.support-mode.exit') }}" class="inline">
                    @csrf
                    <button type="submit" class="px-3 py-1 bg-slate-950 hover:bg-slate-800 text-white rounded text-xs font-bold transition">
                        Exit Support Mode &rarr;
                    </button>
                </form>
            </div>
        @endif

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
                @if ($hasStoreContext)
                    <x-sync-status-widget :store="$activeStore" />
                @endif

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
                            <svg class="h-4 w-4 shrink-0 text-slate-500 dark:text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/>
                                <path d="M21 3v5h-5"/>
                                <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/>
                                <path d="M3 21v-5h5"/>
                            </svg>
                            <span>{{ __('messages.reload_page') }}</span>
                        </button>
                        <button type="button" role="menuitem" @click="moreOpen = false; openCalculator()"
                            class="w-full flex items-center gap-2.5 px-3 min-h-11 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                            <svg class="h-4 w-4 shrink-0 text-blue-600 dark:text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <rect x="5" y="3" width="14" height="18" rx="2" stroke-width="2" />
                                <path stroke-linecap="round" stroke-width="2" d="M8 7h8M8 11h.01M12 11h.01M16 11h.01M8 15h.01M12 15h.01M16 15h.01" />
                            </svg>
                            {{ __('messages.calculator') }}
                        </button>
                        <button type="button" role="menuitem" @click="moreOpen = false; toggleDarkMode()"
                            class="w-full flex items-center gap-2.5 px-3 min-h-11 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                            <svg x-show="!darkMode" class="h-4 w-4 shrink-0 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.8A8.5 8.5 0 1111.2 3a6.5 6.5 0 009.8 9.8z" />
                            </svg>
                            <svg x-show="darkMode" class="h-4 w-4 shrink-0 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v2m0 14v2m9-9h-2M5 12H3m15.36-6.36-1.42 1.42M7.06 16.94l-1.42 1.42m12.72 0-1.42-1.42M7.06 7.06 5.64 5.64" />
                                <circle cx="12" cy="12" r="4" stroke-width="2" />
                            </svg>
                            <span x-text="darkMode ? 'Light Mode (အလင်း)' : 'Dark Mode (အမှောင်)'"></span>
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
                        aria-label="{{ __('messages.view_commerce') }}"
                        title="{{ __('messages.view_commerce') }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10.5 5 5h14l2 5.5M4 10.5V19a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8.5M3 10.5h18M8 21v-6h8v6" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 5v5.5m5-5.5v5.5M17 5v5.5" />
                        </svg>
                    </a>
                @endif

                <button @click="window.location.reload()" type="button"
                    class="hidden sm:inline-flex h-11 w-11 sm:h-10 sm:w-10 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white transition items-center justify-center focus:outline-none focus:ring-2 focus:ring-slate-400"
                    aria-label="{{ __('messages.reload_page') }}"
                    title="{{ __('messages.reload_page') }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/>
                        <path d="M21 3v5h-5"/>
                        <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/>
                        <path d="M3 21v-5h5"/>
                    </svg>
                </button>

                <button @click="openCalculator()" type="button"
                    class="h-11 w-11 sm:h-10 sm:w-10 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-500/15 dark:text-blue-300 dark:hover:bg-blue-500/25 transition inline-flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-blue-500"
                    aria-label="{{ __('messages.calculator') }}"
                    title="{{ __('messages.calculator') }}">
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

                {{-- User Profile Dropdown Menu --}}
                <div class="relative" x-data="{ userMenuOpen: false }" @click.outside="userMenuOpen = false" @keydown.escape.window="userMenuOpen = false">
                    <button type="button" @click="userMenuOpen = !userMenuOpen"
                        class="h-11 w-11 sm:h-10 sm:w-10 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-700/80 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 transition inline-flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-violet-500 border border-slate-200/80 dark:border-slate-600 shadow-sm flex-shrink-0"
                        :aria-expanded="userMenuOpen.toString()"
                        aria-haspopup="menu"
                        aria-label="{{ auth()->user()?->name ?? 'User Profile' }}">
                        <div class="h-7 w-7 rounded-lg bg-gradient-to-tr from-violet-600 to-indigo-500 text-white flex items-center justify-center font-bold text-xs shadow-sm">
                            @if(auth()->user()?->name)
                                {{ mb_substr(auth()->user()->name, 0, 1) }}
                            @else
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                            @endif
                        </div>
                    </button>

                    <div x-show="userMenuOpen"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 scale-95 transform"
                        x-transition:enter-end="opacity-100 scale-100 transform"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 scale-100 transform"
                        x-transition:leave-end="opacity-0 scale-95 transform"
                        x-cloak
                        class="absolute right-0 top-full z-50 mt-2 w-64 sm:w-72 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl shadow-slate-900/10 dark:border-slate-700 dark:bg-slate-900"
                        role="menu"
                        aria-label="{{ __('messages.admin_panel') }}">

                        {{-- User Header Details --}}
                        <div class="px-3 py-2.5 bg-slate-50 dark:bg-slate-800/60 rounded-xl mb-1 border border-slate-100 dark:border-slate-800">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 shrink-0 rounded-xl bg-gradient-to-tr from-violet-600 to-indigo-500 text-white flex items-center justify-center font-bold text-sm shadow">
                                    @if(auth()->user()?->name)
                                        {{ mb_substr(auth()->user()->name, 0, 1) }}
                                    @else
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                                            <circle cx="12" cy="7" r="4"/>
                                        </svg>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-bold text-slate-800 dark:text-slate-100 truncate">
                                        {{ auth()->user()?->name ?? 'User' }}
                                    </p>
                                    <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                                        @if(auth()->user()?->isPlatformOwner())
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-violet-100 text-violet-700 dark:bg-violet-500/20 dark:text-violet-300">
                                                Super Admin
                                            </span>
                                        @elseif(isset($activeStore) && auth()->user()?->getStoreRole($activeStore->id))
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-sky-100 text-sky-700 dark:bg-sky-500/20 dark:text-sky-300 capitalize">
                                                {{ str_replace('_', ' ', auth()->user()->getStoreRole($activeStore->id)) }}
                                            </span>
                                        @endif
                                        @if(auth()->user()?->phone)
                                            <span class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                                                {{ auth()->user()->phone }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Quick link if authorized for user management --}}
                        @if ($hasStoreContext && auth()->user()?->hasStoreRole($activeStore->id, ['store_owner']))
                            <a href="{{ route('store.admin.users.index', $storeRouteParams) }}" role="menuitem" @click="userMenuOpen = false"
                                class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                <svg class="h-4 w-4 text-slate-500 dark:text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <span>{{ __('messages.user_management') ?? 'User Management' }}</span>
                            </a>
                        @endif

                        {{-- Logout Form / Button --}}
                        <div class="pt-1 mt-1 border-t border-slate-100 dark:border-slate-800">
                            <form method="POST" action="{{ route('logout') }}" class="w-full">
                                @csrf
                                <button type="submit" role="menuitem"
                                    class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition">
                                    <svg class="h-4 w-4 text-red-500 dark:text-red-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                        <polyline points="16 17 21 12 16 7"/>
                                        <line x1="21" y1="12" x2="9" y2="12"/>
                                    </svg>
                                    <span>{{ __('messages.logout') }}</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto @yield('main_padding', 'p-2 sm:p-2.5 lg:p-3') bg-slate-50 dark:bg-slate-900/60 transition-colors duration-200">
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

    {{-- Reusable Confirmation Modal & Form Submit Protection --}}
    <x-admin.confirm-modal />
</body>
</html>
