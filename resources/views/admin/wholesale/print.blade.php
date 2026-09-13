@php
    $tmpl = $voucherTemplate ?? null;
    $storeName = data_get($tmpl, 'header_title') ?: $store->name;
    $storeSubtitle = data_get($tmpl, 'header_subtitle') ?: __('messages.wholesale_slip_sub');
    $storePhone = data_get($tmpl, 'phone') ?: ($setting?->phone ?? $store->phone);
    $storeAddress = data_get($tmpl, 'address') ?: ($setting?->address ?? $store->address);
    $showLogo = (bool) data_get($tmpl, 'show_logo', true);
    $templateLogo = ($tmpl instanceof \App\Models\VoucherTemplate) ? $tmpl->logoUrl() : null;
    $logoUrl = $showLogo ? ($templateLogo ?? ($setting?->storefrontLogo() ? asset('storage/' . $setting->storefrontLogo()) : null)) : null;

    $myanmarFontUrl = null;
    try {
        $myanmarFontUrl = \Illuminate\Support\Facades\Vite::asset('resources/assets/fonts/NotoSansMyanmar/NotoSansMyanmar-Regular.ttf');
    } catch (\Throwable $e) {
        $myanmarFontUrl = null;
    }

    $backUrl = route('store.admin.wholesale.applications.show', $storeRouteParams + ['application' => $application->id]);
    $appNumberFormatted = 'APP-' . str_pad($application->id, 5, '0', STR_PAD_LEFT);
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ __('messages.wholesale_app_title') }} #{{ $appNumberFormatted }} — {{ $storeName }}</title>

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
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Noto Sans Myanmar', 'Pyidaungsu', 'Myanmar Text', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
        }

        html, body {
            background-color: #f1f5f9;
            color: #1e293b;
            font-size: 12.5px;
            line-height: 1.45;
            margin: 0;
            padding: 0;
        }

        @page {
            size: 210mm 297mm;
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
            .sheet {
                box-shadow: none !important;
                border-radius: 0 !important;
                margin: 0 auto !important;
                width: 210mm !important;
                max-width: 210mm !important;
                min-height: 297mm !important;
                padding: 12mm 15mm !important;
                border: 2px solid #4f46e5 !important;
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
            background: #4f46e5;
            color: #ffffff;
            border-color: #4338ca;
        }
        .btn-primary:hover {
            background: #4338ca;
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
            padding: 24px 12px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* Standard A4 Corporate Sheet Container */
        .sheet {
            width: 210mm;
            max-width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #ffffff;
            border: 2px solid #4f46e5;
            border-radius: 12px;
            padding: 14mm 16mm;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.1);
            position: relative;
            box-sizing: border-box;
            transform-origin: top center;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 16px;
            border-bottom: 2px solid #4f46e5;
            gap: 16px;
        }

        .brand .store-name {
            font-size: 20px;
            font-weight: 900;
            color: #111827;
            text-transform: uppercase;
        }
        .brand .store-sub {
            font-size: 11.5px;
            color: #4f46e5;
            font-weight: 700;
            margin-top: 2px;
        }
        .doc-title {
            text-align: right;
        }
        .doc-title h1 {
            font-size: 20px;
            font-weight: 900;
            color: #4f46e5;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .doc-title .app-no {
            font-size: 13px;
            font-weight: 800;
            color: #334155;
            margin-top: 2px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }
        .doc-title .date {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }

        .status-banner {
            margin: 18px 0;
            padding: 12px 18px;
            border-radius: 8px;
            font-weight: 800;
            font-size: 12.5px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .status-approved { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .status-pending { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
        .status-rejected { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .status-suspended { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }

        .section-title {
            font-size: 11.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #4f46e5;
            margin: 18px 0 8px;
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 4px;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }
        .field-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 14px;
        }
        .field-label {
            font-size: 10px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 3px;
            letter-spacing: 0.5px;
        }
        .field-val {
            font-size: 12.5px;
            font-weight: 700;
            color: #1e293b;
            word-break: break-word;
        }

        .notes-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 11.5px;
            line-height: 1.6;
            color: #334155;
            margin-top: 10px;
        }

        .signatures {
            margin-top: 42px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding-top: 16px;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .sign-box {
            text-align: center;
            width: 210px;
        }
        .sign-line {
            border-top: 1px dashed #94a3b8;
            margin-top: 36px;
            padding-top: 6px;
            font-size: 11px;
            font-weight: 700;
            color: #475569;
        }

        .footer {
            margin-top: 28px;
            text-align: center;
            font-size: 10.5px;
            color: #94a3b8;
            border-top: 1px solid #f1f5f9;
            padding-top: 10px;
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
                <span>{{ __('messages.wholesale_back_to_application') }}</span>
            </a>
            <div class="doc-badge">
                <span>🏢</span>
                <span>{{ __('messages.wholesale_app_title') }} #{{ $appNumberFormatted }}</span>
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
                <span>{{ __('messages.wholesale_print_slip') }}</span>
            </button>
        </div>
    </div>

    {{-- Toast Notification --}}
    <div id="printToast" class="print-toast no-print"></div>

    {{-- Responsive Stage --}}
    <div class="preview-stage" id="previewStage">
        <div class="sheet" id="wholesaleSheet">
            {{-- Header --}}
            <div class="header">
                <div class="brand">
                    @if ($logoUrl)
                        <div style="margin-bottom: 8px;">
                            <img src="{{ $logoUrl }}" alt="{{ $storeName }}" style="max-height: 48px; max-width: 160px; object-fit: contain;">
                        </div>
                    @endif
                    <div class="store-name">{{ $storeName }}</div>
                    <div class="store-sub">{{ $storeSubtitle }}</div>
                    @if ($storePhone)
                        <div style="font-size: 10.5px; color: #64748b; margin-top: 4px;">{{ __('messages.wholesale_phone') }} {{ $storePhone }}</div>
                    @endif
                    @if ($storeAddress)
                        <div style="font-size: 10.5px; color: #64748b;">{{ __('messages.wholesale_address') }} {{ $storeAddress }}</div>
                    @endif
                </div>
                <div class="doc-title">
                    <h1>{{ __('messages.wholesale_app_title') }}</h1>
                    <div class="app-no">{{ $appNumberFormatted }}</div>
                    <div class="date">{{ __('messages.wholesale_date') }} {{ $application->created_at->format('d/m/Y, h:i A') }}</div>
                </div>
            </div>

            {{-- Status Banner --}}
            <div class="status-banner status-{{ $application->status }}">
                <div>
                    {{ __('messages.wholesale_status_label') }} <strong>{{ strtoupper($application->status) }}</strong>
                </div>
                <div>
                    @if ($application->status === 'approved')
                        ✓ {{ __('messages.wholesale_approved') }}
                    @elseif ($application->status === 'pending')
                        ⏳ {{ __('messages.wholesale_pending') }}
                    @elseif ($application->status === 'rejected')
                        ✕ {{ __('messages.wholesale_rejected') }}
                    @else
                        ⊘ {{ __('messages.wholesale_suspended') }}
                    @endif
                </div>
            </div>

            {{-- Business & Applicant Information --}}
            <div class="section-title">{{ __('messages.wholesale_business_info') }}</div>
            <div class="grid-2">
                <div class="field-box">
                    <div class="field-label">{{ __('messages.wholesale_business_name') }}</div>
                    <div class="field-val">{{ $application->business_name }}</div>
                </div>
                <div class="field-box">
                    <div class="field-label">{{ __('messages.wholesale_applicant_name') }}</div>
                    <div class="field-val">{{ $application->user?->name ?? __('messages.wholesale_guest_applicant') }}</div>
                </div>
                <div class="field-box">
                    <div class="field-label">{{ __('messages.wholesale_contact_phone') }}</div>
                    <div class="field-val">{{ $application->phone }}</div>
                </div>
                <div class="field-box">
                    <div class="field-label">{{ __('messages.wholesale_member_user_id') }}</div>
                    <div class="field-val">#{{ $application->user_id ?? 'N/A' }}</div>
                </div>
            </div>

            @if ($application->address)
                <div class="field-box" style="margin-top: 12px;">
                    <div class="field-label">{{ __('messages.wholesale_business_address') }}</div>
                    <div class="field-val">{{ $application->address }}</div>
                </div>
            @endif

            {{-- Applicant Note --}}
            @if ($application->notes)
                <div class="section-title">{{ __('messages.wholesale_applicant_note') }}</div>
                <div class="notes-box">
                    {{ $application->notes }}
                </div>
            @endif

            {{-- Admin Internal Note / Verification --}}
            @if ($application->admin_note)
                <div class="section-title">{{ __('messages.wholesale_verification_note') }}</div>
                <div class="notes-box" style="background: #fdf4ff; border-color: #f0abfc; color: #86198f;">
                    {{ $application->admin_note }}
                </div>
            @endif

            {{-- Signatures --}}
            <div class="signatures">
                <div class="sign-box">
                    <div class="sign-line">{{ __('messages.wholesale_applicant_sign') }}</div>
                </div>
                <div class="sign-box">
                    <div class="sign-line">{{ __('messages.wholesale_manager_sign') }}</div>
                </div>
            </div>

            <div class="footer">
                {{ __('messages.wholesale_generated_on') }} {{ now()->format('d/m/Y, h:i A') }} · {{ $store->name }} DataPOS Wholesale Management System
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

        function updatePreviewScale() {
            var stage = document.getElementById('previewStage');
            var sheet = document.getElementById('wholesaleSheet');
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

        function printSlip() {
            window.print();
        }

        function getPdfConfig() {
            return {
                margin: 0,
                filename: 'wholesale_app_{{ $appNumberFormatted }}.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff', logging: false },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
        }

        async function downloadPdfDirectly() {
            var sheet = document.getElementById('wholesaleSheet');
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
            var sheet = document.getElementById('wholesaleSheet');
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
                var imageFilename = 'wholesale_app_{{ $appNumberFormatted }}.png';

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
                    printSlip();
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
                printSlip();
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
            var sheet = document.getElementById('wholesaleSheet');
            if (sheet) sheet.style.transform = 'none';
        });
        window.addEventListener('afterprint', updatePreviewScale);
    </script>
</body>
</html>
