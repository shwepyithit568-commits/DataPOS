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
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
        }

        .no-print-bar-title {
            font-size: 13px;
            font-weight: 600;
        }

        .btn {
            padding: 6px 16px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            border: none;
            transition: all 0.15s ease;
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
            margin-right: 8px;
        }

        .btn-close:hover {
            background: #475569;
        }

        /* ── Accurate Page Dimension & Layout Rules ── */
        @if($preset['type'] === 'thermal')
            @page {
                size: {{ $preset['width_mm'] }}mm {{ $preset['height_mm'] }}mm;
                margin: 0mm;
            }

            .print-container {
                display: flex;
                flex-direction: column;
                align-items: center;
                margin: 0 auto;
                padding: 16px 0;
            }

            .label-item {
                width: {{ $preset['width_mm'] }}mm;
                height: {{ $preset['height_mm'] }}mm;
                max-width: {{ $preset['width_mm'] }}mm;
                max-height: {{ $preset['height_mm'] }}mm;
                padding: {{ $preset['padding'] ?? '1.2mm 2mm' }};
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: space-between;
                text-align: center;
                background: #ffffff;
                overflow: hidden;
                box-sizing: border-box;
                page-break-after: always;
                break-after: page;
                page-break-inside: avoid;
                break-inside: avoid;
                margin-bottom: 8px; /* On-screen gap */
                box-shadow: 0 1px 4px rgba(0, 0, 0, 0.12);
                border: 1px solid #cbd5e1;
                border-radius: 2px;
            }
        @else
            @page {
                size: A4 portrait;
                margin: 0mm;
            }

            .print-container {
                display: grid;
                grid-template-columns: repeat({{ $preset['cols'] }}, {{ $preset['width_mm'] }}mm);
                grid-auto-rows: {{ $preset['height_mm'] }}mm;
                width: 210mm;
                margin: 20px auto;
                padding: 0;
                justify-content: center;
                background: #ffffff;
                box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
                border: 1px solid #cbd5e1;
                page-break-inside: auto;
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
        }

        .barcode-wrapper svg {
            width: 100%;
            height: 100%;
            max-width: 100%;
            max-height: 100%;
            display: block;
        }

        .price-tag {
            font-size: {{ $preset['price_font'] ?? '10.5px' }};
            font-weight: 900;
            color: #000000;
            line-height: 1;
            font-family: monospace, sans-serif;
            letter-spacing: -0.02em;
        }

        /* ── Exact Print Media Overrides ── */
        @media print {
            body, html {
                background: #ffffff !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }

            .no-print-bar {
                display: none !important;
            }

            .print-container {
                padding: 0 !important;
                box-shadow: none !important;
                margin: 0 !important;
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
        }
    </style>
</head>
<body>

    {{-- Top Action Toolbar --}}
    <div class="no-print-bar">
        <div class="no-print-bar-title">
            <strong>{{ $store->name }}</strong> —
            <span>{{ $preset['name'] }} ({{ count($labels) }} {{ __('messages.barcode_total_labels') }})</span>
        </div>
        <div>
            <button type="button" class="btn btn-close" onclick="window.close()">{{ __('messages.close') }}</button>
            <button type="button" class="btn btn-print" onclick="window.print()">🖨️ {{ __('messages.print') }}</button>
        </div>
    </div>

    {{-- Printable Label Container --}}
    <div class="print-container">
        @foreach ($labels as $label)
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

                {{-- Price --}}
                @if($showPrice && $label['price'] > 0)
                    <div class="price-tag">{{ number_format($label['price'], 0) }} Ks</div>
                @endif
            </div>
        @endforeach
    </div>

</body>
</html>
