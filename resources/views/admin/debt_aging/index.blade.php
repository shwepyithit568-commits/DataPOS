@extends('layouts.admin.app')

@section('title', __('messages.debt_aging_title') . ' - ' . ($store->name ?? 'DataPOS'))

@php
    $storeRouteParams = ['store_slug' => $store->slug];
@endphp

@section('content')
<div class="w-full space-y-5 sm:space-y-6 pb-12"
     x-data="{
        viewMode: localStorage.getItem('admin_view_mode') || 'table',
        reminderModalOpen: false,
        reminderCustomer: { name: '', phone: '', due: 0, days: 0 },
        openReminder(customer) {
            this.reminderCustomer = customer;
            this.reminderModalOpen = true;
        },
        copyReminderText() {
            const text = `မင်္ဂလာပါ ${this.reminderCustomer.name} ခင်ဗျာ - ${'{{ $store->name }}'} မှ လူကြီးမင်း၏ ကျန်ရှိသော အကြွေးငွေ ကျပ် ${Number(this.reminderCustomer.due).toLocaleString()} အား အဆင်ပြေသည့်အချိန်တွင် လာရောက်ရှင်းလင်းပေးပါရန် လေးစားစွာ အသိပေးအပ်ပါသည်ခင်ဗျာ။ ကျေးဇူးတင်ပါသည်။`;
            navigator.clipboard.writeText(text);
            alert('ငွေတောင်းခံလွှာ စာသားကို Copy ကူးယူပြီးပါပြီ (Viber / SMS တွင် Paste ချ၍ ပို့နိုင်ပါသည်)');
            this.reminderModalOpen = false;
        }
     }"
     @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_view_mode', $event.detail)">

    {{-- 1. Top Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <span class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 grid place-items-center text-xl sm:text-2xl font-bold shadow-sm flex-shrink-0">
                ⏳
            </span>
            <div class="min-w-0">
                <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-400">
                    <a href="{{ route('store.admin.dashboard', ['store_slug' => $store->slug]) }}" class="hover:text-slate-600 dark:hover:text-slate-300 transition">
                        {{ __('messages.admin_dashboard') }}
                    </a>
                    <span>/</span>
                    <span class="text-amber-600 dark:text-amber-400">{{ __('messages.sidebar_reports') }}</span>
                </div>
                <h1 class="text-lg sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2 truncate">
                    <span class="truncate">{{ __('messages.debt_aging_title') }}</span>
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $store->name }} · {{ __('messages.debt_aging_subtitle') }}</p>
            </div>
        </div>

        {{-- Top Right Actions (Print Debt Aging Sheet & CSV Export) --}}
        <div class="flex items-center gap-2.5 self-start sm:self-auto">
            <a href="{{ route('store.admin.debt_aging.print', array_merge($storeRouteParams, request()->only(['search', 'bucket', 'risk', 'sort']))) }}"
               target="_blank"
               class="px-3.5 py-2 rounded-2xl text-xs font-bold border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-sm transition inline-flex items-center gap-1.5 active:scale-95">
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                <span>{{ __('messages.debt_aging_print_statement') }}</span>
            </a>
        </div>
    </div>

    {{-- 2. 4 Key Aging KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
        
        {{-- Total Outstanding Debt --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm flex items-center justify-between transition hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-1 truncate">{{ __('messages.debt_aging_total_receivables') }}</p>
                <h3 class="text-xl sm:text-2xl font-black text-rose-600 dark:text-rose-400 font-mono tracking-tight tabular-nums">
                    Ks {{ number_format($metrics['total_outstanding']) }}
                </h3>
                <p class="text-[11px] text-slate-400 font-semibold mt-0.5">{{ number_format($metrics['total_debtors']) }} {{ __('messages.debt_aging_total_debtors') }}</p>
            </div>
            <span class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 grid place-items-center text-xl font-bold shadow-inner flex-shrink-0">
                💳
            </span>
        </div>

        {{-- 0 - 30 Days (Current / Safe) --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm flex items-center justify-between transition hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-1 truncate">{{ __('messages.debt_aging_bucket_0_30') }}</p>
                <h3 class="text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono tracking-tight tabular-nums">
                    Ks {{ number_format($metrics['bucket_0_30']) }}
                </h3>
                <p class="text-[11px] text-emerald-600 dark:text-emerald-400 font-bold mt-0.5">{{ $metrics['pct_current'] }}% {{ __('messages.debt_aging_pct_of_total') }}</p>
            </div>
            <span class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 grid place-items-center text-xl font-bold shadow-inner flex-shrink-0">
                🟢
            </span>
        </div>

        {{-- 31 - 60 Days (Follow-up) --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm flex items-center justify-between transition hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-1 truncate">{{ __('messages.debt_aging_bucket_31_60') }}</p>
                <h3 class="text-xl sm:text-2xl font-black text-amber-600 dark:text-amber-400 font-mono tracking-tight tabular-nums">
                    Ks {{ number_format($metrics['bucket_31_60']) }}
                </h3>
                <p class="text-[11px] text-amber-600 dark:text-amber-400 font-semibold mt-0.5">{{ __('messages.debt_aging_need_reminder') }}</p>
            </div>
            <span class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 grid place-items-center text-xl font-bold shadow-inner flex-shrink-0">
                🟡
            </span>
        </div>

        {{-- 61+ & 90+ Days (Critical Overdue) --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm flex items-center justify-between transition hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-1 truncate">{{ __('messages.debt_aging_bucket_90_plus') }}</p>
                <h3 class="text-xl sm:text-2xl font-black text-rose-700 dark:text-rose-400 font-mono tracking-tight tabular-nums">
                    Ks {{ number_format($metrics['bucket_61_90'] + $metrics['bucket_90_plus']) }}
                </h3>
                <p class="text-[11px] text-rose-600 dark:text-rose-400 font-bold mt-0.5">{{ $metrics['high_risk_debtors'] }} {{ __('messages.debt_aging_high_risk') }}</p>
            </div>
            <span class="w-12 h-12 rounded-2xl bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300 grid place-items-center text-xl font-bold shadow-inner flex-shrink-0">
                🔴
            </span>
        </div>
    </div>

    {{-- 3. Debt Aging Distribution Progress Widget --}}
    @if ($metrics['total_outstanding'] > 0)
        @php
            $p0 = round(($metrics['bucket_0_30'] / $metrics['total_outstanding']) * 100, 1);
            $p30 = round(($metrics['bucket_31_60'] / $metrics['total_outstanding']) * 100, 1);
            $p60 = round(($metrics['bucket_61_90'] / $metrics['total_outstanding']) * 100, 1);
            $p90 = round(($metrics['bucket_90_plus'] / $metrics['total_outstanding']) * 100, 1);
        @endphp
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm space-y-3">
            <div class="flex items-center justify-between text-xs">
                <span class="font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">{{ __('messages.debt_aging_health_overview') }}</span>
                <span class="font-mono text-slate-400 font-bold">100% (Ks {{ number_format($metrics['total_outstanding']) }})</span>
            </div>
            
            {{-- Multi-colored segmented progress bar --}}
            <div class="w-full h-3 rounded-full overflow-hidden flex bg-slate-100 dark:bg-slate-800">
                @if ($p0 > 0)
                    <div class="bg-emerald-500 h-full transition-all" style="width: {{ $p0 }}%" title="{{ __('messages.debt_aging_col_0_30') }}: {{ $p0 }}%"></div>
                @endif
                @if ($p30 > 0)
                    <div class="bg-amber-500 h-full transition-all" style="width: {{ $p30 }}%" title="{{ __('messages.debt_aging_col_31_60') }}: {{ $p30 }}%"></div>
                @endif
                @if ($p60 > 0)
                    <div class="bg-orange-500 h-full transition-all" style="width: {{ $p60 }}%" title="{{ __('messages.debt_aging_col_61_90') }}: {{ $p60 }}%"></div>
                @endif
                @if ($p90 > 0)
                    <div class="bg-rose-600 h-full transition-all" style="width: {{ $p90 }}%" title="{{ __('messages.debt_aging_col_90_plus') }}: {{ $p90 }}%"></div>
                @endif
            </div>

            {{-- Legend --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-1 text-xs">
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 flex-shrink-0"></span>
                    <span class="text-slate-600 dark:text-slate-400">{{ __('messages.debt_aging_days_30') }} <strong>{{ $p0 }}%</strong></span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 flex-shrink-0"></span>
                    <span class="text-slate-600 dark:text-slate-400">{{ __('messages.debt_aging_days_60') }} <strong>{{ $p30 }}%</strong></span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-orange-500 flex-shrink-0"></span>
                    <span class="text-slate-600 dark:text-slate-400">{{ __('messages.debt_aging_days_90') }} <strong>{{ $p60 }}%</strong></span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-600 flex-shrink-0"></span>
                    <span class="text-slate-600 dark:text-slate-400">{{ __('messages.debt_aging_days_90p') }} <strong>{{ $p90 }}%</strong></span>
                </div>
            </div>
        </div>
    @endif

    {{-- 4. Unified Admin Toolbar --}}
    @php
        $bucketFilterOptions = [
            '0_30'    => __('messages.debt_aging_bucket_0_30'),
            '31_60'   => __('messages.debt_aging_bucket_31_60'),
            '61_90'   => __('messages.debt_aging_bucket_61_90'),
            '90_plus' => __('messages.debt_aging_bucket_90_plus'),
        ];

        $riskFilterOptions = [
            'low'      => __('messages.debt_aging_risk_low'),
            'medium'   => __('messages.debt_aging_risk_medium'),
            'high'     => __('messages.debt_aging_risk_high'),
            'critical' => __('messages.debt_aging_risk_critical'),
        ];

        $exportUrl = route('store.admin.debt_aging.export', array_merge($storeRouteParams, request()->only(['search', 'sort', 'bucket', 'risk'])));
    @endphp

    <x-admin.toolbar
        :search="request('search', $filters['search'] ?? '')"
        :searchPlaceholder="__('messages.debt_aging_filter_search')"
        :sort="request('sort', $filters['sort'] ?? 'total_due_desc')"
        :sortOptions="[
            'total_due_desc'     => __('messages.debt_aging_sort_due_desc'),
            'total_due_asc'      => __('messages.debt_aging_sort_due_asc'),
            'overdue_days_desc'  => __('messages.debt_aging_sort_overdue_desc'),
            'overdue_days_asc'   => __('messages.debt_aging_sort_overdue_asc'),
            'bucket_90_desc'     => __('messages.debt_aging_sort_bucket90_desc'),
            'name_asc'           => __('messages.debt_aging_sort_name_asc'),
        ]"
        :filters="[
            'bucket' => [
                'label' => __('messages.debt_aging_bucket_filter'),
                'options' => $bucketFilterOptions,
            ],
            'risk' => [
                'label' => __('messages.debt_aging_risk_filter'),
                'options' => $riskFilterOptions,
            ],
        ]"
        :viewMode="'table'"
        :showViewToggle="true"
        :showExportImport="true"
        :exportUrl="$exportUrl"
        :totalCount="$customers instanceof \Illuminate\Pagination\LengthAwarePaginator ? $customers->total() : $customers->count()"
        :paginator="$customers instanceof \Illuminate\Pagination\LengthAwarePaginator ? $customers : null"
        :perPageOptions="[25 => '25', 50 => '50', 100 => '100', 'all' => __('messages.all')]"
    />

    {{-- 5. Card Grid View --}}
    <div x-show="viewMode === 'card'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($customers as $c)
            <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm hover:shadow-md transition flex flex-col justify-between space-y-4 group">
                <div class="space-y-3">
                    <div class="flex items-start justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div>
                            <h3 class="font-black text-sm text-slate-900 dark:text-slate-100 group-hover:text-amber-600 transition">
                                {{ $c['customer_name'] }}
                            </h3>
                            <p class="text-xs text-slate-400 font-mono mt-0.5">
                                {{ $c['customer_phone'] }}
                            </p>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider
                            {{ $c['risk_level'] === 'critical' ? 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300' :
                              ($c['risk_level'] === 'high' ? 'bg-orange-100 text-orange-700 dark:bg-orange-950 dark:text-orange-300' :
                              ($c['risk_level'] === 'medium' ? 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300' :
                              'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300')) }}">
                            {{ $c['risk_level'] }}
                        </span>
                    </div>

                    <div class="p-3 rounded-2xl bg-rose-50/60 dark:bg-rose-950/30 flex items-center justify-between">
                        <span class="text-rose-600 dark:text-rose-400 text-xs font-bold">{{ __('messages.debt_aging_total_receivables') }}</span>
                        <span class="font-mono font-black text-rose-700 dark:text-rose-300 text-base">
                            Ks {{ number_format($c['total_due']) }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="p-2 rounded-xl bg-slate-50 dark:bg-slate-800/50">
                            <span class="text-slate-400 block text-[10px] font-bold">{{ __('messages.debt_aging_col_0_30') }}</span>
                            <span class="font-mono font-bold text-slate-700 dark:text-slate-300">
                                Ks {{ number_format($c['bucket_0_30']) }}
                            </span>
                        </div>
                        <div class="p-2 rounded-xl bg-slate-50 dark:bg-slate-800/50">
                            <span class="text-slate-400 block text-[10px] font-bold">{{ __('messages.debt_aging_col_31_60') }}</span>
                            <span class="font-mono font-bold text-slate-700 dark:text-slate-300">
                                Ks {{ number_format($c['bucket_31_60']) }}
                            </span>
                        </div>
                        <div class="p-2 rounded-xl bg-slate-50 dark:bg-slate-800/50">
                            <span class="text-slate-400 block text-[10px] font-bold">{{ __('messages.debt_aging_col_61_90') }}</span>
                            <span class="font-mono font-bold text-orange-600 dark:text-orange-400">
                                Ks {{ number_format($c['bucket_61_90']) }}
                            </span>
                        </div>
                        <div class="p-2 rounded-xl bg-slate-50 dark:bg-slate-800/50">
                            <span class="text-slate-400 block text-[10px] font-bold">{{ __('messages.debt_aging_col_90_plus') }}</span>
                            <span class="font-mono font-bold text-rose-600 dark:text-rose-400">
                                Ks {{ number_format($c['bucket_90_plus']) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                    <span class="text-[11px] text-slate-400">{{ __('messages.debt_aging_overdue') }} <strong>{{ $c['max_overdue_days'] }} {{ __('messages.debt_aging_days_unit') }}</strong></span>
                    <button type="button" @click="openReminder({{ json_encode(['name' => $c['customer_name'], 'phone' => $c['customer_phone'], 'due' => $c['total_due'], 'days' => $c['max_overdue_days']]) }})"
                            class="px-2.5 py-1 rounded-xl text-xs font-bold bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/60 dark:text-amber-300 transition flex items-center gap-1">
                        <span>💬</span>
                        <span>{{ __('messages.debt_aging_send_reminder') }}</span>
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center text-slate-400 dark:text-slate-500 text-sm">
                {{ __('messages.debt_aging_no_debtors') }}
            </div>
        @endforelse
    </div>

    {{-- 6. Table View --}}
    <div x-show="viewMode === 'table'" class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                        <th class="py-3.5 px-4">{{ __('messages.debt_aging_col_customer') }}</th>
                        <th class="py-3.5 px-4 text-right">{{ __('messages.debt_aging_total_receivables') }}</th>
                        <th class="py-3.5 px-4 text-right">{{ __('messages.debt_aging_col_0_30') }}</th>
                        <th class="py-3.5 px-4 text-right">{{ __('messages.debt_aging_col_31_60') }}</th>
                        <th class="py-3.5 px-4 text-right">{{ __('messages.debt_aging_col_61_90') }}</th>
                        <th class="py-3.5 px-4 text-right">{{ __('messages.debt_aging_col_90_plus') }}</th>
                        <th class="py-3.5 px-4 text-center">{{ __('messages.debt_aging_col_overdue_days') }}</th>
                        <th class="py-3.5 px-4 text-center">{{ __('messages.debt_aging_col_risk') }}</th>
                        <th class="py-3.5 px-4 text-right">{{ __('messages.debt_aging_col_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($customers as $c)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <td class="py-3.5 px-4">
                                <div class="font-black text-slate-900 dark:text-slate-100 text-sm">
                                    {{ $c['customer_name'] }}
                                </div>
                                <div class="font-mono text-[11px] text-slate-400">
                                    {{ $c['customer_phone'] }}
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-black text-rose-600 dark:text-rose-400 text-sm tabular-nums">
                                Ks {{ number_format($c['total_due']) }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono text-emerald-600 dark:text-emerald-400 font-semibold tabular-nums">
                                {{ $c['bucket_0_30'] > 0 ? 'Ks ' . number_format($c['bucket_0_30']) : '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono text-amber-600 dark:text-amber-400 font-semibold tabular-nums">
                                {{ $c['bucket_31_60'] > 0 ? 'Ks ' . number_format($c['bucket_31_60']) : '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono text-orange-600 dark:text-orange-400 font-semibold tabular-nums">
                                {{ $c['bucket_61_90'] > 0 ? 'Ks ' . number_format($c['bucket_61_90']) : '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono text-rose-600 dark:text-rose-400 font-black tabular-nums">
                                {{ $c['bucket_90_plus'] > 0 ? 'Ks ' . number_format($c['bucket_90_plus']) : '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-center font-mono font-bold text-slate-700 dark:text-slate-300">
                                {{ $c['max_overdue_days'] }} {{ __('messages.debt_aging_days_unit') }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider
                                    {{ $c['risk_level'] === 'critical' ? 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300' :
                                      ($c['risk_level'] === 'high' ? 'bg-orange-100 text-orange-700 dark:bg-orange-950 dark:text-orange-300' :
                                      ($c['risk_level'] === 'medium' ? 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300' :
                                      'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300')) }}">
                                    {{ $c['risk_level'] }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <button type="button" @click="openReminder({{ json_encode(['name' => $c['customer_name'], 'phone' => $c['customer_phone'], 'due' => $c['total_due'], 'days' => $c['max_overdue_days']]) }})"
                                        class="px-2.5 py-1 rounded-xl text-xs font-bold bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/60 dark:text-amber-300 transition inline-flex items-center gap-1">
                                    <span>💬</span>
                                    <span>{{ __('messages.debt_aging_reminder_btn') }}</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-400 dark:text-slate-500 text-sm">
                                {{ __('messages.debt_aging_no_debtors') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 7. Payment Reminder Copy Modal --}}
    <div x-show="reminderModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div @click.away="reminderModalOpen = false" class="w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl p-6 shadow-2xl space-y-4 border border-slate-200 dark:border-slate-800">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <span>💬</span>
                    <span>{{ __('messages.debt_aging_reminder_modal_title') }}</span>
                </h3>
                <button type="button" @click="reminderModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">✕</button>
            </div>

            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 text-xs font-medium text-slate-700 dark:text-slate-300 space-y-2 border border-slate-100 dark:border-slate-700">
                <p><strong>{{ __('messages.debt_aging_reminder_customer') }}</strong> <span x-text="reminderCustomer.name"></span> (<span x-text="reminderCustomer.phone"></span>)</p>
                <p><strong>{{ __('messages.debt_aging_reminder_balance') }}</strong> Ks <span class="font-bold text-rose-600 font-mono" x-text="Number(reminderCustomer.due).toLocaleString()"></span></p>
                <div class="p-3 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 font-sans leading-relaxed text-slate-600 dark:text-slate-300">
                    မင်္ဂလာပါ <span class="font-bold" x-text="reminderCustomer.name"></span> ခင်ဗျာ - <strong>{{ $store->name }}</strong> မှ လူကြီးမင်း၏ ကျန်ရှိသော အကြွေးငွေ ကျပ် <strong class="text-rose-600" x-text="Number(reminderCustomer.due).toLocaleString()"></strong> အား အဆင်ပြေသည့်အချိန်တွင် လာရောက်ရှင်းလင်းပေးပါရန် လေးစားစွာ အသိပေးအပ်ပါသည်ခင်ဗျာ။
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" @click="reminderModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                    {{ __('messages.debt_aging_reminder_cancel') }}
                </button>
                <button type="button" @click="copyReminderText()" class="px-4 py-2 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white shadow-md shadow-amber-500/20 transition flex items-center gap-1.5">
                    <span>📋</span>
                    <span>{{ __('messages.debt_aging_reminder_copy') }}</span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
