@php
    $tmpl = $voucherTemplate ?? null;
    $storeName = data_get($tmpl, 'header_title') ?: $store->name;
    $storeSubtitle = data_get($tmpl, 'header_subtitle');
    $storePhone = data_get($tmpl, 'phone') ?: ($store->setting?->phone ?? $store->phone);
    $storeAddress = data_get($tmpl, 'address') ?: ($store->setting?->address ?? $store->address);
    $showLogo = (bool) data_get($tmpl, 'show_logo', true);
    $templateLogo = ($tmpl instanceof \App\Models\VoucherTemplate) ? $tmpl->logoUrl() : null;
    $logoUrl = $showLogo ? ($templateLogo ?? ($store->setting?->adminLogo() ? asset('storage/' . $store->setting->adminLogo()) : null)) : null;
    $footerGreeting = data_get($tmpl, 'footer_greeting');

    $myanmarFontUrl = null;
    try {
        $myanmarFontUrl = \Illuminate\Support\Facades\Vite::asset('resources/assets/fonts/NotoSansMyanmar/NotoSansMyanmar-Regular.ttf');
    } catch (\Throwable $e) {
        $myanmarFontUrl = null;
    }

    $backUrl = route('store.admin.transactions.index', ['store_slug' => $store->slug]);
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ __('messages.transactions_voucher') }} — {{ $transaction->transaction_number }} — {{ $storeName }}</title>

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
            font-size: 12px;
            line-height: 1.45;
            margin: 0;
            padding: 0;
        }

        @page {
            size: 148mm 210mm;
            margin: 0;
        }

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
            .voucher-box {
                box-shadow: none !important;
                border-radius: 0 !important;
                margin: 0 auto !important;
                width: 148mm !important;
                max-width: 148mm !important;
                min-height: 210mm !important;
                padding: 8mm 10mm !important;
                border: 1.5px solid #4338ca !important;
                transform: none !important;
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
            gap: 12px;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 12px;
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
            padding: 7px 14px;
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
            padding: 20px 12px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* Standard A5 Corporate Sheet Container */
        .voucher-box {
            width: 148mm;
            max-width: 148mm;
            min-height: 210mm;
            margin: 0 auto;
            background: #ffffff;
            border: 2px solid #4338ca;
            border-radius: 10px;
            padding: 10mm 12mm;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
            position: relative;
            box-sizing: border-box;
            transform-origin: top center;
        }

        .header {
            text-align: center;
            border-bottom: 2px dashed #cbd5e1;
            padding-bottom: 12px;
            margin-bottom: 12px;
        }

        .store-name {
            font-size: 18px;
            font-weight: 900;
            color: #1e1b4b;
            text-transform: uppercase;
            letter-spacing: -0.3px;
        }

        .store-sub {
            font-size: 11px;
            font-weight: 700;
            color: #4338ca;
            margin-top: 1px;
        }

        .store-info {
            font-size: 10.5px;
            color: #64748b;
            margin-top: 3px;
        }

        .voucher-badge-wrap {
            margin-top: 10px;
        }

        .voucher-title-badge {
            display: inline-block;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 4px 12px;
            border-radius: 6px;
        }
        .badge-deposit {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }
        .badge-withdrawal {
            background: #fff1f2;
            color: #be123c;
            border: 1px solid #fecdd3;
        }
        .badge-transfer {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }

        .meta-list {
            margin: 12px 0;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 6px;
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11.5px;
            margin-bottom: 6px;
            gap: 8px;
        }

        .meta-label {
            color: #64748b;
            font-weight: 600;
        }

        .meta-value {
            font-weight: 700;
            color: #0f172a;
            text-align: right;
        }

        .font-mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        .type-pill {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10.5px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .amount-box {
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
            text-align: center;
            margin: 14px 0;
        }

        .amount-title {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #64748b;
            margin-bottom: 3px;
            font-weight: 800;
        }

        .amount-num {
            font-size: 22px;
            font-weight: 900;
            color: #1e1b4b;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        .fee-num {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            margin-top: 3px;
        }

        .notes-box {
            background: #fafafa;
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 11px;
            color: #334155;
            margin: 12px 0;
            line-height: 1.5;
        }

        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 32px;
            padding-top: 8px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .sig-block {
            text-align: center;
            width: 44%;
            border-top: 1px dashed #94a3b8;
            padding-top: 6px;
            font-size: 11px;
            font-weight: 700;
            color: #475569;
        }

        .footer-greeting {
            text-align: center;
            font-size: 10.5px;
            color: #94a3b8;
            margin-top: 18px;
            border-top: 1px solid #f1f5f9;
            padding-top: 8px;
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
                <span>💸</span>
                <span>{{ __('messages.transactions_voucher') }} #{{ $transaction->transaction_number }}</span>
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
        <div class="voucher-box" id="voucherBox">
            {{-- Header --}}
            <div class="header">
                @if ($logoUrl)
                    <div style="margin-bottom: 8px;">
                        <img src="{{ $logoUrl }}" alt="{{ $storeName }}" style="max-height: 46px; max-width: 160px; object-fit: contain; margin: 0 auto; display: block;">
                    </div>
                @endif
                <div class="store-name">{{ $storeName }}</div>
                @if ($storeSubtitle)
                    <div class="store-sub">{{ $storeSubtitle }}</div>
                @endif
                @if ($storePhone || $storeAddress)
                    <div class="store-info">
                        @if ($storePhone) 📞 {{ $storePhone }} @endif
                        @if ($storeAddress) | 📍 {{ $storeAddress }} @endif
                    </div>
                @endif

                <div class="voucher-badge-wrap">
                    @if($transaction->type === 'deposit')
                        <div class="voucher-title-badge badge-deposit">{{ __('messages.voucher_type_receipt') }}</div>
                    @elseif($transaction->type === 'withdrawal')
                        <div class="voucher-title-badge badge-withdrawal">{{ __('messages.voucher_type_payment') }}</div>
                    @else
                        <div class="voucher-title-badge badge-transfer">{{ __('messages.voucher_type_transfer') }}</div>
                    @endif
                </div>
            </div>

            {{-- Meta Information --}}
            <div class="meta-list">
                <div class="meta-row">
                    <span class="meta-label">{{ __('messages.voucher_no') }}:</span>
                    <span class="meta-value font-mono">{{ $transaction->transaction_number }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">{{ __('messages.date_time') }}:</span>
                    <span class="meta-value font-mono">{{ $transaction->transaction_date->format('d/m/Y h:i A') }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">{{ __('messages.transaction_type') }}:</span>
                    <span class="meta-value">
                        @if($transaction->type === 'deposit')
                            <span class="type-pill badge-deposit">{{ __('messages.transactions_type_deposit') }}</span>
                        @elseif($transaction->type === 'withdrawal')
                            <span class="type-pill badge-withdrawal">{{ __('messages.transactions_type_withdrawal') }}</span>
                        @else
                            <span class="type-pill badge-transfer">{{ __('messages.transactions_type_transfer') }}</span>
                        @endif
                    </span>
                </div>

                @if($transaction->fromAccount)
                    <div class="meta-row">
                        <span class="meta-label">{{ __('messages.from_account') }}:</span>
                        <span class="meta-value">{{ $transaction->fromAccount->name }}</span>
                    </div>
                @endif

                @if($transaction->toAccount)
                    <div class="meta-row">
                        <span class="meta-label">{{ __('messages.to_account') }}:</span>
                        <span class="meta-value">{{ $transaction->toAccount->name }}</span>
                    </div>
                @endif

                @if($transaction->category)
                    <div class="meta-row">
                        <span class="meta-label">{{ __('messages.category') }} / {{ __('messages.purpose') }}:</span>
                        <span class="meta-value">{{ ucwords(str_replace('_', ' ', $transaction->category)) }}</span>
                    </div>
                @endif

                @if($transaction->payer_or_payee)
                    <div class="meta-row">
                        <span class="meta-label">{{ __('messages.payer_payee') }}:</span>
                        <span class="meta-value">{{ $transaction->payer_or_payee }}</span>
                    </div>
                @endif

                @if($transaction->reference_no)
                    <div class="meta-row">
                        <span class="meta-label">{{ __('messages.reference_slip') }}:</span>
                        <span class="meta-value font-mono">{{ $transaction->reference_no }}</span>
                    </div>
                @endif
            </div>

            {{-- Amount Box --}}
            <div class="amount-box">
                <div class="amount-title">{{ __('messages.transactions_amount') }}</div>
                <div class="amount-num">{{ format_currency($transaction->amount, $store) }}</div>
                @if((float) $transaction->fee > 0)
                    <div class="fee-num">
                        + {{ __('messages.transactions_fee') }}: {{ format_currency($transaction->fee, $store) }}
                    </div>
                @endif
            </div>

            {{-- Notes --}}
            @if($transaction->notes)
                <div class="notes-box">
                    <strong style="font-size: 10px; text-transform: uppercase; color: #64748b;">{{ __('messages.notes') }}:</strong>
                    <div style="margin-top: 2px;">{{ $transaction->notes }}</div>
                </div>
            @endif

            {{-- Signatures --}}
            <div class="signatures">
                <div class="sig-block">
                    {{ __('messages.voucher_prepared_by') }} ({{ $transaction->recorder?->name ?? 'Cashier' }})
                </div>
                <div class="sig-block">
                    {{ __('messages.voucher_authorized_by') }}
                </div>
            </div>

            @if($footerGreeting)
                <div class="footer-greeting">
                    {{ $footerGreeting }}
                </div>
            @endif
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

        function updatePreviewScale() {
            var stage = document.getElementById('previewStage');
            var sheet = document.getElementById('voucherBox');
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

        function printVoucher() {
            window.print();
        }

        function getPdfConfig() {
            return {
                margin: 0,
                filename: 'voucher_{{ $transaction->transaction_number }}.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff', logging: false },
                jsPDF: { unit: 'mm', format: 'a5', orientation: 'portrait' }
            };
        }

        async function downloadPdfDirectly() {
            var sheet = document.getElementById('voucherBox');
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
            var sheet = document.getElementById('voucherBox');
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
                var imageFilename = 'voucher_{{ $transaction->transaction_number }}.png';

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
                    printVoucher();
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

            updatePreviewScale();
        });

        // Delegated fallback for button clicks
        document.addEventListener('click', function(e) {
            if (e.target.closest('#btnPrint')) {
                e.preventDefault();
                printVoucher();
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
            var sheet = document.getElementById('voucherBox');
            if (sheet) sheet.style.transform = 'none';
        });
        window.addEventListener('afterprint', updatePreviewScale);
    </script>
</body>
</html>
