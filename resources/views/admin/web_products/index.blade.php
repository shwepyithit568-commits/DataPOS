@extends('layouts.admin.app')

@section('title', __('messages.web_catalog_title') . ' - ' . ($store->name ?? 'DataPOS'))

@section('content')
<div class="w-full space-y-5 sm:space-y-6"
     x-data="{
        selectedIds: [],
        selectAll: false,
        categoryModal: false,
        loadingId: null,

        toggleSelectAll() {
            if (this.selectAll) {
                this.selectedIds = Array.from(document.querySelectorAll('.product-select-cb')).map(cb => parseInt(cb.value));
            } else {
                this.selectedIds = [];
            }
        },

        toggleAllFromList(ids) {
            this.selectedIds = Array.from(new Set([...this.selectedIds, ...ids]));
        },

        async toggleVisibility(productId, currentVal) {
            this.loadingId = 'vis-' + productId;
            try {
                const res = await fetch('{{ route('store.admin.web_products.toggle_visibility', ['store_slug' => $store->slug]) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ product_id: productId })
                });
                const data = await res.json();
                if (data.success) {
                    const row = document.getElementById('product-row-' + productId);
                    if (row) {
                        const toggleBtn = row.querySelector('.vis-btn');
                        if (data.is_ecommerce) {
                            if (toggleBtn) {
                                toggleBtn.className = 'vis-btn inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition bg-emerald-600 hover:bg-emerald-500 text-white shadow-sm shadow-emerald-600/20';
                                toggleBtn.setAttribute('data-active', '1');
                            }
                        } else {
                            if (toggleBtn) {
                                toggleBtn.className = 'vis-btn inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700';
                                toggleBtn.setAttribute('data-active', '0');
                            }
                        }
                    }
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.loadingId = null;
            }
        },

        async toggleFeatured(productId) {
            this.loadingId = 'feat-' + productId;
            try {
                const res = await fetch('{{ route('store.admin.web_products.toggle_featured', ['store_slug' => $store->slug]) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ product_id: productId })
                });
                const data = await res.json();
                if (data.success) {
                    const btn = document.getElementById('feat-btn-' + productId);
                    if (btn) {
                        if (data.is_featured) {
                            btn.className = 'feat-btn inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-xs font-black transition bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-700/50 hover:bg-amber-200';
                            btn.innerHTML = '<svg class=\'w-3.5 h-3.5 fill-amber-500 text-amber-500\' viewBox=\'0 0 20 20\'><path d=\'M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z\'/></svg> <span>Featured</span>';
                        } else {
                            btn.className = 'feat-btn inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-xs font-bold transition bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700';
                            btn.innerHTML = '<svg class=\'w-3.5 h-3.5 stroke-current fill-none\' viewBox=\'0 0 24 24\' stroke-width=\'2\'><polygon points=\'12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2\'/></svg> <span>Standard</span>';
                        }
                    }
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.loadingId = null;
            }
        }
     }">

    {{-- ============================================================
         PAGE HEADER
         ============================================================ --}}
    <div class="admin-page-header">
        <div class="min-w-0">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-black uppercase tracking-wider bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300 border border-sky-200 dark:border-sky-800/50">
                    Ecommerce Channel
                </span>
                <span class="text-xs text-slate-400 dark:text-slate-500">Live Sync</span>
            </div>
            <h1 class="admin-page-title mt-1">{{ __('messages.web_catalog_title') }}</h1>
            <p class="admin-page-sub mt-1">
                {{ $store->name }} · {{ __('messages.web_catalog_subtitle') }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <button type="button" @click="categoryModal = true"
                    class="admin-secondary-btn">
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <span>{{ __('messages.web_catalog_category_breakdown') }}</span>
            </button>
            <a href="{{ route('storefront.store.home', ['store_slug' => $store->slug]) }}" target="_blank"
               class="admin-primary-btn bg-sky-600 hover:bg-sky-500 shadow-sm shadow-sky-600/20">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                <span>{{ __('messages.web_catalog_preview_storefront') }}</span>
            </a>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 rounded-2xl text-sm text-emerald-800 dark:text-emerald-200 flex items-center gap-3">
            <svg class="w-5 h-5 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- ============================================================
         KPI HAIRLINE GRID
         ============================================================ --}}
    <div class="admin-hairline-grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6">
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-slate-600 dark:text-slate-400">{{ __('messages.web_catalog_total_products') }}</div>
            <div class="admin-stat-value text-slate-800 dark:text-slate-100 font-mono">{{ number_format($stats['total_products']) }}</div>
            <div class="admin-stat-sub">Store inventory total</div>
        </div>
        <div class="admin-hairline-cell bg-emerald-50/40 dark:bg-emerald-950/20">
            <div class="admin-stat-label text-emerald-700 dark:text-emerald-400 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>{{ __('messages.web_catalog_online_products') }}</span>
            </div>
            <div class="admin-stat-value text-emerald-700 dark:text-emerald-300 font-mono">{{ number_format($stats['online_products']) }}</div>
            <div class="admin-stat-sub">Published to Web</div>
        </div>
        <div class="admin-hairline-cell bg-slate-50/50 dark:bg-slate-900/30">
            <div class="admin-stat-label text-slate-500 dark:text-slate-400">{{ __('messages.web_catalog_counter_only') }}</div>
            <div class="admin-stat-value text-slate-600 dark:text-slate-300 font-mono">{{ number_format($stats['counter_only_products']) }}</div>
            <div class="admin-stat-sub">In-store POS counter only</div>
        </div>
        <div class="admin-hairline-cell bg-amber-50/30 dark:bg-amber-950/20">
            <div class="admin-stat-label text-amber-700 dark:text-amber-400">{{ __('messages.web_catalog_featured_products') }}</div>
            <div class="admin-stat-value text-amber-600 dark:text-amber-400 font-mono">{{ number_format($stats['featured_products']) }}</div>
            <div class="admin-stat-sub">Homepage highlight</div>
        </div>
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-sky-600 dark:text-sky-400">{{ __('messages.web_catalog_in_stock_online') }}</div>
            <div class="admin-stat-value text-sky-600 dark:text-sky-400 font-mono">{{ number_format($stats['online_in_stock']) }}</div>
            <div class="admin-stat-sub">Ready to order online</div>
        </div>
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-rose-600 dark:text-rose-400">{{ __('messages.web_catalog_on_sale') }}</div>
            <div class="admin-stat-value text-rose-600 dark:text-rose-400 font-mono">{{ number_format($stats['on_sale_products']) }}</div>
            <div class="admin-stat-sub">Discounted on web</div>
        </div>
    </div>

    {{-- ============================================================
         REUSABLE ADMIN TOOLBAR COMPONENT
         (Matches products, categories, orders, customers pages)
         ============================================================ --}}
    <x-admin.toolbar
        :search="request('search', '')"
        :searchPlaceholder="__('messages.search_by_name_sku_brand_category')"
        :sort="request('sort', 'newest')"
        :sortOptions="[
            'newest'         => __('messages.sort_newest'),
            'oldest'         => __('messages.sort_oldest'),
            'online_first'   => 'Online First',
            'counter_first'  => 'Counter First',
            'featured_first' => 'Featured First',
            'name_asc'       => 'Name (A-Z)',
            'price_asc'      => __('messages.sort_price_low_high'),
            'price_desc'     => __('messages.sort_price_high_low'),
        ]"
        :filters="[
            'visibility' => [
                'label'   => 'Storefront Visibility',
                'options' => [
                    'online'       => __('messages.web_catalog_filter_online'),
                    'counter_only' => __('messages.web_catalog_filter_counter'),
                ]
            ],
            'featured' => [
                'label'   => 'Featured Status',
                'options' => [
                    'featured' => __('messages.web_catalog_filter_featured'),
                    'standard' => __('messages.web_catalog_filter_standard'),
                ]
            ],
            'stock_status' => [
                'label'   => __('messages.stock_status'),
                'options' => [
                    'in_stock'     => __('messages.in_stock'),
                    'low_stock'    => 'Low Stock',
                    'out_of_stock' => __('messages.out_of_stock'),
                ]
            ],
            'category_id' => [
                'label'   => __('messages.categories'),
                'options' => $categories,
                'groups'  => $categoryGroups,
            ],
            'brand_id' => [
                'label'   => __('messages.brands'),
                'options' => $brands,
            ],
            'sale_status' => [
                'label'   => 'Sale / Promo',
                'options' => [
                    'on_sale' => 'On Sale Only',
                    'regular' => 'Regular Price',
                ]
            ],
        ]"
        :showViewToggle="false"
        :showExportImport="false"
        :paginator="$products"
        :perPageOptions="[20 => '20', 50 => '50', 100 => '100', 200 => '200', 'all' => __('messages.all')]"
        :bulkActions="true"
    />

    {{-- ============================================================
         BULK ACTION BAR (ELEVATED SEGMENTED SURFACE STYLE)
         ============================================================ --}}
    <div id="bulk-actions-bar" x-show="selectedIds.length > 0" x-cloak class="bg-white dark:bg-slate-850 dark:bg-slate-800 text-slate-900 dark:text-slate-100 p-2.5 sm:p-3 rounded-2xl shadow-sm text-sm border border-slate-200 dark:border-slate-700 scroll-mt-24">
        <div class="flex flex-wrap items-center gap-2.5 sm:gap-3">
            <div class="flex items-center gap-2 min-w-0">
                <div class="font-black text-slate-800 dark:text-slate-100 whitespace-nowrap">
                    <span x-text="selectedIds.length"></span> {{ __('messages.items_selected') }}
                </div>
                <button type="button" @click="selectAll = false; selectedIds = []"
                    class="min-h-[40px] px-3 py-1.5 bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-black shadow-sm transition">
                    {{ __('messages.cancel') }}
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 ml-auto">
                <button type="button" @click="selectAll = true; toggleAllFromList({{ json_encode($products->pluck('id')) }})"
                    class="min-h-[40px] px-3 py-1.5 bg-violet-600 hover:bg-violet-700 text-white rounded-xl text-xs font-black shadow-sm transition">
                    {{ __('messages.select_all') }}
                </button>

                {{-- Bulk Online --}}
                <form method="POST" action="{{ route('store.admin.web_products.bulk_visibility', ['store_slug' => $store->slug]) }}" class="flex items-center">
                    @csrf
                    <template x-for="id in selectedIds" :key="id">
                        <input type="hidden" name="ids[]" :value="id" />
                    </template>
                    <input type="hidden" name="is_ecommerce" value="1" />
                    <button type="submit" class="min-h-[40px] inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black shadow-sm transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span>{{ __('messages.web_catalog_bulk_publish') }}</span>
                    </button>
                </form>

                {{-- Bulk Counter Only --}}
                <form method="POST" action="{{ route('store.admin.web_products.bulk_visibility', ['store_slug' => $store->slug]) }}" class="flex items-center">
                    @csrf
                    <template x-for="id in selectedIds" :key="id">
                        <input type="hidden" name="ids[]" :value="id" />
                    </template>
                    <input type="hidden" name="is_ecommerce" value="0" />
                    <button type="submit" class="min-h-[40px] inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-slate-600 hover:bg-slate-700 text-white rounded-xl text-xs font-black shadow-sm transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        <span>{{ __('messages.web_catalog_bulk_hide') }}</span>
                    </button>
                </form>

                {{-- Bulk Feature --}}
                <form method="POST" action="{{ route('store.admin.web_products.bulk_featured', ['store_slug' => $store->slug]) }}" class="flex items-center">
                    @csrf
                    <template x-for="id in selectedIds" :key="id">
                        <input type="hidden" name="ids[]" :value="id" />
                    </template>
                    <input type="hidden" name="is_featured" value="1" />
                    <button type="submit" class="min-h-[40px] inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-black shadow-sm transition">
                        <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <span>{{ __('messages.web_catalog_bulk_feature') }}</span>
                    </button>
                </form>

                {{-- Bulk Unfeature --}}
                <form method="POST" action="{{ route('store.admin.web_products.bulk_featured', ['store_slug' => $store->slug]) }}" class="flex items-center">
                    @csrf
                    <template x-for="id in selectedIds" :key="id">
                        <input type="hidden" name="ids[]" :value="id" />
                    </template>
                    <input type="hidden" name="is_featured" value="0" />
                    <button type="submit" class="min-h-[40px] inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-slate-700 hover:bg-slate-800 text-white rounded-xl text-xs font-black shadow-sm transition">
                        <span>{{ __('messages.web_catalog_bulk_unfeature') }}</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ============================================================
         PRODUCTS DATA TABLE
         ============================================================ --}}
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-800/60 text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        <th class="py-3.5 pl-4 pr-2 w-10">
                            <input type="checkbox" x-model="selectAll" @change="toggleSelectAll"
                                   class="rounded border-slate-300 dark:border-slate-700 text-sky-600 focus:ring-sky-500 h-4 w-4">
                        </th>
                        <th class="py-3.5 px-3">Product</th>
                        <th class="py-3.5 px-3">Category / Brand</th>
                        <th class="py-3.5 px-3 text-right">Price</th>
                        <th class="py-3.5 px-3 text-center">Stock</th>
                        <th class="py-3.5 px-3 text-center">Storefront Visibility</th>
                        <th class="py-3.5 px-3 text-center">Featured</th>
                        <th class="py-3.5 pl-3 pr-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                    @forelse($products as $p)
                        <tr id="product-row-{{ $p->id }}" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                            {{-- Checkbox --}}
                            <td class="py-3.5 pl-4 pr-2">
                                <input type="checkbox" :value="{{ $p->id }}" x-model="selectedIds"
                                       class="product-select-cb rounded border-slate-300 dark:border-slate-700 text-sky-600 focus:ring-sky-500 h-4 w-4">
                            </td>

                            {{-- Product info --}}
                            <td class="py-3.5 px-3">
                                <div class="flex items-center gap-3">
                                    @if($p->image_path)
                                        <img src="{{ asset('storage/' . $p->image_path) }}" alt="{{ $p->name }}"
                                             class="w-10 h-10 rounded-xl object-cover border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 shrink-0">
                                    @else
                                        <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-slate-400 shrink-0">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-900 dark:text-slate-100 truncate max-w-xs sm:max-w-sm">
                                            {{ $p->name }}
                                        </div>
                                        <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                                            <span class="font-mono">{{ $p->sku ?: 'No SKU' }}</span>
                                            @if($p->variants->count() > 0)
                                                <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                                                    {{ $p->variants->count() }} Variants
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Category / Brand --}}
                            <td class="py-3.5 px-3">
                                <div class="text-xs text-slate-800 dark:text-slate-200 font-medium">
                                    {{ $p->category->name ?? 'Uncategorized' }}
                                </div>
                                <div class="text-[11px] text-slate-400 dark:text-slate-500">
                                    {{ $p->brand->name ?? 'No Brand' }}
                                </div>
                            </td>

                            {{-- Price & Sale --}}
                            <td class="py-3.5 px-3 text-right">
                                <div class="font-mono font-bold text-slate-900 dark:text-slate-100">
                                    {{ number_format($p->retail_price) }} Ks
                                </div>
                                @if($p->isOnSale())
                                    <div class="flex items-center justify-end gap-1 text-[11px]">
                                        <span class="line-through text-slate-400 font-mono">{{ number_format($p->old_price) }}</span>
                                        <span class="text-rose-600 dark:text-rose-400 font-black">-{{ $p->discountPercent() }}%</span>
                                    </div>
                                @endif
                            </td>

                            {{-- Stock --}}
                            <td class="py-3.5 px-3 text-center">
                                @if($p->stock_status === 'in_stock')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                        In Stock
                                    </span>
                                @elseif($p->stock_status === 'low_stock')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                                        Low Stock
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300">
                                        Out of Stock
                                    </span>
                                @endif
                            </td>

                            {{-- Visibility Toggle --}}
                            <td class="py-3.5 px-3 text-center">
                                <div class="inline-flex flex-col items-center gap-1">
                                    <button type="button"
                                            @click="toggleVisibility({{ $p->id }}, {{ $p->is_ecommerce ? 1 : 0 }})"
                                            :disabled="loadingId === 'vis-{{ $p->id }}'"
                                            data-active="{{ $p->is_ecommerce ? 1 : 0 }}"
                                            class="vis-btn inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $p->is_ecommerce ? 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-sm shadow-emerald-600/20' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700' }}">
                                        <template x-if="loadingId === 'vis-{{ $p->id }}'">
                                            <svg class="animate-spin w-3.5 h-3.5 text-current" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                        </template>
                                        <template x-if="loadingId !== 'vis-{{ $p->id }}'">
                                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                                        </template>
                                        <span>{{ $p->is_ecommerce ? __('messages.web_catalog_status_online') : __('messages.web_catalog_status_counter') }}</span>
                                    </button>
                                </div>
                            </td>

                            {{-- Featured Toggle --}}
                            <td class="py-3.5 px-3 text-center">
                                <button type="button"
                                        id="feat-btn-{{ $p->id }}"
                                        @click="toggleFeatured({{ $p->id }})"
                                        :disabled="loadingId === 'feat-{{ $p->id }}'"
                                        class="feat-btn inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-xs font-bold transition {{ $p->is_featured ? 'bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-700/50 hover:bg-amber-200' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700' }}">
                                    @if($p->is_featured)
                                        <svg class="w-3.5 h-3.5 fill-amber-500 text-amber-500" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                        <span>Featured</span>
                                    @else
                                        <svg class="w-3.5 h-3.5 stroke-current fill-none" viewBox="0 0 24 24" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                        <span>Standard</span>
                                    @endif
                                </button>
                            </td>

                            {{-- Actions --}}
                            <td class="py-3.5 pl-3 pr-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if($p->is_ecommerce)
                                        <a href="{{ route('storefront.product', ['store_slug' => $store->slug, 'slug' => $p->slug]) }}"
                                           target="_blank"
                                           title="View on Storefront"
                                           class="p-1.5 rounded-lg text-slate-500 hover:text-sky-600 dark:hover:text-sky-400 hover:bg-sky-50 dark:hover:bg-sky-950/50 transition">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </a>
                                    @endif
                                    <a href="{{ route('store.admin.products.edit', ['store_slug' => $store->slug, 'product' => $p->id]) }}"
                                       title="Edit Full Product"
                                       class="p-1.5 rounded-lg text-slate-500 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-500 dark:text-slate-400">
                                <div class="max-w-sm mx-auto space-y-3">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto text-slate-400">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/></svg>
                                    </div>
                                    <p class="font-bold text-slate-700 dark:text-slate-300">{{ __('messages.web_catalog_empty') }}</p>
                                    <a href="{{ route('store.admin.web_products.index', ['store_slug' => $store->slug]) }}" class="inline-block text-xs font-bold text-sky-600 dark:text-sky-400 hover:underline">
                                        Reset all filters
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ============================================================
         CATEGORY VISIBILITY BREAKDOWN MODAL
         ============================================================ --}}
    <div x-show="categoryModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div @click.away="categoryModal = false"
             class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-2xl w-full p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">{{ __('messages.web_catalog_category_breakdown') }}</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Products published to web storefront per category</p>
                </div>
                <button type="button" @click="categoryModal = false" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="space-y-3 max-h-96 overflow-y-auto pr-1">
                @forelse($categoryBreakdown as $cat)
                    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60 flex items-center justify-between">
                        <div>
                            <div class="font-bold text-sm text-slate-900 dark:text-slate-100">{{ $cat->name }}</div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">
                                Total {{ $cat->total_count }} products
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                {{ $cat->online_count }} Online
                            </span>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-xs font-bold bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                                {{ $cat->counter_count }} Counter
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-center py-6 text-xs text-slate-400">No categories created yet.</p>
                @endforelse
            </div>

            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 text-right">
                <button type="button" @click="categoryModal = false" class="admin-primary-btn px-4 py-2 text-xs">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
