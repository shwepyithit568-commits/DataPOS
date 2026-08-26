@extends('layouts.admin.app')

@section('title', __('messages.price_wizard_title') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

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
                    var retailRef = item.current_retail;
                    calculated = retailRef * (1 - (this.calcValue / 100));
                    break;
            }

            calculated = this.applyRounding(calculated, this.roundingRule);
            calculated = Math.max(0, calculated);

            item.new_price = Math.round(calculated);
            item.delta = Math.round((item.new_price - basePrice) * 100) / 100;
            item.delta_percent = basePrice > 0 ? Math.round((item.delta / basePrice) * 1000) / 10 : 0;
            item.new_margin = item.new_price > 0 && cost > 0 ? Math.round(((item.new_price - cost) / item.new_price) * 1000) / 10 : 0;
            item.is_below_cost = cost > 0 && item.new_price < cost;
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
                    var floored = Math.floor(val / 1000) * 1000;
                    return floored + 900;
                case 'charm_990':
                    var floored100 = Math.floor(val / 1000) * 1000;
                    return floored100 + 990;
                case 'none':
                default:
                    return Math.round(val);
            }
        },

        formatCurrency: function (num) {
            if (num === null || num === undefined || isNaN(num)) return '0';
            return Number(num).toLocaleString('en-US');
        },

        openConfirmModal: function () {
            if (this.modifiedCount === 0) {
                alert('No price changes detected to apply.');
                return;
            }
            this.showModal = true;
        },

        submitForm: function () {
            var container = document.getElementById('hiddenItemsContainer');
            container.innerHTML = '';

            var selectedItems = this.items.filter(function (i) { return i.selected && i.is_modified; });
            var target = this.targetField;

            selectedItems.forEach(function (item, idx) {
                var pIdInput = document.createElement('input');
                pIdInput.type = 'hidden';
                pIdInput.name = 'items[' + idx + '][product_id]';
                pIdInput.value = item.id;
                container.appendChild(pIdInput);

                if (target === 'retail_price') {
                    var retailInput = document.createElement('input');
                    retailInput.type = 'hidden';
                    retailInput.name = 'items[' + idx + '][retail_price]';
                    retailInput.value = item.new_price;
                    container.appendChild(retailInput);
                } else if (target === 'wholesale_price') {
                    var wsInput = document.createElement('input');
                    wsInput.type = 'hidden';
                    wsInput.name = 'items[' + idx + '][wholesale_price]';
                    wsInput.value = item.new_price;
                    container.appendChild(wsInput);
                } else if (target === 'old_price') {
                    var oldInput = document.createElement('input');
                    oldInput.type = 'hidden';
                    oldInput.name = 'items[' + idx + '][old_price]';
                    oldInput.value = item.new_price;
                    container.appendChild(oldInput);
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
    class="w-full space-y-2 sm:space-y-2.5"
>

    {{-- ============================================================
         1. COMPACT HERO PAGE HEADER
         ============================================================ --}}
    <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">
        <div class="min-w-0">
            <div class="flex items-center gap-1.5 mb-0.5">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-violet-50 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 border border-violet-200 dark:border-violet-800">
                    <span>🏷️</span>
                    <span>{{ __('messages.sidebar_price_wizard') ?? 'Price Wizard' }}</span>
                </span>
                <span class="text-slate-300 dark:text-slate-700">/</span>
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 truncate">{{ $store->name }}</span>
            </div>
            <h1 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
                <span>{{ __('messages.price_wizard_title') }}</span>
                <span class="text-xs font-mono font-bold text-slate-400">({{ number_format(count($products)) }})</span>
            </h1>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 truncate">{{ __('messages.price_wizard_subtitle') }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-1.5 shrink-0">
            {{-- Export CSV --}}
            <a href="{{ route('store.admin.price_wizard.export', array_merge(['store_slug' => $store->slug], request()->all())) }}"
               class="px-2.5 py-1.5 text-xs font-bold rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 shadow-2xs transition flex items-center gap-1">
                <span>📊</span>
                <span class="hidden sm:inline">{{ __('messages.price_wizard_export_csv') }}</span>
            </a>

            {{-- Primary Apply Changes Button (triggers modal) --}}
            <button type="button"
                    @click="openConfirmModal()"
                    :disabled="selectedCount === 0 || modifiedCount === 0"
                    class="px-3 py-1.5 rounded-lg text-xs font-black bg-violet-600 hover:bg-violet-700 text-white shadow-2xs transition flex items-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed">
                <span>💾</span>
                <span>{{ __('messages.price_wizard_apply_changes') }} (<span x-text="modifiedCount"></span>)</span>
            </button>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if (session('success'))
        <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 rounded-lg text-xs text-emerald-800 dark:text-emerald-200 flex items-center gap-2 shadow-2xs font-semibold">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 rounded-lg text-xs text-rose-800 dark:text-rose-200 shadow-2xs">
            <div class="font-bold mb-1">Please fix the following issues:</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ============================================================
         2. KPI SUMMARY CARDS (4-UP COMPACT)
         ============================================================ --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-2.5">
        {{-- Total Products --}}
        <div class="p-2.5 sm:p-3 rounded-lg border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xs">
            <div class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider flex items-center gap-1">
                <span>📦</span>
                <span>{{ __('messages.price_wizard_stat_total_products') }}</span>
            </div>
            <div class="text-base sm:text-lg font-black text-slate-900 dark:text-slate-100 font-mono mt-0.5">
                {{ number_format($stats['total_products']) }}
            </div>
            <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5 truncate">
                {{ number_format(count($products)) }} matched in filter
            </div>
        </div>

        {{-- With Purchase Cost --}}
        <div class="p-2.5 sm:p-3 rounded-lg border border-emerald-200/80 dark:border-emerald-900/60 bg-emerald-50/40 dark:bg-emerald-950/20 shadow-2xs">
            <div class="text-[11px] font-bold text-emerald-700 dark:text-emerald-300 uppercase tracking-wider flex items-center gap-1">
                <span>💵</span>
                <span>{{ __('messages.price_wizard_stat_with_cost') }}</span>
            </div>
            <div class="text-base sm:text-lg font-black text-emerald-600 dark:text-emerald-400 font-mono mt-0.5">
                {{ number_format($stats['with_cost_count']) }}
            </div>
            <div class="text-[10px] text-emerald-600/80 dark:text-emerald-400/80 mt-0.5 truncate">
                {{ number_format($stats['zero_cost_count']) }} {{ __('messages.price_wizard_stat_zero_cost') }}
            </div>
        </div>

        {{-- Avg Retail Margin --}}
        <div class="p-2.5 sm:p-3 rounded-lg border border-violet-200/80 dark:border-violet-900/60 bg-violet-50/40 dark:bg-violet-950/20 shadow-2xs">
            <div class="text-[11px] font-bold text-violet-700 dark:text-violet-300 uppercase tracking-wider flex items-center gap-1">
                <span>📈</span>
                <span>{{ __('messages.price_wizard_stat_avg_margin') }}</span>
            </div>
            <div class="text-base sm:text-lg font-black text-violet-600 dark:text-violet-400 font-mono mt-0.5">
                {{ $stats['avg_margin'] }}%
            </div>
            <div class="text-[10px] text-violet-600/80 dark:text-violet-400/80 mt-0.5 truncate">
                Based on cost & retail
            </div>
        </div>

        {{-- Below Cost Warning --}}
        <div class="p-2.5 sm:p-3 rounded-lg border shadow-2xs {{ $stats['below_cost_count'] > 0 ? 'border-rose-200/80 dark:border-rose-900/60 bg-rose-50/40 dark:bg-rose-950/20' : 'border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900' }}">
            <div class="text-[11px] font-bold uppercase tracking-wider flex items-center gap-1 {{ $stats['below_cost_count'] > 0 ? 'text-rose-700 dark:text-rose-300' : 'text-slate-500' }}">
                <span>⚠️</span>
                <span>{{ __('messages.price_wizard_stat_below_cost') }}</span>
            </div>
            <div class="text-base sm:text-lg font-black font-mono mt-0.5 {{ $stats['below_cost_count'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-700 dark:text-slate-200' }}">
                {{ number_format($stats['below_cost_count']) }}
            </div>
            <div class="text-[10px] mt-0.5 truncate {{ $stats['below_cost_count'] > 0 ? 'text-rose-600/80 dark:text-rose-400/80 font-bold' : 'text-slate-400' }}">
                {{ $stats['below_cost_count'] > 0 ? 'Requires attention' : 'All healthy' }}
            </div>
        </div>
    </div>

    {{-- ============================================================
         3. PRICING STRATEGY WIZARD CONTROL PANEL (COMPACT SECTION)
         ============================================================ --}}
    <section class="w-full rounded-lg bg-gradient-to-r from-violet-500/5 via-indigo-500/5 to-purple-500/5 dark:from-violet-950/30 dark:via-indigo-950/20 dark:to-purple-950/30 border border-violet-200/80 dark:border-violet-800/60 p-3 sm:p-3.5 shadow-2xs space-y-2.5">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-2 border-b border-violet-100 dark:border-violet-900/60 pb-2">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 rounded-lg bg-violet-600 text-white grid place-items-center text-xs font-black shadow-xs">
                    ⚡
                </span>
                <div>
                    <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100">
                        {{ __('messages.price_wizard_calc_strategy') }}
                    </h2>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                        Select target field, pricing formula, and rounding rules to instantly calculate new prices.
                    </p>
                </div>
            </div>

            {{-- Quick Preset Buttons --}}
            <div class="flex flex-wrap items-center gap-1">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider mr-0.5">Quick Markup:</span>
                <template x-for="pct in [10, 15, 20, 25, 30, 40, 50]" :key="pct">
                    <button type="button"
                            @click="setQuickMarkup(pct)"
                            class="px-2 py-1 rounded-md text-xs font-mono font-bold bg-white dark:bg-slate-800 border border-violet-200 dark:border-violet-700 text-violet-700 dark:text-violet-300 hover:bg-violet-600 hover:text-white dark:hover:bg-violet-600 dark:hover:text-white transition shadow-2xs"
                            x-text="'+' + pct + '%'">
                    </button>
                </template>
            </div>
        </div>

        {{-- Strategy Form Controls --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5">
            {{-- 1. Target Price Field --}}
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                    {{ __('messages.price_wizard_target_price') }}
                </label>
                <select x-model="targetField"
                        @change="recalculateAll()"
                        class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-violet-500 cursor-pointer shadow-2xs">
                    <option value="retail_price">{{ __('messages.price_wizard_retail_price') }}</option>
                    <option value="wholesale_price">{{ __('messages.price_wizard_wholesale_price') }}</option>
                    <option value="old_price">{{ __('messages.price_wizard_compare_at_price') }}</option>
                </select>
            </div>

            {{-- 2. Calculation Mode --}}
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                    {{ __('messages.price_wizard_calc_strategy') }}
                </label>
                <select x-model="calcMode"
                        @change="recalculateAll()"
                        class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-violet-500 cursor-pointer shadow-2xs">
                    <option value="markup_on_cost">{{ __('messages.price_wizard_mode_markup') }}</option>
                    <option value="margin_on_cost">{{ __('messages.price_wizard_mode_margin') }}</option>
                    <option value="percentage_on_current">{{ __('messages.price_wizard_mode_percentage') }}</option>
                    <option value="fixed_amount_on_current">{{ __('messages.price_wizard_mode_fixed_amount') }}</option>
                    <option value="fixed_price">{{ __('messages.price_wizard_mode_fixed_price') }}</option>
                    <option value="wholesale_from_retail">{{ __('messages.price_wizard_mode_wholesale_derive') }}</option>
                </select>
            </div>

            {{-- 3. Adjustment Value --}}
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 truncate">
                    <span x-text="getValueLabel()"></span>
                </label>
                <div class="relative">
                    <input type="number"
                           step="any"
                           x-model.number="calcValue"
                           @input="recalculateAll()"
                           class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-2.5 py-1.5 pr-10 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-mono font-bold focus:ring-2 focus:ring-violet-500 shadow-2xs">
                    <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400"
                          x-text="isPercentageMode() ? '%' : 'Ks'"></span>
                </div>
            </div>

            {{-- 4. Rounding Rule --}}
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                    {{ __('messages.price_wizard_rounding') }}
                </label>
                <select x-model="roundingRule"
                        @change="recalculateAll()"
                        class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-violet-500 cursor-pointer shadow-2xs">
                    <option value="none">{{ __('messages.price_wizard_round_none') }}</option>
                    <option value="round_10">{{ __('messages.price_wizard_round_10') }}</option>
                    <option value="round_50">{{ __('messages.price_wizard_round_50') }}</option>
                    <option value="round_100">{{ __('messages.price_wizard_round_100') }}</option>
                    <option value="round_500">{{ __('messages.price_wizard_round_500') }}</option>
                    <option value="round_1000">{{ __('messages.price_wizard_round_1000') }}</option>
                    <option value="charm_900">{{ __('messages.price_wizard_round_charm_900') }}</option>
                    <option value="charm_990">{{ __('messages.price_wizard_round_charm_990') }}</option>
                </select>
            </div>
        </div>

        {{-- Selection Helpers & Checkbox Options --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pt-1 border-t border-violet-100 dark:border-violet-900/40">
            <div class="flex flex-wrap items-center gap-1.5">
                <button type="button"
                        @click="selectAll(true)"
                        class="px-2 py-1 rounded-md text-xs font-bold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 transition shadow-2xs">
                    {{ __('messages.price_wizard_select_all') }}
                </button>
                <button type="button"
                        @click="selectAll(false)"
                        class="px-2 py-1 rounded-md text-xs font-bold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 transition shadow-2xs">
                    {{ __('messages.price_wizard_deselect_all') }}
                </button>
                <button type="button"
                        @click="invertSelection()"
                        class="px-2 py-1 rounded-md text-xs font-bold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 transition shadow-2xs">
                    {{ __('messages.price_wizard_invert_selection') }}
                </button>
                <span class="text-xs font-bold text-slate-500 font-mono ml-1">
                    <span x-text="selectedCount"></span> / <span x-text="items.length"></span> selected
                </span>
            </div>

            <div class="flex flex-wrap items-center gap-3 text-xs font-bold text-slate-700 dark:text-slate-300">
                <label class="flex items-center gap-1.5 cursor-pointer">
                    <input type="checkbox" x-model="syncVariants" class="rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                    <span>{{ __('messages.price_wizard_sync_variants') }}</span>
                </label>
                <label class="flex items-center gap-1.5 cursor-pointer">
                    <input type="checkbox" x-model="setOldPrice" class="rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                    <span>{{ __('messages.price_wizard_set_old_price') }}</span>
                </label>
            </div>
        </div>
    </section>

    {{-- ============================================================
         4. COMPACT FILTER TOOLBAR
         ============================================================ --}}
    <div class="p-2 sm:p-2.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/90 dark:border-slate-800 shadow-2xs">
        <form method="GET" action="{{ route('store.admin.price_wizard.index', ['store_slug' => $store->slug]) }}">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-2 items-center">
                {{-- Search --}}
                <div class="relative lg:col-span-2">
                    <input type="text"
                           name="search"
                           value="{{ $filters['search'] ?? '' }}"
                           placeholder="{{ __('messages.search') }} product, SKU..."
                           class="w-full pl-8 pr-3 py-1.5 border border-slate-200 dark:border-slate-700 rounded-lg text-xs bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500 shadow-2xs font-semibold">
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                {{-- Category --}}
                <select name="category_id" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-violet-500 cursor-pointer shadow-2xs">
                    <option value="">{{ __('messages.all_categories') ?? 'All Categories' }}</option>
                    @foreach($filterOptions['categories'] as $cat)
                        <option value="{{ $cat->id }}" {{ ($filters['category_id'] ?? '') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }} ({{ $cat->products_count }})
                        </option>
                    @endforeach
                </select>

                {{-- Brand --}}
                <select name="brand_id" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-violet-500 cursor-pointer shadow-2xs">
                    <option value="">{{ __('messages.all_brands') ?? 'All Brands' }}</option>
                    @foreach($filterOptions['brands'] as $br)
                        <option value="{{ $br->id }}" {{ ($filters['brand_id'] ?? '') == $br->id ? 'selected' : '' }}>
                            {{ $br->name }} ({{ $br->products_count }})
                        </option>
                    @endforeach
                </select>

                {{-- Cost Filter Status --}}
                <select name="cost_filter" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-violet-500 cursor-pointer shadow-2xs">
                    <option value="">{{ __('messages.price_wizard_filter_cost_all') }}</option>
                    <option value="with_cost" {{ ($filters['cost_filter'] ?? '') === 'with_cost' ? 'selected' : '' }}>
                        {{ __('messages.price_wizard_filter_with_cost') }}
                    </option>
                    <option value="zero_cost" {{ ($filters['cost_filter'] ?? '') === 'zero_cost' ? 'selected' : '' }}>
                        {{ __('messages.price_wizard_filter_zero_cost') }}
                    </option>
                    <option value="below_cost" {{ ($filters['cost_filter'] ?? '') === 'below_cost' ? 'selected' : '' }}>
                        {{ __('messages.price_wizard_filter_below_cost') }}
                    </option>
                </select>

                {{-- Submit & Reset --}}
                <div class="flex items-center gap-1 justify-end">
                    @if(!empty($filters['search']) || !empty($filters['category_id']) || !empty($filters['brand_id']) || !empty($filters['supplier_id']) || !empty($filters['cost_filter']))
                        <a href="{{ route('store.admin.price_wizard.index', ['store_slug' => $store->slug]) }}"
                           class="px-2.5 py-1.5 text-xs font-bold rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 transition shadow-2xs">
                            {{ __('messages.reset') }}
                        </a>
                    @endif
                    <button type="submit"
                            class="px-3.5 py-1.5 bg-violet-600 hover:bg-violet-700 text-white rounded-lg text-xs font-bold shadow-2xs flex items-center gap-1 transition">
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
         5. SPREADSHEET DATA GRID TABLE (GOOGLE SHEETS STYLE)
         ============================================================ --}}
    <div id="data-table" class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden transition">
        {{-- Live Change Summary Bar --}}
        <div class="p-2 sm:p-2.5 bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <div class="text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                <span>📊</span>
                <span>Live Pricing Preview (<span x-text="items.length"></span>)</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-mono text-emerald-600 dark:text-emerald-400 font-bold" x-show="priceIncreases > 0">
                    +<span x-text="priceIncreases"></span> increases
                </span>
                <span class="text-xs font-mono text-rose-600 dark:text-rose-400 font-bold" x-show="priceDecreases > 0">
                    -<span x-text="priceDecreases"></span> decreases
                </span>
                <span class="text-[11px] font-mono px-2 py-0.5 rounded-md bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 font-bold" x-show="belowCostWarnings > 0">
                    ⚠️ <span x-text="belowCostWarnings"></span> below cost
                </span>
            </div>
        </div>

        <div class="overflow-x-auto max-h-[70vh] overflow-y-auto divide-y divide-slate-200 dark:divide-slate-800">
            <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                {{-- Sticky Header --}}
                <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs border-b-2 border-slate-300 dark:border-slate-600 shadow-2xs select-none">
                    <tr class="text-[11px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider divide-x divide-slate-300 dark:divide-slate-700">
                        <th class="p-2.5 w-10 text-center">
                            <input type="checkbox"
                                   @change="toggleSelectAll($event.target.checked)"
                                   :checked="selectedCount === items.length && items.length > 0"
                                   class="rounded border-slate-300 text-violet-600 focus:ring-violet-500 cursor-pointer">
                        </th>
                        <th class="py-2.5 px-3 min-w-[200px]">{{ __('messages.product') }}</th>
                        <th class="py-2.5 px-3 text-right min-w-[110px]">{{ __('messages.price_wizard_current_cost') }}</th>
                        <th class="py-2.5 px-3 text-right min-w-[120px]">{{ __('messages.price_wizard_current_retail') }}</th>
                        <th class="py-2.5 px-3 text-right min-w-[120px]">{{ __('messages.price_wizard_current_wholesale') }}</th>
                        <th class="py-2.5 px-3 text-right min-w-[90px]">{{ __('messages.price_wizard_current_margin') }}</th>
                        <th class="py-2.5 px-3 text-right min-w-[140px] font-black text-violet-700 dark:text-violet-300 bg-violet-50/70 dark:bg-violet-950/40">
                            {{ __('messages.price_wizard_new_price') }}
                        </th>
                        <th class="py-2.5 px-3 text-right min-w-[130px]">{{ __('messages.price_wizard_difference') }}</th>
                        <th class="py-2.5 px-3 text-right min-w-[90px]">{{ __('messages.price_wizard_new_margin') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/90 dark:divide-slate-800 bg-white dark:bg-slate-900">
                    <template x-for="(item, index) in items" :key="item.id">
                        <tr :class="item.selected ? 'hover:bg-slate-50/80 dark:hover:bg-slate-800/50' : 'bg-slate-50/50 dark:bg-slate-950/50 opacity-60'"
                            class="divide-x divide-slate-200/80 dark:divide-slate-800 transition">

                            {{-- Row Checkbox --}}
                            <td class="p-2.5 text-center">
                                <input type="checkbox"
                                       x-model="item.selected"
                                       @change="onItemSelectionChange(item)"
                                       class="rounded border-slate-300 text-violet-600 focus:ring-violet-500 cursor-pointer">
                            </td>

                            {{-- Product Name & SKU --}}
                            <td class="py-2 px-3">
                                <div class="font-bold text-slate-900 dark:text-slate-100 leading-tight" x-text="item.name"></div>
                                <div class="flex items-center gap-1.5 text-[10px] text-slate-400 mt-0.5 font-mono">
                                    <span>SKU: <span x-text="item.sku"></span></span>
                                    <span>•</span>
                                    <span x-text="item.category_name"></span>
                                </div>
                            </td>

                            {{-- Purchase Cost --}}
                            <td class="py-2 px-3 text-right font-mono font-semibold text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                <span x-text="formatCurrency(item.cost)"></span> <span class="text-[10px]">Ks</span>
                            </td>

                            {{-- Current Retail --}}
                            <td class="py-2 px-3 text-right font-mono text-xs text-slate-800 dark:text-slate-200 font-bold whitespace-nowrap">
                                <span x-text="formatCurrency(item.current_retail)"></span> <span class="text-[10px] text-slate-400">Ks</span>
                            </td>

                            {{-- Current Wholesale --}}
                            <td class="py-2 px-3 text-right font-mono text-xs text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                <span x-text="formatCurrency(item.current_wholesale)"></span> <span class="text-[10px] text-slate-400">Ks</span>
                            </td>

                            {{-- Current Margin --}}
                            <td class="py-2 px-3 text-right font-mono font-bold text-xs whitespace-nowrap"
                                :class="item.current_margin >= 20 ? 'text-emerald-600 dark:text-emerald-400' : (item.current_margin > 0 ? 'text-amber-600' : 'text-slate-400')">
                                <span x-text="item.current_margin + '%'"></span>
                            </td>

                            {{-- New Calculated Price (INLINE EDITABLE!) --}}
                            <td class="py-1.5 px-3 text-right whitespace-nowrap bg-violet-50/40 dark:bg-violet-950/20">
                                <div class="relative inline-block w-32">
                                    <input type="number"
                                           step="any"
                                           x-model.number="item.new_price"
                                           @input="onManualPriceChange(item)"
                                           :disabled="!item.selected"
                                           class="w-full text-right font-mono font-black text-xs px-2 py-1 rounded-md border focus:ring-2 focus:ring-violet-500 shadow-inner transition
                                               bg-white dark:bg-slate-800 text-violet-900 dark:text-violet-200 border-violet-300 dark:border-violet-700">
                                </div>
                            </td>

                            {{-- Difference / Delta --}}
                            <td class="py-2 px-3 text-right font-mono text-xs whitespace-nowrap">
                                <template x-if="item.delta > 0">
                                    <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[11px] bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 font-bold">
                                        +<span x-text="formatCurrency(item.delta)"></span>
                                        (<span x-text="item.delta_percent + '%'"></span>)
                                    </span>
                                </template>
                                <template x-if="item.delta < 0">
                                    <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[11px] bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 font-bold">
                                        <span x-text="formatCurrency(item.delta)"></span>
                                        (<span x-text="item.delta_percent + '%'"></span>)
                                    </span>
                                </template>
                                <template x-if="item.delta === 0">
                                    <span class="text-slate-400">-</span>
                                </template>
                            </td>

                            {{-- New Margin % --}}
                            <td class="py-2 px-3 text-right font-mono text-xs whitespace-nowrap font-black">
                                <span :class="item.is_below_cost ? 'text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 px-1.5 py-0.5 rounded' : (item.new_margin >= 20 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600')">
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
                            <td colspan="9" class="p-8 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center">
                                    <span class="text-3xl mb-2">🏷️</span>
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
         6. CONFIRMATION MODAL & SUBMIT FORM
         ============================================================ --}}
    <div x-show="showModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div @click.away="showModal = false"
             class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 shadow-2xl max-w-lg w-full p-4 sm:p-5 space-y-3"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="scale-95 opacity-0"
             x-transition:enter-end="scale-100 opacity-100">

            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-violet-100 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 grid place-items-center text-sm font-black">
                        ⚠️
                    </span>
                    <div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-slate-100">
                            {{ __('messages.price_wizard_confirm_title') }}
                        </h3>
                        <p class="text-[11px] text-slate-500">Review the summary before applying batch price adjustments.</p>
                    </div>
                </div>
                <button type="button" @click="showModal = false" class="text-slate-400 hover:text-slate-600 text-base font-bold">&times;</button>
            </div>

            {{-- Summary Stats in Modal --}}
            <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700 space-y-1.5 text-xs">
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
                    <div class="flex justify-between text-rose-600 font-bold pt-1.5 border-t border-rose-200 dark:border-rose-800">
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

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button"
                            @click="showModal = false"
                            class="px-3.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="button"
                            @click="submitForm()"
                            class="px-4 py-1.5 rounded-lg bg-violet-600 hover:bg-violet-700 text-white text-xs font-black shadow-2xs transition flex items-center gap-1.5">
                        <span>💾</span>
                        <span>{{ __('messages.price_wizard_confirm_btn') }}</span>
                    </button>
                </div>
            </form>

        </div>
    </div>

</div>
@endsection
