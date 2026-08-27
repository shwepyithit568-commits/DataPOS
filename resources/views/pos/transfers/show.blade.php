@extends('layouts.admin.app')

@section('title', __('messages.transfers_title') . ' #' . $transfer->transfer_number . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@section('content')
<div class="w-full space-y-2 sm:space-y-2.5">

    {{-- ============================================================
         1. COMPACT HERO PAGE HEADER (Admin UI Standard)
         ============================================================ --}}
    <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 transition">
        <div class="min-w-0">
            <div class="flex items-center gap-1.5 mb-0.5">
                <a href="{{ route('pos.transfers.index', $storeRouteParams) }}"
                   class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                    <span>{{ __('messages.back') }}</span>
                </a>
                <span class="text-slate-300 dark:text-slate-700">/</span>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-violet-50 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 border border-violet-200 dark:border-violet-800">
                    <span>🔄</span>
                    <span>{{ __('messages.sidebar_transfers') }}</span>
                </span>
                <span class="text-slate-300 dark:text-slate-700">/</span>
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 truncate">{{ $store->name }}</span>
            </div>
            <div class="flex flex-wrap items-center gap-2 mt-1">
                <h1 class="text-sm sm:text-base font-mono font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    <span>{{ $transfer->transfer_number }}</span>
                </h1>

                {{-- Status Badge --}}
                @if($transfer->status === 'completed')
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                        <span>✓</span>
                        <span>{{ __('messages.transfer_status_completed') }}</span>
                    </span>
                @elseif($transfer->status === 'in_transit')
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                        <span class="w-1.5 h-1.5 rounded-full bg-sky-500 animate-pulse"></span>
                        <span>🚚 {{ __('messages.transfer_status_in_transit') }}</span>
                    </span>
                @elseif($transfer->status === 'pending')
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                        <span>{{ __('messages.transfer_status_pending') }}</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-full bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                        <span>✕</span>
                        <span>{{ __('messages.transfer_status_cancelled') }}</span>
                    </span>
                @endif
            </div>
        </div>

        {{-- Workflow Actions --}}
        <div class="flex flex-wrap items-center gap-1.5 shrink-0">
            @if($transfer->status === 'pending')
                {{-- Ship Action Button --}}
                <form method="POST" action="{{ route('pos.transfers.ship', [...$storeRouteParams, 'transfer' => $transfer->id]) }}" class="inline"
                      onsubmit="return confirm('{{ addslashes(__('messages.transfer_ship_confirm')) }}')">
                    @csrf
                    <button type="submit"
                            class="px-3.5 py-1.5 rounded-lg text-xs font-black bg-amber-500 hover:bg-amber-600 text-white shadow-md transition flex items-center gap-1.5 active:scale-95 cursor-pointer">
                        <span>🚚</span>
                        <span>{{ __('messages.ship') }}</span>
                    </button>
                </form>

                {{-- Cancel Action Button --}}
                <form method="POST" action="{{ route('pos.transfers.cancel', [...$storeRouteParams, 'transfer' => $transfer->id]) }}" class="inline"
                      onsubmit="return confirm('{{ addslashes(__('messages.transfer_cancel_confirm')) }}')">
                    @csrf
                    <button type="submit"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 hover:bg-rose-50 dark:bg-slate-800 dark:hover:bg-rose-950/40 text-slate-700 hover:text-rose-600 dark:text-slate-300 dark:hover:text-rose-400 border border-slate-200 dark:border-slate-700 transition flex items-center gap-1 active:scale-95 cursor-pointer">
                        <span>✕</span>
                        <span>{{ __('messages.cancel') }}</span>
                    </button>
                </form>
            @elseif($transfer->status === 'in_transit')
                {{-- Receive Action Button --}}
                <form method="POST" action="{{ route('pos.transfers.receive', [...$storeRouteParams, 'transfer' => $transfer->id]) }}" class="inline"
                      onsubmit="return confirm('{{ addslashes(__('messages.transfer_receive_confirm')) }}')">
                    @csrf
                    <button type="submit"
                            class="px-3.5 py-1.5 rounded-lg text-xs font-black bg-emerald-600 hover:bg-emerald-700 text-white shadow-md transition flex items-center gap-1.5 active:scale-95 cursor-pointer">
                        <span>✅</span>
                        <span>{{ __('messages.receive') }}</span>
                    </button>
                </form>

                {{-- Cancel Action Button --}}
                <form method="POST" action="{{ route('pos.transfers.cancel', [...$storeRouteParams, 'transfer' => $transfer->id]) }}" class="inline"
                      onsubmit="return confirm('{{ addslashes(__('messages.transfer_cancel_confirm')) }}')">
                    @csrf
                    <button type="submit"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 hover:bg-rose-50 dark:bg-slate-800 dark:hover:bg-rose-950/40 text-slate-700 hover:text-rose-600 dark:text-slate-300 dark:hover:text-rose-400 border border-slate-200 dark:border-slate-700 transition flex items-center gap-1 active:scale-95 cursor-pointer">
                        <span>✕</span>
                        <span>{{ __('messages.cancel') }}</span>
                    </button>
                </form>
            @endif

            {{-- New Transfer Link --}}
            <a href="{{ route('pos.transfers.create', $storeRouteParams) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition flex items-center gap-1.5 shadow-2xs">
                <span>+</span>
                <span>{{ __('messages.new_transfer') }}</span>
            </a>
        </div>
    </div>

    {{-- Feedback Messages --}}
    @if (session('success'))
        <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs font-bold text-emerald-700 dark:text-emerald-300 flex items-center gap-2">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-lg text-xs font-bold text-rose-700 dark:text-rose-300 space-y-1">
            @foreach ($errors->all() as $error)
                <p class="flex items-center gap-1.5">
                    <span>⚠️</span>
                    <span>{{ $error }}</span>
                </p>
            @endforeach
        </div>
    @endif

    {{-- ============================================================
         2. ROUTE & DETAILS OVERVIEW CARDS (4-UP GRID)
         ============================================================ --}}
    @php
        $totalQty = $transfer->items->sum('quantity');
        $totalValue = $transfer->items->sum(fn($i) => $i->quantity * $i->unit_cost);
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-2.5">
        {{-- From Warehouse Card --}}
        <div class="p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-1">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 flex items-center gap-1">
                <span>📤</span>
                <span>{{ __('messages.from_warehouse') }}</span>
            </span>
            <p class="text-sm font-black text-slate-900 dark:text-slate-100">
                {{ $transfer->fromWarehouse->name ?? '—' }}
            </p>
            @if($transfer->fromWarehouse?->code)
                <p class="text-[10px] font-mono text-slate-400">Code: {{ $transfer->fromWarehouse->code }}</p>
            @endif
        </div>

        {{-- To Warehouse Card --}}
        <div class="p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-1">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 flex items-center gap-1">
                <span>📥</span>
                <span>{{ __('messages.to_warehouse') }}</span>
            </span>
            <p class="text-sm font-black text-slate-900 dark:text-slate-100">
                {{ $transfer->toWarehouse->name ?? '—' }}
            </p>
            @if($transfer->toWarehouse?->code)
                <p class="text-[10px] font-mono text-slate-400">Code: {{ $transfer->toWarehouse->code }}</p>
            @endif
        </div>

        {{-- Total Items & Quantity --}}
        <div class="p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-1">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 flex items-center gap-1">
                <span>📦</span>
                <span>{{ __('messages.items') }} & {{ __('messages.transfer_total_qty') }}</span>
            </span>
            <p class="text-sm font-black text-slate-900 dark:text-slate-100 font-mono">
                {{ $transfer->items->count() }} items • {{ number_format($totalQty, $totalQty == round($totalQty) ? 0 : 2) }} pcs
            </p>
            <p class="text-[10px] font-mono text-violet-600 dark:text-violet-400 font-bold">
                Est. Value: {{ number_format($totalValue) }} MMK
            </p>
        </div>

        {{-- Created Date & User --}}
        <div class="p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-1">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 flex items-center gap-1">
                <span>👤</span>
                <span>{{ __('messages.created') }}</span>
            </span>
            <p class="text-sm font-bold text-slate-900 dark:text-slate-100 truncate">
                {{ $transfer->creator?->name ?? 'System' }}
            </p>
            <p class="text-[10px] font-mono text-slate-400">
                {{ $transfer->created_at->format('d/m/Y H:i') }}
            </p>
        </div>
    </div>

    @if($transfer->notes)
        <div class="p-3 bg-amber-50/60 dark:bg-amber-950/20 border border-amber-200/80 dark:border-amber-800/60 rounded-lg text-xs text-amber-900 dark:text-amber-200">
            <span class="font-black mr-1.5">📝 {{ __('messages.notes') }}:</span>
            <span>{{ $transfer->notes }}</span>
        </div>
    @endif

    {{-- ============================================================
         3. SPREADSHEET DATA GRID: TRANSFER ITEMS
         ============================================================ --}}
    <div class="rounded-lg border border-slate-200/90 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xs overflow-hidden">
        <div class="px-3 py-2.5 bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-800 dark:text-slate-100 flex items-center gap-1.5">
                <span>📋</span>
                <span>{{ __('messages.items') }} ({{ $transfer->items->count() }})</span>
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                <thead class="bg-slate-100 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 select-none">
                    <tr class="text-[11px] font-black uppercase tracking-wider divide-x divide-slate-200 dark:divide-slate-700 text-slate-800 dark:text-slate-100">
                        <th class="py-2 px-3 w-12 text-center">#</th>
                        <th class="py-2 px-3 min-w-[200px]">{{ __('messages.product') }}</th>
                        <th class="py-2 px-3 text-right min-w-[110px]">{{ __('messages.unit_cost') }}</th>
                        <th class="py-2 px-3 text-right min-w-[110px]">{{ __('messages.quantity') }}</th>
                        <th class="py-2 px-3 text-right min-w-[130px]">{{ __('messages.subtotal') ?? 'Subtotal' }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900">
                    @foreach($transfer->items as $item)
                        @php
                            $subtotal = $item->quantity * $item->unit_cost;
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 divide-x divide-slate-200/80 dark:divide-slate-800 transition">
                            <td class="py-2.5 px-3 text-center font-mono text-slate-400 text-xs">
                                {{ $loop->iteration }}
                            </td>
                            <td class="py-2.5 px-3">
                                <div class="font-bold text-slate-900 dark:text-slate-100">
                                    {{ $item->product->name ?? '—' }}
                                </div>
                                @if($item->product?->sku || $item->product?->barcode)
                                    <div class="text-[10px] font-mono text-slate-400 mt-0.5">
                                        {{ $item->product->sku ?: $item->product->barcode }}
                                    </div>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono text-slate-600 dark:text-slate-400">
                                {{ number_format($item->unit_cost) }} MMK
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono font-black text-slate-900 dark:text-slate-100">
                                {{ number_format($item->quantity, $item->quantity == round($item->quantity) ? 0 : 2) }}
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono font-bold text-violet-600 dark:text-violet-400">
                                {{ number_format($subtotal) }} MMK
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-50 dark:bg-slate-800/60 border-t-2 border-slate-200 dark:border-slate-700 font-bold">
                    <tr class="divide-x divide-slate-200 dark:divide-slate-700 text-slate-900 dark:text-slate-100">
                        <td colspan="3" class="py-2 px-3 text-right uppercase text-[11px] font-black">
                            {{ __('messages.total') ?? 'Total' }}:
                        </td>
                        <td class="py-2 px-3 text-right font-mono font-black text-xs text-slate-900 dark:text-slate-100">
                            {{ number_format($totalQty, $totalQty == round($totalQty) ? 0 : 2) }} pcs
                        </td>
                        <td class="py-2 px-3 text-right font-mono font-black text-xs text-violet-600 dark:text-violet-400">
                            {{ number_format($totalValue) }} MMK
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- ============================================================
         4. WORKFLOW TIMELINE STEPPER
         ============================================================ --}}
    <div class="p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-2.5">
        <h3 class="text-xs font-black uppercase tracking-wider text-slate-800 dark:text-slate-100 flex items-center gap-1.5">
            <span>⏱️</span>
            <span>{{ __('messages.timeline') }}</span>
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-1">
            {{-- Step 1: Created --}}
            <div class="p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-800/50 flex items-start gap-2.5">
                <span class="w-6 h-6 rounded-full bg-violet-100 dark:bg-violet-950/80 text-violet-600 dark:text-violet-300 font-black text-xs grid place-items-center shrink-0">1</span>
                <div class="min-w-0">
                    <p class="text-xs font-bold text-slate-900 dark:text-slate-100">{{ __('messages.created') }}</p>
                    <p class="text-[10px] font-mono text-slate-400 mt-0.5">{{ $transfer->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>

            {{-- Step 2: Shipped --}}
            <div class="p-2.5 rounded-lg border {{ $transfer->shipped_at ? 'border-amber-200 dark:border-amber-800/80 bg-amber-50/50 dark:bg-amber-950/20' : 'border-slate-200 dark:border-slate-700 bg-slate-50/40 dark:bg-slate-800/30 opacity-60' }} flex items-start gap-2.5">
                <span class="w-6 h-6 rounded-full {{ $transfer->shipped_at ? 'bg-amber-500 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-500' }} font-black text-xs grid place-items-center shrink-0">2</span>
                <div class="min-w-0">
                    <p class="text-xs font-bold text-slate-900 dark:text-slate-100">{{ __('messages.shipped') }}</p>
                    @if($transfer->shipped_at)
                        <p class="text-[10px] font-mono text-amber-600 dark:text-amber-400 mt-0.5">{{ $transfer->shipped_at->format('d/m/Y H:i') }}</p>
                    @else
                        <p class="text-[10px] text-slate-400 mt-0.5">Pending shipment</p>
                    @endif
                </div>
            </div>

            {{-- Step 3: Received / Cancelled --}}
            @if($transfer->status === 'cancelled')
                <div class="p-2.5 rounded-lg border border-rose-200 dark:border-rose-800/80 bg-rose-50/50 dark:bg-rose-950/20 flex items-start gap-2.5">
                    <span class="w-6 h-6 rounded-full bg-rose-500 text-white font-black text-xs grid place-items-center shrink-0">✕</span>
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-rose-700 dark:text-rose-300">{{ __('messages.transfer_status_cancelled') }}</p>
                        <p class="text-[10px] text-rose-500 dark:text-rose-400 mt-0.5">Transfer voided</p>
                    </div>
                </div>
            @else
                <div class="p-2.5 rounded-lg border {{ $transfer->received_at ? 'border-emerald-200 dark:border-emerald-800/80 bg-emerald-50/50 dark:bg-emerald-950/20' : 'border-slate-200 dark:border-slate-700 bg-slate-50/40 dark:bg-slate-800/30 opacity-60' }} flex items-start gap-2.5">
                    <span class="w-6 h-6 rounded-full {{ $transfer->received_at ? 'bg-emerald-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-500' }} font-black text-xs grid place-items-center shrink-0">3</span>
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-slate-900 dark:text-slate-100">{{ __('messages.received') }}</p>
                        @if($transfer->received_at)
                            <p class="text-[10px] font-mono text-emerald-600 dark:text-emerald-400 mt-0.5">{{ $transfer->received_at->format('d/m/Y H:i') }}</p>
                        @else
                            <p class="text-[10px] text-slate-400 mt-0.5">Pending receipt</p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
