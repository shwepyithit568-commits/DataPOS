@extends('layouts.admin.app')

@section('title', __('messages.import_history') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2 sm:p-3 md:p-4')

@php
    $storeRouteParams = ['store_slug' => $store->slug];
@endphp

@section('content')
<div class="w-full space-y-2.5 sm:space-y-3 pb-8" x-data>

    {{-- 1. Compact Header --}}
    <div class="admin-page-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-3 sm:p-4 shadow-sm">
        <div class="flex items-center gap-3 min-w-0">
            <span class="w-10 h-10 rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300 grid place-items-center text-lg font-bold shrink-0 shadow-sm">
                📋
            </span>
            <div class="min-w-0">
                <div class="flex items-center gap-1.5 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                    <span>{{ __('messages.sidebar_maintenance') }}</span>
                    <span>/</span>
                    <span class="text-violet-600 dark:text-violet-400">{{ __('messages.import_history') }}</span>
                </div>
                <h1 class="admin-page-title text-base sm:text-lg font-bold text-slate-900 dark:text-slate-100 truncate">
                    {{ __('messages.import_history_title') }}
                </h1>
                <p class="admin-page-sub text-xs text-slate-500 dark:text-slate-400 truncate">
                    {{ __('messages.import_history_subtitle') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('store.admin.pilot-import.index', $storeRouteParams) }}"
               class="min-h-11 h-11 sm:h-9 px-3.5 rounded-xl text-xs font-semibold bg-violet-600 hover:bg-violet-700 text-white shadow-sm transition inline-flex items-center gap-1.5 cursor-pointer active:scale-95">
                <span>📥</span>
                <span>{{ __('messages.pilot_import') }}</span>
            </a>
            <a href="{{ route('store.admin.backups.index', $storeRouteParams) }}"
               class="min-h-11 h-11 sm:h-9 px-3.5 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 transition inline-flex items-center gap-1.5">
                <span>💾</span>
                <span>{{ __('messages.backups') }}</span>
            </a>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if (session('success'))
        <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs font-medium text-emerald-800 dark:text-emerald-200 flex items-center gap-2">
            <span class="w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 grid place-items-center text-xs font-bold">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- 2. 4 Key Stat KPI Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3">
        {{-- Total Imports --}}
        <div class="rounded-xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-3 shadow-sm">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400">
                <span class="text-violet-600 dark:text-violet-400">Total Imports</span>
                <span>📁</span>
            </div>
            <div class="mt-1 text-xl sm:text-2xl font-black font-mono tracking-tight text-violet-600 dark:text-violet-400 tabular-nums">
                {{ number_format($summary['total_imports']) }}
            </div>
            <p class="text-[11px] text-slate-400 truncate">စုစုပေါင်း တင်သွင်းမှု အကြိမ်ရေ</p>
        </div>

        {{-- Successful Rows --}}
        <div class="rounded-xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-3 shadow-sm">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400">
                <span class="text-emerald-600 dark:text-emerald-400">Successful Rows</span>
                <span>✓</span>
            </div>
            <div class="mt-1 text-xl sm:text-2xl font-black font-mono tracking-tight text-emerald-600 dark:text-emerald-400 tabular-nums">
                {{ number_format($summary['successful_rows']) }}
            </div>
            <p class="text-[11px] text-slate-400 truncate">အောင်မြင်စွာ သွင်းပြီးသော စာကြောင်းရေ</p>
        </div>

        {{-- Failed Rows --}}
        <div class="rounded-xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-3 shadow-sm">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400">
                <span class="text-rose-600 dark:text-rose-400">Failed Rows</span>
                <span>⚠️</span>
            </div>
            <div class="mt-1 text-xl sm:text-2xl font-black font-mono tracking-tight text-rose-600 dark:text-rose-400 tabular-nums">
                {{ number_format($summary['failed_rows']) }}
            </div>
            <p class="text-[11px] text-slate-400 truncate">အမှားရှိသော စာကြောင်းရေ</p>
        </div>

        {{-- Success Rate --}}
        <div class="rounded-xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-3 shadow-sm">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400">
                <span class="text-sky-600 dark:text-sky-400">Success Rate</span>
                <span>📊</span>
            </div>
            <div class="mt-1 text-xl sm:text-2xl font-black font-mono tracking-tight text-sky-600 dark:text-sky-400 tabular-nums">
                {{ ($summary['successful_rows'] + $summary['failed_rows']) > 0 ? round(($summary['successful_rows'] / ($summary['successful_rows'] + $summary['failed_rows'])) * 100, 1) . '%' : '100%' }}
            </div>
            <p class="text-[11px] text-slate-400 truncate">အောင်မြင်မှု ရာခိုင်နှုန်း</p>
        </div>
    </div>

    {{-- 3. Filter & Search Toolbar --}}
    <form method="GET" action="{{ route('store.admin.import-history.index', $storeRouteParams) }}"
          class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-3 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-2.5">
        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
            {{-- Search Filename --}}
            <div class="relative min-w-[200px] flex-1 sm:flex-initial">
                <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400 text-xs">
                    🔍
                </span>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="ဖိုင်အမည် ရှာရန်..."
                       class="w-full pl-8 pr-3 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500 font-mono">
            </div>

            {{-- Type Selector --}}
            <select name="type" onchange="this.form.submit()"
                    class="py-1.5 px-3 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">
                <option value="all" {{ ($type ?? 'all') === 'all' ? 'selected' : '' }}>အမျိုးအစား အားလုံး (All Types)</option>
                <option value="products" {{ ($type ?? '') === 'products' ? 'selected' : '' }}>📦 ကုန်ပစ္စည်းများ (Products)</option>
                <option value="customers" {{ ($type ?? '') === 'customers' ? 'selected' : '' }}>👥 ဝယ်ယူသူများ (Customers)</option>
                <option value="suppliers" {{ ($type ?? '') === 'suppliers' ? 'selected' : '' }}>🏢 ကုန်သွင်းသူများ (Suppliers)</option>
                <option value="debt" {{ ($type ?? '') === 'debt' ? 'selected' : '' }}>💳 အကြွေးစာရင်း (Debt Opening)</option>
                <option value="glass_finder" {{ ($type ?? '') === 'glass_finder' ? 'selected' : '' }}>🔍 Glass Finder</option>
            </select>

            <button type="submit"
                    class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold border border-slate-200 dark:border-slate-700 transition cursor-pointer">
                ရှာမည်
            </button>

            @if ($search || ($type && $type !== 'all'))
                <a href="{{ route('store.admin.import-history.index', $storeRouteParams) }}"
                   class="px-2.5 py-1.5 text-xs text-rose-600 dark:text-rose-400 hover:underline">
                    Reset
                </a>
            @endif
        </div>

        <div class="text-[11px] text-slate-400 shrink-0">
            နောက်ဆုံး တင်သွင်းချိန်: {{ $summary['last_import_date'] ? \Illuminate\Support\Carbon::parse($summary['last_import_date'])->format('d M Y, h:i A') : 'မရှိသေးပါ' }}
        </div>
    </form>

    {{-- 4. Import History Table --}}
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden space-y-0">
        <div class="overflow-x-auto">
            <table class="min-w-[920px] w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                        <th class="py-2.5 px-3.5">ရက်စွဲ/အချိန်</th>
                        <th class="py-2.5 px-3.5">အမျိုးအစား</th>
                        <th class="py-2.5 px-3.5">ဖိုင်အမည်</th>
                        <th class="py-2.5 px-3.5 text-right">စုစုပေါင်း</th>
                        <th class="py-2.5 px-3.5 text-right">အောင်မြင်</th>
                        <th class="py-2.5 px-3.5 text-right">အမှား</th>
                        <th class="py-2.5 px-3.5 text-center">အခြေအနေ</th>
                        <th class="py-2.5 px-3.5">ဆောင်ရွက်သူ</th>
                        <th class="py-2.5 px-3.5 text-right">လုပ်ဆောင်ချက်</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                    @forelse ($histories as $history)
                        @php
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
                            <td class="py-2.5 px-3.5 font-mono text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                {{ $history->created_at->format('d M Y, h:i A') }}
                            </td>
                            <td class="py-2.5 px-3.5 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold {{ $typeBadge['bg'] }}">
                                    <span>{{ $typeBadge['icon'] }}</span>
                                    <span>{{ $history->displayType() }}</span>
                                </span>
                            </td>
                            <td class="py-2.5 px-3.5 font-mono font-semibold text-slate-900 dark:text-slate-100 max-w-[200px] truncate" title="{{ $history->filename }}">
                                {{ $history->filename }}
                            </td>
                            <td class="py-2.5 px-3.5 text-right font-mono font-bold tabular-nums">
                                {{ number_format($history->total_rows) }}
                            </td>
                            <td class="py-2.5 px-3.5 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400 tabular-nums">
                                {{ number_format($history->success_rows) }}
                            </td>
                            <td class="py-2.5 px-3.5 text-right font-mono font-bold text-rose-600 dark:text-rose-400 tabular-nums">
                                {{ number_format($history->failed_rows) }}
                            </td>
                            <td class="py-2.5 px-3.5 text-center whitespace-nowrap">
                                @if ($history->failed_rows === 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300">
                                        ✓ {{ $status }}
                                    </span>
                                @elseif ($history->success_rows > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300">
                                        ⚠ {{ $status }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300">
                                        ⨯ {{ $status }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3.5 text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                {{ $history->user?->name ?? 'System' }}
                            </td>
                            <td class="py-2.5 px-3.5 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('store.admin.import-history.show', array_merge($storeRouteParams, ['history' => $history->id])) }}"
                                       class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300 hover:bg-violet-100 transition">
                                        View
                                    </a>

                                    @if ($history->failed_rows > 0 && $history->error_file_path)
                                        <a href="{{ route('store.admin.import-history.errors', array_merge($storeRouteParams, ['history' => $history->id])) }}"
                                           class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300 transition inline-flex items-center gap-1">
                                            <span>⬇</span>
                                            <span>Errors CSV</span>
                                        </a>
                                    @endif

                                    <form method="POST" action="{{ route('store.admin.import-history.destroy', array_merge($storeRouteParams, ['history' => $history->id])) }}"
                                          onsubmit="return confirm('{{ __('messages.import_history_delete_confirm') }}')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-2.5 py-1.5 rounded-lg text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/50 transition cursor-pointer">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-10 text-center text-slate-400 text-xs">
                                {{ __('messages.import_history_empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($histories->hasPages())
            <div class="p-3 border-t border-slate-200 dark:border-slate-800">
                {{ $histories->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
