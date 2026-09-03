@extends('layouts.admin.app')

@section('title', __('messages.expenses_title') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
@php
    $storeRouteParams = ['store_slug' => $store->slug];
    $paymentMethodOptions = \App\Http\Controllers\Admin\ExpenseController::PAYMENT_METHODS;

    $categoryOptions = [];
    foreach ($allCategoriesForFilter as $cat) {
        $categoryOptions[$cat->id] = ($cat->code ? "[{$cat->code}] " : '') . $cat->name;
    }

    $paymentMethodFilterOptions = [];
    foreach ($paymentMethodOptions as $mKey => $mLabel) {
        $paymentMethodFilterOptions[$mKey] = $mLabel;
    }

    $filters = [
        'preset' => [
            'label' => __('messages.period'),
            'type' => 'select',
            'options' => [
                'all'        => __('messages.period_all'),
                'today'      => __('messages.period_today'),
                'yesterday'  => __('messages.period_yesterday'),
                'this_week'  => __('messages.period_this_week'),
                'this_month' => __('messages.period_this_month'),
                'last_month' => __('messages.period_last_month'),
                'this_year'  => __('messages.period_this_year'),
            ],
        ],
        'expense_date' => [
            'label' => __('messages.expense_date'),
            'type' => 'date_range',
        ],
        'category_id' => [
            'label' => __('messages.category'),
            'type' => 'select',
            'options' => $categoryOptions,
        ],
        'payment_method' => [
            'label' => __('messages.expense_payment_method'),
            'type' => 'select',
            'options' => $paymentMethodFilterOptions,
        ],
    ];

    $sortOptions = [
        'newest' => __('messages.expense_sort_newest'),
        'oldest' => __('messages.expense_sort_oldest'),
        'amount_desc' => __('messages.expense_sort_amount_desc'),
        'amount_asc' => __('messages.expense_sort_amount_asc'),
        'title_asc' => __('messages.expense_sort_title_az'),
    ];

    $paymentMethodBadgeClass = function (string $method): string {
        return match ($method) {
            'cash' => 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 border-emerald-200/80 dark:border-emerald-800',
            'kpay' => 'bg-blue-50 text-blue-800 dark:bg-blue-950/80 dark:text-blue-300 border-blue-200/80 dark:border-blue-800',
            'wave' => 'bg-amber-50 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300 border-amber-200/80 dark:border-amber-800',
            'cbpay' => 'bg-red-50 text-red-800 dark:bg-red-950/80 dark:text-red-300 border-red-200/80 dark:border-red-800',
            'bank_transfer' => 'bg-purple-50 text-purple-800 dark:bg-purple-950/80 dark:text-purple-300 border-purple-200/80 dark:border-purple-800',
            default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200/80 dark:border-slate-700',
        };
    };
@endphp

<div class="w-full space-y-0.5 pb-6"
     x-data="{
        viewMode: localStorage.getItem('admin_expenses_view_mode') || 'table',
        createModalOpen: false,
        editModalOpen: false,
        deleteConfirmOpen: false,
        receiptModalOpen: false,
        activeReceiptUrl: '',
        activeReceiptTitle: '',
        expenseToDelete: null,
        editingExpense: {
            id: null,
            title: '',
            amount: '',
            expense_date: '{{ now()->toDateString() }}',
            expense_category_id: '',
            payment_method: 'cash',
            paid_to: '',
            reference_no: '',
            notes: '',
            attachment_path: null,
            attachment_url: null
        },
        openCreateModal() {
            this.createModalOpen = true;
            this.$nextTick(() => {
                this.$refs.createExpenseTitle?.focus();
            });
        },
        openEditModal(expense) {
            this.editingExpense = {
                id: expense.id,
                title: expense.title,
                amount: expense.amount,
                expense_date: expense.expense_date ? expense.expense_date.substring(0, 10) : '{{ now()->toDateString() }}',
                expense_category_id: expense.expense_category_id || '',
                payment_method: expense.payment_method || 'cash',
                paid_to: expense.paid_to || '',
                reference_no: expense.reference_no || '',
                notes: expense.notes || '',
                attachment_path: expense.attachment_path || null,
                attachment_url: expense.attachment_path ? '{{ asset('storage') }}/' + expense.attachment_path : null
            };
            this.editModalOpen = true;
            this.$nextTick(() => {
                this.$refs.editExpenseTitle?.focus();
            });
        },
        confirmDelete(expense) {
            this.expenseToDelete = expense;
            this.deleteConfirmOpen = true;
        },
        openReceipt(url, title) {
            this.activeReceiptUrl = url;
            this.activeReceiptTitle = title;
            this.receiptModalOpen = true;
        }
     }"
     @keydown.escape.window="if (createModalOpen) createModalOpen = false; if (editModalOpen) editModalOpen = false; if (deleteConfirmOpen) deleteConfirmOpen = false; if (receiptModalOpen) receiptModalOpen = false;"
     @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_expenses_view_mode', $event.detail)">

    {{-- ============================================================
         1. TOP ULTRA-DENSE HEADER BANNER (Standard v4.1)
         ============================================================ --}}
    <div class="px-2 py-1.5 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 select-none transition">
        <div class="flex items-center gap-2 min-w-0">
            <a href="{{ route('store.admin.dashboard', $storeRouteParams) }}"
               class="h-6 w-6 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 flex items-center justify-center text-slate-500 transition active:scale-95 shrink-0"
               title="{{ __('messages.back') }}">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div class="w-6 h-6 rounded bg-rose-600 text-white flex items-center justify-center font-bold text-xs shadow-2xs shrink-0">
                <span>💸</span>
            </div>
            <div class="flex items-center gap-1.5 min-w-0">
                <span class="text-[10px] font-bold text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-950/60 px-1.5 py-0.5 rounded border border-violet-200/50 dark:border-violet-800/50 truncate max-w-[120px] sm:max-w-none">
                    {{ $store->name }}
                </span>
                <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white tracking-tight truncate">
                    {{ __('messages.expenses_title') }}
                </h1>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200/80 dark:border-slate-700/80 font-mono shrink-0">
                    {{ number_format($expenses->total()) }} {{ __('messages.items') ?? 'records' }}
                </span>
                @if($fromDate && $toDate)
                    <span class="hidden md:inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/80 dark:text-indigo-300 border border-indigo-200/80 dark:border-indigo-800/80 font-mono shrink-0">
                        {{ $fromDate->format('d/m/Y') }} — {{ $toDate->format('d/m/Y') }}
                    </span>
                @endif
            </div>
        </div>

        {{-- Top Right Actions --}}
        <div class="flex items-center gap-1 sm:gap-1.5 shrink-0 self-start sm:self-auto">
            {{-- Quick Link to Expense Categories --}}
            <a href="{{ route('store.admin.expense_categories.index', $storeRouteParams) }}"
               class="h-7 px-2 sm:px-2.5 rounded text-[11px] sm:text-xs font-bold bg-white hover:bg-slate-50 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 transition inline-flex items-center gap-1 active:scale-95 shadow-2xs">
                <span>🏷️</span>
                <span>{{ __('messages.sidebar_expense_categories') }}</span>
            </a>

            {{-- Add New Expense Button --}}
            <button type="button" @click="openCreateModal()"
                    class="h-7 px-2.5 sm:px-3 rounded text-[11px] sm:text-xs font-black bg-violet-600 hover:bg-violet-700 text-white shadow-2xs transition inline-flex items-center gap-1 active:scale-95 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                <span>{{ __('messages.expenses_new') }}</span>
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
                <span>{{ __('messages.validation_error') }}</span>
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
        {{-- Total Filtered Expenses --}}
        <div class="rounded border p-2 sm:p-2.5 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm shrink-0 border bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 border-rose-100 dark:border-rose-900/50">
                💰
            </div>
            <div class="min-w-0 text-left">
                <div class="text-[11px] font-bold truncate text-slate-500 dark:text-slate-400">
                    {{ __('messages.expenses_total_filtered') }}
                </div>
                <div class="text-sm sm:text-base font-black text-rose-600 dark:text-rose-400 font-mono tracking-tight">
                    {{ format_currency($metrics['total_filtered_sum'], $store) }}
                </div>
                <div class="text-[10px] text-slate-400 font-mono truncate">
                    {{ number_format($metrics['total_count']) }} {{ __('messages.items') ?? 'records' }}
                </div>
            </div>
        </div>

        {{-- Today's Expenses --}}
        <div class="rounded border p-2 sm:p-2.5 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm shrink-0 border bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 border-sky-100 dark:border-sky-900/50">
                📅
            </div>
            <div class="min-w-0 text-left">
                <div class="text-[11px] font-bold truncate text-slate-500 dark:text-slate-400">
                    {{ __('messages.expenses_today') }}
                </div>
                <div class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 font-mono tracking-tight">
                    {{ format_currency($metrics['today_sum'], $store) }}
                </div>
                <div class="text-[10px] text-slate-400 font-mono truncate">
                    {{ now()->translatedFormat('d M Y') }}
                </div>
            </div>
        </div>

        {{-- This Month's Expenses --}}
        <div class="rounded border p-2 sm:p-2.5 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm shrink-0 border bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border-indigo-100 dark:border-indigo-900/50">
                📊
            </div>
            <div class="min-w-0 text-left">
                <div class="text-[11px] font-bold truncate text-slate-500 dark:text-slate-400">
                    {{ __('messages.expenses_this_month') }}
                </div>
                <div class="text-sm sm:text-base font-black text-indigo-600 dark:text-indigo-400 font-mono tracking-tight">
                    {{ format_currency($metrics['this_month_sum'], $store) }}
                </div>
                <div class="text-[10px] text-slate-400 font-mono truncate">
                    {{ now()->translatedFormat('F Y') }}
                </div>
            </div>
        </div>

        {{-- Top Expense Category --}}
        <div class="rounded border p-2 sm:p-2.5 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm shrink-0 border bg-violet-50 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 border-violet-100 dark:border-violet-900/50">
                🏷️
            </div>
            <div class="min-w-0 text-left">
                <div class="text-[11px] font-bold truncate text-slate-500 dark:text-slate-400">
                    {{ __('messages.expenses_top_category') }}
                </div>
                <div class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 truncate max-w-[130px]" title="{{ $metrics['top_category_name'] }}">
                    {{ $metrics['top_category_name'] }}
                </div>
                <div class="text-[10px] text-violet-600 dark:text-violet-400 font-bold font-mono truncate">
                    {{ format_currency($metrics['top_category_amount'], $store) }}
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         3. TOOLBAR AREA: Search, Filters, Period Dropdown, Date-to-Date Calendar, Sort, View Toggle, Export
         ============================================================ --}}
    <x-admin.toolbar
        :search="request('search', '')"
        :searchPlaceholder="__('messages.expense_filter_search')"
        :sort="request('sort', 'newest')"
        :sortOptions="$sortOptions"
        :filters="$filters"
        :viewMode="request('view', 'table')"
        :showViewToggle="true"
        :showExportImport="true"
        :exportUrl="$exportBaseUrl"
        :totalCount="$metrics['total_count']"
        :paginator="$expenses"
    >
        {{-- Period Presets Dropdown on Toolbar --}}
        <form method="GET" class="shrink-0" data-auto-submit>
            @foreach (request()->except(['preset', 'expense_date_from', 'expense_date_to', 'date_from', 'date_to', 'page']) as $key => $val)
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
                    <option value="all" {{ ($preset ?? 'all') === 'all' && !request('expense_date_from') && !request('expense_date_to') ? 'selected' : '' }}>
                        {{ __('messages.period_all') }}
                    </option>
                    <option value="today" {{ ($preset ?? '') === 'today' ? 'selected' : '' }}>{{ __('messages.period_today') }}</option>
                    <option value="yesterday" {{ ($preset ?? '') === 'yesterday' ? 'selected' : '' }}>{{ __('messages.period_yesterday') }}</option>
                    <option value="this_week" {{ ($preset ?? '') === 'this_week' ? 'selected' : '' }}>{{ __('messages.period_this_week') }}</option>
                    <option value="this_month" {{ ($preset ?? '') === 'this_month' ? 'selected' : '' }}>{{ __('messages.period_this_month') }}</option>
                    <option value="last_month" {{ ($preset ?? '') === 'last_month' ? 'selected' : '' }}>{{ __('messages.period_last_month') }}</option>
                    <option value="this_year" {{ ($preset ?? '') === 'this_year' ? 'selected' : '' }}>{{ __('messages.period_this_year') }}</option>
                    @if(request('expense_date_from') || request('expense_date_to'))
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
            $hasCustomDate = (bool) (request('expense_date_from') || request('expense_date_to'));
            $dateButtonLabel = __('messages.date');
            if ($hasCustomDate) {
                $fromFmt = request('expense_date_from') ? \Carbon\Carbon::parse(request('expense_date_from'))->format('d/m') : '…';
                $toFmt = request('expense_date_to') ? \Carbon\Carbon::parse(request('expense_date_to'))->format('d/m') : '…';
                $dateButtonLabel = "{$fromFmt} → {$toFmt}";
            }
        @endphp
        <div class="relative shrink-0" x-data="{ datePopoverOpen: false }">
            <button type="button" @click="datePopoverOpen = !datePopoverOpen"
                    class="min-h-[36px] px-2.5 rounded-lg text-xs font-bold border transition inline-flex items-center gap-1.5 shadow-2xs {{ $hasCustomDate ? 'bg-violet-50 text-violet-700 border-violet-300 dark:bg-violet-950/60 dark:text-violet-300 dark:border-violet-800 ring-1 ring-violet-500/20' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200 border-slate-200/90 dark:border-slate-700' }}"
                    title="{{ __('messages.expense_date') }}">
                <span>📅</span>
                <span class="text-[11px] whitespace-nowrap">{{ $dateButtonLabel }}</span>
                <svg class="w-3 h-3 text-slate-400 transition-transform duration-150" :class="datePopoverOpen ? 'rotate-180 text-violet-600 dark:text-violet-400' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            {{-- Floating Popover Modal / Teleported to Body to prevent overflow clipping --}}
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
                    
                    <div class="relative w-full max-w-xs p-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl space-y-3 text-left"
                         @click.stop>
                        <div class="flex items-center justify-between border-b pb-2 border-slate-100 dark:border-slate-800">
                            <div class="flex items-center gap-2 text-xs font-black text-slate-900 dark:text-slate-100">
                                <span class="w-6 h-6 rounded-lg bg-violet-100 dark:bg-violet-950/80 text-violet-600 dark:text-violet-400 grid place-items-center text-xs">📅</span>
                                <span>{{ __('messages.expense_date') }}</span>
                            </div>
                            @if ($hasCustomDate)
                                <a href="{{ route('store.admin.expenses.index', array_merge($storeRouteParams, request()->except(['expense_date_from', 'expense_date_to', 'date_from', 'date_to', 'page']))) }}"
                                   class="text-[11px] text-rose-600 dark:text-rose-400 font-bold hover:underline">
                                    {{ __('messages.clear') }}
                                </a>
                            @else
                                <button type="button" @click="datePopoverOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-sm font-black">
                                    ✕
                                </button>
                            @endif
                        </div>

                        <form method="GET" class="space-y-3">
                            @foreach (request()->except(['expense_date_from', 'expense_date_to', 'date_from', 'date_to', 'preset', 'page']) as $key => $val)
                                @if (is_array($val))
                                    @foreach ($val as $subVal)
                                        <input type="hidden" name="{{ $key }}[]" value="{{ $subVal }}" />
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $key }}" value="{{ $val }}" />
                                @endif
                            @endforeach

                            <div class="space-y-1">
                                <label class="text-[11px] font-bold text-slate-600 dark:text-slate-300">စတင်သည့်ရက် (From):</label>
                                <input type="date" name="expense_date_from" value="{{ request('expense_date_from', $fromDate?->format('Y-m-d')) }}"
                                       class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500 [color-scheme:light] dark:[color-scheme:dark]" />
                            </div>

                            <div class="space-y-1">
                                <label class="text-[11px] font-bold text-slate-600 dark:text-slate-300">ပြီးဆုံးသည့်ရက် (To):</label>
                                <input type="date" name="expense_date_to" value="{{ request('expense_date_to', $toDate?->format('Y-m-d')) }}"
                                       class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500 [color-scheme:light] dark:[color-scheme:dark]" />
                            </div>

                            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                                <button type="button" @click="datePopoverOpen = false"
                                        class="px-3 py-1.5 text-xs text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-bold rounded-lg transition">
                                    {{ __('messages.cancel') }}
                                </button>
                                <button type="submit"
                                        class="px-4 py-1.5 bg-violet-600 hover:bg-violet-500 text-white rounded-xl text-xs font-black shadow-xs active:scale-95 transition">
                                    ✓ {{ __('messages.apply') ?? 'Filter' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </template>
        </div>
    </x-admin.toolbar>

    {{-- Floating Action Button for Mobile Quick Add --}}
    <button type="button" @click="openCreateModal()"
            class="fixed bottom-5 right-5 z-40 sm:hidden w-12 h-12 rounded-full bg-violet-600 hover:bg-violet-700 text-white shadow-xl shadow-violet-900/40 flex items-center justify-center text-2xl font-bold active:scale-95 transition cursor-pointer"
            title="{{ __('messages.expenses_new') }}">
        +
    </button>

    {{-- ============================================================
         5. EXPENSES LIST (Table View)
         ============================================================ --}}
    <div x-show="viewMode === 'table'" x-cloak class="w-full bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs overflow-hidden transition">
        
        {{-- Mobile Swipe Hint Bar --}}
        <div class="sm:hidden px-2.5 py-1 bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between text-[10px] font-semibold text-slate-500 dark:text-slate-400">
            <span class="flex items-center gap-1">
                <span>⟷</span>
                <span>{{ __('messages.swipe_hint') ?? 'Swipe table' }}</span>
            </span>
            <span class="font-mono text-[10px] px-1.5 py-0.2 bg-slate-200/70 dark:bg-slate-700 rounded">{{ $expenses->total() }} {{ __('messages.items') ?? 'items' }}</span>
        </div>

        @if ($expenses->isEmpty())
            <div class="py-10 text-center text-slate-400 dark:text-slate-500 text-xs space-y-1.5">
                <div class="text-3xl">💸</div>
                <p class="font-bold text-slate-600 dark:text-slate-300">{{ __('messages.expense_no_items') }}</p>
                <p class="text-[11px] text-slate-400">{{ __('messages.no_matching_records') }}</p>
                <button type="button" @click="openCreateModal()"
                        class="mt-1.5 px-3 py-1 rounded text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-2xs transition inline-flex items-center gap-1 cursor-pointer">
                    <span>+</span>
                    <span>{{ __('messages.expenses_new') }}</span>
                </button>
            </div>
        @else
            <div class="overflow-x-auto scrollbar-thin">
                <table class="w-full text-left border-collapse min-w-[760px]">
                    <thead>
                        <tr class="border-b border-slate-200/80 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-[10px] sm:text-[11px] font-black text-slate-500 uppercase tracking-wider">
                            <th class="px-2.5 py-2 whitespace-nowrap">{{ __('messages.expense_voucher_no') }}</th>
                            <th class="px-2.5 py-2 whitespace-nowrap">{{ __('messages.stock_ledger_date') }}</th>
                            <th class="px-2.5 py-2 min-w-[160px]">{{ __('messages.expense_title') }}</th>
                            <th class="px-2.5 py-2 whitespace-nowrap">{{ __('messages.category') }}</th>
                            <th class="px-2.5 py-2 text-right whitespace-nowrap">{{ __('messages.expense_amount') }}</th>
                            <th class="px-2.5 py-2 whitespace-nowrap">{{ __('messages.expense_payment_method') }}</th>
                            <th class="px-2.5 py-2 whitespace-nowrap">{{ __('messages.expense_paid_to') }}</th>
                            <th class="px-2.5 py-2 whitespace-nowrap">{{ __('messages.expense_recorded_by') }}</th>
                            <th class="px-2.5 py-2 text-right whitespace-nowrap">{{ __('messages.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs font-medium text-slate-700 dark:text-slate-300">
                        @foreach ($expenses as $exp)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition duration-150">
                                {{-- Voucher Number & Attachment button --}}
                                <td class="px-2.5 py-2 whitespace-nowrap font-mono text-[11px]">
                                    <div class="font-bold text-rose-600 dark:text-rose-400">{{ $exp->expense_number }}</div>
                                    @if ($exp->attachment_path)
                                        <button type="button" @click="openReceipt('{{ asset('storage/' . $exp->attachment_path) }}', '{{ addslashes($exp->title) }}')"
                                                class="inline-flex items-center gap-0.5 text-[10px] text-teal-600 hover:text-teal-700 dark:text-teal-400 font-bold cursor-pointer mt-0.5"
                                                title="{{ __('messages.expense_attachment_view') }}">
                                            <span>📎</span>
                                            <span>{{ __('messages.expense_attachment_view') }}</span>
                                        </button>
                                    @endif
                                </td>

                                {{-- Date --}}
                                <td class="px-2.5 py-2 whitespace-nowrap text-slate-600 dark:text-slate-400 font-mono text-[11px]">
                                    {{ $exp->expense_date?->format('d/m/Y') }}
                                </td>

                                {{-- Title & Notes --}}
                                <td class="px-2.5 py-2">
                                    <div class="font-bold text-slate-900 dark:text-slate-100 text-xs sm:text-sm">{{ $exp->title }}</div>
                                    @if ($exp->notes)
                                        <div class="text-[11px] text-slate-400 truncate max-w-xs leading-tight mt-0.5" title="{{ $exp->notes }}">{{ $exp->notes }}</div>
                                    @endif
                                </td>

                                {{-- Category --}}
                                <td class="px-2.5 py-2 whitespace-nowrap">
                                    @if ($exp->category)
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[11px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200/80 dark:border-slate-700">
                                            @if($exp->category->color)
                                                <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $exp->category->color }};"></span>
                                            @endif
                                            <span>{{ $exp->category->name }}</span>
                                        </span>
                                    @else
                                        <span class="text-slate-400 italic text-[11px]">—</span>
                                    @endif
                                </td>

                                {{-- Amount (Highlighted) --}}
                                <td class="px-2.5 py-2 text-right font-mono tabular-nums font-black text-rose-600 dark:text-rose-400 whitespace-nowrap">
                                    <span class="px-1.5 py-0.5 rounded bg-rose-50 dark:bg-rose-950/50 text-[12px] sm:text-xs">
                                        {{ format_currency($exp->amount, $store) }}
                                    </span>
                                </td>

                                {{-- Payment Method --}}
                                <td class="px-2.5 py-2 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $paymentMethodBadgeClass($exp->payment_method) }}">
                                        {{ $paymentMethodOptions[$exp->payment_method] ?? ucfirst($exp->payment_method) }}
                                    </span>
                                </td>

                                {{-- Paid To --}}
                                <td class="px-2.5 py-2 text-slate-700 dark:text-slate-300 whitespace-nowrap">
                                    <div class="font-semibold">{{ $exp->paid_to ?? '—' }}</div>
                                    @if ($exp->reference_no)
                                        <div class="text-[10px] text-slate-400 font-mono">Ref: {{ $exp->reference_no }}</div>
                                    @endif
                                </td>

                                {{-- Recorded By --}}
                                <td class="px-2.5 py-2 text-slate-600 dark:text-slate-400 whitespace-nowrap text-[11px]">
                                    {{ $exp->recorder?->name ?? '—' }}
                                </td>

                                {{-- Actions --}}
                                <td class="px-2.5 py-2 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" @click="openEditModal({{ json_encode($exp) }})"
                                                class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition cursor-pointer"
                                                title="{{ __('messages.edit') }}">
                                            {{ __('messages.edit') }}
                                        </button>
                                        <button type="button" @click="confirmDelete({{ json_encode($exp) }})"
                                                class="p-1 rounded text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition cursor-pointer"
                                                title="{{ __('messages.delete') }}">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t-2 border-slate-200 dark:border-slate-800 bg-slate-50/90 dark:bg-slate-800/60 font-bold">
                        <tr>
                            <td colspan="4" class="py-2 px-2.5 text-slate-900 dark:text-slate-100 uppercase tracking-wider text-[11px]">
                                {{ __('messages.total') }} ({{ number_format($expenses->total()) }} {{ __('messages.items') ?? 'items' }})
                            </td>
                            <td class="py-2 px-2.5 text-right font-mono tabular-nums text-rose-600 dark:text-rose-400 text-xs sm:text-sm font-black whitespace-nowrap">
                                {{ format_currency($metrics['total_filtered_sum'], $store) }}
                            </td>
                            <td colspan="4"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>

    {{-- ============================================================
         6. EXPENSES LIST (Card View)
         ============================================================ --}}
    <div x-show="viewMode === 'card'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-0.5 sm:gap-1">
        @forelse ($expenses as $exp)
            <div class="rounded bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-2.5 shadow-2xs hover:shadow-xs transition flex flex-col justify-between space-y-2 group">
                <div class="space-y-1.5">
                    {{-- Top Voucher & Badge --}}
                    <div class="flex items-start justify-between gap-1.5 border-b border-slate-100 dark:border-slate-800 pb-1.5">
                        <div>
                            <span class="font-mono text-[11px] font-bold text-rose-600 dark:text-rose-400">{{ $exp->expense_number }}</span>
                            <div class="text-[10px] text-slate-400 font-mono">{{ $exp->expense_date?->format('d M Y') }}</div>
                        </div>
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold border {{ $paymentMethodBadgeClass($exp->payment_method) }}">
                            {{ $paymentMethodOptions[$exp->payment_method] ?? ucfirst($exp->payment_method) }}
                        </span>
                    </div>

                    {{-- Title & Category --}}
                    <div>
                        <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-slate-100">{{ $exp->title }}</h4>
                        @if ($exp->category)
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 flex items-center gap-1">
                                @if($exp->category->color)
                                    <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $exp->category->color }};"></span>
                                @endif
                                <span>{{ $exp->category->name }}</span>
                            </div>
                        @endif
                        @if ($exp->notes)
                            <div class="text-[11px] text-slate-400 mt-0.5 line-clamp-2 leading-tight">{{ $exp->notes }}</div>
                        @endif
                    </div>
                </div>

                {{-- Amount & Actions --}}
                <div class="pt-1.5 border-t border-slate-100 dark:border-slate-800 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] text-slate-400">{{ __('messages.expense_amount') }}:</span>
                        <span class="text-sm sm:text-base font-black font-mono text-rose-600 dark:text-rose-400 tabular-nums">
                            {{ format_currency($exp->amount, $store) }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-400 pt-1 border-t border-slate-100 dark:border-slate-800">
                        <div class="truncate max-w-[50%]">{{ $exp->paid_to ? __('messages.expense_paid_to') . ': ' . $exp->paid_to : '' }}</div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            @if ($exp->attachment_path)
                                <button type="button" @click="openReceipt('{{ asset('storage/' . $exp->attachment_path) }}', '{{ addslashes($exp->title) }}')"
                                        class="text-teal-600 dark:text-teal-400 font-bold hover:underline cursor-pointer">
                                    📎 {{ __('messages.expense_attachment_view') }}
                                </button>
                            @endif
                            <button type="button" @click="openEditModal({{ json_encode($exp) }})"
                                    class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition cursor-pointer">
                                {{ __('messages.edit') }}
                            </button>
                            <button type="button" @click="confirmDelete({{ json_encode($exp) }})"
                                    class="p-1 rounded text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition cursor-pointer" title="{{ __('messages.delete') }}">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-10 text-center text-slate-400 text-xs bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800">
                <span class="text-3xl">💸</span>
                <p class="font-bold text-slate-600 dark:text-slate-300 mt-1">{{ __('messages.expense_no_items') }}</p>
                <button type="button" @click="openCreateModal()"
                        class="mt-1.5 px-3 py-1 rounded text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-2xs transition inline-flex items-center gap-1 cursor-pointer">
                    <span>+</span>
                    <span>{{ __('messages.expenses_new') }}</span>
                </button>
            </div>
        @endforelse
    </div>

    {{-- 7. Pagination --}}
    @if ($expenses->hasPages())
        <div class="p-2 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs">
            {{ $expenses->links() }}
        </div>
    @endif

    {{-- ============================================================
         CREATE EXPENSE MODAL (Teleport)
         ============================================================ --}}
    <template x-teleport="body">
        <div x-show="createModalOpen" x-cloak
             style="z-index: 99999;"
             class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/60 backdrop-blur-xs"
             @click.self="createModalOpen = false"
             @keydown.escape.window="createModalOpen = false">
            
            <div x-show="createModalOpen"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="w-full max-w-lg rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col text-left"
                 @click.stop>
                
                <div class="px-3.5 py-2.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/40 shrink-0">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded bg-rose-100 dark:bg-rose-950/80 text-rose-600 dark:text-rose-400 grid place-items-center text-xs font-bold shadow-inner">➕</span>
                        <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white">{{ __('messages.expenses_new') }}</h3>
                    </div>
                    <button type="button" @click="createModalOpen = false" class="w-6 h-6 rounded text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 flex items-center justify-center text-sm font-bold transition">✕</button>
                </div>

                <form method="POST" action="{{ route('store.admin.expenses.store', $storeRouteParams) }}" enctype="multipart/form-data" class="p-3.5 space-y-2.5 overflow-y-auto">
                    @csrf

                    {{-- Title / Description --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                            {{ __('messages.expense_title') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="title" x-ref="createExpenseTitle" required placeholder="Shop Rent, Utilities, Staff Meals..."
                               class="w-full h-8 px-2.5 rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs font-semibold text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none transition">
                    </div>

                    {{-- Amount & Date Grid --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                                {{ __('messages.expense_amount') }} ({{ $store->currency ?? 'MMK' }}) <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" step="0.01" min="1" name="amount" required placeholder="0.00"
                                   class="w-full h-8 px-2.5 rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs font-bold font-mono text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none transition">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                                {{ __('messages.expense_date') }} <span class="text-rose-500">*</span>
                            </label>
                            <input type="date" name="expense_date" value="{{ now()->toDateString() }}" required
                                   class="w-full h-8 px-2.5 rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs font-mono font-semibold text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none transition">
                        </div>
                    </div>

                    {{-- Category & Payment Method Grid --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                                {{ __('messages.category') }}
                            </label>
                            <select name="expense_category_id"
                                    class="w-full h-8 px-2 rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs font-semibold text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none transition cursor-pointer">
                                <option value="">-- {{ __('messages.expense_all_categories') }} --</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                                {{ __('messages.expense_payment_method') }} <span class="text-rose-500">*</span>
                            </label>
                            <select name="payment_method" required
                                    class="w-full h-8 px-2 rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs font-semibold text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none transition cursor-pointer">
                                @foreach ($paymentMethodOptions as $mKey => $mLabel)
                                    <option value="{{ $mKey }}" @selected($mKey === 'cash')>{{ $mLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Paid To & Reference No --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                                {{ __('messages.expense_paid_to') }}
                            </label>
                            <input type="text" name="paid_to" placeholder="e.g. KBZ Internet, Landlord"
                                   class="w-full h-8 px-2.5 rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs font-semibold text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none transition">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                                {{ __('messages.expense_reference_no') }}
                            </label>
                            <input type="text" name="reference_no" placeholder="e.g. Inv #99812"
                                   class="w-full h-8 px-2.5 rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs font-semibold text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none transition">
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                            {{ __('messages.expense_notes') }}
                        </label>
                        <textarea name="notes" rows="2" placeholder="Additional notes or details..."
                                  class="w-full px-2.5 py-1.5 rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none transition"></textarea>
                    </div>

                    {{-- File Attachment --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                            {{ __('messages.expense_attachment') }}
                        </label>
                        <input type="file" name="attachment" accept="image/jpeg,image/png,image/webp,application/pdf"
                               class="w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded file:border-0 file:text-xs file:font-bold file:bg-rose-50 file:text-rose-700 dark:file:bg-rose-950 dark:file:text-rose-300 hover:file:bg-rose-100">
                        <p class="text-[10px] text-slate-400 mt-0.5">{{ __('messages.expense_attachment_hint') }}</p>
                    </div>

                    {{-- Footer Actions --}}
                    <div class="pt-2.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-1.5">
                        <button type="button" @click="createModalOpen = false"
                                class="h-7 px-3 rounded text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition cursor-pointer">
                            {{ __('messages.cancel') }}
                        </button>
                        <button type="submit"
                                class="h-7 px-3.5 rounded text-xs font-black bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-500 hover:to-pink-500 text-white shadow-2xs transition cursor-pointer">
                            {{ __('messages.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    {{-- ============================================================
         EDIT EXPENSE MODAL (Teleport)
         ============================================================ --}}
    <template x-teleport="body">
        <div x-show="editModalOpen" x-cloak
             style="z-index: 99999;"
             class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/60 backdrop-blur-xs"
             @click.self="editModalOpen = false"
             @keydown.escape.window="editModalOpen = false">
            
            <div x-show="editModalOpen"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="w-full max-w-lg rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col text-left"
                 @click.stop>
                
                <div class="px-3.5 py-2.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/40 shrink-0">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded bg-rose-100 dark:bg-rose-950/80 text-rose-600 dark:text-rose-400 grid place-items-center text-xs font-bold shadow-inner">✏️</span>
                        <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white">{{ __('messages.expenses_edit') }}</h3>
                    </div>
                    <button type="button" @click="editModalOpen = false" class="w-6 h-6 rounded text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 flex items-center justify-center text-sm font-bold transition">✕</button>
                </div>

                <form method="POST" :action="'{{ url('/store/' . $store->slug . '/admin/expenses') }}/' + editingExpense.id" enctype="multipart/form-data" class="p-3.5 space-y-2.5 overflow-y-auto">
                    @csrf
                    @method('PUT')

                    {{-- Title / Description --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                            {{ __('messages.expense_title') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="title" x-ref="editExpenseTitle" x-model="editingExpense.title" required
                               class="w-full h-8 px-2.5 rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs font-semibold text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none transition">
                    </div>

                    {{-- Amount & Date Grid --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                                {{ __('messages.expense_amount') }} ({{ $store->currency ?? 'MMK' }}) <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" step="0.01" min="1" name="amount" x-model="editingExpense.amount" required
                                   class="w-full h-8 px-2.5 rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs font-bold font-mono text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none transition">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                                {{ __('messages.expense_date') }} <span class="text-rose-500">*</span>
                            </label>
                            <input type="date" name="expense_date" x-model="editingExpense.expense_date" required
                                   class="w-full h-8 px-2.5 rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs font-mono font-semibold text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none transition">
                        </div>
                    </div>

                    {{-- Category & Payment Method Grid --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                                {{ __('messages.category') }}
                            </label>
                            <select name="expense_category_id" x-model="editingExpense.expense_category_id"
                                    class="w-full h-8 px-2 rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs font-semibold text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none transition cursor-pointer">
                                <option value="">-- {{ __('messages.expense_all_categories') }} --</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                                {{ __('messages.expense_payment_method') }} <span class="text-rose-500">*</span>
                            </label>
                            <select name="payment_method" x-model="editingExpense.payment_method" required
                                    class="w-full h-8 px-2 rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs font-semibold text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none transition cursor-pointer">
                                @foreach ($paymentMethodOptions as $mKey => $mLabel)
                                    <option value="{{ $mKey }}">{{ $mLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Paid To & Reference No --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                                {{ __('messages.expense_paid_to') }}
                            </label>
                            <input type="text" name="paid_to" x-model="editingExpense.paid_to"
                                   class="w-full h-8 px-2.5 rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs font-semibold text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none transition">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                                {{ __('messages.expense_reference_no') }}
                            </label>
                            <input type="text" name="reference_no" x-model="editingExpense.reference_no"
                                   class="w-full h-8 px-2.5 rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs font-semibold text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none transition">
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                            {{ __('messages.expense_notes') }}
                        </label>
                        <textarea name="notes" rows="2" x-model="editingExpense.notes"
                                  class="w-full px-2.5 py-1.5 rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none transition"></textarea>
                    </div>

                    {{-- File Attachment --}}
                    <div class="space-y-1">
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300">
                            {{ __('messages.expense_attachment') }}
                        </label>
                        
                        <template x-if="editingExpense.attachment_url">
                            <div class="flex items-center justify-between p-2 rounded bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs">
                                <a :href="editingExpense.attachment_url" target="_blank" class="font-bold text-teal-600 dark:text-teal-400 hover:underline flex items-center gap-1">
                                    <span>📎</span>
                                    <span>{{ __('messages.expense_attachment_view') }}</span>
                                </a>
                                <label class="flex items-center gap-1.5 text-rose-600 text-[11px] font-bold cursor-pointer">
                                    <input type="checkbox" name="remove_attachment" value="1" class="rounded text-rose-600">
                                    <span>{{ __('messages.expense_remove_attachment') }}</span>
                                </label>
                            </div>
                        </template>

                        <input type="file" name="attachment" accept="image/jpeg,image/png,image/webp,application/pdf"
                               class="w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded file:border-0 file:text-xs file:font-bold file:bg-rose-50 file:text-rose-700 dark:file:bg-rose-950 dark:file:text-rose-300 hover:file:bg-rose-100">
                    </div>

                    {{-- Footer Actions --}}
                    <div class="pt-2.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-1.5">
                        <button type="button" @click="editModalOpen = false"
                                class="h-7 px-3 rounded text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition cursor-pointer">
                            {{ __('messages.cancel') }}
                        </button>
                        <button type="submit"
                                class="h-7 px-3.5 rounded text-xs font-black bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-500 hover:to-pink-500 text-white shadow-2xs transition cursor-pointer">
                            {{ __('messages.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    {{-- ============================================================
         DELETE CONFIRMATION MODAL (Teleport)
         ============================================================ --}}
    <template x-teleport="body">
        <div x-show="deleteConfirmOpen" x-cloak
             style="z-index: 99999;"
             class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/60 backdrop-blur-xs"
             @click.self="deleteConfirmOpen = false"
             @keydown.escape.window="deleteConfirmOpen = false">
            <div x-show="deleteConfirmOpen"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="w-full max-w-sm rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl p-4 space-y-3 text-center"
                 @click.stop>
                
                <div class="w-10 h-10 rounded-full bg-rose-100 dark:bg-rose-950/80 text-rose-600 dark:text-rose-400 grid place-items-center mx-auto text-xl shadow-inner">
                    ⚠️
                </div>

                <div>
                    <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white">{{ __('messages.expense_delete_confirm') }}</h3>
                    <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mt-1 font-mono" x-text="expenseToDelete ? expenseToDelete.expense_number + ' — ' + expenseToDelete.title : ''"></p>
                </div>

                <form method="POST" :action="'{{ url('/store/' . $store->slug . '/admin/expenses') }}/' + (expenseToDelete ? expenseToDelete.id : '')"
                      class="flex items-center justify-center gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    @csrf
                    @method('DELETE')
                    <button type="button" @click="deleteConfirmOpen = false"
                            class="h-7 px-3.5 rounded text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition cursor-pointer">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit"
                            class="h-7 px-4 rounded text-xs font-black bg-rose-600 hover:bg-rose-700 text-white shadow-2xs transition cursor-pointer">
                        {{ __('messages.delete') }}
                    </button>
                </form>
            </div>
        </div>
    </template>

    {{-- ============================================================
         RECEIPT QUICK VIEW MODAL (Teleport)
         ============================================================ --}}
    <template x-teleport="body">
        <div x-show="receiptModalOpen" x-cloak
             style="z-index: 99999;"
             class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/70 backdrop-blur-xs"
             @click.self="receiptModalOpen = false"
             @keydown.escape.window="receiptModalOpen = false">
            
            <div x-show="receiptModalOpen"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="w-full max-w-2xl rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-3.5 shadow-2xl space-y-2.5 text-left"
                 @click.stop>
                
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                    <div class="flex items-center gap-1.5 min-w-0">
                        <span class="text-sm">📎</span>
                        <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-100 truncate" x-text="activeReceiptTitle"></h3>
                    </div>
                    <button type="button" @click="receiptModalOpen = false" class="w-6 h-6 rounded text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 flex items-center justify-center text-sm font-bold transition">✕</button>
                </div>

                <div class="flex items-center justify-center bg-slate-50 dark:bg-slate-950 rounded-lg overflow-hidden min-h-[260px] max-h-[70vh] border border-slate-100 dark:border-slate-850">
                    <template x-if="activeReceiptUrl && activeReceiptUrl.endsWith('.pdf')">
                        <iframe :src="activeReceiptUrl" class="w-full h-[450px] border-0"></iframe>
                    </template>
                    <template x-if="activeReceiptUrl && !activeReceiptUrl.endsWith('.pdf')">
                        <img :src="activeReceiptUrl" :alt="activeReceiptTitle" class="max-w-full max-h-[65vh] object-contain p-2">
                    </template>
                </div>

                <div class="flex items-center justify-between pt-1 border-t border-slate-100 dark:border-slate-800">
                    <a :href="activeReceiptUrl" target="_blank" download
                       class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline inline-flex items-center gap-1">
                        <span>⬇️</span>
                        <span>{{ __('messages.download') ?? 'Download' }}</span>
                    </a>
                    <button type="button" @click="receiptModalOpen = false"
                            class="h-7 px-3 rounded bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 text-xs font-bold hover:bg-slate-200 dark:hover:bg-slate-700 transition cursor-pointer">
                        {{ __('messages.close') }}
                    </button>
                </div>
            </div>
        </div>
    </template>

</div>
@endsection
