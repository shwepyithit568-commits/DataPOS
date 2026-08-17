@extends('layouts.admin.app')

@section('content')
<div class="w-full space-y-6"
    x-data="{
        viewMode: localStorage.getItem('admin_view_mode') || 'table',
        selectedIds: [],
        selectAll: false,
        priceFormOpen: false,
        toastShow: false,
        toastMsg: '',
        detailsOpen: false,
        detailsLoading: false,
        detailsHtml: '',
        openDetails(url) {
            this.detailsOpen = true;
            this.detailsLoading = true;
            this.detailsHtml = '';
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                .then(r => r.text())
                .then(html => { this.detailsHtml = html; this.detailsLoading = false; })
                .catch(() => {
                    this.detailsHtml = '⚠️ Failed to load product details.';
                    this.detailsLoading = false;
                });
        },
        setDetailsTab(name) {
            this.$refs.detailsBody.querySelectorAll('[data-spec-tab]').forEach(t => {
                const active = t.getAttribute('data-spec-tab') === name;
                t.setAttribute('aria-selected', active ? 'true' : 'false');
                t.classList.toggle('border-sky-500', active);
                t.classList.toggle('text-sky-600', active);
                t.classList.toggle('dark:text-sky-400', active);
                t.classList.toggle('border-transparent', !active);
                t.classList.toggle('text-gray-500', !active);
                t.classList.toggle('dark:text-slate-400', !active);
            });
            this.$refs.detailsBody.querySelectorAll('[data-spec-panel]').forEach(p => {
                p.hidden = p.getAttribute('data-spec-panel') !== name;
            });
        },
        onDetailsClick(e) {
            const tab = e.target.closest('[data-spec-tab]');
            if (tab) this.setDetailsTab(tab.getAttribute('data-spec-tab'));
        },
        onDetailsKeydown(e) {
            const el = document.activeElement;
            if (!el || !el.matches('[data-spec-tab]')) return;
            const names = ['description', 'specifications'];
            const i = names.indexOf(el.getAttribute('data-spec-tab'));
            if (i === -1) return;
            let next = null;
            if (e.key === 'ArrowRight') next = names[(i + 1) % names.length];
            else if (e.key === 'ArrowLeft') next = names[(i - 1 + names.length) % names.length];
            else if (e.key === 'Home') next = names[0];
            else if (e.key === 'End') next = names[names.length - 1];
            if (!next) return;
            e.preventDefault();
            this.setDetailsTab(next);
            const target = this.$refs.detailsBody.querySelector('[data-spec-tab=' + next + ']');
            if (target) target.focus();
        },
        showToast(msg) {
            this.toastMsg = msg;
            this.toastShow = true;
            clearTimeout(this._toastTimer);
            this._toastTimer = setTimeout(() => this.toastShow = false, 2600);
        },
        toggleAll(allIds) {
            if (this.selectAll) {
                this.selectedIds = [...allIds];
            } else {
                this.selectedIds = [];
            }
        }
    }"
    @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_view_mode', $event.detail)"
    @bulk-actions-request.window="
        if (selectedIds.length === 0) {
            showToast('First select products using the checkboxes');
            document.getElementById('product-table')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else {
            $nextTick(() => document.getElementById('bulk-actions-bar')?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
        }
    ">

    <div class="admin-page-header">
        <div>
            <h1 class="admin-page-title">Product Management</h1>
            <p class="admin-page-sub">Search, filter, bulk-edit and manage your catalog</p>
        </div>
    </div>

    @if (session('success'))
        <div class="p-4 bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-800 rounded-md text-sm text-green-700 dark:text-green-300">{{ session('success') }}</div>
    @endif

    {{-- Reusable Admin Toolbar Component --}}
    @php
        // Export follows the selected per-page size (50 / 100 / 200 / all) so
        // admins can choose how many rows land in the Excel CSV.
        $exportUrl = url('/store/' . $store->slug . '/admin/products/export');
        if (request()->has('per_page')) {
            $exportUrl .= '?' . http_build_query(['per_page' => request('per_page')]);
        }
    @endphp
    <x-admin.toolbar
        :search="request('search', '')"
        searchPlaceholder="Search by name, SKU, brand, category..."
        :sort="request('sort', 'newest')"
        :sortOptions="[
            'newest' => 'Newest',
            'oldest' => 'Oldest',
            'price_asc' => 'Price: Low to High',
            'price_desc' => 'Price: High to Low',
            'stock' => 'Stock Status',
        ]"
        :filters="[
            'stock_status' => [
                'label' => 'Stock Status',
                'options' => ['in_stock' => 'In Stock', 'out_of_stock' => 'Out of Stock']
            ],
            'is_ecommerce' => [
                'label' => 'Online Visibility',
                'options' => ['online' => 'Online', 'counter_only' => 'Counter only']
            ],
            'category_id' => [
                'label' => 'Category',
                'options' => $categories,
                'groups' => $categoryGroups,
            ],
            'brand_id' => [
                'label' => 'Brand',
                'options' => $brands
            ]
        ]"
        :showViewToggle="true"
        :importUrl="url('/store/' . $store->slug . '/admin/products/import')"
        :exportUrl="$exportUrl"
        :paginator="$products"
        :perPageOptions="[50 => '50', 100 => '100', 200 => '200', 'all' => 'All']"
        :bulkActions="true"
    />

    {{-- Bulk Action Floating Bar --}}
    <div id="bulk-actions-bar" x-show="selectedIds.length > 0" x-cloak class="bg-violet-900 dark:bg-slate-800 text-white p-3 rounded-lg shadow-lg text-sm border border-violet-700 dark:border-slate-600 scroll-mt-24">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <div class="font-medium">
                    <span x-text="selectedIds.length"></span> items selected
                </div>
                <button type="button" @click="selectAll = false; selectedIds = []; priceFormOpen = false"
                    class="px-2.5 py-1 bg-gray-600 hover:bg-gray-700 rounded text-xs font-semibold shadow">Cancel</button>
            </div>
            <div class="flex items-center space-x-3">
                <button type="button" @click="selectAll = true; toggleAll({{ json_encode($products->pluck('id')) }})"
                    class="px-2.5 py-1 bg-violet-700 hover:bg-violet-600 rounded text-xs font-semibold shadow">Select All</button>

                <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/bulk-stock') }}" class="flex items-center space-x-1">
                    @csrf
                    <template x-for="id in selectedIds" :key="id">
                        <input type="hidden" name="ids[]" :value="id" />
                    </template>
                    <input type="hidden" name="stock_status" value="in_stock" />
                    <button type="submit" class="px-2.5 py-1 bg-green-600 hover:bg-green-700 rounded text-xs font-semibold shadow">Set In Stock</button>
                </form>

                <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/bulk-stock') }}" class="flex items-center space-x-1">
                    @csrf
                    <template x-for="id in selectedIds" :key="id">
                        <input type="hidden" name="ids[]" :value="id" />
                    </template>
                    <input type="hidden" name="stock_status" value="out_of_stock" />
                    <button type="submit" class="px-2.5 py-1 bg-yellow-600 hover:bg-yellow-700 rounded text-xs font-semibold shadow">Set Out of Stock</button>
                </form>

                <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/bulk-ecommerce') }}" class="flex items-center space-x-1">
                    @csrf
                    <template x-for="id in selectedIds" :key="id">
                        <input type="hidden" name="ids[]" :value="id" />
                    </template>
                    <input type="hidden" name="is_ecommerce" value="1" />
                    <button type="submit" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 rounded text-xs font-semibold shadow">🛒 Sell Online</button>
                </form>

                <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/bulk-ecommerce') }}" class="flex items-center space-x-1">
                    @csrf
                    <template x-for="id in selectedIds" :key="id">
                        <input type="hidden" name="ids[]" :value="id" />
                    </template>
                    <input type="hidden" name="is_ecommerce" value="0" />
                    <button type="submit" class="px-2.5 py-1 bg-gray-600 hover:bg-gray-700 rounded text-xs font-semibold shadow">🚫 Counter only</button>
                </form>

                <button type="button" @click="priceFormOpen = !priceFormOpen"
                    class="px-2.5 py-1 bg-sky-600 hover:bg-sky-700 rounded text-xs font-semibold shadow"
                    :class="priceFormOpen ? 'ring-2 ring-sky-300' : ''">
                    Adjust Prices
                </button>

                <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/bulk-delete') }}" data-confirm="Are you sure you want to delete selected products?" class="inline">
                    @csrf
                    <template x-for="id in selectedIds" :key="id">
                        <input type="hidden" name="ids[]" :value="id" />
                    </template>
                    <button type="submit" class="px-2.5 py-1 bg-red-600 hover:bg-red-700 rounded text-xs font-semibold shadow">Bulk Delete</button>
                </form>
            </div>
        </div>

        {{-- Bulk Price Adjustment Form --}}
        <div x-show="priceFormOpen" x-transition x-cloak class="mt-3 pt-3 border-t border-violet-700/40">
            <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/bulk-prices') }}" class="flex flex-wrap items-end gap-2">
                @csrf
                <template x-for="id in selectedIds" :key="id">
                    <input type="hidden" name="ids[]" :value="id" />
                </template>
                <div>
                    <label class="block text-xs uppercase tracking-wide text-violet-200 mb-1">Apply to</label>
                    <select name="apply_to" class="text-xs rounded border bg-white text-gray-900 px-2 py-1.5">
                        <option value="both">Retail &amp; Wholesale</option>
                        <option value="retail">Retail only</option>
                        <option value="wholesale">Wholesale only</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-wide text-violet-200 mb-1">Direction</label>
                    <select name="direction" class="text-xs rounded border bg-white text-gray-900 px-2 py-1.5">
                        <option value="increase">Increase</option>
                        <option value="decrease">Decrease</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-wide text-violet-200 mb-1">Mode</label>
                    <select name="mode" class="text-xs rounded border bg-white text-gray-900 px-2 py-1.5">
                        <option value="percent">Percentage (%)</option>
                        <option value="amount">Fixed amount (Ks)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-wide text-violet-200 mb-1">Value</label>
                    <input type="number" name="value" required min="0" step="100" placeholder="e.g. 10 or 1000"
                        class="text-xs rounded border bg-white text-gray-900 px-2 py-1.5 w-32 placeholder-gray-400" />
                </div>
                <button type="submit" class="px-3 py-1.5 bg-sky-500 hover:bg-sky-600 rounded text-xs font-semibold shadow">Apply Prices</button>
            </form>
        </div>
    </div>

    {{-- View 1: Table View --}}
    <div id="product-table" x-show="viewMode === 'table'" class="admin-panel overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600 dark:text-slate-300">
            <thead class="bg-gray-50 dark:bg-slate-900/50 border-b dark:border-slate-700 font-semibold text-gray-700 dark:text-slate-200">
                <tr>
                    <th class="p-3 w-10">
                        <input type="checkbox" x-model="selectAll" @change="toggleAll({{ json_encode($products->pluck('id')) }})" class="rounded border-gray-300 dark:border-slate-600 dark:bg-slate-800" />
                    </th>
                    <th class="p-3">Image</th>
                    <th class="p-3">Name / SKU</th>
                    <th class="p-3">Category / Brand</th>
                    <th class="p-3">Prices (Retail / Wholesale)</th>
                    <th class="p-3">Stock Status</th>
                    <th class="p-3">Featured</th>
                    <th class="p-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y dark:divide-slate-700">
                @forelse ($products as $product)
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-700/50 transition">
                        <td class="p-3">
                            <input type="checkbox" :value="{{ $product->id }}" x-model="selectedIds" class="rounded border-gray-300 dark:border-slate-600 dark:bg-slate-800" />
                        </td>
                        <td class="p-3">
                            @if ($product->image_path)
                                <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}" class="h-10 w-10 object-cover rounded border dark:border-slate-600" />
                            @else
                                <div class="h-10 w-10 bg-gray-100 dark:bg-slate-700 rounded border dark:border-slate-600 flex items-center justify-center text-xs text-gray-400 dark:text-slate-400">No Img</div>
                            @endif
                        </td>
                        <td class="p-3">
                            <div class="font-bold text-gray-900 dark:text-slate-100">{{ $product->name }}</div>
                            <div class="text-xs text-gray-400 dark:text-slate-400">SKU: {{ $product->sku }}</div>
                        </td>
                        <td class="p-3">
                            <div class="text-xs font-medium text-gray-800 dark:text-slate-200">{{ $product->category->name ?? '—' }}</div>
                            <div class="text-xs text-gray-400 dark:text-slate-400">{{ $product->brand->name ?? '—' }}</div>
                        </td>
                        <td class="p-3">
                            <div>Retail: Ks {{ number_format($product->retail_price) }}</div>
                            <div class="text-green-600 dark:text-green-400 font-semibold">Wholesale: {{ $product->wholesale_price > 0 ? 'Ks ' . number_format($product->wholesale_price) : '—' }}</div>
                        </td>
                        <td class="p-3">
                            <span class="px-2 py-0.5 text-xs rounded font-semibold {{ $product->isInStock() ? 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300' }}">
                                {{ $product->stock_status }}
                            </span>
                        </td>
                        <td class="p-3">
                            <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/' . $product->id . '/toggle-featured') }}" class="inline">
                                @csrf
                                <button type="submit" class="px-2 py-0.5 text-xs rounded font-semibold {{ $product->is_featured ? 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-700 dark:text-yellow-300 hover:bg-yellow-200' : 'bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-400 hover:bg-gray-200' }}">
                                    {{ $product->is_featured ? '⭐ Featured' : '☆ Feature' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/' . $product->id . '/toggle-ecommerce') }}" class="inline">
                                @csrf
                                <button type="submit" class="px-2 py-0.5 text-xs rounded font-semibold {{ $product->is_ecommerce ? 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-200' : 'bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-400 hover:bg-gray-200' }}">
                                    {{ $product->is_ecommerce ? '🛒 Online' : '🚫 Counter only' }}
                                </button>
                            </form>
                        </td>
                        <td class="p-3">
                            <div class="flex items-center space-x-2">
                                <button type="button" @click="openDetails('{{ url('/store/' . $store->slug . '/admin/products/' . $product->id . '/details') }}')" class="text-teal-600 dark:text-teal-400 hover:underline font-medium">View</button>
                                <a href="{{ url('/store/' . $store->slug . '/admin/products/' . $product->id . '/edit') }}" class="text-violet-600 dark:text-violet-400 hover:underline font-medium">Edit</a>
                                <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/' . $product->id . '/duplicate') }}" class="inline">
                                    @csrf
                                    <button type="submit" title="Duplicate this product" class="text-sky-600 dark:text-sky-400 hover:underline font-medium">Copy</button>
                                </form>
                                <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/' . $product->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:underline font-medium">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="p-4 text-center text-gray-500 dark:text-slate-400">No products found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- View 2: Card View — Mobile: 2 cols, Tablet: 3 cols, Desktop: 4 cols --}}
    <div x-show="viewMode === 'card'" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-2">
        @forelse ($products as $product)
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 overflow-hidden transition-colors duration-200 hover:shadow-md">
                {{-- Card header: image + product info --}}
                <div class="p-3 sm:p-4">
                    <div class="relative mb-2">
                        @if ($product->image_path)
                            <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}" class="h-32 sm:h-40 w-full object-cover rounded-md border dark:border-slate-600" />
                        @else
                            <div class="h-32 sm:h-40 w-full bg-gray-100 dark:bg-slate-700 rounded-md border dark:border-slate-600 flex items-center justify-center text-xs text-gray-400 dark:text-slate-400">No Image</div>
                        @endif
                        <div class="absolute top-2 left-2 z-10">
                            <input type="checkbox" :value="{{ $product->id }}" x-model="selectedIds" class="rounded border-gray-300 dark:border-slate-600 dark:bg-slate-800" />
                        </div>
                        <span class="absolute top-2 right-2 z-10 px-1.5 py-0.5 rounded font-semibold text-[11px] {{ $product->isInStock() ? 'bg-green-100 dark:bg-green-900/80 text-green-700 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/80 text-red-700 dark:text-red-300' }}">
                            {{ $product->isInStock() ? 'In Stock' : 'Out of Stock' }}
                        </span>
                    </div>
                    <div class="font-bold text-gray-900 dark:text-slate-100 text-sm break-words" title="{{ $product->name }}">{{ $product->name }}</div>
                    <div class="text-[11px] text-gray-400 dark:text-slate-500 truncate font-mono">SKU: {{ $product->sku }}</div>
                    <div class="text-[11px] text-gray-500 dark:text-slate-400 truncate">{{ $product->category->name ?? '—' }} · {{ $product->brand->name ?? '—' }}</div>
                    <div class="mt-1.5 flex items-center gap-2">
                        <span class="text-sm font-bold text-gray-900 dark:text-slate-100">Ks {{ number_format($product->retail_price) }}</span>
                        @if ($product->wholesale_price > 0)
                            <span class="text-[11px] font-semibold text-green-600 dark:text-green-400">WS: Ks {{ number_format($product->wholesale_price) }}</span>
                        @endif
                    </div>
                </div>
                {{-- Card action row --}}
                <div class="flex items-center gap-2 px-3 sm:px-4 py-2.5 border-t border-gray-100 dark:border-slate-700/60">
                    <button type="button" @click="openDetails('{{ url('/store/' . $store->slug . '/admin/products/' . $product->id . '/details') }}')"
                        class="min-h-11 inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-teal-600 dark:text-teal-400 hover:bg-teal-50 dark:hover:bg-teal-950/40 transition">
                        View
                    </button>
                    <a href="{{ url('/store/' . $store->slug . '/admin/products/' . $product->id . '/edit') }}"
                        class="min-h-11 inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-violet-600 dark:text-violet-400 hover:bg-violet-50 dark:hover:bg-violet-950/40 transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.4-9.4a2 2 0 1 1 2.8 2.8L11 14l-4 1 1-4 9.6-9.4Z"/></svg>
                        Edit
                    </a>
                    <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/' . $product->id . '/duplicate') }}" class="inline">
                        @csrf
                        <button type="submit" title="Duplicate this product"
                            class="min-h-11 inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-sky-600 dark:text-sky-400 hover:bg-sky-50 dark:hover:bg-sky-950/40 transition">
                            Copy
                        </button>
                    </form>
                    <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/' . $product->id) }}" class="ml-auto">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="min-h-11 inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40 transition">
                            Del
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white dark:bg-slate-800 p-6 rounded-lg text-center text-gray-500 dark:text-slate-400">No products found.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $products->links() }}</div>

    {{-- Product Details Modal — Description | Specifications (shared presenter) --}}
    <div x-show="detailsOpen" x-cloak x-transition.opacity class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-label="Product details" @keydown.escape.window="detailsOpen = false">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="detailsOpen = false"></div>
        <div class="relative flex min-h-full items-start justify-center p-4 sm:p-6">
            <div class="relative w-full max-w-2xl rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-800">
                <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-slate-700 sm:px-5">
                    <h2 class="text-sm font-black uppercase tracking-wide text-gray-700 dark:text-slate-200">{{ __('messages.product_details') }}</h2>
                    <button type="button" @click="detailsOpen = false" aria-label="Close"
                        class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-700 dark:hover:text-slate-200">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="p-4 sm:p-5" x-ref="detailsBody" @click="onDetailsClick($event)" @keydown="onDetailsKeydown($event)">
                    <div x-show="detailsLoading" class="py-10 text-center text-sm text-gray-500 dark:text-slate-400">Loading…</div>
                    <div x-show="!detailsLoading" x-html="detailsHtml"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Toast notification (bulk actions feedback) --}}
    <div x-show="toastShow" x-transition x-cloak
        class="fixed bottom-24 right-1/2 translate-x-1/2 z-50 px-4 py-2.5 rounded-lg bg-slate-900/95 dark:bg-slate-700 text-white text-sm font-medium shadow-xl border border-slate-700/50 whitespace-nowrap">
        <span x-text="toastMsg"></span>
    </div>

    {{-- Floating Action Button: Add New Product --}}
    <a href="{{ url('/store/' . $store->slug . '/admin/products/create') }}"
       title="Add New Product"
       class="fixed bottom-[calc(env(safe-area-inset-bottom,0px)+1.5rem)] right-6 z-40 flex items-center justify-center w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-violet-600 hover:bg-violet-700 text-white shadow-lg shadow-violet-600/30 hover:shadow-violet-700/40 hover:scale-110 active:scale-95 transition-all duration-200 group">
        <svg class="w-7 h-7 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
        </svg>
        <span class="absolute right-full mr-3 px-2 py-1 bg-slate-800 dark:bg-slate-700 text-white text-xs font-medium rounded-md whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none">Add New Product</span>
    </a>
</div>
@endsection
