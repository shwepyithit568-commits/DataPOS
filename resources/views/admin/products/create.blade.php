@extends('layouts.admin.app')

@section('content')
@php
    $initialCategory = collect($categories)->first(fn ($c) => (string) $c->id === (string) old('category_id'));
    $initialMainCategory = $initialCategory ? ($initialCategory->parent_id ?? $initialCategory->id) : '';
    $initialSubCategory = $initialCategory?->parent_id ? $initialCategory->id : '';
@endphp
<div class="w-full space-y-5"
    x-data="{
        categoryModalOpen: false,
        brandModalOpen: false,
        supplierModalOpen: false,
        newCategoryName: '',
        newCategoryParent: '',
        newBrandName: '',
        newSupplierName: '',
        newSupplierPhone: '',
        autoSku: {{ old('auto_sku') ? 'true' : 'false' }},
        categories: {{ json_encode($categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'parent' => $c->parent?->name, 'parent_id' => $c->parent_id])) }},
        brands: {{ json_encode($brands->map(fn($b) => ['id' => $b->id, 'name' => $b->name])) }},
        suppliers: {{ json_encode($suppliers->map(fn($s) => ['id' => $s->id, 'name' => $s->name])) }},
        selectedSupplier: '{{ old('supplier_id') }}',
        variantPresets: @js($variantPresets),
        selectedVariantPresetId: '',
        selectedVariantPresetIdTwo: '',
        selectedMainCategory: '{{ $initialMainCategory }}',
        selectedSubCategory: '{{ $initialSubCategory }}',
        selectedBrand: '{{ old('brand_id') }}',
        variants: {{ json_encode(collect(old('variants', []))->map(fn($v) => [
            'id' => $v['id'] ?? null,
            'name' => $v['name'] ?? '',
            'attributes' => $v['attributes'] ?? [],
            'sku' => $v['sku'] ?? '',
            'retail_price' => $v['retail_price'] ?? '',
            'wholesale_price' => $v['wholesale_price'] ?? '',
            'stock_status' => $v['stock_status'] ?? 'in_stock',
            'is_default' => !empty($v['is_default']),
            'image_path' => $v['image_path'] ?? null,
            'image_preview' => null,
            'remove_image' => false,
        ])->values()) }},
        marginRetail: '{{ old('retail_price') }}',
        marginWhole: '{{ old('wholesale_price') }}',
        mainPreview: null,
        galleryPreviews: [],
        // Storefront preview bindings (mirror app/Support/ProductSpecifications.php)
        productSku: '{{ old('sku', $product->sku) }}',
        productWarranty: '{{ old('warranty', $product->warranty) }}',
        productStock: '{{ old('stock_status', $product->stock_status) }}',
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
        addVariant() { this.variants.push({ id: null, name: '', attributes: [], sku: '', retail_price: '', wholesale_price: '', stock_status: 'in_stock', is_default: false, image_path: null, image_preview: null, remove_image: false }); },
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
                body: JSON.stringify({ name: this.newCategoryName, parent_id: this.newCategoryParent || null })
            });
            const data = await res.json();
            if (data.success) {
                // Same categories table the Master Data page manages — the new
                // row is immediately available in this form's dropdowns.
                const parentName = data.parent_id
                    ? (this.categories.find((c) => String(c.id) === String(data.parent_id))?.name ?? null)
                    : null;
                this.categories.push({ id: data.id, name: data.name, parent: parentName, parent_id: data.parent_id });
                if (data.parent_id) {
                    this.selectedSubCategory = String(data.id);
                } else {
                    this.selectedMainCategory = String(data.id);
                    this.selectedSubCategory = '';
                }
                this.normalizeVariantPresetSelection();
                this.newCategoryName = '';
                this.newCategoryParent = '';
                this.categoryModalOpen = false;
            }
        },
        async createBrand() {
            if (!this.newBrandName.trim()) return;
            const res = await fetch('{{ url('/store/' . $store->slug . '/admin/brands/quick-store') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ name: this.newBrandName })
            });
            const data = await res.json();
            if (data.success) {
                this.brands.push({ id: data.id, name: data.name });
                this.selectedBrand = data.id;
                this.newBrandName = '';
                this.brandModalOpen = false;
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

    <div class="flex flex-col justify-between gap-3 rounded-xl bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800 sm:flex-row sm:items-center sm:p-5">
        <div>
            <p class="text-xs font-bold uppercase tracking-wide text-violet-600 dark:text-violet-400">{{ $store->name }}</p>
            <h1 class="mt-1 text-2xl font-black text-gray-900 dark:text-slate-100 font-outfit">{{ __('messages.product_form_create_title') }}</h1>
        </div>
        <a href="{{ $returnTo ?? url('/store/' . $store->slug . '/admin/products') }}" class="shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-200 rounded-xl hover:bg-gray-200 dark:hover:bg-slate-600 text-sm font-medium shadow transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            {{ __('messages.product_form_back_to_products') }}
        </a>
    </div>

    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-300">
            <p class="font-bold">{{ __('messages.product_form_check_fields') }}</p>
        </div>
    @endif

    <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/products') }}" enctype="multipart/form-data"
        class="space-y-5">
        @csrf

        @include('admin.products._form', ['isEdit' => false])
    </form>

    {{-- Quick Create Category Modal (Main or Sub — connected to Master Data) --}}
    <div x-show="categoryModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl p-6 max-w-sm w-full space-y-4 shadow-xl">
            <h3 class="text-lg font-bold text-gray-900 dark:text-slate-100">{{ __('messages.product_form_quick_category_title') }}</h3>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">{{ __('messages.product_form_category_name') }}</label>
                <input type="text" x-model="newCategoryName" @keydown.enter.prevent="createCategory()" class="w-full rounded-xl border dark:border-slate-600 px-3 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100" placeholder="{{ __('messages.product_form_category_name_placeholder') }}" />
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">{{ __('messages.product_form_quick_category_type') }}</label>
                <select x-model="newCategoryParent" class="w-full rounded-xl border dark:border-slate-600 px-3 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 cursor-pointer">
                    <option value="">{{ __('messages.product_form_quick_category_main') }}</option>
                    <template x-for="m in mainCategories" :key="m.id">
                        <option :value="m.id" x-text="'{{ __('messages.product_form_quick_category_sub_of') }}: ' + m.name"></option>
                    </template>
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-slate-400" x-show="newCategoryParent" x-cloak>
                    {{ __('messages.product_form_quick_category_sub_hint') }}
                </p>
            </div>
            <div class="flex justify-end space-x-2">
                <button type="button" @click="categoryModalOpen = false" class="px-3 py-1.5 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-slate-300 rounded-xl text-sm font-medium">{{ __('messages.close') }}</button>
                <button type="button" @click="createCategory()" class="px-3 py-1.5 bg-violet-600 text-white rounded-xl text-sm font-medium">{{ __('messages.product_form_save_category') }}</button>
            </div>
        </div>
    </div>

    {{-- Quick Create Brand Modal --}}
    <div x-show="brandModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl p-6 max-w-sm w-full space-y-4 shadow-xl">
            <h3 class="text-lg font-bold text-gray-900 dark:text-slate-100">{{ __('messages.product_form_quick_brand_title') }}</h3>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">{{ __('messages.product_form_brand_name') }}</label>
                <input type="text" x-model="newBrandName" @keydown.enter.prevent="createBrand()" class="w-full rounded-xl border dark:border-slate-600 px-3 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100" placeholder="{{ __('messages.product_form_brand_name_placeholder') }}" />
            </div>
            <div class="flex justify-end space-x-2">
                <button type="button" @click="brandModalOpen = false" class="px-3 py-1.5 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-slate-300 rounded-xl text-sm font-medium">{{ __('messages.close') }}</button>
                <button type="button" @click="createBrand()" class="px-3 py-1.5 bg-violet-600 text-white rounded-xl text-sm font-medium">{{ __('messages.product_form_save_brand') }}</button>
            </div>
        </div>
    </div>
    {{-- Quick Create Supplier Modal --}}
    <div x-show="supplierModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl p-6 max-w-sm w-full space-y-4 shadow-xl">
            <h3 class="text-lg font-bold text-gray-900 dark:text-slate-100">{{ __('messages.product_form_quick_supplier_title') }}</h3>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">{{ __('messages.product_form_supplier_name') }}</label>
                <input type="text" x-model="newSupplierName" @keydown.enter.prevent="createSupplier()" class="w-full rounded-xl border dark:border-slate-600 px-3 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100" placeholder="{{ __('messages.product_form_supplier_name_placeholder') }}" />
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">{{ __('messages.product_form_supplier_phone') }}</label>
                <input type="text" x-model="newSupplierPhone" @keydown.enter.prevent="createSupplier()" class="w-full rounded-xl border dark:border-slate-600 px-3 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100" placeholder="09xxxxxxxxx" />
            </div>
            <div class="flex justify-end space-x-2">
                <button type="button" @click="supplierModalOpen = false" class="px-3 py-1.5 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-slate-300 rounded-xl text-sm font-medium">{{ __('messages.close') }}</button>
                <button type="button" @click="createSupplier()" class="px-3 py-1.5 bg-violet-600 text-white rounded-xl text-sm font-medium">{{ __('messages.product_form_save_supplier') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection
