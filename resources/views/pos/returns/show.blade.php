@extends('layouts.admin.app')

@section('title', $return->refund_number . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
<div class="w-full space-y-0.5 pb-6">

    {{-- ============================================================
         1. COMPACT PAGE HEADER — refund number, status badge, back CTA
         ============================================================ --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-8 h-8 rounded-lg bg-sky-50 dark:bg-sky-950/50 text-sky-600 dark:text-sky-400 grid place-items-center text-base font-bold shadow-xs flex-shrink-0">
                ↩️
            </span>
            <div class="min-w-0">
                <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5 truncate">
                    <span class="font-mono">{{ $return->refund_number }}</span>
                    <span class="inline-flex items-center px-1.5 py-0.5 text-[9px] font-bold rounded-full bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800/60">
                        {{ __('messages.posted') }}
                    </span>
                </h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                    {{ $store->name }} · {{ __('messages.returns_title') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-1.5 self-start sm:self-auto shrink-0">
            <a href="{{ route('pos.returns.index', $storeRouteParams) }}"
               class="h-7 px-2.5 rounded-md bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition inline-flex items-center gap-1 border border-slate-200/60 dark:border-slate-700 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
                <span>{{ __('messages.back') }}</span>
            </a>
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
        {{-- Sale Receipt --}}
        <div class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 shadow-inner text-xs sm:text-sm font-bold">
                🧾
            </div>
            <div class="min-w-0">
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider truncate">{{ __('messages.sale_receipt') }}</p>
                <p class="font-mono text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 truncate">
                    {{ $return->sale?->receipt_number ?? '—' }}
                </p>
            </div>
        </div>

        {{-- Total Refunded --}}
        <div class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-rose-200/70 dark:border-rose-900/50 shadow-2xs bg-rose-50/20 dark:bg-rose-950/10 flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-rose-100 text-rose-600 dark:bg-rose-950/70 dark:text-rose-300 shadow-inner text-xs sm:text-sm font-bold">
                💸
            </div>
            <div class="min-w-0">
                <p class="text-[9px] sm:text-[10px] text-rose-600/80 dark:text-rose-400/80 font-bold uppercase tracking-wider truncate">{{ __('messages.refund_total') }}</p>
                <p class="text-xs sm:text-sm font-black text-rose-600 dark:text-rose-400 tabular-nums truncate font-outfit">
                    Ks {{ number_format((float) $return->total) }}
                </p>
            </div>
        </div>

        {{-- Cashier --}}
        <div class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-sky-100 text-sky-600 dark:bg-sky-950/70 dark:text-sky-300 shadow-inner text-xs sm:text-sm font-bold">
                👤
            </div>
            <div class="min-w-0">
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider truncate">{{ __('messages.cashier') }}</p>
                <p class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 truncate">{{ $return->cashier?->name ?? '—' }}</p>
            </div>
        </div>

        {{-- Date & Time --}}
        <div class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-amber-100 text-amber-600 dark:bg-amber-950/70 dark:text-amber-300 shadow-inner text-xs sm:text-sm font-bold">
                📅
            </div>
            <div class="min-w-0">
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider truncate">{{ __('messages.date') }}</p>
                <p class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 truncate">{{ $return->posted_at?->format('d M Y H:i') }}</p>
            </div>
        </div>
    </div>

    {{-- ============================================================
         3. CUSTOMER INFO (if customer exists)
         ============================================================ --}}
    @if ($return->customer)
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg px-2.5 py-1.5 shadow-2xs flex items-center gap-2.5">
            <div class="shrink-0 w-7 h-7 rounded-lg bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 grid place-items-center text-xs font-bold">
                👤
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">{{ __('messages.customer') }}</p>
                <p class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 truncate">{{ $return->customer->name }}</p>
            </div>
        </div>
    @endif

    {{-- ============================================================
         4. RETURNED ITEMS TABLE
         ============================================================ --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden">
        <div class="px-3 py-1.5 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between gap-2 bg-slate-50/50 dark:bg-slate-800/40">
            <h2 class="text-xs font-black text-slate-900 dark:text-slate-100">{{ __('messages.items') }}</h2>
            <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 tabular-nums font-outfit">{{ $return->items->count() }} items</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    <tr>
                        <th class="px-3 py-2 w-10">#</th>
                        <th class="px-3 py-2 min-w-[180px]">{{ __('messages.product') }}</th>
                        <th class="px-3 py-2 min-w-[120px]">{{ __('messages.sku') }}</th>
                        <th class="px-3 py-2 min-w-[80px] text-right">{{ __('messages.quantity') }}</th>
                        <th class="px-3 py-2 min-w-[110px] text-right">{{ __('messages.unit_price') }}</th>
                        <th class="px-3 py-2 min-w-[120px] text-right">{{ __('messages.subtotal') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($return->items as $item)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">
                            <td class="px-3 py-2 text-slate-400 dark:text-slate-500 font-mono text-[11px]">{{ $loop->iteration }}</td>
                            <td class="px-3 py-2 font-bold text-slate-900 dark:text-slate-100">{{ $item->product_name }}</td>
                            <td class="px-3 py-2 font-mono text-[11px] text-slate-500 dark:text-slate-400">{{ $item->sku }}</td>
                            <td class="px-3 py-2 text-right font-mono tabular-nums font-semibold">{{ number_format((float) $item->quantity, 3) }}</td>
                            <td class="px-3 py-2 text-right font-mono tabular-nums">Ks {{ number_format((float) $item->unit_price) }}</td>
                            <td class="px-3 py-2 text-right font-mono font-black text-slate-900 dark:text-slate-100 tabular-nums">Ks {{ number_format((float) $item->line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-50/80 dark:bg-slate-800/80 border-t border-slate-200 dark:border-slate-700">
                    <tr>
                        <td colspan="5" class="px-3 py-2 text-right font-black text-slate-900 dark:text-slate-100 text-xs">{{ __('messages.total') }}</td>
                        <td class="px-3 py-2 text-right font-mono font-black text-rose-600 dark:text-rose-400 tabular-nums text-xs sm:text-sm">Ks {{ number_format((float) $return->total) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- ============================================================
         5. REFUND PAYMENTS
         ============================================================ --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden">
        <div class="px-3 py-1.5 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/40">
            <h2 class="text-xs font-black text-slate-900 dark:text-slate-100">{{ __('messages.refund_method') }}</h2>
        </div>
        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse ($return->payments as $payment)
                <div class="flex items-center justify-between px-3 py-1.5 text-xs">
                    <span class="inline-flex items-center gap-1.5 font-bold text-slate-700 dark:text-slate-200">
                        <span class="w-1.5 h-1.5 rounded-full {{ $payment->method === 'cash' ? 'bg-emerald-500' : 'bg-amber-500' }}" aria-hidden="true"></span>
                        {{ $payment->method === 'cash' ? __('messages.cash_refund') : __('messages.credit_refund') }}
                    </span>
                    <span class="font-mono font-black text-slate-900 dark:text-slate-100 tabular-nums">Ks {{ number_format((float) $payment->amount) }}</span>
                </div>
            @empty
                <div class="px-3 py-2 text-xs text-slate-400 dark:text-slate-500">{{ __('messages.no_refund_payments') }}</div>
            @endforelse
        </div>
    </div>

    {{-- ============================================================
         6. NOTES (if exists)
         ============================================================ --}}
    @if ($return->notes)
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg p-2.5 shadow-2xs space-y-0.5">
            <p class="text-[10px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wide">{{ __('messages.notes') }}</p>
            <p class="text-xs text-slate-700 dark:text-slate-300 leading-tight">{{ $return->notes }}</p>
        </div>
    @endif
</div>
@endsection
