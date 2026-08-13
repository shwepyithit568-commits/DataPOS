@extends('layouts.pos.app')

@section('content')
    @php
        $isManager = auth()->user()?->hasStoreRole($store->id, 'store_manager');
        $methods = \App\POS\Models\DailyClosing::expectedMethods();
    @endphp

    <div class="mx-auto max-w-3xl px-4 py-6 space-y-6">

        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('messages.closing_title') }}</p>
                <h1 class="text-xl font-black mt-0.5">{{ $date->format('d M Y') }}</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ __('messages.closing_hint') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <form method="GET" action="{{ url('/store/' . $store->slug . '/pos/closing') }}" class="flex items-center gap-2">
                    <input type="date" name="date" value="{{ $date->toDateString() }}" max="{{ today()->toDateString() }}"
                           class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm font-semibold">
                    <button class="rounded-xl px-4 py-2 text-sm font-bold bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 transition">→</button>
                </form>
                <a href="{{ url('/store/' . $store->slug . '/pos') }}"
                   class="rounded-xl px-4 py-2 text-sm font-bold bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 transition">
                    ← {{ __('messages.back_to_pos') }}
                </a>
            </div>
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

        {{-- Status banner --}}
        @if ($closing)
            @php
                $statusColors = $closing->isApproved()
                    ? 'border-emerald-300 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300'
                    : 'border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-950 text-amber-800 dark:text-amber-300';
            @endphp
            <div class="rounded-xl border {{ $statusColors }} px-4 py-3 text-sm space-y-0.5">
                <p class="font-black">
                    {{ $closing->isApproved() ? '✅ ' . __('messages.closing_approved') : '⏳ ' . __('messages.closing_pending') }}
                </p>
                <p class="text-xs opacity-80">
                    {{ __('messages.closed_by') }}: {{ $closing->closingUser?->name ?? '—' }} · {{ $closing->closed_at?->format('d M Y, H:i') }}
                    @if ($closing->approver) · {{ __('messages.closing_approver') }}: {{ $closing->approver->name }} ({{ $closing->approved_at?->format('d M Y, H:i') }}) @endif
                </p>
                @if (bccomp((string) $closing->total_difference, '0', 2) !== 0)
                    <p class="text-xs font-bold">{{ __('messages.closing_total_difference') }}: {{ (float) $closing->total_difference > 0 ? '+' : '' }}{{ number_format((float) $closing->total_difference) }} Ks</p>
                @endif
                @if ($closing->explanation)
                    <p class="text-xs italic opacity-80">"{{ $closing->explanation }}"</p>
                @endif
            </div>
        @endif

        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm">

            @if ($closing)
                {{-- ── Read-only snapshot ─────────────────────────────────── --}}
                <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                    <table class="w-full text-sm">
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
                                    <td class="px-3 py-2.5 text-right font-mono font-semibold">Ks {{ number_format((float) ($closing->expected_totals[$method] ?? 0)) }}</td>
                                    <td class="px-3 py-2.5 text-right font-mono">{{ $isCredit ? '—' : 'Ks ' . number_format((float) ($closing->counted_totals[$method] ?? 0)) }}</td>
                                    <td class="px-3 py-2.5 text-right font-mono font-bold {{ $diffClass }}">
                                        {{ (float) $diff > 0 ? '+' : '' }}{{ number_format((float) $diff) }}
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="bg-slate-50 dark:bg-slate-800/60 font-black">
                                <td class="px-3 py-2.5">{{ __('messages.closing_total_difference') }}</td>
                                <td></td>
                                <td></td>
                                <td class="px-3 py-2.5 text-right font-mono {{ (float) $closing->total_difference < 0 ? 'text-rose-600' : ((float) $closing->total_difference > 0 ? 'text-amber-600' : 'text-slate-400') }}">
                                    {{ (float) $closing->total_difference > 0 ? '+' : '' }}{{ number_format((float) $closing->total_difference) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                @if ($closing->isPending())
                    <div class="mt-5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 p-4 space-y-3">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('messages.closing_approval') }}</p>
                        <p class="text-xs text-slate-500">{{ __('messages.closing_approval_hint') }}</p>
                        @if ($isManager)
                            <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/closing/' . $closing->id . '/approve') }}">
                                @csrf
                                <button type="submit"
                                        class="w-full rounded-xl px-4 py-3 text-sm font-black text-white bg-emerald-600 hover:bg-emerald-500 transition">
                                    ✅ {{ __('messages.closing_approve') }}
                                </button>
                            </form>
                        @else
                            <p class="text-xs font-semibold text-slate-500">{{ __('messages.closing_approval_waits') }}</p>
                        @endif
                    </div>
                @endif
            @else
                {{-- ── Create form (no closing yet) ─────────────────────── --}}
                <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/closing') }}" class="space-y-5"
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
                        <table class="w-full text-sm">
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
                                            x-text="'Ks ' + (+expected['{{ $method }}'] || 0).toLocaleString()"></td>
                                        <td class="px-3 py-2.5 text-right">
                                            <input type="number" name="counted[{{ $method }}]" min="0" step="any"
                                                   x-model.number="counted['{{ $method }}']" :disabled="{{ $isCredit ? 'true' : 'false' }}"
                                                   class="w-36 ml-auto rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-2 py-1.5 text-right text-sm font-semibold">
                                        </td>
                                        <td class="px-3 py-2.5 text-right font-mono font-bold"
                                            :class="diffs['{{ $method }}'] < 0 ? 'text-rose-600' : (diffs['{{ $method }}'] > 0 ? 'text-amber-600' : 'text-slate-400')"
                                            x-text="(diffs['{{ $method }}'] > 0 ? '+' : '') + diffs['{{ $method }}'].toLocaleString()"></td>
                                    </tr>
                                @endforeach
                                <tr class="bg-slate-50 dark:bg-slate-800/60 font-black">
                                    <td class="px-3 py-2.5">{{ __('messages.closing_total_difference') }}</td>
                                    <td></td>
                                    <td></td>
                                    <td class="px-3 py-2.5 text-right font-mono"
                                        :class="diffs._total < 0 ? 'text-rose-600' : (diffs._total > 0 ? 'text-amber-600' : 'text-slate-400')"
                                        x-text="(diffs._total > 0 ? '+' : '') + diffs._total.toLocaleString()"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <textarea name="explanation" rows="2" maxlength="2000"
                              placeholder="{{ __('messages.closing_explanation') }}"
                              class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm"></textarea>

                    <button type="submit"
                            class="w-full rounded-xl px-4 py-3 text-sm font-black text-white bg-sky-600 hover:bg-sky-500 transition">
                        📋 {{ __('messages.closing_create') }}
                    </button>
                </form>
            @endif
        </div>
    </div>
@endsection
