@php
    // Resolve the Noto Sans Myanmar woff2 from the Vite manifest when the build
    // exists; fall back to no @font-face (system Myanmar fonts) if not.
    $myanmarFontUrl = null;
    try {
        $myanmarFontUrl = \Illuminate\Support\Facades\Vite::asset('resources/assets/fonts/NotoSansMyanmar/NotoSansMyanmar-Regular.ttf');
    } catch (\Throwable $e) {
        $myanmarFontUrl = null;
    }

    $tmpl = $voucherTemplate ?? null;
    $headerTitle = data_get($tmpl, 'header_title') ?: ($setting?->store_name ?? $store->name);
    $headerSubtitle = data_get($tmpl, 'header_subtitle');
    $address = data_get($tmpl, 'address') ?: ($setting?->address ?? $store->address ?? null);
    $phone = data_get($tmpl, 'phone') ?: ($setting?->phone ?? $store->phone ?? null);
    $tin = $store->setting?->tax_number ?: ($store->tax_number ?: null);
    $showLogo = (bool) data_get($tmpl, 'show_logo', true);
    $templateLogo = ($tmpl instanceof \App\Models\VoucherTemplate) ? $tmpl->logoUrl() : null;
    $logoUrl = $showLogo ? ($templateLogo ?? (!empty($setting?->storefrontLogo()) ? asset('storage/' . $setting->storefrontLogo()) : null)) : null;
    
    $showQr = (bool) data_get($tmpl, 'show_qr', true);
    $qrLabel = data_get($tmpl, 'qr_label') ?: 'Scan to pay with KPay / Wave / Bank';
    $templateQr = ($tmpl instanceof \App\Models\VoucherTemplate) ? $tmpl->qrUrl() : null;
    $paymentQrDataUri = $templateQr ?? ($orderQrDataUri ?? null);

    $showCustomer = (bool) data_get($tmpl, 'show_customer_info', true);
    $showCashier = (bool) data_get($tmpl, 'show_cashier_name', true);
    $showTax = (bool) data_get($tmpl, 'show_tax_breakdown', true);
    $showDiscount = (bool) data_get($tmpl, 'show_discount_line', true);
    $showBarcode = (bool) data_get($tmpl, 'show_barcode', true);

    $stylePreset = data_get($tmpl, 'style_preset') ?: 'clean_minimal';
    $fontSize = data_get($tmpl, 'font_size') ?: 'medium';
    $footerGreeting = data_get($tmpl, 'footer_greeting') ?: __('messages.thank_you_purchase');
    $footerPolicy = data_get($tmpl, 'footer_policy') ?: __('messages.invoice_terms_notice');

    $paperSize = in_array($paperSize ?? '', ['58mm', '80mm', 'a5', 'a4'], true) ? $paperSize : 'a4';
    $customerPhone = $order->customer_phone ? preg_replace('/[^0-9]/', '', (string)$order->customer_phone) : null;
    $shareUrl = route('store.admin.orders.invoice', ['store_slug' => $store->slug, 'order' => $order->id]);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ __('messages.invoice_commercial_title') }} #{{ $order->order_number }} — {{ $store->name }}</title>
    <script nonce="{{ $cspNonce }}" src="{{ asset('vendor/html2pdf/html2pdf.bundle.min.js') }}"></script>
    <style>
        /* ---- Burmese font (subset woff2 shipped with the app build) ---- */
        @if ($myanmarFontUrl)
        @font-face {
            font-family: 'Noto Sans Myanmar';
            src: url('{{ $myanmarFontUrl }}') format('woff2');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }
        @endif

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body {
            font-family: 'Noto Sans Myanmar', 'Pyidaungsu', 'Myanmar Text', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            color: #1e293b;
            background: #eef2f7;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

    </style>
    {{-- Dynamic @page style tag for live paper-size updates in print/PDF --}}
    <style id="dynamicPageStyle">
        @if ($paperSize === 'a4')
            @page { size: 210mm 297mm; margin: 0; }
        @elseif ($paperSize === 'a5')
            @page { size: 148mm 210mm; margin: 0; }
        @elseif ($paperSize === '80mm')
            @page { size: 80mm auto; margin: 0; }
        @elseif ($paperSize === '58mm')
            @page { size: 58mm auto; margin: 0; }
        @endif
    </style>
    <style>

        @media print {
            html, body {
                margin: 0 !important; padding: 0 !important; background: #fff !important; color: #000 !important;
            }
            .no-print { display: none !important; }
            .preview-stage {
                padding: 0 !important;
                margin: 0 !important;
                display: block !important;
                height: auto !important;
                overflow: visible !important;
            }
            .sheet {
                box-shadow: none !important;
                border-radius: 0 !important;
                border: none !important;
                margin: 0 auto !important;
                transform: none !important;
                page-break-inside: avoid;
                page-break-after: avoid;
            }
            .sheet.size-a4 {
                width: 210mm !important;
                min-height: 297mm !important;
                max-width: 210mm !important;
                padding: 12mm 15mm !important;
            }
            .sheet.size-a5 {
                width: 148mm !important;
                min-height: 210mm !important;
                max-width: 148mm !important;
                padding: 8mm 10mm !important;
            }
            .sheet.size-80mm {
                width: 80mm !important;
                max-width: 80mm !important;
                padding: 3mm 3mm !important;
            }
            .sheet.size-58mm {
                width: 58mm !important;
                max-width: 58mm !important;
                padding: 2mm 2mm !important;
            }
        }

        /* ---- Sticky Interactive Studio Top Navigation (Single Row Standard) ---- */
        .top-nav-bar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid rgba(51, 65, 85, 0.6);
            padding: 8px 12px;
            margin-bottom: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }
        .top-nav-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: nowrap;
            white-space: nowrap;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            padding: 2px 4px;
        }
        .top-nav-inner::-webkit-scrollbar {
            display: none;
        }
        .top-nav-left, .top-nav-right {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex-wrap: nowrap;
            flex-shrink: 0;
        }
        .top-nav-divider {
            width: 1px;
            height: 22px;
            background: rgba(255, 255, 255, 0.18);
            margin: 0 4px;
            flex-shrink: 0;
        }
        .tool-btn {
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 700;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
            flex-shrink: 0;
            transition: all 0.15s ease;
        }
        .tool-btn:active { transform: scale(0.97); }
        .tool-btn-back {
            background: #1e293b;
            color: #cbd5e1;
            border-color: #334155;
        }
        .tool-btn-back:hover {
            background: #334155;
            color: #fff;
        }
        .tool-btn-print {
            background: #7c3aed;
            color: #fff;
            box-shadow: 0 2px 6px rgba(124, 58, 237, .3);
        }
        .tool-btn-print:hover { background: #6d28d9; }
        .tool-btn-pdf {
            background: #0284c7;
            color: #fff;
        }
        .tool-btn-pdf:hover { background: #0369a1; }
        .tool-btn-share-jpg {
            background: #0d9488;
            color: #fff;
            box-shadow: 0 2px 6px rgba(13, 148, 136, .3);
        }
        .tool-btn-share-jpg:hover { background: #0f766e; }
        .tool-btn-share {
            background: #059669;
            color: #fff;
            box-shadow: 0 2px 6px rgba(5, 150, 105, .3);
        }
        .tool-btn-share:hover { background: #047857; }

        /* Compact Paper Size Selector */
        .size-select-wrapper {
            display: inline-flex;
            align-items: center;
            background: #1e293b;
            border-radius: 8px;
            padding: 2px 6px 2px 8px;
            gap: 6px;
            border: 1px solid #334155;
        }
        .size-select-label {
            font-size: 11px;
            font-weight: 700;
            color: #94a3b8;
            white-space: nowrap;
        }
        .size-select {
            background: #0f172a;
            color: #f8fafc;
            border: 1px solid #475569;
            border-radius: 6px;
            padding: 3px 8px;
            font-size: 11.5px;
            font-weight: 700;
            cursor: pointer;
            outline: none;
            transition: all 0.15s ease;
        }
        .size-select:focus {
            border-color: #7c3aed;
            box-shadow: 0 0 0 2px rgba(124, 58, 237, 0.25);
        }

        /* Toast notification */
        .print-toast {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%) translateY(20px);
            background: rgba(15, 23, 42, 0.95);
            color: #f8fafc;
            border: 1px solid rgba(255, 255, 255, 0.18);
            padding: 10px 20px;
            border-radius: 9999px;
            font-size: 12.5px;
            font-weight: 700;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.35);
            backdrop-filter: blur(8px);
            opacity: 0;
            visibility: hidden;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 9999;
            pointer-events: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .print-toast.show {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) translateY(0);
        }

        /* ---- Preview Stage with Proportional Scaling ---- */
        .preview-stage {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 16px 8px 36px 8px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* ---- Container Sizing by Exact Paper Size ---- */
        .sheet {
            margin: 0 auto;
            background: #fff;
            box-shadow: 0 8px 30px rgba(15, 23, 42, .12);
            box-sizing: border-box;
            transition: width 0.2s ease, min-height 0.2s ease;
            transform-origin: top center;
        }

        .sheet.size-a4 {
            width: 210mm;
            max-width: 210mm;
            min-height: 297mm;
            padding: 14mm 16mm;
            font-size: 12px;
            border-radius: 4px;
        }
        .sheet.size-a5 {
            width: 148mm;
            max-width: 148mm;
            min-height: 210mm;
            padding: 8mm 10mm;
            font-size: 10.5px;
            border-radius: 4px;
        }
        .sheet.size-80mm {
            width: 80mm;
            max-width: 80mm;
            min-height: 120mm;
            padding: 4mm 3mm;
            font-size: 11.5px;
            border-radius: 4px;
        }
        .sheet.size-58mm {
            width: 58mm;
            max-width: 58mm;
            min-height: 100mm;
            padding: 3mm 2mm;
            font-size: 10px;
            border-radius: 4px;
        }

        /* A5 Compact Tuning - Calibrated for exact 148mm × 210mm Sheet */
        .sheet.size-a5 .invoice-header { padding-bottom: 8px; }
        .sheet.size-a5 .invoice-brand .store-name { font-size: 15px; }
        .sheet.size-a5 .invoice-brand .logo { max-height: 40px; }
        .sheet.size-a5 .invoice-title-block h1 { font-size: 18px; }
        .sheet.size-a5 .party-grid { margin-top: 8px; padding-bottom: 8px; gap: 8px; }
        .sheet.size-a5 .party-block h3 { font-size: 8.5px; margin-bottom: 2px; }
        .sheet.size-a5 .party-block .line { font-size: 10px; line-height: 1.35; }
        .sheet.size-a5 table.items { margin-top: 8px; }
        .sheet.size-a5 thead th { padding: 4px 6px; font-size: 9px; }
        .sheet.size-a5 tbody td { padding: 4px 6px; font-size: 10px; }
        .sheet.size-a5 .item-name { font-size: 10.5px; }
        .sheet.size-a5 .item-variant { font-size: 8.5px; }
        .sheet.size-a5 .bottom-summary-grid { margin-top: 8px; gap: 10px; }
        .sheet.size-a5 .payment-qr-block { padding: 6px 8px; gap: 8px; }
        .sheet.size-a5 .payment-qr-img { width: 50px; height: 50px; }
        .sheet.size-a5 .payment-qr-title { font-size: 10px; }
        .sheet.size-a5 .payment-qr-sub { font-size: 8.5px; }
        .sheet.size-a5 .totals-row { padding: 2px 4px; font-size: 10px; }
        .sheet.size-a5 .totals-grand { padding: 5px 8px; font-size: 12px; margin-top: 2px; }
        .sheet.size-a5 .sign-stamp-block { margin-top: 10px; padding-top: 8px; gap: 8px; }
        .sheet.size-a5 .sign-col { height: 46px; }
        .sheet.size-a5 .sign-label { font-size: 8px; }
        .sheet.size-a5 .sign-person { font-size: 9px; }
        .sheet.size-a5 .stamp-box { height: 46px; width: 80px; font-size: 8px; }
        .sheet.size-a5 .invoice-foot { margin-top: 8px; padding-top: 6px; font-size: 9px; }

        /* Preset Variations */
        .sheet.preset-modern_tech {
            border-top: 5px solid #7c3aed;
        }
        .sheet.preset-classic_border {
            border: 2px solid #0f172a;
        }
        .sheet.preset-clean_minimal {
            border: 1px solid #e2e8f0;
        }

        /* Font Sizes */
        .sheet.font-small { font-size: 10.5px; }
        .sheet.font-medium { font-size: 12px; }
        .sheet.font-large { font-size: 13.5px; }

        /* ---- Header: Brand left, Invoice right ---- */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            padding-bottom: 12px;
            border-bottom: 1.5px solid #7c3aed;
        }
        .sheet.size-80mm .invoice-header,
        .sheet.size-58mm .invoice-header {
            flex-direction: column;
            text-align: center;
            align-items: center;
        }

        .invoice-brand { min-width: 0; max-width: 65%; }
        .sheet.size-80mm .invoice-brand,
        .sheet.size-58mm .invoice-brand { max-width: 100%; width: 100%; }

        .invoice-brand .logo { max-width: 140px; max-height: 52px; object-fit: contain; display: block; margin-bottom: 4px; }
        .sheet.size-80mm .invoice-brand .logo,
        .sheet.size-58mm .invoice-brand .logo { margin: 0 auto 4px auto; max-width: 110px; }

        .invoice-brand .store-name { font-size: 16px; font-weight: 900; color: #0f172a; line-height: 1.2; }
        .sheet.size-a4 .invoice-brand .store-name { font-size: 18px; }
        .sheet.size-58mm .invoice-brand .store-name { font-size: 14px; }

        .invoice-brand .store-sub { font-size: 11px; color: #64748b; margin-top: 2px; }
        .invoice-contact { margin-top: 4px; font-size: 10.5px; color: #475569; line-height: 1.6; }
        .invoice-contact .row { display: flex; gap: 4px; align-items: baseline; }
        .sheet.size-80mm .invoice-contact .row,
        .sheet.size-58mm .invoice-contact .row { justify-content: center; }
        .invoice-contact .row .k { font-weight: 700; color: #334155; flex-shrink: 0; }
        .invoice-contact .row .v { overflow-wrap: anywhere; min-width: 0; }

        .invoice-title-block { text-align: right; flex-shrink: 0; max-width: 48%; }
        .sheet.size-80mm .invoice-title-block,
        .sheet.size-58mm .invoice-title-block { text-align: center; max-width: 100%; width: 100%; margin-top: 8px; }

        .invoice-title-block h1 {
            font-size: 22px;
            font-weight: 900;
            color: #7c3aed;
            letter-spacing: 0.5px;
            line-height: 1.1;
            text-transform: uppercase;
        }
        .sheet.size-a4 .invoice-title-block h1 { font-size: 26px; }
        .sheet.size-80mm .invoice-title-block h1 { font-size: 17px; }
        .sheet.size-58mm .invoice-title-block h1 { font-size: 15px; }

        .invoice-title-block .invoice-no {
            font-size: 12px;
            font-weight: 800;
            color: #1e293b;
            margin-top: 3px;
            font-family: monospace;
        }
        .invoice-title-block .invoice-date { font-size: 10.5px; color: #64748b; margin-top: 2px; white-space: nowrap; }
        
        .tin-badge {
            display: inline-block;
            margin-top: 4px;
            padding: 2px 8px;
            border-radius: 6px;
            background: #f1f5f9;
            color: #334155;
            font-size: 10px;
            font-weight: 800;
            font-family: monospace;
            border: 1px solid #cbd5e1;
        }

        .barcode-wrap {
            margin-top: 6px;
            display: flex;
            justify-content: flex-end;
        }
        .sheet.size-80mm .barcode-wrap,
        .sheet.size-58mm .barcode-wrap { justify-content: center; }
        .barcode-wrap svg { max-width: 160px; height: auto; }

        /* ---- Billed To / Order Details Grid ---- */
        .party-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.8fr);
            gap: 16px;
            margin-top: 14px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        .sheet.size-80mm .party-grid,
        .sheet.size-58mm .party-grid {
            grid-template-columns: 1fr;
            gap: 8px;
            margin-top: 10px;
        }

        .party-block h3 {
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #94a3b8;
            margin-bottom: 4px;
            font-weight: 800;
        }
        .party-block .line { font-size: 11px; line-height: 1.5; color: #334155; overflow-wrap: anywhere; }
        .party-block .line + .line { margin-top: 2px; }
        .party-block .line .k { font-weight: 700; color: #0f172a; }

        .status-pills { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 4px; }
        .pill {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .pill.pending, .pill.pending_contact { background: #fef3c7; color: #92400e; }
        .pill.confirmed { background: #d1fae5; color: #065f46; }
        .pill.delivered { background: #dbeafe; color: #1e40af; }
        .pill.cancelled { background: #fee2e2; color: #991b1b; }
        .pill.paid { background: #ede9fe; color: #5b21b6; }
        .pill.unpaid { background: #f1f5f9; color: #475569; }

        .note-box {
            margin-top: 6px;
            font-size: 10px;
            color: #475569;
            line-height: 1.5;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 6px 8px;
            overflow-wrap: anywhere;
        }
        .note-box .k { font-weight: 700; color: #334155; }

        /* ---- Items Table ---- */
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            table-layout: fixed;
        }
        .col-no { width: 6%; }
        .col-item { width: 50%; }
        .col-qty { width: 12%; }
        .col-price { width: 16%; }
        .col-total { width: 16%; }

        .thermal-qty-calc { display: none; }

        /* Dual-mode Item Table: 5-column for Corporate (A4/A5) vs 2-column POS for Thermal (80mm/58mm) */
        .sheet.size-a4 .col-desktop,
        .sheet.size-a5 .col-desktop {
            display: table-cell !important;
        }
        .sheet.size-a4 .thermal-qty-calc,
        .sheet.size-a5 .thermal-qty-calc {
            display: none !important;
        }

        .sheet.size-80mm .col-desktop,
        .sheet.size-58mm .col-desktop {
            display: none !important;
        }

        .sheet.size-80mm .col-item,
        .sheet.size-58mm .col-item {
            width: 60% !important;
        }
        .sheet.size-80mm .col-total,
        .sheet.size-58mm .col-total {
            width: 40% !important;
        }

        .sheet.size-80mm .thermal-qty-calc,
        .sheet.size-58mm .thermal-qty-calc {
            display: block !important;
            font-size: 11px;
            font-weight: 700;
            color: #475569;
            margin-top: 3px;
            line-height: 1.3;
        }
        .sheet.size-58mm .thermal-qty-calc {
            font-size: 10px;
        }

        /* Thermal table borders & padding */
        .sheet.size-80mm table.items,
        .sheet.size-58mm table.items {
            border-top: 1px dashed #cbd5e1;
            border-bottom: 1px dashed #cbd5e1;
            margin-top: 8px;
            margin-bottom: 8px;
        }
        .sheet.size-80mm tbody td,
        .sheet.size-58mm tbody td {
            padding: 6px 4px;
        }
        .sheet.size-80mm thead th,
        .sheet.size-58mm thead th {
            padding: 6px 4px;
        }
        thead th {
            text-align: left;
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #64748b;
            padding: 6px 8px;
            border-bottom: 1.5px solid #cbd5e1;
            font-weight: 800;
        }
        thead th.num { text-align: right; }
        thead th.center { text-align: center; }

        tbody td {
            padding: 7px 8px;
            font-size: 11px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
            overflow-wrap: anywhere;
        }
        tbody td.num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        tbody td.center { text-align: center; }
        tbody tr:last-child td { border-bottom: 1.5px solid #cbd5e1; }

        .item-name { font-weight: 700; color: #0f172a; }
        .item-variant { font-size: 9.5px; color: #64748b; margin-top: 2px; }

        /* ---- Totals & QR Grid ---- */
        .bottom-summary-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 16px;
            margin-top: 14px;
            align-items: start;
        }
        .sheet.size-80mm .bottom-summary-grid,
        .sheet.size-58mm .bottom-summary-grid {
            grid-template-columns: 1fr;
            gap: 12px;
        }

        /* Payment QR Block (Left) */
        .payment-qr-block {
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            padding: 8px 12px;
            background: #fafafa;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .sheet.size-80mm .payment-qr-block,
        .sheet.size-58mm .payment-qr-block {
            flex-direction: column;
            text-align: center;
        }
        .payment-qr-img {
            width: 72px;
            height: 72px;
            object-fit: contain;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            background: #fff;
            padding: 2px;
            flex-shrink: 0;
        }
        .payment-qr-info { min-width: 0; }
        .payment-qr-title { font-size: 11px; font-weight: 800; color: #0f172a; }
        .payment-qr-sub { font-size: 9.5px; color: #64748b; margin-top: 2px; line-height: 1.4; }

        /* Totals Block (Right) */
        .totals-block {
            margin-left: auto;
            width: 100%;
            max-width: 320px;
        }
        .sheet.size-80mm .totals-block,
        .sheet.size-58mm .totals-block {
            max-width: 100%;
            margin-left: 0;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 6px;
            font-size: 11px;
            color: #475569;
        }
        .totals-row .k { font-weight: 600; }
        .totals-row .v { font-weight: 700; white-space: nowrap; font-variant-numeric: tabular-nums; }
        
        .totals-grand {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            font-size: 14px;
            font-weight: 900;
            color: #7c3aed;
            background: #f5f3ff;
            border: 1px solid #ddd6fe;
            border-radius: 8px;
            padding: 7px 10px;
            margin-top: 4px;
        }
        .totals-grand .v { white-space: nowrap; font-variant-numeric: tabular-nums; }

        /* ---- Commercial Signatures & Stamp Block (A4 / A5) ---- */
        .sign-stamp-block {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 110px;
            gap: 12px;
            margin-top: 24px;
            padding-top: 14px;
            border-top: 1px solid #e2e8f0;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .sheet.size-80mm .sign-stamp-block,
        .sheet.size-58mm .sign-stamp-block {
            display: none !important;
        }

        .sign-col {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 75px;
            text-align: center;
        }
        .sign-line {
            border-bottom: 1px dashed #94a3b8;
            margin-top: auto;
            margin-bottom: 4px;
        }
        .sign-label {
            font-size: 10px;
            font-weight: 700;
            color: #475569;
        }
        .sign-person {
            font-size: 9px;
            color: #64748b;
        }

        .stamp-box {
            border: 1.5px dashed #cbd5e1;
            border-radius: 6px;
            height: 75px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 4px;
            color: #94a3b8;
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
            line-height: 1.3;
        }

        /* ---- Footer ---- */
        .invoice-foot {
            margin-top: 18px;
            padding-top: 10px;
            border-top: 1px dashed #cbd5e1;
            font-size: 10px;
            color: #64748b;
            line-height: 1.6;
            text-align: center;
            page-break-inside: avoid;
            break-inside: avoid;
            overflow-wrap: anywhere;
        }

        /* ── Social Share Modal ── */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 200;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .modal-backdrop.show { display: flex; }
        .share-modal-card {
            background: #fff;
            color: #0f172a;
            border-radius: 16px;
            width: 100%;
            max-width: 440px;
            padding: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
            animation: modalPop 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes modalPop {
            0% { opacity: 0; transform: scale(0.94); }
            100% { opacity: 1; transform: scale(1); }
        }
        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        .modal-header h3 { font-size: 14px; font-weight: 800; color: #0f172a; }
        .modal-close-btn {
            background: none;
            border: none;
            font-size: 18px;
            color: #94a3b8;
            cursor: pointer;
            line-height: 1;
        }
        .modal-close-btn:hover { color: #0f172a; }

        .share-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 14px;
        }
        .share-option-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            color: #fff;
            transition: all 0.15s ease;
        }
        .share-option-btn:hover { transform: translateY(-1px); opacity: 0.95; }
        .share-viber { background: #7360f2; }
        .share-telegram { background: #229ed9; }
        .share-whatsapp { background: #25d366; }
        .share-copy { background: #475569; }

        .share-url-input-group {
            display: flex;
            gap: 6px;
            margin-top: 10px;
        }
        .share-url-input {
            flex: 1;
            padding: 7px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 11px;
            background: #f8fafc;
            color: #334155;
            outline: none;
        }
        .share-copy-btn {
            padding: 7px 14px;
            background: #7c3aed;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 11.5px;
            font-weight: 700;
            cursor: pointer;
        }
        .share-copy-btn:hover { background: #6d28d9; }
    </style>
</head>
<body>
    {{-- Sticky Interactive Studio Top Navigation (Single Row Standard) --}}
    <div class="top-nav-bar no-print">
        <div class="top-nav-inner">
            <div class="top-nav-left">
                <a class="tool-btn tool-btn-back" href="{{ url('/store/' . $store->slug . '/admin/orders/' . $order->id) }}">
                    ← <span>{{ __('messages.invoice_back_to_order') }}</span>
                </a>

                {{-- Compact Paper Size Dropdown --}}
                <div class="size-select-wrapper">
                    <label for="paperSizeSelect" class="size-select-label">📄 {{ __('messages.paper_size') }}:</label>
                    <select class="size-select" id="paperSizeSelect">
                        <option value="58mm" {{ $paperSize === '58mm' ? 'selected' : '' }}>58mm (POS)</option>
                        <option value="80mm" {{ $paperSize === '80mm' ? 'selected' : '' }}>80mm (POS)</option>
                        <option value="a5" {{ $paperSize === 'a5' ? 'selected' : '' }}>A5 (Half)</option>
                        <option value="a4" {{ $paperSize === 'a4' ? 'selected' : '' }}>A4 (Full)</option>
                    </select>
                </div>
            </div>

            <div class="top-nav-divider"></div>

            <div class="top-nav-right">
                <button type="button" class="tool-btn tool-btn-print" id="btnPrint" data-print>
                    🖨️ <span>{{ __('messages.invoice_print') }}</span>
                </button>
                <button type="button" class="tool-btn tool-btn-pdf" id="btnDownloadPdf">
                    📥 <span>{{ __('messages.vouchers_save_pdf') }}</span>
                </button>
                <button type="button" class="tool-btn tool-btn-share-jpg" id="btnShareJpg" title="{{ __('messages.vouchers_copy_jpg') }}">
                    🖼️ <span>{{ __('messages.vouchers_copy_jpg') }}</span>
                </button>
                <button type="button" class="tool-btn tool-btn-share" id="btnOpenShareModal">
                    📲 <span>{{ __('messages.vouchers_share_jpg') }}</span>
                </button>
            </div>
        </div>
    </div>

    {{-- The Document Sheet Container inside Proportional Preview Stage --}}
    <div class="preview-stage" id="previewStage">
        <div class="sheet size-{{ $paperSize }} preset-{{ $stylePreset }} font-{{ $fontSize }}" id="invoiceSheet">
        
        {{-- Invoice Header --}}
        <div class="invoice-header">
            <div class="invoice-brand">
                @if ($logoUrl)
                    <img class="logo" src="{{ $logoUrl }}" alt="{{ $store->name }}">
                @else
                    <div class="store-name">{{ $headerTitle }}</div>
                @endif
                @if ($headerSubtitle)
                    <div class="store-sub">{{ $headerSubtitle }}</div>
                @endif

                <div class="invoice-contact">
                    @if ($address)
                        <div class="row"><span class="k">{{ __('messages.invoice_address') }}</span><span class="v">{{ $address }}</span></div>
                    @endif
                    @if ($phone)
                        <div class="row"><span class="k">{{ __('messages.invoice_phone') }}</span><span class="v">{{ $phone }}</span></div>
                    @endif
                    @if ($setting?->viber_number)
                        <div class="row"><span class="k">{{ __('messages.invoice_viber') }}</span><span class="v">{{ $setting->viber_number }}</span></div>
                    @endif
                    @if ($setting?->telegram_username)
                        <div class="row"><span class="k">{{ __('messages.invoice_telegram') }}</span><span class="v">{{ $setting->telegram_username }}</span></div>
                    @endif
                </div>
            </div>

            <div class="invoice-title-block">
                <h1>{{ __('messages.invoice_commercial_title') }}</h1>
                <div class="invoice-no">#{{ $order->order_number }}</div>
                <div class="invoice-date">{{ $order->created_at->format('F j, Y · h:i A') }}</div>

                @if ($tin)
                    <div><span class="tin-badge">{{ __('messages.invoice_tin') }}: {{ $tin }}</span></div>
                @endif

                @if ($showBarcode && !empty($barcodeSvg))
                    <div class="barcode-wrap">
                        {!! $barcodeSvg !!}
                    </div>
                @endif
            </div>
        </div>

        {{-- Billed To & Status Grid --}}
        <div class="party-grid">
            <div class="party-block">
                <h3>{{ __('messages.invoice_billed_to') }}</h3>
                @if ($order->customer_name)
                    <div class="line"><span class="k">{{ $order->customer_name }}</span></div>
                @else
                    <div class="line"><span class="k">{{ __('messages.walk_in_customer') }}</span></div>
                @endif
                @if ($order->customer_phone)
                    <div class="line"><span class="k">{{ __('messages.invoice_phone') }}</span> {{ $order->customer_phone }}</div>
                @endif
                @if ($order->customer_address)
                    <div class="line"><span class="k">{{ __('messages.invoice_address') }}</span> {{ $order->customer_address }}</div>
                @endif
                @if ($order->contact_channel && $order->contact_identifier && $order->contact_identifier !== $order->customer_phone)
                    <div class="line"><span class="k">{{ ucfirst(str_replace('_', ' ', $order->contact_channel)) }}:</span> {{ $order->contact_identifier }}</div>
                @endif
            </div>

            <div class="party-block">
                <h3>{{ __('messages.invoice_status') }}</h3>
                <div class="status-pills">
                    <span class="pill {{ $order->status }}">{{ str_replace('_', ' ', $order->status) }}</span>
                    <span class="pill {{ $order->payment_status }}">{{ __('messages.invoice_payment') }} {{ $order->payment_status }}</span>
                </div>
                @if ($order->customer_note)
                    <div class="note-box"><span class="k">{{ __('messages.invoice_customer_note') }}</span> {{ $order->customer_note }}</div>
                @endif
            </div>
        </div>

        {{-- Itemized Table (Responsive Dual-mode) --}}
        <table class="items">
            <thead>
                <tr>
                    <th class="col-desktop col-no">#</th>
                    <th class="col-item">{{ __('messages.invoice_col_item') }}</th>
                    <th class="col-desktop col-qty center">{{ __('messages.invoice_col_qty') }}</th>
                    <th class="col-desktop col-price num">{{ __('messages.invoice_col_unit_price') }}</th>
                    <th class="col-total num">{{ __('messages.invoice_col_amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $i => $item)
                    <tr>
                        <td class="col-desktop col-no">{{ $i + 1 }}</td>
                        <td class="col-item">
                            <div class="item-name">{{ $item->product_name }}</div>
                            @if ($item->variant_name && !str_contains((string) $item->product_name, (string) $item->variant_name))
                                <div class="item-variant">{{ $item->variant_name }}{{ $item->variant_sku ? ' · ' . $item->variant_sku : '' }}</div>
                            @elseif ($item->variant_sku)
                                <div class="item-variant">{{ $item->variant_sku }}</div>
                            @endif

                            {{-- Thermal Slip Qty x Price Subline (Shown on 80mm & 58mm, hidden on A4/A5) --}}
                            <div class="thermal-qty-calc">
                                {{ format_quantity((float) $item->quantity, $store) }} × {{ format_currency((float) $item->unit_price, $store) }}
                            </div>

                            @if ($item->product && $item->product->product_type === 'service' && trim((string) $item->product->service_duration))
                                <div class="item-variant" style="color:#b45309;">⏱️ {{ __('messages.product_form_service_duration') }}: {{ $item->product->service_duration }}</div>
                            @endif
                            @if ($item->product && $item->product->product_type === 'digital' && trim((string) $item->product->digital_delivery_method))
                                <div class="item-variant" style="color:#0369a1;">📲 {{ __('messages.product_form_digital_delivery_method') }}: {{ $item->product->digital_delivery_method }}</div>
                            @endif
                        </td>
                        <td class="col-desktop col-qty center">{{ format_quantity((float) $item->quantity, $store) }}</td>
                        <td class="col-desktop col-price num">{{ format_currency((float) $item->unit_price, $store) }}</td>
                        <td class="col-total num">{{ format_currency((float) $item->subtotal, $store) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals Breakdown & Payment QR Section --}}
        <div class="bottom-summary-grid">
            {{-- Left: Payment QR code block --}}
            <div>
                @if ($showQr && !empty($paymentQrDataUri))
                    <div class="payment-qr-block">
                        <img src="{{ $paymentQrDataUri }}" alt="Payment QR" class="payment-qr-img" />
                        <div class="payment-qr-info">
                            <div class="payment-qr-title">{{ $qrLabel }}</div>
                            <div class="payment-qr-sub">{{ __('messages.receipt_scan_to_pay') }}</div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Right: Totals block --}}
            <div class="totals-block">
                @if ($order->agreed_amount !== null)
                    <div class="totals-row">
                        <span class="k">{{ __('messages.invoice_original_total') }}</span>
                        <span class="v">{{ format_currency((float) $order->total_amount, $store) }}</span>
                    </div>
                    <div class="totals-row">
                        <span class="k">{{ __('messages.invoice_agreed_amount') }}</span>
                        <span class="v">{{ format_currency((float) $order->agreed_amount, $store) }}</span>
                    </div>
                    <div class="totals-grand">
                        <span>{{ __('messages.invoice_total_due') }}</span>
                        <span class="v">{{ format_currency((float) $order->agreed_amount, $store) }}</span>
                    </div>
                @else
                    @php
                        $orderTax = (float) ($order->tax ?? $order->tax_amount ?? 0);
                        $orderSubtotal = (float) ($order->taxable_amount ?? $order->subtotal ?? $order->items->sum('subtotal'));
                        $taxPct = ($orderSubtotal > 0 && $orderTax > 0) ? round(($orderTax / $orderSubtotal) * 100) : null;
                    @endphp
                    @if ($showTax && $orderTax > 0)
                        <div class="totals-row">
                            <span class="k">{{ __('messages.subtotal') }}</span>
                            <span class="v">{{ format_currency($orderSubtotal, $store) }}</span>
                        </div>
                        <div class="totals-row">
                            <span class="k">{{ __('messages.commercial_tax') }} {{ $taxPct ? '(' . $taxPct . '%)' : '' }}</span>
                            <span class="v">+ {{ format_currency($orderTax, $store) }}</span>
                        </div>
                    @endif
                    @if ((float) ($order->shipping_fee ?? 0) > 0)
                        <div class="totals-row">
                            <span class="k">{{ __('messages.shipping_fee') ?? 'Shipping' }}</span>
                            <span class="v">+ {{ format_currency((float) $order->shipping_fee, $store) }}</span>
                        </div>
                    @endif
                    @if ($showDiscount && (float) ($order->discount_amount ?? 0) > 0)
                        <div class="totals-row" style="color:#dc2626;">
                            <span class="k">{{ __('messages.discount') }}</span>
                            <span class="v">− {{ format_currency((float) $order->discount_amount, $store) }}</span>
                        </div>
                    @endif
                    <div class="totals-grand">
                        <span>{{ __('messages.invoice_total_due') }}</span>
                        <span class="v">{{ format_currency((float) $order->total_amount, $store) }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Official Commercial Signatures & Stamp Block (Visible on A4/A5, CSS-hidden on 80mm/58mm) --}}
        <div class="sign-stamp-block">
            <div class="sign-col">
                <div class="sign-line"></div>
                <div class="sign-label">{{ __('messages.invoice_prepared_by') }}</div>
                <div class="sign-person">{{ $order->user?->name ?? 'Sales Staff' }}</div>
            </div>
            <div class="sign-col">
                <div class="sign-line"></div>
                <div class="sign-label">{{ __('messages.invoice_checked_by') }}</div>
                <div class="sign-person">Manager</div>
            </div>
            <div class="sign-col">
                <div class="sign-line"></div>
                <div class="sign-label">{{ __('messages.invoice_received_by') }}</div>
                <div class="sign-person">{{ $order->customer_name ?: 'Customer' }}</div>
            </div>
            <div class="stamp-box">
                <span>{{ __('messages.invoice_company_stamp') }}</span>
            </div>
        </div>

        {{-- Footer --}}
        <div class="invoice-foot">
            <p style="font-weight:700;">{{ $footerGreeting }}</p>
            @if ($footerPolicy)
                <p style="font-size:10px;color:#64748b;margin-top:3px;">{{ $footerPolicy }}</p>
            @endif
            @if ($phone || $setting?->telegram_username || $address)
                <p style="margin-top:3px;font-size:9.5px;color:#94a3b8;">
                    @if ($address) 📍 {{ $address }} · @endif
                    @if ($phone) 📞 {{ $phone }} @endif
                    @if ($setting?->telegram_username) · ✈️ t.me/{{ ltrim($setting->telegram_username, '@') }} @endif
                </p>
            @endif
        </div>
    </div>
    </div>

    {{-- Social Share Modal --}}
    <div class="modal-backdrop no-print" id="shareModal">
        <div class="share-modal-card">
            <div class="modal-header">
                <h3>📲 {{ __('messages.vouchers_share_jpg') }} — #{{ $order->order_number }}</h3>
                <button type="button" class="modal-close-btn" id="btnCloseShareModal">✕</button>
            </div>
            
            <p style="font-size:11.5px;color:#64748b;margin-bottom:12px;">
                {{ __('messages.vouchers_jpg_copied') }}
            </p>

            <div class="share-grid">
                @php
                    $shareText = rawurlencode($headerTitle . ' — ' . __('messages.invoice_commercial_title') . ' #' . $order->order_number . "\n" . __('messages.invoice_total_due') . ': ' . format_currency((float)$order->total_amount, $store) . "\n" . $shareUrl);
                @endphp
                <a href="viber://forward?text={{ $shareText }}" class="share-option-btn share-viber" target="_blank">
                    <span>🟣 Viber</span>
                </a>
                <a href="https://t.me/share/url?url={{ rawurlencode($shareUrl) }}&text={{ $shareText }}" class="share-option-btn share-telegram" target="_blank">
                    <span>✈️ Telegram</span>
                </a>
                <a href="https://api.whatsapp.com/send?text={{ $shareText }}" class="share-option-btn share-whatsapp" target="_blank">
                    <span>🟢 WhatsApp</span>
                </a>
                <button type="button" class="share-option-btn share-copy" id="btnCopyLink">
                    <span>📋 Copy Link</span>
                </button>
            </div>

            <div class="share-url-input-group">
                <input type="text" class="share-url-input" id="shareUrlInput" value="{{ $shareUrl }}" readonly />
                <button type="button" class="share-copy-btn" id="btnModalCopyLink">{{ __('messages.vouchers_copy_jpg') }}</button>
            </div>
        </div>
    </div>

    {{-- Toast Notification Box --}}
    <div id="printToast" class="print-toast no-print"></div>

    <script nonce="{{ $cspNonce }}">
        var paperSize = '{{ $paperSize }}';
        var pdfConfig = {
            margin: [0, 0, 0, 0],
            filename: 'Invoice_{{ $order->order_number }}_{{ $paperSize }}.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff', logging: false },
            jsPDF: {
                unit: 'mm',
                format: paperSize === 'a4' ? [210, 297] : (paperSize === 'a5' ? [148, 210] : [paperSize === '80mm' ? 80 : 58, 160]),
                orientation: 'portrait'
            }
        };

        function showToast(message) {
            var toast = document.getElementById('printToast');
            if (toast) {
                toast.innerHTML = '📋 ' + message;
                toast.classList.add('show');
                clearTimeout(window._toastTimer);
                window._toastTimer = setTimeout(function() {
                    toast.classList.remove('show');
                }, 3500);
            }
        }

        function downloadBlob(blob, fileName) {
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = fileName;
            document.body.appendChild(a);
            a.click();
            setTimeout(function() {
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }, 100);
        }

        function updatePreviewScale() {
            var stage = document.getElementById('previewStage');
            var sheet = document.getElementById('invoiceSheet');
            if (!stage || !sheet) return;

            sheet.style.transform = 'none';
            stage.style.height = 'auto';

            var stageWidth = stage.clientWidth - 20;
            var sheetWidth = sheet.offsetWidth;

            if (stageWidth > 0 && sheetWidth > 0 && stageWidth < sheetWidth) {
                var scale = stageWidth / sheetWidth;
                sheet.style.transform = 'scale(' + scale.toFixed(4) + ')';
                sheet.style.transformOrigin = 'top center';
                var scaledHeight = sheet.offsetHeight * scale;
                stage.style.height = (scaledHeight + 36) + 'px';
            }
        }

        function setPaperSize(size) {
            if (!['a4', 'a5', '80mm', '58mm'].includes(size)) return;
            paperSize = size;

            var sheet = document.getElementById('invoiceSheet');
            if (sheet) {
                sheet.className = 'sheet size-' + size + ' preset-{{ $stylePreset }} font-{{ $fontSize }}';
            }

            // Sync select dropdown
            var select = document.getElementById('paperSizeSelect');
            if (select && select.value !== size) {
                select.value = size;
            }

            // Update dynamic @page CSS and PDF format (Exact Dimensions: A4: 210x297mm, A5: 148x210mm)
            var pageStyle = document.getElementById('dynamicPageStyle');
            if (pageStyle) {
                if (size === '58mm') {
                    pageStyle.innerHTML = '@page { size: 58mm auto; margin: 0; }';
                    pdfConfig.jsPDF = { unit: 'mm', format: [58, 120], orientation: 'portrait' };
                    pdfConfig.margin = [0, 0, 0, 0];
                } else if (size === '80mm') {
                    pageStyle.innerHTML = '@page { size: 80mm auto; margin: 0; }';
                    pdfConfig.jsPDF = { unit: 'mm', format: [80, 160], orientation: 'portrait' };
                    pdfConfig.margin = [0, 0, 0, 0];
                } else if (size === 'a5') {
                    pageStyle.innerHTML = '@page { size: 148mm 210mm; margin: 0; }';
                    pdfConfig.jsPDF = { unit: 'mm', format: [148, 210], orientation: 'portrait' };
                    pdfConfig.margin = [0, 0, 0, 0];
                } else if (size === 'a4') {
                    pageStyle.innerHTML = '@page { size: 210mm 297mm; margin: 0; }';
                    pdfConfig.jsPDF = { unit: 'mm', format: [210, 297], orientation: 'portrait' };
                    pdfConfig.margin = [0, 0, 0, 0];
                }
            }
            pdfConfig.filename = 'Invoice_{{ $order->order_number }}_' + size + '.pdf';

            // Sync URL parameter silently so reloads remember the chosen size
            try {
                var url = new URL(window.location.href);
                url.searchParams.set('paper_size', size);
                window.history.replaceState({}, '', url.toString());
            } catch (e) {}

            updatePreviewScale();
            showToast('{{ __("messages.paper_size") }}: ' + size.toUpperCase());
        }

        function downloadPdf() {
            var sheet = document.getElementById('invoiceSheet');
            var stage = document.getElementById('previewStage');
            var btn = document.getElementById('btnDownloadPdf');
            var originalText = btn ? btn.innerHTML : '';
            if (btn) { btn.disabled = true; btn.innerHTML = '⏳ Generating...'; }

            // Temporarily reset transform for unscaled PDF generation
            if (sheet) sheet.style.transform = 'none';
            if (stage) stage.style.height = 'auto';

            // Dynamic height calculation for thermal sizes
            if (sheet && (paperSize === '80mm' || paperSize === '58mm')) {
                var pxHeight = sheet.offsetHeight;
                var mmHeight = Math.ceil(pxHeight * 0.264583) + 8;
                var widthMm = paperSize === '80mm' ? 80 : 58;
                pdfConfig.jsPDF = { unit: 'mm', format: [widthMm, Math.max(mmHeight, 80)], orientation: 'portrait' };
            } else if (paperSize === 'a5') {
                pdfConfig.jsPDF = { unit: 'mm', format: [148, 210], orientation: 'portrait' };
            } else if (paperSize === 'a4') {
                pdfConfig.jsPDF = { unit: 'mm', format: [210, 297], orientation: 'portrait' };
            }

            if (window.html2pdf && sheet) {
                html2pdf().set(pdfConfig).from(sheet).save().then(function() {
                    if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
                    updatePreviewScale();
                }).catch(function(err) {
                    console.error('PDF error:', err);
                    if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
                    updatePreviewScale();
                    window.print();
                });
            } else {
                if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
                updatePreviewScale();
                window.print();
            }
        }

        function dataUriToBlob(dataUri) {
            var parts = dataUri.split(',');
            var byteString = atob(parts[1]);
            var mimeString = parts[0].split(':')[1].split(';')[0];
            var ab = new ArrayBuffer(byteString.length);
            var ia = new Uint8Array(ab);
            for (var i = 0; i < byteString.length; i++) {
                ia[i] = byteString.charCodeAt(i);
            }
            return new Blob([ab], { type: mimeString });
        }

        async function shareJpgDirectly() {
            var sheet = document.getElementById('invoiceSheet');
            var stage = document.getElementById('previewStage');
            var btn = document.getElementById('btnShareJpg');
            var originalText = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '⏳ <span>Generating...</span>';
            }

            // Temporarily reset transform for unscaled crisp capture
            if (sheet) sheet.style.transform = 'none';
            if (stage) stage.style.height = 'auto';

            try {
                if (!window.html2pdf || !sheet) {
                    window.print();
                    return;
                }

                var worker = html2pdf().set({
                    ...pdfConfig,
                    margin: 0,
                    image: { type: 'png', quality: 1.0 },
                    html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff', logging: false }
                }).from(sheet);

                await worker.toContainer();
                var exactWidthPx = sheet.offsetWidth;
                if (worker.prop && worker.prop.container) {
                    worker.prop.container.style.width = exactWidthPx + 'px';
                    if (worker.prop.container.firstChild) {
                        worker.prop.container.firstChild.style.width = exactWidthPx + 'px';
                    }
                }
                await worker.toCanvas();
                await worker.toImg();

                var dataUri = await worker.outputImg('datauristring');
                var blob = dataUriToBlob(dataUri);
                var imageFilename = pdfConfig.filename.replace(/\.pdf$/i, '.png');
                var file = new File([blob], imageFilename, { type: 'image/png' });

                var copied = false;
                if (navigator.clipboard && navigator.clipboard.write) {
                    try {
                        await navigator.clipboard.write([
                            new ClipboardItem({ 'image/png': blob })
                        ]);
                        copied = true;
                        showToast("{{ __('messages.vouchers_jpg_copied') }}");
                    } catch (clipErr) {
                        console.warn('Clipboard image write failed:', clipErr);
                        copied = false;
                    }
                }

                if (!copied) {
                    var isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
                    if (isMobile && navigator.canShare && navigator.canShare({ files: [file] })) {
                        try {
                            await navigator.share({
                                title: 'Invoice #{{ $order->order_number }}',
                                text: '{{ $headerTitle }} - Invoice #{{ $order->order_number }}',
                                files: [file]
                            });
                            return;
                        } catch (shareErr) {
                            if (shareErr.name === 'AbortError') return;
                        }
                    }

                    downloadBlob(blob, imageFilename);
                    showToast("{{ __('messages.vouchers_jpg_copied') }}");
                }
            } catch (err) {
                if (err.name !== 'AbortError') {
                    console.error('Share JPG error:', err);
                    showToast('Failed to generate image');
                }
            } finally {
                updatePreviewScale();
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
        }

        function openShareModal() {
            var modal = document.getElementById('shareModal');
            if (modal) modal.classList.add('show');
        }

        function closeShareModal() {
            var modal = document.getElementById('shareModal');
            if (modal) modal.classList.remove('show');
        }

        function copyInvoiceLink() {
            var input = document.getElementById('shareUrlInput');
            if (input) {
                input.select();
                navigator.clipboard.writeText(input.value).then(function() {
                    showToast("{{ __('messages.vouchers_copied') }}");
                    closeShareModal();
                }).catch(function() {
                    document.execCommand('copy');
                    showToast("{{ __('messages.vouchers_copied') }}");
                    closeShareModal();
                });
            }
        }

        window.setPaperSize = setPaperSize;
        window.downloadPdf = downloadPdf;
        window.shareJpgDirectly = shareJpgDirectly;
        window.showToast = showToast;
        window.downloadBlob = downloadBlob;
        window.openShareModal = openShareModal;
        window.closeShareModal = closeShareModal;
        window.copyInvoiceLink = copyInvoiceLink;

        document.addEventListener('DOMContentLoaded', function() {
            // Paper Size Live Pills
            document.querySelectorAll('.size-pill').forEach(function(pill) {
                pill.addEventListener('click', function(e) {
                    e.preventDefault();
                    var sz = this.getAttribute('data-paper-size');
                    if (sz) setPaperSize(sz);
                });
            });

            // Paper Size Select Dropdown
            var sizeSelect = document.getElementById('paperSizeSelect');
            if (sizeSelect) {
                sizeSelect.addEventListener('change', function() {
                    setPaperSize(this.value);
                });
            }

            // Print Button
            var printBtn = document.getElementById('btnPrint');
            if (printBtn) {
                printBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.print();
                });
            }

            // PDF Button
            var pdfBtn = document.getElementById('btnDownloadPdf');
            if (pdfBtn) {
                pdfBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    downloadPdf();
                });
            }

            // Copy JPG Button
            var shareJpgBtn = document.getElementById('btnShareJpg');
            if (shareJpgBtn) {
                shareJpgBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    shareJpgDirectly();
                });
            }

            // Open Share Modal Button
            var openShareBtn = document.getElementById('btnOpenShareModal');
            if (openShareBtn) {
                openShareBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    openShareModal();
                });
            }

            // Close Share Modal Button
            var closeShareBtn = document.getElementById('btnCloseShareModal');
            if (closeShareBtn) {
                closeShareBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    closeShareModal();
                });
            }

            // Backdrop click closes modal
            var shareModal = document.getElementById('shareModal');
            if (shareModal) {
                shareModal.addEventListener('click', function(e) {
                    if (e.target === shareModal) {
                        closeShareModal();
                    }
                });
            }

            // Copy link buttons inside modal
            var copyLinkBtn = document.getElementById('btnCopyLink');
            if (copyLinkBtn) {
                copyLinkBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    copyInvoiceLink();
                });
            }
            var modalCopyBtn = document.getElementById('btnModalCopyLink');
            if (modalCopyBtn) {
                modalCopyBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    copyInvoiceLink();
                });
            }

            // Delegated click support for any data-print or data-paper-size elements
            document.addEventListener('click', function(e) {
                var printEl = e.target.closest('[data-print]');
                if (printEl) {
                    e.preventDefault();
                    window.print();
                }
            }, true);

            // Initialize responsive preview scale and window resize listener
            updatePreviewScale();
            window.addEventListener('resize', updatePreviewScale);

            window.addEventListener('beforeprint', function() {
                var sheet = document.getElementById('invoiceSheet');
                if (sheet) sheet.style.transform = 'none';
            });
            window.addEventListener('afterprint', function() {
                updatePreviewScale();
            });
        });
    </script>
</body>
</html>
