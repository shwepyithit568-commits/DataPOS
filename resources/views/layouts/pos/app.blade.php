<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>POS - {{ $store->name }}</title>
    <meta name="theme-color" content="#2563eb">
    <script nonce="{{ $cspNonce }}">
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <x-currency-js-init :store="$store ?? null" />
    @vite(['resources/css/admin.css', 'resources/js/app-admin.js'])
</head>
<body class="bg-slate-100 dark:bg-slate-950 text-gray-900 dark:text-slate-100 font-sans antialiased min-h-dvh flex flex-col transition-colors duration-200"
    x-data="{
        darkMode: localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches),
        toggleDarkMode() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
            if (this.darkMode) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
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
        }
    }"
    @keydown.window="handlePosCalcKey($event)">

    <header class="sticky top-0 z-40 bg-white/90 dark:bg-slate-900/90 backdrop-blur border-b border-slate-200 dark:border-slate-800">
        <div class="mx-auto max-w-[1600px] px-4 py-3 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-9 h-9 rounded-xl bg-blue-600/15 text-blue-600 dark:text-blue-400 grid place-items-center">
                    {{-- POS cash-register mark --}}
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><rect x="2" y="6" width="20" height="4"/><path d="M4 10v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8M7 14h2m4 0h4M7 18h2m4 0h4"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="font-black text-sm truncate">{{ $store->name }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">POS · {{ auth()->user()?->name }}</p>
                </div>
            </div>

            {{-- Real-time clock (Alpine.js) --}}
            <div x-data="{
                    t: '',
                    d: '',
                    tick() {
                        const now = new Date();
                        const hh = String(now.getHours()).padStart(2,'0');
                        const mm = String(now.getMinutes()).padStart(2,'0');
                        const ss = String(now.getSeconds()).padStart(2,'0');
                        this.t = hh + ':' + mm + ':' + ss;
                        const days = ['တနင်္ဂနွေ','တနင်္လာ','အင်္ဂါ','ဗုဒ္ဓဟူး','ကြာသပတေး','သောကြာ','စနေ'];
                        const months = ['ဇန်','ဖေဖော်','မတ်','ဧပြီ','မေ','ဇွန်','ဇူ','ဩ','စက်','အောက်','နို','ဒီ'];
                        this.d = days[now.getDay()] + ' · ' + now.getDate() + ' ' + months[now.getMonth()];
                    }
                }"
                 x-init="tick(); setInterval(() => tick(), 1000)"
                 class="hidden md:flex flex-col items-end leading-tight select-none shrink-0">
                <span class="font-black text-sm tabular-nums text-slate-800 dark:text-slate-100" x-text="t"></span>
                <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400" x-text="d"></span>
            </div>

            <div class="flex items-center gap-2">
                @if (isset($store))
                    <x-sync-status-widget :store="$store" />
                @endif
                {{-- Mobile: time only --}}
                <div x-data="{
                        t: '',
                        tick() {
                            const n = new Date();
                            this.t = String(n.getHours()).padStart(2,'0') + ':' + String(n.getMinutes()).padStart(2,'0');
                        }
                    }"
                     x-init="tick(); setInterval(() => tick(), 10000)"
                     class="sm:hidden font-black text-sm tabular-nums text-slate-700 dark:text-slate-200"
                     x-text="t"></div>

                {{-- Language Switcher --}}
                <x-language-switcher id="pos-header" />

                {{-- Dark / Light Mode Switcher --}}
                <button @click="toggleDarkMode()" type="button"
                        class="w-10 h-10 rounded-xl border border-slate-200 dark:border-slate-700 bg-white/90 dark:bg-slate-800 text-slate-600 dark:text-amber-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition inline-flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm"
                        :aria-label="darkMode ? 'Switch to light mode' : 'Switch to dark mode'"
                        :title="darkMode ? 'Switch to light mode' : 'Switch to dark mode'">
                    <svg x-show="!darkMode" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.8A8.5 8.5 0 1111.2 3a6.5 6.5 0 009.8 9.8z" />
                    </svg>
                    <svg x-show="darkMode" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true" x-cloak>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v2m0 14v2m9-9h-2M5 12H3m15.36-6.36-1.42 1.42M7.06 16.94l-1.42 1.42m12.72 0-1.42-1.42M7.06 7.06 5.64 5.64" />
                        <circle cx="12" cy="12" r="4" stroke-width="2" />
                    </svg>
                </button>

                {{-- Calculator button --}}
                <button type="button" @click="openCalculator()"
                        class="w-10 h-10 rounded-xl border border-slate-200 dark:border-slate-700 bg-white/90 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-sky-600 dark:hover:text-sky-400 transition inline-flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm"
                        aria-label="{{ __('messages.calculator') }}"
                        title="{{ __('messages.calculator') }}">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="5" y="3" width="14" height="18" rx="2"/>
                        <path d="M8 7h8M8 11h.01M12 11h.01M16 11h.01M8 15h.01M12 15h.01M16 15h.01"/>
                    </svg>
                </button>

                <a href="{{ url('/store/' . $store->slug . '/admin/dashboard') }}"
                   class="text-xs font-semibold px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition hidden sm:inline-flex items-center">
                    {{ __('messages.admin_panel') }}
                </a>
                <form method="POST" action="{{ url('/logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-rose-100 dark:hover:bg-rose-900/40 hover:text-rose-600 dark:hover:text-rose-400 transition">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                        <span class="hidden sm:inline">{{ __('messages.logout') }}</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="flex-1 w-full max-w-[1600px] mx-auto px-4 py-6">
        @if (session('success'))
            <div class="mb-4 px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-900 text-emerald-700 dark:text-emerald-300 text-sm font-semibold" role="alert">
                {{ session('success') }}
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
         class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/45 p-0 backdrop-blur-sm sm:items-center sm:p-3"
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

    {{-- Reusable Confirmation Modal & Form Submit Protection --}}
    <x-admin.confirm-modal />
</body>
</html>
