<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') · @endif Admin Panel - {{ config('app.name') }}</title>
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
        [x-cloak] { display: none !important; }
        :root { --admin-accent: {{ $adminAccent }}; }
        /* Restrained: only the active sidebar nav link uses the brand accent */
        aside a.bg-violet-600,
        aside a.bg-violet-600:hover {
            background-color: var(--admin-accent) !important;
        }
        @media (min-width: 1024px) {
            aside.lg\:w-20 {
                transition: width 220ms cubic-bezier(0.4, 0, 0.2, 1);
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
        $isPlatformScope = request()->is('admin/*') && ! request()->is('store/*');
        $activeStore = $isPlatformScope ? null : ($store ?? app(\App\Services\StoreContext::class)->getStore());
        $currentSlug = $isPlatformScope ? null : (request()->route('store_slug') ?? $activeStore?->slug);
        $hasStoreContext = filled($currentSlug);
        $storeRouteParams = $hasStoreContext ? ['store_slug' => $currentSlug] : [];
        $pendingOrderCount = $adminPendingOrderCount ?? 0;
        $canAccessStaffTools = $hasStoreContext && ($adminCanAccessStaffTools ?? false);
        $canManageSettings = $hasStoreContext && ($adminCanManageSettings ?? false);
        $canManageUsers = $hasStoreContext && ($adminCanManageUsers ?? false);
        // Finance pages (P&L, expenses, transactions, admin receivables) are
        // Owner/Manager-only — mirror the server-side `finance_access` gate here
        // so staff/cashier do not even see the links (audit §5.3 / §13). Note:
        // supplier Payables lives in the POS purchase back-office and stays
        // available to staff, so its nav link is NOT gated here.
        $canManageFinance = $hasStoreContext && $activeStore
            && auth()->user()?->hasStoreRole($activeStore->id, ['store_owner', 'store_manager']);
        $adminStoreSetting = $activeStore?->setting;
        $navGroupInitialState = [];
        if (!empty($navigationTree)) {
            foreach ($navigationTree as $node) {
                if (($node['type'] ?? '') === 'group') {
                    $navGroupInitialState[$node['key'] . 'Open'] = !empty($node['active']);
                }
            }
        }
    @endphp

    <aside :inert="!viewportLg && !sidebarOpen" :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full', sidebarCollapsed ? 'lg:w-20' : 'lg:w-72']"
        data-admin-alerts-url="{{ $hasStoreContext ? url('/store/' . $currentSlug . '/admin/alerts/check') : '' }}"
        data-admin-alerts-interval="30000"
        x-data="{
            posOpen: false,
            inventoryOpen: false,
            purchasingOpen: false,
            ecommerceOpen: false,
            customersOpen: false,
            serviceOpen: false,
            financeOpen: false,
            reportsOpen: false,
            securityOpen: false,
            maintenanceOpen: false,
            setupOpen: false,
            @foreach ($navGroupInitialState as $groupKey => $groupActive)
                {{ $groupKey }}: {{ $groupActive ? 'true' : 'false' }},
            @endforeach
            closeGroups() {
                this.posOpen = false;
                this.inventoryOpen = false;
                this.purchasingOpen = false;
                this.ecommerceOpen = false;
                this.customersOpen = false;
                this.serviceOpen = false;
                this.financeOpen = false;
                this.reportsOpen = false;
                this.securityOpen = false;
                this.maintenanceOpen = false;
                this.setupOpen = false;
                @foreach (array_keys($navGroupInitialState) as $groupKey)
                    this.{{ $groupKey }} = false;
                @endforeach
            },
            activeHoverGroup: null,
            hoverFlyoutTop: 0,
            hoverFlyoutSidebarRight: 0,
            hoverTimer: null,
            openHoverGroup(name, trigger) {
                if (!this.viewportLg || !this.sidebarCollapsed) return;
                if (this.hoverTimer) clearTimeout(this.hoverTimer);

                const triggerRect = trigger.getBoundingClientRect();
                const sidebarRect = trigger.closest('aside').getBoundingClientRect();
                this.hoverFlyoutTop = Math.max(8, triggerRect.top);
                this.hoverFlyoutSidebarRight = sidebarRect.right;
                this.activeHoverGroup = name;

                this.$nextTick(() => requestAnimationFrame(() => {
                    const panel = document.querySelector('[data-nav-flyout=' + name + ']');
                    if (!panel || this.activeHoverGroup !== name) return;
                    this.hoverFlyoutTop = Math.max(
                        8,
                        Math.min(triggerRect.top, window.innerHeight - panel.offsetHeight - 8)
                    );
                }));
            },
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

        <nav class="flex-1 min-h-0 px-3 py-4 space-y-1.5 overflow-y-auto overscroll-contain text-sm" aria-label="{{ __('messages.admin_navigation') }}" data-scope="{{ $isPlatformScope ? 'platform' : 'store' }}">
            @if ($hasStoreContext && auth()->user()?->isPlatformOwner() && ! $isPlatformScope)
                <div class="pt-0.5 pb-0.5">
                    <a href="{{ route('admin.dashboard') }}"
                       class="flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-medium text-slate-500 hover:text-violet-600 dark:text-slate-400 dark:hover:text-violet-300 hover:bg-violet-50/60 dark:hover:bg-slate-800/60 transition"
                       :class="sidebarCollapsed ? 'lg:justify-center' : ''"
                       title="← Platform Admin">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        <span :class="sidebarCollapsed ? 'lg:hidden' : ''">Platform Admin</span>
                    </a>
                </div>
            @endif

            @foreach ($navigationTree ?? [] as $item)
                @if (($item['type'] ?? '') === 'link')
                    <div>
                        <x-admin.nav-link variant="main"
                            :href="$item['url']"
                            :route-name="$item['route_name'] ?? null"
                            :active="!empty($item['active'])"
                            :label="$item['label']">
                            <x-slot:icon>
                                {!! $item['icon'] !!}
                            </x-slot:icon>
                            @if (!empty($item['badge']))
                                <x-slot:badge>
                                    <span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full font-bold shadow">{{ $item['badge'] }}</span>
                                </x-slot:badge>
                            @endif
                        </x-admin.nav-link>
                    </div>
                @elseif (($item['type'] ?? '') === 'group')
                    <x-admin.nav-group :name="$item['key']" :label="$item['label']" :icon-class="$item['icon_class'] ?? ''">
                        <x-slot:icon>
                            {!! $item['icon'] !!}
                        </x-slot:icon>

                        @if (!empty($item['badge']))
                            <x-slot:badge>
                                <span data-pending-order-count="{{ $item['badge'] }}" class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full font-bold shadow max-w-[3rem] truncate">{{ $item['badge'] }}</span>
                            </x-slot:badge>
                            <x-slot:corner-badge>
                                <span data-pending-order-count="{{ $item['badge'] }}" x-cloak :class="sidebarCollapsed && viewportLg ? 'inline-flex' : 'hidden'"
                                    class="absolute -top-1 -right-0.5 z-10 min-w-5 h-5 px-1 items-center justify-center rounded-full bg-red-500 text-white text-xs leading-none font-bold shadow-lg ring-2 ring-white dark:ring-slate-950">{{ $item['badge'] }}</span>
                            </x-slot:corner-badge>
                        @endif

                        @foreach ($item['children'] as $child)
                            <x-admin.nav-link
                                :href="$child['url']"
                                :route-name="$child['route_name'] ?? null"
                                :active="!empty($child['active'])"
                                :label="$child['label']">
                                <x-slot:icon>
                                    {!! $child['icon'] !!}
                                </x-slot:icon>
                                @if (!empty($child['badge']))
                                    <x-slot:badge>
                                        <span data-pending-order-count="{{ $child['badge'] }}" class="bg-red-500 text-white text-xs px-1.5 rounded-full font-bold max-w-[3rem] truncate">{{ $child['badge'] }}</span>
                                    </x-slot:badge>
                                @endif
                            </x-admin.nav-link>
                        @endforeach
                    </x-admin.nav-group>
                @endif
            @endforeach
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

        <header class="bg-white/90 dark:bg-slate-900/90 backdrop-blur border-b border-slate-200/80 dark:border-slate-800/80 h-[calc(3.25rem+env(safe-area-inset-top))] pt-[env(safe-area-inset-top)] flex items-center justify-between px-3 sm:px-4 transition-colors duration-200 gap-1.5 sm:gap-2 sticky top-0 z-10">
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

                @php
                    $headerDashboardUrl = $hasStoreContext
                        ? route('store.admin.dashboard', ['store_slug' => $currentSlug])
                        : url('/admin');
                @endphp
                <a href="{{ $headerDashboardUrl }}"
                   title="{{ __('messages.admin_dashboard') }} — {{ $activeStore->name ?? 'Select Store' }}"
                   class="inline-flex items-center px-2.5 py-1 rounded-lg bg-gradient-to-r from-sky-500 to-sky-600 hover:from-sky-400 hover:to-sky-500 text-white font-outfit text-xs sm:text-sm font-bold shadow-xs hover:shadow-md hover:shadow-sky-500/20 border border-sky-300/40 border-b-2 border-b-sky-800 active:translate-y-0.5 transition-all truncate max-w-[180px] sm:max-w-xs">
                    <span class="truncate">{{ $activeStore->name ?? 'Select Store' }}</span>
                </a>
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

        <main class="flex-1 overflow-y-auto @yield('main_padding', 'p-0.5 sm:p-1.5 lg:p-2') bg-slate-50 dark:bg-slate-900/60 transition-colors duration-200">
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

    {{-- Global Floating Toast Notifications (Auto-dismiss & Close [X]) --}}
    <x-floating-toast />
    {{-- Page-specific scripts injected outside Alpine body scope --}}
    @stack('scripts')
</body>
</html>

