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
        // Variants are a deliberate choice: collapsed until asked for.
        existingProductId: {{ $product->id }},
        variantsOpen: {{ (old('variants') || $errors->has('variants') || $product->product_type === 'variant' || count($variants) > 0) ? 'true' : 'false' }},
        autoSku: false,
        productType: '{{ old('product_type', $product->product_type ?? 'standard') }}',
        productBarcode: '{{ old('barcode', $product->barcode ?? '') }}',
        productShelfLocation: '{{ old('shelf_location', $product->shelf_location ?? '') }}',
        productWarehouseId: '{{ old('warehouse_id', $product->warehouse_id ?? '') }}',
        productCompatibleModels: '{{ old('compatible_models', $product->compatible_models ?? '') }}',
        productModelCode: '',
        productNameInput: {{ json_encode(old('name', $product->name)) }},
        // On edit the existing name is authoritative — the Smart Name builder
        // never overwrites it (SKU auto-generation keeps working).
        nameTouched: true,
        categories: {{ json_encode($categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'code' => $c->code, 'parent' => $c->parent?->name, 'parent_id' => $c->parent_id])) }},
        brands: {{ json_encode($brands->map(fn($b) => ['id' => $b->id, 'name' => $b->name, 'code' => $b->code])) }},
        suppliers: {{ json_encode($suppliers->map(fn($s) => ['id' => $s->id, 'name' => $s->name])) }},
        warehouses: {{ json_encode($warehouses->map(fn($w) => ['id' => $w->id, 'name' => $w->name])) }},
        selectedSupplier: '{{ old('supplier_id', $product->supplier_id) }}',
        variantPresets: {{ json_encode($variantPresets) }},
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
            'quantity_on_hand' => is_array($v)
                ? ($v['quantity_on_hand'] ?? 0)
                : (float) ($v->ledger_qty ?? $v->quantity_on_hand ?? 0),
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
        purchaseCost: '{{ old('purchase_cost', $product->purchase_cost ?? '') }}',
        marginRetail: '{{ old('retail_price', $product->retail_price) }}',
        marginWhole: '{{ old('wholesale_price', $product->wholesale_price) }}',
        retailMarkup: '',
        retailCustom: false,
        wholesaleMarkup: '',
        wholesaleCustom: false,
        retailPresets: ['5','10','15','20','25','30','35','40','50','60','70','80','100'],
        wholesalePresets: ['3','5','8','10','12','15','20','25','30'],
        init() {
            this.initPricing();
        },
        mainPreview: null,
        galleryPreviews: [],
        // Storefront preview bindings (mirror app/Support/ProductSpecifications.php)
        productSku: '{{ old('sku', $product->sku) }}',
        productWarranty: '{{ old('warranty', $product->warranty) }}',
        productReturnPolicy: {{ json_encode(old('return_policy', $product->return_policy ?? '')) }},
        productStock: '{{ old('stock_status', $product->stock_status) }}',
        recomputeSmartSkuAndName() {
            if (!this.autoSku) return;
            // Services & digital items keep a manually typed name — don't
            // rebuild it from brand/category parts.
            if (this.productType === 'service' || this.productType === 'digital') return;
            const brandObj = this.brands.find(b => String(b.id) === String(this.selectedBrand));
            const subCatObj = this.categories.find(c => String(c.id) === String(this.selectedSubCategory));
            const mainCatObj = this.categories.find(c => String(c.id) === String(this.selectedMainCategory));
            const activeCatObj = subCatObj || mainCatObj;
            
            // Brand Code & Sub Category Code from Master Data
            const brandCode = (brandObj ? (brandObj.code || brandObj.name || '') : '').trim().replace(/[^A-Za-z0-9 _\-\/]/g, '');
            const model = (this.productModelCode || '').trim().replace(/[^A-Za-z0-9 _\-\/]/g, '');
            const subCatCode = (activeCatObj ? (activeCatObj.code || activeCatObj.name || '') : '').trim().replace(/[^A-Za-z0-9 _\-\/]/g, '');
            
            // 1. Auto SKU: Brand Code + Model + Sub Category Code
            const parts = [];
            if (brandCode) parts.push(brandCode);
            if (model) parts.push(model);
            if (subCatCode) parts.push(subCatCode);
            
            if (parts.length > 0) {
                this.productSku = parts.join('-');
            }
            
            // 2. Auto Name: Brand Code + Model + Compatible Models + Sub Category Code
            const brandNamePart = (brandObj ? (brandObj.code || brandObj.name || '') : '').trim();
            const subCatNamePart = (activeCatObj ? (activeCatObj.code || activeCatObj.name.split('(')[0] || '') : '').trim();
            
            const nameParts = [];
            if (brandNamePart) nameParts.push(brandNamePart);
            if (this.productModelCode && this.productModelCode.trim()) {
                nameParts.push(this.productModelCode.trim());
            }
            if (this.productCompatibleModels && this.productCompatibleModels.trim()) {
                let comp = this.productCompatibleModels.trim();
                if (comp.startsWith('-') || comp.startsWith('(')) {
                    nameParts.push(comp);
                } else {
                    nameParts.push('- ' + comp);
                }
            }
            if (subCatNamePart) nameParts.push(subCatNamePart);
            
            if (nameParts.length > 0 && !this.nameTouched) {
                this.productNameInput = nameParts.join(' ');
            }
        },
        // Return-policy preview + meta-length counter read the DOM (no string
        // embedding in this double-quoted x-data attribute).
        returnPolicyPreview: {{ json_encode(old('return_policy', $product->return_policy ?? '')) }},
        metaDescLen: {{ mb_strlen(old('meta_description', $product->meta_description ?? '')) }},
        refreshReturnPolicyPreview() {
            this.returnPolicyPreview = this.productReturnPolicy && this.productReturnPolicy.trim() ? this.productReturnPolicy.trim() : null;
        },
        descriptionPreviewHtml: {{ json_encode(\App\Support\SafeHtml::sanitize(old('description', $product->description))) }},
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
        initPricing() {
            const cost = parseFloat(this.purchaseCost);
            const retail = parseFloat(this.marginRetail);
            const whole = parseFloat(this.marginWhole);

            if (cost > 0 && retail > 0) {
                this.retailMarkup = (Math.round(((retail - cost) / cost) * 100 * 10) / 10).toString();
            } else {
                try {
                    const savedRetail = localStorage.getItem('datapos_default_retail_markup');
                    if (savedRetail && !this.retailMarkup) {
                        this.retailMarkup = savedRetail;
                        if (cost > 0) this.applyRetailMarkup();
                    }
                } catch (e) {}
            }

            if (cost > 0 && whole > 0) {
                this.wholesaleMarkup = (Math.round(((whole - cost) / cost) * 100 * 10) / 10).toString();
            } else {
                try {
                    const savedWholesale = localStorage.getItem('datapos_default_wholesale_markup');
                    if (savedWholesale && !this.wholesaleMarkup) {
                        this.wholesaleMarkup = savedWholesale;
                        if (cost > 0) this.applyWholesaleMarkup();
                    }
                } catch (e) {}
            }

            if (this.retailMarkup) {
                this.retailCustom = !this.retailPresets.includes(this.retailMarkup);
            }
            if (this.wholesaleMarkup) {
                this.wholesaleCustom = !this.wholesalePresets.includes(this.wholesaleMarkup);
            }
        },
        onRetailMarkupSelect(val) {
            if (val === 'custom') {
                this.retailCustom = true;
                this.$nextTick(() => {
                    this.$refs.retailMarkupInput?.focus();
                    this.$refs.retailMarkupInput?.select();
                });
                return;
            }
            if (val !== '') {
                this.retailMarkup = val;
                this.retailCustom = false;
                try {
                    localStorage.setItem('datapos_default_retail_markup', val);
                } catch (e) {}
                this.applyRetailMarkup();
            }
        },
        onRetailMarkupInput(val) {
            this.retailMarkup = val;
            if (val !== '' && !isNaN(parseFloat(val))) {
                try {
                    localStorage.setItem('datapos_default_retail_markup', val);
                } catch (e) {}
                this.applyRetailMarkup();
            }
        },
        onWholesaleMarkupSelect(val) {
            if (val === 'custom') {
                this.wholesaleCustom = true;
                this.$nextTick(() => {
                    this.$refs.wholesaleMarkupInput?.focus();
                    this.$refs.wholesaleMarkupInput?.select();
                });
                return;
            }
            if (val !== '') {
                this.wholesaleMarkup = val;
                this.wholesaleCustom = false;
                try {
                    localStorage.setItem('datapos_default_wholesale_markup', val);
                } catch (e) {}
                this.applyWholesaleMarkup();
            }
        },
        onWholesaleMarkupInput(val) {
            this.wholesaleMarkup = val;
            if (val !== '' && !isNaN(parseFloat(val))) {
                try {
                    localStorage.setItem('datapos_default_wholesale_markup', val);
                } catch (e) {}
                this.applyWholesaleMarkup();
            }
        },
        onPurchaseCostInput() {
            const cost = parseFloat(this.purchaseCost);
            if (cost > 0) {
                if (this.retailMarkup !== '' && !isNaN(parseFloat(this.retailMarkup))) {
                    this.applyRetailMarkup();
                } else if (parseFloat(this.marginRetail) > 0) {
                    const calculated = (Math.round(((parseFloat(this.marginRetail) - cost) / cost) * 100 * 10) / 10).toString();
                    this.retailMarkup = calculated;
                    this.retailCustom = !this.retailPresets.includes(calculated);
                }
                if (this.wholesaleMarkup !== '' && !isNaN(parseFloat(this.wholesaleMarkup))) {
                    this.applyWholesaleMarkup();
                } else if (parseFloat(this.marginWhole) > 0) {
                    const calculated = (Math.round(((parseFloat(this.marginWhole) - cost) / cost) * 100 * 10) / 10).toString();
                    this.wholesaleMarkup = calculated;
                    this.wholesaleCustom = !this.wholesalePresets.includes(calculated);
                }
            }
        },
        onRetailInput() {
            const cost = parseFloat(this.purchaseCost);
            const retail = parseFloat(this.marginRetail);
            if (cost > 0 && retail > 0) {
                const calculated = (Math.round(((retail - cost) / cost) * 100 * 10) / 10).toString();
                this.retailMarkup = calculated;
                this.retailCustom = !this.retailPresets.includes(calculated);
            }
        },
        applyRetailMarkup() {
            const cost = parseFloat(this.purchaseCost);
            const markup = parseFloat(this.retailMarkup);
            if (cost > 0 && !isNaN(markup)) {
                this.marginRetail = Math.round(cost * (1 + (markup / 100))).toString();
            }
        },
        setRetailMarkupPct(pct) {
            this.onRetailMarkupSelect(pct.toString());
        },
        onWholesaleInput() {
            const cost = parseFloat(this.purchaseCost);
            const whole = parseFloat(this.marginWhole);
            if (cost > 0 && whole > 0) {
                const calculated = (Math.round(((whole - cost) / cost) * 100 * 10) / 10).toString();
                this.wholesaleMarkup = calculated;
                this.wholesaleCustom = !this.wholesalePresets.includes(calculated);
            }
        },
        applyWholesaleMarkup() {
            const cost = parseFloat(this.purchaseCost);
            const markup = parseFloat(this.wholesaleMarkup);
            if (cost > 0 && !isNaN(markup)) {
                this.marginWhole = Math.round(cost * (1 + (markup / 100))).toString();
            }
        },
        setWholesaleMarkupPct(pct) {
            this.onWholesaleMarkupSelect(pct.toString());
        },
        get marginPercent() {
            const r = parseFloat(this.marginRetail);
            if (!r || r <= 0) return 0;
            const c = parseFloat(this.purchaseCost);
            if (c > 0) {
                return Math.round(((r - c) / r) * 100);
            }
            // Blank wholesale means the same as retail (see the field hint) → 0%.
            const raw = this.marginWhole === null || this.marginWhole === undefined ? '' : String(this.marginWhole).trim();
            const w = raw === '' ? r : parseFloat(raw);
            if (!isFinite(w)) return 0;
            return Math.round(((r - w) / r) * 100);
        },
        get mainCategories() {
            return this.categories.filter((c) => !c.parent_id);
        },
        get subCategories() {
            return this.categories.filter((c) => String(c.parent_id) === String(this.selectedMainCategory));
        },
        // The Smart panel drives the same two fields as the Core card, so the
        // Sub Category Code dropdown reads/writes through this one value
        // instead of keeping a second copy of the selection.
        get smartCategoryPick() {
            return String(this.selectedSubCategory || this.selectedMainCategory || '');
        },
        get smartCategoryOptions() {
            const rows = [];
            this.mainCategories.forEach((main) => {
                rows.push({ id: String(main.id), label: (main.code ? '[' + main.code + '] ' : '') + main.name, depth: 0 });
                this.categories
                    .filter((c) => String(c.parent_id) === String(main.id))
                    .forEach((sub) => rows.push({ id: String(sub.id), label: (sub.code ? '[' + sub.code + '] ' : '') + sub.name, depth: 1 }));
            });
            return rows;
        },
        onSmartCategoryPick(value) {
            const cat = this.categories.find((c) => String(c.id) === String(value));
            if (!cat) {
                this.selectedMainCategory = '';
                this.selectedSubCategory = '';
            } else if (cat.parent_id) {
                this.selectedMainCategory = String(cat.parent_id);
                this.selectedSubCategory = String(cat.id);
            } else {
                this.selectedMainCategory = String(cat.id);
                this.selectedSubCategory = '';
            }
            this.normalizeVariantPresetSelection();
            this.recomputeSmartSkuAndName();
        },
        // Phone camera barcode scan: the captured photo is decoded locally by
        // html5-qrcode (already bundled for the POS). Nothing is uploaded.
        scanningBarcode: false,
        barcodeScanError: '',
        async scanBarcodeFromCamera(event) {
            const input = event.target;
            const file = input.files && input.files[0];
            input.value = '';
            if (!file) return;
            this.barcodeScanError = '';
            if (!window.Html5Qrcode) {
                this.barcodeScanError = '{{ __('messages.product_form_barcode_scan_failed') }}';
                return;
            }
            this.scanningBarcode = true;
            try {
                const scanner = new window.Html5Qrcode('admin-barcode-scan-region', { verbose: false });
                const code = await scanner.scanFile(file, false);
                if (code && String(code).trim() !== '') {
                    this.productBarcode = String(code).trim();
                } else {
                    this.barcodeScanError = '{{ __('messages.product_form_barcode_scan_failed') }}';
                }
                try { scanner.clear(); } catch (_) {}
            } catch (e) {
                this.barcodeScanError = '{{ __('messages.product_form_barcode_scan_failed') }}';
            } finally {
                this.scanningBarcode = false;
            }
        },
        // Server field names (variants.0.name) map to DOM names (variants[0][name]).
        jumpToError(field) {
            const name = String(field || '');
            const domName = name.replace(/\.(\d+)\./g, '[$1][').replace(/\./g, '][') + (name.includes('.') ? ']' : '');
            // Built without quote characters: this JS lives inside a double-quoted
            // x-data attribute, so a literal double quote would truncate all state.
            const all = Array.from(document.querySelectorAll('[name]'));
            const root = name.split('.')[0];
            const el = all.find((e) => e.getAttribute('name') === domName)
                || all.find((e) => e.getAttribute('name') === name)
                || all.find((e) => (e.getAttribute('name') || '').indexOf(root) === 0);
            if (!el) return;
            el.scrollIntoView({ block: 'center', behavior: 'smooth' });
            try { el.focus({ preventScroll: true }); } catch (_) { try { el.focus(); } catch (__) {} }
            el.classList.add('ring-2', 'ring-rose-400');
            setTimeout(() => el.classList.remove('ring-2', 'ring-rose-400'), 2600);
        },
        // Product type: labels/icons/descriptions drive the compact indicator strip
        // and the selector that now lives inside the More Details accordion.
        productTypeLabels: {
            standard: '{{ __('messages.product_type_standard') }}',
            serialized: '{{ __('messages.product_type_serialized') }}',
            variant: '{{ __('messages.product_type_variant') }}',
            service: '{{ __('messages.product_type_service') }}',
            digital: '{{ __('messages.product_type_digital') }}',
            weight_based: '{{ __('messages.product_type_weight_based') }}',
        },
        productTypeIcons: { standard: '📦', serialized: '📱', variant: '🔀', service: '🛠️', digital: '💻', weight_based: '⚖️' },
        productTypeDescriptions: {
            standard: '{{ __('messages.product_type_standard_desc') }}',
            serialized: '{{ __('messages.product_type_serialized_desc') }}',
            variant: '{{ __('messages.product_type_variant_desc') }}',
            service: '{{ __('messages.product_type_service_desc') }}',
            digital: '{{ __('messages.product_type_digital_desc') }}',
            weight_based: '{{ __('messages.product_type_weight_based_desc') }}',
        },
        get productTypeLabel() { return this.productTypeLabels[this.productType] || this.productType; },
        get productTypeIcon() { return this.productTypeIcons[this.productType] || '📦'; },
        get productTypeDescription() { return this.productTypeDescriptions[this.productType] || ''; },
        // Services and digital goods hold no stock, so their stock fields are
        // DISABLED (not merely hidden) — a disabled input never submits.
        get isStockless() { return this.productType === 'service' || this.productType === 'digital'; },
        setProductType(type) {
            this.productType = type;
            if (this.isStockless) {
                this.productWarehouseId = '';
                this.productShelfLocation = '';
                const stock = document.querySelector('input[name=initial_stock]');
                if (stock) stock.value = '';
            }
            if (type === 'variant') this.variantsOpen = true;
            this.recomputeSmartSkuAndName();
        },
        openProductTypeSelector() {
            window.dispatchEvent(new CustomEvent('expand-advanced'));
            setTimeout(() => {
                const el = document.getElementById('product-type-selector');
                if (el) el.scrollIntoView({ block: 'center', behavior: 'smooth' });
            }, 160);
        },
        // Live duplicate-code lookup while typing, so a clash is visible before Save.
        codeChecks: { sku: null, barcode: null },
        _codeTimers: {},
        scheduleCodeCheck(field) {
            clearTimeout(this._codeTimers[field]);
            this._codeTimers[field] = setTimeout(() => this.checkCode(field), 600);
        },
        async checkCode(field) {
            const value = String((field === 'sku' ? this.productSku : this.productBarcode) || '').trim();
            if (value === '') {
                this.codeChecks[field] = null;
                return;
            }
            const params = new URLSearchParams({ field: field, value: value });
            if (this.existingProductId) params.set('exclude', String(this.existingProductId));
            try {
                const res = await fetch('{{ route('store.admin.products.check-code', ['store_slug' => $store->slug]) }}?' + params.toString(), {
                    headers: { 'Accept': 'application/json' },
                });
                if (!res.ok) return;
                const data = await res.json();
                this.codeChecks[field] = data.exists ? (data.name || true) : false;
            } catch (_) {
                // Best-effort helper: a failed lookup must never block saving.
            }
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
        },
        handleKeydown(e) {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                e.preventDefault();
                const form = document.querySelector('form[action*=products]');
                if (form) {
                    const saveBtn = form.querySelector('button[value=save]') || form.querySelector('button[type=submit]');
                    if (saveBtn) saveBtn.click();
                }
            }
        }
    }" @keydown.window="handleKeydown($event)" @richtext-sync.window="onRichTextSync($event)">

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
               class="sf-btn-3d h-7 px-2.5 rounded-md text-xs font-bold transition inline-flex items-center gap-1 cursor-pointer">
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
            {{-- Each message jumps to (and highlights) the field it belongs to. --}}
            @foreach ($errors->getBag('default')->getMessages() as $field => $messages)
                @foreach ($messages as $error)
                    <button type="button" @click="jumpToError('{{ $field }}')"
                            title="{{ __('messages.product_form_error_jump') }}"
                            class="pl-5 block w-full text-left text-[11px] hover:underline cursor-pointer">
                        • {{ $error }}
                    </button>
                @endforeach
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
            <button type="submit" {{ $remainingGallerySlots === 0 ? 'disabled' : '' }} class="sf-btn-3d-success px-4 py-2 text-white rounded-lg font-black text-xs whitespace-nowrap shadow-sm disabled:opacity-50 disabled:cursor-not-allowed transition cursor-pointer">
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
                <button type="button" @click="categoryModalOpen = false" class="sf-btn-3d px-3.5 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer">{{ __('messages.close') }}</button>
                <button type="button" @click="createCategory()" class="sf-btn-3d-primary px-3.5 py-1.5 rounded-lg text-xs font-black shadow-sm transition cursor-pointer">{{ __('messages.product_form_save_category') }}</button>
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
                <button type="button" @click="brandModalOpen = false" class="sf-btn-3d px-3.5 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer">{{ __('messages.close') }}</button>
                <button type="button" @click="createBrand()" class="sf-btn-3d-primary px-3.5 py-1.5 rounded-lg text-xs font-black shadow-sm transition cursor-pointer">{{ __('messages.product_form_save_brand') }}</button>
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
                <button type="button" @click="supplierModalOpen = false" class="sf-btn-3d px-3.5 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer">{{ __('messages.close') }}</button>
                <button type="button" @click="createSupplier()" class="sf-btn-3d-primary px-3.5 py-1.5 rounded-lg text-xs font-black shadow-sm transition cursor-pointer">{{ __('messages.product_form_save_supplier') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection
