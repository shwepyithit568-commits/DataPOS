@extends('layouts.pos.app')

@section('content')
    @php
        $isManager = auth()->user()?->hasStoreRole($store->id, 'store_manager');
    @endphp

    <div class="mx-auto max-w-5xl px-4 py-6 space-y-6">

        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('messages.reconciliation_title') }}</p>
                <h1 class="text-xl font-black mt-0.5">{{ __('messages.reconciliation_subtitle') }}</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ __('messages.reconciliation_hint') }}</p>
            </div>
            <a href="{{ url('/store/' . $store->slug . '/pos') }}"
               class="rounded-xl px-4 py-2 text-sm font-bold bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 transition">
                ← {{ __('messages.back_to_pos') }}
            </a>
        </div>

        {{-- Inventory Operations Navigation Tabs --}}
        <div class="flex items-center gap-1 p-1 bg-slate-100 dark:bg-slate-800/80 rounded-xl border border-slate-200 dark:border-slate-700/60 overflow-x-auto scrollbar-none text-xs font-bold">
            <a href="{{ route('pos.adjustments.index', ['store_slug' => $store->slug]) }}"
               class="px-4 py-2 rounded-lg transition-all whitespace-nowrap {{ request()->routeIs('pos.adjustments.*') ? 'bg-white dark:bg-slate-900 text-sky-600 dark:text-sky-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                ⚡ {{ __('messages.adjustment_title') }}
            </a>
            <a href="{{ route('pos.reconciliation.index', ['store_slug' => $store->slug]) }}"
               class="px-4 py-2 rounded-lg transition-all whitespace-nowrap {{ request()->routeIs('pos.reconciliation.*') ? 'bg-white dark:bg-slate-900 text-sky-600 dark:text-sky-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                ⚖️ {{ __('messages.reconciliation') }}
            </a>
            <a href="{{ route('pos.opening-stock.index', ['store_slug' => $store->slug]) }}"
               class="px-4 py-2 rounded-lg transition-all whitespace-nowrap {{ request()->routeIs('pos.opening-stock.*') ? 'bg-white dark:bg-slate-900 text-sky-600 dark:text-sky-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                📦 {{ __('messages.opening_stock_title') }}
            </a>
        </div>

        @if (session('error'))
            <div class="rounded-xl border border-rose-300 dark:border-rose-700 bg-rose-50 dark:bg-rose-950 text-rose-800 dark:text-rose-300 px-4 py-3 text-sm font-semibold">
                ⚠️ {{ session('error') }}
            </div>
        @endif
        @if (session('success'))
            <div class="rounded-xl border border-emerald-300 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 px-4 py-3 text-sm font-semibold">
                ✅ {{ session('success') }}
            </div>
        @endif

        <div x-data="{ onlyDiffs: false }" class="space-y-6">
            {{-- Summary cards --}}
            <div class="grid grid-cols-3 gap-3">
                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">{{ __('messages.reconciliation_products') }}</p>
                    <p class="text-2xl font-black mt-1">{{ $report['products'] }}</p>
                </div>
                <div class="rounded-2xl bg-white dark:bg-slate-900 border p-4 shadow-sm
                    {{ $report['clean'] ? 'border-emerald-200 dark:border-emerald-800' : 'border-amber-200 dark:border-amber-800' }}">
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">{{ __('messages.reconciliation_diff_products') }}</p>
                    <p class="text-2xl font-black mt-1 {{ $report['clean'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                        {{ $report['diff_products'] }}
                    </p>
                </div>
                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">{{ __('messages.reconciliation_total_diff') }}</p>
                    <p class="text-2xl font-black mt-1">{{ number_format((float) $report['total_diff'], 3) }}</p>
                </div>
            </div>

            @if ($report['clean'] && $report['products'] > 0)
                <div class="rounded-xl border border-emerald-300 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 px-4 py-3 text-sm font-semibold">
                    ✅ {{ __('messages.reconciliation_clean') }}
                </div>
            @endif

            @if ($report['products'] === 0)
                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-8 text-center text-sm text-slate-500 shadow-sm">
                    {{ __('messages.reconciliation_none') }}
                </div>
            @else
                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                    <div class="flex items-center justify-between gap-3 px-4 pt-3.5 pb-3 flex-wrap">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('messages.opening_stock_requests') }}</p>
                        <label class="flex items-center gap-1.5 text-xs font-bold text-slate-500 cursor-pointer">
                            <input type="checkbox" x-model="onlyDiffs" class="rounded border-slate-300">
                            {{ __('messages.reconciliation_only_diffs') }}
                        </label>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs text-slate-500 dark:text-slate-400">
                                <tr>
                                    <th class="text-left px-4 py-2">{{ __('messages.product') }}</th>
                                    <th class="text-right px-4 py-2">{{ __('messages.reconciliation_imported') }}</th>
                                    <th class="text-right px-4 py-2">{{ __('messages.reconciliation_recorded') }}</th>
                                    <th class="text-right px-4 py-2">{{ __('messages.reconciliation_diff') }}</th>
                                    <th class="text-right px-4 py-2">{{ __('messages.reconciliation_on_hand') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach ($report['rows'] as $row)
                                    @php
                                        $isDiff = bccomp((string) $row['diff'], '0', 3) !== 0;
                                        $diffColor = $isDiff ? (bccomp((string) $row['diff'], '0', 3) > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400') : 'text-slate-400';
                                    @endphp
                                    <tr x-show="!onlyDiffs || {{ $isDiff ? 'true' : 'false' }}"
                                        class="{{ $isDiff ? 'bg-amber-50/60 dark:bg-amber-950/20' : '' }}">
                                        <td class="px-4 py-2">
                                            <span class="font-semibold">{{ $row['product_name'] }}</span>
                                            @if ($row['sku'])
                                                <span class="block text-[10px] font-mono text-slate-400">{{ $row['sku'] }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-right font-mono">{{ number_format((float) $row['imported'], 3) }}</td>
                                        <td class="px-4 py-2 text-right font-mono">{{ number_format((float) $row['recorded'], 3) }}</td>
                                        <td class="px-4 py-2 text-right font-mono font-bold {{ $diffColor }}">
                                            {{ $isDiff ? ($row['diff'] > 0 ? '+' : '') . number_format((float) $row['diff'], 3) : '—' }}
                                        </td>
                                        <td class="px-4 py-2 text-right font-mono text-slate-500">{{ number_format((float) $row['on_hand'], 3) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($isManager)
                    <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/reconciliation/approve') }}"
                          class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm space-y-3"
                          onsubmit="return confirm('{{ __('messages.reconciliation_approve_confirm') }}');">
                        @csrf
                        <div class="flex gap-2 flex-wrap">
                            <input type="text" name="review_notes" maxlength="500" placeholder="{{ __('messages.reconciliation_review_notes') }}"
                                   class="flex-1 min-w-[200px] rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2.5 text-sm">
                            <button type="submit"
                                    class="rounded-lg px-5 py-2.5 text-sm font-black text-white bg-emerald-600 hover:bg-emerald-500 transition">
                                ⚖️ {{ __('messages.reconciliation_approve') }}
                            </button>
                        </div>
                        <p class="text-xs text-slate-500">{{ __('messages.reconciliation_approve_hint') }}</p>
                    </form>
                @else
                    <p class="text-xs font-semibold text-slate-500 text-center">{{ __('messages.reconciliation_waits') }}</p>
                @endif
            @endif

            {{-- History --}}
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm space-y-3">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('messages.reconciliation_history') }}</p>

                @if ($history->isEmpty())
                    <p class="text-center text-sm text-slate-500 py-4">{{ __('messages.reconciliation_history_none') }}</p>
                @else
                    @foreach ($history as $rec)
                        <div class="rounded-xl border border-emerald-300 dark:border-emerald-700 bg-emerald-50/60 dark:bg-emerald-950/20 p-3.5 space-y-2">
                            <div class="flex items-center justify-between gap-3 flex-wrap">
                                <div>
                                    <p class="font-mono font-bold">{{ $rec->reconciliation_number }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ $rec->approved_at?->format('d M Y, H:i') }} · {{ __('messages.opening_stock_reviewed_by') }}: {{ $rec->reviewedBy?->name ?? '—' }}
                                    </p>
                                    @if ($rec->review_notes)
                                        <p class="text-xs italic text-slate-500 mt-0.5">"{{ $rec->review_notes }}"</p>
                                    @endif
                                </div>
                                <div class="text-right text-xs">
                                    <p class="font-bold">
                                        @if ($rec->diff_count > 0)
                                            <span class="text-amber-600 dark:text-amber-400">{{ $rec->diff_count }} {{ __('messages.reconciliation_diff_products') }}</span>
                                        @else
                                            <span class="text-emerald-600 dark:text-emerald-400">✓ {{ __('messages.reconciliation_clean') }}</span>
                                        @endif
                                    </p>
                                    <p class="text-slate-500">{{ __('messages.reconciliation_total_diff') }}: {{ number_format((float) $rec->total_diff, 3) }}</p>
                                </div>
                            </div>
                            @if ($rec->items->isNotEmpty())
                                <div class="overflow-x-auto rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                                    <table class="w-full text-xs">
                                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400">
                                            <tr>
                                                <th class="text-left px-3 py-1.5">{{ __('messages.product') }}</th>
                                                <th class="text-right px-3 py-1.5">{{ __('messages.reconciliation_imported') }}</th>
                                                <th class="text-right px-3 py-1.5">{{ __('messages.reconciliation_recorded') }}</th>
                                                <th class="text-right px-3 py-1.5">{{ __('messages.reconciliation_diff') }}</th>
                                                <th class="text-right px-3 py-1.5">{{ __('messages.adjustment_in') }} / {{ __('messages.adjustment_out') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                            @foreach ($rec->items as $item)
                                                <tr>
                                                    <td class="px-3 py-1.5 font-semibold">{{ $item->product?->name ?? '—' }}</td>
                                                    <td class="px-3 py-1.5 text-right font-mono">{{ number_format((float) $item->imported_quantity, 3) }}</td>
                                                    <td class="px-3 py-1.5 text-right font-mono">{{ number_format((float) $item->recorded_quantity, 3) }}</td>
                                                    <td class="px-3 py-1.5 text-right font-mono font-bold {{ bccomp((string) $item->difference, '0', 3) > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                                        {{ number_format((float) $item->difference, 3) }}
                                                    </td>
                                                    <td class="px-3 py-1.5 text-right font-mono">{{ $item->movement_type === 'adjustment_out' ? '−' : '+' }}{{ number_format((float) $item->correction, 3) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
@endsection
