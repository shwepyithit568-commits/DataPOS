@extends('layouts.pos.app')

@section('content')
    <div class="mx-auto max-w-4xl px-4 py-6 space-y-6">

        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('messages.sidebar_purchases') }}</p>
                <h1 class="text-xl font-black mt-0.5">{{ $po->po_number }}</h1>
            </div>
            <a href="{{ url('/store/' . $store->slug . '/pos/purchases') }}"
               class="rounded-xl px-4 py-2 text-sm font-bold bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 transition">
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

        {{-- PO info card --}}
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
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="inline-block rounded-lg px-2.5 py-0.5 text-xs font-bold {{ $statusColors[$po->status] ?? '' }}">
                            {{ __('messages.po_status_' . $po->status) }}
                        </span>
                        @if ($po->isReceived())
                            <span class="inline-block rounded-lg px-2.5 py-0.5 text-xs font-bold {{ $paymentStatusColors[$po->payment_status] ?? '' }}">
                                {{ __('messages.po_payment_' . $po->payment_status) }}
                            </span>
                        @endif
                        @if ($po->supplier)
                            <span class="text-sm text-slate-500 dark:text-slate-400">· {{ $po->supplier->name }}</span>
                        @endif
                    </div>
                    @if ($po->reference)
                        <p class="text-xs text-slate-500">{{ __('messages.receiving_reference') }}: <span class="font-bold">{{ $po->reference }}</span></p>
                    @endif
                    @if ($po->notes)
                        <p class="text-xs text-slate-500">{{ __('messages.notes') }}: <span class="font-bold">{{ $po->notes }}</span></p>
                    @endif
                    <p class="text-xs text-slate-400">
                        {{ __('messages.po_created') }}: {{ $po->created_at->format('d M Y, H:i') }}
                        @if ($po->createdBy) · {{ $po->createdBy->name }} @endif
                    </p>
                    @if ($po->ordered_at)
                        <p class="text-xs text-sky-500">{{ __('messages.po_ordered_at') }}: {{ $po->ordered_at->format('d M Y, H:i') }}</p>
                    @endif
                    @if ($po->received_at)
                        <p class="text-xs text-emerald-500">{{ __('messages.po_received_at') }}: {{ $po->received_at->format('d M Y, H:i') }}</p>
                    @endif
                    @if ($po->cancelled_at)
                        <p class="text-xs text-slate-400">{{ __('messages.po_cancelled_at') }}: {{ $po->cancelled_at->format('d M Y, H:i') }}</p>
                    @endif
                    @if ($po->isReceived() && !$po->isPaid())
                        <div class="flex items-center gap-2 mt-2 text-sm">
                            <span class="text-slate-500">{{ __('messages.payables_balance') }}:</span>
                            <span class="font-bold text-rose-600 dark:text-rose-400 font-mono">Ks {{ number_format((float) $po->remaining_balance) }}</span>
                        </div>
                    @endif
                </div>

                {{-- Action buttons --}}
                <div class="flex items-center gap-2 flex-wrap">
                    @if ($po->isPending())
                        <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/purchases/' . $po->id . '/order') }}" x-data
                              x-confirm="{{ __('messages.po_confirm_order') }}">
                            @csrf
                            <button type="submit"
                                    class="rounded-xl px-4 py-2 text-sm font-bold bg-sky-600 hover:bg-sky-500 text-white transition">
                                ✅ {{ __('messages.po_btn_order') }}
                            </button>
                        </form>
                        <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/purchases/' . $po->id . '/cancel') }}" x-data
                              x-confirm="{{ __('messages.po_confirm_cancel') }}">
                            @csrf
                            <button type="submit"
                                    class="rounded-xl px-4 py-2 text-sm font-bold bg-slate-200 dark:bg-slate-700 hover:bg-rose-100 dark:hover:bg-rose-900/40 hover:text-rose-600 dark:hover:text-rose-400 transition">
                                ✕ {{ __('messages.po_btn_cancel') }}
                            </button>
                        </form>
                    @endif

                    @if ($po->isOrdered())
                        <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/purchases/' . $po->id . '/receive') }}" x-data
                              x-confirm="{{ __('messages.po_confirm_receive') }}">
                            @csrf
                            <button type="submit"
                                    class="rounded-xl px-4 py-2 text-sm font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition">
                                📦 {{ __('messages.po_btn_receive') }}
                            </button>
                        </form>
                        <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/purchases/' . $po->id . '/cancel') }}" x-data
                              x-confirm="{{ __('messages.po_confirm_cancel') }}">
                            @csrf
                            <button type="submit"
                                    class="rounded-xl px-4 py-2 text-sm font-bold bg-slate-200 dark:bg-slate-700 hover:bg-rose-100 dark:hover:bg-rose-900/40 hover:text-rose-600 dark:hover:text-rose-400 transition">
                                ✕ {{ __('messages.po_btn_cancel') }}
                            </button>
                        </form>
                    @endif

                    {{-- Pay button for received but unpaid POs --}}
                    @if ($po->isReceived() && !$po->isPaid())
                        <button type="button" x-data
                                @click="$dispatch('open-pay-modal', { id: {{ $po->id }}, number: '{{ addslashes($po->po_number) }}', balance: {{ $po->remaining_balance }} })"
                                class="rounded-xl px-4 py-2 text-sm font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition">
                            💳 {{ __('messages.payables_pay_now') }}
                        </button>
                    @endif

                    {{-- Return button for received POs --}}
                    @if ($po->isReceived())
                        <button type="button" x-data
                                @click="$dispatch('open-return-modal', { id: {{ $po->id }}, number: '{{ addslashes($po->po_number) }}', items: {{ $po->items->toJson() }} })"
                                class="rounded-xl px-4 py-2 text-sm font-bold bg-orange-600 hover:bg-orange-500 text-white transition">
                            🔄 {{ __('messages.po_btn_return') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- PO items --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400 mb-3">{{ __('messages.reports_items') }}</p>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs text-slate-500 dark:text-slate-400">
                        <tr>
                            <th class="text-left px-3 py-2">#</th>
                            <th class="text-left px-3 py-2">{{ __('messages.receiving_product') }}</th>
                            <th class="text-right px-3 py-2">{{ __('messages.reports_qty') }}</th>
                            <th class="text-right px-3 py-2">{{ __('messages.po_unit_cost') }}</th>
                            <th class="text-right px-3 py-2">{{ __('messages.reports_value') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($po->items as $i => $item)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                <td class="px-3 py-2.5 text-slate-400">{{ $i + 1 }}</td>
                                <td class="px-3 py-2.5">
                                    <span class="font-bold">{{ $item->product->name ?? '—' }}</span>
                                    @if ($item->variant)
                                        <span class="text-xs text-slate-400 ml-1">/ {{ $item->variant->name }}</span>
                                    @endif
                                    @if ($item->product?->sku)
                                        <span class="text-[10px] font-mono text-slate-400 ml-1">{{ $item->product->sku }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5 text-right font-mono">{{ number_format((float) $item->quantity, 3) }}</td>
                                <td class="px-3 py-2.5 text-right font-mono">Ks {{ number_format((float) $item->unit_cost) }}</td>
                                <td class="px-3 py-2.5 text-right font-mono font-bold">Ks {{ number_format((float) $item->line_total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 dark:bg-slate-800/60 text-sm">
                        <tr>
                            <td colspan="2" class="px-3 py-2.5 font-bold text-right">{{ __('messages.receiving_total') }}</td>
                            <td class="px-3 py-2.5 text-right font-mono font-bold">{{ number_format((float) $po->total_quantity, 3) }}</td>
                            <td colspan="2" class="px-3 py-2.5 text-right font-mono font-black">Ks {{ number_format((float) $po->total_cost) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Receipt link (if received) --}}
        @if ($po->isReceived())
            <div class="rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 p-5 shadow-sm">
                <p class="text-sm font-bold text-emerald-700 dark:text-emerald-400">
                    📦 {{ __('messages.po_received_info') }}
                </p>
                <p class="text-xs text-emerald-600 dark:text-emerald-500 mt-1">
                    {{ __('messages.po_received_info_detail') }}
                </p>
            </div>
        @endif

        {{-- Payment summary (if received) --}}
        @if ($po->isReceived())
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <div class="space-y-1">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('messages.payables_payment_summary') }}</p>
                        <div class="grid grid-cols-3 gap-4 mt-2">
                            <div>
                                <p class="text-xs text-slate-500">{{ __('messages.po_total_cost') }}</p>
                                <p class="font-mono font-bold">Ks {{ number_format((float) $po->total_cost) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">{{ __('messages.payables_paid') }}</p>
                                <p class="font-mono font-bold text-emerald-600 dark:text-emerald-400">Ks {{ number_format((float) $po->paid_amount) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">{{ __('messages.payables_balance') }}</p>
                                <p class="font-mono font-bold text-rose-600 dark:text-rose-400">Ks {{ number_format((float) $po->remaining_balance) }}</p>
                            </div>
                        </div>
                    </div>
                    @if ($po->canReceivePayment())
                        <button type="button" x-data
                                @click="$dispatch('open-pay-modal', { id: {{ $po->id }}, number: '{{ addslashes($po->po_number) }}', balance: {{ $po->remaining_balance }} })"
                                class="rounded-xl px-4 py-2 text-sm font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition">
                            💳 {{ __('messages.payables_pay_now') }}
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- Payment modal --}}
    <div x-data="{ open: false, id: null, number: '', balance: 0 }"
         @open-pay-modal.window="open = true; id = $event.detail.id; number = $event.detail.number; balance = $event.detail.balance"
         x-show="open"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
         x-cloak>
        <div class="w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-xl"
             @click.outside="open = false">
            <h3 class="text-lg font-black mb-4">{{ __('messages.payables_pay_specific') }}</h3>
            <p class="text-sm text-slate-500 mb-4 font-mono">{{ __('messages.po_number') }}: <span x-text="number"></span></p>
            <form :action="`/store/{{ $store->slug }}/pos/purchases/${id}/pay`" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">{{ __('messages.payables_amount') }}</label>
                    <input type="number" name="amount" step="0.01" min="0.01" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">{{ __('messages.receiving_reference') }}</label>
                    <input type="text" name="reference" maxlength="100"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 outline-none"
                           placeholder="Optional">
                </div>
                <div class="flex items-center gap-2 justify-end pt-2">
                    <button type="button" @click="open = false"
                            class="rounded-xl px-4 py-2 text-sm font-bold bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 transition">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit"
                            class="rounded-xl px-6 py-2 text-sm font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition">
                        💳 {{ __('messages.payables_pay') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Return modal --}}
    <div x-data="{ open: false, id: null, number: '', items: [] }"
         @open-return-modal.window="open = true; id = $event.detail.id; number = $event.detail.number; items = $event.detail.items"
         x-show="open"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
         x-cloak>
        <div class="w-full max-w-lg rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-xl max-h-[80vh] overflow-y-auto"
             @click.outside="open = false">
            <h3 class="text-lg font-black mb-1">{{ __('messages.po_return_title') }}</h3>
            <p class="text-sm text-slate-500 mb-4 font-mono">{{ __('messages.po_number') }}: <span x-text="number"></span></p>
            <form :action="`/store/{{ $store->slug }}/pos/purchases/${id}/return`" method="POST" class="space-y-3">
                @csrf
                <template x-for="(item, idx) in items" :key="idx">
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-sm truncate" x-text="item.product?.name || 'Product #' + item.product_id"></p>
                            <p class="text-xs text-slate-400">{{ __('messages.reports_qty') }}: <span x-text="item.quantity"></span> · Ks <span x-text="Number(item.unit_cost).toLocaleString()"></span></p>
                        </div>
                        <div class="w-24">
                            <label class="block text-[10px] font-bold text-slate-400 mb-0.5">{{ __('messages.po_return_qty') }}</label>
                            <input type="number" :name="`items[${idx}][quantity]`" step="0.001" min="0" :max="item.quantity" value="0"
                                   class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-2 py-1.5 text-sm text-right focus:ring-2 focus:ring-orange-500 outline-none">
                            <input type="hidden" :name="`items[${idx}][product_id]`" :value="item.product_id">
                            <input type="hidden" :name="`items[${idx}][product_variant_id]`" :value="item.product_variant_id || ''">
                        </div>
                    </div>
                </template>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">{{ __('messages.po_return_reason') }}</label>
                    <input type="text" name="reason" maxlength="500"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500 outline-none"
                           placeholder="{{ __('messages.po_return_reason_placeholder') }}">
                </div>
                <div class="flex items-center gap-2 justify-end pt-2">
                    <button type="button" @click="open = false"
                            class="rounded-xl px-4 py-2 text-sm font-bold bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 transition">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit"
                            class="rounded-xl px-6 py-2 text-sm font-bold bg-orange-600 hover:bg-orange-500 text-white transition">
                        🔄 {{ __('messages.po_return_confirm') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
