@extends('layouts.admin.app')

@section('title', __('messages.edit_warranty') . ' - ' . $warranty->serial_number)

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="{
    selectedProductId: '{{ $warranty->product_id }}',
    productName: '{{ addslashes($warranty->product_name) }}',
    customerId: '{{ $warranty->customer_id }}',
    customerName: '{{ addslashes($warranty->customer_name ?? '') }}',
    customerPhone: '{{ addslashes($warranty->customer_phone ?? '') }}',
    onProductChange(e) {
        const opt = e.target.selectedOptions[0];
        if (opt && opt.value) {
            this.productName = opt.dataset.name || '';
        }
    },
    onCustomerChange(e) {
        const opt = e.target.selectedOptions[0];
        if (opt && opt.value) {
            this.customerName = opt.dataset.name || '';
            this.customerPhone = opt.dataset.phone || '';
        }
    }
}">

    {{-- Breadcrumbs & Header --}}
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                <a href="{{ route('store.admin.warranty.index', ['store_slug' => $store->slug]) }}" class="hover:text-violet-600 dark:hover:text-violet-400">{{ __('messages.sidebar_warranty') }}</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-200 font-semibold">{{ __('messages.edit_warranty') }}</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100 font-outfit mt-1">
                {{ __('messages.edit_warranty') }}
            </h1>
        </div>
        <a href="{{ route('store.admin.warranty.show', ['store_slug' => $store->slug, 'warranty' => $warranty->id]) }}"
           class="px-4 py-2 text-xs sm:text-sm font-semibold rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 transition">
            &larr; {{ __('messages.back') }}
        </a>
    </div>

    @if ($errors->any())
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 dark:bg-rose-950/40 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('store.admin.warranty.update', ['store_slug' => $store->slug, 'warranty' => $warranty->id]) }}" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Device Details Card --}}
        <div class="p-6 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-4">
            <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider font-outfit">
                {{ __('messages.device_and_serial_info') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.product') }}</label>
                    <select name="product_id"
                            x-model="selectedProductId"
                            @change="onProductChange($event)"
                            class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        <option value="">-- {{ __('messages.select_product_or_manual') }} --</option>
                        @foreach ($products as $p)
                            <option value="{{ $p->id }}" data-name="{{ $p->name }}">{{ $p->name }} (SKU: {{ $p->sku ?: '-' }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.product_name') }} *</label>
                    <input type="text"
                           name="product_name"
                           x-model="productName"
                           required
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Serial Number (SN) *</label>
                    <input type="text"
                           name="serial_number"
                           value="{{ old('serial_number', $warranty->serial_number) }}"
                           required
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 font-mono font-bold">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Primary IMEI / IMEI 1</label>
                    <input type="text"
                           name="imei_primary"
                           value="{{ old('imei_primary', $warranty->imei_primary) }}"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Secondary IMEI / IMEI 2</label>
                    <input type="text"
                           name="imei_secondary"
                           value="{{ old('imei_secondary', $warranty->imei_secondary) }}"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Invoice / Receipt No.</label>
                    <input type="text"
                           name="invoice_number"
                           value="{{ old('invoice_number', $warranty->invoice_number) }}"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 font-mono">
                </div>
            </div>
        </div>

        {{-- Warranty Policy & Terms Card --}}
        <div class="p-6 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-4">
            <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider font-outfit">
                {{ __('messages.warranty_terms_and_policy') }}
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.purchase_date') }} *</label>
                    <input type="date"
                           name="purchase_date"
                           value="{{ old('purchase_date', $warranty->purchase_date->toDateString()) }}"
                           required
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.warranty_duration') }} *</label>
                    <select name="warranty_duration_months"
                            class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        @foreach ([1, 3, 6, 12, 24, 36] as $m)
                            <option value="{{ $m }}" {{ $warranty->warranty_duration_months == $m ? 'selected' : '' }}>{{ $m }} Months</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.warranty_type') }} *</label>
                    <select name="warranty_type"
                            class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        <option value="shop" {{ $warranty->warranty_type === 'shop' ? 'selected' : '' }}>{{ __('messages.warranty_type_shop') }}</option>
                        <option value="official_brand" {{ $warranty->warranty_type === 'official_brand' ? 'selected' : '' }}>{{ __('messages.warranty_type_official') }}</option>
                        <option value="distributor" {{ $warranty->warranty_type === 'distributor' ? 'selected' : '' }}>{{ __('messages.warranty_type_distributor') }}</option>
                        <option value="service_only" {{ $warranty->warranty_type === 'service_only' ? 'selected' : '' }}>{{ __('messages.warranty_type_service_only') }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.warranty_status') }} *</label>
                    <select name="status"
                            class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 font-bold">
                        <option value="active" {{ $warranty->status === 'active' ? 'selected' : '' }} class="text-emerald-600">Active (အာမခံ အကျုံးဝင်)</option>
                        <option value="expired" {{ $warranty->status === 'expired' ? 'selected' : '' }} class="text-rose-600">Expired (အာမခံ ကုန်ဆုံး)</option>
                        <option value="claimed" {{ $warranty->status === 'claimed' ? 'selected' : '' }} class="text-indigo-600">Claimed (အာမခံ လဲလှယ်/ပြင်ဆင်ပြီး)</option>
                        <option value="void" {{ $warranty->status === 'void' ? 'selected' : '' }} class="text-slate-500">Void (အာမခံ ပျက်ပြယ်)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.warranty_terms_conditions') }}</label>
                <textarea name="terms_conditions"
                          rows="2"
                          class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">{{ old('terms_conditions', $warranty->terms_conditions) }}</textarea>
            </div>
        </div>

        {{-- Customer Info Card --}}
        <div class="p-6 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-4">
            <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider font-outfit">
                {{ __('messages.customer_information') }}
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.select_customer') }}</label>
                    <select name="customer_id"
                            x-model="customerId"
                            @change="onCustomerChange($event)"
                            class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        <option value="">-- {{ __('messages.select_customer_or_walkin') }} --</option>
                        @foreach ($customers as $c)
                            <option value="{{ $c->id }}" data-name="{{ $c->name }}" data-phone="{{ $c->phone }}">{{ $c->name }} ({{ $c->phone ?: '-' }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.customer_name') }}</label>
                    <input type="text"
                           name="customer_name"
                           x-model="customerName"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.phone') }}</label>
                    <input type="text"
                           name="customer_phone"
                           x-model="customerPhone"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 font-mono">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.internal_notes') }}</label>
                <textarea name="notes"
                          rows="3"
                          class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 font-mono">{{ old('notes', $warranty->notes) }}</textarea>
            </div>
        </div>

        {{-- Submit Button --}}
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('store.admin.warranty.show', ['store_slug' => $store->slug, 'warranty' => $warranty->id]) }}"
               class="px-5 py-2.5 text-sm font-semibold rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 transition">
                {{ __('messages.cancel') }}
            </a>
            <button type="submit"
                    class="px-6 py-2.5 text-sm font-extrabold rounded-xl bg-violet-600 hover:bg-violet-500 text-white shadow-lg shadow-violet-600/30 transition transform active:scale-95">
                {{ __('messages.update_warranty_card') }}
            </button>
        </div>
    </form>
</div>
@endsection
