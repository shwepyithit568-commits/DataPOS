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
            ];
        @endphp
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="inline-block rounded-lg px-2.5 py-0.5 text-xs font-bold {{ $statusColors[$po->status] ?? '' }}">
                            {{ __('messages.po_status_' . $po->status) }}
                        </span>
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
    </div>
@endsection
