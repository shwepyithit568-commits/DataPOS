@extends('layouts.admin.app')

@section('content')
<div class="w-full space-y-4 sm:space-y-5">

    {{-- ============================================================
         HEADER — back, refund number, status pill
         ============================================================ --}}
    <header class="admin-page-header">
        <div class="min-w-0">
            <p class="text-[11px] font-black uppercase tracking-wider text-sky-600 dark:text-sky-400">
                {{ __('messages.sidebar_returns') }}
            </p>
            <h1 class="admin-page-title mt-0.5 font-mono">{{ $return->refund_number }}</h1>
            <p class="admin-page-sub mt-1">
                <span class="inline-flex items-center px-2.5 py-0.5 text-[11px] font-black rounded-full bg-sky-100 dark:bg-sky-900/50 text-sky-700 dark:text-sky-300">
                    {{ __('messages.posted') }}
                </span>
            </p>
        </div>
        <a href="{{ route('pos.returns.index', $storeRouteParams) }}"
           class="admin-secondary-btn shrink-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
            <span class="hidden sm:inline">{{ __('messages.back') }}</span>
        </a>
    </header>

    @if (session('success'))
        <div class="p-4 bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-800 rounded-xl text-sm text-green-700 dark:text-green-300">{{ session('success') }}</div>
    @endif

    {{-- ============================================================
         META GRID — sale, totals, actors
         ============================================================ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3">
        <div class="bg-white dark:bg-slate-800/90 border border-slate-200/80 dark:border-slate-700 rounded-xl p-3 sm:p-4">
            <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wide">{{ __('messages.sale_receipt') }}</p>
            <p class="mt-1 font-mono text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 truncate">
                {{ $return->sale?->receipt_number ?? '—' }}
            </p>
        </div>
        <div class="bg-white dark:bg-slate-800/90 border border-slate-200/80 dark:border-slate-700 rounded-xl p-3 sm:p-4">
            <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wide">{{ __('messages.refund_total') }}</p>
            <p class="mt-1 text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 tabular-nums truncate">Ks {{ number_format((float) $return->total) }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800/90 border border-slate-200/80 dark:border-slate-700 rounded-xl p-3 sm:p-4">
            <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wide">{{ __('messages.cashier') }}</p>
            <p class="mt-1 text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 truncate">{{ $return->cashier?->name ?? '—' }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800/90 border border-slate-200/80 dark:border-slate-700 rounded-xl p-3 sm:p-4">
            <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wide">{{ __('messages.date') }}</p>
            <p class="mt-1 text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 truncate">{{ $return->posted_at?->format('d M Y H:i') }}</p>
        </div>
    </div>

    {{-- ============================================================
         CUSTOMER
         ============================================================ --}}
    @if ($return->customer)
        <div class="bg-white dark:bg-slate-800/90 border border-slate-200/80 dark:border-slate-700 rounded-xl p-3 sm:p-4 flex items-center gap-3">
            <div class="shrink-0 w-9 h-9 rounded-xl bg-sky-100 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 grid place-items-center">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wide">{{ __('messages.customer') }}</p>
                <p class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 truncate">{{ $return->customer->name }}</p>
            </div>
        </div>
    @endif

    {{-- ============================================================
         ITEMS
         ============================================================ --}}
    <div class="bg-white dark:bg-slate-800/90 border border-slate-200/80 dark:border-slate-700 rounded-2xl overflow-hidden">
        <div class="px-4 py-3 border-b dark:border-slate-700 flex items-center justify-between gap-2">
            <h2 class="font-black text-slate-900 dark:text-slate-100">{{ __('messages.items') }}</h2>
            <span class="text-xs font-black text-slate-500 dark:text-slate-400 tabular-nums">{{ $return->items->count() }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-900/50 border-b dark:border-slate-700 font-black text-slate-700 dark:text-slate-200">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">{{ __('messages.product') }}</th>
                        <th class="px-4 py-3">{{ __('messages.sku') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('messages.quantity') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('messages.unit_price') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('messages.subtotal') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-slate-700">
                    @foreach ($return->items as $item)
                        <tr>
                            <td class="px-4 py-3 text-slate-400 dark:text-slate-500">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-900 dark:text-slate-100">{{ $item->product_name }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-500 dark:text-slate-400">{{ $item->sku }}</td>
                            <td class="px-4 py-3 text-right font-mono tabular-nums">{{ number_format((float) $item->quantity, 3) }}</td>
                            <td class="px-4 py-3 text-right font-mono tabular-nums">Ks {{ number_format((float) $item->unit_price) }}</td>
                            <td class="px-4 py-3 text-right font-mono font-black text-slate-900 dark:text-slate-100 tabular-nums">Ks {{ number_format((float) $item->line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-50 dark:bg-slate-900/50 border-t dark:border-slate-700">
                    <tr>
                        <td colspan="5" class="px-4 py-3 text-right font-black text-slate-900 dark:text-slate-100">{{ __('messages.total') }}</td>
                        <td class="px-4 py-3 text-right font-mono font-black text-slate-900 dark:text-slate-100 tabular-nums">Ks {{ number_format((float) $return->total) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- ============================================================
         REFUND PAYMENTS
         ============================================================ --}}
    <div class="bg-white dark:bg-slate-800/90 border border-slate-200/80 dark:border-slate-700 rounded-2xl overflow-hidden">
        <div class="px-4 py-3 border-b dark:border-slate-700">
            <h2 class="font-black text-slate-900 dark:text-slate-100">{{ __('messages.refund_method') }}</h2>
        </div>
        <div class="divide-y dark:divide-slate-700">
            @forelse ($return->payments as $payment)
                <div class="flex items-center justify-between px-4 py-3">
                    <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-700 dark:text-slate-200">
                        <span class="w-2 h-2 rounded-full {{ $payment->method === 'cash' ? 'bg-green-500' : 'bg-amber-500' }}" aria-hidden="true"></span>
                        {{ $payment->method === 'cash' ? __('messages.cash_refund') : __('messages.credit_refund') }}
                    </span>
                    <span class="font-mono font-black text-slate-900 dark:text-slate-100 tabular-nums">Ks {{ number_format((float) $payment->amount) }}</span>
                </div>
            @empty
                <div class="px-4 py-3 text-sm text-slate-400 dark:text-slate-500">{{ __('messages.no_refund_payments') }}</div>
            @endforelse
        </div>
    </div>

    {{-- ============================================================
         NOTES
         ============================================================ --}}
    @if ($return->notes)
        <div class="bg-white dark:bg-slate-800/90 border border-slate-200/80 dark:border-slate-700 rounded-xl p-4">
            <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wide">{{ __('messages.notes') }}</p>
            <p class="mt-1 text-sm text-slate-700 dark:text-slate-300">{{ $return->notes }}</p>
        </div>
    @endif
</div>
@endsection
