@extends('layouts.admin.app')

@section('title', __('messages.transactions_title') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@section('content')
<div
    x-data="{
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

        openDeposit(accountId = '') {
            this.depositForm.to_account_id = accountId ? String(accountId) : '';
            this.depositForm.amount = '';
            this.depositForm.reference_no = '';
            this.depositForm.payer_or_payee = '';
            this.depositForm.notes = '';
            this.showDepositModal = true;
            this.$nextTick(() => {
                this.$refs.depositAmountInput?.focus();
            });
        },

        openWithdraw(accountId = '') {
            this.withdrawForm.from_account_id = accountId ? String(accountId) : '';
            this.withdrawForm.amount = '';
            this.withdrawForm.reference_no = '';
            this.withdrawForm.payer_or_payee = '';
            this.withdrawForm.notes = '';
            this.showWithdrawModal = true;
            this.$nextTick(() => {
                this.$refs.withdrawAmountInput?.focus();
            });
        },

        openTransfer(fromAccountId = '') {
            this.transferForm.from_account_id = fromAccountId ? String(fromAccountId) : '';
            this.transferForm.to_account_id = '';
            this.transferForm.amount = '';
            this.transferForm.fee = 0;
            this.transferForm.reference_no = '';
            this.transferForm.notes = '';
            this.showTransferModal = true;
            this.$nextTick(() => {
                this.$refs.transferAmountInput?.focus();
            });
        },

        openAddAccount() {
            this.showAccountModal = true;
            this.$nextTick(() => {
                this.$refs.accountNameInput?.focus();
            });
        }
    }"
    class="w-full space-y-2 sm:space-y-2.5"
>

    {{-- ============================================================
         1. COMPACT PAGE HEADER & ACTIONS
         ============================================================ --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 shadow-2xs">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-3">
            <div class="min-w-0">
                <h1 class="text-base sm:text-lg font-black tracking-tight text-slate-900 dark:text-slate-100 font-outfit truncate">
                    {{ __('messages.transactions_title') }}
                </h1>
                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 truncate">
                    {{ $store->name }} · {{ __('messages.transactions_subtitle') }}
                </p>
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-wrap items-center gap-1.5 shrink-0">
                {{-- Export CSV --}}
                <a href="{{ route('store.admin.transactions.export', array_merge(['store_slug' => $store->slug], request()->all())) }}"
                   class="inline-flex items-center justify-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-750 text-xs font-bold transition shadow-2xs">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span class="hidden sm:inline">{{ __('messages.transactions_export_csv') }}</span>
                </a>

                {{-- Fund Transfer Button --}}
                <button type="button"
                        @click="openTransfer()"
                        class="inline-flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg border border-violet-200 dark:border-violet-800/60 bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300 hover:bg-violet-100 dark:hover:bg-violet-900/60 text-xs font-bold transition shadow-2xs">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                    <span>{{ __('messages.transactions_btn_transfer') }}</span>
                </button>

                {{-- Withdraw (Cash Out) Button --}}
                <button type="button"
                        @click="openWithdraw()"
                        class="inline-flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg border border-rose-200 dark:border-rose-800/60 bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-xs font-bold transition shadow-2xs">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" />
                    </svg>
                    <span>{{ __('messages.transactions_btn_withdraw') }}</span>
                </button>

                {{-- Deposit (Cash In) Primary Button --}}
                <button type="button"
                        @click="openDeposit()"
                        class="inline-flex items-center justify-center gap-1 px-3 py-1.5 rounded-lg bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-bold shadow-sm shadow-emerald-950/20 transition active:scale-98">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>{{ __('messages.transactions_btn_deposit') }}</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if (session('success'))
        <div class="p-2.5 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 rounded-lg text-xs font-semibold text-emerald-800 dark:text-emerald-200 flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-2.5 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 rounded-lg text-xs text-rose-800 dark:text-rose-200">
            <div class="font-bold mb-1">Please fix the following issues:</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ============================================================
         2. KPI SUMMARY METRIC CARDS (4 Hairline Grid Cards)
         ============================================================ --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-2.5">
        {{-- 1. Total Net Liquidity --}}
        <div class="bg-gradient-to-br from-violet-50/70 to-indigo-50/40 dark:from-violet-950/30 dark:to-indigo-950/20 rounded-lg border border-violet-200/80 dark:border-violet-800/70 p-2.5 sm:p-3 shadow-2xs">
            <div class="text-[10px] sm:text-[11px] font-black uppercase tracking-wider text-violet-700 dark:text-violet-300">
                {{ __('messages.transactions_total_liquidity') }}
            </div>
            <div class="text-base sm:text-xl font-black font-mono tracking-tight text-violet-900 dark:text-violet-100 mt-1 tabular-nums">
                {{ number_format((float) $stats['total_liquidity'], 2) }}
            </div>
            <div class="text-[10px] text-violet-600/80 dark:text-violet-400 font-semibold mt-0.5 truncate">
                MMK · {{ $stats['accounts_count'] }} Accounts
            </div>
        </div>

        {{-- 2. Cash in Hand --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 shadow-2xs">
            <div class="text-[10px] sm:text-[11px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                {{ __('messages.transactions_cash_in_hand') }}
            </div>
            <div class="text-base sm:text-xl font-black font-mono tracking-tight text-emerald-700 dark:text-emerald-300 mt-1 tabular-nums">
                {{ number_format((float) $stats['cash_in_hand'], 2) }}
            </div>
            <div class="text-[10px] text-slate-400 mt-0.5 truncate">
                Physical Cash Drawer
            </div>
        </div>

        {{-- 3. Banks & Digital Wallets --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 shadow-2xs">
            <div class="text-[10px] sm:text-[11px] font-black uppercase tracking-wider text-sky-600 dark:text-sky-400">
                {{ __('messages.transactions_bank_wallets') }}
            </div>
            <div class="text-base sm:text-xl font-black font-mono tracking-tight text-sky-700 dark:text-sky-300 mt-1 tabular-nums">
                {{ number_format((float) $stats['bank_and_wallets'], 2) }}
            </div>
            <div class="text-[10px] text-slate-400 mt-0.5 truncate">
                KPay, Wave & Bank Accounts
            </div>
        </div>

        {{-- 4. Period Activity --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 shadow-2xs">
            <div class="text-[10px] sm:text-[11px] font-black uppercase tracking-wider text-slate-600 dark:text-slate-400">
                Inflow / Outflow
            </div>
            <div class="flex items-baseline gap-1.5 mt-1">
                <span class="text-xs sm:text-sm font-black font-mono text-emerald-600 tabular-nums">+{{ number_format((float) $stats['total_deposits']) }}</span>
                <span class="text-[10px] text-slate-400">/</span>
                <span class="text-xs sm:text-sm font-black font-mono text-rose-600 tabular-nums">-{{ number_format((float) $stats['total_outflow']) }}</span>
            </div>
            <div class="text-[10px] text-slate-400 mt-0.5 truncate">
                {{ $stats['period_label'] }}
            </div>
        </div>
    </div>

    {{-- ============================================================
         3. FINANCIAL ACCOUNT CARDS (Quick Balances & 1-Click Triggers)
         ============================================================ --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 shadow-2xs space-y-2">
        <div class="flex items-center justify-between">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-600 dark:text-slate-300 font-mono flex items-center gap-1.5">
                <span>🏦 {{ __('messages.transactions_all_accounts') }}</span>
                <span class="px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 text-[10px] text-slate-500 font-bold">{{ count($accounts) }}</span>
            </h2>
            <button type="button"
                    @click="openAddAccount()"
                    class="text-xs font-bold text-violet-600 dark:text-violet-400 hover:text-violet-700 flex items-center gap-1 transition">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>{{ __('messages.transactions_btn_add_account') }}</span>
            </button>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 sm:gap-2.5">
            @foreach($accounts as $acc)
                @php
                    $isCash = $acc->account_type === 'cash';
                    $isWallet = $acc->account_type === 'mobile_wallet';
                    $isBank = $acc->account_type === 'bank_account';
                    $cardBg = $isCash
                        ? 'border-emerald-200/80 dark:border-emerald-900/60 bg-emerald-50/20 dark:bg-emerald-950/20'
                        : ($isWallet
                            ? 'border-sky-200/80 dark:border-sky-900/60 bg-sky-50/20 dark:bg-sky-950/20'
                            : 'border-slate-200/80 dark:border-slate-800 bg-slate-50/40 dark:bg-slate-800/40');
                @endphp
                <div class="rounded-lg border p-2.5 {{ $cardBg }} flex flex-col justify-between space-y-2 shadow-2xs hover:shadow-xs transition">
                    <div>
                        <div class="flex items-center justify-between gap-1">
                            <span class="text-[9px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded {{ $isCash ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300' : ($isWallet ? 'bg-sky-100 text-sky-800 dark:bg-sky-900/60 dark:text-sky-300' : 'bg-slate-200/80 text-slate-700 dark:bg-slate-700 dark:text-slate-300') }}">
                                {{ $acc->account_type }}
                            </span>
                            @if($acc->account_number)
                                <span class="text-[9px] font-mono text-slate-400 truncate max-w-[65px]" title="{{ $acc->account_number }}">
                                    {{ $acc->maskedAccountNumber() }}
                                </span>
                            @endif
                        </div>
                        <h3 class="font-bold text-xs text-slate-900 dark:text-slate-100 mt-1 truncate" title="{{ $acc->name }}">
                            {{ $acc->name }}
                        </h3>
                        <div class="font-mono font-black text-xs sm:text-sm text-slate-900 dark:text-slate-100 mt-0.5 tabular-nums">
                            {{ number_format((float) $acc->current_balance, 2) }}
                            <span class="text-[9px] font-semibold text-slate-400 font-sans">MMK</span>
                        </div>
                    </div>

                    {{-- Card Quick Action Buttons --}}
                    <div class="flex items-center gap-1 pt-1.5 border-t border-slate-200/60 dark:border-slate-800/80">
                        <button type="button"
                                @click="openDeposit({{ $acc->id }})"
                                class="flex-1 py-0.5 text-[10px] font-bold rounded bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 transition shadow-2xs text-center">
                            + In
                        </button>
                        <button type="button"
                                @click="openTransfer({{ $acc->id }})"
                                class="flex-1 py-0.5 text-[10px] font-bold rounded bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-violet-600 dark:text-violet-400 hover:bg-violet-50 dark:hover:bg-violet-950/40 transition shadow-2xs text-center">
                            ⇄ Move
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ============================================================
         4. COMPACT FILTER TOOLBAR
         ============================================================ --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 shadow-2xs">
        <form method="GET" action="{{ route('store.admin.transactions.index', ['store_slug' => $store->slug]) }}">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-2 items-center">

                {{-- Search --}}
                <div class="relative lg:col-span-3">
                    <input type="text"
                           name="search"
                           value="{{ $filters['search'] ?? '' }}"
                           placeholder="{{ __('messages.search') }} Txn#, ref, payee..."
                           class="w-full pl-8 pr-3 py-1.5 border border-slate-200 dark:border-slate-700 rounded-lg text-xs bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-violet-500 transition shadow-2xs">
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                {{-- Account Filter --}}
                <div class="lg:col-span-3">
                    <select name="account_id" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-semibold focus:outline-none focus:ring-2 focus:ring-violet-500 cursor-pointer shadow-2xs">
                        <option value="">{{ __('messages.transactions_all_accounts') }}</option>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}" {{ ($filters['account_id'] ?? '') == $acc->id ? 'selected' : '' }}>
                                {{ $acc->name }} ({{ number_format((float) $acc->current_balance) }} MMK)
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Type Filter --}}
                <div class="lg:col-span-2">
                    <select name="type" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-semibold focus:outline-none focus:ring-2 focus:ring-violet-500 cursor-pointer shadow-2xs">
                        <option value="">{{ __('messages.transactions_all_types') }}</option>
                        <option value="deposit" {{ ($filters['type'] ?? '') === 'deposit' ? 'selected' : '' }}>
                            {{ __('messages.transactions_type_deposit') }}
                        </option>
                        <option value="withdrawal" {{ ($filters['type'] ?? '') === 'withdrawal' ? 'selected' : '' }}>
                            {{ __('messages.transactions_type_withdrawal') }}
                        </option>
                        <option value="transfer" {{ ($filters['type'] ?? '') === 'transfer' ? 'selected' : '' }}>
                            {{ __('messages.transactions_type_transfer') }}
                        </option>
                    </select>
                </div>

                {{-- Date Presets --}}
                <div class="lg:col-span-2">
                    <select name="preset" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-semibold focus:outline-none focus:ring-2 focus:ring-violet-500 cursor-pointer shadow-2xs">
                        <option value="today" {{ ($filters['preset'] ?? '') === 'today' ? 'selected' : '' }}>{{ __('messages.today') ?? 'Today' }}</option>
                        <option value="yesterday" {{ ($filters['preset'] ?? '') === 'yesterday' ? 'selected' : '' }}>{{ __('messages.yesterday') ?? 'Yesterday' }}</option>
                        <option value="7days" {{ ($filters['preset'] ?? '') === '7days' ? 'selected' : '' }}>{{ __('messages.7days') ?? '7 Days' }}</option>
                        <option value="this_month" {{ ($filters['preset'] ?? 'this_month') === 'this_month' ? 'selected' : '' }}>{{ __('messages.this_month') ?? 'This Month' }}</option>
                        <option value="last_month" {{ ($filters['preset'] ?? '') === 'last_month' ? 'selected' : '' }}>{{ __('messages.last_month') ?? 'Last Month' }}</option>
                        <option value="all" {{ ($filters['preset'] ?? '') === 'all' ? 'selected' : '' }}>{{ __('messages.all_time') ?? 'All Time' }}</option>
                    </select>
                </div>

                {{-- Submit & Reset Buttons --}}
                <div class="lg:col-span-2 flex items-center gap-1.5 justify-end">
                    @if(!empty($filters['search']) || !empty($filters['account_id']) || !empty($filters['type']) || ($filters['preset'] ?? '') !== 'this_month')
                        <a href="{{ route('store.admin.transactions.index', ['store_slug' => $store->slug]) }}"
                           class="px-2.5 py-1.5 text-xs font-bold rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition shadow-2xs">
                            {{ __('messages.reset') }}
                        </a>
                    @endif
                    <button type="submit"
                            class="px-3.5 py-1.5 bg-violet-600 hover:bg-violet-500 text-white rounded-lg text-xs font-bold shadow-2xs flex items-center gap-1 transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <span>{{ __('messages.filter') }}</span>
                    </button>
                </div>

            </div>
        </form>
    </div>

    {{-- ============================================================
         5. TRANSACTIONS LEDGER TABLE (Dense, Responsive, Swipeable)
         ============================================================ --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs overflow-hidden">
        {{-- Table Top Header --}}
        <div class="px-3 py-2 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/40">
            <h2 class="font-bold text-slate-900 dark:text-slate-100 font-outfit text-xs sm:text-sm flex items-center gap-2">
                <span>Transaction Ledger</span>
                <span class="px-2 py-0.5 rounded-full bg-slate-200/70 dark:bg-slate-700 text-[10px] font-mono text-slate-700 dark:text-slate-300">
                    {{ number_format($transactions->total()) }}
                </span>
            </h2>
            <span class="text-[11px] font-mono text-slate-400">Page {{ $transactions->currentPage() }} / {{ $transactions->lastPage() }}</span>
        </div>

        {{-- Mobile Swipe Hint Bar --}}
        <div class="sm:hidden px-2.5 py-1 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200/60 dark:border-slate-800 text-[10px] text-slate-400 flex items-center justify-between">
            <span class="flex items-center gap-1">
                <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                <span>Swipe horizontally to view all columns</span>
            </span>
            <span class="font-mono text-[9px] uppercase tracking-wider text-slate-400">Scrollable</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300 border-collapse min-w-[760px]">
                <thead class="sticky top-0 bg-slate-50/90 dark:bg-slate-800/80 backdrop-blur-xs text-[10px] sm:text-[11px] uppercase tracking-wider text-slate-500 dark:text-slate-400 font-black border-b border-slate-200/80 dark:border-slate-800">
                    <tr>
                        <th class="px-3 py-2.5">{{ __('messages.date') }} & Txn#</th>
                        <th class="px-3 py-2.5">{{ __('messages.type') }}</th>
                        <th class="px-3 py-2.5">{{ __('messages.transactions_from_account') }} / {{ __('messages.transactions_to_account') }}</th>
                        <th class="px-3 py-2.5">{{ __('messages.transactions_category') }} & Payee</th>
                        <th class="px-3 py-2.5 text-right">{{ __('messages.transactions_amount') }}</th>
                        <th class="px-3 py-2.5 text-right">{{ __('messages.transactions_fee') }}</th>
                        <th class="px-3 py-2.5 text-right">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                    @forelse($transactions as $txn)
                        @php
                            $isDep = $txn->isDeposit();
                            $isWdr = $txn->isWithdrawal();
                            $isTrf = $txn->isTransfer();
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                            {{-- Date & Txn # --}}
                            <td class="px-3 py-2 whitespace-nowrap">
                                <div class="font-bold text-slate-900 dark:text-slate-100 text-xs">
                                    {{ $txn->transaction_date->format('Y-m-d h:i A') }}
                                </div>
                                <div class="font-mono text-[10px] text-slate-400 mt-0.5">
                                    {{ $txn->transaction_number }}
                                </div>
                            </td>

                            {{-- Type Badge --}}
                            <td class="px-3 py-2 whitespace-nowrap">
                                @if($isDep)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                                        Deposit
                                    </span>
                                @elseif($isWdr)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                                        Withdrawal
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-violet-100 text-violet-800 dark:bg-violet-950/60 dark:text-violet-300">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                        Transfer
                                    </span>
                                @endif
                            </td>

                            {{-- From / To Accounts --}}
                            <td class="px-3 py-2">
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
                            <td class="px-3 py-2">
                                <div class="font-bold text-slate-900 dark:text-slate-100 text-xs">
                                    {{ ucwords(str_replace('_', ' ', $txn->category ?? 'general')) }}
                                </div>
                                @if($txn->payer_or_payee || $txn->reference_no)
                                    <div class="text-[10px] text-slate-400 mt-0.5">
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
                            <td class="px-3 py-2 text-right font-mono font-black text-xs sm:text-sm whitespace-nowrap tabular-nums">
                                @if($isDep)
                                    <span class="text-emerald-600 dark:text-emerald-400">+{{ number_format((float) $txn->amount, 2) }}</span>
                                @elseif($isWdr)
                                    <span class="text-rose-600 dark:text-rose-400">-{{ number_format((float) $txn->amount, 2) }}</span>
                                @else
                                    <span class="text-violet-700 dark:text-violet-300">{{ number_format((float) $txn->amount, 2) }}</span>
                                @endif
                            </td>

                            {{-- Fee --}}
                            <td class="px-3 py-2 text-right font-mono text-[11px] text-slate-400 whitespace-nowrap tabular-nums">
                                {{ (float) $txn->fee > 0 ? number_format((float) $txn->fee, 2) : '-' }}
                            </td>

                            {{-- Actions (Print Voucher) --}}
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                <a href="{{ route('store.admin.transactions.voucher', ['store_slug' => $store->slug, 'transaction' => $txn->id]) }}"
                                   target="_blank"
                                   class="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-bold rounded-md border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition shadow-2xs">
                                    <svg class="w-3 h-3 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    <span>Voucher</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-10 h-10 text-slate-300 dark:text-slate-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <rect width="20" height="12" x="2" y="6" rx="2" stroke-width="1.5"/><circle cx="12" cy="12" r="2" stroke-width="1.5"/>
                                    </svg>
                                    <p class="text-xs font-semibold">{{ __('messages.transactions_no_records') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination Links --}}
        @if($transactions->hasPages())
            <div class="p-2.5 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

    {{-- ============================================================
         1. DEPOSIT MODAL (Cash In)
         ============================================================ --}}
    <div x-show="showDepositModal"
         x-cloak
         @keydown.escape.window="showDepositModal = false"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div @click.away="showDepositModal = false"
             class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xl max-w-md w-full p-4 sm:p-5 space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 font-outfit">{{ __('messages.transactions_btn_deposit') }}</h3>
                        <p class="text-[11px] text-slate-500">Record cash or bank inflow</p>
                    </div>
                </div>
                <button type="button" @click="showDepositModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-md">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('store.admin.transactions.deposit', ['store_slug' => $store->slug]) }}" class="space-y-2.5">
                @csrf
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_to_account') }} *</label>
                    <select name="to_account_id" x-model="depositForm.to_account_id" required class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-emerald-500 shadow-2xs">
                        <option value="">-- Select Destination Account --</option>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }} (Balance: {{ number_format((float) $acc->current_balance) }} MMK)</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_amount') }} *</label>
                    <input type="number" step="any" min="1" name="amount" x-ref="depositAmountInput" x-model="depositForm.amount" required placeholder="0.00" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-mono font-bold focus:ring-2 focus:ring-emerald-500 shadow-2xs">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_category') }}</label>
                    <select name="category" x-model="depositForm.category" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-semibold focus:ring-2 focus:ring-emerald-500 shadow-2xs">
                        <option value="capital_injection">Capital Injection (အရင်းမတည်ငွေ)</option>
                        <option value="debt_collection">Customer Debt Settlement (အကြွေးရငွေ)</option>
                        <option value="other_income">Other Income (အခြားဝင်ငွေ)</option>
                        <option value="bank_deposit">Bank Cash Deposit (ဘဏ်သွင်းငွေ)</option>
                        <option value="general_deposit">General Deposit (အထွေထွေငွေသွင်း)</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_payer_payee') }}</label>
                        <input type="text" name="payer_or_payee" x-model="depositForm.payer_or_payee" placeholder="Payer name" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 shadow-2xs">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_reference_no') }}</label>
                        <input type="text" name="reference_no" x-model="depositForm.reference_no" placeholder="Slip # / Ref" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 shadow-2xs">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_notes') }}</label>
                    <textarea name="notes" rows="2" x-model="depositForm.notes" placeholder="Optional remarks..." class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 shadow-2xs"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showDepositModal = false" class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-2xs">
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
         @keydown.escape.window="showWithdrawModal = false"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div @click.away="showWithdrawModal = false"
             class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xl max-w-md w-full p-4 sm:p-5 space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 font-outfit">{{ __('messages.transactions_btn_withdraw') }}</h3>
                        <p class="text-[11px] text-slate-500">Record cash or bank withdrawal</p>
                    </div>
                </div>
                <button type="button" @click="showWithdrawModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-md">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('store.admin.transactions.withdraw', ['store_slug' => $store->slug]) }}" class="space-y-2.5">
                @csrf
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_from_account') }} *</label>
                    <select name="from_account_id" x-model="withdrawForm.from_account_id" required class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-rose-500 shadow-2xs">
                        <option value="">-- Select Source Account --</option>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }} (Balance: {{ number_format((float) $acc->current_balance) }} MMK)</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_amount') }} *</label>
                    <input type="number" step="any" min="1" name="amount" x-ref="withdrawAmountInput" x-model="withdrawForm.amount" required placeholder="0.00" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-mono font-bold focus:ring-2 focus:ring-rose-500 shadow-2xs">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_category') }}</label>
                    <select name="category" x-model="withdrawForm.category" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-semibold focus:ring-2 focus:ring-rose-500 shadow-2xs">
                        <option value="owner_drawing">Owner Drawing (ပိုင်ရှင်ထုတ်ငွေ)</option>
                        <option value="salary_advance">Salary Advance (လစာကြိုထုတ်)</option>
                        <option value="supplier_payment">Supplier Debt Settlement (ကုန်ဖိုးငွေချေ)</option>
                        <option value="petty_cash">Petty Cash (နေ့စဉ်အသေးသုံးငွေ)</option>
                        <option value="other_withdrawal">Other Withdrawal (အခြားငွေထုတ်)</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_payer_payee') }}</label>
                        <input type="text" name="payer_or_payee" x-model="withdrawForm.payer_or_payee" placeholder="Payee name" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 shadow-2xs">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_reference_no') }}</label>
                        <input type="text" name="reference_no" x-model="withdrawForm.reference_no" placeholder="Slip # / Ref" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 shadow-2xs">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_notes') }}</label>
                    <textarea name="notes" rows="2" x-model="withdrawForm.notes" placeholder="Optional remarks..." class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 shadow-2xs"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showWithdrawModal = false" class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold shadow-2xs">
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
         @keydown.escape.window="showTransferModal = false"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div @click.away="showTransferModal = false"
             class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xl max-w-md w-full p-4 sm:p-5 space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-violet-100 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 font-outfit">{{ __('messages.transactions_btn_transfer') }}</h3>
                        <p class="text-[11px] text-slate-500">Transfer funds between store accounts</p>
                    </div>
                </div>
                <button type="button" @click="showTransferModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-md">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('store.admin.transactions.transfer', ['store_slug' => $store->slug]) }}" class="space-y-2.5">
                @csrf
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_from_account') }} *</label>
                    <select name="from_account_id" x-model="transferForm.from_account_id" required class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-violet-500 shadow-2xs">
                        <option value="">-- Select Source Account --</option>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }} (Balance: {{ number_format((float) $acc->current_balance) }} MMK)</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_to_account') }} *</label>
                    <select name="to_account_id" x-model="transferForm.to_account_id" required class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-violet-500 shadow-2xs">
                        <option value="">-- Select Destination Account --</option>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }} (Balance: {{ number_format((float) $acc->current_balance) }} MMK)</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_amount') }} *</label>
                        <input type="number" step="any" min="1" name="amount" x-ref="transferAmountInput" x-model="transferForm.amount" required placeholder="0.00" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-mono font-bold focus:ring-2 focus:ring-violet-500 shadow-2xs">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_fee') }}</label>
                        <input type="number" step="any" min="0" name="fee" x-model="transferForm.fee" placeholder="0.00" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-mono focus:ring-2 focus:ring-violet-500 shadow-2xs">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_reference_no') }}</label>
                    <input type="text" name="reference_no" x-model="transferForm.reference_no" placeholder="Bank transaction / slip ref" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 shadow-2xs">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_notes') }}</label>
                    <textarea name="notes" rows="2" x-model="transferForm.notes" placeholder="Optional transfer remarks..." class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 shadow-2xs"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showTransferModal = false" class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-violet-600 hover:bg-violet-500 text-white text-xs font-bold shadow-2xs">
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
         @keydown.escape.window="showAccountModal = false"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div @click.away="showAccountModal = false"
             class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xl max-w-md w-full p-4 sm:p-5 space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 font-outfit">{{ __('messages.transactions_btn_add_account') }}</h3>
                        <p class="text-[11px] text-slate-500">Add a new bank or wallet account</p>
                    </div>
                </div>
                <button type="button" @click="showAccountModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-md">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('store.admin.transactions.account.store', ['store_slug' => $store->slug]) }}" class="space-y-2.5">
                @csrf
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_account_name') }} *</label>
                    <input type="text" name="name" x-ref="accountNameInput" required placeholder="e.g. Yoma Bank (Main)" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-blue-500 shadow-2xs">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_account_type') }} *</label>
                    <select name="account_type" required class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-blue-500 shadow-2xs">
                        <option value="bank_account">{{ __('messages.transactions_type_bank') }}</option>
                        <option value="mobile_wallet">{{ __('messages.transactions_type_wallet') }}</option>
                        <option value="cash">{{ __('messages.transactions_type_cash') }}</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_account_number') }}</label>
                        <input type="text" name="account_number" placeholder="Account # or Phone" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 shadow-2xs">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_account_holder') }}</label>
                        <input type="text" name="account_holder" placeholder="Holder name" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 shadow-2xs">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_opening_balance') }}</label>
                    <input type="number" step="any" min="0" name="opening_balance" placeholder="0.00" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs font-mono bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 shadow-2xs">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_notes') }}</label>
                    <textarea name="notes" rows="2" placeholder="Optional notes..." class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 shadow-2xs"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showAccountModal = false" class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-2xs">
                        {{ __('messages.transactions_btn_add_account') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         6. MOBILE FLOATING ACTION BUTTON (Quick Deposit)
         ============================================================ --}}
    <button type="button"
            @click="openDeposit()"
            class="sm:hidden fixed bottom-5 right-5 z-40 w-12 h-12 rounded-full bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-lg flex items-center justify-center hover:scale-105 active:scale-95 transition focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
            title="{{ __('messages.transactions_btn_deposit') }}"
            aria-label="{{ __('messages.transactions_btn_deposit') }}">
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
        </svg>
    </button>

</div>
@endsection
