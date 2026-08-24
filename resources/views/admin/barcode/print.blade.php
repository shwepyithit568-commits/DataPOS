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
        }

        /* ── Non-printable Action Toolbar ── */
        .no-print-bar {
            background: #1e293b;
            color: #ffffff;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
        }

        .btn {
            padding: 7px 16px;
            border-radius: 6px;
            font-size: 13px;
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

        /* ── Thermal Roll Label Layout ── */
        @if($preset['type'] === 'thermal')
            @page {
                size: {{ $preset['width_mm'] }}mm {{ $preset['height_mm'] }}mm;
                margin: 0;
            }

            .print-container {
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 10px 0;
            }

            .label-item {
                width: {{ $preset['width_mm'] }}mm;
                height: {{ $preset['height_mm'] }}mm;
                padding: 1.5mm 2mm;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: space-between;
                text-align: center;
                background: #ffffff;
                overflow: hidden;
                page-break-after: always;
                break-after: page;
                margin-bottom: 4px; /* For screen preview */
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            }
        @else
            /* ── A4 Sheet Label Layout ── */
            @page {
                size: A4 portrait;
                margin: 8mm 6mm;
            }

            .print-container {
                display: grid;
                grid-template-columns: repeat({{ $preset['cols'] }}, {{ $preset['width_mm'] }}mm);
                gap: 1.5mm;
                justify-content: center;
                padding: 10px;
                background: #ffffff;
                max-width: 210mm;
                margin: 0 auto;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }

            .label-item {
                width: {{ $preset['width_mm'] }}mm;
                height: {{ $preset['height_mm'] }}mm;
                padding: 2mm;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: space-between;
                text-align: center;
                background: #ffffff;
                border: 1px dashed #e2e8f0;
                overflow: hidden;
            }
        @endif

        /* ── Label Content Styles ── */
        .store-name {
            font-size: {{ max(8, ($preset['font_size_px'] ?? 10) - 1) }}px;
            font-weight: 800;
            color: #000000;
            line-height: 1.1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 100%;
        }

        .product-name {
            font-size: {{ max(7, ($preset['font_size_px'] ?? 10) - 2) }}px;
            font-weight: 600;
            color: #000000;
            line-height: 1.15;
            max-height: 2.3em;
            overflow: hidden;
            width: 100%;
        }

        .barcode-wrapper {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-grow: 1;
            padding: 1px 0;
        }

        .barcode-wrapper svg {
            max-width: 100%;
            max-height: 100%;
        }

        .price-tag {
            font-size: {{ ($preset['font_size_px'] ?? 10) + 1 }}px;
            font-weight: 900;
            color: #000000;
            line-height: 1;
        }

        /* ── Print Media Optimization ── */
        @media print {
            body {
                background: transparent;
            }

            .no-print-bar {
                display: none !important;
            }

            .print-container {
                padding: 0 !important;
                box-shadow: none !important;
                margin: 0 !important;
                background: transparent !important;
            }

            .label-item {
                margin: 0 !important;
                box-shadow: none !important;
                border: none !important;
            }
        }
    </style>
</head>
<body>

    {{-- Top Action Toolbar --}}
    <div class="no-print-bar">
        <div>
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
