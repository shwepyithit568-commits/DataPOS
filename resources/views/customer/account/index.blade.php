@extends('layouts.storefront.app')

@section('content')
@php
    $accountUrlSuffix = $store ? '?store_slug=' . $store->slug : '';
@endphp
<div class="max-w-4xl mx-auto space-y-6">
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-white/50 dark:border-slate-800/80 shadow-2xl space-y-6">
        <div class="flex items-center space-x-4">
            <div class="h-16 w-16 bg-gradient-to-tr from-violet-600 to-rose-500 text-white rounded-2xl flex items-center justify-center font-black text-2xl font-outfit shadow-lg shadow-violet-500/30">
                {{ substr($user->name, 0, 1) }}
            </div>
            <div class="space-y-1">
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white font-outfit">
                    {{ $user->name }}
                </h1>
                <p class="text-xs font-mono text-slate-500 dark:text-slate-400">
                    ဖုန်း (Phone): {{ $user->phone }}
                </p>
                <div class="pt-1">
                    <span class="px-3 py-1 text-xs font-extrabold rounded-full uppercase tracking-wider {{ $isWholesaleApproved ? 'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 border border-emerald-300' : 'bg-violet-100 dark:bg-violet-950/80 text-violet-700 dark:text-violet-300 border border-violet-300' }}">
                        {{ $isWholesaleApproved ? 'Approved Wholesale Customer' : 'Retail Customer' }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Notification Preferences (Web Push) --}}
        <div class="pt-6 border-t border-slate-200/60 dark:border-slate-800/60">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <h2 class="text-base font-extrabold text-slate-900 dark:text-white font-outfit flex items-center gap-2">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-violet-600 to-fuchsia-500 text-white shadow-sm" aria-hidden="true">🔔</span>
                        {{ __('messages.push_prefs_title') }}
                    </h2>
                    <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        {{ __('messages.push_prefs_desc') }}
                    </p>
                    <p id="push-prefs-status" class="mt-1 text-xs font-bold text-slate-600 dark:text-slate-300">
                        {{ __('messages.push_prefs_disabled') }}
                    </p>
                </div>
                <label class="relative inline-flex shrink-0 cursor-pointer items-center" aria-label="{{ __('messages.push_prefs_title') }}">
                    <input
                        type="checkbox"
                        id="push-prefs-toggle"
                        role="switch"
                        autocomplete="off"
                        class="peer sr-only"
                    >
                    <span class="relative inline-flex h-7 w-12 items-center rounded-full bg-slate-300 transition-colors duration-200 peer-checked:bg-emerald-500 focus-within:ring-2 focus-within:ring-violet-500 focus-within:ring-offset-2 dark:bg-slate-700 dark:peer-checked:bg-emerald-500 dark:focus-within:ring-offset-slate-900"></span>
                    <span class="absolute left-1 inline-block h-5 w-5 rounded-full bg-white shadow transition-transform duration-200 peer-checked:translate-x-5"></span>
                </label>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-6 border-t border-slate-200/60 dark:border-slate-800/60">
            <a href="{{ url('/account/orders' . $accountUrlSuffix) }}" class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800/60 hover:border-violet-500 hover:shadow-lg transition text-center space-y-1">
                <div class="text-2xl">📦</div>
                <div class="font-extrabold text-violet-600 dark:text-violet-400 font-outfit text-sm">My Orders</div>
                <div class="text-xs text-slate-500 dark:text-slate-400 font-myanmar">အော်ဒါမှတ်တမ်းများ</div>
            </a>
            <a href="{{ url('/account/favorites' . $accountUrlSuffix) }}" class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800/60 hover:border-purple-500 hover:shadow-lg transition text-center space-y-1">
                <div class="text-2xl">❤️</div>
                <div class="font-extrabold text-purple-600 dark:text-purple-400 font-outfit text-sm">Favorites</div>
                <div class="text-xs text-slate-500 dark:text-slate-400 font-myanmar">သိမ်းဆည်းထားသည်များ</div>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="block">
                @csrf
                <button type="submit" class="w-full h-full p-5 rounded-2xl bg-rose-50/60 dark:bg-rose-950/40 border border-rose-200/60 dark:border-rose-900/50 hover:bg-rose-100 transition text-center space-y-1">
                    <div class="text-2xl">🚪</div>
                    <div class="font-extrabold text-rose-600 dark:text-rose-400 font-outfit text-sm">Log Out</div>
                    <div class="text-xs text-rose-500/80 font-myanmar">ထွက်မည်</div>
                </button>
            </form>

            @if ($adminUrl)
                <a href="{{ $adminUrl }}" class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800/60 hover:border-violet-500 hover:shadow-lg transition text-center space-y-1">
                    <div class="text-2xl">🛠️</div>
                    <div class="font-extrabold text-violet-600 dark:text-violet-400 font-outfit text-sm">Admin Panel</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 font-myanmar">စီမံခန့်ခွဲမှု</div>
                </a>
            @endif
        </div>
    </div>
</div>
@endsection
