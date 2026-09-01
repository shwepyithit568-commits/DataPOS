@extends('layouts.admin.app')

@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
@php
    $initialCategoryId = old('category_id', $product->category_id);
    $initialCategory = collect($categories)->first(fn ($c) => (string) $c->id === (string) $initialCategoryId);
    $initialMainCategory = $initialCategory ? ($initialCategory->parent_id ?? $initialCategory->id) : '';
    $initialSubCategory = $initialCategory?->parent_id ? $initialCategory->id : '';
@endphp
<div class="w-full space-y-0.5 pb-6"
    x-data="{
        categoryModalOpen: false,
        brandModalOpen: false,
        supplierModalOpen: false,
        newCategoryName: '',
        newCategoryCode: '',
        newCategoryParent: '',
        newBrandName: '',
        newBrandCode: '',
        newSupplierName: '',
        newSupplierPhone: '',
        autoSku: false,
        productType: '{{ old('product_type', $product->product_type ?? 'standard') }}',
        productBarcode: '{{ old('barcode', $product->barcode ?? '') }}',
        productShelfLocation: '{{ old('shelf_location', $product->shelf_location ?? '') }}',
        productWarehouseId: '{{ old('warehouse_id', $product->warehouse_id ?? '') }}',
        productCompatibleModels: '{{ old('compatible_models', $product->compatible_models ?? '') }}',
        productModelCode: '',
        productExtraCode: '',
        productColorCode: '',
        productNameInput: @js(old('name', $product->name)),
        // On edit the existing name is authoritative — the Smart Name builder
        // never overwrites it (SKU auto-generation keeps working).
        nameTouched: true,
        categories: {{ json_encode($categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'code' => $c->code, 'parent' => $c->parent?->name, 'parent_id' => $c->parent_id])) }},
        brands: {{ json_encode($brands->map(fn($b) => ['id' => $b->id, 'name' => $b->name, 'code' => $b->code])) }},
        suppliers: {{ json_encode($suppliers->map(fn($s) => ['id' => $s->id, 'name' => $s->name])) }},
        warehouses: {{ json_encode($warehouses->map(fn($w) => ['id' => $w->id, 'name' => $w->name])) }},
        selectedSupplier: '{{ old('supplier_id', $product->supplier_id) }}',
        variantPresets: @js($variantPresets),
        selectedVariantPresetId: '',
        selectedVariantPresetIdTwo: '',
        selectedMainCategory: '{{ $initialMainCategory }}',
        selectedSubCategory: '{{ $initialSubCategory }}',
        selectedBrand: '{{ old('brand_id', $product->brand_id) }}',
        variants: {{ json_encode(collect(old('variants', collect($variants)->map(fn($v) => [
            'id' => is_array($v) ? ($v['id'] ?? null) : $v->id,
            'name' => is_array($v) ? ($v['name'] ?? '') : $v->name,
            'attributes' => is_array($v) ? ($v['attributes'] ?? []) : ($v->attributes ?? []),
            'sku' => is_array($v) ? ($v['sku'] ?? '') : $v->sku,
            'retail_price' => (string) (is_array($v) ? ($v['retail_price'] ?? '') : $v->retail_price),
            'wholesale_price' => (is_array($v) ? ($v['wholesale_price'] ?? null) : $v->wholesale_price) !== null ? (string) (is_array($v) ? $v['wholesale_price'] : $v->wholesale_price) : '',
            'stock_status' => is_array($v) ? ($v['stock_status'] ?? 'in_stock') : $v->stock_status,
            'quantity_on_hand' => is_array($v) ? ($v['quantity_on_hand'] ?? 0) : ($v->quantity_on_hand ?? 0),
            'is_default' => (bool) (is_array($v) ? ($v['is_default'] ?? false) : $v->is_default),
            'image_path' => is_array($v) ? ($v['image_path'] ?? null) : $v->image_path,
        ])->toArray()))->map(fn($v) => [
            'id' => $v['id'] ?? null,
            'name' => $v['name'] ?? '',
            'attributes' => $v['attributes'] ?? [],
            'sku' => $v['sku'] ?? '',
            'retail_price' => $v['retail_price'] ?? '',
            'wholesale_price' => $v['wholesale_price'] ?? '',
            'stock_status' => $v['stock_status'] ?? 'in_stock',
            'quantity_on_hand' => $v['quantity_on_hand'] ?? 0,
            'is_default' => (bool) ($v['is_default'] ?? false),
            'image_path' => $v['image_path'] ?? null,
            'image_preview' => null,
            'remove_image' => false,
        ])->values()) }},
        marginRetail: '{{ old('retail_price', $product->retail_price) }}',
        marginWhole: '{{ old('wholesale_price', $product->wholesale_price) }}',
        mainPreview: null,
        galleryPreviews: [],
        // Storefront preview bindings (mirror app/Support/ProductSpecifications.php)
        productSku: '{{ old('sku', $product->sku) }}',
        productWarranty: '{{ old('warranty', $product->warranty) }}',
        productStock: '{{ old('stock_status', $product->stock_status) }}',
        recomputeSmartSkuAndName() {
            if (!this.autoSku) return;
            // Services & digital items keep a manually typed name — don't
            // rebuild it from brand/category parts.
            if (this.productType === 'service' || this.productType === 'digital') return;
            const brandObj = this.brands.find(b => String(b.id) === String(this.selectedBrand));
            const catObj = this.categories.find(c => String(c.id) === String(this.selectedSubCategory || this.selectedMainCategory));
            
            const brandCode = (brandObj ? (brandObj.code || brandObj.name || '') : '').toUpperCase().trim().replace(/[^A-Z0-9]/g, '');
            const model = (this.productModelCode || '').toUpperCase().trim().replace(/[^A-Z0-9\-_]/g, '');
            const catCode = (catObj ? (catObj.code || catObj.name || '') : '').toUpperCase().trim().replace(/[^A-Z0-9]/g, '');
            const extra = (this.productExtraCode || '').toUpperCase().trim().replace(/[^A-Z0-9\-_]/g, '');
            const color = (this.productColorCode || '').toUpperCase().trim().replace(/[^A-Z0-9\-_]/g, '');
            
            const parts = [];
            if (brandCode) parts.push(brandCode);
            if (model) parts.push(model);
            if (catCode) parts.push(catCode);
            if (extra) parts.push(extra);
            if (color) parts.push(color);
            
            if (parts.length > 0) {
                this.productSku = parts.join('-');
            }
            
            const nameParts = [];
            if (brandObj && brandObj.name) {
                nameParts.push(brandObj.name.trim());
            }
            if (model) nameParts.push(this.productModelCode.trim());
            if (catObj && catObj.name) {
                let cleanCat = catObj.name.split('(')[0].trim();
                nameParts.push(cleanCat);
            }
            if (this.productCompatibleModels && this.productCompatibleModels.trim()) {
                nameParts.push('(' + this.productCompatibleModels.trim() + ')');
            }
            if (this.productExtraCode && this.productExtraCode.trim()) {
                nameParts.push(this.productExtraCode.trim());
            }
            if (this.productColorCode && this.productColorCode.trim()) {
                nameParts.push(this.productColorCode.trim());
            }
            
            if (nameParts.length > 0 && !this.nameTouched) {
                this.productNameInput = nameParts.join(' ');
            }
        },
        // Return-policy preview + meta-length counter read the DOM (no string
        // embedding in this double-quoted x-data attribute).
        returnPolicyPreview: null,
        metaDescLen: {{ mb_strlen(old('meta_description', $product->meta_description ?? '')) }},
        refreshReturnPolicyPreview() {
            const ta = document.querySelector('textarea[name=return_policy]');
            this.returnPolicyPreview = ta && ta.value.trim() ? ta.value.trim() : null;
        },
        descriptionPreviewHtml: @js(\App\Support\SafeHtml::sanitize(old('description', $product->description))),
        refreshDescriptionPreview() {
            // No double-quote chars allowed in this x-data attribute (it is
            // delimited by double quotes, so one would truncate the HTML).
            const ta = document.querySelector('textarea[name=description]');
            const value = ta ? ta.value : '';
            this.descriptionPreviewHtml = value && value.trim() ? value : null;
        },
        onRichTextSync(event) {
            if (event.detail && event.detail.name === 'description') this.refreshDescriptionPreview();
        },
        previewSpecs() {
            const rows = [];
            const brand = this.brands.find((b) => String(b.id) === String(this.selectedBrand));
            if (brand && brand.name && brand.name.trim()) rows.push({ label: '{{ __('messages.spec_brand') }}', value: brand.name.trim() });
            const cat = this.categories.find((c) => String(c.id) === String(this.selectedSubCategory || this.selectedMainCategory));
            if (cat && cat.name && cat.name.trim()) rows.push({ label: '{{ __('messages.spec_product_type') }}', value: cat.name.trim() });
            if (cat && cat.parent_id && cat.parent && cat.parent.trim()) rows.push({ label: '{{ __('messages.spec_main_category') }}', value: cat.parent.trim() });
            if (this.productSku && this.productSku.trim()) rows.push({ label: '{{ __('messages.spec_sku') }}', value: this.productSku.trim() });
            if (this.productWarranty && this.productWarranty.trim()) rows.push({ label: '{{ __('messages.spec_warranty') }}', value: this.productWarranty.trim() });
            const stock = this.productStock === 'in_stock' ? '{{ __('messages.spec_stock_in') }}' : this.productStock === 'out_of_stock' ? '{{ __('messages.spec_stock_out') }}' : null;
            if (stock) rows.push({ label: '{{ __('messages.spec_stock_status') }}', value: stock });
            const attrGroups = {};
            (this.variants || []).forEach((v) => (v.attributes || []).forEach((a) => {
                const label = (a.label || '').trim();
                const value = (a.value || '').trim();
                if (!label || !value) return;
                (attrGroups[label] = attrGroups[label] || {})[value] = true;
            }));
            Object.keys(attrGroups).forEach((label) => rows.push({ label, value: Object.keys(attrGroups[label]).join(', ') }));
            const names = (this.variants || []).map((v) => (v.name || '').trim()).filter(Boolean);
            if (names.length) rows.push({ label: '{{ __('messages.spec_variant_name') }}', value: names.join(', ') });
            const skus = (this.variants || []).map((v) => (v.sku || '').trim()).filter(Boolean);
            if (skus.length) rows.push({ label: '{{ __('messages.spec_variant_sku') }}', value: skus.join(', ') });
            return rows;
        },
        get marginPercent() {
            const r = parseFloat(this.marginRetail), w = parseFloat(this.marginWhole);
            if (!r || r <= 0) return 0;
            return Math.round(((r - w) / r) * 100);
        },
        get mainCategories() {
            return this.categories.filter((c) => !c.parent_id);
        },
        get subCategories() {
            return this.categories.filter((c) => String(c.parent_id) === String(this.selectedMainCategory));
        },
        previewMain(evt) { this.mainPreview = evt.target.files[0] ? URL.createObjectURL(evt.target.files[0]) : null; },
        previewGallery(evt) { this.galleryPreviews = [...evt.target.files].map(f => URL.createObjectURL(f)); },
        previewVariantImage(evt, index) {
            const v = this.variants[index];
            if (!v) return;
            v.image_preview = evt.target.files[0] ? URL.createObjectURL(evt.target.files[0]) : null;
            v.remove_image = false;
        },
        addVariant() { this.variants.push({ id: null, name: '', attributes: [], sku: '', retail_price: '', wholesale_price: '', stock_status: 'in_stock', quantity_on_hand: 0, is_default: false, image_path: null, image_preview: null, remove_image: false }); },
        removeVariant(i) { this.variants.splice(i, 1); },
        findVariantPreset(id) {
            return this.variantPresets.find((item) => String(item.id) === String(id));
        },
        get selectedCategoryName() {
            const finalId = this.selectedSubCategory || this.selectedMainCategory;
            return (this.categories.find((cat) => String(cat.id) === String(finalId))?.name || '').toLowerCase();
        },
        get filteredVariantPresets() {
            const category = this.selectedCategoryName;
            if (!category) return this.variantPresets;

            const presetFamilies = {
                mobile: ['mobile', 'phone'],
                accessories: ['accessories', 'accessory'],
                cctv: ['cctv', 'camera'],
                computer: ['computer', 'laptop'],
                fashion: ['fashion']
            };
            const matchedKey = Object.keys(presetFamilies).find((key) => category.includes(key));
            if (!matchedKey) return this.variantPresets;

            const keywords = presetFamilies[matchedKey];
            const matched = this.variantPresets.filter((preset) => {
                if (preset.category_family) return preset.category_family === matchedKey;
                const name = (preset.name || '').toLowerCase();
                return keywords.some((keyword) => name.includes(keyword));
            });

            return matched.length ? matched : this.variantPresets;
        },
        normalizeVariantPresetSelection() {
            const visibleIds = this.filteredVariantPresets.map((preset) => String(preset.id));
            if (this.selectedVariantPresetId && !visibleIds.includes(String(this.selectedVariantPresetId))) this.selectedVariantPresetId = '';
            if (this.selectedVariantPresetIdTwo && !visibleIds.includes(String(this.selectedVariantPresetIdTwo))) this.selectedVariantPresetIdTwo = '';
        },
        onMainCategoryChange() {
            this.selectedSubCategory = '';
            this.normalizeVariantPresetSelection();
        },
        baseVariantValues() {
            const skuInput = document.querySelector('input[name=sku]');
            return {
                sku: skuInput ? skuInput.value.trim() : '',
                retail: parseFloat(this.marginRetail) || 0,
                wholesale: parseFloat(this.marginWhole) || 0
            };
        },
        optionAdjustment(option, key) {
            return parseFloat(option?.[key]) || 0;
        },
        buildVariantRow(name, skuSuffix, retailAdjustment, wholesaleAdjustment, stockStatus, index, attributes = []) {
            const base = this.baseVariantValues();
            const suffix = (skuSuffix || '').trim();
            return {
                id: null,
                name,
                attributes: attributes || [],
                sku: suffix ? (base.sku ? `${base.sku}-${suffix}` : suffix) : '',
                retail_price: Math.max(0, base.retail + retailAdjustment).toFixed(2),
                wholesale_price: Math.max(0, base.wholesale + wholesaleAdjustment).toFixed(2),
                stock_status: stockStatus || 'in_stock',
                quantity_on_hand: 0,
                is_default: index === 0,
                image_path: null,
                image_preview: null,
                remove_image: false
            };
        },
        applyVariantPreset() {
            const preset = this.findVariantPreset(this.selectedVariantPresetId);
            if (!preset) return;

            this.variants = (preset.options || []).map((option, index) => {
                const skuSuffix = (option.sku_suffix || '').trim();
                return this.buildVariantRow(
                    option.name || '',
                    skuSuffix,
                    this.optionAdjustment(option, 'retail_price_adjustment'),
                    this.optionAdjustment(option, 'wholesale_price_adjustment'),
                    option.stock_status || 'in_stock',
                    index,
                    [{ label: preset.name, value: option.name || '' }]
                );
            });
        },
        applyVariantPresetCombination() {
            const firstPreset = this.findVariantPreset(this.selectedVariantPresetId);
            const secondPreset = this.findVariantPreset(this.selectedVariantPresetIdTwo);
            if (!firstPreset || !secondPreset || firstPreset.id === secondPreset.id) return;

            const rows = [];
            (firstPreset.options || []).forEach((firstOption) => {
                (secondPreset.options || []).forEach((secondOption) => {
                    rows.push(this.buildVariantRow(
                        `${firstOption.name || ''} / ${secondOption.name || ''}`,
                        [firstOption.sku_suffix, secondOption.sku_suffix].filter(Boolean).join('-'),
                        this.optionAdjustment(firstOption, 'retail_price_adjustment') + this.optionAdjustment(secondOption, 'retail_price_adjustment'),
                        this.optionAdjustment(firstOption, 'wholesale_price_adjustment') + this.optionAdjustment(secondOption, 'wholesale_price_adjustment'),
                        firstOption.stock_status === 'out_of_stock' || secondOption.stock_status === 'out_of_stock' ? 'out_of_stock' : 'in_stock',
                        rows.length,
                        [
                            { label: firstPreset.name, value: firstOption.name || '' },
                            { label: secondPreset.name, value: secondOption.name || '' }
                        ]
                    ));
                });
            });

            this.variants = rows.slice(0, 30);
        },
        async createCategory() {
            if (!this.newCategoryName.trim()) return;
            const res = await fetch('{{ url('/store/' . $store->slug . '/admin/categories/quick-store') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ name: this.newCategoryName, code: this.newCategoryCode || null, parent_id: this.newCategoryParent || null })
            });
            const data = await res.json();
            if (data.success) {
                // Same categories table the Master Data page manages — the new
                // row is immediately available in this form's dropdowns.
                const parentName = data.parent_id
                    ? (this.categories.find((c) => String(c.id) === String(data.parent_id))?.name ?? null)
                    : null;
                this.categories.push({ id: data.id, name: data.name, code: data.code, parent: parentName, parent_id: data.parent_id });
                if (data.parent_id) {
                    this.selectedSubCategory = String(data.id);
                } else {
                    this.selectedMainCategory = String(data.id);
                    this.selectedSubCategory = '';
                }
                this.normalizeVariantPresetSelection();
                this.newCategoryName = '';
                this.newCategoryCode = '';
                this.newCategoryParent = '';
                this.categoryModalOpen = false;
                this.recomputeSmartSkuAndName();
            }
        },
        async createBrand() {
            if (!this.newBrandName.trim()) return;
            const res = await fetch('{{ url('/store/' . $store->slug . '/admin/brands/quick-store') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ name: this.newBrandName, code: this.newBrandCode || null })
            });
            const data = await res.json();
            if (data.success) {
                this.brands.push({ id: data.id, name: data.name, code: data.code });
                this.selectedBrand = String(data.id);
                this.newBrandName = '';
                this.newBrandCode = '';
                this.brandModalOpen = false;
                this.recomputeSmartSkuAndName();
            }
        },
        async createSupplier() {
            if (!this.newSupplierName.trim()) return;
            const res = await fetch('{{ url('/store/' . $store->slug . '/admin/suppliers/quick-store') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ name: this.newSupplierName, phone: this.newSupplierPhone })
            });
            const data = await res.json();
            if (data.success) {
                this.suppliers.push({ id: data.id, name: data.name });
                this.selectedSupplier = String(data.id);
                this.newSupplierName = '';
                this.newSupplierPhone = '';
                this.supplierModalOpen = false;
            }
        }
    }" @richtext-sync.window="onRichTextSync($event)">

    {{-- Compact Page Header (34px - 38px) --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-8 h-8 rounded-lg bg-violet-50 dark:bg-violet-950/50 text-violet-600 dark:text-violet-400 grid place-items-center text-base font-bold shadow-xs shrink-0">
                📦
            </span>
            <div class="min-w-0">
                <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5 truncate">
                    <span>{{ $product->name }}</span>
                    @if($product->sku)
                        <span class="px-1.5 py-0.2 rounded text-[10px] font-mono font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700 shrink-0">
                            {{ $product->sku }}
                        </span>
                    @endif
                </h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                    {{ $store->name }} · {{ __('messages.product_form_edit_title', ['name' => $product->name]) }}
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-1 sm:gap-1.5 self-start sm:self-auto shrink-0">
            <a href="{{ url('/store/' . $store->slug . '/products/' . $product->slug) }}" target="_blank"
               class="h-7 px-2.5 rounded-md text-xs font-bold bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 border border-sky-200 dark:border-sky-800 hover:bg-sky-100 transition flex items-center gap-1 cursor-pointer">
                <span>👁️</span>
                <span>{{ __('messages.view_in_store') ?? 'Storefront' }}</span>
            </a>
            <a href="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug]) }}"
               class="h-7 px-2.5 rounded-md text-xs font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 transition flex items-center gap-1 cursor-pointer">
                <span>📁</span>
                <span>{{ __('messages.master_data') }}</span>
            </a>
            <a href="{{ url('/store/' . $store->slug . '/admin/suppliers') }}"
               class="h-7 px-2.5 rounded-md text-xs font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 transition flex items-center gap-1 cursor-pointer">
                <span>🏢</span>
                <span>{{ __('messages.suppliers') }}</span>
            </a>
            <a href="{{ $returnTo ?? url('/store/' . $store->slug . '/admin/products') }}"
               class="h-7 px-2.5 rounded-md text-xs font-bold bg-violet-50 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 border border-violet-200 dark:border-violet-800 hover:bg-violet-100 transition flex items-center gap-1 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>{{ __('messages.product_form_back_to_products') }}</span>
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="p-2 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-1.5 shadow-2xs">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-2.5 bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 rounded-lg text-xs font-bold text-rose-700 dark:text-rose-300 space-y-0.5 shadow-2xs">
            <div class="flex items-center gap-1.5">
                <span>⚠️</span>
                <span class="font-black">{{ __('messages.product_form_check_fields') }}</span>
            </div>
            @foreach ($errors->all() as $error)
                <p class="pl-5 text-[11px]">• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/' . $product->id) }}" enctype="multipart/form-data"
        class="space-y-0.5">
        @csrf
        @method('PUT')

        @include('admin.products._form', ['isEdit' => true])
    </form>

    {{-- Product Gallery Multi-Image Uploader (existing images live here) --}}
    <div class="w-full rounded-lg bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-2.5 sm:p-3 space-y-2 transition-colors duration-200 shadow-2xs">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 rounded-lg bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 grid place-items-center text-sm font-bold">🖼️</span>
                <div>
                    <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white">{{ __('messages.product_form_gallery_section') }}</h2>
                    <p class="text-[11px] text-slate-400">
                        {{ __('messages.product_form_gallery_upload_hint', ['count' => $maxGalleryImages, 'remaining' => $remainingGallerySlots, 'size' => $imageMaxMb]) }}
                    </p>
                </div>
            </div>
            <span class="px-2 py-0.5 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                {{ count($images) }} / {{ $maxGalleryImages }}
            </span>
        </div>

        <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/' . $product->id . '/images') }}" enctype="multipart/form-data" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
            @csrf
            <div class="flex-1">
                <input type="file" name="images[]" multiple accept="image/*" required {{ $remainingGallerySlots === 0 ? 'disabled' : '' }} class="block w-full text-xs text-slate-600 dark:text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-violet-50 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-violet-700 hover:file:bg-violet-100 dark:file:bg-slate-800 dark:file:text-violet-300 rounded-lg border border-slate-200 dark:border-slate-700 p-1.5 bg-slate-50 dark:bg-slate-800/60 disabled:opacity-50 disabled:cursor-not-allowed" />
            </div>
            <button type="submit" {{ $remainingGallerySlots === 0 ? 'disabled' : '' }} class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-black text-xs whitespace-nowrap shadow-sm disabled:opacity-50 disabled:cursor-not-allowed transition">
                + {{ __('messages.product_form_upload_images') }}
            </button>
        </form>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 sm:gap-3">
            @forelse ($images as $img)
                <div class="relative rounded-lg p-2 space-y-1.5 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 group transition">
                    <img src="{{ asset('storage/' . $img->image_path) }}" class="h-28 w-full object-cover rounded-md" />
                    @if ($img->is_primary || $product->image_path === $img->image_path)
                        <span class="absolute top-3 left-3 bg-violet-600 text-white text-[9px] px-1.5 py-0.2 rounded font-bold shadow-md">{{ __('messages.product_form_primary') }}</span>
                    @endif
                    <div class="flex items-center justify-between pt-1">
                        @if (!$img->is_primary && $product->image_path !== $img->image_path)
                            <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/' . $product->id . '/images/' . $img->id . '/primary') }}">
                                @csrf
                                <button type="submit" class="text-[11px] text-violet-600 dark:text-violet-400 hover:underline font-bold">{{ __('messages.product_form_set_primary') }}</button>
                            </form>
                        @else
                            <span class="text-[10px] text-slate-400 dark:text-slate-500 font-bold">★ {{ __('messages.product_form_primary') }}</span>
                        @endif

                        <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products/' . $product->id . '/images/' . $img->id) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" data-confirm="{{ __('messages.product_form_delete_image_confirm') }}" class="text-[11px] text-rose-600 dark:text-rose-400 hover:underline font-bold">{{ __('messages.product_form_delete_image') }}</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-6 text-center text-xs text-slate-400 italic">{{ __('messages.product_form_no_gallery_images') }}</div>
            @endforelse
        </div>
    </div>

    {{-- Quick Create Category Modal (Main or Sub — connected to Master Data) --}}
    <div x-show="categoryModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 backdrop-blur-xs p-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 max-w-sm w-full space-y-3.5 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-violet-50 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 grid place-items-center text-sm font-bold">📁</span>
                    <h3 class="text-sm font-black text-slate-900 dark:text-white">{{ __('messages.product_form_quick_category_title') }}</h3>
                </div>
                <button type="button" @click="categoryModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs font-bold">✕</button>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.product_form_category_name') }} <span class="text-rose-500">*</span></label>
                <input type="text" x-model="newCategoryName" class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs sm:text-sm bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 font-semibold focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500/40 outline-none transition" placeholder="{{ __('messages.product_form_category_name_placeholder') }}" />
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.product_form_code') }}</label>
                <input type="text" x-model="newCategoryCode" class="w-full uppercase rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs sm:text-sm bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 font-semibold focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500/40 outline-none transition" placeholder="{{ __('messages.product_form_code_placeholder') }}" />
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.product_form_quick_category_type') }}</label>
                <select x-model="newCategoryParent" class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs sm:text-sm bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 font-semibold focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500/40 outline-none transition cursor-pointer">
                    <option value="">{{ __('messages.product_form_quick_category_main') }}</option>
                    <template x-for="m in mainCategories" :key="m.id">
                        <option :value="m.id" x-text="'{{ __('messages.product_form_quick_category_sub_of') }}: ' + m.name"></option>
                    </template>
                </select>
                <p class="mt-1 text-[11px] text-slate-400" x-show="newCategoryParent" x-cloak>
                    {{ __('messages.product_form_quick_category_sub_hint') }}
                </p>
            </div>
            <div class="flex justify-end gap-2 pt-1.5">
                <button type="button" @click="categoryModalOpen = false" class="px-3.5 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-bold hover:bg-slate-200 dark:hover:bg-slate-700 transition">{{ __('messages.close') }}</button>
                <button type="button" @click="createCategory()" class="px-3.5 py-1.5 bg-violet-600 hover:bg-violet-500 text-white rounded-lg text-xs font-black shadow-sm transition">{{ __('messages.product_form_save_category') }}</button>
            </div>
        </div>
    </div>

    {{-- Quick Create Brand Modal --}}
    <div x-show="brandModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 backdrop-blur-xs p-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 max-w-sm w-full space-y-3.5 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-violet-50 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 grid place-items-center text-sm font-bold">🏷️</span>
                    <h3 class="text-sm font-black text-slate-900 dark:text-white">{{ __('messages.product_form_quick_brand_title') }}</h3>
                </div>
                <button type="button" @click="brandModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs font-bold">✕</button>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.product_form_brand_name') }} <span class="text-rose-500">*</span></label>
                <input type="text" x-model="newBrandName" class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs sm:text-sm bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 font-semibold focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500/40 outline-none transition" placeholder="{{ __('messages.product_form_brand_name_placeholder') }}" />
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.product_form_code') }}</label>
                <input type="text" x-model="newBrandCode" class="w-full uppercase rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs sm:text-sm bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 font-semibold focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500/40 outline-none transition" placeholder="{{ __('messages.product_form_code_placeholder') }}" />
            </div>
            <div class="flex justify-end gap-2 pt-1.5">
                <button type="button" @click="brandModalOpen = false" class="px-3.5 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-bold hover:bg-slate-200 dark:hover:bg-slate-700 transition">{{ __('messages.close') }}</button>
                <button type="button" @click="createBrand()" class="px-3.5 py-1.5 bg-violet-600 hover:bg-violet-500 text-white rounded-lg text-xs font-black shadow-sm transition">{{ __('messages.product_form_save_brand') }}</button>
            </div>
        </div>
    </div>

    {{-- Quick Create Supplier Modal --}}
    <div x-show="supplierModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 backdrop-blur-xs p-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 max-w-sm w-full space-y-3.5 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-violet-50 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 grid place-items-center text-sm font-bold">🏢</span>
                    <h3 class="text-sm font-black text-slate-900 dark:text-white">{{ __('messages.product_form_quick_supplier_title') }}</h3>
                </div>
                <button type="button" @click="supplierModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs font-bold">✕</button>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.product_form_supplier_name') }}</label>
                <input type="text" x-model="newSupplierName" @keydown.enter.prevent="createSupplier()" class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs sm:text-sm bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 font-semibold focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500/40 outline-none transition" placeholder="{{ __('messages.product_form_supplier_name_placeholder') }}" />
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.product_form_supplier_phone') }}</label>
                <input type="text" x-model="newSupplierPhone" @keydown.enter.prevent="createSupplier()" class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs sm:text-sm bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 font-semibold focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500/40 outline-none transition" placeholder="09xxxxxxxxx" />
            </div>
            <div class="flex justify-end gap-2 pt-1.5">
                <button type="button" @click="supplierModalOpen = false" class="px-3.5 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-bold hover:bg-slate-200 dark:hover:bg-slate-700 transition">{{ __('messages.close') }}</button>
                <button type="button" @click="createSupplier()" class="px-3.5 py-1.5 bg-violet-600 hover:bg-violet-500 text-white rounded-lg text-xs font-black shadow-sm transition">{{ __('messages.product_form_save_supplier') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection
