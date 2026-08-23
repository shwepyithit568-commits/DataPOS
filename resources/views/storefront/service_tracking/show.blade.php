@extends('layouts.storefront.app', ['title' => ($job->voucher_no ?? $job->job_number) . ' · ' . __('messages.track_service_title')])

@section('content')
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
@endphp

<div class="max-w-5xl mx-auto space-y-6"
     x-data="{
        copied: false,
        copyLink() {
            navigator.clipboard.writeText(window.location.href);
            this.copied = true;
            setTimeout(() => this.copied = false, 3000);
        }
     }">

    {{-- Top Action Strip --}}
    <div class="flex items-center justify-between text-xs sm:text-sm">
        <a href="{{ url('/store/' . $store->slug . '/track/service') }}"
           class="inline-flex items-center gap-1.5 text-slate-500 dark:text-slate-400 hover:text-teal-600 dark:hover:text-teal-400 font-bold transition">
            <span>←</span>
            <span>နောက်သို့ (စက်မှတ်တမ်း အသစ်ရှာမည်)</span>
        </a>
        <button type="button" @click="copyLink()"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs border border-slate-200 dark:border-slate-700 shadow-sm transition active:scale-95 cursor-pointer">
            <span x-show="!copied">🔗 {{ __('messages.track_service_copy_link') }}</span>
            <span x-show="copied" x-cloak class="text-teal-600 dark:text-teal-400">✓ {{ __('messages.track_service_link_copied') }}</span>
        </button>
    </div>

    {{-- Ready for Pickup Banner (If ready) --}}
    @if ($isReady)
        <div class="p-5 sm:p-6 rounded-2xl bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-600 text-white shadow-xl shadow-emerald-500/20 flex flex-col sm:flex-row items-center gap-4 text-center sm:text-left">
            <div class="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center text-3xl shrink-0">
                🎉
            </div>
            <div class="space-y-1 flex-1">
                <h3 class="text-lg sm:text-xl font-black font-myanmar">
                    {{ __('messages.track_service_pickup_ready') }}
                </h3>
                <p class="text-xs sm:text-sm text-emerald-100 font-myanmar">
                    ဆိုင်လိပ်စာ: {{ $setting?->address ?? $store->address ?? 'ဆိုင်သို့ လာရောက်ထုတ်ယူနိုင်ပါသည်' }}
                    @if ($setting?->opening_hours)
                        · (ဖွင့်ချိန်: {{ $setting->opening_hours }})
                    @endif
                </p>
            </div>
        </div>
    @elseif ($isTerminalCancelled)
        <div class="p-5 sm:p-6 rounded-2xl bg-gradient-to-r from-rose-600 to-red-600 text-white shadow-xl flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center text-2xl shrink-0">
                ⚠️
            </div>
            <div class="space-y-1">
                <h3 class="text-base sm:text-lg font-black font-myanmar">
                    အခြေအနေ: {{ __('messages.repair_status_' . $job->status) }}
                </h3>
                <p class="text-xs sm:text-sm text-rose-100 font-myanmar">
                    အသေးစိတ်သိရှိလိုပါက ဆိုင်သို့ ဆက်သွယ်မေးမြန်းနိုင်ပါသည်။
                </p>
            </div>
        </div>
    @endif

    {{-- Status Header Card --}}
    <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 sm:p-7 border border-slate-200/90 dark:border-slate-800/80 shadow-2xl space-y-6">
        
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-5 border-b border-slate-100 dark:border-slate-800">
            <div class="space-y-1.5">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs font-mono font-black px-3 py-1 rounded-xl bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 border border-teal-200 dark:border-teal-800">
                        {{ $job->voucher_no ?? $job->job_number }}
                    </span>
                    @if ($job->voucher_no)
                        <span class="text-xs font-mono text-slate-400 font-bold">Ref: {{ $job->job_number }}</span>
                    @endif
                    <span class="text-xs text-slate-400">·</span>
                    <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                        {{ $job->created_at->format('d M Y, h:i A') }}
                    </span>
                </div>
                <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white font-outfit">
                    {{ $deviceLabel }}
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-myanmar">
                    ပိုင်ရှင်: <span class="font-bold text-slate-700 dark:text-slate-200">{{ $job->contact_name ?: ($job->customer?->name ?? '—') }}</span>
                    @if ($job->contact_phone)
                        · ဖုန်း: <span class="font-bold text-slate-700 dark:text-slate-200">{{ $job->contact_phone }}</span>
                    @endif
                </p>
            </div>

            <div class="flex sm:flex-col items-start sm:items-end justify-between gap-1.5 shrink-0">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">လက်ရှိအခြေအနေ</span>
                <span class="px-4 py-2 text-sm font-black rounded-xl shadow-xs
                    @if ($job->status === 'ready') bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700
                    @elseif ($job->status === 'delivered') bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700
                    @elseif (in_array($job->status, ['in_repair', 'awaiting_parts'])) bg-orange-100 text-orange-800 dark:bg-orange-950/80 dark:text-orange-300 border border-orange-300 dark:border-orange-700
                    @elseif (in_array($job->status, ['diagnosing', 'awaiting_approval'])) bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300 border border-amber-300 dark:border-amber-700
                    @elseif ($isTerminalCancelled) bg-rose-100 text-rose-800 dark:bg-rose-950/80 dark:text-rose-300 border border-rose-300 dark:border-rose-700
                    @else bg-blue-100 text-blue-800 dark:bg-blue-950/80 dark:text-blue-300 border border-blue-300 dark:border-blue-700
                    @endif">
                    {{ __('messages.repair_status_' . $job->status) }}
                </span>
            </div>
        </div>

        {{-- Progress Stepper --}}
        @if (! $isTerminalCancelled)
            <div class="py-2">
                <div class="relative">
                    <div class="absolute top-5 left-6 right-6 h-1 bg-slate-200 dark:bg-slate-800 rounded-full -z-0">
                        <div class="h-full bg-teal-500 rounded-full transition-all duration-500"
                             style="width: {{ min(100, max(0, ($currentStep - 1) * 25)) }}%"></div>
                    </div>

                    <div class="grid grid-cols-5 relative z-10 text-center">
                        <div class="flex flex-col items-center space-y-2">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all
                                {{ $currentStep >= 1 ? 'bg-teal-600 text-white shadow-lg shadow-teal-500/30 ring-4 ring-white dark:ring-slate-900' : 'bg-slate-200 dark:bg-slate-800 text-slate-400' }}">
                                1
                            </div>
                            <span class="text-[11px] sm:text-xs font-bold block {{ $currentStep >= 1 ? 'text-teal-700 dark:text-teal-300' : 'text-slate-400' }}">
                                လက်ခံရရှိ
                            </span>
                        </div>

                        <div class="flex flex-col items-center space-y-2">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all
                                {{ $currentStep >= 2 ? 'bg-teal-600 text-white shadow-lg shadow-teal-500/30 ring-4 ring-white dark:ring-slate-900' : 'bg-slate-200 dark:bg-slate-800 text-slate-400' }}">
                                2
                            </div>
                            <span class="text-[11px] sm:text-xs font-bold block {{ $currentStep >= 2 ? 'text-teal-700 dark:text-teal-300' : 'text-slate-400' }}">
                                စစ်ဆေးနေ
                            </span>
                        </div>

                        <div class="flex flex-col items-center space-y-2">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all
                                {{ $currentStep >= 3 ? 'bg-teal-600 text-white shadow-lg shadow-teal-500/30 ring-4 ring-white dark:ring-slate-900' : 'bg-slate-200 dark:bg-slate-800 text-slate-400' }}">
                                3
                            </div>
                            <span class="text-[11px] sm:text-xs font-bold block {{ $currentStep >= 3 ? 'text-teal-700 dark:text-teal-300' : 'text-slate-400' }}">
                                ပြင်ဆင်နေ
                            </span>
                        </div>

                        <div class="flex flex-col items-center space-y-2">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all
                                {{ $currentStep >= 4 ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-500/30 ring-4 ring-white dark:ring-slate-900' : 'bg-slate-200 dark:bg-slate-800 text-slate-400' }}">
                                4
                            </div>
                            <span class="text-[11px] sm:text-xs font-bold block {{ $currentStep >= 4 ? 'text-emerald-700 dark:text-emerald-300 font-black' : 'text-slate-400' }}">
                                ပြင်ဆင်ပြီး
                            </span>
                        </div>

                        <div class="flex flex-col items-center space-y-2">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all
                                {{ $currentStep >= 5 ? 'bg-slate-800 text-white dark:bg-slate-700 ring-4 ring-white dark:ring-slate-900' : 'bg-slate-200 dark:bg-slate-800 text-slate-400' }}">
                                5
                            </div>
                            <span class="text-[11px] sm:text-xs font-bold block {{ $currentStep >= 5 ? 'text-slate-900 dark:text-white' : 'text-slate-400' }}">
                                လွှဲပြောင်းပြီး
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>

    {{-- Two Column Detail Breakdown --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: Device & Repair Info (2 cols) --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Device Specs Card --}}
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 sm:p-6 border border-slate-200/90 dark:border-slate-800/80 shadow-2xl space-y-4">
                <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                    <span class="w-8 h-8 rounded-xl bg-teal-50 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 flex items-center justify-center text-lg">
                        📱
                    </span>
                    <h3 class="font-black text-base text-slate-900 dark:text-white font-myanmar">
                        {{ __('messages.track_service_device_info') }}
                    </h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs sm:text-sm">
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 space-y-1">
                        <span class="text-slate-400 text-xs block">အမျိုးအစား / Category:</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ $job->category ?? $job->device_type ?? '—' }}</span>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 space-y-1">
                        <span class="text-slate-400 text-xs block">Brand & Model:</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ $job->brand ?? '' }} {{ $job->model ?? '—' }}</span>
                    </div>

                    @if ($job->imei_serial)
                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 space-y-1">
                            <span class="text-slate-400 text-xs block">IMEI / Serial Number:</span>
                            <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ $job->imei_serial }}</span>
                        </div>
                    @endif

                    @if ($job->color || $job->storage)
                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 space-y-1">
                            <span class="text-slate-400 text-xs block">အရောင် / Storage:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ $job->color ?? '' }} {{ $job->storage ? '(' . $job->storage . ')' : '' }}</span>
                        </div>
                    @endif

                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 space-y-1 sm:col-span-2">
                        <span class="text-slate-400 text-xs block font-bold">ကြုံတွေ့ရသော ပြဿနာ (Reported Problem):</span>
                        <span class="font-semibold text-rose-600 dark:text-rose-400">{{ $job->reported_problem ?: '—' }}</span>
                    </div>

                    @if ($job->intake_condition)
                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 space-y-1 sm:col-span-2">
                            <span class="text-slate-400 text-xs block">စက်အခြေအနေ (Intake Condition):</span>
                            <span class="font-medium text-slate-700 dark:text-slate-300">{{ $job->intake_condition }}</span>
                        </div>
                    @endif

                    @if ($job->accessories)
                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 space-y-1 sm:col-span-2">
                            <span class="text-slate-400 text-xs block">တွဲဖက်ပစ္စည်းများ (Accessories):</span>
                            <span class="font-medium text-slate-700 dark:text-slate-300">{{ $job->accessories }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Technician Diagnosis / Notes --}}
            @if ($job->diagnosis || $job->warranty_notes || $job->estimated_completion)
                <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 sm:p-6 border border-slate-200/90 dark:border-slate-800/80 shadow-2xl space-y-4">
                    <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <span class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-lg">
                            🔍
                        </span>
                        <h3 class="font-black text-base text-slate-900 dark:text-white font-myanmar">
                            {{ __('messages.track_service_technician_notes') }}
                        </h3>
                    </div>

                    <div class="space-y-3 text-xs sm:text-sm">
                        @if ($job->diagnosis)
                            <div class="p-4 rounded-xl bg-indigo-50/50 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900/50 space-y-1">
                                <span class="font-bold text-indigo-900 dark:text-indigo-300 block">စစ်ဆေးတွေ့ရှိချက် (Diagnosis):</span>
                                <p class="text-slate-700 dark:text-slate-300 leading-relaxed font-myanmar whitespace-pre-line">{{ $job->diagnosis }}</p>
                            </div>
                        @endif

                        @if ($job->estimated_completion)
                            <div class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60">
                                <span class="text-slate-500 font-myanmar">{{ __('messages.track_service_estimated_completion') }}:</span>
                                <span class="font-bold text-teal-600 dark:text-teal-400">{{ $job->estimated_completion->format('d M Y') }}</span>
                            </div>
                        @endif

                        @if ($job->warranty_notes)
                            <div class="flex items-center justify-between p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                <span class="font-bold font-myanmar">🛡️ အာမခံသတ်မှတ်ချက်:</span>
                                <span class="font-bold">{{ $job->warranty_notes }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Line Items Table --}}
            @if ($job->items->isNotEmpty())
                <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 sm:p-6 border border-slate-200/90 dark:border-slate-800/80 shadow-2xl space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center text-lg">
                                ⚙️
                            </span>
                            <h3 class="font-black text-base text-slate-900 dark:text-white font-myanmar">
                                အပိုပစ္စည်းနှင့် ဝန်ဆောင်ခစာရင်း (Parts & Services)
                            </h3>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs sm:text-sm">
                            <thead>
                                <tr class="text-slate-400 border-b border-slate-100 dark:border-slate-800">
                                    <th class="pb-2">အကြောင်းအရာ</th>
                                    <th class="pb-2 text-center">အရေအတွက်</th>
                                    <th class="pb-2 text-right">ကျသင့်ငွေ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach ($job->items as $item)
                                    <tr class="py-2.5">
                                        <td class="py-2.5">
                                            <span class="font-bold text-slate-800 dark:text-slate-200 block">{{ $item->name }}</span>
                                            <span class="text-[11px] text-slate-400 uppercase font-mono">{{ $item->item_type }}</span>
                                        </td>
                                        <td class="py-2.5 text-center font-bold text-slate-600 dark:text-slate-300">
                                            x{{ $item->quantity }}
                                        </td>
                                        <td class="py-2.5 text-right font-black text-slate-900 dark:text-white font-mono">
                                            {{ number_format((float) $item->subtotal) }} MMK
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Timeline History --}}
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 sm:p-6 border border-slate-200/90 dark:border-slate-800/80 shadow-2xl space-y-4">
                <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                    <span class="w-8 h-8 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center text-lg">
                        ⏱️
                    </span>
                    <h3 class="font-black text-base text-slate-900 dark:text-white font-myanmar">
                        {{ __('messages.track_service_timeline') }}
                    </h3>
                </div>

                <div class="space-y-4 pt-2">
                    @forelse ($job->statusHistory as $history)
                        <div class="flex items-start gap-3 relative">
                            <div class="w-2.5 h-2.5 rounded-full bg-teal-500 mt-1.5 shrink-0 ring-4 ring-teal-100 dark:ring-teal-950"></div>
                            <div class="space-y-0.5 flex-1 text-xs sm:text-sm">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-black text-slate-800 dark:text-slate-200">
                                        {{ __('messages.repair_status_' . $history->status) }}
                                    </span>
                                    <span class="text-[11px] text-slate-400 font-mono">
                                        {{ $history->created_at->format('d M Y, h:i A') }}
                                    </span>
                                </div>
                                @if ($history->note)
                                    <p class="text-xs text-slate-500 dark:text-slate-400 font-myanmar bg-slate-50 dark:bg-slate-800/60 p-2.5 rounded-xl mt-1">
                                        {{ $history->note }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 text-center py-4">မှတ်တမ်း မရှိသေးပါ။</p>
                    @endforelse
                </div>
            </div>

        </div>

        {{-- Right Column: Charges & Contact Shop --}}
        <div class="space-y-6">

            {{-- Charges & Payments Card --}}
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 sm:p-6 border border-slate-200/90 dark:border-slate-800/80 shadow-2xl space-y-4">
                <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                    <span class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-lg">
                        💰
                    </span>
                    <h3 class="font-black text-base text-slate-900 dark:text-white font-myanmar">
                        {{ __('messages.track_service_cost_breakdown') }}
                    </h3>
                </div>

                <div class="space-y-2.5 text-xs sm:text-sm">
                    @if ($job->final_charge !== null)
                        <div class="flex justify-between py-1 border-b border-slate-100 dark:border-slate-800">
                            <span class="text-slate-500">သတ်မှတ်ကျသင့်ငွေ:</span>
                            <span class="font-black text-slate-900 dark:text-white font-mono">{{ number_format((float) $job->final_charge) }} MMK</span>
                        </div>
                    @elseif ($job->estimated_charge > 0)
                        <div class="flex justify-between py-1 border-b border-slate-100 dark:border-slate-800">
                            <span class="text-slate-500">ခန့်မှန်းကျသင့်ငွေ:</span>
                            <span class="font-bold text-slate-700 dark:text-slate-300 font-mono">{{ number_format((float) $job->estimated_charge) }} MMK</span>
                        </div>
                    @endif

                    <div class="flex justify-between py-1 border-b border-slate-100 dark:border-slate-800">
                        <span class="text-slate-500">ပေးချေပြီးငွေ (Paid):</span>
                        <span class="font-bold text-emerald-600 dark:text-emerald-400 font-mono">{{ number_format($job->paidAmount()) }} MMK</span>
                    </div>

                    <div class="p-3.5 rounded-xl bg-gradient-to-r from-teal-50 to-emerald-50 dark:from-teal-950/40 dark:to-emerald-950/40 border border-teal-200 dark:border-teal-800/80 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-teal-800 dark:text-teal-300 block font-myanmar">ပေးရန်ကျန်ငွေ (Balance)</span>
                            <span class="text-[10px] text-teal-600 dark:text-teal-400 font-mono">Outstanding</span>
                        </div>
                        <span class="text-base sm:text-lg font-black text-teal-700 dark:text-teal-300 font-mono">
                            {{ number_format($job->outstanding()) }} MMK
                        </span>
                    </div>
                </div>
            </div>

            {{-- Contact Shop Card --}}
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 sm:p-6 border border-slate-200/90 dark:border-slate-800/80 shadow-2xl space-y-4">
                <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                    <span class="w-8 h-8 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center text-lg">
                        📞
                    </span>
                    <h3 class="font-black text-base text-slate-900 dark:text-white font-myanmar">
                        {{ __('messages.track_service_contact_shop') }}
                    </h3>
                </div>

                <p class="text-xs text-slate-500 dark:text-slate-400 font-myanmar leading-relaxed">
                    စက်ပြင်ဆင်မှုနှင့်ပတ်သက်၍ မေးမြန်းလိုပါက ဆိုင်သို့ တိုက်ရိုက် မက်ဆေ့ခ်ျပေးပို့နိုင်ပါသည်။
                </p>

                <div class="space-y-2 pt-1">
                    @if ($viberUrl)
                        <a href="{{ $viberUrl }}" data-ios-href="{{ $viberIosUrl ?? $viberUrl }}" target="_blank" rel="noopener noreferrer"
                           class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs sm:text-sm transition shadow-lg shadow-purple-600/20 active:scale-98">
                            <x-brand-icon brand="viber" class="h-4 w-4 shrink-0"/>
                            <span>Viber မှ မေးမြန်းမည်</span>
                        </a>
                    @endif

                    @if ($telegramUrl)
                        <a href="{{ $telegramUrl }}" target="_blank" rel="noopener noreferrer"
                           class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-sky-500 hover:bg-sky-400 text-white font-bold text-xs sm:text-sm transition shadow-lg shadow-sky-500/20 active:scale-98">
                            <x-brand-icon brand="telegram" class="h-4 w-4 shrink-0"/>
                            <span>Telegram မှ မေးမြန်းမည်</span>
                        </a>
                    @endif

                    @if ($setting?->phone ?? $store->phone)
                        <a href="tel:{{ $setting?->phone ?? $store->phone }}"
                           class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs sm:text-sm transition active:scale-98">
                            <span>📞</span>
                            <span>ဖုန်းခေါ်ဆိုမည် ({{ $setting?->phone ?? $store->phone }})</span>
                        </a>
                    @endif
                </div>

                {{-- Shop Address Details --}}
                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 text-xs text-slate-500 dark:text-slate-400 space-y-1 font-myanmar">
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
