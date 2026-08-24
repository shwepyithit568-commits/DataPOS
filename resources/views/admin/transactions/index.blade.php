@extends('layouts.admin.app')

@section('title', __('messages.transactions_title') . ' - ' . ($store->name ?? 'DataPOS'))

@section('content')
<script nonce="{{ $cspNonce }}">
window.transactionsModalManager = function () {
    return {
        showDepositModal: false,
        showWithdrawModal: false,
        showTransferModal: false,
        showAccountModal: false,

        depositForm: {
            to_account_id: '',
            amount: '',
            category: 'capital_injection',
            reference_no: '',
            payer_or_payee: '',
            notes: ''
        },

        withdrawForm: {
            from_account_id: '',
            amount: '',
            category: 'owner_drawing',
            reference_no: '',
            payer_or_payee: '',
            notes: ''
        },

        transferForm: {
            from_account_id: '',
            to_account_id: '',
            amount: '',
            fee: 0,
            category: 'internal_transfer',
            reference_no: '',
            notes: ''
        },

        openDeposit: function (accountId) {
            this.depositForm.to_account_id = accountId || '';
            this.depositForm.amount = '';
            this.showDepositModal = true;
        },

        openWithdraw: function (accountId) {
            this.withdrawForm.from_account_id = accountId || '';
            this.withdrawForm.amount = '';
            this.showWithdrawModal = true;
        },

        openTransfer: function (fromAccountId) {
            this.transferForm.from_account_id = fromAccountId || '';
            this.transferForm.to_account_id = '';
            this.transferForm.amount = '';
            this.transferForm.fee = 0;
            this.showTransferModal = true;
        },

        openAddAccount: function () {
            this.showAccountModal = true;
        }
    };
};
</script>

<div
    x-data="window.transactionsModalManager()"
    class="w-full space-y-5 sm:space-y-6"
>

    {{-- ============================================================
         PAGE HEADER
         ============================================================ --}}
    <div class="admin-page-header">
        <div class="min-w-0">
            <p class="text-[11px] font-black uppercase tracking-wider text-violet-600 dark:text-violet-400">
                {{ __('messages.sidebar_finance') ?? 'Finance & Accounts' }}
            </p>
            <h1 class="admin-page-title mt-0.5">
                {{ __('messages.transactions_title') }}
            </h1>
            <p class="admin-page-sub mt-1">
                {{ $store->name }} · {{ __('messages.transactions_subtitle') }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            {{-- Export CSV --}}
            <a href="{{ route('store.admin.transactions.export', array_merge(['store_slug' => $store->slug], request()->all())) }}"
               class="admin-secondary-btn">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span class="hidden sm:inline">{{ __('messages.transactions_export_csv') }}</span>
            </a>

            {{-- Fund Transfer Button --}}
            <button type="button"
                    @click="openTransfer()"
                    class="admin-secondary-btn border-violet-300 dark:border-violet-700 text-violet-700 dark:text-violet-300 hover:bg-violet-50 dark:hover:bg-violet-950/40">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
                <span>{{ __('messages.transactions_btn_transfer') }}</span>
            </button>

            {{-- Withdraw (Cash Out) Button --}}
            <button type="button"
                    @click="openWithdraw()"
                    class="admin-secondary-btn border-rose-300 dark:border-rose-700 text-rose-700 dark:text-rose-300 hover:bg-rose-50 dark:hover:bg-rose-950/40">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" />
                </svg>
                <span>{{ __('messages.transactions_btn_withdraw') }}</span>
            </button>

            {{-- Deposit (Cash In) Primary Button --}}
            <button type="button"
                    @click="openDeposit()"
                    class="admin-primary-btn bg-emerald-600 hover:bg-emerald-500">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                <span>{{ __('messages.transactions_btn_deposit') }}</span>
            </button>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if (session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 rounded-2xl text-sm text-emerald-800 dark:text-emerald-200 flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 rounded-2xl text-sm text-rose-800 dark:text-rose-200">
            <div class="font-bold mb-1">Please fix the following issues:</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ============================================================
         KPI Summary Hairline Grid
         ============================================================ --}}
    <div class="admin-hairline-grid grid-cols-2 sm:grid-cols-4">
        {{-- 1. Total Net Liquidity --}}
        <div class="admin-hairline-cell bg-violet-50/30 dark:bg-violet-950/20">
            <div class="admin-stat-label text-violet-600 dark:text-violet-400">{{ __('messages.transactions_total_liquidity') }}</div>
            <div class="admin-stat-value text-violet-700 dark:text-violet-300 font-mono">
                {{ number_format($stats['total_liquidity'], 2) }}
            </div>
            <div class="admin-stat-sub text-slate-500 font-medium">MMK · {{ $stats['accounts_count'] }} Accounts</div>
        </div>

        {{-- 2. Cash in Hand --}}
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-emerald-600 dark:text-emerald-400">{{ __('messages.transactions_cash_in_hand') }}</div>
            <div class="admin-stat-value text-emerald-600 dark:text-emerald-400 font-mono">
                {{ number_format($stats['cash_in_hand'], 2) }}
            </div>
            <div class="admin-stat-sub text-slate-400">Physical Cash Drawer</div>
        </div>

        {{-- 3. Banks & Digital Wallets --}}
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-blue-600 dark:text-blue-400">{{ __('messages.transactions_bank_wallets') }}</div>
            <div class="admin-stat-value text-blue-600 dark:text-blue-400 font-mono">
                {{ number_format($stats['bank_and_wallets'], 2) }}
            </div>
            <div class="admin-stat-sub text-slate-400">KPay, Wave & Bank Accounts</div>
        </div>

        {{-- 4. Period Activity --}}
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-slate-600 dark:text-slate-400">Deposits / Outflow</div>
            <div class="flex items-baseline gap-2 mt-0.5">
                <span class="text-sm sm:text-base font-extrabold font-mono text-emerald-600">+{{ number_format($stats['total_deposits']) }}</span>
                <span class="text-xs text-slate-400">/</span>
                <span class="text-sm sm:text-base font-extrabold font-mono text-rose-600">-{{ number_format($stats['total_outflow']) }}</span>
            </div>
            <div class="admin-stat-sub text-[11px] text-slate-400 truncate">{{ $stats['period_label'] }}</div>
        </div>
    </div>

    {{-- ============================================================
         FINANCIAL ACCOUNT CARDS (Quick Balances & Fast Action Triggers)
         ============================================================ --}}
    <div class="space-y-2.5">
        <div class="flex items-center justify-between">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 font-mono">
                {{ __('messages.transactions_all_accounts') }} ({{ count($accounts) }})
            </h2>
            <button type="button"
                    @click="openAddAccount()"
                    class="text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>{{ __('messages.transactions_btn_add_account') }}</span>
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">
            @foreach($accounts as $acc)
                @php
                    $isCash = $acc->account_type === 'cash';
                    $isWallet = $acc->account_type === 'mobile_wallet';
                    $isBank = $acc->account_type === 'bank_account';
                    $cardBg = $isCash ? 'border-emerald-200 dark:border-emerald-900/60 bg-emerald-50/30 dark:bg-emerald-950/20' : ($isWallet ? 'border-blue-200 dark:border-blue-900/60 bg-blue-50/30 dark:bg-blue-950/20' : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900');
                @endphp
                <div class="rounded-2xl border p-3.5 shadow-sm transition hover:shadow-md {{ $cardBg }} flex flex-col justify-between space-y-2">
                    <div>
                        <div class="flex items-center justify-between gap-1.5">
                            <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full {{ $isCash ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300' : ($isWallet ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300') }}">
                                {{ $acc->account_type }}
                            </span>
                            @if($acc->account_number)
                                <span class="text-[10px] font-mono text-slate-400 truncate max-w-[80px]" title="{{ $acc->account_number }}">
                                    {{ $acc->maskedAccountNumber() }}
                                </span>
                            @endif
                        </div>
                        <h3 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-slate-100 mt-1.5 truncate" title="{{ $acc->name }}">
                            {{ $acc->name }}
                        </h3>
                        <div class="font-mono font-black text-sm sm:text-base text-slate-900 dark:text-slate-100 mt-1">
                            {{ number_format((float) $acc->current_balance, 2) }} <span class="text-[10px] font-semibold text-slate-400 font-sans">MMK</span>
                        </div>
                    </div>

                    {{-- Card Quick Action Buttons --}}
                    <div class="flex items-center gap-1.5 pt-2 border-t border-slate-100 dark:border-slate-800/80">
                        <button type="button"
                                @click="openDeposit({{ $acc->id }})"
                                class="flex-1 py-1 text-[11px] font-bold rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 transition shadow-sm text-center">
                            + Deposit
                        </button>
                        <button type="button"
                                @click="openTransfer({{ $acc->id }})"
                                class="flex-1 py-1 text-[11px] font-bold rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-violet-600 dark:text-violet-400 hover:bg-violet-50 dark:hover:bg-violet-950/40 transition shadow-sm text-center">
                            ⇄ Transfer
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ============================================================
         FILTER TOOLBAR
         ============================================================ --}}
    <div class="rounded-2xl sm:rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm p-3.5 transition">
        <form method="GET" action="{{ route('store.admin.transactions.index', ['store_slug' => $store->slug]) }}">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-2.5 items-center">

                {{-- Search --}}
                <div class="relative lg:col-span-3">
                    <input type="text"
                           name="search"
                           value="{{ $filters['search'] ?? '' }}"
                           placeholder="{{ __('messages.search') }} Txn#, ref, payee..."
                           class="w-full pl-9 pr-3.5 py-2 min-h-[42px] border border-slate-200 dark:border-slate-700 rounded-xl text-xs sm:text-sm bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-violet-500 transition shadow-inner">
                    <svg class="w-4 h-4 text-slate-400 dark:text-slate-500 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                {{-- Account Filter --}}
                <div class="lg:col-span-3">
                    <select name="account_id" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 min-h-[42px] py-2 text-xs sm:text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:outline-none focus:ring-2 focus:ring-violet-500 cursor-pointer shadow-sm transition">
                        <option value="" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100">{{ __('messages.transactions_all_accounts') }}</option>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100" {{ ($filters['account_id'] ?? '') == $acc->id ? 'selected' : '' }}>
                                {{ $acc->name }} ({{ number_format((float) $acc->current_balance) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Type Filter --}}
                <div class="lg:col-span-2">
                    <select name="type" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 min-h-[42px] py-2 text-xs sm:text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:outline-none focus:ring-2 focus:ring-violet-500 cursor-pointer shadow-sm transition">
                        <option value="" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100">{{ __('messages.transactions_all_types') }}</option>
                        <option value="deposit" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100" {{ ($filters['type'] ?? '') === 'deposit' ? 'selected' : '' }}>
                            {{ __('messages.transactions_type_deposit') }}
                        </option>
                        <option value="withdrawal" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100" {{ ($filters['type'] ?? '') === 'withdrawal' ? 'selected' : '' }}>
                            {{ __('messages.transactions_type_withdrawal') }}
                        </option>
                        <option value="transfer" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100" {{ ($filters['type'] ?? '') === 'transfer' ? 'selected' : '' }}>
                            {{ __('messages.transactions_type_transfer') }}
                        </option>
                    </select>
                </div>

                {{-- Date Presets --}}
                <div class="lg:col-span-2">
                    <select name="preset" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 min-h-[42px] py-2 text-xs sm:text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:outline-none focus:ring-2 focus:ring-violet-500 cursor-pointer shadow-sm transition">
                        <option value="today" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100" {{ ($filters['preset'] ?? '') === 'today' ? 'selected' : '' }}>{{ __('messages.today') ?? 'Today' }}</option>
                        <option value="yesterday" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100" {{ ($filters['preset'] ?? '') === 'yesterday' ? 'selected' : '' }}>{{ __('messages.yesterday') ?? 'Yesterday' }}</option>
                        <option value="7days" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100" {{ ($filters['preset'] ?? '') === '7days' ? 'selected' : '' }}>{{ __('messages.7days') ?? '7 Days' }}</option>
                        <option value="this_month" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100" {{ ($filters['preset'] ?? 'this_month') === 'this_month' ? 'selected' : '' }}>{{ __('messages.this_month') ?? 'This Month' }}</option>
                        <option value="last_month" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100" {{ ($filters['preset'] ?? '') === 'last_month' ? 'selected' : '' }}>{{ __('messages.last_month') ?? 'Last Month' }}</option>
                        <option value="all" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100" {{ ($filters['preset'] ?? '') === 'all' ? 'selected' : '' }}>{{ __('messages.all_time') ?? 'All Time' }}</option>
                    </select>
                </div>

                {{-- Submit & Reset --}}
                <div class="lg:col-span-2 flex items-center gap-1.5 justify-end">
                    @if(!empty($filters['search']) || !empty($filters['account_id']) || !empty($filters['type']) || ($filters['preset'] ?? '') !== 'this_month')
                        <a href="{{ route('store.admin.transactions.index', ['store_slug' => $store->slug]) }}"
                           class="min-h-[42px] px-3 py-2 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition flex items-center shadow-sm">
                            {{ __('messages.reset') }}
                        </a>
                    @endif
                    <button type="submit"
                            class="min-h-[42px] px-4 py-2 bg-violet-600 hover:bg-violet-500 text-white rounded-xl text-xs sm:text-sm font-bold shadow-sm flex items-center gap-1.5 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <span>{{ __('messages.filter') }}</span>
                    </button>
                </div>

            </div>
        </form>
    </div>

    {{-- ============================================================
         TRANSACTIONS LEDGER TABLE
         ============================================================ --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h2 class="font-bold text-slate-900 dark:text-slate-100 font-outfit text-base">
                Transaction Ledger ({{ number_format($transactions->total()) }})
            </h2>
            <span class="text-xs font-mono text-slate-400">Page {{ $transactions->currentPage() }} of {{ $transactions->lastPage() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50/75 dark:bg-slate-800/50 text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400 font-semibold">
                    <tr>
                        <th class="px-4 py-3">{{ __('messages.date') }} & Txn#</th>
                        <th class="px-4 py-3">{{ __('messages.type') }}</th>
                        <th class="px-4 py-3">{{ __('messages.transactions_from_account') }} / {{ __('messages.transactions_to_account') }}</th>
                        <th class="px-4 py-3">{{ __('messages.transactions_category') }} & Payee</th>
                        <th class="px-4 py-3 text-right">{{ __('messages.transactions_amount') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('messages.transactions_fee') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($transactions as $txn)
                        @php
                            $isDep = $txn->isDeposit();
                            $isWdr = $txn->isWithdrawal();
                            $isTrf = $txn->isTransfer();
                        @endphp
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition">
                            {{-- Date & Txn # --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="font-bold text-slate-900 dark:text-slate-100 text-xs">
                                    {{ $txn->transaction_date->format('Y-m-d h:i A') }}
                                </div>
                                <div class="font-mono text-[11px] text-slate-400 mt-0.5">
                                    {{ $txn->transaction_number }}
                                </div>
                            </td>

                            {{-- Type Badge --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($isDep)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                                        Deposit
                                    </span>
                                @elseif($isWdr)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                                        Withdrawal
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-violet-100 text-violet-800 dark:bg-violet-950/60 dark:text-violet-300">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                        Transfer
                                    </span>
                                @endif
                            </td>

                            {{-- From / To Accounts --}}
                            <td class="px-4 py-3">
                                @if($isDep)
                                    <div class="text-xs font-bold text-emerald-700 dark:text-emerald-400 flex items-center gap-1">
                                        <span>→ {{ $txn->toAccount?->name ?? 'Account' }}</span>
                                    </div>
                                @elseif($isWdr)
                                    <div class="text-xs font-bold text-rose-700 dark:text-rose-400 flex items-center gap-1">
                                        <span>← {{ $txn->fromAccount?->name ?? 'Account' }}</span>
                                    </div>
                                @else
                                    <div class="text-xs font-semibold text-slate-800 dark:text-slate-200">
                                        <span class="text-slate-400">From:</span> {{ $txn->fromAccount?->name ?? '-' }}
                                    </div>
                                    <div class="text-xs font-bold text-violet-600 dark:text-violet-400 mt-0.5">
                                        <span class="text-slate-400 font-normal">To:</span> {{ $txn->toAccount?->name ?? '-' }}
                                    </div>
                                @endif
                            </td>

                            {{-- Category / Payee & Ref --}}
                            <td class="px-4 py-3">
                                <div class="font-bold text-slate-900 dark:text-slate-100 text-xs">
                                    {{ ucwords(str_replace('_', ' ', $txn->category ?? 'general')) }}
                                </div>
                                @if($txn->payer_or_payee || $txn->reference_no)
                                    <div class="text-[11px] text-slate-400 mt-0.5">
                                        {{ $txn->payer_or_payee }} {{ $txn->reference_no ? '· Ref: ' . $txn->reference_no : '' }}
                                    </div>
                                @endif
                                @if($txn->notes)
                                    <div class="text-[10px] text-slate-500 italic mt-0.5 line-clamp-1" title="{{ $txn->notes }}">
                                        {{ $txn->notes }}
                                    </div>
                                @endif
                            </td>

                            {{-- Amount --}}
                            <td class="px-4 py-3 text-right font-mono font-bold text-sm whitespace-nowrap">
                                @if($isDep)
                                    <span class="text-emerald-600 dark:text-emerald-400">+{{ number_format((float) $txn->amount, 2) }}</span>
                                @elseif($isWdr)
                                    <span class="text-rose-600 dark:text-rose-400">-{{ number_format((float) $txn->amount, 2) }}</span>
                                @else
                                    <span class="text-violet-700 dark:text-violet-300">{{ number_format((float) $txn->amount, 2) }}</span>
                                @endif
                            </td>

                            {{-- Fee --}}
                            <td class="px-4 py-3 text-right font-mono text-xs text-slate-400 whitespace-nowrap">
                                {{ (float) $txn->fee > 0 ? number_format((float) $txn->fee, 2) : '-' }}
                            </td>

                            {{-- Actions (Print Voucher) --}}
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('store.admin.transactions.voucher', ['store_slug' => $store->slug, 'transaction' => $txn->id]) }}"
                                   target="_blank"
                                   class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    <span>Voucher</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-16 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-slate-300 dark:text-slate-600 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <rect width="20" height="12" x="2" y="6" rx="2" stroke-width="1.5"/><circle cx="12" cy="12" r="2" stroke-width="1.5"/>
                                    </svg>
                                    <p class="text-sm font-semibold">{{ __('messages.transactions_no_records') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination Links --}}
        @if($transactions->hasPages())
            <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

    {{-- ============================================================
         1. DEPOSIT MODAL (Cash In)
         ============================================================ --}}
    <div x-show="showDepositModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4"
         x-transition>
        <div @click.away="showDepositModal = false"
             class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-md w-full p-6 space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-slate-100 font-outfit">{{ __('messages.transactions_btn_deposit') }}</h3>
                    <p class="text-xs text-slate-500">Record cash or bank inflow</p>
                </div>
            </div>

            <form method="POST" action="{{ route('store.admin.transactions.deposit', ['store_slug' => $store->slug]) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_to_account') }} *</label>
                    <select name="to_account_id" x-model="depositForm.to_account_id" required class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs sm:text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-emerald-500 shadow-sm">
                        <option value="">-- Select Destination Account --</option>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }} (Balance: {{ number_format((float) $acc->current_balance) }} MMK)</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_amount') }} *</label>
                    <input type="number" step="any" min="1" name="amount" x-model="depositForm.amount" required placeholder="0.00" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs sm:text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-mono font-bold focus:ring-2 focus:ring-emerald-500 shadow-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_category') }}</label>
                    <select name="category" x-model="depositForm.category" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs sm:text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-semibold focus:ring-2 focus:ring-emerald-500 shadow-sm">
                        <option value="capital_injection">Capital Injection (အရင်းမတည်ငွေ)</option>
                        <option value="debt_collection">Customer Debt Settlement (အကြွေးရငွေ)</option>
                        <option value="other_income">Other Income (အခြားဝင်ငွေ)</option>
                        <option value="bank_deposit">Bank Cash Deposit (ဘဏ်သွင်းငွေ)</option>
                        <option value="general_deposit">General Deposit (အထွေထွေငွေသွင်း)</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_payer_payee') }}</label>
                        <input type="text" name="payer_or_payee" x-model="depositForm.payer_or_payee" placeholder="Payer name" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_reference_no') }}</label>
                        <input type="text" name="reference_no" x-model="depositForm.reference_no" placeholder="Slip # / Ref" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 shadow-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_notes') }}</label>
                    <textarea name="notes" rows="2" x-model="depositForm.notes" placeholder="Optional remarks..." class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 shadow-sm"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="showDepositModal = false" class="px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-sm">
                        {{ __('messages.transactions_btn_deposit') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         2. WITHDRAWAL MODAL (Cash Out)
         ============================================================ --}}
    <div x-show="showWithdrawModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4"
         x-transition>
        <div @click.away="showWithdrawModal = false"
             class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-md w-full p-6 space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-slate-100 font-outfit">{{ __('messages.transactions_btn_withdraw') }}</h3>
                    <p class="text-xs text-slate-500">Record cash or bank withdrawal</p>
                </div>
            </div>

            <form method="POST" action="{{ route('store.admin.transactions.withdraw', ['store_slug' => $store->slug]) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_from_account') }} *</label>
                    <select name="from_account_id" x-model="withdrawForm.from_account_id" required class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs sm:text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-rose-500 shadow-sm">
                        <option value="">-- Select Source Account --</option>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }} (Balance: {{ number_format((float) $acc->current_balance) }} MMK)</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_amount') }} *</label>
                    <input type="number" step="any" min="1" name="amount" x-model="withdrawForm.amount" required placeholder="0.00" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs sm:text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-mono font-bold focus:ring-2 focus:ring-rose-500 shadow-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_category') }}</label>
                    <select name="category" x-model="withdrawForm.category" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs sm:text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-semibold focus:ring-2 focus:ring-rose-500 shadow-sm">
                        <option value="owner_drawing">Owner Drawing (ပိုင်ရှင်ထုတ်ငွေ)</option>
                        <option value="salary_advance">Salary Advance (လစာကြိုထုတ်)</option>
                        <option value="supplier_payment">Supplier Debt Settlement (ကုန်ဖိုးငွေချေ)</option>
                        <option value="petty_cash">Petty Cash (နေ့စဉ်အသေးသုံးငွေ)</option>
                        <option value="other_withdrawal">Other Withdrawal (အခြားငွေထုတ်)</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_payer_payee') }}</label>
                        <input type="text" name="payer_or_payee" x-model="withdrawForm.payer_or_payee" placeholder="Payee name" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_reference_no') }}</label>
                        <input type="text" name="reference_no" x-model="withdrawForm.reference_no" placeholder="Slip # / Ref" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 shadow-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_notes') }}</label>
                    <textarea name="notes" rows="2" x-model="withdrawForm.notes" placeholder="Optional remarks..." class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 shadow-sm"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="showWithdrawModal = false" class="px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold shadow-sm">
                        {{ __('messages.transactions_btn_withdraw') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         3. FUND TRANSFER MODAL (Account-to-Account)
         ============================================================ --}}
    <div x-show="showTransferModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4"
         x-transition>
        <div @click.away="showTransferModal = false"
             class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-md w-full p-6 space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-violet-100 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-slate-100 font-outfit">{{ __('messages.transactions_btn_transfer') }}</h3>
                    <p class="text-xs text-slate-500">Transfer funds between store accounts</p>
                </div>
            </div>

            <form method="POST" action="{{ route('store.admin.transactions.transfer', ['store_slug' => $store->slug]) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_from_account') }} *</label>
                    <select name="from_account_id" x-model="transferForm.from_account_id" required class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs sm:text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-violet-500 shadow-sm">
                        <option value="">-- Select Source Account --</option>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }} (Balance: {{ number_format((float) $acc->current_balance) }} MMK)</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_to_account') }} *</label>
                    <select name="to_account_id" x-model="transferForm.to_account_id" required class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs sm:text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-violet-500 shadow-sm">
                        <option value="">-- Select Destination Account --</option>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }} (Balance: {{ number_format((float) $acc->current_balance) }} MMK)</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_amount') }} *</label>
                        <input type="number" step="any" min="1" name="amount" x-model="transferForm.amount" required placeholder="0.00" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs sm:text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-mono font-bold focus:ring-2 focus:ring-violet-500 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_fee') }}</label>
                        <input type="number" step="any" min="0" name="fee" x-model="transferForm.fee" placeholder="0.00" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs sm:text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-mono focus:ring-2 focus:ring-violet-500 shadow-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_reference_no') }}</label>
                    <input type="text" name="reference_no" x-model="transferForm.reference_no" placeholder="Bank transaction / slip ref" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 shadow-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_notes') }}</label>
                    <textarea name="notes" rows="2" x-model="transferForm.notes" placeholder="Optional transfer remarks..." class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 shadow-sm"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="showTransferModal = false" class="px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-violet-600 hover:bg-violet-500 text-white text-xs font-bold shadow-sm">
                        {{ __('messages.transactions_btn_transfer') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         4. CREATE ACCOUNT MODAL
         ============================================================ --}}
    <div x-show="showAccountModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4"
         x-transition>
        <div @click.away="showAccountModal = false"
             class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-md w-full p-6 space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-blue-100 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-slate-100 font-outfit">{{ __('messages.transactions_btn_add_account') }}</h3>
                    <p class="text-xs text-slate-500">Add a new bank or wallet account</p>
                </div>
            </div>

            <form method="POST" action="{{ route('store.admin.transactions.account.store', ['store_slug' => $store->slug]) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_account_name') }} *</label>
                    <input type="text" name="name" required placeholder="e.g. Yoma Bank (Main)" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs sm:text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-blue-500 shadow-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_account_type') }} *</label>
                    <select name="account_type" required class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs sm:text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-blue-500 shadow-sm">
                        <option value="bank_account">{{ __('messages.transactions_type_bank') }}</option>
                        <option value="mobile_wallet">{{ __('messages.transactions_type_wallet') }}</option>
                        <option value="cash">{{ __('messages.transactions_type_cash') }}</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_account_number') }}</label>
                        <input type="text" name="account_number" placeholder="Account # or Phone" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_account_holder') }}</label>
                        <input type="text" name="account_holder" placeholder="Holder name" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 shadow-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_opening_balance') }}</label>
                    <input type="number" step="any" min="0" name="opening_balance" placeholder="0.00" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs font-mono bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 shadow-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_notes') }}</label>
                    <textarea name="notes" rows="2" placeholder="Optional notes..." class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 shadow-sm"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="showAccountModal = false" class="px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-sm">
                        {{ __('messages.transactions_btn_add_account') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
