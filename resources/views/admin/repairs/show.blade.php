@extends('layouts.admin.app')

@section('title', ($store->name ?? 'DataPOS') . ' · ' . $repair->job_number . ' - ' . __('messages.repair_jobs'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
@php
    $statusColors = [
        'received' => 'bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800',
        'diagnosing' => 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800',
        'awaiting_approval' => 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800',
        'awaiting_parts' => 'bg-orange-50 dark:bg-orange-950/60 text-orange-700 dark:text-orange-300 border-orange-200 dark:border-orange-800',
        'in_repair' => 'bg-violet-50 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 border-violet-200 dark:border-violet-800',
        'ready' => 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
        'delivered' => 'bg-slate-50 dark:bg-slate-900/60 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-800',
        'cancelled' => 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800',
        'unrepairable' => 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800',
    ];
    $badgeStyle = $statusColors[$repair->status] ?? 'bg-slate-50 text-slate-600 border-slate-200';
    $outstanding = (float) $repair->outstanding();
    $paidAmount = (float) $repair->paidAmount();
    $chargeAmount = $repair->final_charge !== null ? (float) $repair->final_charge : (float) $repair->estimated_charge;
    $customerName = $repair->customer?->name ?: ($repair->contact_name ?: __('messages.repair_walk_in'));
    $customerPhone = $repair->contact_phone ?: ($repair->customer?->phone ?? null);
    $trackingUrl = $trackingUrl ?? route('storefront.service.track.token', ['store_slug' => $store->slug, 'token' => $repair->tracking_token]);
@endphp

<div class="w-full space-y-0.5 pb-6">

    {{-- ── SECTION 1: Ultra-Dense Standard Header Banner ── --}}
    <div class="admin-toolbar-root rounded-lg bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 px-2 sm:px-3 py-1.5 shadow-xs flex flex-wrap items-center justify-between gap-1.5 sm:gap-2">
        <div class="flex items-center gap-1.5 sm:gap-2 min-w-0">
            <a href="{{ route('store.admin.repairs.index', $storeRouteParams) }}"
               class="h-7 w-7 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 grid place-items-center text-slate-600 dark:text-slate-300 transition-colors shrink-0"
               title="{{ __('messages.back') }}">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>

            <div class="h-6 w-6 rounded-md bg-violet-600/10 dark:bg-violet-400/10 text-violet-600 dark:text-violet-400 grid place-items-center text-xs font-black shrink-0">
                🔧
            </div>

            <div class="min-w-0">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-semibold bg-violet-50 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 border border-violet-200/60 dark:border-violet-800/60 shrink-0">
                        {{ $store->name }}
                    </span>
                    <h1 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white truncate font-mono">
                        {{ $repair->job_number }}
                    </h1>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $badgeStyle }}">
                        {{ __('messages.repair_status_' . $repair->status) }}
                    </span>
                    @if ($repair->voucher_no)
                        <span class="hidden sm:inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-mono text-slate-500 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                            {{ $repair->voucher_no }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex items-center gap-1 sm:gap-1.5 shrink-0">
            <button type="button"
                    data-repair-notify-open
                    class="h-7 px-2 sm:px-2.5 rounded-lg text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition flex items-center gap-1 cursor-pointer"
                    title="{{ __('messages.repair_notify_customer') }}">
                <span>📢</span>
                <span class="hidden sm:inline">{{ __('messages.repair_notify_customer') }}</span>
            </button>

            <a href="{{ route('store.admin.repairs.print', [...$storeRouteParams, 'repair' => $repair->id]) }}" target="_blank"
               class="h-7 px-2 sm:px-2.5 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-600 transition flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2z" />
                </svg>
                <span class="hidden sm:inline">{{ __('messages.repair_print_ticket') }}</span>
            </a>

            @if (! $repair->isTerminal())
                <a href="{{ route('store.admin.repairs.edit', [...$storeRouteParams, 'repair' => $repair->id]) }}"
                   class="h-7 px-2 sm:px-2.5 rounded-lg text-xs font-semibold bg-violet-50 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 border border-violet-200 dark:border-violet-800 hover:bg-violet-100 dark:hover:bg-violet-900/50 transition flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span>{{ __('messages.repair_edit') }}</span>
                </a>
            @endif
        </div>
    </div>

    {{-- Flash / Error Alerts --}}
    @if (session('success'))
        <div class="rounded-lg p-2 bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-xs text-emerald-700 dark:text-emerald-300 flex items-center gap-1.5 shadow-xs">
            <span>✓</span>
            <span class="font-semibold">{{ session('success') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="rounded-lg p-2 bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 text-xs text-rose-700 dark:text-rose-300 space-y-0.5 shadow-xs">
            @foreach ($errors->all() as $error)
                <p>• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- ── SECTION 2: Centered Row-based 4 Stat Cards (Standard v4.1) ── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-0.5 sm:gap-1">
        {{-- Card 1: Charges / Estimate --}}
        <div class="rounded-lg bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 px-2 py-1.5 sm:px-3 sm:py-2 shadow-xs flex items-center justify-center gap-2 sm:gap-2.5">
            <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-violet-50 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 grid place-items-center text-xs sm:text-sm shrink-0">
                💰
            </span>
            <div class="min-w-0">
                <span class="text-[10px] sm:text-[11px] font-semibold text-slate-500 dark:text-slate-400 truncate block">
                    {{ $repair->final_charge !== null ? __('messages.repair_final_charge') : __('messages.repair_estimated_charge') }}
                </span>
                <span class="text-xs sm:text-sm font-black text-slate-900 dark:text-white font-mono block">
                    {{ format_currency($chargeAmount, $store) }}
                </span>
            </div>
        </div>

        {{-- Card 2: Paid Amount --}}
        <div class="rounded-lg bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 px-2 py-1.5 sm:px-3 sm:py-2 shadow-xs flex items-center justify-center gap-2 sm:gap-2.5">
            <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 grid place-items-center text-xs sm:text-sm shrink-0">
                💵
            </span>
            <div class="min-w-0">
                <span class="text-[10px] sm:text-[11px] font-semibold text-slate-500 dark:text-slate-400 truncate block">
                    {{ __('messages.repair_paid') }}
                </span>
                <span class="text-xs sm:text-sm font-black text-emerald-600 dark:text-emerald-400 font-mono block">
                    {{ format_currency($paidAmount, $store) }}
                </span>
            </div>
        </div>

        {{-- Card 3: Outstanding Debt --}}
        <div class="rounded-lg bg-white dark:bg-slate-800 border {{ $outstanding > 0 ? 'border-rose-200 dark:border-rose-900/60 bg-rose-50/20' : 'border-slate-200/80 dark:border-slate-700/80' }} px-2 py-1.5 sm:px-3 sm:py-2 shadow-xs flex items-center justify-center gap-2 sm:gap-2.5">
            <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-full {{ $outstanding > 0 ? 'bg-rose-100 dark:bg-rose-950/80 text-rose-600 dark:text-rose-400' : 'bg-slate-100 dark:bg-slate-800 text-slate-500' }} grid place-items-center text-xs sm:text-sm shrink-0">
                ⚠️
            </span>
            <div class="min-w-0">
                <span class="text-[10px] sm:text-[11px] font-semibold text-slate-500 dark:text-slate-400 truncate block">
                    {{ __('messages.repair_outstanding') }}
                </span>
                <span class="text-xs sm:text-sm font-black {{ $outstanding > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white' }} font-mono block">
                    {{ format_currency($outstanding, $store) }}
                </span>
            </div>
        </div>

        {{-- Card 4: Items & Services Count --}}
        <div class="rounded-lg bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 px-2 py-1.5 sm:px-3 sm:py-2 shadow-xs flex items-center justify-center gap-2 sm:gap-2.5">
            <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 grid place-items-center text-xs sm:text-sm shrink-0">
                🛠️
            </span>
            <div class="min-w-0">
                <span class="text-[10px] sm:text-[11px] font-semibold text-slate-500 dark:text-slate-400 truncate block">
                    {{ __('messages.repair_ticket_items') }}
                </span>
                <span class="text-xs sm:text-sm font-black text-slate-900 dark:text-white font-mono block">
                    {{ $repair->items->count() }}
                </span>
            </div>
        </div>
    </div>

    {{-- ── SECTION 3: 2-Column Responsive Layout ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-0.5 sm:gap-1">

        {{-- ── LEFT COLUMN: Device & Customer Details ── --}}
        <div class="space-y-0.5 sm:space-y-1">

            {{-- Card 1: 📱 Device Information --}}
            <div class="rounded-lg bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 p-2.5 sm:p-3 shadow-xs space-y-2">
                <div class="flex items-center justify-between pb-1.5 border-b border-slate-100 dark:border-slate-700">
                    <div class="flex items-center gap-1.5 text-xs font-bold text-slate-900 dark:text-white">
                        <span class="text-amber-500">📱</span>
                        <span>{{ __('messages.repair_device_section') }}</span>
                    </div>
                    @if ($repair->tracking_token)
                        <span class="text-[10px] font-mono text-slate-400" title="Tracking Token">
                            🔑 {{ substr($repair->tracking_token, 0, 8) }}...
                        </span>
                    @endif
                </div>

                {{-- Device Grid Specs --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs">
                    <div class="p-1.5 rounded bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800">
                        <span class="text-[10px] text-slate-400 block font-semibold">🏢 {{ __('messages.repair_brand') }}</span>
                        <span class="font-bold text-slate-800 dark:text-slate-100 truncate block">{{ $repair->brand ?? '—' }}</span>
                    </div>

                    <div class="p-1.5 rounded bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800">
                        <span class="text-[10px] text-slate-400 block font-semibold">📁 {{ __('messages.category') ?? 'Category' }}</span>
                        <span class="font-bold text-slate-800 dark:text-slate-100 truncate block">{{ $repair->category ?? ($repair->device_type ?: '—') }}</span>
                    </div>

                    <div class="p-1.5 rounded bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800">
                        <span class="text-[10px] text-slate-400 block font-semibold">📱 {{ __('messages.repair_model') }}</span>
                        <span class="font-bold text-slate-800 dark:text-slate-100 truncate block">{{ $repair->model ?? '—' }}</span>
                    </div>

                    <div class="p-1.5 rounded bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800">
                        <span class="text-[10px] text-slate-400 block font-semibold">🎨 {{ __('messages.repair_color') }}</span>
                        <span class="font-semibold text-slate-800 dark:text-slate-200 truncate block">{{ $repair->color ?? '—' }}</span>
                    </div>

                    <div class="p-1.5 rounded bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800">
                        <span class="text-[10px] text-slate-400 block font-semibold">💾 {{ __('messages.repair_storage') }}</span>
                        <span class="font-semibold text-slate-800 dark:text-slate-200 truncate block">{{ $repair->storage ?? '—' }}</span>
                    </div>

                    <div class="p-1.5 rounded bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800">
                        <span class="text-[10px] text-slate-400 block font-semibold">IMEI / Serial</span>
                        <span class="font-mono font-bold text-slate-800 dark:text-slate-200 truncate block">{{ $repair->imei_serial ?? '—' }}</span>
                    </div>

                    <div class="p-1.5 rounded bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800">
                        <span class="text-[10px] text-slate-400 block font-semibold">🧾 {{ __('messages.repair_voucher_no') }}</span>
                        <span class="font-mono font-bold text-slate-800 dark:text-slate-200 truncate block">{{ $repair->voucher_no ?? '—' }}</span>
                    </div>

                    <div class="p-1.5 rounded bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800">
                        <span class="text-[10px] text-slate-400 block font-semibold">👨‍🔧 {{ __('messages.repair_technician') }}</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200 truncate block">{{ $repair->technician?->name ?? __('messages.repair_unassigned') }}</span>
                    </div>

                    <div class="p-1.5 rounded bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800">
                        <span class="text-[10px] text-slate-400 block font-semibold">📅 {{ __('messages.repair_estimated_completion') }}</span>
                        <span class="font-semibold text-slate-800 dark:text-slate-200 truncate block">{{ $repair->estimated_completion ? $repair->estimated_completion->format('d/m/Y') : '—' }}</span>
                    </div>
                </div>

                {{-- Pattern Lock or Password --}}
                @if ($repair->pattern_lock || $repair->device_password)
                    <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 flex flex-wrap gap-2 text-xs">
                        @if ($repair->pattern_lock)
                            <div class="flex items-center gap-1.5">
                                <span class="text-slate-400 font-semibold">🔒 Pattern Lock:</span>
                                <span class="font-mono font-bold text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-950/80 border border-violet-200 dark:border-violet-800 px-2 py-0.5 rounded">
                                    {{ str_replace('-', ' → ', $repair->pattern_lock) }}
                                </span>
                            </div>
                        @endif
                        @if ($repair->device_password)
                            <div class="flex items-center gap-1.5">
                                <span class="text-slate-400 font-semibold">🔑 PIN / Password:</span>
                                <span class="font-mono font-bold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/80 border border-amber-200 dark:border-amber-800 px-2 py-0.5 rounded">
                                    {{ $repair->device_password }}
                                </span>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Problem & Condition Details --}}
                <div class="space-y-1.5 pt-1 text-xs">
                    <div class="p-2 rounded-md bg-amber-50/50 dark:bg-amber-950/20 border border-amber-200/60 dark:border-amber-900/40">
                        <span class="text-[10px] font-bold text-amber-700 dark:text-amber-400 block mb-0.5">
                            ⚠️ {{ __('messages.repair_reported_problem') }}
                        </span>
                        <p class="text-slate-800 dark:text-slate-200 font-medium whitespace-pre-line">{{ $repair->reported_problem }}</p>
                    </div>

                    @if ($repair->intake_condition)
                        <div>
                            <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 block mb-0.5">
                                {{ __('messages.repair_intake_condition') }}
                            </span>
                            <p class="text-slate-700 dark:text-slate-300 whitespace-pre-line">{{ $repair->intake_condition }}</p>
                        </div>
                    @endif

                    @if ($repair->accessories)
                        <div>
                            <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 block mb-0.5">
                                🎒 {{ __('messages.repair_accessories') }}
                            </span>
                            <p class="text-slate-700 dark:text-slate-300">{{ $repair->accessories }}</p>
                        </div>
                    @endif

                    @if ($repair->diagnosis)
                        <div class="p-2 rounded-md bg-sky-50/50 dark:bg-sky-950/20 border border-sky-200/60 dark:border-sky-900/40">
                            <span class="text-[10px] font-bold text-sky-700 dark:text-sky-400 block mb-0.5">
                                🔬 {{ __('messages.repair_diagnosis') }}
                            </span>
                            <p class="text-slate-800 dark:text-slate-200 whitespace-pre-line">{{ $repair->diagnosis }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Card 2: 👤 Customer Information --}}
            <div class="rounded-lg bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 p-2.5 sm:p-3 shadow-xs space-y-2">
                <div class="flex items-center justify-between pb-1.5 border-b border-slate-100 dark:border-slate-700">
                    <div class="flex items-center gap-1.5 text-xs font-bold text-slate-900 dark:text-white">
                        <span class="text-sky-500">👤</span>
                        <span>{{ __('messages.repair_customer_section') }}</span>
                    </div>
                    @if ($repair->customer)
                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                            ✓ {{ __('messages.registered_customer') ?? 'Registered Customer' }}
                        </span>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                    <div>
                        <span class="text-[10px] text-slate-400 block font-semibold">{{ __('messages.repair_customer_label') }}</span>
                        <span class="font-bold text-slate-900 dark:text-white text-sm">
                            {{ $repair->customer ? $repair->customer->name : ($repair->contact_name ?: 'Walk-in') }}
                        </span>
                    </div>

                    <div>
                        <span class="text-[10px] text-slate-400 block font-semibold">{{ __('messages.repair_contact_phone') }}</span>
                        @php $phone = $repair->contact_phone ?: ($repair->customer?->phone ?? null); @endphp
                        @if ($phone)
                            <a href="tel:{{ $phone }}"
                               class="font-bold text-violet-600 dark:text-violet-400 hover:underline flex items-center gap-1">
                                <span>📞</span>
                                <span>{{ $phone }}</span>
                            </a>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </div>
                </div>

                @if ($repair->shipping_address)
                    <div class="text-xs pt-1 border-t border-slate-100 dark:border-slate-700">
                        <span class="text-[10px] text-slate-400 block font-semibold">{{ __('messages.address') }}</span>
                        <p class="text-slate-700 dark:text-slate-300">{{ $repair->shipping_address }}</p>
                    </div>
                @endif

                @if ($repair->warranty_notes)
                    <div class="text-xs pt-1 border-t border-slate-100 dark:border-slate-700">
                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 block mb-0.5">
                            🛡️ {{ __('messages.repair_warranty_notes') }}
                        </span>
                        <p class="text-slate-700 dark:text-slate-300 whitespace-pre-line">{{ $repair->warranty_notes }}</p>
                    </div>
                @endif

                @if ($repair->notes)
                    <div class="text-xs pt-1 border-t border-slate-100 dark:border-slate-700">
                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 block mb-0.5">
                            📝 {{ __('messages.notes') }}
                        </span>
                        <p class="text-slate-700 dark:text-slate-300 whitespace-pre-line">{{ $repair->notes }}</p>
                    </div>
                @endif
            </div>

        </div>

        {{-- ── RIGHT COLUMN: Parts, Payments & Status History ── --}}
        <div class="space-y-0.5 sm:space-y-1">

            {{-- Card 3: 🛠️ Parts & Services List --}}
            <div class="rounded-lg bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 overflow-hidden shadow-xs">
                <div class="p-2 sm:p-2.5 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between gap-2 flex-wrap">
                    <div class="flex items-center gap-1.5 text-xs font-bold text-slate-900 dark:text-white">
                        <span class="text-violet-500">🛠️</span>
                        <span>{{ __('messages.repair_items_section') }}</span>
                    </div>

                    @if (! $repair->isTerminal())
                        <a href="{{ route('store.admin.repairs.edit', [...$storeRouteParams, 'repair' => $repair->id]) }}"
                           class="h-6 px-2 rounded text-[11px] font-bold text-violet-700 dark:text-violet-300 bg-violet-50 dark:bg-violet-950/60 border border-violet-200 dark:border-violet-800 hover:bg-violet-100 transition flex items-center gap-1">
                            <span>+</span>
                            <span>{{ __('messages.repair_add_item') }}</span>
                        </a>
                    @endif
                </div>

                @if ($repair->items->isEmpty())
                    <div class="p-4 text-center text-xs text-slate-400 dark:text-slate-500">
                        {{ __('messages.repair_items_empty') }}
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-700 font-bold text-slate-600 dark:text-slate-300 text-[11px]">
                                <tr>
                                    <th class="p-2 whitespace-nowrap">{{ __('messages.repair_item_type') }}</th>
                                    <th class="p-2 whitespace-nowrap">{{ __('messages.repair_item_name') }}</th>
                                    <th class="p-2 whitespace-nowrap text-center">{{ __('messages.repair_item_qty') }}</th>
                                    <th class="p-2 whitespace-nowrap text-right">{{ __('messages.repair_item_price') }}</th>
                                    <th class="p-2 whitespace-nowrap text-right">{{ __('messages.repair_item_subtotal') }}</th>
                                    <th class="p-2 whitespace-nowrap text-right">{{ __('messages.status') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                @foreach ($repair->items as $item)
                                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/40 transition-colors">
                                        <td class="p-2 whitespace-nowrap">
                                            @if ($item->isService())
                                                <span class="px-1.5 py-0.5 text-[10px] font-bold rounded bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                                                    {{ __('messages.repair_item_service') }}
                                                </span>
                                            @else
                                                <span class="px-1.5 py-0.5 text-[10px] font-bold rounded bg-violet-50 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 border border-violet-200 dark:border-violet-800">
                                                    {{ __('messages.repair_item_part') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="p-2">
                                            <div class="font-bold text-slate-900 dark:text-slate-100">{{ $item->name }}</div>
                                            @if ($item->sku)
                                                <div class="text-[10px] text-slate-400 font-mono">{{ $item->sku }}</div>
                                            @endif
                                        </td>
                                        <td class="p-2 text-center font-mono font-bold text-slate-800 dark:text-slate-200">
                                            {{ format_quantity($item->quantity, $store) }}
                                        </td>
                                        <td class="p-2 text-right font-mono text-slate-700 dark:text-slate-300 whitespace-nowrap">
                                            {{ format_currency($item->unit_price, $store) }}
                                        </td>
                                        <td class="p-2 text-right font-mono font-bold text-slate-900 dark:text-white whitespace-nowrap">
                                            {{ format_currency($item->subtotal, $store) }}
                                        </td>
                                        <td class="p-2 text-right whitespace-nowrap">
                                            @if ($item->isPart() && $item->product_id)
                                                @if ($item->is_deducted)
                                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                        {{ __('messages.repair_deducted') }}
                                                    </span>
                                                @elseif (! $repair->isTerminal())
                                                    <form method="POST"
                                                          action="{{ route('store.admin.repairs.items.deduct', [...$storeRouteParams, 'repair' => $repair->id, 'item' => $item->id]) }}"
                                                          onsubmit="return confirm('{{ __('messages.repair_deduct_confirm') ?? 'Deduct stock for this part?' }}')">
                                                        @csrf
                                                        <button type="submit"
                                                                class="h-6 px-2 rounded text-[10px] font-bold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800 hover:bg-amber-100 transition cursor-pointer">
                                                            📦 {{ __('messages.repair_deduct_stock') }}
                                                        </button>
                                                    </form>
                                                @endif
                                            @else
                                                <span class="text-slate-300 dark:text-slate-600">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-slate-50 dark:bg-slate-900/50 border-t border-slate-100 dark:border-slate-700">
                                <tr>
                                    <td colspan="4" class="p-2 text-right font-bold text-slate-600 dark:text-slate-300">
                                        {{ __('messages.repair_items_total') }}
                                    </td>
                                    <td class="p-2 text-right font-black font-mono text-slate-900 dark:text-white whitespace-nowrap">
                                        {{ format_currency($repair->itemsTotal(), $store) }}
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Card 4: 💳 Payment Records & Quick Intake Form --}}
            <div class="rounded-lg bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 p-2.5 sm:p-3 shadow-xs space-y-2">
                <div class="flex items-center justify-between pb-1.5 border-b border-slate-100 dark:border-slate-700">
                    <div class="flex items-center gap-1.5 text-xs font-bold text-slate-900 dark:text-white">
                        <span class="text-emerald-500">💳</span>
                        <span>{{ __('messages.repair_payments') }}</span>
                    </div>
                    <span class="text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400">
                        {{ format_currency($paidAmount, $store) }}
                    </span>
                </div>

                @if ($repair->payments->isEmpty())
                    <div class="p-2.5 text-center text-xs text-slate-400 dark:text-slate-500">
                        {{ __('messages.repair_no_payments') }}
                    </div>
                @else
                    <div class="space-y-1">
                        @foreach ($repair->payments as $payment)
                            <div class="p-1.5 rounded bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold uppercase bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800">
                                            {{ $payment->method }}
                                        </span>
                                        @if ($payment->reference)
                                            <span class="text-[10px] text-slate-400 font-mono truncate">
                                                #{{ $payment->reference }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">
                                        {{ $payment->created_at->format('d/m/Y H:i') }}
                                        @if ($payment->creator)
                                            · {{ $payment->creator->name }}
                                        @endif
                                    </div>
                                </div>
                                <div class="font-mono font-bold text-slate-900 dark:text-white text-xs text-right shrink-0">
                                    {{ format_currency($payment->amount, $store) }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Quick Payment Form (if outstanding exists) --}}
                @if (! $repair->isTerminal() && $outstanding > 0)
                    <form method="POST" action="{{ route('store.admin.repairs.payments.store', [...$storeRouteParams, 'repair' => $repair->id]) }}"
                          class="pt-2 border-t border-slate-100 dark:border-slate-700 space-y-1.5">
                        @csrf
                        <div class="text-[11px] font-bold text-slate-700 dark:text-slate-300 flex items-center justify-between">
                            <span>+ {{ __('messages.repair_add_payment') }}</span>
                            <span class="text-rose-500 font-mono font-bold">{{ __('messages.repair_outstanding') }}: {{ format_currency($outstanding, $store) }}</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-1.5">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-0.5">{{ __('messages.method') ?? 'Method' }}</label>
                                <select name="method" required
                                        class="w-full h-7 px-1.5 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-semibold text-slate-800 dark:text-slate-200 outline-none">
                                    <option value="cash">Cash</option>
                                    <option value="kpay">KBZ Pay</option>
                                    <option value="wavepay">Wave Pay</option>
                                    <option value="cb_pay">CB Pay</option>
                                    <option value="mmqr">MMQR</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-0.5">{{ __('messages.amount') }}</label>
                                <input type="number" name="amount" min="0.01" step="100" max="{{ $outstanding }}" value="{{ $outstanding }}" required
                                       class="w-full h-7 px-1.5 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-mono font-bold text-slate-800 dark:text-slate-200 outline-none" />
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-0.5">{{ __('messages.reference') }}</label>
                                <input type="text" name="reference" maxlength="100" placeholder="TXN ID / မှတ်ချက်..."
                                       class="w-full h-7 px-1.5 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-semibold text-slate-800 dark:text-slate-200 outline-none" />
                            </div>
                        </div>

                        <button type="submit"
                                class="w-full h-7 rounded text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 active:scale-[0.99] transition cursor-pointer shadow-xs">
                            ✓ {{ __('messages.repair_add_payment') }}
                        </button>
                    </form>
                @endif
            </div>

            {{-- Card 5: 🔄 Lifecycle Status Transition & History Timeline --}}
            <div class="rounded-lg bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 p-2.5 sm:p-3 shadow-xs space-y-2">
                <div class="flex items-center justify-between pb-1.5 border-b border-slate-100 dark:border-slate-700">
                    <div class="flex items-center gap-1.5 text-xs font-bold text-slate-900 dark:text-white">
                        <span class="text-violet-500">🔄</span>
                        <span>{{ __('messages.repair_status_history') }}</span>
                    </div>
                </div>

                {{-- Status Change Form (if not terminal) --}}
                @if (! $repair->isTerminal())
                    <form method="POST" action="{{ route('store.admin.repairs.status', [...$storeRouteParams, 'repair' => $repair->id]) }}"
                          data-repair-status-form
                          class="p-2 rounded-lg bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 space-y-1.5">
                        @csrf
                        <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300 block">
                            {{ __('messages.repair_update_status') }}
                        </span>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-0.5">{{ __('messages.repair_new_status') }}</label>
                                <select name="status" required
                                        class="w-full h-7 px-1.5 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-bold text-slate-800 dark:text-slate-200 outline-none">
                                    <option value="" selected disabled>{{ __('messages.select') }}</option>
                                    @foreach (\App\POS\Models\ServiceJob::STATUSES as $status)
                                        @if ($status !== $repair->status)
                                            <option value="{{ $status }}">{{ __('messages.repair_status_' . $status) }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-0.5">{{ __('messages.repair_status_note') }}</label>
                                <input type="text" name="note" maxlength="500" placeholder="{{ __('messages.repair_status_note_placeholder') }}"
                                       class="w-full h-7 px-1.5 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-semibold text-slate-800 dark:text-slate-200 outline-none" />
                            </div>
                        </div>

                        <div class="pt-0.5">
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" name="notify_customer" value="1" checked
                                       class="rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                                <span class="text-[11px] text-slate-600 dark:text-slate-300 font-medium select-none">
                                    {{ __('messages.repair_notify_checkbox') }}
                                </span>
                            </label>
                        </div>

                        <button type="submit" data-repair-status-submit
                                class="w-full h-7 rounded text-xs font-bold text-white bg-violet-600 hover:bg-violet-700 transition cursor-pointer shadow-xs">
                            {{ __('messages.repair_apply') }}
                        </button>
                    </form>
                @endif

                {{-- Status History Timeline List --}}
                @if ($repair->statusHistory->isEmpty())
                    <div class="p-3 text-center text-xs text-slate-400 dark:text-slate-500">
                        {{ __('messages.repair_no_history') }}
                    </div>
                @else
                    <div class="space-y-1 pt-1">
                        @foreach ($repair->statusHistory->sortByDesc('id') as $entry)
                            <div class="p-1.5 rounded bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800 flex items-start justify-between gap-2 text-xs">
                                <div class="flex items-start gap-1.5 min-w-0">
                                    <span class="px-1.5 py-0.2 rounded text-[10px] font-bold border shrink-0 {{ $statusColors[$entry->status] ?? 'bg-slate-50 text-slate-600 border-slate-200' }}">
                                        {{ __('messages.repair_status_' . $entry->status) }}
                                    </span>
                                    <div class="min-w-0">
                                        @if ($entry->note)
                                            <p class="font-semibold text-slate-800 dark:text-slate-200 text-xs">{{ $entry->note }}</p>
                                        @endif
                                        <span class="text-[10px] text-slate-400 block">
                                            {{ $entry->changer?->name ?? '—' }}
                                        </span>
                                    </div>
                                </div>
                                <span class="text-[10px] text-slate-400 font-mono whitespace-nowrap shrink-0">
                                    {{ $entry->created_at->format('d/m H:i') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>

    </div>


    {{-- ── SECTION 5: Interactive Customer Notification Modal (pure DOM) ── --}}
    <div id="repairNotifyModal"
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/70 backdrop-blur-xs"
         data-repair-notify-backdrop>
        <div class="w-full max-w-lg rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">

            {{-- Modal Header --}}
            <div class="p-3 sm:p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/80 dark:bg-slate-800/90">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 grid place-items-center text-base">
                        📢
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                            {{ __('messages.repair_notify_customer') }}
                        </h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">
                            👤 {{ $customerName }} · 📞 {{ $customerPhone ?? '—' }}
                        </p>
                    </div>
                </div>
                <button type="button" data-repair-notify-close
                        class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 grid place-items-center text-sm transition cursor-pointer">
                    ✕
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-3 sm:p-4 space-y-3 overflow-y-auto bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100">
                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-myanmar">
                    {{ __('messages.repair_notify_customer_desc') }}
                </p>

                {{-- Quick Template Switcher Pills --}}
                <div class="flex items-center gap-1.5 p-1 rounded-lg bg-slate-100 dark:bg-slate-950 text-xs border border-slate-200/60 dark:border-slate-800">
                    <button type="button" id="notifyTabReady" data-notify-tab="ready"
                            class="flex-1 py-1 px-2 rounded-md transition text-center flex items-center justify-center gap-1 cursor-pointer bg-white dark:bg-slate-800 text-emerald-600 dark:text-emerald-400 shadow-xs font-bold border border-slate-200/60 dark:border-slate-700">
                        <span>🎉</span>
                        <span>စက်ပြင်ပြီးစီး လာယူရန်</span>
                    </button>
                    <button type="button" id="notifyTabProgress" data-notify-tab="progress"
                            class="flex-1 py-1 px-2 rounded-md transition text-center flex items-center justify-center gap-1 cursor-pointer text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 font-medium border border-transparent">
                        <span>ℹ️</span>
                        <span>လက်ရှိအခြေအနေ အသိပေးစာ</span>
                    </button>
                </div>

                {{-- Editable Textarea with live preview --}}
                <div class="space-y-1">
                    <div class="flex items-center justify-between text-[11px] font-bold text-slate-700 dark:text-slate-300">
                        <span>{{ __('messages.repair_notify_preview_title') }}</span>
                        <span class="text-slate-400 dark:text-slate-500 font-normal text-[10px]">စိတ်ကြိုက် ပြင်ဆင်နိုင်ပါသည်</span>
                    </div>
                    <textarea id="notifyMessageText" rows="7"
                              class="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-xs leading-relaxed text-slate-800 dark:text-slate-200 font-myanmar outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"></textarea>
                </div>

                {{-- Dispatch Channels Grid --}}
                <div class="space-y-1.5 pt-1">
                    <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">
                        ပေးပို့လိုသည့် လမ်းကြောင်း (Channels)
                    </span>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        {{-- Viber Button --}}
                        <button type="button" data-notify-channel="viber"
                                class="w-full py-2 px-3 rounded-xl bg-violet-600 hover:bg-violet-700 active:scale-[0.98] text-white font-bold text-xs flex items-center justify-center gap-2 shadow-sm shadow-violet-500/20 transition cursor-pointer">
                            <span>💬</span>
                            <span>{{ __('messages.repair_notify_send_viber') }}</span>
                        </button>

                        {{-- Telegram Button --}}
                        <button type="button" data-notify-channel="telegram"
                                class="w-full py-2 px-3 rounded-xl bg-sky-500 hover:bg-sky-600 active:scale-[0.98] text-white font-bold text-xs flex items-center justify-center gap-2 shadow-sm shadow-sky-500/20 transition cursor-pointer">
                            <span>✈️</span>
                            <span>{{ __('messages.repair_notify_send_telegram') }}</span>
                        </button>

                        {{-- SMS Button --}}
                        <button type="button" data-notify-channel="sms"
                                class="w-full py-2 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:scale-[0.98] text-white font-bold text-xs flex items-center justify-center gap-2 shadow-sm shadow-emerald-500/20 transition cursor-pointer">
                            <span>📱</span>
                            <span>{{ __('messages.repair_notify_send_sms') }}</span>
                        </button>

                        {{-- Copy Text Button --}}
                        <button type="button" id="notifyCopyBtn" data-notify-channel="copy"
                                class="w-full py-2 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 active:scale-[0.98] text-slate-800 dark:text-slate-200 font-bold text-xs flex items-center justify-center gap-2 border border-slate-200 dark:border-slate-700 transition cursor-pointer">
                            <span>📋</span>
                            <span id="notifyCopyText">{{ __('messages.repair_notify_copy_message') }}</span>
                        </button>
                    </div>
                </div>

            </div>

            {{-- Modal Footer --}}
            <div class="p-2.5 sm:p-3 border-t border-slate-100 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-800/90 flex items-center justify-between text-xs">
                <span class="text-[11px] text-slate-400 dark:text-slate-500 truncate max-w-[280px]">
                    Live: <span class="font-mono text-violet-500 dark:text-violet-400">{{ $trackingUrl }}</span>
                </span>
                <button type="button" data-repair-notify-close
                        class="px-3 py-1.5 rounded-lg font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-200/60 dark:hover:bg-slate-700 transition cursor-pointer">
                    {{ __('messages.close') ?? 'Close' }}
                </button>
            </div>

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}">
    var _notifyData = {
        customerName: @js($customerName),
        customerPhone: @js($customerPhone ?? ''),
        cleanPhone: @js($customerPhone ? preg_replace('/[^0-9]/', '', $customerPhone) : ''),
        storeName: @js($store->name),
        storePhone: @js($store->phone ?? ''),
        jobNumber: @js($repair->job_number),
        deviceLabel: @js(trim(($repair->brand ?? '') . ' ' . ($repair->model ?? $repair->device_type))),
        statusLabel: @js(__('messages.repair_status_' . $repair->status)),
        status: @js($repair->status),
        charge: @js(format_currency($chargeAmount, $store)),
        paid: @js(format_currency($paidAmount, $store)),
        outstanding: @js(format_currency($outstanding, $store)),
        hasOutstanding: {{ $outstanding > 0 ? 'true' : 'false' }},
        trackingUrl: @js($trackingUrl)
    };

    var _notifyTab = _notifyData.status === 'ready' ? 'ready' : 'progress';

    function _buildNotifyText(tab) {
        var d = _notifyData;
        var lines = [];
        if (tab === 'ready') {
            lines.push('မင်္ဂလာပါ ' + d.customerName + ' ရှင့်။');
            lines.push(d.storeName + ' မှ လူကြီးမင်း အပ်နှံထားသော ' + d.deviceLabel + ' (အလုပ်နံပါတ်: #' + d.jobNumber + ') ပြင်ဆင်ပြီးစီးပါပြီဖြစ်ပါ၍ ဆိုင်သို့ လာရောက်ထုတ်ယူနိုင်ပါပြီရှင်。\n');
            lines.push('💵 ကျသင့်ငွေ: ' + d.charge);
            lines.push('✅ ပေးချေပြီး: ' + d.paid);
            if (d.hasOutstanding) { lines.push('⚠️ ပေးရန်ကျန်ငွေ: ' + d.outstanding); }
        } else {
            lines.push('မင်္ဂလာပါ ' + d.customerName + ' ရှင့်။');
            lines.push(d.storeName + ' မှ လူကြီးမင်း အပ်နှံထားသော ' + d.deviceLabel + ' (အလုပ်နံပါတ်: #' + d.jobNumber + ') ၏ ပြင်ဆင်မှုအခြေအနေမှာ \'[' + d.statusLabel + ']\' သို့ ရောက်ရှိနေပါပြီဖြစ်ကြောင်း လေးစားစွာ အသိပေးအပ်ပါသည်။\n');
        }
        lines.push('\n🔍 Live အခြေအနေနှင့် ပြေစာအသေးစိတ်ကို အောက်ပါ link တွင် စစ်ဆေးနိုင်ပါသည်:');
        lines.push(d.trackingUrl);
        if (d.storePhone) { lines.push('\n📞 ဆက်သွယ်ရန် ဖုန်း: ' + d.storePhone); }
        lines.push('ကျေးဇူးတင်ရှိပါသည်ရှင်။');
        return lines.join('\n');
    }

    function _notifyRefreshTextarea() {
        var ta = document.getElementById('notifyMessageText');
        if (ta) ta.value = _buildNotifyText(_notifyTab);
    }

    function openRepairNotifyModal() {
        var m = document.getElementById('repairNotifyModal');
        if (!m) return;
        _notifyRefreshTextarea();
        m.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeRepairNotifyModal() {
        var m = document.getElementById('repairNotifyModal');
        if (!m) return;
        m.style.display = 'none';
        document.body.style.overflow = '';
    }

    function notifySetTab(tab) {
        _notifyTab = tab;
        var ready = document.getElementById('notifyTabReady');
        var prog  = document.getElementById('notifyTabProgress');
        if (ready)  ready.dataset.active  = (tab === 'ready')    ? '1' : '0';
        if (prog)   prog.dataset.active   = (tab === 'progress') ? '1' : '0';
        _applyTabStyles();
        _notifyRefreshTextarea();
    }

    function _applyTabStyles() {
        var ready = document.getElementById('notifyTabReady');
        var prog  = document.getElementById('notifyTabProgress');
        var baseClass = 'flex-1 py-1 px-2 rounded-md transition text-center flex items-center justify-center gap-1 cursor-pointer ';
        var activeClass = baseClass + 'bg-white dark:bg-slate-800 text-emerald-600 dark:text-emerald-400 shadow-xs font-bold border border-slate-200/60 dark:border-slate-700';
        var inactiveClass = baseClass + 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 font-medium border border-transparent';
        if (ready) { ready.className = (_notifyTab === 'ready') ? activeClass : inactiveClass; }
        if (prog)  { prog.className  = (_notifyTab === 'progress') ? activeClass : inactiveClass; }
    }

    function notifySendViber() {
        var ta = document.getElementById('notifyMessageText');
        var text = ta ? ta.value : _buildNotifyText(_notifyTab);
        window.location.href = 'viber://forward?text=' + encodeURIComponent(text);
    }

    function notifySendTelegram() {
        var ta = document.getElementById('notifyMessageText');
        var text = ta ? ta.value : _buildNotifyText(_notifyTab);
        window.open('https://t.me/share/url?url=' + encodeURIComponent(_notifyData.trackingUrl) + '&text=' + encodeURIComponent(text), '_blank');
    }

    function notifySendSms() {
        var ta = document.getElementById('notifyMessageText');
        var text = ta ? ta.value : _buildNotifyText(_notifyTab);
        window.location.href = 'sms:' + _notifyData.cleanPhone + '?body=' + encodeURIComponent(text);
    }

    function notifyCopyText() {
        var ta = document.getElementById('notifyMessageText');
        var text = ta ? ta.value : _buildNotifyText(_notifyTab);
        var span = document.getElementById('notifyCopyText');
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function() {
                if (span) span.textContent = '{{ __('messages.repair_notify_message_copied') }}';
                setTimeout(function() {
                    if (span) span.textContent = '{{ __('messages.repair_notify_copy_message') }}';
                }, 2500);
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[data-repair-notify-open]').forEach(function(button) {
            button.addEventListener('click', openRepairNotifyModal);
        });
        document.querySelectorAll('[data-repair-notify-close]').forEach(function(button) {
            button.addEventListener('click', closeRepairNotifyModal);
        });

        var backdrop = document.querySelector('[data-repair-notify-backdrop]');
        if (backdrop) {
            backdrop.addEventListener('click', function(event) {
                if (event.target === backdrop) closeRepairNotifyModal();
            });
        }

        document.querySelectorAll('[data-notify-tab]').forEach(function(button) {
            button.addEventListener('click', function() {
                notifySetTab(button.dataset.notifyTab);
            });
        });
        document.querySelectorAll('[data-notify-channel]').forEach(function(button) {
            button.addEventListener('click', function() {
                var handlers = {
                    viber: notifySendViber,
                    telegram: notifySendTelegram,
                    sms: notifySendSms,
                    copy: notifyCopyText
                };
                var handler = handlers[button.dataset.notifyChannel];
                if (handler) handler();
            });
        });

        var statusForm = document.querySelector('[data-repair-status-form]');
        if (statusForm) {
            statusForm.addEventListener('submit', function(event) {
                if (statusForm.dataset.submitting === 'true') {
                    event.preventDefault();
                    return;
                }
                statusForm.dataset.submitting = 'true';
                var submitButton = statusForm.querySelector('[data-repair-status-submit]');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.classList.add('opacity-60', 'cursor-wait');
                    submitButton.textContent = @js(__('messages.saving'));
                }
            });
        }

        @if (session('notify_customer'))
        openRepairNotifyModal();
        @endif
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeRepairNotifyModal();
        });
    });
</script>
@endpush
