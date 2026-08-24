@extends('layouts.admin.app')

@section('title', __('messages.sidebar_stock_count') . ' - ' . ($store->name ?? 'DataPOS'))

@section('content')
<div class="space-y-6">

    {{-- Breadcrumbs & Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                <a href="{{ route('store.admin.dashboard', ['store_slug' => $store->slug]) }}" class="hover:text-violet-600 dark:hover:text-violet-400">{{ __('messages.admin_dashboard') }}</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-200 font-semibold">{{ __('messages.sidebar_stock_count') }}</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100 font-outfit mt-1">
                {{ __('messages.stock_count_title') }}
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                {{ __('messages.stock_count_sub') }}
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('store.admin.stock_count.create', ['store_slug' => $store->slug]) }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-extrabold rounded-xl bg-violet-600 hover:bg-violet-500 text-white shadow-lg shadow-violet-600/30 transition transform active:scale-95">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>{{ __('messages.stock_count_new_session') }}</span>
            </a>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-950/40 dark:border-emerald-800/60 dark:text-emerald-300 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 dark:bg-rose-950/40 dark:border-rose-800/60 dark:text-rose-300 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-rose-600 dark:text-rose-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- 4 KPI Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Sessions --}}
        <a href="{{ route('store.admin.stock_count.index', ['store_slug' => $store->slug]) }}"
           class="p-4 rounded-2xl border transition {{ empty($status) ? 'border-violet-600 bg-violet-50/40 dark:border-violet-500 dark:bg-violet-950/20 ring-2 ring-violet-500/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 hover:border-slate-300' }} shadow-sm">
            <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('messages.stock_count_stat_total') }}</div>
            <div class="text-2xl font-black text-slate-900 dark:text-slate-100 font-outfit mt-1">{{ number_format($stats['total']) }}</div>
        </a>

        {{-- In Progress --}}
        <a href="{{ route('store.admin.stock_count.index', ['store_slug' => $store->slug, 'status' => 'in_progress']) }}"
           class="p-4 rounded-2xl border transition {{ $status === 'in_progress' ? 'border-amber-600 bg-amber-50/40 dark:border-amber-500 dark:bg-amber-950/20 ring-2 ring-amber-500/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 hover:border-slate-300' }} shadow-sm">
            <div class="text-xs font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wider">{{ __('messages.stock_count_stat_in_progress') }}</div>
            <div class="text-2xl font-black text-amber-600 dark:text-amber-400 font-outfit mt-1">{{ number_format($stats['in_progress']) }}</div>
        </a>

        {{-- Approved --}}
        <a href="{{ route('store.admin.stock_count.index', ['store_slug' => $store->slug, 'status' => 'approved']) }}"
           class="p-4 rounded-2xl border transition {{ $status === 'approved' ? 'border-emerald-600 bg-emerald-50/40 dark:border-emerald-500 dark:bg-emerald-950/20 ring-2 ring-emerald-500/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 hover:border-slate-300' }} shadow-sm">
            <div class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">{{ __('messages.stock_count_stat_approved') }}</div>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 font-outfit mt-1">{{ number_format($stats['approved']) }}</div>
        </a>

        {{-- Cancelled --}}
        <a href="{{ route('store.admin.stock_count.index', ['store_slug' => $store->slug, 'status' => 'cancelled']) }}"
           class="p-4 rounded-2xl border transition {{ $status === 'cancelled' ? 'border-rose-600 bg-rose-50/40 dark:border-rose-500 dark:bg-rose-950/20 ring-2 ring-rose-500/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 hover:border-slate-300' }} shadow-sm">
            <div class="text-xs font-semibold text-rose-600 dark:text-rose-400 uppercase tracking-wider">{{ __('messages.stock_count_stat_cancelled') }}</div>
            <div class="text-2xl font-black text-rose-600 dark:text-rose-400 font-outfit mt-1">{{ number_format($stats['cancelled']) }}</div>
        </a>
    </div>

    {{-- Filter & Search Bar --}}
    <div class="p-4 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm">
        <form method="GET" action="{{ route('store.admin.stock_count.index', ['store_slug' => $store->slug]) }}" class="flex flex-col sm:flex-row gap-3 items-center justify-between">
            <input type="hidden" name="status" value="{{ $status }}">
            
            <div class="relative w-full sm:w-96">
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="{{ __('messages.search') }}..."
                       class="w-full pl-10 pr-4 py-2 text-sm rounded-xl border border-slate-200 bg-slate-50/50 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-violet-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                @if($search || $status)
                    <a href="{{ route('store.admin.stock_count.index', ['store_slug' => $store->slug]) }}" class="px-3 py-2 text-xs font-semibold rounded-xl text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800">
                        {{ __('messages.reset') }}
                    </a>
                @endif
                <button type="submit" class="px-4 py-2 text-sm font-semibold rounded-xl bg-slate-900 text-white hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                    {{ __('messages.filter') }}
                </button>
            </div>
        </form>
    </div>

    {{-- Sessions Table --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h2 class="font-bold text-slate-900 dark:text-slate-100 font-outfit text-base">
                {{ __('messages.stock_count_history') }}
            </h2>
            <span class="text-xs text-slate-500 dark:text-slate-400 font-mono">
                {{ $sessions->total() }} {{ __('messages.stock_count_stat_total') }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50/75 dark:bg-slate-800/50 text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400 font-semibold">
                    <tr>
                        <th class="px-4 py-3">{{ __('messages.stock_count_session_number') }}</th>
                        <th class="px-4 py-3">{{ __('messages.stock_count_date') }}</th>
                        <th class="px-4 py-3">{{ __('messages.stock_count_scope') }}</th>
                        <th class="px-4 py-3 text-center">{{ __('messages.stock_count_progress') }}</th>
                        <th class="px-4 py-3 text-center">{{ __('messages.stock_count_variance_items') }}</th>
                        <th class="px-4 py-3 text-center">{{ __('messages.stock_count_status') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($sessions as $session)
                        @php
                            $progressPct = $session->total_items > 0 ? round(($session->counted_items / $session->total_items) * 100) : 0;
                        @endphp
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition">
                            <td class="px-4 py-3">
                                <a href="{{ route('store.admin.stock_count.show', ['store_slug' => $store->slug, 'stock_count' => $session->id]) }}"
                                   class="font-mono font-bold text-violet-600 dark:text-violet-400 hover:underline">
                                    {{ $session->session_number }}
                                </a>
                                @if($session->notes)
                                    <p class="text-xs text-slate-400 truncate max-w-xs mt-0.5">{{ $session->notes }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-500 dark:text-slate-400">
                                <div>{{ $session->created_at->format('d/m/Y H:i') }}</div>
                                <div class="text-[11px] text-slate-400">{{ $session->createdBy?->name ?? 'System' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                @if($session->scope === 'category')
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-md bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300">
                                        {{ __('messages.stock_count_scope_category') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-md bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        {{ __('messages.stock_count_scope_all') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="text-xs font-bold text-slate-700 dark:text-slate-200">
                                        {{ $session->counted_items }} / {{ $session->total_items }}
                                    </div>
                                    <div class="w-24 bg-slate-200 dark:bg-slate-700 rounded-full h-1.5 mt-1 overflow-hidden">
                                        <div class="bg-violet-600 h-1.5 rounded-full transition-all duration-300" style="width: {{ $progressPct }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($session->variance_items > 0)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-bold rounded-md bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        {{ $session->variance_items }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400">0</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($session->isApproved())
                                    <span class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                        {{ __('messages.stock_count_status_approved') }}
                                    </span>
                                @elseif($session->isCancelled())
                                    <span class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-full bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 border border-rose-200 dark:border-rose-800">
                                        {{ __('messages.stock_count_status_cancelled') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-full bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border border-amber-200 dark:border-amber-800 animate-pulse">
                                        {{ __('messages.stock_count_status_in_progress') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('store.admin.stock_count.print', ['store_slug' => $store->slug, 'stock_count' => $session->id]) }}"
                                       target="_blank"
                                       title="{{ __('messages.stock_count_print_sheet') }}"
                                       class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                        </svg>
                                    </a>
                                    <a href="{{ route('store.admin.stock_count.show', ['store_slug' => $store->slug, 'stock_count' => $session->id]) }}"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-xl {{ $session->isInProgress() ? 'bg-violet-600 hover:bg-violet-500 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200' }} transition">
                                        <span>{{ $session->isInProgress() ? __('messages.stock_count_start_session') : __('messages.view') }}</span>
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-slate-300 dark:text-slate-600 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    <p class="text-sm font-semibold">{{ __('messages.stock_count_no_sessions') }}</p>
                                    <p class="text-xs text-slate-400 mt-1">{{ __('messages.stock_count_create_first') }}</p>
                                    <a href="{{ route('store.admin.stock_count.create', ['store_slug' => $store->slug]) }}"
                                       class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold rounded-xl bg-violet-600 text-white hover:bg-violet-500 transition">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        <span>{{ __('messages.stock_count_new_session') }}</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sessions->hasPages())
            <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                {{ $sessions->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
