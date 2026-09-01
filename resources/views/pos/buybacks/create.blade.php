@extends('layouts.admin.app')

@section('title', __('messages.new_buyback') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
<div x-data="buybackForm()" class="w-full space-y-0.5 pb-6">

    {{-- ============================================================
         1. COMPACT PAGE HEADER — back button & title
         ============================================================ --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
        <div class="flex items-center gap-2.5 min-w-0">
            <a href="{{ route('pos.buybacks.index', $storeRouteParams) }}"
               class="w-7 h-7 rounded-md bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 grid place-items-center text-xs font-bold transition shrink-0"
               title="{{ __('messages.back') }}">
                ←
            </a>
            <div class="min-w-0">
                <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5 truncate">
                    <span>{{ __('messages.new_buyback') }}</span>
                    <span class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 hidden sm:inline">({{ $store->name }})</span>
                </h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                    {{ __('messages.buyback_create_sub') ?? 'Record customer returned goods & buy-back acquisition' }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-1.5 self-start sm:self-auto shrink-0">
            <a href="{{ route('pos.buybacks.index', $storeRouteParams) }}"
               class="h-7 px-3 rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 text-slate-700 dark:text-slate-300 text-xs font-bold transition inline-flex items-center gap-1 cursor-pointer">
                <span>{{ __('messages.cancel') }}</span>
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="p-2 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 rounded-lg text-xs text-rose-700 dark:text-rose-300 shadow-2xs">
            <div class="flex items-center gap-1 font-bold">
                <span>⚠️</span>
                <span>{{ __('messages.validation_error') ?? 'Validation Error' }}</span>
            </div>
            <ul class="list-disc list-inside text-xs pl-2 space-y-0.5 mt-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('pos.buybacks.store', $storeRouteParams) }}" class="space-y-0.5">
        @csrf

        {{-- Customer & Reason Section --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg p-3 shadow-2xs space-y-2">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                <span>👤</span>
                <span>{{ __('messages.customer_info') }}</span>
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">{{ __('messages.customer') }}</label>
                    <select name="customer_id"
                            class="w-full h-8 px-2.5 rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-semibold text-slate-800 dark:text-slate-100 focus:ring-1 focus:ring-sky-500 focus:bg-white dark:focus:bg-slate-900 transition">
                        <option value="">{{ __('messages.walk_in_customer') }}</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }} ({{ $customer->phone ?? 'No phone' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">{{ __('messages.reason') }}</label>
                    <input type="text" name="reason" maxlength="500" value="{{ old('reason') }}"
                           class="w-full h-8 px-2.5 rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-semibold text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-1 focus:ring-sky-500 focus:bg-white dark:focus:bg-slate-900 transition"
                           placeholder="{{ __('messages.buyback_reason_placeholder') ?? 'e.g. Upgrade to new model, Customer trade-in' }}">
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">{{ __('messages.notes') }}</label>
                <input type="text" name="notes" maxlength="500" value="{{ old('notes') }}"
                       class="w-full h-8 px-2.5 rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-semibold text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-1 focus:ring-sky-500 focus:bg-white dark:focus:bg-slate-900 transition"
                       placeholder="{{ __('messages.buyback_notes_placeholder') ?? 'Additional acquisition notes...' }}">
            </div>
        </div>

        {{-- Products / Items Section --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg p-3 shadow-2xs space-y-2">
            <div class="flex items-center justify-between">
                <h2 class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                    <span>📦</span>
                    <span>{{ __('messages.products') }}</span>
                </h2>
                <button type="button" @click="addItem()"
                        class="h-6 px-2 text-[11px] font-bold text-sky-700 dark:text-sky-300 bg-sky-50 dark:bg-sky-950/60 hover:bg-sky-100 dark:hover:bg-sky-900/60 rounded border border-sky-200 dark:border-sky-800 shadow-2xs transition inline-flex items-center gap-1 cursor-pointer">
                    + {{ __('messages.add_item') ?? 'Add Item' }}
                </button>
            </div>

            <div class="space-y-1">
                <template x-for="(item, index) in items" :key="index">
                    <div class="flex items-center gap-1.5 p-1.5 bg-slate-50 dark:bg-slate-800/60 rounded-md border border-slate-200/70 dark:border-slate-700">
                        <div class="flex-1 min-w-[150px]">
                            <select :name="'items[' + index + '][product_id]'" x-model="item.product_id" required
                                    class="w-full h-7 px-2 rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-800 dark:text-slate-100 focus:ring-1 focus:ring-sky-500">
                                <option value="">-- {{ __('messages.select_product') }} --</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-24 shrink-0">
                            <input type="number" :name="'items[' + index + '][quantity]'" x-model.number="item.quantity" min="0.001" step="0.001" required
                                   class="w-full h-7 px-2 rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-mono font-bold text-slate-800 dark:text-slate-100 text-right focus:ring-1 focus:ring-sky-500"
                                   placeholder="{{ __('messages.qty') }}">
                        </div>
                        <div class="w-32 shrink-0">
                            <input type="number" :name="'items[' + index + '][unit_price]'" x-model.number="item.unit_price" min="0" step="1" required
                                   class="w-full h-7 px-2 rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-mono font-bold text-slate-800 dark:text-slate-100 text-right focus:ring-1 focus:ring-sky-500"
                                   placeholder="{{ __('messages.price') }}">
                        </div>
                        <div class="w-28 shrink-0 text-right font-mono text-xs font-black text-slate-900 dark:text-slate-100 tabular-nums">
                            Ks <span x-text="formatRowSubtotal(item)"></span>
                        </div>
                        <button type="button" @click="removeItem(index)"
                                class="w-6 h-6 rounded text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/60 grid place-items-center text-xs font-bold transition shrink-0 cursor-pointer">
                            ✕
                        </button>
                    </div>
                </template>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-slate-200 dark:border-slate-700">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('messages.total') }}:</span>
                <span class="text-sm sm:text-base font-black text-rose-600 dark:text-rose-400 font-outfit tabular-nums">
                    Ks <span x-text="formatTotal()"></span>
                </span>
            </div>
        </div>

        {{-- Bottom Action Buttons --}}
        <div class="flex justify-end gap-1.5 pt-1">
            <a href="{{ route('pos.buybacks.index', $storeRouteParams) }}"
               class="h-8 px-4 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 text-slate-700 dark:text-slate-300 text-xs font-bold transition inline-flex items-center cursor-pointer">
                {{ __('messages.cancel') }}
            </a>
            <button type="submit"
                    class="h-8 px-5 rounded-md bg-sky-600 hover:bg-sky-500 text-white text-xs font-black shadow-2xs hover:shadow-sky-500/20 transition inline-flex items-center gap-1.5 cursor-pointer active:scale-95">
                <span>✓ {{ __('messages.create_buyback') ?? 'Create Buy-Back' }}</span>
            </button>
        </div>
    </form>
</div>

<script nonce="{{ $cspNonce }}">
function buybackForm() {
    return {
        items: [{ product_id: '', quantity: 1, unit_price: 0 }],
        addItem() {
            this.items.push({ product_id: '', quantity: 1, unit_price: 0 });
        },
        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
            }
        },
        formatRowSubtotal(item) {
            const sub = (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0);
            return Math.round(sub).toLocaleString();
        },
        formatTotal() {
            const total = this.items.reduce((sum, item) => sum + ((parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0)), 0);
            return Math.round(total).toLocaleString();
        }
    }
}
</script>
@endsection
