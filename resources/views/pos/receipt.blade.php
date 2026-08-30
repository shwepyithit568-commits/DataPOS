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
    $paperSize = data_get($tmpl, 'paper_size', '80mm');
    $fontSize = data_get($tmpl, 'font_size', 'medium');
    $is58 = $paperSize === '58mm';
    $showLogo = (bool) data_get($tmpl, 'show_logo', true);
    $showQr = (bool) data_get($tmpl, 'show_qr', false);
    $showBarcode = (bool) data_get($tmpl, 'show_barcode', false);
    $showCustomer = (bool) data_get($tmpl, 'show_customer_info', true);
    $showCashier = (bool) data_get($tmpl, 'show_cashier_name', true);
    $headerTitle = data_get($tmpl, 'header_title') ?: $store->name;
    $headerSubtitle = data_get($tmpl, 'header_subtitle');
    $address = data_get($tmpl, 'address') ?: ($store->address ?? null);
    $phone = data_get($tmpl, 'phone') ?: ($store->viber_number ? 'Viber: ' . $store->viber_number : ($store->phone ?? null));
    $footerGreeting = data_get($tmpl, 'footer_greeting') ?: __('messages.thank_you_purchase');
    $footerPolicy = data_get($tmpl, 'footer_policy');
    $qrLabel = data_get($tmpl, 'qr_label') ?: 'Scan to pay with KPay / Wave';

    $logoUrl = null;
    if ($showLogo) {
        $logoPath = $store->setting?->logo();
        $logoUrl = $logoPath ? asset('storage/' . $logoPath) : null;
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

        @page {
            @if ($is58)
                size: 58mm auto;
                margin: 0;
            @else
                size: 80mm auto;
                margin: 0;
            @endif
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Noto Sans Myanmar', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #0f172a;
            background: #f1f5f9;
            font-size: {{ $is58 || $fontSize === 'small' ? '11px' : ($fontSize === 'large' ? '14px' : '12.5px') }};
            line-height: 1.4;
            padding: 16px;
        }

        /* ---- Thermal Receipt Container ---- */
        .receipt {
            width: {{ $is58 ? '260px' : '340px' }};
            max-width: 100%;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            padding: {{ $is58 ? '14px 10px' : '20px 16px' }};
            box-shadow: 0 4px 20px rgba(2, 6, 23, .08);
        }

        @media print {
            body { background: #fff; padding: 0; }
            .receipt { margin: 0; box-shadow: none; border-radius: 0; width: 100%; max-width: 100%; padding: 4px 6px; }
            .no-print { display: none !important; }
        }

        .store-header { text-align: center; margin-bottom: 6px; }
        .store-logo { max-height: 44px; max-width: 120px; object-fit: contain; margin: 0 auto 6px auto; display: block; }
        .store-name { font-size: {{ $is58 ? '14px' : '16px' }}; font-weight: 800; line-height: 1.2; }
        .store-sub { font-size: 11px; color: #475569; margin-top: 2px; }
        .store-meta { font-size: 10.5px; color: #64748b; margin-top: 2px; line-height: 1.3; }

        .rule { border: none; border-top: 1px dashed #cbd5e1; margin: 8px 0; }
        .solid-rule { border: none; border-top: 1.5px solid #0f172a; margin: 8px 0; }

        .receipt-no { text-align: center; font-size: {{ $is58 ? '13px' : '14px' }}; font-weight: 800; letter-spacing: .3px; }
        .receipt-no span { color: #0284c7; }

        .meta-row { display: flex; justify-content: space-between; font-size: 11px; color: #334155; padding: 1.5px 0; }
        .meta-row b { color: #0f172a; font-weight: 700; }

        table.items { width: 100%; border-collapse: collapse; font-size: inherit; margin: 4px 0; }
        table.items th {
            text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: .4px;
            color: #64748b; border-bottom: 1px solid #e2e8f0; padding: 3px 0;
        }
        table.items td { padding: 4px 0; vertical-align: top; }
        table.items td.amt, table.items th.amt { text-align: right; white-space: nowrap; }
        .qty-price { font-size: 10.5px; color: #64748b; }

        .totals { margin-top: 4px; }
        .total-row { display: flex; justify-content: space-between; padding: 2px 0; font-size: inherit; }
        .total-row.grand { font-size: {{ $is58 ? '13px' : '15px' }}; font-weight: 800; border-top: 1px solid #e2e8f0; margin-top: 4px; padding-top: 4px; }
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

        /* ---- Screen-only Action Toolbar ---- */
        .toolbar {
            width: {{ $is58 ? '280px' : '360px' }};
            max-width: 100%;
            margin: 14px auto 0 auto;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }
        .toolbar button, .toolbar a {
            padding: 9px 8px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            transition: transform 0.1s ease, opacity 0.1s ease;
        }
        .toolbar button:active, .toolbar a:active { transform: scale(0.97); }
        .toolbar .full-col { grid-column: span 2; }
        .btn-print { background: #0284c7; color: #fff; box-shadow: 0 2px 8px rgba(2, 132, 199, .25); }
        .btn-pdf { background: #475569; color: #fff; box-shadow: 0 2px 8px rgba(71, 85, 105, .25); }
        .btn-share-pdf { background: #059669; color: #fff; box-shadow: 0 2px 8px rgba(5, 150, 105, .25); }
        .btn-viber { background: #7360f2; color: #fff; box-shadow: 0 2px 8px rgba(115, 96, 242, .25); }
        .btn-back { background: #e2e8f0; color: #334155; }
    </style>
    <script src="{{ asset('vendor/html2pdf/html2pdf.bundle.min.js') }}"></script>
</head>
<body>
    <div id="receiptContent" class="receipt">
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
        </div>

        <hr class="rule">

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
                                <div class="qty-price">{{ rtrim(rtrim($item->quantity, '0'), '.') }} × <s>Ks {{ number_format((float) $item->original_unit_price) }}</s> Ks {{ number_format((float) $item->unit_price )}} ✏️</div>
                            @else
                                <div class="qty-price">{{ rtrim(rtrim($item->quantity, '0'), '.') }} × Ks {{ number_format((float) $item->unit_price) }}</div>
                            @endif
                        </td>
                        <td class="amt">Ks {{ number_format((float) $item->line_total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <hr class="rule">

        <div class="totals">
            @php $balanceDue = $sale->payments->firstWhere('method', 'credit')?->amount ?? '0'; @endphp
            @foreach ($sale->payments as $payment)
                <div class="total-row">
                    <span>{{ $payment->method === 'credit' ? __('messages.payment_credit') : ucfirst($payment->method) }}</span>
                    <span>Ks {{ number_format((float) $payment->amount) }}</span>
                </div>
                @if ((float) $payment->change_given > 0)
                    <div class="total-row">
                        <span>{{ __('messages.change') ?? 'ပြန်အမ်းငွေ' }}</span>
                        <span class="change">− Ks {{ number_format((float) $payment->change_given) }}</span>
                    </div>
                @endif
            @endforeach
            <div class="total-row grand">
                <span>{{ __('messages.total') ?? 'စုစုပေါင်း' }}</span>
                <span>Ks {{ number_format((float) $sale->total) }}</span>
            </div>
            @if ((float) $balanceDue > 0)
                <div class="total-row" style="color:#d97706;font-weight:700;">
                    <span>Balance due ({{ __('messages.balance_due') }})</span>
                    <span>Ks {{ number_format((float) $balanceDue) }}</span>
                </div>
            @endif
        </div>

        @if ($showQr)
            <div class="qr-section">
                <div class="qr-box">
                    <span>📱 QR PAY</span>
                </div>
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
            <p class="reprint-note">COPY — REPRINT #{{ $printCount }}</p>
        @endif

        <div class="footer">
            <p>{{ $footerGreeting }}</p>
            @if ($footerPolicy)
                <p class="policy">{{ $footerPolicy }}</p>
            @endif
            <p style="font-size:9px;color:#cbd5e1;margin-top:4px;">{{ $store->name }} · {{ now()->format('Y') }}</p>
        </div>
    </div>

    {{-- Screen Action Toolbar with Direct PDF Share, Print, Download, Viber and Back --}}
    <div class="toolbar no-print">
        <button type="button" class="btn-print" onclick="window.print()">🖨️ {{ __('messages.print') }}</button>
        <button type="button" class="btn-pdf" onclick="downloadPdf()">📥 {{ __('messages.export_pdf') ?? 'Download PDF' }}</button>
        <button type="button" id="btnSharePdf" class="btn-share-pdf full-col" onclick="sharePdfDirectly()">
            📤 <strong>{{ __('messages.share_pdf') ?? 'PDF ဖိုင် Share မည်' }}</strong> (Viber / Apps)
        </button>
        <button type="button" class="btn-viber" onclick="shareToViber()">
            <svg class="w-3.5 h-3.5 inline-block -mt-0.5 fill-current" viewBox="0 0 24 24" style="width:14px;height:14px;"><path d="{{ \App\Support\BrandIconPath::get('viber') }}"/></svg>
            <span>{{ __('messages.share_via_viber') }}</span>
        </button>
        <a href="{{ url('/store/' . $store->slug . '/pos') }}" class="btn-back">← {{ __('messages.back_to_pos') }}</a>
    </div>

    <script>
        var pdfConfig = {
            margin: [3, 2, 3, 2],
            filename: 'Receipt_{{ $sale->receipt_number }}_{{ $store->slug }}.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true, logging: false },
            jsPDF: { unit: 'mm', format: {{ $is58 ? '[58, 160]' : '[80, 200]' }}, orientation: 'portrait' }
        };

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
                lines.push("• {{ $item->product_name }}: {{ rtrim(rtrim($item->quantity, '0'), '.') }} x Ks {{ number_format((float)$item->unit_price) }} = Ks {{ number_format((float)$item->line_total) }}");
            @endforeach
            lines.push("--------------------------------");
            lines.push("💵 စုစုပေါင်း: Ks {{ number_format((float)$sale->total) }}");
            @if ((float)$balanceDue > 0)
                lines.push("⚠️ ကျန်ငွေ: Ks {{ number_format((float)$balanceDue) }}");
            @endif
            lines.push("\n{{ $footerGreeting }}");
            @if ($phone)
                lines.push("📞 ဆက်သွယ်ရန်: {{ $phone }}");
            @endif

            var message = lines.join("\n");
            var viberUrl = "viber://forward?text=" + encodeURIComponent(message);
            window.location.href = viberUrl;
        }
    </script>
</body>
</html>
