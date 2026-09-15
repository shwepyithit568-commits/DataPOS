<script nonce="{{ $cspNonce ?? '' }}">
    /**
     * Shared JavaScript Factory & Helper for Purchase Order Price Adjustment,
     * Keyboard-first Zero-Mouse Navigation, Camera Barcode Scanner and Voucher Cross-Check.
     * Used by both create.blade.php and edit.blade.php without code duplication.
     */
    window.initPriceAdjustmentComponent = function (config) {
        // Resolve a markup % with the documented precedence:
        // 1. Store Settings (Admin → Settings → POS) — only when the owner actually saved one
        // 2. legacy per-browser localStorage value from the product form
        // 3. hard fallback (20% retail / 10% wholesale)
        const resolveMarkup = (storeValue, localValue, fallback) => {
            const store = parseFloat(storeValue);
            if (config.storeHasMarkups && !isNaN(store)) return store;
            const local = parseFloat(localValue);
            if (!isNaN(local)) return local;
            return !isNaN(store) ? store : fallback;
        };

        const effectiveRetailMarkup = resolveMarkup(
            config.defaultRetailMarkup,
            localStorage.getItem('datapos_default_retail_markup'),
            20
        );
        const effectiveWholesaleMarkup = resolveMarkup(
            config.defaultWholesaleMarkup,
            localStorage.getItem('datapos_default_wholesale_markup'),
            10
        );

        const labelReviewPrices = @js(__('messages.po_price_adjustment_btn'));
        const labelCostUp = @js(__('messages.po_price_adjustment_badge_up'));
        const labelCostDown = @js(__('messages.po_price_adjustment_badge_down'));

        return {
            priceModalOpen: false,
            changedItems: [],
            submitting: false,
            bypassModalSubmit: false,

            // Voucher Total Cross-Check state
            expectedVoucherTotal: '',
            get voucherDiff() {
                const exp = parseFloat(this.expectedVoucherTotal);
                if (isNaN(exp) || exp <= 0) return 0;
                return this.netTotal - exp;
            },
            get voucherMatchStatus() {
                const exp = parseFloat(this.expectedVoucherTotal);
                if (isNaN(exp) || exp <= 0) return 'none';
                const diff = this.netTotal - exp;
                if (Math.abs(diff) < 0.01) return 'matched';
                return diff > 0 ? 'over' : 'short';
            },

            // Camera Barcode Scanner state
            cameraModalOpen: false,
            continuousScan: true,
            scannerActive: false,
            scannerError: '',
            scannerNotice: '',
            scannerNoticeTimer: null,
            _html5QrScanner: null,
            _lastScanCode: null,
            _lastScanTime: 0,

            /**
             * Check if a row has a changed cost compared to baseline.
             */
            hasCostChanged(row) {
                const base = parseFloat(row.baseline_cost !== undefined ? row.baseline_cost : (row.cost || 0)) || 0;
                const current = parseFloat(row.unit_cost) || 0;
                return Math.abs(current - base) >= 0.01;
            },

            /**
             * Get cost change percentage formatted.
             */
            costDiffPct(row) {
                const base = parseFloat(row.baseline_cost !== undefined ? row.baseline_cost : (row.cost || 0)) || 0;
                const current = parseFloat(row.unit_cost) || 0;
                if (base <= 0) return current > 0 ? '+100' : '0';
                const diff = ((current - base) / base) * 100;
                const rounded = Math.round(diff * 10) / 10;
                return (rounded > 0 ? '+' : '') + rounded;
            },

            /**
             * Tooltip for the in-row gear badge, e.g. "Review Prices: +50% (Cost Up)".
             * Built here (not inline in the view) so the label can be translated without
             * risking quote collisions inside an x-attribute.
             */
            priceAdjustmentTitle(row) {
                const pct = this.costDiffPct(row);
                const dir = parseFloat(pct) >= 0 ? labelCostUp : labelCostDown;
                return labelReviewPrices + ': ' + pct + '% (' + dir + ')';
            },

            /**
             * Scan all current rows and gather those with changed cost.
             */
            gatherChangedItems(rows) {
                const list = [];
                for (const r of rows) {
                    const baseCost = parseFloat(r.baseline_cost !== undefined ? r.baseline_cost : (r.cost || 0)) || 0;
                    const newCost = parseFloat(r.unit_cost) || 0;

                    if (Math.abs(newCost - baseCost) >= 0.01) {
                        const currentRetail = parseFloat(r.retail_price || r.price || 0) || 0;
                        const currentWholesale = parseFloat(r.wholesale_price || 0) || 0;

                        // Suggested prices = New Cost * (1 + markup / 100)
                        const suggestedRetail = Math.round(newCost * (1 + effectiveRetailMarkup / 100) * 100) / 100;
                        const suggestedWholesale = Math.round(newCost * (1 + effectiveWholesaleMarkup / 100) * 100) / 100;

                        const diffPct = baseCost > 0 ? Math.round(((newCost - baseCost) / baseCost) * 1000) / 10 : 100;

                        const key = (r.product_id || 0) + ':' + (r.product_variant_id || 0);

                        list.push({
                            key: key,
                            product_id: r.product_id,
                            product_variant_id: r.product_variant_id || null,
                            name: r.name,
                            sku: r.sku,
                            baseline_cost: baseCost.toFixed(2),
                            new_unit_cost: newCost.toFixed(2),
                            cost_diff_pct: diffPct,
                            expected_retail_price: currentRetail.toFixed(2),
                            expected_wholesale_price: currentWholesale.toFixed(2),
                            suggested_retail: suggestedRetail.toFixed(2),
                            suggested_wholesale: suggestedWholesale.toFixed(2),
                            retail_price: suggestedRetail.toFixed(2),
                            wholesale_price: suggestedWholesale.toFixed(2),
                            update_prices: true
                        });
                    }
                }
                return list;
            },

            /**
             * Open review modal for a single item row clicked via in-table badge.
             */
            openSingleItemReview(singleRow, allRows) {
                const allChanged = this.gatherChangedItems(allRows);
                const match = allChanged.find(i => i.product_id === singleRow.product_id && (i.product_variant_id || null) === (singleRow.product_variant_id || null));
                if (match) {
                    this.changedItems = [match];
                } else {
                    const baseCost = parseFloat(singleRow.baseline_cost !== undefined ? singleRow.baseline_cost : (singleRow.cost || 0)) || 0;
                    const newCost = parseFloat(singleRow.unit_cost) || 0;
                    const currentRetail = parseFloat(singleRow.retail_price || singleRow.price || 0) || 0;
                    const currentWholesale = parseFloat(singleRow.wholesale_price || 0) || 0;
                    const suggestedRetail = Math.round(newCost * (1 + effectiveRetailMarkup / 100) * 100) / 100;
                    const suggestedWholesale = Math.round(newCost * (1 + effectiveWholesaleMarkup / 100) * 100) / 100;
                    const diffPct = baseCost > 0 ? Math.round(((newCost - baseCost) / baseCost) * 1000) / 10 : 100;

                    this.changedItems = [{
                        key: (singleRow.product_id || 0) + ':' + (singleRow.product_variant_id || 0),
                        product_id: singleRow.product_id,
                        product_variant_id: singleRow.product_variant_id || null,
                        name: singleRow.name,
                        sku: singleRow.sku,
                        baseline_cost: baseCost.toFixed(2),
                        new_unit_cost: newCost.toFixed(2),
                        cost_diff_pct: diffPct,
                        expected_retail_price: currentRetail.toFixed(2),
                        expected_wholesale_price: currentWholesale.toFixed(2),
                        suggested_retail: suggestedRetail.toFixed(2),
                        suggested_wholesale: suggestedWholesale.toFixed(2),
                        retail_price: suggestedRetail.toFixed(2),
                        wholesale_price: suggestedWholesale.toFixed(2),
                        update_prices: true
                    }];
                }
                this.priceModalOpen = true;
            },

            selectAllPriceUpdates(val) {
                this.changedItems.forEach(i => { i.update_prices = val; });
            },

            areAllSelected() {
                return this.changedItems.length > 0 && this.changedItems.every(i => i.update_prices);
            },

            /**
             * Intercept form submit:
             * 1. Check Voucher cross-check difference (if provided)
             * 2. If changed items exist and not bypassing, open price adjustment modal.
             */
            handleFormSubmit(event, formElement, allRows) {
                if (this.bypassModalSubmit) {
                    return true;
                }

                // Step 1: Check voucher total mismatch warning if user specified expected voucher total
                if (this.voucherMatchStatus === 'over' || this.voucherMatchStatus === 'short') {
                    const diffFormatted = this.fmt(Math.abs(this.voucherDiff));
                    const confirmMsg = (@js(__('messages.po_voucher_mismatch_warning')) || '').replace(':diff', diffFormatted);
                    if (!window.confirm(confirmMsg)) {
                        if (event) { event.preventDefault(); event.stopPropagation(); }
                        return false;
                    }
                }

                // Step 2: Price adjustment check
                this.changedItems = this.gatherChangedItems(allRows);
                if (this.changedItems.length > 0) {
                    if (event) { event.preventDefault(); event.stopPropagation(); }
                    this.priceModalOpen = true;
                    return false;
                }

                // No price changes, proceed normally
                return true;
            },

            /**
             * Submit with "Keep Current Prices & Save".
             */
            submitKeepCurrentPrices(formElement) {
                if (this.submitting) return;
                this.submitting = true;
                this.cleanHiddenPriceInputs(formElement);
                this.bypassModalSubmit = true;
                this.priceModalOpen = false;
                formElement.submit();
            },

            /**
             * Submit with "Update Selected Prices & Save".
             */
            submitApplySelectedPrices(formElement) {
                if (this.submitting) return;
                this.submitting = true;
                this.cleanHiddenPriceInputs(formElement);

                // Append hidden inputs for price_updates
                this.changedItems.forEach((item, idx) => {
                    this.appendHidden(formElement, `price_updates[${idx}][product_id]`, item.product_id);
                    if (item.product_variant_id) {
                        this.appendHidden(formElement, `price_updates[${idx}][product_variant_id]`, item.product_variant_id);
                    }
                    this.appendHidden(formElement, `price_updates[${idx}][expected_retail_price]`, item.expected_retail_price);
                    this.appendHidden(formElement, `price_updates[${idx}][expected_wholesale_price]`, item.expected_wholesale_price);
                    this.appendHidden(formElement, `price_updates[${idx}][retail_price]`, item.retail_price);
                    this.appendHidden(formElement, `price_updates[${idx}][wholesale_price]`, item.wholesale_price);
                    this.appendHidden(formElement, `price_updates[${idx}][update_prices]`, item.update_prices ? '1' : '0');
                });

                this.bypassModalSubmit = true;
                this.priceModalOpen = false;
                formElement.submit();
            },

            appendHidden(form, name, value) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value !== null && value !== undefined ? value : '';
                input.classList.add('temp-price-update-field');
                form.appendChild(input);
            },

            cleanHiddenPriceInputs(form) {
                const existing = form.querySelectorAll('.temp-price-update-field');
                existing.forEach(el => el.remove());
            },

            // --- Keyboard Navigation & Zero-Mouse Helpers ---
            focusRowQty(idx) {
                this.$nextTick(() => {
                    const el = document.querySelector(`input[name="items[${idx}][quantity]"]`);
                    if (el) {
                        el.focus();
                        el.select();
                    }
                });
            },

            focusRowCost(idx) {
                this.$nextTick(() => {
                    const el = document.querySelector(`input[name="items[${idx}][unit_cost]"]`);
                    if (el) {
                        el.focus();
                        el.select();
                    }
                });
            },

            focusSearch() {
                this.q = '';
                this.results = [];
                this.open = false;
                this.searched = false;
                this.$nextTick(() => {
                    if (this.$refs.searchInput) {
                        this.$refs.searchInput.focus();
                    }
                });
            },

            initKeyboardShortcuts() {
                window.addEventListener('keydown', (e) => {
                    // F2: Focus Search
                    if (e.key === 'F2') {
                        e.preventDefault();
                        this.focusSearch();
                    }
                    // Ctrl+S / Cmd+S: Save PO
                    else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                        e.preventDefault();
                        if (document.activeElement && typeof document.activeElement.blur === 'function') {
                            document.activeElement.blur();
                        }
                        if (this.$refs.poForm) {
                            this.$refs.poForm.requestSubmit();
                        }
                    }
                });
            },

            playBeepChime() {
                try {
                    const AudioCtx = window.AudioContext || window.webkitAudioContext;
                    if (!AudioCtx) return;
                    const ctx = new AudioCtx();
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(880, ctx.currentTime); // A5 tone
                    gain.gain.setValueAtTime(0.08, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.12);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start();
                    osc.stop(ctx.currentTime + 0.12);
                } catch (_) {}
            },

            // --- Camera Barcode Scanner Methods ---
            async openCameraScanner() {
                this.cameraModalOpen = true;
                this.scannerError = '';
                this.scannerNotice = '';
                await this.$nextTick();
                await this.startHtml5Scanner();
            },

            async startHtml5Scanner() {
                if (!window.Html5Qrcode) {
                    this.scannerError = 'Html5Qrcode library not loaded.';
                    return;
                }
                const viewportEl = document.getElementById('po-camera-scanner-viewport');
                if (!viewportEl) return;

                try {
                    if (this._html5QrScanner) {
                        await this._html5QrScanner.stop().catch(() => {});
                        await this._html5QrScanner.clear().catch(() => {});
                    }
                    this._html5QrScanner = new window.Html5Qrcode('po-camera-scanner-viewport', { verbose: false });
                    this.scannerActive = true;
                    await this._html5QrScanner.start(
                        { facingMode: 'environment' },
                        {
                            fps: 10,
                            qrbox: { width: 280, height: 160 },
                            aspectRatio: 1.777778
                        },
                        (decodedText) => {
                            this.onBarcodeDetected(decodedText);
                        },
                        () => { /* frame scan - silent */ }
                    );
                } catch (err) {
                    this.scannerActive = false;
                    this.scannerError = @js(__('messages.po_camera_permission_denied'));
                }
            },

            async closeCameraScanner() {
                if (this._html5QrScanner) {
                    try {
                        await this._html5QrScanner.stop().catch(() => {});
                        await this._html5QrScanner.clear().catch(() => {});
                    } catch (_) {}
                    this._html5QrScanner = null;
                }
                this.scannerActive = false;
                this.cameraModalOpen = false;
                this.focusSearch();
            },

            async onBarcodeDetected(code) {
                const cleanCode = String(code || '').trim();
                if (!cleanCode) return;

                // Debounce rapid identical scans within 1.5 seconds
                if (this._lastScanCode === cleanCode && (Date.now() - (this._lastScanTime || 0)) < 1500) {
                    return;
                }
                this._lastScanCode = cleanCode;
                this._lastScanTime = Date.now();

                this.playBeepChime();
                this.showScannerNotice('🔍 Scanning ' + cleanCode + '...');

                try {
                    const params = new URLSearchParams({ q: cleanCode });
                    const res = await fetch(config.productsSearchUrl + '?' + params.toString(), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const json = await res.json();
                    const items = json.results || [];
                    if (items.length > 0) {
                        const target = items[0];
                        this.addProduct(target, false);
                        this.showScannerNotice('✅ ' + target.name + ' (' + (target.sku || cleanCode) + ')');
                        if (!this.continuousScan) {
                            await this.closeCameraScanner();
                        }
                    } else {
                        const notFoundTpl = @js(__('messages.po_barcode_not_found')) || 'No product found with barcode :code';
                        this.showScannerNotice('⚠️ ' + notFoundTpl.replace(':code', cleanCode));
                    }
                } catch (e) {
                    this.showScannerNotice('⚠️ Error finding barcode: ' + cleanCode);
                }
            },

            showScannerNotice(msg) {
                this.scannerNotice = msg;
                if (this.scannerNoticeTimer) clearTimeout(this.scannerNoticeTimer);
                this.scannerNoticeTimer = setTimeout(() => {
                    this.scannerNotice = '';
                }, 3000);
            },

            async scanUploadedBarcodeFile(e) {
                const file = e.target.files && e.target.files[0];
                e.target.value = '';
                if (!file || !window.Html5Qrcode) return;
                try {
                    const tempScanner = new window.Html5Qrcode('po-camera-scanner-viewport', { verbose: false });
                    const code = await tempScanner.scanFile(file, false);
                    if (code) {
                        await this.onBarcodeDetected(code);
                    } else {
                        this.showScannerNotice('⚠️ Could not read barcode from image.');
                    }
                    try { tempScanner.clear(); } catch (_) {}
                } catch (err) {
                    this.showScannerNotice('⚠️ Could not read barcode from image.');
                }
            }
        };
    };

    window.poCreateComponent = function (config) {
        const adjustment = window.initPriceAdjustmentComponent(config);
        return {
            ...adjustment,
            submitting: false,
            rows: config.initialRows || [],
            q: '',
            results: [],
            open: false,
            searching: false,
            searched: false,
            filterBrand: '',
            filterCategory: '',
            supplierId: config.supplierId || '',
            supplierName: config.supplierName || '',
            discountAmount: parseFloat(config.discountAmount) || 0,
            deliveryFee: parseFloat(config.deliveryFee) || 0,
            paymentMode: config.paymentMode === 'cash' ? 'cash' : 'credit',
            paidAmount: parseFloat(config.paidAmount) || 0,
            voucherPreviews: [],

            init() {
                this.initKeyboardShortcuts();
            },

            get effectivePaid() {
                const total = this.netTotal;
                if (this.paymentMode !== 'cash') return 0;
                let amt = parseFloat(this.paidAmount) || 0;
                if (amt <= 0) amt = total;
                return Math.min(Math.max(0, amt), total);
            },
            get paidStatus() {
                if (this.paymentMode !== 'cash') return '';
                return this.effectivePaid >= this.netTotal ? 'paid' : 'partial';
            },
            setPaymentMode(mode) {
                this.paymentMode = mode;
                if (mode === 'cash' && (!this.paidAmount || this.paidAmount <= 0)) {
                    this.paidAmount = this.netTotal;
                }
            },
            get canSearch() { return this.q.trim() !== '' || this.filterBrand !== '' || this.filterCategory !== ''; },
            async search(open = true) {
                if (!this.canSearch) { this.results = []; this.open = false; this.searched = false; return; }
                this.searching = true;
                try {
                    const params = new URLSearchParams();
                    if (this.q.trim() !== '') params.set('q', this.q.trim());
                    if (this.filterBrand) params.set('brand_id', this.filterBrand);
                    if (this.filterCategory) params.set('category_id', this.filterCategory);
                    const res = await fetch(config.productsSearchUrl + '?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    const json = await res.json();
                    this.results = (json.results || []).slice(0, 10);
                    this.searched = true;
                    this.open = open;
                } finally {
                    this.searching = false;
                }
            },
            onFilterChange() { this.search(true); },
            enterPick() {
                if (this.open && this.results.length > 0) { this.addProduct(this.results[0]); }
            },
            addProduct(p, focusQty = true) {
                const variantId = p.type === 'variant' ? p.id : null;
                let targetIdx = this.rows.findIndex(r => r.product_id === p.product_id && r.product_variant_id === variantId);
                if (targetIdx >= 0) {
                    this.rows[targetIdx].quantity = String((parseFloat(this.rows[targetIdx].quantity) || 0) + 1);
                } else {
                    this.rows.push({
                        product_id: p.product_id,
                        product_variant_id: variantId,
                        name: p.name,
                        sku: p.sku,
                        balance: p.balance || 0,
                        quantity: '1',
                        unit_cost: p.cost || '',
                        baseline_cost: p.cost || '0',
                        cost: p.cost || '0',
                        retail_price: p.retail_price || p.price || '0',
                        wholesale_price: p.wholesale_price || '0'
                    });
                    targetIdx = this.rows.length - 1;
                }
                this.q = '';
                this.results = [];
                this.open = false;
                this.searched = false;
                this.playBeepChime();
                if (focusQty) {
                    this.focusRowQty(targetIdx);
                } else {
                    this.focusSearch();
                }
            },
            removeRow(i) { this.rows.splice(i, 1); },
            incQty(r) { r.quantity = String((parseFloat(r.quantity) || 0) + 1); },
            decQty(r) { r.quantity = String(Math.max(1, (parseFloat(r.quantity) || 0) - 1)); },
            lineTotal(r) { return (parseFloat(r.quantity) || 0) * (parseFloat(r.unit_cost) || 0); },
            fmt(n) { return typeof window.formatCurrency === 'function' ? window.formatCurrency(n) : Number(n).toLocaleString(); },
            fmtQty(n) { return typeof window.formatQuantity === 'function' ? window.formatQuantity(n) : String(n); },
            get totalQty() { return this.rows.reduce((s, r) => s + (parseFloat(r.quantity) || 0), 0); },
            get subtotal() { return this.rows.reduce((s, r) => s + this.lineTotal(r), 0); },
            get netTotal() {
                const sub = this.subtotal;
                const disc = parseFloat(this.discountAmount) || 0;
                const deliv = parseFloat(this.deliveryFee) || 0;
                return Math.max(0, sub - disc + deliv);
            },
            get valid() { return this.rows.length > 0 && this.rows.every(r => r.product_id && (parseFloat(r.quantity) || 0) > 0 && (parseFloat(r.unit_cost) || 0) >= 0); },
            handleFiles(event) {
                const files = Array.from(event.target.files || []);
                this.voucherPreviews = [];
                files.forEach((file, idx) => {
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            this.voucherPreviews.push({ name: file.name, url: e.target.result, isPdf: false });
                        };
                        reader.readAsDataURL(file);
                    } else if (file.type === 'application/pdf') {
                        this.voucherPreviews.push({ name: file.name, url: '', isPdf: true });
                    }
                });
            }
        };
    };

    window.poEditComponent = function (config) {
        const adjustment = window.initPriceAdjustmentComponent(config);
        return {
            ...adjustment,
            submitting: false,
            rows: config.initialRows || [],
            isReceived: config.isReceived || false,
            q: '',
            results: [],
            open: false,
            searching: false,
            searched: false,
            filterBrand: '',
            filterCategory: '',
            supplierId: config.supplierId || '',
            supplierName: config.supplierName || '',
            discountAmount: config.discountAmount || 0,
            deliveryFee: config.deliveryFee || 0,
            voucherPreviews: [],

            init() {
                this.initKeyboardShortcuts();
            },

            get canSearch() { return !this.isReceived && (this.q.trim() !== '' || this.filterBrand !== '' || this.filterCategory !== ''); },
            async search(open = true) {
                if (!this.canSearch) { this.results = []; this.open = false; this.searched = false; return; }
                this.searching = true;
                try {
                    const params = new URLSearchParams();
                    if (this.q.trim() !== '') params.set('q', this.q.trim());
                    if (this.filterBrand) params.set('brand_id', this.filterBrand);
                    if (this.filterCategory) params.set('category_id', this.filterCategory);
                    const res = await fetch(config.productsSearchUrl + '?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    const json = await res.json();
                    this.results = (json.results || []).slice(0, 10);
                    this.searched = true;
                    this.open = open;
                } finally {
                    this.searching = false;
                }
            },
            onFilterChange() { this.search(true); },
            enterPick() {
                if (this.open && this.results.length > 0) { this.addProduct(this.results[0]); }
            },
            addProduct(p, focusQty = true) {
                if (this.isReceived) return;
                const variantId = p.type === 'variant' ? p.id : null;
                let targetIdx = this.rows.findIndex(r => r.product_id === p.product_id && r.product_variant_id === variantId);
                if (targetIdx >= 0) {
                    this.rows[targetIdx].quantity = String((parseFloat(this.rows[targetIdx].quantity) || 0) + 1);
                } else {
                    this.rows.push({
                        product_id: p.product_id,
                        product_variant_id: variantId,
                        name: p.name,
                        sku: p.sku,
                        balance: p.balance || 0,
                        quantity: '1',
                        unit_cost: p.cost || '',
                        baseline_cost: p.cost || '0',
                        cost: p.cost || '0',
                        retail_price: p.retail_price || p.price || '0',
                        wholesale_price: p.wholesale_price || '0'
                    });
                    targetIdx = this.rows.length - 1;
                }
                this.q = '';
                this.results = [];
                this.open = false;
                this.searched = false;
                this.playBeepChime();
                if (focusQty) {
                    this.focusRowQty(targetIdx);
                } else {
                    this.focusSearch();
                }
            },
            removeRow(i) { if (!this.isReceived) this.rows.splice(i, 1); },
            incQty(r) { if (!this.isReceived) r.quantity = String((parseFloat(r.quantity) || 0) + 1); },
            decQty(r) { if (!this.isReceived) r.quantity = String(Math.max(1, (parseFloat(r.quantity) || 0) - 1)); },
            lineTotal(r) { return (parseFloat(r.quantity) || 0) * (parseFloat(r.unit_cost) || 0); },
            fmt(n) { return typeof window.formatCurrency === 'function' ? window.formatCurrency(n) : Number(n).toLocaleString(); },
            fmtQty(n) { return typeof window.formatQuantity === 'function' ? window.formatQuantity(n) : String(n); },
            get totalQty() { return this.rows.reduce((s, r) => s + (parseFloat(r.quantity) || 0), 0); },
            get subtotal() { return this.rows.reduce((s, r) => s + this.lineTotal(r), 0); },
            get netTotal() {
                const sub = this.subtotal;
                const disc = parseFloat(this.discountAmount) || 0;
                const deliv = parseFloat(this.deliveryFee) || 0;
                return Math.max(0, sub - disc + deliv);
            },
            get valid() { return !this.isReceived && this.rows.length > 0 && this.rows.every(r => r.product_id && (parseFloat(r.quantity) || 0) > 0 && (parseFloat(r.unit_cost) || 0) >= 0); },
            handleFiles(event) {
                const files = Array.from(event.target.files || []);
                this.voucherPreviews = [];
                files.forEach((file, idx) => {
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            this.voucherPreviews.push({ name: file.name, url: e.target.result, isPdf: false });
                        };
                        reader.readAsDataURL(file);
                    } else if (file.type === 'application/pdf') {
                        this.voucherPreviews.push({ name: file.name, url: '', isPdf: true });
                    }
                });
            }
        };
    };

    if (window.Alpine) {
        window.Alpine.data('poCreateComponent', window.poCreateComponent);
        window.Alpine.data('poEditComponent', window.poEditComponent);
    } else {
        document.addEventListener('alpine:init', function () {
            if (window.Alpine) {
                window.Alpine.data('poCreateComponent', window.poCreateComponent);
                window.Alpine.data('poEditComponent', window.poEditComponent);
            }
        });
    }
</script>
