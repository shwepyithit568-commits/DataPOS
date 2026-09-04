@extends('layouts.admin.app')

@section('title', 'Platform Dashboard - DataPOS')
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
<div class="w-full space-y-3 pb-8">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl px-3 py-2.5 shadow-2xs">
        <div>
            <h1 class="text-sm sm:text-base font-black text-slate-900 dark:text-white leading-tight font-outfit">
                Platform Owner Store Selector
            </h1>
            <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">
                Please select a store to view its specific admin control dashboard or manage store resources:
            </p>
        </div>
        <div class="flex items-center gap-1.5 self-start sm:self-auto">
            <a
                href="{{ route('admin.stores.create') }}"
                class="inline-flex h-8 items-center gap-1 rounded-lg bg-violet-600 px-3 text-xs font-black text-white hover:bg-violet-700 transition shadow-2xs"
            >
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>{{ __('messages.store_create_title') }}</span>
            </a>
            <a
                href="{{ route('admin.stores.index') }}"
                class="inline-flex h-8 items-center gap-1 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition"
            >
                <span>{{ __('messages.all_stores') }}</span>
            </a>
        </div>
    </div>

    {{-- Centered Row-based Stat Cards (Admin UI/UX Standard v4.1) --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-1 sm:gap-1.5">
        {{-- Total Stores --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-2.5 shadow-2xs">
            <div class="flex items-center justify-center gap-2.5 sm:gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-violet-500/10 text-violet-600 dark:bg-violet-500/20 dark:text-violet-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.614A2.993 2.993 0 009 9.35c.69 0 1.32-.232 1.82-.624a3.002 3.002 0 003.88 0c.5.392 1.13.624 1.82.624a2.993 2.993 0 002.48-1.314 3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .414.336.75.75.75z" />
                    </svg>
                </div>
                <div class="text-center">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 leading-none">
                        {{ __('messages.all_stores') }}
                    </div>
                    <div class="mt-1 text-base sm:text-lg font-black text-slate-900 dark:text-white leading-tight">
                        {{ $totalStores ?? $stores->count() }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Active Stores --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-2.5 shadow-2xs">
            <div class="flex items-center justify-center gap-2.5 sm:gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="text-center">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 leading-none">
                        {{ __('messages.store_active') }}
                    </div>
                    <div class="mt-1 text-base sm:text-lg font-black text-emerald-600 dark:text-emerald-400 leading-tight">
                        {{ $activeStores ?? $stores->where('is_active', true)->count() }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Inactive Stores --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-2.5 shadow-2xs">
            <div class="flex items-center justify-center gap-2.5 sm:gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                </div>
                <div class="text-center">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 leading-none">
                        {{ __('messages.store_inactive') }}
                    </div>
                    <div class="mt-1 text-base sm:text-lg font-black text-amber-600 dark:text-amber-400 leading-tight">
                        {{ $inactiveStores ?? $stores->where('is_active', false)->count() }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Total Users --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-2.5 shadow-2xs">
            <div class="flex items-center justify-center gap-2.5 sm:gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-sky-500/10 text-sky-600 dark:bg-sky-500/20 dark:text-sky-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </div>
                <div class="text-center">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 leading-none">
                        {{ __('messages.users') }}
                    </div>
                    <div class="mt-1 text-base sm:text-lg font-black text-slate-900 dark:text-white leading-tight">
                        {{ $totalUsers ?? '-' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Stores Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 sm:gap-3">
        @foreach ($stores as $store)
            <div class="group flex flex-col justify-between bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-3 shadow-2xs hover:border-violet-300 dark:hover:border-violet-700/60 transition">
                <div>
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <h3 class="font-bold text-sm sm:text-base text-slate-900 dark:text-slate-100 font-outfit truncate">
                                {{ $store->name }}
                            </h3>
                            <p class="text-[11px] text-slate-400 dark:text-slate-500 font-mono truncate">
                                slug: {{ $store->slug }}
                            </p>
                        </div>
                        <div class="shrink-0 flex items-center gap-1">
                            @if ($store->is_primary)
                                <span class="px-1.5 py-0.5 rounded-md bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 text-[10px] font-black border border-amber-200 dark:border-amber-800/60">
                                    Primary
                                </span>
                            @endif
                            @if ($store->is_active)
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 text-[10px] font-black border border-emerald-200 dark:border-emerald-800/60">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-[10px] font-black">
                                    Inactive
                                </span>
                            @endif
                        </div>
                    </div>

                    @if (isset($store->products_count))
                        <div class="mt-2 text-[11px] text-slate-500 dark:text-slate-400">
                            <span>{{ __('messages.products') }}:</span>
                            <span class="font-bold text-slate-700 dark:text-slate-200">{{ $store->products_count }}</span>
                        </div>
                    @endif
                </div>

                {{-- Action Buttons --}}
                <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800 flex items-center gap-1.5 flex-wrap">
                    @if ($store->is_active)
                        <a
                            href="{{ url('/store/' . $store->slug . '/admin/dashboard') }}"
                            class="inline-flex h-7 items-center gap-1 px-2.5 rounded-lg bg-violet-600 text-white hover:bg-violet-700 text-[11px] font-black transition shadow-2xs"
                            title="{{ __('messages.store_open_admin') }}"
                        >
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.3 4.5 3 9l7.3 4.5L17.6 9 10.3 4.5Zm0 6L3 15l7.3 4.5L17.6 15l-7.3-4.5Z"/></svg>
                            <span>{{ __('messages.store_open_admin') }}</span>
                        </a>
                        <a
                            href="{{ url('/store/' . $store->slug . '/pos') }}"
                            class="inline-flex h-7 items-center gap-1 px-2 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 border border-emerald-200 dark:border-emerald-800/60 text-[11px] font-black transition"
                            title="POS"
                        >
                            <span>POS</span>
                        </a>
                    @endif
                    <a
                        href="{{ url('/store/' . $store->slug) }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex h-7 items-center gap-1 px-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 text-[11px] font-bold border border-slate-200 dark:border-slate-700 transition"
                        title="{{ __('messages.store_open_storefront') }}"
                    >
                        <span>{{ __('messages.store_open_storefront') }}</span>
                    </a>
                    <a
                        href="{{ route('admin.stores.edit', $store->id) }}"
                        class="inline-flex h-7 items-center px-2 rounded-lg text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 text-[11px] font-bold hover:bg-slate-100 dark:hover:bg-slate-800 transition ml-auto"
                        title="{{ __('messages.edit') }}"
                    >
                        <span>{{ __('messages.edit') }}</span>
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
