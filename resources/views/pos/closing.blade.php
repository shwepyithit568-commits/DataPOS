@extends('layouts.pos.app')

@section('title', __('messages.closing_title') . ' — ' . $store->name)

@section('content')
    @php
        $isManager = auth()->user()?->hasStoreRole($store->id, 'store_manager');
        $methods = \App\POS\Models\DailyClosing::expectedMethods();
        $summary = $totals['summary'] ?? [];
    @endphp

    <div class="mx-auto max-w-4xl px-2 sm:px-4 py-4 space-y-3" x-data="{ showPrintModal: false, printType: '{{ $closing ? "z" : "x" }}', printLayout: '80mm' }">

        {{-- Top Header & Navigation Toolbar --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2.5 p-3 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wide bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300">
                        {{ $closing ? ($closing->isApproved() ? __('messages.approved') : __('messages.pending')) : __('messages.closing_title') }}
                    </span>
                    <h1 class="text-base sm:text-lg font-black text-slate-900 dark:text-slate-100">
                        {{ $date->format('d M Y') }}
                    </h1>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ __('messages.closing_hint') }}</p>
            </div>

            <div class="flex items-center gap-1.5 flex-wrap w-full sm:w-auto justify-between sm:justify-end">
                <form method="GET" action="{{ route('pos.closing.index', ['store_slug' => $store->slug]) }}" class="flex items-center gap-1">
                    <input type="date" name="date" value="{{ $date->toDateString() }}" max="{{ today()->toDateString() }}"
                           class="h-8 px-2 text-xs font-semibold rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
                    <button type="submit" class="h-8 px-2.5 text-xs font-bold rounded-xl bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 transition">
                        →
                    </button>
                </form>

                {{-- X-Report Reading Button --}}
                <a href="{{ route('pos.closing.x-report', ['store_slug' => $store->slug, 'date' => $date->toDateString()]) }}"
                   class="inline-flex items-center gap-1 h-8 px-2.5 rounded-xl text-xs font-bold text-amber-900 dark:text-amber-200 bg-amber-100 dark:bg-amber-950/60 hover:bg-amber-200 dark:hover:bg-amber-900/60 transition shadow-2xs">
                    📊 {{ __('messages.x_report') }}
                </a>

                {{-- Print Dialog Button --}}
                <button type="button" @click="showPrintModal = true"
                        class="inline-flex items-center gap-1 h-8 px-3 rounded-xl text-xs font-bold text-white bg-slate-800 dark:bg-slate-700 hover:bg-slate-700 dark:hover:bg-slate-600 transition shadow-2xs">
                    🖨️ {{ __('messages.print') ?? 'Print' }}
                </button>

                <a href="{{ url('/store/' . $store->slug . '/pos') }}"
                   class="inline-flex items-center gap-1 h-8 px-2.5 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                    ← {{ __('messages.back_to_pos') }}
                </a>
            </div>
        </div>

        @if (session('error'))
            <div class="rounded-xl border border-rose-300 dark:border-rose-700 bg-rose-50 dark:bg-rose-950 text-rose-800 dark:text-rose-300 px-3.5 py-2.5 text-xs font-semibold">
                ⚠️ {{ session('error') }}
            </div>
        @endif
        @if (session('success'))
            <div class="rounded-xl border border-emerald-300 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 px-3.5 py-2.5 text-xs font-semibold">
                ✅ {{ session('success') }}
            </div>
        @endif

        {{-- Centered Row-based Stat Cards (Ultra-dense standard) --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-1 sm:gap-1.5">
            <div class="p-2 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center justify-center gap-2.5 shadow-2xs">
                <div class="w-8 h-8 rounded-lg bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300 flex items-center justify-center text-sm font-bold">
                    💵
                </div>
                <div class="text-center">
                    <div class="text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('messages.expected_cash') }}</div>
                    <div class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 font-mono">
                        {{ format_currency((float) ($totals['expected']['cash'] ?? 0), $store) }}
                    </div>
                </div>
            </div>

            <div class="p-2 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center justify-center gap-2.5 shadow-2xs">
                <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 flex items-center justify-center text-sm font-bold">
                    📥
                </div>
                <div class="text-center">
                    <div class="text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('messages.opening_float') }}</div>
                    <div class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 font-mono">
                        {{ format_currency((float) ($totals['opening_amount'] ?? 0), $store) }}
                    </div>
                </div>
            </div>

            <div class="p-2 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center justify-center gap-2.5 shadow-2xs">
                <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300 flex items-center justify-center text-sm font-bold">
                    📈
                </div>
                <div class="text-center">
                    <div class="text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('messages.net_sales') }}</div>
                    <div class="text-xs sm:text-sm font-black text-emerald-600 dark:text-emerald-400 font-mono">
                        {{ format_currency((float) ($summary['net_sales'] ?? 0), $store) }}
                    </div>
                </div>
            </div>

            <div class="p-2 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center justify-center gap-2.5 shadow-2xs">
                @php
                    $diffAmount = (float) ($closing ? $closing->total_difference : 0);
                    $diffColor = $diffAmount < 0 ? 'text-rose-600 dark:text-rose-400' : ($diffAmount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-500');
                    $iconBg = $diffAmount < 0 ? 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300' : ($diffAmount > 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300');
                @endphp
                <div class="w-8 h-8 rounded-lg {{ $iconBg }} flex items-center justify-center text-sm font-bold">
                    ⚖️
                </div>
                <div class="text-center">
                    <div class="text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('messages.closing_total_difference') }}</div>
                    <div class="text-xs sm:text-sm font-black {{ $diffColor }} font-mono">
                        {{ $diffAmount > 0 ? '+' : '' }}{{ format_currency($diffAmount, $store) }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Status banner --}}
        @if ($closing)
            @php
                $statusColors = $closing->isApproved()
                    ? 'border-emerald-300 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300'
                    : 'border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300';
            @endphp
            <div class="rounded-xl border {{ $statusColors }} px-3.5 py-2.5 text-xs space-y-0.5 shadow-2xs">
                <div class="flex items-center justify-between">
                    <p class="font-black text-sm">
                        {{ $closing->isApproved() ? '✅ ' . __('messages.closing_approved') : '⏳ ' . __('messages.closing_pending') }}
                    </p>
                    <span class="text-[10px] font-mono opacity-75">
                        Ref: CLOSING-{{ str_pad($closing->id, 5, '0', STR_PAD_LEFT) }}
                    </span>
                </div>
                <p class="opacity-80">
                    {{ __('messages.closed_by') }}: {{ $closing->closingUser?->name ?? '—' }} · {{ $closing->closed_at?->format('d M Y, H:i') }}
                    @if ($closing->approver) · {{ __('messages.closing_approver') }}: {{ $closing->approver->name }} ({{ $closing->approved_at?->format('d M Y, H:i') }}) @endif
                </p>
                @if (bccomp((string) $closing->total_difference, '0', 2) !== 0)
                    <p class="font-bold">{{ __('messages.closing_total_difference') }}: {{ (float) $closing->total_difference > 0 ? '+' : '' }}{{ format_currency((float) $closing->total_difference, $store) }}</p>
                @endif
                @if ($closing->explanation)
                    <p class="italic opacity-85">"{{ $closing->explanation }}"</p>
                @endif
            </div>
        @endif

        {{-- Main Content Card --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-3 sm:p-4 shadow-sm space-y-4">

            @if ($closing)
                {{-- ── Read-only snapshot (Approved / Pending) ── --}}
                <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                    <table class="w-full text-xs sm:text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs text-slate-500 dark:text-slate-400">
                            <tr>
                                <th class="text-left px-3 py-2">{{ __('messages.payment_method') }}</th>
                                <th class="text-right px-3 py-2">{{ __('messages.closing_expected') }}</th>
                                <th class="text-right px-3 py-2">{{ __('messages.closing_counted') }}</th>
                                <th class="text-right px-3 py-2">{{ __('messages.closing_difference') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($methods as $method)
                                @php
                                    $isCredit = $method === 'credit';
                                    $diff = $closing->differences[$method] ?? '0';
                                    $diffClass = (float) $diff < 0 ? 'text-rose-600 dark:text-rose-400' : ((float) $diff > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400');
                                @endphp
                                <tr>
                                    <td class="px-3 py-2.5 font-bold">
                                        {{ __('messages.payment_' . $method) }}
                                        @if ($isCredit)
                                            <span class="block text-[10px] font-semibold text-slate-400">{{ __('messages.closing_credit_info') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5 text-right font-mono font-semibold">{{ format_currency((float) ($closing->expected_totals[$method] ?? 0), $store) }}</td>
                                    <td class="px-3 py-2.5 text-right font-mono">{{ $isCredit ? '—' : format_currency((float) ($closing->counted_totals[$method] ?? 0), $store) }}</td>
                                    <td class="px-3 py-2.5 text-right font-mono font-bold {{ $diffClass }}">
                                        {{ (float) $diff > 0 ? '+' : '' }}{{ format_currency((float) $diff, $store) }}
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="bg-slate-50 dark:bg-slate-800/60 font-black">
                                <td class="px-3 py-2.5">{{ __('messages.closing_total_difference') }}</td>
                                <td></td>
                                <td></td>
                                <td class="px-3 py-2.5 text-right font-mono {{ (float) $closing->total_difference < 0 ? 'text-rose-600' : ((float) $closing->total_difference > 0 ? 'text-amber-600' : 'text-slate-400') }}">
                                    {{ (float) $closing->total_difference > 0 ? '+' : '' }}{{ format_currency((float) $closing->total_difference, $store) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                @if ($closing->isPending())
                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 p-3 sm:p-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-black uppercase text-slate-700 dark:text-slate-200">{{ __('messages.closing_approval') }}</p>
                                <p class="text-xs text-slate-500">{{ __('messages.closing_approval_hint') }}</p>
                            </div>
                        </div>

                        @if ($isManager)
                            <form method="POST" action="{{ route('pos.closing.approve', ['store_slug' => $store->slug, 'closing' => $closing->id]) }}"
                                  onsubmit="return confirm('{{ __('messages.confirm_action') }}: နေ့စဉ် စာရင်းချုပ်အား အတည်ပြုရန် သေချာပါသလား? အတည်ပြုပြီးပါက ပြင်ဆင်၍ မရတော့ပါ။');">
                                @csrf
                                <button type="submit"
                                        class="w-full rounded-xl px-4 py-2.5 text-xs sm:text-sm font-black text-white bg-emerald-600 hover:bg-emerald-500 transition shadow-sm">
                                    ✅ {{ __('messages.closing_approve') }}
                                </button>
                            </form>
                        @else
                            <p class="text-xs font-semibold text-slate-500 py-1">{{ __('messages.closing_approval_waits') }}</p>
                        @endif
                    </div>
                @else
                    {{-- Approved notice (Immutable) --}}
                    <div class="rounded-xl border border-emerald-200 dark:border-emerald-800/60 bg-emerald-50/50 dark:bg-emerald-950/20 p-3 text-xs text-emerald-800 dark:text-emerald-300 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span>🔒</span>
                            <span>{{ __('messages.cannot_modify_approved_closing') }}</span>
                        </div>
                        <a href="{{ route('pos.closing.print', ['store_slug' => $store->slug, 'closing' => $closing->id, 'type' => 'z', 'layout' => '80mm']) }}"
                           target="_blank"
                           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-emerald-700 hover:bg-emerald-600 transition shadow-2xs">
                            🖨️ {{ __('messages.reprint') }}
                        </a>
                    </div>
                @endif

            @else
                {{-- ── Create form (No closing yet) ── --}}
                <form method="POST" action="{{ route('pos.closing.store', ['store_slug' => $store->slug]) }}" class="space-y-4"
                      x-data="{
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
                    @csrf
                    <input type="hidden" name="business_date" value="{{ $date->toDateString() }}">

                    <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                        <table class="w-full text-xs sm:text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs text-slate-500 dark:text-slate-400">
                                <tr>
                                    <th class="text-left px-3 py-2">{{ __('messages.payment_method') }}</th>
                                    <th class="text-right px-3 py-2">{{ __('messages.closing_expected') }}</th>
                                    <th class="text-right px-3 py-2">{{ __('messages.closing_counted') }}</th>
                                    <th class="text-right px-3 py-2">{{ __('messages.closing_difference') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach ($methods as $method)
                                    @php $isCredit = $method === 'credit'; @endphp
                                    <tr>
                                        <td class="px-3 py-2.5 font-bold">
                                            {{ __('messages.payment_' . $method) }}
                                            @if ($isCredit)
                                                <span class="block text-[10px] font-semibold text-slate-400">{{ __('messages.closing_credit_info') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2.5 text-right font-mono font-semibold"
                                            x-text="typeof window.formatCurrency === 'function' ? window.formatCurrency(+expected['{{ $method }}'] || 0) : (+expected['{{ $method }}'] || 0).toLocaleString()"></td>
                                        <td class="px-3 py-2.5 text-right">
                                            <input type="number" name="counted[{{ $method }}]" min="0" step="any"
                                                   x-model.number="counted['{{ $method }}']" :disabled="{{ $isCredit ? 'true' : 'false' }}"
                                                   class="w-32 sm:w-36 ml-auto rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-2 py-1 text-right text-xs sm:text-sm font-semibold">
                                        </td>
                                        <td class="px-3 py-2.5 text-right font-mono font-bold"
                                            :class="diffs['{{ $method }}'] < 0 ? 'text-rose-600' : (diffs['{{ $method }}'] > 0 ? 'text-amber-600' : 'text-slate-400')"
                                            x-text="(diffs['{{ $method }}'] > 0 ? '+' : '') + (typeof window.formatCurrency === 'function' ? window.formatCurrency(diffs['{{ $method }}']) : diffs['{{ $method }}'].toLocaleString())"></td>
                                    </tr>
                                @endforeach
                                <tr class="bg-slate-50 dark:bg-slate-800/60 font-black">
                                    <td class="px-3 py-2.5">{{ __('messages.closing_total_difference') }}</td>
                                    <td></td>
                                    <td></td>
                                    <td class="px-3 py-2.5 text-right font-mono"
                                        :class="diffs._total < 0 ? 'text-rose-600' : (diffs._total > 0 ? 'text-amber-600' : 'text-slate-400')"
                                        x-text="(diffs._total > 0 ? '+' : '') + (typeof window.formatCurrency === 'function' ? window.formatCurrency(diffs._total) : diffs._total.toLocaleString())"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Staff Discrepancy Guidance --}}
                    <div x-show="diffs._total !== 0" x-cloak class="p-3 rounded-xl border border-amber-200 dark:border-amber-900/60 bg-amber-50/70 dark:bg-amber-950/30 text-xs space-y-1 text-amber-900 dark:text-amber-300">
                        <div class="flex items-center gap-1.5 font-bold">
                            <span>⚠️</span>
                            <span x-text="diffs._total < 0 ? 'ငွေစာရင်း လိုအပ်ချက် (Cash Shortage)' : 'ငွေစာရင်း ပိုလျှံမှု (Cash Overage)'"></span>
                        </div>
                        <p class="text-[11px] opacity-90">
                            {{ __('messages.explanation_required') }} — အောက်ပါ ကွက်လပ်တွင် အကြောင်းရင်းအား အသေးစိတ် ရှင်းလင်းရေးသားပေးပါ။
                        </p>
                    </div>

                    <textarea name="explanation" rows="2" maxlength="2000"
                              placeholder="{{ __('messages.closing_explanation') }}"
                              class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs sm:text-sm"></textarea>

                    <button type="submit"
                            class="w-full rounded-xl px-4 py-3 text-xs sm:text-sm font-black text-white bg-sky-600 hover:bg-sky-500 transition shadow-sm">
                        📋 {{ __('messages.closing_create') }}
                    </button>
                </form>
            @endif

        </div>

        {{-- ── Print Layout Selection Modal ── --}}
        <div x-show="showPrintModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-xs"
             @keydown.escape.window="showPrintModal = false">
            <div class="w-full max-w-sm bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-xl space-y-4"
                 @click.away="showPrintModal = false">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                    <h3 class="text-sm font-black text-slate-900 dark:text-slate-100">
                        🖨️ {{ __('messages.print') }} — ပုံနှိပ်ပုံစံ ရွေးချယ်ပါ
                    </h3>
                    <button type="button" @click="showPrintModal = false" class="text-slate-400 hover:text-slate-600 text-sm font-bold">✕</button>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Report Type</label>
                        <div class="grid grid-cols-2 gap-1.5">
                            <label class="flex items-center gap-1.5 p-2 rounded-lg border border-slate-200 dark:border-slate-700 cursor-pointer"
                                   :class="printType === 'x' ? 'bg-sky-50 dark:bg-sky-950/40 border-sky-400' : ''">
                                <input type="radio" name="modal_type" value="x" x-model="printType">
                                <span class="font-bold">X-Report (Reading)</span>
                            </label>
                            <label class="flex items-center gap-1.5 p-2 rounded-lg border border-slate-200 dark:border-slate-700 cursor-pointer"
                                   :class="printType === 'z' ? 'bg-sky-50 dark:bg-sky-950/40 border-sky-400' : ''">
                                <input type="radio" name="modal_type" value="z" x-model="printType">
                                <span class="font-bold">Z-Report (Closing)</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.paper_size') }} & {{ __('messages.orientation') }}</label>
                        <div class="grid grid-cols-2 gap-1.5">
                            <label class="flex items-center gap-1.5 p-2 rounded-lg border border-slate-200 dark:border-slate-700 cursor-pointer"
                                   :class="printLayout === '58mm' ? 'bg-sky-50 dark:bg-sky-950/40 border-sky-400' : ''">
                                <input type="radio" name="modal_layout" value="58mm" x-model="printLayout">
                                <span>{{ __('messages.print_58mm') }}</span>
                            </label>
                            <label class="flex items-center gap-1.5 p-2 rounded-lg border border-slate-200 dark:border-slate-700 cursor-pointer"
                                   :class="printLayout === '80mm' ? 'bg-sky-50 dark:bg-sky-950/40 border-sky-400' : ''">
                                <input type="radio" name="modal_layout" value="80mm" x-model="printLayout">
                                <span>{{ __('messages.print_80mm') }}</span>
                            </label>
                            <label class="flex items-center gap-1.5 p-2 rounded-lg border border-slate-200 dark:border-slate-700 cursor-pointer"
                                   :class="printLayout === 'a5_portrait' ? 'bg-sky-50 dark:bg-sky-950/40 border-sky-400' : ''">
                                <input type="radio" name="modal_layout" value="a5_portrait" x-model="printLayout">
                                <span>{{ __('messages.print_a5_portrait') }}</span>
                            </label>
                            <label class="flex items-center gap-1.5 p-2 rounded-lg border border-slate-200 dark:border-slate-700 cursor-pointer"
                                   :class="printLayout === 'a5_landscape' ? 'bg-sky-50 dark:bg-sky-950/40 border-sky-400' : ''">
                                <input type="radio" name="modal_layout" value="a5_landscape" x-model="printLayout">
                                <span>{{ __('messages.print_a5_landscape') }}</span>
                            </label>
                            <label class="flex items-center gap-1.5 p-2 rounded-lg border border-slate-200 dark:border-slate-700 cursor-pointer"
                                   :class="printLayout === 'a4_portrait' ? 'bg-sky-50 dark:bg-sky-950/40 border-sky-400' : ''">
                                <input type="radio" name="modal_layout" value="a4_portrait" x-model="printLayout">
                                <span>{{ __('messages.print_a4_portrait') }}</span>
                            </label>
                            <label class="flex items-center gap-1.5 p-2 rounded-lg border border-slate-200 dark:border-slate-700 cursor-pointer"
                                   :class="printLayout === 'a4_landscape' ? 'bg-sky-50 dark:bg-sky-950/40 border-sky-400' : ''">
                                <input type="radio" name="modal_layout" value="a4_landscape" x-model="printLayout">
                                <span>{{ __('messages.print_a4_landscape') }}</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showPrintModal = false"
                            class="px-3 py-1.5 text-xs font-bold rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                        {{ __('messages.cancel') ?? 'Cancel' }}
                    </button>
                    <a :href="'{{ url('/store/' . $store->slug . '/pos/closing/print') }}?type=' + printType + '&layout=' + printLayout + '&date={{ $date->toDateString() }}'"
                       target="_blank" @click="showPrintModal = false"
                       class="px-4 py-1.5 text-xs font-black rounded-lg text-white bg-sky-600 hover:bg-sky-500 shadow-sm transition">
                        🖨️ {{ __('messages.print') }} →
                    </a>
                </div>
            </div>
        </div>

    </div>
@endsection
