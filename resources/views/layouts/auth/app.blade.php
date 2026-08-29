<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Login' }} — {{ config('app.name', 'DataPOS') }}</title>
    <meta name="theme-color" content="#4f46e5">

    {{-- Dark Mode Initial Script --}}
    <script nonce="{{ $cspNonce }}">
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @keyframes floatSlow {
            0%, 100% { transform: translate(0px, 0px) scale(1); }
            50% { transform: translate(25px, -20px) scale(1.08); }
        }
        @keyframes floatReverse {
            0%, 100% { transform: translate(0px, 0px) scale(1); }
            50% { transform: translate(-25px, 20px) scale(1.05); }
        }
        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.96) translateY(12px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-6px); }
            40%, 80% { transform: translateX(6px); }
        }
        .animate-float-1 { animation: floatSlow 12s ease-in-out infinite; }
        .animate-float-2 { animation: floatReverse 14s ease-in-out infinite; }
        .animate-card-in { animation: fadeInScale 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    </style>
</head>
<body
    x-data="{
        darkMode: localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)
    }"
    class="min-h-screen bg-slate-900 text-slate-100 font-sans antialiased relative flex flex-col justify-between p-4 sm:p-6 overflow-x-hidden selection:bg-indigo-500 selection:text-white"
>
    {{-- Ambient Radiant Glow Mesh --}}
    <div class="fixed inset-0 pointer-events-none overflow-hidden z-0" aria-hidden="true">
        <div class="absolute -top-40 -left-40 w-[32rem] h-[32rem] bg-gradient-to-tr from-indigo-500/25 to-sky-400/20 dark:from-indigo-600/20 dark:to-sky-500/15 rounded-full blur-3xl animate-float-1"></div>
        <div class="absolute top-1/3 -right-40 w-[30rem] h-[30rem] bg-gradient-to-bl from-fuchsia-500/20 to-purple-600/20 dark:from-fuchsia-600/15 dark:to-violet-600/15 rounded-full blur-3xl animate-float-2"></div>
        <div class="absolute -bottom-40 left-1/4 w-[36rem] h-[36rem] bg-gradient-to-t from-sky-400/20 to-indigo-600/15 dark:from-indigo-950/40 dark:to-sky-900/20 rounded-full blur-3xl animate-float-1 [animation-delay:3s]"></div>
        <div class="absolute inset-0 bg-[radial-gradient(#6366f1_1px,transparent_1px)] [background-size:24px_24px] opacity-[0.03] dark:opacity-[0.05]"></div>
    </div>

    {{-- Top Left: Home / Storefront Quick Link --}}
    <div class="fixed top-4 left-4 z-50">
        <a href="{{ url('/') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-2xl bg-white/85 dark:bg-slate-800/85 backdrop-blur-xl border border-slate-200/80 dark:border-slate-700/80 shadow-md shadow-slate-900/5 text-xs font-bold text-slate-700 dark:text-slate-200 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            <span class="font-myanmar hidden sm:inline">ပင်မစာမျက်နှာ</span>
        </a>
    </div>

    {{-- Top Right: Floating Glass Pill Controls (Language Switcher + Theme Switcher) --}}
    <div class="fixed top-4 right-4 z-50 flex items-center gap-1 p-1 rounded-2xl bg-white/85 dark:bg-slate-800/85 backdrop-blur-xl border border-slate-200/80 dark:border-slate-700/80 shadow-md shadow-slate-900/5">
        {{-- Language Switcher Component --}}
        <x-language-switcher id="auth-header" align="right" />

        <div class="w-px h-5 bg-slate-200 dark:bg-slate-700 mx-0.5" aria-hidden="true"></div>

        {{-- Theme Switcher --}}
        <button
            @click="darkMode = !darkMode; localStorage.setItem('theme', darkMode ? 'dark' : 'light'); $nextTick(() => darkMode ? document.documentElement.classList.add('dark') : document.documentElement.classList.remove('dark'))"
            type="button"
            class="h-10 w-10 inline-flex items-center justify-center rounded-xl text-slate-700 dark:text-amber-400 hover:bg-slate-100 dark:hover:bg-slate-700/80 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            :aria-label="darkMode ? 'Switch to light mode' : 'Switch to dark mode'"
        >
            <svg x-show="!darkMode" class="h-5 w-5 text-slate-700 transition-transform duration-300 rotate-0 hover:rotate-45" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.8A8.5 8.5 0 1111.2 3a6.5 6.5 0 009.8 9.8z" />
            </svg>
            <svg x-show="darkMode" class="h-5 w-5 text-amber-400 transition-transform duration-300 rotate-0 hover:rotate-90" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 14v2m9-9h-2M5 12H3m15.36-6.36-1.42 1.42M7.06 16.94l-1.42 1.42m12.72 0-1.42-1.42M7.06 7.06 5.64 5.64" />
                <circle cx="12" cy="12" r="4" />
            </svg>
        </button>
    </div>

    {{-- Center Card Content --}}
    <main class="w-full max-w-md mx-auto my-auto relative z-10 pt-14 pb-4">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    {{-- Footer Watermark --}}
    <footer class="relative z-10 w-full max-w-md mx-auto mt-4 text-center text-xs font-semibold text-slate-400 dark:text-slate-500 font-myanmar space-y-1">
        <div class="flex items-center justify-center gap-1.5 opacity-90 text-[11px]">
            <svg class="w-3.5 h-3.5 text-indigo-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 1.944A11.954 11.954 0 012.166 5C2.056 5.649 2 6.319 2 7c0 5.225 3.34 9.67 8 11.317C14.66 16.67 18 12.225 18 7c0-.682-.057-1.35-.166-2.001A11.954 11.954 0 0110 1.944zM11 14a1 1 0 11-2 0 1 1 0 012 0zm0-7a1 1 0 10-2 0v3a1 1 0 102 0V7z" clip-rule="evenodd"/></svg>
            <span>256-Bit SSL Encrypted & Secure Multi-Tenant System</span>
        </div>
        <p class="text-[11px] text-slate-400 dark:text-slate-600">
            © {{ date('Y') }} DataPOS · {{ __('messages.trusted_by_us') }}
        </p>
    </footer>
</body>
</html>
