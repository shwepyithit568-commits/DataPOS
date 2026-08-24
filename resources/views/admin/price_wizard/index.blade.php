@extends('layouts.admin.app')

@section('title', __('messages.price_wizard_title') . ' - ' . ($store->name ?? 'DataPOS'))

@section('content')
<script nonce="{{ $cspNonce }}">
window.priceWizardData = function (initialProducts) {
    return {
        items: initialProducts || [],
        targetField: 'retail_price',
        calcMode: 'markup_on_cost',
        calcValue: 20,
        roundingRule: 'round_100',
        syncVariants: true,
        setOldPrice: false,
        showModal: false,

        init: function () {
            this.recalculateAll();
        },

        get selectedCount() {
            return this.items.filter(function (i) { return i.selected; }).length;
        },

        get modifiedCount() {
            return this.items.filter(function (i) { return i.selected && i.is_modified; }).length;
        },

        get priceIncreases() {
            return this.items.filter(function (i) { return i.selected && i.delta > 0; }).length;
        },

        get priceDecreases() {
            return this.items.filter(function (i) { return i.selected && i.delta < 0; }).length;
        },

        get belowCostWarnings() {
            return this.items.filter(function (i) { return i.selected && i.is_below_cost; }).length;
        },

        isPercentageMode: function () {
            return ['markup_on_cost', 'margin_on_cost', 'percentage_on_current', 'wholesale_from_retail'].indexOf(this.calcMode) !== -1;
        },

        getValueLabel: function () {
            switch (this.calcMode) {
                case 'markup_on_cost': return 'Markup % (e.g. 20%)';
                case 'margin_on_cost': return 'Target Margin % (e.g. 25%)';
                case 'percentage_on_current': return 'Adjustment % (+/- %)';
                case 'fixed_amount_on_current': return 'Fixed Amount (+/- MMK)';
                case 'fixed_price': return 'Fixed Price (MMK)';
                case 'wholesale_from_retail': return 'Discount % from Retail';
                default: return 'Value';
            }
        },

        setQuickMarkup: function (pct) {
            this.calcMode = 'markup_on_cost';
            this.calcValue = pct;
            this.recalculateAll();
        },

        selectAll: function (select) {
            this.items.forEach(function (i) { i.selected = select; });
        },

        toggleSelectAll: function (checked) {
            this.items.forEach(function (i) { i.selected = checked; });
        },

        invertSelection: function () {
            this.items.forEach(function (i) { i.selected = !i.selected; });
        },

        onItemSelectionChange: function (item) {
            if (item.selected) {
                this.calculateSingleItem(item);
            }
        },

        onManualPriceChange: function (item) {
            var basePrice = this.getBasePrice(item);
            var cost = item.cost;
            item.delta = Math.round((item.new_price - basePrice) * 100) / 100;
            item.delta_percent = basePrice > 0 ? Math.round((item.delta / basePrice) * 1000) / 10 : 0;
            item.new_margin = item.new_price > 0 && cost > 0 ? Math.round(((item.new_price - cost) / item.new_price) * 1000) / 10 : 0;
            item.is_below_cost = cost > 0 && item.new_price < cost;
            item.is_modified = Math.abs(item.delta) > 0.001;
        },

        getBasePrice: function (item) {
            if (this.targetField === 'wholesale_price') return item.current_wholesale;
            if (this.targetField === 'old_price') return item.current_old_price;
            return item.current_retail;
        },

        calculateSingleItem: function (item) {
            var basePrice = this.getBasePrice(item);
            var cost = item.cost;
            var calculated = basePrice;

            switch (this.calcMode) {
                case 'markup_on_cost':
                    var baseCost = cost > 0 ? cost : basePrice;
                    calculated = baseCost * (1 + (this.calcValue / 100));
                    break;
                case 'margin_on_cost':
                    var marginBase = cost > 0 ? cost : basePrice;
                    var marginDec = Math.min(this.calcValue / 100, 0.99);
                    calculated = marginBase / (1 - marginDec);
                    break;
                case 'percentage_on_current':
                    calculated = basePrice * (1 + (this.calcValue / 100));
                    break;
                case 'fixed_amount_on_current':
                    calculated = basePrice + Number(this.calcValue);
                    break;
                case 'fixed_price':
                    calculated = Number(this.calcValue);
                    break;
                case 'wholesale_from_retail':
                    calculated = item.current_retail * (1 - (this.calcValue / 100));
                    break;
            }

            if (calculated < 0) calculated = 0;

            calculated = this.applyRounding(calculated, this.roundingRule);

            item.new_price = calculated;
            item.delta = Math.round((calculated - basePrice) * 100) / 100;
            item.delta_percent = basePrice > 0 ? Math.round((item.delta / basePrice) * 1000) / 10 : 0;
            item.new_margin = calculated > 0 && cost > 0 ? Math.round(((calculated - cost) / calculated) * 1000) / 10 : 0;
            item.is_below_cost = cost > 0 && calculated < cost;
            item.is_modified = Math.abs(item.delta) > 0.001;
        },

        recalculateAll: function () {
            var self = this;
            this.items.forEach(function (item) {
                if (item.selected) {
                    self.calculateSingleItem(item);
                }
            });
        },

        applyRounding: function (val, rule) {
            switch (rule) {
                case 'round_10': return Math.round(val / 10) * 10;
                case 'round_50': return Math.round(val / 50) * 50;
                case 'round_100': return Math.round(val / 100) * 100;
                case 'round_500': return Math.round(val / 500) * 500;
                case 'round_1000': return Math.round(val / 1000) * 1000;
                case 'charm_900':
                    var thousands900 = Math.floor(val / 1000) * 1000;
                    return (thousands900 + 900) > 0 ? thousands900 + 900 : val;
                case 'charm_990':
                    var thousands990 = Math.floor(val / 1000) * 1000;
                    return (thousands990 + 990) > 0 ? thousands990 + 990 : val;
                case 'none':
                default:
                    return Math.round(val * 100) / 100;
            }
        },

        formatCurrency: function (num) {
            if (num === null || num === undefined) return '0.00';
            return Number(num).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        openConfirmModal: function () {
            if (this.modifiedCount === 0) return;
            this.showModal = true;
        },

        submitForm: function () {
            var container = document.getElementById('hiddenItemsContainer');
            container.innerHTML = '';

            var counter = 0;
            var target = this.targetField;
            this.items.forEach(function (item) {
                if (item.selected && item.is_modified) {
                    var idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'items[' + counter + '][product_id]';
                    idInput.value = item.id;
                    container.appendChild(idInput);

                    var priceInput = document.createElement('input');
                    priceInput.type = 'hidden';
                    priceInput.name = 'items[' + counter + '][' + target + ']';
                    priceInput.value = item.new_price;
                    container.appendChild(priceInput);

                    counter++;
                }
            });

            document.getElementById('priceWizardForm').submit();
        }
    };
};
</script>

@php
    // Prepare JSON payload for Alpine.js
    $productsPayload = $products->map(function ($p) {
        $cost = (float) ($p->purchase_cost ?? 0);
        $retail = (float) ($p->retail_price ?? 0);
        $wholesale = (float) ($p->wholesale_price ?? 0);
        $oldPrice = (float) ($p->old_price ?? 0);
        $currentMargin = $retail > 0 && $cost > 0 ? round((($retail - $cost) / $retail) * 100, 1) : 0;

        return [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'category_name' => $p->category?->name ?? '-',
            'brand_name' => $p->brand?->name ?? '-',
            'cost' => $cost,
            'current_retail' => $retail,
            'current_wholesale' => $wholesale,
            'current_old_price' => $oldPrice,
            'current_margin' => $currentMargin,
            'selected' => true,
            'new_price' => $retail,
            'delta' => 0,
            'delta_percent' => 0,
            'new_margin' => $currentMargin,
            'is_below_cost' => $cost > 0 && $retail < $cost,
            'is_modified' => false,
        ];
    })->values()->toJson();
@endphp

<div
    x-data="window.priceWizardData({{ $productsPayload }})"
    class="w-full space-y-5 sm:space-y-6"
>

    {{-- ============================================================
         PAGE HEADER — Standard admin-page-header pattern
         ============================================================ --}}
    <div class="admin-page-header">
        <div class="min-w-0">
            <p class="text-[11px] font-black uppercase tracking-wider text-violet-600 dark:text-violet-400">
                {{ __('messages.sidebar_inventory') ?? 'Inventory' }}
            </p>
            <h1 class="admin-page-title mt-0.5">
                {{ __('messages.price_wizard_title') }}
            </h1>
            <p class="admin-page-sub mt-1">
                {{ $store->name }} · {{ __('messages.price_wizard_subtitle') }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            {{-- Export CSV --}}
            <a href="{{ route('store.admin.price_wizard.export', array_merge(['store_slug' => $store->slug], request()->all())) }}"
               class="admin-secondary-btn">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span class="hidden sm:inline">{{ __('messages.price_wizard_export_csv') }}</span>
            </a>
            {{-- Primary Submit Button (triggers modal) --}}
            <button type="button"
                    @click="openConfirmModal()"
                    :disabled="selectedCount === 0 || modifiedCount === 0"
                    class="admin-primary-btn disabled:opacity-50 disabled:cursor-not-allowed">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ __('messages.price_wizard_apply_changes') }} (<span x-text="modifiedCount"></span>)</span>
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
        {{-- 1. Total Products --}}
        <div class="admin-hairline-cell">
            <div class="admin-stat-label">{{ __('messages.price_wizard_stat_total_products') }}</div>
            <div class="admin-stat-value">{{ number_format($stats['total_products']) }}</div>
            <div class="admin-stat-sub">{{ number_format(count($products)) }} matched in filter</div>
        </div>
        {{-- 2. With Purchase Cost --}}
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-emerald-600 dark:text-emerald-400">{{ __('messages.price_wizard_stat_with_cost') }}</div>
            <div class="admin-stat-value text-emerald-600 dark:text-emerald-400">{{ number_format($stats['with_cost_count']) }}</div>
            <div class="admin-stat-sub text-slate-400">{{ number_format($stats['zero_cost_count']) }} {{ __('messages.price_wizard_stat_zero_cost') }}</div>
        </div>
        {{-- 3. Avg Retail Margin --}}
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-violet-600 dark:text-violet-400">{{ __('messages.price_wizard_stat_avg_margin') }}</div>
            <div class="admin-stat-value text-violet-600 dark:text-violet-400">{{ $stats['avg_margin'] }}%</div>
            <div class="admin-stat-sub">Based on cost & retail</div>
        </div>
        {{-- 4. Below Cost Warning --}}
        <div class="admin-hairline-cell {{ $stats['below_cost_count'] > 0 ? 'bg-rose-50/40 dark:bg-rose-950/20' : '' }}">
            <div class="admin-stat-label {{ $stats['below_cost_count'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-500' }}">
                {{ __('messages.price_wizard_stat_below_cost') }}
            </div>
            <div class="admin-stat-value {{ $stats['below_cost_count'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-700 dark:text-slate-200' }}">
                {{ number_format($stats['below_cost_count']) }}
            </div>
            <div class="admin-stat-sub">{{ $stats['below_cost_count'] > 0 ? 'Requires attention' : 'All healthy' }}</div>
        </div>
    </div>

    {{-- ============================================================
         FILTER TOOLBAR
         ============================================================ --}}
    <div class="rounded-2xl sm:rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm p-3.5 transition">
        <form method="GET" action="{{ route('store.admin.price_wizard.index', ['store_slug' => $store->slug]) }}">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-2.5 items-center">

                {{-- Search --}}
                <div class="relative lg:col-span-2">
                    <input type="text"
                           name="search"
                           value="{{ $filters['search'] ?? '' }}"
                           placeholder="{{ __('messages.search') }} product, SKU..."
                           class="w-full pl-9 pr-3.5 py-2 min-h-[42px] border border-slate-200 dark:border-slate-700 rounded-xl text-xs sm:text-sm bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-violet-500 transition shadow-inner">
                    <svg class="w-4 h-4 text-slate-400 dark:text-slate-500 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                {{-- Category --}}
                <select name="category_id" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 min-h-[42px] py-2 text-xs sm:text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:outline-none focus:ring-2 focus:ring-violet-500 cursor-pointer shadow-sm transition">
                    <option value="" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100">{{ __('messages.all_categories') ?? 'All Categories' }}</option>
                    @foreach($filterOptions['categories'] as $cat)
                        <option value="{{ $cat->id }}" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100" {{ ($filters['category_id'] ?? '') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }} ({{ $cat->products_count }})
                        </option>
                    @endforeach
                </select>

                {{-- Brand --}}
                <select name="brand_id" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 min-h-[42px] py-2 text-xs sm:text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:outline-none focus:ring-2 focus:ring-violet-500 cursor-pointer shadow-sm transition">
                    <option value="" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100">{{ __('messages.all_brands') ?? 'All Brands' }}</option>
                    @foreach($filterOptions['brands'] as $br)
                        <option value="{{ $br->id }}" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100" {{ ($filters['brand_id'] ?? '') == $br->id ? 'selected' : '' }}>
                            {{ $br->name }} ({{ $br->products_count }})
                        </option>
                    @endforeach
                </select>

                {{-- Cost Filter Status --}}
                <select name="cost_filter" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 min-h-[42px] py-2 text-xs sm:text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:outline-none focus:ring-2 focus:ring-violet-500 cursor-pointer shadow-sm transition">
                    <option value="" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100">{{ __('messages.price_wizard_filter_cost_all') }}</option>
                    <option value="with_cost" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100" {{ ($filters['cost_filter'] ?? '') === 'with_cost' ? 'selected' : '' }}>
                        {{ __('messages.price_wizard_filter_with_cost') }}
                    </option>
                    <option value="zero_cost" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100" {{ ($filters['cost_filter'] ?? '') === 'zero_cost' ? 'selected' : '' }}>
                        {{ __('messages.price_wizard_filter_zero_cost') }}
                    </option>
                    <option value="below_cost" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100" {{ ($filters['cost_filter'] ?? '') === 'below_cost' ? 'selected' : '' }}>
                        {{ __('messages.price_wizard_filter_below_cost') }}
                    </option>
                </select>

                {{-- Submit & Reset --}}
                <div class="flex items-center gap-1.5 justify-end">
                    @if(!empty($filters['search']) || !empty($filters['category_id']) || !empty($filters['brand_id']) || !empty($filters['supplier_id']) || !empty($filters['cost_filter']))
                        <a href="{{ route('store.admin.price_wizard.index', ['store_slug' => $store->slug]) }}"
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
         INTERACTIVE PRICING STRATEGY WIZARD CONTROL PANEL
         ============================================================ --}}
    <div class="rounded-2xl sm:rounded-3xl bg-slate-50 dark:bg-slate-900 border border-violet-200 dark:border-violet-800/60 shadow-md p-4 sm:p-5 space-y-4">

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-violet-600 text-white flex items-center justify-center shadow-md">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-slate-100 font-outfit">
                        {{ __('messages.price_wizard_calc_strategy') }}
                    </h2>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                        Select target field, pricing formula, and rounding rules to instantly calculate new prices.
                    </p>
                </div>
            </div>

            {{-- Quick Preset Buttons --}}
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mr-1">Quick Markup:</span>
                <template x-for="pct in [10, 15, 20, 25, 30, 40, 50]" :key="pct">
                    <button type="button"
                            @click="setQuickMarkup(pct)"
                            class="px-2.5 py-1.5 rounded-lg text-xs font-mono font-bold bg-white dark:bg-slate-800 border border-violet-200 dark:border-violet-700 text-violet-700 dark:text-violet-300 hover:bg-violet-600 hover:text-white dark:hover:bg-violet-600 dark:hover:text-white transition shadow-sm"
                            x-text="'+' + pct + '%'">
                    </button>
                </template>
            </div>
        </div>

        {{-- Strategy Form Controls --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">

            {{-- 1. Target Price Field --}}
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                    {{ __('messages.price_wizard_target_price') }}
                </label>
                <select x-model="targetField"
                        @change="recalculateAll()"
                        class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 min-h-[42px] py-2 text-xs sm:text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-violet-500 shadow-sm">
                    <option value="retail_price" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100">{{ __('messages.price_wizard_retail_price') }}</option>
                    <option value="wholesale_price" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100">{{ __('messages.price_wizard_wholesale_price') }}</option>
                    <option value="old_price" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100">{{ __('messages.price_wizard_compare_at_price') }}</option>
                </select>
            </div>

            {{-- 2. Calculation Mode --}}
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                    {{ __('messages.price_wizard_calc_strategy') }}
                </label>
                <select x-model="calcMode"
                        @change="recalculateAll()"
                        class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 min-h-[42px] py-2 text-xs sm:text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-violet-500 shadow-sm">
                    <option value="markup_on_cost" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100">{{ __('messages.price_wizard_mode_markup') }}</option>
                    <option value="margin_on_cost" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100">{{ __('messages.price_wizard_mode_margin') }}</option>
                    <option value="percentage_on_current" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100">{{ __('messages.price_wizard_mode_percentage') }}</option>
                    <option value="fixed_amount_on_current" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100">{{ __('messages.price_wizard_mode_fixed_amount') }}</option>
                    <option value="fixed_price" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100">{{ __('messages.price_wizard_mode_fixed_price') }}</option>
                    <option value="wholesale_from_retail" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100">{{ __('messages.price_wizard_mode_wholesale_derive') }}</option>
                </select>
            </div>

            {{-- 3. Adjustment Value --}}
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                    <span x-text="getValueLabel()"></span>
                </label>
                <div class="relative">
                    <input type="number"
                           step="any"
                           x-model.number="calcValue"
                           @input="recalculateAll()"
                           class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 min-h-[42px] py-2 pr-10 text-xs sm:text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-mono font-bold focus:ring-2 focus:ring-violet-500 shadow-sm">
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400"
                          x-text="isPercentageMode() ? '%' : 'MMK'"></span>
                </div>
            </div>

            {{-- 4. Rounding Rule --}}
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                    {{ __('messages.price_wizard_rounding') }}
                </label>
                <select x-model="roundingRule"
                        @change="recalculateAll()"
                        class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 min-h-[42px] py-2 text-xs sm:text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-violet-500 shadow-sm">
                    <option value="none" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100">{{ __('messages.price_wizard_round_none') }}</option>
                    <option value="round_10" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100">{{ __('messages.price_wizard_round_10') }}</option>
                    <option value="round_50" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100">{{ __('messages.price_wizard_round_50') }}</option>
                    <option value="round_100" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100">{{ __('messages.price_wizard_round_100') }}</option>
                    <option value="round_500" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100">{{ __('messages.price_wizard_round_500') }}</option>
                    <option value="round_1000" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100">{{ __('messages.price_wizard_round_1000') }}</option>
                    <option value="charm_900" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100">{{ __('messages.price_wizard_round_charm_900') }}</option>
                    <option value="charm_990" class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100">{{ __('messages.price_wizard_round_charm_990') }}</option>
                </select>
            </div>

        </div>

        {{-- Action Bar: Selection Helpers & Options --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2">
            <div class="flex flex-wrap items-center gap-2">
                <button type="button"
                        @click="selectAll(true)"
                        class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 transition shadow-sm">
                    {{ __('messages.price_wizard_select_all') }}
                </button>
                <button type="button"
                        @click="selectAll(false)"
                        class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 transition shadow-sm">
                    {{ __('messages.price_wizard_deselect_all') }}
                </button>
                <button type="button"
                        @click="invertSelection()"
                        class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 transition shadow-sm">
                    {{ __('messages.price_wizard_invert_selection') }}
                </button>
                <span class="text-xs font-bold text-slate-500 font-mono ml-1">
                    <span x-text="selectedCount"></span> / <span x-text="items.length"></span> selected
                </span>
            </div>

            <div class="flex flex-wrap items-center gap-4 text-xs font-semibold text-slate-700 dark:text-slate-300">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" x-model="syncVariants" class="rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                    <span>{{ __('messages.price_wizard_sync_variants') }}</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" x-model="setOldPrice" class="rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                    <span>{{ __('messages.price_wizard_set_old_price') }}</span>
                </label>
            </div>
        </div>

    </div>

    {{-- ============================================================
         LIVE PREVIEW & EDITABLE PRICING TABLE
         ============================================================ --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h2 class="font-bold text-slate-900 dark:text-slate-100 font-outfit text-base">
                Product Pricing Live Preview (<span x-text="items.length"></span>)
            </h2>
            <div class="flex items-center gap-2">
                <span class="text-xs font-mono text-emerald-600 dark:text-emerald-400 font-bold" x-show="priceIncreases > 0">
                    +<span x-text="priceIncreases"></span> increases
                </span>
                <span class="text-xs font-mono text-rose-600 dark:text-rose-400 font-bold" x-show="priceDecreases > 0">
                    -<span x-text="priceDecreases"></span> decreases
                </span>
                <span class="text-xs font-mono px-2 py-0.5 rounded-full bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 font-bold" x-show="belowCostWarnings > 0">
                    ⚠️ <span x-text="belowCostWarnings"></span> below cost
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50/75 dark:bg-slate-800/50 text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400 font-semibold">
                    <tr>
                        <th class="p-4 w-10 text-center">
                            <input type="checkbox"
                                   @change="toggleSelectAll($event.target.checked)"
                                   :checked="selectedCount === items.length && items.length > 0"
                                   class="rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                        </th>
                        <th class="px-4 py-3">{{ __('messages.product') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('messages.price_wizard_current_cost') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('messages.price_wizard_current_retail') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('messages.price_wizard_current_wholesale') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('messages.price_wizard_current_margin') }}</th>
                        <th class="px-4 py-3 text-right w-44 font-bold text-violet-700 dark:text-violet-300">
                            {{ __('messages.price_wizard_new_price') }}
                        </th>
                        <th class="px-4 py-3 text-right">{{ __('messages.price_wizard_difference') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('messages.price_wizard_new_margin') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <template x-for="(item, index) in items" :key="item.id">
                        <tr :class="item.selected ? 'bg-white dark:bg-slate-900 hover:bg-slate-50/70 dark:hover:bg-slate-800/50' : 'bg-slate-50/40 dark:bg-slate-950/40 opacity-60'"
                            class="transition">

                            {{-- Row Checkbox --}}
                            <td class="p-4 text-center">
                                <input type="checkbox"
                                       x-model="item.selected"
                                       @change="onItemSelectionChange(item)"
                                       class="rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                            </td>

                            {{-- Product Name & SKU --}}
                            <td class="px-4 py-3">
                                <div class="font-bold text-slate-900 dark:text-slate-100 text-sm" x-text="item.name"></div>
                                <div class="flex items-center gap-1.5 text-xs text-slate-400 mt-0.5 font-mono">
                                    <span>SKU: <span x-text="item.sku"></span></span>
                                    <span>•</span>
                                    <span x-text="item.category_name"></span>
                                </div>
                            </td>

                            {{-- Purchase Cost --}}
                            <td class="px-4 py-3 text-right font-mono text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                <span x-text="formatCurrency(item.cost)"></span>
                            </td>

                            {{-- Current Retail --}}
                            <td class="px-4 py-3 text-right font-mono text-xs text-slate-800 dark:text-slate-200 font-semibold whitespace-nowrap">
                                <span x-text="formatCurrency(item.current_retail)"></span>
                            </td>

                            {{-- Current Wholesale --}}
                            <td class="px-4 py-3 text-right font-mono text-xs text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                <span x-text="formatCurrency(item.current_wholesale)"></span>
                            </td>

                            {{-- Current Margin --}}
                            <td class="px-4 py-3 text-right font-mono text-xs whitespace-nowrap"
                                :class="item.current_margin >= 20 ? 'text-emerald-600 dark:text-emerald-400' : (item.current_margin > 0 ? 'text-amber-600' : 'text-slate-400')">
                                <span x-text="item.current_margin + '%'"></span>
                            </td>

                            {{-- New Calculated Price (INLINE EDITABLE!) --}}
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="relative inline-block w-36">
                                    <input type="number"
                                           step="any"
                                           x-model.number="item.new_price"
                                           @input="onManualPriceChange(item)"
                                           :disabled="!item.selected"
                                           class="w-full text-right font-mono font-bold text-sm px-2.5 py-1.5 rounded-xl border focus:ring-2 focus:ring-violet-500 shadow-inner transition
                                               bg-violet-50/50 dark:bg-violet-950/20 text-violet-900 dark:text-violet-200 border-violet-300 dark:border-violet-700">
                                </div>
                            </td>

                            {{-- Difference / Delta --}}
                            <td class="px-4 py-3 text-right font-mono text-xs whitespace-nowrap">
                                <template x-if="item.delta > 0">
                                    <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 font-bold">
                                        +<span x-text="formatCurrency(item.delta)"></span>
                                        (<span x-text="item.delta_percent + '%'"></span>)
                                    </span>
                                </template>
                                <template x-if="item.delta < 0">
                                    <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-lg bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 font-bold">
                                        <span x-text="formatCurrency(item.delta)"></span>
                                        (<span x-text="item.delta_percent + '%'"></span>)
                                    </span>
                                </template>
                                <template x-if="item.delta === 0">
                                    <span class="text-slate-400">-</span>
                                </template>
                            </td>

                            {{-- New Margin % --}}
                            <td class="px-4 py-3 text-right font-mono text-xs whitespace-nowrap font-bold">
                                <span :class="item.is_below_cost ? 'text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 px-2 py-0.5 rounded-lg' : (item.new_margin >= 20 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600')">
                                    <span x-text="item.new_margin + '%'"></span>
                                    <template x-if="item.is_below_cost">
                                        <span class="ml-1 text-[10px]" title="Below Purchase Cost!">⚠️ Loss</span>
                                    </template>
                                </span>
                            </td>

                        </tr>
                    </template>

                    <template x-if="items.length === 0">
                        <tr>
                            <td colspan="9" class="px-4 py-16 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-slate-300 dark:text-slate-600 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    <p class="text-sm font-semibold">{{ __('messages.price_wizard_no_products') }}</p>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ============================================================
         CONFIRMATION MODAL & SUBMIT FORM
         ============================================================ --}}
    <div x-show="showModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div @click.away="showModal = false"
             class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-lg w-full p-6 space-y-5"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="scale-95 opacity-0"
             x-transition:enter-end="scale-100 opacity-100">

            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-violet-100 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 font-outfit">
                        {{ __('messages.price_wizard_confirm_title') }}
                    </h3>
                    <p class="text-xs text-slate-500">
                        Review the summary before applying batch price adjustments.
                    </p>
                </div>
            </div>

            {{-- Summary Stats in Modal --}}
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700 space-y-2 text-xs">
                <div class="flex justify-between">
                    <span class="text-slate-500">Products to update:</span>
                    <span class="font-bold font-mono text-slate-900 dark:text-slate-100" x-text="modifiedCount"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Target Field:</span>
                    <span class="font-bold text-violet-600 dark:text-violet-400" x-text="targetField.replace('_', ' ').toUpperCase()"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Price Increases:</span>
                    <span class="font-bold font-mono text-emerald-600" x-text="priceIncreases"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Price Decreases:</span>
                    <span class="font-bold font-mono text-rose-600" x-text="priceDecreases"></span>
                </div>
                <template x-if="belowCostWarnings > 0">
                    <div class="flex justify-between text-rose-600 font-bold pt-1 border-t border-rose-200 dark:border-rose-800">
                        <span>⚠️ Below Purchase Cost Warnings:</span>
                        <span class="font-mono" x-text="belowCostWarnings"></span>
                    </div>
                </template>
            </div>

            {{-- Actual POST Form --}}
            <form method="POST" action="{{ route('store.admin.price_wizard.apply', ['store_slug' => $store->slug]) }}" id="priceWizardForm">
                @csrf
                <input type="hidden" name="sync_variants" :value="syncVariants ? 1 : 0">
                <input type="hidden" name="set_old_price" :value="setOldPrice ? 1 : 0">

                {{-- Hidden fields dynamically populated --}}
                <div id="hiddenItemsContainer"></div>

                <div class="flex items-center justify-end gap-2.5 pt-2">
                    <button type="button"
                            @click="showModal = false"
                            class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="button"
                            @click="submitForm()"
                            class="px-5 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-500 text-white text-xs font-bold shadow-md transition flex items-center gap-2">
                        <span>{{ __('messages.price_wizard_confirm_btn') }}</span>
                    </button>
                </div>
            </form>

        </div>
    </div>

</div>
@endsection
