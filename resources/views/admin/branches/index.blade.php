@extends('layouts.admin.app')

@section('title', __('messages.branches_title') . ' - ' . ($store->name ?? 'DataPOS'))

@section('content')
<div class="w-full space-y-5 sm:space-y-6">

    {{-- ============================================================
         PAGE HEADER
         ============================================================ --}}
    <div class="admin-page-header">
        <div class="min-w-0">
            <p class="text-[11px] font-black uppercase tracking-wider text-violet-600 dark:text-violet-400">
                {{ __('messages.sidebar_setup') ?? 'Store & Location Setup' }}
            </p>
            <h1 class="admin-page-title mt-0.5">
                {{ __('messages.branches_title') }}
            </h1>
            <p class="admin-page-sub mt-1">
                {{ $store->name }} · {{ __('messages.branches_subtitle') }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            {{-- Add New Branch Button --}}
            <a href="{{ route('store.admin.branches.create', ['store_slug' => $store->slug]) }}"
               class="admin-primary-btn bg-violet-600 hover:bg-violet-500">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                <span>{{ __('messages.branches_add_new') }}</span>
            </a>
        </div>
    </div>

    {{-- Flash Notifications & Errors --}}
    @if (session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 rounded-2xl text-sm text-emerald-800 dark:text-emerald-200 flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 rounded-2xl text-sm text-rose-800 dark:text-rose-200">
            @foreach ($errors->all() as $err)
                <p>{{ $err }}</p>
            @endforeach
        </div>
    @endif

    {{-- ============================================================
         SUMMARY STATS HAIRLINE GRID
         ============================================================ --}}
    <div class="admin-hairline-grid grid-cols-2 sm:grid-cols-4">
        {{-- 1. Total Outlets --}}
        <div class="admin-hairline-cell bg-violet-50/30 dark:bg-violet-950/20">
            <div class="admin-stat-label text-violet-600 dark:text-violet-400">{{ __('messages.branches_total_count') }}</div>
            <div class="admin-stat-value text-violet-700 dark:text-violet-300 font-mono">
                {{ $stats['total_branches'] }}
            </div>
            <div class="admin-stat-sub text-slate-500">{{ $stats['active_branches'] }} {{ __('messages.active') }}</div>
        </div>

        {{-- 2. Default Branch --}}
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-emerald-600 dark:text-emerald-400">{{ __('messages.branches_default_branch') }}</div>
            <div class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-slate-100 truncate mt-0.5" title="{{ $stats['default_branch_name'] }}">
                {{ $stats['default_branch_name'] }}
            </div>
            <div class="admin-stat-sub text-slate-400">{{ __('messages.code') }}: {{ $stats['default_branch_code'] }}</div>
        </div>

        {{-- 3. Active Outlets --}}
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-blue-600 dark:text-blue-400">{{ __('messages.branches_active_count') }}</div>
            <div class="admin-stat-value text-blue-600 dark:text-blue-400 font-mono">
                {{ $stats['active_branches'] }}
            </div>
            <div class="admin-stat-sub text-slate-400">{{ __('messages.branches_operating_locations') }}</div>
        </div>

        {{-- 4. Total Linked Warehouses --}}
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-indigo-600 dark:text-indigo-400">{{ __('messages.branches_total_warehouses') }}</div>
            <div class="admin-stat-value text-indigo-600 dark:text-indigo-400 font-mono">
                {{ $stats['total_warehouses'] }}
            </div>
            <div class="admin-stat-sub text-slate-400">{{ __('messages.branches_stockpoints') }}</div>
        </div>
    </div>

    {{-- ============================================================
         BRANCHES CARDS LIST
         ============================================================ --}}
    <div class="space-y-4">
        <h2 class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 font-mono">
            {{ __('messages.branches_outlets_section') }} ({{ count($branches) }})
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @forelse($branches as $b)
                <div class="rounded-2xl sm:rounded-3xl border {{ $b->is_default ? 'border-violet-300 dark:border-violet-700/80 bg-violet-50/20 dark:bg-violet-950/20 shadow-md ring-1 ring-violet-400/30' : 'border-slate-200/90 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm' }} p-5 flex flex-col justify-between space-y-4 transition hover:shadow-lg">

                    <div>
                        {{-- Top Header & Badges --}}
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                @if($b->code)
                                    <span class="px-2 py-0.5 rounded-full text-xs font-black font-mono bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900">
                                        {{ $b->code }}
                                    </span>
                                @endif

                                <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $b->is_active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' }}">
                                    {{ $b->is_active ? __('messages.active') : __('messages.inactive') }}
                                </span>

                                <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300">
                                    {{ __('messages.branches_warehouses_count', ['count' => $b->warehouses_count]) }}
                                </span>
                            </div>

                            @if($b->is_default)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 shadow-sm">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    {{ __('messages.branches_default_badge') }}
                                </span>
                            @endif
                        </div>

                        {{-- Branch Name --}}
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-slate-100 mt-2.5 font-outfit">
                            {{ $b->name }}
                        </h3>

                        {{-- Metadata lines --}}
                        <div class="mt-2 space-y-1 text-xs text-slate-600 dark:text-slate-400">
                            @if($b->manager_name)
                                <div class="flex items-center gap-1.5">
                                    <span>👤 {{ __('messages.branches_manager') }}:</span>
                                    <strong class="text-slate-900 dark:text-slate-200">{{ $b->manager_name }}</strong>
                                </div>
                            @endif
                            @if($b->phone)
                                <div class="flex items-center gap-1.5">
                                    <span>📞 {{ __('messages.branches_phone') }}:</span>
                                    <strong class="text-slate-900 dark:text-slate-200 font-mono">{{ $b->phone }}</strong>
                                </div>
                            @endif
                            @if($b->address)
                                <div class="flex items-start gap-1.5 text-[11px] text-slate-500">
                                    <span>📍</span>
                                    <span class="line-clamp-2">{{ $b->address }}</span>
                                </div>
                            @endif
                        </div>

                        {{-- Linked Warehouses Pills --}}
                        @if($b->warehouses->isNotEmpty())
                            <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800 flex flex-wrap gap-1">
                                @foreach($b->warehouses as $wh)
                                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                        📦 {{ $wh->name }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex items-center justify-between gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        {{-- Left Actions: View Details & Set Default --}}
                        <div class="flex items-center gap-1.5">
                            <a href="{{ route('store.admin.branches.show', ['store_slug' => $store->slug, 'branch' => $b->id]) }}"
                               class="px-2.5 py-1.5 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                {{ __('messages.branches_view_details') }}
                            </a>

                            @if(!$b->is_default)
                                <form method="POST" action="{{ route('store.admin.branches.set_default', ['store_slug' => $store->slug, 'branch' => $b->id]) }}" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="px-2.5 py-1.5 text-xs font-bold rounded-xl border border-violet-200 dark:border-violet-800 bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300 hover:bg-violet-600 hover:text-white dark:hover:bg-violet-600 dark:hover:text-white transition shadow-sm">
                                        {{ __('messages.branches_set_default') }}
                                    </button>
                                </form>
                            @endif
                        </div>

                        {{-- Right Actions: Edit & Delete --}}
                        <div class="flex items-center gap-1">
                            <a href="{{ route('store.admin.branches.edit', ['store_slug' => $store->slug, 'branch' => $b->id]) }}"
                               class="p-1.5 text-slate-500 hover:text-slate-900 dark:hover:text-slate-100 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                               title="{{ __('messages.edit') }}">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 0 1-2-2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>

                            @if(!$b->is_default)
                                <form method="POST" action="{{ route('store.admin.branches.destroy', ['store_slug' => $store->slug, 'branch' => $b->id]) }}" onsubmit="return confirm('{{ __('messages.branches_confirm_delete') }}')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="p-1.5 text-rose-500 hover:text-rose-700 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/40 transition"
                                            title="{{ __('messages.delete') }}">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                </div>
            @empty
                <div class="col-span-full rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-12 text-center text-slate-400">
                    <p class="text-sm font-semibold">{{ __('messages.branches_no_branches') }}</p>
                </div>
            @endforelse
        </div>
    </div>

</div>
@endsection
