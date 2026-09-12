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
    $paperSize = strtolower((string) request('paper_size', data_get($tmpl, 'paper_size', '80mm')));
    if (!in_array($paperSize, ['58mm', '80mm', 'a5', 'a4'], true)) {
        $paperSize = '80mm';
    }
    $fontSize = data_get($tmpl, 'font_size', 'medium');
    $is58 = $paperSize === '58mm';
    $is80 = $paperSize === '80mm';
    $isA5 = $paperSize === 'a5';
    $isA4 = $paperSize === 'a4';
    $showLogo = (bool) data_get($tmpl, 'show_logo', true);
    $showQr = (bool) data_get($tmpl, 'show_qr', false);
    $showBarcode = (bool) data_get($tmpl, 'show_barcode', false);
    $showCustomer = (bool) data_get($tmpl, 'show_customer_info', true);
    $showCashier = (bool) data_get($tmpl, 'show_cashier_name', true);
    $showTaxBreakdown = (bool) data_get($tmpl, 'show_tax_breakdown', true);
    $showDiscountLine = (bool) data_get($tmpl, 'show_discount_line', true);
    $stylePreset = data_get($tmpl, 'style_preset', 'clean_minimal');
    $headerTitle = data_get($tmpl, 'header_title') ?: $store->name;
    $headerSubtitle = data_get($tmpl, 'header_subtitle');
    $address = data_get($tmpl, 'address') ?: ($store->address ?? null);
    $phone = data_get($tmpl, 'phone') ?: ($store->viber_number ? 'Viber: ' . $store->viber_number : ($store->phone ?? null));
    $footerGreeting = data_get($tmpl, 'footer_greeting') ?: __('messages.thank_you_purchase');
    $footerPolicy = data_get($tmpl, 'footer_policy');
    $qrLabel = data_get($tmpl, 'qr_label') ?: 'Scan to pay with KPay / Wave';

    $logoUrl = null;
    if ($showLogo) {
        $templateLogo = ($tmpl instanceof \App\Models\VoucherTemplate) ? $tmpl->logoUrl() : null;
        $logoUrl = $templateLogo ?? ($store->setting?->adminLogo() ? asset('storage/' . $store->setting->adminLogo()) : null);
    }

    $templateQr = ($tmpl instanceof \App\Models\VoucherTemplate) ? $tmpl->qrUrl() : null;
    $customerName = $sale->customer?->name ?? 'Walk-in Customer';
    $customerPhone = $customerPhone ?? ($sale->customer?->phone ? preg_replace('/[^0-9]/', '', (string)$sale->customer->phone) : null);
    $storePhone = $store->phone ? preg_replace('/[^0-9]/', '', (string)$store->phone) : null;
    $receiptUrl = route('pos.receipt', ['store_slug' => $store->slug, 'sale' => $sale->id]);

    $receiptQrDataUri = null;
    $isPaymentQr = false;
    if ($showQr) {
        if ($templateQr) {
            $receiptQrDataUri = $templateQr;
            $isPaymentQr = true;
        } else {
            try {
                $receiptQrDataUri = \App\Services\QrCodeEncoder::generatePngDataUri($receiptUrl, 4);
            } catch (\Throwable $e) {
                $receiptQrDataUri = null;
            }
        }
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>Receipt_{{ $sale->receipt_number }} — {{ $store->name }}</title>
    <style>
        @if ($myanmarFontUrl)
        @font-face {
            font-family: 'Noto Sans Myanmar';
            src: url('{{ $myanmarFontUrl }}') format('woff2');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }
        @endif
    </style>
    <style id="dynamicPageStyle">
        @if ($is58)
            @page { size: 58mm auto; margin: 0; }
        @elseif ($is80)
            @page { size: 80mm auto; margin: 0; }
        @elseif ($isA5)
            @page { size: 148mm 210mm; margin: 0; }
        @elseif ($isA4)
            @page { size: 210mm 297mm; margin: 0; }
        @else
            @page { size: 80mm auto; margin: 0; }
        @endif
    </style>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Noto Sans Myanmar', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #0f172a;
            background: #f1f5f9;
            font-size: 12.5px;
            line-height: 1.4;
            padding: 16px;
        }

        /* ---- Top Sticky Navigation Bar ---- */
        .top-nav-bar {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid rgba(51, 65, 85, 0.6);
            padding: 10px 16px;
            margin: -16px -16px 16px -16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.12);
        }
        .top-nav-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            flex-wrap: nowrap;
            white-space: nowrap;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            padding: 2px 8px;
        }
        .top-nav-inner::-webkit-scrollbar {
            display: none;
        }
        .top-nav-left, .top-nav-right {
            display: inline-flex;
            align-items: center;
            gap: 8px;
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
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
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
            background: #0284c7;
            color: #fff;
            box-shadow: 0 2px 6px rgba(2, 132, 199, .3);
        }
        .tool-btn-print:hover {
            background: #0369a1;
        }
        .tool-btn-pdf {
            background: #475569;
            color: #fff;
        }
        .tool-btn-pdf:hover {
            background: #334155;
        }
        .tool-btn-share-jpg {
            background: #0d9488;
            color: #fff;
            box-shadow: 0 2px 6px rgba(13, 148, 136, .3);
        }
        .tool-btn-share-jpg:hover {
            background: #0f766e;
        }
        .tool-btn-share {
            background: #059669;
            color: #fff;
            box-shadow: 0 2px 6px rgba(5, 150, 105, .3);
        }
        .tool-btn-share:hover {
            background: #047857;
        }

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
            border-color: #0284c7;
            box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.25);
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

        /* ---- Thermal & Document Receipt Container ---- */
        .receipt {
            margin: 0 auto;
            background: #fff;
            box-shadow: 0 4px 24px rgba(2, 6, 23, .10);
            box-sizing: border-box;
            transition: width 0.2s ease, padding 0.2s ease;
            transform-origin: top center;
        }

        .receipt.size-58mm {
            width: 58mm;
            max-width: 58mm;
            min-height: 100mm;
            padding: 3mm 2mm;
            font-size: 10.5px;
            border-radius: 4px;
        }
        .receipt.size-80mm {
            width: 80mm;
            max-width: 80mm;
            min-height: 120mm;
            padding: 4mm 3mm;
            font-size: 12px;
            border-radius: 4px;
        }
        .receipt.size-a5 {
            width: 148mm;
            max-width: 148mm;
            min-height: 210mm;
            padding: 8mm 10mm;
            font-size: 11px;
            border-radius: 4px;
        }
        .receipt.size-a4 {
            width: 210mm;
            max-width: 210mm;
            min-height: 297mm;
            padding: 14mm 16mm;
            font-size: 13px;
            border-radius: 4px;
        }
        .receipt.preset-classic_border {
            border: 2px solid #0f172a;
        }
        .receipt.preset-modern_tech {
            border-top: 4px solid #0284c7;
        }
        .receipt.font-small { font-size: 11px; }
        .receipt.font-medium { font-size: 12.5px; }
        .receipt.font-large { font-size: 14px; }

        @media print {
            body { background: #fff !important; padding: 0 !important; color: #000 !important; margin: 0 !important; }
            .preview-stage {
                padding: 0 !important;
                margin: 0 !important;
                display: block !important;
                height: auto !important;
                overflow: visible !important;
            }
            .receipt {
                margin: 0 auto !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                border: none !important;
                transform: none !important;
                page-break-inside: avoid;
                page-break-after: avoid;
            }
            .receipt.size-58mm {
                width: 58mm !important;
                max-width: 58mm !important;
                padding: 2mm 2mm !important;
                font-size: 10.5px !important;
            }
            .receipt.size-80mm {
                width: 80mm !important;
                max-width: 80mm !important;
                padding: 3mm 3mm !important;
                font-size: 12px !important;
            }
            .receipt.size-a5 {
                width: 148mm !important;
                min-height: 210mm !important;
                max-width: 148mm !important;
                padding: 8mm 10mm !important;
                font-size: 11px !important;
            }
            .receipt.size-a4 {
                width: 210mm !important;
                min-height: 297mm !important;
                max-width: 210mm !important;
                padding: 12mm 15mm !important;
                font-size: 13px !important;
            }
            .no-print { display: none !important; }
        }

        .store-header { text-align: center; margin-bottom: 6px; }
        .store-logo { max-height: 44px; max-width: 120px; object-fit: contain; margin: 0 auto 6px auto; display: block; }
        .store-name { font-size: 16px; font-weight: 800; line-height: 1.2; }
        .size-58mm .store-name { font-size: 14px; }
        .size-a4 .store-name { font-size: 20px; }
        .size-a5 .store-name { font-size: 18px; }
        .store-sub { font-size: 11px; color: #475569; margin-top: 2px; }
        .store-meta { font-size: 10.5px; color: #64748b; margin-top: 2px; line-height: 1.3; }

        .rule { border: none; border-top: 1px dashed #cbd5e1; margin: 8px 0; }
        .solid-rule { border: none; border-top: 1.5px solid #0f172a; margin: 8px 0; }

        .receipt-no { text-align: center; font-size: 14px; font-weight: 800; letter-spacing: .3px; }
        .size-58mm .receipt-no { font-size: 13px; }
        .size-a4 .receipt-no { font-size: 16px; }
        .receipt-no span { color: #0284c7; }

        .meta-row { display: flex; justify-content: space-between; font-size: 11px; color: #334155; padding: 1.5px 0; }
        .size-a4 .meta-row { font-size: 12.5px; padding: 2.5px 0; }
        .meta-row b { color: #0f172a; font-weight: 700; }

        table.items { width: 100%; border-collapse: collapse; font-size: inherit; margin: 4px 0; }
        table.items th {
            text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: .4px;
            color: #64748b; border-bottom: 1px solid #e2e8f0; padding: 3px 0;
        }
        .size-a4 table.items th, .size-a5 table.items th {
            font-size: 11.5px;
            padding: 5px 0;
        }
        table.items td { padding: 4px 0; vertical-align: top; }
        .size-a4 table.items td, .size-a5 table.items td { padding: 6px 0; }
        table.items td.amt, table.items th.amt { text-align: right; white-space: nowrap; }
        .qty-price { font-size: 10.5px; color: #64748b; }

        .totals { margin-top: 4px; }
        .total-row { display: flex; justify-content: space-between; padding: 2px 0; font-size: inherit; }
        .total-row.grand { font-size: 15px; font-weight: 800; border-top: 1px solid #e2e8f0; margin-top: 4px; padding-top: 4px; }
        .size-58mm .total-row.grand { font-size: 13px; }
        .size-a4 .total-row.grand { font-size: 17px; }
        .total-row .change { color: #059669; font-weight: 700; }

        /* QR Code & Barcode */
        .qr-section {
            margin: 10px auto 10px auto;
            text-align: center;
            padding: 8px 6px;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
        }
        .qr-svg-wrap {
            display: inline-block;
            background: #fff;
            padding: 4px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            margin-bottom: 4px;
        }
        .qr-box {
            width: 72px;
            height: 72px;
            margin: 0 auto;
            border: 1px solid #0f172a;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            font-weight: 800;
        }
        .qr-label { font-size: 10.5px; font-weight: 700; color: #334155; margin-top: 2px; line-height: 1.3; }

        .barcode-section { text-align: center; margin: 8px 0; }
        .barcode-bars {
            height: 24px;
            background: repeating-linear-gradient(90deg, #000 0px, #000 2px, #fff 2px, #fff 4px, #000 4px, #000 6px);
            width: 70%;
            margin: 3px auto;
        }
        .barcode-text { font-family: monospace; font-size: 10px; font-weight: 700; color: #475569; }

        .footer { text-align: center; font-size: 10.5px; color: #64748b; margin-top: 10px; line-height: 1.4; }
        .policy { font-size: 9.5px; color: #94a3b8; margin-top: 3px; font-style: italic; }
        .reprint-note { text-align: center; font-size: 10.5px; color: #d97706; font-weight: 800; margin-top: 6px; padding: 2px; border: 1px dashed #f59e0b; border-radius: 4px; }
        .void-banner { text-align: center; font-size: 12px; color: #dc2626; font-weight: 900; letter-spacing: 0.5px; margin: 6px 0; padding: 3px; border: 2px dashed #dc2626; border-radius: 4px; background: #fef2f2; text-transform: uppercase; }

        /* Modal Backdrop */
        .share-modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.7);
            z-index: 200;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 12px;
            backdrop-filter: blur(4px);
        }
        .share-modal.show {
            display: flex;
        }
        .share-card {
            background: #ffffff;
            border-radius: 12px;
            max-width: 440px;
            width: 100%;
            padding: 16px 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            text-align: left;
        }
        .share-channel-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #1e293b;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .share-channel-btn:hover {
            background: #f1f5f9;
        }
        .share-channel-btn:active {
            transform: scale(0.99);
        }

    </style>
    <script nonce="{{ $cspNonce }}" src="{{ asset('vendor/html2pdf/html2pdf.bundle.min.js') }}"></script>
</head>
<body>
    {{-- Top Action Toolbar (Sticky on screen, hidden in print) --}}
    <div class="top-nav-bar no-print">
        <div class="top-nav-inner">
            <div class="top-nav-left">
                <a href="{{ url('/store/' . $store->slug . '/pos') }}" class="tool-btn tool-btn-back">
                    ← <span>{{ __('messages.back_to_pos') }}</span>
                </a>

                {{-- Compact Paper Size Dropdown --}}
                <div class="size-select-wrapper">
                    <label for="paperSizeSelect" class="size-select-label">📄 {{ __('messages.paper_size') }}:</label>
                    <select id="paperSizeSelect" class="size-select">
                        <option value="58mm" {{ $paperSize === '58mm' ? 'selected' : '' }}>58mm (POS)</option>
                        <option value="80mm" {{ $paperSize === '80mm' ? 'selected' : '' }}>80mm (POS)</option>
                        <option value="a5" {{ $paperSize === 'a5' ? 'selected' : '' }}>A5 (Half)</option>
                        <option value="a4" {{ $paperSize === 'a4' ? 'selected' : '' }}>A4 (Full)</option>
                    </select>
                </div>
            </div>

            <div class="top-nav-divider"></div>

            <div class="top-nav-right">
                <button type="button" class="tool-btn tool-btn-print" id="btnPrint">
                    🖨️ <span>{{ __('messages.print') }}</span>
                </button>
                <button type="button" class="tool-btn tool-btn-pdf" id="btnDownloadPdf">
                    📄 <span>{{ __('messages.vouchers_save_pdf') }}</span>
                </button>
                <button type="button" class="tool-btn tool-btn-share-jpg" id="btnShareJpg" title="{{ __('messages.vouchers_copy_jpg') }}">
                    🖼️ <span>{{ __('messages.vouchers_share_jpg') }}</span>
                </button>
                <button type="button" class="tool-btn tool-btn-share" id="btnShare" title="{{ __('messages.share') }}">
                    📲 <span>{{ __('messages.share') }}</span>
                </button>
            </div>
        </div>
    </div>

    <div class="preview-stage" id="previewStage">
        <div id="receiptContent" class="receipt size-{{ $paperSize }} preset-{{ $stylePreset }} font-{{ $fontSize }}">
        <div class="store-header">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $store->name }}" class="store-logo" />
            @endif
            <p class="store-name">{{ $headerTitle }}</p>
            @if ($headerSubtitle)
                <p class="store-sub">{{ $headerSubtitle }}</p>
            @endif
            @if ($address)
                <p class="store-meta">{{ $address }}</p>
            @endif
            @if ($phone)
                <p class="store-meta">{{ $phone }}</p>
            @endif
            @if ($store->setting?->getPosSetting('show_tax_id') && $store->setting?->getPosSetting('tax_id_number'))
                <p class="store-meta" style="font-weight:700;letter-spacing:0.3px;">TIN: {{ $store->setting->getPosSetting('tax_id_number') }}</p>
            @endif
        </div>

        <hr class="rule">

        @if ($sale->isVoided())
            <div class="void-banner">*** VOID / CANCELLED — {{ __('messages.voucher_watermark_void') }} ***</div>
        @endif

        <p class="receipt-no">{{ __('messages.receipt') ?? 'Receipt' }} <span>#{{ $sale->receipt_number }}</span></p>
        <hr class="rule">

        <div class="meta-row"><span>{{ __('messages.date') }}</span><b>{{ $sale->posted_at?->format('d M Y, H:i') }}</b></div>
        @if ($showCashier)
            <div class="meta-row"><span>{{ __('messages.cashier') }}</span><b>{{ $sale->cashier?->name ?? '—' }}</b></div>
        @endif
        @if ($showCustomer && $sale->customer)
            <div class="meta-row"><span>{{ __('messages.customer') }}</span><b>{{ $sale->customer->name }}</b></div>
        @endif
        @if ($sale->cashierShift?->register_name)
            <div class="meta-row"><span>{{ __('messages.register') }}</span><b>{{ $sale->cashierShift->register_name }}</b></div>
        @endif

        <hr class="rule">

        <table class="items">
            <thead>
                <tr>
                    <th>{{ __('messages.reports_items') ?? 'Items' }}</th>
                    <th class="amt">{{ __('messages.amount') ?? 'Amount' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sale->items as $item)
                    <tr>
                        <td>
                            {{ $item->product_name }}
                            @if ($item->original_unit_price !== null && (float) $item->original_unit_price != (float) $item->unit_price)
                                <div class="qty-price">{{ format_quantity($item->quantity, $store) }} × <s>{{ format_currency((float) $item->original_unit_price, $store) }}</s> {{ format_currency((float) $item->unit_price, $store) }} ✏️</div>
                            @else
                                <div class="qty-price">{{ format_quantity($item->quantity, $store) }} × {{ format_currency((float) $item->unit_price, $store) }}</div>
                            @endif
                        </td>
                        <td class="amt">{{ format_currency((float) $item->line_total, $store) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <hr class="rule">

        <div class="totals">
            @if ($showTaxBreakdown && (float) $sale->tax > 0 && $sale->tax_type === 'exclusive')
                <div class="total-row">
                    <span>{{ __('messages.subtotal') }}</span>
                    <span>{{ format_currency((float) $sale->subtotal, $store) }}</span>
                </div>
                <div class="total-row">
                    <span>{{ __('messages.commercial_tax') }}</span>
                    <span>+ {{ format_currency((float) $sale->tax, $store) }}</span>
                </div>
            @endif
            @if ($showDiscountLine && (float) $sale->discount > 0)
                <div class="total-row">
                    <span>{{ __('messages.discount') }}</span>
                    <span style="color:#d97706;">− {{ format_currency((float) $sale->discount, $store) }}</span>
                </div>
            @endif
            <div class="total-row grand">
                <span>{{ __('messages.total') }}</span>
                <span>{{ format_currency((float) $sale->total, $store) }}</span>
            </div>
            @if ($showTaxBreakdown && (float) $sale->tax > 0 && $sale->tax_type === 'inclusive')
                <div class="total-row" style="font-size:10px;color:#64748b;font-style:italic;">
                    <span>({{ __('messages.commercial_tax') }})</span>
                    <span>{{ format_currency((float) $sale->tax, $store) }}</span>
                </div>
            @endif

            @php $balanceDue = $sale->payments->firstWhere('method', 'credit')?->amount ?? '0'; @endphp
            @foreach ($sale->payments as $payment)
                <div class="total-row" style="font-size:11px;color:#475569;">
                    <span>{{ $payment->method === 'credit' ? __('messages.payment_credit') : ucfirst($payment->method) }}</span>
                    <span>{{ format_currency((float) $payment->amount, $store) }}</span>
                </div>
                @if ((float) $payment->change_given > 0)
                    <div class="total-row" style="font-size:11px;">
                        <span>{{ __('messages.change') }}</span>
                        <span class="change">− {{ format_currency((float) $payment->change_given, $store) }}</span>
                    </div>
                @endif
            @endforeach
            @if ((float) $balanceDue > 0)
                <div class="total-row" style="color:#d97706;font-weight:700;">
                    <span>{{ __('messages.balance_due') }}</span>
                    <span>{{ format_currency((float) $balanceDue, $store) }}</span>
                </div>
            @endif
        </div>

        @if ($showQr && !empty($receiptQrDataUri))
            <div class="qr-section">
                <div class="qr-svg-wrap">
                    <img src="{{ $receiptQrDataUri }}" alt="QR Code" class="qr-image" style="display:block; margin:0 auto 4px auto; max-height:80px; max-width:80px; border-radius:6px; padding:2px; border:1px solid #cbd5e1;" />
                    <canvas class="qr-code-canvas" width="180" height="180" style="display:none; margin:0 auto; width:80px; height:80px;"></canvas>
                </div>
                <div class="qr-label">
                    @if ($isPaymentQr)
                        {{ $qrLabel ?: __('messages.receipt_scan_to_pay') }}
                    @else
                        {{ ($qrLabel && !str_contains(strtolower($qrLabel), 'pay') && !str_contains(strtolower($qrLabel), 'kpay')) ? $qrLabel : __('messages.receipt_scan_to_verify') }}
                    @endif
                </div>
            </div>
        @endif

        @if ($showBarcode)
            <div class="barcode-section">
                <div class="barcode-bars"></div>
                <div class="barcode-text">*{{ $sale->receipt_number }}*</div>
            </div>
        @endif

        @if ($isReprint)
            <p class="reprint-note">*** {{ __('messages.voucher_watermark_reprint') ?? 'COPY — REPRINT' }} (REPRINT #{{ $printCount }}) — {{ now()->format('d/m/Y H:i') }} ***</p>
        @endif

        <div class="footer">
            <p>{{ $footerGreeting }}</p>
            @if ($footerPolicy)
                <p class="policy">{{ $footerPolicy }}</p>
            @endif
            <p style="font-size:9px;color:#cbd5e1;margin-top:4px;">{{ $store->name }} · {{ now()->format('Y') }}</p>
        </div>
    </div>
    </div>

    {{-- ── SECTION 3: Social Sharing Modal Dialog ── --}}
    <div id="shareModal" class="share-modal no-print" onclick="if(event.target===this)closeShareModal()">
        <div class="share-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                <h3 style="font-size:15px; font-weight:800; color:#0f172a;">
                    📲 {{ __('messages.receipt_share_modal_title') }}
                </h3>
                <button type="button" onclick="closeShareModal()" style="border:none; background:transparent; font-size:18px; cursor:pointer; color:#64748b;">✕</button>
            </div>
            <p style="font-size:12px; color:#64748b; margin-bottom:14px;">
                {{ __('messages.receipt_share_modal_desc') }}
            </p>

            {{-- 1. Copy Receipt Summary Text --}}
            <button type="button" class="share-channel-btn" onclick="copyReceiptText()" style="background:#0284c7; color:#fff; border-color:#0369a1;">
                <span>📋</span>
                <span id="copyReceiptTextLabel">{{ __('messages.receipt_copy_text') }}</span>
            </button>

            {{-- 2. Share / Copy JPG Image --}}
            <button type="button" class="share-channel-btn" id="modalBtnShareJpg" onclick="shareJpgDirectly()" style="background:#0d9488; color:#fff; border-color:#0f766e;">
                <span>🖼️</span>
                <span>{{ __('messages.vouchers_share_jpg') }} ({{ __('messages.vouchers_copy_jpg') }})</span>
            </button>

            {{-- 3. Viber Channel --}}
            <button type="button" class="share-channel-btn" onclick="shareToViber()">
                <span style="color:#7360f2; font-size:16px;">💬</span>
                <span>{{ __('messages.repair_share_viber') }}</span>
            </button>

            {{-- 4. Telegram Channel --}}
            <button type="button" class="share-channel-btn" onclick="shareToTelegram()">
                <span style="color:#229ed9; font-size:16px;">✈️</span>
                <span>{{ __('messages.repair_share_telegram') }}</span>
            </button>

            {{-- 5. WhatsApp Channel --}}
            <button type="button" class="share-channel-btn" onclick="shareToWhatsApp()">
                <span style="color:#25d366; font-size:16px;">🟢</span>
                <span>{{ __('messages.repair_share_whatsapp') }}</span>
            </button>

            {{-- 6. Native Share PDF --}}
            <button type="button" class="share-channel-btn" onclick="shareNativePdf()" style="background:#7c3aed; color:#fff; border-color:#6d28d9;">
                <span>📄</span>
                <span>{{ __('messages.share_pdf') }}</span>
            </button>

            {{-- 7. Copy Link Only --}}
            <button type="button" class="share-channel-btn" onclick="copyReceiptLink()">
                <span>🔗</span>
                <span id="copyReceiptLinkLabel">{{ __('messages.receipt_copy_link') }}</span>
            </button>
        </div>
    </div>

    <script nonce="{{ $cspNonce }}">
        var currentPaperSize = '{{ $paperSize }}';
        var jobNumber = @js($sale->receipt_number);
        var storeName = @js($store->name);
        var receiptUrl = @js($receiptUrl);
        var customerPhone = @js($customerPhone);
        var storePhone = @js($storePhone);

        var pdfDimensions = {
            '58mm': { unit: 'mm', format: [58, 120], orientation: 'portrait' },
            '80mm': { unit: 'mm', format: [80, 160], orientation: 'portrait' },
            'a5': { unit: 'mm', format: [148, 210], orientation: 'portrait' },
            'a4': { unit: 'mm', format: [210, 297], orientation: 'portrait' }
        };

        var pdfConfig = {
            margin: [0, 0, 0, 0],
            filename: 'Receipt_{{ $sale->receipt_number }}_{{ $store->slug }}.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true, logging: false },
            jsPDF: pdfDimensions[currentPaperSize] || pdfDimensions['80mm']
        };

        function initQrCanvas() {
            var wraps = document.querySelectorAll('.qr-svg-wrap');
            wraps.forEach(function(wrap) {
                var img = wrap.querySelector('img');
                var canvas = wrap.querySelector('canvas.qr-code-canvas');
                if (img && canvas) {
                    var render = function() {
                        var w = img.naturalWidth || 180;
                        var h = img.naturalHeight || 180;
                        canvas.width = w;
                        canvas.height = h;
                        var ctx = canvas.getContext('2d');
                        ctx.imageSmoothingEnabled = false;
                        ctx.drawImage(img, 0, 0, w, h);
                        canvas.style.display = 'block';
                        img.style.display = 'none';
                    };
                    if (img.complete && img.naturalWidth > 0) {
                        render();
                    } else {
                        img.onload = render;
                    }
                }
            });
        }
        document.addEventListener('DOMContentLoaded', initQrCanvas);
        initQrCanvas();

        function stampQrOnPdfCanvas(worker, ticket, rootRect, cwRect) {
            var canvas = worker.prop ? worker.prop.canvas : null;
            var qrWrap = ticket.querySelector('.qr-svg-wrap');
            var qrSource = qrWrap ? (qrWrap.querySelector('canvas') || qrWrap.querySelector('img')) : null;

            if (canvas && rootRect && cwRect && qrSource) {
                var scale = canvas.width / rootRect.width;
                var boxX = (cwRect.left - rootRect.left) * scale;
                var boxY = (cwRect.top - rootRect.top) * scale;
                var boxW = cwRect.width * scale;
                var boxH = cwRect.height * scale;

                var pad = 4 * scale;
                var drawW = boxW - pad * 2;
                var drawH = boxH - pad * 2;
                var drawX = boxX + pad;
                var drawY = boxY + pad;

                var ctx = canvas.getContext('2d');
                ctx.setTransform(1, 0, 0, 1, 0, 0);
                ctx.imageSmoothingEnabled = false;
                ctx.drawImage(qrSource, drawX, drawY, drawW, drawH);
            }
        }

        function showToast(message) {
            var toast = document.getElementById('printToast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'printToast';
                toast.className = 'print-toast no-print';
                document.body.appendChild(toast);
            }
            toast.innerHTML = '📋 ' + message;
            toast.classList.add('show');
            clearTimeout(window._toastTimer);
            window._toastTimer = setTimeout(function() {
                toast.classList.remove('show');
            }, 3500);
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
            var sheet = document.getElementById('receiptContent');
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
            currentPaperSize = size;
            var element = document.getElementById('receiptContent');
            if (element) {
                element.className = 'receipt size-' + size + ' preset-{{ $stylePreset }} font-{{ $fontSize }}';
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
                    pdfConfig.jsPDF = pdfDimensions['58mm'];
                    pdfConfig.margin = [0, 0, 0, 0];
                } else if (size === '80mm') {
                    pageStyle.innerHTML = '@page { size: 80mm auto; margin: 0; }';
                    pdfConfig.jsPDF = pdfDimensions['80mm'];
                    pdfConfig.margin = [0, 0, 0, 0];
                } else if (size === 'a5') {
                    pageStyle.innerHTML = '@page { size: 148mm 210mm; margin: 0; }';
                    pdfConfig.jsPDF = pdfDimensions['a5'];
                    pdfConfig.margin = [0, 0, 0, 0];
                } else if (size === 'a4') {
                    pageStyle.innerHTML = '@page { size: 210mm 297mm; margin: 0; }';
                    pdfConfig.jsPDF = pdfDimensions['a4'];
                    pdfConfig.margin = [0, 0, 0, 0];
                }
            }

            // Sync URL parameter silently so reloads remember the chosen size
            try {
                var url = new URL(window.location.href);
                url.searchParams.set('paper_size', size);
                window.history.replaceState({}, '', url.toString());
            } catch (e) {}

            updatePreviewScale();
            showToast('{{ __("messages.paper_size") }}: ' + size.toUpperCase());
        }

        async function downloadPdf() {
            var btn = document.getElementById('btnDownloadPdf');
            var originalText = btn ? btn.innerHTML : '';
            var element = document.getElementById('receiptContent');
            var stage = document.getElementById('previewStage');

            // Temporarily reset transform for unscaled PDF generation
            if (element) element.style.transform = 'none';
            if (stage) stage.style.height = 'auto';

            // Dynamic height calculation for thermal sizes
            if (element && (currentPaperSize === '80mm' || currentPaperSize === '58mm')) {
                var pxHeight = element.offsetHeight;
                var mmHeight = Math.ceil(pxHeight * 0.264583) + 8;
                var widthMm = currentPaperSize === '80mm' ? 80 : 58;
                pdfConfig.jsPDF = { unit: 'mm', format: [widthMm, Math.max(mmHeight, 80)], orientation: 'portrait' };
            } else if (currentPaperSize === 'a5') {
                pdfConfig.jsPDF = { unit: 'mm', format: [148, 210], orientation: 'portrait' };
            } else if (currentPaperSize === 'a4') {
                pdfConfig.jsPDF = { unit: 'mm', format: [210, 297], orientation: 'portrait' };
            }

            if (window.html2pdf && element) {
                if (btn) { btn.disabled = true; btn.innerHTML = '⏳ {{ __("messages.generating_pdf") ?? "Generating..." }}'; }
                try {
                    initQrCanvas();
                    var worker = html2pdf().set(pdfConfig).from(element);
                    await worker.toContainer();
                    var container = worker.prop.container;
                    var containerWrap = container ? container.querySelector('.qr-svg-wrap') : null;
                    var rootRect = container ? container.getBoundingClientRect() : null;
                    var cwRect = containerWrap ? containerWrap.getBoundingClientRect() : null;

                    await worker.toCanvas();
                    stampQrOnPdfCanvas(worker, element, rootRect, cwRect);
                    await worker.toPdf();
                    await worker.save();
                } catch (err) {
                    console.error('PDF error:', err);
                    window.print();
                } finally {
                    updatePreviewScale();
                    if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
                }
            } else {
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
            if (typeof closeShareModal === 'function') closeShareModal();
            var element = document.getElementById('receiptContent');
            var btn = document.getElementById('btnShareJpg');
            var originalText = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '⏳ <span>Generating...</span>';
            }

            try {
                if (!window.html2pdf || !element) {
                    window.print();
                    return;
                }

                initQrCanvas();
                var worker = html2pdf().set({
                    ...pdfConfig,
                    margin: 0,
                    image: { type: 'png', quality: 1.0 },
                    html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff', logging: false }
                }).from(element);

                await worker.toContainer();
                var exactWidthPx = element.offsetWidth;
                if (worker.prop && worker.prop.container) {
                    worker.prop.container.style.width = exactWidthPx + 'px';
                    if (worker.prop.container.firstChild) {
                        worker.prop.container.firstChild.style.width = exactWidthPx + 'px';
                    }
                }
                await worker.toCanvas();
                var canvas = worker.prop.canvas;

                // Stamp QR Code directly onto the rendered canvas
                var wrap = element.querySelector('.qr-svg-wrap');
                var origImg = wrap ? wrap.querySelector('img') : null;
                var qrCanvas = wrap ? wrap.querySelector('canvas.qr-code-canvas') : null;
                var qrSource = qrCanvas || origImg;
                if (canvas && qrSource && wrap) {
                    var ticketRect = element.getBoundingClientRect();
                    var wrapRect = wrap.getBoundingClientRect();
                    var scale = canvas.width / ticketRect.width;

                    var boxX = (wrapRect.left - ticketRect.left) * scale;
                    var boxY = (wrapRect.top - ticketRect.top) * scale;
                    var boxW = wrapRect.width * scale;
                    var boxH = wrapRect.height * scale;
                    var imgW = (qrSource.offsetWidth || 72) * scale;
                    var imgH = (qrSource.offsetHeight || 72) * scale;
                    var imgX = boxX + (boxW - imgW) / 2;
                    var imgY = boxY + (boxH - imgH) / 2;

                    var ctx = canvas.getContext('2d');
                    ctx.setTransform(1, 0, 0, 1, 0, 0);
                    ctx.imageSmoothingEnabled = false;
                    ctx.drawImage(qrSource, imgX, imgY, imgW, imgH);
                }

                var dataUri = canvas.toDataURL('image/png');
                var blob = dataUriToBlob(dataUri);
                var fileName = 'Receipt_{{ $sale->receipt_number }}_{{ $store->slug }}.png';
                var file = new File([blob], fileName, { type: 'image/png' });

                // 1. Prioritize Direct Clipboard Copy (Paste directly Ctrl+V into WeChat / Viber / Telegram)
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
                    // 2. Mobile WebShare API fallback
                    var isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
                    if (isMobile && navigator.canShare && navigator.canShare({ files: [file] })) {
                        try {
                            await navigator.share({
                                title: 'Receipt #{{ $sale->receipt_number }}',
                                text: 'Receipt from {{ $headerTitle }} #{{ $sale->receipt_number }}',
                                files: [file]
                            });
                            return;
                        } catch (shareErr) {
                            if (shareErr.name === 'AbortError') return;
                        }
                    }

                    // 3. Fallback direct download
                    downloadBlob(blob, fileName);
                    showToast("{{ __('messages.vouchers_jpg_copied') }}");
                }
            } catch (err) {
                if (err.name !== 'AbortError') {
                    console.error('Share JPG error:', err);
                    showToast('Failed to generate image');
                }
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
                var modalBtn = document.getElementById('modalBtnShareJpg');
                if (modalBtn) {
                    modalBtn.disabled = false;
                }
            }
        }

        async function shareNativePdf() {
            closeShareModal();
            var btn = document.getElementById('btnShare');
            var originalText = btn ? btn.innerHTML : '';
            var element = document.getElementById('receiptContent');

            if (window.html2pdf && element) {
                if (btn) { btn.disabled = true; btn.innerHTML = '⏳ Preparing PDF...'; }
                try {
                    initQrCanvas();
                    var worker = html2pdf().set(pdfConfig).from(element);
                    await worker.toContainer();
                    var container = worker.prop.container;
                    var containerWrap = container ? container.querySelector('.qr-svg-wrap') : null;
                    var rootRect = container ? container.getBoundingClientRect() : null;
                    var cwRect = containerWrap ? containerWrap.getBoundingClientRect() : null;

                    await worker.toCanvas();
                    stampQrOnPdfCanvas(worker, element, rootRect, cwRect);
                    await worker.toPdf();
                    var pdfBlob = await worker.output('blob');
                    var pdfFile = new File([pdfBlob], pdfConfig.filename, { type: 'application/pdf' });

                    if (navigator.canShare && navigator.canShare({ files: [pdfFile] })) {
                        await navigator.share({
                            title: 'Receipt #{{ $sale->receipt_number }}',
                            text: 'Receipt from {{ $headerTitle }} #{{ $sale->receipt_number }}',
                            files: [pdfFile]
                        });
                        return;
                    }
                    downloadBlob(pdfBlob, pdfConfig.filename);
                } catch (e) {
                    if (e.name !== 'AbortError') {
                        console.error('Share error:', e);
                    }
                } finally {
                    if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
                }
            }
        }

        function buildShareText() {
            var lines = [];
            lines.push("🧾 {{ __('messages.receipt') ?? 'Receipt' }} #{{ $sale->receipt_number }}");
            lines.push("🏪 {{ $headerTitle }}");
            lines.push("📅 {{ __('messages.date') }}: {{ $sale->posted_at?->format('d/m/Y, H:i') }}");
            @if ($showCashier && $sale->cashier)
                lines.push("👤 {{ __('messages.cashier') }}: {{ $sale->cashier->name }}");
            @endif
            @if ($showCustomer && $sale->customer)
                lines.push("👤 {{ __('messages.customer') }}: {{ $sale->customer->name }}");
            @endif
            lines.push("--------------------------------");
            @foreach ($sale->items as $item)
                lines.push("• {{ addslashes($item->product_name) }}: {{ format_quantity($item->quantity, $store) }} x {{ format_currency((float)$item->unit_price, $store) }} = {{ format_currency((float)$item->line_total, $store) }}");
            @endforeach
            lines.push("--------------------------------");
            lines.push("💵 {{ __('messages.total') }}: {{ format_currency((float)$sale->total, $store) }}");
            @if ((float)$balanceDue > 0)
                lines.push("⚠️ {{ __('messages.balance_due') }}: {{ format_currency((float)$balanceDue, $store) }}");
            @endif
            lines.push("\n{{ $footerGreeting }}");
            @if ($phone)
                lines.push("📞 {{ $phone }}");
            @endif

            return lines.join("\n");
        }

        function fallbackCopyText(text, successMsg) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.position = 'fixed';
            ta.style.top = '0';
            ta.style.left = '0';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.focus();
            ta.select();
            var success = false;
            try {
                success = document.execCommand('copy');
            } catch (err) {
                success = false;
            }
            document.body.removeChild(ta);
            if (success) {
                if (successMsg) showToast(successMsg);
            } else {
                prompt('Copy text:', text);
            }
            return success;
        }

        function copyReceiptText() {
            var text = buildShareText();
            var msg = '📋 {{ __("messages.receipt_text_copied") }}';
            var lbl = document.getElementById('copyReceiptTextLabel');
            if (lbl) lbl.innerText = '✓ {{ __("messages.vouchers_copied") }}';

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function() {
                    showToast(msg);
                    setTimeout(function() {
                        if (lbl) lbl.innerText = '{{ __("messages.receipt_copy_text") }}';
                    }, 2500);
                }).catch(function() {
                    fallbackCopyText(text, msg);
                    setTimeout(function() {
                        if (lbl) lbl.innerText = '{{ __("messages.receipt_copy_text") }}';
                    }, 2500);
                });
            } else {
                fallbackCopyText(text, msg);
                setTimeout(function() {
                    if (lbl) lbl.innerText = '{{ __("messages.receipt_copy_text") }}';
                }, 2500);
            }
        }

        function copyReceiptLink() {
            var msg = '🔗 {{ __("messages.receipt_link_copied") }}';
            var lbl = document.getElementById('copyReceiptLinkLabel');
            if (lbl) lbl.innerText = '✓ {{ __("messages.vouchers_copied") }}';

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(receiptUrl).then(function() {
                    showToast(msg);
                    setTimeout(function() {
                        if (lbl) lbl.innerText = '{{ __("messages.receipt_copy_link") }}';
                    }, 2000);
                }).catch(function() {
                    fallbackCopyText(receiptUrl, msg);
                    setTimeout(function() {
                        if (lbl) lbl.innerText = '{{ __("messages.receipt_copy_link") }}';
                    }, 2000);
                });
            } else {
                fallbackCopyText(receiptUrl, msg);
                setTimeout(function() {
                    if (lbl) lbl.innerText = '{{ __("messages.receipt_copy_link") }}';
                }, 2000);
            }
        }

        function shareToViber() {
            closeShareModal();
            var text = encodeURIComponent(buildShareText());
            var url = customerPhone
                ? 'viber://chat?number=%2B' + customerPhone + '&draft=' + text
                : 'viber://forward?text=' + text;
            window.open(url, '_blank');
        }

        function shareToTelegram() {
            closeShareModal();
            var text = encodeURIComponent(buildShareText());
            window.open('https://t.me/share/url?url=' + encodeURIComponent(receiptUrl) + '&text=' + text, '_blank');
        }

        function shareToWhatsApp() {
            closeShareModal();
            var text = encodeURIComponent(buildShareText());
            var url = customerPhone
                ? 'https://api.whatsapp.com/send?phone=' + customerPhone + '&text=' + text
                : 'https://api.whatsapp.com/send?text=' + text;
            window.open(url, '_blank');
        }

        function openShareModal() {
            var m = document.getElementById('shareModal');
            if (m) m.classList.add('show');
        }

        function closeShareModal() {
            var m = document.getElementById('shareModal');
            if (m) m.classList.remove('show');
        }

        // Expose functions globally
        window.setPaperSize = setPaperSize;
        window.downloadPdf = downloadPdf;
        window.shareJpgDirectly = shareJpgDirectly;
        window.shareNativePdf = shareNativePdf;
        window.shareToViber = shareToViber;
        window.shareToTelegram = shareToTelegram;
        window.shareToWhatsApp = shareToWhatsApp;
        window.copyReceiptText = copyReceiptText;
        window.copyReceiptLink = copyReceiptLink;
        window.openShareModal = openShareModal;
        window.closeShareModal = closeShareModal;
        window.showToast = showToast;
        window.downloadBlob = downloadBlob;

        // CSP-compliant DOM Event Listeners
        document.addEventListener('DOMContentLoaded', function() {
            var sizeSelect = document.getElementById('paperSizeSelect');
            if (sizeSelect) {
                sizeSelect.addEventListener('change', function() {
                    setPaperSize(this.value);
                });
            }

            var printBtn = document.getElementById('btnPrint') || document.querySelector('.tool-btn-print');
            if (printBtn) {
                printBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.print();
                });
            }

            var pdfBtn = document.getElementById('btnDownloadPdf') || document.querySelector('.tool-btn-pdf');
            if (pdfBtn) {
                pdfBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    downloadPdf();
                });
            }

            var shareJpgBtn = document.getElementById('btnShareJpg');
            if (shareJpgBtn) {
                shareJpgBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    shareJpgDirectly();
                });
            }

            var shareBtn = document.getElementById('btnShare');
            if (shareBtn) {
                shareBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    openShareModal();
                });
            }

            @if (session('auto_print') || request('auto_print'))
            setTimeout(function() {
                window.print();
            }, 500);
            @endif
        });
    </script>
</body>
</html>
