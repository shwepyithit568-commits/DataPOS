@extends('layouts.pos.app')

@section('content')
    <div class="mx-auto max-w-6xl px-3 sm:px-4 py-4 sm:py-6 space-y-4 sm:space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">{{ __('messages.sidebar_purchases') }}</p>
                <h1 class="text-lg sm:text-xl font-black mt-0.5 truncate">{{ __('messages.po_list_title') }}</h1>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ url('/store/' . $store->slug . '/pos') }}"
                   class="rounded-xl px-3 sm:px-4 py-2.5 text-sm font-bold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                    ← <span class="hidden sm:inline">{{ __('messages.back_to_pos') }}</span>
                </a>
                <a href="{{ url('/store/' . $store->slug . '/pos/purchases/payables') }}"
                   class="rounded-xl px-3 sm:px-4 py-2.5 text-xs sm:text-sm font-bold text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/60 hover:bg-amber-100 dark:hover:bg-amber-950/70 transition"
                   title="{{ __('messages.payables_title') }}">
                    <svg class="w-4 h-4 inline-block -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    <span class="hidden md:inline">{{ __('messages.payables_title') }}</span>
                </a>
                <a href="{{ url('/store/' . $store->slug . '/pos/purchases/create') }}"
                   class="rounded-xl px-3 sm:px-4 py-2.5 text-sm font-bold bg-sky-600 hover:bg-sky-500 active:bg-sky-700 text-white shadow transition">
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

        {{-- Summary stats (filtered set) --}}
        @if ($pos->isNotEmpty())
            @php
                $outstanding = $pos->sum(fn ($po) => (float) $po->remaining_balance);
                $pendingCount = $pos->where('status', 'pending')->count();
            @endphp
            <div class="grid grid-cols-3 gap-2 sm:gap-3">
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-3 sm:p-4 flex items-center gap-2.5 sm:gap-3">
                    <div class="shrink-0 w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-sky-100 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 grid place-items-center">
                        <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-lg sm:text-2xl font-black text-gray-900 dark:text-slate-100 leading-none">{{ number_format($pos->count()) }}</p>
                        <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate">{{ __('messages.po_list_title') }}</p>
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-3 sm:p-4 flex items-center gap-2.5 sm:gap-3">
                    <div class="shrink-0 w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 grid place-items-center">
                        <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-lg sm:text-2xl font-black text-amber-600 dark:text-amber-400 leading-none">{{ number_format($pendingCount) }}</p>
                        <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate">{{ __('messages.po_status_pending') }}</p>
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-3 sm:p-4 flex items-center gap-2.5 sm:gap-3">
                    <div class="shrink-0 w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-violet-100 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 grid place-items-center">
                        <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm sm:text-2xl font-black text-gray-900 dark:text-slate-100 leading-none truncate">Ks {{ number_format($outstanding, 0) }}</p>
                        <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate">{{ __('messages.payables_title') }}</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Status filter pills with counts --}}
        <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap">
            @php
                $statuses = [
                    '' => __('messages.po_filter_all'),
                    'pending' => __('messages.po_status_pending'),
                    'ordered' => __('messages.po_status_ordered'),
                    'received' => __('messages.po_status_received'),
                    'cancelled' => __('messages.po_status_cancelled'),
                ];
                $allCount = $statusCounts->sum();
            @endphp
            @foreach ($statuses as $val => $label)
                <a href="{{ url('/store/' . $store->slug . '/pos/purchases' . ($val ? "?status={$val}" : '')) }}"
                   class="inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-bold transition
                          {{ ($status ?? '') === $val ? 'bg-sky-600 text-white shadow' : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700' }}">
                    {{ $label }}
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black {{ ($status ?? '') === $val ? 'bg-white/25' : 'bg-slate-100 dark:bg-slate-700' }}"
                    >{{ number_format($val === '' ? $allCount : ($statusCounts[$val] ?? 0)) }}</span>
                </a>
            @endforeach
        </div>

        {{-- PO list --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            @if ($pos->isEmpty())
                {{-- Empty state --}}
                <div class="py-14 text-center px-4">
                    <div class="mx-auto w-14 h-14 rounded-2xl bg-sky-50 dark:bg-sky-950/40 grid place-items-center mb-3">
                        <svg class="w-7 h-7 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z"/></svg>
                    </div>
                    <p class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('messages.po_none') }}</p>
                    <a href="{{ url('/store/' . $store->slug . '/pos/purchases/create') }}"
                       class="mt-4 inline-flex items-center gap-1.5 rounded-xl px-5 py-2.5 text-sm font-bold bg-sky-600 hover:bg-sky-500 text-white transition">
                        + {{ __('messages.po_new') }}
                    </a>
                </div>
            @else
                @php
                    $statusColors = [
                        'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                        'ordered' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400',
                        'received' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                        'cancelled' => 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
                        'returned' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400',
                    ];
                    $paymentColors = [
                        'unpaid' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
                        'partial' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                        'paid' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                    ];
                @endphp

                {{-- ===== Desktop: table ===== --}}
                <div class="hidden md:block overflow-x-auto scrollbar-thin">
                    <table class="w-full min-w-[760px] text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs text-slate-500 dark:text-slate-400">
                            <tr>
                                <th class="text-left px-4 py-3">{{ __('messages.po_number') }}</th>
                                <th class="text-left px-4 py-3">{{ __('messages.po_supplier') }}</th>
                                <th class="text-left px-4 py-3">{{ __('messages.reports_status') }}</th>
                                <th class="text-right px-4 py-3">{{ __('messages.reports_items') }}</th>
                                <th class="text-right px-4 py-3">{{ __('messages.reports_value') }}</th>
                                <th class="text-right px-4 py-3">{{ __('messages.receiving_total') }}</th>
                                <th class="text-right px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($pos as $po)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                    <td class="px-4 py-3">
                                        <a href="{{ url('/store/' . $store->slug . '/pos/purchases/' . $po->id) }}" class="group block">
                                            <span class="block font-mono font-bold text-sky-600 dark:text-sky-400 group-hover:underline">{{ $po->po_number }}</span>
                                            <span class="block text-[11px] text-slate-400 mt-0.5">{{ $po->created_at->format('d M Y, H:i') }}</span>
                                        </a>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($po->supplier)
                                            <div class="flex items-center gap-2 min-w-0">
                                                <span class="shrink-0 w-7 h-7 rounded-full bg-gradient-to-br from-orange-400 to-amber-500 text-white grid place-items-center font-black text-[11px] select-none">{{ mb_strtoupper(mb_substr(trim($po->supplier->name), 0, 1)) }}</span>
                                                <span class="font-semibold text-slate-700 dark:text-slate-200 truncate max-w-40" title="{{ $po->supplier->name }}">{{ $po->supplier->name }}</span>
                                            </div>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-block rounded-lg px-2 py-0.5 text-xs font-bold {{ $statusColors[$po->status] ?? '' }}">
                                            {{ __('messages.po_status_' . $po->status) }}
                                        </span>
                                        @if ($po->status === 'received')
                                            <span class="mt-1 inline-block ml-1 rounded-lg px-2 py-0.5 text-[10px] font-bold {{ $paymentColors[$po->payment_status] ?? '' }}">
                                                {{ __('messages.po_payment_' . $po->payment_status) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold">{{ $po->items->count() }}</td>
                                    <td class="px-4 py-3 text-right font-mono font-bold whitespace-nowrap">Ks {{ number_format((float) $po->total_cost) }}</td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        @if ((float) $po->remaining_balance > 0)
                                            <span class="font-mono font-bold text-amber-600 dark:text-amber-400">Ks {{ number_format((float) $po->remaining_balance) }}</span>
                                        @else
                                            <span class="text-slate-300 dark:text-slate-600">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ url('/store/' . $store->slug . '/pos/purchases/' . $po->id) }}"
                                           class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-bold text-sky-600 dark:text-sky-400 hover:bg-sky-50 dark:hover:bg-sky-950/40 transition">
                                            {{ __('messages.po_view') }} →
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- ===== Mobile: cards ===== --}}
                <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($pos as $po)
                        <a href="{{ url('/store/' . $store->slug . '/pos/purchases/' . $po->id) }}"
                           class="block p-4 active:bg-slate-50 dark:active:bg-slate-800/60 transition">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="font-mono font-bold text-sky-600 dark:text-sky-400 text-sm">{{ $po->po_number }}</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">{{ $po->created_at->format('d M Y, H:i') }} · {{ $po->supplier?->name ?? '—' }}</p>
                                </div>
                                <span class="shrink-0 rounded-lg px-2 py-0.5 text-xs font-bold {{ $statusColors[$po->status] ?? '' }}">
                                    {{ __('messages.po_status_' . $po->status) }}
                                </span>
                            </div>
                            <div class="mt-2.5 flex items-center justify-between gap-2">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="rounded-lg px-2 py-0.5 text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                        {{ $po->items->count() }} {{ __('messages.reports_items') }}
                                    </span>
                                    @if ($po->status === 'received')
                                        <span class="rounded-lg px-2 py-0.5 text-[10px] font-bold {{ $paymentColors[$po->payment_status] ?? '' }}">
                                            {{ __('messages.po_payment_' . $po->payment_status) }}
                                        </span>
                                    @endif
                                    @if ((float) $po->remaining_balance > 0)
                                        <span class="rounded-lg px-2 py-0.5 text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                            Ks {{ number_format((float) $po->remaining_balance) }}
                                        </span>
                                    @endif
                                </div>
                                <p class="font-mono font-black text-sm whitespace-nowrap">Ks {{ number_format((float) $po->total_cost) }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
