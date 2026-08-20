@extends('layouts.admin.app')

@section('content')
<div x-data="buybackForm()" class="max-w-4xl mx-auto">
    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('pos.buybacks.index', $storeRouteParams) }}" class="text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300">← {{ __('messages.back') }}</a>
        <h1 class="text-xl font-semibold text-neutral-900 dark:text-white">↩️ {{ __('messages.new_buyback') }}</h1>
    </div>

    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 rounded-lg text-sm text-red-600 dark:text-red-400">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('pos.buybacks.store', $storeRouteParams) }}" class="space-y-6">
        @csrf

        {{-- Customer Selection --}}
        <div class="bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-xl p-5 space-y-4">
            <h2 class="font-semibold text-neutral-900 dark:text-white">{{ __('messages.customer_info') }}</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">{{ __('messages.customer') }}</label>
                    <select name="customer_id"
                            class="w-full rounded-lg border border-neutral-300 dark:border-white/10 bg-white dark:bg-neutral-800 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 dark:focus:ring-emerald-500 focus:border-transparent">
                        <option value="">{{ __('messages.walk_in_customer') }}</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">{{ __('messages.reason') }}</label>
                    <input type="text" name="reason" maxlength="500"
                           class="w-full rounded-lg border border-neutral-300 dark:border-white/10 bg-white dark:bg-neutral-800 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 dark:focus:ring-emerald-500 focus:border-transparent"
                           placeholder="{{ __('messages.buyback_reason_placeholder') }}">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">{{ __('messages.notes') }}</label>
                <textarea name="notes" rows="2" maxlength="500"
                          class="w-full rounded-lg border border-neutral-300 dark:border-white/10 bg-white dark:bg-neutral-800 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 dark:focus:ring-emerald-500 focus:border-transparent"
                          placeholder="{{ __('messages.buyback_notes_placeholder') }}"></textarea>
            </div>
        </div>

        {{-- Products --}}
        <div class="bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-xl p-5 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-neutral-900 dark:text-white">{{ __('messages.products') }}</h2>
                <button type="button" @click="addItem()"
                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-blue-600 dark:text-emerald-400 bg-blue-50 dark:bg-emerald-500/10 rounded-lg hover:bg-blue-100 dark:hover:bg-emerald-500/20 transition-colors cursor-pointer">
                    + {{ __('messages.add_item') }}
                </button>
            </div>

            <template x-for="(item, index) in items" :key="index">
                <div class="flex items-center gap-3 p-3 bg-neutral-50 dark:bg-white/[2%] rounded-lg border border-neutral-100 dark:border-white/5">
                    <div class="flex-1">
                        <select :name="'items[' + index + '][product_id]'" x-model="item.product_id" required
                                class="w-full rounded-lg border border-neutral-300 dark:border-white/10 bg-white dark:bg-neutral-800 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 dark:focus:ring-emerald-500 focus:border-transparent">
                            <option value="">{{ __('messages.select_product') }}</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-24">
                        <input type="number" :name="'items[' + index + '][quantity]'" x-model="item.quantity" min="0.001" step="0.001" required
                               class="w-full rounded-lg border border-neutral-300 dark:border-white/10 bg-white dark:bg-neutral-800 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 dark:focus:ring-emerald-500 focus:border-transparent"
                               placeholder="{{ __('messages.qty') }}">
                    </div>
                    <div class="w-32">
                        <input type="number" :name="'items[' + index + '][unit_price]'" x-model="item.unit_price" min="0" step="0.01" required
                               class="w-full rounded-lg border border-neutral-300 dark:border-white/10 bg-white dark:bg-neutral-800 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 dark:focus:ring-emerald-500 focus:border-transparent"
                               placeholder="{{ __('messages.price') }}">
                    </div>
                    <button type="button" @click="removeItem(index)"
                            class="inline-flex items-center justify-center w-8 h-8 text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg transition-colors cursor-pointer">
                        ✕
                    </button>
                </div>
            </template>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-neutral-100 dark:border-white/5">
                <span class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('messages.total') }}:</span>
                <span class="text-lg font-bold text-neutral-900 dark:text-white" x-text="formatTotal()"></span>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('pos.buybacks.index', $storeRouteParams) }}"
               class="px-4 py-2 text-sm font-medium text-neutral-700 dark:text-neutral-300 bg-neutral-100 dark:bg-white/5 rounded-lg hover:bg-neutral-200 dark:hover:bg-white/10 transition-colors">
                {{ __('messages.cancel') }}
            </a>
            <button type="submit"
                    class="px-6 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 dark:bg-emerald-600 dark:hover:bg-emerald-700 rounded-lg transition-colors cursor-pointer">
                {{ __('messages.create_buyback') }}
            </button>
        </div>
    </form>
</div>

<script>
function buybackForm() {
    return {
        items: [{ product_id: '', quantity: 1, unit_price: 0 }],
        addItem() {
            this.items.push({ product_id: '', quantity: 1, unit_price: 0 });
        },
        removeItem(index) {
            if (this.items.length > 1) this.items.splice(index, 1);
        },
        formatTotal() {
            const total = this.items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
            return total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    }
}
</script>
@endsection
