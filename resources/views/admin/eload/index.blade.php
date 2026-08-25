@extends('layouts.admin.app')

@section('title', __('messages.sidebar_eload') . ' - ' . $store->name)

@php
    $storeRouteParams = ['store_slug' => $store->slug];
    $operatorsList = [
        ['key' => 'mpt', 'name' => 'MPT', 'discount' => (float) ($accounts->where('operator', 'mpt')->first()?->discount_percent ?? 4.0), 'activeClass' => 'border-amber-500 bg-amber-50 dark:bg-amber-950/60 text-amber-900 dark:text-amber-200 ring-2 ring-amber-500'],
        ['key' => 'atom', 'name' => 'ATOM', 'discount' => (float) ($accounts->where('operator', 'atom')->first()?->discount_percent ?? 3.5), 'activeClass' => 'border-sky-500 bg-sky-50 dark:bg-sky-950/60 text-sky-900 dark:text-sky-200 ring-2 ring-sky-500'],
        ['key' => 'ooredoo', 'name' => 'OOREDOO', 'discount' => (float) ($accounts->where('operator', 'ooredoo')->first()?->discount_percent ?? 4.0), 'activeClass' => 'border-rose-500 bg-rose-50 dark:bg-rose-950/60 text-rose-900 dark:text-rose-200 ring-2 ring-rose-500'],
        ['key' => 'mytel', 'name' => 'MYTEL', 'discount' => (float) ($accounts->where('operator', 'mytel')->first()?->discount_percent ?? 5.0), 'activeClass' => 'border-orange-500 bg-orange-50 dark:bg-orange-950/60 text-orange-900 dark:text-orange-200 ring-2 ring-orange-500'],
    ];
@endphp

@section('content')
<script nonce="{{ $cspNonce }}">
window.eloadManager = function () {
    return {
        openTopupModal: false,
        openAccountsModal: false,
        openFloatModal: false,

        operators: @json($operatorsList),

        form: {
            operator: 'mpt',
            phone_number: '',
            amount: 5000,
            discount_percent: 4.0,
            cost: 4800,
            profit: 200,
            type: 'topup',
            payment_method: 'cash',
            customer_name: '',
            package_name: ''
        },

        refillData: {
            accountId: null,
            accountName: '',
            amount: 100000
        },

        openRegisterModal() {
            this.form.phone_number = '';
            this.form.amount = 5000;
            let currentOp = this.operators.find(o => o.key === this.form.operator);
            this.form.discount_percent = currentOp ? currentOp.discount : 4.0;
            this.calculateProfit();
            this.openTopupModal = true;
        },

        selectOperator(opKey) {
            this.form.operator = opKey;
            let op = this.operators.find(o => o.key === opKey);
            if (op) {
                this.form.discount_percent = op.discount;
            }
            this.calculateProfit();
        },

        detectOperator() {
            let num = this.form.phone_number.replace(/\s+/g, '');
            if (num.startsWith('09')) num = num.substring(2);
            else if (num.startsWith('+959')) num = num.substring(4);
            else if (num.startsWith('959')) num = num.substring(3);

            let detected = null;
            if (num.startsWith('2') || num.startsWith('4') || num.startsWith('5') || num.startsWith('8')) {
                detected = 'mpt';
            } else if (num.startsWith('7') || num.startsWith('79') || num.startsWith('78') || num.startsWith('77') || num.startsWith('76')) {
                detected = 'atom';
            } else if (num.startsWith('9') || num.startsWith('97') || num.startsWith('96') || num.startsWith('95') || num.startsWith('98')) {
                detected = 'ooredoo';
            } else if (num.startsWith('6') || num.startsWith('69') || num.startsWith('68')) {
                detected = 'mytel';
            }

            if (detected && this.form.operator !== detected) {
                this.selectOperator(detected);
            } else {
                this.calculateProfit();
            }
        },

        calculateProfit() {
            let amt = parseFloat(this.form.amount) || 0;
            let margin = (parseFloat(this.form.discount_percent) || 0) / 100;
            this.form.cost = Math.round(amt * (1 - margin));
            this.form.profit = Math.round(amt - this.form.cost);
        },

        openRefillModal(id, name, balance) {
            this.refillData.accountId = id;
            this.refillData.accountName = name;
            this.refillData.amount = 100000;
            this.openFloatModal = true;
        },

        formatKs(num) {
            return (parseFloat(num) || 0).toLocaleString() + ' Ks';
        },

        printSlip(url) {
            const frame = document.getElementById('slipPrinterFrame');
            if (frame) {
                frame.src = url;
            } else {
                window.open(url, '_blank', 'width=380,height=600');
            }
        }
    };
};
</script>

<div x-data="window.eloadManager()" class="space-y-6">

    {{-- Header Banner & Action Buttons --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex p-2 rounded-xl bg-sky-500/10 text-sky-600 dark:bg-sky-500/20 dark:text-sky-400">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2zM12 7v4m-2-2h4"/></svg>
                </span>
                <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100">
                    {{ __('messages.sidebar_eload') }}
                </h1>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                {{ __('messages.eload_header_subtitle') }}
            </p>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" @click="openAccountsModal = true" class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition shadow-sm">
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <span>{{ __('messages.eload_manage_accounts') }}</span>
            </button>

            <button type="button" @click="openRegisterModal()" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-sky-600 to-indigo-600 hover:from-sky-700 hover:to-indigo-700 text-xs font-black text-white shadow-md shadow-sky-500/20 hover:shadow-sky-500/30 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                <span>{{ __('messages.eload_quick_topup') }}</span>
            </button>
        </div>
    </div>

    {{-- Hairline KPI Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4">
        {{-- Today Volume --}}
        <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                {{ __('messages.eload_today_volume') }}
            </div>
            <div class="mt-1 text-lg sm:text-xl font-black text-slate-900 dark:text-slate-100">
                {{ number_format($stats['today_volume']) }} <span class="text-xs font-normal text-slate-400">Ks</span>
            </div>
            <div class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                {{ number_format($stats['today_count']) }} {{ __('messages.transactions') }}
            </div>
        </div>

        {{-- Today Profit --}}
        <div class="p-4 rounded-2xl border border-emerald-200/60 dark:border-emerald-900/40 bg-emerald-50/40 dark:bg-emerald-950/20 shadow-sm">
            <div class="text-[11px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">
                {{ __('messages.eload_today_profit') }}
            </div>
            <div class="mt-1 text-lg sm:text-xl font-black text-emerald-800 dark:text-emerald-300">
                +{{ number_format($stats['today_profit']) }} <span class="text-xs font-normal">Ks</span>
            </div>
            <div class="mt-1 text-[11px] text-emerald-600 dark:text-emerald-400 font-medium">
                {{ __('messages.eload_net_commission') }}
            </div>
        </div>

        {{-- MPT Float --}}
        <div class="p-4 rounded-2xl border border-amber-200/70 dark:border-amber-900/40 bg-amber-50/40 dark:bg-amber-950/20 shadow-sm">
            <div class="flex items-center justify-between text-[11px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">
                <span>MPT Float</span>
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
            </div>
            <div class="mt-1 text-base sm:text-lg font-black text-amber-900 dark:text-amber-300">
                {{ number_format($stats['operator_balances']['mpt']) }} <span class="text-xs font-normal">Ks</span>
            </div>
            <div class="mt-1 text-[10px] text-amber-600 dark:text-amber-400">
                SIM Float Balance
            </div>
        </div>

        {{-- Atom Float --}}
        <div class="p-4 rounded-2xl border border-sky-200/70 dark:border-sky-900/40 bg-sky-50/40 dark:bg-sky-950/20 shadow-sm">
            <div class="flex items-center justify-between text-[11px] font-bold uppercase tracking-wider text-sky-700 dark:text-sky-400">
                <span>ATOM Float</span>
                <span class="w-2 h-2 rounded-full bg-sky-500"></span>
            </div>
            <div class="mt-1 text-base sm:text-lg font-black text-sky-900 dark:text-sky-300">
                {{ number_format($stats['operator_balances']['atom']) }} <span class="text-xs font-normal">Ks</span>
            </div>
            <div class="mt-1 text-[10px] text-sky-600 dark:text-sky-400">
                SIM Float Balance
            </div>
        </div>

        {{-- Ooredoo Float --}}
        <div class="p-4 rounded-2xl border border-rose-200/70 dark:border-rose-900/40 bg-rose-50/40 dark:bg-rose-950/20 shadow-sm">
            <div class="flex items-center justify-between text-[11px] font-bold uppercase tracking-wider text-rose-700 dark:text-rose-400">
                <span>Ooredoo Float</span>
                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
            </div>
            <div class="mt-1 text-base sm:text-lg font-black text-rose-900 dark:text-rose-300">
                {{ number_format($stats['operator_balances']['ooredoo']) }} <span class="text-xs font-normal">Ks</span>
            </div>
            <div class="mt-1 text-[10px] text-rose-600 dark:text-rose-400">
                SIM Float Balance
            </div>
        </div>

        {{-- Mytel Float --}}
        <div class="p-4 rounded-2xl border border-orange-200/70 dark:border-orange-900/40 bg-orange-50/40 dark:bg-orange-950/20 shadow-sm">
            <div class="flex items-center justify-between text-[11px] font-bold uppercase tracking-wider text-orange-700 dark:text-orange-400">
                <span>Mytel Float</span>
                <span class="w-2 h-2 rounded-full bg-orange-500"></span>
            </div>
            <div class="mt-1 text-base sm:text-lg font-black text-orange-900 dark:text-orange-300">
                {{ number_format($stats['operator_balances']['mytel']) }} <span class="text-xs font-normal">Ks</span>
            </div>
            <div class="mt-1 text-[10px] text-orange-600 dark:text-orange-400">
                SIM Float Balance
            </div>
        </div>
    </div>

    {{-- Standard Toolbar --}}
    <x-admin.toolbar
        :search="request('search', '')"
        :searchPlaceholder="__('messages.eload_search_placeholder')"
        :sort="request('sort', 'newest')"
        :sortOptions="[
            'newest'      => __('messages.sort_newest'),
            'oldest'      => __('messages.sort_oldest'),
            'amount_desc' => __('messages.eload_sort_amount_desc'),
            'amount_asc'  => __('messages.eload_sort_amount_asc'),
            'profit_desc' => __('messages.eload_sort_profit_desc'),
        ]"
        :filters="[
            'operator' => [
                'label'   => __('messages.eload_operator'),
                'options' => [
                    'mpt'     => 'MPT',
                    'atom'    => 'ATOM',
                    'ooredoo' => 'OOREDOO',
                    'mytel'   => 'MYTEL',
                    'other'   => 'Other',
                ],
            ],
            'type' => [
                'label'   => __('messages.type'),
                'options' => [
                    'topup'        => 'Top-up (ဘေလ်)',
                    'data_pack'    => 'Data Pack (ဒေတာ)',
                    'pin_code'     => 'Pin / Card (ကတ်)',
                    'bill_payment' => 'Bill Payment',
                ],
            ],
            'payment_method' => [
                'label'   => __('messages.payment_method'),
                'options' => [
                    'cash'    => 'Cash',
                    'kpay'    => 'KPay',
                    'wavepay' => 'WavePay',
                    'cbpay'   => 'CBPay',
                    'ayapay'  => 'AYAPay',
                    'other'   => 'Other',
                ],
            ],
            'status' => [
                'label'   => __('messages.status'),
                'options' => [
                    'completed' => __('messages.completed'),
                    'pending'   => __('messages.pending'),
                    'failed'    => __('messages.failed'),
                    'refunded'  => __('messages.refunded'),
                ],
            ],
            'occurred_at' => [
                'label' => __('messages.date'),
                'type'  => 'date',
            ],
        ]"
        :paginator="$transactions"
        :totalCount="$transactions->total()"
        :showExportImport="false"
    />

    {{-- Transactions Data Table --}}
    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/60 font-bold uppercase text-[11px] text-slate-500 dark:text-slate-400">
                        <th class="py-3.5 px-4">{{ __('messages.date') }} / {{ __('messages.ref_no') }}</th>
                        <th class="py-3.5 px-4">{{ __('messages.eload_operator') }}</th>
                        <th class="py-3.5 px-4">{{ __('messages.phone_number') }}</th>
                        <th class="py-3.5 px-4">{{ __('messages.type') }}</th>
                        <th class="py-3.5 px-4 text-right">{{ __('messages.amount') }}</th>
                        <th class="py-3.5 px-4 text-right">{{ __('messages.cost') }}</th>
                        <th class="py-3.5 px-4 text-right">{{ __('messages.profit') }}</th>
                        <th class="py-3.5 px-4">{{ __('messages.payment_method') }}</th>
                        <th class="py-3.5 px-4">{{ __('messages.status') }}</th>
                        <th class="py-3.5 px-4 text-right">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($transactions as $tx)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900 dark:text-slate-100">{{ $tx->occurred_at->format('d M Y, h:i A') }}</div>
                                <div class="text-[11px] font-mono text-slate-400 dark:text-slate-500">{{ $tx->ref_no }}</div>
                            </td>

                            <td class="py-3.5 px-4">
                                @php
                                    $op = strtolower($tx->operator);
                                    $badgeStyle = match($op) {
                                        'mpt'     => 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800/50',
                                        'atom'    => 'bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300 border-sky-200 dark:border-sky-800/50',
                                        'ooredoo' => 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border-rose-200 dark:border-rose-800/50',
                                        'mytel'   => 'bg-orange-100 text-orange-800 dark:bg-orange-950/60 dark:text-orange-300 border-orange-200 dark:border-orange-800/50',
                                        default   => 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-black border {{ $badgeStyle }} uppercase tracking-wider">
                                    {{ $tx->operator }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900 dark:text-slate-100 text-sm font-mono tracking-tight">{{ $tx->phone_number }}</div>
                                @if($tx->customer_name)
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400">{{ $tx->customer_name }}</div>
                                @endif
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="font-medium text-slate-800 dark:text-slate-200">{{ $tx->typeLabel() }}</div>
                                @if($tx->package_name)
                                    <div class="text-[11px] text-slate-400">{{ $tx->package_name }}</div>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-right font-black text-slate-900 dark:text-slate-100 text-sm">
                                {{ number_format($tx->amount, 0) }} <span class="text-[10px] font-normal text-slate-400">Ks</span>
                            </td>

                            <td class="py-3.5 px-4 text-right text-slate-500 dark:text-slate-400">
                                {{ number_format($tx->cost, 0) }} Ks
                            </td>

                            <td class="py-3.5 px-4 text-right font-bold text-emerald-600 dark:text-emerald-400">
                                +{{ number_format($tx->profit, 0) }} Ks
                            </td>

                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 uppercase">
                                    {{ $tx->payment_method }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4">
                                @if($tx->status === 'completed')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        {{ __('messages.completed') }}
                                    </span>
                                @elseif($tx->status === 'refunded')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        {{ __('messages.refunded') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        {{ ucfirst($tx->status) }}
                                    </span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    {{-- Print Slip --}}
                                    <button type="button" @click="printSlip('{{ route('store.admin.eload.slip', [...$storeRouteParams, 'id' => $tx->id]) }}')" class="p-1.5 rounded-lg text-slate-500 hover:text-sky-600 hover:bg-sky-50 dark:hover:bg-slate-800 transition" title="{{ __('messages.print_slip') }}">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    </button>

                                    {{-- Refund / Void toggle if completed --}}
                                    @if($tx->status === 'completed')
                                        <form method="POST" action="{{ route('store.admin.eload.status', [...$storeRouteParams, 'id' => $tx->id]) }}" onsubmit="return confirm('{{ __('messages.eload_refund_confirm') }}');">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="refunded">
                                            <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-slate-800 transition" title="{{ __('messages.refund') }}">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-12 text-center text-slate-400 dark:text-slate-500">
                                <svg class="w-10 h-10 mx-auto mb-2 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2zM12 7v4m-2-2h4"/></svg>
                                <p class="text-sm font-medium">{{ __('messages.no_transactions_found') }}</p>
                                <button type="button" @click="openRegisterModal()" class="mt-3 inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-sky-50 dark:bg-sky-950/40 text-sky-600 dark:text-sky-400 text-xs font-bold hover:bg-sky-100 transition">
                                    {{ __('messages.eload_quick_topup') }}
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

    {{-- Quick Register Top-Up Modal --}}
    <div
        x-show="openTopupModal"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
        style="display: none;"
    >
        <div
            @click.away="openTopupModal = false"
            class="w-full max-w-lg rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-5"
        >
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <span class="p-2 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2zM12 7v4m-2-2h4"/></svg>
                    </span>
                    <h3 class="text-lg font-black text-slate-900 dark:text-slate-100">
                        {{ __('messages.eload_quick_topup') }}
                    </h3>
                </div>
                <button type="button" @click="openTopupModal = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('store.admin.eload.store', $storeRouteParams) }}" class="space-y-4">
                @csrf

                {{-- Operator Selector Chips --}}
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                        {{ __('messages.eload_operator') }} *
                    </label>
                    <div class="grid grid-cols-4 gap-2">
                        <template x-for="op in operators" :key="op.key">
                            <button
                                type="button"
                                @click="selectOperator(op.key)"
                                :class="form.operator === op.key
                                    ? op.activeClass
                                    : 'border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300'"
                                class="py-2.5 px-3 rounded-2xl border text-xs font-black uppercase tracking-wider flex flex-col items-center justify-center gap-1 transition"
                            >
                                <span x-text="op.name"></span>
                                <span class="text-[10px] font-medium opacity-75" x-text="op.discount + '% margin'"></span>
                            </button>
                        </template>
                    </div>
                    <input type="hidden" name="operator" :value="form.operator" required>
                </div>

                {{-- Phone Number Input --}}
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                        {{ __('messages.phone_number') }} *
                    </label>
                    <div class="relative">
                        <input
                            type="text"
                            name="phone_number"
                            x-model="form.phone_number"
                            @input="detectOperator()"
                            required
                            placeholder="09xxxxxxxxx"
                            class="w-full text-base font-mono font-bold rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-4 py-2.5 focus:ring-2 focus:ring-sky-500"
                        >
                    </div>
                </div>

                {{-- Amount Preset Buttons + Custom Input --}}
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                        {{ __('messages.amount') }} (Ks) *
                    </label>
                    <div class="grid grid-cols-3 gap-2 mb-2">
                        <template x-for="preset in [1000, 3000, 5000, 10000, 20000, 50000]" :key="preset">
                            <button
                                type="button"
                                @click="form.amount = preset; calculateProfit()"
                                :class="form.amount == preset ? 'bg-sky-600 text-white font-black border-sky-600 shadow-sm' : 'bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700'"
                                class="py-2 rounded-xl border text-xs font-bold transition hover:border-sky-500"
                                x-text="preset.toLocaleString() + ' Ks'"
                            >
                            </button>
                        </template>
                    </div>
                    <input
                        type="number"
                        name="amount"
                        x-model.number="form.amount"
                        @input="calculateProfit()"
                        step="100"
                        min="100"
                        required
                        placeholder="{{ __('messages.custom_amount') }}"
                        class="w-full text-sm font-bold rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-4 py-2"
                    >
                </div>

                {{-- Discount Margin % & Calculation Row --}}
                <div class="grid grid-cols-2 gap-3 items-end">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                            {{ __('messages.eload_margin_percent') }}
                        </label>
                        <div class="relative">
                            <input
                                type="number"
                                name="discount_percent"
                                x-model.number="form.discount_percent"
                                @input="calculateProfit()"
                                step="0.1"
                                min="0"
                                max="100"
                                class="w-full text-sm font-black rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-4 py-2 focus:ring-2 focus:ring-sky-500"
                            >
                            <span class="absolute right-3 top-2 text-xs font-bold text-slate-400">%</span>
                        </div>
                    </div>

                    {{-- Live Profit & Cost Preview Box --}}
                    <div class="p-2.5 rounded-2xl border border-emerald-200/80 dark:border-emerald-900/40 bg-emerald-50/50 dark:bg-emerald-950/20 flex items-center justify-between text-xs h-[42px]">
                        <div>
                            <div class="text-[10px] text-emerald-700 dark:text-emerald-400 font-semibold">{{ __('messages.buying_cost') }}</div>
                            <div class="text-xs font-bold text-slate-800 dark:text-slate-200" x-text="formatKs(form.cost)"></div>
                        </div>
                        <div class="text-right">
                            <div class="text-[10px] text-emerald-700 dark:text-emerald-400 font-semibold">{{ __('messages.profit') }}</div>
                            <div class="text-xs font-black text-emerald-700 dark:text-emerald-300" x-text="'+ ' + formatKs(form.profit)"></div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    {{-- Transaction Type --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                            {{ __('messages.type') }}
                        </label>
                        <select name="type" x-model="form.type" class="w-full text-xs font-semibold rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-3 py-2">
                            <option value="topup">Top-up (ဘေလ်)</option>
                            <option value="data_pack">Data Pack (ဒေတာ)</option>
                            <option value="pin_code">Pin / Card (ကတ်)</option>
                            <option value="bill_payment">Bill Payment</option>
                        </select>
                    </div>

                    {{-- Payment Method --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                            {{ __('messages.payment_method') }}
                        </label>
                        <select name="payment_method" x-model="form.payment_method" class="w-full text-xs font-semibold rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-3 py-2">
                            <option value="cash">Cash</option>
                            <option value="kpay">KPay</option>
                            <option value="wavepay">WavePay</option>
                            <option value="cbpay">CBPay</option>
                            <option value="ayapay">AYAPay</option>
                        </select>
                    </div>
                </div>

                {{-- Customer Name / Package --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                            {{ __('messages.customer_name') }}
                        </label>
                        <input type="text" name="customer_name" x-model="form.customer_name" placeholder="Optional" class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                            {{ __('messages.package') }}
                        </label>
                        <input type="text" name="package_name" x-model="form.package_name" placeholder="e.g. 5GB Data" class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-3 py-2">
                    </div>
                </div>

                <div class="pt-2 flex items-center justify-end gap-2">
                    <button type="button" @click="openTopupModal = false" class="px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-xs font-black text-white shadow-md shadow-sky-500/20 transition">
                        {{ __('messages.confirm_and_save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Manage Operator Accounts Modal --}}
    <div
        x-show="openAccountsModal"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
        style="display: none;"
    >
        <div
            @click.away="openAccountsModal = false"
            class="w-full max-w-2xl rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-5 max-h-[90vh] overflow-y-auto"
        >
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div>
                    <h3 class="text-lg font-black text-slate-900 dark:text-slate-100">
                        {{ __('messages.eload_operator_accounts') }}
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ __('messages.eload_manage_accounts_desc') }}
                    </p>
                </div>
                <button type="button" @click="openAccountsModal = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Accounts List --}}
            <div class="space-y-3">
                @forelse($accounts as $acc)
                    <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 flex items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider border {{ match($acc->operator) { 'mpt' => 'bg-amber-100 text-amber-800 border-amber-200', 'atom' => 'bg-sky-100 text-sky-800 border-sky-200', 'ooredoo' => 'bg-rose-100 text-rose-800 border-rose-200', default => 'bg-orange-100 text-orange-800 border-orange-200'} }}">
                                    {{ $acc->operator }}
                                </span>
                                <span class="font-bold text-slate-900 dark:text-slate-100 text-sm">{{ $acc->name }}</span>
                                @if($acc->phone_number)
                                    <span class="text-xs font-mono text-slate-400">({{ $acc->phone_number }})</span>
                                @endif
                            </div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">
                                {{ __('messages.eload_margin_percent') }}: <span class="font-bold text-slate-700 dark:text-slate-300">{{ $acc->discount_percent }}%</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <div class="text-xs text-slate-400 font-medium">{{ __('messages.eload_sim_float_balance') }}</div>
                                <div class="text-base font-black text-slate-900 dark:text-slate-100">{{ number_format($acc->balance, 0) }} Ks</div>
                            </div>

                            <button type="button" @click="openRefillModal({{ $acc->id }}, '{{ strtoupper($acc->operator) }} - {{ $acc->name }}', {{ $acc->balance }})" class="px-3 py-1.5 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800/60 text-xs font-bold hover:bg-sky-100 transition">
                                {{ __('messages.eload_refill_float') }}
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center text-xs text-slate-400">
                        {{ __('messages.eload_no_accounts') }}
                    </div>
                @endforelse
            </div>

            {{-- Add New / Edit Account Form --}}
            <div class="border-t border-slate-100 dark:border-slate-800 pt-4">
                <h4 class="text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-3">
                    {{ __('messages.eload_add_update_account') }}
                </h4>
                <form method="POST" action="{{ route('store.admin.eload.accounts.store', $storeRouteParams) }}" class="space-y-3">
                    @csrf
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">{{ __('messages.eload_operator') }}</label>
                            <select name="operator" class="w-full text-xs font-semibold rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-2.5 py-1.5 text-slate-900 dark:text-slate-100">
                                <option value="mpt">MPT</option>
                                <option value="atom">ATOM</option>
                                <option value="ooredoo">Ooredoo</option>
                                <option value="mytel">Mytel</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">{{ __('messages.eload_account_name') }}</label>
                            <input type="text" name="name" required placeholder="e.g. MPT Agent SIM 1" class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-2.5 py-1.5">
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">{{ __('messages.phone_number') }}</label>
                            <input type="text" name="phone_number" placeholder="09xxxxxxx" class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-2.5 py-1.5">
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">{{ __('messages.eload_margin_percent') }}</label>
                            <input type="number" step="0.1" min="0" max="100" name="discount_percent" value="4.0" required class="w-full text-xs font-bold rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-2.5 py-1.5">
                        </div>
                    </div>

                    <div class="flex justify-end pt-1">
                        <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 dark:bg-slate-200 text-white dark:text-slate-900 text-xs font-black hover:bg-slate-900 transition">
                            {{ __('messages.eload_save_account') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Float Refill Modal --}}
    <div
        x-show="openFloatModal"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
        style="display: none;"
    >
        <div
            @click.away="openFloatModal = false"
            class="w-full max-w-md rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-4"
        >
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div>
                    <h3 class="text-base font-black text-slate-900 dark:text-slate-100">
                        {{ __('messages.eload_refill_float_title') }}
                    </h3>
                    <p class="text-xs font-bold text-sky-600 dark:text-sky-400" x-text="refillData.accountName"></p>
                </div>
                <button type="button" @click="openFloatModal = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('store.admin.eload.refill', $storeRouteParams) }}" class="space-y-4">
                @csrf
                <input type="hidden" name="eload_account_id" :value="refillData.accountId">

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                        {{ __('messages.eload_refill_amount') }} *
                    </label>
                    <div class="grid grid-cols-3 gap-2 mb-2">
                        <template x-for="preset in [50000, 100000, 200000, 300000, 500000, 1000000]" :key="preset">
                            <button
                                type="button"
                                @click="refillData.amount = preset"
                                :class="refillData.amount == preset ? 'bg-sky-600 text-white font-black border-sky-600' : 'bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700'"
                                class="py-2 rounded-xl border text-xs font-bold transition hover:border-sky-500"
                                x-text="(preset/1000) + 'k Ks'"
                            >
                            </button>
                        </template>
                    </div>
                    <input
                        type="number"
                        name="amount"
                        x-model.number="refillData.amount"
                        step="1000"
                        min="100"
                        required
                        class="w-full text-base font-bold rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-4 py-2.5"
                    >
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                        {{ __('messages.eload_note_optional') }}
                    </label>
                    <input type="text" name="notes" placeholder="{{ __('messages.eload_note_placeholder') }}" class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-slate-900 dark:text-slate-100">
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" @click="openFloatModal = false" class="px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-xs font-black text-white shadow-md transition">
                        {{ __('messages.eload_add_to_float') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Hidden Print Slip Iframe --}}
    <iframe id="slipPrinterFrame" class="hidden"></iframe>
</div>
@endsection
