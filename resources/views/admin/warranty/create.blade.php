@extends('layouts.admin.app')

@section('title', __('messages.register_new_warranty') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@section('content')
<div class="w-full space-y-2 sm:space-y-2.5" x-data="{
    selectedProductId: '',
    productName: '{{ old('product_name', '') }}',
    customerId: '{{ old('customer_id', '') }}',
    customerName: '{{ old('customer_name', '') }}',
    customerPhone: '{{ old('customer_phone', '') }}',
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

    {{-- ============================================================
         1. COMPACT HERO PAGE HEADER
         ============================================================ --}}
    <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-between gap-2.5">
        <div class="min-w-0">
            <div class="flex items-center gap-1.5 text-[11px] text-slate-400 dark:text-slate-500 mb-0.5">
                <a href="{{ route('store.admin.dashboard', ['store_slug' => $store->slug]) }}" class="hover:text-violet-600 dark:hover:text-violet-400 transition">{{ __('messages.admin_dashboard') }}</a>
                <span class="text-slate-300 dark:text-slate-700">/</span>
                <a href="{{ route('store.admin.warranty.index', ['store_slug' => $store->slug]) }}" class="hover:text-violet-600 dark:hover:text-violet-400 transition">{{ __('messages.sidebar_warranty') }}</a>
                <span class="text-slate-300 dark:text-slate-700">/</span>
                <span class="text-slate-700 dark:text-slate-200 font-bold">{{ __('messages.register_new_warranty') }}</span>
            </div>
            <h1 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                <span>🛡️</span>
                <span>{{ __('messages.register_new_warranty') }}</span>
            </h1>
        </div>
        <a href="{{ route('store.admin.warranty.index', ['store_slug' => $store->slug]) }}"
           class="px-2.5 py-1.5 text-xs font-bold rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 transition shadow-2xs flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>{{ __('messages.back') }}</span>
        </a>
    </div>

    @if ($errors->any())
        <div class="p-3 rounded-lg bg-rose-50 border border-rose-200 dark:bg-rose-950/40 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs shadow-2xs">
            <ul class="list-disc list-inside space-y-0.5 font-semibold">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('store.admin.warranty.store', ['store_slug' => $store->slug]) }}" class="space-y-2 sm:space-y-2.5">
        @csrf

        {{-- ============================================================
             SECTION 1: DEVICE & PRODUCT INFORMATION
             ============================================================ --}}
        <section class="w-full rounded-lg bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-3 sm:p-4 shadow-2xs space-y-3">
            <div class="border-b border-slate-100 dark:border-slate-800 pb-2.5 flex items-center gap-2">
                <span class="w-7 h-7 rounded-lg bg-violet-600 text-white grid place-items-center text-xs font-black shadow-xs">
                    1
                </span>
                <div>
                    <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100">{{ __('messages.device_product_info') }}</h2>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('messages.device_product_info_sub') }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                {{-- Existing Product Quick Selector --}}
                <div class="sm:col-span-2 lg:col-span-3">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.select_existing_product_optional') }}
                    </label>
                    <select name="product_id"
                            x-model="selectedProductId"
                            @change="onProductChange($event)"
                            class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 font-semibold focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500/40 outline-none transition cursor-pointer">
                        <option value="">-- {{ __('messages.manual_custom_product_entry') }} --</option>
                        @foreach($products as $prod)
                            <option value="{{ $prod->id }}" data-name="{{ $prod->name }}" {{ old('product_id') == $prod->id ? 'selected' : '' }}>
                                {{ $prod->name }} (SKU: {{ $prod->sku }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Product Name (Auto-filled or manual) --}}
                <div class="sm:col-span-2 lg:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.device_model_name') }} <span class="text-rose-500">*</span>
                    </label>
                    <input type="text"
                           name="product_name"
                           x-model="productName"
                           required
                           placeholder="e.g. iPhone 15 Pro Max 256GB Natural Titanium"
                           class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 font-bold focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500/40 outline-none transition">
                </div>

                {{-- Serial Number --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.serial_number') }} <span class="text-rose-500">*</span>
                    </label>
                    <input type="text"
                           name="serial_number"
                           value="{{ old('serial_number') }}"
                           required
                           placeholder="e.g. F2LZ90K8MD6M"
                           class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 font-mono font-bold uppercase focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500/40 outline-none transition">
                </div>

                {{-- Invoice Number --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.invoice_order_number') }}
                    </label>
                    <input type="text"
                           name="invoice_number"
                           value="{{ old('invoice_number') }}"
                           placeholder="e.g. INV-2026-0042"
                           class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 font-mono focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500/40 outline-none transition">
                </div>

                {{-- Primary IMEI --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.primary_imei_optional') }}
                    </label>
                    <input type="text"
                           name="imei_primary"
                           value="{{ old('imei_primary') }}"
                           placeholder="15-digit Primary IMEI"
                           class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 font-mono focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500/40 outline-none transition">
                </div>

                {{-- Secondary IMEI --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.secondary_imei_optional') }}
                    </label>
                    <input type="text"
                           name="imei_secondary"
                           value="{{ old('imei_secondary') }}"
                           placeholder="eSIM / Secondary IMEI"
                           class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 font-mono focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500/40 outline-none transition">
                </div>
            </div>
        </section>

        {{-- ============================================================
             SECTION 2: CUSTOMER INFORMATION
             ============================================================ --}}
        <section class="w-full rounded-lg bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-3 sm:p-4 shadow-2xs space-y-3">
            <div class="border-b border-slate-100 dark:border-slate-800 pb-2.5 flex items-center gap-2">
                <span class="w-7 h-7 rounded-lg bg-violet-600 text-white grid place-items-center text-xs font-black shadow-xs">
                    2
                </span>
                <div>
                    <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100">{{ __('messages.customer_information') }}</h2>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('messages.customer_information_sub') }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                {{-- Existing Customer Selector --}}
                <div class="sm:col-span-2 lg:col-span-3">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.select_existing_customer_optional') }}
                    </label>
                    <select name="customer_id"
                            x-model="customerId"
                            @change="onCustomerChange($event)"
                            class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 font-semibold focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500/40 outline-none transition cursor-pointer">
                        <option value="">-- {{ __('messages.manual_or_walkin_customer') }} --</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" data-name="{{ $c->name }}" data-phone="{{ $c->phone }}" {{ old('customer_id') == $c->id ? 'selected' : '' }}>
                                {{ $c->name }} ({{ $c->phone ?: 'No phone' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Customer Name --}}
                <div class="sm:col-span-1 lg:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.customer_name') }}
                    </label>
                    <input type="text"
                           name="customer_name"
                           x-model="customerName"
                           placeholder="e.g. U Mya Aung"
                           class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 font-semibold focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500/40 outline-none transition">
                </div>

                {{-- Customer Phone --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.customer_phone') }}
                    </label>
                    <input type="text"
                           name="customer_phone"
                           x-model="customerPhone"
                           placeholder="e.g. 09790123456"
                           class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 font-mono focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500/40 outline-none transition">
                </div>
            </div>
        </section>

        {{-- ============================================================
             SECTION 3: WARRANTY TERMS & VALIDITY
             ============================================================ --}}
        <section class="w-full rounded-lg bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-3 sm:p-4 shadow-2xs space-y-3">
            <div class="border-b border-slate-100 dark:border-slate-800 pb-2.5 flex items-center gap-2">
                <span class="w-7 h-7 rounded-lg bg-violet-600 text-white grid place-items-center text-xs font-black shadow-xs">
                    3
                </span>
                <div>
                    <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100">{{ __('messages.warranty_terms_validity') }}</h2>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('messages.warranty_terms_validity_sub') }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                {{-- Purchase Date --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.purchase_date') }} <span class="text-rose-500">*</span>
                    </label>
                    <input type="date"
                           name="purchase_date"
                           value="{{ old('purchase_date', date('Y-m-d')) }}"
                           required
                           class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 font-semibold focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500/40 outline-none transition">
                </div>

                {{-- Duration in Months --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.warranty_duration_months') }} <span class="text-rose-500">*</span>
                    </label>
                    <input type="number"
                           name="warranty_duration_months"
                           value="{{ old('warranty_duration_months', 12) }}"
                           min="1"
                           max="120"
                           required
                           class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 font-mono font-bold focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500/40 outline-none transition">
                </div>

                {{-- Warranty Type --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.warranty_type') }} <span class="text-rose-500">*</span>
                    </label>
                    <select name="warranty_type" required class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 font-semibold focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500/40 outline-none transition cursor-pointer">
                        <option value="shop" {{ old('warranty_type') === 'shop' ? 'selected' : '' }}>{{ __('messages.warranty_type_shop') }}</option>
                        <option value="official_brand" {{ old('warranty_type', 'official_brand') === 'official_brand' ? 'selected' : '' }}>{{ __('messages.warranty_type_official_brand') }}</option>
                        <option value="distributor" {{ old('warranty_type') === 'distributor' ? 'selected' : '' }}>{{ __('messages.warranty_type_distributor') }}</option>
                        <option value="service_only" {{ old('warranty_type') === 'service_only' ? 'selected' : '' }}>{{ __('messages.warranty_type_service_only') }}</option>
                    </select>
                </div>

                {{-- Initial Status --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.initial_status') }}
                    </label>
                    <select name="status" class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 font-semibold focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500/40 outline-none transition cursor-pointer">
                        <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>{{ __('messages.status_active') }}</option>
                        <option value="expired" {{ old('status') === 'expired' ? 'selected' : '' }}>{{ __('messages.status_expired') }}</option>
                        <option value="claimed" {{ old('status') === 'claimed' ? 'selected' : '' }}>{{ __('messages.status_claimed') }}</option>
                        <option value="void" {{ old('status') === 'void' ? 'selected' : '' }}>{{ __('messages.status_void') }}</option>
                    </select>
                </div>

                {{-- Terms & Notes --}}
                <div class="sm:col-span-2 lg:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.warranty_terms_conditions') }}
                    </label>
                    <textarea name="terms_conditions"
                              rows="3"
                              placeholder="e.g. Covers manufacturing hardware defects. Physical damage and liquid intrusion are excluded."
                              class="w-full rounded-lg border border-slate-200 dark:border-slate-700 p-2.5 text-xs bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500/40 outline-none transition">{{ old('terms_conditions') }}</textarea>
                </div>

                <div class="sm:col-span-2 lg:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.internal_notes') }}
                    </label>
                    <textarea name="notes"
                              rows="3"
                              placeholder="Optional staff-only notes..."
                              class="w-full rounded-lg border border-slate-200 dark:border-slate-700 p-2.5 text-xs bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500/40 outline-none transition">{{ old('notes') }}</textarea>
                </div>
            </div>
        </section>

        {{-- ============================================================
             STICKY BOTTOM ACTION BAR (SECTION 11 STANDARD)
             ============================================================ --}}
        <div class="sticky bottom-0 z-20 w-full border border-slate-200/90 bg-white/95 px-3 py-2.5 sm:px-4 backdrop-blur-md shadow-[0_-4px_16px_rgba(15,23,42,0.06)] dark:border-slate-800/90 dark:bg-slate-900/95 rounded-lg flex items-center justify-between gap-3">
            <div class="flex items-center gap-1.5 text-xs font-bold text-slate-500 dark:text-slate-400">
                <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>{{ __('messages.warranty_terms_validity') }}</span>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('store.admin.warranty.index', ['store_slug' => $store->slug]) }}"
                   class="px-3.5 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 transition">
                    {{ __('messages.cancel') }}
                </a>
                <button type="submit"
                        class="px-5 py-2 rounded-lg bg-violet-600 hover:bg-violet-700 text-white font-black text-xs shadow-md shadow-violet-500/20 transition flex items-center gap-1.5">
                    <span>💾</span>
                    <span>{{ __('messages.save_warranty_record') }}</span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
