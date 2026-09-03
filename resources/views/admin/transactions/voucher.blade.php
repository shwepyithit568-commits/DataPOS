<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher - {{ $transaction->transaction_number }} - {{ $store->name }}</title>
    <style>
        @page {
            size: A5 portrait;
            margin: 10mm;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 13px;
            color: #1e293b;
            background: #fff;
            margin: 0;
            padding: 20px;
        }
        .voucher-box {
            max-width: 550px;
            margin: 0 auto;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
        }
        .header {
            text-align: center;
            border-bottom: 2px dashed #cbd5e1;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }
        .store-name {
            font-size: 20px;
            font-weight: 800;
            margin: 0 0 4px 0;
            text-transform: uppercase;
        }
        .voucher-title {
            font-size: 14px;
            font-weight: 700;
            color: #475569;
            margin: 0;
        }
        .meta-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 13px;
        }
        .meta-label {
            color: #64748b;
            font-weight: 600;
        }
        .meta-value {
            font-weight: 700;
            font-family: monospace;
        }
        .amount-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            margin: 20px 0;
        }
        .amount-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            margin-bottom: 4px;
            font-weight: 700;
        }
        .amount-num {
            font-size: 24px;
            font-weight: 800;
            font-family: monospace;
            color: #0f172a;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            padding-top: 10px;
        }
        .sig-block {
            text-align: center;
            width: 40%;
            border-top: 1px solid #94a3b8;
            padding-top: 6px;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
        }
        .btn-print {
            display: block;
            margin: 20px auto 0;
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
            .voucher-box {
                border: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>

<div class="voucher-box">
    <div class="header">
        <h1 class="store-name">{{ $store->name }}</h1>
        <p class="voucher-title">
            @if($transaction->type === 'deposit')
                RECEIPT VOUCHER (ငွေရပြေစာ)
            @elseif($transaction->type === 'withdrawal')
                PAYMENT VOUCHER (ငွေပေးပြေစာ)
            @else
                FUND TRANSFER VOUCHER (ငွေလွှဲပြေစာ)
            @endif
        </p>
    </div>

    <div class="meta-row">
        <span class="meta-label">Voucher No:</span>
        <span class="meta-value">{{ $transaction->transaction_number }}</span>
    </div>
    <div class="meta-row">
        <span class="meta-label">Date & Time:</span>
        <span class="meta-value">{{ $transaction->transaction_date->format('Y-m-d h:i A') }}</span>
    </div>
    <div class="meta-row">
        <span class="meta-label">Transaction Type:</span>
        <span class="meta-value">{{ strtoupper($transaction->type) }}</span>
    </div>

    @if($transaction->fromAccount)
        <div class="meta-row">
            <span class="meta-label">From Account:</span>
            <span class="meta-value">{{ $transaction->fromAccount->name }}</span>
        </div>
    @endif

    @if($transaction->toAccount)
        <div class="meta-row">
            <span class="meta-label">To Account:</span>
            <span class="meta-value">{{ $transaction->toAccount->name }}</span>
        </div>
    @endif

    @if($transaction->category)
        <div class="meta-row">
            <span class="meta-label">Category / Purpose:</span>
            <span class="meta-value">{{ ucwords(str_replace('_', ' ', $transaction->category)) }}</span>
        </div>
    @endif

    @if($transaction->payer_or_payee)
        <div class="meta-row">
            <span class="meta-label">Payer / Payee:</span>
            <span class="meta-value">{{ $transaction->payer_or_payee }}</span>
        </div>
    @endif

    @if($transaction->reference_no)
        <div class="meta-row">
            <span class="meta-label">Reference / Slip #:</span>
            <span class="meta-value">{{ $transaction->reference_no }}</span>
        </div>
    @endif

    <div class="amount-box">
        <div class="amount-title">{{ __('messages.transactions_amount') }}</div>
        <div class="amount-num">{{ format_currency($transaction->amount, $store) }}</div>
        @if((float) $transaction->fee > 0)
            <div style="font-size: 12px; color: #64748b; margin-top: 4px;">
                + {{ __('messages.transactions_fee') }}: {{ format_currency($transaction->fee, $store) }}
            </div>
        @endif
    </div>

    @if($transaction->notes)
        <div style="margin-bottom: 20px;">
            <span class="meta-label">Notes:</span>
            <p style="margin: 4px 0 0 0; color: #334155; font-size: 12px;">{{ $transaction->notes }}</p>
        </div>
    @endif

    <div class="signatures">
        <div class="sig-block">
            Prepared By ({{ $transaction->recorder?->name ?? 'Cashier' }})
        </div>
        <div class="sig-block">
            Authorized / Received By
        </div>
    </div>
</div>

<button type="button" class="btn-print" onclick="window.print()">
    🖨️ Print Voucher
</button>

</body>
</html>
