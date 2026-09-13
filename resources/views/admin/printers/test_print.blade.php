@php
    $myanmarFontUrl = null;
    try {
        $myanmarFontUrl = \Illuminate\Support\Facades\Vite::asset('resources/assets/fonts/NotoSansMyanmar/NotoSansMyanmar-Regular.ttf');
    } catch (\Throwable $e) {
        $myanmarFontUrl = null;
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ __('messages.printers_test_print') }} — {{ $printer->name }} — {{ $store->name }}</title>
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
            size: {{ $printer->is58mm() ? '58mm' : '80mm' }} auto;
            margin: 0;
        }
        * {
            box-sizing: border-box;
            font-family: 'Noto Sans Myanmar', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Courier New", monospace;
        }
        body {
            font-size: {{ $printer->is58mm() ? '10px' : '11.5px' }};
            color: #000;
            background: #fff;
            margin: 0;
            padding: 10px;
        }
        .thermal-receipt {
            width: {{ $printer->is58mm() ? '48mm' : '72mm' }};
            max-width: {{ $printer->is58mm() ? '48mm' : '72mm' }};
            margin: 0 auto;
            text-align: center;
        }
        .store-title {
            font-size: {{ $printer->is58mm() ? '13px' : '15px' }};
            font-weight: 900;
            text-transform: uppercase;
            margin: 0 0 3px 0;
        }
        .divider {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }
        .double-divider {
            border-top: 2px solid #000;
            margin: 6px 0;
        }
        .test-banner {
            background: #000;
            color: #fff;
            font-weight: 900;
            font-size: {{ $printer->is58mm() ? '11px' : '12px' }};
            padding: 3px 0;
            margin: 5px 0;
            letter-spacing: 0.5px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: {{ $printer->is58mm() ? '9.5px' : '10.5px' }};
            text-align: left;
            margin: 5px 0;
        }
        .info-table td {
            padding: 1.5px 0;
        }
        .info-table td:last-child {
            text-align: right;
            font-weight: 700;
        }
        .barcode-box {
            margin: 8px 0;
            font-family: monospace;
            font-weight: bold;
        }
        .barcode-bars {
            height: 32px;
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
            width: 85%;
        }
        .qr-placeholder {
            width: 70px;
            height: 70px;
            border: 2px solid #000;
            margin: 6px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9.5px;
            font-weight: bold;
        }
        .feed-spacing {
            height: {{ ($printer->feed_lines ?? 2) * 10 }}px;
        }
        .cut-indicator {
            border-top: 1px dotted #666;
            margin-top: 8px;
            font-size: 9px;
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
            .btn-print, .hardware-toolbar {
                display: none !important;
            }
            body {
                padding: 0 !important;
                background: #fff !important;
            }
        }
    </style>
</head>
<body>

<div class="thermal-receipt">

    {{-- Store Info --}}
    <h1 class="store-title">{{ $store->name }}</h1>
    <div style="font-size: 9.5px;">{{ $store->address ?? 'Myanmar Retail & Tech POS' }}</div>
    <div style="font-size: 9.5px;">Tel: {{ $store->phone ?? '-' }}</div>

    <div class="test-banner">*** TEST PRINT ***</div>

    {{-- Header text --}}
    @if($printer->header_text)
        <div style="font-size: 10px; font-style: italic; margin: 3px 0;">
            {{ $printer->header_text }}
        </div>
    @endif

    <div class="divider"></div>

    {{-- Hardware Info Table --}}
    <table class="info-table">
        <tr>
            <td>{{ __('messages.printer_name') }}</td>
            <td>{{ $printer->name }}</td>
        </tr>
        <tr>
            <td>{{ __('messages.connection') }}</td>
            <td>{{ strtoupper($printer->connection_type) }}</td>
        </tr>
        <tr>
            <td>{{ __('messages.paper_width') }}</td>
            <td>{{ $printer->paper_width }}</td>
        </tr>
        <tr>
            <td>{{ __('messages.role') }}</td>
            <td>{{ ucwords($printer->printer_role) }}</td>
        </tr>
        @if($printer->isNetwork() && $printer->ip_address)
            <tr>
                <td>{{ __('messages.ip_address') }}</td>
                <td>{{ $printer->ip_address }}:{{ $printer->port }}</td>
            </tr>
        @endif
        <tr>
            <td>{{ __('messages.auto_cutter') }}</td>
            <td>{{ $printer->auto_cut ? 'ENABLED' : 'DISABLED' }}</td>
        </tr>
        <tr>
            <td>{{ __('messages.drawer_kick') }}</td>
            <td>{{ $printer->cash_drawer_kick ? 'ENABLED' : 'DISABLED' }}</td>
        </tr>
        <tr>
            <td>{{ __('messages.print_date') }}</td>
            <td>{{ now()->format('Y-m-d H:i') }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    {{-- Alignment Test Pattern --}}
    <div style="font-size: 9.5px; font-weight: bold; margin-bottom: 2px;">{{ __('messages.alignment_density_test') }}</div>
    @if($printer->is58mm())
        <div style="font-family: monospace; font-size: 9px; letter-spacing: -0.3px;">
            12345678901234567890123456789012<br>
            ================================<br>
            L..........CENTER..........R
        </div>
    @else
        <div style="font-family: monospace; font-size: 9.5px;">
            123456789012345678901234567890123456789012<br>
            ==========================================<br>
            LEFT ............ CENTER ............ RIGHT
        </div>
    @endif

    <div class="divider"></div>

    {{-- Test Barcode --}}
    <div class="barcode-box">
        <div style="font-size: 9.5px;">{{ __('messages.code128_test') }}</div>
        <div class="barcode-bars"></div>
        <div style="font-size: 9.5px; font-family: monospace;">*DATAPOS-TEST-8899*</div>
    </div>

    {{-- Test QR Code Box --}}
    <div style="font-size: 9.5px; font-weight: bold; margin-top: 4px;">QR CODE TEST</div>
    <div class="qr-placeholder">
        [ QR OK ]
    </div>

    <div class="double-divider"></div>

    @if($printer->footer_text)
        <div style="font-size: 9.5px; margin: 3px 0;">
            {{ $printer->footer_text }}
        </div>
    @endif

    <div style="font-size: 9.5px; font-weight: bold; margin-top: 3px;">
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

{{-- Hardware Diagnostic Actions Toolbar (Hidden on actual print) --}}
<div style="max-width: 480px; margin: 20px auto; padding: 15px; border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc; font-family: sans-serif;" class="hardware-toolbar">
    <div style="display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;">
        <button type="button" id="btnPrint" style="background: #2563eb; color: #fff; border: none; padding: 8px 16px; font-weight: bold; border-radius: 6px; cursor: pointer;">
            🖨️ {{ __('messages.printers_test_print') }}
        </button>
        <a href="{{ route('store.admin.printers.escpos_bin', ['store_slug' => $store->slug, 'printer' => $printer->id]) }}"
           style="display: inline-block; background: #059669; color: #fff; text-decoration: none; padding: 8px 16px; font-weight: bold; border-radius: 6px;">
            💾 Download ESC/POS .bin
        </a>
    </div>

    {{-- Barcode Scanner Speed & Latency Test Widget --}}
    <div style="margin-top: 15px; padding-top: 12px; border-top: 1px dashed #cbd5e1; text-align: left;" id="scanner-test-section">
        <label style="display: block; font-size: 11px; font-weight: bold; text-transform: uppercase; color: #64748b; margin-bottom: 4px;">
            🔍 Barcode Scanner Diagnostic (USB / 2.4G HID)
        </label>
        <input type="text" id="barcode-diagnostic-input" placeholder="Click here and scan any barcode..."
               style="width: 100%; box-sizing: border-box; padding: 8px 10px; font-family: monospace; font-size: 12px; font-weight: bold; border: 1px solid #94a3b8; border-radius: 6px;">
        <div id="barcode-diagnostic-result" style="margin-top: 6px; font-size: 11px; font-weight: 600; color: #334155; display: none;"></div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
(function() {
    var printBtn = document.getElementById('btnPrint');
    if (printBtn) {
        printBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.print();
        });
    }

    var input = document.getElementById('barcode-diagnostic-input');
    var res = document.getElementById('barcode-diagnostic-result');
    if (!input || !res) return;

    var firstCharTime = 0;
    var charCount = 0;

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            var totalTime = Date.now() - firstCharTime;
            var val = input.value.trim();
            if (val.length > 0) {
                var charsPerSec = totalTime > 0 ? Math.round((val.length / (totalTime / 1000))) : 999;
                var isFast = totalTime <= 100 || charsPerSec >= 50;
                res.style.display = 'block';
                res.innerHTML = '<span style="color: ' + (isFast ? '#059669' : '#d97706') + '; font-weight: bold;">' +
                    (isFast ? '✅ Fast HID Scanner Detected' : '⚠️ Manual Keyboard Input Detected') + '</span> — ' +
                    'Scanned: <code>' + val + '</code> (' + val.length + ' chars in ' + totalTime + 'ms, ~' + charsPerSec + ' chars/sec, Enter CR: OK)';
            }
            input.value = '';
            firstCharTime = 0;
            charCount = 0;
            return;
        }

        if (firstCharTime === 0) {
            firstCharTime = Date.now();
            charCount = 0;
        }
        charCount++;
    });
})();
</script>

</body>
</html>
