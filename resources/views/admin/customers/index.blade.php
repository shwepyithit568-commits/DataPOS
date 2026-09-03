@extends('layouts.admin.app')

@php
    $storeRouteParams = ['store_slug' => $store->slug];
    $customersArray = ($customers instanceof \Illuminate\Pagination\LengthAwarePaginator ? $customers->items() : $customers->all());
    $exportUrl = route('store.admin.customers.export', array_merge($storeRouteParams, request()->only(['search', 'sort', 'tab', 'role', 'status'])));
@endphp

@section('title', __('messages.sidebar_customer_directory') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
<div class="w-full space-y-0.5 pb-6"
     x-data="{
        viewMode: localStorage.getItem('admin_customer_view_mode') || 'card',
        createModalOpen: false,
        editModalOpen: false,
        allCustomers: {{ Illuminate\Support\Js::from($customersArray) }},
        newCustomer: {
            name: '',
            phone: '',
            email: '',
            role: 'retail_customer'
        },
        editingCustomer: {
            id: null,
            name: '',
            phone: '',
            email: '',
            role: 'retail_customer',
            status: 'active'
        },
        openCreateModal() {
            this.newCustomer = {
                name: '',
                phone: '',
                email: '',
                role: 'retail_customer'
            };
            this.createModalOpen = true;
        },
        openEditModal(customer) {
            const membership = (customer.stores && customer.stores.length > 0) ? customer.stores[0].pivot : null;
            this.editingCustomer = {
                id: customer.id,
                name: customer.name || '',
                phone: customer.phone || '',
                email: customer.email || '',
                role: membership ? (membership.role || 'retail_customer') : 'retail_customer',
                status: membership ? (membership.status || 'active') : 'active'
            };
            this.editModalOpen = true;
        }
     }"
     @view-changed.window="viewMode = ($event.detail === 'grid' ? 'card' : $event.detail); localStorage.setItem('admin_customer_view_mode', viewMode)">

    {{-- ============================================================
         PAGE HEADER — 34px - 38px Compact Layout
         ============================================================ --}}
    <header class="w-full flex items-center justify-between gap-1.5 bg-white dark:bg-slate-900 rounded-lg px-2.5 py-1.5 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
        <div class="flex items-center gap-2 min-w-0">
            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 grid place-items-center text-xs sm:text-sm font-black shrink-0 shadow-xs">
                👥
            </div>
            <div class="min-w-0">
                <div class="flex items-center gap-1.5">
                    <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white tracking-tight truncate">
                        {{ __('messages.customer_admin_title') }}
                    </h1>
                    <span class="text-[10px] font-bold px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700 shrink-0">
                        {{ $store->name }}
                    </span>
                </div>
                <p class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 truncate">
                    {{ __('messages.customer_admin_subtitle') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-1 sm:gap-1.5 shrink-0">
            <a href="{{ route('store.admin.receivables.index', $storeRouteParams) }}"
               class="h-7 px-2 sm:px-2.5 rounded-md text-xs font-bold bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/60 border border-amber-200 dark:border-amber-800 transition flex items-center gap-1 active:scale-95 shadow-2xs">
                <span class="text-xs">💰</span>
                <span class="hidden xs:inline">{{ __('messages.customer_ledger_btn') }}</span>
            </a>

            <button type="button" @click.stop="openCreateModal()"
                    class="h-7 px-2.5 sm:px-3 rounded-md text-xs font-black bg-emerald-600 hover:bg-emerald-500 text-white shadow-2xs transition flex items-center gap-1 active:scale-95 cursor-pointer">
                <span class="text-xs font-black leading-none">+</span>
                <span>{{ __('messages.customer_add_btn') }}</span>
            </button>
        </div>
    </header>

    {{-- Flash Notifications --}}
    @if (session('success'))
        <div class="w-full px-2.5 py-1.5 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-2 shadow-2xs">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="w-full px-2.5 py-1.5 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-lg text-xs text-rose-800 dark:text-rose-300 space-y-0.5 shadow-2xs">
            <div class="font-black flex items-center gap-1.5">
                <span>⚠️</span>
                <span>{{ __('messages.validation_error') ?? 'Validation Error' }}:</span>
            </div>
            @foreach ($errors->all() as $error)
                <p class="ml-4 text-[11px]">• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- ============================================================
         KPI STAT CARDS — Centered Row-based Alignment (Standard v4.1)
         ============================================================ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-0.5 sm:gap-1">
        {{-- Total Customers --}}
        <a href="{{ route('store.admin.customers.index', array_merge($storeRouteParams, ['tab' => 'all'])) }}"
           class="bg-white dark:bg-slate-900 rounded-lg p-2 sm:p-2.5 border transition cursor-pointer hover:border-slate-300 dark:hover:border-slate-700 shadow-2xs {{ $tab === 'all' ? 'border-emerald-500/80 bg-emerald-50/20 dark:bg-emerald-950/20 ring-1 ring-emerald-500/40' : 'border-slate-200/80 dark:border-slate-800' }}">
            <div class="flex items-center justify-center gap-2.5 sm:gap-3">
                <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 grid place-items-center text-sm shrink-0 shadow-2xs">
                    👥
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 truncate">{{ __('messages.customer_total') }}</p>
                    <div class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 font-mono tracking-tight">{{ number_format($stats['total']) }}</div>
                    <p class="text-[9px] text-slate-400 dark:text-slate-500 truncate">{{ __('messages.customer_all_profiles_sub') }}</p>
                </div>
            </div>
        </a>

        {{-- Retail Customers --}}
        <a href="{{ route('store.admin.customers.index', array_merge($storeRouteParams, ['tab' => 'retail'])) }}"
           class="bg-white dark:bg-slate-900 rounded-lg p-2 sm:p-2.5 border transition cursor-pointer hover:border-slate-300 dark:hover:border-slate-700 shadow-2xs {{ $tab === 'retail' ? 'border-emerald-500/80 bg-emerald-50/20 dark:bg-emerald-950/20 ring-1 ring-emerald-500/40' : 'border-slate-200/80 dark:border-slate-800' }}">
            <div class="flex items-center justify-center gap-2.5 sm:gap-3">
                <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 grid place-items-center text-sm shrink-0 shadow-2xs">
                    🛍️
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 truncate">{{ __('messages.customer_retail') }}</p>
                    <div class="text-sm sm:text-base font-black text-emerald-600 dark:text-emerald-400 font-mono tracking-tight">{{ number_format($stats['retail']) }}</div>
                    <p class="text-[9px] text-emerald-600/80 dark:text-emerald-400/80 truncate">{{ __('messages.customer_walkin_retail_sub') }}</p>
                </div>
            </div>
        </a>

        {{-- Wholesale Customers --}}
        <a href="{{ route('store.admin.customers.index', array_merge($storeRouteParams, ['tab' => 'wholesale'])) }}"
           class="bg-white dark:bg-slate-900 rounded-lg p-2 sm:p-2.5 border transition cursor-pointer hover:border-slate-300 dark:hover:border-slate-700 shadow-2xs {{ $tab === 'wholesale' ? 'border-indigo-500/80 bg-indigo-50/20 dark:bg-indigo-950/20 ring-1 ring-indigo-500/40' : 'border-slate-200/80 dark:border-slate-800' }}">
            <div class="flex items-center justify-center gap-2.5 sm:gap-3">
                <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 dark:bg-indigo-950/70 dark:text-indigo-300 grid place-items-center text-sm shrink-0 shadow-2xs">
                    📦
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 truncate">{{ __('messages.customer_wholesale') }}</p>
                    <div class="text-sm sm:text-base font-black text-indigo-600 dark:text-indigo-400 font-mono tracking-tight">{{ number_format($stats['wholesale']) }}</div>
                    <p class="text-[9px] text-indigo-600/80 dark:text-indigo-400/80 truncate">{{ __('messages.customer_dealers_wholesale_sub') }}</p>
                </div>
            </div>
        </a>

        {{-- Outstanding Debt --}}
        <a href="{{ route('store.admin.customers.index', array_merge($storeRouteParams, ['tab' => 'debt'])) }}"
           class="bg-white dark:bg-slate-900 rounded-lg p-2 sm:p-2.5 border transition cursor-pointer hover:border-slate-300 dark:hover:border-slate-700 shadow-2xs {{ $tab === 'debt' ? 'border-amber-500/80 bg-amber-50/20 dark:bg-amber-950/20 ring-1 ring-amber-500/40' : 'border-slate-200/80 dark:border-slate-800' }}">
            <div class="flex items-center justify-center gap-2.5 sm:gap-3">
                <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300 grid place-items-center text-sm shrink-0 shadow-2xs">
                    💰
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 truncate">{{ __('messages.customer_debt') }}</p>
                    <div class="text-sm sm:text-base font-black text-amber-600 dark:text-amber-400 font-mono tracking-tight">
                        {{ format_currency($stats['total_debt_amount'], $store) }}
                    </div>
                    <p class="text-[9px] text-amber-600/80 dark:text-amber-400/80 truncate">
                        {{ __('messages.customer_debt_count_sub', ['count' => $stats['debt_customers_count']]) }}
                    </p>
                </div>
            </div>
        </a>
    </div>

    {{-- ============================================================
         UNIFIED TOOLBAR — Search, Filters, Sort & View Mode Switcher
         ============================================================ --}}
    <x-admin.toolbar
        :search="request('search', '')"
        :searchPlaceholder="__('messages.customer_search_placeholder')"
        :sort="request('sort', $sort)"
        :sortOptions="[
            'name_asc'   => __('messages.customer_sort_name_asc'),
            'name_desc'  => __('messages.customer_sort_name_desc'),
            'newest'     => __('messages.customer_sort_newest'),
            'oldest'     => __('messages.customer_sort_oldest'),
            'phone'      => __('messages.customer_sort_phone'),
        ]"
        :filters="[
            'tab' => [
                'label' => __('messages.customer_group_label'),
                'options' => [
                    'all'       => __('messages.customer_group_all'),
                    'retail'    => __('messages.customer_group_retail'),
                    'wholesale' => __('messages.customer_group_wholesale'),
                    'debt'      => __('messages.customer_group_debt'),
                ],
            ],
            'status' => [
                'label' => __('messages.customer_status_filter'),
                'options' => [
                    'active'    => __('messages.customer_status_active'),
                    'suspended' => __('messages.customer_status_suspended'),
                ],
            ],
        ]"
        :viewMode="'card'"
        :showViewToggle="true"
        :showExportImport="true"
        :exportUrl="$exportUrl"
        :totalCount="$customers->total()"
        :perPage="$customers->perPage()"
        :paginator="$customers"
        :showPagination="true"
    />

    {{-- ============================================================
         CARD GRID VIEW (viewMode === 'card' || viewMode === 'grid')
         ============================================================ --}}
    <div id="customers-grid" x-show="viewMode === 'card' || viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-1 sm:gap-1.5">
        @forelse ($customers as $customer)
            @php
                $membership = $customer->stores->first()?->pivot;
                $isWholesale = ($membership?->role ?? 'retail_customer') === 'wholesale_customer';
                $debt = (float) ($customer->debt_balance ?? 0);
                $initial = mb_substr($customer->name ?: 'C', 0, 1);
            @endphp
            <div class="rounded-lg bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 shadow-2xs hover:shadow-xs transition flex flex-col justify-between space-y-2 group">
                <div class="space-y-2">
                    <div class="flex items-start justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-tr {{ $isWholesale ? 'from-indigo-600 to-violet-500' : 'from-emerald-600 to-teal-500' }} text-white font-black text-xs grid place-items-center shadow-xs shrink-0">
                                {{ $initial }}
                            </div>
                            <div class="min-w-0">
                                <a href="{{ route('store.admin.customers.show', array_merge($storeRouteParams, ['customer' => $customer->id])) }}"
                                   class="font-black text-xs sm:text-sm text-slate-900 dark:text-slate-100 group-hover:text-emerald-600 transition truncate block">
                                    {{ $customer->name }}
                                </a>
                                <span class="text-[10px] font-mono text-slate-400 block truncate">📞 {{ $customer->phone ?: __('messages.customer_no_phone') }}</span>
                            </div>
                        </div>
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase whitespace-nowrap
                            {{ $isWholesale ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' }}">
                            {{ $isWholesale ? __('messages.customer_type_wholesale') : __('messages.customer_type_retail') }}
                        </span>
                    </div>

                    {{-- Metric Boxes --}}
                    <div class="grid grid-cols-2 gap-1 text-xs">
                        <div class="p-1.5 rounded-md bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                            <span class="text-slate-400 block text-[9px] font-bold uppercase">{{ __('messages.customer_sales_history') }}</span>
                            <span class="font-mono font-bold text-slate-800 dark:text-slate-200 text-xs block truncate">
                                {{ $customer->orders_count ?? 0 }} {{ __('messages.customer_times') }} · {{ format_currency($customer->total_spent ?? 0, $store) }}
                            </span>
                        </div>
                        <div class="p-1.5 rounded-md bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                            <span class="text-slate-400 block text-[9px] font-bold uppercase">{{ __('messages.customer_debt') }}</span>
                            @if ($debt > 0)
                                <span class="font-mono font-black text-amber-600 dark:text-amber-400 text-xs block truncate">
                                    {{ format_currency($debt, $store) }}
                                </span>
                            @else
                                <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400 text-xs block">
                                    {{ __('messages.customer_debt_cleared') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Card Actions --}}
                <div class="pt-1.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-1.5">
                    <a href="{{ route('store.admin.customers.show', array_merge($storeRouteParams, ['customer' => $customer->id])) }}"
                       class="h-6 px-2 rounded text-[11px] font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition flex items-center gap-1 cursor-pointer">
                        <span>🔍</span>
                        <span>{{ __('messages.customer_detail') }}</span>
                    </a>

                    <div class="flex items-center gap-1">
                        @if ($debt > 0)
                            <a href="{{ route('store.admin.receivables.show', array_merge($storeRouteParams, ['customer' => $customer->id])) }}"
                               class="h-6 px-2 rounded text-[11px] font-bold bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/60 dark:text-amber-300 transition flex items-center cursor-pointer">
                                {{ __('messages.customer_collect_debt') }}
                            </a>
                        @endif
                        <button type="button" @click.stop="openEditModal({{ json_encode($customer) }})"
                                class="h-6 px-2 rounded text-[11px] font-bold text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/50 transition cursor-pointer">
                            {{ __('messages.edit') }}
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-8 text-center text-slate-400 dark:text-slate-500 text-xs font-bold">
                {{ __('messages.customer_empty') }}
            </div>
        @endforelse
    </div>

    {{-- ============================================================
         SPREADSHEET TABLE VIEW (viewMode === 'table')
         ============================================================ --}}
    <div id="customers-table" x-show="viewMode === 'table'" class="rounded-lg bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs overflow-hidden transition">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300 min-w-[650px]">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800">
                    <tr>
                        <th class="py-2 px-2.5">{{ __('messages.customer_name') }}</th>
                        <th class="py-2 px-2.5">{{ __('messages.customer_contact') }}</th>
                        <th class="py-2 px-2.5 text-center">{{ __('messages.customer_type') }}</th>
                        <th class="py-2 px-2.5 text-right">{{ __('messages.customer_sales_history') }}</th>
                        <th class="py-2 px-2.5 text-right">{{ __('messages.customer_debt') }}</th>
                        <th class="py-2 px-2.5 text-right">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($customers as $customer)
                        @php
                            $membership = $customer->stores->first()?->pivot;
                            $isWholesale = ($membership?->role ?? 'retail_customer') === 'wholesale_customer';
                            $debt = (float) ($customer->debt_balance ?? 0);
                            $initial = mb_substr($customer->name ?: 'C', 0, 1);
                        @endphp
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                            <td class="py-1.5 px-2.5">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-md bg-gradient-to-tr {{ $isWholesale ? 'from-indigo-600 to-violet-500' : 'from-emerald-600 to-teal-500' }} text-white font-black text-[10px] grid place-items-center shadow-xs shrink-0">
                                        {{ $initial }}
                                    </div>
                                    <div class="min-w-0">
                                        <a href="{{ route('store.admin.customers.show', array_merge($storeRouteParams, ['customer' => $customer->id])) }}"
                                           class="font-bold text-slate-900 dark:text-slate-100 hover:text-emerald-600 transition block truncate">
                                            {{ $customer->name }}
                                        </a>
                                        <span class="text-[9px] text-slate-400 block font-mono">{{ __('messages.customer_joined') }} {{ $customer->created_at ? $customer->created_at->format('d/m/Y') : '-' }}</span>
                                    </div>
                                </div>
                            </td>

                            <td class="py-1.5 px-2.5 font-mono">
                                <div class="font-bold text-slate-800 dark:text-slate-200 text-xs">📞 {{ $customer->phone ?: '-' }}</div>
                                @if ($customer->email)
                                    <div class="text-[10px] text-slate-400 truncate max-w-[160px]">✉️ {{ $customer->email }}</div>
                                @endif
                            </td>

                            <td class="py-1.5 px-2.5 text-center">
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase whitespace-nowrap
                                    {{ $isWholesale ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' }}">
                                    {{ $isWholesale ? __('messages.customer_type_wholesale') : __('messages.customer_type_retail') }}
                                </span>
                            </td>

                            <td class="py-1.5 px-2.5 text-right font-mono">
                                <span class="font-bold text-slate-900 dark:text-slate-100 block text-xs">
                                    {{ format_currency($customer->total_spent ?? 0, $store) }}
                                </span>
                                <span class="text-[10px] text-slate-400 block">
                                    {{ $customer->orders_count ?? 0 }} {{ __('messages.customer_orders_suffix') }}
                                </span>
                            </td>

                            <td class="py-1.5 px-2.5 text-right font-mono">
                                @if ($debt > 0)
                                    <span class="font-black text-amber-600 dark:text-amber-400 text-xs block">
                                        {{ format_currency($debt, $store) }}
                                    </span>
                                    <span class="text-[9px] text-amber-600/90 font-bold block">{{ __('messages.customer_debt_owing') }}</span>
                                @else
                                    <span class="text-emerald-600 dark:text-emerald-400 font-bold text-xs">
                                        {{ __('messages.customer_debt_cleared') }}
                                    </span>
                                @endif
                            </td>

                            <td class="py-1.5 px-2.5 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <a href="{{ route('store.admin.customers.show', array_merge($storeRouteParams, ['customer' => $customer->id])) }}"
                                       class="h-6 px-2 rounded text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-200 transition inline-flex items-center cursor-pointer">
                                        {{ __('messages.customer_detail') }}
                                    </a>

                                    @if ($debt > 0)
                                        <a href="{{ route('store.admin.receivables.show', array_merge($storeRouteParams, ['customer' => $customer->id])) }}"
                                           class="h-6 px-2 rounded text-xs font-bold bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/60 dark:text-amber-300 transition inline-flex items-center cursor-pointer">
                                            {{ __('messages.customer_collect_debt') }}
                                        </a>
                                    @endif

                                    <button type="button" @click.stop="openEditModal({{ json_encode($customer) }})"
                                            class="h-6 px-2 rounded text-xs font-bold text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/50 transition cursor-pointer">
                                        {{ __('messages.edit') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-6 text-center text-slate-400 dark:text-slate-500 text-xs font-bold">
                                {{ __('messages.customer_empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if ($customers->hasPages())
        <div class="mt-1">
            {{ $customers->links() }}
        </div>
    @endif

    {{-- ============================================================
         MODAL 1: CREATE CUSTOMER MODAL
         ============================================================ --}}
    <div x-show="createModalOpen" x-cloak
         @click.self="createModalOpen = false"
         @keydown.escape.window="createModalOpen = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-3 bg-slate-900/60 backdrop-blur-xs overflow-y-auto">
        <div class="w-full max-w-md bg-white dark:bg-slate-900 rounded-xl p-3.5 sm:p-4 shadow-xl space-y-2.5 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-slate-100 animate-in fade-in zoom-in-95 duration-150">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5">
                    <span>✨</span>
                    <span>{{ __('messages.customer_form_create_title') }}</span>
                </h3>
                <button type="button" @click="createModalOpen = false" class="text-slate-400 hover:text-slate-600 text-xs font-bold p-1 cursor-pointer">✕</button>
            </div>

            <form action="{{ route('store.admin.customers.store', $storeRouteParams) }}" method="POST" class="space-y-2.5">
                @csrf

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-0.5">{{ __('messages.customer_name_label') }} *</label>
                    <input type="text" name="name" x-model="newCustomer.name" required autofocus
                           placeholder="e.g. U Ba / Daw Hla"
                           class="w-full h-8 px-2.5 rounded-md text-xs font-bold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:ring-1 focus:ring-emerald-500 focus:bg-white dark:focus:bg-slate-900 transition">
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-0.5">{{ __('messages.customer_phone_label') }} *</label>
                    <input type="text" name="phone" x-model="newCustomer.phone" required
                           placeholder="09xxxxxxxxx"
                           class="w-full h-8 px-2.5 rounded-md text-xs font-mono font-bold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:ring-1 focus:ring-emerald-500 focus:bg-white dark:focus:bg-slate-900 transition">
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-0.5">{{ __('messages.customer_email_label') }} ({{ __('messages.optional') ?? 'Optional' }})</label>
                    <input type="email" name="email" x-model="newCustomer.email"
                           placeholder="customer@example.com"
                           class="w-full h-8 px-2.5 rounded-md text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:ring-1 focus:ring-emerald-500 focus:bg-white dark:focus:bg-slate-900 transition">
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-0.5">{{ __('messages.customer_type_label') }} *</label>
                    <select name="role" x-model="newCustomer.role" required
                            class="w-full h-8 px-2.5 rounded-md text-xs font-bold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:ring-1 focus:ring-emerald-500 focus:bg-white dark:focus:bg-slate-900 transition cursor-pointer">
                        <option value="retail_customer">{{ __('messages.customer_retail') }}</option>
                        <option value="wholesale_customer">{{ __('messages.customer_wholesale') }}</option>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-1.5 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="createModalOpen = false" class="h-7 px-2.5 rounded-md text-xs font-bold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 cursor-pointer">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit" class="h-7 px-3.5 rounded-md text-xs font-black bg-emerald-600 hover:bg-emerald-500 text-white shadow-2xs transition active:scale-95 cursor-pointer">
                        {{ __('messages.customer_save_btn') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         MODAL 2: EDIT CUSTOMER MODAL
         ============================================================ --}}
    <div x-show="editModalOpen" x-cloak
         @click.self="editModalOpen = false"
         @keydown.escape.window="editModalOpen = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-3 bg-slate-900/60 backdrop-blur-xs overflow-y-auto">
        <div class="w-full max-w-md bg-white dark:bg-slate-900 rounded-xl p-3.5 sm:p-4 shadow-xl space-y-2.5 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-slate-100 animate-in fade-in zoom-in-95 duration-150">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5">
                    <span>✏️</span>
                    <span>{{ __('messages.customer_form_edit_title') }}</span>
                </h3>
                <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600 text-xs font-bold p-1 cursor-pointer">✕</button>
            </div>

            <form :action="'{{ url('/store/' . $store->slug . '/admin/customers') }}/' + editingCustomer.id"
                  method="POST" class="space-y-2.5">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-0.5">{{ __('messages.customer_name_label') }} *</label>
                    <input type="text" name="name" x-model="editingCustomer.name" required
                           class="w-full h-8 px-2.5 rounded-md text-xs font-bold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:ring-1 focus:ring-emerald-500 focus:bg-white dark:focus:bg-slate-900 transition">
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-0.5">{{ __('messages.customer_phone_label') }} *</label>
                    <input type="text" name="phone" x-model="editingCustomer.phone" required
                           class="w-full h-8 px-2.5 rounded-md text-xs font-mono font-bold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:ring-1 focus:ring-emerald-500 focus:bg-white dark:focus:bg-slate-900 transition">
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-0.5">{{ __('messages.customer_email_label') }} ({{ __('messages.optional') ?? 'Optional' }})</label>
                    <input type="email" name="email" x-model="editingCustomer.email"
                           class="w-full h-8 px-2.5 rounded-md text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:ring-1 focus:ring-emerald-500 focus:bg-white dark:focus:bg-slate-900 transition">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-0.5">{{ __('messages.customer_type_label') }} *</label>
                        <select name="role" x-model="editingCustomer.role" required
                                class="w-full h-8 px-2.5 rounded-md text-xs font-bold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:ring-1 focus:ring-emerald-500 focus:bg-white dark:focus:bg-slate-900 transition cursor-pointer">
                            <option value="retail_customer">{{ __('messages.customer_retail') }}</option>
                            <option value="wholesale_customer">{{ __('messages.customer_wholesale') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-0.5">{{ __('messages.customer_status_label') }} *</label>
                        <select name="status" x-model="editingCustomer.status" required
                                class="w-full h-8 px-2.5 rounded-md text-xs font-semibold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:ring-1 focus:ring-emerald-500 focus:bg-white dark:focus:bg-slate-900 transition cursor-pointer">
                            <option value="active">{{ __('messages.customer_status_active') }}</option>
                            <option value="pending">{{ __('messages.customer_status_pending') }}</option>
                            <option value="suspended">{{ __('messages.customer_status_suspended') }}</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-1.5 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="editModalOpen = false" class="h-7 px-2.5 rounded-md text-xs font-bold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 cursor-pointer">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit" class="h-7 px-3.5 rounded-md text-xs font-black bg-emerald-600 hover:bg-emerald-500 text-white shadow-2xs transition active:scale-95 cursor-pointer">
                        {{ __('messages.customer_update_btn') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
