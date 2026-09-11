@extends('layouts.pos.app')

@section('title', __('messages.closing_title') . ' — ' . $store->name)
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
    @php
        $isManager = auth()->user()?->hasStoreRole($store->id, 'store_manager');
        $methods = \App\POS\Models\DailyClosing::expectedMethods();
        $summary = $totals['summary'] ?? [];
        $diffAmount = (float) ($closing ? $closing->total_difference : 0);
    @endphp

    <div class="w-full max-w-6xl mx-auto space-y-0.5 pb-6"
         x-data="{
             viewMode: localStorage.getItem('pos_closing_view') || 'table',
             setView(mode) {
                 this.viewMode = mode;
                 localStorage.setItem('pos_closing_view', mode);
             },
             showPrintModal: false,
             printType: '{{ $closing ? "z" : "x" }}',
             printLayout: '80mm',
             counted: Object.assign(Object.fromEntries(@js($methods).map(m => [m, '0'])), { credit: @js($totals['expected']['credit'] ?? '0') }),
             get expected() { return @js($totals['expected']); },
             get diffs() {
                 const d = {};
                 let total = 0;
                 for (const m of @js($methods)) {
                     d[m] = Math.round(((+this.counted[m] || 0) - (+this.expected[m] || 0)) * 100) / 100;
                     total += d[m];
                 }
                 d._total = Math.round(total * 100) / 100;
                 return d;
             }
         }">

        {{-- ============================================================
             1. COMPACT PAGE HEADER (34px - 38px Standard Height)
             ============================================================ --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
            <div class="flex items-center gap-2.5 min-w-0">
                <span class="w-8 h-8 rounded-lg bg-sky-50 dark:bg-sky-950/50 text-sky-600 dark:text-sky-400 grid place-items-center text-base font-bold shadow-xs shrink-0">
                    ⚖️
                </span>
                <div class="min-w-0">
                    <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5 truncate">
                        <span>{{ __('messages.closing_title') }}</span>
                        @if ($closing)
                            @if ($closing->isApproved())
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-black uppercase tracking-wide bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                    ✅ {{ __('messages.approved') }}
                                </span>
                            @else
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-black uppercase tracking-wide bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                                    ⏳ {{ __('messages.pending') }}
                                </span>
                            @endif
                        @else
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-black uppercase tracking-wide bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300">
                                📝 {{ __('messages.unclosed') }}
                            </span>
                        @endif
                        <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 font-mono">
                            {{ $date->format('d M Y') }}
                        </span>
                    </h1>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                        {{ __('messages.closing_hint') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-1.5 self-start sm:self-auto shrink-0 flex-wrap">
                {{-- X-Report Button --}}
                <a href="{{ route('pos.closing.x-report', ['store_slug' => $store->slug, 'date' => $date->toDateString()]) }}"
                   class="h-7 px-2.5 rounded-md bg-amber-500/15 hover:bg-amber-500/25 text-amber-800 dark:text-amber-200 text-xs font-bold transition inline-flex items-center gap-1 shadow-2xs cursor-pointer">
                    <span>📊</span>
                    <span>{{ __('messages.x_report') }}</span>
                </a>

                {{-- Print Modal Button --}}
                <button type="button" @click.stop="showPrintModal = true"
                        class="h-7 px-2.5 rounded-md bg-slate-800 dark:bg-slate-700 hover:bg-slate-700 dark:hover:bg-slate-600 text-white text-xs font-bold transition inline-flex items-center gap-1 shadow-2xs cursor-pointer">
                    <span>🖨️</span>
                    <span>{{ __('messages.print') }}</span>
                </button>

                {{-- Back to POS Button --}}
                <a href="{{ url('/store/' . $store->slug . '/pos') }}"
                   class="h-7 px-2.5 rounded-md bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition inline-flex items-center gap-1 shadow-2xs">
                    <span>←</span>
                    <span>{{ __('messages.back_to_pos') }}</span>
                </a>
            </div>
        </div>

        @if (session('error'))
            <div class="rounded-lg border border-rose-200 dark:border-rose-800 bg-rose-50 dark:bg-rose-950/50 text-rose-800 dark:text-rose-300 px-3 py-1.5 text-xs font-semibold shadow-2xs">
                ⚠️ {{ session('error') }}
            </div>
        @endif
        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/50 text-emerald-800 dark:text-emerald-300 px-3 py-1.5 text-xs font-semibold shadow-2xs">
                ✅ {{ session('success') }}
            </div>
        @endif

        {{-- ============================================================
             2. SUMMARY STAT CARDS (Row-based Centered Alignment Standard)
             ============================================================ --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-0.5 sm:gap-1" role="list">
            {{-- Expected Cash --}}
            <div role="listitem" class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
                <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-sky-100 text-sky-600 dark:bg-sky-950/70 dark:text-sky-300 shadow-inner text-xs sm:text-sm font-bold">
                    💵
                </div>
                <div class="min-w-0">
                    <div class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-mono">
                        {{ format_currency((float) ($totals['expected']['cash'] ?? 0), $store) }}
                    </div>
                    <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                        {{ __('messages.expected_cash') }}
                    </p>
                </div>
            </div>

            {{-- Opening Float --}}
            <div role="listitem" class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
                <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 shadow-inner text-xs sm:text-sm font-bold">
                    📥
                </div>
                <div class="min-w-0">
                    <div class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-mono">
                        {{ format_currency((float) ($totals['opening_amount'] ?? 0), $store) }}
                    </div>
                    <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                        {{ __('messages.opening_float') }}
                    </p>
                </div>
            </div>

            {{-- Net Sales --}}
            <div role="listitem" class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
                <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-emerald-100 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-300 shadow-inner text-xs sm:text-sm font-bold">
                    📈
                </div>
                <div class="min-w-0">
                    <div class="text-sm sm:text-base font-black text-emerald-600 dark:text-emerald-400 leading-none tabular-nums font-mono">
                        {{ format_currency((float) ($summary['net_sales'] ?? 0), $store) }}
                    </div>
                    <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                        {{ __('messages.net_sales') }}
                    </p>
                </div>
            </div>

            {{-- Variance / Discrepancy --}}
            @php
                $diffColor = $diffAmount < 0 ? 'text-rose-600 dark:text-rose-400' : ($diffAmount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-600 dark:text-slate-300');
                $diffBg = $diffAmount < 0 ? 'bg-rose-100 text-rose-600 dark:bg-rose-950/70 dark:text-rose-300' : ($diffAmount > 0 ? 'bg-amber-100 text-amber-600 dark:bg-amber-950/70 dark:text-amber-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300');
            @endphp
            <div role="listitem" class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
                <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center {{ $diffBg }} shadow-inner text-xs sm:text-sm font-bold">
                    ⚖️
                </div>
                <div class="min-w-0">
                    <div class="text-sm sm:text-base font-black {{ $diffColor }} leading-none tabular-nums font-mono">
                        @if ($closing)
                            {{ $diffAmount > 0 ? '+' : '' }}{{ format_currency($diffAmount, $store) }}
                        @else
                            <span x-text="(diffs._total > 0 ? '+' : '') + (typeof window.formatCurrency === 'function' ? window.formatCurrency(diffs._total) : diffs._total.toLocaleString())">
                                0
                            </span>
                        @endif
                    </div>
                    <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                        {{ __('messages.closing_total_difference') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- ============================================================
             3. INTERACTIVE INLINE TOOLBAR (Date Filters, Export & View Mode)
             ============================================================ --}}
        <div class="bg-white dark:bg-slate-900 px-2.5 py-1 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col md:flex-row md:items-center md:justify-between gap-1">
            {{-- Left: Date picker & Date quick-pills --}}
            <div class="flex flex-wrap items-center gap-1.5 flex-1">
                <form method="GET" action="{{ route('pos.closing.index', ['store_slug' => $store->slug]) }}" class="flex items-center gap-1">
                    <div class="relative">
                        <input type="date" name="date" value="{{ $date->toDateString() }}" max="{{ today()->toDateString() }}"
                               class="h-7 px-2 text-xs font-semibold rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-sky-500">
                    </div>
                    <button type="submit" class="h-7 px-2 text-xs font-bold rounded-md bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-800 dark:text-slate-200 transition cursor-pointer">
                        →
                    </button>
                </form>

                <div class="flex items-center gap-0.5 bg-slate-100 dark:bg-slate-800/80 p-0.5 rounded-md border border-slate-200/60 dark:border-slate-700">
                    <a href="{{ route('pos.closing.index', ['store_slug' => $store->slug, 'date' => today()->toDateString()]) }}"
                       class="px-2 py-0.5 rounded text-[11px] font-bold transition cursor-pointer {{ $date->isToday() ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-300 shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}">
                        {{ __('messages.today') }}
                    </a>
                    <a href="{{ route('pos.closing.index', ['store_slug' => $store->slug, 'date' => today()->subDay()->toDateString()]) }}"
                       class="px-2 py-0.5 rounded text-[11px] font-bold transition cursor-pointer {{ $date->isYesterday() ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-300 shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}">
                        {{ __('messages.yesterday') }}
                    </a>
                </div>
            </div>

            {{-- Right: Excel/CSV Exports & View Mode Switcher --}}
            <div class="flex items-center gap-1 self-end md:self-auto shrink-0">
                {{-- Excel Export Button --}}
                <a href="{{ route('pos.closing.export', ['store_slug' => $store->slug, 'date' => $date->toDateString(), 'format' => 'xlsx']) }}"
                   title="Export Excel (.xlsx)"
                   class="h-6 px-2 rounded text-[11px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 border border-emerald-200 dark:border-emerald-800 shadow-2xs transition inline-flex items-center gap-1 cursor-pointer">
                    <svg class="w-3 h-3 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    <span>Excel</span>
                </a>

                {{-- CSV Export Button --}}
                <a href="{{ route('pos.closing.export', ['store_slug' => $store->slug, 'date' => $date->toDateString(), 'format' => 'csv']) }}"
                   title="Export CSV (.csv)"
                   class="h-6 px-2 rounded text-[11px] font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 shadow-2xs transition inline-flex items-center gap-1 cursor-pointer">
                    <span>CSV</span>
                </a>

                {{-- Table / Cards Switcher --}}
                <div class="flex items-center gap-0.5 bg-slate-100 dark:bg-slate-800/80 p-0.5 rounded-md border border-slate-200/60 dark:border-slate-700">
                    <button type="button"
                            @click="setView('table')"
                            class="px-2 py-0.5 rounded text-[11px] font-bold flex items-center gap-1 transition cursor-pointer"
                            :class="viewMode === 'table' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-300 shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'">
                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
                        <span>{{ __('messages.view_table') ?? 'Table' }}</span>
                    </button>
                    <button type="button"
                            @click="setView('card')"
                            class="px-2 py-0.5 rounded text-[11px] font-bold flex items-center gap-1 transition cursor-pointer"
                            :class="viewMode === 'card' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-300 shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'">
                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        <span>{{ __('messages.view_cards') ?? 'Cards' }}</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Status Banner (when closing exists) --}}
        @if ($closing)
            @php
                $statusColors = $closing->isApproved()
                    ? 'border-emerald-200 dark:border-emerald-800 bg-emerald-50/70 dark:bg-emerald-950/30 text-emerald-900 dark:text-emerald-200'
                    : 'border-amber-200 dark:border-amber-800 bg-amber-50/70 dark:bg-amber-950/30 text-amber-900 dark:text-amber-200';
            @endphp
            <div class="rounded-lg border {{ $statusColors }} px-3 py-1.5 text-xs shadow-2xs space-y-0.5">
                <div class="flex items-center justify-between">
                    <div class="font-black text-xs sm:text-sm flex items-center gap-1.5">
                        <span>{{ $closing->isApproved() ? '✅ ' . __('messages.closing_approved') : '⏳ ' . __('messages.closing_pending') }}</span>
                    </div>
                    <span class="text-[10px] font-mono opacity-75">
                        Ref: CLOSING-{{ str_pad($closing->id, 5, '0', STR_PAD_LEFT) }}
                    </span>
                </div>
                <p class="text-[11px] opacity-85">
                    {{ __('messages.closed_by') }}: <span class="font-bold">{{ $closing->closingUser?->name ?? '—' }}</span> ({{ $closing->closed_at?->format('d M Y, H:i') }})
                    @if ($closing->approver)
                        · {{ __('messages.closing_approver') }}: <span class="font-bold">{{ $closing->approver->name }}</span> ({{ $closing->approved_at?->format('d M Y, H:i') }})
                    @endif
                </p>
                @if ($closing->explanation)
                    <p class="text-[11px] italic opacity-90">"{{ $closing->explanation }}"</p>
                @endif
            </div>
        @endif

        {{-- ============================================================
             4. MAIN CONTENT — TABLE & CARDS BREAKDOWN
             ============================================================ --}}
        <div class="rounded-lg bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs overflow-hidden">
            <div class="px-3 py-1 bg-slate-50/80 dark:bg-slate-800/60 border-b border-slate-200/60 dark:border-slate-800 flex items-center justify-between">
                <span class="text-[11px] font-black uppercase tracking-wide text-slate-700 dark:text-slate-300">
                    {{ __('messages.closing_details') }}
                </span>
                <span class="text-[11px] font-semibold text-slate-400">
                    {{ count($methods) }} {{ __('messages.payment_methods') }}
                </span>
            </div>

            @if ($closing)
                {{-- ─────────────────────────────────────────────────────────────
                     READ-ONLY SNAPSHOT (Approved or Pending)
                     ───────────────────────────────────────────────────────────── --}}

                {{-- Table View --}}
                <div x-show="viewMode === 'table'" class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/40 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800">
                            <tr>
                                <th class="text-left px-3 py-1.5">{{ __('messages.payment_method') }}</th>
                                <th class="text-right px-3 py-1.5">{{ __('messages.closing_expected') }}</th>
                                <th class="text-right px-3 py-1.5">{{ __('messages.closing_counted') }}</th>
                                <th class="text-right px-3 py-1.5">{{ __('messages.closing_difference') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($methods as $method)
                                @php
                                    $isCredit = $method === 'credit';
                                    $diff = $closing->differences[$method] ?? '0';
                                    $diffClass = (float) $diff < 0 ? 'text-rose-600 dark:text-rose-400 font-bold' : ((float) $diff > 0 ? 'text-amber-600 dark:text-amber-400 font-bold' : 'text-slate-400');
                                @endphp
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                    <td class="px-3 py-1.5 font-bold text-slate-900 dark:text-slate-100">
                                        {{ __('messages.payment_' . $method) }}
                                        @if ($isCredit)
                                            <span class="block text-[10px] font-normal text-slate-400">{{ __('messages.closing_credit_info') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-1.5 text-right font-mono font-semibold text-slate-700 dark:text-slate-300">
                                        {{ format_currency((float) ($closing->expected_totals[$method] ?? 0), $store) }}
                                    </td>
                                    <td class="px-3 py-1.5 text-right font-mono text-slate-800 dark:text-slate-200">
                                        {{ $isCredit ? '—' : format_currency((float) ($closing->counted_totals[$method] ?? 0), $store) }}
                                    </td>
                                    <td class="px-3 py-1.5 text-right font-mono {{ $diffClass }}">
                                        {{ (float) $diff > 0 ? '+' : '' }}{{ format_currency((float) $diff, $store) }}
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="bg-slate-50 dark:bg-slate-800/60 font-black border-t border-slate-200 dark:border-slate-700">
                                <td class="px-3 py-1.5 text-slate-900 dark:text-slate-100">{{ __('messages.closing_total_difference') }}</td>
                                <td></td>
                                <td></td>
                                <td class="px-3 py-1.5 text-right font-mono {{ (float) $closing->total_difference < 0 ? 'text-rose-600' : ((float) $closing->total_difference > 0 ? 'text-amber-600' : 'text-slate-400') }}">
                                    {{ (float) $closing->total_difference > 0 ? '+' : '' }}{{ format_currency((float) $closing->total_difference, $store) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Cards View --}}
                <div x-show="viewMode === 'card'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-0.5 sm:gap-1 p-1">
                    @foreach ($methods as $method)
                        @php
                            $isCredit = $method === 'credit';
                            $diff = $closing->differences[$method] ?? '0';
                            $diffClass = (float) $diff < 0 ? 'text-rose-600 dark:text-rose-400' : ((float) $diff > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400');
                        @endphp
                        <div class="p-2.5 rounded-lg border border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="font-black text-xs text-slate-900 dark:text-slate-100">
                                    {{ __('messages.payment_' . $method) }}
                                </span>
                                <span class="text-xs font-mono font-bold {{ $diffClass }}">
                                    {{ (float) $diff > 0 ? '+' : '' }}{{ format_currency((float) $diff, $store) }}
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-1 text-[11px] pt-0.5 border-t border-slate-200/50 dark:border-slate-800">
                                <div>
                                    <span class="text-slate-400">{{ __('messages.closing_expected') }}:</span>
                                    <span class="font-mono font-semibold ml-1">{{ format_currency((float) ($closing->expected_totals[$method] ?? 0), $store) }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-slate-400">{{ __('messages.closing_counted') }}:</span>
                                    <span class="font-mono font-semibold ml-1">{{ $isCredit ? '—' : format_currency((float) ($closing->counted_totals[$method] ?? 0), $store) }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Actions Area for Closed Record --}}
                @if ($closing->isPending())
                    <div class="p-3 bg-amber-50/40 dark:bg-amber-950/20 border-t border-amber-200/60 dark:border-amber-900/40 flex flex-col sm:flex-row items-center justify-between gap-2">
                        <div>
                            <p class="text-xs font-black uppercase text-amber-900 dark:text-amber-200">{{ __('messages.closing_approval') }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('messages.closing_approval_hint') }}</p>
                        </div>
                        @if ($isManager)
                            <form method="POST" action="{{ route('pos.closing.approve', ['store_slug' => $store->slug, 'closing' => $closing->id]) }}"
                                  onsubmit="return confirm('{{ __('messages.confirm_action') }}: နေ့စဉ် စာရင်းချုပ်အား အတည်ပြုရန် သေချာပါသလား? အတည်ပြုပြီးပါက ပြင်ဆင်၍ မရတော့ပါ။');">
                                @csrf
                                <button type="submit"
                                        class="h-8 px-4 rounded-md text-xs font-black text-white bg-emerald-600 hover:bg-emerald-500 transition shadow-2xs active:scale-95 cursor-pointer">
                                    ✅ {{ __('messages.closing_approve') }}
                                </button>
                            </form>
                        @else
                            <span class="text-xs font-semibold text-slate-500">{{ __('messages.closing_approval_waits') }}</span>
                        @endif
                    </div>
                @else
                    {{-- Approved notice --}}
                    <div class="p-2.5 bg-emerald-50/40 dark:bg-emerald-950/20 border-t border-emerald-200/60 dark:border-emerald-900/40 flex items-center justify-between text-xs text-emerald-800 dark:text-emerald-300">
                        <div class="flex items-center gap-1.5 font-bold">
                            <span>🔒</span>
                            <span>{{ __('messages.cannot_modify_approved_closing') }}</span>
                        </div>
                        <a href="{{ route('pos.closing.print', ['store_slug' => $store->slug, 'closing' => $closing->id, 'type' => 'z', 'layout' => '80mm']) }}"
                           target="_blank"
                           class="h-6 px-2.5 rounded-md text-[11px] font-bold text-white bg-emerald-700 hover:bg-emerald-600 transition shadow-2xs inline-flex items-center gap-1 cursor-pointer">
                            <span>🖨️</span>
                            <span>{{ __('messages.reprint') }}</span>
                        </a>
                    </div>
                @endif

            @else
                {{-- ─────────────────────────────────────────────────────────────
                     CREATE FORM (No Closing Record Yet)
                     ───────────────────────────────────────────────────────────── --}}
                <form method="POST" action="{{ route('pos.closing.store', ['store_slug' => $store->slug]) }}" class="space-y-0.5">
                    @csrf
                    <input type="hidden" name="business_date" value="{{ $date->toDateString() }}">

                    {{-- Table View Form --}}
                    <div x-show="viewMode === 'table'" class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead class="bg-slate-50 dark:bg-slate-800/40 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800">
                                <tr>
                                    <th class="text-left px-3 py-1.5">{{ __('messages.payment_method') }}</th>
                                    <th class="text-right px-3 py-1.5">{{ __('messages.closing_expected') }}</th>
                                    <th class="text-right px-3 py-1.5">{{ __('messages.closing_counted') }}</th>
                                    <th class="text-right px-3 py-1.5">{{ __('messages.closing_difference') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach ($methods as $method)
                                    @php $isCredit = $method === 'credit'; @endphp
                                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                        <td class="px-3 py-1.5 font-bold text-slate-900 dark:text-slate-100">
                                            {{ __('messages.payment_' . $method) }}
                                            @if ($isCredit)
                                                <span class="block text-[10px] font-normal text-slate-400">{{ __('messages.closing_credit_info') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-1.5 text-right font-mono font-semibold text-slate-700 dark:text-slate-300"
                                            x-text="typeof window.formatCurrency === 'function' ? window.formatCurrency(+expected['{{ $method }}'] || 0) : (+expected['{{ $method }}'] || 0).toLocaleString()"></td>
                                        <td class="px-3 py-1.5 text-right">
                                            <input type="number" name="counted[{{ $method }}]" min="0" step="any"
                                                   x-model.number="counted['{{ $method }}']" :disabled="{{ $isCredit ? 'true' : 'false' }}"
                                                   class="h-7 w-28 sm:w-32 ml-auto rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-2 py-0.5 text-right text-xs font-bold font-mono focus:outline-none focus:ring-1 focus:ring-sky-500">
                                        </td>
                                        <td class="px-3 py-1.5 text-right font-mono font-bold"
                                            :class="diffs['{{ $method }}'] < 0 ? 'text-rose-600 dark:text-rose-400' : (diffs['{{ $method }}'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400')"
                                            x-text="(diffs['{{ $method }}'] > 0 ? '+' : '') + (typeof window.formatCurrency === 'function' ? window.formatCurrency(diffs['{{ $method }}']) : diffs['{{ $method }}'].toLocaleString())"></td>
                                    </tr>
                                @endforeach
                                <tr class="bg-slate-50 dark:bg-slate-800/60 font-black border-t border-slate-200 dark:border-slate-700">
                                    <td class="px-3 py-1.5 text-slate-900 dark:text-slate-100">{{ __('messages.closing_total_difference') }}</td>
                                    <td></td>
                                    <td></td>
                                    <td class="px-3 py-1.5 text-right font-mono"
                                        :class="diffs._total < 0 ? 'text-rose-600 dark:text-rose-400' : (diffs._total > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400')"
                                        x-text="(diffs._total > 0 ? '+' : '') + (typeof window.formatCurrency === 'function' ? window.formatCurrency(diffs._total) : diffs._total.toLocaleString())"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Cards View Form --}}
                    <div x-show="viewMode === 'card'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-0.5 sm:gap-1 p-1">
                        @foreach ($methods as $method)
                            @php $isCredit = $method === 'credit'; @endphp
                            <div class="p-2.5 rounded-lg border border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <span class="font-black text-xs text-slate-900 dark:text-slate-100">
                                        {{ __('messages.payment_' . $method) }}
                                    </span>
                                    <span class="text-xs font-mono font-bold"
                                          :class="diffs['{{ $method }}'] < 0 ? 'text-rose-600 dark:text-rose-400' : (diffs['{{ $method }}'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400')"
                                          x-text="(diffs['{{ $method }}'] > 0 ? '+' : '') + (typeof window.formatCurrency === 'function' ? window.formatCurrency(diffs['{{ $method }}']) : diffs['{{ $method }}'].toLocaleString())"></span>
                                </div>
                                <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-200/60 dark:border-slate-800">
                                    <span class="text-slate-400">{{ __('messages.closing_expected') }}:</span>
                                    <span class="font-mono font-semibold"
                                          x-text="typeof window.formatCurrency === 'function' ? window.formatCurrency(+expected['{{ $method }}'] || 0) : (+expected['{{ $method }}'] || 0).toLocaleString()"></span>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-400">{{ __('messages.closing_counted') }}:</span>
                                    <input type="number" name="counted[{{ $method }}]" min="0" step="any"
                                           x-model.number="counted['{{ $method }}']" :disabled="{{ $isCredit ? 'true' : 'false' }}"
                                           class="h-7 w-28 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 py-0.5 text-right text-xs font-bold font-mono">
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Discrepancy Guidance Box --}}
                    <div x-show="diffs._total !== 0" x-cloak class="p-2.5 mx-2 my-1 rounded-md border border-amber-200 dark:border-amber-900/60 bg-amber-50/70 dark:bg-amber-950/30 text-xs space-y-0.5 text-amber-900 dark:text-amber-300 shadow-2xs">
                        <div class="flex items-center gap-1.5 font-bold">
                            <span>⚠️</span>
                            <span x-text="diffs._total < 0 ? 'ငွေစာရင်း လိုအပ်ချက် (Cash Shortage)' : 'ငွေစာရင်း ပိုလျှံမှု (Cash Overage)'"></span>
                        </div>
                        <p class="text-[11px] opacity-90">
                            {{ __('messages.explanation_required') }} — အောက်ပါ ကွက်လပ်တွင် အကြောင်းရင်းအား အသေးစိတ် ရှင်းလင်းရေးသားပေးပါ။
                        </p>
                    </div>

                    {{-- Form Footer: Notes & Submit Button --}}
                    <div class="p-2.5 bg-slate-50/60 dark:bg-slate-800/40 border-t border-slate-200/60 dark:border-slate-800 space-y-2">
                        <textarea name="explanation" rows="2" maxlength="2000"
                                  placeholder="{{ __('messages.closing_explanation') }}"
                                  class="w-full rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2.5 py-1.5 text-xs text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-sky-500"></textarea>

                        <button type="submit"
                                class="w-full h-8 sm:h-9 rounded-md font-black text-xs sm:text-sm text-white bg-sky-600 hover:bg-sky-500 active:scale-[0.99] transition shadow-2xs flex items-center justify-center gap-1.5 cursor-pointer">
                            <span>📋</span>
                            <span>{{ __('messages.closing_create') }}</span>
                        </button>
                    </div>
                </form>
            @endif
        </div>

        {{-- ============================================================
             5. PRINT MODAL DIALOG (Standard Ultra-Dense Layout)
             ============================================================ --}}
        <div x-show="showPrintModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-3 bg-slate-900/70 backdrop-blur-xs"
             @click.self="showPrintModal = false"
             @keydown.escape.window="showPrintModal = false">
            <div class="w-full max-w-sm bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 p-3.5 shadow-xl space-y-3"
                 @click.stop>
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                    <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                        <span>🖨️</span>
                        <span>{{ __('messages.print') }} — ပုံနှိပ်ပုံစံ ရွေးချယ်ပါ</span>
                    </h3>
                    <button type="button" @click="showPrintModal = false" class="text-slate-400 hover:text-slate-600 text-sm font-bold cursor-pointer">✕</button>
                </div>

                <div class="space-y-2.5 text-xs">
                    <div>
                        <label class="block font-bold text-[11px] text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.report_type') ?? 'Report Type' }}</label>
                        <div class="grid grid-cols-2 gap-1.5">
                            <label class="flex items-center gap-1.5 p-2 rounded-md border border-slate-200 dark:border-slate-700 cursor-pointer transition"
                                   :class="printType === 'x' ? 'bg-sky-50 dark:bg-sky-950/50 border-sky-400 text-sky-700 dark:text-sky-300' : ''">
                                <input type="radio" name="modal_type" value="x" x-model="printType">
                                <div class="min-w-0">
                                    <span class="font-bold text-[11px] block truncate">X-Report</span>
                                    <span class="text-[9px] text-slate-500 dark:text-slate-400 block truncate">{{ __('messages.reading_only') }}</span>
                                </div>
                            </label>

                            @if ($closing)
                                <label class="flex items-center gap-1.5 p-2 rounded-md border border-slate-200 dark:border-slate-700 cursor-pointer transition"
                                       :class="printType === 'z' ? 'bg-sky-50 dark:bg-sky-950/50 border-sky-400 text-sky-700 dark:text-sky-300' : ''">
                                    <input type="radio" name="modal_type" value="z" x-model="printType">
                                    <div class="min-w-0">
                                        <span class="font-bold text-[11px] block truncate">Z-Report</span>
                                        <span class="text-[9px] text-slate-500 dark:text-slate-400 block truncate">{{ __('messages.closing_title') }}</span>
                                    </div>
                                </label>
                            @else
                                <div class="flex items-center gap-1.5 p-2 rounded-md border border-dashed border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 opacity-60 cursor-not-allowed select-none"
                                     title="{{ __('messages.z_report_requires_closing') }}">
                                    <input type="radio" name="modal_type" value="z" disabled>
                                    <div class="min-w-0">
                                        <span class="font-bold text-[11px] text-slate-400 dark:text-slate-500 block truncate">Z-Report</span>
                                        <span class="text-[9px] text-amber-600 dark:text-amber-400 font-bold block truncate">🔒 {{ __('messages.z_report_requires_closing') }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>

                        @if (!$closing)
                            <div class="mt-2 p-2 rounded-md bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/70 text-[11px] text-amber-800 dark:text-amber-300 flex items-start gap-1.5 leading-snug">
                                <span class="shrink-0 text-xs">ℹ️</span>
                                <span>{{ __('messages.z_report_not_closed_note') }}</span>
                            </div>
                        @endif
                    </div>

                    <div>
                        <label class="block font-bold text-[11px] text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.paper_size') }} & {{ __('messages.orientation') }}</label>
                        <div class="grid grid-cols-2 gap-1">
                            <label class="flex items-center gap-1.5 p-1.5 rounded-md border border-slate-200 dark:border-slate-700 cursor-pointer text-[11px]"
                                   :class="printLayout === '58mm' ? 'bg-sky-50 dark:bg-sky-950/50 border-sky-400 text-sky-700 dark:text-sky-300' : ''">
                                <input type="radio" name="modal_layout" value="58mm" x-model="printLayout">
                                <span>{{ __('messages.print_58mm') }}</span>
                            </label>
                            <label class="flex items-center gap-1.5 p-1.5 rounded-md border border-slate-200 dark:border-slate-700 cursor-pointer text-[11px]"
                                   :class="printLayout === '80mm' ? 'bg-sky-50 dark:bg-sky-950/50 border-sky-400 text-sky-700 dark:text-sky-300' : ''">
                                <input type="radio" name="modal_layout" value="80mm" x-model="printLayout">
                                <span>{{ __('messages.print_80mm') }}</span>
                            </label>
                            <label class="flex items-center gap-1.5 p-1.5 rounded-md border border-slate-200 dark:border-slate-700 cursor-pointer text-[11px]"
                                   :class="printLayout === 'a5_portrait' ? 'bg-sky-50 dark:bg-sky-950/50 border-sky-400 text-sky-700 dark:text-sky-300' : ''">
                                <input type="radio" name="modal_layout" value="a5_portrait" x-model="printLayout">
                                <span>{{ __('messages.print_a5_portrait') }}</span>
                            </label>
                            <label class="flex items-center gap-1.5 p-1.5 rounded-md border border-slate-200 dark:border-slate-700 cursor-pointer text-[11px]"
                                   :class="printLayout === 'a5_landscape' ? 'bg-sky-50 dark:bg-sky-950/50 border-sky-400 text-sky-700 dark:text-sky-300' : ''">
                                <input type="radio" name="modal_layout" value="a5_landscape" x-model="printLayout">
                                <span>{{ __('messages.print_a5_landscape') }}</span>
                            </label>
                            <label class="flex items-center gap-1.5 p-1.5 rounded-md border border-slate-200 dark:border-slate-700 cursor-pointer text-[11px]"
                                   :class="printLayout === 'a4_portrait' ? 'bg-sky-50 dark:bg-sky-950/50 border-sky-400 text-sky-700 dark:text-sky-300' : ''">
                                <input type="radio" name="modal_layout" value="a4_portrait" x-model="printLayout">
                                <span>{{ __('messages.print_a4_portrait') }}</span>
                            </label>
                            <label class="flex items-center gap-1.5 p-1.5 rounded-md border border-slate-200 dark:border-slate-700 cursor-pointer text-[11px]"
                                   :class="printLayout === 'a4_landscape' ? 'bg-sky-50 dark:bg-sky-950/50 border-sky-400 text-sky-700 dark:text-sky-300' : ''">
                                <input type="radio" name="modal_layout" value="a4_landscape" x-model="printLayout">
                                <span>{{ __('messages.print_a4_landscape') }}</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-1.5 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showPrintModal = false"
                            class="h-7 px-3 text-xs font-bold rounded-md text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer">
                        {{ __('messages.cancel') ?? 'Cancel' }}
                    </button>
                    <a :href="'{{ url('/store/' . $store->slug . '/pos/closing/print') }}?type=' + printType + '&layout=' + printLayout + '&date={{ $date->toDateString() }}'"
                       target="_blank" @click="showPrintModal = false"
                       class="h-7 px-3 text-xs font-black rounded-md text-white bg-sky-600 hover:bg-sky-500 shadow-2xs transition inline-flex items-center gap-1 cursor-pointer">
                        <span>🖨️ {{ __('messages.print') }} →</span>
                    </a>
                </div>
            </div>
        </div>

    </div>
@endsection
