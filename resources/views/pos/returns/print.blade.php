@php
    $myanmarFontUrl = null;
    try {
        $myanmarFontUrl = \Illuminate\Support\Facades\Vite::asset('resources/assets/fonts/NotoSansMyanmar/NotoSansMyanmar-Regular.ttf');
    } catch (\Throwable $e) {
        $myanmarFontUrl = null;
    }

    $tmpl = $voucherTemplate ?? null;
    $paperSize = strtolower((string) request('paper_size', $paperSize ?? '80mm'));
    if (!in_array($paperSize, ['58mm', '80mm', 'a5', 'a4'], true)) {
        $paperSize = '80mm';
    }
    $is58 = $paperSize === '58mm';
    $is80 = $paperSize === '80mm';
    $isA5 = $paperSize === 'a5';
    $isA4 = $paperSize === 'a4';

    $showLogo = (bool) data_get($tmpl, 'show_logo', true);
    $templateLogo = ($tmpl instanceof \App\Models\VoucherTemplate) ? $tmpl->logoUrl() : null;
    $logoUrl = $showLogo ? ($templateLogo ?? ($store->setting?->adminLogo() ? asset('storage/' . $store->setting->adminLogo()) : null)) : null;

    $headerTitle = data_get($tmpl, 'header_title') ?: $store->name;
    $headerSubtitle = data_get($tmpl, 'header_subtitle');
    $address = data_get($tmpl, 'address') ?: ($store->address ?? null);
    $phone = data_get($tmpl, 'phone') ?: ($store->viber_number ? 'Viber: ' . $store->viber_number : ($store->phone ?? null));

    $backUrl = route('pos.returns.index', ['store_slug' => $store->slug]);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ __('messages.sales_return_slip_title') ?? __('messages.returns_title') }} - {{ $return->refund_number }} - {{ $store->name }}</title>

    <script nonce="{{ $cspNonce ?? '' }}" src="{{ asset('vendor/html2pdf/html2pdf.bundle.min.js') }}"></script>

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
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Noto Sans Myanmar', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Pyidaungsu", sans-serif;
            color: #0f172a;
            background: #f1f5f9;
            padding: 16px;
            font-size: 12px;
            line-height: 1.4;
            -webkit-font-smoothing: antialiased;
        }

        /* ── Top Navigation Bar (Consistent with POS Receipt & Closing Print) ── */
        .top-nav-bar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid rgba(51, 65, 85, 0.6);
            padding: 10px 16px;
            margin: -16px -16px 16px -16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.12);
        }
        .top-nav-inner {
            max-width: 100%;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            padding: 2px 4px;
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

        /* Standard Tool Buttons */
        .tool-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.15s ease;
            user-select: none;
        }
        .tool-btn:active {
            transform: scale(0.97);
        }
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
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(2, 132, 199, 0.35);
        }
        .tool-btn-print:hover {
            background: #0369a1;
        }
        .tool-btn-pdf {
            background: #059669;
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(5, 150, 105, 0.35);
        }
        .tool-btn-pdf:hover {
            background: #047857;
        }
        .tool-btn-pdf:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }
        .tool-btn-share-jpg {
            background: #0d9488;
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(13, 148, 136, 0.35);
        }
        .tool-btn-share-jpg:hover {
            background: #0f766e;
        }
        .tool-btn-share-jpg:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }
        .tool-btn-close {
            background: #1e293b;
            color: #94a3b8;
            border-color: #334155;
        }
        .tool-btn-close:hover {
            background: #334155;
            color: #f8fafc;
        }

        /* Document Badge in Nav Bar */
        .slip-badge-bar {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            background: #e11d48;
            color: #ffffff;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.02em;
        }

        /* Compact Paper Size Dropdown */
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

        /* Quick Paper Size Pills */
        .paper-pills {
            display: inline-flex;
            background: #0f172a;
            padding: 2px;
            border-radius: 6px;
            gap: 2px;
            border: 1px solid #334155;
        }
        .paper-pill {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            background: transparent;
            color: #94a3b8;
            transition: all 0.15s ease;
        }
        .paper-pill:hover {
            color: #ffffff;
        }
        .paper-pill.active {
            background: #0284c7;
            color: #ffffff;
            font-weight: 800;
        }

        /* Auto-print checkbox */
        .auto-print-wrapper {
            font-size: 11.5px;
            font-weight: 700;
            color: #cbd5e1;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            user-select: none;
        }
        .auto-print-wrapper input[type="checkbox"] {
            accent-color: #0284c7;
            cursor: pointer;
            width: 14px;
            height: 14px;
        }

        /* Toast Feedback */
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

        /* ── Preview Stage Container ── */
        .preview-stage {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 16px 8px 36px 8px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* ── Printable Voucher Paper Container ── */
        .voucher-paper {
            background: #ffffff;
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.08);
            border-radius: 6px;
            color: #0f172a;
            box-sizing: border-box;
            transition: width 0.2s ease, padding 0.2s ease;
            transform-origin: top center;
            margin: 0 auto;
        }

        /* 80mm Mode (Default POS Thermal) */
        .voucher-paper.paper-80mm {
            width: 80mm;
            max-width: 80mm;
            min-height: 120mm;
            padding: 14px 10px;
            font-size: 11.5px;
        }
        /* 58mm Mode (Small POS Thermal) */
        .voucher-paper.paper-58mm {
            width: 58mm;
            max-width: 58mm;
            min-height: 90mm;
            padding: 10px 6px;
            font-size: 10px;
        }
        /* A5 Half Sheet Mode */
        .voucher-paper.paper-a5 {
            width: 148mm;
            max-width: 148mm;
            min-height: 210mm;
            padding: 8mm 10mm;
            font-size: 11.5px;
        }
        /* A4 Full Sheet Mode */
        .voucher-paper.paper-a4 {
            width: 210mm;
            max-width: 210mm;
            min-height: 297mm;
            padding: 14mm 16mm;
            font-size: 13px;
        }

        /* Typography & Structure */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
        .font-bold { font-weight: 700; }
        .font-black { font-weight: 900; }
        .truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        .store-header {
            text-align: center;
            margin-bottom: 8px;
        }
        .store-logo {
            max-height: 48px;
            max-width: 130px;
            object-fit: contain;
            margin: 0 auto 6px auto;
            display: block;
        }
        .header-title {
            font-size: 1.3em;
            font-weight: 900;
            text-transform: uppercase;
            margin: 0 0 2px 0;
            letter-spacing: -0.01em;
            line-height: 1.2;
        }
        .store-sub {
            font-size: 0.85em;
            color: #475569;
            margin: 1px 0;
            line-height: 1.3;
        }
        .voucher-type-badge {
            display: inline-block;
            margin: 8px 0 6px 0;
            padding: 3px 10px;
            background: #fff1f2;
            color: #e11d48;
            border: 1px solid #ffe4e6;
            border-radius: 4px;
            font-size: 0.82em;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .divider-dashed {
            border: none;
            border-top: 1px dashed #cbd5e1;
            margin: 8px 0;
        }
        .divider-solid {
            border: none;
            border-top: 1.5px solid #0f172a;
            margin: 8px 0;
        }

        .meta-grid {
            margin: 6px 0;
            font-size: 0.9em;
        }
        .meta-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 3px;
        }
        .meta-label {
            color: #64748b;
        }
        .meta-val {
            font-weight: 700;
            color: #0f172a;
            text-align: right;
        }

        /* Items Table */
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            font-size: inherit;
        }
        table.items-table th {
            border-bottom: 1.5px solid #0f172a;
            padding: 4px 0;
            text-transform: uppercase;
            font-size: 0.8em;
            color: #475569;
        }
        table.items-table td {
            padding: 5px 0;
            border-bottom: 1px dashed #e2e8f0;
            vertical-align: top;
        }

        .total-box {
            margin-top: 8px;
            padding-top: 6px;
            border-top: 1.5px solid #0f172a;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1.15em;
            font-weight: 900;
            color: #e11d48;
        }

        .payments-box {
            margin-top: 6px;
            padding: 6px 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            font-size: 0.85em;
        }
        .payment-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 2px 0;
        }

        .reason-box {
            margin-top: 8px;
            padding: 6px 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            font-size: 0.85em;
            color: #475569;
        }

        /* Signature Blocks */
        .signatures {
            margin-top: 28px;
            display: flex;
            justify-content: space-between;
            gap: 16px;
            font-size: 0.85em;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .sig-block {
            flex: 1;
            text-align: center;
            padding-top: 32px;
            border-top: 1px dotted #94a3b8;
            font-weight: 700;
            color: #334155;
        }

        /* Print Media Rules */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
                color: #000000 !important;
            }
            * {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            .no-print, .top-nav-bar, .print-toast {
                display: none !important;
            }
            .preview-stage {
                padding: 0 !important;
                margin: 0 !important;
                display: block !important;
                overflow: visible !important;
            }
            .voucher-paper {
                box-shadow: none !important;
                border-radius: 0 !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 auto !important;
                transform: none !important;
                page-break-inside: avoid;
                page-break-after: avoid;
            }
            .voucher-paper.paper-58mm {
                width: 58mm !important;
                max-width: 58mm !important;
                padding: 2mm 2mm !important;
            }
            .voucher-paper.paper-80mm {
                width: 80mm !important;
                max-width: 80mm !important;
                padding: 3mm 3mm !important;
            }
            .voucher-paper.paper-a5 {
                width: 148mm !important;
                max-width: 148mm !important;
                min-height: 210mm !important;
                padding: 8mm 10mm !important;
            }
            .voucher-paper.paper-a4 {
                width: 210mm !important;
                max-width: 210mm !important;
                min-height: 297mm !important;
                padding: 14mm 16mm !important;
            }
        }
    </style>
</head>
<body>

    {{-- ── SECTION 1: Standard Action Toolbar (Consistent with POS Receipt & Closing Print) ── --}}
    <div class="top-nav-bar no-print">
        <div class="top-nav-inner">
            <div class="top-nav-left">
                <a href="{{ $backUrl }}" class="tool-btn tool-btn-back" id="btnBack">
                    ← <span>{{ __('messages.back') }}</span>
                </a>

                <span class="slip-badge-bar">
                    {{ __('messages.sales_return_slip_title') ?? __('messages.returns_title') }}
                </span>

                {{-- Compact Paper Size Dropdown --}}
                <div class="size-select-wrapper">
                    <label for="paperSizeSelect" class="size-select-label">📄 {{ __('messages.paper_size') }}:</label>
                    <select id="paperSizeSelect" class="size-select">
                        <option value="58mm" {{ $paperSize === '58mm' ? 'selected' : '' }}>{{ __('messages.po_return_print_58mm') }}</option>
                        <option value="80mm" {{ $paperSize === '80mm' ? 'selected' : '' }}>{{ __('messages.po_return_print_80mm') }}</option>
                        <option value="a5" {{ $paperSize === 'a5' ? 'selected' : '' }}>{{ __('messages.po_return_print_a5') }}</option>
                        <option value="a4" {{ $paperSize === 'a4' ? 'selected' : '' }}>{{ __('messages.po_return_print_a4') }}</option>
                    </select>
                </div>

                {{-- Quick Paper Pills --}}
                <div class="paper-pills">
                    <button type="button" id="btn-80mm" class="paper-pill {{ $paperSize === '80mm' ? 'active' : '' }}" data-paper="80mm">80mm</button>
                    <button type="button" id="btn-58mm" class="paper-pill {{ $paperSize === '58mm' ? 'active' : '' }}" data-paper="58mm">58mm</button>
                    <button type="button" id="btn-a5" class="paper-pill {{ $paperSize === 'a5' ? 'active' : '' }}" data-paper="a5">A5</button>
                    <button type="button" id="btn-a4" class="paper-pill {{ $paperSize === 'a4' ? 'active' : '' }}" data-paper="a4">A4</button>
                </div>
            </div>

            <div class="top-nav-divider"></div>

            <div class="top-nav-right">
                <label class="auto-print-wrapper" title="{{ __('messages.auto_print') }}">
                    <input type="checkbox" id="autoPrintCheck">
                    <span>{{ __('messages.auto_print') }}</span>
                </label>

                <button type="button" class="tool-btn tool-btn-print" id="btnPrint">
                    🖨️ <span>{{ __('messages.print') }}</span>
                </button>

                <button type="button" class="tool-btn tool-btn-pdf" id="btnDownloadPdf">
                    📥 <span>{{ __('messages.vouchers_save_pdf') }}</span>
                </button>

                <button type="button" class="tool-btn tool-btn-share-jpg" id="btnShareJpg" title="{{ __('messages.vouchers_copy_jpg') }}">
                    🖼️ <span>{{ __('messages.vouchers_share_jpg') }}</span>
                </button>

                <button type="button" class="tool-btn tool-btn-close" id="btnClose">
                    ✕ <span>{{ __('messages.po_return_close') }}</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ── SECTION 2: Printable Document Stage ── --}}
    <div class="preview-stage" id="previewStage">
        <div id="voucherPaper" class="voucher-paper paper-{{ $paperSize }}">

            {{-- Store Branding Header --}}
            <div class="store-header">
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $store->name }}" class="store-logo" />
                @endif
                <h1 class="header-title">{{ $headerTitle }}</h1>
                @if ($headerSubtitle)
                    <p class="store-sub">{{ $headerSubtitle }}</p>
                @endif
                @if ($address)
                    <p class="store-sub">{{ $address }}</p>
                @endif
                @if ($phone)
                    <p class="store-sub font-mono">Tel: {{ $phone }}</p>
                @endif
                <div class="voucher-type-badge">
                    {{ __('messages.sales_return_slip_title') ?? __('messages.returns_title') }}
                </div>
            </div>

            <hr class="divider-dashed" />

            {{-- Voucher Metadata Grid --}}
            <div class="meta-grid">
                <div class="meta-row">
                    <span class="meta-label">{{ __('messages.return_number') }}:</span>
                    <span class="meta-val font-mono font-bold">{{ $return->refund_number }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">{{ __('messages.date') }}:</span>
                    <span class="meta-val font-mono">{{ $return->posted_at?->format('d M Y, H:i') ?? $return->created_at->format('d M Y, H:i') }}</span>
                </div>
                @if ($return->sale)
                    <div class="meta-row">
                        <span class="meta-label">{{ __('messages.sale_receipt') }}:</span>
                        <span class="meta-val font-mono">{{ $return->sale->receipt_number }}</span>
                    </div>
                @endif
                @if ($return->customer)
                    <div class="meta-row">
                        <span class="meta-label">{{ __('messages.customer') }}:</span>
                        <span class="meta-val">{{ $return->customer->name }}</span>
                    </div>
                    @if ($return->customer->phone)
                        <div class="meta-row">
                            <span class="meta-label">Tel:</span>
                            <span class="meta-val font-mono">{{ $return->customer->phone }}</span>
                        </div>
                    @endif
                @endif
                @if ($return->cashier)
                    <div class="meta-row">
                        <span class="meta-label">{{ __('messages.cashier') }}:</span>
                        <span class="meta-val">{{ $return->cashier->name }}</span>
                    </div>
                @endif
            </div>

            <hr class="divider-solid" />

            {{-- Line Items Table --}}
            <table class="items-table">
                <thead>
                    <tr>
                        <th class="text-left" style="width: 48%;">{{ __('messages.product') }}</th>
                        <th class="text-right" style="width: 16%;">{{ __('messages.quantity') }}</th>
                        <th class="text-right" style="width: 18%;">{{ __('messages.unit_price') }}</th>
                        <th class="text-right" style="width: 18%;">{{ __('messages.subtotal') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($return->items as $item)
                        <tr>
                            <td>
                                <div class="font-bold">
                                    {{ $item->product_name }}
                                </div>
                                <div class="font-mono" style="font-size: 0.85em; color: #64748b;">
                                    {{ $item->sku }}
                                </div>
                            </td>
                            <td class="text-right font-mono font-bold">
                                {{ format_quantity($item->quantity, $store) }}
                            </td>
                            <td class="text-right font-mono">
                                {{ format_currency($item->unit_price, $store) }}
                            </td>
                            <td class="text-right font-mono font-bold">
                                {{ format_currency($item->line_total, $store) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Refund Payments breakdown --}}
            @if($return->payments->isNotEmpty())
                <div class="payments-box">
                    <div class="font-bold" style="margin-bottom: 2px; color: #475569;">{{ __('messages.refund_method') }}:</div>
                    @foreach($return->payments as $payment)
                        <div class="payment-line">
                            <span>
                                {{ $payment->method === 'cash' ? __('messages.cash_refund') : __('messages.credit_refund') }}
                            </span>
                            <span class="font-mono font-bold">
                                {{ format_currency($payment->amount, $store) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Grand Totals Box --}}
            <div class="total-box">
                <div class="total-row">
                    <span>{{ __('messages.refund_total') }}:</span>
                    <span class="font-mono">{{ format_currency($return->total, $store) }}</span>
                </div>
            </div>

            {{-- Notes (if specified) --}}
            @if ($return->notes)
                <div class="reason-box">
                    <span class="font-bold">{{ __('messages.notes') }}:</span>
                    <span>{{ $return->notes }}</span>
                </div>
            @endif

            {{-- Signatures --}}
            <div class="signatures">
                <div class="sig-block">
                    {{ __('messages.sales_return_customer_sign') ?? __('messages.customer') }}
                </div>
                <div class="sig-block">
                    {{ __('messages.sales_return_cashier_sign') ?? __('messages.cashier') }}
                </div>
            </div>

            {{-- Footer Print Timestamp --}}
            <div class="text-center" style="margin-top: 18px; font-size: 0.8em; color: #94a3b8;">
                Printed on {{ now()->format('d M Y, H:i') }} · {{ $store->name }}
            </div>
        </div>
    </div>

    {{-- CSP-Compliant Event Handlers & Layout Switching Script --}}
    <script nonce="{{ $cspNonce ?? '' }}">
        var currentPaperSize = '{{ $paperSize }}';
        var backUrl = @js($backUrl);
        var refundNumber = @js($return->refund_number);

        var pdfDimensions = {
            '58mm': { unit: 'mm', format: [58, 140], orientation: 'portrait' },
            '80mm': { unit: 'mm', format: [80, 180], orientation: 'portrait' },
            'a5': { unit: 'mm', format: [148, 210], orientation: 'portrait' },
            'a4': { unit: 'mm', format: [210, 297], orientation: 'portrait' }
        };

        var pdfConfig = {
            margin: [0, 0, 0, 0],
            filename: 'Sales_Return_' + refundNumber + '.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true, logging: false },
            jsPDF: pdfDimensions[currentPaperSize] || pdfDimensions['80mm']
        };

        function showToast(message) {
            var toast = document.getElementById('printToast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'printToast';
                toast.className = 'print-toast no-print';
                document.body.appendChild(toast);
            }
            toast.innerHTML = '📄 ' + message;
            toast.classList.add('show');
            clearTimeout(window._toastTimer);
            window._toastTimer = setTimeout(function() {
                toast.classList.remove('show');
            }, 2500);
        }

        function updatePreviewScale() {
            var stage = document.getElementById('previewStage');
            var sheet = document.getElementById('voucherPaper');
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

        function setPaper(size) {
            if (['58mm', '80mm', 'a5', 'a4'].indexOf(size) === -1) {
                size = '80mm';
            }
            currentPaperSize = size;

            var paper = document.getElementById('voucherPaper');
            if (paper) {
                paper.className = 'voucher-paper paper-' + size;
            }

            // Sync select dropdown
            var select = document.getElementById('paperSizeSelect');
            if (select && select.value !== size) {
                select.value = size;
            }

            // Sync pill buttons
            var pills = document.querySelectorAll('.paper-pill');
            pills.forEach(function(pill) {
                if (pill.getAttribute('data-paper') === size) {
                    pill.classList.add('active');
                } else {
                    pill.classList.remove('active');
                }
            });

            // Update dynamic @page CSS
            var pageStyle = document.getElementById('dynamicPageStyle');
            if (pageStyle) {
                if (size === '58mm') {
                    pageStyle.innerHTML = '@page { size: 58mm auto; margin: 0; }';
                    pdfConfig.jsPDF = pdfDimensions['58mm'];
                } else if (size === '80mm') {
                    pageStyle.innerHTML = '@page { size: 80mm auto; margin: 0; }';
                    pdfConfig.jsPDF = pdfDimensions['80mm'];
                } else if (size === 'a5') {
                    pageStyle.innerHTML = '@page { size: 148mm 210mm; margin: 0; }';
                    pdfConfig.jsPDF = pdfDimensions['a5'];
                } else if (size === 'a4') {
                    pageStyle.innerHTML = '@page { size: 210mm 297mm; margin: 0; }';
                    pdfConfig.jsPDF = pdfDimensions['a4'];
                }
            }

            // Save preference
            try {
                localStorage.setItem('sales_return_paper_pref', size);
            } catch (e) {}

            // Sync URL parameter silently without full reload
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
            var originalHtml = btn ? btn.innerHTML : '';
            var element = document.getElementById('voucherPaper');
            var stage = document.getElementById('previewStage');

            if (element) element.style.transform = 'none';
            if (stage) stage.style.height = 'auto';

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
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '⏳ <span>Generating...</span>';
                }
                try {
                    await html2pdf().set(pdfConfig).from(element).save();
                    showToast('PDF Downloaded');
                } catch (err) {
                    console.error('PDF generation error:', err);
                    alert('PDF generation error: ' + (err.message || err));
                } finally {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = originalHtml;
                    }
                    updatePreviewScale();
                }
            } else {
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

        async function shareJpgDirectly() {
            var element = document.getElementById('voucherPaper');
            var stage = document.getElementById('previewStage');
            var btn = document.getElementById('btnShareJpg');
            var originalHtml = btn ? btn.innerHTML : '';

            if (element) element.style.transform = 'none';
            if (stage) stage.style.height = 'auto';

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '⏳ <span>Generating...</span>';
            }

            try {
                if (!window.html2pdf || !element) {
                    window.print();
                    return;
                }

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

                var dataUri = canvas.toDataURL('image/png');
                var blob = dataUriToBlob(dataUri);
                var fileName = 'Sales_Return_' + refundNumber + '.png';
                var file = new File([blob], fileName, { type: 'image/png' });

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
                                title: 'Return #' + refundNumber,
                                text: 'Sales Return #' + refundNumber + ' - ' + @js($store->name),
                                files: [file]
                            });
                            return;
                        } catch (shareErr) {
                            if (shareErr.name === 'AbortError') return;
                        }
                    }

                    downloadBlob(blob, fileName);
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
                    btn.innerHTML = originalHtml;
                }
            }
        }

        // Attach event listeners after DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Restore user preference if available
            var savedPref = localStorage.getItem('sales_return_paper_pref');
            if (savedPref && ['58mm', '80mm', 'a5', 'a4'].indexOf(savedPref) !== -1 && !new URL(window.location.href).searchParams.get('paper_size')) {
                setPaper(savedPref);
            } else {
                updatePreviewScale();
            }

            // Bind Print Button
            var printBtn = document.getElementById('btnPrint');
            if (printBtn) {
                printBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.print();
                });
            }

            // Bind PDF Button
            var pdfBtn = document.getElementById('btnDownloadPdf');
            if (pdfBtn) {
                pdfBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    downloadPdf();
                });
            }

            // Bind Share JPG Button
            var shareJpgBtn = document.getElementById('btnShareJpg');
            if (shareJpgBtn) {
                shareJpgBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    shareJpgDirectly();
                });
            }

            // Bind Close Button
            var closeBtn = document.getElementById('btnClose');
            if (closeBtn) {
                closeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (window.opener || window.history.length > 1) {
                        window.close();
                    } else {
                        window.location.href = backUrl;
                    }
                });
            }

            // Bind Paper Dropdown
            var paperSelect = document.getElementById('paperSizeSelect');
            if (paperSelect) {
                paperSelect.addEventListener('change', function() {
                    setPaper(this.value);
                });
            }

            // Bind Paper Pills
            var pills = document.querySelectorAll('.paper-pill');
            pills.forEach(function(pill) {
                pill.addEventListener('click', function() {
                    var targetPaper = this.getAttribute('data-paper');
                    if (targetPaper) {
                        setPaper(targetPaper);
                    }
                });
            });

            // Bind Auto Print Checkbox
            var autoPrint = document.getElementById('autoPrintCheck');
            if (autoPrint) {
                var autoPref = localStorage.getItem('sales_return_auto_print');
                var urlAuto = new URL(window.location.href).searchParams.get('auto_print');
                if (urlAuto === '1' || autoPref === 'true') {
                    autoPrint.checked = true;
                    setTimeout(function() {
                        window.print();
                    }, 400);
                }
                autoPrint.addEventListener('change', function() {
                    localStorage.setItem('sales_return_auto_print', this.checked ? 'true' : 'false');
                });
            }

            // Window resize handler for scaling
            window.addEventListener('resize', updatePreviewScale);
        });
    </script>
</body>
</html>
