@extends('layouts.admin.app')

@section('title', __('messages.register_new_warranty') . ' - ' . ($store->name ?? 'DataPOS'))

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="{
    selectedProductId: '',
    productName: '',
    customerType: 'manual',
    customerId: '',
    customerName: '',
    customerPhone: '',
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
                <span class="text-slate-700 dark:text-slate-200 font-semibold">{{ __('messages.register_new_warranty') }}</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100 font-outfit mt-1">
                {{ __('messages.register_new_warranty') }}
            </h1>
        </div>
        <a href="{{ route('store.admin.warranty.index', ['store_slug' => $store->slug]) }}"
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

    <form method="POST" action="{{ route('store.admin.warranty.store', ['store_slug' => $store->slug]) }}" class="space-y-6">
        @csrf

        {{-- Device Details Card --}}
        <div class="p-6 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-4">
            <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider font-outfit">
                {{ __('messages.device_and_serial_info') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Product Select --}}
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

                {{-- Product Name Snapshot --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.product_name') }} *</label>
                    <input type="text"
                           name="product_name"
                           x-model="productName"
                           required
                           placeholder="e.g. iPhone 15 Pro Max 256GB"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                </div>

                {{-- Serial Number (Required) --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Serial Number (SN) *</label>
                    <input type="text"
                           name="serial_number"
                           value="{{ old('serial_number') }}"
                           required
                           placeholder="e.g. F2LZ90K8MD6M"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 font-mono font-bold">
                </div>

                {{-- Primary IMEI --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Primary IMEI / IMEI 1</label>
                    <input type="text"
                           name="imei_primary"
                           value="{{ old('imei_primary') }}"
                           placeholder="e.g. 356789123456789"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 font-mono">
                </div>

                {{-- Secondary IMEI --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Secondary IMEI / IMEI 2</label>
                    <input type="text"
                           name="imei_secondary"
                           value="{{ old('imei_secondary') }}"
                           placeholder="e.g. 356789123456780"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 font-mono">
                </div>

                {{-- Invoice Number --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Invoice / Receipt No.</label>
                    <input type="text"
                           name="invoice_number"
                           value="{{ old('invoice_number') }}"
                           placeholder="e.g. INV-2026-0042"
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
                {{-- Purchase Date --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.purchase_date') }} *</label>
                    <input type="date"
                           name="purchase_date"
                           value="{{ old('purchase_date', now()->toDateString()) }}"
                           required
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                </div>

                {{-- Duration in Months --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.warranty_duration') }} *</label>
                    <select name="warranty_duration_months"
                            class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        <option value="1">1 Month (၁ လ)</option>
                        <option value="3">3 Months (၃ လ)</option>
                        <option value="6">6 Months (၆ လ)</option>
                        <option value="12" selected>12 Months / 1 Year (၁ နှစ်)</option>
                        <option value="24">24 Months / 2 Years (၂ နှစ်)</option>
                        <option value="36">36 Months / 3 Years (၃ နှစ်)</option>
                    </select>
                </div>

                {{-- Warranty Type --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.warranty_type') }} *</label>
                    <select name="warranty_type"
                            class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        <option value="shop" selected>{{ __('messages.warranty_type_shop') }}</option>
                        <option value="official_brand">{{ __('messages.warranty_type_official') }}</option>
                        <option value="distributor">{{ __('messages.warranty_type_distributor') }}</option>
                        <option value="service_only">{{ __('messages.warranty_type_service_only') }}</option>
                    </select>
                </div>

                {{-- Initial Status --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.warranty_status') }} *</label>
                    <select name="status"
                            class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 font-bold">
                        <option value="active" selected class="text-emerald-600">Active (အာမခံ အကျုံးဝင်)</option>
                        <option value="expired" class="text-rose-600">Expired (အာမခံ ကုန်ဆုံး)</option>
                        <option value="void" class="text-slate-500">Void (အာမခံ ပျက်ပြယ်)</option>
                    </select>
                </div>
            </div>

            {{-- Terms & Conditions Textarea --}}
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.warranty_terms_conditions') }}</label>
                <textarea name="terms_conditions"
                          rows="2"
                          placeholder="e.g. ရေဝင်ခြင်း၊ ပြုတ်ကျခြင်း၊ Display ကွဲအက်ခြင်းနှင့် တရားမဝင် ဆော့ဝဲလ်တင်ထားခြင်းများ အာမခံ မပါဝင်ပါ။"
                          class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">{{ old('terms_conditions') }}</textarea>
            </div>
        </div>

        {{-- Customer Info Card --}}
        <div class="p-6 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-4">
            <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider font-outfit">
                {{ __('messages.customer_information') }}
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                {{-- Existing Customer Select --}}
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

                {{-- Customer Name --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.customer_name') }}</label>
                    <input type="text"
                           name="customer_name"
                           x-model="customerName"
                           placeholder="e.g. ဦးအောင်ကျော်"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                </div>

                {{-- Customer Phone --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.phone') }}</label>
                    <input type="text"
                           name="customer_phone"
                           x-model="customerPhone"
                           placeholder="e.g. 09123456789"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 font-mono">
                </div>
            </div>

            {{-- Internal Notes --}}
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.internal_notes') }}</label>
                <input type="text"
                       name="notes"
                       value="{{ old('notes') }}"
                       placeholder="e.g. Gift tempered glass and case included"
                       class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
            </div>
        </div>

        {{-- Submit Button --}}
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('store.admin.warranty.index', ['store_slug' => $store->slug]) }}"
               class="px-5 py-2.5 text-sm font-semibold rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 transition">
                {{ __('messages.cancel') }}
            </a>
            <button type="submit"
                    class="px-6 py-2.5 text-sm font-extrabold rounded-xl bg-violet-600 hover:bg-violet-500 text-white shadow-lg shadow-violet-600/30 transition transform active:scale-95">
                {{ __('messages.save_warranty_card') }}
            </button>
        </div>
    </form>
</div>
@endsection
