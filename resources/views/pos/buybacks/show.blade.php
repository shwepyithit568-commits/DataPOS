@extends('layouts.admin.app')

@section('title', $buyback->buyback_number . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
<div class="w-full space-y-0.5 pb-6">

    {{-- ============================================================
         1. COMPACT PAGE HEADER — back button, title, and status badge
         ============================================================ --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
        <div class="flex items-center gap-2.5 min-w-0">
            <a href="{{ route('pos.buybacks.index', $storeRouteParams) }}"
               class="w-7 h-7 rounded-md bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 grid place-items-center text-xs font-bold transition shrink-0"
               title="{{ __('messages.back') }}">
                ←
            </a>
            <div class="min-w-0">
                <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5 truncate">
                    <span>{{ $buyback->buyback_number }}</span>
                    @php
                        $statusStyles = [
                            'pending' => 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60',
                            'completed' => 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60',
                            'cancelled' => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700',
                        ];
                    @endphp
                    <span class="inline-flex items-center px-1.5 py-0.5 text-[9px] font-bold rounded-full {{ $statusStyles[$buyback->status] ?? '' }}">
                        {{ __('messages.' . $buyback->status) }}
                    </span>
                </h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                    {{ __('messages.sidebar_buy_back') }} · {{ $buyback->created_at->format('d M Y H:i') }}
                </p>
            </div>
        </div>

        {{-- Actions Toolbar in Header --}}
        <div class="flex items-center gap-1.5 self-start sm:self-auto shrink-0">
            @if($buyback->status === 'pending')
                <form method="POST" action="{{ route('pos.buybacks.complete', [...$storeRouteParams, 'buyback' => $buyback->id]) }}">
                    @csrf
                    <button type="submit"
                            onclick="return confirm('{{ __('messages.buyback_confirm_complete') ?? 'Confirm complete this buyback and receive stock?' }}')"
                            class="h-7 px-3 rounded-md bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black shadow-2xs hover:shadow-emerald-500/20 transition inline-flex items-center gap-1 cursor-pointer active:scale-95">
                        <span>✓ {{ __('messages.complete') }}</span>
                    </button>
                </form>
                <form method="POST" action="{{ route('pos.buybacks.cancel', [...$storeRouteParams, 'buyback' => $buyback->id]) }}">
                    @csrf
                    <button type="submit"
                            onclick="return confirm('{{ __('messages.buyback_confirm_cancel') ?? 'Are you sure you want to cancel this buyback?' }}')"
                            class="h-7 px-2.5 rounded-md bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 text-xs font-bold transition inline-flex items-center gap-1 cursor-pointer active:scale-95">
                        <span>✕ {{ __('messages.cancel') }}</span>
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="p-2 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs text-emerald-800 dark:text-emerald-300 flex items-center gap-1.5 shadow-2xs">
            <span class="font-bold text-sm">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- ============================================================
         2. META STAT CARDS (Row-based centered alignment)
         ============================================================ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-0.5 sm:gap-1">
        {{-- BuyBack Number --}}
        <div class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 shadow-inner text-xs sm:text-sm font-bold">
                🧾
            </div>
            <div class="min-w-0">
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider truncate">{{ __('messages.buyback_number') }}</p>
                <p class="font-mono text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 truncate">
                    {{ $buyback->buyback_number }}
                </p>
            </div>
        </div>

        {{-- Total Value --}}
        <div class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-rose-200/70 dark:border-rose-900/50 shadow-2xs bg-rose-50/20 dark:bg-rose-950/10 flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-rose-100 text-rose-600 dark:bg-rose-950/70 dark:text-rose-300 shadow-inner text-xs sm:text-sm font-bold">
                💸
            </div>
            <div class="min-w-0">
                <p class="text-[9px] sm:text-[10px] text-rose-600/80 dark:text-rose-400/80 font-bold uppercase tracking-wider truncate">{{ __('messages.total_value') }}</p>
                <p class="text-xs sm:text-sm font-black text-rose-600 dark:text-rose-400 tabular-nums truncate font-outfit">
                    Ks {{ number_format((float) $buyback->total_value) }}
                </p>
            </div>
        </div>

        {{-- Customer --}}
        <div class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-sky-100 text-sky-600 dark:bg-sky-950/70 dark:text-sky-300 shadow-inner text-xs sm:text-sm font-bold">
                👤
            </div>
            <div class="min-w-0">
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider truncate">{{ __('messages.customer') }}</p>
                <p class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 truncate">
                    {{ $buyback->customer?->name ?? 'Walk-in Customer' }}
                </p>
            </div>
        </div>

        {{-- Created By --}}
        <div class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-amber-100 text-amber-600 dark:bg-amber-950/70 dark:text-amber-300 shadow-inner text-xs sm:text-sm font-bold">
                📅
            </div>
            <div class="min-w-0">
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider truncate">{{ __('messages.created_by') }}</p>
                <p class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 truncate">
                    {{ $buyback->creator?->name ?? '—' }}
                </p>
            </div>
        </div>
    </div>

    {{-- Reason & Notes info if present --}}
    @if($buyback->reason || $buyback->notes)
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg p-2.5 shadow-2xs flex flex-wrap gap-4 text-xs">
            @if($buyback->reason)
                <div>
                    <span class="text-[10px] font-bold uppercase text-slate-400">{{ __('messages.reason') }}:</span>
                    <span class="font-semibold text-slate-700 dark:text-slate-300 ml-1">{{ $buyback->reason }}</span>
                </div>
            @endif
            @if($buyback->notes)
                <div>
                    <span class="text-[10px] font-bold uppercase text-slate-400">{{ __('messages.notes') }}:</span>
                    <span class="text-slate-600 dark:text-slate-400 ml-1">{{ $buyback->notes }}</span>
                </div>
            @endif
        </div>
    @endif

    {{-- ============================================================
         3. ITEMS TABLE
         ============================================================ --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden">
        <div class="px-3 py-1.5 bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-600 dark:text-slate-300 flex items-center gap-1.5">
                <span>📦</span>
                <span>{{ __('messages.items') }} ({{ $buyback->items->count() }})</span>
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50/50 dark:bg-slate-800/40 border-b border-slate-200 dark:border-slate-700 text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    <tr>
                        <th class="px-3 py-2 w-10">#</th>
                        <th class="px-3 py-2">{{ __('messages.product') }}</th>
                        <th class="px-3 py-2 text-right">{{ __('messages.quantity') }}</th>
                        <th class="px-3 py-2 text-right">{{ __('messages.unit_price') }}</th>
                        <th class="px-3 py-2 text-right">{{ __('messages.subtotal') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($buyback->items as $item)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">
                            <td class="px-3 py-2 text-slate-400">{{ $loop->iteration }}</td>
                            <td class="px-3 py-2 font-semibold text-slate-900 dark:text-slate-100">
                                {{ $item->product->name ?? '—' }}
                                @if($item->product?->sku)
                                    <span class="font-mono text-[10px] text-slate-400">({{ $item->product->sku }})</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right font-mono font-semibold text-slate-700 dark:text-slate-300 tabular-nums">
                                {{ number_format((float) $item->quantity) }}
                            </td>
                            <td class="px-3 py-2 text-right font-mono text-slate-600 dark:text-slate-400 tabular-nums">
                                Ks {{ number_format((float) $item->unit_price) }}
                            </td>
                            <td class="px-3 py-2 text-right font-mono font-black text-slate-900 dark:text-slate-100 tabular-nums">
                                Ks {{ number_format((float) ($item->quantity * $item->unit_price)) }}
                            </td>
                        </tr>
                    @endforeach
                    <tr class="bg-slate-50/80 dark:bg-slate-800/60 font-black">
                        <td colspan="4" class="px-3 py-2 text-right uppercase tracking-wider text-slate-600 dark:text-slate-400 text-[11px]">{{ __('messages.total') }}</td>
                        <td class="px-3 py-2 text-right font-mono font-black text-rose-600 dark:text-rose-400 text-xs sm:text-sm tabular-nums">
                            Ks {{ number_format((float) $buyback->total_value) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
