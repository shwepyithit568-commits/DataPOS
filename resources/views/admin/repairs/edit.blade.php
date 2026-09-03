@extends('layouts.admin.app')

@section('title', __('messages.repair_edit') . ' - ' . $repair->job_number . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
@php
    // Consumed parts are preserved server-side (they already moved stock) —
    // only editable lines go back into the form payload.
    $deductedItems = $repair->items->where('is_deducted', true)->values();
    $editableItems = $repair->items->where('is_deducted', false)->values();
    $productOptions = collect($products)->map(function ($p) {
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
            'display_label' => '[' . $catName . '] ' . $p->name . ($p->sku ? " ({$p->sku})" : '') . ' · ' . number_format($p->retail_price),
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

    $statusColors = [
        'received' => 'bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800',
        'diagnosing' => 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800',
        'awaiting_approval' => 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800',
        'awaiting_parts' => 'bg-orange-50 dark:bg-orange-950/60 text-orange-700 dark:text-orange-300 border-orange-200 dark:border-orange-800',
        'in_repair' => 'bg-violet-50 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 border-violet-200 dark:border-violet-800',
        'ready' => 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
        'delivered' => 'bg-slate-50 dark:bg-slate-900/60 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-800',
        'cancelled' => 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800',
        'unrepairable' => 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800',
    ];
    $badgeStyle = $statusColors[$repair->status] ?? 'bg-slate-50 text-slate-600 border-slate-200';
@endphp

<div class="w-full space-y-0.5 pb-6" x-data="{
    items: {{ $editableItems->map(fn ($it) => [
        'item_type' => $it->item_type,
        'name' => $it->name,
        'product_id' => $it->product_id ?? '',
        'quantity' => (float) $it->quantity,
        'unit_price' => (float) $it->unit_price,
    ])->values()->toJson() }},
    products: {{ $productOptions->toJson() }},
    addItem() {
        this.items.push({ item_type: 'part', name: '', product_id: '', quantity: 1, unit_price: 0 });
    },
    removeItem(i) {
        this.items.splice(i, 1);
    },
    onProductChange(i) {
        const p = this.products.find(p => p.id == this.items[i].product_id);
        if (p) {
            this.items[i].name = p.name;
            this.items[i].unit_price = p.price;
        }
    },
    subtotal(i) {
        return (parseFloat(this.items[i].unit_price) || 0) * (parseFloat(this.items[i].quantity) || 0);
    },
    total() {
        return this.items.reduce((s, it) => s + (parseFloat(it.unit_price) || 0) * (parseFloat(it.quantity) || 0), 0);
    },
    useAsFinal() {
        const el = document.getElementById('repair_final_charge');
        if (el) el.value = Math.round(this.total());
    }
}">

    {{-- ── SECTION 1: Standard v4.1 Ultra-Dense Header Banner ── --}}
    <div class="admin-toolbar-root rounded-lg bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 px-2 sm:px-3 py-1.5 shadow-xs flex flex-wrap items-center justify-between gap-1.5 sm:gap-2">
        <div class="flex items-center gap-1.5 sm:gap-2 min-w-0">
            <a href="{{ route('store.admin.repairs.show', [...$storeRouteParams, 'repair' => $repair->id]) }}"
               class="h-7 w-7 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-750 dark:hover:bg-slate-700 grid place-items-center text-slate-600 dark:text-slate-300 transition-colors shrink-0"
               title="{{ __('messages.back') }}">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>

            <div class="h-6 w-6 rounded-md bg-violet-600/10 dark:bg-violet-400/10 text-violet-600 dark:text-violet-400 grid place-items-center text-xs font-black shrink-0">
                🔧
            </div>

            <div class="min-w-0">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-semibold bg-violet-50 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 border border-violet-200/60 dark:border-violet-800/60 shrink-0">
                        {{ $store->name }}
                    </span>
                    <h1 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white truncate font-mono">
                        {{ $repair->job_number }}
                    </h1>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $badgeStyle }}">
                        {{ __('messages.repair_status_' . $repair->status) }}
                    </span>
                    @if ($repair->voucher_no)
                        <span class="hidden sm:inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-mono text-slate-500 bg-slate-100 dark:bg-slate-750 border border-slate-200 dark:border-slate-700">
                            {{ $repair->voucher_no }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex items-center gap-1 sm:gap-1.5 shrink-0">
            <a href="{{ route('store.admin.repairs.print', [...$storeRouteParams, 'repair' => $repair->id]) }}" target="_blank"
               class="h-7 px-2 sm:px-2.5 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-600 transition flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2z" />
                </svg>
                <span class="hidden sm:inline">{{ __('messages.repair_print_ticket') }}</span>
            </a>
            <a href="{{ route('store.admin.repairs.show', [...$storeRouteParams, 'repair' => $repair->id]) }}"
               class="h-7 px-2 sm:px-2.5 rounded-lg text-xs font-semibold bg-violet-50 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 border border-violet-200 dark:border-violet-800 hover:bg-violet-100 transition flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <span>{{ __('messages.view') }}</span>
            </a>
        </div>
    </div>

    {{-- Error Banner --}}
    @if ($errors->any())
        <div class="rounded-lg p-2.5 bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 text-xs text-rose-700 dark:text-rose-300 space-y-1">
            <div class="font-bold flex items-center gap-1.5">
                <span>⚠️</span>
                <span>{{ __('messages.fix_errors') ?? 'Please correct the errors below:' }}</span>
            </div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Main Form --}}
    <form method="POST" action="{{ route('store.admin.repairs.update', [...$storeRouteParams, 'repair' => $repair->id]) }}"
          class="space-y-0.5 sm:space-y-1">
        @csrf
        @method('PUT')

        {{-- 2-Column Responsive Layout --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-0.5 sm:gap-1">

            {{-- ── COLUMN 1: Customer & Device Information ── --}}
            <div class="space-y-0.5 sm:space-y-1">

                {{-- Card 1: 👤 Customer Information --}}
                <div class="rounded-lg bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 p-2.5 sm:p-3 shadow-xs space-y-2">
                    <div class="flex items-center justify-between pb-1.5 border-b border-slate-100 dark:border-slate-750">
                        <div class="flex items-center gap-1.5 text-xs font-bold text-slate-900 dark:text-white">
                            <span class="text-sky-500">👤</span>
                            <span>{{ __('messages.repair_customer_section') }}</span>
                        </div>
                        @if ($repair->customer)
                            <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                ✓ {{ __('messages.registered_customer') ?? 'Registered Customer' }}
                            </span>
                        @endif
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">
                            {{ __('messages.repair_customer_label') }}
                        </label>
                        <select name="customer_id"
                                class="w-full h-8 px-2 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-violet-500 outline-none">
                            <option value="">-- {{ __('messages.repair_walk_in') }} --</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" @selected(old('customer_id', $repair->customer_id) == $customer->id)>
                                    {{ $customer->name }} {{ $customer->phone ? '(' . $customer->phone . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">
                                {{ __('messages.repair_contact_name') }}
                            </label>
                            <input type="text" name="contact_name" value="{{ old('contact_name', $repair->contact_name) }}" maxlength="120"
                                   placeholder="အမည် ရိုက်ထည့်ပါ..."
                                   class="w-full h-8 px-2 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-violet-500 outline-none" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">
                                {{ __('messages.repair_contact_phone') }}
                            </label>
                            <input type="text" name="contact_phone" value="{{ old('contact_phone', $repair->contact_phone) }}" maxlength="40"
                                   placeholder="09..."
                                   class="w-full h-8 px-2 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-violet-500 outline-none" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">
                            {{ __('messages.address') }}
                        </label>
                        <input type="text" name="shipping_address" value="{{ old('shipping_address', $repair->shipping_address) }}" maxlength="1000"
                               placeholder="နေရပ်လိပ်စာ / ပို့ဆောင်ရမည့်လိပ်စာ..."
                               class="w-full h-8 px-2 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-violet-500 outline-none" />
                    </div>
                </div>

                {{-- Card 2: 📱 Device & Defect Information --}}
                <div class="rounded-lg bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 p-2.5 sm:p-3 shadow-xs space-y-2">
                    <div class="flex items-center justify-between pb-1.5 border-b border-slate-100 dark:border-slate-750">
                        <div class="flex items-center gap-1.5 text-xs font-bold text-slate-900 dark:text-white">
                            <span class="text-amber-500">📱</span>
                            <span>{{ __('messages.repair_device_section') }}</span>
                        </div>
                    </div>

                    {{-- Row: Device Type, Brand, Category, Model --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">
                                {{ __('messages.repair_device_type') }} <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="device_type" list="device_types_list" value="{{ old('device_type', $repair->device_type) }}" required maxlength="60"
                                   placeholder="Smartphone / Laptop / CCTV..."
                                   class="w-full h-8 px-2 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-violet-500 outline-none" />
                            <datalist id="device_types_list">
                                <option value="smartphone">Smartphone</option>
                                <option value="laptop">Laptop</option>
                                <option value="tablet">Tablet</option>
                                <option value="desktop">Desktop PC</option>
                                <option value="cctv">CCTV Camera</option>
                                <option value="network">Network Device</option>
                            </datalist>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">
                                {{ __('messages.repair_brand') }}
                            </label>
                            <input type="text" name="brand" list="brands_list" value="{{ old('brand', $repair->brand) }}" maxlength="60"
                                   placeholder="Apple / Samsung / Dell..."
                                   class="w-full h-8 px-2 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-violet-500 outline-none" />
                            <datalist id="brands_list">
                                @foreach ($brands as $b)
                                    <option value="{{ $b->name }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">
                                {{ __('messages.repair_model') }}
                            </label>
                            <input type="text" name="model" list="models_list" value="{{ old('model', $repair->model) }}" maxlength="120"
                                   placeholder="{{ __('messages.repair_model_placeholder') }}"
                                   class="w-full h-8 px-2 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-violet-500 outline-none" />
                            <datalist id="models_list">
                                @foreach ($models as $m)
                                    <option value="{{ $m->name }}"></option>
                                @endforeach
                            </datalist>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">
                                {{ __('messages.repair_imei_serial') }}
                            </label>
                            <input type="text" name="imei_serial" value="{{ old('imei_serial', $repair->imei_serial) }}" maxlength="60"
                                   placeholder="IMEI သို့မဟုတ် Serial No."
                                   class="w-full h-8 px-2 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-mono font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-violet-500 outline-none" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">
                                {{ __('messages.repair_color') }}
                            </label>
                            <input type="text" name="color" list="colors_list" value="{{ old('color', $repair->color) }}" maxlength="40"
                                   placeholder="အရောင်"
                                   class="w-full h-8 px-2 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-violet-500 outline-none" />
                            <datalist id="colors_list">
                                @foreach ($colors as $c)
                                    <option value="{{ $c->name }}"></option>
                                @endforeach
                            </datalist>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">
                                {{ __('messages.repair_storage') }}
                            </label>
                            <input type="text" name="storage" list="storages_list" value="{{ old('storage', $repair->storage) }}" maxlength="40"
                                   placeholder="128GB / 256GB..."
                                   class="w-full h-8 px-2 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-violet-500 outline-none" />
                            <datalist id="storages_list">
                                @foreach ($storages as $st)
                                    <option value="{{ $st->name }}"></option>
                                @endforeach
                            </datalist>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">
                                Pattern / Passcode
                            </label>
                            <input type="text" name="pattern_lock" value="{{ old('pattern_lock', $repair->pattern_lock) }}" maxlength="60"
                                   placeholder="PIN သို့မဟုတ် Pattern"
                                   class="w-full h-8 px-2 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-mono font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-violet-500 outline-none" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">
                            {{ __('messages.repair_reported_problem') }} <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="reported_problem" rows="2" required maxlength="1000"
                                  placeholder="စက်တွင် ဖြစ်ပေါ်နေသော ပြဿနာ သို့မဟုတ် ချွတ်ယွင်းချက်..."
                                  class="w-full p-2 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-violet-500 outline-none resize-y">{{ old('reported_problem', $repair->reported_problem) }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">
                                {{ __('messages.repair_intake_condition') }}
                            </label>
                            <textarea name="intake_condition" rows="2" maxlength="1000"
                                      placeholder="{{ __('messages.repair_intake_placeholder') }}"
                                      class="w-full p-2 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-violet-500 outline-none resize-y">{{ old('intake_condition', $repair->intake_condition) }}</textarea>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">
                                {{ __('messages.repair_accessories') }}
                            </label>
                            <textarea name="accessories" rows="2" maxlength="500"
                                      placeholder="Case, SIM Tray, Charger..."
                                      class="w-full p-2 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-violet-500 outline-none resize-y">{{ old('accessories', $repair->accessories) }}</textarea>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">
                            {{ __('messages.repair_diagnosis') }}
                        </label>
                        <textarea name="diagnosis" rows="2" maxlength="1000"
                                  placeholder="{{ __('messages.repair_diagnosis_placeholder') }}"
                                  class="w-full p-2 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-violet-500 outline-none resize-y">{{ old('diagnosis', $repair->diagnosis) }}</textarea>
                    </div>
                </div>

            </div>

            {{-- ── COLUMN 2: Parts & Services Repeater, Assignment & Pricing ── --}}
            <div class="space-y-0.5 sm:space-y-1">

                {{-- Card 3: 🛠️ Parts & Services Repeater --}}
                <div class="rounded-lg bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 p-2.5 sm:p-3 shadow-xs space-y-2">
                    <div class="flex items-center justify-between pb-1.5 border-b border-slate-100 dark:border-slate-750">
                        <div class="flex items-center gap-1.5 text-xs font-bold text-slate-900 dark:text-white">
                            <span class="text-violet-500">🛠️</span>
                            <span>{{ __('messages.repair_items_section') }}</span>
                        </div>
                        <button type="button" @click="addItem()"
                                class="h-6 px-2 rounded-md text-[11px] font-bold text-violet-700 dark:text-violet-300 bg-violet-50 dark:bg-violet-950/60 border border-violet-200 dark:border-violet-800 hover:bg-violet-100 dark:hover:bg-violet-900/50 transition flex items-center gap-1 cursor-pointer">
                            <span>+</span>
                            <span>{{ __('messages.repair_add_item') }}</span>
                        </button>
                    </div>

                    @if ($deductedItems->isNotEmpty())
                        <div class="p-2 rounded-md bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-[11px] text-emerald-700 dark:text-emerald-300 space-y-1">
                            <div class="font-bold flex items-center gap-1">
                                <span>🔒</span>
                                <span>{{ $deductedItems->count() }} {{ __('messages.repair_part_deducted') }}</span>
                            </div>
                            <div class="space-y-0.5 pl-4">
                                @foreach ($deductedItems as $dit)
                                    <div class="flex justify-between">
                                        <span>• {{ $dit->name }} (×{{ $dit->quantity }})</span>
                                        <span class="font-mono font-bold">{{ format_currency($dit->subtotal, $store) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Repeater Items List --}}
                    <div class="space-y-1.5">
                        <template x-for="(item, i) in items" :key="i">
                            <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 space-y-1.5">
                                <div class="flex items-center gap-1.5">
                                    {{-- Type --}}
                                    <select :name="'items[' + i + '][item_type]'" x-model="item.item_type"
                                            class="w-24 shrink-0 h-7 px-1.5 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-bold text-slate-800 dark:text-slate-200 outline-none">
                                        <option value="part">{{ __('messages.repair_item_part') }}</option>
                                        <option value="service">{{ __('messages.repair_item_service') }}</option>
                                    </select>

                                    {{-- Product selector (if part) --}}
                                    <div class="flex-1 min-w-0" x-show="item.item_type === 'part'">
                                        <select :name="'items[' + i + '][product_id]'" x-model="item.product_id" @change="onProductChange(i)"
                                                class="w-full h-7 px-1.5 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-semibold text-slate-800 dark:text-slate-200 outline-none truncate">
                                            <option value="">-- {{ __('messages.repair_select_product_none') }} --</option>
                                            <template x-for="p in products" :key="p.id">
                                                <option :value="p.id" x-text="p.display_label || p.name"></option>
                                            </template>
                                        </select>
                                    </div>

                                    {{-- Remove button --}}
                                    <button type="button" @click="removeItem(i)"
                                            class="h-7 w-7 rounded grid place-items-center text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 shrink-0 transition"
                                            title="{{ __('messages.repair_remove_item') }}">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>

                                {{-- Item custom name --}}
                                <div>
                                    <input type="text" :name="'items[' + i + '][name]'" x-model="item.name" maxlength="120"
                                           placeholder="{{ __('messages.repair_item_name_placeholder') }}"
                                           class="w-full h-7 px-2 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-semibold text-slate-800 dark:text-slate-200 outline-none" />
                                </div>

                                {{-- Quantity, Unit Price, Subtotal --}}
                                <div class="grid grid-cols-3 gap-1.5 items-center pt-0.5">
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-0.5">{{ __('messages.repair_item_qty') }}</label>
                                        <input type="number" :name="'items[' + i + '][quantity]'" x-model="item.quantity" min="1" step="1"
                                               class="w-full h-7 px-1.5 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-bold font-mono text-slate-800 dark:text-slate-200 outline-none" />
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-0.5">{{ __('messages.repair_item_price') }}</label>
                                        <input type="number" :name="'items[' + i + '][unit_price]'" x-model="item.unit_price" min="0" step="100"
                                               class="w-full h-7 px-1.5 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-bold font-mono text-slate-800 dark:text-slate-200 outline-none" />
                                    </div>
                                    <div class="text-right">
                                        <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-0.5">{{ __('messages.repair_item_subtotal') }}</label>
                                        <div class="h-7 flex items-center justify-end font-mono text-xs font-black text-slate-900 dark:text-white"
                                             x-text="Number(subtotal(i)).toLocaleString()"></div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template x-if="items.length === 0">
                            <div class="p-3 text-center rounded-lg border border-dashed border-slate-200 dark:border-slate-700 text-xs text-slate-500 dark:text-slate-400">
                                {{ __('messages.repair_no_items_yet') }}
                            </div>
                        </template>
                    </div>

                    {{-- Items Total Summary & Action --}}
                    <div class="pt-2 border-t border-slate-100 dark:border-slate-750 flex flex-wrap items-center justify-between gap-2">
                        <div class="text-xs font-bold text-slate-600 dark:text-slate-300 flex items-center gap-1.5">
                            <span>{{ __('messages.repair_items_total') }}:</span>
                            <span class="text-sm font-black font-mono text-violet-600 dark:text-violet-400"
                                  x-text="Number(total()).toLocaleString()"></span>
                            <span class="text-[10px] text-slate-400 font-semibold">{{ $store->currency ?? 'MMK' }}</span>
                        </div>

                        <button type="button" @click="useAsFinal()" x-show="items.length > 0"
                                class="h-6 px-2 rounded text-[11px] font-bold text-sky-700 dark:text-sky-300 bg-sky-50 dark:bg-sky-950/60 border border-sky-200 dark:border-sky-800 hover:bg-sky-100 transition cursor-pointer">
                            ↓ {{ __('messages.repair_use_total_as_charge') }}
                        </button>
                    </div>
                </div>

                {{-- Card 4: 👨‍🔧 Technician Assignment, Pricing & Warranty --}}
                <div class="rounded-lg bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 p-2.5 sm:p-3 shadow-xs space-y-2">
                    <div class="flex items-center justify-between pb-1.5 border-b border-slate-100 dark:border-slate-750">
                        <div class="flex items-center gap-1.5 text-xs font-bold text-slate-900 dark:text-white">
                            <span class="text-indigo-500">👨‍🔧</span>
                            <span>{{ __('messages.repair_assignment_section') }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">
                                {{ __('messages.repair_technician') }}
                            </label>
                            <select name="technician_id"
                                    class="w-full h-8 px-2 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-violet-500 outline-none">
                                <option value="">-- {{ __('messages.repair_unassigned') }} --</option>
                                @foreach ($technicians as $tech)
                                    <option value="{{ $tech->id }}" @selected(old('technician_id', $repair->technician_id) == $tech->id)>
                                        {{ $tech->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">
                                {{ __('messages.repair_voucher_no') }}
                            </label>
                            <input type="text" name="voucher_no" value="{{ old('voucher_no', $repair->voucher_no) }}" maxlength="40"
                                   placeholder="VCH-001"
                                   class="w-full h-8 px-2 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-mono font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-violet-500 outline-none" />
                        </div>
                    </div>

                    {{-- Charges: Estimated & Final --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">
                                {{ __('messages.repair_estimated_charge') }}
                            </label>
                            <div class="relative">
                                <input type="number" name="estimated_charge" value="{{ old('estimated_charge', $repair->estimated_charge) }}" min="0" step="100"
                                       class="w-full h-8 px-2 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-mono font-bold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-violet-500 outline-none" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">
                                {{ __('messages.repair_final_charge') }}
                            </label>
                            <div class="relative">
                                <input type="number" id="repair_final_charge" name="final_charge" value="{{ old('final_charge', $repair->final_charge) }}" min="0" step="100"
                                       class="w-full h-8 px-2 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-mono font-bold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-violet-500 outline-none" />
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">
                                {{ __('messages.repair_estimated_completion') }}
                            </label>
                            <input type="date" name="estimated_completion"
                                   value="{{ old('estimated_completion', $repair->estimated_completion?->format('Y-m-d')) }}"
                                   class="w-full h-8 px-2 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-violet-500 outline-none" />
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">
                                {{ __('messages.repair_warranty_notes') }}
                            </label>
                            <input type="text" name="warranty_notes" value="{{ old('warranty_notes', $repair->warranty_notes) }}" maxlength="1000"
                                   placeholder="{{ __('messages.repair_warranty_placeholder') }}"
                                   class="w-full h-8 px-2 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-violet-500 outline-none" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">
                            {{ __('messages.notes') }}
                        </label>
                        <textarea name="notes" rows="2" maxlength="1000"
                                  placeholder="ဆိုင်အတွင်း သီးသန့်မှတ်ချက်..."
                                  class="w-full p-2 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-semibold text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-violet-500 outline-none resize-y">{{ old('notes', $repair->notes) }}</textarea>
                    </div>
                </div>

            </div>

        </div>

        {{-- ── SECTION 3: Action Toolbar (Bottom) ── --}}
        <div class="rounded-lg bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 p-2 sm:p-2.5 shadow-xs flex items-center justify-between gap-2">
            <a href="{{ route('store.admin.repairs.show', [...$storeRouteParams, 'repair' => $repair->id]) }}"
               class="h-8 px-3 rounded-lg text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-750 transition flex items-center gap-1">
                <span>✕</span>
                <span>{{ __('messages.cancel') }}</span>
            </a>

            <div class="flex items-center gap-1.5 sm:gap-2">
                <a href="{{ route('store.admin.repairs.print', [...$storeRouteParams, 'repair' => $repair->id]) }}" target="_blank"
                   class="h-8 px-2.5 sm:px-3 rounded-lg text-xs font-semibold text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 transition flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2z" />
                    </svg>
                    <span class="hidden sm:inline">{{ __('messages.repair_print_ticket') }}</span>
                </a>

                <button type="submit"
                        class="h-8 px-4 rounded-lg text-xs font-bold text-white bg-violet-600 hover:bg-violet-700 active:scale-[0.98] shadow-xs shadow-violet-500/20 transition flex items-center gap-1.5 cursor-pointer">
                    <span>💾</span>
                    <span>{{ __('messages.repair_save') }}</span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
