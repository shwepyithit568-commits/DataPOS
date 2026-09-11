{{-- LEFT: product grid --}}
<section class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-sm min-w-0"
         x-data="{ gridMode: localStorage.getItem('pos_grid_mode') || 'normal' }"
         x-init="$watch('gridMode', v => localStorage.setItem('pos_grid_mode', v))">
    <div class="flex items-center justify-between gap-3 mb-2">
        <div class="flex items-center gap-2">
            <h2 class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('messages.pos_products') }}</h2>
            <span class="text-xs font-semibold text-slate-400" x-show="gridLoading">…</span>
        </div>
        <div class="flex items-center gap-2">
            {{-- Grid / List mode switcher (visible on all screens including mobile) --}}
            <div class="flex items-center gap-1 rounded-xl bg-slate-100 dark:bg-slate-800 p-1">
                <button type="button" @click="gridMode = 'grid'"
                        :class="gridMode !== 'list' ? 'sf-btn-3d-primary active' : 'sf-btn-3d text-slate-500 dark:text-slate-400'"
                        class="w-8 h-8 rounded-lg transition grid place-items-center cursor-pointer"
                        aria-label="Grid View"
                        title="Grid View (၂ ကော်လံပုံစံ)">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                </button>
                <button type="button" @click="gridMode = 'list'"
                        :class="gridMode === 'list' ? 'sf-btn-3d-primary active' : 'sf-btn-3d text-slate-500 dark:text-slate-400'"
                        class="w-8 h-8 rounded-lg transition grid place-items-center cursor-pointer"
                        aria-label="List View"
                        title="List View (စာရင်းပုံစံ)">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Product cards (grid / list mode) --}}
    {{-- GRID MODE (Mobile: 2 columns, Tablet: 3-4 columns, Desktop: 5 columns) --}}
    <div x-show="gridMode !== 'list'"
         class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-3 xl:grid-cols-4 min-[1350px]:grid-cols-5 2xl:grid-cols-6 min-[1900px]:grid-cols-7 gap-0.5 max-h-[58vh] overflow-y-auto pr-1 pb-1">
        <template x-for="p in products" :key="p.id">
            <div class="rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden p-2 flex flex-col justify-between transition hover:shadow-md hover:-translate-y-0.5 active:scale-[.98]"
                 :class="parseFloat(p.balance) > 0 ? '' : 'opacity-55'">
                {{-- Image section --}}
                <div class="relative aspect-[4/3] rounded-lg bg-slate-100 dark:bg-slate-900/70 grid place-items-center overflow-hidden mb-1.5">
                    <template x-if="p.image">
                        <img :src="p.image" alt="" loading="lazy" class="absolute inset-0 w-full h-full object-contain p-2">
                    </template>
                    <template x-if="!p.image">
                        <svg class="w-8 h-8 text-slate-300 dark:text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    </template>

                    {{-- Stock status badge (top-right) --}}
                    <span class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[8.5px] font-black text-white shadow-sm"
                          :class="stockPillClass(p.balance)"
                          x-text="stockPillText(p.balance)"></span>

                    {{-- Variants badge (top-left) --}}
                    <span x-show="p.variants && p.variants.length > 0" x-cloak
                          class="absolute top-1.5 left-1.5 px-1.5 py-0.5 rounded bg-blue-600 text-white text-[8.5px] font-black shadow-sm"
                          x-text="'↕ ' + p.variants.length + ' ' + labels.variant"></span>

                    {{-- Category badge (bottom-left) --}}
                    <span x-show="p.category" x-cloak
                          class="absolute bottom-1.5 left-1.5 px-1.5 py-0.5 rounded bg-white/90 dark:bg-slate-900/90 text-[8px] font-black uppercase tracking-wider text-slate-500 border border-slate-200 dark:border-slate-700 shadow-sm"
                          x-text="p.category"></span>
                </div>

                {{-- Info section --}}
                <div class="flex-1 flex flex-col justify-between">
                    <p class="text-xs sm:text-[13px] font-bold leading-snug line-clamp-2 min-h-[2.4em] text-slate-800 dark:text-slate-200" x-text="p.name"></p>
                    <div class="mt-1.5 flex items-end justify-between gap-1.5">
                        <div class="min-w-0">
                            {{-- Retail/walk-in: show the sale (old) price struck through --}}
                            <p class="text-[10px] text-rose-500 font-bold line-through truncate" x-show="p.tier !== 'wholesale' && p.old_price && parseFloat(p.old_price) > parseFloat(p.price)" x-text="formatCurrency(p.old_price)"></p>
                            {{-- Wholesale tier: strike the retail price the shopper is NOT paying --}}
                            <p class="text-[10px] text-rose-500 font-bold line-through truncate" x-show="p.tier === 'wholesale' && parseFloat(p.retail_price) > parseFloat(p.price)" x-text="formatCurrency(p.retail_price)"></p>
                            <p class="text-xs sm:text-sm font-extrabold text-blue-600 dark:text-blue-400 leading-tight" x-text="formatCurrency(p.price)"></p>
                            <p class="text-[9px] font-black text-amber-600 dark:text-amber-400 truncate"
                               x-show="p.tier === 'wholesale' && parseFloat(p.retail_price) > parseFloat(p.price)"
                               x-text="'−' + formatCurrency(parseFloat(p.retail_price) - parseFloat(p.price))"></p>
                        </div>
                        <button type="button" @click="addProduct(p)" :disabled="parseFloat(p.balance) <= 0"
                                class="sf-btn-3d-primary shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded-lg grid place-items-center cursor-pointer"
                                :title="p.variants && p.variants.length > 0 ? labels.select_variant : labels.add_to_cart">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- LIST MODE (Mobile: 1 column, Desktop: 2 columns, Gap: 2px) --}}
    <div x-show="gridMode === 'list'"
         class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2 gap-0.5 max-h-[58vh] overflow-y-auto pr-1 pb-1">
        <template x-for="p in products" :key="'list-' + p.id">
            <div class="flex items-center gap-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-2.5 py-1.5 hover:border-blue-400 hover:shadow-sm transition"
                 :class="parseFloat(p.balance) > 0 ? '' : 'opacity-50'">
                {{-- Thumbnail --}}
                <div class="shrink-0 w-9 h-9 rounded-md bg-slate-100 dark:bg-slate-900/70 grid place-items-center overflow-hidden">
                    <template x-if="p.image">
                        <img :src="p.image" alt="" loading="lazy" class="w-full h-full object-contain p-0.5">
                    </template>
                    <template x-if="!p.image">
                        <svg class="w-4 h-4 text-slate-300 dark:text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    </template>
                </div>
                {{-- Name + SKU --}}
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold truncate text-slate-800 dark:text-slate-200" x-text="p.name"></p>
                    <p class="text-[10px] text-slate-400 font-mono truncate" x-show="p.sku" x-text="p.sku || ''"></p>
                </div>
                {{-- Stock badge --}}
                <span class="shrink-0 px-1.5 py-0.5 rounded text-[9px] font-black text-white"
                      :class="stockPillClass(p.balance)"
                      x-text="stockPillText(p.balance)"></span>
                {{-- Price --}}
                <p class="shrink-0 text-xs sm:text-sm font-extrabold text-blue-600 dark:text-blue-400 tabular-nums" x-text="formatCurrency(p.price)"></p>
                {{-- Add button --}}
                <button type="button" @click="addProduct(p)" :disabled="parseFloat(p.balance) <= 0"
                        class="sf-btn-3d-primary shrink-0 w-8 h-8 rounded-lg grid place-items-center cursor-pointer"
                        :title="p.variants && p.variants.length > 0 ? labels.select_variant : labels.add_to_cart">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                </button>
            </div>
        </template>
    </div>

    {{-- Empty state --}}
    <div x-show="!gridLoading && !products.length"
         class="rounded-xl border border-dashed border-slate-300 dark:border-slate-700 p-10 text-center text-sm text-slate-500 dark:text-slate-400">
        <svg class="inline w-4 h-4 -mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        <span x-text="labels.no_products"></span>
    </div>
</section>
