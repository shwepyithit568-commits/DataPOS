@extends('layouts.pos.app')

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-6 space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('messages.sidebar_purchases') }}</p>
                <h1 class="text-xl font-black mt-0.5">{{ __('messages.po_list_title') }}</h1>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ url('/store/' . $store->slug . '/pos') }}"
                   class="rounded-xl px-4 py-2 text-sm font-bold bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 transition">
                    ← {{ __('messages.back_to_pos') }}
                </a>
                <a href="{{ url('/store/' . $store->slug . '/pos/purchases/create') }}"
                   class="rounded-xl px-4 py-2 text-sm font-bold bg-sky-600 hover:bg-sky-500 text-white transition">
                    + {{ __('messages.po_new') }}
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="rounded-xl border border-emerald-300 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 px-4 py-3 text-sm font-semibold">
                ✅ {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-xl border border-rose-300 dark:border-rose-700 bg-rose-50 dark:bg-rose-950 text-rose-800 dark:text-rose-300 px-4 py-3 text-sm font-semibold">
                ⚠️ {{ session('error') }}
            </div>
        @endif

        {{-- Status filter pills --}}
        <div class="flex items-center gap-2 flex-wrap">
            @php $statuses = ['' => __('messages.po_filter_all'), 'pending' => __('messages.po_status_pending'), 'ordered' => __('messages.po_status_ordered'), 'received' => __('messages.po_status_received'), 'cancelled' => __('messages.po_status_cancelled')]; @endphp
            @foreach ($statuses as $val => $label)
                <a href="{{ url('/store/' . $store->slug . '/pos/purchases' . ($val ? "?status={$val}" : '')) }}"
                   class="rounded-xl px-3 py-1.5 text-xs font-bold transition
                          {{ ($status ?? '') === $val ? 'bg-sky-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-600' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- PO list --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
            @if ($pos->isEmpty())
                <p class="text-center text-sm text-slate-500 py-8">{{ __('messages.po_none') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs text-slate-500 dark:text-slate-400">
                            <tr>
                                <th class="text-left px-3 py-2">{{ __('messages.po_number') }}</th>
                                <th class="text-left px-3 py-2">{{ __('messages.reports_date') }}</th>
                                <th class="text-left px-3 py-2">{{ __('messages.po_supplier') }}</th>
                                <th class="text-left px-3 py-2">{{ __('messages.reports_status') }}</th>
                                <th class="text-right px-3 py-2">{{ __('messages.reports_items') }}</th>
                                <th class="text-right px-3 py-2">{{ __('messages.reports_value') }}</th>
                                <th class="text-right px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($pos as $po)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                    <td class="px-3 py-2.5 font-mono font-bold text-sky-600 dark:text-sky-400">
                                        <a href="{{ url('/store/' . $store->slug . '/pos/purchases/' . $po->id) }}" class="hover:underline">
                                            {{ $po->po_number }}
                                        </a>
                                    </td>
                                    <td class="px-3 py-2.5 whitespace-nowrap">{{ $po->created_at->format('d M Y, H:i') }}</td>
                                    <td class="px-3 py-2.5">{{ $po->supplier?->name ?? '—' }}</td>
                                    <td class="px-3 py-2.5">
                                        @php
                                            $statusColors = [
                                                'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                                'ordered' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400',
                                                'received' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                                                'cancelled' => 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
                                            ];
                                        @endphp
                                        <span class="inline-block rounded-lg px-2 py-0.5 text-xs font-bold {{ $statusColors[$po->status] ?? '' }}">
                                            {{ __('messages.po_status_' . $po->status) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2.5 text-right">{{ $po->items->count() }}</td>
                                    <td class="px-3 py-2.5 text-right font-mono font-bold">Ks {{ number_format((float) $po->total_cost) }}</td>
                                    <td class="px-3 py-2.5 text-right">
                                        <a href="{{ url('/store/' . $store->slug . '/pos/purchases/' . $po->id) }}"
                                           class="text-xs font-bold text-sky-600 hover:underline">{{ __('messages.po_view') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
