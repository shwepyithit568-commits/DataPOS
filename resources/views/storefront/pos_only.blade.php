@extends('layouts.storefront.app')

@section('title', ($setting?->store_name ?: ($store->name ?? 'DataPOS Store')) . ' · ' . __('messages.pos_only_notice_badge'))

@section('main_padding', 'px-0.5 sm:px-3 lg:px-6 py-4 sm:py-8')

@section('content')
<div class="max-w-2xl mx-auto">
    {{-- Main 3D Card Container --}}
    <div class="rounded-xl sm:rounded-2xl border border-slate-200 bg-white p-5 sm:p-8 shadow-xl dark:border-slate-800 dark:bg-slate-900 transition-all text-center">
        
        {{-- Store Hero Icon / Avatar --}}
        <div class="relative inline-flex mb-4">
            <div class="inline-flex h-20 w-20 sm:h-24 sm:w-24 items-center justify-center rounded-2xl bg-gradient-to-tr from-sky-500 via-indigo-500 to-violet-600 text-4xl sm:text-5xl shadow-lg shadow-sky-500/20 text-white border border-sky-300 dark:border-sky-400/40">
                🏬
            </div>
            <span class="absolute -bottom-1.5 -right-1.5 inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-500 text-white text-[10px] font-black tracking-wider uppercase shadow-xs ring-2 ring-white dark:ring-slate-900">
                <span class="h-1.5 w-1.5 rounded-full bg-white animate-pulse"></span>
                {{ __('messages.open') }}
            </span>
        </div>
        
        {{-- Store Name & Eyebrow --}}
        <h1 class="text-xl sm:text-2xl lg:text-3xl font-black text-slate-900 dark:text-white font-outfit tracking-tight">
            {{ $setting?->store_name ?: ($store->name ?? 'DataPOS Store') }}
        </h1>
        
        <p class="mt-1.5 text-xs sm:text-sm font-bold text-sky-600 dark:text-sky-400">
            {{ $setting?->tagline ?: 'In-Store Physical Counter & POS' }}
        </p>

        {{-- Eyebrow 3D Badge --}}
        <div class="mt-3 flex justify-center">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-amber-50 dark:bg-amber-950/50 border border-amber-300 dark:border-amber-800 text-amber-800 dark:text-amber-300 text-xs font-black shadow-2xs">
                <span>🏪</span>
                <span>{{ __('messages.pos_only_notice_badge') }} (In-Store / POS Only)</span>
            </span>
        </div>

        {{-- Notice Description Box --}}
        <div class="mt-5 p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/80 text-xs sm:text-sm text-slate-700 dark:text-slate-200 font-myanmar leading-relaxed text-center sm:text-left flex items-start gap-3">
            <span class="text-xl shrink-0 pt-0.5 hidden sm:inline">📢</span>
            <div class="flex-1 min-w-0">
                <p>{{ __('messages.pos_only_notice_desc') }}</p>
            </div>
        </div>

        {{-- Information Grid: Hours & Address --}}
        <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-left">
            @if ($setting?->opening_hours)
                <div class="p-3 rounded-xl bg-sky-50/70 dark:bg-sky-950/40 border border-sky-200/80 dark:border-sky-800/60 flex items-center gap-3">
                    <span class="text-xl shrink-0">⏰</span>
                    <div class="min-w-0">
                        <span class="text-[10px] font-extrabold uppercase tracking-wide text-sky-800 dark:text-sky-300 block font-myanmar">{{ __('messages.opening_hours') }}</span>
                        <span class="text-xs sm:text-sm font-black text-slate-900 dark:text-white truncate block">{{ $setting->opening_hours }}</span>
                    </div>
                </div>
            @endif

            @if ($setting?->address)
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 flex items-center gap-3">
                    <span class="text-xl shrink-0">📍</span>
                    <div class="min-w-0">
                        <span class="text-[10px] font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400 block font-myanmar">{{ __('messages.vouchers_address') }}</span>
                        <span class="text-xs sm:text-sm font-semibold text-slate-800 dark:text-slate-200 truncate block font-myanmar">{{ $setting->address }}</span>
                    </div>
                </div>
            @endif
        </div>

        {{-- Mini Map Embed if enabled --}}
        @if (!empty($setting?->map_enabled) && !empty($setting?->map_embed_enabled) && $setting?->mapEmbedSrc())
            <div class="mt-5 rounded-xl overflow-hidden border border-slate-200 dark:border-slate-800 shadow-2xs">
                <div class="relative w-full h-36 sm:h-44">
                    <iframe class="w-full h-full border-0"
                            src="{{ $setting->mapEmbedSrc() }}"
                            title="{{ $setting->map_title ?: ($store->name ?? 'DataPOS Store') }}"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            allowfullscreen>
                    </iframe>
                </div>
            </div>
        @endif

        {{-- Action Buttons Suite (Pure CSS 3D Buttons) --}}
        <div class="mt-6 pt-5 border-t border-slate-100 dark:border-slate-800 space-y-3">
            <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500 font-myanmar block">
                {{ __('messages.pos_only_support_title') }}
            </span>

            <div class="flex flex-wrap items-center justify-center gap-2 sm:gap-2.5">
                @if ($setting?->phone)
                    <a href="tel:{{ $setting->phone }}" class="sf-btn-3d-success inline-flex items-center gap-2 px-5 py-2 text-xs font-black" title="{{ __('messages.call_now') }}">
                        <span>📞</span>
                        <span>{{ __('messages.call_now') }}</span>
                    </a>
                @endif

                @if ($setting?->viberUrl())
                    <a href="{{ $setting->viberUrl() }}" target="_blank" rel="noopener noreferrer" class="sf-btn-3d-viber inline-flex items-center gap-2 px-4 py-2 text-xs font-bold" title="Viber Chat">
                        <x-brand-icon brand="viber" class="h-4 w-4 fill-current"/>
                        <span>Viber</span>
                    </a>
                @endif

                @if ($setting?->telegramUrl())
                    <a href="{{ $setting->telegramUrl() }}" target="_blank" rel="noopener noreferrer" class="sf-btn-3d-telegram inline-flex items-center gap-2 px-4 py-2 text-xs font-bold" title="Telegram Chat">
                        <x-brand-icon brand="telegram" class="h-4 w-4 fill-current"/>
                        <span>Telegram</span>
                    </a>
                @endif

                @if ($setting?->mapDirectionsUrl())
                    <a href="{{ $setting->mapDirectionsUrl() }}" target="_blank" rel="noopener noreferrer" class="sf-btn-3d inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold" title="{{ __('messages.get_directions') }}">
                        <span>🧭</span>
                        <span>{{ __('messages.get_directions') }}</span>
                    </a>
                @endif
            </div>

            {{-- POS Staff Quick Entrance --}}
            @auth
                @if ($store && $store->users()->where('users.id', auth()->id())->exists())
                    <div class="pt-3">
                        <a href="{{ route('pos.index', ['store_slug' => $store->slug]) }}" class="sf-btn-3d-primary inline-flex items-center gap-2 px-6 py-2.5 text-xs font-black shadow-md">
                            <span>🧾</span>
                            <span>{{ __('messages.pos_sale') }} →</span>
                        </a>
                    </div>
                @endif
            @endauth
        </div>

    </div>
</div>
@endsection
