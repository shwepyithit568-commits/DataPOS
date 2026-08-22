@extends('layouts.admin.app')

@section('content')
@php
    $statusColors = [
        'received' => 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300',
        'diagnosing' => 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300',
        'awaiting_approval' => 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300',
        'awaiting_parts' => 'bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300',
        'in_repair' => 'bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300',
        'ready' => 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300',
        'delivered' => 'bg-gray-100 dark:bg-gray-700/40 text-gray-500 dark:text-gray-400',
        'cancelled' => 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300',
        'unrepairable' => 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300',
    ];
    $badge = $statusColors[$repair->status] ?? 'bg-gray-100 text-gray-500';
@endphp

<div class="w-full space-y-5 sm:space-y-6">
    {{-- Header --}}
    <div class="admin-page-header">
        <div class="flex items-center gap-3 flex-wrap">
            <a href="{{ route('store.admin.repairs.index', $storeRouteParams) }}"
               class="text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h1 class="admin-page-title"><span class="font-mono">{{ $repair->job_number }}</span></h1>
                <p class="admin-page-sub">
                    {{ $store->name }} · {{ __('messages.repair_received_at') }} {{ $repair->created_at->format('M d, Y H:i') }}
                </p>
            </div>
            <span class="px-3 py-1 text-xs font-bold rounded-full whitespace-nowrap {{ $badge }}">
                {{ __('messages.repair_status_' . $repair->status) }}
            </span>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('store.admin.repairs.print', [...$storeRouteParams, 'repair' => $repair->id]) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-semibold text-gray-700 dark:text-slate-200 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 rounded-lg transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2z" />
                </svg>
                {{ __('messages.repair_print_ticket') }}
            </a>
            @if (! $repair->isTerminal())
                <a href="{{ route('store.admin.repairs.edit', [...$storeRouteParams, 'repair' => $repair->id]) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-semibold text-gray-700 dark:text-slate-200 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    {{ __('messages.repair_edit') }}
                </a>
            @endif
        </div>
    </div>

    {{-- Flash / errors --}}
    @if (session('success'))
        <div class="p-3.5 sm:p-4 bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-800 rounded-xl text-sm text-green-700 dark:text-green-300 flex items-start gap-2">
            <span class="text-base flex-shrink-0">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="p-4 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded-xl text-sm text-red-700 dark:text-red-300">
            @foreach ($errors->all() as $error)
                <p>• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- Device + Customer --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Device --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl p-5 space-y-3">
            <h2 class="font-semibold text-gray-900 dark:text-white text-sm flex items-center gap-1.5">
                <svg class="w-4 h-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                {{ __('messages.repair_device_section') }}
            </h2>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <div class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.repair_device_type') }}</div>
                    <div class="font-medium text-gray-900 dark:text-slate-100">{{ $repair->device_type }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.repair_model') }}</div>
                    <div class="font-medium text-gray-900 dark:text-slate-100">{{ $repair->model ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 dark:text-slate-400">IMEI / Serial</div>
                    <div class="font-mono font-medium text-gray-900 dark:text-slate-100">{{ $repair->imei_serial ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.repair_voucher_no') }}</div>
                    <div class="font-mono font-medium text-gray-900 dark:text-slate-100">{{ $repair->voucher_no ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.repair_estimated_completion') }}</div>
                    <div class="font-medium text-gray-900 dark:text-slate-100">{{ $repair->estimated_completion?->format('M d, Y') ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.repair_technician') }}</div>
                    <div class="font-medium text-gray-900 dark:text-slate-100">{{ $repair->technician?->name ?? __('messages.repair_unassigned') }}</div>
                </div>
            </div>
            <div class="pt-2 border-t dark:border-slate-700 space-y-2 text-sm">
                <div>
                    <div class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.repair_reported_problem') }}</div>
                    <div class="text-gray-800 dark:text-slate-200 whitespace-pre-line">{{ $repair->reported_problem }}</div>
                </div>
                @if ($repair->intake_condition)
                    <div>
                        <div class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.repair_intake_condition') }}</div>
                        <div class="text-gray-800 dark:text-slate-200 whitespace-pre-line">{{ $repair->intake_condition }}</div>
                    </div>
                @endif
                @if ($repair->accessories)
                    <div>
                        <div class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.repair_accessories') }}</div>
                        <div class="text-gray-800 dark:text-slate-200">{{ $repair->accessories }}</div>
                    </div>
                @endif
                @if ($repair->diagnosis)
                    <div>
                        <div class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.repair_diagnosis') }}</div>
                        <div class="text-gray-800 dark:text-slate-200 whitespace-pre-line">{{ $repair->diagnosis }}</div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Customer + Creator --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl p-5 space-y-3">
            <h2 class="font-semibold text-gray-900 dark:text-white text-sm flex items-center gap-1.5">
                <svg class="w-4 h-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                {{ __('messages.repair_customer_section') }}
            </h2>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <div class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.repair_customer_label') }}</div>
                    <div class="font-medium text-gray-900 dark:text-slate-100">
                        @if ($repair->customer)
                            {{ $repair->customer->name }}
                        @else
                            {{ $repair->contact_name ?? '—' }}
                        @endif
                    </div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.repair_contact_phone') }}</div>
                    <div class="font-medium text-gray-900 dark:text-slate-100">{{ $repair->contact_phone ?? ($repair->customer?->phone ?? '—') }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.repair_created_by') }}</div>
                    <div class="font-medium text-gray-900 dark:text-slate-100">{{ $repair->creator?->name ?? '—' }}</div>
                </div>
            </div>
            @if ($repair->notes)
                <div class="pt-2 border-t dark:border-slate-700 text-sm">
                    <div class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.notes') }}</div>
                    <div class="text-gray-800 dark:text-slate-200 whitespace-pre-line">{{ $repair->notes }}</div>
                </div>
            @endif
            @if ($repair->warranty_notes)
                <div class="pt-2 border-t dark:border-slate-700 text-sm">
                    <div class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.repair_warranty_notes') }}</div>
                    <div class="text-gray-800 dark:text-slate-200 whitespace-pre-line">{{ $repair->warranty_notes }}</div>
                </div>
            @endif
        </div>
    </div>

    {{-- Parts & Services (line items) --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl overflow-hidden">
        <div class="p-4 border-b dark:border-slate-700 flex items-center justify-between gap-3 flex-wrap">
            <h2 class="font-semibold text-gray-900 dark:text-white text-sm flex items-center gap-1.5">
                <svg class="w-4 h-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                {{ __('messages.repair_items_section') }}
            </h2>
            @if (! $repair->isTerminal())
                <a href="{{ route('store.admin.repairs.edit', [...$storeRouteParams, 'repair' => $repair->id]) }}"
                   class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-violet-700 dark:text-violet-300 bg-violet-50 dark:bg-violet-950/60 border border-violet-200 dark:border-violet-800 rounded-lg hover:bg-violet-100 dark:hover:bg-violet-900/50 transition">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ __('messages.repair_add_item') }}
                </a>
            @endif
        </div>

        @if ($repair->items->isEmpty())
            <div class="p-6 text-center text-sm text-gray-400 dark:text-slate-500">{{ __('messages.repair_items_empty') }}</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm text-gray-600 dark:text-slate-300">
                    <thead class="bg-gray-50 dark:bg-slate-900/50 border-b dark:border-slate-700 font-semibold text-gray-700 dark:text-slate-200 text-xs">
                        <tr>
                            <th class="p-3 whitespace-nowrap">{{ __('messages.repair_item_type') }}</th>
                            <th class="p-3 whitespace-nowrap">{{ __('messages.repair_item_name') }}</th>
                            <th class="p-3 whitespace-nowrap text-center">{{ __('messages.repair_item_qty') }}</th>
                            <th class="p-3 whitespace-nowrap text-right">{{ __('messages.repair_item_price') }}</th>
                            <th class="p-3 whitespace-nowrap text-right">{{ __('messages.repair_item_subtotal') }}</th>
                            <th class="p-3 whitespace-nowrap text-right">{{ __('messages.status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-slate-700">
                        @foreach ($repair->items as $item)
                            <tr>
                                <td class="p-3">
                                    @if ($item->isService())
                                        <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-sky-100 dark:bg-sky-900/40 text-sky-700 dark:text-sky-300">{{ __('messages.repair_item_service') }}</span>
                                    @else
                                        <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300">{{ __('messages.repair_item_part') }}</span>
                                    @endif
                                </td>
                                <td class="p-3">
                                    <div class="font-medium text-gray-900 dark:text-slate-100">{{ $item->name }}</div>
                                    @if ($item->sku)
                                        <div class="text-xs text-gray-400 dark:text-slate-500 font-mono">{{ $item->sku }}</div>
                                    @endif
                                </td>
                                <td class="p-3 text-center text-gray-700 dark:text-slate-200">{{ $item->quantity }}</td>
                                <td class="p-3 text-right text-gray-700 dark:text-slate-200">{{ number_format((float) $item->unit_price, 0) }}</td>
                                <td class="p-3 text-right font-semibold text-gray-900 dark:text-slate-100">{{ number_format((float) $item->subtotal, 0) }} MMK</td>
                                <td class="p-3 text-right whitespace-nowrap">
                                    @if ($item->isPart() && $item->product_id)
                                        @if ($item->is_deducted)
                                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                {{ __('messages.repair_deducted') }}
                                            </span>
                                        @elseif (! $repair->isTerminal())
                                            <form method="POST"
                                                  action="{{ route('store.admin.repairs.items.deduct', [...$storeRouteParams, 'repair' => $repair->id, 'item' => $item->id]) }}"
                                                  onsubmit="return confirm('{{ __('messages.repair_deduct_confirm') }}')">
                                                @csrf
                                                <button type="submit"
                                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/50 transition">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V7M3 7l2-4h14l2 4M3 7h18"/></svg>
                                                    {{ __('messages.repair_deduct_stock') }}
                                                </button>
                                            </form>
                                        @endif
                                    @else
                                        <span class="text-gray-300 dark:text-slate-600">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-slate-900/50 border-t dark:border-slate-700">
                        <tr>
                            <td colspan="4" class="p-3 text-right font-semibold text-gray-600 dark:text-slate-300">{{ __('messages.repair_items_total') }}</td>
                            <td class="p-3 text-right font-bold text-gray-900 dark:text-white">{{ number_format($repair->itemsTotal(), 0) }} MMK</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>

    {{-- Money: charges & payments --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Charges --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl p-5 space-y-3">
            <h2 class="font-semibold text-gray-900 dark:text-white text-sm flex items-center gap-1.5">
                <svg class="w-4 h-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                {{ __('messages.repair_charges') }}
            </h2>
            <div class="grid grid-cols-3 gap-3 text-center">
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-slate-900/50">
                    <div class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.repair_estimated_charge') }}</div>
                    <div class="font-bold text-gray-900 dark:text-slate-100">{{ number_format((float) $repair->estimated_charge, 0) }}</div>
                </div>
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-slate-900/50">
                    <div class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.repair_final_charge') }}</div>
                    <div class="font-bold text-gray-900 dark:text-slate-100">
                        {{ $repair->final_charge !== null ? number_format((float) $repair->final_charge, 0) : '—' }}
                    </div>
                </div>
                <div class="p-3 rounded-lg {{ $repair->outstanding() > 0 ? 'bg-amber-50 dark:bg-amber-950/40' : 'bg-emerald-50 dark:bg-emerald-950/40' }}">
                    <div class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.repair_outstanding') }}</div>
                    <div class="font-bold {{ $repair->outstanding() > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                        {{ number_format($repair->outstanding(), 0) }}
                    </div>
                </div>
            </div>
            <div class="text-xs text-gray-400 dark:text-slate-500">
                {{ __('messages.repair_paid') }}: {{ number_format($repair->paidAmount(), 0) }} MMK
            </div>
        </div>

        {{-- Payments --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl p-5 space-y-3">
            <h2 class="font-semibold text-gray-900 dark:text-white text-sm flex items-center gap-1.5">
                <svg class="w-4 h-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                {{ __('messages.repair_payments') }}
            </h2>
            @if ($repair->payments->isEmpty())
                <div class="text-sm text-gray-400 dark:text-slate-500 py-2">{{ __('messages.repair_no_payments') }}</div>
            @else
                <div class="space-y-2">
                    @foreach ($repair->payments as $payment)
                        <div class="flex items-center justify-between text-sm border-b dark:border-slate-700 pb-2">
                            <div>
                                <span class="font-semibold text-gray-900 dark:text-slate-100">{{ strtoupper($payment->method) }}</span>
                                @if ($payment->reference)
                                    <span class="text-xs text-gray-400 dark:text-slate-500 font-mono"> · {{ $payment->reference }}</span>
                                @endif
                                <div class="text-xs text-gray-400 dark:text-slate-500">{{ $payment->created_at->format('M d, Y H:i') }}</div>
                            </div>
                            <div class="font-semibold text-gray-900 dark:text-slate-100">{{ number_format((float) $payment->amount, 0) }} MMK</div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if (! $repair->isTerminal() && $repair->outstanding() > 0)
                <form method="POST" action="{{ route('store.admin.repairs.payments.store', [...$storeRouteParams, 'repair' => $repair->id]) }}"
                      class="pt-2 border-t dark:border-slate-700 grid grid-cols-2 sm:grid-cols-4 gap-2 items-end">
                    @csrf
                    <div class="col-span-2 sm:col-span-1">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">{{ __('messages.method') }}</label>
                        <select name="method" required class="w-full border dark:border-slate-600 rounded-lg px-2 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">
                            <option value="cash">Cash</option>
                            <option value="kpay">KBZ Pay</option>
                            <option value="wavepay">Wave Pay</option>
                            <option value="cb_pay">CB Pay</option>
                            <option value="mmqr">MMQR</option>
                        </select>
                    </div>
                    <div class="col-span-1">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">{{ __('messages.amount') }}</label>
                        <input type="number" name="amount" min="0.01" step="0.01" max="{{ $repair->outstanding() }}" required
                               class="w-full border dark:border-slate-600 rounded-lg px-2 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500" />
                    </div>
                    <div class="col-span-1">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">{{ __('messages.reference') }}</label>
                        <input type="text" name="reference" maxlength="100"
                               class="w-full border dark:border-slate-600 rounded-lg px-2 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500" />
                    </div>
                    <div class="col-span-2 sm:col-span-4">
                        <button type="submit" class="w-full px-4 py-2 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition cursor-pointer">
                            + {{ __('messages.repair_add_payment') }}
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    {{-- Status transition --}}
    @if (! $repair->isTerminal())
        <div class="bg-white dark:bg-slate-800 rounded-xl p-5 space-y-3">
            <h2 class="font-semibold text-gray-900 dark:text-white text-sm flex items-center gap-1.5">
                <svg class="w-4 h-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                {{ __('messages.repair_update_status') }}
            </h2>
            <form method="POST" action="{{ route('store.admin.repairs.status', [...$storeRouteParams, 'repair' => $repair->id]) }}"
                  class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">{{ __('messages.repair_new_status') }}</label>
                    <select name="status" required class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">
                        @foreach (\App\POS\Models\ServiceJob::STATUSES as $status)
                            @if ($status !== $repair->status)
                                <option value="{{ $status }}">{{ __('messages.repair_status_' . $status) }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">{{ __('messages.repair_status_note') }}</label>
                    <div class="flex gap-2">
                        <input type="text" name="note" maxlength="500" placeholder="{{ __('messages.repair_status_note_placeholder') }}"
                               class="flex-1 border dark:border-slate-600 rounded-lg px-3 py-2.5 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500" />
                        <button type="submit" class="px-4 py-2.5 text-sm font-semibold text-white bg-violet-600 hover:bg-violet-700 rounded-lg transition cursor-pointer whitespace-nowrap">
                            {{ __('messages.repair_apply') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    @endif

    {{-- Status history --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl overflow-hidden">
        <div class="p-4 border-b dark:border-slate-700">
            <h2 class="font-semibold text-gray-900 dark:text-white text-sm flex items-center gap-1.5">
                <svg class="w-4 h-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                {{ __('messages.repair_status_history') }}
            </h2>
        </div>
        @if ($repair->statusHistory->isEmpty())
            <div class="p-8 text-center text-sm text-gray-400 dark:text-slate-500">{{ __('messages.repair_no_history') }}</div>
        @else
            <div class="divide-y dark:divide-slate-700">
                @foreach ($repair->statusHistory as $entry)
                    <div class="flex items-start justify-between gap-3 p-3.5 text-sm">
                        <div class="flex items-start gap-3 min-w-0">
                            <span class="px-2 py-0.5 text-xs font-bold rounded-full whitespace-nowrap {{ $statusColors[$entry->status] ?? 'bg-gray-100 text-gray-500' }}">
                                {{ __('messages.repair_status_' . $entry->status) }}
                            </span>
                            <div class="min-w-0">
                                @if ($entry->note)
                                    <div class="text-gray-700 dark:text-slate-200">{{ $entry->note }}</div>
                                @endif
                                <div class="text-xs text-gray-400 dark:text-slate-500">{{ $entry->changer?->name ?? '—' }}</div>
                            </div>
                        </div>
                        <div class="text-xs text-gray-400 dark:text-slate-500 whitespace-nowrap">{{ $entry->created_at->format('M d, Y H:i') }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
