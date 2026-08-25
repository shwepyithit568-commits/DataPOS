<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>E-Load Voucher #{{ $transaction->ref_no }}</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 4mm;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Noto Sans Myanmar", Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 4px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .divider {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }
        .double-divider {
            border-top: 2px dashed #000;
            margin: 8px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td {
            padding: 2px 0;
            vertical-align: top;
        }
        .badge {
            display: inline-block;
            padding: 1px 6px;
            font-size: 11px;
            font-weight: bold;
            border: 1px solid #000;
            border-radius: 4px;
            text-transform: uppercase;
        }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="text-center">
        <h2 style="margin: 0 0 2px 0; font-size: 16px;">{{ $store->name }}</h2>
        <div style="font-size: 11px;">{{ __('messages.eload_voucher_title') }}</div>
    </div>

    <div class="divider"></div>

    <table>
        <tr>
            <td class="font-bold">{{ __('messages.eload_ref_no') }}:</td>
            <td class="text-right">{{ $transaction->ref_no }}</td>
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
            <td class="text-right font-bold" style="font-size: 14px;">{{ $transaction->phone_number }}</td>
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

    <table style="font-size: 14px;">
        <tr class="font-bold">
            <td>{{ __('messages.total_amount') }}:</td>
            <td class="text-right">{{ number_format($transaction->amount, 0) }} Ks</td>
        </tr>
    </table>

    <div class="divider"></div>

    <div class="text-center" style="font-size: 11px; margin-top: 8px;">
        <div>{{ __('messages.thank_you_come_again') }}</div>
        <div style="font-size: 9px; color: #555; margin-top: 4px;">{{ config('app.name', 'DataPOS') }} System</div>
    </div>
</body>
</html>
