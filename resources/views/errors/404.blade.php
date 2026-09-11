<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page Not Found — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-50 text-gray-900 font-sans antialiased flex items-center justify-center min-h-screen p-6">
    <div class="text-center max-w-md space-y-4">
        <div class="text-7xl font-black text-violet-600 font-outfit">404</div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white font-outfit">စာမျက်နှာ မတွေ့ပါ</h1>

        @if (!empty($exception?->getMessage()))
            <div class="p-3.5 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/80 rounded-xl text-amber-900 dark:text-amber-200 text-xs sm:text-sm font-medium text-left space-y-1 shadow-2xs">
                <div class="flex items-center gap-1.5 font-bold text-amber-800 dark:text-amber-300">
                    <span>⚠️</span>
                    <span>အကြောင်းရင်း (Reason):</span>
                </div>
                <p class="pl-5 leading-relaxed">{{ $exception->getMessage() }}</p>
            </div>
        @else
            <p class="text-gray-600 dark:text-gray-400 text-sm">သင်ရှာဖွေနေသော စာမျက်နှာကို ရှာမတွေ့ပါ။ ကျေးဇူးပြု၍ ပြန်လည်စစ်ဆေးပါ။</p>
            <p class="text-xs text-gray-500">The page you are looking for could not be found.</p>
        @endif

        <div class="flex items-center justify-center gap-2.5 pt-2">
            <button type="button" onclick="window.history.back()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold text-xs rounded-lg transition cursor-pointer">
                ← ရှေ့စာမျက်နှာသို့ (Go Back)
            </button>
            <a href="{{ url('/') }}" class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white font-bold text-xs rounded-lg transition">
                ပင်မစာမျက်နှာသို့ (Home)
            </a>
        </div>
    </div>
</body>
</html>
