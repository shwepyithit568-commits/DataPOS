@extends('layouts.pos.app')

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-6 space-y-6">
        @include('pos.reports._tabs', ['active' => 'sales'])

        {{-- Filters --}}
        <form method="GET" action="{{ url('/store/' . $store->slug . '/pos/reports/sales') }}"
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
            <label class="flex flex-col gap-1 text-xs font-bold text-slate-500">
                {{ __('messages.cashier') }}
                <select name="cashier_id" class="rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 py-1.5 text-sm font-semibold">
                    <option value="">{{ __('messages.reports_all_cashiers') }}</option>
                    @foreach ($cashiers as $cashier)
                        <option value="{{ $cashier->id }}" @selected(request('cashier_id') == $cashier->id)>{{ $cashier->name }}</option>
                    @endforeach
                </select>
            </label>
            <button class="rounded-lg px-4 py-2 text-sm font-bold bg-sky-600 text-white hover:bg-sky-500 transition">
                {{ __('messages.reports_filter') }}
            </button>
        </form>

        {{-- Totals --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4">
                <p class="text-xs text-slate-500">{{ __('messages.reports_sale_count') }}</p>
                <p class="text-2xl font-black mt-1">{{ $report['count'] }}</p>
            </div>
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4">
                <p class="text-xs text-slate-500">{{ __('messages.reports_grand_total') }}</p>
                <p class="text-2xl font-black mt-1 text-sky-600 dark:text-sky-400">Ks {{ number_format((float) $report['total']) }}</p>
            </div>
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 col-span-2">
                <p class="text-xs text-slate-500 mb-1">{{ __('messages.reports_method_totals') }}</p>
                <div class="flex flex-wrap gap-2">
                    @forelse ($report['methods'] as $method => $amount)
                        <span class="px-2 py-1 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800">
                            {{ __('messages.payment_' . $method) }}: Ks {{ number_format((float) $amount) }}
                        </span>
                    @empty
                        <span class="text-xs text-slate-400">—</span>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Sales table --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm overflow-x-auto">
            @if ($report['sales']->isEmpty())
                <p class="text-center text-sm text-slate-500 py-8">{{ __('messages.reports_no_data') }}</p>
            @else
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs text-slate-500 dark:text-slate-400">
                        <tr>
                            <th class="text-left px-3 py-2">{{ __('messages.receipt') }}</th>
                            <th class="text-left px-3 py-2">{{ __('messages.reports_date') }}</th>
                            <th class="text-left px-3 py-2">{{ __('messages.cashier') }}</th>
                            <th class="text-left px-3 py-2">{{ __('messages.customer') }}</th>
                            <th class="text-right px-3 py-2">{{ __('messages.reports_items') }}</th>
                            <th class="text-right px-3 py-2">{{ __('messages.total') }}</th>
                            <th class="text-right px-3 py-2">{{ __('messages.status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($report['sales'] as $sale)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                <td class="px-3 py-2.5 font-mono font-bold text-sky-600 dark:text-sky-400">
                                    <a href="{{ url('/store/' . $store->slug . '/pos/sales/' . $sale->id . '/receipt') }}" target="_blank"
                                       class="hover:underline">{{ $sale->receipt_number }}</a>
                                </td>
                                <td class="px-3 py-2.5 whitespace-nowrap">{{ $sale->posted_at?->format('d M Y, H:i') }}</td>
                                <td class="px-3 py-2.5">{{ $sale->cashier?->name ?? '—' }}</td>
                                <td class="px-3 py-2.5">{{ $sale->customer?->name ?? '—' }}</td>
                                <td class="px-3 py-2.5 text-right">{{ $sale->items->sum('quantity') }}</td>
                                <td class="px-3 py-2.5 text-right font-mono font-bold">Ks {{ number_format((float) $sale->total) }}</td>
                                <td class="px-3 py-2.5 text-right">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold
                                        {{ $sale->status === 'posted' ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300' : 'bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300' }}">
                                        {{ $sale->status }}
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
