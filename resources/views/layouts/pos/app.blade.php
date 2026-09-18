<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>POS - {{ $store->name }}</title>
    @php
        $posFaviconPath = ($store ?? null)?->setting?->favicon();
        $posFaviconHref = $posFaviconPath ? asset('storage/' . $posFaviconPath) : asset('favicon.ico');
        $posDedicatedFavicon = ($store ?? null)?->setting?->favicon_path;
        $posAppleTouchHref = $posDedicatedFavicon
            ? asset('storage/' . $posDedicatedFavicon)
            : asset('apple-touch-icon.png');
    @endphp
    <link rel="icon" type="{{ $posFaviconPath && str_ends_with($posFaviconPath, '.webp') ? 'image/webp' : ($posFaviconPath ? 'image/png' : 'image/x-icon') }}" href="{{ $posFaviconHref }}">
    <link rel="apple-touch-icon" href="{{ $posAppleTouchHref }}">
    <meta name="theme-color" content="#2563eb">
    <link rel="preload" as="font" type="font/woff2" crossorigin href="{{ Vite::asset('resources/assets/fonts/Roboto-Regular.woff2') }}">
    <link rel="preload" as="font" type="font/ttf" crossorigin href="{{ Vite::asset('resources/assets/fonts/NotoSansMyanmar/NotoSansMyanmar-Regular.ttf') }}">
    <link rel="preload" as="font" type="font/woff2" crossorigin href="{{ Vite::asset('resources/assets/fonts/Outfit-Regular.woff2') }}">
    <script nonce="{{ $cspNonce }}">
        // POS theme (Dark / Light):
        (function () {
            var mode = localStorage.getItem('theme') || localStorage.getItem('posDisplayMode') || 'light';
            var isDark = mode === 'dark' || mode === 'oled_dark';
            document.documentElement.classList.toggle('dark', isDark);
            document.documentElement.classList.remove('high-contrast-daylight');
        })();

        // Expose a simple helper for external scripts if needed.
        window.togglePosFullscreen = function() {
            const el = document.documentElement;
            if (!document.fullscreenElement && !document.webkitFullscreenElement) {
                (el.requestFullscreen || el.webkitRequestFullscreen || el.mozRequestFullScreen || el.msRequestFullscreen)
                    ?.call(el)
                    ?.catch(e => console.warn('[POS] requestFullscreen:', e.message));
            } else {
                (document.exitFullscreen || document.webkitExitFullscreen || document.mozCancelFullScreen || document.msExitFullscreen)
                    ?.call(document)
                    ?.catch(e => console.warn('[POS] exitFullscreen:', e.message));
            }
        };

    </script>
    <style>
        [x-cloak] { display: none !important; }
        :fullscreen, ::backdrop {
            background-color: transparent;
        }
        html:fullscreen, body:fullscreen {
            width: 100vw;
            height: 100vh;
        }
        .sf-clock-3d {
            background: linear-gradient(180deg, #ffffff 0%, #f1f5f9 100%) !important;
            border: 1px solid rgba(203, 213, 225, 0.9) !important;
            border-bottom: 2px solid #cbd5e1 !important;
            color: #1e293b !important;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06), inset 0 1px 0 rgba(255, 255, 255, 0.95) !important;
        }
        .dark .sf-clock-3d {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%) !important;
            border: 1px solid rgba(51, 65, 85, 0.9) !important;
            border-bottom: 2px solid #020617 !important;
            color: #f1f5f9 !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.08) !important;
        }
    </style>
    <x-currency-js-init :store="$store ?? null" />
    @vite(['resources/css/admin.css', 'resources/js/app-admin.js'])
</head>
<body class="bg-slate-100 dark:bg-slate-950 text-gray-900 dark:text-slate-100 font-sans antialiased min-h-dvh flex flex-col transition-colors duration-200"
    x-data="{
        isDark: document.documentElement.classList.contains('dark'),
        isFullscreen: false,
        toggleDarkMode() {
            this.isDark = !this.isDark;
            document.documentElement.classList.toggle('dark', this.isDark);
            document.documentElement.classList.remove('high-contrast-daylight');
            var mode = this.isDark ? 'dark' : 'light';
            localStorage.setItem('theme', mode);
            localStorage.setItem('posDisplayMode', mode);
        },
        syncFullscreenState() {
            // Use ONLY the Fullscreen API — innerHeight check causes false-positives.
            this.isFullscreen = !!(
                document.fullscreenElement ||
                document.webkitFullscreenElement ||
                document.mozFullScreenElement ||
                document.msFullscreenElement
            );
        },
        async toggleFullscreen() {
            const el = document.documentElement;
            try {
                if (!document.fullscreenElement && !document.webkitFullscreenElement) {
                    const req = el.requestFullscreen || el.webkitRequestFullscreen || el.mozRequestFullScreen || el.msRequestFullscreen;
                    if (req) await req.call(el);
                } else {
                    const exit = document.exitFullscreen || document.webkitExitFullscreen || document.mozCancelFullScreen || document.msExitFullscreen;
                    if (exit) await exit.call(document);
                }
            } catch(e) {
                console.warn('[POS] Fullscreen toggle:', e.message);
            }
            this.syncFullscreenState();
        },
        calculatorOpen: false,
        calcDisplay: '0',
        calcLeft: null,
        calcOperator: null,
        calcWaitingForNext: false,
        openCalculator() {
            this.calculatorOpen = true;
            this.$nextTick(() => this.$refs.posCalcClose?.focus());
        },
        closeCalculator() { this.calculatorOpen = false; },
        resetCalculator() {
            this.calcDisplay = '0'; this.calcLeft = null;
            this.calcOperator = null; this.calcWaitingForNext = false;
        },
        inputCalcDigit(value) {
            if (this.calcWaitingForNext) {
                this.calcDisplay = value === '.' ? '0.' : value;
                this.calcWaitingForNext = false; return;
            }
            if (value === '.' && this.calcDisplay.includes('.')) return;
            this.calcDisplay = this.calcDisplay === '0' && value !== '.' ? value : this.calcDisplay + value;
        },
        backspaceCalculator() {
            if (this.calcWaitingForNext || this.calcDisplay.length <= 1) {
                this.calcDisplay = '0'; this.calcWaitingForNext = false; return;
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
                    this.calcDisplay = 'Error'; this.calcLeft = null;
                    this.calcOperator = null; this.calcWaitingForNext = true; return;
                }
                result /= right;
            }
            this.calcDisplay = this.formatCalcNumber(result);
            this.calcLeft = result; this.calcOperator = null; this.calcWaitingForNext = true;
        },
        formatCalcNumber(value) {
            if (!Number.isFinite(value)) return 'Error';
            return Number.parseFloat(value.toFixed(8)).toLocaleString('en-US', { maximumFractionDigits: 8 });
        },
        handlePosCalcKey(event) {
            if (!this.calculatorOpen) return;
            if (/^[0-9.]$/.test(event.key)) { event.preventDefault(); this.inputCalcDigit(event.key); }
            else if (['+','-','*','/'].includes(event.key)) { event.preventDefault(); this.chooseCalcOperator(event.key); }
            else if (event.key === 'Enter' || event.key === '=') { event.preventDefault(); this.calculateResult(); }
            else if (event.key === 'Backspace') { event.preventDefault(); this.backspaceCalculator(); }
            else if (event.key === 'Escape') { event.preventDefault(); this.closeCalculator(); }
        },
        init() {
            this.syncFullscreenState();
            // Listen to Fullscreen API events only — NOT resize, which fires for
            // many unrelated reasons and would cause the icon to flicker.
            ['fullscreenchange', 'webkitfullscreenchange', 'mozfullscreenchange', 'MSFullscreenChange'].forEach(evt => {
                document.addEventListener(evt, () => {
                    this.syncFullscreenState();
                });
            });
            // F11 is native browser fullscreen — sync icon state after it settles.
            window.addEventListener('keydown', (e) => {
                if (e.key === 'F11') {
                    setTimeout(() => this.syncFullscreenState(), 300);
                }
            });
        }
    }"
    @keydown.window="handlePosCalcKey($event)">

    <header class="bg-white/90 dark:bg-slate-900/90 backdrop-blur border-b border-slate-200/80 dark:border-slate-800/80 h-[calc(3.25rem+env(safe-area-inset-top))] pt-[env(safe-area-inset-top)] flex items-center justify-between px-3 sm:px-4 transition-colors duration-200 gap-1.5 sm:gap-2 sticky top-0 z-40">
        {{-- Left Section: Store Branding, Tactile Icon & Cashier Info --}}
        <div class="flex items-center gap-2 sm:gap-2.5 min-w-0 flex-1">
            <a href="{{ url('/store/' . $store->slug . '/pos') }}" class="inline-flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-200/90 border-b-2 border-b-slate-300 dark:border-slate-700 dark:border-b-slate-900 bg-gradient-to-b from-white to-slate-50 dark:from-slate-900 dark:to-slate-950 text-sky-600 dark:text-sky-300 shadow-xs hover:shadow-sm transition-all" title="{{ $store->name }}">
                @if (!empty($store->setting?->adminLogo()))
                    <img src="{{ asset('storage/' . $store->setting->adminLogo()) }}" alt="{{ $store->name }}" class="h-full w-full object-contain p-0.5" loading="lazy" />
                @else
                    <svg class="h-5 w-5 text-sky-600 dark:text-sky-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><rect x="2" y="6" width="20" height="4"/><path d="M4 10v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8M7 14h2m4 0h4M7 18h2m4 0h4"/></svg>
                @endif
            </a>

            <div class="min-w-0 flex items-center gap-1.5">
                <a href="{{ url('/store/' . $store->slug . '/pos') }}"
                   title="POS · {{ $store->name }}"
                   class="inline-flex items-center px-2.5 py-1 rounded-lg bg-gradient-to-r from-sky-500 to-sky-600 hover:from-sky-400 hover:to-sky-500 text-white font-outfit text-xs sm:text-sm font-bold shadow-xs hover:shadow-md hover:shadow-sky-500/20 border border-sky-300/40 border-b-2 border-b-sky-800 active:translate-y-0.5 transition-all truncate max-w-[140px] sm:max-w-[200px] md:max-w-xs">
                    <span class="truncate">{{ $store->name }}</span>
                </a>
                <span class="hidden sm:inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-extrabold uppercase tracking-wider bg-slate-200/70 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-300/60 dark:border-slate-700 select-none">
                    POS
                </span>
            </div>
        </div>

        {{-- Right Section: Digital Clock, Sync Status, 3D Buttons & Mobile Overflow Menu --}}
        <div class="flex items-center gap-1.5 sm:gap-2 flex-shrink-0">
            {{-- 1. 3D Digital Date Time Clock Widget --}}
            <x-digital-clock class="hidden sm:inline-flex" />

            {{-- 2. Sync Status Widget --}}
            @if (isset($store))
                <x-sync-status-widget :store="$store" />
            @endif

            {{-- 3. Desktop Action Buttons Group --}}
            <div class="hidden sm:flex items-center gap-1.5 sm:gap-2">
                {{-- Language Switcher (3D Sky/Telegram) --}}
                <x-language-switcher id="pos-header" btn-class="sf-btn-3d-telegram h-10 w-10 rounded-xl inline-flex items-center justify-center text-base cursor-pointer shadow-xs text-white" />

                {{-- View Commerce / Storefront (3D Success Emerald) --}}
                <a href="{{ url('/store/' . $store->slug) }}" target="_blank" rel="noopener noreferrer"
                    class="sf-btn-3d-success h-10 w-10 rounded-xl inline-flex items-center justify-center text-white cursor-pointer shadow-xs focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    aria-label="{{ __('messages.view_commerce') }}"
                    title="{{ __('messages.view_commerce') }}">
                    <svg class="h-4 w-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10.5 5 5h14l2 5.5M4 10.5V19a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8.5M3 10.5h18M8 21v-6h8v6" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 5v5.5m5-5.5v5.5M17 5v5.5" />
                    </svg>
                </a>

                {{-- Reload POS (3D Teal with hard cache-bust) --}}
                <button type="button"
                        x-data="{ reloading: false }"
                        @click="reloading = true; (async () => {
                            if ('caches' in window) {
                                try {
                                    const keys = await caches.keys();
                                    await Promise.all(keys.map(k => caches.delete(k)));
                                } catch (e) {}
                            }
                            const u = new URL(window.location.href);
                            u.searchParams.set('_r', Date.now().toString());
                            window.location.replace(u.toString());
                        })()"
                        class="sf-btn-3d-teal h-10 w-10 rounded-xl inline-flex items-center justify-center text-white cursor-pointer shadow-xs focus:outline-none focus:ring-2 focus:ring-teal-500"
                        aria-label="{{ __('messages.pos_reload') }}"
                        title="{{ __('messages.pos_reload') }} 🔄 (Ctrl+Shift+R)">
                    <svg class="h-4 w-4 text-white" :class="reloading ? 'animate-spin' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 2v6h-6"/>
                        <path d="M3 12a9 9 0 0 1 15-6.7L21 8"/>
                        <path d="M3 22v-6h6"/>
                        <path d="M21 12a9 9 0 0 1-15 6.7L3 16"/>
                    </svg>
                </button>

                {{-- Calculator (3D Primary Blue) --}}
                <button @click="openCalculator()" type="button"
                    class="sf-btn-3d-primary h-10 w-10 rounded-xl inline-flex items-center justify-center text-white cursor-pointer shadow-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
                    aria-label="{{ __('messages.calculator') }}"
                    title="{{ __('messages.calculator') }}">
                    <svg class="h-4 w-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <rect x="5" y="3" width="14" height="18" rx="2" stroke-width="2" />
                        <path stroke-linecap="round" stroke-width="2" d="M8 7h8M8 11h.01M12 11h.01M16 11h.01M8 15h.01M12 15h.01M16 15h.01" />
                    </svg>
                </button>

                {{-- POS Theme Toggle Button (3D Gold - 1-Click Dark/Light Mode) --}}
                <button @click="toggleDarkMode()" type="button"
                    class="sf-btn-3d-gold h-10 w-10 rounded-xl cursor-pointer text-white inline-flex items-center justify-center shadow-xs focus:outline-none focus:ring-2 focus:ring-amber-500"
                    :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
                    :title="isDark ? 'Light Mode (အလင်း)' : 'Dark Mode (အမှောင်)'">
                    {{-- Sun Icon (shown in Dark Mode to switch to Light) --}}
                    <svg x-show="isDark" x-cloak class="h-4 w-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="4"/>
                        <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>
                    </svg>
                    {{-- Moon Icon (shown in Light Mode to switch to Dark) --}}
                    <svg x-show="!isDark" class="h-4 w-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                    </svg>
                </button>

                {{-- Fullscreen Toggle Button (3D Indigo with F11-style toggle) --}}
                <button id="pos-fullscreen-btn" @click="toggleFullscreen()" type="button"
                    class="sf-btn-3d-indigo h-10 w-10 rounded-xl inline-flex items-center justify-center text-white cursor-pointer shadow-xs focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    :title="isFullscreen ? '{{ __('messages.fullscreen_exit') }}' : '{{ __('messages.fullscreen_enter') }}'"
                    :aria-label="isFullscreen ? '{{ __('messages.fullscreen_exit') }}' : '{{ __('messages.fullscreen_enter') }}'">
                    {{-- Enter Fullscreen Icon (Expand) --}}
                    <svg x-show="!isFullscreen" class="h-4 w-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/>
                    </svg>
                    {{-- Exit Fullscreen Icon (Compress) --}}
                    <svg x-show="isFullscreen" class="h-4 w-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M8 3v3a2 2 0 0 1-2 2H3m18 0h-3a2 2 0 0 1-2-2V3m0 18v-3a2 2 0 0 1 2-2h3M3 16h3a2 2 0 0 1 2 2v3"/>
                    </svg>
                </button>

                {{-- Admin Panel Link (3D Accent Purple) --}}
                <a href="{{ url('/store/' . $store->slug . '/admin/dashboard') }}"
                   class="sf-btn-3d-accent h-10 w-10 lg:w-auto lg:px-3 rounded-xl inline-flex items-center justify-center gap-1.5 text-xs font-bold cursor-pointer text-white shadow-xs focus:outline-none focus:ring-2 focus:ring-violet-500"
                   title="{{ __('messages.admin_panel') }}"
                   aria-label="{{ __('messages.admin_panel') }}">
                    <svg class="h-4 w-4 text-white shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    <span class="hidden xl:inline">{{ __('messages.admin_panel') }}</span>
                </a>

                {{-- User Profile Dropdown Menu (3D Tactile with Profile Icon) --}}
                <div class="relative" x-data="{ userMenuOpen: false }" @click.outside="userMenuOpen = false" @keydown.escape.window="userMenuOpen = false">
                    <button type="button" @click="userMenuOpen = !userMenuOpen"
                        class="sf-btn-3d h-10 w-10 lg:w-auto lg:px-2.5 rounded-xl inline-flex items-center justify-center gap-1.5 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:text-sky-600 dark:hover:text-sky-400 cursor-pointer shadow-xs focus:outline-none focus:ring-2 focus:ring-sky-500 flex-shrink-0"
                        :aria-expanded="userMenuOpen.toString()"
                        aria-haspopup="menu"
                        aria-label="{{ auth()->user()?->name ?? 'User Profile' }}"
                        title="{{ auth()->user()?->name ?? 'User Profile' }}">
                        <div class="h-6 w-6 rounded-lg bg-sky-500/15 dark:bg-sky-400/20 text-sky-600 dark:text-sky-300 flex items-center justify-center font-bold text-xs shrink-0">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </div>
                        <span class="hidden xl:inline font-bold text-xs max-w-[100px] truncate">
                            {{ auth()->user()?->name ?? 'Profile' }}
                        </span>
                        <svg class="hidden xl:block h-3.5 w-3.5 text-slate-400 dark:text-slate-500 shrink-0 transition-transform duration-150" :class="userMenuOpen ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
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
                        aria-label="{{ auth()->user()?->name ?? 'User Profile' }}">

                        {{-- User Header Details --}}
                        <div class="px-3 py-2.5 bg-slate-50 dark:bg-slate-800/60 rounded-xl mb-1 border border-slate-100 dark:border-slate-800">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 shrink-0 rounded-xl bg-gradient-to-tr from-sky-500 to-sky-600 text-white flex items-center justify-center font-bold text-sm shadow">
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
                                        @elseif(isset($store) && auth()->user()?->getStoreRole($store->id))
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-sky-100 text-sky-700 dark:bg-sky-500/20 dark:text-sky-300 capitalize">
                                                {{ str_replace('_', ' ', auth()->user()->getStoreRole($store->id)) }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                                Cashier
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

                        {{-- Admin Panel Quick Link --}}
                        <a href="{{ url('/store/' . $store->slug . '/admin/dashboard') }}" role="menuitem" @click="userMenuOpen = false"
                            class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            <svg class="h-4 w-4 text-violet-600 dark:text-violet-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                            </svg>
                            <span>{{ __('messages.admin_panel') }}</span>
                        </a>

                        {{-- Logout Form / Button --}}
                        <div class="pt-1 mt-1 border-t border-slate-100 dark:border-slate-800">
                            <form method="POST" action="{{ url('/logout') }}" class="w-full">
                                @csrf
                                <button type="submit" role="menuitem"
                                    class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition cursor-pointer">
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

            @yield('header_extra')

            {{-- 4. Mobile More '...' Menu (Floating popover) --}}
            <div class="relative sm:hidden"
                x-data="{
                    moreOpen: false,
                    menuTop: '0px',
                    menuRight: '0px',
                    updatePos() {
                        const btn = this.$refs.moreBtn;
                        if (!btn) return;
                        const r = btn.getBoundingClientRect();
                        this.menuTop  = (r.bottom + 8) + 'px';
                        this.menuRight = (window.innerWidth - r.right) + 'px';
                    },
                    open() { this.updatePos(); this.moreOpen = true; },
                    close() { this.moreOpen = false; }
                }"
                @click.outside="close()"
                @keydown.escape.window="close()"
                @scroll.window="moreOpen && updatePos()"
                @resize.window="moreOpen && updatePos()">
                <button type="button" x-ref="moreBtn" @click="moreOpen ? close() : open()"
                    class="sf-btn-3d-accent h-10 w-10 inline-flex items-center justify-center rounded-xl text-white cursor-pointer shadow-xs focus:outline-none focus:ring-2 focus:ring-violet-500 flex-shrink-0"
                    :aria-expanded="moreOpen.toString()" aria-haspopup="menu" aria-label="{{ __('messages.more_actions') }}">
                    <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <circle cx="5" cy="12" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="19" cy="12" r="1.6"/>
                    </svg>
                </button>
                <div x-show="moreOpen" x-transition x-cloak
                    :style="'position:fixed; top:' + menuTop + '; right:' + menuRight + '; z-index:9999;'"
                    class="w-64 max-h-[calc(100dvh-5rem)] overflow-y-auto rounded-xl border border-slate-200 bg-white p-2 shadow-xl shadow-slate-900/10 dark:border-slate-700 dark:bg-slate-900"
                    role="menu" aria-label="{{ __('messages.more_actions') }}">
                    <div class="px-1 pb-2 mb-2 border-b border-slate-100 dark:border-slate-800">
                        <x-digital-clock class="w-full justify-between" />
                    </div>

                    {{-- User Profile Card inside Mobile menu --}}
                    <div class="px-3 py-2 bg-slate-50 dark:bg-slate-800/60 rounded-xl mb-2 border border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-2.5">
                            <div class="h-9 w-9 shrink-0 rounded-xl bg-gradient-to-tr from-sky-500 to-sky-600 text-white flex items-center justify-center font-bold text-xs shadow">
                                @if(auth()->user()?->name)
                                    {{ mb_substr(auth()->user()->name, 0, 1) }}
                                @else
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                                        <circle cx="12" cy="7" r="4"/>
                                    </svg>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate">
                                    {{ auth()->user()?->name ?? 'User' }}
                                </p>
                                <div class="flex items-center gap-1 mt-0.5 flex-wrap">
                                    @if(auth()->user()?->isPlatformOwner())
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-violet-100 text-violet-700 dark:bg-violet-500/20 dark:text-violet-300">
                                            Super Admin
                                        </span>
                                    @elseif(isset($store) && auth()->user()?->getStoreRole($store->id))
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-sky-100 text-sky-700 dark:bg-sky-500/20 dark:text-sky-300 capitalize">
                                            {{ str_replace('_', ' ', auth()->user()->getStoreRole($store->id)) }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                            Cashier
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <a href="{{ url('/store/' . $store->slug . '/admin/dashboard') }}" role="menuitem" @click="moreOpen = false"
                        class="w-full flex items-center gap-2.5 px-3 min-h-11 rounded-lg text-sm font-semibold text-violet-700 dark:text-violet-300 hover:bg-violet-50 dark:hover:bg-violet-950/40 transition mb-0.5">
                        <svg class="h-4 w-4 shrink-0 text-violet-600 dark:text-violet-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                        </svg>
                        <span>{{ __('messages.admin_panel') }}</span>
                    </a>

                    <a href="{{ url('/store/' . $store->slug) }}" target="_blank" rel="noopener noreferrer" role="menuitem" @click="moreOpen = false"
                        class="w-full flex items-center gap-2.5 px-3 min-h-11 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                        <svg class="h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10.5 5 5h14l2 5.5M4 10.5V19a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8.5M3 10.5h18M8 21v-6h8v6" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 5v5.5m5-5.5v5.5M17 5v5.5" />
                        </svg>
                        {{ __('messages.view_commerce') }}
                    </a>

                    <button type="button" role="menuitem" @click="moreOpen = false; (async () => {
                        if ('caches' in window) {
                            try { const keys = await caches.keys(); await Promise.all(keys.map(k => caches.delete(k))); } catch (e) {}
                        }
                        const u = new URL(window.location.href);
                        u.searchParams.set('_r', Date.now().toString());
                        window.location.replace(u.toString());
                    })()"
                        class="w-full flex items-center gap-2.5 px-3 min-h-11 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                        <svg class="h-4 w-4 shrink-0 text-teal-600 dark:text-teal-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 2v6h-6"/>
                            <path d="M3 12a9 9 0 0 1 15-6.7L21 8"/>
                            <path d="M3 22v-6h6"/>
                            <path d="M21 12a9 9 0 0 1-15 6.7L3 16"/>
                        </svg>
                        <span>{{ __('messages.pos_reload') }}</span>
                    </button>

                    <button type="button" role="menuitem" @click="moreOpen = false; openCalculator()"
                        class="w-full flex items-center gap-2.5 px-3 min-h-11 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                        <svg class="h-4 w-4 shrink-0 text-blue-600 dark:text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <rect x="5" y="3" width="14" height="18" rx="2" stroke-width="2" />
                            <path stroke-linecap="round" stroke-width="2" d="M8 7h8M8 11h.01M12 11h.01M16 11h.01M8 15h.01M12 15h.01M16 15h.01" />
                        </svg>
                        {{ __('messages.calculator') }}
                    </button>

                    {{-- Theme toggle in mobile menu --}}
                    <button type="button" role="menuitem" @click="moreOpen = false; toggleDarkMode()"
                        class="w-full flex items-center gap-2.5 px-3 min-h-11 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                        <svg x-show="!isDark" class="h-4 w-4 shrink-0 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                        </svg>
                        <svg x-show="isDark" x-cloak class="h-4 w-4 shrink-0 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="4"/>
                            <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>
                        </svg>
                        <span x-text="isDark ? 'Light Mode (အလင်း)' : 'Dark Mode (အမှောင်)'"></span>
                    </button>

                    {{-- Fullscreen button in mobile menu --}}
                    <button id="mobile-pos-fullscreen-btn" type="button" role="menuitem" @click="moreOpen = false; toggleFullscreen()"
                        class="w-full flex items-center gap-2.5 px-3 min-h-11 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                        <svg class="h-4 w-4 shrink-0 text-indigo-600 dark:text-indigo-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/>
                        </svg>
                        <span x-text="isFullscreen ? '{{ __('messages.fullscreen_exit') }}' : '{{ __('messages.fullscreen_enter') }}'"></span>
                    </button>
                    <div class="my-1 border-t border-slate-100 dark:border-slate-800"></div>
                    {{-- Language switcher row in mobile POS menu (3 equal columns in a single row) --}}
                    <div class="px-1.5 py-1.5">
                        @php $supportedLocales = config('localization.supported', []); $activeLocale = app()->getLocale(); @endphp
                        <form method="POST" action="{{ route('locale.update') }}" class="grid grid-cols-3 gap-1.5 w-full">
                            @csrf
                            @foreach ($supportedLocales as $code => $locale)
                                @php $isActive = $activeLocale === $code; @endphp
                                <button type="submit" name="locale" value="{{ $code }}"
                                    @click="moreOpen = false"
                                    class="h-9 w-full inline-flex items-center justify-center gap-1.5 rounded-xl text-xs font-bold transition focus:outline-none focus:ring-2 focus:ring-sky-500 {{ $isActive ? 'sf-btn-3d-sky text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200/80 dark:bg-slate-800 dark:hover:bg-slate-700/80 text-slate-700 dark:text-slate-200 border border-slate-200/80 dark:border-slate-700/80' }}"
                                    title="{{ $locale['native'] }}"
                                    aria-current="{{ $isActive ? 'true' : 'false' }}">
                                    <x-flag :code="$code" />
                                    <span class="text-[11px] font-bold">{{ $code === 'my' ? 'မြန်မာ' : ($code === 'en' ? 'EN' : '中文') }}</span>
                                </button>
                            @endforeach
                        </form>
                    </div>


                    <form method="POST" action="{{ url('/logout') }}" class="w-full pt-1 mt-1 border-t border-slate-100 dark:border-slate-800">
                        @csrf
                        <button type="submit" role="menuitem"
                            class="w-full flex items-center gap-2.5 px-3 min-h-11 rounded-lg text-sm font-medium text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition">
                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                            {{ __('messages.logout') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-1 w-full @yield('main_padding', 'px-2 sm:px-4 py-1.5 sm:py-2')">
        @if (session('success'))
            <div class="mb-4 px-4 py-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-900 text-emerald-700 dark:text-emerald-300 text-sm font-semibold flex items-center justify-between gap-3 shadow-xs" role="alert">
                <div class="flex items-center gap-2 min-w-0">
                    <svg class="w-5 h-5 shrink-0 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    <span class="truncate">{{ session('success') }}</span>
                </div>
                @if (session('posted_sale_id') && isset($store))
                    <a href="{{ route('pos.receipt', ['store_slug' => $store->slug, 'sale' => session('posted_sale_id')]) }}"
                       class="sf-btn-3d-success shrink-0 !inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-black text-white cursor-pointer shadow-xs transition">
                        🖨️ <span>{{ __('messages.view_receipt') }}</span>
                    </a>
                @endif
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 px-4 py-3 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-900 text-rose-700 dark:text-rose-300 text-sm font-semibold" role="alert">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    {{-- ── POS Calculator Modal ──────────────────────────────────────────── --}}
    <div x-cloak x-show="calculatorOpen" x-transition.opacity
         class="fixed inset-0 z-50 flex items-end justify-center bg-black/25 dark:bg-black/35 p-0 sm:items-center sm:p-3"
         role="dialog" aria-modal="true" aria-labelledby="pos-calculator-title"
         @click.self="closeCalculator()">
        <div x-show="calculatorOpen" x-transition
             class="w-screen max-w-none overflow-hidden rounded-t-[1.75rem] bg-white px-4 pt-4 pb-[calc(1rem+env(safe-area-inset-bottom))] shadow-2xl dark:bg-slate-900 sm:w-full sm:max-w-[360px] sm:rounded-[1.75rem] sm:p-5"
             style="width: 100dvw;">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-300" aria-hidden="true">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <rect x="5" y="3" width="14" height="18" rx="2" stroke-width="2" />
                            <path stroke-linecap="round" stroke-width="2" d="M8 7h8M8 11h.01M12 11h.01M16 11h.01M8 15h.01M12 15h.01M16 15h.01" />
                        </svg>
                    </span>
                    <h2 id="pos-calculator-title" class="text-sm font-black text-slate-700 dark:text-slate-100">{{ __('messages.calculator') }}</h2>
                </div>
                <button x-ref="posCalcClose" @click="closeCalculator()" type="button"
                    class="sf-btn-3d inline-flex h-10 w-10 items-center justify-center rounded-full cursor-pointer"
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
                        class="sf-btn-3d min-h-12 rounded-xl px-2 text-sm font-black sm:min-h-11 cursor-pointer">
                        {{ $percent }}%
                    </button>
                @endforeach
            </div>

            <div class="grid grid-cols-4 gap-2">
                <button type="button" @click="resetCalculator()" class="sf-btn-3d-danger min-h-16 rounded-xl sm:min-h-14 text-xl font-black cursor-pointer">C</button>
                <button type="button" @click="backspaceCalculator()" class="sf-btn-3d-gold min-h-16 rounded-xl sm:min-h-14 text-xl font-black cursor-pointer" aria-label="{{ __('messages.backspace') }}">
                    <svg class="mx-auto h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 6H9l-5 6 5 6h11V6Zm-4 4-4 4m0-4 4 4"/></svg>
                </button>
                <button type="button" @click="chooseCalcOperator('/')" class="sf-btn-3d-primary min-h-16 rounded-xl sm:min-h-14 text-xl font-black cursor-pointer">÷</button>
                <button type="button" @click="chooseCalcOperator('*')" class="sf-btn-3d-primary min-h-16 rounded-xl sm:min-h-14 text-xl font-black cursor-pointer">×</button>

                @foreach ([7, 8, 9] as $digit)
                    <button type="button" @click="inputCalcDigit('{{ $digit }}')" class="sf-btn-3d min-h-16 rounded-xl sm:min-h-14 text-xl font-black cursor-pointer">{{ $digit }}</button>
                @endforeach
                <button type="button" @click="chooseCalcOperator('-')" class="sf-btn-3d-primary min-h-16 rounded-xl sm:min-h-14 text-xl font-black cursor-pointer">-</button>

                @foreach ([4, 5, 6] as $digit)
                    <button type="button" @click="inputCalcDigit('{{ $digit }}')" class="sf-btn-3d min-h-16 rounded-xl sm:min-h-14 text-xl font-black cursor-pointer">{{ $digit }}</button>
                @endforeach
                <button type="button" @click="chooseCalcOperator('+')" class="sf-btn-3d-primary min-h-16 rounded-xl sm:min-h-14 text-xl font-black cursor-pointer">+</button>

                @foreach ([1, 2, 3] as $digit)
                    <button type="button" @click="inputCalcDigit('{{ $digit }}')" class="sf-btn-3d min-h-16 rounded-xl sm:min-h-14 text-xl font-black cursor-pointer">{{ $digit }}</button>
                @endforeach
                <button type="button" @click="calculateResult()" class="sf-btn-3d-success row-span-2 min-h-16 rounded-xl sm:min-h-14 text-xl font-black cursor-pointer">=</button>

                <button type="button" @click="inputCalcDigit('0')" class="sf-btn-3d min-h-16 rounded-xl sm:min-h-14 text-xl font-black cursor-pointer">0</button>
                <button type="button" @click="inputCalcDigit('.')" class="sf-btn-3d min-h-16 rounded-xl sm:min-h-14 text-xl font-black cursor-pointer">.</button>
                <button type="button" @click="applyPercent(100)" class="sf-btn-3d-primary min-h-16 rounded-xl sm:min-h-14 text-xl font-black cursor-pointer">%</button>
            </div>
        </div>
    </div>

    {{-- Reusable Confirmation Modal & Form Submit Protection --}}
    <x-admin.confirm-modal />

    {{-- Global Floating Toast Notifications (Auto-dismiss & Close [X]) --}}
    <x-floating-toast />
</body>
</html>

