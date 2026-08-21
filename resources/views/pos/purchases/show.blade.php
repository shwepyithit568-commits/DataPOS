@extends('layouts.pos.app')

@php
    // Slim item payload for the return modal (no nested product blobs).
    $returnItems = $po->items->map(fn ($i) => [
        'product_id' => $i->product_id,
        'product_variant_id' => $i->product_variant_id,
        'quantity' => (string) $i->quantity,
        'unit_cost' => (string) $i->unit_cost,
        'name' => $i->product?->name ?? ('Product #' . $i->product_id),
    ])->values();
@endphp

@section('content')
    <div class="mx-auto max-w-5xl px-3 sm:px-4 py-4 sm:py-6 space-y-4 sm:space-y-6"
         x-data="{
             poItems: @js($returnItems),
             payOpen: false,
             payAmount: '',
             paySubmitting: false,
             payBalance: {{ (float) $po->remaining_balance }},
             returnOpen: false,
             returnItems: [],
             returnSubmitting: false,
             fmt2(n) { return Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
             fmt(n) { return Number(n).toLocaleString(undefined, { maximumFractionDigits: 2 }); },
             get payAmt() { return parseFloat(this.payAmount) || 0; },
             get payOver() { return this.payAmt > this.payBalance; },
             get payValid() { return this.payAmt > 0 && this.payAmt <= this.payBalance; },
             get payAfter() { return Math.max(0, this.payBalance - this.payAmt); },
             get retSelected() { return this.returnItems.filter(i => (parseFloat(i.returnQty) || 0) > 0).length; },
             get retTotal() { return this.returnItems.reduce((s, i) => s + (parseFloat(i.returnQty) || 0) * (parseFloat(i.unit_cost) || 0), 0); },
             get retCanSubmit() { return this.retSelected > 0 && !this.returnSubmitting; },
             openPay() {
                 this.payOpen = true;
                 this.payAmount = '';
                 this.paySubmitting = false;
                 this.$nextTick(() => this.$refs.payAmount?.focus());
             },
             setHalf() { this.payAmount = String(Math.round(this.payBalance / 2 * 100) / 100); },
             setFull() { this.payAmount = String(this.payBalance); },
             submitPay() {
                 if (!this.payValid || this.paySubmitting) return;
                 this.paySubmitting = true;
                 this.$refs.payForm.submit();
             },
             openReturn() {
                 this.returnOpen = true;
                 this.returnItems = this.poItems.map(i => ({ ...i, returnQty: '0' }));
                 this.returnSubmitting = false;
             },
             retMax(i) { i.returnQty = String(parseFloat(i.quantity)); },
             retZero(i) { i.returnQty = '0'; },
             retMaxAll() { this.returnItems.forEach(i => i.returnQty = String(parseFloat(i.quantity))); },
             retZeroAll() { this.returnItems.forEach(i => i.returnQty = '0'); },
             submitReturn() {
                 if (!this.retCanSubmit) return;
                 this.returnSubmitting = true;
                 this.$refs.returnForm.submit();
             }
         }"
         @keydown.escape.window="payOpen = false; returnOpen = false">

        {{-- Header --}}
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">{{ __('messages.sidebar_purchases') }}</p>
                <h1 class="text-lg sm:text-xl font-black mt-0.5 font-mono truncate">{{ $po->po_number }}</h1>
            </div>
            <a href="{{ url('/store/' . $store->slug . '/pos/purchases') }}"
               class="shrink-0 rounded-xl px-3 sm:px-4 py-2.5 text-sm font-bold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                ← {{ __('messages.back') }}
            </a>
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

        @php
            $statusColors = [
                'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                'ordered' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400',
                'received' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                'cancelled' => 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
                'returned' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
            ];
            $paymentStatusColors = [
                'unpaid' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
                'partial' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                'paid' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
            ];
        @endphp

        {{-- ===== PO status + supplier card ===== --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            {{-- Badges + balance --}}
            <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-start justify-between gap-3 flex-wrap">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="inline-block rounded-lg px-2.5 py-1 text-xs font-bold {{ $statusColors[$po->status] ?? '' }}">
                            {{ __('messages.po_status_' . $po->status) }}
                        </span>
                        @if ($po->isReceived())
                            <span class="inline-block rounded-lg px-2.5 py-1 text-xs font-bold {{ $paymentStatusColors[$po->payment_status] ?? '' }}">
                                {{ __('messages.po_payment_' . $po->payment_status) }}
                            </span>
                        @endif
                        @if ($po->isReceived() && !$po->isPaid())
                            <span class="inline-flex items-baseline gap-1.5 rounded-lg bg-rose-50 dark:bg-rose-950/40 border border-rose-100 dark:border-rose-900/50 px-2.5 py-1">
                                <span class="text-[10px] font-bold text-rose-500 uppercase">{{ __('messages.payables_balance') }}</span>
                                <span class="text-sm font-black text-rose-600 dark:text-rose-400 font-mono">Ks {{ number_format((float) $po->remaining_balance) }}</span>
                            </span>
                        @endif
                    </div>

                    <p class="text-[11px] text-slate-400">
                        {{ __('messages.po_created') }}: {{ $po->created_at->format('d M Y, H:i') }}
                        @if ($po->createdBy) · {{ $po->createdBy->name }} @endif
                    </p>
                </div>

                {{-- Timeline: created → ordered → received --}}
                @if ($po->status !== 'cancelled')
                    @php
                        $steps = [
                            ['label' => __('messages.po_created'), 'at' => $po->created_at, 'done' => true],
                            ['label' => __('messages.po_ordered_at'), 'at' => $po->ordered_at, 'done' => $po->ordered_at !== null],
                            ['label' => __('messages.po_received_at'), 'at' => $po->received_at, 'done' => $po->received_at !== null],
                        ];
                    @endphp
                    <div class="mt-4 flex items-center">
                        @foreach ($steps as $si => $step)
                            @if ($si > 0)
                                <div class="flex-1 h-0.5 mx-1 rounded {{ $step['done'] ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-700' }}"></div>
                            @endif
                            <div class="flex flex-col items-center gap-1 shrink-0">
                                <div class="w-6 h-6 rounded-full grid place-items-center text-[10px] font-black
                                            {{ $step['done'] ? 'bg-emerald-500 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-400' }}">
                                    @if ($step['done'])
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    @else
                                        {{ $si + 1 }}
                                    @endif
                                </div>
                                <span class="text-[10px] font-bold {{ $step['done'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' }}">{{ $step['label'] }}</span>
                                @if ($step['at'])
                                    <span class="text-[9px] text-slate-400 font-mono">{{ $step['at']->format('d/m H:i') }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="mt-4 text-xs text-slate-400">
                        {{ __('messages.po_cancelled_at') }}: {{ $po->cancelled_at?->format('d M Y, H:i') }}
                    </p>
                @endif
            </div>

            {{-- Supplier + reference + notes --}}
            <div class="p-4 sm:p-5 grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1.5">{{ __('messages.po_supplier') }}</p>
                    @if ($po->supplier)
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="shrink-0 w-9 h-9 rounded-full bg-gradient-to-br from-orange-400 to-amber-500 text-white grid place-items-center font-black text-sm select-none">{{ mb_strtoupper(mb_substr(trim($po->supplier->name), 0, 1)) }}</span>
                            <div class="min-w-0">
                                <p class="font-bold text-sm truncate">{{ $po->supplier->name }}</p>
                                @if ($po->supplier->phone)
                                    <a href="tel:{{ preg_replace('/\s+/', '', $po->supplier->phone) }}"
                                       class="text-xs font-mono font-semibold text-sky-600 dark:text-sky-400 hover:underline">{{ $po->supplier->phone }}</a>
                                @endif
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-slate-400">—</p>
                    @endif
                </div>
                <div class="space-y-1.5">
                    @if ($po->reference)
                        <div class="flex items-start gap-2 text-xs">
                            <span class="shrink-0 font-bold text-slate-400 uppercase text-[10px] mt-0.5 w-16">{{ __('messages.receiving_reference') }}</span>
                            <span class="font-semibold break-words">{{ $po->reference }}</span>
                        </div>
                    @endif
                    @if ($po->notes)
                        <div class="flex items-start gap-2 text-xs">
                            <span class="shrink-0 font-bold text-slate-400 uppercase text-[10px] mt-0.5 w-16">{{ __('messages.notes') }}</span>
                            <span class="font-semibold break-words">{{ $po->notes }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Actions --}}
            <div class="px-4 sm:px-5 pb-4 sm:pb-5 grid grid-cols-2 sm:flex sm:flex-wrap gap-2">
                @if ($po->isPending())
                    <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/purchases/' . $po->id . '/order') }}" x-data
                          x-confirm="{{ __('messages.po_confirm_order') }}" class="contents">
                        @csrf
                        <button type="submit"
                                class="rounded-xl px-4 py-2.5 h-11 text-sm font-bold bg-sky-600 hover:bg-sky-500 active:bg-sky-700 text-white transition">
                            ✅ {{ __('messages.po_btn_order') }}
                        </button>
                    </form>
                    <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/purchases/' . $po->id . '/cancel') }}" x-data
                          x-confirm="{{ __('messages.po_confirm_cancel') }}" class="contents">
                        @csrf
                        <button type="submit"
                                class="rounded-xl px-4 py-2.5 h-11 text-sm font-bold bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-rose-50 dark:hover:bg-rose-950/40 hover:text-rose-600 hover:border-rose-200 dark:hover:border-rose-900 transition">
                            ✕ {{ __('messages.po_btn_cancel') }}
                        </button>
                    </form>
                @endif

                @if ($po->isOrdered())
                    <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/purchases/' . $po->id . '/receive') }}" x-data
                          x-confirm="{{ __('messages.po_confirm_receive') }}" class="contents">
                        @csrf
                        <button type="submit"
                                class="rounded-xl px-4 py-2.5 h-11 text-sm font-bold bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white transition">
                            📦 {{ __('messages.po_btn_receive') }}
                        </button>
                    </form>
                    <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/purchases/' . $po->id . '/cancel') }}" x-data
                          x-confirm="{{ __('messages.po_confirm_cancel') }}" class="contents">
                        @csrf
                        <button type="submit"
                                class="rounded-xl px-4 py-2.5 h-11 text-sm font-bold bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-rose-50 dark:hover:bg-rose-950/40 hover:text-rose-600 hover:border-rose-200 dark:hover:border-rose-900 transition">
                            ✕ {{ __('messages.po_btn_cancel') }}
                        </button>
                    </form>
                @endif

                @if ($po->canReceivePayment())
                    <button type="button" @click="openPay()"
                            class="rounded-xl px-4 py-2.5 h-11 text-sm font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition">
                        💳 {{ __('messages.payables_pay_now') }}
                    </button>
                @endif

                @if ($po->isReceived())
                    <button type="button" @click="openReturn()"
                            class="rounded-xl px-4 py-2.5 h-11 text-sm font-bold bg-orange-600 hover:bg-orange-500 text-white transition">
                        🔄 {{ __('messages.po_btn_return') }}
                    </button>
                @endif
            </div>
        </div>

        {{-- ===== Items ===== --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="px-4 sm:px-5 pt-4 sm:pt-5 pb-3 flex items-center justify-between gap-2">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('messages.reports_items') }}</p>
                <span class="text-[11px] font-bold text-slate-400">{{ $po->items->count() }} {{ __('messages.reports_items') }}</span>
            </div>

            {{-- Desktop table --}}
            <div class="hidden md:block overflow-x-auto scrollbar-thin">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs text-slate-500 dark:text-slate-400">
                        <tr>
                            <th class="text-left px-4 py-2 w-10">#</th>
                            <th class="text-left px-4 py-2">{{ __('messages.products') }}</th>
                            <th class="text-right px-4 py-2">{{ __('messages.reports_qty') }}</th>
                            <th class="text-right px-4 py-2">{{ __('messages.po_unit_cost') }}</th>
                            <th class="text-right px-4 py-2">{{ __('messages.reports_value') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($po->items as $i => $item)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                <td class="px-4 py-3 text-slate-400 font-bold">{{ $i + 1 }}</td>
                                <td class="px-4 py-3">
                                    <span class="font-bold">{{ $item->product->name ?? '—' }}</span>
                                    @if ($item->variant)
                                        <span class="text-xs text-slate-400 ml-1">/ {{ $item->variant->name }}</span>
                                    @endif
                                    @if ($item->product?->sku)
                                        <span class="text-[10px] font-mono text-slate-400 ml-1.5">{{ $item->product->sku }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-mono">{{ number_format((float) $item->quantity, 3) }}</td>
                                <td class="px-4 py-3 text-right font-mono">Ks {{ number_format((float) $item->unit_cost) }}</td>
                                <td class="px-4 py-3 text-right font-mono font-bold">Ks {{ number_format((float) $item->line_total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 dark:bg-slate-800/60 text-sm">
                        <tr>
                            <td colspan="2" class="px-4 py-3 font-bold text-right">{{ __('messages.receiving_total') }}</td>
                            <td class="px-4 py-3 text-right font-mono font-bold">{{ number_format((float) $po->total_quantity, 3) }}</td>
                            <td colspan="2" class="px-4 py-3 text-right font-mono font-black text-base">Ks {{ number_format((float) $po->total_cost) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Mobile cards --}}
            <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-800">
                @foreach ($po->items as $i => $item)
                    <div class="px-4 py-3">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="font-bold text-sm">{{ $item->product->name ?? '—' }}</p>
                                @if ($item->variant)
                                    <p class="text-xs text-slate-400">{{ $item->variant->name }}</p>
                                @endif
                                @if ($item->product?->sku)
                                    <p class="text-[10px] font-mono text-slate-400 mt-0.5">{{ $item->product->sku }}</p>
                                @endif
                            </div>
                            <p class="font-mono font-black text-sm whitespace-nowrap">Ks {{ number_format((float) $item->line_total) }}</p>
                        </div>
                        <div class="mt-1.5 flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                            <span class="font-mono font-bold">{{ number_format((float) $item->quantity, 3) }}</span>
                            <span>×</span>
                            <span class="font-mono">Ks {{ number_format((float) $item->unit_cost) }}</span>
                        </div>
                    </div>
                @endforeach
                <div class="px-4 py-3 bg-slate-50 dark:bg-slate-800/60 flex items-center justify-between">
                    <span class="text-sm font-bold">{{ __('messages.receiving_total') }}</span>
                    <div class="text-right">
                        <p class="text-[10px] text-slate-400 font-mono">{{ number_format((float) $po->total_quantity, 3) }} {{ __('messages.reports_units') }}</p>
                        <p class="font-mono font-black text-base">Ks {{ number_format((float) $po->total_cost) }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Stock posted note (if received) ===== --}}
        @if ($po->isReceived())
            <div class="rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 px-4 sm:px-5 py-4 flex items-start gap-3">
                <div class="shrink-0 w-9 h-9 rounded-xl bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400 grid place-items-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-emerald-700 dark:text-emerald-400">{{ __('messages.po_received_info') }}</p>
                    <p class="text-xs text-emerald-600 dark:text-emerald-500 mt-0.5">{{ __('messages.po_received_info_detail') }}</p>
                </div>
            </div>
        @endif

        {{-- ===== Payment summary (if received) ===== --}}
        @if ($po->isReceived())
            @php $paidPct = (float) $po->total_cost > 0 ? min(100, ((float) $po->paid_amount / (float) $po->total_cost) * 100) : 0; @endphp
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 sm:p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('messages.payables_payment_summary') }}</p>
                    @if ($po->canReceivePayment())
                        <button type="button" @click="openPay()"
                                class="rounded-xl px-4 py-2.5 h-11 text-sm font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition">
                            💳 {{ __('messages.payables_pay_now') }}
                        </button>
                    @endif
                </div>

                {{-- Progress bar --}}
                <div class="mt-3">
                    <div class="h-2.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                        <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-emerald-400 transition-all" style="width: {{ $paidPct }}%"></div>
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 mt-1 text-right">{{ round($paidPct) }}% {{ __('messages.payables_paid') }}</p>
                </div>

                <div class="grid grid-cols-3 gap-2 sm:gap-4 mt-2">
                    <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 px-3 py-2.5">
                        <p class="text-[10px] font-bold uppercase text-slate-400">{{ __('messages.po_total_cost') }}</p>
                        <p class="font-mono font-black mt-0.5 text-sm sm:text-base">Ks {{ number_format((float) $po->total_cost) }}</p>
                    </div>
                    <div class="rounded-xl bg-emerald-50 dark:bg-emerald-950/30 px-3 py-2.5">
                        <p class="text-[10px] font-bold uppercase text-emerald-500">{{ __('messages.payables_paid') }}</p>
                        <p class="font-mono font-black mt-0.5 text-sm sm:text-base text-emerald-600 dark:text-emerald-400">Ks {{ number_format((float) $po->paid_amount) }}</p>
                    </div>
                    <div class="rounded-xl {{ (float) $po->remaining_balance > 0 ? 'bg-rose-50 dark:bg-rose-950/30' : 'bg-slate-50 dark:bg-slate-800/60' }} px-3 py-2.5">
                        <p class="text-[10px] font-bold uppercase {{ (float) $po->remaining_balance > 0 ? 'text-rose-500' : 'text-slate-400' }}">{{ __('messages.payables_balance') }}</p>
                        <p class="font-mono font-black mt-0.5 text-sm sm:text-base {{ (float) $po->remaining_balance > 0 ? 'text-rose-600 dark:text-rose-400' : '' }}">Ks {{ number_format((float) $po->remaining_balance) }}</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- ============================================================= --}}
        {{-- ===== Payment modal (same Alpine scope — direct method calls) == --}}
        {{-- ============================================================= --}}
        {{-- NOTE: no @click.outside here — the app's synchronous x-show patch
             (app-admin.js) defeats Alpine's hidden-element guard, making the
             opening click instantly re-close the modal. Backdrop click instead. --}}
        <div x-show="payOpen" x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/50" @click="payOpen = false" aria-hidden="true"></div>
            <div class="relative min-h-full flex items-center justify-center p-4 py-6">
            <div class="w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl">
                {{-- Header --}}
                <div class="flex items-start justify-between gap-3 p-5 pb-4 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <h3 class="text-lg font-black">{{ __('messages.payables_pay_specific') }}</h3>
                        <p class="text-xs text-slate-400 font-mono mt-0.5">{{ $po->po_number }}</p>
                    </div>
                    <button type="button" @click="payOpen = false"
                            class="shrink-0 w-9 h-9 grid place-items-center rounded-xl text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form x-ref="payForm" method="POST" action="{{ url('/store/' . $store->slug . '/pos/purchases/' . $po->id . '/pay') }}"
                      @submit.prevent="submitPay()" class="p-5 space-y-4">
                    @csrf

                    {{-- Balance overview (live) --}}
                    <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 px-4 py-3 space-y-1.5">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-xs font-bold text-slate-500">{{ __('messages.payables_balance') }}</span>
                            <span class="font-mono font-black text-rose-600 dark:text-rose-400" x-text="'Ks ' + fmt2(payBalance)"></span>
                        </div>
                        <div class="flex items-center justify-between text-sm border-t border-slate-200 dark:border-slate-700 pt-1.5" x-show="payAmt > 0 && !payOver" x-cloak>
                            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">+ {{ __('messages.payables_paid') }}</span>
                            <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400" x-text="'Ks ' + fmt2(payAmt)"></span>
                        </div>
                        <div class="flex items-center justify-between text-sm border-t border-slate-200 dark:border-slate-700 pt-1.5" x-show="payAmt > 0 && !payOver" x-cloak>
                            <span class="text-xs font-bold text-slate-500">{{ __('messages.payables_balance') }}</span>
                            <span class="font-mono font-black"
                                  :class="payAfter === 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'"
                                  x-text="payAfter === 0 ? '✓ {{ __('messages.po_payment_paid') }}' : 'Ks ' + fmt2(payAfter)"></span>
                        </div>
                    </div>

                    {{-- Amount --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1.5">{{ __('messages.payables_amount') }} (Ks)</label>
                        <input x-ref="payAmount" type="number" name="amount" step="0.01" min="0.01" :max="payBalance" x-model="payAmount" inputmode="decimal"
                               :class="payOver ? 'border-rose-400 focus:ring-rose-500' : 'focus:ring-emerald-500'"
                               class="w-full h-12 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 text-base font-bold focus:ring-2 focus:border-transparent outline-none transition">

                        {{-- Quick amount chips --}}
                        <div class="flex items-center gap-2 mt-2">
                            <button type="button" @click="setHalf()"
                                    class="flex-1 rounded-lg px-3 py-2.5 text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                                ½ Ks <span x-text="Number(Math.round(payBalance / 2 * 100) / 100).toLocaleString()"></span>
                            </button>
                            <button type="button" @click="setFull()"
                                    class="flex-1 rounded-lg px-3 py-2.5 text-xs font-black bg-emerald-600 text-white hover:bg-emerald-500 transition">
                                ⚡ 100% (Ks <span x-text="Number(payBalance).toLocaleString()"></span>)
                            </button>
                        </div>

                        {{-- Inline validation --}}
                        <p x-show="payOver" x-cloak x-transition.opacity class="mt-2 text-xs font-bold text-rose-600 dark:text-rose-400">
                            ⚠ {{ __('messages.payables_amount') }} &gt; {{ __('messages.payables_balance') }} (Ks <span x-text="fmt2(payBalance)"></span>)
                        </p>
                        <p x-show="payAmount !== '' && payAmt <= 0" x-cloak class="mt-2 text-xs font-bold text-rose-500">⚠ &gt; 0</p>
                    </div>

                    {{-- Reference --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1.5">{{ __('messages.receiving_reference') }}</label>
                        <input type="text" name="reference" maxlength="100"
                               class="w-full h-11 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 text-base sm:text-sm focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none transition">
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2 pt-1">
                        <button type="button" @click="payOpen = false"
                                class="flex-1 sm:flex-none rounded-xl px-4 h-12 text-sm font-bold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                            {{ __('messages.cancel') }}
                        </button>
                        <button type="submit" :disabled="!payValid || paySubmitting"
                                :class="payValid && !paySubmitting ? 'bg-emerald-600 hover:bg-emerald-500' : 'bg-slate-300 dark:bg-slate-700 cursor-not-allowed'"
                                class="flex-1 rounded-xl px-6 h-12 text-sm font-black text-white transition inline-flex items-center justify-center gap-2">
                            <svg x-show="paySubmitting" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                            <span x-text="paySubmitting ? '…' : '💳 {{ __('messages.payables_pay') }}'"></span>
                        </button>
                    </div>
                </form>
            </div>
            </div>
        </div>

        {{-- ============================================================= --}}
        {{-- ===== Return modal (same Alpine scope — direct method calls) === --}}
        {{-- ============================================================= --}}
        <div x-show="returnOpen" x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/50" @click="returnOpen = false" aria-hidden="true"></div>
            <div class="relative min-h-full flex items-center justify-center p-4 py-6">
            <div class="w-full max-w-lg rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl max-h-[88vh] flex flex-col">
                {{-- Header --}}
                <div class="flex items-start justify-between gap-3 p-5 pb-4 border-b border-slate-100 dark:border-slate-800 shrink-0">
                    <div>
                        <h3 class="text-lg font-black">{{ __('messages.po_return_title') }}</h3>
                        <p class="text-xs text-slate-400 font-mono mt-0.5">{{ $po->po_number }}</p>
                    </div>
                    <button type="button" @click="returnOpen = false"
                            class="shrink-0 w-9 h-9 grid place-items-center rounded-xl text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form x-ref="returnForm" method="POST" action="{{ url('/store/' . $store->slug . '/pos/purchases/' . $po->id . '/return') }}"
                      @submit.prevent="submitReturn()" class="flex flex-col min-h-0">
                    @csrf

                    {{-- Scrollable items --}}
                    <div class="overflow-y-auto overscroll-contain px-5 py-4 space-y-2">
                        {{-- Bulk actions --}}
                        <div class="flex items-center justify-between gap-2 pb-1">
                            <span class="text-xs font-bold text-slate-400" x-show="retSelected === 0">{{ __('messages.po_return_qty') }}</span>
                            <span class="text-xs font-bold text-orange-600 dark:text-orange-400" x-show="retSelected > 0" x-cloak x-text="retSelected + ' {{ __('messages.reports_items') }}'"></span>
                            <div class="flex items-center gap-1.5">
                                <button type="button" @click="retMaxAll()"
                                        class="rounded-lg px-2.5 py-1.5 text-[11px] font-black bg-orange-100 dark:bg-orange-950/60 text-orange-700 dark:text-orange-400 hover:bg-orange-200 dark:hover:bg-orange-900/60 transition">
                                    MAX ALL
                                </button>
                                <button type="button" @click="retZeroAll()"
                                        class="rounded-lg px-2.5 py-1.5 text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                                    ✕ 0
                                </button>
                            </div>
                        </div>

                        <template x-for="(item, idx) in returnItems" :key="idx">
                            <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 p-3 transition"
                                 :class="(parseFloat(item.returnQty) || 0) > 0 ? 'ring-2 ring-orange-400/60' : ''">
                                <div class="flex items-start gap-3">
                                    <span class="shrink-0 w-6 h-6 rounded-lg bg-slate-200 dark:bg-slate-700 text-slate-500 grid place-items-center text-[11px] font-black mt-0.5" x-text="idx + 1"></span>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-bold text-sm truncate" x-text="item.name"></p>
                                        <p class="text-xs text-slate-400 mt-0.5">
                                            {{ __('messages.reports_qty') }}: <span class="font-bold" x-text="item.quantity"></span>
                                            · <span x-text="'Ks ' + Number(item.unit_cost).toLocaleString()"></span>
                                        </p>
                                    </div>
                                    <span class="text-[11px] font-mono font-black text-orange-600 dark:text-orange-400 shrink-0 mt-0.5"
                                          x-show="(parseFloat(item.returnQty) || 0) > 0" x-cloak
                                          x-text="'Ks ' + fmt((parseFloat(item.returnQty) || 0) * (parseFloat(item.unit_cost) || 0))"></span>
                                </div>
                                <div class="flex items-center gap-2 mt-2.5 pl-9">
                                    <div class="flex items-center rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 overflow-hidden h-10 focus-within:ring-2 focus-within:ring-orange-500 transition">
                                        <button type="button" @click="retZero(item)" tabindex="-1"
                                                class="w-9 shrink-0 grid place-items-center text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition text-xs font-black">0</button>
                                        <input type="number" :name="`items[${idx}][quantity]`" step="0.001" min="0" :max="item.quantity" x-model="item.returnQty" inputmode="decimal"
                                               class="w-20 text-center text-sm font-bold bg-transparent outline-none"/>
                                        <button type="button" @click="retMax(item)" tabindex="-1"
                                                class="w-9 shrink-0 grid place-items-center text-orange-600 dark:text-orange-400 text-[10px] font-black hover:bg-orange-50 dark:hover:bg-orange-950/40 transition">MAX</button>
                                    </div>
                                    <input type="hidden" :name="`items[${idx}][product_id]`" :value="item.product_id">
                                    <input type="hidden" :name="`items[${idx}][product_variant_id]`" :value="item.product_variant_id || ''">
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Footer (fixed) --}}
                    <div class="shrink-0 border-t border-slate-100 dark:border-slate-800 px-5 py-4 space-y-3 bg-white dark:bg-slate-900 rounded-b-2xl">
                        {{-- Return total --}}
                        <div class="flex items-center justify-between rounded-xl bg-orange-50 dark:bg-orange-950/30 border border-orange-200 dark:border-orange-900/50 px-3.5 py-2.5" x-show="retSelected > 0" x-cloak x-transition.opacity>
                            <span class="text-xs font-bold text-orange-700 dark:text-orange-400">{{ __('messages.receiving_total') }}: <span x-text="retSelected"></span> {{ __('messages.reports_items') }}</span>
                            <span class="font-mono font-black text-orange-700 dark:text-orange-400" x-text="'Ks ' + fmt(retTotal)"></span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1.5">{{ __('messages.po_return_reason') }}</label>
                            <input type="text" name="reason" maxlength="500"
                                   class="w-full h-11 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 text-base sm:text-sm focus:ring-2 focus:ring-orange-500 focus:border-transparent outline-none transition"
                                   placeholder="{{ __('messages.po_return_reason_placeholder') }}">
                        </div>

                        <p class="text-[11px] text-slate-400 flex items-start gap-1.5">
                            <svg class="w-3.5 h-3.5 shrink-0 mt-px" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
                            {{ __('messages.po_received_info_detail') }}
                        </p>

                        <div class="flex items-center gap-2">
                            <button type="button" @click="returnOpen = false"
                                    class="flex-1 sm:flex-none rounded-xl px-4 h-12 text-sm font-bold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                                {{ __('messages.cancel') }}
                            </button>
                            <button type="submit" :disabled="!retCanSubmit"
                                    :class="retCanSubmit ? 'bg-orange-600 hover:bg-orange-500' : 'bg-slate-300 dark:bg-slate-700 cursor-not-allowed'"
                                    class="flex-1 rounded-xl px-6 h-12 text-sm font-black text-white transition inline-flex items-center justify-center gap-2">
                                <svg x-show="returnSubmitting" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                                <span x-text="returnSubmitting ? '…' : '🔄 {{ __('messages.po_return_confirm') }}'"></span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            </div>
        </div>
    </div>
@endsection
