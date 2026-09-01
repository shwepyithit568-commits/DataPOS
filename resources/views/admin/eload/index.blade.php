@extends('layouts.admin.app')

@section('title', __('messages.sidebar_eload') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

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

    $currentOperator = request('operator', '');
    $currentType = request('type', '');
    $currentStatus = request('status', '');
    $currentSearch = request('search', '');
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

<div class="w-full space-y-0.5 pb-6"
     x-data="{
        ...window.eloadManager(),
        viewMode: localStorage.getItem('admin_eload_view') || 'table',
        setView(mode) {
            this.viewMode = mode;
            localStorage.setItem('admin_eload_view', mode);
        }
     }">

    {{-- Hidden iframe for clean silent slip printing --}}
    <iframe id="slipPrinterFrame" class="hidden" style="display:none;"></iframe>

    {{-- ============================================================
         1. COMPACT PAGE HEADER — title, store, and primary action CTAs
         ============================================================ --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-8 h-8 rounded-lg bg-sky-50 dark:bg-sky-950/50 text-sky-600 dark:text-sky-400 grid place-items-center text-base font-bold shadow-xs flex-shrink-0">
                📱
            </span>
            <div class="min-w-0">
                <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5 truncate">
                    <span>{{ __('messages.sidebar_eload') }}</span>
                    <span class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 hidden sm:inline">({{ $store->name }})</span>
                </h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                    MPT, ATOM, OOREDOO, MYTEL ဘေလ်၊ ဒေတာ၊ ငွေဖြည့်ကတ်၊ ဆင်းမ်ကတ် ရောင်းချခြင်း
                </p>
            </div>
        </div>

        {{-- Action Bar --}}
        <div class="flex flex-wrap items-center gap-1 self-start sm:self-auto shrink-0">
            <button type="button" @click="openSalesModal('topup')"
                    class="h-7 px-2.5 rounded-md bg-sky-600 hover:bg-sky-500 text-white text-xs font-black shadow-2xs hover:shadow-sky-500/20 transition inline-flex items-center gap-1 active:scale-95 cursor-pointer">
                <span>⚡ + ဘေလ် / ဒေတာ</span>
            </button>

            <button type="button" @click="openSalesModal('pin_code')"
                    class="h-7 px-2 rounded-md bg-amber-500 hover:bg-amber-600 text-white text-xs font-black shadow-2xs transition inline-flex items-center gap-1 active:scale-95 cursor-pointer">
                <span>💳 ငွေဖြည့်ကတ်</span>
            </button>

            <button type="button" @click="openSalesModal('sim_card')"
                    class="h-7 px-2 rounded-md bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black shadow-2xs transition inline-flex items-center gap-1 active:scale-95 cursor-pointer">
                <span>🪪 ဆင်းမ်ကတ်</span>
            </button>

            <button type="button" @click="openAccountsModal = true"
                    class="h-7 px-2 rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold hover:bg-slate-100 transition inline-flex items-center gap-1 cursor-pointer">
                <span>🏦 အော်ပရေတာ</span>
            </button>
        </div>
    </div>

    {{-- ============================================================
         2. OPERATOR FLOAT BALANCES & KPI STAT CARDS (Centered Row-based)
         ============================================================ --}}
    @php
        $mptAccount = $accounts->where('operator', 'mpt')->first();
        $mptBal = (float) ($stats['operator_balances']['mpt'] ?? 0);
        $atomAccount = $accounts->where('operator', 'atom')->first();
        $atomBal = (float) ($stats['operator_balances']['atom'] ?? 0);
        $ooredooAccount = $accounts->where('operator', 'ooredoo')->first();
        $ooredooBal = (float) ($stats['operator_balances']['ooredoo'] ?? 0);
        $mytelAccount = $accounts->where('operator', 'mytel')->first();
        $mytelBal = (float) ($stats['operator_balances']['mytel'] ?? 0);
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-0.5 sm:gap-1" role="list" aria-label="E-Load KPI and Operator Float Balances">
        {{-- 1. Today Volume --}}
        <div role="listitem" class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2 sm:gap-2.5">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-sky-100 text-sky-600 dark:bg-sky-950/70 dark:text-sky-300 shadow-inner text-xs sm:text-sm font-bold">
                💵
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-outfit truncate">
                    Ks {{ number_format((float) $stats['today_volume']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.eload_today_volume') }} ({{ $stats['today_count'] }})
                </p>
            </div>
        </div>

        {{-- 2. Today Profit --}}
        <div role="listitem" class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-emerald-200/70 dark:border-emerald-900/50 shadow-2xs bg-emerald-50/20 dark:bg-emerald-950/10 flex items-center justify-center gap-2 sm:gap-2.5">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-emerald-100 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-300 shadow-inner text-xs sm:text-sm font-bold">
                💰
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-emerald-600 dark:text-emerald-400 leading-none tabular-nums font-outfit truncate">
                    +Ks {{ number_format((float) $stats['today_profit']) }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-emerald-600/80 dark:text-emerald-400/80 mt-0.5 truncate font-bold uppercase tracking-wider">
                    ယနေ့ အသားတင် အမြတ်
                </p>
            </div>
        </div>

        {{-- 3. MPT Float --}}
        <div role="listitem" class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-amber-200/80 dark:border-amber-900/50 shadow-2xs bg-amber-50/20 dark:bg-amber-950/10 flex items-center justify-center gap-2 sm:gap-2.5">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-amber-100 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300 shadow-inner text-xs sm:text-sm font-bold">
                🟡
            </div>
            <div class="min-w-0">
                <div class="text-xs sm:text-sm font-black text-amber-800 dark:text-amber-300 leading-none tabular-nums font-mono truncate">
                    Ks {{ number_format($mptBal) }}
                </div>
                <div class="flex items-center justify-between gap-1 mt-0.5">
                    <span class="text-[9px] text-amber-700/80 dark:text-amber-400/80 font-bold uppercase">MPT {{ $mptAccount?->discount_percent ?? 4.0 }}%</span>
                    @if($mptAccount)
                        <button type="button" @click="openRefillModal({{ $mptAccount->id }}, '{{ $mptAccount->name }}', {{ $mptBal }})" class="text-[9px] font-bold text-amber-800 hover:text-amber-950 dark:text-amber-300 underline cursor-pointer">+ ဖြည့်</button>
                    @endif
                </div>
            </div>
        </div>

        {{-- 4. ATOM Float --}}
        <div role="listitem" class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-sky-200/80 dark:border-sky-900/50 shadow-2xs bg-sky-50/20 dark:bg-sky-950/10 flex items-center justify-center gap-2 sm:gap-2.5">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-sky-100 text-sky-700 dark:bg-sky-950/70 dark:text-sky-300 shadow-inner text-xs sm:text-sm font-bold">
                🔵
            </div>
            <div class="min-w-0">
                <div class="text-xs sm:text-sm font-black text-sky-800 dark:text-sky-300 leading-none tabular-nums font-mono truncate">
                    Ks {{ number_format($atomBal) }}
                </div>
                <div class="flex items-center justify-between gap-1 mt-0.5">
                    <span class="text-[9px] text-sky-700/80 dark:text-sky-400/80 font-bold uppercase">ATOM {{ $atomAccount?->discount_percent ?? 3.5 }}%</span>
                    @if($atomAccount)
                        <button type="button" @click="openRefillModal({{ $atomAccount->id }}, '{{ $atomAccount->name }}', {{ $atomBal }})" class="text-[9px] font-bold text-sky-800 hover:text-sky-950 dark:text-sky-300 underline cursor-pointer">+ ဖြည့်</button>
                    @endif
                </div>
            </div>
        </div>

        {{-- 5. OOREDOO Float --}}
        <div role="listitem" class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-rose-200/80 dark:border-rose-900/50 shadow-2xs bg-rose-50/20 dark:bg-rose-950/10 flex items-center justify-center gap-2 sm:gap-2.5">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-rose-100 text-rose-700 dark:bg-rose-950/70 dark:text-rose-300 shadow-inner text-xs sm:text-sm font-bold">
                🔴
            </div>
            <div class="min-w-0">
                <div class="text-xs sm:text-sm font-black text-rose-800 dark:text-rose-300 leading-none tabular-nums font-mono truncate">
                    Ks {{ number_format($ooredooBal) }}
                </div>
                <div class="flex items-center justify-between gap-1 mt-0.5">
                    <span class="text-[9px] text-rose-700/80 dark:text-rose-400/80 font-bold uppercase">OOREDOO {{ $ooredooAccount?->discount_percent ?? 4.0 }}%</span>
                    @if($ooredooAccount)
                        <button type="button" @click="openRefillModal({{ $ooredooAccount->id }}, '{{ $ooredooAccount->name }}', {{ $ooredooBal }})" class="text-[9px] font-bold text-rose-800 hover:text-rose-950 dark:text-rose-300 underline cursor-pointer">+ ဖြည့်</button>
                    @endif
                </div>
            </div>
        </div>

        {{-- 6. MYTEL Float --}}
        <div role="listitem" class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-orange-200/80 dark:border-orange-900/50 shadow-2xs bg-orange-50/20 dark:bg-orange-950/10 flex items-center justify-center gap-2 sm:gap-2.5">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-orange-100 text-orange-700 dark:bg-orange-950/70 dark:text-orange-300 shadow-inner text-xs sm:text-sm font-bold">
                🟠
            </div>
            <div class="min-w-0">
                <div class="text-xs sm:text-sm font-black text-orange-800 dark:text-orange-300 leading-none tabular-nums font-mono truncate">
                    Ks {{ number_format($mytelBal) }}
                </div>
                <div class="flex items-center justify-between gap-1 mt-0.5">
                    <span class="text-[9px] text-orange-700/80 dark:text-orange-400/80 font-bold uppercase">MYTEL {{ $mytelAccount?->discount_percent ?? 5.0 }}%</span>
                    @if($mytelAccount)
                        <button type="button" @click="openRefillModal({{ $mytelAccount->id }}, '{{ $mytelAccount->name }}', {{ $mytelBal }})" class="text-[9px] font-bold text-orange-800 hover:text-orange-950 dark:text-orange-300 underline cursor-pointer">+ ဖြည့်</button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         3. INTERACTIVE SEARCH & FILTER TOOLBAR (Theme Governance Style)
         ============================================================ --}}
    <div class="bg-white dark:bg-slate-900 px-2.5 py-1 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col md:flex-row md:items-center md:justify-between gap-1">
        {{-- Left: Search & Filter Pills --}}
        <div class="flex flex-wrap items-center gap-1.5 flex-1">
            {{-- Search Bar --}}
            <form method="GET" action="{{ route('store.admin.eload.index', $storeRouteParams) }}" class="relative min-w-[170px] sm:min-w-[240px] flex-1 max-w-xs">
                @if(!empty($currentOperator))
                    <input type="hidden" name="operator" value="{{ $currentOperator }}">
                @endif
                @if(!empty($currentType))
                    <input type="hidden" name="type" value="{{ $currentType }}">
                @endif
                @if(!empty($currentStatus))
                    <input type="hidden" name="status" value="{{ $currentStatus }}">
                @endif
                <input type="text"
                       name="search"
                       value="{{ $currentSearch }}"
                       placeholder="{{ __('messages.eload_search_placeholder') ?? 'Search phone, ref, customer...' }}"
                       class="w-full h-7 pl-8 pr-2.5 rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-semibold text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-sky-500 focus:bg-white dark:focus:bg-slate-900 transition" />
                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
            </form>

            {{-- Operator Filter Pills --}}
            <div class="flex items-center gap-0.5 bg-slate-100 dark:bg-slate-800/80 p-0.5 rounded-md border border-slate-200/60 dark:border-slate-700">
                <a href="{{ route('store.admin.eload.index', array_merge($storeRouteParams, array_filter(['search' => $currentSearch, 'type' => $currentType, 'status' => $currentStatus]))) }}"
                   class="px-2 py-0.5 rounded text-[11px] font-bold transition cursor-pointer {{ empty($currentOperator) ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700' }}">
                    All Ops
                </a>
                @foreach(['mpt' => 'MPT', 'atom' => 'ATOM', 'ooredoo' => 'OOREDOO', 'mytel' => 'MYTEL'] as $opKey => $opLabel)
                    <a href="{{ route('store.admin.eload.index', array_merge($storeRouteParams, ['operator' => $opKey], array_filter(['search' => $currentSearch, 'type' => $currentType, 'status' => $currentStatus]))) }}"
                       class="px-1.5 py-0.5 rounded text-[11px] font-bold transition cursor-pointer {{ $currentOperator === $opKey ? 'bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-sky-600' }}">
                        {{ $opLabel }}
                    </a>
                @endforeach
            </div>

            {{-- Type Filter Pills --}}
            <div class="hidden sm:flex items-center gap-0.5 bg-slate-100 dark:bg-slate-800/80 p-0.5 rounded-md border border-slate-200/60 dark:border-slate-700">
                <a href="{{ route('store.admin.eload.index', array_merge($storeRouteParams, array_filter(['search' => $currentSearch, 'operator' => $currentOperator, 'status' => $currentStatus]))) }}"
                   class="px-2 py-0.5 rounded text-[11px] font-bold transition cursor-pointer {{ empty($currentType) ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700' }}">
                    All Types
                </a>
                <a href="{{ route('store.admin.eload.index', array_merge($storeRouteParams, ['type' => 'topup'], array_filter(['search' => $currentSearch, 'operator' => $currentOperator, 'status' => $currentStatus]))) }}"
                   class="px-1.5 py-0.5 rounded text-[11px] font-bold transition cursor-pointer {{ $currentType === 'topup' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700' }}">
                    Top-up
                </a>
                <a href="{{ route('store.admin.eload.index', array_merge($storeRouteParams, ['type' => 'data_pack'], array_filter(['search' => $currentSearch, 'operator' => $currentOperator, 'status' => $currentStatus]))) }}"
                   class="px-1.5 py-0.5 rounded text-[11px] font-bold transition cursor-pointer {{ $currentType === 'data_pack' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700' }}">
                    Data
                </a>
                <a href="{{ route('store.admin.eload.index', array_merge($storeRouteParams, ['type' => 'pin_code'], array_filter(['search' => $currentSearch, 'operator' => $currentOperator, 'status' => $currentStatus]))) }}"
                   class="px-1.5 py-0.5 rounded text-[11px] font-bold transition cursor-pointer {{ $currentType === 'pin_code' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700' }}">
                    Cards
                </a>
                <a href="{{ route('store.admin.eload.index', array_merge($storeRouteParams, ['type' => 'sim_card'], array_filter(['search' => $currentSearch, 'operator' => $currentOperator, 'status' => $currentStatus]))) }}"
                   class="px-1.5 py-0.5 rounded text-[11px] font-bold transition cursor-pointer {{ $currentType === 'sim_card' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700' }}">
                    SIM
                </a>
            </div>
        </div>

        {{-- Right: Export Button & View mode switcher --}}
        <div class="flex items-center gap-1 self-end sm:self-auto">
            @if(!empty($exportUrl))
                <a href="{{ $exportUrl }}"
                   title="Export Excel (.xlsx)"
                   class="h-6 px-2 rounded text-[11px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 border border-emerald-200 dark:border-emerald-800 shadow-2xs transition inline-flex items-center gap-1 cursor-pointer">
                    <svg class="w-3 h-3 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    <span>Excel</span>
                </a>
            @endif

            <div class="flex items-center gap-0.5 bg-slate-100 dark:bg-slate-800/80 p-0.5 rounded-md border border-slate-200/60 dark:border-slate-700">
                <button type="button"
                        @click="setView('table')"
                        class="px-2 py-0.5 rounded text-[11px] font-bold flex items-center gap-1 transition cursor-pointer"
                        :class="viewMode === 'table' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-300 shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'">
                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
                    <span>Table</span>
                </button>
                <button type="button"
                        @click="setView('card')"
                        class="px-2 py-0.5 rounded text-[11px] font-bold flex items-center gap-1 transition cursor-pointer"
                        :class="viewMode === 'card' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-300 shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'">
                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    <span>Cards</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ============================================================
         4. TRANSACTIONS PRESENTATION (Table & Card Views)
         ============================================================ --}}
    @if ($transactions->isEmpty())
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg p-6 sm:p-8 text-center shadow-2xs">
            <div class="mx-auto mb-3 w-12 h-12 rounded-lg bg-sky-50 dark:bg-sky-950/60 grid place-items-center text-sky-500 dark:text-sky-400 text-xl">
                📱
            </div>
            <p class="text-xs sm:text-sm font-black text-slate-800 dark:text-slate-200">{{ __('messages.no_transactions_found') }}</p>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 max-w-sm mx-auto">ဖုန်းဘေလ်ဖြည့်ရန် သို့မဟုတ် ငွေဖြည့်ကတ်ရောင်းချရန် အပေါ်ရှိ ခလုတ်များကို အသုံးပြုပါ</p>
            <button type="button" @click="openSalesModal('topup')" class="h-7 px-3 rounded-md bg-sky-600 hover:bg-sky-500 text-white text-xs font-black shadow-2xs transition inline-flex items-center gap-1.5 mt-3 active:scale-95 cursor-pointer">
                <span>⚡ {{ __('messages.eload_quick_topup') }}</span>
            </button>
        </div>
    @else
        {{-- Desktop Table View --}}
        <div x-show="viewMode === 'table'" class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        <tr>
                            <th class="px-3 py-2 min-w-[130px]">{{ __('messages.date') }} / Ref</th>
                            <th class="px-3 py-2 min-w-[80px]">{{ __('messages.eload_operator') }}</th>
                            <th class="px-3 py-2 min-w-[140px]">{{ __('messages.phone_number') }}</th>
                            <th class="px-3 py-2 min-w-[140px]">အမျိုးအစား / ပက်ကေ့ချ်</th>
                            <th class="px-3 py-2 min-w-[110px] text-right">{{ __('messages.amount') }}</th>
                            <th class="px-3 py-2 min-w-[100px] text-right">{{ __('messages.cost') }}</th>
                            <th class="px-3 py-2 min-w-[100px] text-right text-emerald-600 dark:text-emerald-400">{{ __('messages.profit') }}</th>
                            <th class="px-3 py-2 min-w-[90px]">{{ __('messages.payment_method') }}</th>
                            <th class="px-3 py-2 min-w-[90px]">{{ __('messages.status') }}</th>
                            <th class="px-3 py-2 min-w-[80px] text-right">{{ __('messages.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($transactions as $tx)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">
                                <td class="px-3 py-2 align-middle">
                                    <div class="font-bold text-slate-900 dark:text-slate-100 text-xs">{{ $tx->occurred_at->format('d M, h:i A') }}</div>
                                    <div class="text-[10px] font-mono text-slate-400 dark:text-slate-500">{{ $tx->ref_no }}</div>
                                </td>

                                <td class="px-3 py-2 align-middle">
                                    @php
                                        $op = strtolower($tx->operator);
                                        $badgeStyle = match($op) {
                                            'mpt'     => 'bg-amber-50 text-amber-900 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60',
                                            'atom'    => 'bg-sky-50 text-sky-900 dark:bg-sky-950/60 dark:text-sky-300 border border-sky-200 dark:border-sky-800/60',
                                            'ooredoo' => 'bg-rose-50 text-rose-900 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800/60',
                                            'mytel'   => 'bg-orange-50 text-orange-900 dark:bg-orange-950/60 dark:text-orange-300 border border-orange-200 dark:border-orange-800/60',
                                            default   => 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-black {{ $badgeStyle }} uppercase">
                                        {{ $tx->operator }}
                                    </span>
                                </td>

                                <td class="px-3 py-2 align-middle">
                                    <div class="font-bold text-slate-900 dark:text-slate-100 text-xs font-mono">{{ $tx->phone_number }}</div>
                                    @if($tx->customer_name)
                                        <div class="text-[10px] text-slate-500 dark:text-slate-400 truncate">{{ $tx->customer_name }}</div>
                                    @endif
                                </td>

                                <td class="px-3 py-2 align-middle">
                                    <div class="font-bold text-slate-800 dark:text-slate-200 text-xs">{{ $tx->typeLabel() }}</div>
                                    @if($tx->package_name)
                                        <div class="text-[10px] text-slate-400 dark:text-slate-500 truncate max-w-[180px]">{{ $tx->package_name }}</div>
                                    @endif
                                </td>

                                <td class="px-3 py-2 align-middle text-right font-mono font-black text-slate-900 dark:text-slate-100 tabular-nums">
                                    Ks {{ number_format((float) $tx->amount) }}
                                </td>

                                <td class="px-3 py-2 align-middle text-right font-mono text-slate-500 dark:text-slate-400 tabular-nums text-[11px]">
                                    Ks {{ number_format((float) $tx->cost) }}
                                </td>

                                <td class="px-3 py-2 align-middle text-right font-mono font-black text-emerald-600 dark:text-emerald-400 tabular-nums">
                                    +Ks {{ number_format((float) $tx->profit) }}
                                </td>

                                <td class="px-3 py-2 align-middle">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 uppercase">
                                        {{ $tx->payment_method }}
                                    </span>
                                </td>

                                <td class="px-3 py-2 align-middle">
                                    @if($tx->status === 'completed')
                                        <span class="inline-flex items-center px-1.5 py-0.5 text-[9px] font-bold rounded-full bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60">
                                            {{ __('messages.completed') }}
                                        </span>
                                    @elseif($tx->status === 'refunded')
                                        <span class="inline-flex items-center px-1.5 py-0.5 text-[9px] font-bold rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                            {{ __('messages.refunded') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.5 text-[9px] font-bold rounded-full bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60">
                                            {{ ucfirst($tx->status) }}
                                        </span>
                                    @endif
                                </td>

                                <td class="px-3 py-2 align-middle text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button"
                                                @click="printSlip('{{ route('store.admin.eload.slip', [...$storeRouteParams, 'id' => $tx->id]) }}')"
                                                class="h-6 px-1.5 rounded text-[10px] font-bold text-slate-600 hover:text-sky-600 bg-slate-50 dark:bg-slate-800 hover:bg-sky-50 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition inline-flex items-center gap-0.5 cursor-pointer"
                                                title="{{ __('messages.print_slip') }}">
                                            <span>🖨️</span>
                                        </button>
                                        @if($tx->status === 'completed')
                                            <form method="POST" action="{{ route('store.admin.eload.status', [...$storeRouteParams, 'id' => $tx->id]) }}" onsubmit="return confirm('{{ __('messages.eload_refund_confirm') }}');">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="refunded">
                                                <button type="submit" class="h-6 px-1.5 rounded text-[10px] font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/60 border border-rose-200 dark:border-rose-900/40 transition inline-flex items-center cursor-pointer" title="{{ __('messages.refund') }}">
                                                    <span>↩️</span>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Cards Grid View --}}
        <div x-show="viewMode === 'card'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-0.5 sm:gap-1">
            @foreach ($transactions as $tx)
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg p-2.5 shadow-2xs hover:shadow-xs transition flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between gap-1.5">
                            <span class="font-bold text-xs uppercase px-1.5 py-0.5 rounded {{ match(strtolower($tx->operator)) { 'mpt' => 'bg-amber-100 text-amber-800', 'atom' => 'bg-sky-100 text-sky-800', 'ooredoo' => 'bg-rose-100 text-rose-800', 'mytel' => 'bg-orange-100 text-orange-800', default => 'bg-slate-100' } }}">
                                {{ $tx->operator }}
                            </span>
                            @if($tx->status === 'completed')
                                <span class="inline-flex items-center px-1.5 py-0.5 text-[9px] font-bold rounded-full bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300">
                                    {{ __('messages.completed') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-1.5 py-0.5 text-[9px] font-bold rounded-full bg-slate-100 text-slate-600">
                                    {{ ucfirst($tx->status) }}
                                </span>
                            @endif
                        </div>
                        <div class="mt-1.5">
                            <p class="font-mono text-xs font-black text-slate-900 dark:text-slate-100">{{ $tx->phone_number }}</p>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">{{ $tx->typeLabel() }} · {{ $tx->package_name ?: 'Top-up' }}</p>
                        </div>
                        <div class="mt-2 flex items-center justify-between gap-1.5 text-xs">
                            <div>
                                <p class="text-[9px] text-slate-400 uppercase font-bold">{{ __('messages.amount') }}</p>
                                <p class="font-mono text-xs font-black text-slate-900 dark:text-slate-100">Ks {{ number_format((float) $tx->amount) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[9px] text-slate-400 uppercase font-bold">{{ __('messages.profit') }}</p>
                                <p class="font-mono text-xs font-black text-emerald-600 dark:text-emerald-400">+Ks {{ number_format((float) $tx->profit) }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-2 pt-1.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-1.5">
                        <span class="text-[10px] text-slate-400">{{ $tx->occurred_at->format('d M, h:i A') }}</span>
                        <div class="flex items-center gap-1">
                            <button type="button" @click="printSlip('{{ route('store.admin.eload.slip', [...$storeRouteParams, 'id' => $tx->id]) }}')"
                                    class="h-6 px-2 rounded text-[10px] font-bold text-slate-600 hover:text-sky-600 bg-slate-50 dark:bg-slate-800 hover:bg-sky-50 border border-slate-200 dark:border-slate-700 inline-flex items-center gap-0.5 cursor-pointer">
                                <span>🖨️ Slip</span>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-1">{{ $transactions->links() }}</div>
    @endif

    {{-- ============================================================
         5. MODALS & FLYOUTS (Compact 2px Style)
         ============================================================ --}}

    {{-- MODAL 1: UNIFIED SALES MODAL --}}
    <div x-show="openTopupModal"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-3 bg-slate-900/60 backdrop-blur-xs"
         style="display: none;">
        <div @click.away="openTopupModal = false"
             class="w-full max-w-lg rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-xl space-y-3 max-h-[90vh] overflow-y-auto">
            {{-- Modal Header & Tabs --}}
            <div class="border-b border-slate-100 dark:border-slate-800 pb-2 space-y-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-md bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 grid place-items-center text-xs font-bold">
                            📱
                        </span>
                        <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100">
                            ဖုန်းဆိုင် အမြန်ရောင်းချမှု မှတ်တမ်း
                        </h3>
                    </div>
                    <button type="button" @click="openTopupModal = false" class="w-6 h-6 rounded text-slate-400 hover:text-slate-600 grid place-items-center text-xs cursor-pointer">
                        ✕
                    </button>
                </div>

                {{-- Mode Switcher Tabs --}}
                <div class="grid grid-cols-4 gap-0.5 p-0.5 bg-slate-100 dark:bg-slate-800 rounded-md text-[11px] font-bold">
                    <button type="button" @click="switchTab('topup')" :class="activeTab === 'topup' ? 'bg-white dark:bg-slate-900 text-sky-600 dark:text-sky-400 shadow-2xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'" class="py-1 px-1 rounded transition text-center cursor-pointer">
                        📱 ဘေလ်ဖြည့်
                    </button>
                    <button type="button" @click="switchTab('data_pack')" :class="activeTab === 'data_pack' ? 'bg-white dark:bg-slate-900 text-sky-600 dark:text-sky-400 shadow-2xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'" class="py-1 px-1 rounded transition text-center cursor-pointer">
                        🌐 ဒေတာ
                    </button>
                    <button type="button" @click="switchTab('pin_code')" :class="activeTab === 'pin_code' ? 'bg-white dark:bg-slate-900 text-amber-600 dark:text-amber-400 shadow-2xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'" class="py-1 px-1 rounded transition text-center cursor-pointer">
                        💳 ငွေဖြည့်ကတ်
                    </button>
                    <button type="button" @click="switchTab('sim_card')" :class="activeTab === 'sim_card' ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 shadow-2xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'" class="py-1 px-1 rounded transition text-center cursor-pointer">
                        🪪 ဆင်းမ်ကတ်
                    </button>
                </div>
            </div>

            <form method="POST" action="{{ route('store.admin.eload.store', $storeRouteParams) }}" class="space-y-3">
                @csrf
                <input type="hidden" name="type" :value="form.type">

                {{-- Operator Selector Chips --}}
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                        အော်ပရေတာ *
                    </label>
                    <div class="grid grid-cols-4 gap-1">
                        <template x-for="op in operators" :key="op.key">
                            <button type="button"
                                    @click="selectOperator(op.key)"
                                    :class="form.operator === op.key ? op.activeClass : 'border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300'"
                                    class="py-1.5 px-2 rounded-lg border text-xs font-black uppercase tracking-wider flex flex-col items-center justify-center gap-0.5 transition cursor-pointer active:scale-95">
                                <span x-text="op.name"></span>
                                <span class="text-[9px] font-medium opacity-75" x-text="op.discount + '% အမြတ်'"></span>
                            </button>
                        </template>
                    </div>
                    <input type="hidden" name="operator" :value="form.operator" required>
                </div>

                {{-- TAB 1: E-LOAD / PHONE TOP-UP --}}
                <div x-show="activeTab === 'topup'" class="space-y-2">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">ဖုန်းနံပါတ် (Auto Detect) *</label>
                        <input type="text" name="phone_number" x-model="form.phone_number" @input="detectOperator()" :required="activeTab === 'topup' || activeTab === 'data_pack'" placeholder="09xxxxxxxxx"
                               class="w-full h-8 px-2.5 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-mono font-bold text-slate-900 dark:text-slate-100 focus:ring-1 focus:ring-sky-500">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">ဖြည့်သွင်းမည့် ဘေလ်ပမာဏ *</label>
                        <div class="grid grid-cols-4 gap-1 mb-1">
                            <template x-for="amt in topupPresets" :key="amt">
                                <button type="button" @click="setTopupAmount(amt)"
                                        :class="form.amount == amt ? 'bg-sky-600 text-white font-black shadow-2xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold hover:bg-slate-200'"
                                        class="py-1 px-1 rounded text-[11px] transition text-center cursor-pointer active:scale-95">
                                    <span x-text="Number(amt).toLocaleString() + ' Ks'"></span>
                                </button>
                            </template>
                        </div>
                        <input type="number" name="amount" x-model="form.amount" @input="calculateProfit()" min="500" step="100" required
                               class="w-full h-8 px-2.5 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-mono font-bold text-slate-900 dark:text-slate-100 focus:ring-1 focus:ring-sky-500">
                    </div>
                </div>

                {{-- TAB 2: DATA PACKS --}}
                <div x-show="activeTab === 'data_pack'" class="space-y-2">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">ဖုန်းနံပါတ် *</label>
                        <input type="text" x-model="form.phone_number" @input="detectOperator()" placeholder="09xxxxxxxxx"
                               class="w-full h-8 px-2.5 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-mono font-bold text-slate-900 dark:text-slate-100 focus:ring-1 focus:ring-sky-500">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">ဒေတာ ပက်ကေ့ချ် ရွေးချယ်ရန် *</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-1 max-h-40 overflow-y-auto p-0.5">
                            <template x-for="pack in (dataPacksMap[form.operator] || [])" :key="pack.name">
                                <button type="button" @click="selectDataPack(pack.name, pack.amount)"
                                        :class="form.package_name === pack.name ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-900 dark:text-indigo-200 ring-1 ring-indigo-500' : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50'"
                                        class="p-2 rounded-md border text-left flex flex-col justify-between gap-0.5 transition cursor-pointer">
                                    <span class="text-[11px] font-bold truncate" x-text="pack.name"></span>
                                    <span class="text-[11px] font-black text-indigo-600 dark:text-indigo-400" x-text="Number(pack.amount).toLocaleString() + ' Ks'"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- TAB 3: TOP-UP SCRATCH CARDS --}}
                <div x-show="activeTab === 'pin_code'" class="space-y-2">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">ကတ်တန်ဖိုး *</label>
                            <select x-model="form.card_denom" @change="updateCardCalculation()" class="w-full h-8 px-2 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold">
                                <option value="1000">1,000 Ks ကတ်</option>
                                <option value="3000">3,000 Ks ကတ်</option>
                                <option value="5000">5,000 Ks ကတ်</option>
                                <option value="10000">10,000 Ks ကတ်</option>
                                <option value="20000">20,000 Ks ကတ်</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">အရေအတွက် (ကတ်) *</label>
                            <input type="number" x-model="form.card_qty" @input="updateCardCalculation()" min="1" class="w-full h-8 px-2.5 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">Card Serial / PIN</label>
                        <input type="text" x-model="form.card_serial" @input="updateCardCalculation()" placeholder="SN: 9876543210..." class="w-full h-8 px-2.5 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-mono">
                    </div>
                </div>

                {{-- TAB 4: SIM CARD --}}
                <div x-show="activeTab === 'sim_card'" class="space-y-2">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">ဆင်းမ်ကတ် ဖုန်းနံပါတ် *</label>
                            <input type="text" x-model="form.phone_number" placeholder="09xxxxxxxxx" class="w-full h-8 px-2.5 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-mono font-bold">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">ရောင်းဈေး (ကျပ်) *</label>
                            <input type="number" x-model="form.sim_price" @input="updateSimCalculation()" min="0" step="500" class="w-full h-8 px-2.5 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">မူရင်းအရင်း (ကျပ်)</label>
                            <input type="number" x-model="form.sim_cost" @input="updateSimCalculation()" min="0" step="500" class="w-full h-8 px-2.5 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">ဝယ်ယူသူ မှတ်ပုံတင် (NRC)</label>
                            <input type="text" x-model="form.sim_nrc" @input="updateSimCalculation()" placeholder="12/ဥကမ(နိုင်)123456" class="w-full h-8 px-2.5 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">SIM ICCID (Barcode Scan)</label>
                        <input type="text" x-model="form.sim_iccid" @input="updateSimCalculation()" placeholder="89950000000000000000" class="w-full h-8 px-2.5 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-mono">
                    </div>
                </div>

                {{-- Shared Fields --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1 border-t border-slate-100 dark:border-slate-800">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">ဝယ်ယူသူ အမည်</label>
                        <input type="text" name="customer_name" x-model="form.customer_name" placeholder="Customer Name" class="w-full h-8 px-2.5 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">ငွေပေးချေမှု ပုံစံ *</label>
                        <select name="payment_method" x-model="form.payment_method" class="w-full h-8 px-2 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold">
                            <option value="cash">💵 Cash (ငွေသား)</option>
                            <option value="kpay">📱 KBZPay (KPay)</option>
                            <option value="wavepay">💛 WavePay</option>
                            <option value="cbpay">🏦 CBPay</option>
                            <option value="ayapay">🔴 AYAPay</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>

                {{-- Calculation Card --}}
                <div class="p-2.5 rounded-md bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 flex items-center justify-between text-xs">
                    <div>
                        <span class="text-slate-500 dark:text-slate-400">ကျသင့်ငွေ: </span>
                        <span class="font-black text-slate-900 dark:text-slate-100 font-mono" x-text="Number(form.amount || 0).toLocaleString() + ' Ks'"></span>
                        <span class="text-slate-400 text-[10px] ml-1" x-text="'(အရင်း: ' + Number(form.cost || 0).toLocaleString() + ' Ks)'"></span>
                    </div>
                    <div class="text-right">
                        <span class="text-slate-500 dark:text-slate-400">အမြတ်: </span>
                        <span class="font-black text-emerald-600 dark:text-emerald-400 font-mono" x-text="'+' + Number(form.profit || 0).toLocaleString() + ' Ks'"></span>
                    </div>
                </div>

                {{-- Hidden Fields --}}
                <input type="hidden" name="phone_number" :value="form.phone_number">
                <input type="hidden" name="amount" :value="form.amount">
                <input type="hidden" name="package_name" :value="form.package_name">
                <input type="hidden" name="cost" :value="form.cost">
                <input type="hidden" name="discount_percent" :value="form.discount_percent">
                <input type="hidden" name="notes" :value="form.notes">

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-1.5 pt-1">
                    <button type="button" @click="openTopupModal = false" class="h-8 px-3 rounded-md border border-slate-300 dark:border-slate-700 text-xs font-bold text-slate-600 hover:bg-slate-50 cursor-pointer">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit" class="h-8 px-4 rounded-md bg-sky-600 hover:bg-sky-500 text-xs font-black text-white shadow-2xs transition inline-flex items-center gap-1 active:scale-95 cursor-pointer">
                        <span>✓ ရောင်းချမှု မှတ်တမ်းတင်မည်</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 2: REFILL FLOAT MODAL --}}
    <div x-show="openFloatModal"
         x-transition
         class="fixed inset-0 z-50 flex items-center justify-center p-3 bg-slate-900/60 backdrop-blur-xs"
         style="display: none;">
        <div @click.away="openFloatModal = false"
             class="w-full max-w-sm rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-xl space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-md bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 grid place-items-center text-xs font-bold">
                        💳
                    </span>
                    <div>
                        <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100">
                            Float လက်ကျန်ငွေ ထည့်သွင်းခြင်း
                        </h3>
                        <p class="text-[10px] text-slate-500" x-text="refillData.accountName"></p>
                    </div>
                </div>
                <button type="button" @click="openFloatModal = false" class="w-6 h-6 rounded text-slate-400 hover:text-slate-600 grid place-items-center text-xs cursor-pointer">
                    ✕
                </button>
            </div>

            <form method="POST" action="{{ route('store.admin.eload.refill', $storeRouteParams) }}" class="space-y-2.5">
                @csrf
                <input type="hidden" name="eload_account_id" :value="refillData.accountId">

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">ဖြည့်သွင်းမည့် ပမာဏ (ကျပ်) *</label>
                    <div class="grid grid-cols-4 gap-1 mb-1">
                        <template x-for="amt in [100000, 300000, 500000, 1000000]" :key="amt">
                            <button type="button" @click="setRefillAmount(amt)"
                                    :class="refillData.amount == amt ? 'bg-emerald-600 text-white font-black' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold'"
                                    class="py-1 px-1 rounded text-[10px] transition text-center cursor-pointer"
                                    x-text="Number(amt).toLocaleString() + ' Ks'">
                            </button>
                        </template>
                    </div>
                    <input type="number" name="amount" x-model="refillData.amount" min="1000" step="1000" required
                           class="w-full h-8 px-2.5 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-mono font-bold text-slate-900 dark:text-slate-100 focus:ring-1 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">မှတ်ချက် (ဘဏ်လွှဲ / Agent Float)</label>
                    <input type="text" name="notes" x-model="refillData.notes" placeholder="e.g. KBZ Bank လွှဲပြောင်းဖြည့်သွင်း"
                           class="w-full h-8 px-2.5 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs">
                </div>

                <div class="flex items-center justify-end gap-1.5 pt-1">
                    <button type="button" @click="openFloatModal = false" class="h-7 px-3 rounded-md border border-slate-300 text-xs font-bold text-slate-600 cursor-pointer">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit" class="h-7 px-4 rounded-md bg-emerald-600 hover:bg-emerald-500 text-xs font-black text-white shadow-2xs transition inline-flex items-center gap-1 cursor-pointer">
                        + Float ဖြည့်သွင်းမည်
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 3: OPERATOR ACCOUNTS MANAGEMENT MODAL --}}
    <div x-show="openAccountsModal"
         x-transition
         class="fixed inset-0 z-50 flex items-center justify-center p-3 bg-slate-900/60 backdrop-blur-xs"
         style="display: none;">
        <div @click.away="openAccountsModal = false"
             class="w-full max-w-xl rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-xl space-y-3 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-md bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 grid place-items-center text-xs font-bold">
                        🏦
                    </span>
                    <div>
                        <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100">
                            အော်ပရေတာ Float အကောင့်များ စီမံခန့်ခွဲခြင်း
                        </h3>
                        <p class="text-[10px] text-slate-500">Agent SIM များ၏ အမြတ်ရာခိုင်နှုန်းနှင့် လက်ကျန်ငွေများ</p>
                    </div>
                </div>
                <button type="button" @click="openAccountsModal = false" class="w-6 h-6 rounded text-slate-400 hover:text-slate-600 grid place-items-center text-xs cursor-pointer">
                    ✕
                </button>
            </div>

            {{-- Accounts List Table --}}
            <div class="border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800 font-bold uppercase text-[10px] text-slate-500">
                        <tr>
                            <th class="py-2 px-2.5">အော်ပရေတာ</th>
                            <th class="py-2 px-2.5">အကောင့်အမည်</th>
                            <th class="py-2 px-2.5 text-right">လက်ကျန်ငွေ</th>
                            <th class="py-2 px-2.5 text-right">အမြတ် %</th>
                            <th class="py-2 px-2.5 text-right">လုပ်ဆောင်ချက်</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($accounts as $acc)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class="py-2 px-2.5 font-black uppercase text-[10px]">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800">
                                        {{ $acc->operator }}
                                    </span>
                                </td>
                                <td class="py-2 px-2.5">
                                    <div class="font-bold text-slate-900 dark:text-slate-100 text-xs">{{ $acc->name }}</div>
                                    <div class="text-[10px] font-mono text-slate-400">{{ $acc->phone_number }}</div>
                                </td>
                                <td class="py-2 px-2.5 text-right font-black font-mono text-xs">
                                    {{ number_format((float) $acc->balance) }} Ks
                                </td>
                                <td class="py-2 px-2.5 text-right font-bold text-emerald-600 text-xs">
                                    {{ $acc->discount_percent }}%
                                </td>
                                <td class="py-2 px-2.5 text-right">
                                    <button type="button" @click="openEditAccount({{ $acc }})" class="px-2 py-0.5 rounded text-[11px] font-bold text-sky-600 hover:bg-sky-50 cursor-pointer">
                                        ပြင်မည်
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 text-center text-slate-400 text-xs">
                                    အကောင့်များ မရှိသေးပါ။
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Edit / Add Account Form --}}
            <form method="POST" action="{{ route('store.admin.eload.accounts.store', $storeRouteParams) }}" class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-2">
                @csrf
                <input type="hidden" name="id" :value="accountForm.id">

                <div class="text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center justify-between">
                    <span x-text="accountForm.id ? 'အကောင့် အချက်အလက် ပြင်ဆင်ရန်' : '+ အကောင့် အသစ်ထည့်သွင်းရန်'"></span>
                    <button type="button" x-show="accountForm.id" @click="resetAccountForm()" class="text-[10px] text-slate-400 hover:text-slate-600">အသစ်ထည့်ရန် ပြောင်းမည်</button>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 mb-0.5">အော်ပရေတာ *</label>
                        <select name="operator" x-model="accountForm.operator" required class="w-full h-7 text-xs font-bold rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-2">
                            <option value="mpt">MPT</option>
                            <option value="atom">ATOM</option>
                            <option value="ooredoo">OOREDOO</option>
                            <option value="mytel">MYTEL</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 mb-0.5">အကောင့် အမည် *</label>
                        <input type="text" name="name" x-model="accountForm.name" required placeholder="e.g. MPT Agent SIM" class="w-full h-7 text-xs rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-2">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 mb-0.5">SIM ဖုန်းနံပါတ်</label>
                        <input type="text" name="phone_number" x-model="accountForm.phone_number" placeholder="09xxxxxxxxx" class="w-full h-7 text-xs font-mono rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-2">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 mb-0.5">လက်ကျန်ငွေ (Ks)</label>
                        <input type="number" name="balance" x-model="accountForm.balance" min="0" step="100" class="w-full h-7 text-xs font-mono font-bold rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-2">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 mb-0.5">အမြတ် %</label>
                        <input type="number" name="discount_percent" x-model="accountForm.discount_percent" min="0" max="100" step="0.1" class="w-full h-7 text-xs font-mono font-bold rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-2">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-1.5 pt-1">
                    <button type="submit" class="h-7 px-3 rounded-md bg-sky-600 hover:bg-sky-500 text-xs font-black text-white shadow-2xs transition inline-flex items-center gap-1 cursor-pointer">
                        သိမ်းဆည်းမည်
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
