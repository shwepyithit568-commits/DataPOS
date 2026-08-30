@extends('layouts.admin.app')

@php
    $storeRouteParams = ['store_slug' => $store->slug];
    $customersArray = ($customers instanceof \Illuminate\Pagination\LengthAwarePaginator ? $customers->items() : $customers->all());
    $exportUrl = route('store.admin.customers.export', array_merge($storeRouteParams, request()->only(['search', 'sort', 'tab', 'role', 'status'])));
@endphp

@section('title', __('messages.sidebar_customer_directory') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@section('content')
<div class="w-full space-y-2 sm:space-y-2.5"
     x-data="{
        viewMode: localStorage.getItem('admin_customer_view_mode') || 'table',
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
     @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_customer_view_mode', $event.detail)">

    {{-- ============================================================
         PAGE HEADER — Eyebrow, Title, Subtitle & Action CTAs
         ============================================================ --}}
    <header class="w-full flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3.5 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
        <div class="min-w-0">
            <h1 class="text-sm sm:text-base font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>👥 {{ __('messages.customer_admin_title') }}</span>
            </h1>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('store.admin.receivables.index', $storeRouteParams) }}"
               class="px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-lg text-xs font-bold bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/60 border border-amber-200 dark:border-amber-800 transition flex items-center gap-1.5 active:scale-95 shadow-2xs">
                <span>💰</span>
                <span>{{ __('messages.customer_ledger_btn') }}</span>
            </a>

            <button type="button" @click.stop="openCreateModal()"
                    class="px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-lg text-xs font-black bg-emerald-600 hover:bg-emerald-700 text-white shadow-2xs transition flex items-center gap-1.5 active:scale-95 cursor-pointer">
                <span class="text-sm leading-none">+</span>
                <span>{{ __('messages.customer_add_btn') }}</span>
            </button>
        </div>
    </header>

    {{-- Flash Notifications --}}
    @if (session('success'))
        <div class="w-full p-2.5 sm:p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-2 shadow-2xs">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="w-full p-2.5 sm:p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-lg text-xs text-rose-800 dark:text-rose-300 space-y-1 shadow-2xs">
            <div class="font-black flex items-center gap-1.5">
                <span>⚠️</span>
                <span>{{ __('messages.validation_error') ?? 'Validation Error' }}:</span>
            </div>
            @foreach ($errors->all() as $error)
                <p class="ml-4">• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- ============================================================
         KPI STAT CARDS — 4 Compact Interactive Filter Cards
         ============================================================ --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5 sm:gap-2">
        {{-- Total Customers --}}
        <a href="{{ route('store.admin.customers.index', array_merge($storeRouteParams, ['tab' => 'all'])) }}"
           class="bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border {{ $tab === 'all' ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'border-slate-200/80 dark:border-slate-800' }} shadow-2xs hover:shadow-xs transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 truncate">{{ __('messages.customer_total') }}</span>
                <span class="text-xs">👥</span>
            </div>
            <div class="text-lg sm:text-2xl font-black text-slate-900 dark:text-slate-100 mt-1 font-mono tracking-tight">{{ number_format($stats['total']) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">All customer profiles</div>
        </a>

        {{-- Retail Customers --}}
        <a href="{{ route('store.admin.customers.index', array_merge($storeRouteParams, ['tab' => 'retail'])) }}"
           class="bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border {{ $tab === 'retail' ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'border-slate-200/80 dark:border-slate-800' }} shadow-2xs hover:shadow-xs transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 truncate">{{ __('messages.customer_retail') }}</span>
                <span class="text-xs">🛍️</span>
            </div>
            <div class="text-lg sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1 font-mono tracking-tight">{{ number_format($stats['retail']) }}</div>
            <div class="text-[10px] text-emerald-600/80 dark:text-emerald-400/80 mt-0.5">Walk-in & Retail</div>
        </a>

        {{-- Wholesale Customers --}}
        <a href="{{ route('store.admin.customers.index', array_merge($storeRouteParams, ['tab' => 'wholesale'])) }}"
           class="bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border {{ $tab === 'wholesale' ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'border-slate-200/80 dark:border-slate-800' }} shadow-2xs hover:shadow-xs transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 truncate">{{ __('messages.customer_wholesale') }}</span>
                <span class="text-xs">📦</span>
            </div>
            <div class="text-lg sm:text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1 font-mono tracking-tight">{{ number_format($stats['wholesale']) }}</div>
            <div class="text-[10px] text-indigo-600/80 dark:text-indigo-400/80 mt-0.5">Dealers & Wholesalers</div>
        </a>

        {{-- Outstanding Debt --}}
        <a href="{{ route('store.admin.customers.index', array_merge($storeRouteParams, ['tab' => 'debt'])) }}"
           class="bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border {{ $tab === 'debt' ? 'border-amber-500 ring-2 ring-amber-500/20' : 'border-slate-200/80 dark:border-slate-800' }} shadow-2xs hover:shadow-xs transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 truncate">{{ __('messages.customer_debt') }}</span>
                <span class="text-xs">💰</span>
            </div>
            <div class="text-lg sm:text-2xl font-black text-amber-600 dark:text-amber-400 mt-1 font-mono tracking-tight">
                {{ number_format($stats['total_debt_amount'], 0) }} <span class="text-[11px] font-normal">MMK</span>
            </div>
            <div class="text-[10px] text-amber-600/80 dark:text-amber-400/80 mt-0.5">{{ $stats['debt_customers_count'] }} Customers owe</div>
        </a>
    </div>

    {{-- ============================================================
         UNIFIED TOOLBAR — Search, Filters, Sort & View Mode Switcher
         ============================================================ --}}
    <x-admin.toolbar
        :search="request('search', '')"
        searchPlaceholder="ဖောက်သည်အမည်၊ ဖုန်းနံပါတ်၊ အီးမေးလ်ဖြင့် ရှာဖွေပါ..."
        :sort="request('sort', $sort)"
        :sortOptions="[
            'name_asc'   => 'အမည်: A to Z (Name Asc)',
            'name_desc'  => 'အမည်: Z to A (Name Desc)',
            'newest'     => 'အသစ်ဆုံး (Newest First)',
            'oldest'     => 'အဟောင်းဆုံး (Oldest First)',
            'phone'      => 'ဖုန်းနံပါတ် (Phone Number)',
        ]"
        :filters="[
            'tab' => [
                'label' => 'Customer Group',
                'options' => [
                    'all'       => 'အားလုံး (All Customers)',
                    'retail'    => 'လက်လီ (Retail Customers)',
                    'wholesale' => 'လက်ကား (Wholesale Customers)',
                    'debt'      => 'အကြွေးကျန်သူများ (With Debt)',
                ],
            ],
            'status' => [
                'label' => 'Status',
                'options' => [
                    'active'    => 'Active (ပုံမှန်)',
                    'suspended' => 'Suspended (ပိတ်ထား)',
                ],
            ],
        ]"
        :viewMode="'table'"
        :showViewToggle="true"
        :showExportImport="true"
        :exportUrl="$exportUrl"
        :totalCount="$customers->total()"
        :perPage="$customers->perPage()"
        :paginator="$customers"
        :showPagination="true"
    />

    {{-- ============================================================
         CARD GRID VIEW (viewMode === 'card')
         ============================================================ --}}
    <div x-show="viewMode === 'card'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 sm:gap-2.5">
        @forelse ($customers as $customer)
            @php
                $membership = $customer->stores->first()?->pivot;
                $isWholesale = ($membership?->role ?? 'retail_customer') === 'wholesale_customer';
                $debt = (float) ($customer->debt_balance ?? 0);
                $initial = mb_substr($customer->name ?: 'C', 0, 1);
            @endphp
            <div class="rounded-lg bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-3 sm:p-3.5 shadow-2xs hover:shadow-xs transition flex flex-col justify-between space-y-3 group">
                <div class="space-y-2.5">
                    <div class="flex items-start justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-2.5">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-9 h-9 rounded-lg bg-gradient-to-tr {{ $isWholesale ? 'from-indigo-600 to-violet-500' : 'from-emerald-600 to-teal-500' }} text-white font-black text-xs grid place-items-center shadow-2xs shrink-0">
                                {{ $initial }}
                            </div>
                            <div class="min-w-0">
                                <a href="{{ route('store.admin.customers.show', array_merge($storeRouteParams, ['customer' => $customer->id])) }}"
                                   class="font-black text-xs sm:text-sm text-slate-900 dark:text-slate-100 group-hover:text-emerald-600 transition truncate block">
                                    {{ $customer->name }}
                                </a>
                                <span class="text-[11px] font-mono text-slate-400 block truncate">📞 {{ $customer->phone ?: 'No phone' }}</span>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase whitespace-nowrap
                            {{ $isWholesale ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' }}">
                            {{ $isWholesale ? 'Wholesale' : 'Retail' }}
                        </span>
                    </div>

                    {{-- Metric Boxes --}}
                    <div class="grid grid-cols-2 gap-1.5 text-xs">
                        <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                            <span class="text-slate-400 block text-[9px] font-bold uppercase">ဝယ်ယူမှု စုစုပေါင်း</span>
                            <span class="font-mono font-black text-slate-800 dark:text-slate-200 text-xs">
                                {{ $customer->orders_count ?? 0 }} ခေါက် · {{ number_format($customer->total_spent ?? 0, 0) }} MMK
                            </span>
                        </div>
                        <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                            <span class="text-slate-400 block text-[9px] font-bold uppercase">အကြွေးလက်ကျန်</span>
                            @if ($debt > 0)
                                <span class="font-mono font-black text-amber-600 dark:text-amber-400 text-xs block">
                                    {{ number_format($debt, 0) }} MMK
                                </span>
                            @else
                                <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400 text-xs block">ရှင်းပြီး (Clear)</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Card Actions --}}
                <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                    <a href="{{ route('store.admin.customers.show', array_merge($storeRouteParams, ['customer' => $customer->id])) }}"
                       class="px-2.5 py-1 rounded-md text-xs font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition flex items-center gap-1">
                        <span>🔍</span>
                        <span>အသေးစိတ်</span>
                    </a>

                    <div class="flex items-center gap-1">
                        @if ($debt > 0)
                            <a href="{{ route('store.admin.receivables.show', array_merge($storeRouteParams, ['customer' => $customer->id])) }}"
                               class="px-2 py-1 rounded-md text-xs font-bold bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/60 dark:text-amber-300 transition">
                                ငွေကောက်
                            </a>
                        @endif
                        <button type="button" @click.stop="openEditModal({{ json_encode($customer) }})"
                                class="px-2 py-1 rounded-md text-xs font-bold text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/50 transition cursor-pointer">
                            Edit
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center text-slate-400 dark:text-slate-500 text-xs font-bold">
                ဖောက်သည်စာရင်း မရှိသေးပါ။ (No customers found.)
            </div>
        @endforelse
    </div>

    {{-- ============================================================
         SPREADSHEET TABLE VIEW (viewMode === 'table')
         ============================================================ --}}
    <div x-show="viewMode === 'table'" class="rounded-lg bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs overflow-hidden transition">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300 min-w-[700px]">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800">
                    <tr>
                        <th class="p-2.5">ဖောက်သည် အမည်</th>
                        <th class="p-2.5">အဆက်အသွယ်</th>
                        <th class="p-2.5 text-center">အမျိုးအစား</th>
                        <th class="p-2.5 text-right">ဝယ်ယူမှု မှတ်တမ်း</th>
                        <th class="p-2.5 text-right">အကြွေးလက်ကျန်</th>
                        <th class="p-2.5 text-right">လုပ်ဆောင်ချက်</th>
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
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition">
                            <td class="p-2.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-md bg-gradient-to-tr {{ $isWholesale ? 'from-indigo-600 to-violet-500' : 'from-emerald-600 to-teal-500' }} text-white font-black text-[11px] grid place-items-center shadow-2xs shrink-0">
                                        {{ $initial }}
                                    </div>
                                    <div class="min-w-0">
                                        <a href="{{ route('store.admin.customers.show', array_merge($storeRouteParams, ['customer' => $customer->id])) }}"
                                           class="font-bold text-slate-900 dark:text-slate-100 hover:text-emerald-600 transition block truncate">
                                            {{ $customer->name }}
                                        </a>
                                        <span class="text-[10px] text-slate-400 block font-mono">Joined {{ $customer->created_at ? $customer->created_at->format('M d, Y') : '-' }}</span>
                                    </div>
                                </div>
                            </td>

                            <td class="p-2.5 font-mono">
                                <div class="font-bold text-slate-800 dark:text-slate-200">📞 {{ $customer->phone ?: '—' }}</div>
                                @if ($customer->email)
                                    <div class="text-[10px] text-slate-400">✉️ {{ $customer->email }}</div>
                                @endif
                            </td>

                            <td class="p-2.5 text-center">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase whitespace-nowrap
                                    {{ $isWholesale ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' }}">
                                    {{ $isWholesale ? 'Wholesale' : 'Retail' }}
                                </span>
                            </td>

                            <td class="p-2.5 text-right font-mono">
                                <span class="font-bold text-slate-900 dark:text-slate-100 block text-xs">
                                    {{ number_format($customer->total_spent ?? 0, 0) }} MMK
                                </span>
                                <span class="text-[10px] text-slate-400 block">
                                    {{ $customer->orders_count ?? 0 }} Orders
                                </span>
                            </td>

                            <td class="p-2.5 text-right font-mono">
                                @if ($debt > 0)
                                    <span class="font-bold text-amber-600 dark:text-amber-400 text-xs block">
                                        {{ number_format($debt, 0) }} MMK
                                    </span>
                                    <span class="text-[10px] text-amber-600/80 font-bold block">ကျန်ငွေရှိ</span>
                                @else
                                    <span class="text-emerald-600 dark:text-emerald-400 font-bold text-xs">ရှင်းပြီး</span>
                                @endif
                            </td>

                            <td class="p-2.5 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <a href="{{ route('store.admin.customers.show', array_merge($storeRouteParams, ['customer' => $customer->id])) }}"
                                       class="px-2 py-1 rounded-md text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-200 transition">
                                        အသေးစိတ်
                                    </a>

                                    @if ($debt > 0)
                                        <a href="{{ route('store.admin.receivables.show', array_merge($storeRouteParams, ['customer' => $customer->id])) }}"
                                           class="px-2 py-1 rounded-md text-xs font-bold bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/60 dark:text-amber-300 transition">
                                            ငွေကောက်
                                        </a>
                                    @endif

                                    <button type="button" @click.stop="openEditModal({{ json_encode($customer) }})"
                                            class="px-2 py-1 rounded-md text-xs font-bold text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/50 transition cursor-pointer">
                                        Edit
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400 dark:text-slate-500 text-xs font-bold">
                                ဖောက်သည်စာရင်း မရှိသေးပါ။ (No customers found.)
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if ($customers->hasPages())
        <div class="mt-2.5">
            {{ $customers->links() }}
        </div>
    @endif

    {{-- ============================================================
         MODAL 1: CREATE CUSTOMER MODAL
         ============================================================ --}}
    <div x-show="createModalOpen" x-cloak
         @click.self="createModalOpen = false"
         @keydown.escape.window="createModalOpen = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto">
        <div class="w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-2xl space-y-3.5 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-slate-100 animate-in fade-in zoom-in-95 duration-150">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5">
                    <span>✨</span>
                    <span>{{ __('messages.customer_form_create_title') }}</span>
                </h3>
                <button type="button" @click="createModalOpen = false" class="text-slate-400 hover:text-slate-600 text-sm font-bold">✕</button>
            </div>

            <form action="{{ route('store.admin.customers.store', $storeRouteParams) }}" method="POST" class="space-y-3">
                @csrf

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.customer_name_label') }} *</label>
                    <input type="text" name="name" x-model="newCustomer.name" required autofocus
                           placeholder="e.g. U Ba / Daw Hla"
                           class="w-full px-3 py-2 rounded-lg text-xs font-bold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.customer_phone_label') }} *</label>
                    <input type="text" name="phone" x-model="newCustomer.phone" required
                           placeholder="09xxxxxxxxx"
                           class="w-full px-3 py-2 rounded-lg text-xs font-mono font-bold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.customer_email_label') }} ({{ __('messages.optional') ?? 'Optional' }})</label>
                    <input type="email" name="email" x-model="newCustomer.email"
                           placeholder="customer@example.com"
                           class="w-full px-3 py-2 rounded-lg text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.customer_type_label') }} *</label>
                    <select name="role" x-model="newCustomer.role" required
                            class="w-full px-3 py-2 rounded-lg text-xs font-bold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="retail_customer">{{ __('messages.customer_retail') }}</option>
                        <option value="wholesale_customer">{{ __('messages.customer_wholesale') }}</option>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="createModalOpen = false" class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-lg text-xs font-black bg-emerald-600 hover:bg-emerald-700 text-white shadow-2xs transition active:scale-95">
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
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto">
        <div class="w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-2xl space-y-3.5 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-slate-100 animate-in fade-in zoom-in-95 duration-150">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5">
                    <span>✏️</span>
                    <span>{{ __('messages.customer_form_edit_title') }}</span>
                </h3>
                <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600 text-sm font-bold">✕</button>
            </div>

            <form :action="'{{ url('/store/' . $store->slug . '/admin/customers') }}/' + editingCustomer.id"
                  method="POST" class="space-y-3">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.customer_name_label') }} *</label>
                    <input type="text" name="name" x-model="editingCustomer.name" required
                           class="w-full px-3 py-2 rounded-lg text-xs font-bold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.customer_phone_label') }} *</label>
                    <input type="text" name="phone" x-model="editingCustomer.phone" required
                           class="w-full px-3 py-2 rounded-lg text-xs font-mono font-bold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.customer_email_label') }} ({{ __('messages.optional') ?? 'Optional' }})</label>
                    <input type="email" name="email" x-model="editingCustomer.email"
                           class="w-full px-3 py-2 rounded-lg text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.customer_type_label') }} *</label>
                        <select name="role" x-model="editingCustomer.role" required
                                class="w-full px-3 py-2 rounded-lg text-xs font-bold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="retail_customer">{{ __('messages.customer_retail') }}</option>
                            <option value="wholesale_customer">{{ __('messages.customer_wholesale') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.customer_status_label') }} *</label>
                        <select name="status" x-model="editingCustomer.status" required
                                class="w-full px-3 py-2 rounded-lg text-xs font-semibold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="active">{{ __('messages.customer_status_active') }}</option>
                            <option value="pending">{{ __('messages.customer_status_pending') }}</option>
                            <option value="suspended">{{ __('messages.customer_status_suspended') }}</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="editModalOpen = false" class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-lg text-xs font-black bg-emerald-600 hover:bg-emerald-700 text-white shadow-2xs transition active:scale-95">
                        {{ __('messages.customer_update_btn') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
