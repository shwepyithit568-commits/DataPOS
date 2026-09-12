<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('messages.sidebar_barcode') }} - {{ $store->name }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Pyidaungsu", "Myanmar3", sans-serif;
        }

        body {
            background-color: #f1f5f9;
            color: #000000;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ── Non-printable Action Toolbar ── */
        .no-print-bar {
            background: #1e293b;
            color: #ffffff;
            padding: 8px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            font-size: 12px;
        }

        .no-print-bar-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
        }

        .badge-count {
            background: #7c3aed;
            color: #ffffff;
            padding: 2px 8px;
            border-radius: 9999px;
            font-weight: 800;
            font-size: 11px;
        }

        .printer-tip {
            font-size: 11px;
            color: #94a3b8;
            margin-left: 8px;
            display: none;
        }
        @media (min-width: 768px) {
            .printer-tip { display: inline; }
        }

        .btn-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn {
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            border: none;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-print {
            background: #7c3aed;
            color: #ffffff;
        }

        .btn-print:hover {
            background: #6d28d9;
        }

        .btn-close {
            background: #334155;
            color: #e2e8f0;
        }

        .btn-close:hover {
            background: #475569;
        }

        /* ── Accurate Page Dimension & Layout Rules ── */
        @if(($preset['type'] ?? 'thermal') === 'thermal')
            @page {
                size: {{ $pageWidthMm }}mm {{ $pageHeightMm }}mm;
                margin: 0mm;
            }

            .print-container {
                display: flex;
                flex-direction: column;
                align-items: center;
                margin: 0 auto;
                padding: 16px 0;
            }

            .thermal-row {
                display: flex;
                flex-direction: row;
                align-items: center;
                justify-content: center;
                gap: {{ $preset['gap_x_mm'] ?? 0 }}mm;
                width: {{ $pageWidthMm }}mm;
                height: {{ $pageHeightMm }}mm;
                page-break-after: always;
                break-after: page;
                page-break-inside: avoid;
                break-inside: avoid;
                margin-bottom: 8px; /* On-screen gap */
            }

            .label-item {
                width: {{ $preset['width_mm'] }}mm;
                height: {{ $preset['height_mm'] }}mm;
                max-width: {{ $preset['width_mm'] }}mm;
                max-height: {{ $preset['height_mm'] }}mm;
                padding: {{ $preset['padding'] ?? '1.2mm 1.5mm' }};
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: space-between;
                text-align: center;
                background: #ffffff;
                overflow: hidden;
                box-sizing: border-box;
                box-shadow: 0 1px 4px rgba(0, 0, 0, 0.12);
                border: 1px solid #cbd5e1;
                border-radius: 2px;
                page-break-inside: avoid;
                break-inside: avoid;
            }
        @else
            @page {
                size: {{ ($presetKey ?? '') === 'letter_30' ? 'letter portrait' : 'A4 portrait' }};
                margin: {{ $preset['margin_top_mm'] ?? 0 }}mm {{ $preset['margin_right_mm'] ?? 0 }}mm {{ $preset['margin_bottom_mm'] ?? 0 }}mm {{ $preset['margin_left_mm'] ?? 0 }}mm;
            }

            .sheet-page-wrapper {
                margin: 24px auto;
                display: flex;
                flex-direction: column;
                align-items: center;
                page-break-after: always;
                break-after: page;
            }

            .sheet-page-header {
                width: {{ ($presetKey ?? '') === 'letter_30' ? '215.9mm' : '210mm' }};
                max-width: 100%;
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 6px 14px;
                background: #1e293b;
                color: #f8fafc;
                border-radius: 6px 6px 0 0;
                font-size: 11px;
                font-weight: 700;
                box-sizing: border-box;
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
            }

            .sheet-page-subtitle {
                font-size: 10.5px;
                font-weight: 500;
                color: #cbd5e1;
                margin-left: 6px;
            }

            .sheet-page-dim {
                font-size: 10.5px;
                color: #94a3b8;
                font-family: monospace;
            }

            .print-container {
                display: grid;
                grid-template-columns: repeat({{ $preset['cols'] ?? 3 }}, {{ $preset['width_mm'] }}mm);
                grid-template-rows: repeat({{ $preset['rows'] ?? 8 }}, {{ $preset['height_mm'] }}mm);
                column-gap: {{ $preset['gap_x_mm'] ?? 0 }}mm;
                row-gap: {{ $preset['gap_y_mm'] ?? 0 }}mm;
                width: {{ ($presetKey ?? '') === 'letter_30' ? '215.9mm' : '210mm' }};
                height: {{ ($presetKey ?? '') === 'letter_30' ? '279.4mm' : '297mm' }};
                max-height: {{ ($presetKey ?? '') === 'letter_30' ? '279.4mm' : '297mm' }};
                margin: 0 auto;
                padding: 0;
                justify-content: center;
                align-content: start;
                background: #ffffff;
                box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
                border: 1px solid #cbd5e1;
                box-sizing: border-box;
                overflow: hidden;
                page-break-after: always;
                break-after: page;
                page-break-inside: avoid;
                break-inside: avoid;
            }

            .label-item {
                width: {{ $preset['width_mm'] }}mm;
                height: {{ $preset['height_mm'] }}mm;
                max-width: {{ $preset['width_mm'] }}mm;
                max-height: {{ $preset['height_mm'] }}mm;
                padding: {{ $preset['padding'] ?? '1.5mm 2mm' }};
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: space-between;
                text-align: center;
                background: #ffffff;
                box-sizing: border-box;
                overflow: hidden;
                border: 1px dashed #e2e8f0;
                page-break-inside: avoid;
                break-inside: avoid;
            }

            .label-item.blank-label {
                border: 1px dashed #cbd5e1;
                background: #f8fafc;
                opacity: 0.5;
            }
        @endif

        /* ── Label Content Styles (Calibrated per Preset) ── */
        .store-name {
            font-size: {{ $preset['store_font'] ?? '9px' }};
            font-weight: 800;
            color: #000000;
            line-height: 1.1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 100%;
            letter-spacing: -0.01em;
            margin-bottom: {{ $preset['spacing_store_to_name_mm'] ?? 0.5 }}mm;
        }

        .product-name {
            font-size: {{ $preset['name_font'] ?? '8.5px' }};
            font-weight: 600;
            color: #000000;
            line-height: 1.15;
            max-height: {{ ($preset['name_max_lines'] ?? 2) === 1 ? '1.25em' : '2.35em' }};
            overflow: hidden;
            width: 100%;
            word-break: break-word;
            margin-bottom: {{ $preset['spacing_name_to_code_mm'] ?? 0.5 }}mm;
        }

        .barcode-wrapper {
            width: 100%;
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: 0.5px 0;
            margin-bottom: {{ $preset['spacing_code_to_price_mm'] ?? 0.5 }}mm;
        }

        .barcode-wrapper svg {
            width: 100%;
            height: 100%;
            max-width: 100%;
            max-height: 100%;
            display: block;
        }

        .footer-row {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 2px;
        }

        .price-tag {
            font-size: {{ $preset['price_font'] ?? '10.5px' }};
            font-weight: 900;
            color: #000000;
            line-height: 1;
            font-family: monospace, sans-serif;
            letter-spacing: -0.02em;
            white-space: nowrap;
        }

        .custom-text-badge {
            font-size: 7px;
            font-weight: 700;
            color: #334155;
            line-height: 1;
            letter-spacing: -0.01em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 60%;
        }

        /* ── Exact Print Media Overrides ── */
        @media print {
            body, html {
                background: #ffffff !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }

            .no-print-bar,
            .sheet-page-header,
            .no-print {
                display: none !important;
            }

            .sheet-page-wrapper {
                margin: 0 !important;
                padding: 0 !important;
                display: block !important;
                background: transparent !important;
                page-break-after: always !important;
                break-after: page !important;
            }

            .sheet-page-wrapper:last-child {
                page-break-after: auto !important;
                break-after: auto !important;
            }

            .print-container {
                padding: 0 !important;
                box-shadow: none !important;
                margin: 0 !important;
                border: none !important;
                background: transparent !important;
                page-break-after: always !important;
                break-after: page !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .sheet-page-wrapper:last-child .print-container,
            .print-container:last-child {
                page-break-after: auto !important;
                break-after: auto !important;
            }

            .thermal-row {
                margin: 0 !important;
                box-shadow: none !important;
                border: none !important;
                background: transparent !important;
            }

            .label-item {
                margin: 0 !important;
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
                background: transparent !important;
            }

            .label-item.blank-label {
                visibility: hidden !important;
                border: none !important;
                background: transparent !important;
            }
        }
    </style>
</head>
<body>

    {{-- Top Action Toolbar --}}
    <div class="no-print-bar">
        <div class="no-print-bar-title">
            <strong>{{ $store->name }}</strong>
            <span>— {{ $preset['name'] }}</span>
            <span class="badge-count">{{ count($labels) }} {{ __('messages.barcode_total_labels') }}</span>
            @if(($preset['type'] ?? 'thermal') === 'sheet' && count($sheetPages) > 1)
                <span class="badge-count" style="background:#0284c7;">{{ count($sheetPages) }} {{ __('messages.barcode_pages_count') }}</span>
            @endif
            @if($isTestSingle)
                <span style="background:#f59e0b;color:#000;padding:1px 6px;border-radius:4px;font-weight:bold;font-size:10px;">TEST 1 STICKER</span>
            @endif
            <span class="printer-tip">
                💡 <strong>Guide:</strong> Margins: None (0) · Scale: 100% (Do not fit)
            </span>
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-close" onclick="window.close()">{{ __('messages.close') }}</button>
            <button type="button" class="btn btn-print" onclick="window.print()">🖨️ {{ __('messages.print') }}</button>
        </div>
    </div>

    {{-- Printable Label Container --}}
    @if(($preset['type'] ?? 'thermal') === 'thermal')
        {{-- Thermal Continuous Roll / 1-Up / 2-Up / 3-Up Row Packaged --}}
        <div class="print-container">
            @foreach ($labelRows as $row)
                <div class="thermal-row">
                    @foreach ($row as $label)
                        <div class="label-item">
                            {{-- Store Name --}}
                            @if($showStoreName)
                                <div class="store-name">{{ $label['store_name'] }}</div>
                            @endif

                            {{-- Product Name --}}
                            @if($showProductName)
                                <div class="product-name">{{ $label['name'] }}</div>
                            @endif

                            {{-- Barcode or QR Code SVG --}}
                            <div class="barcode-wrapper">
                                {!! $label['svg'] !!}
                            </div>

                            {{-- Bottom Row: Price and/or Custom Text --}}
                            @if(($showPrice && $label['price'] > 0) || ($showCustomText && !empty($label['custom_text'])))
                                <div class="footer-row" style="{{ (!$showCustomText || empty($label['custom_text'])) ? 'justify-content:center;' : '' }}">
                                    @if($showCustomText && !empty($label['custom_text']))
                                        <div class="custom-text-badge">{{ $label['custom_text'] }}</div>
                                    @endif
                                    @if($showPrice && $label['price'] > 0)
                                        <div class="price-tag">{{ format_currency($label['price'], $store) }}</div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    @else
        {{-- A4 / Letter Pre-cut Sticker Sheet Grid Paginated per Physical Sheet --}}
        @foreach ($sheetPages as $pageIndex => $pageItems)
            <div class="sheet-page-wrapper">
                <div class="sheet-page-header no-print">
                    <span class="sheet-page-title">
                        📄 <strong>{{ __('messages.barcode_page') }} {{ $pageIndex + 1 }} / {{ count($sheetPages) }}</strong>
                        <span class="sheet-page-subtitle">({{ count($pageItems) }} / {{ $labelsPerPage }} {{ __('messages.barcode_total_labels') }})</span>
                    </span>
                    <span class="sheet-page-dim">
                        {{ ($presetKey ?? '') === 'letter_30' ? 'US Letter (215.9mm × 279.4mm)' : 'A4 (210mm × 297mm)' }}
                    </span>
                </div>

                <div class="print-container">
                    @foreach ($pageItems as $label)
                        @if(!empty($label['is_blank']))
                            <div class="label-item blank-label"></div>
                        @else
                            <div class="label-item">
                                {{-- Store Name --}}
                                @if($showStoreName)
                                    <div class="store-name">{{ $label['store_name'] }}</div>
                                @endif

                                {{-- Product Name --}}
                                @if($showProductName)
                                    <div class="product-name">{{ $label['name'] }}</div>
                                @endif

                                {{-- Barcode or QR Code SVG --}}
                                <div class="barcode-wrapper">
                                    {!! $label['svg'] !!}
                                </div>

                                {{-- Bottom Row: Price and/or Custom Text --}}
                                @if(($showPrice && $label['price'] > 0) || ($showCustomText && !empty($label['custom_text'])))
                                    <div class="footer-row" style="{{ (!$showCustomText || empty($label['custom_text'])) ? 'justify-content:center;' : '' }}">
                                        @if($showCustomText && !empty($label['custom_text']))
                                            <div class="custom-text-badge">{{ $label['custom_text'] }}</div>
                                        @endif
                                        @if($showPrice && $label['price'] > 0)
                                            <div class="price-tag">{{ format_currency($label['price'], $store) }}</div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif

</body>
</html>
