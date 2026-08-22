@extends('layouts.admin.app')

@section('content')
@php
    // Consumed parts are preserved server-side (they already moved stock) —
    // only editable lines go back into the form payload.
    $editableItems = $repair->items->where('is_deducted', false)->values();
    $productOptions = $products->map(fn ($p) => [
        'id' => $p->id,
        'name' => $p->name . ($p->sku ? " ({$p->sku})" : ''),
        'price' => (float) $p->retail_price,
    ])->values();
@endphp
<div class="max-w-4xl mx-auto space-y-5 sm:space-y-6">
    {{-- Header --}}
    <div class="admin-page-header">
        <div class="flex items-center gap-3">
            <a href="{{ route('store.admin.repairs.show', [...$storeRouteParams, 'repair' => $repair->id]) }}"
               class="text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h1 class="admin-page-title"><span class="font-mono">{{ $repair->job_number }}</span></h1>
                <p class="admin-page-sub">{{ $store->name }} · {{ __('messages.repair_edit') }}</p>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="p-4 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded-xl text-sm text-red-700 dark:text-red-300">
            @foreach ($errors->all() as $error)
                <p>• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('store.admin.repairs.update', [...$storeRouteParams, 'repair' => $repair->id]) }}"
          class="bg-white dark:bg-slate-800 rounded-xl p-5 sm:p-6 space-y-5">
        @csrf
        @method('PUT')

        {{-- ── Customer / Contact ── --}}
        <fieldset class="space-y-4">
            <legend class="text-sm font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                {{ __('messages.repair_customer_section') }}
            </legend>

            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">{{ __('messages.repair_customer_label') }}</label>
                <select name="customer_id" class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">
                    <option value="">{{ __('messages.repair_walk_in') }}</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected(old('customer_id', $repair->customer_id) == $customer->id)>
                            {{ $customer->name }} {{ $customer->phone ? '(' . $customer->phone . ')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">{{ __('messages.repair_contact_name') }}</label>
                    <input type="text" name="contact_name" value="{{ old('contact_name', $repair->contact_name) }}" maxlength="120"
                           class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">{{ __('messages.repair_contact_phone') }}</label>
                    <input type="text" name="contact_phone" value="{{ old('contact_phone', $repair->contact_phone) }}" maxlength="40"
                           class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500" />
                </div>
            </div>
        </fieldset>

        {{-- ── Device ── --}}
        <fieldset class="space-y-4 pt-4 border-t dark:border-slate-700">
            <legend class="text-sm font-bold text-gray-900 dark:text-white mb-2 float-left w-full flex items-center gap-1.5">
                <svg class="w-4 h-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                {{ __('messages.repair_device_section') }}
            </legend>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">{{ __('messages.repair_device_type') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="device_type" value="{{ old('device_type', $repair->device_type) }}" required maxlength="60"
                           class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">{{ __('messages.repair_model') }}</label>
                    <input type="text" name="model" value="{{ old('model', $repair->model) }}" maxlength="120"
                           class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">IMEI / Serial</label>
                    <input type="text" name="imei_serial" value="{{ old('imei_serial', $repair->imei_serial) }}" maxlength="60"
                           class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 font-mono focus:outline-none focus:ring-2 focus:ring-violet-500" />
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">{{ __('messages.repair_reported_problem') }} <span class="text-red-500">*</span></label>
                <textarea name="reported_problem" rows="3" required maxlength="1000"
                          class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">{{ old('reported_problem', $repair->reported_problem) }}</textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">{{ __('messages.repair_intake_condition') }}</label>
                    <textarea name="intake_condition" rows="2" maxlength="1000"
                              class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">{{ old('intake_condition', $repair->intake_condition) }}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">{{ __('messages.repair_accessories') }}</label>
                    <textarea name="accessories" rows="2" maxlength="500"
                              class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">{{ old('accessories', $repair->accessories) }}</textarea>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">{{ __('messages.repair_diagnosis') }}</label>
                <textarea name="diagnosis" rows="3" maxlength="1000" placeholder="{{ __('messages.repair_diagnosis_placeholder') }}"
                          class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">{{ old('diagnosis', $repair->diagnosis) }}</textarea>
            </div>
        </fieldset>

        {{-- ── Parts & Services (Repairs Center parity) ── --}}
        <fieldset class="space-y-4 pt-4 border-t dark:border-slate-700" x-data="{
            items: {{ $editableItems->map(fn ($it) => [
                'item_type' => $it->item_type,
                'name' => $it->name,
                'product_id' => $it->product_id ?? '',
                'quantity' => $it->quantity,
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
                return (parseFloat(this.items[i].unit_price) || 0) * (parseInt(this.items[i].quantity) || 0);
            },
            total() {
                return this.items.reduce((s, it) => s + (parseFloat(it.unit_price) || 0) * (parseInt(it.quantity) || 0), 0);
            },
            useAsFinal() {
                const el = document.getElementById('repair_final_charge');
                if (el) el.value = Math.round(this.total());
            }
        }">
            <legend class="text-sm font-bold text-gray-900 dark:text-white mb-2 float-left w-full flex items-center gap-1.5">
                <svg class="w-4 h-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                {{ __('messages.repair_items_section') }}
            </legend>

            <p class="text-xs text-gray-500 dark:text-slate-400 -mt-2">{{ __('messages.repair_items_hint') }}</p>

            @if ($repair->items->where('is_deducted', true)->isNotEmpty())
                <p class="text-xs text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ $repair->items->where('is_deducted', true)->count() }} {{ __('messages.repair_deducted') }} — {{ __('messages.repair_items_total_hint') }}
                </p>
            @endif

            <template x-if="items.length > 0">
                <div class="space-y-2">
                    <template x-for="(item, i) in items" :key="i">
                        <div class="grid grid-cols-2 sm:grid-cols-12 gap-2 items-center p-2.5 rounded-xl bg-gray-50 dark:bg-slate-900/50 border dark:border-slate-700">
                            <div class="col-span-1 sm:col-span-2">
                                <select :name="'items[' + i + '][item_type]'" x-model="item.item_type"
                                        class="w-full border dark:border-slate-600 rounded-lg px-2 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">
                                    <option value="part">{{ __('messages.repair_item_part') }}</option>
                                    <option value="service">{{ __('messages.repair_item_service') }}</option>
                                </select>
                            </div>
                            <div class="col-span-2 sm:col-span-4">
                                <input type="text" :name="'items[' + i + '][name]'" x-model="item.name" maxlength="120"
                                       placeholder="{{ __('messages.repair_item_name_placeholder') }}"
                                       class="w-full border dark:border-slate-600 rounded-lg px-2 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500" />
                            </div>
                            <div class="col-span-2 sm:col-span-3" x-show="item.item_type === 'part'">
                                <select :name="'items[' + i + '][product_id]'" x-model="item.product_id" @change="onProductChange(i)"
                                        class="w-full border dark:border-slate-600 rounded-lg px-2 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">
                                    <option value="">{{ __('messages.repair_select_product_none') }}</option>
                                    <template x-for="p in products" :key="p.id">
                                        <option :value="p.id" x-text="p.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="col-span-1 sm:col-span-1">
                                <input type="number" :name="'items[' + i + '][quantity]'" x-model="item.quantity" min="1" step="1"
                                       title="{{ __('messages.repair_item_qty') }}"
                                       class="w-full border dark:border-slate-600 rounded-lg px-2 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500" />
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <input type="number" :name="'items[' + i + '][unit_price]'" x-model="item.unit_price" min="0" step="0.01"
                                       title="{{ __('messages.repair_item_price') }}"
                                       class="w-full border dark:border-slate-600 rounded-lg px-2 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500" />
                            </div>
                            <div class="col-span-1 sm:col-span-1 text-right text-sm font-bold text-gray-700 dark:text-slate-200 whitespace-nowrap" x-text="Number(subtotal(i)).toLocaleString()"></div>
                            <div class="col-span-1 sm:col-span-1 text-right">
                                <button type="button" @click="removeItem(i)"
                                        class="inline-flex items-center justify-center p-2 rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-950/40 transition" title="{{ __('messages.repair_remove_item') }}">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <div class="flex flex-wrap items-center gap-3">
                <button type="button" @click="addItem()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-semibold text-violet-700 dark:text-violet-300 bg-violet-50 dark:bg-violet-950/60 border border-violet-200 dark:border-violet-800 rounded-lg hover:bg-violet-100 dark:hover:bg-violet-900/50 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    {{ __('messages.repair_add_item') }}
                </button>
                <template x-if="items.length > 0">
                    <span class="text-sm text-gray-500 dark:text-slate-400 ml-auto">
                        {{ __('messages.repair_items_total') }}:
                        <span class="font-bold text-gray-900 dark:text-white" x-text="Number(total()).toLocaleString()"></span> MMK
                    </span>
                </template>
            </div>

            <template x-if="items.length > 0">
                <div class="flex items-center justify-end">
                    <button type="button" @click="useAsFinal()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-sky-700 dark:text-sky-300 bg-sky-50 dark:bg-sky-950/60 border border-sky-200 dark:border-sky-800 rounded-lg hover:bg-sky-100 dark:hover:bg-sky-900/50 transition">
                        {{ __('messages.repair_use_total_as_charge') }}
                    </button>
                </div>
            </template>
        </fieldset>

        {{-- ── Assignment, Charges & Warranty ── --}}
        <fieldset class="space-y-4 pt-4 border-t dark:border-slate-700">
            <legend class="text-sm font-bold text-gray-900 dark:text-white mb-2 float-left w-full flex items-center gap-1.5">
                <svg class="w-4 h-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                {{ __('messages.repair_assignment_section') }}
            </legend>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">{{ __('messages.repair_technician') }}</label>
                    <select name="technician_id" class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">
                        <option value="">{{ __('messages.repair_unassigned') }}</option>
                        @foreach ($technicians as $tech)
                            <option value="{{ $tech->id }}" @selected(old('technician_id', $repair->technician_id) == $tech->id)>{{ $tech->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">{{ __('messages.repair_voucher_no') }}</label>
                    <input type="text" name="voucher_no" value="{{ old('voucher_no', $repair->voucher_no) }}" maxlength="40"
                           class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 font-mono focus:outline-none focus:ring-2 focus:ring-violet-500" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">{{ __('messages.repair_estimated_charge') }} (MMK)</label>
                    <input type="number" name="estimated_charge" value="{{ old('estimated_charge', $repair->estimated_charge) }}" min="0" step="0.01"
                           class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">{{ __('messages.repair_final_charge') }} (MMK)</label>
                    <input type="number" id="repair_final_charge" name="final_charge" value="{{ old('final_charge', $repair->final_charge) }}" min="0" step="0.01"
                           class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">{{ __('messages.repair_estimated_completion') }}</label>
                    <input type="date" name="estimated_completion"
                           value="{{ old('estimated_completion', $repair->estimated_completion?->format('Y-m-d')) }}"
                           class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">{{ __('messages.repair_warranty_notes') }}</label>
                    <input type="text" name="warranty_notes" value="{{ old('warranty_notes', $repair->warranty_notes) }}" maxlength="1000"
                           placeholder="{{ __('messages.repair_warranty_placeholder') }}"
                           class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500" />
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">{{ __('messages.notes') }}</label>
                <textarea name="notes" rows="2" maxlength="1000"
                          class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">{{ old('notes', $repair->notes) }}</textarea>
            </div>
        </fieldset>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('store.admin.repairs.show', [...$storeRouteParams, 'repair' => $repair->id]) }}"
               class="px-4 py-2.5 text-sm font-semibold text-gray-600 dark:text-slate-300 hover:text-gray-800 dark:hover:text-white transition">
                {{ __('messages.cancel') }}
            </a>
            <button type="submit" class="px-5 py-2.5 text-sm font-semibold text-white bg-violet-600 hover:bg-violet-700 rounded-lg shadow transition cursor-pointer">
                {{ __('messages.repair_save') }}
            </button>
        </div>
    </form>
</div>
@endsection
