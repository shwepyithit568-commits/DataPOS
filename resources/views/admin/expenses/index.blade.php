@extends('layouts.admin.app')

@section('content')
<div class="w-full space-y-5 sm:space-y-6 pb-12"
     x-data="{
        viewMode: localStorage.getItem('admin_view_mode') || 'table',
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
     @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_view_mode', $event.detail)">

    {{-- 1. Top Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <span class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 grid place-items-center text-xl sm:text-2xl font-bold shadow-sm flex-shrink-0">
                💸
            </span>
            <div class="min-w-0">
                <h1 class="text-lg sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2 truncate">
                    <span class="truncate">{{ __('messages.expenses_title') }}</span>
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $store->name }} · {{ __('messages.expenses_subtitle') }}</p>
            </div>
        </div>

        {{-- Top Right Actions (Add Expense Button only — CSV Export moved to Toolbar) --}}
        <div class="flex items-center gap-2.5 self-start sm:self-auto">
            <button type="button" @click="createModalOpen = true"
                    class="px-4 py-2.5 rounded-2xl text-xs sm:text-sm font-black bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-500/20 transition flex items-center gap-2 active:scale-95">
                <span class="text-base leading-none">+</span>
                <span>{{ __('messages.expenses_new') }}</span>
            </button>
        </div>
    </div>

    {{-- 2. Flash Messages --}}
    @if (session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 rounded-3xl text-xs font-bold text-emerald-700 dark:text-emerald-300 flex items-center gap-2.5 shadow-sm">
            <span class="w-6 h-6 rounded-full bg-emerald-100 dark:bg-emerald-900 grid place-items-center text-emerald-600 dark:text-emerald-300 font-black">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-4 bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 rounded-3xl text-xs font-bold text-rose-700 dark:text-rose-300 space-y-1 shadow-sm">
            @foreach ($errors->all() as $error)
                <p>• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- 3. 4 Stat KPI Metric Cards (Responsive 1-col mobile, 2-col tablet, 4-col desktop) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
        {{-- Total Filtered Amount --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm flex items-center justify-between transition hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-1 truncate">{{ __('messages.expenses_total_filtered') }}</p>
                <h3 class="text-xl sm:text-2xl font-black text-rose-600 dark:text-rose-400 font-mono tracking-tight">{{ number_format($metrics['total_filtered_sum']) }} <span class="text-xs text-slate-400 font-sans">MMK</span></h3>
                <p class="text-[11px] text-slate-400 font-semibold mt-0.5">{{ number_format($metrics['total_count']) }} items</p>
            </div>
            <span class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 grid place-items-center text-xl font-bold shadow-inner flex-shrink-0">
                💰
            </span>
        </div>

        {{-- Today's Expenses --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm flex items-center justify-between transition hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-sky-600 dark:text-sky-400 mb-1 truncate">{{ __('messages.expenses_today') }}</p>
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white font-mono tracking-tight">{{ number_format($metrics['today_sum']) }} <span class="text-xs text-slate-400 font-sans">MMK</span></h3>
                <p class="text-[11px] text-slate-400 font-semibold mt-0.5">{{ now()->format('M d, Y') }}</p>
            </div>
            <span class="w-12 h-12 rounded-2xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 grid place-items-center text-xl font-bold shadow-inner flex-shrink-0">
                📅
            </span>
        </div>

        {{-- This Month's Expenses --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm flex items-center justify-between transition hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-indigo-600 dark:text-indigo-400 mb-1 truncate">{{ __('messages.expenses_this_month') }}</p>
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white font-mono tracking-tight">{{ number_format($metrics['this_month_sum']) }} <span class="text-xs text-slate-400 font-sans">MMK</span></h3>
                <p class="text-[11px] text-slate-400 font-semibold mt-0.5">{{ now()->format('F Y') }}</p>
            </div>
            <span class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 grid place-items-center text-xl font-bold shadow-inner flex-shrink-0">
                📊
            </span>
        </div>

        {{-- Top Expense Category --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm flex items-center justify-between transition hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-violet-600 dark:text-violet-400 mb-1 truncate">{{ __('messages.expenses_top_category') }}</p>
                <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-white truncate" title="{{ $metrics['top_category_name'] }}">{{ $metrics['top_category_name'] }}</h3>
                <p class="text-[11px] text-violet-500 font-mono font-bold mt-0.5">{{ number_format($metrics['top_category_amount']) }} MMK</p>
            </div>
            <span class="w-12 h-12 rounded-2xl bg-violet-50 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 grid place-items-center text-xl font-bold shadow-inner flex-shrink-0">
                🏷️
            </span>
        </div>
    </div>

    {{-- 4. Unified Admin Toolbar --}}
    @php
        $categoryFilterOptions = [];
        foreach ($allCategoriesForFilter as $cat) {
            $categoryFilterOptions[$cat->id] = ($cat->code ? "[{$cat->code}] " : '') . $cat->name;
        }

        $paymentMethodFilterOptions = \App\Http\Controllers\Admin\ExpenseController::PAYMENT_METHODS;

        // Build export URL that carries the current active filters/search/sort
        $expenseExportUrl = route('store.admin.expenses.export', array_merge($storeRouteParams, request()->only(['search', 'sort', 'category_id', 'payment_method', 'expense_date_from', 'expense_date_to'])));
    @endphp

    <x-admin.toolbar
        :search="request('search', $search)"
        :searchPlaceholder="__('messages.expense_filter_search')"
        :sort="request('sort', $sort)"
        :sortOptions="[
            'newest' => __('messages.expense_sort_newest'),
            'oldest' => __('messages.expense_sort_oldest'),
            'amount_desc' => __('messages.expense_sort_amount_desc'),
            'amount_asc' => __('messages.expense_sort_amount_asc'),
            'title_asc' => __('messages.expense_sort_title_az'),
        ]"
        :filters="[
            'category_id' => [
                'label' => __('messages.categories'),
                'options' => $categoryFilterOptions,
            ],
            'payment_method' => [
                'label' => __('messages.expense_payment_method'),
                'options' => $paymentMethodFilterOptions,
            ],
            'expense_date' => [
                'type' => 'date_range',
                'label' => __('messages.expense_date'),
            ],
        ]"
        :viewMode="'table'"
        :showViewToggle="true"
        :showExportImport="true"
        :exportUrl="$expenseExportUrl"
        :totalCount="$metrics['total_count']"
        :paginator="$expenses"
        :perPageOptions="[25 => '25', 50 => '50', 100 => '100', 'all' => 'All']"
    />

    {{-- 5. Card Grid View (Responsive Cards for Mobile & Tablet) --}}
    <div x-show="viewMode === 'card'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($expenses as $expense)
            <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm hover:shadow-md transition flex flex-col justify-between space-y-4 group">
                <div class="space-y-3">
                    {{-- Card Top: Voucher Number & Category Badge --}}
                    <div class="flex items-start justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div>
                            <span class="font-mono text-xs font-black text-slate-800 dark:text-slate-200">
                                {{ $expense->expense_number }}
                            </span>
                            <p class="text-[11px] text-slate-400 mt-0.5">
                                {{ $expense->expense_date?->format('d M Y') }}
                            </p>
                        </div>

                        @if ($expense->category)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black border"
                                  style="background-color: {{ $expense->category->color ? $expense->category->color . '15' : '#f1f5f9' }}; color: {{ $expense->category->color ?: '#475569' }}; border-color: {{ $expense->category->color ? $expense->category->color . '30' : '#cbd5e1' }};">
                                <span class="w-1.5 h-1.5 rounded-full" style="background-color: {{ $expense->category->color ?: '#64748b' }};"></span>
                                <span class="truncate max-w-[120px]">{{ $expense->category->name }}</span>
                            </span>
                        @else
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-400">
                                Uncategorized
                            </span>
                        @endif
                    </div>

                    {{-- Title & Amount --}}
                    <div>
                        <h3 class="font-black text-sm text-slate-900 dark:text-slate-100 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition">
                            {{ $expense->title }}
                        </h3>
                        <div class="mt-2 flex items-baseline justify-between">
                            <span class="text-base sm:text-lg font-black text-rose-600 dark:text-rose-400 font-mono">
                                {{ number_format($expense->amount) }} <span class="text-xs font-sans text-slate-400">MMK</span>
                            </span>

                            <span class="px-2 py-0.5 rounded-lg text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                {{ strtoupper($expense->payment_method) }}
                            </span>
                        </div>
                    </div>

                    {{-- Paid to & Notes --}}
                    @if ($expense->paid_to || $expense->notes)
                        <div class="text-xs text-slate-500 dark:text-slate-400 space-y-1 bg-slate-50 dark:bg-slate-800/40 p-2.5 rounded-2xl border border-slate-100 dark:border-slate-800">
                            @if ($expense->paid_to)
                                <p class="truncate"><span class="font-semibold text-slate-400">Paid to:</span> {{ $expense->paid_to }}</p>
                            @endif
                            @if ($expense->notes)
                                <p class="line-clamp-2 italic text-[11px]">{{ $expense->notes }}</p>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Card Bottom: Receipt Preview & Actions --}}
                <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                    <div>
                        @if ($expense->attachment_path)
                            <button type="button" @click="openReceipt('{{ asset('storage/' . $expense->attachment_path) }}', '{{ addslashes($expense->title) }}')"
                                    class="inline-flex items-center gap-1 text-[11px] font-bold text-blue-600 dark:text-blue-400 hover:underline">
                                <span>📎</span> <span>{{ __('messages.expense_attachment_view') }}</span>
                            </button>
                        @else
                            <span class="text-[11px] text-slate-400">No Slip</span>
                        @endif
                    </div>

                    <div class="flex items-center gap-1.5">
                        <button type="button" @click="openEditModal({{ json_encode($expense) }})"
                                class="px-3 py-1.5 rounded-xl font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition">
                            {{ __('messages.edit') }}
                        </button>

                        <form method="POST" action="{{ route('store.admin.expenses.destroy', array_merge($storeRouteParams, ['expense' => $expense->id])) }}"
                              onsubmit="return confirm('{{ __('messages.expense_delete_confirm') }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="p-1.5 rounded-xl text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition" title="Delete">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-16 text-center text-slate-400 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800">
                <span class="text-5xl">💸</span>
                <p class="text-base font-black text-slate-700 dark:text-slate-200 mt-2">{{ __('messages.expense_no_items') }}</p>
                <button type="button" @click="createModalOpen = true"
                        class="mt-3 px-4 py-2 rounded-xl text-xs font-black bg-blue-600 hover:bg-blue-500 text-white shadow transition inline-flex items-center gap-1.5">
                    <span>+</span>
                    <span>{{ __('messages.expenses_new') }}</span>
                </button>
            </div>
        @endforelse
    </div>

    {{-- 6. Table View (Desktop & Tablet with smooth horizontal swipe on Mobile) --}}
    <div x-show="viewMode === 'table'" x-cloak class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden">
        
        {{-- Mobile Swipe Hint Bar --}}
        <div class="sm:hidden px-4 py-2.5 bg-slate-50 dark:bg-slate-800/60 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px] font-semibold text-slate-500 dark:text-slate-400">
            <span class="flex items-center gap-1.5">
                <span class="animate-pulse">⟷</span>
                <span>ဘေးသို့ ဆွဲရွှေ့၍ ကြည့်နိုင်ပါသည် (Swipe table)</span>
            </span>
            <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-200/70 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-mono">{{ $expenses->total() }} items</span>
        </div>

        <div class="overflow-x-auto scrollbar-thin">
            <table class="w-full text-left border-collapse min-w-[1020px]">
                <thead>
                    <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 text-[11px] font-black text-slate-500 uppercase tracking-wider">
                        <th class="px-5 py-4 w-[160px]">{{ __('messages.expense_voucher_no') }}</th>
                        <th class="px-5 py-4 w-[110px]">{{ __('messages.expense_date') }}</th>
                        <th class="px-5 py-4">{{ __('messages.expense_title') }}</th>
                        <th class="px-5 py-4 w-[180px]">{{ __('messages.categories') }}</th>
                        <th class="px-5 py-4 w-[130px]">{{ __('messages.expense_payment_method') }}</th>
                        <th class="px-5 py-4 w-[150px] text-right">{{ __('messages.expense_amount') }}</th>
                        <th class="px-5 py-4 w-[80px] text-center">Slip</th>
                        <th class="px-5 py-4 w-[140px] text-right">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs font-medium text-slate-700 dark:text-slate-300">
                    @forelse ($expenses as $expense)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition duration-150">
                            {{-- Voucher # --}}
                            <td class="px-5 py-4 font-mono font-black text-slate-900 dark:text-slate-100 whitespace-nowrap">
                                {{ $expense->expense_number }}
                                @if ($expense->reference_no)
                                    <span class="block text-[10px] text-slate-400 font-normal">Ref: {{ $expense->reference_no }}</span>
                                @endif
                            </td>

                            {{-- Date --}}
                            <td class="px-5 py-4 whitespace-nowrap text-slate-600 dark:text-slate-400">
                                {{ $expense->expense_date?->format('d M Y') }}
                            </td>

                            {{-- Title & Paid To --}}
                            <td class="px-5 py-4">
                                <span class="font-black text-sm text-slate-900 dark:text-slate-100 block">{{ $expense->title }}</span>
                                @if ($expense->paid_to)
                                    <span class="text-[11px] text-slate-500 dark:text-slate-400">To: {{ $expense->paid_to }}</span>
                                @endif
                                @if ($expense->notes)
                                    <span class="text-[11px] text-slate-400 dark:text-slate-400 block line-clamp-1 italic">{{ $expense->notes }}</span>
                                @endif
                            </td>

                            {{-- Category --}}
                            <td class="px-5 py-4">
                                @if ($expense->category)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-black border"
                                          style="background-color: {{ $expense->category->color ? $expense->category->color . '15' : '#f1f5f9' }}; color: {{ $expense->category->color ?: '#475569' }}; border-color: {{ $expense->category->color ? $expense->category->color . '30' : '#cbd5e1' }};">
                                        <span class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: {{ $expense->category->color ?: '#64748b' }};"></span>
                                        <span class="truncate">{{ $expense->category->name }}</span>
                                    </span>
                                @else
                                    <span class="text-slate-400 italic">Uncategorized</span>
                                @endif
                            </td>

                            {{-- Payment Method --}}
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                    {{ strtoupper($expense->payment_method) }}
                                </span>
                            </td>

                            {{-- Amount --}}
                            <td class="px-5 py-4 text-right font-mono font-black text-sm text-rose-600 dark:text-rose-400 whitespace-nowrap">
                                {{ number_format($expense->amount) }} <span class="text-[10px] font-sans font-normal text-slate-400">MMK</span>
                            </td>

                            {{-- Slip Attachment --}}
                            <td class="px-5 py-4 text-center whitespace-nowrap">
                                @if ($expense->attachment_path)
                                    <button type="button" @click="openReceipt('{{ asset('storage/' . $expense->attachment_path) }}', '{{ addslashes($expense->title) }}')"
                                            class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 inline-grid place-items-center hover:scale-110 transition shadow-sm" title="View Voucher / Receipt">
                                        📎
                                    </button>
                                @else
                                    <span class="text-slate-300 dark:text-slate-700">—</span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button" @click="openEditModal({{ json_encode($expense) }})"
                                            class="px-3 py-1.5 rounded-xl text-[11px] font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition">
                                        {{ __('messages.edit') }}
                                    </button>

                                    <form method="POST" action="{{ route('store.admin.expenses.destroy', array_merge($storeRouteParams, ['expense' => $expense->id])) }}"
                                          onsubmit="return confirm('{{ __('messages.expense_delete_confirm') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="p-1.5 rounded-xl text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition" title="Delete">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-16 text-center text-slate-400">
                                <div class="space-y-2">
                                    <span class="text-4xl">💸</span>
                                    <p class="text-sm font-bold text-slate-600 dark:text-slate-300">{{ __('messages.expense_no_items') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 7. Bottom Pagination --}}
    @if ($expenses->hasPages())
        <div class="p-4 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm">
            {{ $expenses->links() }}
        </div>
    @endif

    {{-- ============================================================
         CREATE EXPENSE MODAL
         ============================================================ --}}
    <div x-show="createModalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto">
        <div @click.away="createModalOpen = false"
             x-show="createModalOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="w-full max-w-xl rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl p-6 space-y-5 my-8">
            
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-xl">💸</span>
                    <h3 class="text-base font-black text-slate-900 dark:text-white">{{ __('messages.expenses_new') }}</h3>
                </div>
                <button type="button" @click="createModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg">✕</button>
            </div>

            <form method="POST" action="{{ route('store.admin.expenses.store', $storeRouteParams) }}" enctype="multipart/form-data" class="space-y-4">
                @csrf

                {{-- Title --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.expense_title') }} <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="title" required placeholder="ဥပမာ - မီးသီးဝယ်ခြင်း၊ ဝန်ထမ်းနေ့လယ်စာ၊ အင်တာနက်လိုင်းကြေး..."
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs sm:text-sm font-semibold text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>

                {{-- Amount & Date --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.expense_amount') }} (MMK) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" name="amount" required step="0.01" min="0.01" placeholder="0.00"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs sm:text-sm font-mono font-bold text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.expense_date') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="expense_date" required value="{{ now()->toDateString() }}"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs sm:text-sm font-mono text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                </div>

                {{-- Category & Payment Method --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.categories') }}
                        </label>
                        <select name="expense_category_id"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs sm:text-sm font-semibold text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="">— {{ __('messages.select') }} —</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}">{{ ($cat->code ? "[{$cat->code}] " : '') . $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.expense_payment_method') }} <span class="text-rose-500">*</span>
                        </label>
                        <select name="payment_method" required
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs sm:text-sm font-semibold text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500 outline-none">
                            @foreach (\App\Http\Controllers\Admin\ExpenseController::PAYMENT_METHODS as $methodKey => $methodLabel)
                                <option value="{{ $methodKey }}">{{ $methodLabel }}</option>
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
                        <input type="text" name="paid_to" placeholder="ဥပမာ - YESC, City Mart, U Ba..."
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs sm:text-sm text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.expense_reference_no') }}
                        </label>
                        <input type="text" name="reference_no" placeholder="ဥပမာ - Bill #12345, Txn #..."
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs sm:text-sm font-mono text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                </div>

                {{-- Attachment File --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.expense_attachment') }}
                    </label>
                    <input type="file" name="attachment" accept="image/*,.pdf"
                           class="w-full text-xs text-slate-500 dark:text-slate-400 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-600 dark:file:bg-slate-800 dark:file:text-blue-400 hover:file:bg-blue-100">
                    <p class="text-[11px] text-slate-400 mt-1">{{ __('messages.expense_attachment_hint') }}</p>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.expense_notes') }}
                    </label>
                    <textarea name="notes" rows="2" placeholder="အပိုဆောင်း မှတ်ချက် (Optional)..."
                              class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs sm:text-sm text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500 outline-none"></textarea>
                </div>

                {{-- Buttons --}}
                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2">
                    <button type="button" @click="createModalOpen = false"
                            class="px-4 py-2.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl text-xs font-black bg-blue-600 hover:bg-blue-500 text-white shadow transition">
                        {{ __('messages.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         EDIT EXPENSE MODAL
         ============================================================ --}}
    <div x-show="editModalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto">
        <div @click.away="editModalOpen = false"
             x-show="editModalOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="w-full max-w-xl rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl p-6 space-y-5 my-8">
            
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-xl">✏️</span>
                    <h3 class="text-base font-black text-slate-900 dark:text-white">{{ __('messages.expenses_edit') }}</h3>
                </div>
                <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg">✕</button>
            </div>

            <form method="POST" :action="'{{ route('store.admin.expenses.index', $storeRouteParams) }}/' + editingExpense.id" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @method('PUT')

                {{-- Title --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.expense_title') }} <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="title" x-model="editingExpense.title" required
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs sm:text-sm font-semibold text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>

                {{-- Amount & Date --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.expense_amount') }} (MMK) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" name="amount" x-model="editingExpense.amount" required step="0.01" min="0.01"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs sm:text-sm font-mono font-bold text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.expense_date') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="expense_date" x-model="editingExpense.expense_date" required
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs sm:text-sm font-mono text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                </div>

                {{-- Category & Payment Method --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.categories') }}
                        </label>
                        <select name="expense_category_id" x-model="editingExpense.expense_category_id"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs sm:text-sm font-semibold text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="">— {{ __('messages.select') }} —</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}">{{ ($cat->code ? "[{$cat->code}] " : '') . $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.expense_payment_method') }} <span class="text-rose-500">*</span>
                        </label>
                        <select name="payment_method" x-model="editingExpense.payment_method" required
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs sm:text-sm font-semibold text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500 outline-none">
                            @foreach (\App\Http\Controllers\Admin\ExpenseController::PAYMENT_METHODS as $methodKey => $methodLabel)
                                <option value="{{ $methodKey }}">{{ $methodLabel }}</option>
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
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs sm:text-sm text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.expense_reference_no') }}
                        </label>
                        <input type="text" name="reference_no" x-model="editingExpense.reference_no"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs sm:text-sm font-mono text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                </div>

                {{-- Current Attachment & Replace/Remove --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.expense_attachment') }}
                    </label>

                    <template x-if="editingExpense.attachment_url">
                        <div class="mb-2 p-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-between">
                            <a :href="editingExpense.attachment_url" target="_blank" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1.5 truncate">
                                <span>📎</span> <span class="truncate">Current Attachment</span>
                            </a>
                            <label class="inline-flex items-center gap-1.5 text-[11px] font-bold text-rose-600 dark:text-rose-400 cursor-pointer">
                                <input type="checkbox" name="remove_attachment" value="1" class="rounded border-slate-300 text-rose-600">
                                <span>{{ __('messages.expense_remove_attachment') }}</span>
                            </label>
                        </div>
                    </template>

                    <input type="file" name="attachment" accept="image/*,.pdf"
                           class="w-full text-xs text-slate-500 dark:text-slate-400 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-600 dark:file:bg-slate-800 dark:file:text-blue-400 hover:file:bg-blue-100">
                    <p class="text-[11px] text-slate-400 mt-1">{{ __('messages.expense_attachment_hint') }}</p>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.expense_notes') }}
                    </label>
                    <textarea name="notes" x-model="editingExpense.notes" rows="2"
                              class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs sm:text-sm text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500 outline-none"></textarea>
                </div>

                {{-- Buttons --}}
                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2">
                    <button type="button" @click="editModalOpen = false"
                            class="px-4 py-2.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl text-xs font-black bg-blue-600 hover:bg-blue-500 text-white shadow transition">
                        {{ __('messages.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         RECEIPT IMAGE MODAL
         ============================================================ --}}
    <div x-show="receiptModalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm">
        <div @click.away="receiptModalOpen = false"
             x-show="receiptModalOpen"
             class="w-full max-w-2xl rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl p-6 space-y-4 overflow-hidden">
            
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-sm font-black text-slate-900 dark:text-white truncate" x-text="activeReceiptTitle"></h3>
                <button type="button" @click="receiptModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg">✕</button>
            </div>

            <div class="max-h-[70vh] overflow-auto flex items-center justify-center p-2 bg-slate-50 dark:bg-slate-800/50 rounded-2xl">
                <template x-if="activeReceiptUrl.toLowerCase().endsWith('.pdf')">
                    <div class="p-8 text-center space-y-3">
                        <span class="text-5xl">📄</span>
                        <p class="text-xs font-bold text-slate-600 dark:text-slate-300">PDF Document</p>
                        <a :href="activeReceiptUrl" target="_blank" class="px-4 py-2 rounded-xl text-xs font-black bg-blue-600 text-white inline-block">Open PDF</a>
                    </div>
                </template>
                <template x-if="!activeReceiptUrl.toLowerCase().endsWith('.pdf')">
                    <img :src="activeReceiptUrl" alt="Receipt Slip" class="max-h-[60vh] max-w-full object-contain rounded-xl shadow">
                </template>
            </div>

            <div class="flex justify-end">
                <button type="button" @click="receiptModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200">
                    {{ __('messages.close') }}
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
