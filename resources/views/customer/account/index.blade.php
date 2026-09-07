@extends('layouts.storefront.app')

@section('title', __('messages.account_my_profile'))
@section('main_padding', 'px-0.5 sm:px-3 lg:px-6 py-1 sm:py-3')

@section('content')
@php
    $accountUrlSuffix = $store ? '?store_slug=' . $store->slug : '';
    $userInitial = mb_substr($user->name ?? 'U', 0, 1);
@endphp

<div class="max-w-4xl mx-auto space-y-2 sm:space-y-4 select-none font-sans pb-12">
    <div class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl p-3 sm:p-6 lg:p-7 border border-slate-200/90 dark:border-slate-800 shadow-sm sm:shadow-lg space-y-3.5 sm:space-y-5">
        
        {{-- Profile Header --}}
        <div class="flex items-center gap-3 sm:gap-5">
            <div class="relative shrink-0">
                <div class="h-14 w-14 sm:h-16 sm:w-16 bg-gradient-to-tr from-violet-600 via-purple-600 to-rose-500 text-white rounded-2xl flex items-center justify-center font-black text-xl sm:text-2xl font-outfit shadow-md shadow-violet-500/20">
                    {{ $userInitial }}
                </div>
                <span class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full bg-emerald-500 border-2 border-white dark:border-slate-900" title="Active"></span>
            </div>
            <div class="space-y-1 min-w-0 flex-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-base sm:text-xl font-extrabold text-slate-900 dark:text-white font-outfit truncate">
                        {{ $user->name }}
                    </h1>
                </div>
                <p class="text-xs font-mono text-slate-500 dark:text-slate-400 flex items-center gap-1.5 truncate">
                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    <span>{{ __('messages.account_phone_label') }}: <span class="font-bold text-slate-700 dark:text-slate-300">{{ $user->phone }}</span></span>
                </p>
                <div class="pt-0.5 flex flex-wrap items-center gap-1.5">
                    <span class="px-2.5 py-0.5 text-[11px] sm:text-xs font-black rounded-full uppercase tracking-wider flex items-center gap-1 {{ $isWholesaleApproved ? 'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700' : 'bg-violet-100 dark:bg-violet-950/80 text-violet-700 dark:text-violet-300 border border-violet-300 dark:border-violet-700' }}">
                        @if ($isWholesaleApproved)
                            <span>⭐</span>
                            <span>{{ __('messages.account_tier_wholesale') }}</span>
                        @else
                            <span>🛍️</span>
                            <span>{{ __('messages.account_tier_retail') }}</span>
                        @endif
                    </span>
                    @if (!$isWholesaleApproved && $wholesaleApplication && $wholesaleApplication->status === 'pending')
                        <a href="{{ $wholesaleUrl }}" class="px-2.5 py-0.5 text-[11px] font-bold rounded-full bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-700 flex items-center gap-1 hover:bg-amber-200 dark:hover:bg-amber-900/80 transition-colors">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                            <span>{{ __('messages.wholesale_account_status_pending') }}</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Centered Row-based Quick Stat Cards --}}
        <div class="grid grid-cols-3 gap-1.5 sm:gap-3">
            <a href="{{ url('/account/orders' . $accountUrlSuffix) }}" class="p-2 sm:p-3 rounded-xl sm:rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/60 flex flex-col sm:flex-row items-center justify-center text-center sm:text-left gap-1 sm:gap-2.5 hover:border-violet-500/70 hover:shadow-xs transition-all group">
                <div class="h-8 w-8 sm:h-9 sm:w-9 rounded-xl bg-violet-100 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 flex items-center justify-center text-base sm:text-lg group-hover:scale-105 transition-transform shrink-0">
                    📦
                </div>
                <div class="min-w-0">
                    <div class="text-sm sm:text-lg font-black text-slate-900 dark:text-white font-outfit leading-tight">
                        {{ $ordersCount ?? 0 }}
                    </div>
                    <div class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 truncate font-myanmar">
                        {{ __('messages.account_stat_orders') }}
                    </div>
                </div>
            </a>

            <a href="{{ url('/account/favorites' . $accountUrlSuffix) }}" class="p-2 sm:p-3 rounded-xl sm:rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/60 flex flex-col sm:flex-row items-center justify-center text-center sm:text-left gap-1 sm:gap-2.5 hover:border-rose-500/70 hover:shadow-xs transition-all group">
                <div class="h-8 w-8 sm:h-9 sm:w-9 rounded-xl bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center text-base sm:text-lg group-hover:scale-105 transition-transform shrink-0">
                    ❤️
                </div>
                <div class="min-w-0">
                    <div class="text-sm sm:text-lg font-black text-slate-900 dark:text-white font-outfit leading-tight">
                        {{ $favoritesCount ?? 0 }}
                    </div>
                    <div class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 truncate font-myanmar">
                        {{ __('messages.account_stat_favorites') }}
                    </div>
                </div>
            </a>

            <div class="p-2 sm:p-3 rounded-xl sm:rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/60 flex flex-col sm:flex-row items-center justify-center text-center sm:text-left gap-1 sm:gap-2.5">
                <div class="h-8 w-8 sm:h-9 sm:w-9 rounded-xl {{ $isWholesaleApproved ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400' : 'bg-sky-100 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400' }} flex items-center justify-center text-base sm:text-lg shrink-0">
                    {{ $isWholesaleApproved ? '⭐' : '🏷️' }}
                </div>
                <div class="min-w-0">
                    <div class="text-xs sm:text-sm font-black text-slate-900 dark:text-white font-outfit leading-tight truncate">
                        {{ $isWholesaleApproved ? 'Wholesale' : 'Retail' }}
                    </div>
                    <div class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 truncate font-myanmar">
                        {{ __('messages.account_stat_tier') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Wholesale Partner Invitation / Status Card (Only for Retail Customers) --}}
        @if (!$isWholesaleApproved)
            <div class="p-3 sm:p-4 rounded-xl sm:rounded-2xl bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-sky-500/5 dark:from-amber-950/30 dark:to-slate-800/80 border border-amber-300/80 dark:border-amber-700/60 shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-4">
                <div class="flex items-start sm:items-center gap-2.5 sm:gap-3.5 min-w-0">
                    <div class="h-9 w-9 sm:h-11 sm:w-11 rounded-xl bg-gradient-to-br from-amber-500 to-amber-600 text-white flex items-center justify-center text-lg sm:text-xl shadow-xs shrink-0">
                        🤝
                    </div>
                    <div class="space-y-0.5 min-w-0">
                        <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap">
                            <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white font-outfit">
                                {{ $wholesaleApplication && $wholesaleApplication->status === 'pending' ? __('messages.wholesale_account_pending_title') : __('messages.wholesale_account_card_title') }}
                            </h3>
                            @if ($wholesaleApplication && $wholesaleApplication->status === 'pending')
                                <span class="inline-flex items-center gap-1 px-2 py-0.2 rounded-full text-[10px] font-bold bg-amber-100 dark:bg-amber-900/60 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                    {{ __('messages.wholesale_account_status_pending') }}
                                </span>
                            @endif
                        </div>
                        <p class="text-[11px] sm:text-xs text-slate-600 dark:text-slate-300 leading-relaxed font-myanmar">
                            {{ $wholesaleApplication && $wholesaleApplication->status === 'pending' ? __('messages.wholesale_pending_hint') : __('messages.wholesale_account_card_desc') }}
                        </p>
                    </div>
                </div>
                <a href="{{ $wholesaleUrl }}" class="w-full sm:w-auto shrink-0 inline-flex items-center justify-center gap-1.5 px-4 sm:px-5 py-2 sm:py-2.5 rounded-xl text-xs font-black text-white sf-btn-3d-primary shadow-xs hover:scale-[1.02] active:scale-95 transition-all">
                    <span>{{ $wholesaleApplication && $wholesaleApplication->status === 'pending' ? __('messages.wholesale_account_status_view') : __('messages.wholesale_account_apply_btn') }}</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        @endif

        {{-- Notification Preferences (Web Push) --}}
        <div class="pt-3 sm:pt-4 border-t border-slate-200/70 dark:border-slate-800/70">
            <div class="flex items-start justify-between gap-3 sm:gap-4 p-3 rounded-xl sm:rounded-2xl bg-slate-50/70 dark:bg-slate-800/40 border border-slate-200/70 dark:border-slate-800/70">
                <div class="min-w-0">
                    <h2 class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white font-outfit flex items-center gap-2">
                        <span class="inline-flex h-7 w-7 sm:h-8 sm:w-8 items-center justify-center rounded-lg sm:rounded-xl bg-gradient-to-br from-violet-600 to-fuchsia-500 text-white text-xs sm:text-sm shadow-xs" aria-hidden="true">🔔</span>
                        {{ __('messages.push_prefs_title') }}
                    </h2>
                    <p class="mt-1 text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 leading-relaxed font-myanmar">
                        {{ __('messages.push_prefs_desc') }}
                    </p>
                    <p id="push-prefs-status" class="mt-1 text-[11px] font-bold text-slate-600 dark:text-slate-300">
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
                    <span class="relative inline-flex h-6 w-11 items-center rounded-full bg-slate-300 transition-colors duration-200 peer-checked:bg-emerald-500 focus-within:ring-2 focus-within:ring-violet-500 focus-within:ring-offset-2 dark:bg-slate-700 dark:peer-checked:bg-emerald-500 dark:focus-within:ring-offset-slate-900"></span>
                    <span class="absolute left-0.5 inline-block h-5 w-5 rounded-full bg-white shadow transition-transform duration-200 peer-checked:translate-x-5"></span>
                </label>
            </div>
        </div>

        {{-- Quick Actions 3D Tactile Buttons (2-Columns on Mobile) --}}
        <div class="pt-2 sm:pt-4 border-t border-slate-200/70 dark:border-slate-800/70">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2 font-outfit">
                {{ __('messages.account_quick_actions') }}
            </h3>
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-1.5 sm:gap-2.5">
                {{-- 1. My Orders --}}
                <a href="{{ url('/account/orders' . $accountUrlSuffix) }}" class="sf-btn-3d flex flex-col items-center justify-center p-2 sm:p-2.5 !rounded-xl sm:!rounded-2xl w-full text-center group cursor-pointer">
                    <span class="text-base sm:text-lg group-hover:scale-110 transition-transform">📦</span>
                    <span class="font-black text-slate-900 dark:text-white font-outfit text-xs sm:text-sm leading-tight mt-0.5">
                        {{ __('messages.account_tile_orders_title') }}
                    </span>
                    <span class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 font-myanmar truncate max-w-full px-1">
                        {{ __('messages.account_tile_orders_sub') }}
                    </span>
                </a>

                {{-- 2. Favorites --}}
                <a href="{{ url('/account/favorites' . $accountUrlSuffix) }}" class="sf-btn-3d flex flex-col items-center justify-center p-2 sm:p-2.5 !rounded-xl sm:!rounded-2xl w-full text-center group cursor-pointer">
                    <span class="text-base sm:text-lg group-hover:scale-110 transition-transform">❤️</span>
                    <span class="font-black text-slate-900 dark:text-white font-outfit text-xs sm:text-sm leading-tight mt-0.5">
                        {{ __('messages.account_tile_favs_title') }}
                    </span>
                    <span class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 font-myanmar truncate max-w-full px-1">
                        {{ __('messages.account_tile_favs_sub') }}
                    </span>
                </a>

                {{-- 3. Wholesale Application (Only for Retail Customers) --}}
                @if (!$isWholesaleApproved)
                    <a href="{{ $wholesaleUrl }}" class="sf-btn-3d sf-btn-3d-gold flex flex-col items-center justify-center p-2 sm:p-2.5 !rounded-xl sm:!rounded-2xl w-full text-center group cursor-pointer">
                        <span class="text-base sm:text-lg group-hover:scale-110 transition-transform">🤝</span>
                        <span class="font-black font-outfit text-xs sm:text-sm leading-tight mt-0.5 truncate max-w-full px-1">
                            {{ $wholesaleApplication && $wholesaleApplication->status === 'pending' ? __('messages.wholesale_account_status_view') : __('messages.wholesale_account_apply_btn') }}
                        </span>
                        <span class="text-[10px] sm:text-[11px] font-myanmar truncate max-w-full px-1 opacity-90">
                            {{ $wholesaleApplication && $wholesaleApplication->status === 'pending' ? __('messages.wholesale_account_status_pending') : __('messages.wholesale_account_apply_desc') }}
                        </span>
                    </a>
                @endif

                {{-- 4. Admin Panel (Staff/Manager Only) --}}
                @if ($adminUrl)
                    <a href="{{ $adminUrl }}" class="sf-btn-3d flex flex-col items-center justify-center p-2 sm:p-2.5 !rounded-xl sm:!rounded-2xl w-full text-center group cursor-pointer">
                        <span class="text-base sm:text-lg group-hover:scale-110 transition-transform">🛠️</span>
                        <span class="font-black text-indigo-600 dark:text-indigo-400 font-outfit text-xs sm:text-sm leading-tight mt-0.5">
                            {{ __('messages.account_tile_admin_title') }}
                        </span>
                        <span class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 font-myanmar truncate max-w-full px-1">
                            {{ __('messages.account_tile_admin_sub') }}
                        </span>
                    </a>
                @endif

                {{-- 5. Log Out --}}
                <form method="POST" action="{{ route('logout') }}" class="block {{ (!$isWholesaleApproved && $adminUrl) ? 'col-span-2 sm:col-span-1' : '' }}">
                    @csrf
                    <button type="submit" class="sf-btn-3d sf-btn-3d-danger flex flex-col items-center justify-center p-2 sm:p-2.5 !rounded-xl sm:!rounded-2xl w-full text-center group cursor-pointer">
                        <span class="text-base sm:text-lg group-hover:scale-110 transition-transform">🚪</span>
                        <span class="font-black font-outfit text-xs sm:text-sm leading-tight mt-0.5">
                            {{ __('messages.account_tile_logout_title') }}
                        </span>
                        <span class="text-[10px] sm:text-[11px] font-myanmar truncate max-w-full px-1 opacity-90">
                            {{ __('messages.account_tile_logout_sub') }}
                        </span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
