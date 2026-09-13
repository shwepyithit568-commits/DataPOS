@php
    $storeName = $store->name;
    $storePhone = $store->setting?->phone ?? $store->phone;
    $storeAddress = $store->setting?->address ?? $store->address;
    $logoUrl = $store->setting?->adminLogo() ? asset('storage/' . $store->setting->adminLogo()) : null;
    $backUrl = route('store.admin.stock_count.show', ['store_slug' => $store->slug, 'stock_count' => $session->id]);

    $myanmarFontUrl = null;
    try {
        $myanmarFontUrl = \Illuminate\Support\Facades\Vite::asset('resources/assets/fonts/NotoSansMyanmar/NotoSansMyanmar-Regular.ttf');
    } catch (\Throwable $e) {
        $myanmarFontUrl = null;
    }

    $fmtQty = function($v) {
        $val = (float) $v;
        return $val == (int) $val ? number_format($val, 0) : rtrim(rtrim(number_format($val, 3), '0'), '.');
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ __('messages.stock_count_print_title') }} — {{ $session->session_number }} — {{ $storeName }}</title>

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
            font-family: 'Noto Sans Myanmar', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, "Pyidaungsu", "Myanmar3", sans-serif;
        }

        html, body {
            background-color: #f1f5f9;
            color: #0f172a;
            font-size: 11.5px;
            line-height: 1.4;
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
            .sheet-card {
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
                margin: 0 auto !important;
                transform: none !important;
                width: 210mm !important;
                max-width: 210mm !important;
                min-height: 297mm !important;
                padding: 12mm 15mm !important;
            }
        }

        /* Top Action Toolbar */
        .top-nav-bar {
            position: sticky;
            top: 0;
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 18px;
            background: #ffffff;
            border-bottom: 1px solid #cbd5e1;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
            flex-wrap: wrap;
            gap: 10px;
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            color: #334155;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
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
            font-size: 12px;
            font-weight: 800;
            color: #1e1b4b;
            background: #e0e7ff;
            padding: 5px 11px;
            border-radius: 8px;
            border: 1px solid #c7d2fe;
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

        /* A4 Sheet Container */
        .sheet-card {
            width: 210mm;
            max-width: 210mm;
            min-height: 297mm;
            background: #ffffff;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            border-radius: 8px;
            padding: 12mm 15mm;
            position: relative;
            box-sizing: border-box;
            transform-origin: top center;
        }

        /* Corporate Header */
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #334155;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }

        .header-brand {
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .header-logo {
            max-height: 48px;
            max-width: 140px;
            object-fit: contain;
        }

        .store-name {
            font-size: 19px;
            font-weight: 900;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .doc-title {
            font-size: 15px;
            font-weight: 800;
            color: #4338ca;
            margin-top: 3px;
        }

        .store-details {
            font-size: 10.5px;
            color: #64748b;
            margin-top: 2px;
        }

        .header-meta {
            text-align: right;
        }

        .session-num {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 16px;
            font-weight: 900;
            color: #312e81;
            letter-spacing: 0.5px;
        }

        .status-pill {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            margin-top: 4px;
            border: 1px solid transparent;
        }
        .status-in_progress { background: #fef3c7; color: #92400e; border-color: #fde68a; }
        .status-approved { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
        .status-cancelled { background: #fee2e2; color: #991b1b; border-color: #fecaca; }

        /* Meta Information Grid */
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 14px;
        }

        .meta-item {
            line-height: 1.45;
        }

        .meta-label {
            font-size: 10px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            display: block;
        }

        .meta-val {
            font-size: 11.5px;
            font-weight: 800;
            color: #0f172a;
        }

        /* Items Table */
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }

        table.items-table th {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 7px 8px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            color: #334155;
            text-align: left;
        }

        table.items-table td {
            border: 1px solid #e2e8f0;
            padding: 6px 8px;
            font-size: 11px;
            vertical-align: middle;
        }

        table.items-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
        .font-bold { font-weight: 800; }

        .write-in-box {
            display: inline-block;
            width: 70px;
            height: 22px;
            border-bottom: 1.5px dashed #94a3b8;
            background: #ffffff;
            vertical-align: middle;
        }

        /* Signatures Section */
        .signatures-section {
            margin-top: 36px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .sig-card {
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            background: #f8fafc;
            padding: 12px 10px;
            text-align: center;
        }

        .sig-line {
            border-top: 1px dashed #94a3b8;
            margin: 40px auto 6px auto;
            width: 80%;
        }

        .sig-title {
            font-size: 11px;
            font-weight: 800;
            color: #1e293b;
        }

        .sig-sub {
            font-size: 9.5px;
            color: #64748b;
            margin-top: 2px;
        }

        /* Footer */
        .sheet-footer {
            margin-top: 24px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 10px;
            color: #94a3b8;
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
            <a href="{{ $backUrl }}" class="btn-back" id="btnBack">
                <span>←</span>
                <span>{{ __('messages.back') ?? 'ပြန်သွားရန်' }}</span>
            </a>
            <div class="doc-badge">
                <span>📋</span>
                <span>{{ __('messages.stock_count_print_title') }} #{{ $session->session_number }}</span>
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
        <div class="sheet-card" id="sheetCard">

            {{-- Header --}}
            <div class="header-section">
                <div class="header-brand">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $storeName }}" class="header-logo">
                    @endif
                    <div>
                        <div class="store-name">{{ $storeName }}</div>
                        <div class="doc-title">{{ __('messages.stock_count_print_title') }}</div>
                        @if($storePhone || $storeAddress)
                            <div class="store-details">
                                @if($storePhone) 📞 {{ $storePhone }} @endif
                                @if($storeAddress) | 📍 {{ $storeAddress }} @endif
                            </div>
                        @endif
                        @if($session->notes)
                            <div style="font-size: 11px; color: #475569; margin-top: 3px; font-style: italic;">
                                📝 {{ $session->notes }}
                            </div>
                        @endif
                    </div>
                </div>

                <div class="header-meta">
                    <div class="session-num">{{ $session->session_number }}</div>
                    <div>
                        <span class="status-pill status-{{ $session->status }}">
                            {{ __('messages.stock_count_status_' . $session->status) ?? strtoupper($session->status) }}
                        </span>
                    </div>
                    <div style="font-size: 10.5px; color: #64748b; margin-top: 4px;">
                        {{ __('messages.date') }}: {{ $session->created_at->format('d/m/Y h:i A') }}
                    </div>
                </div>
            </div>

            {{-- Meta Information Grid --}}
            <div class="meta-grid">
                <div class="meta-item">
                    <span class="meta-label">{{ __('messages.stock_count_scope') }}</span>
                    <span class="meta-val">
                        {{ $session->scope === 'category' ? __('messages.stock_count_scope_category') : __('messages.stock_count_scope_all') }}
                    </span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">{{ __('messages.stock_count_items') }}</span>
                    <span class="meta-val font-mono">{{ $session->total_items }} {{ __('messages.stock_count_products') }}</span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">{{ __('messages.stock_count_location') }}</span>
                    <span class="meta-val">
                        {{ $session->warehouse?->name ?? ($session->branch?->name ?? __('messages.default_warehouse')) }}
                    </span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">{{ __('messages.stock_count_created_by') }}</span>
                    <span class="meta-val">{{ $session->createdBy?->name ?? 'Admin' }}</span>
                </div>
            </div>

            {{-- Items Table --}}
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 32px;" class="text-center">#</th>
                        <th>{{ __('messages.product') }}</th>
                        <th>{{ __('messages.sku') }} / {{ __('messages.barcode') }}</th>
                        <th>{{ __('messages.category') }}</th>
                        <th class="text-right" style="width: 85px;">{{ __('messages.stock_count_system_qty') }}</th>
                        <th class="text-center" style="width: 95px;">{{ __('messages.stock_count_counted_qty') }}</th>
                        <th class="text-right" style="width: 85px;">{{ __('messages.stock_count_variance') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($session->lines as $idx => $line)
                        <tr>
                            <td class="text-center" style="font-size: 10px; color: #94a3b8;">{{ $idx + 1 }}</td>
                            <td>
                                <strong style="color: #0f172a;">{{ $line->product?->name ?? 'Unknown Product' }}</strong>
                            </td>
                            <td class="font-mono" style="font-size: 10px; color: #475569;">
                                {{ $line->product?->sku ?: '-' }} / {{ $line->product?->barcode ?: '-' }}
                            </td>
                            <td>{{ $line->category?->name ?? '-' }}</td>
                            <td class="text-right font-mono font-bold" style="color: #334155;">
                                {{ $fmtQty($line->system_quantity) }}
                            </td>
                            <td class="text-center">
                                @if($line->is_counted && $line->counted_quantity !== null)
                                    <span class="font-mono font-bold" style="color: #4338ca;">{{ $fmtQty($line->counted_quantity) }}</span>
                                @else
                                    <div class="write-in-box"></div>
                                @endif
                            </td>
                            <td class="text-right font-mono font-bold">
                                @if($line->is_counted)
                                    @php $diff = (float) $line->variance_quantity; @endphp
                                    <span style="color: {{ $diff > 0 ? '#16a34a' : ($diff < 0 ? '#dc2626' : '#64748b') }};">
                                        {{ $diff > 0 ? '+' : '' }}{{ $fmtQty($diff) }}
                                    </span>
                                @else
                                    <span style="color: #94a3b8;">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center" style="padding: 20px; color: #94a3b8;">
                                {{ __('messages.stock_count_no_products') ?? 'စာရင်းသွင်းထားသော ပစ္စည်းများ မရှိပါ။' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- Signatures Section --}}
            <div class="signatures-section">
                <div class="sig-card">
                    <div class="sig-title">{{ __('messages.stock_count_counted_by') }}</div>
                    <div class="sig-sub">(Signature / Date)</div>
                    <div class="sig-line"></div>
                    <div style="font-size: 10px; color: #64748b;">Staff / Stock Auditor</div>
                </div>
                <div class="sig-card">
                    <div class="sig-title">{{ __('messages.stock_count_verified_by') }}</div>
                    <div class="sig-sub">(Signature / Date)</div>
                    <div class="sig-line"></div>
                    <div style="font-size: 10px; color: #64748b;">Supervisor / Verifier</div>
                </div>
                <div class="sig-card">
                    <div class="sig-title">{{ __('messages.stock_count_approved_by') }}</div>
                    <div class="sig-sub">(Signature / Date)</div>
                    <div class="sig-line"></div>
                    <div style="font-size: 10px; color: #64748b;">Store Manager</div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="sheet-footer">
                <div>
                    {{ __('messages.printed_at') ?? 'ပုံနှိပ်ချိန်' }}: {{ now()->format('d/m/Y h:i A') }} · {{ $storeName }}
                </div>
                <div>
                    DataPOS Physical Stock Audit Management System
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

        function updatePreviewScale() {
            var stage = document.getElementById('previewStage');
            var sheet = document.getElementById('sheetCard');
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

        function printSheet() {
            window.print();
        }

        function getPdfConfig() {
            return {
                margin: [10, 12, 10, 12],
                filename: 'stock_count_{{ $session->session_number }}.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff', logging: false },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
        }

        async function downloadPdfDirectly() {
            var sheet = document.getElementById('sheetCard');
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
            var sheet = document.getElementById('sheetCard');
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
                var imageFilename = 'stock_count_{{ $session->session_number }}.png';

                var copied = false;
                if (navigator.clipboard && navigator.clipboard.write) {
                    try {
                        await navigator.clipboard.write([
                            new ClipboardItem({ 'image/png': blob })
                        ]);
                        copied = true;
                        showToast('{{ __("messages.vouchers_jpg_copied") ?? "ပုံကို Clipboard သို့ ကူးယူပြီးပါပြီ" }}');
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
                    printSheet();
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
                printSheet();
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
            var sheet = document.getElementById('sheetCard');
            if (sheet) sheet.style.transform = 'none';
        });
        window.addEventListener('afterprint', updatePreviewScale);
    </script>
</body>
</html>
