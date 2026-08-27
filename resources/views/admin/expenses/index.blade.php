@extends('layouts.admin.app')

@section('title', __('messages.expenses_title') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

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
        'expense_date' => [
            'label' => __('messages.expense_date'),
            'type' => 'date_range',
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
            'cash' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
            'kpay' => 'bg-blue-100 text-blue-800 dark:bg-blue-950/80 dark:text-blue-300 border-blue-200 dark:border-blue-800',
            'wave' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300 border-amber-200 dark:border-amber-800',
            'cbpay' => 'bg-red-100 text-red-800 dark:bg-red-950/80 dark:text-red-300 border-red-200 dark:border-red-800',
            'bank_transfer' => 'bg-purple-100 text-purple-800 dark:bg-purple-950/80 dark:text-purple-300 border-purple-200 dark:border-purple-800',
            default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700',
        };
    };
@endphp

<div class="w-full space-y-2 sm:space-y-2.5"
     x-data="{
        viewMode: localStorage.getItem('admin_expenses_view_mode') || 'table',
        createModalOpen: false,
        editModalOpen: false,
        receiptModalOpen: false,
        activeReceiptUrl: '',
        activeReceiptTitle: '',
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
        },
        openReceipt(url, title) {
            this.activeReceiptUrl = url;
            this.activeReceiptTitle = title;
            this.receiptModalOpen = true;
        }
     }"
     @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_expenses_view_mode', $event.detail)">

    {{-- 1. Top Action Bar & Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 bg-white dark:bg-slate-900 rounded-xl p-3 sm:p-4 border border-slate-200/90 dark:border-slate-800 shadow-xs">
        <div class="space-y-1">
            <div class="flex items-center gap-1.5 text-xs text-slate-400 font-medium">
                <a href="{{ route('store.admin.dashboard', $storeRouteParams) }}" class="hover:text-slate-600 dark:hover:text-slate-200 transition">
                    {{ __('messages.admin_dashboard') }}
                </a>
                <span>/</span>
                <span class="text-slate-500 dark:text-slate-400">{{ __('messages.sidebar_finance') }}</span>
                <span>/</span>
                <span class="text-indigo-600 dark:text-indigo-400 font-bold">{{ __('messages.expenses_title') }}</span>
            </div>
            <div class="flex items-center gap-2.5 flex-wrap">
                <h1 class="text-lg sm:text-xl font-black text-slate-900 dark:text-slate-100 font-outfit tracking-tight">
                    {{ __('messages.expenses_title') }}
                </h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold font-mono bg-indigo-50 text-indigo-700 dark:bg-indigo-950/80 dark:text-indigo-300 border border-indigo-200/70 dark:border-indigo-800/80">
                    {{ number_format($expenses->total()) }} {{ __('messages.items') ?? 'records' }}
                </span>
                @if($fromDate && $toDate)
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold font-mono bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                        {{ $fromDate->format('d/m/Y') }} — {{ $toDate->format('d/m/Y') }}
                    </span>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('store.admin.expense_categories.index', $storeRouteParams) }}"
               class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-700 bg-white hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold transition inline-flex items-center gap-1.5 shadow-2xs">
                <span>🏷️</span>
                <span>{{ __('messages.sidebar_expense_categories') }}</span>
            </a>

            {{-- Add New Expense Button --}}
            <button type="button" @click="createModalOpen = true"
                    class="px-3.5 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition inline-flex items-center gap-1.5 shadow-2xs active:scale-95 cursor-pointer">
                <span class="text-sm font-black leading-none">+</span>
                <span>{{ __('messages.expenses_new') }}</span>
            </button>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="p-3 bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 rounded-xl text-xs font-bold text-emerald-700 dark:text-emerald-300 flex items-center gap-2 shadow-2xs">
            <span class="w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-900 grid place-items-center text-emerald-600 dark:text-emerald-300 font-black text-xs">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-3 bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 rounded-xl text-xs font-bold text-rose-700 dark:text-rose-300 space-y-1 shadow-2xs">
            @foreach ($errors->all() as $error)
                <p>• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- 2. 4 Financial KPI Metric Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-2.5">
        {{-- Total Filtered Amount --}}
        <div class="p-3.5 sm:p-4 rounded-xl border border-slate-200/90 bg-white/95 dark:border-slate-800 dark:bg-slate-900/95 shadow-xs space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('messages.expenses_total_filtered') }}</span>
                <div class="w-7 h-7 rounded-lg bg-rose-100 text-rose-700 dark:bg-rose-950/80 dark:text-rose-300 flex items-center justify-center font-bold text-xs shadow-2xs">
                    💰
                </div>
            </div>
            <div>
                <div class="text-xl sm:text-2xl font-black text-rose-600 dark:text-rose-400 font-outfit tabular-nums">
                    {{ number_format($metrics['total_filtered_sum'], 0) }} <span class="text-xs font-bold text-slate-500">{{ __('messages.currency_ks') }}</span>
                </div>
                <div class="text-[11px] text-slate-400 mt-1 flex items-center justify-between">
                    <span>{{ number_format($metrics['total_count']) }} {{ __('messages.items') ?? 'records' }}</span>
                </div>
            </div>
        </div>

        {{-- Today's Expenses --}}
        <div class="p-3.5 sm:p-4 rounded-xl border border-slate-200/90 bg-white/95 dark:border-slate-800 dark:bg-slate-900/95 shadow-xs space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-sky-700 dark:text-sky-400 uppercase tracking-wider">{{ __('messages.expenses_today') }}</span>
                <div class="w-7 h-7 rounded-lg bg-sky-100 text-sky-700 dark:bg-sky-950/80 dark:text-sky-300 flex items-center justify-center font-bold text-xs shadow-2xs">
                    📅
                </div>
            </div>
            <div>
                <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-slate-100 font-outfit tabular-nums">
                    {{ number_format($metrics['today_sum'], 0) }} <span class="text-xs font-bold text-slate-500">{{ __('messages.currency_ks') }}</span>
                </div>
                <div class="text-[11px] text-slate-400 mt-1">
                    <span>{{ now()->translatedFormat('d M Y') }}</span>
                </div>
            </div>
        </div>

        {{-- This Month's Expenses --}}
        <div class="p-3.5 sm:p-4 rounded-xl border border-slate-200/90 bg-white/95 dark:border-slate-800 dark:bg-slate-900/95 shadow-xs space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-indigo-700 dark:text-indigo-400 uppercase tracking-wider">{{ __('messages.expenses_this_month') }}</span>
                <div class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-700 dark:bg-indigo-950/80 dark:text-indigo-300 flex items-center justify-center font-bold text-xs shadow-2xs">
                    📊
                </div>
            </div>
            <div>
                <div class="text-xl sm:text-2xl font-black text-indigo-600 dark:text-indigo-400 font-outfit tabular-nums">
                    {{ number_format($metrics['this_month_sum'], 0) }} <span class="text-xs font-bold text-slate-500">{{ __('messages.currency_ks') }}</span>
                </div>
                <div class="text-[11px] text-slate-400 mt-1">
                    <span>{{ now()->translatedFormat('F Y') }}</span>
                </div>
            </div>
        </div>

        {{-- Top Expense Category --}}
        <div class="p-3.5 sm:p-4 rounded-xl border border-slate-200/90 bg-white/95 dark:border-slate-800 dark:bg-slate-900/95 shadow-xs space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-violet-700 dark:text-violet-400 uppercase tracking-wider">{{ __('messages.expenses_top_category') }}</span>
                <div class="w-7 h-7 rounded-lg bg-violet-100 text-violet-700 dark:bg-violet-950/80 dark:text-violet-300 flex items-center justify-center font-bold text-xs shadow-2xs">
                    🏷️
                </div>
            </div>
            <div>
                <div class="text-base sm:text-lg font-black text-slate-900 dark:text-slate-100 truncate" title="{{ $metrics['top_category_name'] }}">
                    {{ $metrics['top_category_name'] }}
                </div>
                <div class="text-[11px] text-violet-600 dark:text-violet-400 font-bold font-mono mt-1 tabular-nums">
                    {{ number_format($metrics['top_category_amount'], 0) }} {{ __('messages.currency_ks') }}
                </div>
            </div>
        </div>
    </div>

    {{-- 3. Quick Date Preset Pills --}}
    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs">
        @php
            $presets = [
                'all'        => __('messages.all'),
                'today'      => __('messages.period_today'),
                'yesterday'  => __('messages.period_yesterday'),
                'this_week'  => __('messages.period_this_week'),
                'this_month' => __('messages.period_this_month'),
                'last_month' => __('messages.period_last_month'),
                'this_year'  => __('messages.period_this_year'),
            ];
        @endphp
        @foreach ($presets as $pKey => $pLabel)
            <a href="{{ route('store.admin.expenses.index', array_merge($storeRouteParams, ['preset' => $pKey, 'category_id' => request('category_id'), 'payment_method' => request('payment_method'), 'search' => request('search'), 'sort' => request('sort')])) }}"
               class="px-2.5 sm:px-3 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition cursor-pointer {{ ($preset ?? 'all') === $pKey ? 'bg-indigo-600 text-white shadow-2xs' : 'bg-white text-slate-600 hover:bg-slate-100 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 border border-slate-200/80 dark:border-slate-800' }}">
                {{ $pLabel }}
            </a>
        @endforeach
    </div>

    {{-- 4. Standard Reusable Admin Toolbar Component --}}
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
        :paginator="$expenses"
    />

    {{-- 5. Expenses List (Table View) --}}
    <div x-show="viewMode === 'table'" class="rounded-xl border border-slate-200/90 bg-white/95 dark:border-slate-800 dark:bg-slate-900/95 shadow-xs overflow-hidden">
        @if ($expenses->isEmpty())
            <div class="py-12 text-center text-slate-400 dark:text-slate-500 text-xs space-y-1.5">
                <div class="text-3xl">💸</div>
                <p class="font-bold text-slate-600 dark:text-slate-300">{{ __('messages.expense_no_items') }}</p>
                <p class="text-[11px] text-slate-400">{{ __('messages.no_matching_records') }}</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider">
                            <th class="py-2.5 px-3">{{ __('messages.expense_voucher_no') }}</th>
                            <th class="py-2.5 px-3">{{ __('messages.stock_ledger_date') }}</th>
                            <th class="py-2.5 px-3">{{ __('messages.expense_title') }}</th>
                            <th class="py-2.5 px-3">{{ __('messages.category') }}</th>
                            <th class="py-2.5 px-3 text-right">{{ __('messages.expense_amount') }}</th>
                            <th class="py-2.5 px-3">{{ __('messages.expense_payment_method') }}</th>
                            <th class="py-2.5 px-3">{{ __('messages.expense_paid_to') }}</th>
                            <th class="py-2.5 px-3">{{ __('messages.expense_recorded_by') ?? 'Recorded By' }}</th>
                            <th class="py-2.5 px-3 text-right">{{ __('messages.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($expenses as $exp)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                                <td class="py-2.5 px-3 whitespace-nowrap font-mono text-[11px]">
                                    <div class="font-bold text-indigo-600 dark:text-indigo-400">{{ $exp->expense_number }}</div>
                                    @if ($exp->attachment_path)
                                        <button type="button" @click="openReceipt('{{ asset('storage/' . $exp->attachment_path) }}', '{{ addslashes($exp->title) }}')"
                                                class="inline-flex items-center gap-1 text-[10px] text-teal-600 hover:text-teal-700 dark:text-teal-400 font-bold mt-0.5 cursor-pointer">
                                            <span>📎</span>
                                            <span>{{ __('messages.expense_attachment_view') }}</span>
                                        </button>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 whitespace-nowrap text-slate-600 dark:text-slate-300 font-mono text-[11px]">
                                    {{ $exp->expense_date?->format('d/m/Y') }}
                                </td>
                                <td class="py-2.5 px-3">
                                    <div class="font-bold text-slate-900 dark:text-slate-100">{{ $exp->title }}</div>
                                    @if ($exp->notes)
                                        <div class="text-[11px] text-slate-400 truncate max-w-xs">{{ $exp->notes }}</div>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 whitespace-nowrap">
                                    @if ($exp->category)
                                        <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                            {{ $exp->category->name }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 italic text-[11px]">—</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono tabular-nums font-black text-rose-600 dark:text-rose-400 whitespace-nowrap">
                                    {{ number_format((float) $exp->amount, 0) }} <span class="text-[10px] font-sans font-semibold text-slate-400">{{ __('messages.currency_ks') }}</span>
                                </td>
                                <td class="py-2.5 px-3 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $paymentMethodBadgeClass($exp->payment_method) }}">
                                        {{ $paymentMethodOptions[$exp->payment_method] ?? ucfirst($exp->payment_method) }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-slate-700 dark:text-slate-300">
                                    <div>{{ $exp->paid_to ?? '—' }}</div>
                                    @if ($exp->reference_no)
                                        <div class="text-[10px] text-slate-400 font-mono">Ref: {{ $exp->reference_no }}</div>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                    {{ $exp->recorder?->name ?? '—' }}
                                </td>
                                <td class="py-2.5 px-3 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button" @click="openEditModal({{ json_encode($exp) }})"
                                                class="px-2 py-1 rounded-md text-[11px] font-bold text-slate-600 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer">
                                            {{ __('messages.edit') }}
                                        </button>
                                        <form method="POST" action="{{ route('store.admin.expenses.destroy', [...$storeRouteParams, 'expense' => $exp->id]) }}"
                                              onsubmit="return confirm('{{ __('messages.expense_delete_confirm') }}')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2 py-1 rounded-md text-[11px] font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/50 transition cursor-pointer">
                                                {{ __('messages.delete') }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t-2 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 font-bold">
                        <tr>
                            <td colspan="4" class="py-3 px-3 text-slate-900 dark:text-slate-100 uppercase tracking-wider text-[11px]">
                                {{ __('messages.total') }} ({{ number_format($expenses->total()) }} {{ __('messages.items') ?? 'items' }})
                            </td>
                            <td class="py-3 px-3 text-right font-mono tabular-nums text-rose-600 dark:text-rose-400 text-sm font-black whitespace-nowrap">
                                {{ number_format($metrics['total_filtered_sum'], 0) }} {{ __('messages.currency_ks') }}
                            </td>
                            <td colspan="4"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>

    {{-- 6. Expenses List (Card View) --}}
    <div x-show="viewMode === 'card'" style="display: none;" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
        @forelse ($expenses as $exp)
            <div class="p-3.5 rounded-xl border border-slate-200/90 bg-white/95 dark:border-slate-800 dark:bg-slate-900/95 shadow-xs space-y-2">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <span class="font-mono text-[11px] font-bold text-indigo-600 dark:text-indigo-400">{{ $exp->expense_number }}</span>
                        <div class="text-[11px] text-slate-400 font-mono">{{ $exp->expense_date?->format('d M Y') }}</div>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $paymentMethodBadgeClass($exp->payment_method) }}">
                        {{ $paymentMethodOptions[$exp->payment_method] ?? ucfirst($exp->payment_method) }}
                    </span>
                </div>

                <div class="pt-1">
                    <h4 class="font-bold text-xs text-slate-900 dark:text-slate-100">{{ $exp->title }}</h4>
                    @if ($exp->category)
                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">🏷️ {{ $exp->category->name }}</div>
                    @endif
                    @if ($exp->notes)
                        <div class="text-[11px] text-slate-400 mt-1 italic line-clamp-2">{{ $exp->notes }}</div>
                    @endif
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800">
                    <span class="text-xs text-slate-400">{{ __('messages.expense_amount') }}:</span>
                    <span class="text-base font-black font-outfit text-rose-600 dark:text-rose-400 tabular-nums">
                        {{ number_format((float) $exp->amount, 0) }} <span class="text-xs font-sans font-normal text-slate-400">{{ __('messages.currency_ks') }}</span>
                    </span>
                </div>

                <div class="flex items-center justify-between pt-2 text-[11px] text-slate-400">
                    <div>{{ $exp->paid_to ? 'Paid to: ' . $exp->paid_to : '' }}</div>
                    <div class="flex items-center gap-2">
                        @if ($exp->attachment_path)
                            <button type="button" @click="openReceipt('{{ asset('storage/' . $exp->attachment_path) }}', '{{ addslashes($exp->title) }}')"
                                    class="text-teal-600 dark:text-teal-400 font-bold hover:underline">
                                📎 {{ __('messages.expense_attachment_view') }}
                            </button>
                        @endif
                        <button type="button" @click="openEditModal({{ json_encode($exp) }})"
                                class="text-indigo-600 dark:text-indigo-400 font-bold hover:underline">
                            {{ __('messages.edit') }}
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center text-slate-400 text-xs">
                {{ __('messages.expense_no_items') }}
            </div>
        @endforelse
    </div>

    {{-- Create Expense Modal --}}
    <div x-show="createModalOpen" style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div @click.away="createModalOpen = false"
             class="w-full max-w-lg rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-xl space-y-4 max-h-[90vh] overflow-y-auto">
            
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-slate-100 font-outfit">
                    ➕ {{ __('messages.expenses_new') }}
                </h3>
                <button @click="createModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl font-bold">&times;</button>
            </div>

            <form method="POST" action="{{ route('store.admin.expenses.store', $storeRouteParams) }}" enctype="multipart/form-data" class="space-y-3">
                @csrf

                {{-- Title / Description --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.expense_title') }} <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="title" required placeholder="e.g. Wi-Fi bill, Electricity, Staff lunch"
                           class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-indigo-500">
                </div>

                {{-- Amount & Date Grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.expense_amount') }} (MMK) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" step="0.01" min="1" name="amount" required placeholder="0.00"
                               class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs font-bold font-mono text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.expense_date') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="expense_date" value="{{ now()->toDateString() }}" required
                               class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-indigo-500">
                    </div>
                </div>

                {{-- Category & Payment Method Grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.category') }}
                        </label>
                        <select name="expense_category_id"
                                class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-indigo-500">
                            <option value="">-- {{ __('messages.expense_all_categories') }} --</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.expense_payment_method') }} <span class="text-rose-500">*</span>
                        </label>
                        <select name="payment_method" required
                                class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-indigo-500">
                            @foreach ($paymentMethodOptions as $mKey => $mLabel)
                                <option value="{{ $mKey }}" @selected($mKey === 'cash')>{{ $mLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Paid To & Reference No --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.expense_paid_to') }}
                        </label>
                        <input type="text" name="paid_to" placeholder="e.g. KBZ Internet, Landlord"
                               class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.expense_reference_no') }}
                        </label>
                        <input type="text" name="reference_no" placeholder="e.g. Inv #99812"
                               class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-indigo-500">
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.expense_notes') }}
                    </label>
                    <textarea name="notes" rows="2" placeholder="Additional notes or details..."
                              class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-indigo-500"></textarea>
                </div>

                {{-- File Attachment --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.expense_attachment') }}
                    </label>
                    <input type="file" name="attachment" accept="image/jpeg,image/png,image/webp,application/pdf"
                           class="w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-950 dark:file:text-indigo-300 hover:file:bg-indigo-100">
                    <p class="text-[10px] text-slate-400 mt-1">{{ __('messages.expense_attachment_hint') }}</p>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="createModalOpen = false"
                            class="px-3.5 py-2 rounded-lg border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-2xs transition">
                        {{ __('messages.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Expense Modal --}}
    <div x-show="editModalOpen" style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div @click.away="editModalOpen = false"
             class="w-full max-w-lg rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-xl space-y-4 max-h-[90vh] overflow-y-auto">
            
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-slate-100 font-outfit">
                    ✏️ {{ __('messages.expenses_edit') }}
                </h3>
                <button @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl font-bold">&times;</button>
            </div>

            <form method="POST" :action="'{{ url('/store/' . $store->slug . '/admin/expenses') }}/' + editingExpense.id" enctype="multipart/form-data" class="space-y-3">
                @csrf
                @method('PUT')

                {{-- Title / Description --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.expense_title') }} <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="title" x-model="editingExpense.title" required
                           class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-indigo-500">
                </div>

                {{-- Amount & Date Grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.expense_amount') }} (MMK) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" step="0.01" min="1" name="amount" x-model="editingExpense.amount" required
                               class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs font-bold font-mono text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.expense_date') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="expense_date" x-model="editingExpense.expense_date" required
                               class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-indigo-500">
                    </div>
                </div>

                {{-- Category & Payment Method Grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.category') }}
                        </label>
                        <select name="expense_category_id" x-model="editingExpense.expense_category_id"
                                class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-indigo-500">
                            <option value="">-- {{ __('messages.expense_all_categories') }} --</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.expense_payment_method') }} <span class="text-rose-500">*</span>
                        </label>
                        <select name="payment_method" x-model="editingExpense.payment_method" required
                                class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-indigo-500">
                            @foreach ($paymentMethodOptions as $mKey => $mLabel)
                                <option value="{{ $mKey }}">{{ $mLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Paid To & Reference No --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.expense_paid_to') }}
                        </label>
                        <input type="text" name="paid_to" x-model="editingExpense.paid_to"
                               class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.expense_reference_no') }}
                        </label>
                        <input type="text" name="reference_no" x-model="editingExpense.reference_no"
                               class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-indigo-500">
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.expense_notes') }}
                    </label>
                    <textarea name="notes" rows="2" x-model="editingExpense.notes"
                              class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-indigo-500"></textarea>
                </div>

                {{-- File Attachment --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        {{ __('messages.expense_attachment') }}
                    </label>
                    
                    <template x-if="editingExpense.attachment_url">
                        <div class="flex items-center justify-between p-2 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs">
                            <a :href="editingExpense.attachment_url" target="_blank" class="font-bold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
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
                           class="w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-950 dark:file:text-indigo-300 hover:file:bg-indigo-100">
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="editModalOpen = false"
                            class="px-3.5 py-2 rounded-lg border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-2xs transition">
                        {{ __('messages.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Receipt Quick View Modal --}}
    <div x-show="receiptModalOpen" style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div @click.away="receiptModalOpen = false"
             class="w-full max-w-2xl rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-2xl space-y-3">
            
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 font-outfit" x-text="activeReceiptTitle"></h3>
                <button @click="receiptModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl font-bold">&times;</button>
            </div>

            <div class="flex items-center justify-center bg-slate-100 dark:bg-slate-950 rounded-xl overflow-hidden min-h-[300px] max-h-[70vh]">
                <template x-if="activeReceiptUrl && activeReceiptUrl.endsWith('.pdf')">
                    <iframe :src="activeReceiptUrl" class="w-full h-[500px] border-0"></iframe>
                </template>
                <template x-if="activeReceiptUrl && !activeReceiptUrl.endsWith('.pdf')">
                    <img :src="activeReceiptUrl" :alt="activeReceiptTitle" class="max-w-full max-h-[65vh] object-contain p-2">
                </template>
            </div>

            <div class="flex items-center justify-between pt-2">
                <a :href="activeReceiptUrl" target="_blank" download
                   class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline inline-flex items-center gap-1">
                    <span>⬇️</span>
                    <span>Download File</span>
                </a>
                <button type="button" @click="receiptModalOpen = false"
                        class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 text-xs font-bold hover:bg-slate-200 transition">
                    {{ __('messages.close') }}
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
