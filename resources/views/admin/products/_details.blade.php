@php
    use App\Support\SafeHtml;
    use App\Support\ProductSpecifications;

    $specRows = ProductSpecifications::rowsFor($product);
    $hasDescription = trim(strip_tags((string) $product->description)) !== '';
    $variants = $product->variants ?? collect();
    $images = $product->images ?? collect();
    $margin = ($product->retail_price > 0 && $product->wholesale_price > 0)
        ? round((($product->retail_price - $product->wholesale_price) / $product->retail_price) * 100)
        : null;
@endphp

<div class="min-w-0 space-y-4">
    {{-- ============================================================
         1. TOP HERO CARD (Image, Title, Badges, SKU, Pricing)
         ============================================================ --}}
    <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 p-3.5 sm:p-4 rounded-lg sm:rounded-xl bg-slate-50/80 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/80 transition">
        {{-- Product Thumbnail & Gallery --}}
        <div class="shrink-0 flex flex-col items-center sm:items-start gap-2">
            @if ($product->image_path)
                <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}" class="w-24 h-24 sm:w-28 sm:h-28 object-cover rounded-lg border border-slate-200 dark:border-slate-700 shadow-2xs" />
            @else
                <div class="w-24 h-24 sm:w-28 sm:h-28 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700 grid place-items-center text-3xl shadow-inner text-slate-400">
                    📷
                </div>
            @endif

            @if ($images->isNotEmpty())
                <div class="flex items-center gap-1.5 overflow-x-auto max-w-[120px] pb-1">
                    @foreach ($images as $img)
                        <img src="{{ asset('storage/' . $img->image_path) }}" class="w-7 h-7 object-cover rounded-lg border border-slate-200 dark:border-slate-700 shadow-xs" />
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Details & Pricing Summary --}}
        <div class="min-w-0 flex-1 space-y-2">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex flex-wrap items-center gap-1.5">
                    {{-- Archetype Badge --}}
                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] font-bold bg-violet-100 text-violet-700 dark:bg-violet-950/70 dark:text-violet-300 border border-violet-200 dark:border-violet-800/70 uppercase tracking-wide">
                        {{ $product->product_type ?? 'standard' }}
                    </span>

                    {{-- Stock Status Pill --}}
                    @php
                        $isServiceOrDigital = in_array($product->product_type, ['service', 'digital'], true);
                        $onHand = (float) ($product->on_hand_qty ?? $product->stock_on_hand ?? 0);
                        $reorder = (float) ($product->reorder_level ?? 0);
                        $fmtQty = format_quantity($onHand, $store ?? null);

                        if ($isServiceOrDigital) {
                            $badgeClass = 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700';
                            $dotClass = 'bg-slate-400';
                            $stockText = '— ' . ($product->product_type === 'service' ? __('messages.product_type_service_short') : __('messages.product_type_digital_short'));
                        } elseif ($onHand <= 0 || $product->stock_status === 'out_of_stock') {
                            $badgeClass = 'bg-rose-100 text-rose-700 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-200 dark:border-rose-800/80';
                            $dotClass = 'bg-rose-500';
                            $stockText = $fmtQty . ' · ' . __('messages.out_of_stock');
                        } elseif ($reorder > 0 && $onHand <= $reorder) {
                            $badgeClass = 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-200 dark:border-amber-800/80';
                            $dotClass = 'bg-amber-500 animate-pulse';
                            $stockText = $fmtQty . ' · ' . __('messages.low_stock');
                        } else {
                            $badgeClass = 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/80';
                            $dotClass = 'bg-emerald-500 animate-pulse';
                            $stockText = $fmtQty . ' · ' . __('messages.in_stock');
                        }
                    @endphp
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-[11px] font-black {{ $badgeClass }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $dotClass }}"></span>
                        <span class="font-mono font-bold">{{ $stockText }}</span>
                    </span>


                    @if ($product->is_featured)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[11px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300">
                            ⭐ {{ __('messages.featured') }}
                        </span>
                    @endif
                </div>

                @if (isset($store))
                    <a href="{{ url('/store/' . $store->slug . '/admin/products/' . $product->id . '/edit') }}"
                       class="inline-flex items-center gap-1 px-3 py-1 rounded-xl text-xs font-bold bg-violet-600 hover:bg-violet-700 text-white shadow-xs transition active:scale-95">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.4-9.4a2 2 0 1 1 2.8 2.8L11 14l-4 1 1-4 9.6-9.4Z"/></svg>
                        <span>{{ __('messages.edit') }}</span>
                    </a>
                @endif
            </div>

            <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-slate-100 leading-snug break-words">
                {{ $product->name }}
            </h3>

            {{-- Metadata Badges (SKU, Barcode, Shelf Location) --}}
            <div class="flex flex-wrap items-center gap-2 text-xs">
                @if (!empty($product->sku))
                    <div class="inline-flex items-center gap-1 font-mono font-bold text-violet-700 dark:text-violet-300 bg-white dark:bg-slate-900 px-2.5 py-1 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs">
                        <span class="text-slate-400">SKU:</span>
                        <span>{{ $product->sku }}</span>
                    </div>
                @endif

                @if (!empty($product->barcode))
                    <div class="inline-flex items-center gap-1 font-mono font-bold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-900 px-2.5 py-1 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs">
                        <span>🏷️</span>
                        <span>{{ $product->barcode }}</span>
                    </div>
                @endif

                @if (!empty($product->shelf_location))
                    <div class="inline-flex items-center gap-1 font-bold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/60 px-2.5 py-1 rounded-xl border border-amber-200/80 dark:border-amber-800/80 shadow-xs">
                        <span>📍</span>
                        <span>{{ $product->shelf_location }}</span>
                    </div>
                @endif
            </div>

            {{-- Price Display --}}
            <div class="flex flex-wrap items-baseline gap-3 pt-1">
                <div class="flex items-baseline gap-1.5">
                    <span class="text-xs font-bold text-slate-500 uppercase">{{ __('messages.retail') }}:</span>
                    <span class="text-lg sm:text-xl font-black text-slate-900 dark:text-white tabular-nums font-outfit">{{ format_currency($product->retail_price, $store ?? null) }}</span>
                </div>
                @if ($product->wholesale_price > 0)
                    <div class="flex items-baseline gap-1.5 text-emerald-600 dark:text-emerald-400">
                        <span class="text-xs font-bold uppercase">{{ __('messages.wholesale') }}:</span>
                        <span class="text-sm sm:text-base font-black tabular-nums font-outfit">{{ format_currency($product->wholesale_price, $store ?? null) }}</span>
                    </div>
                @endif
                @if ($margin !== null)
                    <span class="text-[11px] font-black px-2 py-0.5 rounded-lg border {{ $margin >= 0 ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800' : 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800' }}">
                        {{ __('messages.details_profit_margin') }}: {{ $margin }}%
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- ============================================================
         2. TAB NAVIGATION (Overview & Specs | Description | Variants | Policy & SEO)
         ============================================================ --}}
    <div role="tablist" aria-label="{{ __('messages.product_details') }}" class="flex items-center gap-1 border-b border-slate-200 dark:border-slate-700 overflow-x-auto">
        <button type="button" role="tab" id="admin-spec-overview-tab" aria-controls="admin-spec-overview-panel" aria-selected="true"
            data-spec-tab="overview"
            class="-mb-px inline-flex items-center gap-1.5 rounded-t-2xl border-b-2 border-violet-600 px-4 py-2.5 text-xs font-black uppercase tracking-wide text-violet-600 dark:text-violet-400 transition focus:outline-none shrink-0">
            <span>📋</span>
            <span>{{ __('messages.tab_overview') }}</span>
        </button>

        <button type="button" role="tab" id="admin-spec-desc-tab" aria-controls="admin-spec-desc-panel" aria-selected="false"
            data-spec-tab="description"
            class="-mb-px inline-flex items-center gap-1.5 rounded-t-2xl border-b-2 border-transparent px-4 py-2.5 text-xs font-black uppercase tracking-wide text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 transition focus:outline-none shrink-0">
            <span>📝</span>
            <span>{{ __('messages.tab_description') }}</span>
        </button>

        @if ($variants->isNotEmpty())
            <button type="button" role="tab" id="admin-spec-variants-tab" aria-controls="admin-spec-variants-panel" aria-selected="false"
                data-spec-tab="variants"
                class="-mb-px inline-flex items-center gap-1.5 rounded-t-2xl border-b-2 border-transparent px-4 py-2.5 text-xs font-black uppercase tracking-wide text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 transition focus:outline-none shrink-0">
                <span>🧩</span>
                <span>{{ __('messages.tab_variants') }} ({{ $variants->count() }})</span>
            </button>
        @endif

        <button type="button" role="tab" id="admin-spec-policies-tab" aria-controls="admin-spec-policies-panel" aria-selected="false"
            data-spec-tab="policies"
            class="-mb-px inline-flex items-center gap-1.5 rounded-t-2xl border-b-2 border-transparent px-4 py-2.5 text-xs font-black uppercase tracking-wide text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 transition focus:outline-none shrink-0">
            <span>🛡️</span>
            <span>{{ __('messages.tab_policies') }}</span>
        </button>
    </div>

    {{-- ============================================================
         3. TAB PANELS
         ============================================================ --}}
    <div class="pt-2">
        {{-- TAB 1: OVERVIEW & SPECS --}}
        <div role="tabpanel" id="admin-spec-overview-panel" aria-labelledby="admin-spec-overview-tab" data-spec-panel="overview" class="space-y-3">
            {{-- Highlight Card for Compatible Models (Critical for Phone Parts) --}}
            @if (!empty($product->compatible_models))
                <div class="p-3.5 rounded-2xl bg-indigo-50/80 dark:bg-indigo-950/40 border border-indigo-200/80 dark:border-indigo-800/80 space-y-1">
                    <div class="flex items-center gap-1.5 text-xs font-black text-indigo-900 dark:text-indigo-200">
                        <span>📱</span>
                        <span>{{ __('messages.product_form_compatible_models') }}</span>
                    </div>
                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200 pl-5">
                        {{ $product->compatible_models }}
                    </p>
                </div>
            @endif

            {{-- Two-Column Structured Spec Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <div class="p-3 rounded-2xl bg-slate-50/60 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60 flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('messages.spec_brand') }}</span>
                    <span class="text-xs font-black text-slate-900 dark:text-slate-100">{{ $product->brand->name ?? '—' }}</span>
                </div>

                <div class="p-3 rounded-2xl bg-slate-50/60 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60 flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('messages.spec_product_type') }}</span>
                    <span class="text-xs font-black text-slate-900 dark:text-slate-100">{{ $product->category->name ?? '—' }}</span>
                </div>

                @if ($product->category?->parent)
                    <div class="p-3 rounded-2xl bg-slate-50/60 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60 flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('messages.spec_main_category') }}</span>
                        <span class="text-xs font-black text-slate-900 dark:text-slate-100">{{ $product->category->parent->name }}</span>
                    </div>
                @endif

                @if ($product->warehouse)
                    <div class="p-3 rounded-2xl bg-slate-50/60 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60 flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('messages.warehouse') }}</span>
                        <span class="text-xs font-black text-slate-900 dark:text-slate-100">{{ $product->warehouse->name }}</span>
                    </div>
                @endif

                @if ($product->supplier)
                    <div class="p-3 rounded-2xl bg-slate-50/60 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60 flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('messages.supplier') }}</span>
                        <span class="text-xs font-black text-slate-900 dark:text-slate-100">{{ $product->supplier->name }}</span>
                    </div>
                @endif

                @if (!empty($product->warranty))
                    <div class="p-3 rounded-2xl bg-slate-50/60 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60 flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('messages.spec_warranty') }}</span>
                        <span class="text-xs font-black text-slate-900 dark:text-slate-100">{{ $product->warranty }}</span>
                    </div>
                @endif

                @if ($product->purchase_cost > 0)
                    <div class="p-3 rounded-2xl bg-slate-50/60 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60 flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('messages.details_purchase_cost') }}</span>
                        <span class="text-xs font-black text-slate-900 dark:text-slate-100">{{ format_currency($product->purchase_cost, $store ?? null) }}</span>
                    </div>
                @endif

                @if ($product->reorder_level > 0)
                    <div class="p-3 rounded-2xl bg-slate-50/60 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60 flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('messages.details_reorder_level') }}</span>
                        <span class="text-xs font-black text-amber-600 dark:text-amber-400">{{ format_quantity($product->reorder_level, $store ?? null) }}</span>
                    </div>
                @endif
            </div>

            {{-- Additional Specs --}}
            @if ($specRows)
                <div class="mt-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                    <dl class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($specRows as $spec)
                            <div class="grid grid-cols-1 sm:grid-cols-[minmax(0,10rem)_minmax(0,1fr)] gap-1 py-2 sm:items-start">
                                <dt class="text-xs font-bold text-slate-500 dark:text-slate-400">{{ $spec['label'] }}</dt>
                                <dd class="text-xs font-medium text-slate-800 dark:text-slate-200">{{ $spec['value'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endif
        </div>

        {{-- TAB 2: DESCRIPTION --}}
        <div role="tabpanel" id="admin-spec-desc-panel" aria-labelledby="admin-spec-desc-tab" data-spec-panel="description" hidden>
            @if ($hasDescription)
                <div class="prose prose-sm max-w-none text-xs sm:text-sm leading-relaxed text-slate-800 dark:text-slate-100 bg-slate-50/60 dark:bg-slate-800/40 p-4 rounded-2xl border border-slate-200/60 dark:border-slate-700/60">
                    {!! SafeHtml::sanitize($product->description) !!}
                </div>
            @else
                <div class="py-8 text-center text-xs text-slate-400 dark:text-slate-500">
                    {{ __('messages.spec_description_empty') }}
                </div>
            @endif
        </div>

        {{-- TAB 3: VARIANTS --}}
        @if ($variants->isNotEmpty())
            <div role="tabpanel" id="admin-spec-variants-panel" aria-labelledby="admin-spec-variants-tab" data-spec-panel="variants" hidden>
                <div class="overflow-x-auto rounded-2xl border border-slate-200/80 dark:border-slate-700/80">
                    <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                        <thead class="bg-slate-100/80 dark:bg-slate-800/80 font-black text-slate-700 dark:text-slate-200 border-b border-slate-200 dark:border-slate-700">
                            <tr>
                                <th class="p-2.5">{{ __('messages.spec_variant_name') }}</th>
                                <th class="p-2.5">{{ __('messages.spec_variant_sku') }}</th>
                                <th class="p-2.5">{{ __('messages.retail') }}</th>
                                <th class="p-2.5">{{ __('messages.wholesale') }}</th>
                                <th class="p-2.5 text-center">{{ __('messages.stock_status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($variants as $v)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                    <td class="p-2.5 font-bold text-slate-900 dark:text-slate-100">
                                        {{ $v->name }}
                                        @if (!empty($v->attributes))
                                            <div class="flex flex-wrap gap-1 mt-0.5">
                                                @foreach ($v->attributes as $attr)
                                                    <span class="text-[10px] bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded text-slate-600 dark:text-slate-400">
                                                        {{ $attr['label'] ?? '' }}: {{ $attr['value'] ?? '' }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td class="p-2.5 font-mono text-[11px] text-violet-700 dark:text-violet-300">{{ $v->sku }}</td>
                                    <td class="p-2.5 font-black text-slate-900 dark:text-slate-100 tabular-nums">{{ format_currency($v->retail_price, $store ?? null) }}</td>
                                    <td class="p-2.5 font-bold text-emerald-600 dark:text-emerald-400 tabular-nums">{{ $v->wholesale_price > 0 ? format_currency($v->wholesale_price, $store ?? null) : '—' }}</td>
                                    <td class="p-2.5 text-center whitespace-nowrap">
                                        @php
                                            $vOnHand = (float) ($v->stock_on_hand ?? $v->quantity_on_hand ?? 0);
                                            $vFmtQty = format_quantity($vOnHand, $store ?? null);
                                            $vReorder = (float) ($product->reorder_level ?? 0);

                                            if ($vOnHand <= 0 || ($v->stock_status ?? 'in_stock') === 'out_of_stock') {
                                                $vBadgeClass = 'bg-rose-100 text-rose-700 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-200 dark:border-rose-800/80';
                                                $vDotClass = 'bg-rose-500';
                                                $vStockText = $vFmtQty . ' · ' . __('messages.out_of_stock');
                                            } elseif ($vReorder > 0 && $vOnHand <= $vReorder) {
                                                $vBadgeClass = 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-200 dark:border-amber-800/80';
                                                $vDotClass = 'bg-amber-500 animate-pulse';
                                                $vStockText = $vFmtQty . ' · ' . __('messages.low_stock');
                                            } else {
                                                $vBadgeClass = 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/80';
                                                $vDotClass = 'bg-emerald-500 animate-pulse';
                                                $vStockText = $vFmtQty . ' · ' . __('messages.in_stock');
                                            }
                                        @endphp
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg text-[10px] font-black {{ $vBadgeClass }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $vDotClass }}"></span>
                                            <span class="font-mono font-bold">{{ $vStockText }}</span>
                                        </span>
                                    </td>

                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- TAB 4: POLICIES & SEO --}}
        <div role="tabpanel" id="admin-spec-policies-panel" aria-labelledby="admin-spec-policies-tab" data-spec-panel="policies" hidden class="space-y-3">
            @if (!empty($product->return_policy))
                <div class="p-4 rounded-2xl bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200/70 dark:border-amber-800/70 space-y-1">
                    <div class="flex items-center gap-1.5 text-xs font-black text-amber-900 dark:text-amber-200">
                        <span>🔄</span>
                        <span>{{ __('messages.product_form_return_policy') }}</span>
                    </div>
                    <p class="text-xs leading-relaxed text-slate-800 dark:text-slate-200 pl-5">
                        {{ $product->return_policy }}
                    </p>
                </div>
            @endif

            @if (!empty($product->meta_description))
                <div class="p-4 rounded-2xl bg-slate-50/70 dark:bg-slate-800/50 border border-slate-200/70 dark:border-slate-700/70 space-y-1">
                    <div class="flex items-center gap-1.5 text-xs font-black text-slate-800 dark:text-slate-200">
                        <span>🌐</span>
                        <span>{{ __('messages.product_form_meta_description') }}</span>
                    </div>
                    <p class="text-xs leading-relaxed text-slate-600 dark:text-slate-300 pl-5 font-mono">
                        {{ $product->meta_description }}
                    </p>
                </div>
            @endif

            @if (empty($product->return_policy) && empty($product->meta_description))
                <div class="py-8 text-center text-xs text-slate-400 dark:text-slate-500">
                    {{ __('messages.no_policy_found') }}
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Vanilla JS Tab Switcher --}}
<script>
(function () {
    var root = document.currentScript && document.currentScript.parentElement;
    if (!root) return;
    var tabs = root.querySelectorAll('[data-spec-tab]');
    var panels = root.querySelectorAll('[data-spec-panel]');
    function show(name) {
        tabs.forEach(function (tab) {
            var active = tab.getAttribute('data-spec-tab') === name;
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
            tab.classList.toggle('border-violet-600', active);
            tab.classList.toggle('text-violet-600', active);
            tab.classList.toggle('dark:text-violet-400', active);
            tab.classList.toggle('border-transparent', !active);
            tab.classList.toggle('text-slate-500', !active);
            tab.classList.toggle('dark:text-slate-400', !active);
        });
        panels.forEach(function (panel) {
            panel.hidden = panel.getAttribute('data-spec-panel') !== name;
        });
    }
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () { show(tab.getAttribute('data-spec-tab')); });
        tab.addEventListener('keydown', function (e) {
            var allTabs = Array.from(tabs);
            var i = allTabs.indexOf(tab);
            if (i === -1) return;
            var next = null;
            if (e.key === 'ArrowRight') next = allTabs[(i + 1) % allTabs.length];
            else if (e.key === 'ArrowLeft') next = allTabs[(i - 1 + allTabs.length) % allTabs.length];
            if (!next) return;
            e.preventDefault();
            var targetName = next.getAttribute('data-spec-tab');
            show(targetName);
            next.focus();
        });
    });
})();
</script>
