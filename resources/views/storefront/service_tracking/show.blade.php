@extends('layouts.storefront.app', ['title' => ($job->voucher_no ?? $job->job_number) . ' · ' . __('messages.track_service_title')])

@section('main_padding', 'px-0.5 sm:px-3 lg:px-6 py-1 sm:py-3')

@section('content')
<style>
    @media print {
        header, footer, .no-print, nav, .sf-sticky-controls, #offline-banner {
            display: none !important;
        }
        body {
            background: #fff !important;
            color: #000 !important;
            padding: 0 !important;
        }
        .print-container {
            max-width: 100% !important;
            box-shadow: none !important;
            border: none !important;
            padding: 0 !important;
        }
        .print-card {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
            background: #fff !important;
        }
    }
</style>

@php
    $statusMap = [
        'received'          => ['step' => 1, 'color' => 'blue',   'label' => __('messages.repair_status_received')],
        'diagnosing'        => ['step' => 2, 'color' => 'indigo', 'label' => __('messages.repair_status_diagnosing')],
        'awaiting_approval' => ['step' => 2, 'color' => 'amber',  'label' => __('messages.repair_status_awaiting_approval')],
        'awaiting_parts'    => ['step' => 3, 'color' => 'purple', 'label' => __('messages.repair_status_awaiting_parts')],
        'in_repair'         => ['step' => 3, 'color' => 'orange', 'label' => __('messages.repair_status_in_repair')],
        'ready'             => ['step' => 4, 'color' => 'emerald','label' => __('messages.repair_status_ready')],
        'delivered'         => ['step' => 5, 'color' => 'slate',  'label' => __('messages.repair_status_delivered')],
        'cancelled'         => ['step' => 0, 'color' => 'rose',   'label' => __('messages.repair_status_cancelled')],
        'unrepairable'      => ['step' => 0, 'color' => 'rose',   'label' => __('messages.repair_status_unrepairable')],
    ];

    $currentStep = $statusMap[$job->status]['step'] ?? 1;
    $isTerminalCancelled = in_array($job->status, ['cancelled', 'unrepairable'], true);
    $isReady = $job->status === 'ready';
    $isDelivered = $job->status === 'delivered';

    $deviceLabel = trim(($job->category ?? $job->device_type ?? 'Device') . ' ' . ($job->brand ? '· ' . $job->brand : '') . ' ' . ($job->model ? '· ' . $job->model : ''));
    $activeStoreSlug = $store?->slug ?? request('store_slug');
@endphp

<div class="print-container max-w-5xl mx-auto space-y-2 sm:space-y-3 pb-16 select-none font-sans"
     x-data="{
        copied: false,
        copyLink() {
            navigator.clipboard.writeText(window.location.href);
            this.copied = true;
            setTimeout(() => this.copied = false, 2500);
        }
     }">

    {{-- Top Action Strip --}}
    <div class="no-print flex items-center justify-between text-xs gap-2">
        <a href="{{ url('/store/' . $activeStoreSlug . '/track/service') }}"
           class="sf-btn-3d active !flex-row px-2.5 py-1 text-xs font-bold leading-none inline-flex items-center gap-1.5">
            <span>←</span>
            <span>{{ __('messages.track_service_back_btn') }}</span>
        </a>

        <div class="flex items-center gap-1.5">
            <button type="button" onclick="window.print()"
                    class="sf-btn-3d active !flex-row px-2.5 py-1 text-xs font-bold leading-none inline-flex items-center gap-1">
                <span>🖨️</span>
                <span class="hidden sm:inline">{{ __('messages.track_service_print_slip') }}</span>
            </button>

            <button type="button" @click="copyLink()"
                    class="sf-btn-3d active !flex-row px-2.5 py-1 text-xs font-bold leading-none inline-flex items-center gap-1 cursor-pointer">
                <span x-show="!copied">🔗 {{ __('messages.track_service_copy_link') }}</span>
                <span x-show="copied" x-cloak class="text-emerald-600 dark:text-emerald-400 font-black">✓ {{ __('messages.track_service_link_copied') }}</span>
            </button>
        </div>
    </div>

    {{-- Ready for Pickup Banner (If ready) --}}
    @if ($isReady)
        <div class="print-card p-3.5 sm:p-4 rounded-lg sm:rounded-xl bg-emerald-500 text-white shadow-md flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-white/20 flex items-center justify-center text-2xl shrink-0">
                🎉
            </div>
            <div class="space-y-0.5 flex-1 min-w-0">
                <h3 class="text-sm sm:text-base font-black font-myanmar">
                    {{ __('messages.track_service_pickup_ready') }}
                </h3>
                <p class="text-xs text-emerald-100 font-myanmar truncate">
                    {{ $setting?->address ?? $store->address ?? 'ဆိုင်သို့ လာရောက်ထုတ်ယူနိုင်ပါသည်' }}
                    @if ($setting?->opening_hours)
                        · (ဖွင့်ချိန်: {{ $setting->opening_hours }})
                    @endif
                </p>
            </div>
        </div>
    @elseif ($isTerminalCancelled)
        <div class="print-card p-3.5 sm:p-4 rounded-lg sm:rounded-xl bg-rose-500 text-white shadow-md flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-white/20 flex items-center justify-center text-xl shrink-0">
                ⚠️
            </div>
            <div class="space-y-0.5">
                <h3 class="text-sm sm:text-base font-black font-myanmar">
                    {{ __('messages.repair_status_' . $job->status) }}
                </h3>
                <p class="text-xs text-rose-100 font-myanmar">
                    {{ __('messages.contact_shop_hint') }}
                </p>
            </div>
        </div>
    @endif

    {{-- Status Header Card --}}
    <div class="print-card bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-3.5 sm:p-5 border border-slate-200/90 dark:border-slate-800 shadow-xs space-y-3">
        
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800">
            <div class="space-y-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs font-mono font-black px-2.5 py-0.5 rounded-md bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 border border-teal-200 dark:border-teal-800">
                        {{ $job->voucher_no ?? $job->job_number }}
                    </span>
                    @if ($job->voucher_no)
                        <span class="text-xs font-mono text-slate-400 font-bold">Ref: {{ $job->job_number }}</span>
                    @endif
                    <span class="text-xs text-slate-400">·</span>
                    <span class="text-xs text-slate-500 dark:text-slate-400 font-mono">
                        {{ $job->created_at->format('d M Y, h:i A') }}
                    </span>
                </div>
                <h2 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white font-outfit">
                    {{ $deviceLabel }}
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-myanmar">
                    {{ __('messages.track_service_device_owner') }}: <span class="font-bold text-slate-700 dark:text-slate-200">{{ $job->contact_name ?: ($job->customer?->name ?? '—') }}</span>
                    @if ($job->contact_phone)
                        · {{ __('messages.phone_number') }}: <span class="font-bold text-slate-700 dark:text-slate-200 font-mono">{{ $job->contact_phone }}</span>
                    @endif
                </p>
            </div>

            <div class="flex sm:flex-col items-start sm:items-end justify-between gap-1 shrink-0">
                <span class="px-3 py-1 text-xs sm:text-sm font-black rounded-md shadow-2xs border
                    @if ($job->status === 'ready') bg-emerald-50 text-emerald-700 dark:bg-emerald-950/80 dark:text-emerald-300 border-emerald-300 dark:border-emerald-700
                    @elseif ($job->status === 'delivered') bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700
                    @elseif (in_array($job->status, ['in_repair', 'awaiting_parts'])) bg-orange-50 text-orange-700 dark:bg-orange-950/80 dark:text-orange-300 border-orange-300 dark:border-orange-700
                    @elseif (in_array($job->status, ['diagnosing', 'awaiting_approval'])) bg-amber-50 text-amber-700 dark:bg-amber-950/80 dark:text-amber-300 border-amber-300 dark:border-amber-700
                    @elseif ($isTerminalCancelled) bg-rose-50 text-rose-700 dark:bg-rose-950/80 dark:text-rose-300 border-rose-300 dark:border-rose-700
                    @else bg-blue-50 text-blue-700 dark:bg-blue-950/80 dark:text-blue-300 border-blue-300 dark:border-blue-700
                    @endif">
                    {{ __('messages.repair_status_' . $job->status) }}
                </span>
            </div>
        </div>

        {{-- 5-Stage Progress Stepper --}}
        @if (! $isTerminalCancelled)
            <div class="py-1.5">
                <div class="relative">
                    <div class="absolute top-4 left-6 right-6 h-1 bg-slate-200 dark:bg-slate-800 rounded-full -z-0">
                        <div class="h-full bg-teal-500 rounded-full transition-all duration-500"
                             style="width: {{ min(100, max(0, ($currentStep - 1) * 25)) }}%"></div>
                    </div>

                    <div class="grid grid-cols-5 relative z-10 text-center">
                        <div class="flex flex-col items-center space-y-1">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs transition-all shadow-2xs
                                {{ $currentStep >= 1 ? 'bg-teal-600 text-white ring-2 ring-white dark:ring-slate-900' : 'bg-slate-200 dark:bg-slate-800 text-slate-400' }}">
                                1
                            </div>
                            <span class="text-[10px] sm:text-xs font-black block {{ $currentStep >= 1 ? 'text-teal-700 dark:text-teal-300' : 'text-slate-400' }}">
                                {{ __('messages.track_service_step_1') }}
                            </span>
                        </div>

                        <div class="flex flex-col items-center space-y-1">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs transition-all shadow-2xs
                                {{ $currentStep >= 2 ? 'bg-teal-600 text-white ring-2 ring-white dark:ring-slate-900' . ($currentStep === 2 ? ' animate-pulse' : '') : 'bg-slate-200 dark:bg-slate-800 text-slate-400' }}">
                                2
                            </div>
                            <span class="text-[10px] sm:text-xs font-black block {{ $currentStep >= 2 ? 'text-teal-700 dark:text-teal-300' : 'text-slate-400' }}">
                                {{ __('messages.track_service_step_2') }}
                            </span>
                        </div>

                        <div class="flex flex-col items-center space-y-1">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs transition-all shadow-2xs
                                {{ $currentStep >= 3 ? 'bg-teal-600 text-white ring-2 ring-white dark:ring-slate-900' . ($currentStep === 3 ? ' animate-pulse' : '') : 'bg-slate-200 dark:bg-slate-800 text-slate-400' }}">
                                3
                            </div>
                            <span class="text-[10px] sm:text-xs font-black block {{ $currentStep >= 3 ? 'text-teal-700 dark:text-teal-300' : 'text-slate-400' }}">
                                {{ __('messages.track_service_step_3') }}
                            </span>
                        </div>

                        <div class="flex flex-col items-center space-y-1">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs transition-all shadow-2xs
                                {{ $currentStep >= 4 ? 'bg-emerald-600 text-white ring-2 ring-white dark:ring-slate-900' . ($currentStep === 4 ? ' animate-pulse' : '') : 'bg-slate-200 dark:bg-slate-800 text-slate-400' }}">
                                4
                            </div>
                            <span class="text-[10px] sm:text-xs font-black block {{ $currentStep >= 4 ? 'text-emerald-700 dark:text-emerald-300' : 'text-slate-400' }}">
                                {{ __('messages.track_service_step_4') }}
                            </span>
                        </div>

                        <div class="flex flex-col items-center space-y-1">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs transition-all shadow-2xs
                                {{ $currentStep >= 5 ? 'bg-slate-800 text-white dark:bg-slate-700 ring-2 ring-white dark:ring-slate-900' : 'bg-slate-200 dark:bg-slate-800 text-slate-400' }}">
                                5
                            </div>
                            <span class="text-[10px] sm:text-xs font-black block {{ $currentStep >= 5 ? 'text-slate-900 dark:text-white' : 'text-slate-400' }}">
                                {{ __('messages.track_service_step_5') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>

    {{-- Two Column Detail Breakdown --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-2 sm:gap-3">

        {{-- Left: Device & Repair Info (2 cols) --}}
        <div class="lg:col-span-2 space-y-2 sm:space-y-3">

            {{-- Device Specs Card --}}
            <div class="print-card bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-3.5 sm:p-5 border border-slate-200/90 dark:border-slate-800 shadow-xs space-y-3">
                <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                    <span class="w-7 h-7 rounded-md bg-teal-50 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 flex items-center justify-center text-sm">
                        📱
                    </span>
                    <h3 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white font-myanmar">
                        {{ __('messages.track_service_device_info') }}
                    </h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                    <div class="p-2 rounded-md bg-slate-50 dark:bg-slate-800/60 space-y-0.5 border border-slate-100 dark:border-slate-700/60">
                        <span class="text-slate-400 text-[11px] block">{{ __('messages.category') }}:</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ $job->category ?? $job->device_type ?? '—' }}</span>
                    </div>

                    <div class="p-2 rounded-md bg-slate-50 dark:bg-slate-800/60 space-y-0.5 border border-slate-100 dark:border-slate-700/60">
                        <span class="text-slate-400 text-[11px] block">Brand & Model:</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ $job->brand ?? '' }} {{ $job->model ?? '—' }}</span>
                    </div>

                    @if ($job->imei_serial)
                        <div class="p-2 rounded-md bg-slate-50 dark:bg-slate-800/60 space-y-0.5 border border-slate-100 dark:border-slate-700/60">
                            <span class="text-slate-400 text-[11px] block">IMEI / Serial:</span>
                            <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ $job->imei_serial }}</span>
                        </div>
                    @endif

                    @if ($job->color || $job->storage)
                        <div class="p-2 rounded-md bg-slate-50 dark:bg-slate-800/60 space-y-0.5 border border-slate-100 dark:border-slate-700/60">
                            <span class="text-slate-400 text-[11px] block">{{ __('messages.color') }} / {{ __('messages.storage') }}:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ $job->color ?? '' }} {{ $job->storage ? '(' . $job->storage . ')' : '' }}</span>
                        </div>
                    @endif

                    <div class="p-2 rounded-md bg-slate-50 dark:bg-slate-800/60 space-y-0.5 border border-slate-100 dark:border-slate-700/60 sm:col-span-2">
                        <span class="text-slate-400 text-[11px] block font-bold">{{ __('messages.track_service_problem_label') }}:</span>
                        <span class="font-semibold text-rose-600 dark:text-rose-400">{{ $job->reported_problem ?: '—' }}</span>
                    </div>

                    @if ($job->intake_condition)
                        <div class="p-2 rounded-md bg-slate-50 dark:bg-slate-800/60 space-y-0.5 border border-slate-100 dark:border-slate-700/60 sm:col-span-2">
                            <span class="text-slate-400 text-[11px] block">စက်အခြေအနေ (Intake Condition):</span>
                            <span class="font-medium text-slate-700 dark:text-slate-300">{{ $job->intake_condition }}</span>
                        </div>
                    @endif

                    @if ($job->accessories)
                        <div class="p-2 rounded-md bg-slate-50 dark:bg-slate-800/60 space-y-0.5 border border-slate-100 dark:border-slate-700/60 sm:col-span-2">
                            <span class="text-slate-400 text-[11px] block">တွဲဖက်ပစ္စည်းများ (Accessories):</span>
                            <span class="font-medium text-slate-700 dark:text-slate-300">{{ $job->accessories }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Technician Diagnosis / Notes --}}
            @if ($job->diagnosis || $job->warranty_notes || $job->estimated_completion)
                <div class="print-card bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-3.5 sm:p-5 border border-slate-200/90 dark:border-slate-800 shadow-xs space-y-2.5">
                    <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                        <span class="w-7 h-7 rounded-md bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-sm">
                            🔍
                        </span>
                        <h3 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white font-myanmar">
                            {{ __('messages.track_service_technician_notes') }}
                        </h3>
                    </div>

                    <div class="space-y-2 text-xs">
                        @if ($job->diagnosis)
                            <div class="p-2.5 rounded-md bg-indigo-50/50 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900/50 space-y-0.5">
                                <span class="font-bold text-indigo-900 dark:text-indigo-300 block">စစ်ဆေးတွေ့ရှိချက် (Diagnosis):</span>
                                <p class="text-slate-700 dark:text-slate-300 leading-relaxed font-myanmar whitespace-pre-line">{{ $job->diagnosis }}</p>
                            </div>
                        @endif

                        @if ($job->estimated_completion)
                            <div class="flex items-center justify-between p-2 rounded-md bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60">
                                <span class="text-slate-500 font-myanmar">{{ __('messages.track_service_estimated_completion') }}:</span>
                                <span class="font-bold text-teal-600 dark:text-teal-400 font-mono">{{ $job->estimated_completion->format('d M Y') }}</span>
                            </div>
                        @endif

                        @if ($job->warranty_notes)
                            <div class="flex items-center justify-between p-2 rounded-md bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                <span class="font-bold font-myanmar">🛡️ အာမခံသတ်မှတ်ချက်:</span>
                                <span class="font-bold">{{ $job->warranty_notes }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Line Items Table --}}
            @if ($job->items->isNotEmpty())
                <div class="print-card bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-3.5 sm:p-5 border border-slate-200/90 dark:border-slate-800 shadow-xs space-y-2.5">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                        <div class="flex items-center gap-2">
                            <span class="w-7 h-7 rounded-md bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center text-sm">
                                ⚙️
                            </span>
                            <h3 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white font-myanmar">
                                {{ __('messages.track_service_parts_list') }}
                            </h3>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="text-slate-400 border-b border-slate-100 dark:border-slate-800 text-[11px]">
                                    <th class="pb-1.5">{{ __('messages.order_table_product') }}</th>
                                    <th class="pb-1.5 text-center">{{ __('messages.order_table_qty') }}</th>
                                    <th class="pb-1.5 text-right">{{ __('messages.subtotal') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach ($job->items as $item)
                                    <tr class="py-1.5">
                                        <td class="py-1.5">
                                            <span class="font-bold text-slate-800 dark:text-slate-200 block">{{ $item->name }}</span>
                                            <span class="text-[10px] text-slate-400 uppercase font-mono">{{ $item->item_type }}</span>
                                        </td>
                                        <td class="py-1.5 text-center font-bold text-slate-600 dark:text-slate-300 font-mono">
                                            {{ format_quantity($item->quantity, $store) }}
                                        </td>
                                        <td class="py-1.5 text-right font-black text-slate-900 dark:text-white font-mono">
                                            {{ format_currency($item->subtotal, $store) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Timeline History --}}
            <div class="print-card bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-3.5 sm:p-5 border border-slate-200/90 dark:border-slate-800 shadow-xs space-y-2.5">
                <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                    <span class="w-7 h-7 rounded-md bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center text-sm">
                        ⏱️
                    </span>
                    <h3 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white font-myanmar">
                        {{ __('messages.track_service_timeline') }}
                    </h3>
                </div>

                <div class="space-y-3 pt-1">
                    @forelse ($job->statusHistory as $history)
                        <div class="flex items-start gap-2.5 relative">
                            <div class="w-2 h-2 rounded-full bg-teal-500 mt-1 shrink-0 ring-4 ring-teal-100 dark:ring-teal-950"></div>
                            <div class="space-y-0.5 flex-1 text-xs">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-black text-slate-800 dark:text-slate-200">
                                        {{ __('messages.repair_status_' . $history->status) }}
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-mono">
                                        {{ $history->created_at->format('d M Y, h:i A') }}
                                    </span>
                                </div>
                                @if ($history->note)
                                    <p class="text-xs text-slate-600 dark:text-slate-300 font-myanmar bg-slate-50 dark:bg-slate-800/60 p-2 rounded-md mt-0.5 border border-slate-100 dark:border-slate-700/50">
                                        {{ $history->note }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 text-center py-2 font-myanmar">မှတ်တမ်း မရှိသေးပါ။</p>
                    @endforelse
                </div>
            </div>

        </div>

        {{-- Right Column: Charges & Contact Shop --}}
        <div class="space-y-2 sm:space-y-3">

            {{-- Charges & Payments Card --}}
            <div class="print-card bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-3.5 sm:p-5 border border-slate-200/90 dark:border-slate-800 shadow-xs space-y-3">
                <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                    <span class="w-7 h-7 rounded-md bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-sm">
                        💰
                    </span>
                    <h3 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white font-myanmar">
                        {{ __('messages.track_service_cost_breakdown') }}
                    </h3>
                </div>

                <div class="space-y-2 text-xs">
                    @if ($job->final_charge !== null)
                        <div class="flex justify-between py-1 border-b border-slate-100 dark:border-slate-800">
                            <span class="text-slate-500">{{ __('messages.track_service_final_charge') }}:</span>
                            <span class="font-black text-slate-900 dark:text-white font-mono">{{ format_currency($job->final_charge, $store) }}</span>
                        </div>
                    @elseif ($job->estimated_charge > 0)
                        <div class="flex justify-between py-1 border-b border-slate-100 dark:border-slate-800">
                            <span class="text-slate-500">{{ __('messages.track_service_est_charge') }}:</span>
                            <span class="font-bold text-slate-700 dark:text-slate-300 font-mono">{{ format_currency($job->estimated_charge, $store) }}</span>
                        </div>
                    @endif

                    <div class="flex justify-between py-1 border-b border-slate-100 dark:border-slate-800">
                        <span class="text-slate-500">{{ __('messages.track_service_paid') }}:</span>
                        <span class="font-bold text-emerald-600 dark:text-emerald-400 font-mono">{{ format_currency($job->paidAmount(), $store) }}</span>
                    </div>

                    <div class="p-2.5 rounded-md bg-teal-50/70 dark:bg-teal-950/40 border border-teal-200 dark:border-teal-800/80 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-teal-800 dark:text-teal-300 block font-myanmar">{{ __('messages.track_service_balance') }}</span>
                            <span class="text-[10px] text-teal-600 dark:text-teal-400 font-mono">Outstanding</span>
                        </div>
                        <span class="text-sm sm:text-base font-black text-teal-700 dark:text-teal-300 font-outfit">
                            {{ format_currency($job->outstanding(), $store) }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Contact Shop Card --}}
            <div class="print-card no-print bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-3.5 sm:p-5 border border-slate-200/90 dark:border-slate-800 shadow-xs space-y-2.5">
                <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                    <span class="w-7 h-7 rounded-md bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center text-sm">
                        📞
                    </span>
                    <h3 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white font-myanmar">
                        {{ __('messages.track_service_contact_shop') }}
                    </h3>
                </div>

                <p class="text-xs text-slate-500 dark:text-slate-400 font-myanmar leading-relaxed">
                    {{ __('messages.contact_shop_hint') }}
                </p>

                <div class="space-y-1.5 pt-1">
                    @if ($viberUrl)
                        <a href="{{ $viberUrl }}" data-ios-href="{{ $viberIosUrl ?? $viberUrl }}" target="_blank" rel="noopener noreferrer"
                           class="sf-btn-3d-viber w-full flex items-center justify-center gap-2 px-3 py-2 text-xs font-black rounded-md">
                            <x-brand-icon brand="viber" class="h-4 w-4 shrink-0"/>
                            <span>{{ __('messages.track_service_inquire_viber') }}</span>
                        </a>
                    @endif

                    @if ($telegramUrl)
                        <a href="{{ $telegramUrl }}" target="_blank" rel="noopener noreferrer"
                           class="sf-btn-3d-telegram w-full flex items-center justify-center gap-2 px-3 py-2 text-xs font-black rounded-md">
                            <x-brand-icon brand="telegram" class="h-4 w-4 shrink-0"/>
                            <span>{{ __('messages.track_service_inquire_telegram') }}</span>
                        </a>
                    @endif

                    @if ($setting?->phone ?? $store->phone)
                        <a href="tel:{{ $setting?->phone ?? $store->phone }}"
                           class="sf-btn-3d-success w-full flex items-center justify-center gap-2 px-3 py-2 text-xs font-black rounded-md">
                            <span>📞</span>
                            <span>{{ __('messages.track_service_call_now') }} ({{ $setting?->phone ?? $store->phone }})</span>
                        </a>
                    @endif
                </div>

                {{-- Shop Address Details --}}
                <div class="pt-2 border-t border-slate-100 dark:border-slate-800 text-[11px] text-slate-500 dark:text-slate-400 space-y-0.5 font-myanmar">
                    <div class="font-bold text-slate-700 dark:text-slate-300">{{ $store->name }}</div>
                    @if ($setting?->address ?? $store->address)
                        <div>📍 {{ $setting?->address ?? $store->address }}</div>
                    @endif
                    @if ($setting?->opening_hours)
                        <div>⏰ ဖွင့်ချိန်: {{ $setting->opening_hours }}</div>
                    @endif
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
