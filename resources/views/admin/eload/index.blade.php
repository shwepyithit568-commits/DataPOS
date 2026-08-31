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

    $dataPacks = [
        'mpt' => [
            ['name' => 'Shal Pyaw 1,000 Ks (800MB + 150 Mins)', 'amount' => 1000],
            ['name' => 'Carry Plus 3,000 Ks (3GB + 700 Mins)', 'amount' => 3000],
            ['name' => 'Carry Plus 5,000 Ks (6GB + 1,200 Mins)', 'amount' => 5000],
            ['name' => 'Data Plus 10,000 Ks (15GB Data)', 'amount' => 10000],
            ['name' => 'Htaw B 1,000 Ks (5x Bonus)', 'amount' => 1000],
            ['name' => 'Htaw B 3,000 Ks (5x Bonus)', 'amount' => 3000],
        ],
        'atom' => [
            ['name' => 'Shal Sub 999 Ks (1GB + Social)', 'amount' => 1000],
            ['name' => 'Shal Sub 2,999 Ks (3.5GB Data)', 'amount' => 3000],
            ['name' => 'Shal Sub 4,999 Ks (7GB Data)', 'amount' => 5000],
            ['name' => 'ATOM Super Data 9,999 Ks (16GB)', 'amount' => 10000],
            ['name' => 'Stream Pack 1,500 Ks (TikTok & YouTube)', 'amount' => 1500],
        ],
        'ooredoo' => [
            ['name' => 'Supernet 999 Ks (1.2GB)', 'amount' => 1000],
            ['name' => 'Maha Baw 2,999 Ks (4GB Data)', 'amount' => 3000],
            ['name' => 'Maha Baw 4,999 Ks (8.5GB Data)', 'amount' => 5000],
            ['name' => 'Super Data 9,999 Ks (18GB Data)', 'amount' => 10000],
            ['name' => 'Night Owl 500 Ks (Unlimited Night)', 'amount' => 500],
        ],
        'mytel' => [
            ['name' => 'MyData 999 Ks (1.5GB Data)', 'amount' => 1000],
            ['name' => 'MyData 2,999 Ks (5GB + TikTok)', 'amount' => 3000],
            ['name' => 'MyData 4,999 Ks (10GB Data)', 'amount' => 5000],
            ['name' => 'Super MyData 9,999 Ks (22GB)', 'amount' => 10000],
            ['name' => 'Maha Pack 1,500 Ks (All-in-one)', 'amount' => 1500],
        ],
    ];

    $cardDenominations = [1000, 3000, 5000, 10000, 20000];
@endphp

@section('content')
<script nonce="{{ $cspNonce }}">
window.eloadManager = function () {
    return {
        openTopupModal: false,
        openAccountsModal: false,
        openFloatModal: false,
        activeTab: 'topup', // 'topup', 'data_pack', 'pin_code', 'sim_card'

        operators: @json($operatorsList),
        dataPacksMap: @json($dataPacks),

        // Presets for Top-up amounts
        topupPresets: [1000, 3000, 5000, 10000, 20000, 30000, 50000, 100000],

        // Unified Transaction Form
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
            package_name: '',
            notes: '',
            // Card specific
            card_qty: 1,
            card_denom: 3000,
            card_serial: '',
            // SIM specific
            sim_iccid: '',
            sim_nrc: '',
            sim_cost: 2500,
            sim_price: 5000
        },

        // Float refill state
        refillData: {
            accountId: null,
            accountName: '',
            amount: 100000,
            notes: ''
        },

        // Account management form
        accountForm: {
            id: null,
            operator: 'mpt',
            name: '',
            phone_number: '',
            balance: 0,
            discount_percent: 4.0,
            is_active: true
        },

        openSalesModal(tab = 'topup') {
            this.activeTab = tab;
            this.form.type = tab;
            this.form.phone_number = '';
            this.form.customer_name = '';
            this.form.notes = '';
            this.form.card_qty = 1;
            this.form.card_denom = 3000;
            this.form.card_serial = '';
            this.form.sim_iccid = '';
            this.form.sim_nrc = '';

            let currentOp = this.operators.find(o => o.key === this.form.operator);
            this.form.discount_percent = currentOp ? currentOp.discount : 4.0;

            if (tab === 'topup') {
                this.form.amount = 5000;
                this.form.package_name = 'ဖုန်းဘေလ် 5,000 Ks';
            } else if (tab === 'data_pack') {
                this.selectFirstDataPack();
            } else if (tab === 'pin_code') {
                this.updateCardCalculation();
            } else if (tab === 'sim_card') {
                this.updateSimCalculation();
            }

            this.calculateProfit();
            this.openTopupModal = true;
        },

        switchTab(tab) {
            this.activeTab = tab;
            this.form.type = tab;
            if (tab === 'topup') {
                this.form.amount = this.form.amount || 5000;
                this.form.package_name = 'ဖုန်းဘေလ် ' + Number(this.form.amount).toLocaleString() + ' Ks';
                this.calculateProfit();
            } else if (tab === 'data_pack') {
                this.selectFirstDataPack();
            } else if (tab === 'pin_code') {
                this.updateCardCalculation();
            } else if (tab === 'sim_card') {
                this.updateSimCalculation();
            }
        },

        selectOperator(opKey) {
            this.form.operator = opKey;
            let op = this.operators.find(o => o.key === opKey);
            if (op) {
                this.form.discount_percent = op.discount;
            }
            if (this.activeTab === 'data_pack') {
                this.selectFirstDataPack();
            } else if (this.activeTab === 'pin_code') {
                this.updateCardCalculation();
            } else if (this.activeTab === 'sim_card') {
                this.updateSimCalculation();
            } else {
                this.calculateProfit();
            }
        },

        selectFirstDataPack() {
            let packs = this.dataPacksMap[this.form.operator] || [];
            if (packs.length > 0) {
                this.selectDataPack(packs[0].name, packs[0].amount);
            }
        },

        selectDataPack(name, amount) {
            this.form.package_name = name;
            this.form.amount = amount;
            this.calculateProfit();
        },

        setTopupAmount(amt) {
            this.form.amount = amt;
            this.form.package_name = 'ဖုန်းဘေလ် ' + Number(amt).toLocaleString() + ' Ks';
            this.calculateProfit();
        },

        updateCardCalculation() {
            let qty = parseInt(this.form.card_qty) || 1;
            let denom = parseFloat(this.form.card_denom) || 3000;
            let total = qty * denom;
            this.form.amount = total;
            this.form.package_name = this.form.operator.toUpperCase() + ' ငွေဖြည့်ကတ် ' + Number(denom).toLocaleString() + ' Ks × ' + qty + ' ကတ်';
            let margin = (parseFloat(this.form.discount_percent) || 0) / 100;
            this.form.cost = Math.round(total * (1 - margin));
            this.form.profit = Math.round(total - this.form.cost);
            if (this.form.card_serial) {
                this.form.notes = 'Serial/PIN: ' + this.form.card_serial;
            }
        },

        updateSimCalculation() {
            let price = parseFloat(this.form.sim_price) || 5000;
            let cost = parseFloat(this.form.sim_cost) || 2500;
            this.form.amount = price;
            this.form.cost = cost;
            this.form.profit = Math.max(0, price - cost);
            this.form.discount_percent = 0;
            let packStr = this.form.operator.toUpperCase() + ' 4G SIM Card';
            if (this.form.sim_nrc) packStr += ' (NRC: ' + this.form.sim_nrc + ')';
            this.form.package_name = packStr;
            let notesArr = [];
            if (this.form.sim_iccid) notesArr.push('ICCID: ' + this.form.sim_iccid);
            if (this.form.sim_nrc) notesArr.push('NRC: ' + this.form.sim_nrc);
            this.form.notes = notesArr.join(' | ');
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
                if (this.activeTab === 'topup' || this.activeTab === 'data_pack') {
                    this.calculateProfit();
                }
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
            this.refillData.notes = '';
            this.openFloatModal = true;
        },

        setRefillAmount(amt) {
            this.refillData.amount = amt;
        },

        openEditAccount(account) {
            this.accountForm = {
                id: account.id,
                operator: account.operator,
                name: account.name,
                phone_number: account.phone_number || '',
                balance: account.balance,
                discount_percent: account.discount_percent,
                is_active: account.is_active
            };
        },

        resetAccountForm() {
            this.accountForm = {
                id: null,
                operator: 'mpt',
                name: '',
                phone_number: '',
                balance: 0,
                discount_percent: 4.0,
                is_active: true
            };
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

    {{-- Hidden iframe for clean silent slip printing --}}
    <iframe id="slipPrinterFrame" class="hidden" style="display:none;"></iframe>

    {{-- Page Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <span class="inline-flex p-2.5 rounded-2xl bg-gradient-to-br from-sky-500 to-indigo-600 text-white shadow-md shadow-sky-500/20">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2zM12 7v4m-2-2h4"/></svg>
                </span>
                <div>
                    <h1 class="text-xl sm:text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100 font-myanmar">
                        ဖုန်းဘေလ်၊ ဒေတာ နှင့် ဆင်းမ်ကတ် စီမံခန့်ခွဲမှု
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5 font-myanmar">
                        MPT, ATOM, OOREDOO, MYTEL ဘေလ်ဖြည့်ခြင်း၊ ဒေတာပက်ကေ့ချ်၊ ငွေဖြည့်ကတ်၊ ဆင်းမ်ကတ် ရောင်းချခြင်းနှင့် Agent Float စာရင်းများ
                    </p>
                </div>
            </div>
        </div>

        {{-- Quick Launch Action Bar --}}
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" @click="openSalesModal('topup')" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-gradient-to-r from-sky-600 to-indigo-600 hover:from-sky-700 hover:to-indigo-700 text-xs font-black text-white shadow-md shadow-sky-500/20 hover:shadow-sky-500/30 transition active:scale-95 font-myanmar">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <span>⚡ ဘေလ် / ဒေတာ ဖြည့်မည်</span>
            </button>

            <button type="button" @click="openSalesModal('pin_code')" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-xs font-black text-white shadow-md shadow-amber-500/20 transition active:scale-95 font-myanmar">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                <span>💳 ငွေဖြည့်ကတ်</span>
            </button>

            <button type="button" @click="openSalesModal('sim_card')" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-xs font-black text-white shadow-md shadow-emerald-600/20 transition active:scale-95 font-myanmar">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                <span>🪪 ဆင်းမ်ကတ် ရောင်းမည်</span>
            </button>

            <button type="button" @click="openAccountsModal = true" class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition shadow-sm font-myanmar">
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <span>🏦 အော်ပရေတာ စာရင်းများ</span>
            </button>
        </div>
    </div>

    {{-- Myanmar Operator Float Balances & Today KPI Summary --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">

        {{-- Today Volume --}}
        <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm relative overflow-hidden">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 font-myanmar">
                {{ __('messages.eload_today_volume') }}
            </div>
            <div class="mt-1 text-lg sm:text-xl font-black text-slate-900 dark:text-slate-100">
                {{ number_format((float) $stats['today_volume']) }} <span class="text-xs font-normal text-slate-400">Ks</span>
            </div>
            <div class="mt-1 text-[11px] text-slate-500 dark:text-slate-400 font-myanmar">
                {{ number_format($stats['today_count']) }} ကြိမ် အရောင်း
            </div>
        </div>

        {{-- Today Profit / Commission --}}
        <div class="p-4 rounded-2xl border border-emerald-200/70 dark:border-emerald-900/50 bg-emerald-50/40 dark:bg-emerald-950/20 shadow-sm relative overflow-hidden">
            <div class="text-[11px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 font-myanmar">
                ယနေ့ အသားတင် အမြတ်
            </div>
            <div class="mt-1 text-lg sm:text-xl font-black text-emerald-700 dark:text-emerald-300">
                +{{ number_format((float) $stats['today_profit']) }} <span class="text-xs font-normal">Ks</span>
            </div>
            <div class="mt-1 text-[11px] text-emerald-600 dark:text-emerald-400 font-myanmar">
                ကော်မရှင် + ဆင်းမ်အမြတ်
            </div>
        </div>

        {{-- MPT Float --}}
        @php
            $mptAccount = $accounts->where('operator', 'mpt')->first();
            $mptBal = (float) ($stats['operator_balances']['mpt'] ?? 0);
        @endphp
        <div class="p-3.5 rounded-2xl border border-amber-200/80 dark:border-amber-900/50 bg-amber-50/40 dark:bg-amber-950/20 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-xs font-black uppercase text-amber-800 dark:text-amber-300">
                    <span class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shadow-xs"></span>
                        MPT Float
                    </span>
                    <span class="text-[10px] px-1.5 py-0.5 rounded-md bg-amber-200/70 dark:bg-amber-900/60 text-amber-900 dark:text-amber-200 font-bold">
                        {{ $mptAccount?->discount_percent ?? 4.0 }}%
                    </span>
                </div>
                <div class="mt-1.5 text-base sm:text-lg font-black text-amber-950 dark:text-amber-200">
                    {{ number_format($mptBal) }} <span class="text-xs font-normal">Ks</span>
                </div>
            </div>
            <div class="mt-2 pt-2 border-t border-amber-200/60 dark:border-amber-900/40 flex items-center justify-between">
                <span class="text-[10px] text-amber-700 dark:text-amber-400 truncate">{{ $mptAccount?->phone_number ?? 'Agent SIM' }}</span>
                @if($mptAccount)
                    <button type="button" @click="openRefillModal({{ $mptAccount->id }}, '{{ $mptAccount->name }}', {{ $mptBal }})" class="text-[10px] font-bold text-amber-800 hover:text-amber-900 dark:text-amber-300 underline font-myanmar">
                        + ဖြည့်မည်
                    </button>
                @endif
            </div>
        </div>

        {{-- ATOM Float --}}
        @php
            $atomAccount = $accounts->where('operator', 'atom')->first();
            $atomBal = (float) ($stats['operator_balances']['atom'] ?? 0);
        @endphp
        <div class="p-3.5 rounded-2xl border border-sky-200/80 dark:border-sky-900/50 bg-sky-50/40 dark:bg-sky-950/20 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-xs font-black uppercase text-sky-800 dark:text-sky-300">
                    <span class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-sky-500 shadow-xs"></span>
                        ATOM Float
                    </span>
                    <span class="text-[10px] px-1.5 py-0.5 rounded-md bg-sky-200/70 dark:bg-sky-900/60 text-sky-900 dark:text-sky-200 font-bold">
                        {{ $atomAccount?->discount_percent ?? 3.5 }}%
                    </span>
                </div>
                <div class="mt-1.5 text-base sm:text-lg font-black text-sky-950 dark:text-sky-200">
                    {{ number_format($atomBal) }} <span class="text-xs font-normal">Ks</span>
                </div>
            </div>
            <div class="mt-2 pt-2 border-t border-sky-200/60 dark:border-sky-900/40 flex items-center justify-between">
                <span class="text-[10px] text-sky-700 dark:text-sky-400 truncate">{{ $atomAccount?->phone_number ?? 'E-Load SIM' }}</span>
                @if($atomAccount)
                    <button type="button" @click="openRefillModal({{ $atomAccount->id }}, '{{ $atomAccount->name }}', {{ $atomBal }})" class="text-[10px] font-bold text-sky-800 hover:text-sky-900 dark:text-sky-300 underline font-myanmar">
                        + ဖြည့်မည်
                    </button>
                @endif
            </div>
        </div>

        {{-- OOREDOO Float --}}
        @php
            $ooredooAccount = $accounts->where('operator', 'ooredoo')->first();
            $ooredooBal = (float) ($stats['operator_balances']['ooredoo'] ?? 0);
        @endphp
        <div class="p-3.5 rounded-2xl border border-rose-200/80 dark:border-rose-900/50 bg-rose-50/40 dark:bg-rose-950/20 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-xs font-black uppercase text-rose-800 dark:text-rose-300">
                    <span class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500 shadow-xs"></span>
                        OOREDOO
                    </span>
                    <span class="text-[10px] px-1.5 py-0.5 rounded-md bg-rose-200/70 dark:bg-rose-900/60 text-rose-900 dark:text-rose-200 font-bold">
                        {{ $ooredooAccount?->discount_percent ?? 4.0 }}%
                    </span>
                </div>
                <div class="mt-1.5 text-base sm:text-lg font-black text-rose-950 dark:text-rose-200">
                    {{ number_format($ooredooBal) }} <span class="text-xs font-normal">Ks</span>
                </div>
            </div>
            <div class="mt-2 pt-2 border-t border-rose-200/60 dark:border-rose-900/40 flex items-center justify-between">
                <span class="text-[10px] text-rose-700 dark:text-rose-400 truncate">{{ $ooredooAccount?->phone_number ?? 'Dealer SIM' }}</span>
                @if($ooredooAccount)
                    <button type="button" @click="openRefillModal({{ $ooredooAccount->id }}, '{{ $ooredooAccount->name }}', {{ $ooredooBal }})" class="text-[10px] font-bold text-rose-800 hover:text-rose-900 dark:text-rose-300 underline font-myanmar">
                        + ဖြည့်မည်
                    </button>
                @endif
            </div>
        </div>

        {{-- MYTEL Float --}}
        @php
            $mytelAccount = $accounts->where('operator', 'mytel')->first();
            $mytelBal = (float) ($stats['operator_balances']['mytel'] ?? 0);
        @endphp
        <div class="p-3.5 rounded-2xl border border-orange-200/80 dark:border-orange-900/50 bg-orange-50/40 dark:bg-orange-950/20 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-xs font-black uppercase text-orange-800 dark:text-orange-300">
                    <span class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-orange-500 shadow-xs"></span>
                        MYTEL Float
                    </span>
                    <span class="text-[10px] px-1.5 py-0.5 rounded-md bg-orange-200/70 dark:bg-orange-900/60 text-orange-900 dark:text-orange-200 font-bold">
                        {{ $mytelAccount?->discount_percent ?? 5.0 }}%
                    </span>
                </div>
                <div class="mt-1.5 text-base sm:text-lg font-black text-orange-950 dark:text-orange-200">
                    {{ number_format($mytelBal) }} <span class="text-xs font-normal">Ks</span>
                </div>
            </div>
            <div class="mt-2 pt-2 border-t border-orange-200/60 dark:border-orange-900/40 flex items-center justify-between">
                <span class="text-[10px] text-orange-700 dark:text-orange-400 truncate">{{ $mytelAccount?->phone_number ?? 'EasyTopup' }}</span>
                @if($mytelAccount)
                    <button type="button" @click="openRefillModal({{ $mytelAccount->id }}, '{{ $mytelAccount->name }}', {{ $mytelBal }})" class="text-[10px] font-bold text-orange-800 hover:text-orange-900 dark:text-orange-300 underline font-myanmar">
                        + ဖြည့်မည်
                    </button>
                @endif
            </div>
        </div>

    </div>

    {{-- Standard Search & Filter Toolbar --}}
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
                    'topup'        => 'Top-up (ဖုန်းဘေလ်)',
                    'data_pack'    => 'Data Pack (ဒေတာ)',
                    'pin_code'     => 'Topup Card (ငွေဖြည့်ကတ်)',
                    'sim_card'     => 'SIM Card (ဆင်းမ်ကတ်)',
                    'bill_payment' => 'Bill Payment (ဘေလ်ပေးသွင်း)',
                ],
            ],
            'payment_method' => [
                'label'   => __('messages.payment_method'),
                'options' => [
                    'cash'    => 'Cash (ငွေသား)',
                    'kpay'    => 'KBZPay',
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

    {{-- Transactions Table --}}
    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/60 font-bold uppercase text-[11px] text-slate-500 dark:text-slate-400">
                        <th class="py-3.5 px-4">{{ __('messages.date') }} / {{ __('messages.ref_no') }}</th>
                        <th class="py-3.5 px-4">{{ __('messages.eload_operator') }}</th>
                        <th class="py-3.5 px-4">{{ __('messages.phone_number') }} / ဝယ်ယူသူ</th>
                        <th class="py-3.5 px-4">အမျိုးအစား / ပက်ကေ့ချ်</th>
                        <th class="py-3.5 px-4 text-right">{{ __('messages.amount') }}</th>
                        <th class="py-3.5 px-4 text-right">{{ __('messages.cost') }}</th>
                        <th class="py-3.5 px-4 text-right font-black text-emerald-600 dark:text-emerald-400">{{ __('messages.profit') }}</th>
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
                                        'mpt'     => 'bg-amber-100 text-amber-900 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800/50',
                                        'atom'    => 'bg-sky-100 text-sky-900 dark:bg-sky-950/60 dark:text-sky-300 border-sky-200 dark:border-sky-800/50',
                                        'ooredoo' => 'bg-rose-100 text-rose-900 dark:bg-rose-950/60 dark:text-rose-300 border-rose-200 dark:border-rose-800/50',
                                        'mytel'   => 'bg-orange-100 text-orange-900 dark:bg-orange-950/60 dark:text-orange-300 border-orange-200 dark:border-orange-800/50',
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
                                    <div class="text-[11px] text-slate-600 dark:text-slate-400 font-myanmar">{{ $tx->customer_name }}</div>
                                @endif
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-800 dark:text-slate-200 font-myanmar">{{ $tx->typeLabel() }}</div>
                                @if($tx->package_name)
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 font-myanmar">{{ $tx->package_name }}</div>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-right font-black text-slate-900 dark:text-slate-100 text-sm">
                                {{ number_format((float) $tx->amount, 0) }} <span class="text-[10px] font-normal text-slate-400">Ks</span>
                            </td>

                            <td class="py-3.5 px-4 text-right text-slate-500 dark:text-slate-400">
                                {{ number_format((float) $tx->cost, 0) }} Ks
                            </td>

                            <td class="py-3.5 px-4 text-right font-black text-emerald-600 dark:text-emerald-400 text-sm">
                                +{{ number_format((float) $tx->profit, 0) }} Ks
                            </td>

                            <td class="py-3.5 px-4">
                                @php
                                    $pm = strtolower($tx->payment_method);
                                    $pmBadge = match($pm) {
                                        'kpay'    => 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-900/40',
                                        'wavepay' => 'bg-yellow-50 text-yellow-800 dark:bg-yellow-950/60 dark:text-yellow-300 border border-yellow-200 dark:border-yellow-900/40',
                                        'cbpay'   => 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-900/40',
                                        'ayapay'  => 'bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-300 border border-red-200 dark:border-red-900/40',
                                        default   => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold {{ $pmBadge }} uppercase">
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
                                <p class="text-sm font-medium font-myanmar">{{ __('messages.no_transactions_found') }}</p>
                                <button type="button" @click="openSalesModal('topup')" class="mt-3 inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-sky-50 dark:bg-sky-950/40 text-sky-600 dark:text-sky-400 text-xs font-bold hover:bg-sky-100 transition font-myanmar">
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

    {{-- UNIFIED SALES MODAL (Top-up, Data Pack, Topup Card, SIM Card) --}}
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
            class="w-full max-w-xl rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-5 max-h-[90vh] overflow-y-auto"
        >
            {{-- Modal Header & Tabs --}}
            <div class="border-b border-slate-100 dark:border-slate-800 pb-3 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="p-2 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2zM12 7v4m-2-2h4"/></svg>
                        </span>
                        <h3 class="text-lg font-black text-slate-900 dark:text-slate-100 font-myanmar">
                            ဖုန်းဆိုင် အမြန်ရောင်းချမှု မှတ်တမ်း
                        </h3>
                    </div>
                    <button type="button" @click="openTopupModal = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Mode Switcher Tabs --}}
                <div class="grid grid-cols-4 gap-1 p-1 bg-slate-100 dark:bg-slate-800 rounded-2xl text-xs font-bold font-myanmar">
                    <button type="button" @click="switchTab('topup')" :class="activeTab === 'topup' ? 'bg-white dark:bg-slate-900 text-sky-600 dark:text-sky-400 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'" class="py-2 px-1 rounded-xl transition text-center">
                        📱 ဘေလ်ဖြည့်
                    </button>
                    <button type="button" @click="switchTab('data_pack')" :class="activeTab === 'data_pack' ? 'bg-white dark:bg-slate-900 text-sky-600 dark:text-sky-400 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'" class="py-2 px-1 rounded-xl transition text-center">
                        🌐 ဒေတာ
                    </button>
                    <button type="button" @click="switchTab('pin_code')" :class="activeTab === 'pin_code' ? 'bg-white dark:bg-slate-900 text-amber-600 dark:text-amber-400 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'" class="py-2 px-1 rounded-xl transition text-center">
                        💳 ငွေဖြည့်ကတ်
                    </button>
                    <button type="button" @click="switchTab('sim_card')" :class="activeTab === 'sim_card' ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'" class="py-2 px-1 rounded-xl transition text-center">
                        🪪 ဆင်းမ်ကတ်
                    </button>
                </div>
            </div>

            <form method="POST" action="{{ route('store.admin.eload.store', $storeRouteParams) }}" class="space-y-4">
                @csrf
                <input type="hidden" name="type" :value="form.type">

                {{-- Operator Selector Chips --}}
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 font-myanmar">
                        အော်ပရေတာ ရွေးချယ်ရန် *
                    </label>
                    <div class="grid grid-cols-4 gap-2">
                        <template x-for="op in operators" :key="op.key">
                            <button
                                type="button"
                                @click="selectOperator(op.key)"
                                :class="form.operator === op.key
                                    ? op.activeClass
                                    : 'border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300'"
                                class="py-2.5 px-3 rounded-2xl border text-xs font-black uppercase tracking-wider flex flex-col items-center justify-center gap-1 transition active:scale-95"
                            >
                                <span x-text="op.name"></span>
                                <span class="text-[10px] font-medium opacity-75 font-myanmar" x-text="op.discount + '% အမြတ်'"></span>
                            </button>
                        </template>
                    </div>
                    <input type="hidden" name="operator" :value="form.operator" required>
                </div>

                {{-- TAB 1: E-LOAD / PHONE TOP-UP --}}
                <div x-show="activeTab === 'topup'" class="space-y-4">
                    {{-- Phone Number Input --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 font-myanmar">
                            ဖုန်းနံပါတ် (Auto Operator Detect) *
                        </label>
                        <input
                            type="text"
                            name="phone_number"
                            x-model="form.phone_number"
                            @input="detectOperator()"
                            :required="activeTab === 'topup' || activeTab === 'data_pack'"
                            placeholder="09xxxxxxxxx"
                            class="w-full text-base font-mono font-bold rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-4 py-2.5 focus:ring-2 focus:ring-sky-500"
                        >
                    </div>

                    {{-- Quick Amount Presets --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 font-myanmar">
                            ဖြည့်သွင်းမည့် ဘေလ်ပမာဏ (ကျပ်) *
                        </label>
                        <div class="grid grid-cols-4 gap-2 mb-2">
                            <template x-for="amt in topupPresets" :key="amt">
                                <button
                                    type="button"
                                    @click="setTopupAmount(amt)"
                                    :class="form.amount == amt
                                        ? 'bg-sky-600 text-white font-black shadow-sm'
                                        : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold hover:bg-slate-200'"
                                    class="py-2 px-1 rounded-xl text-xs transition active:scale-95 text-center"
                                >
                                    <span x-text="Number(amt).toLocaleString() + ' Ks'"></span>
                                </button>
                            </template>
                        </div>
                        <input
                            type="number"
                            name="amount"
                            x-model="form.amount"
                            @input="calculateProfit()"
                            min="500"
                            step="100"
                            required
                            class="w-full text-base font-mono font-bold rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-4 py-2.5 focus:ring-2 focus:ring-sky-500"
                        >
                    </div>
                </div>

                {{-- TAB 2: DATA PACKS --}}
                <div x-show="activeTab === 'data_pack'" class="space-y-4">
                    {{-- Phone Number Input --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 font-myanmar">
                            ဖုန်းနံပါတ် *
                        </label>
                        <input
                            type="text"
                            x-model="form.phone_number"
                            @input="detectOperator()"
                            placeholder="09xxxxxxxxx"
                            class="w-full text-base font-mono font-bold rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-4 py-2.5 focus:ring-2 focus:ring-sky-500"
                        >
                    </div>

                    {{-- Data Pack List for Active Operator --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 font-myanmar">
                            လူကြိုက်များသော ဒေတာ ပက်ကေ့ချ်များ ရွေးရန် *
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto p-1">
                            <template x-for="pack in (dataPacksMap[form.operator] || [])" :key="pack.name">
                                <button
                                    type="button"
                                    @click="selectDataPack(pack.name, pack.amount)"
                                    :class="form.package_name === pack.name
                                        ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-900 dark:text-indigo-200 ring-2 ring-indigo-500'
                                        : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50'"
                                    class="p-2.5 rounded-xl border text-left flex flex-col justify-between gap-1 transition"
                                >
                                    <span class="text-xs font-bold truncate font-myanmar" x-text="pack.name"></span>
                                    <span class="text-xs font-black text-indigo-600 dark:text-indigo-400" x-text="Number(pack.amount).toLocaleString() + ' Ks'"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- TAB 3: TOP-UP SCRATCH CARDS --}}
                <div x-show="activeTab === 'pin_code'" class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 font-myanmar">
                                ကတ်တန်ဖိုး (ကျပ်) *
                            </label>
                            <select
                                x-model="form.card_denom"
                                @change="updateCardCalculation()"
                                class="w-full text-sm font-bold rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-3.5 py-2.5 focus:ring-2 focus:ring-amber-500"
                            >
                                <option value="1000">1,000 Ks ကတ်</option>
                                <option value="3000">3,000 Ks ကတ်</option>
                                <option value="5000">5,000 Ks ကတ်</option>
                                <option value="10000">10,000 Ks ကတ်</option>
                                <option value="20000">20,000 Ks ကတ်</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 font-myanmar">
                                အရေအတွက် (ကတ်) *
                            </label>
                            <input
                                type="number"
                                x-model="form.card_qty"
                                @input="updateCardCalculation()"
                                min="1"
                                class="w-full text-base font-bold rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-4 py-2 focus:ring-2 focus:ring-amber-500"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 font-myanmar">
                            Card Serial / PIN မှတ်တမ်း (ရွေးချယ်ရန်)
                        </label>
                        <input
                            type="text"
                            x-model="form.card_serial"
                            @input="updateCardCalculation()"
                            placeholder="ဥပမာ - SN: 9876543210..."
                            class="w-full text-xs font-mono rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-4 py-2.5 focus:ring-2 focus:ring-amber-500"
                        >
                    </div>
                </div>

                {{-- TAB 4: SIM CARD SALES & REGISTRATION --}}
                <div x-show="activeTab === 'sim_card'" class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 font-myanmar">
                                ဆင်းမ်ကတ် ဖုန်းနံပါတ် *
                            </label>
                            <input
                                type="text"
                                x-model="form.phone_number"
                                placeholder="09xxxxxxxxx"
                                class="w-full text-base font-mono font-bold rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-4 py-2 focus:ring-2 focus:ring-emerald-500"
                            >
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 font-myanmar">
                                ရောင်းဈေး (ကျပ်) *
                            </label>
                            <input
                                type="number"
                                x-model="form.sim_price"
                                @input="updateSimCalculation()"
                                min="0"
                                step="500"
                                class="w-full text-base font-bold rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-4 py-2 focus:ring-2 focus:ring-emerald-500"
                            >
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 font-myanmar">
                                မူရင်းအရင်း (ကျပ်)
                            </label>
                            <input
                                type="number"
                                x-model="form.sim_cost"
                                @input="updateSimCalculation()"
                                min="0"
                                step="500"
                                class="w-full text-sm font-bold rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-4 py-2 focus:ring-2 focus:ring-emerald-500"
                            >
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 font-myanmar">
                                ဝယ်ယူသူ မှတ်ပုံတင် (NRC)
                            </label>
                            <input
                                type="text"
                                x-model="form.sim_nrc"
                                @input="updateSimCalculation()"
                                placeholder="12/ဥကမ(နိုင်)123456"
                                class="w-full text-sm font-myanmar rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-4 py-2 focus:ring-2 focus:ring-emerald-500"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 font-myanmar">
                            SIM Serial / ICCID (Bar Code Scan)
                        </label>
                        <input
                            type="text"
                            x-model="form.sim_iccid"
                            @input="updateSimCalculation()"
                            placeholder="89950000000000000000"
                            class="w-full text-xs font-mono rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-4 py-2.5 focus:ring-2 focus:ring-emerald-500"
                        >
                    </div>
                </div>

                {{-- Shared Customer Name & Payment Method --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 font-myanmar">
                            ဝယ်ယူသူ အမည် (ရွေးချယ်ရန်)
                        </label>
                        <input
                            type="text"
                            name="customer_name"
                            x-model="form.customer_name"
                            placeholder="Customer Name"
                            class="w-full text-xs rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-3.5 py-2.5 focus:ring-2 focus:ring-sky-500"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 font-myanmar">
                            ငွေပေးချေမှု ပုံစံ *
                        </label>
                        <select
                            name="payment_method"
                            x-model="form.payment_method"
                            class="w-full text-xs font-bold rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-3.5 py-2.5 focus:ring-2 focus:ring-sky-500"
                        >
                            <option value="cash">💵 Cash (ငွေသား)</option>
                            <option value="kpay">📱 KBZPay (KPay)</option>
                            <option value="wavepay">💛 WavePay</option>
                            <option value="cbpay">🏦 CBPay</option>
                            <option value="ayapay">🔴 AYAPay</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>

                {{-- Real-time Profit / Margin Calculation Card --}}
                <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 flex items-center justify-between text-xs">
                    <div>
                        <span class="text-slate-500 dark:text-slate-400 font-myanmar">ကျသင့်ငွေ: </span>
                        <span class="font-black text-slate-900 dark:text-slate-100 font-mono text-sm" x-text="Number(form.amount || 0).toLocaleString() + ' Ks'"></span>
                        <span class="text-slate-400 text-[11px] font-myanmar ml-1" x-text="'(အရင်း: ' + Number(form.cost || 0).toLocaleString() + ' Ks)'"></span>
                    </div>
                    <div class="text-right">
                        <span class="text-slate-500 dark:text-slate-400 font-myanmar">အမြတ်: </span>
                        <span class="font-black text-emerald-600 dark:text-emerald-400 font-mono text-sm" x-text="'+' + Number(form.profit || 0).toLocaleString() + ' Ks'"></span>
                    </div>
                </div>

                {{-- Hidden Fields for Submission --}}
                <input type="hidden" name="phone_number" :value="form.phone_number">
                <input type="hidden" name="amount" :value="form.amount">
                <input type="hidden" name="package_name" :value="form.package_name">
                <input type="hidden" name="cost" :value="form.cost">
                <input type="hidden" name="discount_percent" :value="form.discount_percent">
                <input type="hidden" name="notes" :value="form.notes">

                {{-- Action Buttons --}}
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button
                        type="button"
                        @click="openTopupModal = false"
                        class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 font-myanmar"
                    >
                        {{ __('messages.cancel') }}
                    </button>
                    <button
                        type="submit"
                        class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-sky-600 to-indigo-600 hover:from-sky-700 hover:to-indigo-700 text-xs font-black text-white shadow-md shadow-sky-500/20 hover:shadow-sky-500/30 transition active:scale-95 font-myanmar"
                    >
                        ✓ ရောင်းချမှု မှတ်တမ်းတင်မည်
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- REFILL FLOAT MODAL --}}
    <div
        x-show="openFloatModal"
        x-transition
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
        style="display: none;"
    >
        <div
            @click.away="openFloatModal = false"
            class="w-full max-w-md rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-4"
        >
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <span class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    </span>
                    <div>
                        <h3 class="text-base font-black text-slate-900 dark:text-slate-100 font-myanmar">
                            အော်ပရေတာ Float လက်ကျန်ငွေ ထည့်သွင်းခြင်း
                        </h3>
                        <p class="text-xs text-slate-500 font-myanmar" x-text="refillData.accountName"></p>
                    </div>
                </div>
                <button type="button" @click="openFloatModal = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('store.admin.eload.refill', $storeRouteParams) }}" class="space-y-4">
                @csrf
                <input type="hidden" name="eload_account_id" :value="refillData.accountId">

                {{-- Preset Refill Amount Chips --}}
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 font-myanmar">
                        ဖြည့်သွင်းမည့် ပမာဏ (ကျပ်) *
                    </label>
                    <div class="grid grid-cols-4 gap-2 mb-2">
                        <template x-for="amt in [100000, 300000, 500000, 1000000]" :key="amt">
                            <button
                                type="button"
                                @click="setRefillAmount(amt)"
                                :class="refillData.amount == amt ? 'bg-emerald-600 text-white font-black' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold'"
                                class="py-2 px-1 rounded-xl text-xs transition text-center"
                                x-text="Number(amt).toLocaleString() + ' Ks'"
                            >
                            </button>
                        </template>
                    </div>
                    <input
                        type="number"
                        name="amount"
                        x-model="refillData.amount"
                        min="1000"
                        step="1000"
                        required
                        class="w-full text-base font-mono font-bold rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-4 py-2.5 focus:ring-2 focus:ring-emerald-500"
                    >
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 font-myanmar">
                        မှတ်ချက် (ဘဏ်လွှဲ / Agent Float)
                    </label>
                    <input
                        type="text"
                        name="notes"
                        x-model="refillData.notes"
                        placeholder="ဥပမာ - KBZ Bank လွှဲပြောင်းဖြည့်သွင်း"
                        class="w-full text-xs font-myanmar rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-3.5 py-2.5 focus:ring-2 focus:ring-emerald-500"
                    >
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="openFloatModal = false" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-400">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-xs font-black text-white shadow-md shadow-emerald-600/20 font-myanmar">
                        + Float ဖြည့်သွင်းမည်
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- OPERATOR ACCOUNTS MANAGEMENT MODAL --}}
    <div
        x-show="openAccountsModal"
        x-transition
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
        style="display: none;"
    >
        <div
            @click.away="openAccountsModal = false"
            class="w-full max-w-2xl rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto"
        >
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <span class="p-2 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </span>
                    <div>
                        <h3 class="text-base font-black text-slate-900 dark:text-slate-100 font-myanmar">
                            အော်ပရေတာ Float အကောင့်များ စီမံခန့်ခွဲခြင်း
                        </h3>
                        <p class="text-xs text-slate-500 font-myanmar">Agent SIM များ၏ အမြတ်ရာခိုင်နှုန်းနှင့် လက်ကျန်ငွေများ</p>
                    </div>
                </div>
                <button type="button" @click="openAccountsModal = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Accounts List Table --}}
            <div class="border border-slate-200 dark:border-slate-700 rounded-2xl overflow-hidden">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800 font-bold uppercase text-[10px] text-slate-500">
                        <tr>
                            <th class="py-2.5 px-3">အော်ပရေတာ</th>
                            <th class="py-2.5 px-3">အကောင့်အမည် / ဖုန်း</th>
                            <th class="py-2.5 px-3 text-right">လက်ကျန်ငွေ</th>
                            <th class="py-2.5 px-3 text-right">အမြတ် %</th>
                            <th class="py-2.5 px-3 text-right">လုပ်ဆောင်ချက်</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($accounts as $acc)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class="py-2.5 px-3 font-black uppercase">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 dark:bg-slate-800">
                                        {{ $acc->operator }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3">
                                    <div class="font-bold text-slate-900 dark:text-slate-100 font-myanmar">{{ $acc->name }}</div>
                                    <div class="text-[11px] font-mono text-slate-500">{{ $acc->phone_number }}</div>
                                </td>
                                <td class="py-2.5 px-3 text-right font-black font-mono">
                                    {{ number_format((float) $acc->balance) }} Ks
                                </td>
                                <td class="py-2.5 px-3 text-right font-bold text-emerald-600">
                                    {{ $acc->discount_percent }}%
                                </td>
                                <td class="py-2.5 px-3 text-right">
                                    <button type="button" @click="openEditAccount({{ $acc }})" class="p-1 rounded text-sky-600 hover:bg-sky-50 font-bold">
                                        ပြင်မည်
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-slate-400 font-myanmar">
                                    အကောင့်များ မရှိသေးပါ။
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Edit / Add Account Form --}}
            <form method="POST" action="{{ route('store.admin.eload.accounts.store', $storeRouteParams) }}" class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-3">
                @csrf
                <input type="hidden" name="id" :value="accountForm.id">

                <div class="text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300 font-myanmar flex items-center justify-between">
                    <span x-text="accountForm.id ? 'အကောင့် အချက်အလက် ပြင်ဆင်ရန်' : '+ အကောင့် အသစ်ထည့်သွင်းရန်'"></span>
                    <button type="button" x-show="accountForm.id" @click="resetAccountForm()" class="text-[10px] text-slate-400 hover:text-slate-600">အသစ်ထည့်ရန် ပြောင်းမည်</button>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">အော်ပရေတာ *</label>
                        <select name="operator" x-model="accountForm.operator" required class="w-full text-xs font-bold rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2">
                            <option value="mpt">MPT</option>
                            <option value="atom">ATOM</option>
                            <option value="ooredoo">OOREDOO</option>
                            <option value="mytel">MYTEL</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">အကောင့် အမည် *</label>
                        <input type="text" name="name" x-model="accountForm.name" required placeholder="e.g. MPT Agent SIM" class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">SIM ဖုန်းနံပါတ်</label>
                        <input type="text" name="phone_number" x-model="accountForm.phone_number" placeholder="09xxxxxxxxx" class="w-full text-xs font-mono rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">လက်ကျန်ငွေ (Ks)</label>
                        <input type="number" name="balance" x-model="accountForm.balance" min="0" step="100" class="w-full text-xs font-mono font-bold rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">အမြတ် %</label>
                        <input type="number" name="discount_percent" x-model="accountForm.discount_percent" min="0" max="100" step="0.1" class="w-full text-xs font-mono font-bold rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-1">
                    <button type="submit" class="px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-xs font-black text-white font-myanmar shadow-sm">
                        သိမ်းဆည်းမည်
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
