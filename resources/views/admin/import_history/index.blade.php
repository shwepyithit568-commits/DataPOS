@extends('layouts.admin.app')

@section('title', __('messages.import_history') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@php
    /** @var \Illuminate\Pagination\LengthAwarePaginator<\App\Models\ImportHistory> $histories */
    /** @var \App\Models\ImportHistory $history */
    $storeRouteParams = ['store_slug' => $store->slug];
@endphp

@section('content')
<div class="w-full space-y-0.5 pb-6" x-data>

    {{-- 1. Ultra-Dense Header Banner --}}
    <div class="admin-page-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 rounded-lg sm:rounded-xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-2 sm:p-2.5 shadow-2xs">
        <div class="flex items-center gap-2 min-w-0">
            <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300 grid place-items-center text-sm sm:text-base font-bold shrink-0">
                📋
            </span>
            <div class="min-w-0">
                <h1 class="admin-page-title text-sm sm:text-base font-bold text-slate-900 dark:text-slate-100 truncate leading-tight">
                    {{ __('messages.import_history_title') }}
                </h1>
                <p class="admin-page-sub text-[11px] text-slate-500 dark:text-slate-400 truncate leading-tight">
                    {{ __('messages.import_history_subtitle') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-1.5 shrink-0">
            <a href="{{ route('store.admin.pilot-import.index', $storeRouteParams) }}"
               class="min-h-11 sm:min-h-7 h-7 px-2.5 rounded-md text-xs font-semibold bg-violet-600 hover:bg-violet-700 text-white shadow-2xs transition inline-flex items-center gap-1.5 cursor-pointer active:scale-95">
                <span>📥</span>
                <span>{{ __('messages.pilot_import') }}</span>
            </a>
            <a href="{{ route('store.admin.backups.index', $storeRouteParams) }}"
               class="min-h-11 sm:min-h-7 h-7 px-2.5 rounded-md text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 transition inline-flex items-center gap-1.5">
                <span>💾</span>
                <span>{{ __('messages.backups') }}</span>
            </a>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if (session('success'))
        <div class="p-2 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs font-medium text-emerald-800 dark:text-emerald-200 flex items-center gap-2">
            <span class="w-4 h-4 rounded-full bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 grid place-items-center text-[10px] font-bold">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- 2. Centered Row-based Stat Cards (Standard v4.1) --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-0.5 sm:gap-1">
        {{-- Total Imports --}}
        <div class="rounded border p-1.5 sm:p-2 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800">
            <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-md bg-violet-100 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 grid place-items-center text-xs sm:text-sm font-bold shrink-0">
                📁
            </span>
            <div class="min-w-0 text-left">
                <div class="text-[10px] sm:text-[11px] font-semibold text-violet-600 dark:text-violet-400 truncate">
                    {{ __('messages.import_history_total_imports') }}
                </div>
                <div class="text-sm sm:text-base font-bold font-mono tracking-tight text-violet-600 dark:text-violet-400 tabular-nums leading-tight">
                    {{ number_format($summary['total_imports']) }}
                </div>
                <p class="text-[9px] text-slate-400 truncate">{{ __('messages.import_history_total_imports_sub') }}</p>
            </div>
        </div>

        {{-- Successful Rows --}}
        <div class="rounded border p-1.5 sm:p-2 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800">
            <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-md bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 grid place-items-center text-xs sm:text-sm font-bold shrink-0">
                ✓
            </span>
            <div class="min-w-0 text-left">
                <div class="text-[10px] sm:text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 truncate">
                    {{ __('messages.import_history_successful_rows') }}
                </div>
                <div class="text-sm sm:text-base font-bold font-mono tracking-tight text-emerald-600 dark:text-emerald-400 tabular-nums leading-tight">
                    {{ number_format($summary['successful_rows']) }}
                </div>
                <p class="text-[9px] text-slate-400 truncate">{{ __('messages.import_history_successful_rows_sub') }}</p>
            </div>
        </div>

        {{-- Failed Rows --}}
        <div class="rounded border p-1.5 sm:p-2 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800">
            <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-md bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 grid place-items-center text-xs sm:text-sm font-bold shrink-0">
                ⚠️
            </span>
            <div class="min-w-0 text-left">
                <div class="text-[10px] sm:text-[11px] font-semibold text-rose-600 dark:text-rose-400 truncate">
                    {{ __('messages.import_history_failed_rows') }}
                </div>
                <div class="text-sm sm:text-base font-bold font-mono tracking-tight text-rose-600 dark:text-rose-400 tabular-nums leading-tight">
                    {{ number_format($summary['failed_rows']) }}
                </div>
                <p class="text-[9px] text-slate-400 truncate">{{ __('messages.import_history_failed_rows_sub') }}</p>
            </div>
        </div>

        {{-- Success Rate --}}
        <div class="rounded border p-1.5 sm:p-2 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800">
            <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-md bg-sky-100 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 grid place-items-center text-xs sm:text-sm font-bold shrink-0">
                📊
            </span>
            <div class="min-w-0 text-left">
                <div class="text-[10px] sm:text-[11px] font-semibold text-sky-600 dark:text-sky-400 truncate">
                    {{ __('messages.import_history_success_rate') }}
                </div>
                <div class="text-sm sm:text-base font-bold font-mono tracking-tight text-sky-600 dark:text-sky-400 tabular-nums leading-tight">
                    {{ ($summary['successful_rows'] + $summary['failed_rows']) > 0 ? round(($summary['successful_rows'] / ($summary['successful_rows'] + $summary['failed_rows'])) * 100, 1) . '%' : '100%' }}
                </div>
                <p class="text-[9px] text-slate-400 truncate">{{ __('messages.import_history_success_rate_sub') }}</p>
            </div>
        </div>
    </div>

    {{-- 3. Filter & Search Toolbar (h-7 compact) --}}
    <form method="GET" action="{{ route('store.admin.import-history.index', $storeRouteParams) }}"
          class="rounded-lg sm:rounded-xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-1.5 sm:p-2 shadow-2xs flex flex-col sm:flex-row items-center justify-between gap-1.5">
        <div class="flex flex-wrap items-center gap-1.5 w-full sm:w-auto">
            {{-- Search Filename --}}
            <div class="relative min-w-[180px] flex-1 sm:flex-initial">
                <span class="absolute inset-y-0 left-0 pl-2 flex items-center pointer-events-none text-slate-400 text-xs">
                    🔍
                </span>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="{{ __('messages.import_history_search_placeholder') }}"
                       class="w-full h-7 pl-7 pr-2 text-xs rounded-md border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-violet-500 font-mono">
            </div>

            {{-- Type Selector --}}
            <select name="type" data-auto-submit
                    class="h-7 px-2 text-xs rounded-md border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-violet-500">
                <option value="all" {{ ($type ?? 'all') === 'all' ? 'selected' : '' }}>{{ __('messages.import_history_type_all') }}</option>
                <option value="products" {{ ($type ?? '') === 'products' ? 'selected' : '' }}>📦 {{ __('messages.import_history_type_products') }}</option>
                <option value="customers" {{ ($type ?? '') === 'customers' ? 'selected' : '' }}>👥 {{ __('messages.import_history_type_customers') }}</option>
                <option value="suppliers" {{ ($type ?? '') === 'suppliers' ? 'selected' : '' }}>🏢 {{ __('messages.import_history_type_suppliers') }}</option>
                <option value="debt" {{ ($type ?? '') === 'debt' ? 'selected' : '' }}>💳 {{ __('messages.import_history_type_debt') }}</option>
                <option value="glass_finder" {{ ($type ?? '') === 'glass_finder' ? 'selected' : '' }}>🔍 {{ __('messages.import_history_type_glass_finder') }}</option>
            </select>

            <button type="submit"
                    class="h-7 px-2.5 rounded-md bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold border border-slate-200 dark:border-slate-700 transition cursor-pointer">
                {{ __('messages.import_history_btn_search') }}
            </button>

            @if ($search || ($type && $type !== 'all'))
                <a href="{{ route('store.admin.import-history.index', $storeRouteParams) }}"
                   class="h-7 px-2 inline-flex items-center text-xs text-rose-600 dark:text-rose-400 hover:underline">
                    {{ __('messages.import_history_btn_reset') }}
                </a>
            @endif
        </div>

        <div class="text-[10px] text-slate-400 shrink-0">
            {{ __('messages.import_history_last_import', ['time' => $summary['last_import_date'] ? \Illuminate\Support\Carbon::parse($summary['last_import_date'])->format('d M Y, h:i A') : __('messages.import_history_never')]) }}
        </div>
    </form>

    {{-- 4. Import History Table (Ultra-dense) --}}
    <div class="rounded-lg sm:rounded-xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-2xs overflow-hidden space-y-0">
        <div class="overflow-x-auto">
            <table class="min-w-[920px] w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider text-[11px]">
                        <th class="py-1.5 px-2">{{ __('messages.import_history_th_date') }}</th>
                        <th class="py-1.5 px-2">{{ __('messages.import_history_th_type') }}</th>
                        <th class="py-1.5 px-2">{{ __('messages.import_history_th_file') }}</th>
                        <th class="py-1.5 px-2 text-right">{{ __('messages.import_history_th_total') }}</th>
                        <th class="py-1.5 px-2 text-right">{{ __('messages.import_history_th_success') }}</th>
                        <th class="py-1.5 px-2 text-right">{{ __('messages.import_history_th_failed') }}</th>
                        <th class="py-1.5 px-2 text-center">{{ __('messages.import_history_th_status') }}</th>
                        <th class="py-1.5 px-2">{{ __('messages.import_history_th_actor') }}</th>
                        <th class="py-1.5 px-2 text-right">{{ __('messages.import_history_th_action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                    @forelse ($histories as $history)
                        @php
                            /** @var \App\Models\ImportHistory $history */
                            $typeBadge = match ($history->type) {
                                'products' => ['bg' => 'bg-violet-100 dark:bg-violet-950 text-violet-700 dark:text-violet-300', 'icon' => '📦'],
                                'customers' => ['bg' => 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300', 'icon' => '👥'],
                                'suppliers' => ['bg' => 'bg-sky-100 dark:bg-sky-950 text-sky-700 dark:text-sky-300', 'icon' => '🏢'],
                                'debt' => ['bg' => 'bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300', 'icon' => '💳'],
                                default => ['bg' => 'bg-cyan-100 dark:bg-cyan-950 text-cyan-700 dark:text-cyan-300', 'icon' => '🔍'],
                            };
                            $status = $history->status();
                        @endphp
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <td class="py-1.5 px-2 font-mono text-slate-500 dark:text-slate-400 whitespace-nowrap text-[11px]">
                                {{ $history->created_at->format('d M Y, h:i A') }}
                            </td>
                            <td class="py-1.5 px-2 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold {{ $typeBadge['bg'] }}">
                                    <span>{{ $typeBadge['icon'] }}</span>
                                    <span>{{ $history->displayType() }}</span>
                                </span>
                            </td>
                            <td class="py-1.5 px-2 font-mono font-semibold text-slate-900 dark:text-slate-100 max-w-[200px] truncate" title="{{ $history->filename }}">
                                {{ $history->filename }}
                            </td>
                            <td class="py-1.5 px-2 text-right font-mono font-bold tabular-nums">
                                {{ number_format($history->total_rows) }}
                            </td>
                            <td class="py-1.5 px-2 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400 tabular-nums">
                                {{ number_format($history->success_rows) }}
                            </td>
                            <td class="py-1.5 px-2 text-right font-mono font-bold text-rose-600 dark:text-rose-400 tabular-nums">
                                {{ number_format($history->failed_rows) }}
                            </td>
                            <td class="py-1.5 px-2 text-center whitespace-nowrap">
                                @if ($history->failed_rows === 0)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300">
                                        ✓ {{ $status }}
                                    </span>
                                @elseif ($history->success_rows > 0)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300">
                                        ⚠ {{ $status }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300">
                                        ⨯ {{ $status }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-1.5 px-2 text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                {{ $history->user?->name ?? 'System' }}
                            </td>
                            <td class="py-1.5 px-2 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('store.admin.import-history.show', array_merge($storeRouteParams, ['history' => $history->id])) }}"
                                       class="h-6 px-2 rounded text-[11px] font-semibold bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300 hover:bg-violet-100 transition inline-flex items-center">
                                        {{ __('messages.import_history_btn_view') }}
                                    </a>

                                    @if ($history->failed_rows > 0 && $history->error_file_path)
                                        <a href="{{ route('store.admin.import-history.errors', array_merge($storeRouteParams, ['history' => $history->id])) }}"
                                           class="h-6 px-2 rounded text-[11px] font-semibold bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300 transition inline-flex items-center gap-1">
                                            <span>⬇</span>
                                            <span>{{ __('messages.import_history_btn_errors_csv') }}</span>
                                        </a>
                                    @endif

                                    <form method="POST" action="{{ route('store.admin.import-history.destroy', array_merge($storeRouteParams, ['history' => $history->id])) }}"
                                          data-confirm="{{ __('messages.import_history_delete_confirm') }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="h-6 px-2 rounded text-[11px] font-semibold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/50 transition cursor-pointer">
                                            {{ __('messages.import_history_btn_delete') }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-slate-400 text-xs">
                                {{ __('messages.import_history_empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($histories->hasPages())
            <div class="p-2 border-t border-slate-200 dark:border-slate-800">
                {{ $histories->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
