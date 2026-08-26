@extends('layouts.admin.app')

@section('title', __('messages.sidebar_barcode') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@section('content')
<script nonce="{{ $cspNonce }}">
window._barcodeInitialPool = [
    @foreach($recentProducts as $p)
        {
            id: 'p-{{ $p->id }}',
            product_id: {{ $p->id }},
            name: '{{ addslashes($p->name) }}',
            category_name: '{{ addslashes($p->category?->name ?? '-') }}',
            code: '{{ $p->barcode ?: ($p->sku ?: 'PRD-' . $p->id) }}',
            price: {{ (float) $p->retail_price }},
            quantity: 1
        },
    @endforeach
];

window._barcodePresets = {!! json_encode($presets, JSON_UNESCAPED_UNICODE) !!};

// Standalone pure JavaScript QR Code Generator (Versions 1-4, ECL M) - 100% Offline
window.renderQrCodeSvg = function (text, size) {
    size = size || 64;
    text = (text || '000000').trim();
    if (!text) text = '000000';

    var expTable = new Array(512).fill(0);
    var logTable = new Array(256).fill(0);
    var val = 1;
    for (var i = 0; i < 255; i++) {
        expTable[i] = val;
        logTable[val] = i;
        val <<= 1;
        if (val & 0x100) val ^= 0x11d;
    }
    for (var i = 255; i < 512; i++) {
        expTable[i] = expTable[i - 255];
    }
    function gfMul(x, y) {
        if (x === 0 || y === 0) return 0;
        return expTable[logTable[x] + logTable[y]];
    }

    var specs = {
        1: { total: 26, data: 16, ec: 10, blocks: 1, align: [] },
        2: { total: 44, data: 28, ec: 16, blocks: 1, align: [6, 18] },
        3: { total: 70, data: 44, ec: 26, blocks: 1, align: [6, 22] },
        4: { total: 100, data: 64, ec: 18, blocks: 2, align: [6, 26] }
    };

    var utf8Bytes = [];
    for (var i = 0; i < text.length; i++) {
        var code = text.charCodeAt(i);
        if (code < 128) {
            utf8Bytes.push(code);
        } else if (code < 2048) {
            utf8Bytes.push(192 | (code >> 6), 128 | (code & 63));
        } else {
            utf8Bytes.push(224 | (code >> 12), 128 | ((code >> 6) & 63), 128 | (code & 63));
        }
    }

    var version = 1;
    for (var v in specs) {
        if (utf8Bytes.length <= (specs[v].data - 2)) {
            version = parseInt(v);
            break;
        }
        version = parseInt(v);
    }
    var spec = specs[version] || specs[1];
    var matrixSize = 21 + ((version - 1) * 4);

    var bits = '0100'; // Byte mode
    bits += utf8Bytes.length.toString(2).padStart(8, '0');
    for (var i = 0; i < utf8Bytes.length; i++) {
        bits += utf8Bytes[i].toString(2).padStart(8, '0');
    }
    var totalDataBits = spec.data * 8;
    var termLen = Math.min(4, totalDataBits - bits.length);
    if (termLen > 0) bits += '0'.repeat(termLen);
    while (bits.length % 8 !== 0) bits += '0';
    var pad = ['11101100', '00010001'];
    var padIdx = 0;
    while (bits.length < totalDataBits) {
        bits += pad[padIdx++ % 2];
    }

    var dataCodewords = [];
    for (var i = 0; i < bits.length; i += 8) {
        dataCodewords.push(parseInt(bits.substr(i, 8), 2));
    }

    function calcRS(data, ecLen) {
        var gen = [1];
        for (var i = 0; i < ecLen; i++) {
            var newGen = new Array(gen.length + 1).fill(0);
            for (var a = 0; a < gen.length; a++) {
                newGen[a] ^= gen[a];
                newGen[a + 1] ^= gfMul(gen[a], expTable[i]);
            }
            gen = newGen;
        }

        var poly = data.concat(new Array(ecLen).fill(0));
        for (var i = 0; i < data.length; i++) {
            var coef = poly[i];
            if (coef !== 0) {
                for (var j = 0; j < gen.length; j++) {
                    poly[i + j] ^= gfMul(gen[j], coef);
                }
            }
        }
        return poly.slice(data.length);
    }

    var numBlocks = spec.blocks;
    var ecPerBlock = spec.ec;
    var dataPerBlock = Math.floor(spec.data / numBlocks);
    var blockData = [];
    var blockEc = [];
    for (var b = 0; b < numBlocks; b++) {
        var slice = dataCodewords.slice(b * dataPerBlock, (b + 1) * dataPerBlock);
        blockData.push(slice);
        blockEc.push(calcRS(slice, ecPerBlock));
    }

    var finalCodewords = [];
    for (var i = 0; i < dataPerBlock; i++) {
        for (var b = 0; b < numBlocks; b++) finalCodewords.push(blockData[b][i]);
    }
    for (var i = 0; i < ecPerBlock; i++) {
        for (var b = 0; b < numBlocks; b++) finalCodewords.push(blockEc[b][i]);
    }

    var finalBits = [];
    for (var i = 0; i < finalCodewords.length; i++) {
        for (var b = 7; b >= 0; b--) finalBits.push((finalCodewords[i] >> b) & 1);
    }

    var matrix = [];
    var isFunc = [];
    for (var r = 0; r < matrixSize; r++) {
        matrix.push(new Array(matrixSize).fill(0));
        isFunc.push(new Array(matrixSize).fill(false));
    }

    function placeFinder(sr, sc) {
        for (var r = 0; r < 7; r++) {
            for (var c = 0; c < 7; c++) {
                var isDark = (r === 0 || r === 6 || c === 0 || c === 6 || (r >= 2 && r <= 4 && c >= 2 && c <= 4));
                matrix[sr + r][sc + c] = isDark ? 1 : 0;
                isFunc[sr + r][sc + c] = true;
            }
        }
    }
    placeFinder(0, 0);
    placeFinder(0, matrixSize - 7);
    placeFinder(matrixSize - 7, 0);

    // Separators
    for (var i = 0; i < 8; i++) {
        if (i < matrixSize) {
            matrix[7][i] = 0; isFunc[7][i] = true;
            matrix[i][7] = 0; isFunc[i][7] = true;
            if (matrixSize - 8 + i < matrixSize) {
                matrix[7][matrixSize - 8 + i] = 0; isFunc[7][matrixSize - 8 + i] = true;
                matrix[matrixSize - 8 + i][7] = 0; isFunc[matrixSize - 8 + i][7] = true;
            }
            matrix[i][matrixSize - 8] = 0; isFunc[i][matrixSize - 8] = true;
            matrix[matrixSize - 8][i] = 0; isFunc[matrixSize - 8][i] = true;
        }
    }

    // Alignments
    if (spec.align && spec.align.length) {
        for (var ai = 0; ai < spec.align.length; ai++) {
            for (var aj = 0; aj < spec.align.length; aj++) {
                var ar = spec.align[ai];
                var ac = spec.align[aj];
                if ((ar <= 8 && ac <= 8) || (ar <= 8 && ac >= matrixSize - 8) || (ar >= matrixSize - 8 && ac <= 8)) continue;
                for (var dr = -2; dr <= 2; dr++) {
                    for (var dc = -2; dc <= 2; dc++) {
                        var isDark = (Math.abs(dr) === 2 || Math.abs(dc) === 2 || (dr === 0 && dc === 0));
                        matrix[ar + dr][ac + dc] = isDark ? 1 : 0;
                        isFunc[ar + dr][ac + dc] = true;
                    }
                }
            }
        }
    }

    // Timing
    for (var i = 8; i < matrixSize - 8; i++) {
        var v = (i % 2 === 0) ? 1 : 0;
        if (!isFunc[6][i]) { matrix[6][i] = v; isFunc[6][i] = true; }
        if (!isFunc[i][6]) { matrix[i][6] = v; isFunc[i][6] = true; }
    }
    // Dark module
    matrix[matrixSize - 8][8] = 1;
    isFunc[matrixSize - 8][8] = true;

    // Reserve format area
    for (var i = 0; i < 9; i++) {
        isFunc[8][i] = true; isFunc[i][8] = true;
        isFunc[8][matrixSize - 1 - i] = true; isFunc[matrixSize - 1 - i][8] = true;
    }

    // Data placement
    var bitIdx = 0;
    for (var right = matrixSize - 1; right > 0; right -= 2) {
        if (right === 6) right--;
        var upward = (Math.floor((right + 1) / 2) % 2 === 1);
        for (var vert = 0; vert < matrixSize; vert++) {
            var r = upward ? (matrixSize - 1 - vert) : vert;
            for (var cOff = 0; cOff < 2; cOff++) {
                var c = right - cOff;
                if (!isFunc[r][c]) {
                    var bitVal = bitIdx < finalBits.length ? finalBits[bitIdx++] : 0;
                    matrix[r][c] = bitVal;
                }
            }
        }
    }

    // Mask 0: (r + c) % 2 === 0
    for (var r = 0; r < matrixSize; r++) {
        for (var c = 0; c < matrixSize; c++) {
            if (!isFunc[r][c] && ((r + c) % 2 === 0)) {
                matrix[r][c] ^= 1;
            }
        }
    }

    // Format bits for Mask 0 + ECL M: 0x5412
    var fmt = 0x5412;
    for (var i = 0; i < 6; i++) matrix[8][i] = (fmt >> (14 - i)) & 1;
    matrix[8][7] = (fmt >> 8) & 1;
    matrix[8][8] = (fmt >> 7) & 1;
    matrix[7][8] = (fmt >> 6) & 1;
    for (var i = 0; i < 6; i++) matrix[5 - i][8] = (fmt >> (5 - i)) & 1;
    for (var i = 0; i < 7; i++) matrix[matrixSize - 1 - i][8] = (fmt >> i) & 1;
    for (var i = 0; i < 8; i++) matrix[8][matrixSize - 8 + i] = (fmt >> (7 + i)) & 1;

    var quietZone = 2;
    var totalSize = matrixSize + (quietZone * 2);
    var pathD = '';
    for (var r = 0; r < matrixSize; r++) {
        for (var c = 0; c < matrixSize; c++) {
            if (matrix[r][c] === 1) {
                pathD += 'M' + (c + quietZone) + ',' + (r + quietZone) + 'h1v1h-1z ';
            }
        }
    }

    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + totalSize + ' ' + totalSize + '" width="' + size + '" height="' + size + '" shape-rendering="crispEdges" style="display:block;margin:0 auto;"><rect width="100%" height="100%" fill="#ffffff"/><path d="' + pathD.trim() + '" fill="#000000"/></svg>';
};

window.barcodeDesignerFactory = function () {
    var pool = window._barcodeInitialPool || [];
    var initialItems = pool.length > 0 ? [{ ...pool[0] }] : [];

    return {
        presets: window._barcodePresets || {},
        selectedPreset: 'thermal_50x30',
        codeType: 'barcode_128',
        showStoreName: true,
        showProductName: true,
        showPrice: true,
        showCodeText: true,
        searchQuery: '',
        categoryFilter: '',
        brandFilter: '',
        searchResults: [],
        isSearching: false,
        previewIndex: 0,
        selectedItems: initialItems,
        recentPool: pool,

        // Custom Dimensions, Spacing & Typography Designer State
        isCustomMode: false,
        showCustomPanel: false,
        showSaveModal: false,
        isSavingTemplate: false,
        newTemplateName: '',
        customParams: {
            name: 'Custom Label',
            type: 'thermal',
            width_mm: 50,
            height_mm: 30,
            gap_x_mm: 0,
            gap_y_mm: 0,
            padding_top_mm: 1.2,
            padding_bottom_mm: 1.2,
            padding_left_mm: 1.5,
            padding_right_mm: 1.5,
            spacing_store_to_name_mm: 0.5,
            spacing_name_to_code_mm: 0.5,
            spacing_code_to_price_mm: 0.5,
            store_font_num: 9.0,
            name_font_num: 8.5,
            price_font_num: 11.0,
            margin_top_mm: 0,
            margin_bottom_mm: 0,
            margin_left_mm: 0,
            margin_right_mm: 0,
            cols: 1,
            rows: 1,
            bar_height: 28,
            bar_width: 1.35
        },

        init: function () {
            this.syncCustomParamsFromSelected();
        },

        syncCustomParamsFromSelected: function () {
            var p = this.presets[this.selectedPreset] || this.presets['thermal_50x30'];
            if (p) {
                this.customParams.name = p.name || 'Custom Label';
                this.customParams.type = p.type || 'thermal';
                this.customParams.width_mm = p.width_mm || 50;
                this.customParams.height_mm = p.height_mm || 30;
                this.customParams.gap_x_mm = p.gap_x_mm || 0;
                this.customParams.gap_y_mm = p.gap_y_mm || 0;
                this.customParams.padding_top_mm = p.padding_top_mm || 1.2;
                this.customParams.padding_bottom_mm = p.padding_bottom_mm || 1.2;
                this.customParams.padding_left_mm = p.padding_left_mm || 1.5;
                this.customParams.padding_right_mm = p.padding_right_mm || 1.5;
                this.customParams.spacing_store_to_name_mm = p.spacing_store_to_name_mm || 0.5;
                this.customParams.spacing_name_to_code_mm = p.spacing_name_to_code_mm || 0.5;
                this.customParams.spacing_code_to_price_mm = p.spacing_code_to_price_mm || 0.5;
                this.customParams.store_font_num = parseFloat(p.store_font) || 9.0;
                this.customParams.name_font_num = parseFloat(p.name_font) || 8.5;
                this.customParams.price_font_num = parseFloat(p.price_font) || 11.0;
                this.customParams.margin_top_mm = p.margin_top_mm || 0;
                this.customParams.margin_bottom_mm = p.margin_bottom_mm || 0;
                this.customParams.margin_left_mm = p.margin_left_mm || 0;
                this.customParams.margin_right_mm = p.margin_right_mm || 0;
                this.customParams.cols = p.cols || 1;
                this.customParams.rows = p.rows || 1;
                this.customParams.bar_height = p.bar_height || (p.height_mm <= 22 ? 16 : 28);
                this.customParams.bar_width = p.bar_width || 1.35;
            }
        },

        selectPreset: function (key) {
            this.selectedPreset = key;
            this.isCustomMode = false;
            this.syncCustomParamsFromSelected();
        },

        get currentPresetObj() {
            if (this.isCustomMode) {
                return {
                    name: this.customParams.name || 'Custom Label',
                    type: this.customParams.type,
                    width_mm: parseFloat(this.customParams.width_mm) || 50,
                    height_mm: parseFloat(this.customParams.height_mm) || 30,
                    gap_x_mm: parseFloat(this.customParams.gap_x_mm) || 0,
                    gap_y_mm: parseFloat(this.customParams.gap_y_mm) || 0,
                    padding_top_mm: parseFloat(this.customParams.padding_top_mm) || 1.2,
                    padding_bottom_mm: parseFloat(this.customParams.padding_bottom_mm) || 1.2,
                    padding_left_mm: parseFloat(this.customParams.padding_left_mm) || 1.5,
                    padding_right_mm: parseFloat(this.customParams.padding_right_mm) || 1.5,
                    padding: `${this.customParams.padding_top_mm || 1.2}mm ${this.customParams.padding_right_mm || 1.5}mm ${this.customParams.padding_bottom_mm || 1.2}mm ${this.customParams.padding_left_mm || 1.5}mm`,
                    spacing_store_to_name_mm: parseFloat(this.customParams.spacing_store_to_name_mm) || 0.5,
                    spacing_name_to_code_mm: parseFloat(this.customParams.spacing_name_to_code_mm) || 0.5,
                    spacing_code_to_price_mm: parseFloat(this.customParams.spacing_code_to_price_mm) || 0.5,
                    cols: parseInt(this.customParams.cols) || 1,
                    rows: parseInt(this.customParams.rows) || 1,
                    bar_height: parseInt(this.customParams.bar_height) || 28,
                    store_font: (this.customParams.store_font_num || 9.0) + 'px',
                    name_font: (this.customParams.name_font_num || 8.5) + 'px',
                    price_font: (this.customParams.price_font_num || 11.0) + 'px',
                    is_custom: true
                };
            }
            var base = this.presets[this.selectedPreset] || this.presets['thermal_50x30'] || {};
            return {
                ...base,
                spacing_store_to_name_mm: parseFloat(base.spacing_store_to_name_mm) || 0.5,
                spacing_name_to_code_mm: parseFloat(base.spacing_name_to_code_mm) || 0.5,
                spacing_code_to_price_mm: parseFloat(base.spacing_code_to_price_mm) || 0.5,
            };
        },

        get previewItem() {
            if (this.selectedItems.length === 0) return null;
            if (this.previewIndex >= this.selectedItems.length) {
                this.previewIndex = 0;
            }
            return this.selectedItems[this.previewIndex] || this.selectedItems[0];
        },

        get totalLabelsCount() {
            return this.selectedItems.reduce(function (sum, item) {
                return sum + (parseInt(item.quantity) || 0);
            }, 0);
        },

        renderQrSvg: function (code) {
            return window.renderQrCodeSvg(code, 64);
        },

        formatNumber: function (val) {
            return new Intl.NumberFormat().format(val || 0);
        },

        prevPreview: function () {
            if (this.selectedItems.length === 0) return;
            this.previewIndex = (this.previewIndex - 1 + this.selectedItems.length) % this.selectedItems.length;
        },

        nextPreview: function () {
            if (this.selectedItems.length === 0) return;
            this.previewIndex = (this.previewIndex + 1) % this.selectedItems.length;
        },

        async searchProducts() {
            var url = `{{ route('store.admin.barcode.search', ['store_slug' => $store->slug]) }}?q=${encodeURIComponent(this.searchQuery)}`;
            if (this.categoryFilter) url += `&category_id=${encodeURIComponent(this.categoryFilter)}`;
            if (this.brandFilter) url += `&brand_id=${encodeURIComponent(this.brandFilter)}`;

            try {
                const res = await fetch(url);
                if (res.ok) {
                    this.searchResults = await res.json();
                    this.isSearching = true;
                }
            } catch (e) {
                console.error(e);
            }
        },

        addItem: function (item) {
            var existingIndex = this.selectedItems.findIndex(function (i) { return i.id === item.id; });
            if (existingIndex >= 0) {
                this.selectedItems[existingIndex].quantity++;
                this.previewIndex = existingIndex;
            } else {
                this.selectedItems.push({
                    id: item.id,
                    product_id: item.product_id,
                    name: item.name,
                    category_name: item.category_name || '-',
                    code: item.code,
                    price: item.price,
                    quantity: 1
                });
                this.previewIndex = this.selectedItems.length - 1;
            }
            this.searchQuery = '';
            this.searchResults = [];
            this.isSearching = false;
        },

        addAllRecent: function () {
            var self = this;
            this.recentPool.forEach(function (rp) {
                if (!self.selectedItems.some(function (i) { return i.id === rp.id; })) {
                    self.selectedItems.push({ ...rp, quantity: 1 });
                }
            });
            this.previewIndex = 0;
        },

        clearSelection: function () {
            this.selectedItems = [];
            this.previewIndex = 0;
        },

        removeItem: function (index) {
            this.selectedItems.splice(index, 1);
            if (this.previewIndex >= this.selectedItems.length) {
                this.previewIndex = Math.max(0, this.selectedItems.length - 1);
            }
        },

        async saveCurrentTemplate() {
            if (!this.newTemplateName.trim()) return;
            this.isSavingTemplate = true;
            try {
                const payload = {
                    name: this.newTemplateName.trim(),
                    type: this.customParams.type,
                    width_mm: this.customParams.width_mm,
                    height_mm: this.customParams.height_mm,
                    gap_x_mm: this.customParams.gap_x_mm,
                    gap_y_mm: this.customParams.gap_y_mm,
                    padding_top_mm: this.customParams.padding_top_mm,
                    padding_bottom_mm: this.customParams.padding_bottom_mm,
                    padding_left_mm: this.customParams.padding_left_mm,
                    padding_right_mm: this.customParams.padding_right_mm,
                    spacing_store_to_name_mm: this.customParams.spacing_store_to_name_mm,
                    spacing_name_to_code_mm: this.customParams.spacing_name_to_code_mm,
                    spacing_code_to_price_mm: this.customParams.spacing_code_to_price_mm,
                    store_font: (this.customParams.store_font_num || 9.0) + 'px',
                    name_font: (this.customParams.name_font_num || 8.5) + 'px',
                    price_font: (this.customParams.price_font_num || 11.0) + 'px',
                    margin_top_mm: this.customParams.margin_top_mm,
                    margin_bottom_mm: this.customParams.margin_bottom_mm,
                    margin_left_mm: this.customParams.margin_left_mm,
                    margin_right_mm: this.customParams.margin_right_mm,
                    cols: this.customParams.cols,
                    rows: this.customParams.rows,
                    bar_height: this.customParams.bar_height,
                    bar_width: this.customParams.bar_width,
                    code_type: this.codeType,
                    show_store_name: this.showStoreName,
                    show_product_name: this.showProductName,
                    show_price: this.showPrice,
                    show_code_text: this.showCodeText,
                };

                const res = await fetch(`{{ route('store.admin.barcode.templates.save', ['store_slug' => $store->slug]) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();
                if (data.success) {
                    this.presets[data.preset_key] = data.preset;
                    this.selectedPreset = data.preset_key;
                    this.isCustomMode = false;
                    this.showSaveModal = false;
                    this.newTemplateName = '';
                } else {
                    alert(data.message || 'Failed to save template');
                }
            } catch (e) {
                console.error(e);
                alert('Error saving template');
            } finally {
                this.isSavingTemplate = false;
            }
        },

        async deleteCustomTemplate(key, templateId) {
            if (!confirm('{{ __('messages.barcode_delete_template_confirm') }}')) return;
            try {
                const res = await fetch(`{{ route('store.admin.barcode.templates.delete', ['store_slug' => $store->slug, 'id' => ':id']) }}`.replace(':id', templateId), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    delete this.presets[key];
                    if (this.selectedPreset === key) {
                        this.selectedPreset = 'thermal_50x30';
                        this.syncCustomParamsFromSelected();
                    }
                }
            } catch (e) {
                console.error(e);
            }
        },

        submitPrint: function () {
            if (this.selectedItems.length === 0) return;
            document.getElementById('items_json_field').value = JSON.stringify(this.selectedItems);
            document.getElementById('barcodePrintForm').submit();
        }
    };
};
</script>

<div class="w-full space-y-2 sm:space-y-2.5" x-data="barcodeDesignerFactory()" x-init="init()">

    {{-- ============================================================
         1. COMPACT HERO HEADER (Admin UI Standard)
         ============================================================ --}}
    <div class="relative overflow-hidden rounded-lg bg-gradient-to-r from-slate-900 via-violet-950 to-slate-900 text-white p-3 sm:p-3.5 shadow-md border border-violet-900/40">
        <div class="absolute -right-8 -top-8 w-44 h-44 bg-violet-500/10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="space-y-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-violet-500/20 text-violet-300 border border-violet-500/30">
                        🏷️ {{ __('messages.barcode_label_printing_title') }}
                    </span>
                    <span class="text-slate-400 text-xs font-medium">•</span>
                    <span class="text-xs font-semibold text-slate-300">{{ $store->name }}</span>
                </div>
                <h1 class="text-base sm:text-lg font-black tracking-tight text-white flex items-center gap-2">
                    {{ __('messages.sidebar_barcode') }}
                    <span class="text-xs font-mono font-bold px-2 py-0.5 rounded bg-violet-600/30 text-violet-200 border border-violet-500/30"
                          x-text="totalLabelsCount + ' {{ __('messages.barcode_total_labels') }}'"></span>
                </h1>
            </div>

            {{-- Header Action Buttons --}}
            <div class="flex items-center gap-2 flex-wrap">
                {{-- Add all recent button --}}
                <button type="button"
                        @click="addAllRecent()"
                        class="px-2.5 py-1.5 rounded-md text-xs font-bold bg-white/10 hover:bg-white/15 text-white transition border border-white/10 shadow-2xs flex items-center gap-1.5 cursor-pointer">
                    <span>⚡</span>
                    <span>{{ __('messages.barcode_add_all_in_stock') }}</span>
                </button>

                {{-- Clear selection button --}}
                <button type="button"
                        x-show="selectedItems.length > 0"
                        @click="clearSelection()"
                        class="px-2.5 py-1.5 rounded-md text-xs font-bold bg-rose-500/20 hover:bg-rose-500/30 text-rose-200 transition border border-rose-500/30 shadow-2xs cursor-pointer">
                    ✕ {{ __('messages.barcode_clear_selection') ?? 'Clear Selection' }}
                </button>

                {{-- Primary Print Button --}}
                <button type="button"
                        @click="submitPrint()"
                        :disabled="selectedItems.length === 0"
                        :class="selectedItems.length === 0 ? 'opacity-50 cursor-not-allowed bg-slate-700 text-slate-400' : 'bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white shadow-lg shadow-violet-900/30 cursor-pointer'"
                        class="px-3.5 py-1.5 rounded-md text-xs font-black transition flex items-center gap-1.5 border border-violet-400/30">
                    <span>🖨️</span>
                    <span>{{ __('messages.print_stickers_btn') }}</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ============================================================
         2. SUMMARY STAT CARDS (4-UP COMPACT METRIC CARDS)
         ============================================================ --}}
    <div class="w-full grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-2.5">
        {{-- Total Products --}}
        <div class="w-full bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 flex items-center gap-2.5 sm:gap-3 shadow-2xs transition">
            <div class="shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-lg grid place-items-center bg-violet-100 text-violet-600 dark:bg-violet-950/70 dark:text-violet-300 shadow-inner">
                <span class="text-base sm:text-lg">📦</span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-lg font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-mono">
                    {{ number_format($totalProducts) }}
                </p>
                <p class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 mt-1 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.price_wizard_stat_total_products') }}
                </p>
            </div>
        </div>

        {{-- In-Stock Products --}}
        <div class="w-full bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 flex items-center gap-2.5 sm:gap-3 shadow-2xs transition">
            <div class="shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-lg grid place-items-center bg-emerald-100 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-300 shadow-inner">
                <span class="text-base sm:text-lg">✅</span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-lg font-black text-emerald-600 dark:text-emerald-400 leading-none tabular-nums font-mono">
                    {{ number_format($inStockCount) }}
                </p>
                <p class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 mt-1 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.status_in_stock') ?? 'In-Stock Items' }}
                </p>
            </div>
        </div>

        {{-- Queued Products --}}
        <div class="w-full bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 flex items-center gap-2.5 sm:gap-3 shadow-2xs transition">
            <div class="shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-lg grid place-items-center bg-amber-100 text-amber-600 dark:bg-amber-950/70 dark:text-amber-300 shadow-inner">
                <span class="text-base sm:text-lg">📋</span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-lg font-black text-amber-600 dark:text-amber-400 leading-none tabular-nums font-mono" x-text="selectedItems.length">
                </p>
                <p class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 mt-1 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.barcode_selected_products') }}
                </p>
            </div>
        </div>

        {{-- Total Stickers to Print --}}
        <div class="w-full bg-white dark:bg-slate-900 rounded-lg border border-violet-200/80 dark:border-violet-900/60 bg-violet-50/30 dark:bg-violet-950/20 p-2.5 sm:p-3 flex items-center gap-2.5 sm:gap-3 shadow-2xs transition">
            <div class="shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-lg grid place-items-center bg-violet-600 text-white shadow-inner">
                <span class="text-base sm:text-lg">🏷️</span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-lg font-black text-violet-600 dark:text-violet-400 leading-none tabular-nums font-mono" x-text="totalLabelsCount">
                </p>
                <p class="text-[10px] sm:text-[11px] text-violet-900 dark:text-violet-300 mt-1 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.barcode_total_labels') }}
                </p>
            </div>
        </div>
    </div>

    {{-- ============================================================
         3. MAIN DUAL-COLUMN WORKSPACE
         ============================================================ --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-2 sm:gap-2.5 items-start">

        {{-- Left Column: Designer Settings & Product Selection Matrix (8 Cols on Desktop) --}}
        <div class="lg:col-span-8 space-y-2 sm:space-y-2.5">

            {{-- 3.1 LABEL CONFIGURATION & PRESET / CUSTOM TEMPLATE SELECTOR CARD --}}
            <div class="p-3 sm:p-3.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-md bg-violet-100 dark:bg-violet-950 text-violet-600 dark:text-violet-300 grid place-items-center text-xs font-black">
                            ⚙️
                        </span>
                        <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100">
                            {{ __('messages.barcode_settings_title') }}
                        </h2>
                    </div>

                    {{-- Custom Designer Toggle Button --}}
                    <div class="flex items-center gap-2">
                        <button type="button"
                                @click="showCustomPanel = !showCustomPanel; if (showCustomPanel) isCustomMode = true;"
                                :class="showCustomPanel ? 'bg-violet-600 text-white shadow-md' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'"
                                class="px-2.5 py-1 text-xs font-bold rounded-md transition flex items-center gap-1.5 shadow-2xs cursor-pointer">
                            <span>✨</span>
                            <span>{{ __('messages.barcode_custom_designer') }}</span>
                        </button>
                    </div>
                </div>

                {{-- Preset Selector Cards Grid (Defaults + Saved Templates) --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <template x-for="(preset, key) in presets" :key="key">
                        <div class="relative flex flex-col p-2.5 rounded-lg border cursor-pointer transition select-none shadow-2xs group"
                             @click="selectPreset(key)"
                             :class="selectedPreset === key && !isCustomMode ? 'border-violet-600 bg-violet-50/60 dark:border-violet-500 dark:bg-violet-950/40 ring-2 ring-violet-500/20' : 'border-slate-200 hover:border-slate-300 dark:border-slate-800 dark:hover:border-slate-700 bg-white dark:bg-slate-800/60'">
                            <div class="flex items-center justify-between gap-1 mb-1">
                                <div class="flex items-center gap-1 min-w-0">
                                    <span class="font-bold text-xs text-slate-900 dark:text-slate-100 truncate" x-text="preset.name"></span>
                                    <span x-show="preset.is_custom" class="px-1.5 py-0.2 rounded text-[9px] font-black bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-300 dark:border-amber-800">
                                        {{ __('messages.barcode_custom_template_badge') }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    {{-- Delete custom template icon button --}}
                                    <button type="button"
                                            x-show="preset.is_custom"
                                            @click.stop="deleteCustomTemplate(key, preset.template_id)"
                                            class="text-slate-400 hover:text-rose-600 p-0.5 rounded opacity-0 group-hover:opacity-100 transition cursor-pointer"
                                            title="{{ __('messages.delete') ?? 'Delete Template' }}">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                    <span class="w-2.5 h-2.5 rounded-full"
                                          :class="selectedPreset === key && !isCustomMode ? 'bg-violet-600' : 'bg-slate-300 dark:bg-slate-600'"></span>
                                </div>
                            </div>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 line-clamp-1 leading-normal" x-text="preset.description"></p>
                        </div>
                    </template>

                    {{-- Server-rendered fallback for SSR and Crawler / Test engines --}}
                    <noscript>
                        @foreach ($presets as $key => $preset)
                            <div class="p-2.5 rounded-lg border border-slate-200 bg-white dark:bg-slate-800">
                                <span class="font-bold text-xs">{{ $preset['name'] }}</span>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ $preset['description'] }}</p>
                            </div>
                        @endforeach
                    </noscript>
                </div>

                {{-- ============================================================
                     CUSTOM DIMENSION, SPACING & TEMPLATE CREATOR PANEL
                     ============================================================ --}}
                <div x-show="showCustomPanel"
                     x-collapse
                     class="p-3 bg-slate-50 dark:bg-slate-800/80 rounded-lg border border-violet-200/90 dark:border-violet-900/60 space-y-3 mt-2 shadow-inner">
                    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 pb-1.5">
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs font-black text-violet-900 dark:text-violet-200">📐 {{ __('messages.barcode_custom_designer') }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button"
                                    @click="showSaveModal = true"
                                    class="px-2.5 py-1 text-xs font-black rounded bg-violet-600 hover:bg-violet-700 text-white shadow-2xs flex items-center gap-1 cursor-pointer">
                                <span>💾</span>
                                <span>{{ __('messages.barcode_save_template_btn') }}</span>
                            </button>
                        </div>
                    </div>

                    {{-- Section 1: Paper & Dimension Spacings --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                        {{-- Paper Type --}}
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.barcode_paper_type') }}</label>
                            <select x-model="customParams.type"
                                    @change="isCustomMode = true"
                                    class="w-full border border-slate-200 dark:border-slate-700 rounded-md px-2 py-1 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-violet-500">
                                <option value="thermal">{{ __('messages.barcode_paper_thermal') }}</option>
                                <option value="sheet">{{ __('messages.barcode_paper_sheet') }}</option>
                            </select>
                        </div>

                        {{-- Label Width (mm) --}}
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.barcode_label_width') }}</label>
                            <input type="number" step="0.5" x-model.number="customParams.width_mm" @input="isCustomMode = true"
                                   class="w-full border border-slate-200 dark:border-slate-700 rounded-md px-2 py-1 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono font-bold focus:ring-2 focus:ring-violet-500">
                        </div>

                        {{-- Label Height (mm) --}}
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.barcode_label_height') }}</label>
                            <input type="number" step="0.5" x-model.number="customParams.height_mm" @input="isCustomMode = true"
                                   class="w-full border border-slate-200 dark:border-slate-700 rounded-md px-2 py-1 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono font-bold focus:ring-2 focus:ring-violet-500">
                        </div>

                        {{-- Barcode Bar Height --}}
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.barcode_bar_height') }}</label>
                            <input type="number" x-model.number="customParams.bar_height" @input="isCustomMode = true"
                                   class="w-full border border-slate-200 dark:border-slate-700 rounded-md px-2 py-1 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono font-bold focus:ring-2 focus:ring-violet-500">
                        </div>

                        {{-- Horizontal Gap / Spacing (mm) --}}
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.barcode_gap_x') }}</label>
                            <input type="number" step="0.5" x-model.number="customParams.gap_x_mm" @input="isCustomMode = true"
                                   class="w-full border border-slate-200 dark:border-slate-700 rounded-md px-2 py-1 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono font-bold focus:ring-2 focus:ring-violet-500"
                                   placeholder="0">
                        </div>

                        {{-- Vertical Gap / Spacing (mm) --}}
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.barcode_gap_y') }}</label>
                            <input type="number" step="0.5" x-model.number="customParams.gap_y_mm" @input="isCustomMode = true"
                                   class="w-full border border-slate-200 dark:border-slate-700 rounded-md px-2 py-1 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono font-bold focus:ring-2 focus:ring-violet-500"
                                   placeholder="0">
                        </div>

                        {{-- Top/Bottom Padding (mm) --}}
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.barcode_padding_tb') }}</label>
                            <input type="number" step="0.2" x-model.number="customParams.padding_top_mm" @input="isCustomMode = true; customParams.padding_bottom_mm = customParams.padding_top_mm"
                                   class="w-full border border-slate-200 dark:border-slate-700 rounded-md px-2 py-1 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono font-bold focus:ring-2 focus:ring-violet-500">
                        </div>

                        {{-- Left/Right Padding (mm) --}}
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.barcode_padding_lr') }}</label>
                            <input type="number" step="0.2" x-model.number="customParams.padding_left_mm" @input="isCustomMode = true; customParams.padding_right_mm = customParams.padding_left_mm"
                                   class="w-full border border-slate-200 dark:border-slate-700 rounded-md px-2 py-1 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono font-bold focus:ring-2 focus:ring-violet-500">
                        </div>

                        {{-- Sheet Columns (Only for Sheet type) --}}
                        <div x-show="customParams.type === 'sheet'">
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.barcode_sheet_columns') }}</label>
                            <input type="number" min="1" max="10" x-model.number="customParams.cols" @input="isCustomMode = true"
                                   class="w-full border border-slate-200 dark:border-slate-700 rounded-md px-2 py-1 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono font-bold focus:ring-2 focus:ring-violet-500">
                        </div>

                        {{-- Sheet Rows (Only for Sheet type) --}}
                        <div x-show="customParams.type === 'sheet'">
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.barcode_sheet_rows') }}</label>
                            <input type="number" min="1" max="30" x-model.number="customParams.rows" @input="isCustomMode = true"
                                   class="w-full border border-slate-200 dark:border-slate-700 rounded-md px-2 py-1 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono font-bold focus:ring-2 focus:ring-violet-500">
                        </div>
                    </div>

                    {{-- Section 2: Fine-Grained Typography & Inter-Element Spacings --}}
                    <div class="pt-2 border-t border-slate-200 dark:border-slate-700 space-y-2">
                        <span class="text-[11px] font-black text-slate-800 dark:text-slate-200 flex items-center gap-1">
                            <span>🔤</span>
                            <span>{{ __('messages.barcode_typography_spacing_title') }}</span>
                        </span>

                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2.5 text-xs">
                            {{-- Store Name Font Size --}}
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-300 mb-0.5">{{ __('messages.barcode_store_name_size') }}</label>
                                <input type="number" step="0.5" min="6" max="20" x-model.number="customParams.store_font_num" @input="isCustomMode = true"
                                       class="w-full border border-slate-200 dark:border-slate-700 rounded-md px-2 py-1 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono font-bold">
                            </div>

                            {{-- Product Name Font Size --}}
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-300 mb-0.5">{{ __('messages.barcode_product_name_size') }}</label>
                                <input type="number" step="0.5" min="6" max="20" x-model.number="customParams.name_font_num" @input="isCustomMode = true"
                                       class="w-full border border-slate-200 dark:border-slate-700 rounded-md px-2 py-1 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono font-bold">
                            </div>

                            {{-- Price Font Size --}}
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-300 mb-0.5">{{ __('messages.barcode_price_size') }}</label>
                                <input type="number" step="0.5" min="7" max="24" x-model.number="customParams.price_font_num" @input="isCustomMode = true"
                                       class="w-full border border-slate-200 dark:border-slate-700 rounded-md px-2 py-1 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono font-bold">
                            </div>

                            {{-- Spacing: Store to Product Name --}}
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-300 mb-0.5">{{ __('messages.barcode_spacing_store_to_name') }}</label>
                                <input type="number" step="0.2" min="0" max="10" x-model.number="customParams.spacing_store_to_name_mm" @input="isCustomMode = true"
                                       class="w-full border border-slate-200 dark:border-slate-700 rounded-md px-2 py-1 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono font-bold">
                            </div>

                            {{-- Spacing: Product Name to Code --}}
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-300 mb-0.5">{{ __('messages.barcode_spacing_name_to_code') }}</label>
                                <input type="number" step="0.2" min="0" max="10" x-model.number="customParams.spacing_name_to_code_mm" @input="isCustomMode = true"
                                       class="w-full border border-slate-200 dark:border-slate-700 rounded-md px-2 py-1 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono font-bold">
                            </div>

                            {{-- Spacing: Code to Price --}}
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-300 mb-0.5">{{ __('messages.barcode_spacing_code_to_price') }}</label>
                                <input type="number" step="0.2" min="0" max="10" x-model.number="customParams.spacing_code_to_price_mm" @input="isCustomMode = true"
                                       class="w-full border border-slate-200 dark:border-slate-700 rounded-md px-2 py-1 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono font-bold">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Barcode / QR Code Switcher & Field Visibility Toggles --}}
                <div class="pt-2 border-t border-slate-100 dark:border-slate-800 grid grid-cols-1 sm:grid-cols-2 gap-3 items-center">
                    {{-- Format Selector (Code 128 vs QR Code) --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.barcode_type') }}
                        </label>
                        <div class="grid grid-cols-2 gap-1.5 p-1 bg-slate-100 dark:bg-slate-800/90 rounded-lg border border-slate-200 dark:border-slate-700 text-xs">
                            <button type="button"
                                    @click="codeType = 'barcode_128'"
                                    :class="codeType === 'barcode_128' ? 'bg-white dark:bg-slate-900 text-violet-600 dark:text-violet-400 shadow-xs font-black' : 'text-slate-600 dark:text-slate-400 font-semibold hover:text-slate-900 dark:hover:text-slate-200'"
                                    class="py-1.5 px-2 rounded-md text-center transition flex items-center justify-center gap-1.5 cursor-pointer">
                                <span>|||</span>
                                <span>Code 128</span>
                            </button>
                            <button type="button"
                                    @click="codeType = 'qr_code'"
                                    :class="codeType === 'qr_code' ? 'bg-white dark:bg-slate-900 text-violet-600 dark:text-violet-400 shadow-xs font-black' : 'text-slate-600 dark:text-slate-400 font-semibold hover:text-slate-900 dark:hover:text-slate-200'"
                                    class="py-1.5 px-2 rounded-md text-center transition flex items-center justify-center gap-1.5 cursor-pointer">
                                <span>⊞</span>
                                <span>QR Code</span>
                            </button>
                        </div>
                    </div>

                    {{-- Visibility Switches (4 elements) --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                            {{ __('messages.barcode_display_elements') }}
                        </label>
                        <div class="grid grid-cols-2 gap-1 text-[11px]">
                            <label class="flex items-center gap-1.5 text-slate-700 dark:text-slate-300 cursor-pointer">
                                <input type="checkbox" x-model="showStoreName" class="rounded border-slate-300 dark:border-slate-600 text-violet-600 focus:ring-violet-500">
                                <span>{{ __('messages.barcode_show_store_name') }}</span>
                            </label>
                            <label class="flex items-center gap-1.5 text-slate-700 dark:text-slate-300 cursor-pointer">
                                <input type="checkbox" x-model="showProductName" class="rounded border-slate-300 dark:border-slate-600 text-violet-600 focus:ring-violet-500">
                                <span>{{ __('messages.barcode_show_product_name') }}</span>
                            </label>
                            <label class="flex items-center gap-1.5 text-slate-700 dark:text-slate-300 cursor-pointer">
                                <input type="checkbox" x-model="showPrice" class="rounded border-slate-300 dark:border-slate-600 text-violet-600 focus:ring-violet-500">
                                <span>{{ __('messages.barcode_show_price') }}</span>
                            </label>
                            <label class="flex items-center gap-1.5 text-slate-700 dark:text-slate-300 cursor-pointer">
                                <input type="checkbox" x-model="showCodeText" class="rounded border-slate-300 dark:border-slate-600 text-violet-600 focus:ring-violet-500">
                                <span>{{ __('messages.barcode_show_code_text') }}</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3.2 PRODUCT SEARCH & SPREADSHEET-STYLE STICKER SELECTION CARD --}}
            <div class="p-3 sm:p-3.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-md bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-300 grid place-items-center text-xs font-black">
                            🔍
                        </span>
                        <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100">
                            {{ __('messages.barcode_select_products_title') }}
                        </h2>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <span class="text-xs font-mono font-bold text-slate-500 dark:text-slate-400">
                            <span x-text="selectedItems.length"></span> {{ __('messages.barcode_selected_products') }}
                        </span>
                    </div>
                </div>

                {{-- Search & Quick Filter Controls (Extra Wide Search Box on Desktop) --}}
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-center">
                    {{-- Search Input (Extended Width) --}}
                    <div class="sm:col-span-12 md:col-span-6 lg:col-span-7 xl:col-span-8 relative">
                        <div class="relative">
                            <input type="text"
                                   x-model="searchQuery"
                                   @input.debounce.250ms="searchProducts()"
                                   @focus="if (searchQuery.length > 0) isSearching = true"
                                   placeholder="{{ __('messages.barcode_search_placeholder') }}"
                                   class="w-full pl-8 pr-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg text-xs bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500 shadow-2xs font-semibold">
                            <svg class="w-4 h-4 text-slate-400 dark:text-slate-500 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>

                        {{-- Search Results Dropdown Popup --}}
                        <div x-show="searchResults.length > 0 && isSearching"
                             @click.away="isSearching = false"
                             x-cloak
                             class="absolute z-30 top-full left-0 right-0 mt-1 max-h-72 overflow-y-auto rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-xl divide-y divide-slate-100 dark:divide-slate-800">
                            <template x-for="item in searchResults" :key="item.id">
                                <div @click="addItem(item)"
                                     class="p-2.5 hover:bg-violet-50 dark:hover:bg-violet-950/40 cursor-pointer flex items-center justify-between transition group">
                                    <div class="min-w-0 pr-2">
                                        <div class="text-xs font-bold text-slate-900 dark:text-slate-100 truncate group-hover:text-violet-600 dark:group-hover:text-violet-400" x-text="item.name"></div>
                                        <div class="flex items-center gap-1 text-[10px] text-slate-400 font-mono mt-0.5">
                                            <span x-text="'Code: ' + item.code"></span>
                                            <span>•</span>
                                            <span x-text="item.category_name"></span>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div class="text-xs font-bold font-mono text-violet-600 dark:text-violet-400" x-text="formatNumber(item.price) + ' Ks'"></div>
                                        <div class="text-[10px] text-slate-400" x-text="'Stock: ' + item.stock"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Category Filter --}}
                    <div class="sm:col-span-6 md:col-span-3 lg:col-span-3 xl:col-span-2">
                        <select x-model="categoryFilter"
                                @change="searchProducts()"
                                class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-2 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-violet-500 cursor-pointer shadow-2xs">
                            <option value="">{{ __('messages.all_categories') ?? 'All Categories' }}</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }} ({{ $cat->products_count }})</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Brand Filter --}}
                    <div class="sm:col-span-6 md:col-span-3 lg:col-span-2 xl:col-span-2">
                        <select x-model="brandFilter"
                                @change="searchProducts()"
                                class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-2 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-violet-500 cursor-pointer shadow-2xs">
                            <option value="">{{ __('messages.all_brands') ?? 'All Brands' }}</option>
                            @foreach($brands as $br)
                                <option value="{{ $br->id }}">{{ $br->name }} ({{ $br->products_count }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Selected Items Spreadsheet-style Table --}}
                <div class="overflow-x-auto rounded-lg border border-slate-200/90 dark:border-slate-800 max-h-[55vh] overflow-y-auto">
                    <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                        <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800 border-b-2 border-slate-300 dark:border-slate-700 shadow-xs select-none">
                            <tr class="text-[11px] font-black uppercase tracking-wider divide-x divide-slate-300 dark:divide-slate-700 bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-100">
                                <th class="py-2.5 px-3 min-w-[180px] bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-100">{{ __('messages.product') }}</th>
                                <th class="py-2.5 px-3 min-w-[110px] bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-100">{{ __('messages.barcode') }}</th>
                                <th class="py-2.5 px-3 min-w-[110px] bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-100">{{ __('messages.price') }} (Ks)</th>
                                <th class="py-2.5 px-3 text-center min-w-[130px] bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-100">{{ __('messages.sticker_quantity') }}</th>
                                <th class="py-2.5 px-2 text-center w-12 bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-100"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900">
                            <template x-if="selectedItems.length === 0">
                                <tr>
                                    <td colspan="5" class="p-8 text-center text-slate-400 dark:text-slate-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <span class="text-3xl mb-2">🏷️</span>
                                            <p class="text-sm font-semibold text-slate-600 dark:text-slate-300">{{ __('messages.barcode_no_items_selected_hint') }}</p>
                                            <button type="button"
                                                    @click="addAllRecent()"
                                                    class="mt-2 text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline cursor-pointer">
                                                + {{ __('messages.barcode_add_all_in_stock') }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <template x-for="(item, index) in selectedItems" :key="item.id">
                                <tr @click="previewIndex = index"
                                    :class="previewIndex === index ? 'bg-violet-50/80 dark:bg-violet-950/40 font-semibold' : 'hover:bg-slate-50/80 dark:hover:bg-slate-800/40'"
                                    class="divide-x divide-slate-200/80 dark:divide-slate-800 cursor-pointer transition">
                                    {{-- Product Name & Category --}}
                                    <td class="py-2 px-3">
                                        <div class="flex items-center gap-1.5">
                                            <span x-show="previewIndex === index" class="text-violet-600 dark:text-violet-400 text-xs">👁️</span>
                                            <div class="font-bold text-slate-900 dark:text-slate-100 leading-tight truncate max-w-[200px]" x-text="item.name"></div>
                                        </div>
                                        <div class="text-[10px] text-slate-400 dark:text-slate-500 font-mono mt-0.5" x-text="item.category_name"></div>
                                    </td>

                                    {{-- Barcode / Code --}}
                                    <td class="py-2 px-3 font-mono text-xs text-slate-600 dark:text-slate-400 whitespace-nowrap" x-text="item.code"></td>

                                    {{-- Editable Unit Price --}}
                                    <td class="py-1.5 px-3 whitespace-nowrap" @click.stop>
                                        <input type="number"
                                               x-model.number="item.price"
                                               class="w-24 px-2 py-1 text-xs text-right font-mono font-bold rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 shadow-2xs">
                                    </td>

                                    {{-- Sticker Quantity Stepper --}}
                                    <td class="py-1.5 px-3 text-center whitespace-nowrap" @click.stop>
                                        <div class="inline-flex items-center justify-center gap-1">
                                            <button type="button"
                                                    @click="if (item.quantity > 1) item.quantity--"
                                                    class="w-6 h-6 rounded-md bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-black flex items-center justify-center shadow-2xs cursor-pointer">-</button>
                                            <input type="number"
                                                   x-model.number="item.quantity"
                                                   min="1"
                                                   class="w-14 text-center px-1.5 py-1 text-xs font-mono font-black rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 shadow-2xs">
                                            <button type="button"
                                                    @click="item.quantity++"
                                                    class="w-6 h-6 rounded-md bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-black flex items-center justify-center shadow-2xs cursor-pointer">+</button>
                                        </div>
                                    </td>

                                    {{-- Remove Button --}}
                                    <td class="py-2 px-2 text-center" @click.stop>
                                        <button type="button"
                                                @click="removeItem(index)"
                                                class="text-slate-400 hover:text-rose-600 transition p-1 cursor-pointer"
                                                title="{{ __('messages.delete') ?? 'Remove' }}">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Right Column: Live Sticky Sticker Visual Preview (4 Cols on Desktop) --}}
        <div class="lg:col-span-4 sticky top-2 space-y-2 sm:space-y-2.5">
            <div class="p-3 sm:p-3.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-md bg-violet-100 dark:bg-violet-950 text-violet-600 dark:text-violet-300 grid place-items-center text-xs font-black">
                            👁️
                        </span>
                        <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100">
                            {{ __('messages.barcode_live_preview_title') }}
                        </h2>
                    </div>

                    {{-- Multi-Product Carousel Switcher --}}
                    <div x-show="selectedItems.length > 1" class="flex items-center gap-1">
                        <button type="button"
                                @click="prevPreview()"
                                class="w-5 h-5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 flex items-center justify-center text-xs font-bold shadow-2xs cursor-pointer">◀</button>
                        <span class="text-[10px] font-mono font-bold text-slate-500 dark:text-slate-400"
                              x-text="(previewIndex + 1) + ' / ' + selectedItems.length"></span>
                        <button type="button"
                                @click="nextPreview()"
                                class="w-5 h-5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 flex items-center justify-center text-xs font-bold shadow-2xs cursor-pointer">▶</button>
                    </div>
                </div>

                {{-- Scaled Realistic Thermal Sticker Card Box (Responsive Aspect Ratio + Live Typography & Spacing) --}}
                <div class="p-4 sm:p-5 rounded-lg bg-slate-100 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 flex items-center justify-center min-h-[220px]">
                    <div class="bg-white text-slate-900 rounded-md shadow-md border border-slate-300 dark:border-slate-600 w-full max-w-[240px] text-center flex flex-col items-center justify-between transition-all"
                         :style="`aspect-ratio: ${currentPresetObj.width_mm || 50} / ${currentPresetObj.height_mm || 30}; min-height: 110px; padding: ${currentPresetObj.padding_top_mm || 1.2}mm ${currentPresetObj.padding_right_mm || 1.5}mm ${currentPresetObj.padding_bottom_mm || 1.2}mm ${currentPresetObj.padding_left_mm || 1.5}mm;`">
                        {{-- Store Name --}}
                        <div x-show="showStoreName"
                             class="font-extrabold tracking-tight text-slate-800 truncate w-full"
                             :style="`font-size: ${currentPresetObj.store_font || '9px'}; margin-bottom: ${currentPresetObj.spacing_store_to_name_mm || 0.5}mm;`"
                             x-text="'{{ $store->name }}'"></div>

                        {{-- Product Name --}}
                        <div x-show="showProductName"
                             class="font-semibold text-slate-700 line-clamp-2 w-full leading-tight"
                             :style="`font-size: ${currentPresetObj.name_font || '8.5px'}; margin-bottom: ${currentPresetObj.spacing_name_to_code_mm || 0.5}mm;`"
                             x-text="previewItem ? previewItem.name : 'Sample Product Name'"></div>

                        {{-- Barcode / QR Graphic Simulation --}}
                        <div class="w-full flex items-center justify-center flex-1 min-h-0"
                             :style="`margin-bottom: ${currentPresetObj.spacing_code_to_price_mm || 0.5}mm;`">
                            <template x-if="codeType === 'barcode_128'">
                                <div class="w-full flex flex-col items-center">
                                    <div class="w-4/5 bg-slate-900 flex items-center justify-center text-white font-mono tracking-widest rounded-xs"
                                         :style="`height: ${Math.min(32, Math.max(14, (currentPresetObj.bar_height || 28) * 0.85))}px; background-image: repeating-linear-gradient(90deg, #000 0px, #000 2px, #fff 2px, #fff 4px, #000 4px, #000 7px, #fff 7px, #fff 8px);`"></div>
                                    <div x-show="showCodeText"
                                         class="text-[8.5px] font-mono font-bold mt-0.5 text-slate-800"
                                         x-text="previewItem ? previewItem.code : '885123456789'"></div>
                                </div>
                            </template>
                            <template x-if="codeType === 'qr_code'">
                                <div class="w-full flex flex-col items-center justify-center">
                                    <div class="w-14 h-14 bg-white p-0.5 border border-slate-200 rounded flex items-center justify-center shadow-xs"
                                         x-html="renderQrSvg(previewItem ? previewItem.code : '885123456789')">
                                    </div>
                                    <div x-show="showCodeText"
                                         class="text-[8.5px] font-mono font-bold mt-0.5 text-slate-800"
                                         x-text="previewItem ? previewItem.code : '885123456789'"></div>
                                </div>
                            </template>
                        </div>

                        {{-- Price Badge (MMK) --}}
                        <div x-show="showPrice"
                             class="font-black text-slate-950 font-mono"
                             :style="`font-size: ${currentPresetObj.price_font || '11px'};`"
                             x-text="previewItem ? formatNumber(previewItem.price) + ' Ks' : '15,000 Ks'"></div>
                    </div>
                </div>

                {{-- Summary Specs & Dimensions Details --}}
                <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700 space-y-1.5 text-xs">
                    <div class="flex justify-between">
                        <span class="text-slate-500 dark:text-slate-400">Selected Label:</span>
                        <span class="font-bold text-slate-900 dark:text-slate-100" x-text="currentPresetObj.name || selectedPreset"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500 dark:text-slate-400">{{ __('messages.barcode_label_width') }}:</span>
                        <span class="font-mono font-bold text-slate-900 dark:text-slate-100"
                              x-text="(currentPresetObj.width_mm || 50) + 'mm × ' + (currentPresetObj.height_mm || 30) + 'mm'"></span>
                    </div>
                    <div class="flex justify-between" x-show="currentPresetObj.gap_x_mm > 0 || currentPresetObj.gap_y_mm > 0">
                        <span class="text-slate-500 dark:text-slate-400">Gap Spacing:</span>
                        <span class="font-mono font-bold text-amber-600 dark:text-amber-400"
                              x-text="(currentPresetObj.gap_x_mm || 0) + 'mm (X) / ' + (currentPresetObj.gap_y_mm || 0) + 'mm (Y)'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500 dark:text-slate-400">{{ __('messages.barcode_selected_products') }}:</span>
                        <span class="font-bold font-mono text-slate-900 dark:text-slate-100" x-text="selectedItems.length + ' products'"></span>
                    </div>
                    <div class="flex justify-between pt-1 border-t border-slate-200 dark:border-slate-700">
                        <span class="text-slate-500 dark:text-slate-400">{{ __('messages.barcode_total_labels') }}:</span>
                        <span class="font-black font-mono text-violet-600 dark:text-violet-400 text-sm" x-text="totalLabelsCount + ' stickers'"></span>
                    </div>
                </div>

                {{-- Big Print Action CTA Button --}}
                <button type="button"
                        @click="submitPrint()"
                        :disabled="selectedItems.length === 0"
                        :class="selectedItems.length === 0 ? 'opacity-50 cursor-not-allowed bg-slate-700 text-slate-400' : 'bg-gradient-to-r from-violet-600 via-indigo-600 to-violet-700 hover:from-violet-500 hover:to-indigo-500 text-white shadow-lg shadow-violet-900/30 active:scale-[0.99] cursor-pointer'"
                        class="w-full py-2.5 px-4 rounded-lg font-black text-xs sm:text-sm tracking-wide transition flex items-center justify-center gap-2 border border-violet-400/30">
                    <span>🖨️</span>
                    <span>{{ __('messages.print_stickers_btn') }}</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ============================================================
         4. SAVE AS TEMPLATE MODAL DIALOG
         ============================================================ --}}
    <div x-show="showSaveModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div @click.away="showSaveModal = false"
             class="w-full max-w-md bg-white dark:bg-slate-900 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-800 p-5 space-y-4 animate-in fade-in zoom-in-95">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                <div class="flex items-center gap-2">
                    <span class="text-lg">💾</span>
                    <h3 class="text-sm font-black text-slate-900 dark:text-slate-100">{{ __('messages.barcode_save_template_modal_title') }}</h3>
                </div>
                <button type="button" @click="showSaveModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-sm font-bold cursor-pointer">✕</button>
            </div>

            <div class="space-y-3 text-xs">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.barcode_template_name') }}</label>
                    <input type="text"
                           x-model="newTemplateName"
                           placeholder="{{ __('messages.barcode_template_name_placeholder') }}"
                           class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-violet-500">
                </div>

                <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700 space-y-1 text-[11px]">
                    <div class="flex justify-between">
                        <span class="text-slate-500 dark:text-slate-400">{{ __('messages.barcode_label_width') }}:</span>
                        <span class="font-mono font-bold text-slate-800 dark:text-slate-200" x-text="customParams.width_mm + 'mm × ' + customParams.height_mm + 'mm'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500 dark:text-slate-400">Spacing Gaps:</span>
                        <span class="font-mono font-bold text-slate-800 dark:text-slate-200" x-text="(customParams.gap_x_mm || 0) + 'mm (X) / ' + (customParams.gap_y_mm || 0) + 'mm (Y)'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500 dark:text-slate-400">{{ __('messages.barcode_paper_type') }}:</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200 uppercase" x-text="customParams.type"></span>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                <button type="button"
                        @click="showSaveModal = false"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 cursor-pointer">
                    {{ __('messages.cancel') ?? 'Cancel' }}
                </button>
                <button type="button"
                        @click="saveCurrentTemplate()"
                        :disabled="!newTemplateName.trim() || isSavingTemplate"
                        class="px-4 py-1.5 rounded-lg text-xs font-black bg-violet-600 hover:bg-violet-700 text-white shadow-md disabled:opacity-50 flex items-center gap-1.5 cursor-pointer">
                    <span x-show="isSavingTemplate">⏳</span>
                    <span>{{ __('messages.barcode_save_template_btn') }}</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ============================================================
         5. HIDDEN FORM FOR PRINT SUBMISSION
         ============================================================ --}}
    <form id="barcodePrintForm"
          method="POST"
          action="{{ route('store.admin.barcode.print', ['store_slug' => $store->slug]) }}"
          target="_blank"
          class="hidden">
        @csrf
        <input type="hidden" name="preset" :value="selectedPreset">
        <input type="hidden" name="is_custom_override" :value="isCustomMode ? '1' : '0'">
        <input type="hidden" name="custom_name" :value="customParams.name">
        <input type="hidden" name="custom_type" :value="customParams.type">
        <input type="hidden" name="custom_width_mm" :value="customParams.width_mm">
        <input type="hidden" name="custom_height_mm" :value="customParams.height_mm">
        <input type="hidden" name="custom_gap_x_mm" :value="customParams.gap_x_mm">
        <input type="hidden" name="custom_gap_y_mm" :value="customParams.gap_y_mm">
        <input type="hidden" name="custom_padding_top_mm" :value="customParams.padding_top_mm">
        <input type="hidden" name="custom_padding_bottom_mm" :value="customParams.padding_bottom_mm">
        <input type="hidden" name="custom_padding_left_mm" :value="customParams.padding_left_mm">
        <input type="hidden" name="custom_padding_right_mm" :value="customParams.padding_right_mm">
        <input type="hidden" name="custom_spacing_store_to_name_mm" :value="customParams.spacing_store_to_name_mm">
        <input type="hidden" name="custom_spacing_name_to_code_mm" :value="customParams.spacing_name_to_code_mm">
        <input type="hidden" name="custom_spacing_code_to_price_mm" :value="customParams.spacing_code_to_price_mm">
        <input type="hidden" name="custom_store_font" :value="(customParams.store_font_num || 9.0) + 'px'">
        <input type="hidden" name="custom_name_font" :value="(customParams.name_font_num || 8.5) + 'px'">
        <input type="hidden" name="custom_price_font" :value="(customParams.price_font_num || 11.0) + 'px'">
        <input type="hidden" name="custom_margin_top_mm" :value="customParams.margin_top_mm">
        <input type="hidden" name="custom_margin_bottom_mm" :value="customParams.margin_bottom_mm">
        <input type="hidden" name="custom_margin_left_mm" :value="customParams.margin_left_mm">
        <input type="hidden" name="custom_margin_right_mm" :value="customParams.margin_right_mm">
        <input type="hidden" name="custom_cols" :value="customParams.cols">
        <input type="hidden" name="custom_rows" :value="customParams.rows">
        <input type="hidden" name="custom_bar_height" :value="customParams.bar_height">
        <input type="hidden" name="custom_bar_width" :value="customParams.bar_width">
        <input type="hidden" name="code_type" :value="codeType">
        <input type="hidden" name="show_store_name" :value="showStoreName ? '1' : '0'">
        <input type="hidden" name="show_product_name" :value="showProductName ? '1' : '0'">
        <input type="hidden" name="show_price" :value="showPrice ? '1' : '0'">
        <input type="hidden" name="show_code_text" :value="showCodeText ? '1' : '0'">
        <input type="hidden" name="items_json" id="items_json_field" value="[]">
    </form>

</div>
@endsection
