@php
    $tmpl = $voucherTemplate ?? null;
    $storeName = data_get($tmpl, 'header_title') ?: $store->name;
    $storeSubtitle = data_get($tmpl, 'header_subtitle');
    $storePhone = data_get($tmpl, 'phone') ?: ($store->setting?->phone ?? $store->phone);
    $storeAddress = data_get($tmpl, 'address') ?: ($store->setting?->address ?? $store->address);
    $showLogo = (bool) data_get($tmpl, 'show_logo', true);
    $templateLogo = ($tmpl instanceof \App\Models\VoucherTemplate) ? $tmpl->logoUrl() : null;
    $logoUrl = $showLogo ? ($templateLogo ?? ($store->setting?->adminLogo() ? asset('storage/' . $store->setting->adminLogo()) : null)) : null;
    $termsConditions = data_get($tmpl, 'footer_policy') ?: ($warranty->terms_conditions ?: '၁။ ရေဝင်ခြင်း၊ ပြုတ်ကျခြင်း၊ မျက်နှာပြင်ကွဲအက်ခြင်းနှင့် တရားမဝင် ဆော့ဝဲလ်သွင်းထားခြင်းများအတွက် အာမခံ အကျုံးမဝင်ပါ။ ၂။ အာမခံရယူရန် ဤလက်မှတ်နှင့် စက်၏ Serial/IMEI တူညီရမည် ဖြစ်ပါသည်။');

    $paperSize = request('paper_size') ?: ($paperSize ?? 'a5');
    if (!in_array($paperSize, ['a5', 'a4'], true)) {
        $paperSize = 'a5';
    }

    $myanmarFontUrl = null;
    try {
        $myanmarFontUrl = \Illuminate\Support\Facades\Vite::asset('resources/assets/fonts/NotoSansMyanmar/NotoSansMyanmar-Regular.ttf');
    } catch (\Throwable $e) {
        $myanmarFontUrl = null;
    }

    $backUrl = route('store.admin.warranty.show', ['store_slug' => $store->slug, 'warranty' => $warranty->id]);
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ __('messages.warranty_certificate_title') }} — {{ $warranty->serial_number }} — {{ $storeName }}</title>

    <script nonce="{{ $cspNonce ?? '' }}" src="/vendor/html2pdf/html2pdf.bundle.min.js"></script>

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

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Noto Sans Myanmar', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Pyidaungsu", "Myanmar3", sans-serif;
        }

        html, body {
            background-color: #f1f5f9;
            color: #0f172a;
            font-size: {{ $paperSize === 'a5' ? '11px' : '12.5px' }};
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }

        @if ($paperSize === 'a5')
        @page {
            size: 148mm 210mm;
            margin: 0;
        }
        @else
        @page {
            size: 210mm 297mm;
            margin: 0;
        }
        @endif

        @media print {
            html, body {
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
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
            }
            .cert-card {
                box-shadow: none !important;
                border-radius: 0 !important;
                margin: 0 auto !important;
                transform: none !important;
            }
            .cert-card.size-a5 {
                width: 148mm !important;
                max-width: 148mm !important;
                min-height: 210mm !important;
                padding: 7mm 9mm !important;
                border: 2.5px double #4338ca !important;
            }
            .cert-card.size-a4 {
                width: 210mm !important;
                max-width: 210mm !important;
                min-height: 297mm !important;
                padding: 12mm 15mm !important;
                border: 4px double #4338ca !important;
            }
        }

        /* Top Sticky Toolbar */
        .top-nav-bar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 8px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 8px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            color: #334155;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .btn-back:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        .doc-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            background: #ede9fe;
            color: #4338ca;
            border: 1px solid #c7d2fe;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 800;
        }

        .paper-control {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11.5px;
            font-weight: 700;
            color: #475569;
            background: #f8fafc;
            padding: 4px 8px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
        }

        .paper-control select {
            border: 1px solid #c7d2fe;
            border-radius: 6px;
            padding: 3px 6px;
            font-size: 11.5px;
            font-weight: 800;
            color: #312e81;
            background: #ffffff;
            outline: none;
            cursor: pointer;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 13px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.15s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: #4338ca;
            color: #ffffff;
            border-color: #3730a3;
        }
        .btn-primary:hover {
            background: #3730a3;
        }

        .btn-secondary {
            background: #ffffff;
            color: #334155;
            border-color: #cbd5e1;
        }
        .btn-secondary:hover {
            background: #f8fafc;
            border-color: #94a3b8;
        }

        .btn-accent {
            background: #e0e7ff;
            color: #3730a3;
            border-color: #c7d2fe;
        }
        .btn-accent:hover {
            background: #c7d2fe;
        }

        /* Responsive Preview Stage */
        .preview-stage {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 20px 10px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* Corporate Sheet Container */
        .cert-card {
            margin: 0 auto;
            background: #ffffff;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            position: relative;
            box-sizing: border-box;
            transform-origin: top center;
        }

        /* A5 Size Specification */
        .cert-card.size-a5 {
            width: 148mm;
            max-width: 148mm;
            min-height: 210mm;
            border: 3px double #4338ca;
            border-radius: 10px;
            padding: 8mm 10mm;
        }

        .cert-card.size-a5 .store-name {
            font-size: 18px;
            font-weight: 900;
        }
        .cert-card.size-a5 .cert-title {
            font-size: 14.5px;
            margin-top: 6px;
        }
        .cert-card.size-a5 .cert-sub {
            font-size: 10px;
            margin-top: 1px;
        }
        .cert-card.size-a5 .validity-banner {
            padding: 7px 12px;
            margin: 10px 0;
        }
        .cert-card.size-a5 .validity-title {
            font-size: 11.5px;
        }
        .cert-card.size-a5 .validity-dates {
            font-size: 11.5px;
        }
        .cert-card.size-a5 .grid-section {
            gap: 10px;
            margin: 10px 0;
        }
        .cert-card.size-a5 .info-box {
            padding: 9px 11px;
        }
        .cert-card.size-a5 .info-box-title {
            font-size: 10px;
            margin-bottom: 6px;
        }
        .cert-card.size-a5 .row {
            font-size: 11px;
            margin-bottom: 4px;
        }
        .cert-card.size-a5 .terms-box {
            font-size: 9.5px;
            padding: 7px 10px;
            margin-top: 8px;
            line-height: 1.45;
        }
        .cert-card.size-a5 .signatures {
            margin-top: 18px;
            padding-top: 6px;
        }
        .cert-card.size-a5 .sign-box {
            width: 150px;
        }

        /* A4 Size Specification */
        .cert-card.size-a4 {
            width: 210mm;
            max-width: 210mm;
            min-height: 297mm;
            border: 6px double #4338ca;
            border-radius: 12px;
            padding: 14mm 16mm;
        }

        .cert-card.size-a4 .store-name {
            font-size: 24px;
            font-weight: 900;
        }
        .cert-card.size-a4 .cert-title {
            font-size: 19px;
            margin-top: 14px;
        }
        .cert-card.size-a4 .cert-sub {
            font-size: 11.5px;
            margin-top: 2px;
        }
        .cert-card.size-a4 .validity-banner {
            padding: 12px 18px;
            margin: 18px 0;
        }
        .cert-card.size-a4 .validity-title {
            font-size: 13px;
        }
        .cert-card.size-a4 .validity-dates {
            font-size: 13px;
        }
        .cert-card.size-a4 .grid-section {
            gap: 16px;
            margin: 18px 0;
        }
        .cert-card.size-a4 .info-box {
            padding: 14px;
        }
        .cert-card.size-a4 .info-box-title {
            font-size: 11px;
            margin-bottom: 10px;
        }
        .cert-card.size-a4 .row {
            font-size: 12px;
            margin-bottom: 7px;
        }
        .cert-card.size-a4 .terms-box {
            font-size: 11px;
            padding: 12px;
            margin-top: 16px;
            line-height: 1.6;
        }
        .cert-card.size-a4 .signatures {
            margin-top: 36px;
            padding-top: 16px;
        }
        .cert-card.size-a4 .sign-box {
            width: 220px;
        }

        .cert-header {
            text-align: center;
            border-bottom: 1.5px solid #e2e8f0;
            padding-bottom: 10px;
        }

        .store-name {
            color: #1e1b4b;
            letter-spacing: -0.3px;
            text-transform: uppercase;
        }

        .store-info {
            font-size: 10.5px;
            color: #64748b;
            margin-top: 2px;
        }

        .cert-title {
            font-weight: 900;
            color: #4338ca;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        .cert-sub {
            color: #64748b;
            font-weight: 600;
        }

        .validity-banner {
            background-color: #e0e7ff;
            border: 1px solid #c7d2fe;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }

        .validity-title {
            font-weight: 800;
            color: #312e81;
        }

        .validity-dates {
            font-weight: 900;
            color: #1e1b4b;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            white-space: nowrap;
        }

        .grid-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        .info-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
        }

        .info-box-title {
            font-weight: 800;
            text-transform: uppercase;
            color: #4338ca;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            letter-spacing: 0.5px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 6px;
        }

        .row:last-child {
            margin-bottom: 0;
        }

        .label {
            color: #64748b;
            font-weight: 600;
        }

        .value {
            font-weight: 700;
            color: #0f172a;
            text-align: right;
        }

        .font-mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        .terms-box {
            color: #475569;
            background-color: #fafafa;
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
        }

        .signatures {
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .sign-box {
            text-align: center;
        }

        .sign-line {
            border-top: 1px dashed #94a3b8;
            margin-bottom: 4px;
        }

        /* Toast feedback */
        .print-toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #1e1b4b;
            color: #ffffff;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            display: none;
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        .print-toast.show {
            display: block;
            opacity: 1;
        }
    </style>
</head>
<body>

    {{-- Top Action Toolbar --}}
    <div class="top-nav-bar no-print">
        <div class="nav-left">
            <a href="{{ $backUrl }}" class="btn-back">
                <span>←</span>
                <span>{{ __('messages.back') ?? 'ပြန်သွားရန်' }}</span>
            </a>
            <div class="doc-badge">
                <span>🛡️</span>
                <span>{{ __('messages.warranty_certificate_title') }} #{{ $warranty->serial_number }}</span>
            </div>
            <div class="paper-control">
                <label for="paperSizeSelect">📄 {{ __('messages.vouchers_paper_size') ?? 'အရွယ်အစား' }}:</label>
                <select id="paperSizeSelect">
                    <option value="a5" {{ $paperSize === 'a5' ? 'selected' : '' }}>A5 (Half - မူလစံနှုန်း)</option>
                    <option value="a4" {{ $paperSize === 'a4' ? 'selected' : '' }}>A4 (Full Sheet)</option>
                </select>
            </div>
        </div>

        <div class="nav-actions">
            <button type="button" class="btn-action btn-secondary" id="btnShareJpg">
                <span>🖼️</span>
                <span>{{ __('messages.vouchers_copy_jpg') }}</span>
            </button>
            <button type="button" class="btn-action btn-accent" id="btnDownloadPdf">
                <span>📥</span>
                <span>{{ __('messages.vouchers_save_pdf') }}</span>
            </button>
            <button type="button" class="btn-action btn-primary" id="btnPrint">
                <span>🖨️</span>
                <span>{{ __('messages.print') }}</span>
            </button>
        </div>
    </div>

    {{-- Toast Notification --}}
    <div id="printToast" class="print-toast no-print"></div>

    {{-- Responsive Stage --}}
    <div class="preview-stage" id="previewStage">
        <div class="cert-card size-{{ $paperSize }}" id="certCard">
            {{-- Header --}}
            <div class="cert-header">
                @if ($logoUrl)
                    <div style="margin-bottom: 6px;">
                        <img src="{{ $logoUrl }}" alt="{{ $storeName }}" style="max-height: {{ $paperSize === 'a5' ? '40px' : '52px' }}; max-width: 160px; object-fit: contain; margin: 0 auto; display: block;">
                    </div>
                @endif
                <div class="store-name">{{ $storeName }}</div>
                @if ($storeSubtitle)
                    <div style="font-size: {{ $paperSize === 'a5' ? '11px' : '12.5px' }}; font-weight: 700; color: #4338ca; margin-top: 1px;">{{ $storeSubtitle }}</div>
                @endif
                @if($storePhone || $storeAddress)
                    <div class="store-info">
                        @if($storePhone) 📞 {{ $storePhone }} @endif
                        @if($storeAddress) | 📍 {{ $storeAddress }} @endif
                    </div>
                @endif
                <div class="cert-title">{{ __('messages.warranty_certificate_title') }}</div>
                <div class="cert-sub">Official Warranty & Guarantee Card (တရားဝင် အာမခံလက်မှတ်)</div>
            </div>

            {{-- Validity Period Highlight --}}
            <div class="validity-banner">
                <div>
                    <div class="validity-title">{{ __('messages.warranty_coverage') }}: {{ $warranty->warranty_duration_months }} Months ({{ __('messages.warranty_type_' . $warranty->warranty_type) }})</div>
                </div>
                <div class="validity-dates">
                    {{ $warranty->purchase_date->format('d/m/Y') }} &rarr; {{ $warranty->warranty_expiry_date->format('d/m/Y') }}
                </div>
            </div>

            {{-- Info Grid --}}
            <div class="grid-section">
                {{-- Device Details --}}
                <div class="info-box">
                    <div class="info-box-title">{{ __('messages.device_and_serial_info') }}</div>
                    <div class="row">
                        <span class="label">{{ __('messages.product_name') }}:</span>
                        <span class="value">{{ $warranty->product_name }}</span>
                    </div>
                    <div class="row">
                        <span class="label">{{ __('messages.serial_number_label') }}</span>
                        <span class="value font-mono">{{ $warranty->serial_number }}</span>
                    </div>
                    @if($warranty->imei_primary)
                        <div class="row">
                            <span class="label">{{ __('messages.primary_imei_label') }}</span>
                            <span class="value font-mono">{{ $warranty->imei_primary }}</span>
                        </div>
                    @endif
                    @if($warranty->imei_secondary)
                        <div class="row">
                            <span class="label">{{ __('messages.secondary_imei_label') }}</span>
                            <span class="value font-mono">{{ $warranty->imei_secondary }}</span>
                        </div>
                    @endif
                    @if($warranty->invoice_number)
                        <div class="row">
                            <span class="label">{{ __('messages.invoice_no_label') }}</span>
                            <span class="value font-mono">#{{ $warranty->invoice_number }}</span>
                        </div>
                    @endif
                </div>

                {{-- Customer & Policy --}}
                <div class="info-box">
                    <div class="info-box-title">{{ __('messages.customer_information') }}</div>
                    <div class="row">
                        <span class="label">{{ __('messages.customer_name') }}:</span>
                        <span class="value">{{ $warranty->customer_name ?: 'Walk-in Customer' }}</span>
                    </div>
                    <div class="row">
                        <span class="label">{{ __('messages.phone') }}:</span>
                        <span class="value font-mono">{{ $warranty->customer_phone ?: '-' }}</span>
                    </div>
                    <div class="row">
                        <span class="label">{{ __('messages.purchase_date') }}:</span>
                        <span class="value font-mono">{{ $warranty->purchase_date->format('d M Y') }}</span>
                    </div>
                    <div class="row">
                        <span class="label">{{ __('messages.warranty_expiry') }}:</span>
                        <span class="value font-mono" style="color: #4338ca;">{{ $warranty->warranty_expiry_date->format('d M Y') }}</span>
                    </div>
                </div>
            </div>

            {{-- Terms & Conditions --}}
            <div class="terms-box">
                <strong>{{ __('messages.warranty_terms_conditions') }}:</strong>
                <p style="margin-top: 3px; white-space: pre-line;">
                    {{ $termsConditions }}
                </p>
            </div>

            {{-- Signatures --}}
            <div class="signatures">
                <div class="sign-box">
                    <div class="sign-line"></div>
                    <div style="font-size: {{ $paperSize === 'a5' ? '10px' : '11px' }}; color: #64748b;">Customer Signature (ဝယ်ယူသူလက်မှတ်)</div>
                </div>
                <div class="sign-box">
                    <div class="sign-line"></div>
                    <div style="font-size: {{ $paperSize === 'a5' ? '10px' : '11px' }}; color: #64748b;">{{ __('messages.auth_store_stamp_sig') }}</div>
                </div>
            </div>
        </div>
    </div>

    <script nonce="{{ $cspNonce ?? '' }}">
        function showToast(msg) {
            var t = document.getElementById('printToast');
            if (!t) return;
            t.textContent = msg;
            t.classList.add('show');
            clearTimeout(t._timer);
            t._timer = setTimeout(function() {
                t.classList.remove('show');
            }, 3500);
        }

        function changePaperSize(size) {
            var url = new URL(window.location.href);
            url.searchParams.set('paper_size', size);
            window.location.href = url.toString();
        }

        function updatePreviewScale() {
            var stage = document.getElementById('previewStage');
            var sheet = document.getElementById('certCard');
            if (!stage || !sheet) return;

            sheet.style.transform = 'none';
            stage.style.height = 'auto';

            var stageWidth = stage.clientWidth - 24;
            var sheetWidth = sheet.offsetWidth;

            if (stageWidth > 0 && sheetWidth > 0 && stageWidth < sheetWidth) {
                var scale = stageWidth / sheetWidth;
                sheet.style.transform = 'scale(' + scale.toFixed(4) + ')';
                sheet.style.transformOrigin = 'top center';
                var scaledHeight = sheet.offsetHeight * scale;
                stage.style.height = (scaledHeight + 30) + 'px';
            }
        }

        function printCertificate() {
            window.print();
        }

        function getPdfConfig() {
            return {
                margin: 0,
                filename: 'warranty_certificate_{{ $warranty->serial_number }}_{{ $paperSize }}.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff', logging: false },
                jsPDF: { unit: 'mm', format: '{{ $paperSize }}', orientation: 'portrait' }
            };
        }

        async function downloadPdfDirectly() {
            var sheet = document.getElementById('certCard');
            var btn = document.getElementById('btnDownloadPdf');
            var originalHtml = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span>⏳</span><span>Downloading...</span>';
            }
            try {
                if (!window.html2pdf || !sheet) {
                    window.print();
                    return;
                }
                showToast('Generating PDF...');
                sheet.style.transform = 'none';
                await html2pdf().set(getPdfConfig()).from(sheet).save();
                showToast('PDF Downloaded successfully');
            } catch (err) {
                console.error('PDF generation error:', err);
                showToast('PDF generation failed, fallback to print');
                window.print();
            } finally {
                updatePreviewScale();
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            }
        }

        function dataUriToBlob(dataUri) {
            var byteString = atob(dataUri.split(',')[1]);
            var mimeString = dataUri.split(',')[0].split(':')[1].split(';')[0];
            var ab = new ArrayBuffer(byteString.length);
            var ia = new Uint8Array(ab);
            for (var i = 0; i < byteString.length; i++) {
                ia[i] = byteString.charCodeAt(i);
            }
            return new Blob([ab], { type: mimeString });
        }

        function downloadBlob(blob, filename) {
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        async function shareJpgDirectly() {
            var sheet = document.getElementById('certCard');
            var btn = document.getElementById('btnShareJpg');
            var originalHtml = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span>⏳</span><span>Rendering...</span>';
            }
            try {
                if (!window.html2pdf || !sheet) {
                    window.print();
                    return;
                }
                showToast('Rendering image...');
                sheet.style.transform = 'none';
                var worker = html2pdf().set({
                    ...getPdfConfig(),
                    image: { type: 'png', quality: 1.0 },
                    html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff', logging: false }
                }).from(sheet);

                await worker.toContainer();
                await worker.toCanvas();
                await worker.toImg();

                var dataUri = await worker.outputImg('datauristring');
                var blob = dataUriToBlob(dataUri);
                var imageFilename = 'warranty_certificate_{{ $warranty->serial_number }}_{{ $paperSize }}.png';

                var copied = false;
                if (navigator.clipboard && navigator.clipboard.write) {
                    try {
                        await navigator.clipboard.write([
                            new ClipboardItem({ 'image/png': blob })
                        ]);
                        copied = true;
                        showToast('{{ __('messages.vouchers_jpg_copied') ?? "ပုံကို Clipboard သို့ ကူးယူပြီးပါပြီ" }}');
                    } catch (clipErr) {
                        copied = false;
                    }
                }

                if (!copied) {
                    downloadBlob(blob, imageFilename);
                    showToast('Image downloaded');
                }
            } catch (err) {
                console.error('Share JPG error:', err);
                showToast('Failed to generate image');
            } finally {
                updatePreviewScale();
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            }
        }

        // Attach event listeners safely complying with strict CSP
        document.addEventListener('DOMContentLoaded', function() {
            var printBtn = document.getElementById('btnPrint');
            if (printBtn) {
                printBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    printCertificate();
                });
            }

            var pdfBtn = document.getElementById('btnDownloadPdf');
            if (pdfBtn) {
                pdfBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    downloadPdfDirectly();
                });
            }

            var shareJpgBtn = document.getElementById('btnShareJpg');
            if (shareJpgBtn) {
                shareJpgBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    shareJpgDirectly();
                });
            }

            var paperSelect = document.getElementById('paperSizeSelect');
            if (paperSelect) {
                paperSelect.addEventListener('change', function(e) {
                    changePaperSize(this.value);
                });
            }

            updatePreviewScale();
        });

        // Delegated fallback for button clicks
        document.addEventListener('click', function(e) {
            if (e.target.closest('#btnPrint')) {
                e.preventDefault();
                printCertificate();
            } else if (e.target.closest('#btnDownloadPdf')) {
                e.preventDefault();
                downloadPdfDirectly();
            } else if (e.target.closest('#btnShareJpg')) {
                e.preventDefault();
                shareJpgDirectly();
            }
        });

        window.addEventListener('resize', updatePreviewScale);
        window.addEventListener('orientationchange', updatePreviewScale);
        window.addEventListener('beforeprint', function() {
            var sheet = document.getElementById('certCard');
            if (sheet) sheet.style.transform = 'none';
        });
        window.addEventListener('afterprint', updatePreviewScale);
    </script>
</body>
</html>
