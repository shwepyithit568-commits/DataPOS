<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Auth - {{ $title ?? config('app.name', 'DataPOS') }}</title>
    {{-- Match the storefront PWA theme so the browser status bar is light blue here too --}}
    <meta name="theme-color" content="#38bdf8">
    <script nonce="{{ $cspNonce }}">
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-6px); }
            75% { transform: translateX(6px); }
        }
    </style>
</head>
    <body
    x-data="{ darkMode: localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches) }"
    class="bg-gradient-to-br from-slate-100 via-sky-50 to-violet-100 dark:from-slate-950 dark:via-slate-900 dark:to-violet-950 text-slate-900 dark:text-slate-100 font-sans antialiased min-h-dvh relative flex items-start sm:items-center justify-center p-4 sm:p-6 overflow-x-hidden"
>
    {{-- Background Liquid Glow Circles --}}
    <div class="fixed inset-0 pointer-events-none overflow-hidden z-0">
        <div class="absolute -top-32 -left-32 w-96 h-96 bg-sky-500/20 dark:bg-sky-500/15 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute top-1/2 -right-32 w-96 h-96 bg-purple-500/20 dark:bg-purple-500/15 rounded-full blur-3xl animate-pulse [animation-delay:2s]"></div>
        <div class="absolute -bottom-32 left-1/3 w-96 h-96 bg-violet-500/20 dark:bg-violet-500/15 rounded-full blur-3xl animate-pulse [animation-delay:4s]"></div>
    </div>

    <div class="fixed top-4 left-4 right-4 z-50 flex items-center justify-between gap-2">
        <x-language-switcher id="auth-header" align="left" />

        {{-- Theme Toggle --}}
        <button
            @click="darkMode = !darkMode; localStorage.setItem('theme', darkMode ? 'dark' : 'light'); $nextTick(() => darkMode ? document.documentElement.classList.add('dark') : document.documentElement.classList.remove('dark'))"
            type="button"
            class="h-11 w-11 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-amber-400 hover:bg-white dark:hover:bg-slate-700 shadow-lg transition inline-flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-sky-500"
            aria-label="{{ __('messages.theme_toggle') }}"
        >
            <svg x-show="!darkMode" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.8A8.5 8.5 0 1111.2 3a6.5 6.5 0 009.8 9.8z" />
            </svg>
            <svg x-show="darkMode" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v2m0 14v2m9-9h-2M5 12H3m15.36-6.36-1.42 1.42M7.06 16.94l-1.42 1.42m12.72 0-1.42-1.42M7.06 7.06 5.64 5.64" />
                <circle cx="12" cy="12" r="4" stroke-width="2" />
            </svg>
        </button>
    </div>

    {{-- Main Container --}}
    <div class="w-full max-w-md relative z-10">
        {{ $slot ?? '' }}
        @yield('content')
    </div>

</body>
</html>
