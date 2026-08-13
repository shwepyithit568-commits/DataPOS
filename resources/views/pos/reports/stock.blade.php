@extends('layouts.pos.app')

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-6 space-y-6">
        @include('pos.reports._tabs', ['active' => 'stock'])

        <form method="GET" action="{{ url('/store/' . $store->slug . '/pos/reports/stock') }}"
              class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-sm flex flex-wrap items-center gap-3">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('messages.reports_search') }}"
                   class="flex-1 min-w-48 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-sm font-semibold">
            <button class="rounded-lg px-4 py-2 text-sm font-bold bg-sky-600 text-white hover:bg-sky-500 transition">
                🔍 {{ __('messages.reports_filter') }}
            </button>
            @if (request('q'))
                <a href="{{ url('/store/' . $store->slug . '/pos/reports/stock') }}"
                   class="rounded-lg px-3 py-2 text-sm font-bold bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 transition">×</a>
            @endif
        </form>

        {{-- Totals --}}
        <div class="grid grid-cols-2 gap-3">
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4">
                <p class="text-xs text-slate-500">{{ __('messages.reports_total_units') }}</p>
                <p class="text-2xl font-black mt-1">{{ number_format((float) $report['total_units'], 3) }}</p>
            </div>
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4">
                <p class="text-xs text-slate-500">{{ __('messages.reports_stock_value') }}</p>
                <p class="text-2xl font-black mt-1 text-sky-600 dark:text-sky-400">Ks {{ number_format((float) $report['total_value']) }}</p>
            </div>
        </div>

        {{-- Stock table --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm overflow-x-auto">
            @if ($report['rows']->isEmpty())
                <p class="text-center text-sm text-slate-500 py-8">{{ __('messages.reports_stock_no_data') }}</p>
            @else
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs text-slate-500 dark:text-slate-400">
                        <tr>
                            <th class="text-left px-3 py-2">SKU</th>
                            <th class="text-left px-3 py-2">{{ __('messages.product') }}</th>
                            <th class="text-right px-3 py-2">{{ __('messages.reports_qty') }}</th>
                            <th class="text-right px-3 py-2">{{ __('messages.reports_avg_cost') }}</th>
                            <th class="text-right px-3 py-2">{{ __('messages.reports_value') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($report['rows'] as $row)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                <td class="px-3 py-2.5 font-mono text-xs">{{ $row['product']->sku }}</td>
                                <td class="px-3 py-2.5 font-semibold">{{ $row['product']->name }}</td>
                                <td class="px-3 py-2.5 text-right font-mono {{ (float) $row['quantity_on_hand'] <= 0 ? 'text-rose-600 font-bold' : '' }}">
                                    {{ number_format((float) $row['quantity_on_hand'], 3) }}
                                </td>
                                <td class="px-3 py-2.5 text-right font-mono">Ks {{ number_format((float) $row['unit_cost_avg'], 2) }}</td>
                                <td class="px-3 py-2.5 text-right font-mono font-bold">Ks {{ number_format((float) $row['value']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection
