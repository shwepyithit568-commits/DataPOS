@extends('layouts.admin.app')

@section('title', __('messages.x_report_reading') . ' — ' . $store->name)

@section('content')
    <div x-data="{ showExpenseModal: false }" class="w-full space-y-2 p-1 sm:p-2">

        {{-- Top Navigation & Action Toolbar --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 p-2 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs">
            <div class="flex items-center gap-2">
                <a href="{{ route('pos.closing.index', ['store_slug' => $store->slug, 'date' => $date->toDateString()]) }}"
                   class="sf-btn-3d inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition">
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
                    <button type="submit" class="sf-btn-3d-primary h-7 px-2.5 text-xs font-bold rounded-lg transition">
                        →
                    </button>
                </form>

                {{-- Print Dropdown / Link --}}
                <a href="{{ route('pos.closing.print', ['store_slug' => $store->slug, 'type' => 'x', 'date' => $date->toDateString(), 'layout' => '80mm']) }}"
                   target="_blank"
                   class="sf-btn-3d-primary inline-flex items-center gap-1 h-7 px-3 text-xs font-black rounded-lg transition shadow-2xs">
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
                    <div class="flex items-center justify-between py-1 border-b border-slate-50 dark:border-slate-800/60">
                        <div class="flex items-center gap-1.5">
                            <span class="text-slate-500 font-semibold">{{ __('messages.drawer_expenses') }}</span>
                            @if (!empty($xData['totals']['expenses']) && $xData['totals']['expenses']->count() > 0)
                                <button type="button" id="btn-view-expenses" @click="showExpenseModal = true"
                                        class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/50 dark:hover:bg-rose-900/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 transition cursor-pointer">
                                    {{ __('messages.view_expense_details') }} ({{ $xData['totals']['expenses']->count() }})
                                </button>
                            @endif
                        </div>
                        <span class="font-mono font-bold text-rose-600">-{{ format_currency((float) ($xData['totals']['summary']['drawer_expenses'] ?? 0), $store) }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50 dark:border-slate-800/60">
                        <span class="text-slate-500">{{ __('messages.other_cash_out') }}</span>
                        <span class="font-mono font-bold text-rose-600">-{{ format_currency((float) ($xData['totals']['summary']['cash_out'] ?? 0), $store) }}</span>
                    </div>
                    <div class="flex justify-between pt-2 text-sm font-black border-t border-slate-200 dark:border-slate-700">
                        <span>{{ __('messages.expected_cash') }}</span>
                        <span class="font-mono text-sky-600 dark:text-sky-400">{{ format_currency((float) ($xData['totals']['expected']['cash'] ?? 0), $store) }}</span>
                    </div>
                </div>

                @if (!empty($xData['totals']['summary']['other_cash_expenses']) && (float) $xData['totals']['summary']['other_cash_expenses'] > 0)
                    <div class="mt-2 p-2 rounded-lg bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-700 text-[11px] text-slate-600 dark:text-slate-300">
                        <div class="flex items-center justify-between font-bold">
                            <span>ℹ️ {{ __('messages.other_cash_expenses') }}:</span>
                            <span class="font-mono">{{ format_currency((float) $xData['totals']['summary']['other_cash_expenses'], $store) }}</span>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-0.5">{{ __('messages.non_drawer_expenses_notice') }}</p>
                    </div>
                @endif
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

        {{-- Expense Detail Modal --}}
        <div x-show="showExpenseModal" x-cloak
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3"
             @keydown.escape.window="showExpenseModal = false">
            <div class="bg-white dark:bg-slate-900 rounded-xl max-w-2xl w-full border border-slate-200 dark:border-slate-800 shadow-xl overflow-hidden"
                 @click.away="showExpenseModal = false">
                <div class="px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-base">📋</span>
                        <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100">
                            {{ __('messages.expense_details') }} — {{ $date->format('d M Y') }}
                        </h3>
                    </div>
                    <button type="button" @click="showExpenseModal = false"
                            class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-sm font-bold cursor-pointer">
                        ✕
                    </button>
                </div>

                <div class="px-4 py-2 bg-slate-100/70 dark:bg-slate-800/40 border-b border-slate-200 dark:border-slate-700 flex flex-wrap items-center justify-between gap-2 text-xs">
                    <div class="flex items-center gap-3">
                        <div>
                            <span class="text-slate-500 dark:text-slate-400 text-[11px]">{{ __('messages.drawer_expenses') }}:</span>
                            <span class="font-mono font-bold text-rose-600 dark:text-rose-400 ml-1">{{ format_currency((float) ($xData['totals']['summary']['drawer_expenses'] ?? 0), $store) }}</span>
                        </div>
                        @if (!empty($xData['totals']['summary']['other_cash_expenses']) && (float) $xData['totals']['summary']['other_cash_expenses'] > 0)
                            <div>
                                <span class="text-slate-500 dark:text-slate-400 text-[11px]">{{ __('messages.other_cash_expenses') }}:</span>
                                <span class="font-mono font-bold text-slate-700 dark:text-slate-300 ml-1">{{ format_currency((float) ($xData['totals']['summary']['other_cash_expenses'] ?? 0), $store) }}</span>
                            </div>
                        @endif
                    </div>
                    <span class="text-[10px] text-slate-400">
                        {{ __('messages.non_drawer_expenses_notice') }}
                    </span>
                </div>

                <div class="p-3 max-h-96 overflow-y-auto">
                    @if (!empty($xData['totals']['expenses']) && $xData['totals']['expenses']->count() > 0)
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="text-slate-400 border-b border-slate-100 dark:border-slate-800 text-[11px]">
                                    <th class="text-left py-1">Ref / No</th>
                                    <th class="text-left py-1">{{ __('messages.title') ?? 'Title' }}</th>
                                    <th class="text-left py-1">{{ __('messages.payment_source') }}</th>
                                    <th class="text-left py-1">{{ __('messages.shift') ?? 'Shift' }}</th>
                                    <th class="text-right py-1">{{ __('messages.amount') ?? 'Amount' }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach ($xData['totals']['expenses'] as $exp)
                                    @php
                                        $isDrawer = ($exp->payment_method === 'cash' && ($exp->payment_source === 'drawer' || $exp->cashier_shift_id));
                                    @endphp
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50">
                                        <td class="py-1.5 font-mono text-[11px] text-slate-500">
                                            {{ $exp->expense_number }}
                                            <div class="text-[10px] text-slate-400">{{ $exp->created_at?->format('H:i') }}</div>
                                        </td>
                                        <td class="py-1.5 font-semibold text-slate-900 dark:text-slate-100">
                                            {{ $exp->title }}
                                            @if ($exp->category)
                                                <span class="block text-[10px] text-slate-400">{{ $exp->category->name }}</span>
                                            @endif
                                        </td>
                                        <td class="py-1.5">
                                            @if ($isDrawer)
                                                @if ($exp->cashier_shift_id)
                                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300" title="{{ __('messages.source_drawer_confirmed') }}">
                                                        <span>✅</span>
                                                        <span>{{ __('messages.source_drawer_confirmed') }}</span>
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300" title="{{ __('messages.source_drawer_unconfirmed') }}">
                                                        <span>⚠️</span>
                                                        <span>{{ __('messages.source_drawer_unconfirmed') }}</span>
                                                    </span>
                                                @endif
                                            @else
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                                    {{ \App\POS\Models\Expense::PAYMENT_SOURCES[$exp->payment_source] ?? ucfirst($exp->payment_source ?? 'other') }}
                                                </span>
                                                <span class="block text-[9px] text-slate-400">{{ __('messages.non_drawer_not_deducted') }}</span>
                                            @endif
                                        </td>
                                        <td class="py-1.5 font-mono text-[11px] text-slate-500">
                                            @if ($exp->shift)
                                                <span>{{ $exp->shift->register_name ?? 'Register' }} (#{{ $exp->shift->id }})</span>
                                            @else
                                                <span class="text-slate-400">—</span>
                                            @endif
                                        </td>
                                        <td class="py-1.5 text-right font-mono font-bold {{ $isDrawer ? 'text-rose-600' : 'text-slate-500 dark:text-slate-400' }}">
                                            {{ format_currency((float) $exp->amount, $store) }}
                                            @if (! $isDrawer)
                                                <span class="block text-[9px] font-normal text-slate-400">({{ __('messages.not_deducted') }})</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-center text-xs text-slate-400 py-4">{{ __('messages.no_records_found') }}</p>
                    @endif
                </div>

                <div class="px-4 py-2 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-200 dark:border-slate-700 flex justify-end">
                    <button type="button" @click="showExpenseModal = false"
                            class="sf-btn-3d px-3 py-1 text-xs font-bold rounded-lg cursor-pointer">
                        {{ __('messages.close') ?? 'Close' }}
                    </button>
                </div>
            </div>
        </div>

    </div>
@endsection
