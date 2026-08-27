@extends('layouts.admin.app')

@section('title', __('messages.repair_new_job') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@section('content')
@php
    $productOptions = $products->map(function ($p) {
        $catName = $p->category?->name ?? 'General';
        $parentCatName = $p->category?->parent?->name;
        $categoryPath = $parentCatName ? ($parentCatName . ' > ' . $catName) : $catName;

        return [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'price' => (float) $p->retail_price,
            'category_id' => $p->category_id,
            'category_name' => $categoryPath,
            'display_label' => '[' . $catName . '] ' . $p->name . ($p->sku ? " ({$p->sku})" : '') . ' · ' . number_format($p->retail_price) . ' MMK',
        ];
    })->values();

    $brands = $serviceSettings['brand'] ?? collect();
    $categories = $serviceSettings['category'] ?? collect();
    $models = $serviceSettings['model'] ?? collect();
    $colors = $serviceSettings['color'] ?? collect();
    $storages = $serviceSettings['storage'] ?? collect();
    $defects = $serviceSettings['defect'] ?? collect();
    $accessoriesList = $serviceSettings['accessory'] ?? collect();
    $statuses = $serviceSettings['status'] ?? collect();
@endphp

<div class="w-full space-y-2.5 sm:space-y-3 pb-8" x-data="repairTicketForm({
    baseUrl: '{{ url('/store/' . $store->slug . '/admin') }}',
    csrf: '{{ csrf_token() }}',
    products: {{ $productOptions->toJson() }},
    customers: {{ $customers->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'phone' => $c->phone, 'address' => $c->address ?? ''])->toJson() }}
})" x-init="init()">

    {{-- Top Header --}}
    <header class="w-full flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3.5 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
        <div class="flex items-center gap-2.5 min-w-0">
            <a href="{{ route('store.admin.repairs.index', $storeRouteParams) }}"
               class="w-8 h-8 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition grid place-items-center shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div class="min-w-0">
                <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-violet-50 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 text-[10px] sm:text-[11px] font-black uppercase tracking-wider border border-violet-100 dark:border-violet-900/60 mb-0.5">
                    <span>🔧</span>
                    <span>{{ __('messages.sidebar_repair_center') }}</span>
                    <span class="text-slate-400 dark:text-slate-500">·</span>
                    <span class="font-normal normal-case text-slate-500 dark:text-slate-400">Intake / Ticket Form</span>
                </div>
                <h1 class="text-base sm:text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                    <span>{{ __('messages.repair_new_job') }}</span>
                </h1>
                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                    {{ $store->name }} · {{ __('messages.sidebar_repair_center') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('store.admin.service_settings.index', $storeRouteParams) }}"
               class="px-2.5 py-1.5 rounded-md text-xs font-bold bg-violet-50 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 border border-violet-200 dark:border-violet-800 hover:bg-violet-100 transition flex items-center gap-1.5 shadow-2xs">
                <span>⚙️</span>
                <span>{{ __('messages.sidebar_service_settings') }}</span>
            </a>
        </div>
    </header>

    @if ($errors->any())
        <div class="p-4 bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 rounded-2xl text-xs font-bold text-rose-700 dark:text-rose-300 space-y-1">
            @foreach ($errors->all() as $error)
                <p>• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- Main Ticket Form --}}
    <form method="POST" action="{{ route('store.admin.repairs.store', $storeRouteParams) }}" id="repairForm" class="space-y-6">
        @csrf
        <input type="hidden" name="print_after_save" :value="printAfterSave ? '1' : '0'">

        {{-- Row 1: Balanced Two Column Layout (Customer Info & Status/Payment) --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- 1. Customer Information Card --}}
            <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 sm:p-6 shadow-sm flex flex-col justify-between space-y-4">
                <div>
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3 mb-4">
                        <div class="flex items-center gap-2.5">
                            <span class="w-8 h-8 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 grid place-items-center text-base font-bold">
                                👤
                            </span>
                            <div>
                                <h2 class="text-sm sm:text-base font-black text-slate-900 dark:text-white">
                                    {{ __('messages.repair_customer_section') }}
                                </h2>
                                <p class="text-[11px] text-slate-400">ရှာဖွေရွေးချယ်ပါ သို့မဟုတ် တိုက်ရိုက်ရိုက်ထည့်ပါ</p>
                            </div>
                        </div>
                        
                        <button type="button" @click="openCustomerModal()"
                                class="px-3 py-1.5 rounded-xl text-xs font-black bg-sky-600 hover:bg-sky-500 text-white transition flex items-center gap-1.5 shadow-md shadow-sky-500/20">
                            <span>+</span>
                            <span>{{ __('messages.create_new_customer') }}</span>
                        </button>
                    </div>

                    <div class="space-y-3.5">
                        {{-- Customer Name & Autocomplete Picker --}}
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-bold text-slate-600 dark:text-slate-400">
                                    {{ __('messages.customer') }} <span class="text-rose-500">*</span>
                                </label>
                                
                                {{-- If linked to registered customer, show badge with detach button --}}
                                <template x-if="customerId">
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        <span>✓ {{ __('messages.registered_customer') }}</span>
                                        <button type="button" @click="clearCustomer()" class="text-rose-500 hover:text-rose-700 font-bold ml-1" title="Detach">[✕]</button>
                                    </span>
                                </template>
                            </div>

                            <div class="relative">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm">🔍</span>
                                <input type="text" name="contact_name" x-model="contactName" @input="onContactNameInput()" @focus="showCustomerDropdown = true"
                                       placeholder="ဖောက်သည် အမည် ရိုက်ထည့်ပါ သို့မဟုတ် အမည်/ဖုန်းဖြင့် ရှာပါ..." autocomplete="off"
                                       class="w-full pl-9 pr-8 py-2.5 rounded-2xl border bg-slate-50 dark:bg-slate-800/60 text-sm font-semibold focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-sky-500 outline-none transition"
                                       :class="!contactName.trim() ? 'border-rose-300 dark:border-rose-800' : 'border-slate-200 dark:border-slate-700'">
                                
                                <input type="hidden" name="customer_id" :value="customerId">

                                <button type="button" x-show="contactName" @click="contactName = ''; customerId = '';"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 hover:text-slate-600">✕</button>

                                {{-- Autocomplete Dropdown List --}}
                                <div x-show="showCustomerDropdown && filteredCustomers.length > 0" @click.outside="showCustomerDropdown = false" x-cloak
                                     class="absolute z-30 inset-x-0 top-full mt-1 max-h-56 overflow-y-auto rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-2xl divide-y divide-slate-100 dark:divide-slate-800">
                                    <template x-for="c in filteredCustomers" :key="c.id">
                                        <button type="button" @click="selectCustomer(c)"
                                                class="w-full text-left px-4 py-2.5 text-xs hover:bg-sky-50 dark:hover:bg-slate-800 flex items-center justify-between transition group">
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <p class="font-bold text-slate-900 dark:text-slate-100 group-hover:text-sky-600 dark:group-hover:text-sky-400" x-text="c.name"></p>
                                                    <span class="text-[10px] text-slate-400 font-mono" x-text="c.phone ? '· ' + c.phone : ''"></span>
                                                </div>
                                                <p class="text-[11px] text-slate-400 truncate max-w-xs" x-text="c.address || 'No Address'"></p>
                                            </div>
                                            <span class="text-[10px] px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 font-black text-slate-600 dark:text-slate-300 group-hover:bg-sky-600 group-hover:text-white transition">
                                                {{ __('messages.select') }}
                                            </span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        {{-- Contact Phone & Shipping Address Grid --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 pt-1">
                            {{-- Phone Number --}}
                            <div>
                                <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">
                                    {{ __('messages.phone') }} <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs">📞</span>
                                    <input type="tel" name="contact_phone" x-model="contactPhone" maxlength="40"
                                           placeholder="09 123 456 789"
                                           class="w-full pl-9 pr-3.5 py-2.5 rounded-2xl border bg-slate-50 dark:bg-slate-800/60 text-sm font-mono font-semibold focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-sky-500 outline-none transition"
                                           :class="!contactPhone.trim() ? 'border-rose-300 dark:border-rose-800' : 'border-slate-200 dark:border-slate-700'">
                                </div>
                            </div>

                            {{-- Shipping / Contact Address --}}
                            <div>
                                <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">
                                    {{ __('messages.address') }}
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3.5 top-3 text-slate-400 text-xs">📍</span>
                                    <textarea name="shipping_address" x-model="shippingAddress" rows="1" maxlength="500"
                                              placeholder="{{ __('messages.address') }}..."
                                              class="w-full pl-9 pr-3.5 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-xs font-medium focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-sky-500 outline-none transition resize-none"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. Modern Responsive Status & Payment Card --}}
            <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 sm:p-6 shadow-sm flex flex-col justify-between space-y-4">
                <div>
                    <div class="flex items-center gap-2.5 border-b border-slate-100 dark:border-slate-800 pb-3 mb-4">
                        <span class="w-8 h-8 rounded-xl bg-violet-50 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 grid place-items-center text-base font-bold">
                            ✔️
                        </span>
                        <div>
                            <h2 class="text-sm sm:text-base font-black text-slate-900 dark:text-white">
                                {{ __('messages.status_and_payment') }}
                            </h2>
                            <p class="text-[11px] text-slate-400">အခြေအနေ၊ တာဝန်ကျပညာရှင်နှင့် ကြိုတင်ပေးငွေ</p>
                        </div>
                    </div>

                    <div class="space-y-3.5">
                        {{-- Row 1: Status & Technician Grid --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <div class="min-w-0">
                                <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">
                                    📊 {{ __('messages.repair_current_status') }}
                                </label>
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <select name="status" x-model="selectedStatus" x-ref="statusSelect"
                                            class="flex-1 min-w-0 w-full px-3 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-xs font-bold text-slate-800 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500 outline-none truncate">
                                        @foreach ($statuses as $st)
                                            <option value="{{ $st->code ?: $st->name }}">{{ $st->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" @click="openQuickAdd('status', '{{ __('messages.repair_statuses') }}')"
                                            class="w-9 h-9 shrink-0 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm grid place-items-center shadow-sm transition"
                                            title="{{ __('messages.add_new') }}">+</button>
                                </div>
                            </div>

                            <div class="min-w-0">
                                <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">
                                    👨‍🔧 {{ __('messages.repair_technician') }}
                                </label>
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <select name="technician_id" x-ref="technicianSelect"
                                            class="flex-1 min-w-0 w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-xs font-bold text-slate-800 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500 outline-none truncate">
                                        <option value="">-- {{ __('messages.repair_unassigned') }} --</option>
                                        @foreach ($technicians as $tech)
                                            <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" @click="openTechnicianModal()"
                                            class="w-9 h-9 shrink-0 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm grid place-items-center shadow-sm transition cursor-pointer"
                                            title="{{ __('messages.add_new') }}">+</button>
                                </div>
                            </div>
                        </div>

                        {{-- Row 2: Advance Payment & Payment Method Grid --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 pt-1">
                            <div class="min-w-0">
                                <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">
                                    💵 {{ __('messages.advance_payment') }}
                                </label>
                                <div class="relative min-w-0">
                                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs font-bold">{{ $store->currency ?? 'MMK' }}</span>
                                    <input type="number" name="advance_payment" x-model="advancePayment" min="0" step="100"
                                           placeholder="0"
                                           class="w-full pl-14 pr-3.5 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-sm font-mono font-bold focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500 outline-none transition">
                                </div>
                            </div>

                            <div class="min-w-0">
                                <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">
                                    💳 {{ __('messages.payment_method') }}
                                </label>
                                <select name="payment_method"
                                        class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-xs font-bold text-slate-800 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500 outline-none truncate">
                                    <option value="cash">💵 {{ __('messages.payment_cash') }}</option>
                                    <option value="kpay">📱 KBZPay</option>
                                    <option value="wavepay">📱 WavePay</option>
                                    <option value="cbpay">🏦 CB Pay / AYA Pay</option>
                                    <option value="mmqr">⚡ MMQR / Transfer</option>
                                </select>
                            </div>
                        </div>

                        {{-- Row 3: Estimated Charge & Voucher No --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 pt-1">
                            <div class="min-w-0">
                                <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">
                                    💰 {{ __('messages.repair_estimated_charge') }} ({{ $store->currency ?? 'MMK' }})
                                </label>
                                <input type="number" name="estimated_charge" x-model="estimatedCharge" min="0" step="100"
                                       placeholder="0"
                                       class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-xs font-mono font-bold focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500 outline-none">
                            </div>
                            <div class="min-w-0">
                                <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">
                                    🧾 {{ __('messages.repair_voucher_no') }}
                                </label>
                                <input type="text" name="voucher_no" maxlength="40"
                                       placeholder="e.g. VCH-001"
                                       class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-xs font-mono font-semibold focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500 outline-none">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Row 2: Device & Issue Section (Large Card) --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 sm:p-6 shadow-sm space-y-5">
            <div class="flex items-center gap-2.5 border-b border-slate-100 dark:border-slate-800 pb-3">
                <span class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 grid place-items-center text-base font-bold">
                    📱
                </span>
                <h2 class="text-sm sm:text-base font-black text-slate-900 dark:text-white">
                    {{ __('messages.repair_device_section') }}
                </h2>
            </div>

            <div class="space-y-4">
                {{-- Grid Row 1: Brand & Category --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="min-w-0">
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">
                            🏢 {{ __('messages.repair_brand') }}
                        </label>
                        <div class="flex items-center gap-1.5 min-w-0">
                            <select name="brand" x-model="selectedBrand" x-ref="brandSelect"
                                    class="flex-1 min-w-0 w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-xs font-bold text-slate-800 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-amber-500 outline-none truncate">
                                <option value="">-- {{ __('messages.select_brand') }} --</option>
                                @foreach ($brands as $b)
                                    <option value="{{ $b->name }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" @click="openQuickAdd('brand', '{{ __('messages.repair_brand') }}')"
                                    class="w-9 h-9 shrink-0 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm grid place-items-center shadow-sm transition"
                                    title="{{ __('messages.add_new') }}">+</button>
                        </div>
                    </div>

                    <div class="min-w-0">
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">
                            📁 {{ __('messages.category') }}
                        </label>
                        <div class="flex items-center gap-1.5 min-w-0">
                            <select name="category" x-model="selectedCategory" x-ref="categorySelect"
                                    class="flex-1 min-w-0 w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-xs font-bold text-slate-800 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-amber-500 outline-none truncate">
                                <option value="">-- {{ __('messages.select_category') }} --</option>
                                @foreach ($categories as $c)
                                    <option value="{{ $c->name }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" @click="openQuickAdd('category', '{{ __('messages.category') }}')"
                                    class="w-9 h-9 shrink-0 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm grid place-items-center shadow-sm transition"
                                    title="{{ __('messages.add_new') }}">+</button>
                        </div>
                    </div>
                </div>

                {{-- Grid Row 2: Model & Color --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="min-w-0">
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">
                            📱 {{ __('messages.repair_model') }} <span class="text-rose-500">*</span>
                        </label>
                        <div class="flex items-center gap-1.5 min-w-0">
                            <input type="text" name="model" x-model="modelName" x-ref="modelInput" list="modelsDatalist" required maxlength="120"
                                   placeholder="e.g. iPhone 13 Pro Max, Galaxy S23 Ultra"
                                   class="flex-1 min-w-0 w-full px-3.5 py-2.5 rounded-2xl border bg-slate-50 dark:bg-slate-800/60 text-xs font-semibold focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-amber-500 outline-none transition truncate"
                                   :class="!modelName.trim() ? 'border-rose-300 dark:border-rose-800' : 'border-slate-200 dark:border-slate-700'">
                            <datalist id="modelsDatalist" x-ref="modelsDatalist">
                                @foreach ($models as $m)
                                    <option value="{{ $m->name }}"></option>
                                @endforeach
                            </datalist>
                            <button type="button" @click="openQuickAdd('model', '{{ __('messages.repair_model') }}')"
                                    class="w-9 h-9 shrink-0 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm grid place-items-center shadow-sm transition"
                                    title="{{ __('messages.add_new') }}">+</button>
                        </div>
                    </div>

                    <div class="min-w-0">
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">
                            🎨 {{ __('messages.color') }}
                        </label>
                        <div class="flex items-center gap-1.5 min-w-0">
                            <select name="color" x-model="selectedColor" x-ref="colorSelect"
                                    class="flex-1 min-w-0 w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-xs font-bold text-slate-800 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-amber-500 outline-none truncate">
                                <option value="">-- {{ __('messages.select_color') }} --</option>
                                @foreach ($colors as $cl)
                                    <option value="{{ $cl->name }}">{{ $cl->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" @click="openQuickAdd('color', '{{ __('messages.color') }}')"
                                    class="w-9 h-9 shrink-0 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm grid place-items-center shadow-sm transition"
                                    title="{{ __('messages.add_new') }}">+</button>
                        </div>
                    </div>
                </div>

                {{-- Grid Row 3: Storage & IMEI/Serial --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="min-w-0">
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">
                            💾 {{ __('messages.repair_storage') }}
                        </label>
                        <div class="flex items-center gap-1.5 min-w-0">
                            <select name="storage" x-model="selectedStorage" x-ref="storageSelect"
                                    class="flex-1 min-w-0 w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-xs font-bold text-slate-800 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-amber-500 outline-none truncate">
                                <option value="">-- {{ __('messages.select_storage') }} --</option>
                                @foreach ($storages as $stg)
                                    <option value="{{ $stg->name }}">{{ $stg->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" @click="openQuickAdd('storage', '{{ __('messages.repair_storage') }}')"
                                    class="w-9 h-9 shrink-0 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm grid place-items-center shadow-sm transition"
                                    title="{{ __('messages.add_new') }}">+</button>
                        </div>
                    </div>

                    <div class="min-w-0">
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">
                            🔢 {{ __('messages.repair_imei_serial') }}
                        </label>
                        <input type="text" name="imei_serial" maxlength="60"
                               placeholder="e.g. 354892091234567"
                               class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-xs font-mono font-semibold focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-amber-500 outline-none transition">
                    </div>
                </div>

                {{-- Row 4: Issue / Defect (ဖုန်းဖြစ်ချက်) --}}
                <div class="min-w-0">
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">
                        ⚡ {{ __('messages.repair_defects') }} <span class="text-rose-500">*</span>
                    </label>
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 min-w-0">
                        <input type="text" name="reported_problem" x-model="reportedProblem" required maxlength="1000"
                               placeholder="e.g. Screen Broken, No Power, Water Damage..."
                               class="flex-1 min-w-0 w-full px-3.5 py-2.5 rounded-2xl border bg-slate-50 dark:bg-slate-800/60 text-xs font-semibold focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-amber-500 outline-none transition"
                               :class="!reportedProblem.trim() ? 'border-rose-300 dark:border-rose-800' : 'border-slate-200 dark:border-slate-700'">
                        <div class="flex items-center gap-1.5 shrink-0">
                            <select x-ref="defectSelect" @change="if($event.target.value) reportedProblem = $event.target.value"
                                    class="w-44 px-3 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-xs font-bold text-slate-700 dark:text-slate-300 truncate">
                                <option value="">-- {{ __('messages.repair_quick_pick') }} --</option>
                                @foreach ($defects as $df)
                                    <option value="{{ $df->name }}">{{ $df->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" @click="openQuickAdd('defect', '{{ __('messages.repair_defects') }}')"
                                    class="w-9 h-9 shrink-0 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm grid place-items-center shadow-sm transition"
                                    title="{{ __('messages.add_new') }}">+</button>
                        </div>
                    </div>
                </div>

                {{-- Row 5: Accessories (ဖုန်းနှင့်အတူထားခဲ့သည့်ပစ္စည်းများ) --}}
                <div class="min-w-0">
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">
                        📦 {{ __('messages.repair_accessories') }}
                    </label>
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 min-w-0">
                        <input type="text" name="accessories" x-model="accessoriesText" maxlength="500"
                               placeholder="e.g. SIM Card, SIM Tray, Phone Cover, Charger..."
                               class="flex-1 min-w-0 w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-xs font-semibold focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-amber-500 outline-none transition">
                        <div class="flex items-center gap-1.5 shrink-0">
                            <select x-ref="accessorySelect" @change="addAccessoryTag($event.target.value)"
                                    class="w-44 px-3 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-xs font-bold text-slate-700 dark:text-slate-300 truncate">
                                <option value="">-- {{ __('messages.repair_add_preset') }} --</option>
                                @foreach ($accessoriesList as $acc)
                                    <option value="{{ $acc->name }}">{{ $acc->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" @click="openQuickAdd('accessory', '{{ __('messages.repair_accessories_tab') }}')"
                                    class="w-9 h-9 shrink-0 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm grid place-items-center shadow-sm transition"
                                    title="{{ __('messages.add_new') }}">+</button>
                        </div>
                    </div>
                </div>

                {{-- Sub-Card: Security / Password & Pattern Lock --}}
                <div class="rounded-2xl bg-slate-50/80 dark:bg-slate-800/40 border border-slate-200/80 dark:border-slate-700/60 p-4 space-y-3.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                            🔒 {{ __('messages.security_password') }}
                        </span>

                        {{-- Pattern Lock Toggle Switch --}}
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <span class="text-xs font-bold text-slate-600 dark:text-slate-400">
                                {{ __('messages.use_pattern_lock') }}
                            </span>
                            <div class="relative">
                                <input type="checkbox" x-model="usePatternLock" class="sr-only peer">
                                <div class="w-10 h-5 bg-slate-300 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                            </div>
                        </label>
                    </div>

                    {{-- If Pattern Lock is enabled: Interactive 3x3 Canvas / Grid --}}
                    <div x-show="usePatternLock" x-cloak class="p-4 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 space-y-3">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                🎨 {{ __('messages.repair_pattern_instruction') }}:
                            </p>
                            <button type="button" @click="clearPattern()"
                                    class="text-xs font-bold text-rose-500 hover:text-rose-700 transition">
                                ✕ {{ __('messages.repair_pattern_clear') }}
                            </button>
                        </div>

                        <div class="flex flex-col sm:flex-row items-center gap-6 justify-center py-2">
                            {{-- 3x3 Dot Grid --}}
                            <div class="grid grid-cols-3 gap-4 p-3 bg-slate-100 dark:bg-slate-800 rounded-2xl">
                                <template x-for="dot in [1,2,3,4,5,6,7,8,9]" :key="dot">
                                    <button type="button" @click="pressDot(dot)"
                                            :class="patternSequence.includes(dot) ? 'bg-violet-600 text-white scale-110 shadow-lg shadow-violet-500/40 ring-4 ring-violet-300 dark:ring-violet-900' : 'bg-white dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200'"
                                            class="w-10 h-10 rounded-full font-black text-sm grid place-items-center transition duration-150"
                                            x-text="dot"></button>
                                </template>
                            </div>

                            <div class="space-y-2 text-center sm:text-left">
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.repair_pattern_sequence') }}:</p>
                                <div class="px-3.5 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-sm font-mono font-bold text-violet-600 dark:text-violet-400 min-w-[160px]"
                                     x-text="patternSequence.length ? patternSequence.join(' → ') : '{{ __('messages.repair_pattern_none') }}'"></div>
                                <input type="hidden" name="pattern_lock" :value="patternSequence.join('-')">
                            </div>
                        </div>
                    </div>

                    {{-- Device Password / PIN Input --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">
                            🔑 {{ __('messages.device_password') }}
                        </label>
                        <input type="text" name="device_password" maxlength="120"
                               placeholder="e.g. 123456 or password..."
                               class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-mono font-semibold focus:ring-2 focus:ring-amber-500 outline-none transition">
                    </div>
                </div>
            </div>
        </div>

        {{-- Row 3: Service & Parts (Dynamic Line Items Table) --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 sm:p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 grid place-items-center text-base font-bold">
                        🔧
                    </span>
                    <div>
                        <h2 class="text-sm sm:text-base font-black text-slate-900 dark:text-white">
                            {{ __('messages.repair_items_section') }}
                        </h2>
                        <p class="text-xs text-slate-400">{{ __('messages.repair_items_hint') }}</p>
                    </div>
                </div>

                <button type="button" @click="addItem()"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition flex items-center gap-1.5 shadow-md shadow-emerald-500/20">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>{{ __('messages.repair_add_item') }}</span>
                </button>
            </div>

            {{-- Table / Rows --}}
            <template x-if="items.length > 0">
                <div class="space-y-2.5">
                    <template x-for="(item, i) in items" :key="i">
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2.5 items-center p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700">
                            {{-- Type --}}
                            <div class="sm:col-span-2">
                                <select :name="'items[' + i + '][item_type]'" x-model="item.item_type"
                                        class="w-full px-2.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-bold text-slate-700 dark:text-slate-200 outline-none">
                                    <option value="part">📦 {{ __('messages.repair_item_part') }}</option>
                                    <option value="service">🛠️ {{ __('messages.repair_item_service') }}</option>
                                </select>
                            </div>

                            {{-- Name --}}
                            <div class="sm:col-span-4">
                                <input type="text" :name="'items[' + i + '][name]'" x-model="item.name" maxlength="120"
                                       placeholder="{{ __('messages.repair_item_name_placeholder') }}"
                                       class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-semibold outline-none focus:ring-2 focus:ring-emerald-500">
                            </div>

                            {{-- Link to Product (if Part) --}}
                            <div class="sm:col-span-3" x-show="item.item_type === 'part'">
                                <select :name="'items[' + i + '][product_id]'" x-model="item.product_id" @change="onProductChange(i)"
                                        class="w-full px-2.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-semibold outline-none">
                                    <option value="">-- {{ __('messages.repair_select_product_none') }} --</option>
                                    <template x-for="p in products" :key="p.id">
                                        <option :value="p.id" x-text="p.display_label || p.name"></option>
                                    </template>
                                </select>
                            </div>

                            {{-- Qty --}}
                            <div class="sm:col-span-1">
                                <input type="number" :name="'items[' + i + '][quantity]'" x-model="item.quantity" min="1" step="1"
                                       class="w-full px-2 py-2 text-center rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-mono font-bold outline-none">
                            </div>

                            {{-- Price --}}
                            <div class="sm:col-span-1">
                                <input type="number" :name="'items[' + i + '][unit_price]'" x-model="item.unit_price" min="0" step="100"
                                       class="w-full px-2 py-2 text-right rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-mono font-bold outline-none">
                            </div>

                            {{-- Action --}}
                            <div class="sm:col-span-1 text-right flex items-center justify-end gap-1">
                                <span class="text-xs font-black text-slate-800 dark:text-slate-200 font-mono hidden sm:inline"
                                      x-text="Number(subtotal(i)).toLocaleString()"></span>
                                <button type="button" @click="removeItem(i)"
                                        class="w-8 h-8 rounded-lg bg-rose-50 dark:bg-rose-950/60 text-rose-600 hover:bg-rose-100 transition grid place-items-center text-xs font-bold">
                                    ✕
                                </button>
                            </div>
                        </div>
                    </template>

                    {{-- Total bar --}}
                    <div class="flex items-center justify-between pt-2 px-2">
                        <button type="button" @click="useAsEstimate()"
                                class="text-xs font-bold text-sky-600 dark:text-sky-400 hover:underline">
                            ⚡ {{ __('messages.repair_use_total_as_charge') }}
                        </button>
                        <div class="text-sm font-black text-slate-900 dark:text-white">
                            Total: <span class="text-emerald-600 dark:text-emerald-400 font-mono" x-text="'{{ $store->currency ?? 'MMK' }} ' + Number(totalItems()).toLocaleString()"></span>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="items.length === 0">
                <div class="p-8 text-center text-slate-400 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl">
                    <p class="text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('messages.repair_no_items_yet') }}</p>
                </div>
            </template>
        </div>

        {{-- Action Buttons Row --}}
        <div class="flex flex-wrap items-center justify-end gap-3 pt-2">
            <a href="{{ route('store.admin.repairs.index', $storeRouteParams) }}"
               class="px-5 py-2.5 rounded-2xl text-xs sm:text-sm font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 transition">
                ✕ {{ __('messages.cancel') }}
            </a>

            <button type="button" @click="saveAndPrint()"
                    class="px-5 py-2.5 rounded-2xl text-xs sm:text-sm font-bold bg-sky-600 hover:bg-sky-500 text-white shadow-lg shadow-sky-500/20 transition flex items-center gap-1.5">
                <span>🖨️</span>
                <span>{{ __('messages.print') }}</span>
            </button>

            <button type="submit"
                    class="px-6 py-2.5 rounded-2xl text-xs sm:text-sm font-black bg-blue-600 hover:bg-blue-500 text-white shadow-xl shadow-blue-500/20 transition flex items-center gap-2">
                <span>💾</span>
                <span>{{ __('messages.save') }}</span>
            </button>
        </div>
    </form>

    {{-- Dedicated Full-Featured Customer Creation Modal --}}
    <div x-show="customerModalOpen" x-cloak class="fixed inset-0 z-50 grid place-items-center p-4"
         @keydown.escape.window="customerModalOpen = false">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="customerModalOpen = false"></div>
        <div class="relative w-full max-w-md rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 grid place-items-center text-base font-bold">👤</span>
                    <h3 class="text-base font-black text-slate-900 dark:text-white">{{ __('messages.create_new_customer') }}</h3>
                </div>
                <button type="button" @click="customerModalOpen = false" class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 font-bold hover:bg-slate-200">✕</button>
            </div>

            <div class="space-y-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">
                        {{ __('messages.name') }} <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" x-model="newCustomer.name" x-ref="newCustomerName"
                           placeholder="e.g. U Aung / Daw Mya"
                           class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm font-semibold focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-sky-500 outline-none transition">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">
                        {{ __('messages.customer_phone') }} <span class="text-rose-500">*</span>
                    </label>
                    <input type="tel" x-model="newCustomer.phone"
                           placeholder="09 123 456 789"
                           class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm font-mono font-semibold focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-sky-500 outline-none transition">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">
                        {{ __('messages.customer_type') }}
                    </label>
                    <select x-model="newCustomer.type"
                            class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-bold focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-sky-500 outline-none">
                        <option value="retail_customer">🛍️ {{ __('messages.customer_retail') }}</option>
                        <option value="wholesale_customer">🏢 {{ __('messages.customer_wholesale') }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">
                        {{ __('messages.customer_address') }}
                    </label>
                    <textarea x-model="newCustomer.address" rows="2" maxlength="500"
                              placeholder="{{ __('messages.address') }}..."
                              class="w-full px-3.5 py-2 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-medium focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-sky-500 outline-none transition"></textarea>
                </div>

                <div class="flex gap-2.5 pt-2">
                    <button type="button" @click="customerModalOpen = false"
                            class="flex-1 py-2.5 rounded-2xl text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 transition">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="button" @click="submitNewCustomer()" :disabled="customerModalBusy || !newCustomer.name.trim() || !newCustomer.phone.trim()"
                            class="flex-1 py-2.5 rounded-2xl text-xs font-black bg-sky-600 hover:bg-sky-500 text-white shadow-lg shadow-sky-500/20 disabled:opacity-50 transition flex items-center justify-center gap-1.5">
                        <span x-text="customerModalBusy ? '{{ __('messages.saving') }}' : '✓ {{ __('messages.save') }}'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Interactive Universal Quick-Add Modal for Other Master Data Items --}}
    <div x-show="quickAddModalOpen" x-cloak class="fixed inset-0 z-50 grid place-items-center p-4"
         @keydown.escape.window="quickAddModalOpen = false">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="quickAddModalOpen = false"></div>
        <div class="relative w-full max-w-sm rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-sm font-black text-slate-900 dark:text-white" x-text="'+ {{ __('messages.quick_add_title') }} ' + quickAddTitle"></h3>
                <button type="button" @click="quickAddModalOpen = false" class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 font-bold hover:bg-slate-200">✕</button>
            </div>

            <div class="space-y-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.name') }} <span class="text-rose-500">*</span></label>
                    <input type="text" x-model="quickAddName" x-ref="quickAddInput" @keydown.enter="submitQuickAdd()"
                           class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-semibold focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-emerald-500 outline-none"
                           :placeholder="'Enter ' + quickAddTitle + ' name...'">
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="button" @click="quickAddModalOpen = false"
                            class="flex-1 py-2 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 transition">{{ __('messages.cancel') }}</button>
                    <button type="button" @click="submitQuickAdd()" :disabled="quickAddBusy || !quickAddName.trim()"
                            class="flex-1 py-2 rounded-xl text-xs font-black bg-emerald-600 hover:bg-emerald-500 text-white shadow-md shadow-emerald-500/20 disabled:opacity-50 transition">
                        <span x-text="quickAddBusy ? '{{ __('messages.saving') }}' : '{{ __('messages.quick_add_and_select') }}'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Dedicated Quick-Add Technician Modal --}}
    <div x-show="technicianModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto"
         @keydown.escape.window="technicianModalOpen = false" @click.self="technicianModalOpen = false">
        <div class="relative w-full max-w-sm rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-2xl space-y-3.5 text-slate-900 dark:text-slate-100 animate-in fade-in zoom-in-95 duration-150">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                <div class="flex items-center gap-2">
                    <span class="text-base">👨‍🔧</span>
                    <h3 class="text-sm font-black text-slate-900 dark:text-white">+ {{ __('messages.repair_technician') }} (စက်ပြင်ပညာရှင်အသစ်)</h3>
                </div>
                <button type="button" @click="technicianModalOpen = false" class="text-slate-400 hover:text-slate-600 text-sm font-bold">✕</button>
            </div>

            <div class="space-y-3 text-xs">
                <div>
                    <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.name') }} <span class="text-rose-500">*</span></label>
                    <input type="text" x-model="newTechnician.name" x-ref="newTechnicianName"
                           placeholder="e.g. Ko Aung / Technician Name"
                           class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.phone') }} <span class="text-rose-500">*</span></label>
                    <input type="tel" x-model="newTechnician.phone"
                           placeholder="09 123 456 789"
                           class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-mono font-bold focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <div class="flex gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="technicianModalOpen = false"
                            class="flex-1 py-2 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 transition">{{ __('messages.cancel') }}</button>
                    <button type="button" @click="submitNewTechnician()" :disabled="technicianModalBusy || !newTechnician.name.trim() || !newTechnician.phone.trim()"
                            class="flex-1 py-2 rounded-lg text-xs font-black bg-emerald-600 hover:bg-emerald-500 text-white shadow-2xs disabled:opacity-50 transition active:scale-95 cursor-pointer">
                        <span x-text="technicianModalBusy ? '{{ __('messages.saving') }}' : '✓ {{ __('messages.save') }}'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<script nonce="{{ $cspNonce }}">
function repairTicketForm(config) {
    return {
        baseUrl: config.baseUrl,
        csrf: config.csrf,
        products: config.products || [],
        customers: config.customers || [],

        // Form fields
        customerId: '',
        contactName: '',
        contactPhone: '',
        shippingAddress: '',
        showCustomerDropdown: false,
        selectedBrand: '',
        selectedCategory: '',
        modelName: '',
        selectedColor: '',
        selectedStorage: '',
        reportedProblem: '',
        accessoriesText: '',
        selectedStatus: 'received',
        advancePayment: '',
        estimatedCharge: '',
        usePatternLock: false,
        patternSequence: [],
        items: [],
        printAfterSave: false,

        // Customer Modal state
        customerModalOpen: false,
        customerModalBusy: false,
        newCustomer: {
            name: '',
            phone: '',
            type: 'retail_customer',
            address: ''
        },

        // Technician Modal state
        technicianModalOpen: false,
        technicianModalBusy: false,
        newTechnician: {
            name: '',
            phone: ''
        },

        // Universal Master Settings Quick add modal state
        quickAddModalOpen: false,
        quickAddType: '',
        quickAddTitle: '',
        quickAddName: '',
        quickAddBusy: false,

        init() {
            if (this.$refs.statusSelect && this.$refs.statusSelect.value) {
                this.selectedStatus = this.$refs.statusSelect.value;
            }
        },

        get filteredCustomers() {
            if (!this.contactName.trim()) {
                return this.customers.slice(0, 15);
            }
            const q = this.contactName.toLowerCase().trim();
            return this.customers.filter(c => 
                (c.name && c.name.toLowerCase().includes(q)) || 
                (c.phone && c.phone.includes(q))
            ).slice(0, 15);
        },

        onContactNameInput() {
            this.showCustomerDropdown = true;
            this.customerId = '';
        },

        selectCustomer(c) {
            this.customerId = c.id;
            this.contactName = c.name;
            this.contactPhone = c.phone || '';
            if (c.address) {
                this.shippingAddress = c.address;
            }
            this.showCustomerDropdown = false;
        },

        clearCustomer() {
            this.customerId = '';
        },

        openCustomerModal() {
            this.newCustomer = {
                name: this.contactName.trim() || '',
                phone: this.contactPhone.trim() || '',
                type: 'retail_customer',
                address: this.shippingAddress.trim() || ''
            };
            this.customerModalOpen = true;
            this.$nextTick(() => this.$refs.newCustomerName?.focus());
        },

        async submitNewCustomer() {
            if (!this.newCustomer.name.trim() || !this.newCustomer.phone.trim() || this.customerModalBusy) return;
            this.customerModalBusy = true;

            try {
                const res = await fetch('{{ route('pos.customers.add', $storeRouteParams) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': this.csrf,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({
                        name: this.newCustomer.name.trim(),
                        phone: this.newCustomer.phone.trim(),
                        type: this.newCustomer.type || 'retail_customer',
                        address: this.newCustomer.address ? this.newCustomer.address.trim() : ''
                    })
                });
                const data = await res.json();
                if (data.customer) {
                    this.customers.unshift(data.customer);
                    this.selectCustomer(data.customer);
                    this.customerModalOpen = false;
                } else if (data.message || data.error) {
                    alert(data.message || data.error);
                }
            } catch (err) {
                console.error(err);
                alert('An error occurred while creating the customer.');
            } finally {
                this.customerModalBusy = false;
            }
        },

        // Pattern lock methods
        pressDot(num) {
            if (!this.patternSequence.includes(num)) {
                this.patternSequence.push(num);
            }
        },

        clearPattern() {
            this.patternSequence = [];
        },

        addAccessoryTag(name) {
            if (!name) return;
            const current = this.accessoriesText.split(',').map(s => s.trim()).filter(Boolean);
            if (!current.includes(name)) {
                current.push(name);
                this.accessoriesText = current.join(', ');
            }
        },

        // Dynamic Line Items
        addItem() {
            this.items.push({
                item_type: 'part',
                name: '',
                product_id: '',
                quantity: 1,
                unit_price: 0
            });
        },

        removeItem(i) {
            this.items.splice(i, 1);
        },

        onProductChange(i) {
            const p = this.products.find(prod => prod.id == this.items[i].product_id);
            if (p) {
                this.items[i].name = p.name;
                this.items[i].unit_price = p.price;
                this.items[i].sku = p.sku || '';
            }
        },

        subtotal(i) {
            return (parseFloat(this.items[i].unit_price) || 0) * (parseInt(this.items[i].quantity) || 0);
        },

        totalItems() {
            return this.items.reduce((s, it) => s + (parseFloat(it.unit_price) || 0) * (parseInt(it.quantity) || 0), 0);
        },

        useAsEstimate() {
            this.estimatedCharge = Math.round(this.totalItems());
        },

        saveAndPrint() {
            this.printAfterSave = true;
            document.getElementById('repairForm').submit();
        },

        openTechnicianModal() {
            this.newTechnician = { name: '', phone: '' };
            this.technicianModalOpen = true;
            this.$nextTick(() => this.$refs.newTechnicianName?.focus());
        },

        async submitNewTechnician() {
            if (!this.newTechnician.name.trim() || !this.newTechnician.phone.trim() || this.technicianModalBusy) return;
            this.technicianModalBusy = true;

            try {
                const res = await fetch('{{ route('store.admin.repairs.quick_add_technician', $storeRouteParams) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': this.csrf,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({
                        name: this.newTechnician.name.trim(),
                        phone: this.newTechnician.phone.trim()
                    })
                });
                const data = await res.json();
                if (data.success && data.technician) {
                    const tech = data.technician;
                    if (this.$refs.technicianSelect) {
                        const opt = new Option(tech.name + (tech.phone ? ' (' + tech.phone + ')' : ''), tech.id, true, true);
                        this.$refs.technicianSelect.add(opt);
                    }
                    this.technicianModalOpen = false;
                } else if (data.message || data.error) {
                    alert(data.message || data.error);
                }
            } catch (err) {
                console.error(err);
                alert('An error occurred while adding the technician.');
            } finally {
                this.technicianModalBusy = false;
            }
        },

        // Quick add handler for other master settings
        openQuickAdd(type, title) {
            this.quickAddType = type;
            this.quickAddTitle = title;
            this.quickAddName = '';
            this.quickAddModalOpen = true;
            this.$nextTick(() => this.$refs.quickAddInput?.focus());
        },

        async submitQuickAdd() {
            if (!this.quickAddName.trim() || this.quickAddBusy) return;
            this.quickAddBusy = true;

            try {
                const res = await fetch('{{ route('store.admin.service_settings.quick_add', $storeRouteParams) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': this.csrf,
                        'Accept': 'application/json'
                    },
                    body: new URLSearchParams({
                        type: this.quickAddType,
                        name: this.quickAddName.trim()
                    })
                });
                const data = await res.json();
                if (data.success && data.item) {
                    const item = data.item;
                    if (this.quickAddType === 'brand' && this.$refs.brandSelect) {
                        this.$refs.brandSelect.add(new Option(item.name, item.name, true, true));
                        this.selectedBrand = item.name;
                    } else if (this.quickAddType === 'category' && this.$refs.categorySelect) {
                        this.$refs.categorySelect.add(new Option(item.name, item.name, true, true));
                        this.selectedCategory = item.name;
                    } else if (this.quickAddType === 'model') {
                        if (this.$refs.modelsDatalist) {
                            let opt = document.createElement('option');
                            opt.value = item.name;
                            this.$refs.modelsDatalist.appendChild(opt);
                        }
                        this.modelName = item.name;
                    } else if (this.quickAddType === 'color' && this.$refs.colorSelect) {
                        this.$refs.colorSelect.add(new Option(item.name, item.name, true, true));
                        this.selectedColor = item.name;
                    } else if (this.quickAddType === 'storage' && this.$refs.storageSelect) {
                        this.$refs.storageSelect.add(new Option(item.name, item.name, true, true));
                        this.selectedStorage = item.name;
                    } else if (this.quickAddType === 'defect') {
                        if (this.$refs.defectSelect) {
                            this.$refs.defectSelect.add(new Option(item.name, item.name, true, true));
                        }
                        this.reportedProblem = item.name;
                    } else if (this.quickAddType === 'accessory') {
                        if (this.$refs.accessorySelect) {
                            this.$refs.accessorySelect.add(new Option(item.name, item.name, true, true));
                        }
                        this.addAccessoryTag(item.name);
                    } else if (this.quickAddType === 'status' && this.$refs.statusSelect) {
                        this.$refs.statusSelect.add(new Option(item.name, item.code || item.name, true, true));
                        this.selectedStatus = item.code || item.name;
                    }
                }
                this.quickAddModalOpen = false;
            } catch (err) {
                console.error(err);
            } finally {
                this.quickAddBusy = false;
            }
        }
    };
}
</script>
@endsection
