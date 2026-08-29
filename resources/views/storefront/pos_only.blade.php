@extends('layouts.storefront.app')

@section('content')
<div class="max-w-3xl mx-auto py-12 px-4 text-center">
    <div class="rounded-3xl border border-slate-200 bg-white p-8 sm:p-12 shadow-xl dark:border-slate-800 dark:bg-slate-900">
        <div class="inline-flex h-20 w-20 items-center justify-center rounded-3xl bg-gradient-to-tr from-sky-500 to-violet-600 text-4xl shadow-lg shadow-sky-500/20 text-white mb-6">
            🏬
        </div>
        
        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white font-outfit tracking-tight">
            {{ $setting?->store_name ?: ($store->name ?? 'DataPOS Store') }}
        </h1>
        
        <p class="mt-2 text-sm sm:text-base font-semibold text-sky-600 dark:text-sky-400">
            {{ $setting?->tagline ?: 'In-Store Physical Counter & POS' }}
        </p>

        <div class="mt-6 p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/80 text-sm text-slate-600 dark:text-slate-300 font-myanmar leading-relaxed">
            🏪 ဤဆိုင်ခွဲသည် <strong>ဆိုင်တွင်းအရောင်းကောင်တာ (In-Store Counter / POS Only)</strong> ဖြင့် ဝန်ဆောင်မှုပေးနေသော ဆိုင်ဖြစ်ပါသည်။ အွန်လိုင်းဝယ်ယူခြင်းအစား လူကြီးမင်းတို့အနေဖြင့် ဆိုင်သို့ ကိုယ်တိုင်ကိုယ်ကျ လာရောက်ဝယ်ယူ အားပေးနိုင်ပါသည်ခင်ဗျာ။
        </div>

        @if ($setting?->address)
            <div class="mt-6 flex items-center justify-center gap-2 text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-300 font-myanmar">
                <span>📍</span>
                <span>{{ $setting->address }}</span>
            </div>
        @endif

        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            @if ($setting?->phone)
                <a href="tel:{{ $setting->phone }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-violet-600 to-sky-600 text-white font-bold text-sm shadow-md hover:brightness-110 active:scale-95 transition">
                    <span>📞 {{ __('messages.call_now') }}</span>
                </a>
            @endif

            @if ($setting?->viberUrl())
                <a href="{{ $setting->viberUrl() }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl border border-violet-300 bg-violet-50 text-violet-700 dark:bg-violet-950/40 dark:text-violet-300 font-bold text-sm hover:bg-violet-100 transition">
                    <x-brand-icon brand="viber" class="h-4 w-4 fill-current"/>
                    <span>Viber</span>
                </a>
            @endif

            @if ($setting?->telegramUrl())
                <a href="{{ $setting->telegramUrl() }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl border border-sky-300 bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300 font-bold text-sm hover:bg-sky-100 transition">
                    <x-brand-icon brand="telegram" class="h-4 w-4 fill-current"/>
                    <span>Telegram</span>
                </a>
            @endif

            @auth
                @if ($store && $store->users()->where('users.id', auth()->id())->exists())
                    <a href="{{ route('pos.index', ['store_slug' => $store->slug]) }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-bold text-sm shadow-md hover:opacity-90 active:scale-95 transition">
                        <span>🧾 {{ __('messages.pos_sale') }} →</span>
                    </a>
                @endif
            @endauth
        </div>
    </div>
</div>
@endsection
