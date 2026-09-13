@php
    $storeName = $store->name;
    $storePhone = $store->setting?->phone ?? $store->phone;
    $storeAddress = $store->setting?->address ?? $store->address;
    $logoUrl = $store->setting?->adminLogo() ? asset('storage/' . $store->setting->adminLogo()) : null;
    $backUrl = route('store.admin.inventory_valuation.index', ['store_slug' => $store->slug]);

    $myanmarFontUrl = null;
    try {
        $myanmarFontUrl = \Illuminate\Support\Facades\Vite::asset('resources/assets/fonts/NotoSansMyanmar/NotoSansMyanmar-Regular.ttf');
    } catch (\Throwable $e) {
        $myanmarFontUrl = null;
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ __('messages.inv_val_title') }} — {{ $storeName }}</title>

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
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }

        @page {
            size: 297mm 210mm;
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
                width: 297mm !important;
                max-width: 297mm !important;
                min-height: 210mm !important;
                padding: 10mm 12mm !important;
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
            color: #0369a1;
            background: #e0f2fe;
            padding: 5px 11px;
            border-radius: 8px;
            border: 1px solid #bae6fd;
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
            background: #0284c7;
            color: #ffffff;
            border-color: #0369a1;
        }
        .btn-primary:hover {
            background: #0369a1;
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
            background: #e0f2fe;
            color: #0369a1;
            border-color: #bae6fd;
        }
        .btn-accent:hover {
            background: #bae6fd;
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

        /* A4 Landscape Sheet Container */
        .sheet-card {
            width: 297mm;
            max-width: 297mm;
            min-height: 210mm;
            background: #ffffff;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            border-radius: 8px;
            padding: 10mm 14mm;
            position: relative;
            box-sizing: border-box;
            transform-origin: top center;
        }

        /* Header */
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0284c7;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }

        .header-brand {
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .header-logo {
            max-height: 46px;
            max-width: 140px;
            object-fit: contain;
        }

        .store-name {
            font-size: 19px;
            font-weight: 900;
            color: #0f172a;
            text-transform: uppercase;
        }

        .doc-title {
            font-size: 14.5px;
            font-weight: 800;
            color: #0284c7;
            margin-top: 2px;
        }

        .store-details {
            font-size: 10px;
            color: #64748b;
            margin-top: 2px;
        }

        .header-meta {
            text-align: right;
            font-size: 10px;
            color: #64748b;
        }

        /* Meta Grid KPI Cards */
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 14px;
        }

        .kpi-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 12px;
            background: #f8fafc;
        }

        .kpi-card span {
            font-size: 9px;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            display: block;
        }

        .kpi-card h3 {
            margin: 3px 0 0 0;
            font-size: 14px;
            font-weight: 900;
            color: #0f172a;
        }

        /* Table */
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        table.items-table th, table.items-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 7px;
            font-size: 10px;
        }

        table.items-table th {
            background-color: #f1f5f9;
            font-weight: 800;
            text-transform: uppercase;
            color: #334155;
            text-align: left;
        }

        table.items-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
        .font-bold { font-weight: 800; }

        /* Footer */
        .sheet-footer {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            font-size: 9.5px;
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
                <span>📊</span>
                <span>{{ __('messages.inv_val_title') }}</span>
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
                <span>{{ __('messages.print_statement') }}</span>
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
                        <div class="doc-title">{{ __('messages.inv_val_title') }}</div>
                        @if($storePhone || $storeAddress)
                            <div class="store-details">
                                @if($storePhone) 📞 {{ $storePhone }} @endif
                                @if($storeAddress) | 📍 {{ $storeAddress }} @endif
                            </div>
                        @endif
                    </div>
                </div>

                <div class="header-meta">
                    <div style="font-weight: 700; color: #0f172a;">{{ __('messages.printed_at') ?? 'ပုံနှိပ်ချိန်' }}</div>
                    <div>{{ now()->format('d M Y, h:i A') }}</div>
                    <div style="margin-top: 2px;">DataPOS Valuation Module</div>
                </div>
            </div>

            {{-- KPI Summary Cards --}}
            <div class="meta-grid">
                <div class="kpi-card">
                    <span>{{ __('messages.inv_val_total_cost') }}</span>
                    <h3 class="font-mono">{{ format_currency($metrics['total_cost_value'], $store) }}</h3>
                </div>
                <div class="kpi-card">
                    <span>{{ __('messages.inv_val_total_retail') }}</span>
                    <h3 class="font-mono">{{ format_currency($metrics['total_retail_value'], $store) }}</h3>
                </div>
                <div class="kpi-card">
                    <span>{{ __('messages.inv_val_potential_profit') }}</span>
                    <h3 class="font-mono" style="color: #16a34a;">{{ format_currency($metrics['potential_profit'], $store) }}</h3>
                </div>
                <div class="kpi-card">
                    <span>{{ __('messages.inv_val_units_on_hand') }}</span>
                    <h3 class="font-mono">{{ format_quantity($metrics['total_units'], $store) }}</h3>
                </div>
            </div>

            {{-- Items Table --}}
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 25px;" class="text-center">#</th>
                        <th>{{ __('messages.sku') }}</th>
                        <th>{{ __('messages.product_name') }}</th>
                        <th>{{ __('messages.category') }}</th>
                        <th class="text-center">{{ __('messages.qty') }}</th>
                        <th class="text-right">{{ __('messages.unit_cost') }}</th>
                        <th class="text-right">{{ __('messages.total_cost_value') }}</th>
                        <th class="text-right">{{ __('messages.retail_price') }}</th>
                        <th class="text-right">{{ __('messages.total_retail_value') }}</th>
                        <th class="text-right">{{ __('messages.potential_profit') }}</th>
                        <th class="text-center">{{ __('messages.margin') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php $idx = 1; @endphp
                    @forelse ($products as $p)
                        <tr>
                            <td class="text-center" style="font-size: 9.5px; color: #94a3b8;">{{ $idx++ }}</td>
                            <td class="font-mono" style="font-size: 9.5px;">{{ $p->sku ?? '-' }}</td>
                            <td><strong style="color: #0f172a;">{{ $p->name }}</strong></td>
                            <td>{{ $p->category?->name ?? '-' }}</td>
                            <td class="text-center font-mono font-bold" style="color: #312e81;">{{ format_quantity($p->computed_qty, $store) }}</td>
                            <td class="text-right font-mono">{{ format_currency($p->computed_cost, $store) }}</td>
                            <td class="text-right font-mono font-bold" style="color: #dc2626;">{{ format_currency($p->computed_cost_value, $store) }}</td>
                            <td class="text-right font-mono">{{ format_currency((float) $p->retail_price, $store) }}</td>
                            <td class="text-right font-mono">{{ format_currency($p->computed_retail_value, $store) }}</td>
                            <td class="text-right font-mono font-bold" style="color: #16a34a;">{{ format_currency($p->computed_profit, $store) }}</td>
                            <td class="text-center font-bold">{{ $p->computed_margin }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center" style="padding: 20px; color: #94a3b8;">
                                {{ __('messages.no_data_available') ?? 'စာရင်း မရှိသေးပါ။' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr style="background: #f8fafc; font-weight: bold;">
                        <td colspan="4" class="text-right">{{ __('messages.grand_total') }}:</td>
                        <td class="text-center font-mono font-bold" style="color: #312e81;">{{ format_quantity($metrics['total_units'], $store) }}</td>
                        <td></td>
                        <td class="text-right font-mono font-bold" style="color: #dc2626;">{{ format_currency($metrics['total_cost_value'], $store) }}</td>
                        <td></td>
                        <td class="text-right font-mono font-bold">{{ format_currency($metrics['total_retail_value'], $store) }}</td>
                        <td class="text-right font-mono font-bold" style="color: #16a34a;">{{ format_currency($metrics['potential_profit'], $store) }}</td>
                        <td class="text-center font-bold">{{ $metrics['potential_margin'] }}%</td>
                    </tr>
                </tfoot>
            </table>

            {{-- Footer --}}
            <div class="sheet-footer">
                <span>DataPOS {{ __('messages.inventory_valuation_system') }} · {{ $storeName }}</span>
                <span>{{ __('messages.page_label') }} 1 / 1</span>
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

        function printStatement() {
            window.print();
        }

        function getPdfConfig() {
            return {
                margin: [8, 10, 8, 10],
                filename: 'inventory_valuation_{{ $store->slug }}.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff', logging: false },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
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
                var imageFilename = 'inventory_valuation_{{ $store->slug }}.png';

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
                    printStatement();
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
                printStatement();
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
