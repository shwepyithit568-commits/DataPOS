{{-- ── POS Reporting Modal (Today | Registers | Debt | Repairs) ────── --}}
<div x-show="reportingModalOpen" x-cloak x-transition.opacity class="fixed inset-0 z-[95] flex items-center justify-center p-0 sm:p-3 md:p-4 lg:p-6"
     @keydown.escape.window="reportingModalOpen = false">
    <div class="absolute inset-0 bg-black/40 dark:bg-black/60 backdrop-blur-xs transition-opacity" @click="reportingModalOpen = false"></div>
    <div class="relative w-full h-full min-h-dvh max-h-dvh sm:h-auto sm:min-h-[500px] sm:max-h-[90dvh] sm:w-[96vw] sm:max-w-6xl xl:max-w-7xl 2xl:max-w-[1520px] rounded-none sm:rounded-2xl bg-white dark:bg-slate-900 border-0 sm:border border-slate-200 dark:border-slate-800 shadow-2xl dark:shadow-black/80 flex flex-col overflow-hidden">
        
        {{-- Modal Header: Dedicated Topic Title & Icon + Close Button (No tabs) --}}
        <div class="flex items-center justify-between gap-3 px-4 sm:px-6 py-3 sm:py-3.5 border-b border-slate-200 dark:border-slate-800 bg-slate-100/90 dark:bg-slate-800/80 shrink-0">
            {{-- Today Sales Title --}}
            <div x-show="activeTab === 'today'" class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-xl bg-blue-600/10 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/></svg>
                </div>
                <div class="flex items-center gap-2 min-w-0">
                    <h3 class="text-sm sm:text-base font-black text-slate-900 dark:text-white truncate">{{ __('messages.pos_tab_today') }}</h3>
                    @if ($todaySales->isNotEmpty())
                        <span class="px-2 py-0.5 rounded-full text-xs font-black bg-blue-600 text-white leading-none shrink-0">{{ $todaySales->count() }}</span>
                    @endif
                </div>
            </div>

            @if ($store->hasCapability(\App\Capabilities\Capability::OPERATIONS_CASHIER_SHIFTS))
            {{-- Registers Title --}}
            <div x-show="activeTab === 'registers'" class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-xl bg-emerald-600/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16v5H4V5Zm2 5v9h12v-9M7 12h2m-2 4h2m5-4h3m-3 4h3"/></svg>
                </div>
                <div class="flex items-center gap-2 min-w-0">
                    <h3 class="text-sm sm:text-base font-black text-slate-900 dark:text-white truncate">{{ __('messages.pos_tab_registers') }}</h3>
                </div>
            </div>
            @endif

            {{-- Debt Title --}}
            <div x-show="activeTab === 'debt'" class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-xl bg-amber-600/10 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 11V7a4 4 0 0 0-8 0v4M5 9h14l1 12H4L5 9Z"/></svg>
                </div>
                <div class="flex items-center gap-2 min-w-0">
                    <h3 class="text-sm sm:text-base font-black text-slate-900 dark:text-white truncate">{{ __('messages.pos_tab_debt') }}</h3>
                    @if (!empty($outstanding) && count($outstanding) > 0)
                        <span class="px-2 py-0.5 rounded-full text-xs font-black bg-amber-500 text-white leading-none shrink-0">{{ count($outstanding) }}</span>
                    @endif
                </div>
            </div>

            @if ($store->hasCapability(\App\Capabilities\Capability::SERVICE_REPAIR_JOBS))
            {{-- Repairs Title --}}
            <div x-show="activeTab === 'repairs'" class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-xl bg-indigo-600/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                </div>
                <div class="flex items-center gap-2 min-w-0">
                    <h3 class="text-sm sm:text-base font-black text-slate-900 dark:text-white truncate">{{ __('messages.pos_tab_repairs') }}</h3>
                    @if (isset($activeRepairsCount) && $activeRepairsCount > 0)
                        <span class="px-2 py-0.5 rounded-full text-xs font-black bg-amber-500 text-white leading-none shrink-0">{{ $activeRepairsCount }}</span>
                    @endif
                </div>
            </div>
            @endif

            {{-- Actions: External Link & Close Button --}}
            <div class="flex items-center gap-2 shrink-0 ml-auto">
                @if ($store->hasCapability(\App\Capabilities\Capability::SERVICE_REPAIR_JOBS))
                    <a x-show="activeTab === 'repairs'" href="{{ url('/store/' . $store->slug . '/admin/repairs') }}"
                       class="sf-btn-3d hidden sm:inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 rounded-lg transition cursor-pointer whitespace-nowrap"
                       title="{{ __('messages.sidebar_repair_center') }}">
                        <span>admin/repairs</span>
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    </a>
                @endif

                <button type="button" @click="reportingModalOpen = false" aria-label="{{ __('messages.close') }}"
                        class="shrink-0 w-8 h-8 rounded-xl bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-black hover:bg-slate-300 dark:hover:bg-slate-600 transition flex items-center justify-center cursor-pointer text-sm">✕</button>
            </div>
        </div>

        {{-- Modal Body: Full scrollable area with safe-area padding on mobile --}}
        <div class="p-3 sm:p-6 overflow-y-auto space-y-4 sm:space-y-6 flex-1 overscroll-contain pb-[calc(1.5rem+env(safe-area-inset-bottom,0px))] sm:pb-6">
            {{-- TODAY: posted sales --}}
            <div x-show="activeTab === 'today'">
                @if ($todaySales->isNotEmpty())
                    {{-- Mobile View: Responsive Card List (sm:hidden) --}}
                    <div class="sm:hidden space-y-2.5">
                        @foreach ($todaySales as $sale)
                            @php $saleDebt = $sale->payments->firstWhere('method', 'credit')?->amount ?? '0'; @endphp
                            <div class="rounded-xl border border-slate-200 dark:border-slate-700/80 bg-white dark:bg-slate-800 p-3 space-y-2.5 shadow-sm">
                                {{-- Row 1: Receipt No + Time + Total --}}
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        <span class="font-mono font-bold text-xs text-blue-600 dark:text-blue-400 truncate">{{ $sale->receipt_number }}</span>
                                        <span class="text-[11px] text-slate-500 dark:text-slate-400 shrink-0">· {{ $sale->created_at->format('H:i') }}</span>
                                    </div>
                                    <span class="font-black text-sm text-slate-900 dark:text-white shrink-0">
                                        {{ format_currency((float) $sale->total, $store) }}
                                    </span>
                                </div>

                                {{-- Row 2: Customer & Cashier info --}}
                                <div class="flex items-center justify-between text-xs gap-2">
                                    <div class="min-w-0 truncate">
                                        @if ($sale->customer)
                                            <span class="font-bold text-slate-800 dark:text-slate-100">👤 {{ $sale->customer->name }}</span>
                                            @if ((float) $saleDebt > 0)
                                                <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-900 dark:bg-amber-950/90 dark:text-amber-300 border border-amber-300 dark:border-amber-700/80 ml-1">
                                                    {{ __('messages.debt') }} {{ format_currency((float) $saleDebt, $store) }}
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-slate-500 dark:text-slate-300 font-medium">👤 {{ __('messages.retail_customer') }}</span>
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-slate-600 dark:text-slate-300 font-medium shrink-0">
                                        {{ $sale->cashier?->name }}
                                    </div>
                                </div>

                                {{-- Row 3: Items Purchased badge --}}
                                <div class="text-xs text-slate-700 dark:text-slate-200 bg-slate-50 dark:bg-slate-900/90 rounded-lg px-2.5 py-1.5 border border-slate-200 dark:border-slate-700/80 leading-relaxed line-clamp-2">
                                    🛒 {{ $sale->items->take(4)->pluck('product_name')->implode(', ') }}{{ $sale->items->count() > 4 ? '…' : '' }}
                                </div>

                                {{-- Row 4: Payments Badges + Action Buttons --}}
                                <div class="flex items-center justify-between gap-2 pt-1 border-t border-slate-200 dark:border-slate-700/60">
                                    <div class="flex flex-wrap gap-1 min-w-0">
                                        @foreach ($sale->payments as $payment)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-100 border border-slate-200 dark:border-slate-600">
                                                {{ $payment->method }} {{ format_currency((float) $payment->amount, $store) }}
                                            </span>
                                        @endforeach
                                    </div>

                                    <div class="inline-flex items-center gap-1.5 shrink-0">
                                        @if ($sale->status !== 'refunded')
                                            <a href="{{ url('/store/' . $store->slug . '/pos/sales/' . $sale->id . '/refund') }}"
                                               class="sf-btn-3d-danger inline-flex items-center justify-center w-7 h-7 rounded-lg cursor-pointer transition text-xs"
                                               title="{{ __('messages.refund_sale') }}">
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 14l-4-4m0 0l4-4m-4 4h11a4 4 0 010 8h-1"/></svg>
                                            </a>
                                        @endif
                                        <a href="{{ url('/store/' . $store->slug . '/pos/sales/' . $sale->id . '/receipt') }}" target="_blank"
                                           class="sf-btn-3d inline-flex items-center justify-center w-7 h-7 rounded-lg text-blue-600 dark:text-blue-400 cursor-pointer transition"
                                           title="{{ __('messages.print_receipt') }}">
                                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Desktop Table View (hidden on mobile, visible on sm+) --}}
                    <div class="hidden sm:block overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700/80">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-800 text-xs text-slate-700 dark:text-slate-200">
                                <tr>
                                    <th class="text-left px-3 py-2 font-bold">{{ __('messages.receipt_number') }}</th>
                                    <th class="text-left px-3 py-2 font-bold">{{ __('messages.cashier') }}</th>
                                    <th class="text-left px-3 py-2 font-bold">{{ __('messages.customer') }}</th>
                                    <th class="text-left px-3 py-2 font-bold">{{ __('messages.cart') }}</th>
                                    <th class="text-right px-3 py-2 font-bold">{{ __('messages.payments') }}</th>
                                    <th class="text-right px-3 py-2 font-bold">{{ __('messages.total') }}</th>
                                    <th class="text-center px-3 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                                @foreach ($todaySales as $sale)
                                    @php $saleDebt = $sale->payments->firstWhere('method', 'credit')?->amount ?? '0'; @endphp
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition">
                                        <td class="px-3 py-2.5 font-mono font-bold text-blue-600 dark:text-blue-400">{{ $sale->receipt_number }}</td>
                                        <td class="px-3 py-2.5 text-slate-900 dark:text-slate-100 font-medium">{{ $sale->cashier?->name }}</td>
                                        <td class="px-3 py-2.5">
                                            @if ($sale->customer)
                                                <span class="font-bold text-slate-900 dark:text-slate-100">{{ $sale->customer->name }}</span>
                                                @if ((float) $saleDebt > 0)
                                                    <span class="block text-[10px] font-bold text-amber-600 dark:text-amber-400">{{ __('messages.debt') }} {{ format_currency((float) $saleDebt, $store) }}</span>
                                                @endif
                                            @else
                                                <span class="text-slate-400 dark:text-slate-500">—</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2.5 text-xs text-slate-600 dark:text-slate-300">
                                            {{ $sale->items->take(3)->pluck('product_name')->implode(', ') }}{{ $sale->items->count() > 3 ? '…' : '' }}
                                        </td>
                                        <td class="px-3 py-2.5 text-xs">
                                            @foreach ($sale->payments as $payment)
                                                <span class="inline-block px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-100 border border-slate-200 dark:border-slate-600 font-mono mr-1">{{ $payment->method }} {{ format_currency((float) $payment->amount, $store) }}</span>
                                            @endforeach
                                        </td>
                                        <td class="px-3 py-2.5 text-right font-black text-slate-900 dark:text-white">{{ format_currency((float) $sale->total, $store) }}</td>
                                        <td class="px-3 py-2.5 text-center whitespace-nowrap">
                                            <div class="inline-flex items-center gap-1.5">
                                                @if ($sale->status !== 'refunded')
                                                    <a href="{{ url('/store/' . $store->slug . '/pos/sales/' . $sale->id . '/refund') }}"
                                                       class="sf-btn-3d-danger inline-flex items-center justify-center w-8 h-8 rounded-lg cursor-pointer transition"
                                                       title="{{ __('messages.refund_sale') }}">
                                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 14l-4-4m0 0l4-4m-4 4h11a4 4 0 010 8h-1"/></svg>
                                                    </a>
                                                @endif
                                                <a href="{{ url('/store/' . $store->slug . '/pos/sales/' . $sale->id . '/receipt') }}" target="_blank"
                                                   class="sf-btn-3d inline-flex items-center justify-center w-8 h-8 rounded-lg text-blue-600 dark:text-blue-400 cursor-pointer transition"
                                                   title="{{ __('messages.print_receipt') }}">
                                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="rounded-xl border border-dashed border-slate-300 dark:border-slate-700 p-8 text-center text-sm text-slate-500 dark:text-slate-400">
                        {{ __('messages.no_sales_today') }}
                    </div>
                @endif
            </div>

            @if ($store->hasCapability(\App\Capabilities\Capability::OPERATIONS_CASHIER_SHIFTS))
            {{-- REGISTERS: shift card + today's closing summary --}}
            <div x-show="activeTab === 'registers'" class="space-y-1">
                @if ($openShift)
                    <section id="pos-shift-card" class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm scroll-mt-24">
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('messages.open_shift') }}</p>
                                <h2 class="text-lg font-black mt-0.5">{{ $openShift->register_name }}</h2>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                    {{ __('messages.cashier') }}: {{ $openShift->cashier?->name }} ·
                                    {{ __('messages.opened_at') }}: {{ $openShift->opened_at->format('H:i') }}
                                </p>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300">● {{ __('messages.shift_open') }}</span>
                        </div>

                        <dl class="grid grid-cols-2 gap-3 text-sm mb-5">
                            <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-3">
                                <dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.opening_cash') }}</dt>
                                <dd class="font-black mt-0.5">{{ format_currency($openShift->opening_cash, $store) }}</dd>
                            </div>
                            <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-3">
                                <dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.cash_in_out') }}</dt>
                                <dd class="font-black mt-0.5 text-blue-600 dark:text-blue-400">+{{ format_currency($openShift->cash_in, $store) }} / −{{ format_currency($openShift->cash_out, $store) }}</dd>
                            </div>
                            <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-3">
                                <dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.cash_sales') }}</dt>
                                <dd class="font-black mt-0.5">{{ format_currency($openShift->cash_sales, $store) }}</dd>
                            </div>
                            <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-3">
                                <dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.cash_refunds') }}</dt>
                                <dd class="font-black mt-0.5">{{ format_currency($openShift->cash_refunds, $store) }}</dd>
                            </div>
                        </dl>

                        <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/shifts/' . $openShift->id . '/cash-events') }}"
                              class="grid grid-cols-[1fr_auto] gap-2 mb-5" x-data="{ type: 'cash_in' }">
                            @csrf
                            <div class="grid grid-cols-2 gap-2">
                                <select name="type" x-model="type" class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm">
                                    <option value="cash_in">+ {{ __('messages.cash_in') }}</option>
                                    <option value="cash_out">− {{ __('messages.cash_out') }}</option>
                                </select>
                                <input type="number" name="amount" min="1" step="100" required :placeholder="window.__currencyConfig?.currency_symbol || '0'"
                                       class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm">
                            </div>
                            <input type="text" name="reason" maxlength="255" placeholder="{{ __('messages.reason') }}"
                                   class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm">
                            <button type="submit" class="sf-btn-3d-primary rounded-xl px-4 py-2 text-sm font-bold text-white cursor-pointer transition">{{ __('messages.save') }}</button>
                        </form>

                        <div x-data="{ show: false }" class="border-t border-slate-200 dark:border-slate-800 pt-4">
                            <button type="button" @click="show = !show"
                                    class="sf-btn-3d-danger w-full rounded-xl px-4 py-3 text-sm font-bold text-white cursor-pointer transition">{{ __('messages.close_shift') }}</button>
                            <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/shifts/' . $openShift->id . '/close') }}"
                                  x-show="show" x-cloak class="mt-3 grid gap-2">
                                @csrf
                                <input type="number" name="actual_closing_amount" min="0" step="100" required placeholder="{{ __('messages.actual_closing_amount') }}"
                                       class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm">
                                <input type="text" name="variance_reason" maxlength="255" placeholder="{{ __('messages.variance_reason') }}"
                                       class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm">
                                <textarea name="notes" rows="2" maxlength="1000" placeholder="{{ __('messages.notes') }}"
                                          class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm"></textarea>
                                <button type="submit" class="sf-btn-3d-danger rounded-xl px-4 py-2 text-sm font-bold text-white cursor-pointer transition">{{ __('messages.confirm_close_shift') }}</button>
                            </form>
                        </div>
                    </section>
                @endif

                <section class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400 mb-1">{{ __('messages.today_summary') }}</p>
                    <h2 class="text-lg font-black mb-4">{{ now()->format('d M Y') }}</h2>

                    @if ($summary['shift_count'] > 0)
                        <dl class="grid grid-cols-2 gap-3 text-sm mb-4">
                            <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-3">
                                <dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.closed_shifts') }}</dt>
                                <dd class="font-black mt-0.5">{{ $summary['shift_count'] }}</dd>
                            </div>
                            <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-3">
                                <dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.expected_cash') }}</dt>
                                <dd class="font-black mt-0.5">{{ format_currency($summary['expected'], $store) }}</dd>
                            </div>
                            <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-3">
                                <dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.actual_cash') }}</dt>
                                <dd class="font-black mt-0.5">{{ format_currency($summary['actual'], $store) }}</dd>
                            </div>
                            <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-3">
                                <dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.difference') }}</dt>
                                <dd class="font-black mt-0.5 {{ (float) $summary['difference'] < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                    {{ ((float) $summary['difference'] < 0 ? '−' : '+') . format_currency(abs((float) $summary['difference']), $store) }}
                                </dd>
                            </div>
                        </dl>

                        {{-- Mobile View: Responsive Card List (sm:hidden) --}}
                        <div class="sm:hidden space-y-2.5">
                            @foreach ($summary['shifts'] as $shift)
                                <div class="rounded-xl border border-slate-200 dark:border-slate-700/80 bg-white dark:bg-slate-800 p-3 space-y-2 shadow-sm">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-bold text-xs text-slate-900 dark:text-slate-100">👤 {{ $shift->cashier?->name }}</span>
                                        <span class="text-[11px] font-mono font-medium text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-700 px-2 py-0.5 rounded border border-slate-200 dark:border-slate-600">{{ $shift->register_name }}</span>
                                    </div>
                                    <div class="grid grid-cols-3 gap-1.5 text-center text-xs pt-1 border-t border-slate-200 dark:border-slate-700/60">
                                        <div class="bg-slate-50 dark:bg-slate-900/90 rounded-lg p-1.5 border border-slate-200 dark:border-slate-700/80">
                                            <div class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">{{ __('messages.opening_cash') }}</div>
                                            <div class="font-bold text-[11px] mt-0.5 text-slate-900 dark:text-slate-100">{{ format_currency((float) $shift->opening_cash, $store) }}</div>
                                        </div>
                                        <div class="bg-slate-50 dark:bg-slate-900/90 rounded-lg p-1.5 border border-slate-200 dark:border-slate-700/80">
                                            <div class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">{{ __('messages.actual') }}</div>
                                            <div class="font-bold text-[11px] mt-0.5 text-slate-900 dark:text-slate-100">{{ format_currency((float) $shift->actual_closing_amount, $store) }}</div>
                                        </div>
                                        <div class="bg-slate-50 dark:bg-slate-900/90 rounded-lg p-1.5 border border-slate-200 dark:border-slate-700/80">
                                            <div class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">{{ __('messages.difference') }}</div>
                                            <div class="font-black text-[11px] mt-0.5 {{ (float) $shift->difference < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                                {{ ((float) $shift->difference < 0 ? '−' : '+') . format_currency(abs((float) $shift->difference), $store) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Desktop Table View (hidden on mobile, visible on sm+) --}}
                        <div class="hidden sm:block overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700/80">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50 dark:bg-slate-800 text-xs text-slate-700 dark:text-slate-200">
                                    <tr>
                                        <th class="text-left px-3 py-2 font-bold">{{ __('messages.cashier') }}</th>
                                        <th class="text-left px-3 py-2 font-bold">{{ __('messages.register') }}</th>
                                        <th class="text-right px-3 py-2 font-bold">{{ __('messages.opening_cash') }}</th>
                                        <th class="text-right px-3 py-2 font-bold">{{ __('messages.actual') }}</th>
                                        <th class="text-right px-3 py-2 font-bold">{{ __('messages.difference') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                                    @foreach ($summary['shifts'] as $shift)
                                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition">
                                            <td class="px-3 py-2.5 font-bold text-slate-900 dark:text-slate-100">{{ $shift->cashier?->name }}</td>
                                            <td class="px-3 py-2.5 text-slate-700 dark:text-slate-200 font-mono text-xs">{{ $shift->register_name }}</td>
                                            <td class="px-3 py-2.5 text-right text-slate-800 dark:text-slate-200">{{ format_currency((float) $shift->opening_cash, $store) }}</td>
                                            <td class="px-3 py-2.5 text-right font-bold text-slate-900 dark:text-slate-100">{{ format_currency((float) $shift->actual_closing_amount, $store) }}</td>
                                            <td class="px-3 py-2.5 text-right font-black {{ (float) $shift->difference < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                                {{ ((float) $shift->difference < 0 ? '−' : '+') . format_currency(abs((float) $shift->difference), $store) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="rounded-xl border border-dashed border-slate-300 dark:border-slate-700 p-8 text-center text-sm text-slate-500 dark:text-slate-400">
                            {{ __('messages.no_closed_shifts_today') }}
                        </div>
                    @endif
                </section>
            </div>
            @endif

            {{-- DEBT: customer balances (receivables — SoT §17) --}}
            <div x-show="activeTab === 'debt'"
                 x-data="{
                     debtSearch: '',
                     items: {{ \Illuminate\Support\Js::from(array_values(array_map(fn($c) => [
                         'name' => (string) ($c['name'] ?? ''),
                         'phone' => (string) ($c['phone'] ?? '')
                     ], $outstanding ?? []))) }},
                     get filteredCount() {
                         if (!this.debtSearch.trim()) return this.items.length;
                         const q = this.debtSearch.toLowerCase().trim();
                         const cleanQ = q.replace(/[-\s]/g, '');
                         return this.items.filter(i => {
                             const n = (i.name || '').toLowerCase();
                             const p = (i.phone || '').toLowerCase();
                             const cleanP = p.replace(/[-\s]/g, '');
                             return n.includes(q) || p.includes(q) || (cleanQ.length > 0 && cleanP.includes(cleanQ));
                         }).length;
                     },
                     matches(name, phone) {
                         if (!this.debtSearch.trim()) return true;
                         const q = this.debtSearch.toLowerCase().trim();
                         const cleanQ = q.replace(/[-\s]/g, '');
                         const n = (name || '').toLowerCase();
                         const p = (phone || '').toLowerCase();
                         const cleanP = p.replace(/[-\s]/g, '');
                         return n.includes(q) || p.includes(q) || (cleanQ.length > 0 && cleanP.includes(cleanQ));
                     }
                 }">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('messages.customer_balances') }}</p>
                            <div class="flex items-center gap-2 mt-0.5">
                                <h2 class="text-base sm:text-lg font-black text-slate-900 dark:text-white">{{ __('messages.outstanding_debt') }}</h2>
                                <span class="sm:hidden px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-900 dark:bg-amber-950/90 dark:text-amber-300 border border-amber-300 dark:border-amber-700/80">
                                    {{ format_currency($outstandingTotal, $store) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2.5 flex-1 sm:justify-end">
                        @if (!empty($outstanding))
                            <div class="relative flex-1 sm:max-w-xs">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                </span>
                                <input type="text"
                                       x-model="debtSearch"
                                       placeholder="{{ __('messages.search_customer_placeholder') }}"
                                       class="w-full pl-9 pr-8 py-1.5 text-xs sm:text-sm font-medium rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-500/50 dark:focus:ring-amber-400/50 shadow-xs transition">
                                <button type="button"
                                        x-show="debtSearch.trim() !== ''"
                                        @click="debtSearch = ''"
                                        class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer"
                                        title="{{ __('messages.clear') }}">
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                </button>
                            </div>
                            <span x-show="debtSearch.trim() !== ''"
                                  class="px-2 py-1 rounded-lg text-xs font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60 whitespace-nowrap shrink-0">
                                <span x-text="filteredCount"></span> / {{ count($outstanding) }}
                            </span>
                        @endif
                        <span class="hidden sm:inline-flex px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-900 dark:bg-amber-950/90 dark:text-amber-300 border border-amber-300 dark:border-amber-700/80 shrink-0">
                            {{ format_currency($outstandingTotal, $store) }}
                        </span>
                    </div>
                </div>

                @if (!empty($outstanding))
                    {{-- Empty Search Results State --}}
                    <div x-show="debtSearch.trim() !== '' && filteredCount === 0"
                         class="rounded-xl border border-dashed border-slate-300 dark:border-slate-700 p-8 text-center space-y-3">
                        <div class="w-10 h-10 mx-auto rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 flex items-center justify-center">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ __('messages.no_matching_records') }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">"<span class="font-semibold text-slate-700 dark:text-slate-300" x-text="debtSearch"></span>"</p>
                        </div>
                        <button type="button" @click="debtSearch = ''"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800/60 hover:bg-amber-100 dark:hover:bg-amber-900/50 cursor-pointer transition">
                            {{ __('messages.clear') }}
                        </button>
                    </div>

                    {{-- Mobile View: Responsive Card List (sm:hidden) --}}
                    <div class="sm:hidden space-y-2.5" x-show="filteredCount > 0">
                        @foreach ($outstanding as $customer)
                            <div x-show="matches({{ \Illuminate\Support\Js::from((string)($customer['name'] ?? '')) }}, {{ \Illuminate\Support\Js::from((string)($customer['phone'] ?? '')) }})"
                                 class="rounded-xl border border-slate-200 dark:border-slate-700/80 bg-white dark:bg-slate-800 p-3 space-y-2.5 shadow-sm">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="font-bold text-sm text-slate-900 dark:text-white truncate">👤 {{ $customer['name'] }}</p>
                                        @if ($customer['phone'])
                                            <a href="tel:{{ $customer['phone'] }}" class="inline-flex items-center gap-1 text-xs text-blue-600 dark:text-blue-400 font-mono mt-0.5 hover:underline font-semibold">
                                                <svg class="w-3 h-3 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                                <span>{{ $customer['phone'] }}</span>
                                            </a>
                                        @endif
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="block font-black text-sm text-amber-600 dark:text-amber-400">
                                            {{ format_currency($customer['balance'], $store) }}
                                        </span>
                                        <span class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">
                                            {{ $customer['last_activity'] ? \Illuminate\Support\Carbon::parse($customer['last_activity'])->diffForHumans() : '—' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="pt-2 border-t border-slate-200 dark:border-slate-700/60">
                                    <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/customers/' . $customer['customer_id'] . '/collect') }}"
                                          class="flex items-center gap-2" x-data="{ amount: '' }">
                                        @csrf
                                        <div class="relative flex-1">
                                            <input type="number" name="amount" min="0.01" :max="{{ $customer['balance'] }}" step="any" required :placeholder="window.__currencyConfig?.currency_symbol || '0'" x-model="amount"
                                                   class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 px-3 py-1.5 text-xs font-semibold focus:ring-1 focus:ring-emerald-500">
                                        </div>
                                        <button type="submit" :disabled="!amount || parseFloat(amount) <= 0"
                                                class="sf-btn-3d-success text-xs font-bold px-3.5 py-1.5 rounded-xl text-white disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer transition shrink-0 whitespace-nowrap">
                                            {{ __('messages.collect') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Desktop Table View (hidden on mobile, visible on sm+) --}}
                    <div class="hidden sm:block overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700/80" x-show="filteredCount > 0">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-800 text-xs text-slate-700 dark:text-slate-200">
                                <tr>
                                    <th class="text-left px-3 py-2 font-bold">{{ __('messages.customer') }}</th>
                                    <th class="text-right px-3 py-2 font-bold">{{ __('messages.outstanding_balance') }}</th>
                                    <th class="text-left px-3 py-2 font-bold">{{ __('messages.last_activity') }}</th>
                                    <th class="text-right px-3 py-2 font-bold">{{ __('messages.collect') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                                @foreach ($outstanding as $customer)
                                    <tr x-show="matches({{ \Illuminate\Support\Js::from((string)($customer['name'] ?? '')) }}, {{ \Illuminate\Support\Js::from((string)($customer['phone'] ?? '')) }})"
                                        class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition">
                                        <td class="px-3 py-2.5">
                                            <p class="font-bold text-slate-900 dark:text-slate-100">{{ $customer['name'] }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 font-mono">{{ $customer['phone'] ?? '—' }}</p>
                                        </td>
                                        <td class="px-3 py-2.5 text-right font-black text-amber-600 dark:text-amber-400">{{ format_currency($customer['balance'], $store) }}</td>
                                        <td class="px-3 py-2.5 text-xs text-slate-600 dark:text-slate-300">{{ $customer['last_activity'] ? \Illuminate\Support\Carbon::parse($customer['last_activity'])->diffForHumans() : '—' }}</td>
                                        <td class="px-3 py-2.5 text-right">
                                            <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/customers/' . $customer['customer_id'] . '/collect') }}"
                                                  class="inline-flex items-center gap-1.5" x-data="{ amount: '' }">
                                                @csrf
                                                <input type="number" name="amount" min="0.01" :max="{{ $customer['balance'] }}" step="any" required placeholder="0" x-model="amount"
                                                       class="w-28 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-2 py-1 text-right text-sm font-semibold">
                                                <button type="submit" :disabled="!amount || parseFloat(amount) <= 0"
                                                        class="sf-btn-3d-success text-xs font-bold px-3 py-1.5 rounded-lg text-white disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer transition">
                                                    {{ __('messages.collect') }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="rounded-xl border border-dashed border-slate-300 dark:border-slate-700 p-8 text-center text-sm text-slate-500 dark:text-slate-400">
                        <svg class="inline w-4 h-4 -mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        {{ __('messages.no_outstanding_debt') }}
                    </div>
                @endif
            </div>

            @if ($store->hasCapability(\App\Capabilities\Capability::SERVICE_REPAIR_JOBS))
            {{-- REPAIRS: Service & Repair Center Overview --}}
            <div x-show="activeTab === 'repairs'" class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-violet-600 text-white flex items-center justify-center font-bold text-base shadow-sm shrink-0">
                            🔧
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h2 class="text-base sm:text-lg font-black tracking-tight text-slate-900 dark:text-white">
                                    {{ __('messages.sidebar_repair_center') }}
                                </h2>
                                @if ($activeRepairsCount > 0)
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                        {{ $activeRepairsCount }} {{ __('messages.repair_stat_active') }}
                                    </span>
                                @endif
                                @if ($readyRepairsCount > 0)
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        {{ $readyRepairsCount }} {{ __('messages.repair_stat_ready') }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                {{ __('messages.repair_header_subtitle') }} · <a href="{{ url('/store/' . $store->slug . '/admin/repairs') }}" class="text-blue-600 dark:text-blue-400 hover:underline font-mono font-semibold">admin/repairs ↗</a>
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ url('/store/' . $store->slug . '/admin/repairs/create') }}"
                           class="sf-btn-3d-primary inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-black text-white cursor-pointer transition shadow-sm">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            <span>{{ __('messages.repair_new_job') }}</span>
                        </a>
                        <a href="{{ url('/store/' . $store->slug . '/admin/repairs') }}"
                           class="sf-btn-3d inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 hover:text-blue-600 dark:hover:text-blue-400 cursor-pointer transition">
                            <span>{{ __('messages.pos_view_all_repairs') }}</span>
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        </a>
                    </div>
                </div>

                @if ($recentRepairs->isNotEmpty())
                    @php
                        $statusBadgeClasses = [
                            'received' => 'bg-blue-100 dark:bg-blue-950/80 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800/80',
                            'diagnosing' => 'bg-amber-100 dark:bg-amber-950/80 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/80',
                            'awaiting_approval' => 'bg-amber-100 dark:bg-amber-950/80 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/80',
                            'awaiting_parts' => 'bg-purple-100 dark:bg-purple-950/80 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800/80',
                            'in_repair' => 'bg-orange-100 dark:bg-orange-950/80 text-orange-700 dark:text-orange-300 border border-orange-200 dark:border-orange-800/80',
                            'ready' => 'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/80',
                            'delivered' => 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700',
                            'cancelled' => 'bg-rose-100 dark:bg-rose-950/80 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/80',
                            'unrepairable' => 'bg-rose-100 dark:bg-rose-950/80 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/80',
                        ];
                    @endphp
                    {{-- Mobile View: Responsive Card List (sm:hidden) --}}
                    <div class="sm:hidden space-y-2.5">
                        @foreach ($recentRepairs as $job)
                            @php $debtAmount = $job->outstanding(); @endphp
                            <div class="rounded-xl border border-slate-200 dark:border-slate-700/80 bg-white dark:bg-slate-800 p-3 space-y-2.5 shadow-sm">
                                {{-- Row 1: Job Number + Status Badge --}}
                                <div class="flex items-center justify-between gap-2">
                                    <div class="min-w-0">
                                        <a href="{{ url('/store/' . $store->slug . '/admin/repairs/' . $job->id) }}"
                                           class="font-mono font-bold text-xs text-blue-600 dark:text-blue-400 hover:underline truncate">
                                            {{ $job->job_number }}
                                        </a>
                                        @if ($job->voucher_no)
                                            <span class="text-[10px] text-slate-500 dark:text-slate-400 font-mono ml-1">Ref: {{ $job->voucher_no }}</span>
                                        @endif
                                    </div>
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded shrink-0 {{ $statusBadgeClasses[$job->status] ?? 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700' }}">
                                        {{ __('messages.repair_status_' . $job->status) }}
                                    </span>
                                </div>

                                {{-- Row 2: Customer info + Device info --}}
                                <div class="flex items-start justify-between gap-2 text-xs">
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-900 dark:text-white truncate">
                                            👤 {{ $job->contact_name ?: ($job->customer?->name ?? '—') }}
                                        </div>
                                        @if ($job->contact_phone)
                                            <a href="tel:{{ $job->contact_phone }}" class="text-[11px] text-blue-600 dark:text-blue-400 font-mono hover:underline">
                                                {{ $job->contact_phone }}
                                            </a>
                                        @endif
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div class="font-bold text-slate-900 dark:text-white">{{ $job->device_type }}</div>
                                        @if ($job->brand || $job->model)
                                            <div class="text-[10px] text-slate-500 dark:text-slate-400">{{ implode(' · ', array_filter([$job->brand, $job->model])) }}</div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Row 3: Reported problem --}}
                                @if ($job->reported_problem)
                                    <div class="text-xs text-slate-700 dark:text-slate-200 bg-slate-50 dark:bg-slate-900/90 rounded-lg px-2.5 py-1.5 border border-slate-200 dark:border-slate-700/80 leading-relaxed">
                                        ⚠️ {{ $job->reported_problem }}
                                    </div>
                                @endif

                                {{-- Row 4: Technician, Debt & Actions --}}
                                <div class="flex items-center justify-between gap-2 pt-1 border-t border-slate-200 dark:border-slate-700/60 text-xs">
                                    <div class="flex items-center gap-2 min-w-0 truncate">
                                        @if ($job->technician)
                                            <span class="text-[11px] text-slate-600 dark:text-slate-300 truncate">🔧 {{ $job->technician->name }}</span>
                                        @endif
                                        @if ($debtAmount > 0)
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-900 dark:bg-amber-950/90 dark:text-amber-300 border border-amber-300 dark:border-amber-700/80 shrink-0">
                                                {{ __('messages.debt') }} {{ format_currency($debtAmount, $store) }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="inline-flex items-center gap-1.5 shrink-0">
                                        <a href="{{ url('/store/' . $store->slug . '/admin/repairs/' . $job->id) }}"
                                           class="sf-btn-3d inline-flex items-center justify-center w-7 h-7 rounded-lg text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition cursor-pointer"
                                           title="{{ __('messages.view') }}">
                                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </a>
                                        <a href="{{ url('/store/' . $store->slug . '/admin/repairs/' . $job->id . '/print') }}" target="_blank"
                                           class="sf-btn-3d inline-flex items-center justify-center w-7 h-7 rounded-lg text-blue-600 dark:text-blue-400 transition cursor-pointer"
                                           title="{{ __('messages.repair_print_ticket') }}">
                                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Desktop Table View (hidden on mobile, visible on sm+) --}}
                    <div class="hidden sm:block overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700/80">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-800 text-xs text-slate-700 dark:text-slate-200 select-none">
                                <tr>
                                    <th class="text-left px-3 py-2.5 whitespace-nowrap">{{ __('messages.repair_job_number') }}</th>
                                    <th class="text-left px-3 py-2.5 whitespace-nowrap">{{ __('messages.repair_customer_label') }}</th>
                                    <th class="text-left px-3 py-2.5 whitespace-nowrap">{{ __('messages.repair_device') }}</th>
                                    <th class="text-left px-3 py-2.5 whitespace-nowrap">{{ __('messages.repair_reported_problem') }}</th>
                                    <th class="text-left px-3 py-2.5 whitespace-nowrap">{{ __('messages.repair_technician') }}</th>
                                    <th class="text-center px-3 py-2.5 whitespace-nowrap">{{ __('messages.status') }}</th>
                                    <th class="text-right px-3 py-2.5 whitespace-nowrap">{{ __('messages.repair_stat_debt') }}</th>
                                    <th class="text-center px-3 py-2.5 whitespace-nowrap">{{ __('messages.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60 text-xs">
                                @foreach ($recentRepairs as $job)
                                    @php $debtAmount = $job->outstanding(); @endphp
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition">
                                        <td class="px-3 py-2.5">
                                            <a href="{{ url('/store/' . $store->slug . '/admin/repairs/' . $job->id) }}"
                                               class="font-mono font-bold text-blue-600 dark:text-blue-400 hover:underline">
                                                {{ $job->job_number }}
                                            </a>
                                            @if ($job->voucher_no)
                                                <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-mono">Ref: {{ $job->voucher_no }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2.5">
                                            <div class="font-bold text-slate-900 dark:text-white">
                                                {{ $job->contact_name ?: ($job->customer?->name ?? '—') }}
                                            </div>
                                            @if ($job->contact_phone)
                                                <div class="text-[10px] text-slate-500 dark:text-slate-400 font-mono">{{ $job->contact_phone }}</div>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2.5">
                                            <div class="font-medium text-slate-900 dark:text-white">{{ $job->device_type }}</div>
                                            @if ($job->brand || $job->model)
                                                <div class="text-[10px] text-slate-500 dark:text-slate-400">{{ implode(' · ', array_filter([$job->brand, $job->model])) }}</div>
                                            @endif
                                            @if ($job->imei_serial)
                                                <div class="text-[10px] font-mono text-slate-500 dark:text-slate-400">IMEI: {{ $job->imei_serial }}</div>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2.5 text-slate-700 dark:text-slate-300 max-w-[180px] truncate" title="{{ $job->reported_problem }}">
                                            {{ $job->reported_problem ?: '—' }}
                                        </td>
                                        <td class="px-3 py-2.5 text-slate-600 dark:text-slate-300">
                                            {{ $job->technician?->name ?? '—' }}
                                        </td>
                                        <td class="px-3 py-2.5 text-center whitespace-nowrap">
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded {{ $statusBadgeClasses[$job->status] ?? 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700' }}">
                                                {{ __('messages.repair_status_' . $job->status) }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2.5 text-right whitespace-nowrap font-mono">
                                            @if ($debtAmount > 0)
                                                <span class="font-bold text-amber-600 dark:text-amber-400">{{ format_currency($debtAmount, $store) }}</span>
                                            @else
                                                <span class="text-slate-400 dark:text-slate-500 font-bold">0</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2.5 text-center whitespace-nowrap">
                                            <div class="inline-flex items-center gap-1">
                                                <a href="{{ url('/store/' . $store->slug . '/admin/repairs/' . $job->id) }}"
                                                    class="sf-btn-3d inline-flex items-center justify-center w-7 h-7 rounded-lg text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition cursor-pointer"
                                                    title="{{ __('messages.view') }}">
                                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                                </a>
                                                <a href="{{ url('/store/' . $store->slug . '/admin/repairs/' . $job->id . '/print') }}" target="_blank"
                                                    class="sf-btn-3d inline-flex items-center justify-center w-7 h-7 rounded-lg text-blue-600 dark:text-blue-400 transition cursor-pointer"
                                                    title="{{ __('messages.repair_print_ticket') }}">
                                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="rounded-xl border border-dashed border-slate-300 dark:border-slate-700 p-8 text-center text-sm text-slate-500 dark:text-slate-400">
                        <div class="text-3xl mb-2 opacity-60">🔧</div>
                        <div class="font-bold text-slate-700 dark:text-slate-200">{{ __('messages.pos_no_active_repairs') }}</div>
                        <div class="text-xs text-slate-400 dark:text-slate-500 mt-1 mb-4">{{ __('messages.repair_empty_hint') }}</div>
                        <a href="{{ url('/store/' . $store->slug . '/admin/repairs/create') }}"
                           class="sf-btn-3d-primary inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-black text-white cursor-pointer transition shadow-sm">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            <span>{{ __('messages.repair_new_job') }}</span>
                        </a>
                    </div>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>
