{{-- ── Variant picker modal ─────────────────────────────────────── --}}
<div x-show="variantProduct" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/25 dark:bg-black/35 p-0 sm:p-4 transition-opacity" @click.self="variantProduct = null" @keydown.escape.window="variantProduct = null">
    <div class="relative w-full max-w-full sm:max-w-md rounded-t-3xl rounded-b-none sm:rounded-2xl bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700 sm:border p-5 shadow-2xl max-h-[88dvh] overflow-y-auto pb-[calc(1.5rem+env(safe-area-inset-bottom,0px))]">
        <div class="flex items-center justify-between mb-3">
            <div class="min-w-0">
                <h3 class="text-base font-black" x-text="labels.select_variant"></h3>
                <p class="text-sm text-slate-500 truncate" x-text="variantProduct ? variantProduct.name : ''"></p>
            </div>
            <button type="button" @click="variantProduct = null" class="sf-btn-3d w-8 h-8 rounded-lg flex items-center justify-center text-slate-500 font-bold cursor-pointer transition">✕</button>
        </div>
        <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
            <template x-for="v in variantProduct ? variantProduct.variants : []" :key="v.id">
                <button type="button" @click="addVariant(v)" :disabled="parseFloat(v.balance) <= 0"
                        class="sf-btn-3d w-full text-left rounded-xl px-3 py-2.5 flex items-center justify-between gap-2 transition cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
                        :class="parseFloat(v.balance) > 0 ? '' : 'opacity-60'">
                    <span class="min-w-0">
                        <span class="block text-sm font-bold truncate" x-text="v.name"></span>
                        <span class="block text-[11px] text-slate-500 font-mono" x-text="v.sku || ''"></span>
                    </span>
                    <span class="shrink-0 text-right">
                        <span class="block text-[10px] text-rose-500 font-bold line-through" x-show="variantProduct.tier === 'wholesale' && parseFloat(v.retail_price) > parseFloat(v.price)" x-text="formatCurrency(v.retail_price)"></span>
                        <span class="block text-sm font-black text-blue-600 dark:text-blue-400" x-text="formatCurrency(v.price)"></span>
                        <span class="block text-[10px] font-bold"
                              :class="parseFloat(v.balance) <= 0 ? 'text-rose-500' : (parseFloat(v.balance) <= 5 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400')"
                              x-text="stockPillText(v.balance)"></span>
                    </span>
                </button>
            </template>
        </div>
    </div>
</div>
