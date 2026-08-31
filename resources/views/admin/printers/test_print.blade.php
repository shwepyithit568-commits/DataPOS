<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('messages.printers_test_print') }} - {{ $printer->name }} - {{ $store->name }}</title>
    <style>
        @page {
            size: {{ $printer->is58mm() ? '58mm' : '80mm' }} auto;
            margin: 0;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Courier New", monospace;
            font-size: {{ $printer->is58mm() ? '11px' : '12px' }};
            color: #000;
            background: #fff;
            margin: 0;
            padding: 10px;
        }
        .thermal-receipt {
            max-width: {{ $printer->is58mm() ? '48mm' : '72mm' }};
            margin: 0 auto;
            text-align: center;
        }
        .store-title {
            font-size: 15px;
            font-weight: 900;
            text-transform: uppercase;
            margin: 0 0 4px 0;
        }
        .divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }
        .double-divider {
            border-top: 2px solid #000;
            margin: 8px 0;
        }
        .test-banner {
            background: #000;
            color: #fff;
            font-weight: 900;
            font-size: 13px;
            padding: 4px 0;
            margin: 6px 0;
            letter-spacing: 1px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            text-align: left;
            margin: 6px 0;
        }
        .info-table td {
            padding: 2px 0;
        }
        .info-table td:last-child {
            text-align: right;
            font-weight: 700;
        }
        .barcode-box {
            margin: 10px 0;
            font-family: monospace;
            font-weight: bold;
        }
        .barcode-bars {
            height: 35px;
            background: repeating-linear-gradient(
                90deg,
                #000 0px,
                #000 2px,
                #fff 2px,
                #fff 4px,
                #000 4px,
                #000 7px,
                #fff 7px,
                #fff 8px
            );
            margin: 4px auto;
            width: 80%;
        }
        .qr-placeholder {
            width: 80px;
            height: 80px;
            border: 2px solid #000;
            margin: 8px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
        }
        .feed-spacing {
            height: {{ ($printer->feed_lines ?? 2) * 12 }}px;
        }
        .cut-indicator {
            border-top: 1px dotted #666;
            margin-top: 10px;
            font-size: 10px;
            color: #666;
        }
        .btn-print {
            display: block;
            margin: 20px auto;
            background: #7c3aed;
            color: #fff;
            border: none;
            padding: 10px 24px;
            font-weight: 700;
            border-radius: 8px;
            cursor: pointer;
        }
        @media print {
            .btn-print {
                display: none;
            }
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>

<div class="thermal-receipt">

    {{-- Store Info --}}
    <h1 class="store-title">{{ $store->name }}</h1>
    <div style="font-size: 10px;">{{ $store->address ?? 'Myanmar Retail & Tech POS' }}</div>
    <div style="font-size: 10px;">Tel: {{ $store->phone ?? '-' }}</div>

    <div class="test-banner">*** TEST PRINT ***</div>

    {{-- Header text --}}
    @if($printer->header_text)
        <div style="font-size: 11px; font-style: italic; margin: 4px 0;">
            {{ $printer->header_text }}
        </div>
    @endif

    <div class="divider"></div>

    {{-- Hardware Info Table --}}
    <table class="info-table">
        <tr>
            <td>Printer Name:</td>
            <td>{{ $printer->name }}</td>
        </tr>
        <tr>
            <td>Connection:</td>
            <td>{{ strtoupper($printer->connection_type) }}</td>
        </tr>
        <tr>
            <td>Paper Width:</td>
            <td>{{ $printer->paper_width }}</td>
        </tr>
        <tr>
            <td>Role:</td>
            <td>{{ ucwords($printer->printer_role) }}</td>
        </tr>
        @if($printer->isNetwork() && $printer->ip_address)
            <tr>
                <td>IP Address:</td>
                <td>{{ $printer->ip_address }}:{{ $printer->port }}</td>
            </tr>
        @endif
        <tr>
            <td>Auto Cutter:</td>
            <td>{{ $printer->auto_cut ? 'ENABLED' : 'DISABLED' }}</td>
        </tr>
        <tr>
            <td>Drawer Kick:</td>
            <td>{{ $printer->cash_drawer_kick ? 'ENABLED' : 'DISABLED' }}</td>
        </tr>
        <tr>
            <td>Print Date:</td>
            <td>{{ now()->format('Y-m-d H:i') }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    {{-- Alignment Test Pattern --}}
    <div style="font-size: 10px; font-weight: bold; margin-bottom: 2px;">ALIGNMENT & DENSITY TEST</div>
    <div style="font-family: monospace; font-size: 10px;">
        123456789012345678901234567890<br>
        ==============================<br>
        LEFT .......... CENTER .......... RIGHT
    </div>

    <div class="divider"></div>

    {{-- Test Barcode --}}
    <div class="barcode-box">
        <div style="font-size: 10px;">CODE128 TEST</div>
        <div class="barcode-bars"></div>
        <div style="font-size: 10px; font-family: monospace;">*DATAPOS-TEST-8899*</div>
    </div>

    {{-- Test QR Code Box --}}
    <div style="font-size: 10px; font-weight: bold; margin-top: 6px;">QR CODE TEST</div>
    <div class="qr-placeholder">
        [ QR OK ]
    </div>

    <div class="double-divider"></div>

    @if($printer->footer_text)
        <div style="font-size: 10px; margin: 4px 0;">
            {{ $printer->footer_text }}
        </div>
    @endif

    <div style="font-size: 10px; font-weight: bold; margin-top: 4px;">
        *** HARDWARE OK ***
    </div>

    {{-- Paper feed lines spacing --}}
    <div class="feed-spacing"></div>

    @if($printer->auto_cut)
        <div class="cut-indicator">
            ✂ - - - - - - - - - - - - - - - - - ✂ (AUTO CUT)
        </div>
    @endif

</div>

<button type="button" class="btn-print" onclick="window.print()">
    🖨️ {{ __('messages.printers_test_print') }}
</button>

</body>
</html>
