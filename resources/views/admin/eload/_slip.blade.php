@php
    $tmpl = $voucherTemplate ?? null;
    $storeName = data_get($tmpl, 'header_title') ?: $store->name;
    $storeSubtitle = data_get($tmpl, 'header_subtitle');
    $storePhone = data_get($tmpl, 'phone') ?: ($store->setting?->phone ?? $store->phone);
    $storeAddress = data_get($tmpl, 'address') ?: ($store->setting?->address ?? $store->address);
    $showLogo = (bool) data_get($tmpl, 'show_logo', true);
    $templateLogo = ($tmpl instanceof \App\Models\VoucherTemplate) ? $tmpl->logoUrl() : null;
    $logoUrl = $showLogo ? ($templateLogo ?? ($store->setting?->adminLogo() ? asset('storage/' . $store->setting->adminLogo()) : null)) : null;
    $footerGreeting = data_get($tmpl, 'footer_greeting') ?: __('messages.thank_you_come_again');
    $footerPolicy = data_get($tmpl, 'footer_policy');

    $paperSize = strtolower((string) request('paper_size', '80mm'));
    if (!in_array($paperSize, ['58mm', '80mm'], true)) {
        $paperSize = '80mm';
    }
    $is58 = $paperSize === '58mm';

    $myanmarFontUrl = null;
    try {
        $myanmarFontUrl = \Illuminate\Support\Facades\Vite::asset('resources/assets/fonts/NotoSansMyanmar/NotoSansMyanmar-Regular.ttf');
    } catch (\Throwable $e) {
        $myanmarFontUrl = null;
    }
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>E-Load Voucher #{{ $transaction->ref_no }} — {{ $storeName }}</title>
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
            font-family: 'Noto Sans Myanmar', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
        }

        @page {
            size: {{ $is58 ? '58mm' : '80mm' }} auto;
            margin: 0;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #f1f5f9;
            color: #000;
            font-size: {{ $is58 ? '10px' : '11.5px' }};
            line-height: 1.4;
        }

        .slip-container {
            width: {{ $is58 ? '58mm' : '80mm' }};
            max-width: {{ $is58 ? '58mm' : '80mm' }};
            margin: 16px auto;
            background: #fff;
            padding: {{ $is58 ? '3mm 3mm' : '4mm 5mm' }};
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border-radius: 4px;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }

        .divider {
            border-top: 1px dashed #000;
            margin: {{ $is58 ? '4px 0' : '6px 0' }};
        }
        .double-divider {
            border-top: 2px dashed #000;
            margin: {{ $is58 ? '6px 0' : '8px 0' }};
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }
        td {
            padding: {{ $is58 ? '1.5px 0' : '2px 0' }};
            vertical-align: top;
        }

        .badge {
            display: inline-block;
            padding: 1px 5px;
            font-size: {{ $is58 ? '9.5px' : '10.5px' }};
            font-weight: bold;
            border: 1px solid #000;
            border-radius: 3px;
            text-transform: uppercase;
        }

        /* Toolbar Controls */
        .slip-toolbar {
            max-width: {{ $is58 ? '58mm' : '80mm' }};
            margin: 12px auto 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
        }

        .btn-tool {
            padding: 5px 10px;
            font-size: 11px;
            font-weight: 700;
            border-radius: 6px;
            cursor: pointer;
            border: 1px solid transparent;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-print {
            background: #4338ca;
            color: #fff;
            flex: 1;
            justify-content: center;
        }
        .btn-size {
            background: #e2e8f0;
            color: #334155;
            border-color: #cbd5e1;
        }
        .btn-size.active {
            background: #1e1b4b;
            color: #fff;
        }

        @media print {
            html, body {
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .no-print, .slip-toolbar {
                display: none !important;
            }
            .slip-container {
                box-shadow: none !important;
                border-radius: 0 !important;
                margin: 0 auto !important;
                padding: {{ $is58 ? '2mm 3mm' : '3mm 5mm' }} !important;
                width: {{ $is58 ? '58mm' : '80mm' }} !important;
                max-width: {{ $is58 ? '58mm' : '80mm' }} !important;
            }
        }
    </style>
</head>
<body>

    {{-- Top Action Toolbar for Quick Size Selection & Print --}}
    <div class="slip-toolbar no-print">
        <div style="display: flex; gap: 4px;">
            <a href="?paper_size=80mm" class="btn-tool btn-size {{ !$is58 ? 'active' : '' }}">80mm</a>
            <a href="?paper_size=58mm" class="btn-tool btn-size {{ $is58 ? 'active' : '' }}">58mm</a>
        </div>
        <button type="button" class="btn-tool btn-print" id="btnPrint">
            🖨️ {{ __('messages.print') }}
        </button>
    </div>

    <div class="slip-container">
        {{-- Store Branding --}}
        <div class="text-center">
            @if ($logoUrl)
                <div style="margin-bottom: 4px;">
                    <img src="{{ $logoUrl }}" alt="{{ $storeName }}" style="max-height: {{ $is58 ? '32px' : '40px' }}; max-width: {{ $is58 ? '110px' : '140px' }}; object-fit: contain; margin: 0 auto; display: block;">
                </div>
            @endif
            <h2 style="margin: 0 0 2px 0; font-size: {{ $is58 ? '13px' : '15px' }}; font-weight: 800;">{{ $storeName }}</h2>
            @if ($storeSubtitle)
                <div style="font-size: {{ $is58 ? '9px' : '10px' }}; color: #555;">{{ $storeSubtitle }}</div>
            @endif
            @if ($storePhone || $storeAddress)
                <div style="font-size: {{ $is58 ? '8.5px' : '9.5px' }}; color: #666; margin-top: 1px;">
                    @if ($storePhone) {{ $storePhone }} @endif
                    @if ($storeAddress) | {{ $storeAddress }} @endif
                </div>
            @endif
            <div style="font-size: {{ $is58 ? '10px' : '11px' }}; font-weight: 700; margin-top: 2px;">
                {{ __('messages.eload_voucher_title') }}
            </div>
        </div>

        <div class="divider"></div>

        <table>
            <tr>
                <td class="font-bold">{{ __('messages.eload_ref_no') }}:</td>
                <td class="text-right font-mono font-bold">{{ $transaction->ref_no }}</td>
            </tr>
            <tr>
                <td>{{ __('messages.date') }}:</td>
                <td class="text-right">{{ $transaction->occurred_at->format('d/m/Y h:i A') }}</td>
            </tr>
            @if($transaction->cashier)
            <tr>
                <td>{{ __('messages.cashier') }}:</td>
                <td class="text-right">{{ $transaction->cashier->name }}</td>
            </tr>
            @endif
            @if($transaction->customer_name)
            <tr>
                <td>{{ __('messages.customer') }}:</td>
                <td class="text-right">{{ $transaction->customer_name }}</td>
            </tr>
            @endif
        </table>

        <div class="divider"></div>

        <table>
            <tr>
                <td class="font-bold">{{ __('messages.eload_operator') }}:</td>
                <td class="text-right"><span class="badge">{{ strtoupper($transaction->operator) }}</span></td>
            </tr>
            <tr>
                <td class="font-bold">{{ __('messages.phone_number') }}:</td>
                <td class="text-right font-bold font-mono" style="font-size: {{ $is58 ? '12px' : '13.5px' }};">
                    {{ $transaction->phone_number }}
                </td>
            </tr>
            <tr>
                <td>{{ __('messages.type') }}:</td>
                <td class="text-right">{{ $transaction->typeLabel() }}</td>
            </tr>
            @if($transaction->package_name)
            <tr>
                <td>{{ __('messages.package') }}:</td>
                <td class="text-right">{{ $transaction->package_name }}</td>
            </tr>
            @endif
            <tr>
                <td>{{ __('messages.payment_method') }}:</td>
                <td class="text-right" style="text-transform: uppercase;">{{ $transaction->payment_method }}</td>
            </tr>
        </table>

        <div class="double-divider"></div>

        <table style="font-size: {{ $is58 ? '12px' : '13.5px' }};">
            <tr class="font-bold">
                <td>{{ __('messages.total_amount') }}:</td>
                <td class="text-right font-mono">{{ format_currency($transaction->amount, $store) }}</td>
            </tr>
        </table>

        <div class="divider"></div>

        <div class="text-center" style="font-size: {{ $is58 ? '9.5px' : '10.5px' }}; margin-top: 6px;">
            <div>{{ $footerGreeting }}</div>
            @if ($footerPolicy)
                <div style="font-size: 8.5px; color: #555; margin-top: 3px; white-space: pre-line;">{{ $footerPolicy }}</div>
            @endif
            <div style="font-size: 8px; color: #777; margin-top: 4px;">{{ config('app.name', 'DataPOS') }} E-Load System</div>
        </div>
    </div>

    <script nonce="{{ $cspNonce ?? '' }}">
        document.addEventListener('DOMContentLoaded', function() {
            var printBtn = document.getElementById('btnPrint');
            if (printBtn) {
                printBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.print();
                });
            }
        });
    </script>
</body>
</html>
