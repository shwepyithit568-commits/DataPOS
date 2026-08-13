@extends('layouts.pos.app')

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-6 space-y-6">
        @include('pos.reports._tabs', ['active' => 'cash'])

        <form method="GET" action="{{ url('/store/' . $store->slug . '/pos/reports/cash') }}"
              class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-sm flex flex-wrap items-end gap-3">
            <label class="flex flex-col gap-1 text-xs font-bold text-slate-500">
                {{ __('messages.reports_from') }}
                <input type="date" name="from" value="{{ $from->toDateString() }}" max="{{ today()->toDateString() }}"
                       class="rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 py-1.5 text-sm font-semibold">
            </label>
            <label class="flex flex-col gap-1 text-xs font-bold text-slate-500">
                {{ __('messages.reports_to') }}
                <input type="date" name="to" value="{{ $to->toDateString() }}" max="{{ today()->toDateString() }}"
                       class="rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 py-1.5 text-sm font-semibold">
            </label>
            <button class="rounded-lg px-4 py-2 text-sm font-bold bg-sky-600 text-white hover:bg-sky-500 transition">
                {{ __('messages.reports_filter') }}
            </button>
        </form>

        {{-- Aggregates --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4">
                <p class="text-xs text-slate-500">{{ __('messages.reports_shift_count') }}</p>
                <p class="text-2xl font-black mt-1">{{ $report['shift_count'] }}</p>
            </div>
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4">
                <p class="text-xs text-slate-500">{{ __('messages.expected_cash') }}</p>
                <p class="text-2xl font-black mt-1">Ks {{ number_format((float) $report['expected']) }}</p>
            </div>
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4">
                <p class="text-xs text-slate-500">{{ __('messages.actual_cash') }}</p>
                <p class="text-2xl font-black mt-1">Ks {{ number_format((float) $report['actual']) }}</p>
            </div>
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4">
                <p class="text-xs text-slate-500">{{ __('messages.difference') }}</p>
                <p class="text-2xl font-black mt-1 {{ (float) $report['difference'] < 0 ? 'text-rose-600' : ((float) $report['difference'] > 0 ? 'text-amber-600' : 'text-emerald-600') }}">
                    {{ (float) $report['difference'] > 0 ? '+' : '' }}{{ number_format((float) $report['difference']) }}
                </p>
            </div>
        </div>

        {{-- Per-shift table --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm overflow-x-auto">
            @if ($report['shifts']->isEmpty())
                <p class="text-center text-sm text-slate-500 py-8">{{ __('messages.reports_no_data') }}</p>
            @else
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs text-slate-500 dark:text-slate-400">
                        <tr>
                            <th class="text-left px-3 py-2">{{ __('messages.register') }}</th>
                            <th class="text-left px-3 py-2">{{ __('messages.cashier') }}</th>
                            <th class="text-right px-3 py-2">{{ __('messages.opening_cash') }}</th>
                            <th class="text-right px-3 py-2">{{ __('messages.cash_sales') }}</th>
                            <th class="text-right px-3 py-2">{{ __('messages.cash_refunds') }}</th>
                            <th class="text-right px-3 py-2">{{ __('messages.cash_in_out') }}</th>
                            <th class="text-right px-3 py-2">{{ __('messages.expected_cash') }}</th>
                            <th class="text-right px-3 py-2">{{ __('messages.actual') }}</th>
                            <th class="text-right px-3 py-2">{{ __('messages.difference') }}</th>
                            <th class="text-right px-3 py-2">{{ __('messages.status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($report['shifts'] as $shift)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                <td class="px-3 py-2.5 font-semibold">{{ $shift->register_name }}</td>
                                <td class="px-3 py-2.5">{{ $shift->cashier?->name ?? '—' }}</td>
                                <td class="px-3 py-2.5 text-right font-mono">Ks {{ number_format((float) $shift->opening_cash) }}</td>
                                <td class="px-3 py-2.5 text-right font-mono">Ks {{ number_format((float) $shift->cash_sales) }}</td>
                                <td class="px-3 py-2.5 text-right font-mono">Ks {{ number_format((float) $shift->cash_refunds) }}</td>
                                <td class="px-3 py-2.5 text-right font-mono">+{{ number_format((float) $shift->cash_in) }} / −{{ number_format((float) $shift->cash_out) }}</td>
                                <td class="px-3 py-2.5 text-right font-mono">{{ $shift->expected_closing_amount !== null ? 'Ks ' . number_format((float) $shift->expected_closing_amount) : '—' }}</td>
                                <td class="px-3 py-2.5 text-right font-mono">{{ $shift->actual_closing_amount !== null ? 'Ks ' . number_format((float) $shift->actual_closing_amount) : '—' }}</td>
                                <td class="px-3 py-2.5 text-right font-mono font-bold {{ (float) $shift->difference < 0 ? 'text-rose-600' : ((float) $shift->difference > 0 ? 'text-amber-600' : 'text-slate-400') }}">
                                    {{ $shift->difference !== null ? ((float) $shift->difference > 0 ? '+' : '') . number_format((float) $shift->difference) : '—' }}
                                </td>
                                <td class="px-3 py-2.5 text-right">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold
                                        {{ $shift->isOpen() ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-500' }}">
                                        {{ $shift->status }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection
