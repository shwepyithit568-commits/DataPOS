@extends('layouts.admin.app')

@section('title', __('messages.x_report_reading') . ' — ' . $store->name)

@section('content')
    <div class="w-full space-y-2 p-1 sm:p-2">

        {{-- Top Navigation & Action Toolbar --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 p-2 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs">
            <div class="flex items-center gap-2">
                <a href="{{ route('pos.closing.index', ['store_slug' => $store->slug, 'date' => $date->toDateString()]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                    ← {{ __('messages.back') }}
                </a>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-black uppercase bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                    {{ __('messages.x_report') }}
                </span>
                <h1 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100">
                    {{ __('messages.x_report_reading') }} — {{ $date->format('d M Y') }}
                </h1>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <form method="GET" action="{{ route('pos.closing.x-report', ['store_slug' => $store->slug]) }}" class="flex items-center gap-1">
                    <input type="date" name="date" value="{{ $date->toDateString() }}" max="{{ today()->toDateString() }}"
                           class="h-7 px-2 text-xs font-semibold rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
                    <button type="submit" class="h-7 px-2.5 text-xs font-bold rounded-lg bg-sky-600 hover:bg-sky-500 text-white transition">
                        →
                    </button>
                </form>

                {{-- Print Dropdown / Link --}}
                <a href="{{ route('pos.closing.print', ['store_slug' => $store->slug, 'type' => 'x', 'date' => $date->toDateString(), 'layout' => '80mm']) }}"
                   target="_blank"
                   class="inline-flex items-center gap-1 h-7 px-3 text-xs font-black rounded-lg text-white bg-sky-600 hover:bg-sky-500 transition shadow-2xs">
                    🖨️ {{ __('messages.print') }}
                </a>
            </div>
        </div>

        {{-- Reading-Only Banner --}}
        <div class="p-2.5 rounded-xl border border-amber-300 dark:border-amber-800 bg-amber-50/70 dark:bg-amber-950/30 text-amber-900 dark:text-amber-200 flex items-center justify-between gap-3 text-xs">
            <div class="flex items-center gap-2">
                <span class="text-base">ℹ️</span>
                <div>
                    <span class="font-bold">{{ __('messages.reading_only') }}:</span>
                    <span class="opacity-90">ဤစာမျက်နှာသည် လက်ရှိအချိန်အထိ အရောင်းနှင့် ငွေစာရင်းအား ကြည့်ရှုစစ်ဆေးခြင်း (Interim Reading) သာဖြစ်ပြီး၊ မည်သည့် စာရင်းပိတ်သိမ်းမှု သို့မဟုတ် Database ပြောင်းလဲမှုမျှ မပြုလုပ်ပါ။</span>
                </div>
            </div>
            <div class="text-[11px] font-mono opacity-80 shrink-0">
                {{ $xData['generated_at']->format('H:i:s') }}
            </div>
        </div>

        {{-- Centered Row-based Stat Cards --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-1 sm:gap-1.5">
            <div class="p-2 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center justify-center gap-2.5 shadow-2xs">
                <div class="w-8 h-8 rounded-lg bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300 flex items-center justify-center text-sm font-bold">
                    💵
                </div>
                <div class="text-center">
                    <div class="text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('messages.expected_cash') }}</div>
                    <div class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 font-mono">
                        {{ format_currency((float) ($xData['totals']['expected']['cash'] ?? 0), $store) }}
                    </div>
                </div>
            </div>

            <div class="p-2 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center justify-center gap-2.5 shadow-2xs">
                <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300 flex items-center justify-center text-sm font-bold">
                    📈
                </div>
                <div class="text-center">
                    <div class="text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('messages.net_sales') }}</div>
                    <div class="text-xs sm:text-sm font-black text-emerald-600 dark:text-emerald-400 font-mono">
                        {{ format_currency((float) ($xData['totals']['summary']['net_sales'] ?? 0), $store) }}
                    </div>
                </div>
            </div>

            <div class="p-2 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center justify-center gap-2.5 shadow-2xs">
                <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300 flex items-center justify-center text-sm font-bold">
                    🧾
                </div>
                <div class="text-center">
                    <div class="text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('messages.sales_count') }}</div>
                    <div class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 font-mono">
                        {{ $xData['sales_count'] }}
                    </div>
                </div>
            </div>

            <div class="p-2 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center justify-center gap-2.5 shadow-2xs">
                <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-700 dark:bg-purple-500/15 dark:text-purple-300 flex items-center justify-center text-sm font-bold">
                    ⏱️
                </div>
                <div class="text-center">
                    <div class="text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('messages.shifts_count') }}</div>
                    <div class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 font-mono">
                        {{ $xData['shifts_count'] }} ({{ $xData['open_shifts_count'] }} ဖွင့်ဆဲ)
                    </div>
                </div>
            </div>
        </div>

        {{-- Payment Methods & Drawer Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-1.5">

            {{-- Drawer Math Breakdown --}}
            <div class="p-3 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs space-y-2">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                    <h2 class="text-xs font-black uppercase text-slate-800 dark:text-slate-200">
                        💵 {{ __('messages.closing_drawer_heading') }}
                    </h2>
                    <span class="text-[10px] font-mono text-slate-400">Shift Drawer Math</span>
                </div>

                <div class="space-y-1 text-xs">
                    <div class="flex justify-between py-1 border-b border-slate-50 dark:border-slate-800/60">
                        <span class="text-slate-500">{{ __('messages.opening_float') }}</span>
                        <span class="font-mono font-bold">{{ format_currency((float) ($xData['totals']['opening_amount'] ?? 0), $store) }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50 dark:border-slate-800/60">
                        <span class="text-slate-500">{{ __('messages.cash_sales') }}</span>
                        <span class="font-mono font-bold text-emerald-600">+{{ format_currency((float) ($xData['totals']['summary']['cash_sales'] ?? 0), $store) }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50 dark:border-slate-800/60">
                        <span class="text-slate-500">{{ __('messages.cash_in') }}</span>
                        <span class="font-mono font-bold text-sky-600">+{{ format_currency((float) ($xData['totals']['summary']['cash_in'] ?? 0), $store) }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50 dark:border-slate-800/60">
                        <span class="text-slate-500">{{ __('messages.cash_refunds') }}</span>
                        <span class="font-mono font-bold text-rose-600">-{{ format_currency((float) ($xData['totals']['summary']['cash_refunds'] ?? 0), $store) }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50 dark:border-slate-800/60">
                        <span class="text-slate-500">{{ __('messages.cash_out') }}</span>
                        <span class="font-mono font-bold text-rose-600">-{{ format_currency((float) ($xData['totals']['summary']['cash_out'] ?? 0), $store) }}</span>
                    </div>
                    <div class="flex justify-between pt-2 text-sm font-black border-t border-slate-200 dark:border-slate-700">
                        <span>{{ __('messages.expected_cash') }}</span>
                        <span class="font-mono text-sky-600 dark:text-sky-400">{{ format_currency((float) ($xData['totals']['expected']['cash'] ?? 0), $store) }}</span>
                    </div>
                </div>
            </div>

            {{-- Payment Methods Reconciliation --}}
            <div class="p-3 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs space-y-2">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                    <h2 class="text-xs font-black uppercase text-slate-800 dark:text-slate-200">
                        💳 {{ __('messages.payment_methods') ?? 'Payment Methods' }}
                    </h2>
                    <span class="text-[10px] font-mono text-slate-400">All Channels</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-slate-400 border-b border-slate-100 dark:border-slate-800">
                                <th class="text-left py-1">{{ __('messages.payment_method') }}</th>
                                <th class="text-right py-1">{{ __('messages.expected') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-800/60">
                            @foreach (\App\POS\Models\DailyClosing::expectedMethods() as $method)
                                @php
                                    $isCredit = $method === 'credit';
                                    $amount = (float) ($xData['totals']['expected'][$method] ?? 0);
                                @endphp
                                <tr>
                                    <td class="py-1.5 font-bold">
                                        {{ __('messages.payment_' . $method) }}
                                        @if ($isCredit)
                                            <span class="text-[10px] text-slate-400 block font-normal">{{ __('messages.closing_credit_info') }}</span>
                                        @endif
                                    </td>
                                    <td class="py-1.5 text-right font-mono font-bold">{{ format_currency($amount, $store) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
@endsection
