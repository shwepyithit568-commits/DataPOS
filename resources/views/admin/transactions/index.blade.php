@extends('layouts.admin.app')

@section('title', __('messages.transactions_title') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
<div
    x-data="{
        viewMode: '{{ request('view', 'table') }}',
        showDepositModal: false,
        showWithdrawModal: false,
        showTransferModal: false,
        showAccountModal: false,
        exportModalOpen: false,

        depositForm: {
            to_account_id: '',
            amount: '',
            category: 'capital_injection',
            reference_no: '',
            payer_or_payee: '',
            notes: '',
            transaction_date: '{{ now()->format('Y-m-d') }}'
        },

        withdrawForm: {
            from_account_id: '',
            amount: '',
            category: 'owner_drawing',
            reference_no: '',
            payer_or_payee: '',
            notes: '',
            transaction_date: '{{ now()->format('Y-m-d') }}'
        },

        transferForm: {
            from_account_id: '',
            to_account_id: '',
            amount: '',
            fee: 0,
            reference_no: '',
            notes: '',
            transaction_date: '{{ now()->format('Y-m-d') }}'
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
    class="w-full space-y-0.5 pb-6"
>

    {{-- ============================================================
         1. ULTRA-DENSE PAGE HEADER BANNER
         ============================================================ --}}
    <div class="rounded border px-2 py-1.5 bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 select-none transition">
        {{-- Left: Back Button, Store Badge, Title & Count Badge --}}
        <div class="flex items-center gap-1.5 min-w-0 flex-wrap">
            <a href="{{ route('store.admin.dashboard', ['store_slug' => $store->slug]) }}"
               class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-100 transition shrink-0"
               title="{{ __('messages.back') }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <span class="px-1.5 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-violet-50 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 border border-violet-200/80 dark:border-violet-800 shrink-0">
                {{ $store->name }}
            </span>
            <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 tracking-tight flex items-center gap-1.5">
                <span>{{ __('messages.transactions_title') }}</span>
                <span class="text-[10px] font-mono px-1.5 py-0.2 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200/80 dark:border-slate-700">
                    {{ number_format($transactions->total()) }}
                </span>
            </h1>
        </div>

        {{-- Right: High-Contrast Solid Action Buttons --}}
        <div class="flex items-center gap-1 sm:gap-1.5 flex-wrap shrink-0">
            {{-- Add Account Button --}}
            <button type="button"
                    @click="openAddAccount()"
                    class="h-7 px-2 sm:px-2.5 rounded text-xs font-bold transition flex items-center gap-1 bg-white hover:bg-slate-50 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 shadow-2xs">
                <span>🏦</span>
                <span class="hidden sm:inline">{{ __('messages.transactions_btn_add_account') }}</span>
            </button>

            {{-- Fund Transfer Button --}}
            <button type="button"
                    @click="openTransfer()"
                    class="h-7 px-2 sm:px-2.5 rounded text-xs font-bold transition flex items-center gap-1 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200/80 dark:border-indigo-800 shadow-2xs">
                <span>⇄</span>
                <span>{{ __('messages.transactions_btn_transfer') }}</span>
            </button>

            {{-- Withdraw (Cash Out) Button --}}
            <button type="button"
                    @click="openWithdraw()"
                    class="h-7 px-2 sm:px-2.5 rounded text-xs font-bold transition flex items-center gap-1 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/60 dark:hover:bg-rose-900/60 text-rose-700 dark:text-rose-300 border border-rose-200/80 dark:border-rose-800 shadow-2xs">
                <span>-</span>
                <span>{{ __('messages.transactions_btn_withdraw') }}</span>
            </button>

            {{-- Deposit (Cash In) Primary CTA Button --}}
            <button type="button"
                    @click="openDeposit()"
                    class="h-7 px-2.5 sm:px-3 rounded text-xs font-black transition flex items-center gap-1 bg-emerald-600 hover:bg-emerald-700 text-white shadow-2xs">
                <span>+</span>
                <span>{{ __('messages.transactions_btn_deposit') }}</span>
            </button>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if (session('success'))
        <div class="px-2.5 py-1.5 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded text-xs font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-2 shadow-2xs">
            <span class="text-sm">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="px-2.5 py-1.5 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded text-xs font-bold text-rose-800 dark:text-rose-300 space-y-0.5 shadow-2xs">
            <div class="flex items-center gap-1.5">
                <span>⚠️</span>
                <span>{{ __('messages.validation_error') ?? 'Please fix the errors below:' }}</span>
            </div>
            @foreach ($errors->all() as $error)
                <p class="pl-4 text-[11px] font-medium">• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- ============================================================
         2. 4 FINANCIAL KPI STAT CARDS (Centered Row-based Standard v4.1)
         ============================================================ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-0.5 sm:gap-1 select-none">
        {{-- Card 1: Total Net Liquidity --}}
        <div class="rounded border p-2 sm:p-2.5 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm shrink-0 border bg-violet-50 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 border-violet-100 dark:border-violet-900/50">
                💰
            </div>
            <div class="min-w-0 text-left">
                <div class="text-[11px] font-bold truncate text-slate-500 dark:text-slate-400">
                    {{ __('messages.transactions_total_liquidity') }}
                </div>
                <div class="text-sm sm:text-base font-black text-violet-700 dark:text-violet-300 font-mono tracking-tight">
                    {{ format_currency($stats['total_liquidity'], $store) }}
                </div>
                <div class="text-[10px] text-slate-400 font-mono truncate">
                    {{ $stats['accounts_count'] }} {{ __('messages.transactions_all_accounts') }}
                </div>
            </div>
        </div>

        {{-- Card 2: Cash in Hand --}}
        <div class="rounded border p-2 sm:p-2.5 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm shrink-0 border bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border-emerald-100 dark:border-emerald-900/50">
                💵
            </div>
            <div class="min-w-0 text-left">
                <div class="text-[11px] font-bold truncate text-slate-500 dark:text-slate-400">
                    {{ __('messages.transactions_cash_in_hand') }}
                </div>
                <div class="text-sm sm:text-base font-black text-emerald-700 dark:text-emerald-300 font-mono tracking-tight">
                    {{ format_currency($stats['cash_in_hand'], $store) }}
                </div>
                <div class="text-[10px] text-slate-400 font-mono truncate">
                    {{ __('messages.transactions_type_cash') }}
                </div>
            </div>
        </div>

        {{-- Card 3: Banks & Digital Wallets --}}
        <div class="rounded border p-2 sm:p-2.5 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm shrink-0 border bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 border-sky-100 dark:border-sky-900/50">
                🏦
            </div>
            <div class="min-w-0 text-left">
                <div class="text-[11px] font-bold truncate text-slate-500 dark:text-slate-400">
                    {{ __('messages.transactions_bank_wallets') }}
                </div>
                <div class="text-sm sm:text-base font-black text-sky-700 dark:text-sky-300 font-mono tracking-tight">
                    {{ format_currency($stats['bank_and_wallets'], $store) }}
                </div>
                <div class="text-[10px] text-slate-400 font-mono truncate">
                    {{ __('messages.transactions_type_bank') }} & {{ __('messages.transactions_type_wallet') }}
                </div>
            </div>
        </div>

        {{-- Card 4: Period Activity --}}
        <div class="rounded border p-2 sm:p-2.5 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm shrink-0 border bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border-indigo-100 dark:border-indigo-900/50">
                📊
            </div>
            <div class="min-w-0 text-left">
                <div class="text-[11px] font-bold truncate text-slate-500 dark:text-slate-400">
                    {{ __('messages.transactions_period_activity') }}
                </div>
                <div class="flex items-baseline gap-1 mt-0.5">
                    <span class="text-xs sm:text-sm font-black font-mono text-emerald-600 dark:text-emerald-400 tabular-nums">
                        +{{ format_currency($stats['total_deposits'], $store) }}
                    </span>
                    <span class="text-[10px] text-slate-400">/</span>
                    <span class="text-xs sm:text-sm font-black font-mono text-rose-600 dark:text-rose-400 tabular-nums">
                        -{{ format_currency($stats['total_outflow'], $store) }}
                    </span>
                </div>
                <div class="text-[10px] text-slate-400 font-mono truncate">
                    {{ $stats['period_label'] }}
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         3. FINANCIAL ACCOUNT CARDS (Quick Balances & 1-Click Triggers)
         ============================================================ --}}
    <div class="rounded border p-2 sm:p-2.5 bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800 shadow-2xs space-y-1.5">
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

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-1 sm:gap-1.5">
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
                <div class="rounded border p-2 {{ $cardBg }} flex flex-col justify-between space-y-1.5 shadow-2xs hover:shadow-xs transition">
                    <div>
                        <div class="flex items-center justify-between gap-1">
                            <span class="text-[9px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded {{ $isCash ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300' : ($isWallet ? 'bg-sky-100 text-sky-800 dark:bg-sky-900/60 dark:text-sky-300' : 'bg-slate-200/80 text-slate-700 dark:bg-slate-700 dark:text-slate-300') }}">
                                {{ $acc->account_type === 'cash' ? __('messages.transactions_type_cash') : ($acc->account_type === 'mobile_wallet' ? __('messages.transactions_type_wallet') : __('messages.transactions_type_bank')) }}
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
                            {{ format_currency($acc->current_balance, $store) }}
                        </div>
                    </div>

                    {{-- Card Quick Action Buttons --}}
                    <div class="flex items-center gap-1 pt-1 border-t border-slate-200/60 dark:border-slate-800/80">
                        <button type="button"
                                @click="openDeposit({{ $acc->id }})"
                                class="flex-1 py-0.5 text-[10px] font-bold rounded bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 transition shadow-2xs text-center"
                                title="{{ __('messages.transactions_btn_deposit') }}">
                            {{ __('messages.transactions_quick_in') }}
                        </button>
                        <button type="button"
                                @click="openWithdraw({{ $acc->id }})"
                                class="flex-1 py-0.5 text-[10px] font-bold rounded bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition shadow-2xs text-center"
                                title="{{ __('messages.transactions_btn_withdraw') }}">
                            {{ __('messages.transactions_quick_out') }}
                        </button>
                        <button type="button"
                                @click="openTransfer({{ $acc->id }})"
                                class="flex-1 py-0.5 text-[10px] font-bold rounded bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition shadow-2xs text-center"
                                title="{{ __('messages.transactions_btn_transfer') }}">
                            {{ __('messages.transactions_quick_move') }}
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ============================================================
         4. TOOLBAR AREA: Search, Filters, Presets, Date Modal, View Toggle, Export
         ============================================================ --}}
    <x-admin.toolbar
        :search="request('search', '')"
        :searchPlaceholder="__('messages.search') . ' Txn#, ref, payee...'"
        :sort="request('sort', 'newest')"
        :sortOptions="$sortOptions"
        :filters="$toolbarFilters"
        :viewMode="request('view', 'table')"
        :showViewToggle="true"
        :showExportImport="true"
        :exportUrl="$exportBaseUrl"
        :totalCount="$transactions->total()"
        :paginator="$transactions"
    >
        {{-- Period Presets Dropdown on Toolbar --}}
        <form method="GET" class="shrink-0" data-auto-submit>
            @foreach (request()->except(['preset', 'date_from', 'date_to', 'page']) as $key => $val)
                @if (is_array($val))
                    @foreach ($val as $subVal)
                        <input type="hidden" name="{{ $key }}[]" value="{{ $subVal }}" />
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $val }}" />
                @endif
            @endforeach
            <div class="relative inline-flex items-center">
                <svg class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 absolute left-2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <select name="preset" data-auto-submit
                        class="border border-slate-200 dark:border-slate-700 rounded-lg pl-7 pr-6 min-h-[36px] py-1 text-xs bg-slate-50 hover:bg-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700/80 text-slate-700 dark:text-slate-200 font-bold focus:outline-none focus:ring-2 focus:ring-violet-500/40 cursor-pointer appearance-none shadow-2xs transition max-w-[130px] sm:max-w-[155px] truncate">
                    <option value="all" {{ ($filters['preset'] ?? '') === 'all' && !request('date_from') && !request('date_to') ? 'selected' : '' }}>
                        {{ __('messages.period_all') }}
                    </option>
                    <option value="today" {{ ($filters['preset'] ?? '') === 'today' ? 'selected' : '' }}>{{ __('messages.period_today') }}</option>
                    <option value="yesterday" {{ ($filters['preset'] ?? '') === 'yesterday' ? 'selected' : '' }}>{{ __('messages.period_yesterday') }}</option>
                    <option value="7days" {{ ($filters['preset'] ?? '') === '7days' ? 'selected' : '' }}>{{ __('messages.period_this_week') }}</option>
                    <option value="this_month" {{ ($filters['preset'] ?? 'this_month') === 'this_month' && !request('date_from') ? 'selected' : '' }}>{{ __('messages.period_this_month') }}</option>
                    <option value="last_month" {{ ($filters['preset'] ?? '') === 'last_month' ? 'selected' : '' }}>{{ __('messages.period_last_month') }}</option>
                    @if(request('date_from') || request('date_to'))
                        <option value="custom" selected>{{ __('messages.period_custom') }}</option>
                    @endif
                </select>
                <svg class="w-3 h-3 text-slate-400 dark:text-slate-500 absolute right-1.5 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </form>

        {{-- Compact Date-to-Date Calendar Popover Button (Space-saving) --}}
        @php
            $hasCustomDate = (bool) (request('date_from') || request('date_to'));
            $dateButtonLabel = __('messages.date');
            if ($hasCustomDate) {
                $fromFmt = request('date_from') ? \Carbon\Carbon::parse(request('date_from'))->format('d/m') : '…';
                $toFmt = request('date_to') ? \Carbon\Carbon::parse(request('date_to'))->format('d/m') : '…';
                $dateButtonLabel = "{$fromFmt} → {$toFmt}";
            }
        @endphp
        <div class="relative shrink-0" x-data="{ datePopoverOpen: false }">
            <button type="button" @click="datePopoverOpen = !datePopoverOpen"
                    class="min-h-[36px] px-2.5 rounded-lg text-xs font-bold border transition inline-flex items-center gap-1.5 shadow-2xs {{ $hasCustomDate ? 'bg-violet-50 text-violet-700 border-violet-300 dark:bg-violet-950/60 dark:text-violet-300 dark:border-violet-800 ring-1 ring-violet-500/20' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200 border-slate-200/90 dark:border-slate-700' }}"
                    title="{{ __('messages.date') }}">
                <span>📅</span>
                <span class="text-[11px] whitespace-nowrap">{{ $dateButtonLabel }}</span>
                <svg class="w-3 h-3 text-slate-400 transition-transform duration-150" :class="datePopoverOpen ? 'rotate-180 text-violet-600 dark:text-violet-400' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            {{-- Floating Popover Modal / Teleported to Body --}}
            <template x-teleport="body">
                <div x-show="datePopoverOpen" x-cloak
                     style="z-index: 99999;"
                     class="fixed inset-0 flex items-center justify-center p-4 bg-slate-950/50 backdrop-blur-xs"
                     @click.self="datePopoverOpen = false"
                     @keydown.escape.window="datePopoverOpen = false"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95">
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xl p-4 w-full max-w-sm space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                            <h3 class="text-xs font-bold text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                                <span>📅</span>
                                <span>{{ __('messages.period_custom') }}</span>
                            </h3>
                            <button type="button" @click="datePopoverOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">
                                ✕
                            </button>
                        </div>
                        <form method="GET" action="{{ route('store.admin.transactions.index', ['store_slug' => $store->slug]) }}" class="space-y-3">
                            @foreach (request()->except(['date_from', 'date_to', 'preset', 'page']) as $k => $v)
                                @if (is_array($v))
                                    @foreach ($v as $subV)
                                        <input type="hidden" name="{{ $k }}[]" value="{{ $subV }}" />
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $k }}" value="{{ $v }}" />
                                @endif
                            @endforeach
                            <input type="hidden" name="preset" value="custom" />
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.date_from') ?? 'From Date' }}</label>
                                <input type="date" name="date_from" value="{{ request('date_from', '') }}"
                                       class="w-full h-8 px-2 text-xs rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-1 focus:ring-violet-500" />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.date_to') ?? 'To Date' }}</label>
                                <input type="date" name="date_to" value="{{ request('date_to', '') }}"
                                       class="w-full h-8 px-2 text-xs rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-1 focus:ring-violet-500" />
                            </div>
                            <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800">
                                @if($hasCustomDate)
                                    <a href="{{ route('store.admin.transactions.index', array_merge(['store_slug' => $store->slug], request()->except(['date_from', 'date_to', 'preset']))) }}"
                                       class="text-xs font-bold text-rose-600 hover:underline">
                                        {{ __('messages.reset') }}
                                    </a>
                                @else
                                    <span></span>
                                @endif
                                <div class="flex items-center gap-1.5">
                                    <button type="button" @click="datePopoverOpen = false" class="px-2.5 py-1 text-xs rounded border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300">
                                        {{ __('messages.cancel') }}
                                    </button>
                                    <button type="submit" class="px-3 py-1 text-xs font-bold rounded bg-violet-600 hover:bg-violet-700 text-white">
                                        {{ __('messages.apply') ?? 'Apply' }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </template>
        </div>

        {{-- Filter Drawer Slot --}}
        <x-slot name="filterDrawer">
            <div class="space-y-3">
                {{-- Account Filter --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.transactions_all_accounts') }}
                    </label>
                    <select name="account_id" class="w-full text-xs rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 p-2">
                        <option value="">{{ __('messages.all') }}</option>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}" {{ request('account_id') == $acc->id ? 'selected' : '' }}>
                                {{ $acc->name }} ({{ format_currency($acc->current_balance, $store) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Type Filter --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.transactions_all_types') }}
                    </label>
                    <select name="type" class="w-full text-xs rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 p-2">
                        <option value="">{{ __('messages.all') }}</option>
                        <option value="deposit" {{ request('type') === 'deposit' ? 'selected' : '' }}>
                            {{ __('messages.transactions_type_deposit') }}
                        </option>
                        <option value="withdrawal" {{ request('type') === 'withdrawal' ? 'selected' : '' }}>
                            {{ __('messages.transactions_type_withdrawal') }}
                        </option>
                        <option value="transfer" {{ request('type') === 'transfer' ? 'selected' : '' }}>
                            {{ __('messages.transactions_type_transfer') }}
                        </option>
                    </select>
                </div>
            </div>
        </x-slot>
    </x-admin.toolbar>

    {{-- ============================================================
         5. TABLE VIEW (x-show="viewMode === 'table'")
         ============================================================ --}}
    <div x-show="viewMode === 'table'" x-cloak
         class="rounded border bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800 shadow-2xs overflow-hidden">

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
                        <th class="py-2 px-2.5 text-center w-10">#</th>
                        <th class="py-2 px-2.5">{{ __('messages.reference_no') ?? 'Txn #' }}</th>
                        <th class="py-2 px-2.5 text-center">{{ __('messages.type') }}</th>
                        <th class="py-2 px-2.5">{{ __('messages.transactions_from_account') }} → {{ __('messages.transactions_to_account') }}</th>
                        <th class="py-2 px-2.5">{{ __('messages.transactions_category') }} / {{ __('messages.transactions_payer_payee') }}</th>
                        <th class="py-2 px-2.5 text-right font-black">{{ __('messages.transactions_amount') }}</th>
                        <th class="py-2 px-2.5 text-right">{{ __('messages.transactions_fee') }}</th>
                        <th class="py-2 px-2.5 text-center w-20">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    @forelse($transactions as $index => $txn)
                        @php
                            $isDeposit = $txn->type === 'deposit';
                            $isWithdrawal = $txn->type === 'withdrawal';
                            $isTransfer = $txn->type === 'transfer';
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">
                            {{-- Index --}}
                            <td class="py-2 px-2.5 text-center font-mono text-[10px] text-slate-400">
                                {{ $transactions->firstItem() + $index }}
                            </td>

                            {{-- Transaction Number & Date --}}
                            <td class="py-2 px-2.5">
                                <div class="font-mono font-bold text-xs text-slate-900 dark:text-slate-100">
                                    {{ $txn->transaction_number }}
                                </div>
                                <div class="text-[10px] text-slate-400">
                                    {{ $txn->transaction_date ? $txn->transaction_date->format('d M Y, h:i A') : '-' }}
                                    @if($txn->reference_no)
                                        · <span class="font-mono">{{ $txn->reference_no }}</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Type Pill --}}
                            <td class="py-2 px-2.5 text-center">
                                @if($isDeposit)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800">
                                        {{ __('messages.transactions_type_deposit') }}
                                    </span>
                                @elseif($isWithdrawal)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200/80 dark:border-rose-800">
                                        {{ __('messages.transactions_type_withdrawal') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200/80 dark:border-indigo-800">
                                        {{ __('messages.transactions_type_transfer') }}
                                    </span>
                                @endif
                            </td>

                            {{-- Accounts Flow --}}
                            <td class="py-2 px-2.5">
                                <div class="flex items-center gap-1 text-xs">
                                    @if($isDeposit)
                                        <span class="text-slate-400 font-mono text-[11px]">—</span>
                                        <span class="text-slate-400">→</span>
                                        <span class="font-bold text-slate-900 dark:text-slate-100">
                                            {{ $txn->toAccount?->name ?? '-' }}
                                        </span>
                                    @elseif($isWithdrawal)
                                        <span class="font-bold text-slate-900 dark:text-slate-100">
                                            {{ $txn->fromAccount?->name ?? '-' }}
                                        </span>
                                        <span class="text-slate-400">→</span>
                                        <span class="text-slate-400 font-mono text-[11px]">—</span>
                                    @else
                                        <span class="font-bold text-slate-900 dark:text-slate-100">
                                            {{ $txn->fromAccount?->name ?? '-' }}
                                        </span>
                                        <span class="text-indigo-500 font-bold">→</span>
                                        <span class="font-bold text-slate-900 dark:text-slate-100">
                                            {{ $txn->toAccount?->name ?? '-' }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- Category / Payee --}}
                            <td class="py-2 px-2.5">
                                <div class="text-xs text-slate-800 dark:text-slate-200 font-semibold truncate max-w-[170px]" title="{{ $txn->category }}">
                                    {{ $txn->category ?? '-' }}
                                </div>
                                @if($txn->payer_or_payee)
                                    <div class="text-[10px] text-slate-400 truncate max-w-[170px]" title="{{ $txn->payer_or_payee }}">
                                        👤 {{ $txn->payer_or_payee }}
                                    </div>
                                @endif
                            </td>

                            {{-- Amount (Soft Highlight & Bold font) --}}
                            <td class="py-2 px-2.5 text-right font-mono font-black text-xs sm:text-sm tabular-nums">
                                @if($isDeposit)
                                    <span class="text-emerald-600 dark:text-emerald-400">
                                        +{{ format_currency($txn->amount, $store) }}
                                    </span>
                                @elseif($isWithdrawal)
                                    <span class="text-rose-600 dark:text-rose-400">
                                        -{{ format_currency($txn->amount, $store) }}
                                    </span>
                                @else
                                    <span class="text-indigo-600 dark:text-indigo-400">
                                        {{ format_currency($txn->amount, $store) }}
                                    </span>
                                @endif
                            </td>

                            {{-- Fee --}}
                            <td class="py-2 px-2.5 text-right font-mono text-[11px] text-slate-400 tabular-nums">
                                @if($txn->fee > 0)
                                    <span class="text-rose-500 font-bold">{{ format_currency($txn->fee, $store) }}</span>
                                @else
                                    -
                                @endif
                            </td>

                            {{-- Actions / Voucher Link --}}
                            <td class="py-2 px-2.5 text-center">
                                <a href="{{ route('store.admin.transactions.voucher', ['store_slug' => $store->slug, 'transaction' => $txn->id]) }}"
                                   target="_blank"
                                   class="inline-flex items-center justify-center p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-violet-600 dark:text-violet-400 transition"
                                   title="{{ __('messages.transactions_print_voucher') }}">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400">
                                <div class="text-3xl mb-1">🏦</div>
                                <div class="text-xs font-bold">{{ __('messages.transactions_no_records') }}</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($transactions->hasPages())
            <div class="px-2.5 py-2 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

    {{-- ============================================================
         6. CARDS VIEW (x-show="viewMode === 'cards'")
         ============================================================ --}}
    <div x-show="viewMode === 'cards'" x-cloak class="space-y-1.5">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-1 sm:gap-1.5">
            @forelse($transactions as $txn)
                @php
                    $isDeposit = $txn->type === 'deposit';
                    $isWithdrawal = $txn->type === 'withdrawal';
                    $isTransfer = $txn->type === 'transfer';
                    $borderHighlight = $isDeposit
                        ? 'border-l-4 border-l-emerald-500'
                        : ($isWithdrawal ? 'border-l-4 border-l-rose-500' : 'border-l-4 border-l-indigo-500');
                @endphp
                <div class="rounded border bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800 shadow-2xs p-2.5 space-y-2 {{ $borderHighlight }}">
                    {{-- Card Header: Txn # & Type Badge --}}
                    <div class="flex items-center justify-between gap-1.5">
                        <div class="min-w-0">
                            <span class="font-mono font-bold text-xs text-slate-900 dark:text-slate-100">
                                {{ $txn->transaction_number }}
                            </span>
                            <div class="text-[10px] text-slate-400">
                                {{ $txn->transaction_date ? $txn->transaction_date->format('d M Y, h:i A') : '-' }}
                            </div>
                        </div>
                        @if($isDeposit)
                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                {{ __('messages.transactions_type_deposit') }}
                            </span>
                        @elseif($isWithdrawal)
                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300">
                                {{ __('messages.transactions_type_withdrawal') }}
                            </span>
                        @else
                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300">
                                {{ __('messages.transactions_type_transfer') }}
                            </span>
                        @endif
                    </div>

                    {{-- Card Flow: Accounts --}}
                    <div class="text-xs flex items-center gap-1 bg-slate-50 dark:bg-slate-800/60 p-1.5 rounded">
                        @if($isDeposit)
                            <span class="text-slate-400 font-mono text-[10px]">—</span>
                            <span class="text-slate-400">→</span>
                            <span class="font-bold text-slate-900 dark:text-slate-100 truncate">
                                {{ $txn->toAccount?->name ?? '-' }}
                            </span>
                        @elseif($isWithdrawal)
                            <span class="font-bold text-slate-900 dark:text-slate-100 truncate">
                                {{ $txn->fromAccount?->name ?? '-' }}
                            </span>
                            <span class="text-slate-400">→</span>
                            <span class="text-slate-400 font-mono text-[10px]">—</span>
                        @else
                            <span class="font-bold text-slate-900 dark:text-slate-100 truncate">
                                {{ $txn->fromAccount?->name ?? '-' }}
                            </span>
                            <span class="text-indigo-500 font-bold">→</span>
                            <span class="font-bold text-slate-900 dark:text-slate-100 truncate">
                                {{ $txn->toAccount?->name ?? '-' }}
                            </span>
                        @endif
                    </div>

                    {{-- Card Bottom: Amount & Voucher Link --}}
                    <div class="flex items-center justify-between pt-1 border-t border-slate-100 dark:border-slate-800">
                        <div>
                            <div class="text-[10px] text-slate-400">
                                {{ $txn->category ?? '-' }}
                                @if($txn->payer_or_payee)
                                    · {{ $txn->payer_or_payee }}
                                @endif
                            </div>
                            <div class="font-mono font-black text-sm">
                                @if($isDeposit)
                                    <span class="text-emerald-600 dark:text-emerald-400">+{{ format_currency($txn->amount, $store) }}</span>
                                @elseif($isWithdrawal)
                                    <span class="text-rose-600 dark:text-rose-400">-{{ format_currency($txn->amount, $store) }}</span>
                                @else
                                    <span class="text-indigo-600 dark:text-indigo-400">{{ format_currency($txn->amount, $store) }}</span>
                                @endif
                            </div>
                        </div>

                        <a href="{{ route('store.admin.transactions.voucher', ['store_slug' => $store->slug, 'transaction' => $txn->id]) }}"
                           target="_blank"
                           class="h-7 px-2.5 rounded text-xs font-bold transition flex items-center gap-1 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-200">
                            <span>🖨️</span>
                            <span>{{ __('messages.transactions_print_voucher') }}</span>
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-10 text-center text-slate-400 bg-white dark:bg-slate-900 rounded border border-slate-200 dark:border-slate-800">
                    <div class="text-3xl mb-1">🏦</div>
                    <div class="text-xs font-bold">{{ __('messages.transactions_no_records') }}</div>
                </div>
            @endforelse
        </div>

        {{-- Pagination for cards --}}
        @if($transactions->hasPages())
            <div class="px-2.5 py-2 border rounded bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

    {{-- ============================================================
         7. TELEPORTED MODALS (Deposit, Withdraw, Transfer, Account)
         ============================================================ --}}

    {{-- 7.1 DEPOSIT MODAL (Cash In) --}}
    <template x-teleport="body">
        <div x-show="showDepositModal" x-cloak
             style="z-index: 99999;"
             @keydown.escape.window="showDepositModal = false"
             class="fixed inset-0 overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div @click.away="showDepositModal = false"
                 class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xl max-w-md w-full p-4 space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        </div>
                        <div>
                            <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-100">{{ __('messages.transactions_btn_deposit') }}</h3>
                            <p class="text-[10px] text-slate-500">{{ __('messages.transactions_type_deposit') }}</p>
                        </div>
                    </div>
                    <button type="button" @click="showDepositModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">
                        ✕
                    </button>
                </div>

                <form method="POST" action="{{ route('store.admin.transactions.deposit', ['store_slug' => $store->slug]) }}" class="space-y-2.5">
                    @csrf
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_to_account') }} *</label>
                        <select name="to_account_id" x-model="depositForm.to_account_id" required class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-emerald-500 shadow-2xs">
                            <option value="">-- {{ __('messages.transactions_to_account') }} --</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }} ({{ format_currency($acc->current_balance, $store) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_amount') }} *</label>
                        <input type="number" step="any" min="0.01" name="amount" x-ref="depositAmountInput" x-model="depositForm.amount" required placeholder="0.00" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-mono font-bold focus:ring-2 focus:ring-emerald-500 shadow-2xs">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_category') }}</label>
                        <select name="category" x-model="depositForm.category" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-semibold focus:ring-2 focus:ring-emerald-500 shadow-2xs">
                            <option value="capital_injection">{{ __('messages.transactions_capital_injection') }}</option>
                            <option value="debt_collection">{{ __('messages.transactions_debt_collection') }}</option>
                            <option value="other_income">{{ __('messages.transactions_other_income') }}</option>
                            <option value="bank_deposit">{{ __('messages.transactions_bank_deposit') }}</option>
                            <option value="general_deposit">{{ __('messages.transactions_general_deposit') }}</option>
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
                        <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-2xs">
                            {{ __('messages.transactions_btn_deposit') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    {{-- 7.2 WITHDRAWAL MODAL (Cash Out) --}}
    <template x-teleport="body">
        <div x-show="showWithdrawModal" x-cloak
             style="z-index: 99999;"
             @keydown.escape.window="showWithdrawModal = false"
             class="fixed inset-0 overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div @click.away="showWithdrawModal = false"
                 class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xl max-w-md w-full p-4 space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                        </div>
                        <div>
                            <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-100">{{ __('messages.transactions_btn_withdraw') }}</h3>
                            <p class="text-[10px] text-slate-500">{{ __('messages.transactions_type_withdrawal') }}</p>
                        </div>
                    </div>
                    <button type="button" @click="showWithdrawModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">
                        ✕
                    </button>
                </div>

                <form method="POST" action="{{ route('store.admin.transactions.withdraw', ['store_slug' => $store->slug]) }}" class="space-y-2.5">
                    @csrf
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_from_account') }} *</label>
                        <select name="from_account_id" x-model="withdrawForm.from_account_id" required class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-rose-500 shadow-2xs">
                            <option value="">-- {{ __('messages.transactions_from_account') }} --</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }} ({{ format_currency($acc->current_balance, $store) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_amount') }} *</label>
                        <input type="number" step="any" min="0.01" name="amount" x-ref="withdrawAmountInput" x-model="withdrawForm.amount" required placeholder="0.00" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-mono font-bold focus:ring-2 focus:ring-rose-500 shadow-2xs">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_category') }}</label>
                        <select name="category" x-model="withdrawForm.category" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-semibold focus:ring-2 focus:ring-rose-500 shadow-2xs">
                            <option value="owner_drawing">{{ __('messages.transactions_owner_drawing') }}</option>
                            <option value="salary_advance">{{ __('messages.transactions_salary_advance') }}</option>
                            <option value="supplier_payment">{{ __('messages.transactions_supplier_payment') }}</option>
                            <option value="petty_cash">{{ __('messages.transactions_petty_cash') }}</option>
                            <option value="other_withdrawal">{{ __('messages.transactions_other_withdrawal') }}</option>
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
                        <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-2xs">
                            {{ __('messages.transactions_btn_withdraw') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    {{-- 7.3 FUND TRANSFER MODAL (Account-to-Account) --}}
    <template x-teleport="body">
        <div x-show="showTransferModal" x-cloak
             style="z-index: 99999;"
             @keydown.escape.window="showTransferModal = false"
             class="fixed inset-0 overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div @click.away="showTransferModal = false"
                 class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xl max-w-md w-full p-4 space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        </div>
                        <div>
                            <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-100">{{ __('messages.transactions_btn_transfer') }}</h3>
                            <p class="text-[10px] text-slate-500">{{ __('messages.transactions_internal_transfer') }}</p>
                        </div>
                    </div>
                    <button type="button" @click="showTransferModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">
                        ✕
                    </button>
                </div>

                <form method="POST" action="{{ route('store.admin.transactions.transfer', ['store_slug' => $store->slug]) }}" class="space-y-2.5">
                    @csrf
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_from_account') }} *</label>
                        <select name="from_account_id" x-model="transferForm.from_account_id" required class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-indigo-500 shadow-2xs">
                            <option value="">-- {{ __('messages.transactions_from_account') }} --</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }} ({{ format_currency($acc->current_balance, $store) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_to_account') }} *</label>
                        <select name="to_account_id" x-model="transferForm.to_account_id" required class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-indigo-500 shadow-2xs">
                            <option value="">-- {{ __('messages.transactions_to_account') }} --</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" :disabled="String(transferForm.from_account_id) === '{{ $acc->id }}'">
                                    {{ $acc->name }} ({{ format_currency($acc->current_balance, $store) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_amount') }} *</label>
                            <input type="number" step="any" min="0.01" name="amount" x-ref="transferAmountInput" x-model="transferForm.amount" required placeholder="0.00" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-mono font-bold focus:ring-2 focus:ring-indigo-500 shadow-2xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_fee') }}</label>
                            <input type="number" step="any" min="0" name="fee" x-model="transferForm.fee" placeholder="0.00" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-mono focus:ring-2 focus:ring-indigo-500 shadow-2xs">
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
                        <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-2xs">
                            {{ __('messages.transactions_btn_transfer') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    {{-- 7.4 CREATE ACCOUNT MODAL --}}
    <template x-teleport="body">
        <div x-show="showAccountModal" x-cloak
             style="z-index: 99999;"
             @keydown.escape.window="showAccountModal = false"
             class="fixed inset-0 overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div @click.away="showAccountModal = false"
                 class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xl max-w-md w-full p-4 space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-violet-100 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        </div>
                        <div>
                            <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-100">{{ __('messages.transactions_btn_add_account') }}</h3>
                            <p class="text-[10px] text-slate-500">{{ __('messages.transactions_subtitle') }}</p>
                        </div>
                    </div>
                    <button type="button" @click="showAccountModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">
                        ✕
                    </button>
                </div>

                <form method="POST" action="{{ route('store.admin.transactions.account.store', ['store_slug' => $store->slug]) }}" class="space-y-2.5">
                    @csrf
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_account_name') }} *</label>
                        <input type="text" name="name" x-ref="accountNameInput" required placeholder="e.g. KBZ Bank (Main)" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-violet-500 shadow-2xs">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.transactions_account_type') }} *</label>
                        <select name="account_type" required class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-violet-500 shadow-2xs">
                            <option value="bank_account">{{ __('messages.transactions_type_bank') }}</option>
                            <option value="mobile_wallet">{{ __('messages.transactions_type_wallet') }}</option>
                            <option value="cash">{{ __('messages.transactions_type_cash') }}</option>
                            <option value="other">{{ __('messages.other') ?? 'Other' }}</option>
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
                        <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-violet-600 hover:bg-violet-700 text-white text-xs font-bold shadow-2xs">
                            {{ __('messages.transactions_btn_add_account') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    {{-- ============================================================
         8. MOBILE FLOATING ACTION BUTTON (Quick Deposit)
         ============================================================ --}}
    <button type="button"
            @click="openDeposit()"
            class="sm:hidden fixed bottom-4 right-4 z-40 w-11 h-11 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white shadow-lg flex items-center justify-center hover:scale-105 active:scale-95 transition focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
            title="{{ __('messages.transactions_btn_deposit') }}"
            aria-label="{{ __('messages.transactions_btn_deposit') }}">
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
        </svg>
    </button>

</div>
@endsection
