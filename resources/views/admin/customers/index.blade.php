@extends('layouts.admin.app')

@section('title', __('messages.sidebar_customer_directory', [], 'en') ? __('messages.sidebar_customer_directory') . ' - ' . $store->name : 'Customers - ' . $store->name)

@php
    $storeRouteParams = ['store_slug' => $store->slug];
    $customersArray = ($customers instanceof \Illuminate\Pagination\LengthAwarePaginator ? $customers->items() : $customers->all());
    $exportUrl = route('store.admin.customers.export', array_merge($storeRouteParams, request()->only(['search', 'sort', 'tab', 'role', 'status'])));
@endphp

@section('content')
<div class="w-full space-y-5 sm:space-y-6 pb-12"
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

    {{-- 1. Top Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <span class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 grid place-items-center text-xl sm:text-2xl font-bold shadow-sm flex-shrink-0">
                👥
            </span>
            <div class="min-w-0">
                <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-400">
                    <a href="{{ route('store.admin.dashboard', $storeRouteParams) }}" class="hover:text-slate-600 dark:hover:text-slate-300 transition">
                        {{ __('messages.admin_dashboard') }}
                    </a>
                    <span>/</span>
                    <span class="text-emerald-600 dark:text-emerald-400">{{ __('messages.sidebar_customer_directory') }}</span>
                </div>
                <h1 class="text-lg sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2 truncate">
                    <span>ဖောက်သည် စာရင်းချုပ် (Customer Directory)</span>
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $store->name }} · ဖောက်သည် အချက်အလက်၊ အရောင်းမှတ်တမ်းနှင့် အကြွေးလက်ကျန်များ</p>
            </div>
        </div>

        {{-- Top Right Actions --}}
        <div class="flex items-center gap-2.5 self-start sm:self-auto">
            <a href="{{ route('store.admin.receivables.index', $storeRouteParams) }}"
               class="px-3.5 py-2.5 rounded-2xl text-xs font-bold bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/60 dark:text-amber-300 transition flex items-center gap-2 border border-amber-200/60 dark:border-amber-800/60">
                <span>💰</span>
                <span>အကြွေးစာရင်း (Debt Ledger)</span>
            </a>
            <button type="button" @click.stop="openCreateModal()"
                    class="px-4 py-2.5 rounded-2xl text-xs sm:text-sm font-black bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg shadow-emerald-500/20 transition flex items-center gap-2 active:scale-95">
                <span class="text-base leading-none">+</span>
                <span>ဖောက်သည်အသစ် စာရင်းသွင်းမည်</span>
            </button>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 rounded-3xl text-xs font-bold text-emerald-700 dark:text-emerald-300 flex items-center gap-2.5 shadow-sm">
            <span class="w-6 h-6 rounded-full bg-emerald-100 dark:bg-emerald-900 grid place-items-center text-emerald-600 dark:text-emerald-300 font-black">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-4 bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 rounded-3xl text-xs font-bold text-rose-700 dark:text-rose-300 space-y-1 shadow-sm">
            <div class="font-black flex items-center gap-1.5">
                <span>⚠️</span>
                <span>အချက်အလက် ဖြည့်သွင်းမှု မှားယွင်းနေပါသည်:</span>
            </div>
            @foreach ($errors->all() as $error)
                <p class="ml-5">• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- 2. 4 Key Customer KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
        <a href="{{ route('store.admin.customers.index', array_merge($storeRouteParams, ['tab' => 'all'])) }}"
           class="rounded-3xl bg-white dark:bg-slate-900 border {{ $tab === 'all' ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'border-slate-200/90 dark:border-slate-800' }} p-4 sm:p-5 shadow-sm flex items-center justify-between transition hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-1 truncate">စုစုပေါင်း ဖောက်သည် (Total)</p>
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white font-mono tracking-tight">{{ number_format($stats['total']) }}</h3>
                <p class="text-[11px] text-slate-400 font-semibold mt-0.5">All customer profiles</p>
            </div>
            <span class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 grid place-items-center text-xl font-bold shadow-inner flex-shrink-0">
                👥
            </span>
        </a>

        <a href="{{ route('store.admin.customers.index', array_merge($storeRouteParams, ['tab' => 'retail'])) }}"
           class="rounded-3xl bg-white dark:bg-slate-900 border {{ $tab === 'retail' ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'border-slate-200/90 dark:border-slate-800' }} p-4 sm:p-5 shadow-sm flex items-center justify-between transition hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-1 truncate">လက်လီ ဖောက်သည် (Retail)</p>
                <h3 class="text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono tracking-tight">{{ number_format($stats['retail']) }}</h3>
                <p class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold mt-0.5">Walk-in & Retail</p>
            </div>
            <span class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 grid place-items-center text-xl font-bold shadow-inner flex-shrink-0">
                🛍️
            </span>
        </a>

        <a href="{{ route('store.admin.customers.index', array_merge($storeRouteParams, ['tab' => 'wholesale'])) }}"
           class="rounded-3xl bg-white dark:bg-slate-900 border {{ $tab === 'wholesale' ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'border-slate-200/90 dark:border-slate-800' }} p-4 sm:p-5 shadow-sm flex items-center justify-between transition hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-1 truncate">လက်ကား ဖောက်သည် (Wholesale)</p>
                <h3 class="text-xl sm:text-2xl font-black text-indigo-600 dark:text-indigo-400 font-mono tracking-tight">{{ number_format($stats['wholesale']) }}</h3>
                <p class="text-[11px] text-indigo-600 dark:text-indigo-400 font-semibold mt-0.5">Dealer & Wholesalers</p>
            </div>
            <span class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 grid place-items-center text-xl font-bold shadow-inner flex-shrink-0">
                📦
            </span>
        </a>

        <a href="{{ route('store.admin.customers.index', array_merge($storeRouteParams, ['tab' => 'debt'])) }}"
           class="rounded-3xl bg-white dark:bg-slate-900 border {{ $tab === 'debt' ? 'border-amber-500 ring-2 ring-amber-500/20' : 'border-slate-200/90 dark:border-slate-800' }} p-4 sm:p-5 shadow-sm flex items-center justify-between transition hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-1 truncate">အကြွေးလက်ကျန် (Outstanding Debt)</p>
                <h3 class="text-xl sm:text-2xl font-black text-amber-600 dark:text-amber-400 font-mono tracking-tight">{{ number_format($stats['total_debt_amount'], 0) }} <span class="text-xs font-normal">MMK</span></h3>
                <p class="text-[11px] text-amber-600 dark:text-amber-400 font-semibold mt-0.5">{{ $stats['debt_customers_count'] }} Customers owe money</p>
            </div>
            <span class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 grid place-items-center text-xl font-bold shadow-inner flex-shrink-0">
                💰
            </span>
        </a>
    </div>

    {{-- 3. Unified Admin Toolbar --}}
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

    {{-- 4. Customer Cards Grid View --}}
    <div x-show="viewMode === 'card'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($customers as $customer)
            @php
                $membership = $customer->stores->first()?->pivot;
                $isWholesale = ($membership?->role ?? 'retail_customer') === 'wholesale_customer';
                $debt = (float) ($customer->debt_balance ?? 0);
                $initial = mb_substr($customer->name ?: 'C', 0, 1);
            @endphp
            <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-5 shadow-sm hover:shadow-md transition flex flex-col justify-between space-y-4 group">
                <div class="space-y-3">
                    <div class="flex items-start justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr {{ $isWholesale ? 'from-indigo-600 to-violet-500' : 'from-emerald-600 to-teal-500' }} text-white font-black text-sm grid place-items-center shadow-sm flex-shrink-0">
                                {{ $initial }}
                            </div>
                            <div class="min-w-0">
                                <a href="{{ route('store.admin.customers.show', array_merge($storeRouteParams, ['customer' => $customer->id])) }}"
                                   class="font-black text-sm text-slate-900 dark:text-slate-100 group-hover:text-emerald-600 transition truncate block">
                                    {{ $customer->name }}
                                </a>
                                <span class="text-xs font-mono text-slate-400 block truncate">📞 {{ $customer->phone ?: 'No phone' }}</span>
                            </div>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase whitespace-nowrap
                            {{ $isWholesale ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' }}">
                            {{ $isWholesale ? 'Wholesale' : 'Retail' }}
                        </span>
                    </div>

                    {{-- Customer Info Metric Boxes --}}
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/50">
                            <span class="text-slate-400 block text-[10px] font-bold uppercase">ဝယ်ယူမှု စုစုပေါင်း</span>
                            <span class="font-mono font-black text-slate-800 dark:text-slate-200 text-xs">
                                {{ $customer->orders_count ?? 0 }} ခေါက် · {{ number_format($customer->total_spent ?? 0, 0) }} Ks
                            </span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/50">
                            <span class="text-slate-400 block text-[10px] font-bold uppercase">အကြွေးလက်ကျန်</span>
                            @if ($debt > 0)
                                <span class="font-mono font-black text-amber-600 dark:text-amber-400 text-xs block">
                                    {{ number_format($debt, 0) }} MMK
                                </span>
                            @else
                                <span class="font-mono font-bold text-emerald-600 text-xs block">ရှင်းပြီး (Clear)</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Card Bottom Actions --}}
                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                    <a href="{{ route('store.admin.customers.show', array_merge($storeRouteParams, ['customer' => $customer->id])) }}"
                       class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition flex items-center gap-1">
                        <span>🔍</span>
                        <span>အသေးစိတ်</span>
                    </a>

                    <div class="flex items-center gap-1.5">
                        @if ($debt > 0)
                            <a href="{{ route('store.admin.receivables.show', array_merge($storeRouteParams, ['customer' => $customer->id])) }}"
                               class="px-2.5 py-1.5 rounded-xl text-xs font-bold bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/60 dark:text-amber-300 transition">
                                ငွေကောက်
                            </a>
                        @endif
                        <button type="button" @click.stop="openEditModal({{ json_encode($customer) }})"
                                class="px-2.5 py-1.5 rounded-xl text-xs font-bold text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/50 transition">
                            Edit
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center text-slate-400 dark:text-slate-500 text-sm">
                ဖောက်သည်စာရင်း မရှိသေးပါ။ (No customers found.)
            </div>
        @endforelse
    </div>

    {{-- 5. Customer Table View --}}
    <div x-show="viewMode === 'table'" class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                        <th class="py-3.5 px-4">ဖောက်သည် အမည် (Customer)</th>
                        <th class="py-3.5 px-4">အဆက်အသွယ် (Contact)</th>
                        <th class="py-3.5 px-4 text-center">အမျိုးအစား (Type)</th>
                        <th class="py-3.5 px-4 text-right">ဝယ်ယူမှု မှတ်တမ်း (Sales Record)</th>
                        <th class="py-3.5 px-4 text-right">အကြွေးလက်ကျန် (Debt Balance)</th>
                        <th class="py-3.5 px-4 text-right">လုပ်ဆောင်ချက် (Actions)</th>
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
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-2xl bg-gradient-to-tr {{ $isWholesale ? 'from-indigo-600 to-violet-500' : 'from-emerald-600 to-teal-500' }} text-white font-black text-xs grid place-items-center shadow-sm flex-shrink-0">
                                        {{ $initial }}
                                    </div>
                                    <div class="min-w-0">
                                        <a href="{{ route('store.admin.customers.show', array_merge($storeRouteParams, ['customer' => $customer->id])) }}"
                                           class="font-black text-slate-900 dark:text-slate-100 text-sm hover:text-emerald-600 transition block truncate">
                                            {{ $customer->name }}
                                        </a>
                                        <span class="text-[11px] text-slate-400 block">Joined {{ $customer->created_at ? $customer->created_at->format('M d, Y') : '-' }}</span>
                                    </div>
                                </div>
                            </td>

                            <td class="py-3.5 px-4 font-mono text-slate-600 dark:text-slate-300">
                                <div>📞 {{ $customer->phone ?: '—' }}</div>
                                @if ($customer->email)
                                    <div class="text-[10px] text-slate-400">✉️ {{ $customer->email }}</div>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase whitespace-nowrap
                                    {{ $isWholesale ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' }}">
                                    {{ $isWholesale ? 'Wholesale' : 'Retail' }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4 text-right font-mono">
                                <span class="font-black text-slate-800 dark:text-slate-200 block text-xs">
                                    {{ number_format($customer->total_spent ?? 0, 0) }} Ks
                                </span>
                                <span class="text-[11px] text-slate-400 block">
                                    {{ $customer->orders_count ?? 0 }} Orders
                                </span>
                            </td>

                            <td class="py-3.5 px-4 text-right font-mono">
                                @if ($debt > 0)
                                    <span class="font-black text-amber-600 dark:text-amber-400 text-xs block">
                                        {{ number_format($debt, 0) }} Ks
                                    </span>
                                    <span class="text-[10px] text-amber-600/80 font-bold block">ကျန်ငွေရှိ</span>
                                @else
                                    <span class="text-emerald-600 dark:text-emerald-400 font-bold text-xs">ရှင်းပြီး</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('store.admin.customers.show', array_merge($storeRouteParams, ['customer' => $customer->id])) }}"
                                       class="px-2.5 py-1 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-200 transition">
                                        အသေးစိတ်
                                    </a>

                                    @if ($debt > 0)
                                        <a href="{{ route('store.admin.receivables.show', array_merge($storeRouteParams, ['customer' => $customer->id])) }}"
                                           class="px-2.5 py-1 rounded-xl text-xs font-bold bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/60 dark:text-amber-300 transition">
                                            ငွေကောက်
                                        </a>
                                    @endif

                                    <button type="button" @click.stop="openEditModal({{ json_encode($customer) }})"
                                            class="px-2 py-1 rounded-xl text-xs font-bold text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/50 transition">
                                        Edit
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400 dark:text-slate-500 text-sm">
                                ဖောက်သည်စာရင်း မရှိသေးပါ။ (No customers found.)
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $customers->links() }}
    </div>

    {{-- MODAL 1: CREATE CUSTOMER MODAL --}}
    <div x-show="createModalOpen" x-cloak
         @click.self="createModalOpen = false"
         @keydown.escape.window="createModalOpen = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto">
        <div class="w-full max-w-lg bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-7 shadow-2xl space-y-4 border border-slate-200 dark:border-slate-800">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <span>✨</span>
                    <span>ဖောက်သည်အသစ် စာရင်းသွင်းခြင်း (Add Customer)</span>
                </h3>
                <button type="button" @click="createModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">✕</button>
            </div>

            <form action="{{ route('store.admin.customers.store', $storeRouteParams) }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">ဖောက်သည် အမည် (Customer Name) *</label>
                    <input type="text" name="name" x-model="newCustomer.name" required
                           placeholder="e.g. U Ba / Daw Hla"
                           class="w-full px-3.5 py-2.5 rounded-xl text-xs font-semibold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">ဖုန်းနံပါတ် (Phone Number) *</label>
                    <input type="text" name="phone" x-model="newCustomer.phone" required
                           placeholder="09xxxxxxxxx"
                           class="w-full px-3.5 py-2.5 rounded-xl text-xs font-mono font-semibold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">အီးမေးလ် (Email - Optional)</label>
                    <input type="email" name="email" x-model="newCustomer.email"
                           placeholder="customer@example.com"
                           class="w-full px-3.5 py-2.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">ဖောက်သည် အမျိုးအစား (Customer Type) *</label>
                    <select name="role" x-model="newCustomer.role" required
                            class="w-full px-3.5 py-2.5 rounded-xl text-xs font-bold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="retail_customer">လက်လီ ဖောက်သည် (Retail Customer)</option>
                        <option value="wholesale_customer">လက်ကား ဖောက်သည် (Wholesale Customer / Dealer)</option>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="createModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-black bg-emerald-600 hover:bg-emerald-500 text-white shadow-md shadow-emerald-500/20 transition">
                        စာရင်းသွင်းမည် (Create Customer)
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 2: EDIT CUSTOMER MODAL --}}
    <div x-show="editModalOpen" x-cloak
         @click.self="editModalOpen = false"
         @keydown.escape.window="editModalOpen = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto">
        <div class="w-full max-w-lg bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-7 shadow-2xl space-y-4 border border-slate-200 dark:border-slate-800">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <span>✏️</span>
                    <span>ဖောက်သည် အချက်အလက် ပြင်ဆင်ခြင်း (Edit Customer)</span>
                </h3>
                <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">✕</button>
            </div>

            <form :action="'{{ url('/store/' . $store->slug . '/admin/customers') }}/' + editingCustomer.id"
                  method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">ဖောက်သည် အမည် (Customer Name) *</label>
                    <input type="text" name="name" x-model="editingCustomer.name" required
                           class="w-full px-3.5 py-2.5 rounded-xl text-xs font-semibold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">ဖုန်းနံပါတ် (Phone Number) *</label>
                    <input type="text" name="phone" x-model="editingCustomer.phone" required
                           class="w-full px-3.5 py-2.5 rounded-xl text-xs font-mono font-semibold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">အီးမေးလ် (Email - Optional)</label>
                    <input type="email" name="email" x-model="editingCustomer.email"
                           class="w-full px-3.5 py-2.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">အမျိုးအစား (Type) *</label>
                        <select name="role" x-model="editingCustomer.role" required
                                class="w-full px-3 py-2 rounded-xl text-xs font-bold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="retail_customer">လက်လီ (Retail)</option>
                            <option value="wholesale_customer">လက်ကား (Wholesale)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">အခြေအနေ (Status) *</label>
                        <select name="status" x-model="editingCustomer.status" required
                                class="w-full px-3 py-2 rounded-xl text-xs font-semibold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="active">Active (ပုံမှန်)</option>
                            <option value="pending">Pending</option>
                            <option value="suspended">Suspended (ပိတ်)</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="editModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-black bg-emerald-600 hover:bg-emerald-500 text-white shadow-md shadow-emerald-500/20 transition">
                        Update Customer (ပြင်ဆင်မည်)
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
