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
            @page { size: A5 portrait; margin: 8mm; }
        @elseif ($isA4)
            @page { size: A4 portrait; margin: 10mm; }
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
        .tool-btn-share {
            background: #059669;
            color: #fff;
        }
        .tool-btn-share:hover {
            background: #047857;
        }
        .tool-btn-viber {
            background: #7360f2;
            color: #fff;
            box-shadow: 0 2px 6px rgba(115, 96, 242, .3);
        }
        .tool-btn-viber:hover {
            background: #5f4bd8;
        }

        /* Paper Size Switcher Pills */
        .size-pills {
            display: inline-flex;
            align-items: center;
            background: #1e293b;
            border-radius: 8px;
            padding: 2.5px;
            gap: 2.5px;
            border: 1px solid #334155;
        }
        .size-pills-label {
            font-size: 10px;
            font-weight: 800;
            color: #94a3b8;
            padding: 0 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .size-pill {
            padding: 4px 9px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 800;
            color: #94a3b8;
            background: transparent;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .size-pill:hover {
            color: #f8fafc;
            background: rgba(255, 255, 255, 0.08);
        }
        .size-pill.active {
            background: #0284c7;
            color: #fff;
            box-shadow: 0 2px 6px rgba(2, 132, 199, 0.4);
        }

        /* ---- Thermal & Document Receipt Container ---- */
        .receipt {
            max-width: 100%;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(2, 6, 23, .08);
            transition: width 0.2s ease, padding 0.2s ease;
        }

        .receipt.size-58mm {
            width: 260px;
            padding: 14px 10px;
            font-size: 11px;
        }
        .receipt.size-80mm {
            width: 340px;
            padding: 20px 16px;
            font-size: 12.5px;
        }
        .receipt.size-a5 {
            width: 520px;
            padding: 26px 24px;
            font-size: 13px;
        }
        .receipt.size-a4 {
            width: 720px;
            padding: 34px 30px;
            font-size: 14px;
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

        @media screen and (max-width: 760px) {
            .receipt.size-a4, .receipt.size-a5 {
                width: 100%;
                padding: 16px 12px;
            }
        }

        @media print {
            body { background: #fff; padding: 0; }
            .receipt {
                margin: 0 auto;
                box-shadow: none;
                border-radius: 0;
            }
            .receipt.size-58mm {
                width: 100%;
                max-width: 58mm;
                padding: 4px 6px;
                font-size: 11px;
            }
            .receipt.size-80mm {
                width: 100%;
                max-width: 80mm;
                padding: 6px 10px;
                font-size: 12.5px;
            }
            .receipt.size-a5 {
                width: 100%;
                max-width: 148mm;
                padding: 6mm 8mm;
                font-size: 12.5px;
            }
            .receipt.size-a4 {
                width: 100%;
                max-width: 210mm;
                padding: 8mm 12mm;
                font-size: 13.5px;
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
            margin: 10px auto 6px auto;
            text-align: center;
            padding: 6px;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
        }
        .qr-box {
            width: 72px;
            height: 72px;
            margin: 0 auto 4px auto;
            border: 1px solid #0f172a;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            font-weight: 800;
        }
        .qr-label { font-size: 10px; font-weight: 700; color: #334155; }

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

                {{-- Paper Size Switcher Pills --}}
                <div class="size-pills">
                    <span class="size-pills-label">{{ __('messages.paper_size') }}:</span>
                    <button type="button" class="size-pill {{ $paperSize === '58mm' ? 'active' : '' }}" data-size="58mm" onclick="setPaperSize('58mm')" title="58mm POS Slip">
                        58mm
                    </button>
                    <button type="button" class="size-pill {{ $paperSize === '80mm' ? 'active' : '' }}" data-size="80mm" onclick="setPaperSize('80mm')" title="80mm Thermal Receipt">
                        80mm
                    </button>
                    <button type="button" class="size-pill {{ $paperSize === 'a5' ? 'active' : '' }}" data-size="a5" onclick="setPaperSize('a5')" title="A5 Half Sheet">
                        A5
                    </button>
                    <button type="button" class="size-pill {{ $paperSize === 'a4' ? 'active' : '' }}" data-size="a4" onclick="setPaperSize('a4')" title="A4 Full Sheet">
                        A4
                    </button>
                </div>
            </div>

            <div class="top-nav-divider"></div>

            <div class="top-nav-right">
                <button type="button" class="tool-btn tool-btn-print" onclick="window.print()">
                    🖨️ <span>{{ __('messages.print') }}</span>
                </button>
                <button type="button" class="tool-btn tool-btn-pdf" onclick="downloadPdf()">
                    📥 <span>{{ __('messages.export_pdf') ?? 'PDF' }}</span>
                </button>
                <button type="button" id="btnSharePdf" class="tool-btn tool-btn-share" onclick="sharePdfDirectly()">
                    📤 <span>{{ __('messages.share_pdf') ?? 'Share' }}</span>
                </button>
                <button type="button" class="tool-btn tool-btn-viber" onclick="shareToViber()" title="{{ __('messages.share_via_viber') }}">
                    <svg class="w-3.5 h-3.5 inline-block -mt-0.5 fill-current" viewBox="0 0 24 24" style="width:13px;height:13px;"><path d="{{ \App\Support\BrandIconPath::get('viber') }}"/></svg>
                    <span>Viber</span>
                </button>
            </div>
        </div>
    </div>

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
            <div class="void-banner">*** {{ __('messages.voucher_watermark_void') ?? 'VOID / CANCELLED' }} ***</div>
        @endif

        <p class="receipt-no">Receipt <span>#{{ $sale->receipt_number }}</span></p>
        <hr class="rule">

        <div class="meta-row"><span>Date</span><b>{{ $sale->posted_at?->format('d M Y, H:i') }}</b></div>
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
                    <th>{{ __('messages.reports_items') ?? 'ပစ္စည်း' }}</th>
                    <th class="amt">{{ __('messages.amount') ?? 'သင့်ငွေ' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sale->items as $item)
                    <tr>
                        <td>
                            {{ $item->product_name }}
                            @if ($item->original_unit_price !== null && (float) $item->original_unit_price != (float) $item->unit_price)
                                <div class="qty-price">{{ rtrim(rtrim($item->quantity, '0'), '.') }} × <s>{{ format_currency((float) $item->original_unit_price, $store) }}</s> {{ format_currency((float) $item->unit_price, $store) }} ✏️</div>
                            @else
                                <div class="qty-price">{{ rtrim(rtrim($item->quantity, '0'), '.') }} × {{ format_currency((float) $item->unit_price, $store) }}</div>
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
                <span>{{ __('messages.total') ?? 'စုစုပေါင်း' }}</span>
                <span>{{ format_currency((float) $sale->total, $store) }}</span>
            </div>
            @if ($showTaxBreakdown && (float) $sale->tax > 0 && $sale->tax_type === 'inclusive')
                <div class="total-row" style="font-size:10px;color:#64748b;font-style:italic;">
                    <span>({{ __('messages.commercial_tax') }} Included)</span>
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
                        <span>{{ __('messages.change') ?? 'ပြန်အမ်းငွေ' }}</span>
                        <span class="change">− {{ format_currency((float) $payment->change_given, $store) }}</span>
                    </div>
                @endif
            @endforeach
            @if ((float) $balanceDue > 0)
                <div class="total-row" style="color:#d97706;font-weight:700;">
                    <span>Balance due ({{ __('messages.balance_due') }})</span>
                    <span>{{ format_currency((float) $balanceDue, $store) }}</span>
                </div>
            @endif
        </div>

        @if ($showQr)
            <div class="qr-section">
                @php
                    $templateQr = ($tmpl instanceof \App\Models\VoucherTemplate) ? $tmpl->qrUrl() : null;
                @endphp
                @if ($templateQr)
                    <img src="{{ $templateQr }}" alt="QR Pay" class="qr-image" style="max-height:80px;max-width:80px;margin:0 auto 4px auto;display:block;border:1px solid #cbd5e1;border-radius:6px;padding:2px;" />
                @else
                    <div class="qr-box">
                        <span>📱 QR PAY</span>
                    </div>
                @endif
                <div class="qr-label">{{ $qrLabel }}</div>
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



    <script nonce="{{ $cspNonce }}">
        var currentPaperSize = '{{ $paperSize }}';
        var pdfConfig = {
            margin: {{ $isA4 || $isA5 ? '[8, 8, 8, 8]' : '[3, 2, 3, 2]' }},
            filename: 'Receipt_{{ $sale->receipt_number }}_{{ $store->slug }}.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true, logging: false },
            jsPDF: {
                unit: 'mm',
                format: {{ $is58 ? '[58, 180]' : ($is80 ? '[80, 220]' : ($isA5 ? "'a5'" : "'a4'")) }},
                orientation: 'portrait'
            }
        };

        function setPaperSize(size) {
            currentPaperSize = size;
            var element = document.getElementById('receiptContent');
            if (element) {
                element.className = 'receipt size-' + size;
            }

            // Update active state on all size pills
            document.querySelectorAll('.size-pill').forEach(function(btn) {
                if (btn.getAttribute('data-size') === size) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });

            // Update dynamic @page CSS and PDF format
            var pageStyle = document.getElementById('dynamicPageStyle');
            if (pageStyle) {
                if (size === '58mm') {
                    pageStyle.innerHTML = '@page { size: 58mm auto; margin: 0; }';
                    pdfConfig.jsPDF = { unit: 'mm', format: [58, 180], orientation: 'portrait' };
                    pdfConfig.margin = [3, 2, 3, 2];
                } else if (size === '80mm') {
                    pageStyle.innerHTML = '@page { size: 80mm auto; margin: 0; }';
                    pdfConfig.jsPDF = { unit: 'mm', format: [80, 220], orientation: 'portrait' };
                    pdfConfig.margin = [3, 2, 3, 2];
                } else if (size === 'a5') {
                    pageStyle.innerHTML = '@page { size: A5 portrait; margin: 8mm; }';
                    pdfConfig.jsPDF = { unit: 'mm', format: 'a5', orientation: 'portrait' };
                    pdfConfig.margin = [8, 8, 8, 8];
                } else if (size === 'a4') {
                    pageStyle.innerHTML = '@page { size: A4 portrait; margin: 10mm; }';
                    pdfConfig.jsPDF = { unit: 'mm', format: 'a4', orientation: 'portrait' };
                    pdfConfig.margin = [10, 10, 10, 10];
                }
            }

            // Sync URL parameter silently so reloads remember the chosen size
            try {
                var url = new URL(window.location.href);
                url.searchParams.set('paper_size', size);
                window.history.replaceState({}, '', url.toString());
            } catch (e) {}
        }

        function downloadPdf() {
            var element = document.getElementById('receiptContent');
            if (window.html2pdf) {
                html2pdf().set(pdfConfig).from(element).save();
            } else {
                var oldTitle = document.title;
                document.title = pdfConfig.filename;
                window.print();
                setTimeout(function() { document.title = oldTitle; }, 1000);
            }
        }

        async function sharePdfDirectly() {
            var element = document.getElementById('receiptContent');
            var btn = document.getElementById('btnSharePdf');
            var originalText = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '⏳ Generating PDF...';
            }

            try {
                if (window.html2pdf) {
                    var worker = html2pdf().set(pdfConfig).from(element).toPdf();
                    var pdfBlob = await worker.output('blob');
                    var pdfFile = new File([pdfBlob], pdfConfig.filename, { type: 'application/pdf' });

                    if (navigator.canShare && navigator.canShare({ files: [pdfFile] })) {
                        await navigator.share({
                            title: 'Receipt #{{ $sale->receipt_number }}',
                            text: 'Receipt from {{ $headerTitle }} #{{ $sale->receipt_number }}',
                            files: [pdfFile]
                        });
                    } else {
                        // On desktop or devices without WebShare files support: download PDF and trigger Viber forward
                        html2pdf().set(pdfConfig).from(element).save();
                        setTimeout(function() {
                            shareToViber();
                        }, 600);
                    }
                } else {
                    window.print();
                }
            } catch (err) {
                if (err.name !== 'AbortError') {
                    console.error('Share PDF error:', err);
                    downloadPdf();
                }
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            }
        }

        function shareToViber() {
            var lines = [];
            lines.push("🧾 ပြေစာ #{{ $sale->receipt_number }}");
            lines.push("🏪 {{ $headerTitle }}");
            lines.push("📅 ရက်စွဲ: {{ $sale->posted_at?->format('d/m/Y, H:i') }}");
            @if ($showCashier && $sale->cashier)
                lines.push("👤 အရောင်းဝန်ထမ်း: {{ $sale->cashier->name }}");
            @endif
            @if ($showCustomer && $sale->customer)
                lines.push("👤 ဝယ်ယူသူ: {{ $sale->customer->name }}");
            @endif
            lines.push("--------------------------------");
            @foreach ($sale->items as $item)
                lines.push("• {{ addslashes($item->product_name) }}: {{ format_quantity($item->quantity, $store) }} x {{ format_currency((float)$item->unit_price, $store) }} = {{ format_currency((float)$item->line_total, $store) }}");
            @endforeach
            lines.push("--------------------------------");
            lines.push("💵 စုစုပေါင်း: {{ format_currency((float)$sale->total, $store) }}");
            @if ((float)$balanceDue > 0)
                lines.push("⚠️ ကျန်ငွေ: {{ format_currency((float)$balanceDue, $store) }}");
            @endif
            lines.push("\n{{ $footerGreeting }}");
            @if ($phone)
                lines.push("📞 ဆက်သွယ်ရန်: {{ $phone }}");
            @endif

            var message = lines.join("\n");
            var viberUrl = "viber://forward?text=" + encodeURIComponent(message);
            window.location.href = viberUrl;
        }

        // Expose functions globally
        window.setPaperSize = setPaperSize;
        window.downloadPdf = downloadPdf;
        window.sharePdfDirectly = sharePdfDirectly;
        window.shareToViber = shareToViber;

        // CSP-compliant DOM Event Listeners
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.size-pill').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var size = btn.getAttribute('data-size');
                    if (size) {
                        setPaperSize(size);
                    }
                });
            });

            var printBtn = document.querySelector('.tool-btn-print');
            if (printBtn) {
                printBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.print();
                });
            }

            var pdfBtn = document.querySelector('.tool-btn-pdf');
            if (pdfBtn) {
                pdfBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    downloadPdf();
                });
            }

            var shareBtn = document.getElementById('btnSharePdf');
            if (shareBtn) {
                shareBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    sharePdfDirectly();
                });
            }

            var viberBtn = document.querySelector('.tool-btn-viber');
            if (viberBtn) {
                viberBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    shareToViber();
                });
            }
        });
    </script>
</body>
</html>
